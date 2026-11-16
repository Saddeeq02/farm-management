<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class FarmService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
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
        return $enriched;
    }

    /**
     * Enrich all inventory items with gram/ml conversion metrics, kg equivalents, and stock percentage.
     */
    public function getInventoryItemsEnriched(): array
    {
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
            if ($mId > 0) {
                $consumedByItem[$mId] = ($consumedByItem[$mId] ?? 0.0) + (float) $log['med_dose_g_ml'];
            }
        }

        $enriched = [];
        foreach ($items as $item) {
            $id = (int) $item['id'];
            $initial = max(1.0, (float) ($item['initial_quantity_g_or_ml'] ?? $item['total_quantity_g_or_ml']));
            $remaining = (float) $item['total_quantity_g_or_ml'];
            $unitCost = (float) $item['unit_cost_per_g_or_ml'];
            $stockPct = min(100.0, max(0.0, round(($remaining / $initial) * 100, 1)));

            $isFeed = in_array($item['category'], ['starter', 'grower', 'layer'], true);
            $unitLabel = $isFeed ? 'g' : ($item['unit_type'] ?? 'ml');
            $remainingKgOrL = round($remaining / 1000, 2);
            $initialKgOrL = round($initial / 1000, 2);
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

        return $enriched;
    }

    /**
     * Enrich all production batches with daily telemetry totals, age-based feed transition alerts,
     * live cost per bird, target margin pricing, and sales ledger summaries.
     */
    public function getProductionBatchesEnriched(): array
    {
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
            $totalWaterLiters = 0.0;
            $totalFeedGrams = 0.0;
            $totalFeedCost = 0.0;
            $totalMedDose = 0.0;
            $totalMedCost = 0.0;
            $totalLaborCost = 0.0;
            $totalSanitationCost = 0.0;
            $totalWaterOtherCost = 0.0;
            $lastFeedCategory = null;

            foreach ($batchLogs as $idx => $log) {
                $totalMortality += (int) $log['mortality_count'];
                $totalWaterLiters += (float) $log['water_liters'];
                $totalFeedGrams += (float) $log['feed_grams'];
                $totalFeedCost += (float) $log['feed_cost_calculated'];
                $totalMedDose += (float) $log['med_dose_g_ml'];
                $totalMedCost += (float) $log['med_cost_calculated'];
                $totalLaborCost += (float) $log['labor_cost'];
                $totalSanitationCost += (float) $log['sanitation_cost'];
                $waterOtherCost = (float) ($log['water_other_cost'] ?? 0);
                $totalWaterOtherCost += $waterOtherCost;

                $feedItem = isset($log['feed_item_id']) ? ($invMap[(int) $log['feed_item_id']] ?? null) : null;
                $medItem = isset($log['med_item_id']) ? ($invMap[(int) $log['med_item_id']] ?? null) : null;
                $batchLogs[$idx]['feed_item_name'] = $feedItem['item_name'] ?? 'None';
                $batchLogs[$idx]['feed_category'] = $feedItem['category'] ?? null;
                $batchLogs[$idx]['med_item_name'] = $medItem['item_name'] ?? 'None';
                $batchLogs[$idx]['med_unit'] = $medItem['unit_type'] ?? 'ml';
                $batchLogs[$idx]['water_other_cost'] = $waterOtherCost;
                $batchLogs[$idx]['daily_total_cost'] = (float) $log['feed_cost_calculated']
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
            $batchDailyRent = round($rentPerLiveBirdPerDay * $currentInHouseBirds, 2);
            $accumulatedGeneralRent = round($batchDailyRent * $daysInHouse, 2);

            $incubationCost = (float) $batch['transferred_incubation_cost'];
            $manualHouseRent = (float) $batch['house_rent_total'];
            $houseRent = $manualHouseRent > 0 ? $manualHouseRent : $accumulatedGeneralRent;
            $variableCost = $totalFeedCost + $totalMedCost + $totalLaborCost + $totalSanitationCost + $totalWaterOtherCost;
            $totalAccumulatedCost = $incubationCost + $houseRent + $variableCost;

            // Current Cost per Bird = (Incubation + House Rent + Sum(Feed + Med + Labor + Sanitation)) / Surviving Bird Count
            // Note: surviving biological birds absorb all mortalities; we also compute cost per unsold in-house bird
            $costPerSurvivingBird = round($totalAccumulatedCost / $survivingBiologicalPool, 2);
            $costPerInHouseBird = $currentInHouseBirds > 0
                ? round(max(0.0, $totalAccumulatedCost - ($totalBirdsSold * $costPerSurvivingBird)) / $currentInHouseBirds, 2)
                : $costPerSurvivingBird;

            // Cost per bird without mortality (to show exact mortality absorption impact)
            $costWithoutMortality = round($totalAccumulatedCost / $initialBirds, 2);
            $mortalityCostAbsorbedPerBird = round(max(0.0, $costPerSurvivingBird - $costWithoutMortality), 2);

            $marginPct = (float) $batch['target_profit_margin_pct'];
            $recommendedSellingPrice = round($costPerSurvivingBird * (1 + ($marginPct / 100)), 2);

            // Projected total revenue across all surviving biological birds (sold at actual + remaining at recommended)
            $projectedRemainingRevenue = round($currentInHouseBirds * $recommendedSellingPrice, 2);
            $projectedTotalRevenue = round($realizedRevenue + $projectedRemainingRevenue, 2);
            $projectedNetProfit = round($projectedTotalRevenue - $totalAccumulatedCost, 2);
            $realizedNetBalance = round($realizedRevenue - $totalAccumulatedCost, 2);

            // Age calculation & Feed Transition Alert (respects initial_age_days for direct grower purchases)
            $initialAgeDays = max(1, (int) ($batch['initial_age_days'] ?? 1));
            $daysSinceStart = max(0, (int) floor((time() - $startTs) / 86400));
            $ageDays = max(1, $initialAgeDays + $daysSinceStart);
            $ageWeeks = (int) ceil($ageDays / 7);

            $feedAlert = $this->computeFeedTransitionAlert($ageDays, $ageWeeks, $lastFeedCategory);

            $incBatchId = isset($batch['incubation_batch_id']) ? (int) $batch['incubation_batch_id'] : 0;
            $originIncBatch = $incBatchId > 0 ? ($incMap[$incBatchId] ?? null) : null;

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
                'surviving_biological_birds' => $survivingBiologicalPool,
                'current_in_house_birds' => $currentInHouseBirds,
                'total_birds_sold' => $totalBirdsSold,
                'total_water_liters' => $totalWaterLiters,
                'total_feed_grams' => $totalFeedGrams,
                'total_feed_kg' => round($totalFeedGrams / 1000, 2),
                'total_feed_cost' => $totalFeedCost,
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
                'total_accumulated_cost' => $totalAccumulatedCost,
                'cost_per_bird' => $costPerSurvivingBird,
                'cost_per_in_house_bird' => $costPerInHouseBird,
                'mortality_absorbed_per_bird' => $mortalityCostAbsorbedPerBird,
                'target_margin_pct' => $marginPct,
                'target_selling_price' => (float) ($batch['target_selling_price'] ?? $recommendedSellingPrice),
                'recommended_selling_price' => $recommendedSellingPrice,
                'profit_per_bird' => round($recommendedSellingPrice - $costPerSurvivingBird, 2),
                'realized_revenue' => $realizedRevenue,
                'paid_revenue' => $paidRevenue,
                'total_receivables' => round($batchReceivables ?? 0, 2),
                'projected_remaining_revenue' => $projectedRemainingRevenue,
                'projected_total_revenue' => $projectedTotalRevenue,
                'projected_net_profit' => $projectedNetProfit,
                'realized_net_balance' => $realizedNetBalance,
            ];

            $enriched[] = $batch;
        }

        usort($enriched, fn($a, $b) => (int) $b['id'] <=> (int) $a['id']);
        return $enriched;
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

        return [
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
        ];
    }

    // =========================================================================
    // MUTATION ACTIONS (Modules 1 - 4)
    // =========================================================================

    public function createIncubationBatch(array $input): int
    {
        $batchCode = trim((string) ($input['batch_code'] ?? ''));
        if ($batchCode === '') {
            $count = count($this->db->getTable('incubation_batches')) + 1;
            $batchCode = sprintf('INC-%s-%02d', date('Y'), $count);
        }

        $profiles = VeterinaryKnowledgeBase::getIncubationSpeciesProfiles();
        $species = strtolower(trim((string) ($input['species'] ?? 'chicken')));
        if (!isset($profiles[$species])) {
            $species = 'chicken';
        }
        $startDate = !empty($input['start_date']) ? (string) $input['start_date'] : date('Y-m-d');
        $parentInfo = trim((string) ($input['parent_flock_info'] ?? ''));
        if ($parentInfo === '') {
            $parentInfo = $profiles[$species]['label'] . ' Intake';
        }

        $batchId = $this->db->insert('incubation_batches', [
            'batch_code' => $batchCode,
            'species' => $species,
            'setter_id' => trim((string) ($input['setter_id'] ?? 'Setter A')),
            'parent_flock_info' => $parentInfo,
            'flock_age_weeks' => max(18, (int) ($input['flock_age_weeks'] ?? 32)),
            'eggs_set' => max(1, (int) ($input['eggs_set'] ?? 1000)),
            'egg_cost' => max(0.0, (float) ($input['egg_cost'] ?? 0)),
            'start_date' => $startDate,
            'status' => 'Active',
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

        $prodCount = count($this->db->getTable('production_batches')) + 1;
        $prodCode = !empty($options['batch_code'])
            ? trim((string) $options['batch_code'])
            : sprintf('PRD-%s-%02d', date('Y'), $prodCount);

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
        $rawQty = max(0.01, (float) ($input['quantity_value'] ?? 1));
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

        // Exact formula: Cost per Gram / ml = Total Purchase Price / Total Weight in Grams (or Volume in ml)
        $unitCostPerGramOrMl = round($totalCost / $totalGramsOrMl, 6);

        $itemId = $this->db->insert('inventory_items', [
            'item_name' => $name,
            'category' => $category,
            'unit_type' => $unitType,
            'initial_quantity_g_or_ml' => round($totalGramsOrMl, 2),
            'total_quantity_g_or_ml' => round($totalGramsOrMl, 2),
            'total_cost' => round($totalCost, 2),
            'unit_cost_per_g_or_ml' => $unitCostPerGramOrMl,
            'purchase_date' => $input['purchase_date'] ?: date('Y-m-d'),
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
        $count = count($this->db->getTable('production_batches')) + 1;
        $batchCode = trim((string) ($input['batch_code'] ?? ''));
        if ($batchCode === '') {
            $batchCode = sprintf('PRD-%s-%02d', date('Y'), $count);
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

        if ($sellingPrice > 0 && $costPerChick > 0) {
            $targetMargin = round(max(0.0, (($sellingPrice - $costPerChick) / $costPerChick) * 100), 2);
        } else {
            $targetMargin = isset($input['target_profit_margin_pct']) ? max(0.0, (float) $input['target_profit_margin_pct']) : 30.00;
        }
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

        return [
            'annual_rent' => $annualRent,
            'daily_rent' => $dailyRent,
            'total_general_expenses' => round($totalGeneralExpenses, 2),
            'daily_general_expenses' => $dailyGeneralExpenses,
            'combined_daily_overhead' => round($combinedDaily, 2),
            'total_live_birds' => $totalLiveBirds,
            'rent_per_bird_per_day' => $rentPerBirdPerDay,
        ];
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
        $waterLiters = max(0.0, (float) ($input['water_liters'] ?? 0));

        // Feed consumption (in grams)
        $feedItemId = !empty($input['feed_item_id']) ? (int) $input['feed_item_id'] : null;
        $feedGrams = max(0.0, (float) ($input['feed_grams'] ?? 0));
        $feedCostCalculated = 0.0;

        if ($feedItemId && $feedGrams > 0) {
            $feedItem = $this->db->findById('inventory_items', $feedItemId);
            if ($feedItem) {
                $unitCost = (float) $feedItem['unit_cost_per_g_or_ml'];
                $feedCostCalculated = round($feedGrams * $unitCost, 2);
                $newRemaining = max(0.0, (float) $feedItem['total_quantity_g_or_ml'] - $feedGrams);
                $this->db->update('inventory_items', $feedItemId, [
                    'total_quantity_g_or_ml' => round($newRemaining, 2),
                ]);
            }
        }

        // Medication dosage (in g or ml)
        $medItemId = !empty($input['med_item_id']) ? (int) $input['med_item_id'] : null;
        $medDose = max(0.0, (float) ($input['med_dose_g_ml'] ?? 0));
        $medCostCalculated = 0.0;

        if ($medItemId && $medDose > 0) {
            $medItem = $this->db->findById('inventory_items', $medItemId);
            if ($medItem) {
                $unitCost = (float) $medItem['unit_cost_per_g_or_ml'];
                $medCostCalculated = round($medDose * $unitCost, 2);
                $newRemaining = max(0.0, (float) $medItem['total_quantity_g_or_ml'] - $medDose);
                $this->db->update('inventory_items', $medItemId, [
                    'total_quantity_g_or_ml' => round($newRemaining, 2),
                ]);
            }
        }

        $laborCost = max(0.0, (float) ($input['labor_cost'] ?? 0));
        $sanitationCost = max(0.0, (float) ($input['sanitation_cost'] ?? 0));
        $waterOtherCost = max(0.0, (float) ($input['water_other_cost'] ?? 0));

        $logId = $this->db->insert('production_daily_logs', [
            'production_batch_id' => $batchId,
            'log_date' => $input['log_date'] ?: date('Y-m-d'),
            'mortality_count' => $mortality,
            'water_liters' => $waterLiters,
            'feed_item_id' => $feedItemId,
            'feed_grams' => $feedGrams,
            'feed_cost_calculated' => $feedCostCalculated,
            'med_item_id' => $medItemId,
            'med_dose_g_ml' => $medDose,
            'med_cost_calculated' => $medCostCalculated,
            'labor_cost' => $laborCost,
            'sanitation_cost' => $sanitationCost,
            'water_other_cost' => $waterOtherCost,
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

        $newCount = max(0, (int) $batch['current_bird_count'] - $birdsSold);
        $this->db->update('production_batches', $batchId, [
            'current_bird_count' => $newCount,
            'status' => $newCount === 0 ? 'Depleted' : 'Active',
        ]);

        $this->addAiMemory(
            "Market price realized on {$batch['batch_code']} ({$batch['flock_type']}): {$qtySold} birds sold at ₦" . number_format($unitPrice, 2) . "/bird.",
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
                if (($d === $today || $d === $yesterday) && !empty($pl['med_item_id']) && (float) ($pl['med_dose_g_ml'] ?? 0) > 0) {
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

        return $reminders;
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

    public function getWholeFarmFinancials(): array
    {
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
        $annualRent = (float) ($rent['annual_farm_rent'] ?? 0);
        $monthlyRent = round($annualRent / 12.0, 2);
        $monthlyOverhead = (float) ($rent['monthly_general_expenses'] ?? 0);

        // Flow / Current Assets
        $liveFlockAssetValue = 0.0;
        $totalDirectDoc = 0.0;
        $totalFeedConsumed = 0.0;
        $totalMedsVaccines = 0.0;
        $totalLaborSanitOther = 0.0;
        $totalBatchOverheadShare = 0.0;
        $totalProdCost = 0.0;
        $totalProdRev = 0.0;
        $totalProdCollected = 0.0;
        $totalProdReceivables = 0.0;

        $batchRows = [];
        foreach ($prodBatches as $pb) {
            $m = $pb['metrics'];
            $remBirds = (int) ($m['remaining_live_birds'] ?? 0);
            $liveValue = round($remBirds * (float) ($m['cost_per_bird'] ?? 0), 2);
            if (($pb['status'] ?? '') === 'Active') {
                $liveFlockAssetValue += $liveValue;
            }
            $carriedBase = (float) ($m['incubation_cost'] ?? 0);
            $feedCost = (float) ($m['total_feed_cost'] ?? 0);
            $medCost = (float) ($m['total_med_cost'] ?? 0);
            $otherOps = (float) ($m['total_labor_cost'] ?? 0) + (float) ($m['total_sanitation_cost'] ?? 0) + (float) ($m['total_water_other_cost'] ?? 0);
            $rentShare = (float) ($m['house_rent'] ?? 0);
            $accumCost = (float) ($m['total_accumulated_cost'] ?? 0);
            $rev = (float) ($m['total_revenue'] ?? 0);
            $rec = (float) ($m['total_receivables'] ?? 0);
            $collected = round(max(0.0, $rev - $rec), 2);

            if (empty($pb['incubation_batch_id'])) {
                $totalDirectDoc += $carriedBase;
            }
            $totalFeedConsumed += $feedCost;
            $totalMedsVaccines += $medCost;
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
                'units_label' => number_format((int) ($m['initial_birds'] ?? 0)) . ' init / ' . number_format($remBirds) . ' live / ' . number_format((int) ($m['birds_sold'] ?? 0)) . ' sold',
                'base_cost' => $carriedBase,
                'feed_med_cost' => $feedCost + $medCost,
                'overhead_other' => $rentShare + $otherOps,
                'total_cost' => $accumCost,
                'revenue_billed' => $rev,
                'cash_collected' => $collected,
                'receivables' => $rec,
                'net_profit' => round($rev - $accumCost, 2),
            ];
        }

        $incubatorEggAssetValue = 0.0;
        $totalIncubationSpend = 0.0;
        foreach ($incBatches as $ib) {
            $incCost = (float) ($ib['total_batch_cost'] ?? 0);
            $totalIncubationSpend += $incCost;
            if (($ib['status'] ?? '') === 'Active') {
                $incubatorEggAssetValue += $incCost;
            }
            $batchRows[] = [
                'stage' => 'Incubator',
                'batch_code' => $ib['batch_code'],
                'label' => ($ib['species_label'] ?? 'Hatchery') . ' • ' . ($ib['parent_flock_info'] ?? ''),
                'status' => $ib['status'],
                'units_label' => number_format((int) ($ib['total_eggs_set'] ?? 0)) . ' eggs • Day ' . (int) ($ib['incubation_day'] ?? 0),
                'base_cost' => (float) ($ib['egg_cost'] ?? 0),
                'feed_med_cost' => 0.0,
                'overhead_other' => (float) ($ib['gas_and_repairs_total'] ?? 0),
                'total_cost' => $incCost,
                'revenue_billed' => 0.0,
                'cash_collected' => 0.0,
                'receivables' => 0.0,
                'net_profit' => round(-$incCost, 2),
            ];
        }

        $storeInventoryValue = 0.0;
        foreach ($invItems as $item) {
            $storeInventoryValue += (float) ($item['total_quantity_g_or_ml'] ?? 0) * (float) ($item['unit_cost_per_g_or_ml'] ?? 0);
        }
        $storeInventoryValue = round($storeInventoryValue, 2);

        $cashCollectedAll = 0.0;
        $receivablesAll = 0.0;
        $billedAll = 0.0;
        foreach ($sales as $s) {
            $tot = (float) ($s['total_amount'] ?? 0);
            $paid = isset($s['amount_paid']) ? (float) $s['amount_paid'] : (($s['payment_status'] ?? '') === 'Paid' ? $tot : 0.0);
            $due = isset($s['balance_due']) ? (float) $s['balance_due'] : max(0.0, $tot - $paid);
            $billedAll += $tot;
            $cashCollectedAll += $paid;
            $receivablesAll += $due;
        }

        // Unlinked active incubator spend not yet pushed to production
        $untransferredIncCost = 0.0;
        foreach ($incBatches as $ib) {
            if (($ib['status'] ?? '') === 'Active') {
                $untransferredIncCost += (float) ($ib['total_batch_cost'] ?? 0);
            }
        }

        $totalOperatingCost = round($totalProdCost + $untransferredIncCost, 2);
        $totalFlowAssets = round($liveFlockAssetValue + $incubatorEggAssetValue + $storeInventoryValue + $receivablesAll, 2);
        $netFarmProfit = round($billedAll - $totalOperatingCost, 2);

        return [
            'fixed_assets' => $fixedAssets,
            'fixed_capital_total' => round($fixedCapitalTotal, 2),
            'annual_farm_rent' => round($annualRent, 2),
            'total_fixed_and_rent' => round($fixedCapitalTotal + $annualRent, 2),
            'monthly_depreciation' => round($monthlyDepTotal, 2),
            'monthly_rent' => $monthlyRent,
            'monthly_general_overhead' => round($monthlyOverhead, 2),
            'monthly_fixed_burden' => round($monthlyDepTotal + $monthlyRent + $monthlyOverhead, 2),

            'live_flock_asset_value' => round($liveFlockAssetValue, 2),
            'incubator_egg_asset_value' => round($incubatorEggAssetValue, 2),
            'store_inventory_value' => $storeInventoryValue,
            'accounts_receivable' => round($receivablesAll, 2),
            'cash_collected' => round($cashCollectedAll, 2),
            'total_flow_assets' => $totalFlowAssets,

            'total_sales_billed' => round($billedAll, 2),
            'spend_incubation_and_doc' => round($totalIncubationSpend + $totalDirectDoc, 2),
            'spend_feed_consumed' => round($totalFeedConsumed, 2),
            'spend_meds_vaccines' => round($totalMedsVaccines, 2),
            'spend_labor_sanit_other' => round($totalLaborSanitOther, 2),
            'spend_overhead_rent' => round($totalBatchOverheadShare + $monthlyOverhead, 2),
            'total_operating_cost' => $totalOperatingCost,
            'net_farm_profit' => $netFarmProfit,

            'general_expenses' => $generalExpenses,
            'batches' => $batchRows,
        ];
    }

    public function exportFullBackup(): array
    {
        $tables = [
            'incubation_batches',
            'incubation_daily_logs',
            'incubation_expenses',
            'inventory_items',
            'production_batches',
            'production_daily_logs',
            'batch_sales',
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

