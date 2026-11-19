<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE && !getenv('VERCEL')) {
    @session_start();
}

$basePath = dirname(__DIR__);

require_once $basePath . '/app/Core/BladeEngine.php';
require_once $basePath . '/app/Core/Database.php';
require_once $basePath . '/app/Core/VeterinaryKnowledgeBase.php';
require_once $basePath . '/app/Core/FarmService.php';
require_once $basePath . '/app/Core/AiCopilotService.php';

use App\Core\AiCopilotService;
use App\Core\BladeEngine;
use App\Core\Database;
use App\Core\FarmService;

$appSecret = (string) (getenv('APP_SECRET') ?: getenv('SUPABASE_SERVICE_ROLE_KEY') ?: 'blackswan_local_dev_secret_change_in_prod');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

$setSignedCookie = static function (string $name, array $payload, int $ttlSeconds = 604800) use ($appSecret, $isHttps): void {
    $data = base64_encode((string) json_encode(array_merge($payload, ['exp' => time() + $ttlSeconds])));
    $sig = hash_hmac('sha256', $data, $appSecret);
    setcookie($name, $data . '.' . $sig, [
        'expires' => time() + $ttlSeconds,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
};

$getSignedCookie = static function (string $name) use ($appSecret): ?array {
    $raw = (string) ($_COOKIE[$name] ?? '');
    if ($raw === '' || !str_contains($raw, '.')) {
        return null;
    }
    [$data, $sig] = explode('.', $raw, 2);
    if (!hash_equals(hash_hmac('sha256', $data, $appSecret), $sig)) {
        return null;
    }
    $decoded = json_decode((string) base64_decode($data, true), true);
    if (!is_array($decoded) || (int) ($decoded['exp'] ?? 0) < time()) {
        return null;
    }
    return $decoded;
};

$clearAppCookie = static function (string $name) use ($isHttps): void {
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[$name]);
};

$setFlash = static function (string $key, string $message) use ($setSignedCookie): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[$key] = $message;
    }
    $setSignedCookie('bs_flash_' . $key, ['msg' => $message], 120);
};

$consumeFlash = static function (string $key) use ($getSignedCookie, $clearAppCookie): ?string {
    $val = null;
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$key])) {
        $val = (string) $_SESSION[$key];
        unset($_SESSION[$key]);
    }
    $cookieVal = $getSignedCookie('bs_flash_' . $key);
    if ($cookieVal !== null) {
        $val = $val ?? (string) ($cookieVal['msg'] ?? '');
        $clearAppCookie('bs_flash_' . $key);
    }
    return $val !== '' ? $val : null;
};

$viewsCachePath = $basePath . '/storage/views';
if (!is_dir($viewsCachePath)) {
    @mkdir($viewsCachePath, 0775, true);
}
if (!is_dir($viewsCachePath) || !is_writable($viewsCachePath)) {
    $viewsCachePath = rtrim(sys_get_temp_dir(), '/') . '/blackswan_views';
    if (!is_dir($viewsCachePath)) {
        @mkdir($viewsCachePath, 0775, true);
    }
}

$db = new Database(
    $basePath . '/storage/database',
    $basePath . '/database/schema.sql'
);
$farm = new FarmService($db);
$ai = new AiCopilotService($farm);
$blade = new BladeEngine(
    $basePath . '/resources/views',
    $viewsCachePath
);

$currentPage = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard', 'incubation', 'layers', 'eggs', 'inventory', 'production', 'sales', 'finance', 'ai'];
if (!in_array($currentPage, $allowedPages, true)) {
    $currentPage = 'dashboard';
}

// Handle Auth Actions (Logout & Login)
if (($_GET['action'] ?? '') === 'logout' || (($_POST['action'] ?? '') === 'logout')) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['farm_manager_logged_in'], $_SESSION['farm_manager_name']);
    }
    $clearAppCookie('blackswan_auth');
    $setFlash('login_success', 'You have been signed out.');
    header('Location: /?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if ($farm->verifyManagerLogin($username, $password)) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['farm_manager_logged_in'] = true;
        }
        $profile = $farm->getManagerProfile();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['farm_manager_name'] = $profile['name'];
        }
        $setSignedCookie('blackswan_auth', ['logged_in' => true, 'name' => $profile['name']], 604800);
        header('Location: /?page=dashboard');
        exit;
    }
    $setFlash('login_error', 'Invalid username or password.');
    header('Location: /?page=login');
    exit;
}

