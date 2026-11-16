<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

class Database
{
    private ?PDO $pdo = null;
    private string $jsonFile;
    private array $store = [];
    private bool $usingMysql = false;
    private bool $usingSupabase = false;
    private string $supabaseUrl = '';
    private string $supabaseKey = '';
    private string $supabaseRowId = 'blackswan_main';

    public function __construct(string $storageDir, string $schemaFile)
    {
        $this->supabaseUrl = rtrim((string) (getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? '')), '/');
        $this->supabaseKey = (string) (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? ''));
        if ($this->supabaseUrl !== '' && $this->supabaseKey !== '') {
            $this->usingSupabase = true;
        }

        if (!$this->usingSupabase) {
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0775, true);
            }
            if (!is_dir($storageDir) || !is_writable($storageDir)) {
                $storageDir = rtrim(sys_get_temp_dir(), '/') . '/blackswan_db';
                if (!is_dir($storageDir)) {
                    @mkdir($storageDir, 0775, true);
                }
            }
        }
        $this->jsonFile = rtrim($storageDir, '/') . '/poultry_store.json';

        // Attempt PDO MySQL connection if configured or active
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_DATABASE') ?: 'poultry_farm_system';
        $user = getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';

        if (getenv('USE_MYSQL') === 'true') {
            try {
                $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 2,
                ]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
                $this->pdo = $pdo;
                $this->usingMysql = true;
            } catch (Throwable $e) {
                $this->usingMysql = false;
            }
        }

        $this->loadStore();
    }

    public function isMysqlActive(): bool
    {
        return $this->usingMysql;
    }

    private function buildSupabaseHeaders(bool $isWrite = false): string
    {
        $headers = "apikey: {$this->supabaseKey}\r\n";
        // Legacy JWT service_role keys require Authorization: Bearer; new sb_secret_ keys use apikey only
        if (!str_starts_with($this->supabaseKey, 'sb_secret_')) {
            $headers .= "Authorization: Bearer {$this->supabaseKey}\r\n";
        }
        if ($isWrite) {
            $headers .= "Content-Type: application/json\r\nPrefer: resolution=merge-duplicates,return=minimal\r\n";
        } else {
            $headers .= "Accept: application/json\r\n";
        }
        return $headers;
    }

    private function loadStore(): void
    {
        if ($this->usingSupabase) {
            $url = $this->supabaseUrl . '/rest/v1/app_store?id=eq.' . rawurlencode($this->supabaseRowId) . '&select=data&limit=1';
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => $this->buildSupabaseHeaders(false),
                    'timeout' => 8,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = @file_get_contents($url, false, $ctx);
            if (is_string($raw) && $raw !== '') {
                $rows = json_decode($raw, true);
                if (is_array($rows) && isset($rows[0]['data']) && is_array($rows[0]['data']) && isset($rows[0]['data']['incubation_batches'])) {
                    $this->store = $rows[0]['data'];
                    return;
                }
            }
            $this->store = $this->getInitialSeedData();
            $this->saveStore();
            return;
        }

        if (file_exists($this->jsonFile)) {
            $decoded = json_decode((string) file_get_contents($this->jsonFile), true);
            if (is_array($decoded) && isset($decoded['incubation_batches'])) {
                $this->store = $decoded;
                return;
            }
        }

        $this->store = $this->getInitialSeedData();
        $this->saveStore();
    }

    private function saveStore(): void
    {
        if ($this->usingSupabase) {
            $url = $this->supabaseUrl . '/rest/v1/app_store?on_conflict=id';
            $body = json_encode([
                'id' => $this->supabaseRowId,
                'data' => $this->store,
                'updated_at' => gmdate('c'),
            ], JSON_UNESCAPED_UNICODE);
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => $this->buildSupabaseHeaders(true),
                    'content' => $body,
                    'timeout' => 8,
                    'ignore_errors' => true,
                ],
            ]);
            @file_get_contents($url, false, $ctx);
            return;
        }

        @file_put_contents($this->jsonFile, json_encode($this->store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function resetDemoData(): void
    {
        $this->store = $this->getInitialSeedData();
        $this->saveStore();
    }

    public function getTable(string $table): array
    {
        return $this->store[$table] ?? [];
    }

    public function findById(string $table, int $id): ?array
    {
        foreach ($this->getTable($table) as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    public function where(string $table, string $column, mixed $value): array
    {
        $results = [];
        foreach ($this->getTable($table) as $row) {
            if (($row[$column] ?? null) == $value) {
                $results[] = $row;
            }
        }
        return $results;
    }

    public function insert(string $table, array $record): int
    {
        if (!isset($this->store[$table])) {
            $this->store[$table] = [];
        }
        $maxId = 0;
        foreach ($this->store[$table] as $row) {
            if ((int) $row['id'] > $maxId) {
                $maxId = (int) $row['id'];
            }
        }
        $newId = $maxId + 1;
        $record['id'] = $newId;
        if (!isset($record['created_at'])) {
            $record['created_at'] = date('Y-m-d H:i:s');
        }
        $this->store[$table][] = $record;
        $this->saveStore();
        return $newId;
    }

    public function update(string $table, int $id, array $updates): bool
    {
        if (!isset($this->store[$table])) {
            return false;
        }
        foreach ($this->store[$table] as $idx => $row) {
            if ((int) $row['id'] === $id) {
                $this->store[$table][$idx] = array_merge($row, $updates);
                $this->saveStore();
                return true;
            }
        }
        return false;
    }

    public function delete(string $table, int $id): bool
    {
        if (!isset($this->store[$table])) {
            return false;
        }
        $before = count($this->store[$table]);
        $this->store[$table] = array_values(array_filter(
            $this->store[$table],
            fn($row) => (int) ($row['id'] ?? 0) !== $id
        ));
        if (count($this->store[$table]) !== $before) {
            $this->saveStore();
            return true;
        }
        return false;
    }

    public function upsertByColumn(string $table, string $column, mixed $value, array $record): int
    {
        if (!isset($this->store[$table])) {
            $this->store[$table] = [];
        }
        foreach ($this->store[$table] as $idx => $row) {
            if (($row[$column] ?? null) == $value) {
                $this->store[$table][$idx] = array_merge($row, $record);
                $this->saveStore();
                return (int) $row['id'];
            }
        }
        return $this->insert($table, $record);
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->store['settings'][$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): void
    {
        if (!isset($this->store['settings'])) {
            $this->store['settings'] = [];
        }
        $this->store['settings'][$key] = $value;
        $this->saveStore();
    }

    private function getInitialSeedData(): array
    {
        return [
            'settings' => [
                'gemini_api_key' => getenv('GEMINI_API_KEY') ?: '',
                'gemini_model' => 'gemini-3.8-flash',
                'farm_name' => 'Aurelia Agro & Hatchery Ltd',
                'currency' => '₦',
            ],
            'incubation_batches' => [
                [
                    'id' => 1,
                    'batch_code' => 'INC-2026-01',
                    'parent_flock_info' => 'Cobb 500 Breeder Flock — Zaria Grandparent Stock',
                    'flock_age_weeks' => 36,
                    'eggs_set' => 2500,
                    'egg_cost' => 625000.00,
                    'start_date' => '2026-09-20',
                    'status' => 'Moved to Production',
                    'created_at' => '2026-09-20 08:00:00',
                ],
                [
                    'id' => 2,
                    'batch_code' => 'INC-2026-02',
                    'parent_flock_info' => 'Ross 308 Breeder Flock — Kaduna Multiplier Farm',
                    'flock_age_weeks' => 34,
                    'eggs_set' => 3000,
                    'egg_cost' => 780000.00,
                    'start_date' => '2026-10-18',
                    'status' => 'Completed',
                    'created_at' => '2026-10-18 08:30:00',
                ],
                [
                    'id' => 3,
                    'batch_code' => 'INC-2026-03',
                    'parent_flock_info' => 'ISA Brown Layer Breeders — Jos Highland Stock',
                    'flock_age_weeks' => 40,
                    'eggs_set' => 2000,
                    'egg_cost' => 540000.00,
                    'start_date' => '2026-10-30',
                    'status' => 'Active',
                    'created_at' => '2026-10-30 09:15:00',
                ],
            ],
            'incubation_daily_logs' => [
                [
                    'id' => 1,
                    'incubation_batch_id' => 1,
                    'log_date' => '2026-09-28',
                    'milestone' => 'Day 7/10 Candling',
                    'infertiles' => 140,
                    'early_deaths' => 45,
                    'cracked' => 15,
                    'dead_in_shell' => 0,
                    'notes' => 'First formal candling completed; strong embryo vascularization.',
                ],
                [
                    'id' => 2,
                    'incubation_batch_id' => 1,
                    'log_date' => '2026-10-08',
                    'milestone' => 'Day 18 Transfer',
                    'infertiles' => 10,
                    'early_deaths' => 20,
                    'cracked' => 8,
                    'dead_in_shell' => 52,
                    'notes' => 'Transferred to hatcher trays; humidity raised to 70% RH.',
                ],
                [
                    'id' => 3,
                    'incubation_batch_id' => 2,
                    'log_date' => '2026-10-26',
                    'milestone' => 'Day 7/10 Candling',
                    'infertiles' => 180,
                    'early_deaths' => 55,
                    'cracked' => 12,
                    'dead_in_shell' => 0,
                    'notes' => 'Routine Day 8 candling inspection across setter racks A-D.',
                ],
                [
                    'id' => 4,
                    'incubation_batch_id' => 2,
                    'log_date' => '2026-11-05',
                    'milestone' => 'Day 18 Transfer',
                    'infertiles' => 15,
                    'early_deaths' => 18,
                    'cracked' => 10,
                    'dead_in_shell' => 65,
                    'notes' => 'Moved 2,645 viable eggs to hatcher cabinets.',
                ],
                [
                    'id' => 5,
                    'incubation_batch_id' => 3,
                    'log_date' => '2026-11-07',
                    'milestone' => 'Day 7/10 Candling',
                    'infertiles' => 95,
                    'early_deaths' => 28,
                    'cracked' => 9,
                    'dead_in_shell' => 0,
                    'notes' => 'High fertility rate (95.25%) observed at Day 8 candling.',
                ],
            ],
            'incubation_expenses' => [
                [
                    'id' => 1,
                    'incubation_batch_id' => 1,
                    'expense_type' => 'gas',
                    'gas_weight_kg' => 50.00,
                    'amount' => 65000.00,
                    'description' => 'LPG Cylinder Refill (2 x 25kg) for setter thermal backup',
                    'date' => '2026-09-22',
                ],
                [
                    'id' => 2,
                    'incubation_batch_id' => 1,
                    'expense_type' => 'repair',
                    'gas_weight_kg' => 0.00,
                    'amount' => 28000.00,
                    'description' => 'Replaced automatic turner relay & humidity sensor probe',
                    'date' => '2026-10-01',
                ],
                [
                    'id' => 3,
                    'incubation_batch_id' => 2,
                    'expense_type' => 'gas',
                    'gas_weight_kg' => 62.50,
                    'amount' => 81250.00,
                    'description' => 'LPG gas refill during grid outage week',
                    'date' => '2026-10-24',
                ],
                [
                    'id' => 4,
                    'incubation_batch_id' => 2,
                    'expense_type' => 'repair',
                    'gas_weight_kg' => 0.00,
                    'amount' => 18750.00,
                    'description' => 'Hatcher fan belt replacement & calibration',
                    'date' => '2026-11-02',
                ],
                [
                    'id' => 5,
                    'incubation_batch_id' => 3,
                    'expense_type' => 'gas',
                    'gas_weight_kg' => 35.00,
                    'amount' => 45500.00,
                    'description' => 'Initial LPG gas fill for Batch INC-2026-03',
                    'date' => '2026-11-01',
                ],
            ],
            'hatch_breakouts' => [
                [
                    'id' => 1,
                    'incubation_batch_id' => 1,
                    'live_chicks' => 2150,
                    'culls' => 32,
                    'pipped' => 28,
                    'dead_in_shell' => 52,
                    'fertility_rate' => 94.00,
                    'hatchability_rate' => 86.00,
                    'cost_per_chick' => 333.95,
                    'breakout_date' => '2026-10-11',
                ],
                [
                    'id' => 2,
                    'incubation_batch_id' => 2,
                    'live_chicks' => 2560,
                    'culls' => 40,
                    'pipped' => 45,
                    'dead_in_shell' => 65,
                    'fertility_rate' => 93.50,
                    'hatchability_rate' => 85.33,
                    'cost_per_chick' => 343.75,
                    'breakout_date' => '2026-11-08',
                ],
            ],
            'inventory_items' => [
                [
                    'id' => 1,
                    'item_name' => 'Ultima Broiler Super Starter Crumble',
                    'category' => 'starter',
                    'unit_type' => 'g',
                    'initial_quantity_g_or_ml' => 1500000.00, // 1,500 kg (30 bags x 50kg)
                    'total_quantity_g_or_ml' => 880000.00,
                    'total_cost' => 900000.00,
                    'unit_cost_per_g_or_ml' => 0.600000, // ₦0.60 per gram (₦30,000 per 50kg bag)
                    'purchase_date' => '2026-10-10',
                ],
                [
                    'id' => 2,
                    'item_name' => 'Vital Feed Broiler Grower Pellet',
                    'category' => 'grower',
                    'unit_type' => 'g',
                    'initial_quantity_g_or_ml' => 2000000.00, // 2,000 kg (40 bags x 50kg)
                    'total_quantity_g_or_ml' => 1450000.00,
                    'total_cost' => 1100000.00,
                    'unit_cost_per_g_or_ml' => 0.550000, // ₦0.55 per gram (₦27,500 per 50kg bag)
                    'purchase_date' => '2026-10-25',
                ],
                [
                    'id' => 3,
                    'item_name' => 'TopFeeds Finisher / Layer Mash',
                    'category' => 'layer',
                    'unit_type' => 'g',
                    'initial_quantity_g_or_ml' => 1000000.00, // 1,000 kg (20 bags x 50kg)
                    'total_quantity_g_or_ml' => 950000.00,
                    'total_cost' => 520000.00,
                    'unit_cost_per_g_or_ml' => 0.520000, // ₦0.52 per gram
                    'purchase_date' => '2026-11-01',
                ],
                [
                    'id' => 4,
                    'item_name' => 'Gumboro + Lasota Vaccine & Multivitamin Pack',
                    'category' => 'medicine',
                    'unit_type' => 'ml',
                    'initial_quantity_g_or_ml' => 5000.00, // 5,000 ml
                    'total_quantity_g_or_ml' => 3650.00,
                    'total_cost' => 125000.00,
                    'unit_cost_per_g_or_ml' => 25.000000, // ₦25.00 per ml
                    'purchase_date' => '2026-10-10',
                ],
                [
                    'id' => 5,
                    'item_name' => 'Tylodox Broad-Spectrum Poultry Antibiotic (Soluble)',
                    'category' => 'medicine',
                    'unit_type' => 'g',
                    'initial_quantity_g_or_ml' => 3000.00, // 3,000 g
                    'total_quantity_g_or_ml' => 2250.00,
                    'total_cost' => 90000.00,
                    'unit_cost_per_g_or_ml' => 30.000000, // ₦30.00 per gram
                    'purchase_date' => '2026-10-15',
                ],
            ],
            'production_batches' => [
                [
                    'id' => 1,
                    'batch_code' => 'PRD-2026-01',
                    'incubation_batch_id' => 1,
                    'flock_type' => 'Cobb 500 Commercial Broilers',
                    'initial_bird_count' => 2150,
                    'current_bird_count' => 1825,
                    'transferred_incubation_cost' => 718000.00,
                    'house_rent_total' => 250000.00,
                    'target_profit_margin_pct' => 32.00,
                    'start_date' => '2026-10-11',
                    'status' => 'Active',
                    'created_at' => '2026-10-11 10:00:00',
                ],
                [
                    'id' => 2,
                    'batch_code' => 'PRD-2026-02',
                    'incubation_batch_id' => null,
                    'flock_type' => 'Arbor Acres Plus Broilers (Direct DOC)',
                    'initial_bird_count' => 1200,
                    'current_bird_count' => 1185,
                    'transferred_incubation_cost' => 480000.00,
                    'house_rent_total' => 150000.00,
                    'target_profit_margin_pct' => 28.00,
                    'start_date' => '2026-11-01',
                    'status' => 'Active',
                    'created_at' => '2026-11-01 09:00:00',
                ],
            ],
            'production_daily_logs' => [
                [
                    'id' => 1,
                    'production_batch_id' => 1,
                    'log_date' => '2026-10-18',
                    'mortality_count' => 18,
                    'water_liters' => 420.00,
                    'feed_item_id' => 1,
                    'feed_grams' => 320000.00,
                    'feed_cost_calculated' => 192000.00,
                    'med_item_id' => 4,
                    'med_dose_g_ml' => 850.00,
                    'med_cost_calculated' => 21250.00,
                    'labor_cost' => 35000.00,
                    'sanitation_cost' => 18000.00,
                    'notes' => 'Week 1 brooding telemetry & Gumboro first dose vaccination.',
                ],
                [
                    'id' => 2,
                    'production_batch_id' => 1,
                    'log_date' => '2026-10-28',
                    'mortality_count' => 15,
                    'water_liters' => 610.00,
                    'feed_item_id' => 1,
                    'feed_grams' => 300000.00,
                    'feed_cost_calculated' => 180000.00,
                    'med_item_id' => 5,
                    'med_dose_g_ml' => 500.00,
                    'med_cost_calculated' => 15000.00,
                    'labor_cost' => 35000.00,
                    'sanitation_cost' => 22000.00,
                    'notes' => 'Completed Starter phase; fresh wood shavings litter change.',
                ],
                [
                    'id' => 3,
                    'production_batch_id' => 1,
                    'log_date' => '2026-11-06',
                    'mortality_count' => 12,
                    'water_liters' => 890.00,
                    'feed_item_id' => 2,
                    'feed_grams' => 550000.00,
                    'feed_cost_calculated' => 302500.00,
                    'med_item_id' => 4,
                    'med_dose_g_ml' => 500.00,
                    'med_cost_calculated' => 12500.00,
                    'labor_cost' => 40000.00,
                    'sanitation_cost' => 25000.00,
                    'notes' => 'Transitioned to Vital Feed Broiler Grower Pellet; strong weight gain.',
                ],
                [
                    'id' => 4,
                    'production_batch_id' => 2,
                    'log_date' => '2026-11-08',
                    'mortality_count' => 15,
                    'water_liters' => 260.00,
                    'feed_item_id' => 1,
                    'feed_grams' => 180000.00,
                    'feed_cost_calculated' => 108000.00,
                    'med_item_id' => 5,
                    'med_dose_g_ml' => 250.00,
                    'med_cost_calculated' => 7500.00,
                    'labor_cost' => 25000.00,
                    'sanitation_cost' => 12000.00,
                    'notes' => 'Week 1 brooding summary for Batch PRD-2026-02.',
                ],
            ],
            'batch_sales' => [
                [
                    'id' => 1,
                    'production_batch_id' => 1,
                    'sale_date' => '2026-11-07',
                    'buyer_name' => 'Kano Fresh Table Birds Ltd',
                    'buyer_contact' => '0803-555-0192',
                    'birds_sold' => 180,
                    'unit_price' => 1650.00,
                    'total_amount' => 297000.00,
                    'payment_status' => 'Paid',
                ],
                [
                    'id' => 2,
                    'production_batch_id' => 1,
                    'sale_date' => '2026-11-09',
                    'buyer_name' => 'Al-Madina Cold Room & Grill',
                    'buyer_contact' => '0809-441-8820',
                    'birds_sold' => 100,
                    'unit_price' => 1700.00,
                    'total_amount' => 170000.00,
                    'payment_status' => 'Paid',
                ],
            ],
        ];
    }
}

