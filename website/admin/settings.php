<?php
/**
 * Admin Settings Page - SQLITE3 VERSION
 * 
 * PURPOSE: Manage all system settings from database
 * 100% DATABASE DRIVEN - NO HARDCODING!
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $adminDb = Database::getInstance('admin');
        
        // Update each setting
        $settings = [
            'paypal_client_id', 'paypal_secret', 'paypal_mode', 'paypal_webhook_id',
            'paypal_plan_standard', 'paypal_plan_pro',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_email', 'smtp_from_name',
            'site_name', 'support_email', 'business_name', 'business_address'
        ];
        
        foreach ($settings as $key) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);
                
                // Check if setting exists
                $stmt = $adminDb->prepare("SELECT id FROM system_settings WHERE setting_key = :key");
                $stmt->bindValue(':key', $key, SQLITE3_TEXT);
                $result = $stmt->execute();
                $existing = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($existing) {
                    $stmt = $adminDb->prepare("UPDATE system_settings SET setting_value = :value, updated_at = datetime('now') WHERE setting_key = :key");
                } else {
                    $stmt = $adminDb->prepare("INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at) VALUES (:key, :value, datetime('now'), datetime('now'))");
                }
                $stmt->bindValue(':key', $key, SQLITE3_TEXT);
                $stmt->bindValue(':value', $value, SQLITE3_TEXT);
                $stmt->execute();
            }
        }
        
        $message = 'Settings saved successfully!';
        
        // Log settings change
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (0, 'settings_updated', 'system', 0, :details, :ip, datetime('now'))
        ");
        $stmt->bindValue(':details', json_encode(['admin' => $_SESSION['admin_email']]), SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->execute();
        
    } catch (Exception $e) {
        $error = 'Error saving settings: ' . $e->getMessage();
        logError('Settings save error: ' . $e->getMessage());
    }
}

// Load current settings
try {
    $adminDb = Database::getInstance('admin');
    $result = $adminDb->query("SELECT setting_key, setting_value FROM system_settings");
    
    $settings = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
    $error = 'Error loading settings';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TrueVault Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 250px; background: rgba(255,255,255,0.03); border-right: 1px solid rgba(255,255,255,0.1); padding: 20px; }
        .logo { color: #00d9ff; font-size: 20px; font-weight: bold; margin-bottom: 30px; }
        .nav-item { display: block; padding: 12px 15px; color: #888; text-decoration: none; border-radius: 8px; margin-bottom: 5px; transition: all 0.2s; }
        .nav-item:hover, .nav-item.active { background: rgba(0,217,255,0.1); color: #00d9ff; }
        .nav-item.logout { color: #ff6464; margin-top: 20px; }
        .main { margin-left: 250px; padding: 30px; max-width: 900px; }
        h1 { font-size: 28px; margin-bottom: 30px; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 25px; margin-bottom: 20px; }
        .card h2 { font-size: 18px; margin-bottom: 20px; color: #00d9ff; }
        .form-group { margin-bottom: 20px; }
        label { display: block; color: #888; margin-bottom: 8px; font-weight: 500; }
        input, select { width: 100%; padding: 12px; border: 2px solid rgba(255,255,255,0.1); border-radius: 8px; background: rgba(0,0,0,0.3); color: #fff; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #00d9ff; }
        select option { background: #1a1a2e; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { padding: 14px 30px; background: linear-gradient(90deg, #00d9ff, #00ff88); border: none; border-radius: 8px; color: #0f0f1a; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,217,255,0.3); }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .message.success { background: rgba(0,255,136,0.15); border: 1px solid #00ff88; color: #00ff88; }
        .message.error { background: rgba(255,100,100,0.15); border: 1px solid #ff6464; color: #ff6464; }
        .help { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">🛡️ TrueVault Admin</div>
        <a href="/admin/dashboard.php" class="nav-item">📊 Dashboard</a>
        <a href="/admin/users.php" class="nav-item">👥 Users</a>
        <a href="/admin/servers.php" class="nav-item">🖥️ Servers</a>
        <a href="/admin/billing.php" class="nav-item">💳 Billing</a>
        <a href="/admin/settings.php" class="nav-item active">⚙️ Settings</a>
        <a href="/admin/logout.php" class="nav-item logout">🚪 Logout</a>
    </div>
    
    <div class="main">
        <h1>⚙️ System Settings</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="card">
                <h2>💳 PayPal Configuration</h2>
                <div class="row">
                    <div class="form-group">
                        <label>Client ID</label>
                        <input type="text" name="paypal_client_id" value="<?php echo htmlspecialchars($settings['paypal_client_id'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Secret Key</label>
                        <input type="password" name="paypal_secret" value="<?php echo htmlspecialchars($settings['paypal_secret'] ?? ''); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>Mode</label>
                        <select name="paypal_mode">
                            <option value="sandbox" <?php echo ($settings['paypal_mode'] ?? '') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                            <option value="live" <?php echo ($settings['paypal_mode'] ?? '') === 'live' ? 'selected' : ''; ?>>Live (Production)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Webhook ID</label>
                        <input type="text" name="paypal_webhook_id" value="<?php echo htmlspecialchars($settings['paypal_webhook_id'] ?? ''); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>Standard Plan ID</label>
                        <input type="text" name="paypal_plan_standard" value="<?php echo htmlspecialchars($settings['paypal_plan_standard'] ?? ''); ?>">
                        <div class="help">PayPal subscription plan ID for Standard tier ($9.97/mo)</div>
                    </div>
                    <div class="form-group">
                        <label>Pro Plan ID</label>
                        <input type="text" name="paypal_plan_pro" value="<?php echo htmlspecialchars($settings['paypal_plan_pro'] ?? ''); ?>">
                        <div class="help">PayPal subscription plan ID for Pro tier ($14.97/mo)</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>📧 Email Configuration</h2>
                <div class="row">
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>From Email</label>
                        <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>From Name</label>
                        <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? 'TrueVault VPN'); ?>">
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>🏢 Business Information</h2>
                <div class="row">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'TrueVault VPN'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Support Email</label>
                        <input type="email" name="support_email" value="<?php echo htmlspecialchars($settings['support_email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Business Name</label>
                    <input type="text" name="business_name" value="<?php echo htmlspecialchars($settings['business_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Business Address</label>
                    <input type="text" name="business_address" value="<?php echo htmlspecialchars($settings['business_address'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn">💾 Save All Settings</button>
        </form>
    </div>
</body>
</html>