$authCookie = $getSignedCookie('blackswan_auth');
$isManagerAuthenticated = !empty($_SESSION['farm_manager_logged_in']) || !empty($authCookie['logged_in']);

// Require Farm Manager Authentication for all app pages, POST actions, and backups
if (!$isManagerAuthenticated) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($_GET['action'] ?? '') === 'download_backup') {
        header('Location: /?page=login');
        exit;
    }
    $loginError = $consumeFlash('login_error');
    $loginSuccess = $consumeFlash('login_success');
    echo $blade->render('auth.login', [
        'loginError' => $loginError,
        'loginSuccess' => $loginSuccess,
    ]);
    exit;
}

if (($_GET['action'] ?? '') === 'download_backup') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="aurapoultry_backup_' . date('Y-m-d_His') . '.json"');
    echo json_encode($farm->exportFullBackup(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create_incubation_batch':
                $farm->createIncubationBatch($_POST);
                $_SESSION['flash_success'] = 'New incubation batch loaded into setter successfully.';
                header('Location: /?page=incubation');
                exit;

            case 'add_incubation_expense':
                $farm->addIncubationExpense($_POST);
                $_SESSION['flash_success'] = 'Batch-linked operational expense recorded and added to chick cost base.';
                header('Location: /?page=incubation');
                exit;

            case 'add_candling_log':
                $farm->addCandlingLog($_POST);
                $_SESSION['flash_success'] = 'Candling inspection logged and fertility metrics updated.';
                header('Location: /?page=incubation');
                exit;

            case 'record_hatch_breakout':
                $farm->recordHatchBreakout($_POST);
                $_SESSION['flash_success'] = 'Final hatch breakout recorded. Fertility %, Hatchability %, and Initial Cost per Chick computed.';
                header('Location: /?page=incubation');
                exit;

            case 'push_to_production':
                $incId = (int) ($_POST['incubation_batch_id'] ?? 0);
                $farm->pushIncubationToProduction($incId, $_POST);
                $_SESSION['flash_success'] = 'Incubation batch transitioned to Flock Production! Live hatchlings and accumulated incubation cost carried over.';
                header('Location: /?page=production');
                exit;

            case 'record_incubation_payment':
                $farm->recordIncubationPayment(
                    (int) ($_POST['incubation_batch_id'] ?? 0),
                    (float) ($_POST['payment_amount'] ?? 0)
                );
                $_SESSION['flash_success'] = 'Client hatching service payment recorded.';
                header('Location: /?page=incubation');
                exit;

            case 'mark_incubation_collected':
                $farm->markIncubationCollected((int) ($_POST['incubation_batch_id'] ?? 0));
                $_SESSION['flash_success'] = 'Client chicks marked as collected and batch archived.';
                header('Location: /?page=incubation');
                exit;

            case 'add_inventory_item':
                $farm->addInventoryItem($_POST);
                $_SESSION['flash_success'] = 'Bulk purchase converted to base units (g/ml) and unit cost computed.';
                header('Location: /?page=inventory');
                exit;

            case 'update_annual_farm_rent':
                $farm->setAnnualFarmRent((float) ($_POST['annual_farm_rent'] ?? 0));
                $_SESSION['flash_success'] = 'General farm annual rent updated. Daily live-bird rent share recalculated across all active flocks.';
                header('Location: /?page=inventory');
                exit;

            case 'add_general_expense':
                $farm->addGeneralFarmExpense($_POST);
                $_SESSION['flash_success'] = 'General farm overhead expense logged and shared across active live chickens.';
                header('Location: /?page=inventory');
                exit;

            case 'create_production_batch':
                $farm->createProductionBatch($_POST);
                $_SESSION['flash_success'] = 'New commercial production batch created.';
                header('Location: /?page=production');
                exit;

            case 'update_batch_overhead':
                $batchId = (int) ($_POST['production_batch_id'] ?? 0);
                $farm->updateProductionBatchConfig($batchId, $_POST);
                $_SESSION['flash_success'] = 'Fixed batch house rent overhead updated and amortized across surviving birds.';
                header('Location: /?page=production');
                exit;

            case 'add_production_daily_log':
                $farm->addProductionDailyLog($_POST);
                $_SESSION['flash_success'] = 'Daily telemetry recorded: mortality deducted, feed/meds deducted from inventory, and Live Cost per Bird updated.';
                header('Location: /?page=production');
                exit;

            case 'update_target_margin':
                $batchId = (int) ($_POST['production_batch_id'] ?? 0);
                $farm->updateProductionBatchConfig($batchId, $_POST);
                $_SESSION['flash_success'] = 'Target profit margin updated and Recommended Selling Price per Bird recalculated.';
                header('Location: /?page=sales');
                exit;

            case 'record_batch_sale':
                $farm->recordBatchSale($_POST);
                $_SESSION['flash_success'] = 'Commercial bird sale recorded: live flock count deducted and realized revenue updated.';
                header('Location: /?page=sales');
                exit;

            case 'record_sale_payment':
                $saleId = (int) ($_POST['sale_id'] ?? 0);
                $paymentAmount = (float) ($_POST['payment_amount'] ?? 0);
                $farm->recordSalePayment($saleId, $paymentAmount);
                $_SESSION['flash_success'] = 'Customer balance payment recorded and receivables updated.';
                header('Location: /?page=sales');
                exit;

            case 'ask_ai_ajax':
                header('Content-Type: application/json');
                $reply = $ai->ask((string) ($_POST['question'] ?? ''));
                echo json_encode($reply);
                exit;

            case 'mark_reminder_read':
                header('Content-Type: application/json');
                $res = $farm->markReminderAsRead((string) ($_POST['reminder_id'] ?? ''));
                echo json_encode($res);
                exit;

            case 'mark_all_reminders_read':
                header('Content-Type: application/json');
                $res = $farm->markAllRemindersRead();
                echo json_encode($res);
                exit;

            case 'ask_ai_question':
                $ai->ask((string) ($_POST['question'] ?? ''));
                header('Location: /?page=ai');
                exit;

            case 'clear_ai_chat':
                $ai->clearChatHistory();
                $_SESSION['flash_success'] = 'Farm AI conversation history reset.';
                header('Location: /?page=ai');
                exit;

            case 'save_ai_settings':
                $db->setSetting('gemini_api_key', trim((string) ($_POST['gemini_api_key'] ?? '')));
                $db->setSetting('gemini_model', trim((string) ($_POST['gemini_model'] ?? 'gemini-3.8-flash')));
                $_SESSION['flash_success'] = 'AI Copilot configuration saved.';
                header('Location: /?page=ai');
                exit;

            case 'add_ai_memory':
                $fact = trim((string) ($_POST['fact'] ?? ''));
                if ($fact !== '') {
                    $farm->addAiMemory($fact, 'user_instruction');
                    $_SESSION['flash_success'] = 'New farm house rule learned and saved to AI memory.';
                }
                header('Location: /?page=ai');
                exit;

            case 'delete_ai_memory':
                $memId = (int) ($_POST['memory_id'] ?? 0);
                if ($memId > 0) {
                    $farm->deleteAiMemory($memId);
                    $_SESSION['flash_success'] = 'Learned farm rule removed.';
                }
                header('Location: /?page=ai');
                exit;

            case 'add_fixed_asset':
                $farm->addFixedAsset($_POST);
                $_SESSION['flash_success'] = 'Fixed farm asset added to capital register.';
                header('Location: /?page=finance');
                exit;

            case 'delete_fixed_asset':
                $assetId = (int) ($_POST['asset_id'] ?? 0);
                if ($assetId > 0) {
                    $farm->deleteFixedAsset($assetId);
                    $_SESSION['flash_success'] = 'Fixed asset removed from register.';
                }
                header('Location: /?page=finance');
                exit;

            case 'clear_all_farm_data':
                $confirmText = strtoupper(trim((string) ($_POST['confirm_clear'] ?? '')));
                if ($confirmText !== 'CLEAR') {
                    throw new InvalidArgumentException('Type CLEAR to confirm wiping all farm data.');
                }
                $farm->clearAllFarmData();
                $_SESSION['flash_success'] = 'All farm data cleared. You are starting with a clean database.';
                header('Location: /?page=dashboard');
                exit;

            case 'change_manager_password':
                $currentPwd = (string) ($_POST['current_password'] ?? '');
                $newPwd = (string) ($_POST['new_password'] ?? '');
                $confirmPwd = (string) ($_POST['confirm_password'] ?? '');
                if ($newPwd !== $confirmPwd) {
                    throw new InvalidArgumentException('New password and confirmation do not match.');
                }
                $farm->updateManagerPassword($currentPwd, $newPwd);
                $_SESSION['flash_success'] = 'Farm Manager password updated successfully.';
                header('Location: /?page=' . urlencode($currentPage));
                exit;

            case 'create_layer_flock':
                $farm->createLayerFlock($_POST);
                $setFlash('flash_success', 'New layer flock added and ready for daily egg collection.');
                header('Location: /?page=layers');
                exit;

            case 'retire_layer_flock':
                $farm->retireLayerFlock((int) ($_POST['layer_flock_id'] ?? 0));
                $setFlash('flash_success', 'Layer flock marked as retired.');
                header('Location: /?page=layers');
                exit;

            case 'record_layer_daily_log':
                $farm->recordLayerDailyLog($_POST);
                $setFlash('flash_success', 'Daily egg collection and layer care logged to Egg Stockroom.');
                header('Location: /?page=layers');
                exit;

            case 'transfer_eggs_to_incubator':
                $farm->transferEggsToIncubator($_POST);
                $setFlash('flash_success', 'Eggs transferred from stockroom and loaded as an active Incubator batch.');
                header('Location: /?page=eggs');
                exit;

            case 'record_egg_sale':
                $farm->recordEggSale($_POST);
                $setFlash('flash_success', 'Egg sale recorded and deducted from Egg Stockroom.');
                header('Location: /?page=eggs');
                exit;

            case 'record_egg_sale_payment':
                $farm->recordEggSalePayment(
                    (int) ($_POST['egg_sale_id'] ?? 0),
                    (float) ($_POST['payment_amount'] ?? 0)
                );
                $setFlash('flash_success', 'Egg sale balance payment recorded.');
                header('Location: /?page=eggs');
                exit;
        }
    } catch (Throwable $e) {
        $setFlash('flash_error', $e->getMessage());
        header('Location: /?page=' . urlencode($currentPage));
        exit;
    }
}

