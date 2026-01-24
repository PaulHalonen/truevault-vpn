<?php
/**
 * TrueVault VPN - Self-Service Portal
 * Task 17.8: Tier 2 Customer Self-Service
 * Created: January 24, 2026
 * 
 * 9 Self-Service Actions:
 * 1. Reset password
 * 2. Download VPN configs
 * 3. View/download invoices
 * 4. Update payment method
 * 5. View connected devices
 * 6. Regenerate WireGuard keys
 * 7. Pause subscription
 * 8. Cancel subscription
 * 9. Connection test
 */

session_start();

// Check if customer is logged in
$isLoggedIn = isset($_SESSION['customer_id']);
$customerId = $_SESSION['customer_id'] ?? 0;
$customerEmail = $_SESSION['customer_email'] ?? '';

// Get action from URL
$action = $_GET['action'] ?? '';

// Database connections
$settingsDb = new SQLite3(__DIR__ . '/../../admin/databases/settings.db');
$automationDb = new SQLite3(__DIR__ . '/../../admin/automation/databases/automation.db');

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

// Self-service actions configuration
$selfServiceActions = [
    'reset_password' => [
        'icon' => '🔐',
        'title' => 'Reset Password',
        'description' => 'Change your account password securely',
        'requires_login' => false,
        'file' => 'reset-password.php'
    ],
    'download_configs' => [
        'icon' => '📥',
        'title' => 'Download VPN Configs',
        'description' => 'Get configuration files for all your devices',
        'requires_login' => true,
        'file' => 'download-configs.php'
    ],
    'view_invoices' => [
        'icon' => '📄',
        'title' => 'View Invoices',
        'description' => 'Access and download your billing history',
        'requires_login' => true,
        'file' => 'view-invoices.php'
    ],
    'update_payment' => [
        'icon' => '💳',
        'title' => 'Update Payment',
        'description' => 'Change your payment method via PayPal',
        'requires_login' => true,
        'file' => 'update-payment.php'
    ],
    'view_devices' => [
        'icon' => '📱',
        'title' => 'View Devices',
        'description' => 'See all devices connected to your account',
        'requires_login' => true,
        'file' => 'view-devices.php'
    ],
    'regenerate_keys' => [
        'icon' => '🔑',
        'title' => 'Regenerate Keys',
        'description' => 'Generate new WireGuard encryption keys',
        'requires_login' => true,
        'file' => 'regenerate-keys.php'
    ],
    'pause_subscription' => [
        'icon' => '⏸️',
        'title' => 'Pause Subscription',
        'description' => 'Temporarily pause your VPN service (up to 30 days)',
        'requires_login' => true,
        'file' => 'pause-subscription.php'
    ],
    'cancel_subscription' => [
        'icon' => '❌',
        'title' => 'Cancel Subscription',
        'description' => 'Cancel your TrueVault VPN subscription',
        'requires_login' => true,
        'file' => 'cancel-subscription.php'
    ],
    'connection_test' => [
        'icon' => '🔍',
        'title' => 'Connection Test',
        'description' => 'Test your VPN connection and diagnose issues',
        'requires_login' => false,
        'file' => 'connection-test.php'
    ]
];

// Handle specific action
$message = '';
$messageType = '';

// Record self-service access
if ($action && $customerId) {
    $automationDb->exec("UPDATE self_service_actions SET times_used = times_used + 1 WHERE action_key = '$action'");
}

$settingsDb->close();
$automationDb->close();
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
            padding: 20px;
        }
        
        .container { max-width: 1000px; margin: 0 auto; }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #888;
            font-size: 1.1rem;
        }
        
        .login-notice {
            background: rgba(255,180,0,0.1);
            border: 1px solid rgba(255,180,0,0.3);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .login-notice-text {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffb400;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            color: #fff;
        }
        
        .btn-secondary {
            background: <?php echo $cardBg; ?>;
            color: <?php echo $textColor; ?>;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 25px;
            text-decoration: none;
            color: <?php echo $textColor; ?>;
            transition: all 0.3s;
            display: block;
            position: relative;
            overflow: hidden;
        }
        
        .action-card:hover {
            border-color: <?php echo $primaryColor; ?>60;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .action-card.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .action-card:hover::before { opacity: 1; }
        
        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .action-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .action-description {
            color: #888;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .action-arrow {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #555;
            transition: all 0.3s;
        }
        
        .action-card:hover .action-arrow {
            color: <?php echo $primaryColor; ?>;
            transform: translateY(-50%) translateX(5px);
        }
        
        .login-required {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 10px;
            background: rgba(255,180,0,0.15);
            color: #ffb400;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .need-help {
            text-align: center;
            margin-top: 50px;
            padding: 30px;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
        }
        
        .need-help h3 {
            margin-bottom: 10px;
        }
        
        .need-help p {
            color: #888;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #888;
            text-decoration: none;
            margin-bottom: 30px;
            transition: color 0.2s;
        }
        
        .back-link:hover { color: <?php echo $primaryColor; ?>; }
        
        @media (max-width: 600px) {
            .actions-grid { grid-template-columns: 1fr; }
            .login-notice { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="page-header">
            <div class="logo">🛡️</div>
            <h1 class="page-title">Self-Service Portal</h1>
            <p class="page-subtitle">Manage your account quickly without waiting for support</p>
        </div>
        
        <?php if (!$isLoggedIn): ?>
        <div class="login-notice">
            <div class="login-notice-text">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Some actions require you to be logged in to your account</span>
            </div>
            <a href="/login?redirect=/customer/self-service/" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Log In
            </a>
        </div>
        <?php endif; ?>
        
        <div class="actions-grid">
            <?php foreach ($selfServiceActions as $key => $actionConfig): ?>
            <?php 
                $isDisabled = $actionConfig['requires_login'] && !$isLoggedIn;
                $href = $isDisabled ? '#' : "?action=$key";
            ?>
            <a href="<?php echo $href; ?>" class="action-card <?php echo $isDisabled ? 'disabled' : ''; ?>">
                <?php if ($actionConfig['requires_login'] && !$isLoggedIn): ?>
                <span class="login-required"><i class="fas fa-lock"></i> Login Required</span>
                <?php endif; ?>
                
                <div class="action-icon"><?php echo $actionConfig['icon']; ?></div>
                <div class="action-title"><?php echo $actionConfig['title']; ?></div>
                <div class="action-description"><?php echo $actionConfig['description']; ?></div>
                <i class="fas fa-chevron-right action-arrow"></i>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="need-help">
            <h3>🤔 Still Need Help?</h3>
            <p>If you can't find what you're looking for, our support team is here to help.</p>
            <a href="/support/new-ticket" class="btn btn-primary">
                <i class="fas fa-headset"></i> Contact Support
            </a>
        </div>
    </div>
    
    <?php if ($action && isset($selfServiceActions[$action])): ?>
    <!-- Action Modal -->
    <div class="modal" id="actionModal" style="display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #1a1a2e; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h3 style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.5rem;"><?php echo $selfServiceActions[$action]['icon']; ?></span>
                    <?php echo $selfServiceActions[$action]['title']; ?>
                </h3>
                <a href="?" style="background: none; border: none; color: #888; font-size: 1.5rem; text-decoration: none;">&times;</a>
            </div>
            <div style="padding: 20px;">
                <?php include __DIR__ . '/' . $selfServiceActions[$action]['file']; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
