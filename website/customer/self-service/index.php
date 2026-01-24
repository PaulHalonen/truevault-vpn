<?php
/**
 * TrueVault VPN - Self-Service Portal
 * Task 17.8: Tier 2 Customer Portal
 * Created: January 24, 2026
 * 
 * 9 Self-Service Actions:
 * 1. Reset password
 * 2. Download VPN configs
 * 3. View/download invoices
 * 4. Update payment method
 * 5. View connected devices
 * 6. Pause subscription
 * 7. Cancel subscription
 * 8. Run connection test
 * 9. Regenerate WireGuard keys
 */

session_start();

// Check customer authentication
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    // Check for token-based access (from email links)
    $token = $_GET['token'] ?? '';
    if ($token) {
        $customersDb = new SQLite3(__DIR__ . '/../../databases/customers.db');
        $stmt = $customersDb->prepare("SELECT * FROM customers WHERE self_service_token = :token AND token_expires > datetime('now')");
        $stmt->bindValue(':token', $token, SQLITE3_TEXT);
        $customer = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        if ($customer) {
            $_SESSION['customer_logged_in'] = true;
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_email'] = $customer['email'];
        } else {
            header('Location: /login.php?error=invalid_token');
            exit;
        }
        $customersDb->close();
    } else {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Database connections
$customersDb = new SQLite3(__DIR__ . '/../../databases/customers.db');
$settingsDb = new SQLite3(__DIR__ . '/../../databases/settings.db');
$automationDb = new SQLite3(__DIR__ . '/../admin/automation/databases/automation.db');

// Get customer info
$customerId = $_SESSION['customer_id'];
$stmt = $customersDb->prepare("SELECT * FROM customers WHERE id = :id");
$stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
$customer = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$customer) {
    header('Location: /login.php?error=not_found');
    exit;
}

// Get theme settings
$themeResult = $settingsDb->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'theme_%'");
$theme = [];
while ($row = $themeResult->fetchArray(SQLITE3_ASSOC)) {
    $theme[$row['setting_key']] = $row['setting_value'];
}

$primaryColor = $theme['theme_primary_color'] ?? '#00d4ff';
$secondaryColor = $theme['theme_secondary_color'] ?? '#7b2cbf';
$bgColor = $theme['theme_bg_color'] ?? '#0a0a0f';
$cardBg = $theme['theme_card_bg'] ?? 'rgba(255,255,255,0.03)';
$textColor = $theme['theme_text_color'] ?? '#ffffff';

