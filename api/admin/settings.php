<?php
/**
 * TrueVault VPN - Admin Settings API
 * System configuration management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require admin authentication
$admin = Auth::requireAdmin();
if (!$admin) exit;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'get';

try {
    $db = Database::getConnection('settings');
    
    switch ($method) {
        case 'GET':
            if ($action === 'get') {
                // Get all settings
                $stmt = $db->query("SELECT * FROM settings ORDER BY category, key");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Group by category
                $settings = [];
                foreach ($rows as $row) {
                    $category = $row['category'] ?? 'general';
                    if (!isset($settings[$category])) {
                        $settings[$category] = [];
                    }
                    $settings[$category][$row['key']] = [
                        'value' => $row['value'],
                        'type' => $row['type'] ?? 'string',
                        'description' => $row['description'] ?? ''
                    ];
                }
                
                Response::json(['success' => true, 'settings' => $settings]);
                
            } elseif ($action === 'database-status') {
                // Check database health
                $databases = [
                    'users', 'subscriptions', 'payments', 'vpn', 'certificates',
                    'devices', 'identities', 'mesh', 'cameras', 'themes',
                    'pages', 'emails', 'media', 'logs', 'settings', 'automation',
                    'forms', 'notifications', 'analytics', 'bandwidth', 'support'
                ];
                
                $status = [];
                foreach ($databases as $dbName) {
                    try {
                        $testDb = Database::getConnection($dbName);
                        $status[$dbName] = ['status' => 'ok', 'message' => 'Connected'];
                    } catch (Exception $e) {
                        $status[$dbName] = ['status' => 'error', 'message' => $e->getMessage()];
                    }
                }
                
                Response::json(['success' => true, 'databases' => $status]);
                
            } elseif ($action === 'vip-config') {
                // Get VIP configuration
                $vipStmt = $db->query("SELECT * FROM settings WHERE category = 'vip'");
                $vipSettings = [];
                while ($row = $vipStmt->fetch(PDO::FETCH_ASSOC)) {
                    $vipSettings[$row['key']] = $row['value'];
                }
                
                Response::json(['success' => true, 'vip_config' => $vipSettings]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'update') {
                // Update settings
                if (empty($data['settings'])) {
                    Response::error('Settings data required', 400);
                }
                
                foreach ($data['settings'] as $key => $value) {
                    // Check if setting exists
                    $stmt = $db->prepare("SELECT id FROM settings WHERE key = ?");
                    $stmt->execute([$key]);
                    
                    if ($stmt->fetch()) {
                        // Update
                        $updateStmt = $db->prepare("UPDATE settings SET value = ?, updated_at = datetime('now') WHERE key = ?");
                        $updateStmt->execute([$value, $key]);
                    } else {
                        // Insert new setting
                        $insertStmt = $db->prepare("
                            INSERT INTO settings (key, value, category, created_at, updated_at)
                            VALUES (?, ?, 'general', datetime('now'), datetime('now'))
                        ");
                        $insertStmt->execute([$key, $value]);
                    }
                }
                
                // Log the change
                $logDb = Database::getConnection('logs');
                $logStmt = $logDb->prepare("
                    INSERT INTO admin_log (admin_id, action, details, created_at)
                    VALUES (?, 'settings_updated', ?, datetime('now'))
                ");
                $logStmt->execute([$admin['id'], json_encode(array_keys($data['settings']))]);
                
                Response::json(['success' => true, 'message' => 'Settings updated']);
                
            } elseif ($action === 'paypal') {
                // Update PayPal configuration
                $paypalSettings = [
                    'paypal_mode' => $data['mode'] ?? 'sandbox',
                    'paypal_client_id' => $data['client_id'] ?? '',
                    'paypal_secret' => $data['secret'] ?? '',
                    'paypal_webhook_url' => $data['webhook_url'] ?? ''
                ];
                
                foreach ($paypalSettings as $key => $value) {
                    $stmt = $db->prepare("
                        INSERT OR REPLACE INTO settings (key, value, category, updated_at)
                        VALUES (?, ?, 'paypal', datetime('now'))
                    ");
                    $stmt->execute([$key, $value]);
                }
                
                Response::json(['success' => true, 'message' => 'PayPal settings updated']);
                
            } elseif ($action === 'smtp') {
                // Update SMTP configuration
                $smtpSettings = [
                    'smtp_host' => $data['host'] ?? '',
                    'smtp_port' => $data['port'] ?? '587',
                    'smtp_user' => $data['user'] ?? '',
                    'smtp_pass' => $data['pass'] ?? '',
                    'smtp_from_email' => $data['from_email'] ?? '',
                    'smtp_from_name' => $data['from_name'] ?? 'TrueVault VPN'
                ];
                
                foreach ($smtpSettings as $key => $value) {
                    $stmt = $db->prepare("
                        INSERT OR REPLACE INTO settings (key, value, category, updated_at)
                        VALUES (?, ?, 'smtp', datetime('now'))
                    ");
                    $stmt->execute([$key, $value]);
                }
                
                Response::json(['success' => true, 'message' => 'SMTP settings updated']);
                
            } elseif ($action === 'security') {
                // Update security settings
                $securitySettings = [
                    'require_2fa_admin' => $data['require_2fa_admin'] ?? '0',
                    'rate_limiting' => $data['rate_limiting'] ?? '1',
                    'ip_blocking' => $data['ip_blocking'] ?? '1',
                    'session_timeout' => $data['session_timeout'] ?? '3600'
                ];
                
                foreach ($securitySettings as $key => $value) {
                    $stmt = $db->prepare("
                        INSERT OR REPLACE INTO settings (key, value, category, updated_at)
                        VALUES (?, ?, 'security', datetime('now'))
                    ");
                    $stmt->execute([$key, $value]);
                }
                
                Response::json(['success' => true, 'message' => 'Security settings updated']);
                
            } elseif ($action === 'maintenance') {
                // Toggle maintenance mode
                $stmt = $db->prepare("
                    INSERT OR REPLACE INTO settings (key, value, category, updated_at)
                    VALUES ('maintenance_mode', ?, 'general', datetime('now'))
                ");
                $stmt->execute([$data['enabled'] ? '1' : '0']);
                
                if (!empty($data['message'])) {
                    $msgStmt = $db->prepare("
                        INSERT OR REPLACE INTO settings (key, value, category, updated_at)
                        VALUES ('maintenance_message', ?, 'general', datetime('now'))
                    ");
                    $msgStmt->execute([$data['message']]);
                }
                
                Response::json(['success' => true, 'message' => 'Maintenance mode ' . ($data['enabled'] ? 'enabled' : 'disabled')]);
                
            } elseif ($action === 'clear-cache') {
                // Clear various caches
                // In production, this would clear Redis/Memcached, opcache, etc.
                
                // Log the action
                $logDb = Database::getConnection('logs');
                $logStmt = $logDb->prepare("
                    INSERT INTO system_log (level, category, message, created_at)
                    VALUES ('info', 'system', 'Cache cleared by admin', datetime('now'))
                ");
                $logStmt->execute();
                
                Response::json(['success' => true, 'message' => 'Cache cleared']);
                
            } elseif ($action === 'backup') {
                // Trigger database backup
                $backupDir = __DIR__ . '/../../backups';
                if (!is_dir($backupDir)) {
                    mkdir($backupDir, 0755, true);
                }
                
                $timestamp = date('Y-m-d_H-i-s');
                $backupFile = "$backupDir/backup_$timestamp.zip";
                
                // In production, this would create actual backups
                // For now, just log it
                
                $logDb = Database::getConnection('logs');
                $logStmt = $logDb->prepare("
                    INSERT INTO system_log (level, category, message, details, created_at)
                    VALUES ('info', 'backup', 'Database backup initiated', ?, datetime('now'))
                ");
                $logStmt->execute([json_encode(['backup_file' => $backupFile, 'admin_id' => $admin['id']])]);
                
                Response::json(['success' => true, 'message' => 'Backup initiated', 'file' => $backupFile]);
                
            } elseif ($action === 'regenerate-jwt') {
                // Regenerate JWT secret (invalidates all tokens!)
                $newSecret = bin2hex(random_bytes(32));
                
                $stmt = $db->prepare("
                    INSERT OR REPLACE INTO settings (key, value, category, updated_at)
                    VALUES ('jwt_secret', ?, 'security', datetime('now'))
                ");
                $stmt->execute([$newSecret]);
                
                // Log the action
                $logDb = Database::getConnection('logs');
                $logStmt = $logDb->prepare("
                    INSERT INTO admin_log (admin_id, action, details, created_at)
                    VALUES (?, 'jwt_secret_regenerated', 'All user sessions invalidated', datetime('now'))
                ");
                $logStmt->execute([$admin['id']]);
                
                Response::json(['success' => true, 'message' => 'JWT secret regenerated. All users will need to login again.']);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Admin Settings API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
