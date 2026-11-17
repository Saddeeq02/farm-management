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

    public function clearAllFarmData(): void
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
            'general_farm_expenses',
            'fixed_assets',
            'farm_ai_knowledge_memory',
            'ai_chat_history',
        ];
        foreach ($tables as $t) {
            $this->store[$t] = [];
        }
        $this->store['settings']['annual_farm_rent'] = 0.0;
        $this->store['settings']['read_reminder_ids'] = [];
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
                'farm_name' => 'Blackswan Farms and General Enterprises',
                'currency' => '₦',
                'annual_farm_rent' => 0.0,
                'manager_username' => 'manager',
                'manager_name' => 'Farm Manager',
                'manager_password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'read_reminder_ids' => [],
            ],
            'incubation_batches' => [],
            'incubation_daily_logs' => [],
            'incubation_expenses' => [],
            'hatch_breakouts' => [],
            'inventory_items' => [],
            'production_batches' => [],
            'production_daily_logs' => [],
            'batch_sales' => [],
            'general_farm_expenses' => [],
            'fixed_assets' => [],
            'farm_ai_knowledge_memory' => [],
            'ai_chat_history' => [],
        ];
    }
}