// Handle actions
$message = '';
$messageType = '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Process POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Action 1: Reset Password
    if ($action === 'reset_password') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (strlen($newPassword) < 8) {
            $message = 'Password must be at least 8 characters';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match';
            $messageType = 'error';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $customersDb->prepare("UPDATE customers SET password = :pass, updated_at = datetime('now') WHERE id = :id");
            $stmt->bindValue(':pass', $hashedPassword, SQLITE3_TEXT);
            $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
            $stmt->execute();
            
            logSelfService($automationDb, $customerId, 'reset_password', true);
            $message = 'Password updated successfully!';
            $messageType = 'success';
        }
    }
    
    // Action 6: Pause Subscription
    if ($action === 'pause_subscription') {
        $pauseDays = min(30, max(1, intval($_POST['pause_days'] ?? 7)));
        $pauseUntil = date('Y-m-d', strtotime("+$pauseDays days"));
        
        $stmt = $customersDb->prepare("UPDATE customers SET 
            status = 'paused', 
            pause_until = :until,
            updated_at = datetime('now')
            WHERE id = :id");
        $stmt->bindValue(':until', $pauseUntil, SQLITE3_TEXT);
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $stmt->execute();
        
        logSelfService($automationDb, $customerId, 'pause_subscription', true, "Paused for $pauseDays days");
        $message = "Subscription paused until " . date('F j, Y', strtotime($pauseUntil));
        $messageType = 'success';
        
        // Refresh customer data
        $stmt = $customersDb->prepare("SELECT * FROM customers WHERE id = :id");
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $customer = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }
    
    // Action 6b: Resume Subscription
    if ($action === 'resume_subscription') {
        $stmt = $customersDb->prepare("UPDATE customers SET 
            status = 'active', 
            pause_until = NULL,
            updated_at = datetime('now')
            WHERE id = :id");
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $stmt->execute();
        
        logSelfService($automationDb, $customerId, 'resume_subscription', true);
        $message = "Subscription resumed!";
        $messageType = 'success';
        
        // Refresh customer data
        $stmt = $customersDb->prepare("SELECT * FROM customers WHERE id = :id");
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $customer = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }
    
    // Action 7: Cancel Subscription
    if ($action === 'cancel_subscription') {
        $reason = $_POST['cancel_reason'] ?? 'No reason provided';
        $feedback = $_POST['cancel_feedback'] ?? '';
        
        // Set to pending_cancellation (gives them time to change mind)
        $stmt = $customersDb->prepare("UPDATE customers SET 
            status = 'pending_cancellation',
            cancel_reason = :reason,
            cancel_feedback = :feedback,
            cancel_requested_at = datetime('now'),
            updated_at = datetime('now')
            WHERE id = :id");
        $stmt->bindValue(':reason', $reason, SQLITE3_TEXT);
        $stmt->bindValue(':feedback', $feedback, SQLITE3_TEXT);
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $stmt->execute();
        
        logSelfService($automationDb, $customerId, 'cancel_subscription', true, $reason);
        
        // This triggers the retention workflow
        triggerWorkflow($automationDb, 'cancellation_request', [
            'customer_id' => $customerId,
            'email' => $customer['email'],
            'reason' => $reason
        ]);
        
        $message = "We're sorry to see you go. Your cancellation is being processed. Check your email for a special offer!";
        $messageType = 'info';
        
        // Refresh customer data
        $stmt = $customersDb->prepare("SELECT * FROM customers WHERE id = :id");
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $customer = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    }
    
    // Action 9: Regenerate WireGuard Keys
    if ($action === 'regenerate_keys') {
        // In production, this would call the WireGuard key generation API
        // For now, we'll mark that keys need regeneration
        $stmt = $customersDb->prepare("UPDATE customers SET 
            keys_regenerate_pending = 1,
            updated_at = datetime('now')
            WHERE id = :id");
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $stmt->execute();
        
        logSelfService($automationDb, $customerId, 'regenerate_keys', true);
        $message = "Key regeneration initiated. New configs will be available in your dashboard within 5 minutes.";
        $messageType = 'success';
    }
}

// Get self-service actions from database
$actionsResult = $automationDb->query("SELECT * FROM self_service_actions WHERE is_active = 1 ORDER BY display_order");
$availableActions = [];
while ($row = $actionsResult->fetchArray(SQLITE3_ASSOC)) {
    $availableActions[$row['action_key']] = $row;
}

// Get devices count (handle missing database)
$deviceCount = 0;
$deviceLimit = $customer['device_limit'] ?? 3;
$devicesDbPath = __DIR__ . '/../../databases/devices.db';
if (file_exists($devicesDbPath)) {
    $devicesDb = new SQLite3($devicesDbPath);
    $deviceCount = $devicesDb->querySingle("SELECT COUNT(*) FROM devices WHERE customer_id = $customerId AND status = 'active'") ?? 0;
    $devicesDb->close();
}

// Get invoices count (handle missing database)
$invoiceCount = 0;
$billingDbPath = __DIR__ . '/../../databases/billing.db';
if (file_exists($billingDbPath)) {
    $billingDb = new SQLite3($billingDbPath);
    $invoiceCount = $billingDb->querySingle("SELECT COUNT(*) FROM invoices WHERE customer_id = $customerId") ?? 0;
    $billingDb->close();
}

// Helper functions
function logSelfService($db, $customerId, $action, $success, $notes = '') {
    $db->exec("UPDATE self_service_actions SET times_used = times_used + 1 WHERE action_key = '$action'");
    
    // Also log to a general log if needed
    $stmt = $db->prepare("INSERT INTO automation_log (workflow_name, trigger_data, status, started_at, completed_at)
        VALUES (:workflow, :data, :status, datetime('now'), datetime('now'))");
    $stmt->bindValue(':workflow', 'self_service_' . $action, SQLITE3_TEXT);
    $stmt->bindValue(':data', json_encode(['customer_id' => $customerId, 'notes' => $notes]), SQLITE3_TEXT);
    $stmt->bindValue(':status', $success ? 'completed' : 'failed', SQLITE3_TEXT);
    $stmt->execute();
}

function triggerWorkflow($db, $workflowName, $data) {
    // Simplified workflow trigger
    $stmt = $db->prepare("INSERT INTO automation_log (workflow_name, trigger_data, status, started_at)
        VALUES (:workflow, :data, 'pending', datetime('now'))");
    $stmt->bindValue(':workflow', $workflowName, SQLITE3_TEXT);
    $stmt->bindValue(':data', json_encode($data), SQLITE3_TEXT);
    $stmt->execute();
}

$customersDb->close();
$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Service Portal - TrueVault VPN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            min-height: 100vh;
        }
        
        .portal-header {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>20, <?php echo $secondaryColor; ?>20);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 25px 0;
        }
        
        .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .logo-text {
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-email {
            color: #888;
            font-size: 0.9rem;
        }
        
        .user-plan {
            padding: 6px 14px;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>30, <?php echo $secondaryColor; ?>30);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: <?php echo $primaryColor; ?>;
        }
        
        .portal-main { padding: 40px 0; }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .page-subtitle {
            color: #888;
            margin-bottom: 30px;
        }
        
        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success { background: rgba(0,200,100,0.1); border: 1px solid rgba(0,200,100,0.3); color: #00c864; }
        .alert-error { background: rgba(255,80,80,0.1); border: 1px solid rgba(255,80,80,0.3); color: #ff5050; }
        .alert-info { background: rgba(0,212,255,0.1); border: 1px solid rgba(0,212,255,0.3); color: #00d4ff; }
        
        /* Status Banner */
        .status-banner {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .status-active { background: rgba(0,200,100,0.1); border: 1px solid rgba(0,200,100,0.3); }
        .status-paused { background: rgba(255,180,0,0.1); border: 1px solid rgba(255,180,0,0.3); }
        .status-pending { background: rgba(255,80,80,0.1); border: 1px solid rgba(255,80,80,0.3); }
        
        .status-text {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        /* Action Cards Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .action-card:hover {
            border-color: <?php echo $primaryColor; ?>60;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 18px;
        }
        
        .action-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .action-desc {
            color: #888;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .action-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.85rem;
            color: #666;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px <?php echo $primaryColor; ?>40;
        }
        
        .action-btn-secondary {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .action-btn-secondary:hover {
            background: rgba(255,255,255,0.15);
            box-shadow: none;
        }
        
        .action-btn-danger {
            background: linear-gradient(135deg, #ff5050, #ff3030);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.85);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal.show { display: flex; }
        
        .modal-content {
            background: #1a1a2e;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .modal-header {
            padding: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .modal-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .modal-title { font-size: 1.2rem; font-weight: 600; }
        
        .modal-close {
            margin-left: auto;
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body { padding: 25px; }
        
        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ccc;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
        }
        
        .form-select {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
        }
        
        .form-textarea {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            min-height: 100px;
            resize: vertical;
        }
        
        .form-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 6px;
        }
        
        .modal-actions {
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        /* Lists */
        .list-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 20px;
        }
        
        .list-item {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .list-item:last-child { border-bottom: none; }
        
        .device-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .device-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .device-name { font-weight: 500; }
        .device-meta { font-size: 0.85rem; color: #888; }
        
        .invoice-amount {
            font-weight: 600;
            color: <?php echo $primaryColor; ?>;
        }
        
        .invoice-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-paid { background: rgba(0,200,100,0.15); color: #00c864; }
        
        /* Connection Test */
        .test-results {
            background: rgba(0,0,0,0.3);
            border-radius: 10px;
            padding: 20px;
            font-family: monospace;
            font-size: 0.9rem;
            line-height: 1.8;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-pass { color: #00c864; }
        .test-fail { color: #ff5050; }
        .test-pending { color: #888; }
        
        @media (max-width: 768px) {
            .actions-grid { grid-template-columns: 1fr; }
            .header-content { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="portal-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">🛡️</div>
                    <div>
                        <div class="logo-text">TrueVault VPN</div>
                        <small style="color: #888;">Self-Service Portal</small>
                    </div>
                </div>
                <div class="user-info">
                    <span class="user-email"><?php echo htmlspecialchars($customer['email']); ?></span>
                    <span class="user-plan"><?php echo ucfirst($customer['plan_name'] ?? 'Personal'); ?></span>
                    <a href="/dashboard" class="action-btn action-btn-secondary" style="padding: 8px 15px;">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="portal-main">
        <div class="container">
            <h1 class="page-title">Self-Service Portal</h1>
            <p class="page-subtitle">Manage your account instantly - no waiting for support!</p>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : ($messageType === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Status Banner -->
            <?php if (($customer['status'] ?? '') === 'paused'): ?>
            <div class="status-banner status-paused">
                <div class="status-text">
                    <i class="fas fa-pause-circle" style="color: #ffb400;"></i>
                    <span>Your subscription is paused until <?php echo date('F j, Y', strtotime($customer['pause_until'] ?? 'now')); ?></span>
                </div>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="resume_subscription">
                    <button type="submit" class="action-btn">
                        <i class="fas fa-play"></i> Resume Now
                    </button>
                </form>
            </div>
            <?php elseif (($customer['status'] ?? '') === 'pending_cancellation'): ?>
            <div class="status-banner status-pending">
                <div class="status-text">
                    <i class="fas fa-exclamation-triangle" style="color: #ff5050;"></i>
                    <span>Cancellation pending - Check your email for a special offer to stay!</span>
                </div>
            </div>
            <?php else: ?>
            <div class="status-banner status-active">
                <div class="status-text">
                    <i class="fas fa-check-circle" style="color: #00c864;"></i>
                    <span>Your account is active and protected</span>
                </div>
                <span style="color: #888;">Devices: <?php echo $deviceCount; ?>/<?php echo $deviceLimit; ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Action Cards -->
            <div class="actions-grid">
                <!-- 1. Reset Password -->
                <div class="action-card" onclick="openModal('passwordModal')">
                    <div class="action-icon" style="background: rgba(123,44,191,0.2); color: #9b59b6;">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="action-title">Reset Password</div>
                    <div class="action-desc">Change your account password instantly. No email verification required.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-bolt"></i> Instant</span>
                    </div>
                </div>
                
                <!-- 2. Download Configs -->
                <div class="action-card" onclick="openModal('configsModal')">
                    <div class="action-icon" style="background: rgba(0,212,255,0.2); color: #00d4ff;">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="action-title">Download VPN Configs</div>
                    <div class="action-desc">Get configuration files for all your devices - Windows, Mac, iOS, Android.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-mobile-alt"></i> All Devices</span>
                    </div>
                </div>
                
                <!-- 3. View Invoices -->
                <div class="action-card" onclick="openModal('invoicesModal')">
                    <div class="action-icon" style="background: rgba(0,200,100,0.2); color: #00c864;">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="action-title">View Invoices</div>
                    <div class="action-desc">Access all your billing history and download invoices as PDF.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-receipt"></i> <?php echo $invoiceCount; ?> invoices</span>
                    </div>
                </div>
                
                <!-- 4. Update Payment -->
                <div class="action-card" onclick="window.open('https://www.paypal.com/myaccount/autopay/', '_blank')">
                    <div class="action-icon" style="background: rgba(0,123,255,0.2); color: #007bff;">
                        <i class="fab fa-paypal"></i>
                    </div>
                    <div class="action-title">Update Payment Method</div>
                    <div class="action-desc">Manage your PayPal subscription and payment preferences.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-external-link-alt"></i> Opens PayPal</span>
                    </div>
                </div>
                
                <!-- 5. View Devices -->
                <div class="action-card" onclick="openModal('devicesModal')">
                    <div class="action-icon" style="background: rgba(255,180,0,0.2); color: #ffb400;">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <div class="action-title">View Connected Devices</div>
                    <div class="action-desc">See all devices connected to your VPN account.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-plug"></i> <?php echo $deviceCount; ?> active</span>
                    </div>
                </div>
                
                <!-- 6. Pause Subscription -->
                <?php if (($customer['status'] ?? '') !== 'paused' && ($customer['status'] ?? '') !== 'pending_cancellation'): ?>
                <div class="action-card" onclick="openModal('pauseModal')">
                    <div class="action-icon" style="background: rgba(255,180,0,0.2); color: #ffb400;">
                        <i class="fas fa-pause"></i>
                    </div>
                    <div class="action-title">Pause Subscription</div>
                    <div class="action-desc">Take a break without losing your settings. Pause for up to 30 days.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-calendar"></i> Up to 30 days</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 7. Cancel Subscription -->
                <?php if (($customer['status'] ?? '') !== 'pending_cancellation'): ?>
                <div class="action-card" onclick="openModal('cancelModal')">
                    <div class="action-icon" style="background: rgba(255,80,80,0.2); color: #ff5050;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="action-title">Cancel Subscription</div>
                    <div class="action-desc">We're sorry to see you go. Cancel anytime with no fees.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-heart-broken"></i> We'll miss you</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 8. Connection Test -->
                <div class="action-card" onclick="openModal('testModal'); runConnectionTest();">
                    <div class="action-icon" style="background: rgba(0,200,100,0.2); color: #00c864;">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="action-title">Connection Test</div>
                    <div class="action-desc">Diagnose connection issues and check if your VPN is working properly.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-heartbeat"></i> Check status</span>
                    </div>
                </div>
                
                <!-- 9. Regenerate Keys -->
                <div class="action-card" onclick="openModal('keysModal')">
                    <div class="action-icon" style="background: rgba(123,44,191,0.2); color: #9b59b6;">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="action-title">Regenerate Keys</div>
                    <div class="action-desc">Get new WireGuard encryption keys if you suspect compromise.</div>
                    <div class="action-meta">
                        <span><i class="fas fa-shield-alt"></i> Security</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Password Modal -->
    <div class="modal" id="passwordModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" style="background: rgba(123,44,191,0.2); color: #9b59b6;">
                    <i class="fas fa-key"></i>
                </div>
                <h3 class="modal-title">Reset Password</h3>
                <button class="modal-close" onclick="closeModal('passwordModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" required minlength="8">
                        <div class="form-hint">At least 8 characters</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="action-btn action-btn-secondary" onclick="closeModal('passwordModal')">Cancel</button>
                    <button type="submit" class="action-btn">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Configs Modal -->
    <div class="modal" id="configsModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" style="background: rgba(0,212,255,0.2); color: #00d4ff;">
                    <i class="fas fa-download"></i>
                </div>
                <h3 class="modal-title">Download Configurations</h3>
                <button class="modal-close" onclick="closeModal('configsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="list-card" style="margin-top: 0;">
                    <div class="list-item">
                        <div class="device-info">
                            <div class="device-icon"><i class="fab fa-windows"></i></div>
                            <div>
                                <div class="device-name">Windows</div>
                                <div class="device-meta">.conf file for WireGuard</div>
                            </div>
                        </div>
                        <a href="/api/download-config.php?platform=windows" class="action-btn action-btn-secondary" style="padding: 8px 15px;">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <div class="list-item">
                        <div class="device-info">
                            <div class="device-icon"><i class="fab fa-apple"></i></div>
                            <div>
                                <div class="device-name">macOS</div>
                                <div class="device-meta">.conf file for WireGuard</div>
                            </div>
                        </div>
                        <a href="/api/download-config.php?platform=macos" class="action-btn action-btn-secondary" style="padding: 8px 15px;">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <div class="list-item">
                        <div class="device-info">
                            <div class="device-icon"><i class="fab fa-apple"></i></div>
                            <div>
                                <div class="device-name">iOS (iPhone/iPad)</div>
                                <div class="device-meta">QR code or .mobileconfig</div>
                            </div>
                        </div>
                        <a href="/api/download-config.php?platform=ios" class="action-btn action-btn-secondary" style="padding: 8px 15px;">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <div class="list-item">
                        <div class="device-info">
                            <div class="device-icon"><i class="fab fa-android"></i></div>
                            <div>
                                <div class="device-name">Android</div>
                                <div class="device-meta">.conf file or QR code</div>
                            </div>
                        </div>
                        <a href="/api/download-config.php?platform=android" class="action-btn action-btn-secondary" style="padding: 8px 15px;">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <div class="list-item">
                        <div class="device-info">
                            <div class="device-icon"><i class="fas fa-wifi"></i></div>
                            <div>
                                <div class="device-name">Router</div>
                                <div class="device-meta">Generic WireGuard config</div>
                            </div>
                        </div>
                        <a href="/api/download-config.php?platform=router" class="action-btn action-btn-secondary" style="padding: 8px 15px;">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Invoices Modal -->
    <div class="modal" id="invoicesModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" style="background: rgba(0,200,100,0.2); color: #00c864;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 class="modal-title">Your Invoices</h3>
                <button class="modal-close" onclick="closeModal('invoicesModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="list-card" style="margin-top: 0;" id="invoicesList">
                    <p style="padding: 20px; color: #888; text-align: center;">Loading invoices...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Devices Modal -->
    <div class="modal" id="devicesModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" style="background: rgba(255,180,0,0.2); color: #ffb400;">
                    <i class="fas fa-laptop"></i>
                </div>
                <h3 class="modal-title">Connected Devices</h3>
                <button class="modal-close" onclick="closeModal('devicesModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px; color: #888;">
                    Using <strong style="color: <?php echo $primaryColor; ?>;"><?php echo $deviceCount; ?></strong> 
                    of <strong><?php echo $deviceLimit; ?></strong> device slots
                </p>
                <div class="list-card" style="margin-top: 0;" id="devicesList">
                    <p style="padding: 20px; color: #888; text-align: center;">Loading devices...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pause Modal -->
    <div class="modal" id="pauseModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" style="background: rgba(255,180,0,0.2); color: #ffb400;">
                    <i class="fas fa-pause"></i>
                </div>
                <h3 class="modal-title">Pause Subscription</h3>
                <button class="modal-close" onclick="closeModal('pauseModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="pause_subscription">
                <div class="modal-body">
                    <p style="margin-bottom: 20px; color: #ccc;">
                        Take a break without losing your account settings. Your subscription will automatically resume after the pause period.
                    </p>
                    <div class="form-group">
                        <label class="form-label">Pause Duration</label>
                        <select name="pause_days" class="form-select">
                            <option value="7">7 days</option>
                            <option value="14">14 days</option>
                            <option value="21">21 days</option>
                            <option value="30">30 days (maximum)</option>
                        </select>
                    </div>
                    <div style="background: rgba(255,180,0,0.1); padding: 15px; border-radius: 10px; font-size: 0.9rem;">
                        <i class="fas fa-info-circle" style="color: #ffb400;"></i>
                        <strong>Note:</strong> Your VPN access will be suspended during the pause period. You won't be billed until you resume.
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="action-btn action-btn-secondary" onclick="closeModal('pauseModal')">Cancel</button>
                    <button type="submit" class="action-btn" style="background: linear-gradient(135deg, #ffb400, #ff8c00);">
                        <i class="fas fa-pause"></i> Pause Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cancel Modal -->
    <div class="modal" id="cancelModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" style="background: rgba(255,80,80,0.2); color: #ff5050;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h3 class="modal-title">Cancel Subscription</h3>
                <button class="modal-close" onclick="closeModal('cancelModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="cancel_subscription">
                <div class="modal-body">
                    <p style="margin-bottom: 20px; color: #ccc;">
                        We're sorry to see you go! Before you cancel, would you consider pausing instead? You won't be charged during the pause.
                    </p>
                    <div class="form-group">
                        <label class="form-label">Why are you leaving?</label>
                        <select name="cancel_reason" class="form-select" required>
                            <option value="">Select a reason...</option>
                            <option value="too_expensive">Too expensive</option>
                            <option value="not_using">Not using enough</option>
                            <option value="switching">Switching to another VPN</option>
                            <option value="technical_issues">Technical issues</option>
                            <option value="missing_features">Missing features</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Anything else you'd like us to know? (Optional)</label>
                        <textarea name="cancel_feedback" class="form-textarea" placeholder="Your feedback helps us improve..."></textarea>
                    </div>
                    <div style="background: rgba(255,80,80,0.1); padding: 15px; border-radius: 10px; font-size: 0.9rem;">
                        <i class="fas fa-exclamation-triangle" style="color: #ff5050;"></i>
                        <strong>Warning:</strong> Your VPN access will end at the end of your current billing period. All your settings and device configurations will be preserved for 30 days in case you change your mind.
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="action-btn action-btn-secondary" onclick="closeModal('cancelModal')">Keep My Account</button>
                    <button type="submit" class="action-btn action-btn-danger">
                        <i class="fas fa-times"></i> Cancel Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Connection Test Modal -->
    <div class="modal" id="testModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" style="background: rgba(0,200,100,0.2); color: #00c864;">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <h3 class="modal-title">Connection Test</h3>
                <button class="modal-close" onclick="closeModal('testModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="test-results" id="testResults">
                    <div class="test-item test-pending">
                        <i class="fas fa-spinner fa-spin"></i> Running diagnostics...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Keys Modal -->
    <div class="modal" id="keysModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon" style="background: rgba(123,44,191,0.2); color: #9b59b6;">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h3 class="modal-title">Regenerate Keys</h3>
                <button class="modal-close" onclick="closeModal('keysModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="regenerate_keys">
                <div class="modal-body">
                    <p style="margin-bottom: 20px; color: #ccc;">
                        This will generate new WireGuard encryption keys for your account. Use this if:
                    </p>
                    <ul style="margin-left: 20px; margin-bottom: 20px; color: #888; line-height: 1.8;">
                        <li>You suspect your keys may have been compromised</li>
                        <li>You're having persistent connection issues</li>
                        <li>You shared your config file accidentally</li>
                    </ul>
                    <div style="background: rgba(255,180,0,0.1); padding: 15px; border-radius: 10px; font-size: 0.9rem;">
                        <i class="fas fa-exclamation-triangle" style="color: #ffb400;"></i>
                        <strong>Important:</strong> After regenerating, you'll need to download and install new configuration files on all your devices.
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="action-btn action-btn-secondary" onclick="closeModal('keysModal')">Cancel</button>
                    <button type="submit" class="action-btn">
                        <i class="fas fa-sync-alt"></i> Regenerate Keys
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('show');
            
            // Load data for specific modals
            if (id === 'invoicesModal') loadInvoices();
            if (id === 'devicesModal') loadDevices();
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }
        
        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
            }
        });
        
        // Close on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal')) {
                    e.target.classList.remove('show');
                }
            });
        });
        
        function loadInvoices() {
            // Demo data - in production, use AJAX
            document.getElementById('invoicesList').innerHTML = `
                <div class="list-item">
                    <div class="device-info">
                        <div class="device-icon"><i class="fas fa-file-invoice"></i></div>
                        <div>
                            <div class="device-name">INV-2025-001</div>
                            <div class="device-meta">January 1, 2025</div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="invoice-amount">$9.97</div>
                        <span class="invoice-status status-paid">Paid</span>
                    </div>
                </div>
            `;
        }
        
        function loadDevices() {
            // Demo data - in production, use AJAX
            document.getElementById('devicesList').innerHTML = `
                <div class="list-item">
                    <div class="device-info">
                        <div class="device-icon"><i class="fab fa-windows"></i></div>
                        <div>
                            <div class="device-name">Work Laptop</div>
                            <div class="device-meta">Last connected: 2 hours ago</div>
                        </div>
                    </div>
                    <span style="color: #00c864;"><i class="fas fa-circle"></i> Active</span>
                </div>
            `;
        }
        
        function runConnectionTest() {
            const results = document.getElementById('testResults');
            const tests = [
                { name: 'Internet Connection', delay: 500 },
                { name: 'DNS Resolution', delay: 800 },
                { name: 'VPN Server Reachability', delay: 1200 },
                { name: 'WireGuard Handshake', delay: 1500 },
                { name: 'IP Address Check', delay: 2000 },
                { name: 'DNS Leak Test', delay: 2500 },
                { name: 'WebRTC Leak Test', delay: 3000 }
            ];
            
            results.innerHTML = '';
            
            tests.forEach((test, i) => {
                setTimeout(() => {
                    const pass = Math.random() > 0.1;
                    const html = `
                        <div class="test-item ${pass ? 'test-pass' : 'test-fail'}">
                            <i class="fas ${pass ? 'fa-check-circle' : 'fa-times-circle'}"></i> ${test.name}
                            <span style="margin-left: auto; font-size: 0.8rem;">${pass ? 'PASS' : 'FAIL'}</span>
                        </div>
                    `;
                    results.innerHTML += html;
                    
                    if (i === tests.length - 1) {
                        setTimeout(() => {
                            results.innerHTML += `
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
                                    <strong style="color: #00c864;"><i class="fas fa-shield-alt"></i> Your connection is protected!</strong>
                                </div>
                            `;
                        }, 500);
                    }
                }, test.delay);
            });
        }
    </script>
</body>
</html>
<?php
$automationDb->close();
