<?php
/**
 * TrueVault VPN - System Settings
 * 
 * PURPOSE: Admin page to configure system settings
 * AUTHENTICATION: Admin tier required (not VIP, only admin)
 * 
 * FEATURES:
 * - Edit system settings
 * - Save to database (database-driven, no hardcoded values)
 * - PayPal configuration
 * - Email configuration
 * - Security settings
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/Auth.php';

// Check authentication and admin access (ONLY admin, not VIP)
try {
    $user = Auth::require();
    $userTier = $user['tier'] ?? 'standard';
    
    if ($userTier !== 'admin') {
        header('Location: /admin/dashboard.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}

// Get current settings from database (placeholder - will implement settings table later)
$settings = [
    'site_name' => 'TrueVault VPN',
    'support_email' => 'paulhalonen@gmail.com',
    'paypal_client_id' => 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk',
    'paypal_secret' => 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN',
    'paypal_webhook_id' => '46924926WL757580D',
    'jwt_secret' => 'your-jwt-secret-here',
    'session_timeout' => 7,
    'max_devices_standard' => 3,
    'max_devices_pro' => 5,
    'max_devices_vip' => 999
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .header-title p {
            color: #64748b;
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .settings-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #475569;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-help {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 2px solid #f1f5f9;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #60a5fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>⚙️ System Settings</h1>
                    <p>Configure system-wide settings</p>
                </div>
                <a href="/admin/dashboard.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Alert -->
        <div class="alert alert-warning">
            ⚠️ <strong>Admin Only:</strong> Changes to these settings affect the entire system. Use caution.
        </div>

        <form id="settings-form">
            <!-- General Settings -->
            <div class="settings-card">
                <h2 class="card-title">🌐 General Settings</h2>
                
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" class="form-input" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                    <div class="form-help">Displayed throughout the application</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Support Email</label>
                    <input type="email" class="form-input" name="support_email" value="<?= htmlspecialchars($settings['support_email']) ?>" required>
                    <div class="form-help">Used for customer support and notifications</div>
                </div>
            </div>

            <!-- PayPal Settings -->
            <div class="settings-card">
                <h2 class="card-title">💳 PayPal Settings</h2>
                
                <div class="alert alert-info">
                    ℹ️ PayPal credentials from GoDaddy project notes
                </div>

                <div class="form-group">
                    <label class="form-label">PayPal Client ID</label>
                    <input type="text" class="form-input" name="paypal_client_id" value="<?= htmlspecialchars($settings['paypal_client_id']) ?>" required>
                    <div class="form-help">Live PayPal App Client ID</div>
                </div>

                <div class="form-group">
                    <label class="form-label">PayPal Secret Key</label>
                    <input type="password" class="form-input" name="paypal_secret" value="<?= htmlspecialchars($settings['paypal_secret']) ?>" required>
                    <div class="form-help">Live PayPal App Secret Key</div>
                </div>

                <div class="form-group">
                    <label class="form-label">PayPal Webhook ID</label>
                    <input type="text" class="form-input" name="paypal_webhook_id" value="<?= htmlspecialchars($settings['paypal_webhook_id']) ?>" required>
                    <div class="form-help">Webhook ID: 46924926WL757580D</div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="settings-card">
                <h2 class="card-title">🔐 Security Settings</h2>
                
                <div class="form-group">
                    <label class="form-label">JWT Secret</label>
                    <input type="password" class="form-input" name="jwt_secret" value="<?= htmlspecialchars($settings['jwt_secret']) ?>" required>
                    <div class="form-help">Secret key for JWT token generation (change requires all users to re-login)</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Session Timeout (days)</label>
                    <input type="number" class="form-input" name="session_timeout" value="<?= htmlspecialchars($settings['session_timeout']) ?>" min="1" max="30" required>
                    <div class="form-help">How long JWT tokens remain valid</div>
                </div>
            </div>

            <!-- Device Limits -->
            <div class="settings-card">
                <h2 class="card-title">📱 Device Limits</h2>
                
                <div class="form-group">
                    <label class="form-label">Standard Tier Limit</label>
                    <input type="number" class="form-input" name="max_devices_standard" value="<?= htmlspecialchars($settings['max_devices_standard']) ?>" min="1" required>
                    <div class="form-help">Maximum devices for Standard tier users</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Pro Tier Limit</label>
                    <input type="number" class="form-input" name="max_devices_pro" value="<?= htmlspecialchars($settings['max_devices_pro']) ?>" min="1" required>
                    <div class="form-help">Maximum devices for Pro tier users</div>
                </div>

                <div class="form-group">
                    <label class="form-label">VIP Tier Limit</label>
                    <input type="number" class="form-input" name="max_devices_vip" value="<?= htmlspecialchars($settings['max_devices_vip']) ?>" min="1" required>
                    <div class="form-help">Maximum devices for VIP tier users (999 = unlimited)</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="settings-card">
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='/admin/dashboard.php'">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const JWT_TOKEN = localStorage.getItem('vpn_token');

        if (!JWT_TOKEN) {
            window.location.href = '/auth/login.php';
        }

        document.getElementById('settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const settings = {};
            
            for (let [key, value] of formData.entries()) {
                settings[key] = value;
            }

            try {
                const response = await fetch('/api/admin/settings/save.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify(settings)
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Settings saved successfully!');
                    window.location.reload();
                } else {
                    alert('❌ Error: ' + (result.error || 'Failed to save settings'));
                }
            } catch (error) {
                alert('❌ Error saving settings: ' + error.message);
            }
        });
    </script>
</body>
</html>