$flashSuccess = $consumeFlash('flash_success');
$flashError = $consumeFlash('flash_error');

$viewMap = [
    'dashboard' => 'dashboard.index',
    'incubation' => 'incubation.index',
    'layers' => 'layers.index',
    'eggs' => 'eggs.index',
    'inventory' => 'inventory.index',
    'production' => 'production.index',
    'sales' => 'sales.index',
    'finance' => 'finance.index',
    'ai' => 'ai.index',
];

$viewData = [
    'currentPage' => $currentPage,
    'summary' => $farm->getExecutiveSummary(),
    'incubationBatches' => $farm->getIncubationBatchesEnriched(),
    'inventoryItems' => $farm->getInventoryItemsEnriched(),
    'productionBatches' => $farm->getProductionBatchesEnriched(),
    'layersOverview' => $farm->getLayersOverview(),
    'generalRent' => $farm->getGeneralRentMetrics(),
    'generalExpenses' => $farm->getGeneralFarmExpenses(),
    'wholeFarmFinancials' => $farm->getWholeFarmFinancials(),
    'reminders' => $farm->getActiveReminders(),
    'aiChatHistory' => $ai->getChatHistory(),
    'aiMemories' => $farm->getAiMemories(),
    'geminiApiKey' => (string) $db->getSetting('gemini_api_key', ''),
    'geminiModel' => (string) $db->getSetting('gemini_model', 'gemini-3.8-flash'),
    'flashSuccess' => $flashSuccess,
    'flashError' => $flashError,
];

echo $blade->render($viewMap[$currentPage], $viewData);
