<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class FarmService
{
    private Database $db;
    private array $memo = [];
    private int $memoVer = -1;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    private function getMemo(string $key): ?array
    {
        $v = $this->db->getMutationVersion();
        if ($this->memoVer !== $v) {
            $this->memo = [];
            $this->memoVer = $v;
        }
        return $this->memo[$key] ?? null;
    }

    private function setMemo(string $key, array $value): array
    {
        $this->memoVer = $this->db->getMutationVersion();
        $this->memo[$key] = $value;
        return $value;
    }

    public function getDatabase(): Database
    {
        return $this->db;
    }

    /**
     * Enrich all incubation batches with candling totals, expenses, breakout metrics, and calculated costs.
     */
    public function getIncubationBatchesEnriched(): array
    {
        if (($cached = $this->getMemo('inc')) !== null) {
            return $cached;
        }
        $batches = $this->db->getTable('incubation_batches');
        $logs = $this->db->getTable('incubation_daily_logs');
        $expenses = $this->db->getTable('incubation_expenses');
        $breakouts = $this->db->getTable('hatch_breakouts');
        $prodBatches = $this->db->getTable('production_batches');

        $enriched = [];
        foreach ($batches as $batch) {
            $batchId = (int) $batch['id'];

            $batchLogs = array_values(array_filter($logs, fn($l) => (int) $l['incubation_batch_id'] === $batchId));
            $batchExpenses = array_values(array_filter($expenses, fn($e) => (int) $e['incubation_batch_id'] === $batchId));

            $totalInfertiles = 0;
            $totalEarlyDeaths = 0;
            $totalCracked = 0;
            $totalCandlingDis = 0;

            foreach ($batchLogs as $log) {
                $totalInfertiles += (int) $log['infertiles'];
                $totalEarlyDeaths += (int) $log['early_deaths'];
                $totalCracked += (int) $log['cracked'];
                $totalCandlingDis += (int) $log['dead_in_shell'];
            }

            $totalRemovedCandling = $totalInfertiles + $totalEarlyDeaths + $totalCracked + $totalCandlingDis;
            $viableEggsRemaining = max(0, (int) $batch['eggs_set'] - $totalRemovedCandling);

            $gasCost = 0.0;
            $gasWeightKg = 0.0;
            $repairCost = 0.0;

            foreach ($batchExpenses as $exp) {
                if ($exp['expense_type'] === 'gas') {
                    $gasCost += (float) $exp['amount'];
                    $gasWeightKg += (float) $exp['gas_weight_kg'];
                } else {
                    $repairCost += (float) $exp['amount'];
                }
            }

            $eggCost = (float) $batch['egg_cost'];
            $totalIncubationCost = $eggCost + $gasCost + $repairCost;

            $breakout = null;
            foreach ($breakouts as $b) {
                if ((int) $b['incubation_batch_id'] === $batchId) {
                    $breakout = $b;
                    break;
                }
            }

            $eggsSet = max(1, (int) $batch['eggs_set']);
            $fertilityRate = round((($eggsSet - $totalInfertiles) / $eggsSet) * 100, 2);

            $liveChicks = $breakout ? (int) $breakout['live_chicks'] : 0;
            $hatchabilityRate = $breakout
                ? (float) $breakout['hatchability_rate']
                : 0.0;
            $costPerChick = ($liveChicks > 0)
                ? round($totalIncubationCost / $liveChicks, 2)
                : round($totalIncubationCost / $eggsSet, 2);

            if ($breakout) {
                $fertilityRate = (float) $breakout['fertility_rate'];
                $costPerChick = $liveChicks > 0 ? round($totalIncubationCost / $liveChicks, 2) : (float) $breakout['cost_per_chick'];
            }

            // Linked production batch
            $linkedProdBatch = null;
            foreach ($prodBatches as $pb) {
                if ((int) ($pb['incubation_batch_id'] ?? 0) === $batchId) {
                    $linkedProdBatch = $pb;
                    break;
                }
            }

            // Species & dynamic Setter/Hatcher timeline
            $speciesProfiles = VeterinaryKnowledgeBase::getIncubationSpeciesProfiles();
            $speciesKey = strtolower((string) ($batch['species'] ?? ''));
            if ($speciesKey === '' || !isset($speciesProfiles[$speciesKey])) {
                $infoLower = strtolower((string) ($batch['parent_flock_info'] ?? ''));
                if (str_contains($infoLower, 'turkey')) {
                    $speciesKey = 'turkey';
                } elseif (str_contains($infoLower, 'muscovy')) {
                    $speciesKey = 'muscovy_duck';
                } elseif (str_contains($infoLower, 'duck')) {
                    $speciesKey = 'duck';
                } elseif (str_contains($infoLower, 'guinea')) {
                    $speciesKey = 'guinea_fowl';
                } elseif (str_contains($infoLower, 'quail')) {
                    $speciesKey = 'quail';
                } elseif (str_contains($infoLower, 'goose')) {
                    $speciesKey = 'goose';
                } elseif (str_contains($infoLower, 'ostrich')) {
                    $speciesKey = 'ostrich';
                } else {
                    $speciesKey = 'chicken';
                }
            }
            $profile = $speciesProfiles[$speciesKey];
            $totalDays = (int) $profile['total_days'];
            $transferDay = (int) $profile['transfer_day'];

            // Days in incubator
            $startTs = strtotime($batch['start_date']);
            $daysElapsed = max(1, (int) floor((time() - $startTs) / 86400) + 1);
            $incubationDay = min($totalDays, $daysElapsed);
            $currentStage = ($incubationDay <= $transferDay) ? 'Setter' : 'Hatcher';
            $isTransferDue = ($daysElapsed === $transferDay && $batch['status'] === 'Active');
            $isBreakoutDue = ($daysElapsed >= $totalDays && $batch['status'] === 'Active');
            $expectedTransferDate = date('Y-m-d', strtotime($batch['start_date'] . " + {$transferDay} days"));
            $expectedHatchDate = date('Y-m-d', strtotime($batch['start_date'] . " + {$totalDays} days"));

            $batch['species'] = $speciesKey;
            $batch['species_label'] = $profile['label'];
            $batch['species_profile'] = $profile;
            $batch['setter_id'] = $batch['setter_id'] ?? 'Setter A';
            $batch['ownership_type'] = $batch['ownership_type'] ?? 'Farm';
            $batch['is_client_service'] = ($batch['ownership_type'] === 'Client');
            $batch['client_name'] = (string) ($batch['client_name'] ?? '');
            $batch['client_phone'] = (string) ($batch['client_phone'] ?? '');
            $batch['service_fee'] = (float) ($batch['service_fee'] ?? 0);
            $batch['amount_paid'] = (float) ($batch['amount_paid'] ?? 0);
            $batch['balance_due'] = isset($batch['balance_due'])
                ? (float) $batch['balance_due']
                : max(0.0, $batch['service_fee'] - $batch['amount_paid']);
            $batch['payment_status'] = $batch['payment_status'] ?? ($batch['balance_due'] <= 0 ? 'Paid' : ($batch['amount_paid'] > 0 ? 'Partial' : 'Pending'));
            $batch['current_stage'] = $currentStage;
            $batch['total_days'] = $totalDays;
            $batch['transfer_day'] = $transferDay;
            $batch['is_transfer_due'] = $isTransferDue;
            $batch['is_breakout_due'] = $isBreakoutDue;
            $batch['expected_transfer_date'] = $expectedTransferDate;
            $batch['expected_hatch_date'] = $expectedHatchDate;
            $batch['logs'] = $batchLogs;
            $batch['expenses'] = $batchExpenses;
            $batch['breakout'] = $breakout;
            $batch['linked_production_batch'] = $linkedProdBatch;
            $batch['incubation_day'] = $incubationDay;
            $batch['metrics'] = [
                'infertiles' => $totalInfertiles,
                'early_deaths' => $totalEarlyDeaths,
                'cracked' => $totalCracked,
                'candling_dis' => $totalCandlingDis,
                'total_removed' => $totalRemovedCandling,
                'viable_eggs_remaining' => $viableEggsRemaining,
                'gas_cost' => $gasCost,
                'gas_weight_kg' => $gasWeightKg,
                'repair_cost' => $repairCost,
                'total_incubation_cost' => $totalIncubationCost,
                'fertility_rate' => $fertilityRate,
                'hatchability_rate' => $hatchabilityRate,
                'live_chicks' => $liveChicks,
                'cost_per_chick' => $costPerChick,
            ];

            $enriched[] = $batch;
        }

        // Newest first
        usort($enriched, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);
        return $this->setMemo('inc', $enriched);
    }

    /**
     * Enrich all inventory items with gram/ml conversion metrics, kg equivalents, and stock percentage.
     */
    public function getInventoryItemsEnriched(): array
    {
        if (($cached = $this->getMemo('inv')) !== null) {
            return $cached;
        }
        $items = $this->db->getTable('inventory_items');
        $prodLogs = $this->db->getTable('production_daily_logs');

        // Calculate average daily consumption per item across logs
        $consumedByItem = [];
        foreach ($prodLogs as $log) {
            $fId = (int) ($log['feed_item_id'] ?? 0);
            if ($fId > 0) {
                $consumedByItem[$fId] = ($consumedByItem[$fId] ?? 0.0) + (float) $log['feed_grams'];
            }
            $mId = (int) ($log['med_item_id'] ?? 0);
            if ($mId > 0 && empty($log['medications_used'])) {
                $consumedByItem[$mId] = ($consumedByItem[$mId] ?? 0.0) + (float) $log['med_dose_g_ml'];
            }
            if (!empty($log['medications_used']) && is_array($log['medications_used'])) {
                foreach ($log['medications_used'] as $mu) {
                    $muId = (int) ($mu['item_id'] ?? 0);
                    if ($muId > 0) {
                        $consumedByItem[$muId] = ($consumedByItem[$muId] ?? 0.0) + (float) ($mu['dose'] ?? 0);
                    }
                }
            }
        }

        $enriched = [];
        foreach ($items as $item) {
            $id = (int) $item['id'];
            $initial = max(0.0001, (float) ($item['initial_quantity_g_or_ml'] ?? $item['total_quantity_g_or_ml']));
            $remaining = (float) $item['total_quantity_g_or_ml'];
            $unitCost = (float) $item['unit_cost_per_g_or_ml'];
            $stockPct = min(100.0, max(0.0, round(($remaining / $initial) * 100, 1)));

            $isFeed = in_array($item['category'], ['starter', 'grower', 'layer'], true);
            $unitLabel = $isFeed ? 'g' : ($item['unit_type'] ?? 'ml');
            $remainingKgOrL = round($remaining / 1000, 4);
            $initialKgOrL = round($initial / 1000, 4);
            $remainingValue = round($remaining * $unitCost, 2);

            $item['unit_label'] = $unitLabel;
            $item['bulk_unit_label'] = $unitLabel === 'g' ? 'kg' : 'L';
            $item['remaining_bulk'] = $remainingKgOrL;
            $item['initial_bulk'] = $initialKgOrL;
            $item['stock_pct'] = $stockPct;
            $item['remaining_value'] = $remainingValue;
            $item['cost_per_kg_or_l'] = round($unitCost * 1000, 2);
            $item['cost_per_50kg_bag'] = round($unitCost * 50000, 2);
            $item['total_consumed'] = $consumedByItem[$id] ?? 0.0;
            $item['low_stock'] = $stockPct <= 25.0;

            $enriched[] = $item;
        }

        return $this->setMemo('inv', $enriched);
    }

    /**
     * Enrich all production batches with daily telemetry totals, age-based feed transition alerts,
     * live cost per bird, target margin pricing, and sales ledger summaries.
     */
    public function getProductionBatchesEnriched(): array
    {
        if (($cached = $this->getMemo('prod')) !== null) {
            return $cached;
        }
        $batches = $this->db->getTable('production_batches');
        $logs = $this->db->getTable('production_daily_logs');
        $sales = $this->db->getTable('batch_sales');
        $inventory = $this->db->getTable('inventory_items');
        $incBatches = $this->db->getTable('incubation_batches');

        $invMap = [];
        foreach ($inventory as $item) {
            $invMap[(int) $item['id']] = $item;
        }

        $incMap = [];
        foreach ($incBatches as $ib) {
            $incMap[(int) $ib['id']] = $ib;
        }

        // Pre-compute total live chickens across all active batches for daily general rent sharing
        $mortalityByBatch = [];
        foreach ($logs as $l) {
            $bid = (int) $l['production_batch_id'];
            $mortalityByBatch[$bid] = ($mortalityByBatch[$bid] ?? 0) + (int) $l['mortality_count'];
        }
        $soldByBatch = [];
        foreach ($sales as $s) {
            $bid = (int) $s['production_batch_id'];
            $soldByBatch[$bid] = ($soldByBatch[$bid] ?? 0) + (int) $s['birds_sold'];
        }
        $totalFarmLiveBirds = 0;
        foreach ($batches as $b) {
            if (($b['status'] ?? 'Active') === 'Active') {
                $bid = (int) $b['id'];
                $init = max(1, (int) $b['initial_bird_count']);
                $live = max(0, $init - ($mortalityByBatch[$bid] ?? 0) - ($soldByBatch[$bid] ?? 0));
                $totalFarmLiveBirds += $live;
            }
        }
        $annualFarmRent = max(0.0, (float) $this->db->getSetting('annual_farm_rent', 0));
        $dailyFarmRent = $annualFarmRent > 0 ? ($annualFarmRent / 365.0) : 0.0;
        $generalExpenses = $this->db->getTable('general_farm_expenses');
        $totalGeneralExpenses = 0.0;
        foreach ($generalExpenses as $ge) {
            $totalGeneralExpenses += (float) ($ge['amount'] ?? 0);
        }
        $dailyGeneralExpenses = $totalGeneralExpenses > 0 ? ($totalGeneralExpenses / 30.0) : 0.0;
        $combinedDailyOverhead = $dailyFarmRent + $dailyGeneralExpenses;
        $rentPerLiveBirdPerDay = ($combinedDailyOverhead > 0 && $totalFarmLiveBirds > 0)
            ? round($combinedDailyOverhead / $totalFarmLiveBirds, 4)
            : 0.0;

        $enriched = [];
        foreach ($batches as $batch) {
            $batchId = (int) $batch['id'];
            $batchLogs = array_values(array_filter($logs, fn($l) => (int) $l['production_batch_id'] === $batchId));
            $batchSales = array_values(array_filter($sales, fn($s) => (int) $s['production_batch_id'] === $batchId));

            // Sort logs & sales newest first for display
            usort($batchLogs, fn($a, $b) => strcmp($b['log_date'], $a['log_date']) ?: ((int) $b['id'] <=> (int) $a['id']));
            usort($batchSales, fn($a, $b) => strcmp($b['sale_date'], $a['sale_date']) ?: ((int) $b['id'] <=> (int) $a['id']));

            $totalMortality = 0;
            $totalGoodEggs = 0;
            $totalCrackedEggs = 0;
            $totalWaterLiters = 0.0;
            $totalFeedGrams = 0.0;
            $totalWastedFeedGrams = 0.0;
            $totalWastedFeedCost = 0.0;
            $totalFeedCost = 0.0;
            $totalMedDose = 0.0;
            $totalMedCost = 0.0;
            $totalLaborCost = 0.0;
            $totalSanitationCost = 0.0;
            $totalWaterOtherCost = 0.0;
            $feedLoggedDays = 0;
            $loggedToday = false;
            $todayDate = date('Y-m-d');
            $lastFeedCategory = null;

            foreach ($batchLogs as $idx => $log) {
                $logGoodEggs = (int) ($log['good_eggs'] ?? 0);
                $logCrackedEggs = (int) ($log['cracked_eggs'] ?? 0);
                $totalGoodEggs += $logGoodEggs;
                $totalCrackedEggs += $logCrackedEggs;
                $totalMortality += (int) $log['mortality_count'];
                $totalWaterLiters += (float) $log['water_liters'];
                $logWastedFeedGrams = (float) ($log['wasted_feed_grams'] ?? 0);
                $logWastedFeedCost = (float) ($log['wasted_feed_cost'] ?? 0);
                $logFeedGrams = (float) $log['feed_grams'];
                if ($logFeedGrams > 0) {
                    $feedLoggedDays++;
                }
                if (($log['log_date'] ?? '') === $todayDate) {
                    $loggedToday = true;
                }
                $totalFeedGrams += $logFeedGrams;
                $totalWastedFeedGrams += $logWastedFeedGrams;
                $totalWastedFeedCost += $logWastedFeedCost;
                $totalFeedCost += (float) $log['feed_cost_calculated'] + $logWastedFeedCost;
                $totalMedDose += (float) $log['med_dose_g_ml'];
                $totalMedCost += (float) $log['med_cost_calculated'];
                $totalLaborCost += (float) $log['labor_cost'];
                $totalSanitationCost += (float) $log['sanitation_cost'];
                $waterOtherCost = (float) ($log['water_other_cost'] ?? 0);
                $totalWaterOtherCost += $waterOtherCost;

                $feedItem = isset($log['feed_item_id']) ? ($invMap[(int) $log['feed_item_id']] ?? null) : null;
                $medItem = isset($log['med_item_id']) ? ($invMap[(int) $log['med_item_id']] ?? null) : null;
                $batchLogs[$idx]['good_eggs'] = $logGoodEggs;
                $batchLogs[$idx]['cracked_eggs'] = $logCrackedEggs;
                $batchLogs[$idx]['wasted_feed_grams'] = $logWastedFeedGrams;
                $batchLogs[$idx]['wasted_feed_cost'] = $logWastedFeedCost;
                $batchLogs[$idx]['feed_item_name'] = $feedItem['item_name'] ?? 'None';
                $batchLogs[$idx]['feed_category'] = $feedItem['category'] ?? null;
                if (!empty($log['medications_used']) && is_array($log['medications_used'])) {
                    $medLabels = array_map(
                        fn($mu) => $mu['item_name'] . ' (' . self::fmtQty((float) $mu['dose']) . ($mu['unit'] ?? 'ml') . ')',
                        $log['medications_used']
                    );
                    $batchLogs[$idx]['med_item_name'] = implode(', ', $medLabels);
                    $batchLogs[$idx]['med_unit'] = '';
                    $batchLogs[$idx]['has_multi_meds'] = true;
                } else {
                    $batchLogs[$idx]['med_item_name'] = $medItem['item_name'] ?? 'None';
                    $batchLogs[$idx]['med_unit'] = $medItem['unit_type'] ?? 'ml';
                    $batchLogs[$idx]['has_multi_meds'] = false;
                }
                $batchLogs[$idx]['water_other_cost'] = $waterOtherCost;
                $batchLogs[$idx]['daily_total_cost'] = (float) $log['feed_cost_calculated']
                    + $logWastedFeedCost
                    + (float) $log['med_cost_calculated']
                    + (float) $log['labor_cost']
                    + (float) $log['sanitation_cost']
                    + $waterOtherCost;

                if ($lastFeedCategory === null && $feedItem) {
                    $lastFeedCategory = $feedItem['category'];
                }
            }

            $totalBirdsSold = 0;
            $realizedRevenue = 0.0;
            $paidRevenue = 0.0;
            $batchReceivables = 0.0;
            foreach ($batchSales as $sIdx => $sale) {
                $totalBirdsSold += (int) $sale['birds_sold'];
                $totalAmt = (float) $sale['total_amount'];
                $realizedRevenue += $totalAmt;
                if (isset($sale['amount_paid'])) {
                    $amtPaid = (float) $sale['amount_paid'];
                } else {
                    $amtPaid = ($sale['payment_status'] === 'Paid') ? $totalAmt : 0.0;
                }
                $balDue = round(max(0.0, $totalAmt - $amtPaid), 2);
                $batchSales[$sIdx]['amount_paid'] = round($amtPaid, 2);
                $batchSales[$sIdx]['balance_due'] = $balDue;
                $paidRevenue += $amtPaid;
                $batchReceivables += $balDue;
            }

            $initialBirds = max(1, (int) $batch['initial_bird_count']);
            // Surviving bird count (Initial - Mortality) vs Current in-house bird count (Initial - Mortality - Sold)
            $survivingBiologicalPool = max(1, $initialBirds - $totalMortality);
            $currentInHouseBirds = max(0, $initialBirds - $totalMortality - $totalBirdsSold);

            $startTs = strtotime($batch['start_date']);
            $daysInHouse = max(1, (int) floor((time() - $startTs) / 86400) + 1);
            $rentBirdBasis = $currentInHouseBirds > 0 ? $currentInHouseBirds : $survivingBiologicalPool;
            $batchDailyRent = round($rentPerLiveBirdPerDay * $rentBirdBasis, 2);
            $accumulatedGeneralRent = round($batchDailyRent * $daysInHouse, 2);

            $incubationCost = (float) $batch['transferred_incubation_cost'];
            $manualHouseRent = (float) $batch['house_rent_total'];
            $houseRent = $manualHouseRent > 0 ? $manualHouseRent : $accumulatedGeneralRent;
            $variableCost = $totalFeedCost + $totalMedCost + $totalLaborCost + $totalSanitationCost + $totalWaterOtherCost;
            // ONLY variable flock costs (DOC/Incubation expenses + Feed + Water + Labour + Medications + Sanitation/Other Variable)
            // are divided into chicks/birds and drive Cost/Bird, Selling Price, and Gross Profit.
            // Fixed Assets, Annual Rent, and General Farm Overhead are deducted strictly at Whole-Farm Net Profit.
            $variableChickenCost = round($incubationCost + $variableCost, 2);
            $totalAccumulatedCost = $variableChickenCost;

            // Current Variable Cost per Bird = (DOC/Incubation + Feed + Meds + Water + Labour + Sanitation) / Surviving Bird Count
            $costPerSurvivingBird = round($variableChickenCost / $survivingBiologicalPool, 2);
            $costPerInHouseBird = $currentInHouseBirds > 0
                ? round(max(0.0, $variableChickenCost - ($totalBirdsSold * $costPerSurvivingBird)) / $currentInHouseBirds, 2)
                : $costPerSurvivingBird;

            // Cost per bird without mortality (to show exact mortality absorption impact)
            $costWithoutMortality = round($variableChickenCost / $initialBirds, 2);
            $mortalityCostAbsorbedPerBird = round(max(0.0, $costPerSurvivingBird - $costWithoutMortality), 2);

            $marginPct = (float) $batch['target_profit_margin_pct'];
            $recommendedSellingPrice = round($costPerSurvivingBird * (1 + ($marginPct / 100)), 2);

            // Projected total revenue & Gross Profit across surviving birds (variable chicken costs only)
            $projectedRemainingRevenue = round($currentInHouseBirds * $recommendedSellingPrice, 2);
            $projectedTotalRevenue = round($realizedRevenue + $projectedRemainingRevenue, 2);
            $projectedGrossProfit = round($projectedTotalRevenue - $variableChickenCost, 2);
            $realizedGrossProfit = round($realizedRevenue - $variableChickenCost, 2);
            $projectedNetProfit = $projectedGrossProfit;
            $realizedNetBalance = $realizedGrossProfit;

            // Age calculation & Feed Transition Alert (respects initial_age_days for direct grower purchases)
            $initialAgeDays = max(1, (int) ($batch['initial_age_days'] ?? 1));
            $daysSinceStart = max(0, (int) floor((time() - $startTs) / 86400));
            $ageDays = max(1, $initialAgeDays + $daysSinceStart);
            $ageWeeks = (int) ceil($ageDays / 7);

            $feedAlert = $this->computeFeedTransitionAlert($ageDays, $ageWeeks, $lastFeedCategory);
            $recFeed = $this->computeRecommendedDailyFeed((string) ($batch['flock_type'] ?? ''), $ageDays, $currentInHouseBirds);
            $actualAvgFeedPerDay = $feedLoggedDays > 0 ? round($totalFeedGrams / $feedLoggedDays, 2) : 0.0;
            $actualAvgFeedPerBird = ($feedLoggedDays > 0 && $currentInHouseBirds > 0) ? round($actualAvgFeedPerDay / $currentInHouseBirds, 2) : 0.0;

            $incBatchId = isset($batch['incubation_batch_id']) ? (int) $batch['incubation_batch_id'] : 0;
            $originIncBatch = $incBatchId > 0 ? ($incMap[$incBatchId] ?? null) : null;

            $eggCostPerEgg = $totalGoodEggs > 0 ? round(($totalFeedCost + $totalMedCost) / $totalGoodEggs, 2) : 0.0;
            $eggCostPerCrate = round($eggCostPerEgg * 30, 2);

            $batch['logs'] = $batchLogs;
            $batch['sales'] = $batchSales;
            $batch['origin_incubation_batch'] = $originIncBatch;
            $batch['age_days'] = $ageDays;
            $batch['age_weeks'] = $ageWeeks;
            $batch['feed_alert'] = $feedAlert;
            $batch['current_bird_count'] = $currentInHouseBirds;
            $batch['metrics'] = [
                'initial_birds' => $initialBirds,
                'total_mortality' => $totalMortality,
                'mortality_rate_pct' => round(($totalMortality / $initialBirds) * 100, 2),
                'total_good_eggs' => $totalGoodEggs,
                'total_cracked_eggs' => $totalCrackedEggs,
                'egg_cost_per_egg' => $eggCostPerEgg,
                'egg_cost_per_crate' => $eggCostPerCrate,
                'surviving_biological_birds' => $survivingBiologicalPool,
                'current_in_house_birds' => $currentInHouseBirds,
                'total_birds_sold' => $totalBirdsSold,
                'total_water_liters' => $totalWaterLiters,
                'total_feed_grams' => $totalFeedGrams,
                'total_feed_kg' => round($totalFeedGrams / 1000, 4),
                'total_wasted_feed_grams' => round($totalWastedFeedGrams, 4),
                'total_wasted_feed_kg' => round($totalWastedFeedGrams / 1000, 4),
                'total_wasted_feed_cost' => round($totalWastedFeedCost, 2),
                'total_feed_cost' => $totalFeedCost,
                'recommended_feed_g_per_bird' => $recFeed['g_per_bird'],
                'recommended_daily_feed_grams' => $recFeed['total_grams'],
                'recommended_daily_feed_kg' => $recFeed['total_kg'],
                'recommended_feed_category' => $recFeed['recommended_category'],
                'actual_avg_feed_grams_per_day' => $actualAvgFeedPerDay,
                'actual_avg_feed_g_per_bird' => $actualAvgFeedPerBird,
                'feed_logged_days' => $feedLoggedDays,
                'logged_today' => $loggedToday,
                'total_med_dose' => $totalMedDose,
                'total_med_cost' => $totalMedCost,
                'total_labor_cost' => $totalLaborCost,
                'total_sanitation_cost' => $totalSanitationCost,
                'total_water_other_cost' => $totalWaterOtherCost,
                'incubation_cost' => $incubationCost,
                'house_rent' => $houseRent,
                'rent_per_bird_per_day' => $rentPerLiveBirdPerDay,
                'batch_daily_rent' => $batchDailyRent,
                'days_in_house' => $daysInHouse,
                'variable_cost' => $variableCost,
                'variable_chicken_cost' => $variableChickenCost,
                'total_accumulated_cost' => $totalAccumulatedCost,
                'cost_per_bird' => $costPerSurvivingBird,
                'variable_cost_per_bird' => $costPerSurvivingBird,
                'cost_per_in_house_bird' => $costPerInHouseBird,
                'mortality_absorbed_per_bird' => $mortalityCostAbsorbedPerBird,
                'target_margin_pct' => $marginPct,
                'target_selling_price' => (float) ($batch['target_selling_price'] ?? $recommendedSellingPrice),
                'recommended_selling_price' => $recommendedSellingPrice,
                'profit_per_bird' => round($recommendedSellingPrice - $costPerSurvivingBird, 2),
                'gross_profit_per_bird' => round($recommendedSellingPrice - $costPerSurvivingBird, 2),
                'realized_revenue' => $realizedRevenue,
                'paid_revenue' => $paidRevenue,
                'total_receivables' => round($batchReceivables ?? 0, 2),
                'projected_remaining_revenue' => $projectedRemainingRevenue,
                'projected_total_revenue' => $projectedTotalRevenue,
                'projected_gross_profit' => $projectedGrossProfit,
                'realized_gross_profit' => $realizedGrossProfit,
                'projected_net_profit' => $projectedNetProfit,
                'realized_net_balance' => $realizedNetBalance,
            ];

            $enriched[] = $batch;
        }

        usort($enriched, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);
        return $this->setMemo('prod', $enriched);
    }

    private function generateDateBatchCode(string $prefix, ?string $dateStr = null): string
    {
        $datePart = (!empty($dateStr) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr))
            ? $dateStr
            : date('Y-m-d');
        $basePrefix = $prefix . '-' . $datePart . '-';
        $seq = 1;
        foreach ($this->db->getTable('production_batches') as $pb) {
            $code = (string) ($pb['batch_code'] ?? '');
            if (str_starts_with($code, $basePrefix)) {
                $suffix = (int) substr($code, strlen($basePrefix));
                if ($suffix >= $seq) {
                    $seq = $suffix + 1;
                }
            }
        }
        return sprintf('%s%02d', $basePrefix, $seq);
    }

    /**
     * Standard poultry daily feed intake (g/bird/day) by breed/species & age day.
     */
    public function computeRecommendedDailyFeed(string $flockType, int $ageDays, int $liveBirds): array
    {
        $type = strtolower($flockType);
        $week = max(1, (int) ceil(max(1, $ageDays) / 7));

        if (str_contains($type, 'turkey')) {
            $gPerBird = $week <= 2 ? 25.0 : ($week <= 4 ? 60.0 : ($week <= 8 ? 120.0 : 220.0));
        } elseif (str_contains($type, 'layer') || str_contains($type, 'pullet') || str_contains($type, 'isa')) {
            $gPerBird = $week <= 2 ? 16.0 : ($week <= 4 ? 30.0 : ($week <= 8 ? 50.0 : ($week <= 16 ? 75.0 : 115.0)));
        } elseif (str_contains($type, 'noiler') || str_contains($type, 'cockerel') || str_contains($type, 'local') || str_contains($type, 'guinea')) {
            $gPerBird = $week === 1 ? 15.0 : ($week === 2 ? 28.0 : ($week === 3 ? 42.0 : ($week === 4 ? 55.0 : ($week <= 6 ? 70.0 : ($week <= 10 ? 90.0 : 110.0)))));
        } else {
            // Broiler default curve
            $gPerBird = $week === 1 ? 20.0 : ($week === 2 ? 45.0 : ($week === 3 ? 75.0 : ($week === 4 ? 110.0 : ($week === 5 ? 140.0 : 165.0))));
        }

        $totalGrams = round(max(0, $liveBirds) * $gPerBird, 2);
        $recCat = $ageDays <= 21 ? 'starter' : ($ageDays <= 42 ? 'grower' : 'layer');

        return [
            'g_per_bird' => $gPerBird,
            'total_grams' => $totalGrams,
            'total_kg' => round($totalGrams / 1000.0, 3),
            'recommended_category' => $recCat,
        ];
    }

    /**
     * 24-Hour Hands-Free Auto-Log Settings (global + per-flock overrides).
     */
    public function getAutoFeedSettings(): array
    {
        $enabledRaw = $this->db->getSetting('auto_feed_enabled', '1');
        $enabled = !in_array((string) $enabledRaw, ['0', 'false', 'off', ''], true);
        $defaultWater = max(0.0, (float) $this->db->getSetting('auto_feed_default_water', 10.0));
        $lastRunDate = (string) $this->db->getSetting('auto_feed_last_run_date', '');
        $overridesRaw = $this->db->getSetting('auto_feed_overrides', []);
        $overrides = is_array($overridesRaw) ? $overridesRaw : [];

        return [
            'enabled' => $enabled,
            'default_water_liters' => round($defaultWater, 2),
            'last_run_date' => $lastRunDate,
            'overrides' => $overrides,
        ];
    }

    public function saveAutoFeedSettings(array $input): void
    {
        $enabled = !empty($input['auto_feed_enabled']) && (string) $input['auto_feed_enabled'] !== '0';
        $defaultWater = round(max(0.0, (float) ($input['default_water_liters'] ?? 10.0)), 2);

        $rawOverrides = isset($input['flock_overrides']) && is_array($input['flock_overrides'])
            ? $input['flock_overrides']
            : [];
        $cleanOverrides = [];
        foreach ($rawOverrides as $bidKey => $ov) {
            $bid = (int) $bidKey;
            if ($bid <= 0 || !is_array($ov)) {
                continue;
            }
            $customG = isset($ov['custom_g_per_bird']) && $ov['custom_g_per_bird'] !== ''
                ? round(max(0.0, (float) $ov['custom_g_per_bird']), 2)
                : null;
            $prefFeedId = isset($ov['preferred_feed_id']) && $ov['preferred_feed_id'] !== ''
                ? (int) $ov['preferred_feed_id']
                : null;
            $waterOv = isset($ov['water_liters']) && $ov['water_liters'] !== ''
                ? round(max(0.0, (float) $ov['water_liters']), 2)
                : null;
            $paused = !empty($ov['paused']);

            $cleanOverrides[(string) $bid] = [
                'paused' => $paused,
                'custom_g_per_bird' => $customG,
                'preferred_feed_id' => $prefFeedId,
                'water_liters' => $waterOv,
            ];
        }

        $this->db->setSetting('auto_feed_enabled', $enabled ? '1' : '0');
        $this->db->setSetting('auto_feed_default_water', $defaultWater);
        $this->db->setSetting('auto_feed_overrides', $cleanOverrides);
    }

    /**
     * Automatically runs once every 24 hours (once per calendar day) without requiring any button click.
     */
    public function runScheduledAutoFeedIfDue(?string $targetDate = null): ?array
    {
        $today = (!empty($targetDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate))
            ? $targetDate
            : date('Y-m-d');
        $cfg = $this->getAutoFeedSettings();
        if (!$cfg['enabled']) {
            return null;
        }
        if ($cfg['last_run_date'] === $today) {
            return null;
        }

        $res = $this->autoLogDailyFeedForActiveFlocks($today);
        $this->db->setSetting('auto_feed_last_run_date', $today);
        return $res;
    }

    /**
     * Auto-Log: logs age-based (or custom-overridden) daily feed + water for every active flock not yet logged on $logDate.
     */
    public function autoLogDailyFeedForActiveFlocks(?string $logDate = null): array
    {
        $targetDate = (!empty($logDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $logDate))
            ? $logDate
            : date('Y-m-d');

        $cfg = $this->getAutoFeedSettings();
        $defaultWater = (float) ($cfg['default_water_liters'] ?? 10.0);
        $overrides = $cfg['overrides'] ?? [];

        $batches = $this->getProductionBatchesEnriched();
        $logged = 0;
        $skipped = 0;
        $loggedCodes = [];

        foreach ($batches as $pb) {
            $m = $pb['metrics'] ?? [];
            $liveBirds = (int) ($m['current_in_house_birds'] ?? 0);
            if ($liveBirds <= 0 || ($pb['status'] ?? 'Active') === 'Completed') {
                continue;
            }

            $bidKey = (string) ((int) $pb['id']);
            $ov = isset($overrides[$bidKey]) && is_array($overrides[$bidKey]) ? $overrides[$bidKey] : [];
            if (!empty($ov['paused'])) {
                $skipped++;
                continue;
            }

            // Skip if already logged on targetDate
            $alreadyLogged = false;
            foreach ($pb['logs'] ?? [] as $lg) {
                if (($lg['log_date'] ?? '') === $targetDate) {
                    $alreadyLogged = true;
                    break;
                }
            }
            if ($alreadyLogged) {
                $skipped++;
                continue;
            }

            $gPerBird = isset($ov['custom_g_per_bird']) && $ov['custom_g_per_bird'] !== null
                ? (float) $ov['custom_g_per_bird']
                : (float) ($m['recommended_feed_g_per_bird'] ?? 0);
            $recGrams = round($liveBirds * $gPerBird, 2);
            $waterToLog = isset($ov['water_liters']) && $ov['water_liters'] !== null
                ? (float) $ov['water_liters']
                : $defaultWater;
            $recCat = (string) ($m['recommended_feed_category'] ?? 'starter');
            $prefFeedId = !empty($ov['preferred_feed_id']) ? (int) $ov['preferred_feed_id'] : 0;

            // Pick preferred or matching available feed item from Inventory (fresh read each iteration)
            $preferredFeed = null;
            $feedItem = null;
            $fallbackFeed = null;
            foreach ($this->db->getTable('inventory_items') as $it) {
                $cat = (string) ($it['category'] ?? '');
                $rem = (float) ($it['total_quantity_g_or_ml'] ?? 0);
                if (!in_array($cat, ['starter', 'grower', 'layer'], true) || $rem <= 0) {
                    continue;
                }
                if ($prefFeedId > 0 && (int) ($it['id'] ?? 0) === $prefFeedId) {
                    $preferredFeed = $it;
                }
                if ($cat === $recCat && ($feedItem === null || $rem > (float) $feedItem['total_quantity_g_or_ml'])) {
                    $feedItem = $it;
                }
                if ($fallbackFeed === null || $rem > (float) $fallbackFeed['total_quantity_g_or_ml']) {
                    $fallbackFeed = $it;
                }
            }
            $chosenFeed = $preferredFeed ?? $feedItem ?? $fallbackFeed;
            $feedItemId = $chosenFeed ? (int) $chosenFeed['id'] : null;
            $gramsToLog = $chosenFeed ? round(min($recGrams, (float) $chosenFeed['total_quantity_g_or_ml']), 2) : 0.0;

            $this->addProductionDailyLog([
                'production_batch_id' => (int) $pb['id'],
                'log_date' => $targetDate,
                'mortality_count' => 0,
                'water_liters' => $waterToLog,
                'feed_item_id' => $feedItemId,
                'feed_grams' => $gramsToLog,
                'wasted_feed_grams' => 0.0,
                'labor_cost' => 0.0,
                'sanitation_cost' => 0.0,
                'water_other_cost' => 0.0,
                'is_auto_logged' => true,
                'notes' => 'Auto-logged (' . $gPerBird . 'g/bird × ' . $liveBirds . ' birds • Day ' . (int) ($pb['age_days'] ?? 1) . ' • ' . $waterToLog . 'L water)',
            ]);
            $logged++;
            $loggedCodes[] = (string) $pb['batch_code'];
        }

        $this->db->setSetting('auto_feed_last_run_date', $targetDate);

        return [
            'logged' => $logged,
            'skipped' => $skipped,
            'batches' => $loggedCodes,
            'date' => $targetDate,
        ];
    }

    private function computeFeedTransitionAlert(int $ageDays, int $ageWeeks, ?string $lastFeedCategory): array
    {
        if ($ageDays <= 21) {
            return [
                'recommended_stage' => 'Starter',
                'recommended_category' => 'starter',
                'badge_color' => 'emerald',
                'needs_transition' => ($lastFeedCategory !== null && $lastFeedCategory !== 'starter'),
                'title' => "Starter Phase (Day {$ageDays} • Week {$ageWeeks})",
                'message' => 'Flock is in early brooding phase (Days 1–21). Maintain high-protein Starter Crumble (22–23% CP). Transition to Grower feed in ' . max(0, 22 - $ageDays) . ' days.',
            ];
        }

        if ($ageDays <= 42) {
            $needsSwitch = ($lastFeedCategory === 'starter');
            return [
                'recommended_stage' => 'Grower',
                'recommended_category' => 'grower',
                'badge_color' => $needsSwitch ? 'amber' : 'emerald',
                'needs_transition' => $needsSwitch,
                'title' => $needsSwitch
                    ? "Action Required: Transition from Starter to Grower Feed (Day {$ageDays} • Week {$ageWeeks})"
                    : "Grower Phase Active (Day {$ageDays} • Week {$ageWeeks})",
                'message' => $needsSwitch
                    ? "Flock has passed Day 21 (currently Day {$ageDays}), but last recorded feeding was still Starter. Switch immediately to Grower Pellet to optimize frame growth and lower feed cost per gram."
                    : "Flock is in Grower phase (Days 22–42). Next transition to Finisher / Layer feed scheduled at Day 43 (" . max(0, 43 - $ageDays) . " days remaining).",
            ];
        }

        $needsSwitch = ($lastFeedCategory !== 'layer');
        return [
            'recommended_stage' => 'Finisher / Layer',
            'recommended_category' => 'layer',
            'badge_color' => $needsSwitch ? 'amber' : 'emerald',
            'needs_transition' => $needsSwitch,
            'title' => $needsSwitch
                ? "Action Required: Transition to Finisher / Layer Feed (Day {$ageDays} • Week {$ageWeeks})"
                : "Finisher / Layer Phase Active (Day {$ageDays} • Week {$ageWeeks})",
            'message' => $needsSwitch
                ? "Flock has reached Day {$ageDays} (Week {$ageWeeks}). Transition from Grower to Finisher / Layer ration immediately for market weight conditioning or egg production."
                : "Flock is on Finisher / Layer ration and ready for commercial harvest or laying cycle.",
        ];
    }

    /**
     * Executive summary KPIs across all 4 modules.
     */
    public function getExecutiveSummary(): array
    {
        if (($cached = $this->getMemo('sum')) !== null) {
            return $cached;
        }
        $incBatches = $this->getIncubationBatchesEnriched();
        $prodBatches = $this->getProductionBatchesEnriched();
        $inventory = $this->getInventoryItemsEnriched();

        $activeEggsInIncubator = 0;
        $totalChicksHatched = 0;
        $totalIncubationSpend = 0.0;
        $avgHatchability = 0.0;
        $hatchCount = 0;

        foreach ($incBatches as $ib) {
            if ($ib['status'] === 'Active') {
                $activeEggsInIncubator += (int) $ib['metrics']['viable_eggs_remaining'];
            }
            $totalIncubationSpend += (float) $ib['metrics']['total_incubation_cost'];
            if ($ib['breakout']) {
                $totalChicksHatched += (int) $ib['metrics']['live_chicks'];
                $avgHatchability += (float) $ib['metrics']['hatchability_rate'];
                $hatchCount++;
            }
        }
        $avgHatchability = $hatchCount > 0 ? round($avgHatchability / $hatchCount, 1) : 0.0;

        $totalLiveBirds = 0;
        $totalProductionInvestment = 0.0;
        $totalRealizedRevenue = 0.0;
        $totalProjectedRevenue = 0.0;
        $totalProjectedProfit = 0.0;
        $totalBirdsSold = 0;

        foreach ($prodBatches as $pb) {
            $totalLiveBirds += (int) $pb['metrics']['current_in_house_birds'];
            $totalProductionInvestment += (float) $pb['metrics']['total_accumulated_cost'];
            $totalRealizedRevenue += (float) $pb['metrics']['realized_revenue'];
            $totalProjectedRevenue += (float) $pb['metrics']['projected_total_revenue'];
            $totalProjectedProfit += (float) $pb['metrics']['projected_net_profit'];
            $totalBirdsSold += (int) $pb['metrics']['total_birds_sold'];
        }

        $totalInventoryValue = 0.0;
        $totalFeedKgRemaining = 0.0;
        $lowStockCount = 0;
        foreach ($inventory as $item) {
            $totalInventoryValue += (float) $item['remaining_value'];
            if (in_array($item['category'], ['starter', 'grower', 'layer'], true)) {
                $totalFeedKgRemaining += (float) $item['remaining_bulk'];
            }
            if ($item['low_stock']) {
                $lowStockCount++;
            }
        }

        return $this->setMemo('sum', [
            'active_eggs_in_incubator' => $activeEggsInIncubator,
            'total_chicks_hatched' => $totalChicksHatched,
            'avg_hatchability_pct' => $avgHatchability,
            'total_live_birds' => $totalLiveBirds,
            'total_birds_sold' => $totalBirdsSold,
            'total_production_investment' => $totalProductionInvestment,
            'total_realized_revenue' => $totalRealizedRevenue,
            'total_projected_revenue' => $totalProjectedRevenue,
            'total_projected_profit' => $totalProjectedProfit,
            'total_inventory_value' => round($totalInventoryValue, 2),
            'total_feed_kg_remaining' => round($totalFeedKgRemaining, 1),
            'low_stock_count' => $lowStockCount,
            'active_incubation_count' => count(array_filter($incBatches, fn($b) => $b['status'] === 'Active')),
            'ready_to_move_count' => count(array_filter($incBatches, fn($b) => $b['status'] === 'Completed')),
            'active_production_count' => count(array_filter($prodBatches, fn($b) => $b['status'] === 'Active')),
        ]);
    }

    // =========================================================================
    // MUTATION ACTIONS (Modules 1 - 4)
    // =========================================================================

    public function createIncubationBatch(array $input): int
    {
        $startDate = !empty($input['start_date']) ? (string) $input['start_date'] : date('Y-m-d');
        $batchCode = trim((string) ($input['batch_code'] ?? ''));
        if ($batchCode === '') {
            $datePart = preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) ? $startDate : date('Y-m-d');
            $basePrefix = 'INC-' . $datePart . '-';
            $seq = 1;
            foreach ($this->db->getTable('incubation_batches') as $ib) {
                $c = (string) ($ib['batch_code'] ?? '');
                if (str_starts_with($c, $basePrefix)) {
                    $s = (int) substr($c, strlen($basePrefix));
                    if ($s >= $seq) {
                        $seq = $s + 1;
                    }
                }
            }
            $batchCode = sprintf('%s%02d', $basePrefix, $seq);
        }

        $profiles = VeterinaryKnowledgeBase::getIncubationSpeciesProfiles();
        $species = strtolower(trim((string) ($input['species'] ?? 'chicken')));
        if (!isset($profiles[$species])) {
            $species = 'chicken';
        }
        $parentInfo = trim((string) ($input['parent_flock_info'] ?? ''));
        if ($parentInfo === '') {
            $parentInfo = $profiles[$species]['label'] . ' Intake';
        }

        $ownershipType = (($input['ownership_type'] ?? 'Farm') === 'Client') ? 'Client' : 'Farm';
        $clientName = trim((string) ($input['client_name'] ?? ''));
        $clientPhone = trim((string) ($input['client_phone'] ?? ''));
        $serviceFee = $ownershipType === 'Client' ? max(0.0, (float) ($input['service_fee'] ?? 0)) : 0.0;
        $amountPaid = $ownershipType === 'Client' ? max(0.0, min($serviceFee, (float) ($input['amount_paid'] ?? $serviceFee))) : 0.0;
        $balanceDue = round(max(0.0, $serviceFee - $amountPaid), 2);
        $paymentStatus = $ownershipType === 'Client'
            ? ($balanceDue <= 0 ? 'Paid' : ($amountPaid > 0 ? 'Partial' : 'Pending'))
            : 'N/A';

        $batchId = $this->db->insert('incubation_batches', [
            'batch_code' => $batchCode,
            'species' => $species,
            'setter_id' => trim((string) ($input['setter_id'] ?? 'Setter A')),
            'parent_flock_info' => $clientName !== '' ? (($ownershipType === 'Client' ? 'Client: ' : 'Brought by: ') . $clientName) : $parentInfo,
            'flock_age_weeks' => max(18, (int) ($input['flock_age_weeks'] ?? 32)),
            'eggs_set' => max(1, (int) ($input['eggs_set'] ?? 1000)),
            'egg_cost' => $ownershipType === 'Client' ? 0.0 : max(0.0, (float) ($input['egg_cost'] ?? 0)),
            'start_date' => $startDate,
            'status' => 'Active',
            'ownership_type' => $ownershipType,
            'client_name' => $clientName,
            'client_phone' => $clientPhone,
            'service_fee' => round($serviceFee, 2),
            'amount_paid' => round($amountPaid, 2),
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus,
        ]);

        // Automatically record First Gas Intake if provided
        $initialGasKg = max(0.0, (float) ($input['initial_gas_kg'] ?? 0));
        $initialGasCost = max(0.0, (float) ($input['initial_gas_cost'] ?? 0));
        if ($initialGasKg > 0 || $initialGasCost > 0) {
            $this->db->insert('incubation_expenses', [
                'incubation_batch_id' => $batchId,
                'expense_type' => 'gas',
                'gas_weight_kg' => $initialGasKg,
                'amount' => $initialGasCost,
                'description' => 'First Gas Intake',
                'date' => $startDate,
            ]);
        }

        return $batchId;
    }

    public function addIncubationExpense(array $input): int
    {
        $batchId = (int) ($input['incubation_batch_id'] ?? 0);
        if (!$this->db->findById('incubation_batches', $batchId)) {
            throw new RuntimeException('Selected Incubation Batch does not exist.');
        }

        $rawType = strtolower(trim((string) ($input['expense_type'] ?? 'gas')));
        $type = in_array($rawType, ['gas', 'repair', 'other'], true) ? $rawType : 'gas';
        $gasWeight = $type === 'gas' ? max(0.0, (float) ($input['gas_weight_kg'] ?? 0)) : 0.0;
        $amount = max(0.0, (float) ($input['amount'] ?? 0));
        $defaultDesc = $type === 'gas' ? 'LPG Gas Refill' : ($type === 'repair' ? 'Incubator Maintenance' : 'Operational Expense');

        return $this->db->insert('incubation_expenses', [
            'incubation_batch_id' => $batchId,
            'expense_type' => $type,
            'gas_weight_kg' => $gasWeight,
            'amount' => $amount,
            'description' => trim((string) ($input['description'] ?? $defaultDesc)) ?: $defaultDesc,
            'date' => $input['date'] ?: date('Y-m-d'),
        ]);
    }

    public function addCandlingLog(array $input): int
    {
        $batchId = (int) ($input['incubation_batch_id'] ?? 0);
        if (!$this->db->findById('incubation_batches', $batchId)) {
            throw new RuntimeException('Selected Incubation Batch does not exist.');
        }

        return $this->db->insert('incubation_daily_logs', [
            'incubation_batch_id' => $batchId,
            'log_date' => $input['log_date'] ?: date('Y-m-d'),
            'stage' => trim((string) ($input['stage'] ?? 'Setter')),
            'milestone' => trim((string) ($input['milestone'] ?? 'Daily Inspection')),
            'temperature_c' => (float) ($input['temperature_c'] ?? 37.5),
            'humidity_pct' => (float) ($input['humidity_pct'] ?? 58.0),
            'infertiles' => max(0, (int) ($input['infertiles'] ?? 0)),
            'early_deaths' => max(0, (int) ($input['early_deaths'] ?? 0)),
            'cracked' => max(0, (int) ($input['cracked'] ?? 0)),
            'dead_in_shell' => max(0, (int) ($input['dead_in_shell'] ?? 0)),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ]);
    }

    public function recordHatchBreakout(array $input): int
    {
        $batchId = (int) ($input['incubation_batch_id'] ?? 0);
        $batch = $this->db->findById('incubation_batches', $batchId);
        if (!$batch) {
            throw new RuntimeException('Selected Incubation Batch does not exist.');
        }

        $liveChicks = max(1, (int) ($input['live_chicks'] ?? 0));
        $culls = max(0, (int) ($input['culls'] ?? 0));
        $pipped = max(0, (int) ($input['pipped'] ?? 0));
        $dis = max(0, (int) ($input['dead_in_shell'] ?? 0));

        // Gather candling infertiles and total expenses for exact formula calculation
        $logs = $this->db->where('incubation_daily_logs', 'incubation_batch_id', $batchId);
        $infertiles = 0;
        foreach ($logs as $log) {
            $infertiles += (int) $log['infertiles'];
        }

        $expenses = $this->db->where('incubation_expenses', 'incubation_batch_id', $batchId);
        $gasAndRepairs = 0.0;
        foreach ($expenses as $exp) {
            $gasAndRepairs += (float) $exp['amount'];
        }

        $eggsSet = max(1, (int) $batch['eggs_set']);
        $totalBatchCost = (float) $batch['egg_cost'] + $gasAndRepairs;

        // Exact formulas from specification:
        // Fertility % = ((Total Eggs Set - Infertiles) / Total Eggs Set) * 100
        // Hatchability % = (Live Hatchlings / Total Eggs Set) * 100
        // Initial Cost per Chick = (Total Egg Cost + Gas Cost + Incubation Repairs) / Total Live Grade-A Chicks
        $fertilityRate = round((($eggsSet - $infertiles) / $eggsSet) * 100, 2);
        $hatchabilityRate = round(($liveChicks / $eggsSet) * 100, 2);
        $costPerChick = round($totalBatchCost / $liveChicks, 2);

        $breakoutId = $this->db->upsertByColumn('hatch_breakouts', 'incubation_batch_id', $batchId, [
            'incubation_batch_id' => $batchId,
            'live_chicks' => $liveChicks,
            'culls' => $culls,
            'pipped' => $pipped,
            'dead_in_shell' => $dis,
            'fertility_rate' => $fertilityRate,
            'hatchability_rate' => $hatchabilityRate,
            'cost_per_chick' => $costPerChick,
            'breakout_date' => $input['breakout_date'] ?: date('Y-m-d'),
        ]);

        if ($batch['status'] === 'Active') {
            $this->db->update('incubation_batches', $batchId, ['status' => 'Completed']);
        }

        $this->addAiMemory(
            "Hatchery {$batch['batch_code']} ({$batch['parent_flock_info']}): {$fertilityRate}% fertility, {$hatchabilityRate}% hatchability, ₦" . number_format($costPerChick, 2) . "/chick.",
            'hatchery'
        );

        return $breakoutId;
    }

    /**
     * One-Click UI Transition ("Push to Production")
     */
    public function pushIncubationToProduction(int $incubationBatchId, array $options = []): int
    {
        $enrichedBatches = $this->getIncubationBatchesEnriched();
        $target = null;
        foreach ($enrichedBatches as $b) {
            if ((int) $b['id'] === $incubationBatchId) {
                $target = $b;
                break;
            }
        }

        if (!$target) {
            throw new RuntimeException('Incubation batch not found.');
        }

        if ($target['linked_production_batch'] !== null) {
            return (int) $target['linked_production_batch']['id'];
        }

        $liveChicks = (int) ($target['metrics']['live_chicks'] > 0
            ? $target['metrics']['live_chicks']
            : $target['metrics']['viable_eggs_remaining']);
        $accumulatedCost = (float) $target['metrics']['total_incubation_cost'];

        $prodCode = !empty($options['batch_code'])
            ? trim((string) $options['batch_code'])
            : $this->generateDateBatchCode('PRD', date('Y-m-d'));

        $houseRent = isset($options['house_rent_total']) && $options['house_rent_total'] !== ''
            ? max(0.0, (float) $options['house_rent_total'])
            : 0.00;
        $costPerChick = $liveChicks > 0 ? ($accumulatedCost / $liveChicks) : 0.0;
        $sellingPrice = isset($options['selling_price_per_bird']) && (float) $options['selling_price_per_bird'] > 0
            ? round((float) $options['selling_price_per_bird'], 2)
            : 0.0;

        if ($sellingPrice > 0 && $costPerChick > 0) {
            $targetMargin = round(max(0.0, (($sellingPrice - $costPerChick) / $costPerChick) * 100), 2);
        } else {
            $targetMargin = isset($options['target_profit_margin_pct']) ? max(0.0, (float) $options['target_profit_margin_pct']) : 30.00;
        }
        $targetSellingPrice = $sellingPrice > 0 ? $sellingPrice : round($costPerChick * (1 + ($targetMargin / 100)), 2);

        $prodId = $this->db->insert('production_batches', [
            'batch_code' => $prodCode,
            'incubation_batch_id' => $incubationBatchId,
            'flock_type' => $target['parent_flock_info'],
            'initial_bird_count' => $liveChicks,
            'current_bird_count' => $liveChicks,
            'transferred_incubation_cost' => round($accumulatedCost, 2),
            'house_rent_total' => $houseRent,
            'target_profit_margin_pct' => $targetMargin,
            'target_selling_price' => $targetSellingPrice,
            'start_date' => date('Y-m-d'),
            'status' => 'Active',
        ]);

        $this->db->update('incubation_batches', $incubationBatchId, ['status' => 'Moved to Production']);

        return $prodId;
    }

    /**
     * Module 2: Add or restock bulk feed or medication and compute exact Cost per Gram / ml.
     */
    public function addInventoryItem(array $input): int
    {
        $name = trim((string) ($input['item_name'] ?? 'Feed / Med Stock'));
        $category = in_array($input['category'] ?? '', ['starter', 'grower', 'layer', 'medicine'], true)
            ? $input['category']
            : 'starter';

        $inputUnitMode = $input['input_unit_mode'] ?? 'kg'; // kg, g, l, ml
        $rawQty = max(0.0001, (float) ($input['quantity_value'] ?? 1));
        $totalCost = max(0.0, (float) ($input['total_cost'] ?? 0));

        if ($category !== 'medicine') {
            $unitType = 'g';
            $totalGramsOrMl = ($inputUnitMode === 'g') ? $rawQty : ($rawQty * 1000.0);
        } else {
            if ($inputUnitMode === 'kg') {
                $unitType = 'g';
                $totalGramsOrMl = $rawQty * 1000.0;
            } elseif ($inputUnitMode === 'g') {
                $unitType = 'g';
                $totalGramsOrMl = $rawQty;
            } elseif ($inputUnitMode === 'l') {
                $unitType = 'ml';
                $totalGramsOrMl = $rawQty * 1000.0;
            } else {
                $unitType = 'ml';
                $totalGramsOrMl = $rawQty;
            }
        }

        $totalGramsOrMl = round(max(0.0001, $totalGramsOrMl), 4);

        // Exact formula: Cost per Gram / ml = Total Purchase Price / Total Weight in Grams (or Volume in ml)
        $unitCostPerGramOrMl = round($totalCost / $totalGramsOrMl, 6);

        $itemId = $this->db->insert('inventory_items', [
            'item_name' => $name,
            'category' => $category,
            'unit_type' => $unitType,
            'initial_quantity_g_or_ml' => $totalGramsOrMl,
            'total_quantity_g_or_ml' => $totalGramsOrMl,
            'total_cost' => round($totalCost, 2),
            'unit_cost_per_g_or_ml' => $unitCostPerGramOrMl,
            'purchase_date' => !empty($input['purchase_date']) ? (string) $input['purchase_date'] : date('Y-m-d'),
        ]);

        $this->addAiMemory(
            "Inventory stocked: {$name} ({$category}) — " . number_format($totalGramsOrMl, 0) . " " . ($unitType === 'Weight' ? 'g' : 'ml') . " at ₦" . number_format($totalCost, 2) . ".",
            'inventory'
        );

        return $itemId;
    }

    /**
     * Module 3: Create a direct Production Batch (or update House Rent / Target Margin).
     */
    public function createProductionBatch(array $input): int
    {
        $startDate = !empty($input['start_date']) ? (string) $input['start_date'] : date('Y-m-d');
        $batchCode = trim((string) ($input['batch_code'] ?? ''));
        if ($batchCode === '') {
            $batchCode = $this->generateDateBatchCode('PRD', $startDate);
        }

        $birds = max(1, (int) ($input['initial_bird_count'] ?? 1000));
        $initialAgeDays = max(1, (int) ($input['initial_age_days'] ?? 1));
        $docCost = max(0.0, (float) ($input['transferred_incubation_cost'] ?? 0));
        $houseRent = isset($input['house_rent_total']) && $input['house_rent_total'] !== ''
            ? max(0.0, (float) $input['house_rent_total'])
            : 0.00;
        $costPerChick = $docCost / $birds;
        $sellingPrice = isset($input['selling_price_per_bird']) && (float) $input['selling_price_per_bird'] > 0
            ? round((float) $input['selling_price_per_bird'], 2)
            : 0.0;

        $targetMargin = isset($input['target_profit_margin_pct']) && $input['target_profit_margin_pct'] !== ''
            ? max(0.0, (float) $input['target_profit_margin_pct'])
            : 30.00;
        $targetSellingPrice = $sellingPrice > 0 ? $sellingPrice : round($costPerChick * (1 + ($targetMargin / 100)), 2);

        return $this->db->insert('production_batches', [
            'batch_code' => $batchCode,
            'incubation_batch_id' => null,
            'flock_type' => trim((string) ($input['flock_type'] ?? 'Commercial Flock (Direct DOC)')),
            'initial_bird_count' => $birds,
            'current_bird_count' => $birds,
            'transferred_incubation_cost' => $docCost,
            'house_rent_total' => $houseRent,
            'target_profit_margin_pct' => $targetMargin,
            'target_selling_price' => $targetSellingPrice,
            'initial_age_days' => $initialAgeDays,
            'start_date' => $input['start_date'] ?: date('Y-m-d'),
            'status' => 'Active',
        ]);
    }

    public function setAnnualFarmRent(float $annualRent): void
    {
        $this->db->setSetting('annual_farm_rent', round(max(0.0, $annualRent), 2));
    }

    public function addGeneralFarmExpense(array $input): int
    {
        $amount = max(0.01, (float) ($input['amount'] ?? 0));
        return $this->db->insert('general_farm_expenses', [
            'expense_date' => !empty($input['expense_date']) ? $input['expense_date'] : date('Y-m-d'),
            'category' => trim((string) ($input['category'] ?? 'Generator Fuel')),
            'description' => trim((string) ($input['description'] ?? 'General Farm Expense')),
            'amount' => round($amount, 2),
        ]);
    }

    public function getGeneralFarmExpenses(): array
    {
        $rows = $this->db->getTable('general_farm_expenses');
        usort($rows, fn($a, $b) => strcmp($b['expense_date'] ?? '', $a['expense_date'] ?? '') ?: ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0)));
        return $rows;
    }

    public function getGeneralRentMetrics(): array
    {
        if (($cached = $this->getMemo('rent')) !== null) {
            return $cached;
        }
        $annualRent = max(0.0, (float) $this->db->getSetting('annual_farm_rent', 0));
        $dailyRent = $annualRent > 0 ? round($annualRent / 365.0, 2) : 0.0;

        $generalExpenses = $this->db->getTable('general_farm_expenses');
        $totalGeneralExpenses = 0.0;
        foreach ($generalExpenses as $ge) {
            $totalGeneralExpenses += (float) ($ge['amount'] ?? 0);
        }
        $dailyGeneralExpenses = $totalGeneralExpenses > 0 ? round($totalGeneralExpenses / 30.0, 2) : 0.0;
        $combinedDaily = ($annualRent > 0 ? ($annualRent / 365.0) : 0.0) + ($totalGeneralExpenses > 0 ? ($totalGeneralExpenses / 30.0) : 0.0);

        $batches = $this->db->getTable('production_batches');
        $logs = $this->db->getTable('production_daily_logs');
        $sales = $this->db->getTable('batch_sales');

        $mortalityByBatch = [];
        foreach ($logs as $l) {
            $bid = (int) $l['production_batch_id'];
            $mortalityByBatch[$bid] = ($mortalityByBatch[$bid] ?? 0) + (int) $l['mortality_count'];
        }
        $soldByBatch = [];
        foreach ($sales as $s) {
            $bid = (int) $s['production_batch_id'];
            $soldByBatch[$bid] = ($soldByBatch[$bid] ?? 0) + (int) $s['birds_sold'];
        }

        $totalLiveBirds = 0;
        foreach ($batches as $b) {
            if (($b['status'] ?? 'Active') === 'Active') {
                $bid = (int) $b['id'];
                $init = max(1, (int) $b['initial_bird_count']);
                $totalLiveBirds += max(0, $init - ($mortalityByBatch[$bid] ?? 0) - ($soldByBatch[$bid] ?? 0));
            }
        }

        $rentPerBirdPerDay = ($combinedDaily > 0 && $totalLiveBirds > 0)
            ? round($combinedDaily / $totalLiveBirds, 4)
            : 0.0;

        return $this->setMemo('rent', [
            'annual_rent' => $annualRent,
            'daily_rent' => $dailyRent,
            'total_general_expenses' => round($totalGeneralExpenses, 2),
            'daily_general_expenses' => $dailyGeneralExpenses,
            'combined_daily_overhead' => round($combinedDaily, 2),
            'total_live_birds' => $totalLiveBirds,
            'rent_per_bird_per_day' => $rentPerBirdPerDay,
        ]);
    }

    public function updateProductionBatchConfig(int $batchId, array $input): bool
    {
        $updates = [];
        if (isset($input['house_rent_total'])) {
            $updates['house_rent_total'] = max(0.0, (float) $input['house_rent_total']);
        }
        if (isset($input['target_profit_margin_pct'])) {
            $updates['target_profit_margin_pct'] = max(0.0, (float) $input['target_profit_margin_pct']);
        }
        return $this->db->update('production_batches', $batchId, $updates);
    }

    /**
     * Module 3: Daily Batch Input Form (Mortality, Feed in grams, Water L, Medication in g/ml, Labor, Sanitation).
     * Automatically deducts grams/ml from inventory and adds exact Naira value to batch ledger.
     */
    public function addProductionDailyLog(array $input): int
    {
        $batchId = (int) ($input['production_batch_id'] ?? 0);
        $batch = $this->db->findById('production_batches', $batchId);
        if (!$batch) {
            throw new RuntimeException('Selected Production Batch does not exist.');
        }

        $mortality = max(0, (int) ($input['mortality_count'] ?? 0));
        $waterLiters = round(max(0.0, (float) ($input['water_liters'] ?? 0)), 4);

        // Feed consumption & spilled/wasted feed (in grams)
        $feedItemId = !empty($input['feed_item_id']) ? (int) $input['feed_item_id'] : null;
        $feedGrams = round(max(0.0, (float) ($input['feed_grams'] ?? 0)), 4);
        $wastedFeedGrams = round(max(0.0, (float) ($input['wasted_feed_grams'] ?? 0)), 4);
        $feedCostCalculated = 0.0;
        $wastedFeedCost = 0.0;

        if ($feedItemId && ($feedGrams > 0 || $wastedFeedGrams > 0)) {
            $feedItem = $this->db->findById('inventory_items', $feedItemId);
            if ($feedItem) {
                $unitCost = (float) $feedItem['unit_cost_per_g_or_ml'];
                $feedCostCalculated = round($feedGrams * $unitCost, 2);
                $wastedFeedCost = round($wastedFeedGrams * $unitCost, 2);
                $totalDeduct = round($feedGrams + $wastedFeedGrams, 4);
                $newRemaining = max(0.0, (float) $feedItem['total_quantity_g_or_ml'] - $totalDeduct);
                $this->db->update('inventory_items', $feedItemId, [
                    'total_quantity_g_or_ml' => round($newRemaining, 4),
                ]);
            }
        }

        // Medication dosages (multi-checkbox med_items[] + med_doses[] OR legacy single med_item_id + med_dose_g_ml)
        $medicationsUsed = [];
        $medItemId = null;
        $medDose = 0.0;
        $medCostCalculated = 0.0;

        $checkedMedIds = isset($input['med_items']) && is_array($input['med_items']) ? $input['med_items'] : [];
        $medDosesMap = isset($input['med_doses']) && is_array($input['med_doses']) ? $input['med_doses'] : [];

        if (!empty($checkedMedIds)) {
            foreach ($checkedMedIds as $rawId) {
                $id = (int) $rawId;
                $dose = round(max(0.0, (float) ($medDosesMap[$id] ?? $medDosesMap[(string) $id] ?? 0)), 4);
                if ($id > 0 && $dose > 0) {
                    $medItem = $this->db->findById('inventory_items', $id);
                    if ($medItem) {
                        $unitCost = (float) $medItem['unit_cost_per_g_or_ml'];
                        $cost = round($dose * $unitCost, 2);
                        $newRemaining = max(0.0, (float) $medItem['total_quantity_g_or_ml'] - $dose);
                        $this->db->update('inventory_items', $id, [
                            'total_quantity_g_or_ml' => round($newRemaining, 4),
                        ]);
                        $medicationsUsed[] = [
                            'item_id' => $id,
                            'item_name' => $medItem['item_name'],
                            'dose' => $dose,
                            'unit' => $medItem['unit_type'] ?? 'ml',
                            'cost' => $cost,
                        ];
                        $medDose = round($medDose + $dose, 4);
                        $medCostCalculated += $cost;
                        if ($medItemId === null) {
                            $medItemId = $id;
                        }
                    }
                }
            }
            $medCostCalculated = round($medCostCalculated, 2);
        } else {
            $medItemId = !empty($input['med_item_id']) ? (int) $input['med_item_id'] : null;
            $medDose = round(max(0.0, (float) ($input['med_dose_g_ml'] ?? 0)), 4);
            if ($medItemId && $medDose > 0) {
                $medItem = $this->db->findById('inventory_items', $medItemId);
                if ($medItem) {
                    $unitCost = (float) $medItem['unit_cost_per_g_or_ml'];
                    $medCostCalculated = round($medDose * $unitCost, 2);
                    $newRemaining = max(0.0, (float) $medItem['total_quantity_g_or_ml'] - $medDose);
                    $this->db->update('inventory_items', $medItemId, [
                        'total_quantity_g_or_ml' => round($newRemaining, 4),
                    ]);
                    $medicationsUsed[] = [
                        'item_id' => $medItemId,
                        'item_name' => $medItem['item_name'],
                        'dose' => $medDose,
                        'unit' => $medItem['unit_type'] ?? 'ml',
                        'cost' => $medCostCalculated,
                    ];
                }
            }
        }

        $laborCost = max(0.0, (float) ($input['labor_cost'] ?? 0));
        $sanitationCost = max(0.0, (float) ($input['sanitation_cost'] ?? 0));
        $waterOtherCost = max(0.0, (float) ($input['water_other_cost'] ?? 0));
        $goodEggs = max(0, (int) ($input['good_eggs'] ?? 0));
        $crackedEggs = max(0, (int) ($input['cracked_eggs'] ?? 0));

        $logId = $this->db->insert('production_daily_logs', [
            'production_batch_id' => $batchId,
            'log_date' => $input['log_date'] ?: date('Y-m-d'),
            'mortality_count' => $mortality,
            'good_eggs' => $goodEggs,
            'cracked_eggs' => $crackedEggs,
            'water_liters' => $waterLiters,
            'feed_item_id' => $feedItemId,
            'feed_grams' => $feedGrams,
            'feed_cost_calculated' => $feedCostCalculated,
            'wasted_feed_grams' => $wastedFeedGrams,
            'wasted_feed_cost' => $wastedFeedCost,
            'med_item_id' => $medItemId,
            'med_dose_g_ml' => $medDose,
            'med_cost_calculated' => $medCostCalculated,
            'medications_used' => $medicationsUsed,
            'labor_cost' => $laborCost,
            'sanitation_cost' => $sanitationCost,
            'water_other_cost' => $waterOtherCost,
            'is_auto_logged' => !empty($input['is_auto_logged']),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ]);

        // Immediately reduce active live bird count
        if ($mortality > 0) {
            $newBirdCount = max(0, (int) $batch['current_bird_count'] - $mortality);
            $this->db->update('production_batches', $batchId, [
                'current_bird_count' => $newBirdCount,
            ]);
            if ($batch['current_bird_count'] > 0 && ($mortality / max(1, (int) $batch['current_bird_count'])) >= 0.01) {
                $this->addAiMemory(
                    "Mortality spike in {$batch['batch_code']} ({$batch['flock_type']}): {$mortality} birds died on " . ($input['log_date'] ?: date('Y-m-d')) . ".",
                    'flock_insight'
                );
            }
        }

        if ($medItemId && $medDose > 0) {
            $med = $this->db->findById('inventory_items', $medItemId);
            if ($med) {
                $this->addAiMemory(
                    "Treatment logged for {$batch['batch_code']}: {$med['item_name']} ({$medDose} " . ($med['unit_type'] === 'Weight' ? 'g' : 'ml') . ") on " . ($input['log_date'] ?: date('Y-m-d')) . ".",
                    'flock_insight'
                );
            }
        }

        if ($goodEggs > 0) {
            $this->addAiMemory(
                "Eggs collected from production flock {$batch['batch_code']} ({$batch['flock_type']}): {$goodEggs} good eggs, {$crackedEggs} cracked.",
                'layers'
            );
        }

        return $logId;
    }

    /**
     * Module 4: Record commercial sale transaction & deduct birds from live batch inventory.
     */
    public function recordBatchSale(array $input): int
    {
        $batchId = (int) ($input['production_batch_id'] ?? 0);
        $batch = $this->db->findById('production_batches', $batchId);
        if (!$batch) {
            throw new RuntimeException('Selected Production Batch does not exist.');
        }

        $birdsSold = max(1, (int) ($input['birds_sold'] ?? 1));
        $logs = $this->db->where('production_daily_logs', 'production_batch_id', $batchId);
        $existingSales = $this->db->where('batch_sales', 'production_batch_id', $batchId);
        $totalMortality = array_sum(array_map(fn($l) => (int) ($l['mortality_count'] ?? 0), $logs));
        $totalAlreadySold = array_sum(array_map(fn($s) => (int) ($s['birds_sold'] ?? 0), $existingSales));
        $initialBirds = max(1, (int) ($batch['initial_bird_count'] ?? 1));
        $availableBirds = max(0, $initialBirds - $totalMortality - $totalAlreadySold);

        if ($availableBirds <= 0) {
            throw new RuntimeException("No unsold live birds remaining in {$batch['batch_code']}.");
        }
        if ($birdsSold > $availableBirds) {
            throw new RuntimeException("Cannot sell {$birdsSold} bird(s): only {$availableBirds} unsold bird(s) available in {$batch['batch_code']}.");
        }

        $unitPrice = max(0.0, (float) ($input['unit_price'] ?? 0));
        $totalAmount = round($birdsSold * $unitPrice, 2);
        $rawStatus = in_array($input['payment_status'] ?? '', ['Paid', 'Partial', 'Pending'], true)
            ? $input['payment_status']
            : 'Paid';

        if (isset($input['amount_paid']) && $input['amount_paid'] !== '') {
            $amountPaid = round(min($totalAmount, max(0.0, (float) $input['amount_paid'])), 2);
        } elseif ($rawStatus === 'Paid') {
            $amountPaid = $totalAmount;
        } else {
            $amountPaid = 0.0;
        }
        $balanceDue = round(max(0.0, $totalAmount - $amountPaid), 2);
        $status = $balanceDue <= 0 ? 'Paid' : ($amountPaid > 0 ? 'Partial' : 'Pending');

        $saleId = $this->db->insert('batch_sales', [
            'production_batch_id' => $batchId,
            'sale_date' => $input['sale_date'] ?: date('Y-m-d'),
            'buyer_name' => trim((string) ($input['buyer_name'] ?? 'Commercial Buyer')),
            'buyer_contact' => trim((string) ($input['buyer_contact'] ?? '')),
            'birds_sold' => $birdsSold,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'balance_due' => $balanceDue,
            'payment_status' => $status,
        ]);

        $newCount = max(0, $availableBirds - $birdsSold);
        $this->db->update('production_batches', $batchId, [
            'current_bird_count' => $newCount,
            'status' => $newCount === 0 ? 'Depleted' : 'Active',
        ]);

        $this->addAiMemory(
            "Market price realized on {$batch['batch_code']} ({$batch['flock_type']}): {$birdsSold} birds sold at ₦" . number_format($unitPrice, 2) . "/bird.",
            'sales'
        );

        return $saleId;
    }

    public function recordSalePayment(int $saleId, float $paymentAmount): bool
    {
        $sale = $this->db->findById('batch_sales', $saleId);
        if (!$sale) {
            throw new RuntimeException('Sale record not found.');
        }
        $totalAmount = (float) ($sale['total_amount'] ?? 0);
        $currentPaid = isset($sale['amount_paid'])
            ? (float) $sale['amount_paid']
            : (($sale['payment_status'] ?? '') === 'Paid' ? $totalAmount : 0.0);

        $newPaid = round(min($totalAmount, $currentPaid + max(0.0, $paymentAmount)), 2);
        $newBalance = round(max(0.0, $totalAmount - $newPaid), 2);
        $newStatus = $newBalance <= 0 ? 'Paid' : ($newPaid > 0 ? 'Partial' : 'Pending');

        return $this->db->update('batch_sales', $saleId, [
            'amount_paid' => $newPaid,
            'balance_due' => $newBalance,
            'payment_status' => $newStatus,
        ]);
    }

    /**
     * Real-time Clinical & Operational Reminder Engine
     * Evaluates all active flocks against Dr. Kosiella's veterinary schedules.
     */
    public function getActiveReminders(): array
    {
        if (($cached = $this->getMemo('rem')) !== null) {
            return $cached;
        }
        $reminders = [];
        $prodBatches = $this->getProductionBatchesEnriched();
        $today = date('Y-m-d');

        $chickenSchedule = VeterinaryKnowledgeBase::getChickenSchedule();
        $turkeySchedule = VeterinaryKnowledgeBase::getTurkeySchedule();

        foreach ($prodBatches as $pb) {
            if ($pb['status'] !== 'Active') {
                continue;
            }

            $hasMedLoggedToday = false;
            $hasFeedLoggedToday = false;
            foreach ($pb['logs'] ?? [] as $log) {
                if (($log['log_date'] ?? '') === $today) {
                    if ((float) ($log['med_dose_g_ml'] ?? 0) > 0 || !empty($log['med_item_id'])) {
                        $hasMedLoggedToday = true;
                    }
                    if ((float) ($log['feed_grams'] ?? 0) > 0 || !empty($log['feed_item_id'])) {
                        $hasFeedLoggedToday = true;
                    }
                }
            }

            $ageDays = (int) $pb['age_days'];
            $batchCode = $pb['batch_code'];
            $batchName = trim((string) ($pb['flock_type'] ?? 'Production Flock'));
            $isTurkey = stripos($batchName, 'turkey') !== false;
            $schedule = $isTurkey ? $turkeySchedule : $chickenSchedule;

            // 1. Check if an exact vaccination or medication is due today (skip if already logged today)
            foreach ($schedule as $dayKey => $item) {
                $matchesToday = false;
                if ($dayKey === $ageDays) {
                    $matchesToday = true;
                } elseif (preg_match('/Day\s*(\d+)(?:[–-](\d+))?/', $item['day_range'], $matches)) {
                    $startDay = (int) $matches[1];
                    $endDay = isset($matches[2]) ? (int) $matches[2] : $startDay;
                    if ($ageDays >= $startDay && $ageDays <= $endDay) {
                        $matchesToday = true;
                    }
                }

                if ($matchesToday && !$hasMedLoggedToday) {
                    $reminders[] = [
                        'id' => 'due_' . $pb['id'] . '_' . $dayKey,
                        'level' => $item['is_critical'] ? 'critical' : 'warning',
                        'species' => $isTurkey ? 'Turkey' : 'Chicken',
                        'flock_code' => $batchCode,
                        'batch_name' => $batchName,
                        'batch_id' => $pb['id'],
                        'day_number' => $ageDays,
                        'title' => ($item['is_critical'] ? '🚨 DUE TODAY: ' : '📋 TODAY: ') . $item['target'],
                        'category' => $item['category'],
                        'purpose' => $item['purpose'],
                        'method' => $item['method'],
                        'pre_warning' => $item['pre_warning'],
                        'is_due_today' => true,
                    ];
                }

                // 2. Pre-vaccine alert: If tomorrow is a critical vaccine day
                if ($dayKey === ($ageDays + 1) && $item['is_critical']) {
                    $reminders[] = [
                        'id' => 'pre_' . $pb['id'] . '_' . $dayKey,
                        'level' => 'warning',
                        'species' => $isTurkey ? 'Turkey' : 'Chicken',
                        'flock_code' => $batchCode,
                        'batch_name' => $batchName,
                        'batch_id' => $pb['id'],
                        'day_number' => $ageDays,
                        'title' => '⏳ PRE-VACCINE 24H: ' . $item['target'] . ' Due Tomorrow',
                        'category' => 'Pre-Vaccine Action',
                        'purpose' => 'STOP all antibiotics & anticoccidials immediately. Provide clean chlorine-free water.',
                        'method' => 'Flush drinkers, prepare cold chain storage (2–8°C)',
                        'pre_warning' => $item['pre_warning'] ?? 'Live vaccine virus will be deactivated by antibiotic residues or chlorine.',
                        'is_due_today' => false,
                    ];
                }
            }

            // 3. Feed transition alert (skip if feed already logged today)
            if (!empty($pb['feed_alert']['needs_transition']) && !$hasFeedLoggedToday) {
                $reminders[] = [
                    'id' => 'feed_' . $pb['id'],
                    'level' => 'info',
                    'species' => $isTurkey ? 'Turkey' : 'Chicken',
                    'flock_code' => $batchCode,
                    'batch_name' => $batchName,
                    'batch_id' => $pb['id'],
                    'day_number' => $ageDays,
                    'title' => '🌾 Feed Transition: Switch to ' . $pb['feed_alert']['recommended_stage'],
                    'category' => 'Nutrition Alert',
                    'purpose' => 'Flock age matches ' . $pb['feed_alert']['recommended_stage'] . ' crude protein curve.',
                    'method' => 'Gradual 48h blend transition',
                    'pre_warning' => null,
                    'is_due_today' => true,
                ];
            }
        }

        // 4. Incubation Stage Reminders (Candling, Transfer to Hatcher, Breakout Due)
        $incBatches = $this->getIncubationBatchesEnriched();
        $incDailyLogs = $this->db->getTable('incubation_daily_logs');
        $candledTodayIds = [];
        foreach ($incDailyLogs as $idl) {
            if (($idl['log_date'] ?? '') === $today) {
                $candledTodayIds[(int) $idl['incubation_batch_id']] = true;
            }
        }
        foreach ($incBatches as $ib) {
            if ($ib['status'] !== 'Active') {
                continue;
            }
            $hasCandledToday = !empty($candledTodayIds[(int) $ib['id']]);
            $day = (int) $ib['incubation_day'];
            $prof = $ib['species_profile'];
            $incBatchName = trim((string) ($ib['parent_flock_info'] ?? $ib['species_label']));
            if (!empty($ib['is_breakout_due'])) {
                $reminders[] = [
                    'id' => 'inc_breakout_' . $ib['id'],
                    'level' => 'critical',
                    'species' => $ib['species_label'],
                    'flock_code' => $ib['batch_code'],
                    'batch_name' => $incBatchName,
                    'batch_id' => $ib['id'],
                    'day_number' => $day,
                    'title' => '🐣 BREAKOUT DUE: ' . $ib['batch_code'] . ' (' . $ib['species_label'] . ')',
                    'category' => 'Hatchery Breakout',
                    'purpose' => 'Total ' . $ib['total_days'] . '-day incubation cycle reached. Sort Grade-A chicks, culls, and transfer to brooding.',
                    'method' => 'Module 1 → Record Final Breakout & Transfer',
                    'pre_warning' => 'Ensure brooder pen is pre-warmed (Chickens: 33–35°C, Turkeys: 35–37°C) with antistress water ready.',
                    'is_due_today' => true,
                ];
            } elseif (!empty($ib['is_transfer_due'])) {
                $reminders[] = [
                    'id' => 'inc_transfer_' . $ib['id'],
                    'level' => 'warning',
                    'species' => $ib['species_label'],
                    'flock_code' => $ib['batch_code'],
                    'batch_name' => $incBatchName,
                    'batch_id' => $ib['id'],
                    'day_number' => $day,
                    'title' => '🔄 TRANSFER TO HATCHER: ' . $ib['batch_code'] . ' (Final ' . $prof['hatcher_days'] . ' Days)',
                    'category' => 'Incubator Transfer',
                    'purpose' => 'Move eggs from Setter to Hatcher baskets. STOP egg turning!',
                    'method' => 'Temp: ' . $prof['hatcher_temp_c'] . ' | Raise Humidity: ' . $prof['hatcher_humidity'],
                    'pre_warning' => 'Perform final transfer candling to remove dead-in-shell before lockdown.',
                    'is_due_today' => true,
                ];
            } elseif (in_array($day, $prof['candling_days'], true) && !$hasCandledToday) {
                $reminders[] = [
                    'id' => 'inc_candle_' . $ib['id'] . '_' . $day,
                    'level' => 'warning',
                    'species' => $ib['species_label'],
                    'flock_code' => $ib['batch_code'],
                    'batch_name' => $incBatchName,
                    'batch_id' => $ib['id'],
                    'day_number' => $day,
                    'title' => '🔦 CANDLING CHECK: ' . $ib['batch_code'] . ' (Day ' . $day . ')',
                    'category' => 'Setter Candling',
                    'purpose' => 'Candle trays to remove infertile clears and early embryonic deaths.',
                    'method' => 'Module 1 → Log Candling Counts & Temp/RH',
                    'pre_warning' => $prof['notes'],
                    'is_due_today' => true,
                ];
            }
        }

        // 5. Low-Stock Feed & Medication Alerts
        foreach ($this->getInventoryItemsEnriched() as $item) {
            if (!empty($item['low_stock'])) {
                $reminders[] = [
                    'id' => 'low_stock_' . $item['id'],
                    'level' => ($item['stock_pct'] <= 5) ? 'critical' : 'warning',
                    'species' => 'Inventory',
                    'flock_code' => strtoupper((string) $item['category']),
                    'batch_name' => $item['item_name'],
                    'day_number' => 0,
                    'title' => '⚠️ LOW STOCK: ' . $item['item_name'] . ' (' . $item['stock_pct'] . '% left)',
                    'category' => 'Inventory Alert',
                    'purpose' => 'Remaining: ' . number_format((float) $item['total_quantity_g_or_ml']) . $item['unit_label'] . ' (' . $item['remaining_bulk'] . ' ' . $item['bulk_unit_label'] . '). Restock soon to prevent feeding or treatment gaps.',
                    'method' => 'Module 2 → Log Bulk Purchase',
                    'pre_warning' => null,
                    'is_due_today' => true,
                ];
            }
        }

        // 6. Smart Clinical Cross-Checks (24h Pre-Vaccine Antibiotic Conflict & Mortality Spike)
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $prodLogs = $this->db->getTable('production_daily_logs');
        $batches = $this->db->getTable('production_batches');
        foreach ($batches as $batch) {
            if (($batch['status'] ?? '') !== 'Active') {
                continue;
            }
            $bId = (int) $batch['id'];
            $liveBirds = max(1, (int) ($batch['current_bird_count'] ?? 0));
            $bName = trim((string) ($batch['flock_type'] ?? $batch['batch_code']));

            $todayMortality = 0;
            $recentMed = false;
            foreach ($prodLogs as $pl) {
                if ((int) ($pl['production_batch_id'] ?? 0) !== $bId) {
                    continue;
                }
                $d = (string) ($pl['log_date'] ?? '');
                if ($d === $today) {
                    $todayMortality += (int) ($pl['mortality_count'] ?? 0);
                }
                if (($d === $today || $d === $yesterday) && ((!empty($pl['med_item_id']) && (float) ($pl['med_dose_g_ml'] ?? 0) > 0) || !empty($pl['medications_used']))) {
                    $recentMed = true;
                }
            }

            if ($todayMortality > 0 && ($todayMortality / $liveBirds) >= 0.01) {
                $pct = round(($todayMortality / $liveBirds) * 100, 2);
                $reminders[] = [
                    'id' => 'mortality_spike_' . $bId . '_' . $today,
                    'level' => 'critical',
                    'species' => $bName,
                    'flock_code' => $batch['batch_code'],
                    'batch_name' => $bName,
                    'batch_id' => $bId,
                    'day_number' => (int) ($batch['age_days'] ?? 0),
                    'title' => "🚨 MORTALITY SPIKE ({$pct}%): {$batch['batch_code']} ({$todayMortality} birds today)",
                    'category' => 'Clinical Inspection',
                    'purpose' => 'Daily mortality exceeded 1%. Check brooding temperature, wet litter (Coccidiosis), ventilation (CRD), and isolate sick birds immediately.',
                    'method' => 'Ask Farm AI for MSD disease differential & exact flock dosage',
                    'pre_warning' => 'Rule out notifiable Avian Influenza if sudden mass mortality or head swelling occurs.',
                    'is_due_today' => true,
                ];
            }

            if ($recentMed) {
                foreach ($reminders as $existingRem) {
                    if ((int) ($existingRem['batch_id'] ?? 0) === $bId && ($existingRem['category'] ?? '') === 'Vaccine' && !empty($existingRem['is_due_today'])) {
                        $reminders[] = [
                            'id' => 'prevax_abx_conflict_' . $bId . '_' . $today,
                            'level' => 'critical',
                            'species' => $bName,
                            'flock_code' => $batch['batch_code'],
                            'batch_name' => $bName,
                            'batch_id' => $bId,
                            'day_number' => (int) ($existingRem['day_number'] ?? 0),
                            'title' => "⛔ PRE-VACCINE ANTIBIOTIC CONFLICT: {$batch['batch_code']}",
                            'category' => 'Vaccine Safety',
                            'purpose' => 'Medication was logged within the last 24h while a live vaccine is due today. Flush with plain chlorine-free water + skimmed milk (2g/L) before vaccinating.',
                            'method' => 'Wait 24h antibiotic-free window before administering live vaccine',
                            'pre_warning' => 'Antibiotics and acidifiers neutralize live viral vaccines.',
                            'is_due_today' => true,
                        ];
                        break;
                    }
                }
            }
        }

        // 7. Rainy Season Biosecurity check
        $reminders[] = [
            'id' => 'rainy_season_check',
            'level' => 'info',
            'species' => 'General Farm',
            'flock_code' => 'ALL PENS',
            'batch_name' => 'All Active Pens',
            'day_number' => 0,
            'title' => '🌧️ Rainy Season Protocol: Coccidiosis & Aflatoxin Check',
            'category' => 'Seasonal Biosecurity',
            'purpose' => 'Inspect litter under drinkers for wet caking; inspect feed bags for dampness on pallets.',
            'method' => 'Rake wet shavings, provide dry bedding, clear standing mosquito puddles within 20m.',
            'pre_warning' => null,
            'is_due_today' => false,
        ];

        // Keep all unlogged reminders visible; mark read state without hiding them
        $readMap = $this->getReadRemindersMap();
        foreach ($reminders as $idx => $rem) {
            $reminders[$idx]['is_read'] = (($readMap[$rem['id']] ?? '') === $today);
        }

        return $this->setMemo('rem', $reminders);
    }

    private function getReadRemindersMap(): array
    {
        $raw = $this->db->getSetting('read_reminders_map', []);
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($raw) ? $raw : [];
    }

    public function markReminderAsRead(string $reminderId): array
    {
        $reminderId = trim($reminderId);
        if ($reminderId !== '') {
            $readMap = $this->getReadRemindersMap();
            $today = date('Y-m-d');
            // Prune stale dates from previous days
            $readMap = array_filter($readMap, fn($d) => $d === $today);
            $readMap[$reminderId] = $today;
            $this->db->setSetting('read_reminders_map', $readMap);
        }
        $unread = count(array_filter($this->getActiveReminders(), fn($r) => empty($r['is_read'])));
        return ['ok' => true, 'remaining' => $unread];
    }

    public function markAllRemindersRead(): array
    {
        $active = $this->getActiveReminders();
        $readMap = $this->getReadRemindersMap();
        $today = date('Y-m-d');
        $readMap = array_filter($readMap, fn($d) => $d === $today);
        foreach ($active as $rem) {
            $readMap[$rem['id']] = $today;
        }
        $this->db->setSetting('read_reminders_map', $readMap);
        return ['ok' => true, 'remaining' => 0];
    }

    /**
     * Persistent AI Self-Learning Memory Store
     */
    public function addAiMemory(string $fact, string $category = 'farm_rule'): int
    {
        $cleanFact = trim($fact);
        if ($cleanFact === '') {
            return 0;
        }
        $existing = $this->db->getTable('farm_ai_knowledge_memory');
        foreach ($existing as $row) {
            if (strcasecmp(trim((string) ($row['fact'] ?? '')), $cleanFact) === 0) {
                return (int) ($row['id'] ?? 0);
            }
        }
        if (count($existing) >= 100) {
            $oldestId = (int) ($existing[0]['id'] ?? 0);
            if ($oldestId > 0) {
                $this->db->delete('farm_ai_knowledge_memory', $oldestId);
            }
        }
        return $this->db->insert('farm_ai_knowledge_memory', [
            'category' => $category,
            'fact' => $cleanFact,
            'learned_date' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteAiMemory(int $id): bool
    {
        return $this->db->delete('farm_ai_knowledge_memory', $id);
    }

    public function getAiMemories(): array
    {
        return $this->db->getTable('farm_ai_knowledge_memory');
    }

    /**
     * Fixed Assets Register & Whole-Farm General Financial Statement
     */
    public function getFixedAssets(): array
    {
        $rows = $this->db->getTable('fixed_assets');
        $enriched = [];
        foreach ($rows as $row) {
            $cost = max(0.0, (float) ($row['purchase_cost'] ?? 0));
            $lifeMonths = max(1, (int) ($row['useful_life_months'] ?? 60));
            $monthlyDep = round($cost / $lifeMonths, 2);
            $enriched[] = array_merge($row, [
                'purchase_cost' => $cost,
                'useful_life_months' => $lifeMonths,
                'monthly_depreciation' => $monthlyDep,
                'daily_depreciation' => round($monthlyDep / 30.0, 2),
            ]);
        }
        return array_reverse($enriched);
    }

    public function addFixedAsset(array $input): int
    {
        $name = trim((string) ($input['asset_name'] ?? ''));
        $category = trim((string) ($input['category'] ?? 'Equipment'));
        $cost = max(0.0, (float) ($input['purchase_cost'] ?? 0));
        $lifeMonths = max(1, (int) ($input['useful_life_months'] ?? 60));
        if ($name === '' || $cost <= 0) {
            throw new InvalidArgumentException('Asset name and purchase cost (> 0) are required.');
        }

        return $this->db->insert('fixed_assets', [
            'asset_name' => $name,
            'category' => $category,
            'purchase_cost' => round($cost, 2),
            'useful_life_months' => $lifeMonths,
            'acquisition_date' => $input['acquisition_date'] ?: date('Y-m-d'),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ]);
    }

    public function deleteFixedAsset(int $id): bool
    {
        return $this->db->delete('fixed_assets', $id);
    }

    public function recordIncubationPayment(int $batchId, float $paymentAmount): bool
    {
        $batch = $this->db->findById('incubation_batches', $batchId);
        if (!$batch) {
            throw new RuntimeException('Incubation batch not found.');
        }
        $fee = (float) ($batch['service_fee'] ?? 0);
        $paid = (float) ($batch['amount_paid'] ?? 0);
        $newPaid = round(min($fee, $paid + max(0.0, $paymentAmount)), 2);
        $newBalance = round(max(0.0, $fee - $newPaid), 2);
        $newStatus = $newBalance <= 0 ? 'Paid' : ($newPaid > 0 ? 'Partial' : 'Pending');

        return $this->db->update('incubation_batches', $batchId, [
            'amount_paid' => $newPaid,
            'balance_due' => $newBalance,
            'payment_status' => $newStatus,
        ]);
    }

    public function markIncubationCollected(int $batchId): bool
    {
        $batch = $this->db->findById('incubation_batches', $batchId);
        if (!$batch) {
            throw new RuntimeException('Incubation batch not found.');
        }
        return $this->db->update('incubation_batches', $batchId, [
            'status' => 'Collected',
            'collected_date' => date('Y-m-d'),
        ]);
    }

    public function clearAllFarmData(): void
    {
        $this->db->clearAllFarmData();
    }

    public function getWholeFarmFinancials(): array
    {
        if (($cached = $this->getMemo('fin')) !== null) {
            return $cached;
        }
        $fixedAssets = $this->getFixedAssets();
        $rent = $this->getGeneralRentMetrics();
        $generalExpenses = $this->getGeneralFarmExpenses();
        $prodBatches = $this->getProductionBatchesEnriched();
        $incBatches = $this->getIncubationBatchesEnriched();
        $invItems = $this->getInventoryItemsEnriched();
        $sales = $this->db->getTable('batch_sales');

        $fixedCapitalTotal = 0.0;
        $monthlyDepTotal = 0.0;
        foreach ($fixedAssets as $fa) {
            $fixedCapitalTotal += (float) $fa['purchase_cost'];
            $monthlyDepTotal += (float) $fa['monthly_depreciation'];
        }
        $annualRent = (float) ($rent['annual_rent'] ?? $rent['annual_farm_rent'] ?? 0);
        $monthlyRent = round($annualRent / 12.0, 2);
        $totalGeneralOverhead = (float) ($rent['total_general_expenses'] ?? 0);
        $monthlyOverhead = (float) ($rent['monthly_general_expenses'] ?? $totalGeneralOverhead);

        $catTotalsMap = [];
        foreach ($generalExpenses as $ge) {
            $cat = trim((string) ($ge['category'] ?? 'General / Other')) ?: 'General / Other';
            if (!isset($catTotalsMap[$cat])) {
                $catTotalsMap[$cat] = ['category' => $cat, 'total' => 0.0, 'count' => 0];
            }
            $catTotalsMap[$cat]['total'] = round($catTotalsMap[$cat]['total'] + (float) ($ge['amount'] ?? 0), 2);
            $catTotalsMap[$cat]['count']++;
        }
        $generalExpensesByCategory = array_values($catTotalsMap);
        usort($generalExpensesByCategory, fn($a, $b) => $b['total'] <=> $a['total']);

        // Flow / Current Assets
        $liveFlockAssetValue = 0.0;
        $projectedLiveFlockMarketValue = 0.0;
        $totalDirectDoc = 0.0;
        $totalProdDirectDoc = 0.0;
        $totalFeedConsumed = 0.0;
        $totalMedsVaccines = 0.0;
        $totalLaborCost = 0.0;
        $totalSanitationCost = 0.0;
        $totalWaterOtherCost = 0.0;
        $totalLaborSanitOther = 0.0;
        $totalBatchOverheadShare = 0.0;
        $totalProdCost = 0.0;
        $totalProdRev = 0.0;
        $totalProdCollected = 0.0;
        $totalProdReceivables = 0.0;

        $batchRows = [];
        foreach ($prodBatches as $pb) {
            $m = $pb['metrics'];
            $remBirds = (int) ($m['current_in_house_birds'] ?? $m['remaining_live_birds'] ?? ($pb['current_bird_count'] ?? 0));
            $soldBirds = (int) ($m['total_birds_sold'] ?? $m['birds_sold'] ?? 0);
            $accumCost = (float) ($m['total_accumulated_cost'] ?? 0);
            $liveValue = ($soldBirds === 0 && $remBirds > 0)
                ? round($accumCost, 2)
                : round($remBirds * (float) ($m['cost_per_bird'] ?? 0), 2);
            $sellPricePerBird = (float) ($m['target_selling_price'] ?? $m['recommended_selling_price'] ?? 0);
            if (($pb['status'] ?? '') === 'Active') {
                $liveFlockAssetValue += $liveValue;
                $projectedLiveFlockMarketValue += round($remBirds * ($sellPricePerBird > 0 ? $sellPricePerBird : (float) ($m['cost_per_bird'] ?? 0)), 2);
            }
            $carriedBase = (float) ($m['incubation_cost'] ?? 0);
            $feedCost = (float) ($m['total_feed_cost'] ?? 0);
            $medCost = (float) ($m['total_med_cost'] ?? 0);
            $laborCost = (float) ($m['total_labor_cost'] ?? 0);
            $sanitCost = (float) ($m['total_sanitation_cost'] ?? 0);
            $waterOtherCost = (float) ($m['total_water_other_cost'] ?? 0);
            $otherOps = $laborCost + $sanitCost + $waterOtherCost;
            $rentShare = (float) ($m['house_rent'] ?? 0);
            $rev = (float) ($m['realized_revenue'] ?? $m['total_revenue'] ?? 0);
            $rec = (float) ($m['total_receivables'] ?? 0);
            $collected = round(max(0.0, $rev - $rec), 2);

            if (empty($pb['incubation_batch_id'])) {
                $totalDirectDoc += $carriedBase;
                $totalProdDirectDoc += $carriedBase;
            }
            $totalFeedConsumed += $feedCost;
            $totalMedsVaccines += $medCost;
            $totalLaborCost += $laborCost;
            $totalSanitationCost += $sanitCost;
            $totalWaterOtherCost += $waterOtherCost;
            $totalLaborSanitOther += $otherOps;
            $totalBatchOverheadShare += $rentShare;
            $totalProdCost += $accumCost;
            $totalProdRev += $rev;
            $totalProdCollected += $collected;
            $totalProdReceivables += $rec;

            $batchRows[] = [
                'stage' => 'Production',
                'batch_code' => $pb['batch_code'],
                'label' => $pb['flock_type'],
                'status' => $pb['status'],
                'units_label' => number_format((int) ($m['initial_birds'] ?? 0)) . ' init / ' . number_format($remBirds) . ' live / ' . number_format($soldBirds) . ' sold',
                'base_cost' => $carriedBase,
                'feed_med_cost' => $feedCost + $medCost,
                'overhead_other' => $otherOps,
                'variable_cost' => $accumCost,
                'total_cost' => $accumCost,
                'revenue_billed' => $rev,
                'cash_collected' => $collected,
                'receivables' => $rec,
                'gross_profit' => round($rev - $accumCost, 2),
                'net_profit' => round($rev - $accumCost, 2),
            ];
        }

        $incubatorEggAssetValue = 0.0;
        $totalIncubationSpend = 0.0;
        $totalIncubatorEggCost = 0.0;
        $totalIncubatorGasCost = 0.0;
        $totalIncubatorRepairCost = 0.0;
        $clientHatchBilled = 0.0;
        $clientHatchCollected = 0.0;
        $clientHatchReceivables = 0.0;
        foreach ($incBatches as $ib) {
            $incCost = (float) ($ib['metrics']['total_incubation_cost'] ?? $ib['total_batch_cost'] ?? 0);
            $ibEggCost = (float) ($ib['egg_cost'] ?? 0);
            $ibGasCost = (float) ($ib['metrics']['gas_cost'] ?? 0);
            $ibRepairCost = (float) ($ib['metrics']['repair_cost'] ?? 0);
            $totalIncubationSpend += $incCost;
            $totalIncubatorEggCost += $ibEggCost;
            $totalIncubatorGasCost += $ibGasCost;
            $totalIncubatorRepairCost += $ibRepairCost;
            $isClient = (($ib['ownership_type'] ?? 'Farm') === 'Client');
            if (!$isClient && ($ib['status'] ?? '') === 'Active') {
                $incubatorEggAssetValue += $incCost;
            }
            $svcFee = $isClient ? (float) ($ib['service_fee'] ?? 0) : 0.0;
            $svcPaid = $isClient ? (float) ($ib['amount_paid'] ?? 0) : 0.0;
            $svcDue = $isClient ? (float) ($ib['balance_due'] ?? 0) : 0.0;
            if ($isClient) {
                $clientHatchBilled += $svcFee;
                $clientHatchCollected += $svcPaid;
                $clientHatchReceivables += $svcDue;
            }
            $batchRows[] = [
                'stage' => $isClient ? 'Client Hatch' : 'Incubator',
                'batch_code' => $ib['batch_code'],
                'label' => ($ib['species_label'] ?? 'Hatchery') . ' • ' . ($ib['parent_flock_info'] ?? ''),
                'status' => $ib['status'],
                'units_label' => number_format((int) ($ib['eggs_set'] ?? 0)) . ' eggs • Day ' . (int) ($ib['incubation_day'] ?? 0),
                'base_cost' => $ibEggCost,
                'feed_med_cost' => 0.0,
                'overhead_other' => $ibGasCost + $ibRepairCost,
                'variable_cost' => $incCost,
                'total_cost' => $incCost,
                'revenue_billed' => $svcFee,
                'cash_collected' => $svcPaid,
                'receivables' => $svcDue,
                'gross_profit' => round($svcFee - $incCost, 2),
                'net_profit' => round($svcFee - $incCost, 2),
            ];
        }

        $storeInventoryValue = 0.0;
        $storeFeedValue = 0.0;
        $storeMedValue = 0.0;
        $totalInventoryPurchasedCost = 0.0;
        foreach ($invItems as $item) {
            $remVal = (float) ($item['total_quantity_g_or_ml'] ?? 0) * (float) ($item['unit_cost_per_g_or_ml'] ?? 0);
            $storeInventoryValue += $remVal;
            $totalInventoryPurchasedCost += (float) ($item['total_cost'] ?? 0);
            if (($item['category'] ?? '') === 'medicine') {
                $storeMedValue += $remVal;
            } else {
                $storeFeedValue += $remVal;
            }
        }
        $storeInventoryValue = round($storeInventoryValue, 2);

        $cashCollectedAll = $clientHatchCollected;
        $receivablesAll = $clientHatchReceivables;
        $billedAll = $clientHatchBilled;
        foreach ($sales as $s) {
            $tot = (float) ($s['total_amount'] ?? 0);
            $paid = isset($s['amount_paid']) ? (float) $s['amount_paid'] : (($s['payment_status'] ?? '') === 'Paid' ? $tot : 0.0);
            $due = isset($s['balance_due']) ? (float) $s['balance_due'] : max(0.0, $tot - $paid);
            $billedAll += $tot;
            $cashCollectedAll += $paid;
            $receivablesAll += $due;
        }

        // Unlinked incubator spend not yet pushed to production (or client batches)
        $untransferredIncCost = 0.0;
        foreach ($incBatches as $ib) {
            $isClient = (($ib['ownership_type'] ?? 'Farm') === 'Client');
            if ($isClient || empty($ib['linked_production_batch'])) {
                $untransferredIncCost += (float) ($ib['metrics']['total_incubation_cost'] ?? 0);
            }
        }

        // Layers Flocks & Egg Sales integration
        $layersOverview = $this->getLayersOverview();
        $totalLayerOperatingCost = 0.0;
        $totalLayerPurchaseCost = 0.0;
        $layerSalesByFlock = [];
        $unassignedEggBilled = 0.0;
        $unassignedEggCollected = 0.0;
        $unassignedEggDue = 0.0;
        $unassignedEggTotalEggs = 0;
        foreach ($layersOverview['sales'] as $es) {
            $lfId = (int) ($es['layer_flock_id'] ?? 0);
            $sTotal = (float) ($es['total_amount'] ?? 0);
            $sPaid = (float) ($es['amount_paid'] ?? 0);
            $sDue = (float) ($es['balance_due'] ?? 0);
            if ($lfId > 0) {
                if (!isset($layerSalesByFlock[$lfId])) {
                    $layerSalesByFlock[$lfId] = ['billed' => 0.0, 'collected' => 0.0, 'due' => 0.0];
                }
                $layerSalesByFlock[$lfId]['billed'] += $sTotal;
                $layerSalesByFlock[$lfId]['collected'] += $sPaid;
                $layerSalesByFlock[$lfId]['due'] += $sDue;
            } else {
                $unassignedEggBilled += $sTotal;
                $unassignedEggCollected += $sPaid;
                $unassignedEggDue += $sDue;
                $unassignedEggTotalEggs += (int) ($es['total_eggs'] ?? 0);
            }
        }

        foreach ($layersOverview['flocks'] as $lf) {
            $lfId = (int) $lf['id'];
            $lfTotalCost = (float) ($lf['total_cost'] ?? 0);
            $lfPurchase = (float) ($lf['purchase_cost'] ?? 0);
            if (($lf['status'] ?? '') === 'Active') {
                $liveFlockAssetValue += $lfTotalCost;
                $projectedLiveFlockMarketValue += $lfTotalCost;
            }
            $totalDirectDoc += $lfPurchase;
            $totalLayerPurchaseCost += $lfPurchase;
            $totalFeedConsumed += (float) ($lf['total_feed_cost'] ?? 0);
            $totalMedsVaccines += (float) ($lf['total_med_cost'] ?? 0);
            $totalLayerOperatingCost += $lfTotalCost;

            $lfBilled = round($layerSalesByFlock[$lfId]['billed'] ?? 0.0, 2);
            $lfCollected = round($layerSalesByFlock[$lfId]['collected'] ?? 0.0, 2);
            $lfDue = round($layerSalesByFlock[$lfId]['due'] ?? 0.0, 2);
            $batchRows[] = [
                'stage' => 'Layers',
                'batch_code' => $lf['batch_code'],
                'label' => ($lf['species_label'] ?? 'Layer') . ' • ' . $lf['flock_name'],
                'status' => $lf['status'],
                'units_label' => number_format((int) $lf['current_bird_count']) . ' live • ' . number_format((int) $lf['total_good_eggs']) . ' eggs',
                'base_cost' => round($lfPurchase, 2),
                'feed_med_cost' => round((float) ($lf['total_feed_cost'] ?? 0) + (float) ($lf['total_med_cost'] ?? 0), 2),
                'overhead_other' => 0.0,
                'variable_cost' => $lfTotalCost,
                'total_cost' => $lfTotalCost,
                'revenue_billed' => $lfBilled,
                'cash_collected' => $lfCollected,
                'receivables' => $lfDue,
                'gross_profit' => round($lfBilled - $lfTotalCost, 2),
                'net_profit' => round($lfBilled - $lfTotalCost, 2),
            ];
        }

        if ($unassignedEggBilled > 0) {
            $batchRows[] = [
                'stage' => 'Egg Sales',
                'batch_code' => 'EGG-POOL',
                'label' => 'Combined Egg Stock Sales',
                'status' => 'Completed',
                'units_label' => number_format($unassignedEggTotalEggs) . ' eggs sold',
                'base_cost' => 0.0,
                'feed_med_cost' => 0.0,
                'overhead_other' => 0.0,
                'variable_cost' => 0.0,
                'total_cost' => 0.0,
                'revenue_billed' => round($unassignedEggBilled, 2),
                'cash_collected' => round($unassignedEggCollected, 2),
                'receivables' => round($unassignedEggDue, 2),
                'gross_profit' => round($unassignedEggBilled, 2),
                'net_profit' => round($unassignedEggBilled, 2),
            ];
        }

        $eggStockroomValue = 0.0;
        $eggStockroomMarketValue = 0.0;
        $eggStockroomPcs = 0;
        foreach (($layersOverview['sources'] ?? []) as $src) {
            $availEggs = max(0, (int) ($src['available_eggs'] ?? 0));
            $eggStockroomPcs += $availEggs;
            $eggStockroomValue += round($availEggs * (float) ($src['cost_per_egg'] ?? 0), 2);
            $eggStockroomMarketValue += round($availEggs * (float) ($src['recommended_price_per_egg'] ?? ($src['cost_per_egg'] ?? 0)), 2);
        }

        $billedAll += (float) ($layersOverview['summary']['egg_sales_billed'] ?? 0);
        $cashCollectedAll += (float) ($layersOverview['summary']['egg_sales_collected'] ?? 0);
        $receivablesAll += (float) ($layersOverview['summary']['egg_sales_receivables'] ?? 0);

        $totalVariableCost = round($totalProdCost + $untransferredIncCost + $totalLayerOperatingCost, 2);
        $totalOperatingCost = $totalVariableCost;
        $grossFarmProfit = round($billedAll - $totalVariableCost, 2);
        $grossMarginPct = $billedAll > 0 ? round(($grossFarmProfit / $billedAll) * 100, 1) : 0.0;
        $totalFixedCostsAndAssets = round($fixedCapitalTotal + $annualRent + $totalGeneralOverhead, 2);
        // Post-Sales Net Profit = Gross Profit (Variable Chicken Costs only) minus Fixed Assets, Annual Rent & General Overhead
        $netFarmProfit = round($grossFarmProfit - $totalFixedCostsAndAssets, 2);
        $totalFlowAssets = round($liveFlockAssetValue + $incubatorEggAssetValue + $eggStockroomValue + $storeInventoryValue + $receivablesAll, 2);
        $totalNetWorth = round($fixedCapitalTotal + $totalFlowAssets + $cashCollectedAll, 2);
        $projectedMarketNetWorth = round($fixedCapitalTotal + $projectedLiveFlockMarketValue + $incubatorEggAssetValue + $eggStockroomMarketValue + $storeInventoryValue + $receivablesAll + $cashCollectedAll, 2);
        $grandTotalAllCosts = round(
            $totalIncubationSpend
            + $totalDirectDoc
            + $totalFeedConsumed
            + $totalMedsVaccines
            + $totalLaborSanitOther
            + $storeInventoryValue
            + $totalGeneralOverhead
            + $fixedCapitalTotal
            + $annualRent,
            2
        );

        return $this->setMemo('fin', [
            'fixed_assets' => $fixedAssets,
            'fixed_capital_total' => round($fixedCapitalTotal, 2),
            'annual_farm_rent' => round($annualRent, 2),
            'total_fixed_and_rent' => round($fixedCapitalTotal + $annualRent, 2),
            'total_fixed_costs_and_assets' => $totalFixedCostsAndAssets,
            'monthly_depreciation' => round($monthlyDepTotal, 2),
            'monthly_rent' => $monthlyRent,
            'monthly_general_overhead' => round($monthlyOverhead, 2),
            'monthly_fixed_burden' => round($monthlyDepTotal + $monthlyRent + $monthlyOverhead, 2),

            'live_flock_asset_value' => round($liveFlockAssetValue, 2),
            'projected_live_flock_market_value' => round($projectedLiveFlockMarketValue, 2),
            'incubator_egg_asset_value' => round($incubatorEggAssetValue, 2),
            'egg_stockroom_value' => round($eggStockroomValue, 2),
            'egg_stockroom_market_value' => round($eggStockroomMarketValue, 2),
            'egg_stockroom_pcs' => $eggStockroomPcs,
            'store_inventory_value' => $storeInventoryValue,
            'store_feed_value' => round($storeFeedValue, 2),
            'store_med_value' => round($storeMedValue, 2),
            'total_inventory_purchased_cost' => round($totalInventoryPurchasedCost, 2),
            'accounts_receivable' => round($receivablesAll, 2),
            'cash_collected' => round($cashCollectedAll, 2),
            'total_flow_assets' => $totalFlowAssets,
            'total_net_worth' => $totalNetWorth,
            'projected_market_net_worth' => $projectedMarketNetWorth,

            'total_sales_billed' => round($billedAll, 2),
            'total_variable_cost' => $totalVariableCost,
            'gross_farm_profit' => $grossFarmProfit,
            'gross_margin_pct' => $grossMarginPct,
            'spend_incubation_and_doc' => round($totalIncubationSpend + $totalDirectDoc, 2),
            'spend_direct_flock_purchases' => round($totalDirectDoc, 2),
            'spend_production_doc' => round($totalProdDirectDoc, 2),
            'spend_layer_purchases' => round($totalLayerPurchaseCost, 2),
            'spend_incubator_eggs' => round($totalIncubatorEggCost, 2),
            'spend_incubator_gas' => round($totalIncubatorGasCost, 2),
            'spend_incubator_repairs' => round($totalIncubatorRepairCost, 2),
            'spend_feed_consumed' => round($totalFeedConsumed, 2),
            'spend_meds_vaccines' => round($totalMedsVaccines, 2),
            'spend_direct_labor' => round($totalLaborCost, 2),
            'spend_sanitation' => round($totalSanitationCost, 2),
            'spend_water_other' => round($totalWaterOtherCost, 2),
            'spend_labor_sanit_other' => round($totalLaborSanitOther, 2),
            'spend_batch_rent_share' => round($totalBatchOverheadShare, 2),
            'total_general_expenses_logged' => round($totalGeneralOverhead, 2),
            'spend_overhead_rent' => round($totalBatchOverheadShare + $monthlyOverhead, 2),
            'total_operating_cost' => $totalOperatingCost,
            'grand_total_all_costs' => $grandTotalAllCosts,
            'net_farm_profit' => $netFarmProfit,

            'general_expenses' => $generalExpenses,
            'general_expenses_by_category' => $generalExpensesByCategory,
            'batches' => $batchRows,
        ]);
    }

    public function createLayerFlock(array $input): int
    {
        $count = count($this->db->getTable('layer_flocks')) + 1;
        $batchCode = trim((string) ($input['batch_code'] ?? ''));
        if ($batchCode === '') {
            $batchCode = sprintf('LAY-%s-%02d', date('Y'), $count);
        }

        $profiles = VeterinaryKnowledgeBase::getIncubationSpeciesProfiles();
        $species = strtolower(trim((string) ($input['species'] ?? 'chicken')));
        if (!isset($profiles[$species])) {
            $species = 'chicken';
        }

        $flockName = trim((string) ($input['flock_name'] ?? ''));
        if ($flockName === '') {
            $flockName = $profiles[$species]['label'] . ' Layers';
        }

        $birds = max(1, (int) ($input['initial_bird_count'] ?? 100));
        $ageWeeks = max(1, (int) ($input['age_weeks'] ?? 20));
        $purchaseCost = max(0.0, (float) ($input['purchase_cost'] ?? 0));
        $startDate = !empty($input['start_date']) ? (string) $input['start_date'] : date('Y-m-d');
        $notes = trim((string) ($input['notes'] ?? ''));

        $flockId = $this->db->insert('layer_flocks', [
            'batch_code' => $batchCode,
            'flock_name' => $flockName,
            'species' => $species,
            'initial_bird_count' => $birds,
            'current_bird_count' => $birds,
            'age_weeks' => $ageWeeks,
            'purchase_cost' => round($purchaseCost, 2),
            'start_date' => $startDate,
            'status' => 'Active',
            'notes' => $notes,
        ]);

        $this->addAiMemory(
            "Layer flock added: {$batchCode} ({$flockName}, {$profiles[$species]['label']}) — {$birds} birds at Week {$ageWeeks}.",
            'layers'
        );

        return $flockId;
    }

    public function retireLayerFlock(int $flockId): void
    {
        $flock = $this->db->findById('layer_flocks', $flockId);
        if (!$flock) {
            throw new InvalidArgumentException('Layer flock not found.');
        }
        $this->db->update('layer_flocks', $flockId, ['status' => 'Retired']);
    }

    public function recordLayerDailyLog(array $input): int
    {
        $flockId = (int) ($input['layer_flock_id'] ?? 0);
        $flock = $this->db->findById('layer_flocks', $flockId);
        if (!$flock) {
            throw new InvalidArgumentException('Selected layer flock does not exist.');
        }

        $logDate = !empty($input['log_date']) ? (string) $input['log_date'] : date('Y-m-d');
        $goodEggs = max(0, (int) ($input['good_eggs'] ?? 0));
        $crackedEggs = max(0, (int) ($input['cracked_eggs'] ?? 0));
        $mortality = max(0, (int) ($input['mortality_count'] ?? 0));
        $notes = trim((string) ($input['notes'] ?? ''));

        $feedItemId = !empty($input['feed_item_id']) ? (int) $input['feed_item_id'] : null;
        $rawFeedQty = max(0.0, (float) ($input['feed_qty'] ?? 0));
        $feedUnit = ($input['feed_unit'] ?? 'kg') === 'g' ? 'g' : 'kg';
        $feedGrams = round(($feedUnit === 'kg') ? ($rawFeedQty * 1000.0) : $rawFeedQty, 4);
        $feedCostCalculated = 0.0;
        if ($feedItemId && $feedGrams > 0) {
            $feedItem = $this->db->findById('inventory_items', $feedItemId);
            if ($feedItem) {
                $unitCost = (float) ($feedItem['unit_cost_per_g_or_ml'] ?? 0);
                $feedCostCalculated = round($feedGrams * $unitCost, 2);
                $newRem = max(0.0, (float) ($feedItem['total_quantity_g_or_ml'] ?? 0) - $feedGrams);
                $this->db->update('inventory_items', $feedItemId, [
                    'total_quantity_g_or_ml' => round($newRem, 4),
                ]);
            }
        }

        $medDose = 0.0;
        $medCostCalculated = 0.0;
        $medicationsUsed = [];
        $checkedMedIds = isset($input['med_items']) && is_array($input['med_items']) ? $input['med_items'] : [];
        $medDosesMap = isset($input['med_doses']) && is_array($input['med_doses']) ? $input['med_doses'] : [];
        foreach ($checkedMedIds as $rawId) {
            $id = (int) $rawId;
            $dose = round(max(0.0, (float) ($medDosesMap[$id] ?? $medDosesMap[(string) $id] ?? 0)), 4);
            if ($id > 0 && $dose > 0) {
                $medItem = $this->db->findById('inventory_items', $id);
                if ($medItem) {
                    $unitCost = (float) ($medItem['unit_cost_per_g_or_ml'] ?? 0);
                    $cost = round($dose * $unitCost, 2);
                    $newRem = max(0.0, (float) ($medItem['total_quantity_g_or_ml'] ?? 0) - $dose);
                    $this->db->update('inventory_items', $id, [
                        'total_quantity_g_or_ml' => round($newRem, 4),
                    ]);
                    $medicationsUsed[] = [
                        'item_id' => $id,
                        'item_name' => $medItem['item_name'],
                        'dose' => $dose,
                        'unit' => $medItem['unit_type'] ?? 'ml',
                        'cost' => $cost,
                    ];
                    $medDose = round($medDose + $dose, 4);
                    $medCostCalculated += $cost;
                }
            }
        }
        $medCostCalculated = round($medCostCalculated, 2);

        $logId = $this->db->insert('egg_collection_logs', [
            'layer_flock_id' => $flockId,
            'log_date' => $logDate,
            'good_eggs' => $goodEggs,
            'cracked_eggs' => $crackedEggs,
            'mortality_count' => $mortality,
            'feed_item_id' => $feedItemId,
            'feed_grams' => $feedGrams,
            'feed_cost_calculated' => $feedCostCalculated,
            'med_dose_g_ml' => round($medDose, 4),
            'med_cost_calculated' => $medCostCalculated,
            'medications_used' => $medicationsUsed,
            'notes' => $notes,
        ]);

        if ($mortality > 0) {
            $newCount = max(0, (int) ($flock['current_bird_count'] ?? 0) - $mortality);
            $this->db->update('layer_flocks', $flockId, [
                'current_bird_count' => $newCount,
            ]);
        }

        if ($goodEggs > 0 || $mortality > 0) {
            $this->addAiMemory(
                "Layer log for {$flock['batch_code']} ({$flock['flock_name']}) on {$logDate}: {$goodEggs} good eggs collected, {$crackedEggs} cracked, {$mortality} mortality.",
                'layers'
            );
        }

        return $logId;
    }

    public function getLayersOverview(): array
    {
        if (($cached = $this->getMemo('layers')) !== null) {
            return $cached;
        }
        $flocks = $this->db->getTable('layer_flocks');
        $logs = $this->db->getTable('egg_collection_logs');
        $prodBatches = $this->db->getTable('production_batches');
        $prodLogs = $this->db->getTable('production_daily_logs');
        $transfers = $this->db->getTable('egg_transfers');
        $sales = $this->db->getTable('egg_sales');
        $invItems = $this->db->getTable('inventory_items');
        $profiles = VeterinaryKnowledgeBase::getIncubationSpeciesProfiles();

        $invById = [];
        foreach ($invItems as $it) {
            $invById[(int) $it['id']] = $it;
        }

        $resolveRowSourceKey = static function (array $r): string {
            if (!empty($r['source_key'])) {
                return (string) $r['source_key'];
            }
            if (!empty($r['production_batch_id'])) {
                return 'production:' . (int) $r['production_batch_id'];
            }
            if (!empty($r['layer_flock_id'])) {
                return 'layer:' . (int) $r['layer_flock_id'];
            }
            return '';
        };

        $detectSpecies = static function (string $text): string {
            $t = strtolower($text);
            if (str_contains($t, 'turkey')) {
                return 'turkey';
            }
            if (str_contains($t, 'duck')) {
                return 'duck';
            }
            if (str_contains($t, 'guinea')) {
                return 'guinea_fowl';
            }
            if (str_contains($t, 'quail')) {
                return 'quail';
            }
            return 'chicken';
        };

        $logsByFlock = [];
        foreach ($logs as $l) {
            $fid = (int) ($l['layer_flock_id'] ?? 0);
            $logsByFlock[$fid][] = $l;
        }

        $transfersBySource = [];
        foreach ($transfers as $t) {
            $sKey = $resolveRowSourceKey($t);
            if ($sKey !== '') {
                $transfersBySource[$sKey] = ($transfersBySource[$sKey] ?? 0) + (int) ($t['eggs_transferred'] ?? 0);
            }
        }

        $salesBySource = [];
        $unassignedSoldEggs = 0;
        $totalSoldEggs = 0;
        $eggSalesBilled = 0.0;
        $eggSalesCollected = 0.0;
        $eggSalesReceivables = 0.0;

        foreach ($sales as $s) {
            $sKey = $resolveRowSourceKey($s);
            $tEggs = (int) ($s['total_eggs'] ?? 0);
            $totalSoldEggs += $tEggs;
            if ($sKey !== '') {
                $salesBySource[$sKey] = ($salesBySource[$sKey] ?? 0) + $tEggs;
            } else {
                $unassignedSoldEggs += $tEggs;
            }
            $eggSalesBilled += (float) ($s['total_amount'] ?? 0);
            $eggSalesCollected += (float) ($s['amount_paid'] ?? 0);
            $eggSalesReceivables += (float) ($s['balance_due'] ?? 0);
        }

        $flockMap = [];
        $sourceMap = [];
        $enrichedFlocks = [];
        $eggSources = [];
        $liveLayers = 0;
        $activeFlockCount = 0;
        $totalGoodEggsAll = 0;
        $totalCrackedEggsAll = 0;
        $totalLayerCostAll = 0.0;
        $totalEggInputCostAll = 0.0;
        $totalTransferredAll = 0;
        $weightedLayRateSum = 0.0;

        foreach ($flocks as $f) {
            $fid = (int) $f['id'];
            $sKey = 'layer:' . $fid;
            $fLogs = $logsByFlock[$fid] ?? [];
            usort($fLogs, fn($a, $b) => strcmp((string) ($b['log_date'] ?? ''), (string) ($a['log_date'] ?? '')) ?: ((int) $b['id'] <=> (int) $a['id']));

            $goodEggs = 0;
            $crackedEggs = 0;
            $mortality = 0;
            $feedCost = 0.0;
            $medCost = 0.0;
            foreach ($fLogs as $fl) {
                $goodEggs += (int) ($fl['good_eggs'] ?? 0);
                $crackedEggs += (int) ($fl['cracked_eggs'] ?? 0);
                $mortality += (int) ($fl['mortality_count'] ?? 0);
                $feedCost += (float) ($fl['feed_cost_calculated'] ?? 0);
                $medCost += (float) ($fl['med_cost_calculated'] ?? 0);
            }

            $curBirds = (int) ($f['current_bird_count'] ?? 0);
            $latestGood = !empty($fLogs) ? (int) ($fLogs[0]['good_eggs'] ?? 0) : 0;
            $latestLayRate = $curBirds > 0 && !empty($fLogs) ? round(($latestGood / $curBirds) * 100, 1) : 0.0;

            $startDate = (string) ($f['start_date'] ?? date('Y-m-d'));
            $daysElapsed = max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400));
            $currentAgeWeeks = (int) ($f['age_weeks'] ?? 20) + (int) floor($daysElapsed / 7);

            $transferred = (int) ($transfersBySource[$sKey] ?? 0);
            $sold = (int) ($salesBySource[$sKey] ?? 0);
            $available = max(0, $goodEggs - $transferred - $sold);

            $purchaseCost = (float) ($f['purchase_cost'] ?? 0);
            $totalCost = round($purchaseCost + $feedCost + $medCost, 2);
            $costPerEgg = $goodEggs > 0 ? round(($feedCost + $medCost) / $goodEggs, 2) : 0.0;
            $costPerCrate = round($costPerEgg * 30, 2);
            $recEgg = round($costPerEgg * 1.30, 2);
            $recCrate = round($costPerEgg * 30 * 1.30, 2);

            $speciesKey = (string) ($f['species'] ?? 'chicken');
            $speciesLabel = $profiles[$speciesKey]['label'] ?? ucfirst($speciesKey);

            $row = array_merge($f, [
                'source_key' => $sKey,
                'source_type' => 'layer',
                'source_id' => $fid,
                'species_label' => $speciesLabel,
                'current_age_weeks' => $currentAgeWeeks,
                'total_good_eggs' => $goodEggs,
                'total_good_crates' => intdiv($goodEggs, 30),
                'total_good_loose' => $goodEggs % 30,
                'total_cracked_eggs' => $crackedEggs,
                'total_mortality' => $mortality,
                'total_feed_cost' => round($feedCost, 2),
                'total_med_cost' => round($medCost, 2),
                'total_cost' => $totalCost,
                'cost_per_egg' => $costPerEgg,
                'cost_per_crate' => $costPerCrate,
                'recommended_price_per_egg' => $recEgg,
                'recommended_price_per_crate' => $recCrate,
                'transferred_eggs' => $transferred,
                'sold_eggs' => $sold,
                'available_eggs' => $available,
                'available_crates' => intdiv($available, 30),
                'available_loose' => $available % 30,
                'latest_laying_rate_pct' => $latestLayRate,
                'log_count' => count($fLogs),
            ]);

            $enrichedFlocks[] = $row;
            $eggSources[] = $row;
            $flockMap[$fid] = $row;
            $sourceMap[$sKey] = $row;

            if (($f['status'] ?? 'Active') === 'Active') {
                $liveLayers += $curBirds;
                $activeFlockCount++;
                $weightedLayRateSum += ($latestLayRate * $curBirds);
            }
            $totalGoodEggsAll += $goodEggs;
            $totalCrackedEggsAll += $crackedEggs;
            $totalLayerCostAll += $totalCost;
            $totalEggInputCostAll += ($feedCost + $medCost);
            $totalTransferredAll += $transferred;
        }

        // Aggregate egg production from Production flocks (read raw tables to avoid recursive enrichment)
        $prodStats = [];
        foreach ($prodLogs as $pl) {
            $pbId = (int) ($pl['production_batch_id'] ?? 0);
            if ($pbId <= 0) {
                continue;
            }
            if (!isset($prodStats[$pbId])) {
                $prodStats[$pbId] = ['good' => 0, 'cracked' => 0, 'feed_cost' => 0.0, 'med_cost' => 0.0];
            }
            $prodStats[$pbId]['good'] += (int) ($pl['good_eggs'] ?? 0);
            $prodStats[$pbId]['cracked'] += (int) ($pl['cracked_eggs'] ?? 0);
            $prodStats[$pbId]['feed_cost'] += (float) ($pl['feed_cost_calculated'] ?? 0);
            $prodStats[$pbId]['med_cost'] += (float) ($pl['med_cost_calculated'] ?? 0);
        }

        foreach ($prodBatches as $pb) {
            $pbId = (int) ($pb['id'] ?? 0);
            $sKey = 'production:' . $pbId;
            $st = $prodStats[$pbId] ?? ['good' => 0, 'cracked' => 0, 'feed_cost' => 0.0, 'med_cost' => 0.0];
            $goodEggs = $st['good'];
            $crackedEggs = $st['cracked'];
            $transferred = (int) ($transfersBySource[$sKey] ?? 0);
            $sold = (int) ($salesBySource[$sKey] ?? 0);
            if ($goodEggs <= 0 && $transferred <= 0 && $sold <= 0) {
                continue;
            }

            $feedCost = (float) $st['feed_cost'];
            $medCost = (float) $st['med_cost'];
            $costPerEgg = $goodEggs > 0 ? round(($feedCost + $medCost) / $goodEggs, 2) : 0.0;
            $costPerCrate = round($costPerEgg * 30, 2);
            $recEgg = round($costPerEgg * 1.30, 2);
            $recCrate = round($costPerEgg * 30 * 1.30, 2);

            $startDate = (string) ($pb['start_date'] ?? date('Y-m-d'));
            $daysSinceStart = max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400));
            $ageDays = max(1, (int) ($pb['initial_age_days'] ?? 1) + $daysSinceStart);
            $ageWeeks = max(1, (int) ceil($ageDays / 7));

            $speciesKey = $detectSpecies((string) ($pb['flock_type'] ?? ''));
            $speciesLabel = $profiles[$speciesKey]['label'] ?? ucfirst($speciesKey);
            $available = max(0, $goodEggs - $transferred - $sold);

            $pSource = [
                'id' => $pbId,
                'source_key' => $sKey,
                'source_type' => 'production',
                'source_id' => $pbId,
                'batch_code' => $pb['batch_code'],
                'flock_name' => $pb['flock_type'],
                'species' => $speciesKey,
                'species_label' => $speciesLabel,
                'current_age_weeks' => $ageWeeks,
                'current_bird_count' => (int) ($pb['current_bird_count'] ?? 0),
                'status' => $pb['status'] ?? 'Active',
                'total_good_eggs' => $goodEggs,
                'total_good_crates' => intdiv($goodEggs, 30),
                'total_good_loose' => $goodEggs % 30,
                'total_cracked_eggs' => $crackedEggs,
                'total_feed_cost' => round($feedCost, 2),
                'total_med_cost' => round($medCost, 2),
                'cost_per_egg' => $costPerEgg,
                'cost_per_crate' => $costPerCrate,
                'recommended_price_per_egg' => $recEgg,
                'recommended_price_per_crate' => $recCrate,
                'transferred_eggs' => $transferred,
                'sold_eggs' => $sold,
                'available_eggs' => $available,
                'available_crates' => intdiv($available, 30),
                'available_loose' => $available % 30,
            ];

            $eggSources[] = $pSource;
            $sourceMap[$sKey] = $pSource;

            $totalGoodEggsAll += $goodEggs;
            $totalCrackedEggsAll += $crackedEggs;
            $totalEggInputCostAll += ($feedCost + $medCost);
            $totalTransferredAll += $transferred;
        }

        usort($enrichedFlocks, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);

        $globalAvailable = max(0, $totalGoodEggsAll - $totalTransferredAll - $totalSoldEggs);
        $avgCostPerEgg = $totalGoodEggsAll > 0 ? round($totalEggInputCostAll / $totalGoodEggsAll, 2) : 0.0;
        $avgCostPerCrate = round($avgCostPerEgg * 30, 2);
        $avgRecEgg = round($avgCostPerEgg * 1.30, 2);
        $avgRecCrate = round($avgCostPerEgg * 30 * 1.30, 2);

        $enrichedLogs = [];
        foreach ($logs as $l) {
            $fid = (int) ($l['layer_flock_id'] ?? 0);
            $fl = $flockMap[$fid] ?? null;
            $fItem = !empty($l['feed_item_id']) ? ($invById[(int) $l['feed_item_id']] ?? null) : null;
            $good = (int) ($l['good_eggs'] ?? 0);
            $enrichedLogs[] = array_merge($l, [
                'flock_code' => $fl['batch_code'] ?? 'LAY',
                'flock_name' => $fl['flock_name'] ?? 'Layer Flock',
                'good_crates' => intdiv($good, 30),
                'good_loose' => $good % 30,
                'feed_item_name' => $fItem['item_name'] ?? null,
            ]);
        }
        usort($enrichedLogs, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);

        $enrichedTransfers = [];
        foreach ($transfers as $t) {
            $sKey = $resolveRowSourceKey($t);
            $src = $sourceMap[$sKey] ?? null;
            $tEggs = (int) ($t['eggs_transferred'] ?? 0);
            $enrichedTransfers[] = array_merge($t, [
                'source_key' => $sKey,
                'source_type' => $src['source_type'] ?? ($t['source_type'] ?? 'layer'),
                'flock_code' => $src['batch_code'] ?? 'LAY',
                'flock_name' => $src['flock_name'] ?? 'Layer Flock',
                'crates' => intdiv($tEggs, 30),
                'loose' => $tEggs % 30,
            ]);
        }
        usort($enrichedTransfers, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);

        $enrichedSales = [];
        foreach ($sales as $s) {
            $sKey = $resolveRowSourceKey($s);
            $src = $sKey !== '' ? ($sourceMap[$sKey] ?? null) : null;
            $tEggs = (int) ($s['total_eggs'] ?? 0);
            $costBasis = isset($s['cost_basis']) ? (float) $s['cost_basis'] : round($tEggs * ($src['cost_per_egg'] ?? $avgCostPerEgg), 2);
            $estProfit = isset($s['estimated_profit']) ? (float) $s['estimated_profit'] : round((float) ($s['total_amount'] ?? 0) - $costBasis, 2);
            $enrichedSales[] = array_merge($s, [
                'source_key' => $sKey,
                'source_type' => $src['source_type'] ?? ($s['source_type'] ?? ($sKey === '' ? 'combined' : 'layer')),
                'flock_code' => $src ? $src['batch_code'] : 'COMBINED',
                'flock_name' => $src ? $src['flock_name'] : 'Combined Egg Stock',
                'crates' => intdiv($tEggs, 30),
                'loose' => $tEggs % 30,
                'cost_basis' => $costBasis,
                'estimated_profit' => $estProfit,
            ]);
        }
        usort($enrichedSales, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);

        $avgLayRate = $liveLayers > 0 ? round($weightedLayRateSum / $liveLayers, 1) : 0.0;

        return $this->setMemo('layers', [
            'flocks' => $enrichedFlocks,
            'egg_sources' => $eggSources,
            'collection_logs' => $enrichedLogs,
            'transfers' => $enrichedTransfers,
            'sales' => $enrichedSales,
            'summary' => [
                'live_layers' => $liveLayers,
                'active_flock_count' => $activeFlockCount,
                'avg_laying_rate_pct' => $avgLayRate,
                'total_good_eggs' => $totalGoodEggsAll,
                'total_good_crates' => intdiv($totalGoodEggsAll, 30),
                'total_good_loose' => $totalGoodEggsAll % 30,
                'total_cracked_eggs' => $totalCrackedEggsAll,
                'total_layer_cost' => round($totalLayerCostAll, 2),
                'total_transferred_eggs' => $totalTransferredAll,
                'transfer_batch_count' => count($enrichedTransfers),
                'total_sold_eggs' => $totalSoldEggs,
                'unassigned_sold_eggs' => $unassignedSoldEggs,
                'available_eggs' => $globalAvailable,
                'available_crates' => intdiv($globalAvailable, 30),
                'available_loose' => $globalAvailable % 30,
                'avg_cost_per_egg' => $avgCostPerEgg,
                'avg_cost_per_crate' => $avgCostPerCrate,
                'avg_rec_price_per_egg' => $avgRecEgg,
                'avg_rec_price_per_crate' => $avgRecCrate,
                'egg_sales_billed' => round($eggSalesBilled, 2),
                'egg_sales_collected' => round($eggSalesCollected, 2),
                'egg_sales_receivables' => round($eggSalesReceivables, 2),
            ],
        ]);
    }

    public function transferEggsToIncubator(array $input): int
    {
        $sourceKey = trim((string) ($input['source_key'] ?? ''));
        if ($sourceKey === '' && !empty($input['layer_flock_id'])) {
            $sourceKey = 'layer:' . (int) $input['layer_flock_id'];
        }
        $overview = $this->getLayersOverview();
        $source = null;
        foreach ($overview['egg_sources'] as $src) {
            if (($src['source_key'] ?? '') === $sourceKey) {
                $source = $src;
                break;
            }
        }
        if (!$source) {
            throw new InvalidArgumentException('Selected egg source flock does not exist.');
        }

        $eggs = (int) ($input['eggs_transferred'] ?? 0);
        if ($eggs <= 0) {
            throw new InvalidArgumentException('Number of eggs to transfer must be greater than zero.');
        }
        $maxAvailable = min((int) $source['available_eggs'], (int) $overview['summary']['available_eggs']);
        if ($eggs > $maxAvailable) {
            throw new InvalidArgumentException("Cannot transfer {$eggs} eggs — only {$maxAvailable} egg(s) available in stock for {$source['batch_code']}.");
        }

        $costPerEgg = (float) ($source['cost_per_egg'] ?? 0);
        $rawVal = $input['egg_valuation'] ?? '';
        $valuation = (is_string($rawVal) && trim($rawVal) === '') || $rawVal === null
            ? round($eggs * $costPerEgg, 2)
            : max(0.0, (float) $rawVal);

        $transferDate = !empty($input['transfer_date']) ? (string) $input['transfer_date'] : date('Y-m-d');
        $setterId = trim((string) ($input['setter_id'] ?? 'Setter A')) ?: 'Setter A';
        $notes = trim((string) ($input['notes'] ?? ''));
        $isProd = ($source['source_type'] ?? 'layer') === 'production';

        $incBatchId = $this->createIncubationBatch([
            'species' => $source['species'],
            'setter_id' => $setterId,
            'parent_flock_info' => ($isProd ? 'Production Flock: ' : 'Layer Flock: ') . "{$source['batch_code']} ({$source['flock_name']})",
            'flock_age_weeks' => max(18, (int) $source['current_age_weeks']),
            'eggs_set' => $eggs,
            'egg_cost' => $valuation,
            'start_date' => $transferDate,
            'ownership_type' => 'Farm',
        ]);

        $incBatch = $this->db->findById('incubation_batches', $incBatchId);
        $incCode = $incBatch['batch_code'] ?? ('INC-' . date('Y') . '-' . $incBatchId);

        $this->db->insert('egg_transfers', [
            'source_key' => $source['source_key'],
            'source_type' => $source['source_type'],
            'layer_flock_id' => $isProd ? null : (int) $source['source_id'],
            'production_batch_id' => $isProd ? (int) $source['source_id'] : null,
            'incubation_batch_id' => $incBatchId,
            'incubation_batch_code' => $incCode,
            'eggs_transferred' => $eggs,
            'egg_valuation' => round($valuation, 2),
            'cost_per_egg_used' => $costPerEgg,
            'transfer_date' => $transferDate,
            'notes' => $notes,
        ]);

        $this->addAiMemory(
            "Transferred {$eggs} eggs from {$source['batch_code']} ({$source['flock_name']}) to incubator batch {$incCode} ({$setterId}) at ₦" . number_format($valuation, 2) . ".",
            'layers'
        );

        return $incBatchId;
    }

    public function recordEggSale(array $input): int
    {
        $overview = $this->getLayersOverview();
        $sourceKey = trim((string) ($input['source_key'] ?? ''));
        if ($sourceKey === '' && !empty($input['layer_flock_id'])) {
            $sourceKey = 'layer:' . (int) $input['layer_flock_id'];
        }
        $unitMode = ($input['unit_mode'] ?? 'crates') === 'pieces' ? 'pieces' : 'crates';
        $qty = max(0.0, (float) ($input['quantity'] ?? 0));
        $totalEggs = $unitMode === 'crates' ? (int) round($qty * 30) : (int) round($qty);

        if ($totalEggs <= 0) {
            throw new InvalidArgumentException('Quantity sold must equal at least 1 egg.');
        }

        $source = null;
        if ($sourceKey !== '') {
            foreach ($overview['egg_sources'] as $src) {
                if (($src['source_key'] ?? '') === $sourceKey) {
                    $source = $src;
                    break;
                }
            }
            if (!$source) {
                throw new InvalidArgumentException('Selected egg source flock does not exist.');
            }
            $maxAvail = min((int) $source['available_eggs'], (int) $overview['summary']['available_eggs']);
            if ($totalEggs > $maxAvail) {
                throw new InvalidArgumentException("Cannot sell {$totalEggs} eggs — only {$maxAvail} egg(s) available for {$source['batch_code']}.");
            }
        } else {
            $maxGlobal = (int) $overview['summary']['available_eggs'];
            if ($totalEggs > $maxGlobal) {
                throw new InvalidArgumentException("Cannot sell {$totalEggs} eggs — only {$maxGlobal} egg(s) available in stock.");
            }
        }

        $unitPrice = max(0.0, (float) ($input['unit_price'] ?? 0));
        $totalAmount = round($qty * $unitPrice, 2);

        $costPerEggAtSale = (float) ($source ? $source['cost_per_egg'] : $overview['summary']['avg_cost_per_egg']);
        $costBasis = round($totalEggs * $costPerEggAtSale, 2);
        $estimatedProfit = round($totalAmount - $costBasis, 2);

        $rawStatus = (string) ($input['payment_status'] ?? 'Paid');
        if (!in_array($rawStatus, ['Paid', 'Partial', 'Credit'], true)) {
            $rawStatus = 'Paid';
        }
        if ($rawStatus === 'Paid') {
            $amountPaid = $totalAmount;
        } elseif ($rawStatus === 'Credit') {
            $amountPaid = 0.0;
        } else {
            $amountPaid = round(min($totalAmount, max(0.0, (float) ($input['amount_paid'] ?? 0))), 2);
        }
        $balanceDue = round(max(0.0, $totalAmount - $amountPaid), 2);
        $normalizedStatus = $balanceDue <= 0 ? 'Paid' : ($amountPaid > 0 ? 'Partial' : 'Credit');

        $isLayer = $source && ($source['source_type'] ?? '') === 'layer';
        $isProd = $source && ($source['source_type'] ?? '') === 'production';

        $saleId = $this->db->insert('egg_sales', [
            'source_key' => $source ? $source['source_key'] : null,
            'source_type' => $source ? $source['source_type'] : 'combined',
            'layer_flock_id' => $isLayer ? (int) $source['source_id'] : null,
            'production_batch_id' => $isProd ? (int) $source['source_id'] : null,
            'sale_date' => !empty($input['sale_date']) ? (string) $input['sale_date'] : date('Y-m-d'),
            'buyer_name' => trim((string) ($input['buyer_name'] ?? 'Walk-in Buyer')) ?: 'Walk-in Buyer',
            'buyer_phone' => trim((string) ($input['buyer_phone'] ?? '')),
            'unit_mode' => $unitMode,
            'quantity' => round($qty, 2),
            'total_eggs' => $totalEggs,
            'unit_price' => round($unitPrice, 2),
            'total_amount' => $totalAmount,
            'cost_per_egg_at_sale' => $costPerEggAtSale,
            'cost_basis' => $costBasis,
            'estimated_profit' => $estimatedProfit,
            'amount_paid' => $amountPaid,
            'balance_due' => $balanceDue,
            'payment_status' => $normalizedStatus,
            'notes' => trim((string) ($input['notes'] ?? '')),
        ]);

        $srcLabel = $source ? $source['batch_code'] : 'Combined Egg Stock';
        $this->addAiMemory(
            "Egg sale recorded from {$srcLabel}: {$totalEggs} eggs for ₦" . number_format($totalAmount, 2) . " ({$normalizedStatus}).",
            'sales'
        );

        return $saleId;
    }

    public function recordEggSalePayment(int $saleId, float $paymentAmount): void
    {
        $sale = $this->db->findById('egg_sales', $saleId);
        if (!$sale) {
            throw new InvalidArgumentException('Egg sale record not found.');
        }
        $paymentAmount = round(max(0.0, $paymentAmount), 2);
        if ($paymentAmount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }
        $totalAmount = (float) ($sale['total_amount'] ?? 0);
        $currentPaid = (float) ($sale['amount_paid'] ?? 0);
        $newPaid = round(min($totalAmount, $currentPaid + $paymentAmount), 2);
        $newBalance = round(max(0.0, $totalAmount - $newPaid), 2);
        $newStatus = $newBalance <= 0 ? 'Paid' : ($newPaid > 0 ? 'Partial' : 'Credit');

        $this->db->update('egg_sales', $saleId, [
            'amount_paid' => $newPaid,
            'balance_due' => $newBalance,
            'payment_status' => $newStatus,
        ]);
    }

    private function adjustInventoryBalance(int $itemId, float $delta): ?array
    {
        if ($itemId <= 0) {
            return null;
        }
        $item = $this->db->findById('inventory_items', $itemId);
        if (!$item) {
            return null;
        }
        $newRem = round(max(0.0, (float) ($item['total_quantity_g_or_ml'] ?? 0) + $delta), 4);
        $this->db->update('inventory_items', $itemId, [
            'total_quantity_g_or_ml' => $newRem,
        ]);
        $item['total_quantity_g_or_ml'] = $newRem;
        return $item;
    }

    public static function fmtQty(float $val): string
    {
        $s = rtrim(rtrim(number_format($val, 4), '0'), '.');
        return ($s === '' || $s === '-0') ? '0' : $s;
    }

    public function updateProductionBatch(int $batchId, array $input): void
    {
        $batch = $this->db->findById('production_batches', $batchId);
        if (!$batch) {
            throw new InvalidArgumentException('Production batch not found.');
        }

        $logs = $this->db->where('production_daily_logs', 'production_batch_id', $batchId);
        $sales = $this->db->where('batch_sales', 'production_batch_id', $batchId);
        $totalMortality = array_sum(array_map(fn($l) => (int) ($l['mortality_count'] ?? 0), $logs));
        $totalSold = array_sum(array_map(fn($s) => (int) ($s['birds_sold'] ?? 0), $sales));

        $batchCode = trim((string) ($input['batch_code'] ?? '')) ?: (string) $batch['batch_code'];
        $flockType = trim((string) ($input['flock_type'] ?? '')) ?: (string) $batch['flock_type'];
        $initialBirds = max(1, (int) ($input['initial_bird_count'] ?? $batch['initial_bird_count']));
        $initialAgeDays = max(1, (int) ($input['initial_age_days'] ?? $batch['initial_age_days'] ?? 1));
        $startDate = !empty($input['start_date']) ? (string) $input['start_date'] : (string) $batch['start_date'];
        $docCost = max(0.0, (float) ($input['transferred_incubation_cost'] ?? $batch['transferred_incubation_cost'] ?? 0));
        $status = in_array($input['status'] ?? '', ['Active', 'Completed', 'Depleted'], true)
            ? (string) $input['status']
            : (string) ($batch['status'] ?? 'Active');

        $currentBirds = max(0, $initialBirds - $totalMortality - $totalSold);

        $sellingPrice = isset($input['selling_price_per_bird']) && $input['selling_price_per_bird'] !== ''
            ? round(max(0.0, (float) $input['selling_price_per_bird']), 2)
            : (float) ($batch['target_selling_price'] ?? 0);

        $targetMargin = (float) ($batch['target_profit_margin_pct'] ?? 30.0);

        $this->db->update('production_batches', $batchId, [
            'batch_code' => $batchCode,
            'flock_type' => $flockType,
            'initial_bird_count' => $initialBirds,
            'current_bird_count' => $currentBirds,
            'initial_age_days' => $initialAgeDays,
            'start_date' => $startDate,
            'transferred_incubation_cost' => round($docCost, 2),
            'target_selling_price' => $sellingPrice,
            'target_profit_margin_pct' => $targetMargin,
            'status' => $status,
        ]);
    }

    public function updateProductionDailyLog(int $logId, array $input): void
    {
        $oldLog = $this->db->findById('production_daily_logs', $logId);
        if (!$oldLog) {
            throw new InvalidArgumentException('Production daily log not found.');
        }
        $batchId = (int) ($oldLog['production_batch_id'] ?? 0);
        $batch = $this->db->findById('production_batches', $batchId);
        if (!$batch) {
            throw new InvalidArgumentException('Parent production batch not found.');
        }

        // Step A: Refund previous feed consumption + wasted feed back to inventory
        $oldFeedId = (int) ($oldLog['feed_item_id'] ?? 0);
        $oldFeedTotalGrams = (float) ($oldLog['feed_grams'] ?? 0) + (float) ($oldLog['wasted_feed_grams'] ?? 0);
        if ($oldFeedId > 0 && $oldFeedTotalGrams > 0) {
            $this->adjustInventoryBalance($oldFeedId, $oldFeedTotalGrams);
        }

        // Step B: Refund previous medications back to inventory
        if (!empty($oldLog['medications_used']) && is_array($oldLog['medications_used'])) {
            foreach ($oldLog['medications_used'] as $mu) {
                $mId = (int) ($mu['item_id'] ?? $mu['med_item_id'] ?? 0);
                $mDose = (float) ($mu['dose'] ?? 0);
                if ($mId > 0 && $mDose > 0) {
                    $this->adjustInventoryBalance($mId, $mDose);
                }
            }
        } elseif (!empty($oldLog['med_item_id']) && (float) ($oldLog['med_dose_g_ml'] ?? 0) > 0) {
            $this->adjustInventoryBalance((int) $oldLog['med_item_id'], (float) $oldLog['med_dose_g_ml']);
        }

        // Step C: Deduct updated feed consumption + wasted feed
        $newFeedId = !empty($input['feed_item_id']) ? (int) $input['feed_item_id'] : null;
        $newFeedGrams = round(max(0.0, (float) ($input['feed_grams'] ?? 0)), 4);
        $newWastedFeedGrams = round(max(0.0, (float) ($input['wasted_feed_grams'] ?? 0)), 4);
        $newFeedCost = 0.0;
        $newWastedFeedCost = 0.0;
        if ($newFeedId && ($newFeedGrams > 0 || $newWastedFeedGrams > 0)) {
            $feedItem = $this->db->findById('inventory_items', $newFeedId);
            if ($feedItem) {
                $unitCost = (float) ($feedItem['unit_cost_per_g_or_ml'] ?? 0);
                $newFeedCost = round($newFeedGrams * $unitCost, 2);
                $newWastedFeedCost = round($newWastedFeedGrams * $unitCost, 2);
                $this->adjustInventoryBalance($newFeedId, -round($newFeedGrams + $newWastedFeedGrams, 4));
            }
        }

        // Step D: Deduct updated medications checklist
        $medicationsUsed = [];
        $newMedItemId = null;
        $newMedDose = 0.0;
        $newMedCost = 0.0;
        $checkedMedIds = isset($input['med_items']) && is_array($input['med_items']) ? $input['med_items'] : [];
        $medDosesMap = isset($input['med_doses']) && is_array($input['med_doses']) ? $input['med_doses'] : [];
        foreach ($checkedMedIds as $rawId) {
            $id = (int) $rawId;
            $dose = round(max(0.0, (float) ($medDosesMap[$id] ?? $medDosesMap[(string) $id] ?? 0)), 4);
            if ($id > 0 && $dose > 0) {
                $medItem = $this->db->findById('inventory_items', $id);
                if ($medItem) {
                    $cost = round($dose * (float) ($medItem['unit_cost_per_g_or_ml'] ?? 0), 2);
                    $this->adjustInventoryBalance($id, -$dose);
                    $medicationsUsed[] = [
                        'item_id' => $id,
                        'item_name' => $medItem['item_name'],
                        'dose' => $dose,
                        'unit' => $medItem['unit_type'] ?? 'ml',
                        'cost' => $cost,
                    ];
                    $newMedDose = round($newMedDose + $dose, 4);
                    $newMedCost += $cost;
                    if ($newMedItemId === null) {
                        $newMedItemId = $id;
                    }
                }
            }
        }
        $newMedCost = round($newMedCost, 2);

        // Step E: Adjust parent batch live count by mortality delta
        $newMortality = max(0, (int) ($input['mortality_count'] ?? 0));
        $mortDelta = (int) ($oldLog['mortality_count'] ?? 0) - $newMortality;
        if ($mortDelta !== 0) {
            $this->db->update('production_batches', $batchId, [
                'current_bird_count' => max(0, (int) ($batch['current_bird_count'] ?? 0) + $mortDelta),
            ]);
        }

        // Step F: Save updated log
        $this->db->update('production_daily_logs', $logId, [
            'log_date' => !empty($input['log_date']) ? (string) $input['log_date'] : (string) $oldLog['log_date'],
            'mortality_count' => $newMortality,
            'good_eggs' => max(0, (int) ($input['good_eggs'] ?? 0)),
            'cracked_eggs' => max(0, (int) ($input['cracked_eggs'] ?? 0)),
            'water_liters' => round(max(0.0, (float) ($input['water_liters'] ?? 0)), 4),
            'feed_item_id' => $newFeedId,
            'feed_grams' => $newFeedGrams,
            'feed_cost_calculated' => $newFeedCost,
            'wasted_feed_grams' => $newWastedFeedGrams,
            'wasted_feed_cost' => $newWastedFeedCost,
            'med_item_id' => $newMedItemId,
            'med_dose_g_ml' => $newMedDose,
            'med_cost_calculated' => $newMedCost,
            'medications_used' => $medicationsUsed,
            'labor_cost' => round(max(0.0, (float) ($input['labor_cost'] ?? 0)), 2),
            'sanitation_cost' => round(max(0.0, (float) ($input['sanitation_cost'] ?? 0)), 2),
            'water_other_cost' => round(max(0.0, (float) ($input['water_other_cost'] ?? 0)), 2),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ]);
    }

    public function updateLayerDailyLog(int $logId, array $input): void
    {
        $oldLog = $this->db->findById('egg_collection_logs', $logId);
        if (!$oldLog) {
            throw new InvalidArgumentException('Layer egg collection log not found.');
        }
        $flockId = (int) ($oldLog['layer_flock_id'] ?? 0);
        $flock = $this->db->findById('layer_flocks', $flockId);
        if (!$flock) {
            throw new InvalidArgumentException('Parent layer flock not found.');
        }

        // Refund previous feed to inventory before deducting updated feed
        $oldFeedId = (int) ($oldLog['feed_item_id'] ?? 0);
        $oldFeedGrams = (float) ($oldLog['feed_grams'] ?? 0);
        if ($oldFeedId > 0 && $oldFeedGrams > 0) {
            $this->adjustInventoryBalance($oldFeedId, $oldFeedGrams);
        }

        $newFeedId = !empty($input['feed_item_id']) ? (int) $input['feed_item_id'] : null;
        $rawFeedQty = max(0.0, (float) ($input['feed_qty'] ?? 0));
        $feedUnit = ($input['feed_unit'] ?? 'kg') === 'g' ? 'g' : 'kg';
        $newFeedGrams = round(($feedUnit === 'kg') ? ($rawFeedQty * 1000.0) : $rawFeedQty, 4);
        $newFeedCost = 0.0;
        if ($newFeedId && $newFeedGrams > 0) {
            $feedItem = $this->db->findById('inventory_items', $newFeedId);
            if ($feedItem) {
                $newFeedCost = round($newFeedGrams * (float) ($feedItem['unit_cost_per_g_or_ml'] ?? 0), 2);
                $this->adjustInventoryBalance($newFeedId, -$newFeedGrams);
            }
        }

        $newMortality = max(0, (int) ($input['mortality_count'] ?? 0));
        $mortDelta = (int) ($oldLog['mortality_count'] ?? 0) - $newMortality;
        if ($mortDelta !== 0) {
            $this->db->update('layer_flocks', $flockId, [
                'current_bird_count' => max(0, (int) ($flock['current_bird_count'] ?? 0) + $mortDelta),
            ]);
        }

        $updates = [
            'log_date' => !empty($input['log_date']) ? (string) $input['log_date'] : (string) $oldLog['log_date'],
            'good_eggs' => max(0, (int) ($input['good_eggs'] ?? 0)),
            'cracked_eggs' => max(0, (int) ($input['cracked_eggs'] ?? 0)),
            'mortality_count' => $newMortality,
            'feed_item_id' => $newFeedId,
            'feed_grams' => $newFeedGrams,
            'feed_cost_calculated' => $newFeedCost,
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];

        if (!empty($input['med_editor_present']) || array_key_exists('med_items', $input)) {
            if (!empty($oldLog['medications_used']) && is_array($oldLog['medications_used'])) {
                foreach ($oldLog['medications_used'] as $mu) {
                    $mId = (int) ($mu['item_id'] ?? $mu['med_item_id'] ?? 0);
                    $mDose = (float) ($mu['dose'] ?? 0);
                    if ($mId > 0 && $mDose > 0) {
                        $this->adjustInventoryBalance($mId, $mDose);
                    }
                }
            }

            $medicationsUsed = [];
            $newMedDose = 0.0;
            $newMedCost = 0.0;
            $checkedMedIds = isset($input['med_items']) && is_array($input['med_items']) ? $input['med_items'] : [];
            $medDosesMap = isset($input['med_doses']) && is_array($input['med_doses']) ? $input['med_doses'] : [];
            foreach ($checkedMedIds as $rawId) {
                $id = (int) $rawId;
                $dose = round(max(0.0, (float) ($medDosesMap[$id] ?? $medDosesMap[(string) $id] ?? 0)), 4);
                if ($id > 0 && $dose > 0) {
                    $medItem = $this->db->findById('inventory_items', $id);
                    if ($medItem) {
                        $cost = round($dose * (float) ($medItem['unit_cost_per_g_or_ml'] ?? 0), 2);
                        $this->adjustInventoryBalance($id, -$dose);
                        $medicationsUsed[] = [
                            'item_id' => $id,
                            'item_name' => $medItem['item_name'],
                            'dose' => $dose,
                            'unit' => $medItem['unit_type'] ?? 'ml',
                            'cost' => $cost,
                        ];
                        $newMedDose = round($newMedDose + $dose, 4);
                        $newMedCost += $cost;
                    }
                }
            }
            $newMedCost = round($newMedCost, 2);

            $updates['med_dose_g_ml'] = $newMedDose;
            $updates['med_cost_calculated'] = $newMedCost;
            $updates['medications_used'] = $medicationsUsed;
        }

        $this->db->update('egg_collection_logs', $logId, $updates);
    }

    public function updateIncubationBatch(int $batchId, array $input): void
    {
        $batch = $this->db->findById('incubation_batches', $batchId);
        if (!$batch) {
            throw new InvalidArgumentException('Incubation batch not found.');
        }

        $profiles = VeterinaryKnowledgeBase::getIncubationSpeciesProfiles();
        $species = strtolower(trim((string) ($input['species'] ?? $batch['species'] ?? 'chicken')));
        if (!isset($profiles[$species])) {
            $species = (string) ($batch['species'] ?? 'chicken');
        }

        $allowedStatuses = ['Active', 'Completed', 'Moved to Production', 'Collected'];
        $status = in_array($input['status'] ?? '', $allowedStatuses, true)
            ? (string) $input['status']
            : (string) ($batch['status'] ?? 'Active');

        $updates = [
            'batch_code' => trim((string) ($input['batch_code'] ?? '')) ?: (string) $batch['batch_code'],
            'species' => $species,
            'setter_id' => trim((string) ($input['setter_id'] ?? '')) ?: (string) ($batch['setter_id'] ?? 'Setter A'),
            'parent_flock_info' => trim((string) ($input['parent_flock_info'] ?? '')) ?: (string) ($batch['parent_flock_info'] ?? ''),
            'flock_age_weeks' => max(1, (int) ($input['flock_age_weeks'] ?? $batch['flock_age_weeks'] ?? 32)),
            'eggs_set' => max(1, (int) ($input['eggs_set'] ?? $batch['eggs_set'] ?? 1)),
            'start_date' => !empty($input['start_date']) ? (string) $input['start_date'] : (string) $batch['start_date'],
            'status' => $status,
        ];

        if (($batch['ownership_type'] ?? 'Farm') === 'Client') {
            $serviceFee = round(max(0.0, (float) ($input['service_fee'] ?? $batch['service_fee'] ?? 0)), 2);
            $amountPaid = (float) ($batch['amount_paid'] ?? 0);
            $balanceDue = round(max(0.0, $serviceFee - $amountPaid), 2);
            $updates['client_name'] = trim((string) ($input['client_name'] ?? $batch['client_name'] ?? ''));
            $updates['client_phone'] = trim((string) ($input['client_phone'] ?? $batch['client_phone'] ?? ''));
            $updates['service_fee'] = $serviceFee;
            $updates['balance_due'] = $balanceDue;
            $updates['payment_status'] = $balanceDue <= 0 ? 'Paid' : ($amountPaid > 0 ? 'Partial' : 'Pending');
            $updates['egg_cost'] = 0.0;
        } else {
            $updates['egg_cost'] = round(max(0.0, (float) ($input['egg_cost'] ?? $batch['egg_cost'] ?? 0)), 2);
        }

        $this->db->update('incubation_batches', $batchId, $updates);
    }

    public function updateInventoryItem(int $itemId, array $input): void
    {
        $item = $this->db->findById('inventory_items', $itemId);
        if (!$item) {
            throw new InvalidArgumentException('Inventory item not found.');
        }

        $category = in_array($input['category'] ?? '', ['starter', 'grower', 'layer', 'medicine'], true)
            ? (string) $input['category']
            : (string) ($item['category'] ?? 'starter');
        $unitType = $category !== 'medicine'
            ? 'g'
            : ((($input['unit_type'] ?? $item['unit_type'] ?? 'ml') === 'g') ? 'g' : 'ml');

        $initialQty = round(max(0.0001, (float) ($input['initial_quantity_g_or_ml'] ?? $item['initial_quantity_g_or_ml'])), 4);
        $remQty = round(max(0.0, (float) ($input['total_quantity_g_or_ml'] ?? $item['total_quantity_g_or_ml'])), 4);
        $totalCost = round(max(0.0, (float) ($input['total_cost'] ?? $item['total_cost'])), 2);
        $unitCost = round($totalCost / $initialQty, 6);

        $this->db->update('inventory_items', $itemId, [
            'item_name' => trim((string) ($input['item_name'] ?? '')) ?: (string) $item['item_name'],
            'category' => $category,
            'unit_type' => $unitType,
            'initial_quantity_g_or_ml' => $initialQty,
            'total_quantity_g_or_ml' => $remQty,
            'total_cost' => $totalCost,
            'unit_cost_per_g_or_ml' => $unitCost,
            'purchase_date' => !empty($input['purchase_date']) ? (string) $input['purchase_date'] : (string) ($item['purchase_date'] ?? date('Y-m-d')),
        ]);
    }

    /**
     * Store-level Damaged / Spoiled Stock Waste Logs (subtracted from inventory & logged in General Overhead).
     */
    public function getInventoryWasteLogs(): array
    {
        $rows = $this->db->getTable('inventory_waste_logs');
        $invMap = [];
        foreach ($this->db->getTable('inventory_items') as $it) {
            $invMap[(int) ($it['id'] ?? 0)] = $it;
        }
        foreach ($rows as $idx => $r) {
            $it = $invMap[(int) ($r['inventory_item_id'] ?? 0)] ?? null;
            $rows[$idx]['item_name'] = $it['item_name'] ?? ($r['item_name'] ?? 'Deleted Item');
            $rows[$idx]['unit_label'] = (($it['unit_type'] ?? $r['unit_label'] ?? 'g') === 'ml') ? 'ml' : 'g';
        }
        usort($rows, fn($a, $b) => strcmp($b['waste_date'] ?? '', $a['waste_date'] ?? '') ?: ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0)));
        return $rows;
    }

    public function addInventoryWaste(array $input): int
    {
        $itemId = (int) ($input['inventory_item_id'] ?? 0);
        $item = $this->db->findById('inventory_items', $itemId);
        if (!$item) {
            throw new InvalidArgumentException('Selected inventory item does not exist.');
        }

        $unitMode = (string) ($input['input_unit_mode'] ?? 'g');
        $rawQty = max(0.0001, (float) ($input['quantity_value'] ?? 0));
        $qtyBase = ($unitMode === 'kg' || $unitMode === 'l') ? round($rawQty * 1000.0, 4) : round($rawQty, 4);
        $unitLabel = (($item['unit_type'] ?? 'g') === 'ml') ? 'ml' : 'g';
        $unitCost = (float) ($item['unit_cost_per_g_or_ml'] ?? 0);
        $wasteCost = round($qtyBase * $unitCost, 2);
        $wasteDate = !empty($input['waste_date']) ? (string) $input['waste_date'] : date('Y-m-d');
        $reason = trim((string) ($input['reason'] ?? '')) ?: 'Damaged / Spoiled Stock';

        $this->adjustInventoryBalance($itemId, -$qtyBase);

        $genExpenseId = null;
        if ($wasteCost > 0) {
            $genExpenseId = $this->db->insert('general_farm_expenses', [
                'expense_date' => $wasteDate,
                'category' => 'Feed / Stock Waste',
                'description' => $item['item_name'] . ' (' . self::fmtQty($qtyBase) . $unitLabel . ') — ' . $reason,
                'amount' => $wasteCost,
            ]);
        }

        return $this->db->insert('inventory_waste_logs', [
            'inventory_item_id' => $itemId,
            'item_name' => $item['item_name'],
            'unit_label' => $unitLabel,
            'waste_date' => $wasteDate,
            'quantity_g_or_ml' => $qtyBase,
            'waste_cost' => $wasteCost,
            'reason' => $reason,
            'general_expense_id' => $genExpenseId,
        ]);
    }

    public function updateInventoryWaste(int $wasteId, array $input): void
    {
        $old = $this->db->findById('inventory_waste_logs', $wasteId);
        if (!$old) {
            throw new InvalidArgumentException('Stock waste record not found.');
        }

        // Refund old wasted quantity back to inventory
        $oldItemId = (int) ($old['inventory_item_id'] ?? 0);
        $oldQty = (float) ($old['quantity_g_or_ml'] ?? 0);
        if ($oldItemId > 0 && $oldQty > 0) {
            $this->adjustInventoryBalance($oldItemId, $oldQty);
        }

        $newItemId = !empty($input['inventory_item_id']) ? (int) $input['inventory_item_id'] : $oldItemId;
        $item = $this->db->findById('inventory_items', $newItemId);
        if (!$item) {
            throw new InvalidArgumentException('Selected inventory item does not exist.');
        }

        $unitMode = (string) ($input['input_unit_mode'] ?? 'g');
        $rawQty = max(0.0001, (float) ($input['quantity_value'] ?? 0));
        $newQtyBase = ($unitMode === 'kg' || $unitMode === 'l') ? round($rawQty * 1000.0, 4) : round($rawQty, 4);
        $unitLabel = (($item['unit_type'] ?? 'g') === 'ml') ? 'ml' : 'g';
        $unitCost = (float) ($item['unit_cost_per_g_or_ml'] ?? 0);
        $newWasteCost = round($newQtyBase * $unitCost, 2);
        $wasteDate = !empty($input['waste_date']) ? (string) $input['waste_date'] : (string) ($old['waste_date'] ?? date('Y-m-d'));
        $reason = trim((string) ($input['reason'] ?? '')) ?: 'Damaged / Spoiled Stock';

        // Deduct updated quantity from inventory
        $this->adjustInventoryBalance($newItemId, -$newQtyBase);

        // Sync linked general overhead expense
        $genExpenseId = !empty($old['general_expense_id']) ? (int) $old['general_expense_id'] : null;
        if ($genExpenseId && $this->db->findById('general_farm_expenses', $genExpenseId)) {
            $this->db->update('general_farm_expenses', $genExpenseId, [
                'expense_date' => $wasteDate,
                'description' => $item['item_name'] . ' (' . self::fmtQty($newQtyBase) . $unitLabel . ') — ' . $reason,
                'amount' => $newWasteCost,
            ]);
        } elseif ($newWasteCost > 0) {
            $genExpenseId = $this->db->insert('general_farm_expenses', [
                'expense_date' => $wasteDate,
                'category' => 'Feed / Stock Waste',
                'description' => $item['item_name'] . ' (' . self::fmtQty($newQtyBase) . $unitLabel . ') — ' . $reason,
                'amount' => $newWasteCost,
            ]);
        }

        $this->db->update('inventory_waste_logs', $wasteId, [
            'inventory_item_id' => $newItemId,
            'item_name' => $item['item_name'],
            'unit_label' => $unitLabel,
            'waste_date' => $wasteDate,
            'quantity_g_or_ml' => $newQtyBase,
            'waste_cost' => $newWasteCost,
            'reason' => $reason,
            'general_expense_id' => $genExpenseId,
        ]);
    }

    public function deleteInventoryWaste(int $wasteId): void
    {
        $old = $this->db->findById('inventory_waste_logs', $wasteId);
        if (!$old) {
            return;
        }
        $oldItemId = (int) ($old['inventory_item_id'] ?? 0);
        $oldQty = (float) ($old['quantity_g_or_ml'] ?? 0);
        if ($oldItemId > 0 && $oldQty > 0) {
            $this->adjustInventoryBalance($oldItemId, $oldQty);
        }
        $genExpenseId = !empty($old['general_expense_id']) ? (int) $old['general_expense_id'] : 0;
        if ($genExpenseId > 0) {
            $this->db->delete('general_farm_expenses', $genExpenseId);
        }
        $this->db->delete('inventory_waste_logs', $wasteId);
    }

    public function exportFullBackup(): array
    {
        $tables = [
            'incubation_batches',
            'incubation_daily_logs',
            'incubation_expenses',
            'hatch_breakouts',
            'inventory_items',
            'production_batches',
            'production_daily_logs',
            'batch_sales',
            'layer_flocks',
            'egg_collection_logs',
            'egg_transfers',
            'egg_sales',
            'general_farm_expenses',
            'fixed_assets',
            'farm_ai_knowledge_memory',
            'ai_chat_history',
        ];
        $payload = [
            'exported_at' => date('Y-m-d H:i:s'),
            'app' => 'AuraPoultry OS v3.5',
            'settings' => [
                'annual_farm_rent' => (float) $this->db->getSetting('annual_farm_rent', 0),
                'gemini_model' => (string) $this->db->getSetting('gemini_model', 'gemini-3.8-flash'),
            ],
            'tables' => [],
        ];
        foreach ($tables as $t) {
            $payload['tables'][$t] = $this->db->getTable($t);
        }
        return $payload;
    }

    public function ensureManagerAccount(): void
    {
        $hash = (string) $this->db->getSetting('manager_password_hash', '');
        if ($hash === '') {
            $this->db->setSetting('manager_username', 'manager');
            $this->db->setSetting('manager_name', 'Farm Manager');
            $this->db->setSetting('manager_password_hash', password_hash('password123', PASSWORD_DEFAULT));
        }
    }

    public function getManagerProfile(): array
    {
        $this->ensureManagerAccount();
        return [
            'username' => (string) $this->db->getSetting('manager_username', 'manager'),
            'name' => (string) $this->db->getSetting('manager_name', 'Farm Manager'),
        ];
    }

    public function verifyManagerLogin(string $username, string $password): bool
    {
        $this->ensureManagerAccount();
        $expectedUser = strtolower(trim((string) $this->db->getSetting('manager_username', 'manager')));
        $hash = (string) $this->db->getSetting('manager_password_hash', '');
        if (strtolower(trim($username)) !== $expectedUser) {
            return false;
        }
        return password_verify($password, $hash);
    }

    public function updateManagerPassword(string $currentPassword, string $newPassword): void
    {
        $this->ensureManagerAccount();
        $hash = (string) $this->db->getSetting('manager_password_hash', '');
        if (!password_verify($currentPassword, $hash)) {
            throw new InvalidArgumentException('Current password is incorrect.');
        }
        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException('New password must be at least 6 characters.');
        }
        $this->db->setSetting('manager_password_hash', password_hash($newPassword, PASSWORD_DEFAULT));
    }
}

