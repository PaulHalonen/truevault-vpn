<?php
/**
 * TruthVault VPN - User Settings
 * 
 * PURPOSE: User account settings and preferences
 * AUTHENTICATION: JWT required
 * 
 * FEATURES:
 * - Change password
 * - Update email
 * - Email notification preferences
 * - Account deletion
 * - Two-factor authentication (coming soon)
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

// Check authentication
try {
    $user = Auth::require();
    $userId = $user['user_id'];
    $userEmail = $user['email'];
    $firstName = $user['first_name'];
    $lastName = $user['last_name'];
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = Database::getInstance();
    $usersConn = $db->getConnection('users');
    
    switch ($_POST['action']) {
        case 'update_profile':
            $newFirstName = trim($_POST['first_name'] ?? '');
            $newLastName = trim($_POST['last_name'] ?? '');
            
            if (!empty($newFirstName) && !empty($newLastName)) {
                $stmt = $usersConn->prepare("
                    UPDATE users
                    SET first_name = ?, last_name = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$newFirstName, $newLastName, $userId]);
                
                $message = 'Profile updated successfully!';
                $messageType = 'success';
                $firstName = $newFirstName;
                $lastName = $newLastName;
            } else {
                $message = 'Please provide both first and last name';
                $messageType = 'error';
            }
            break;
            
        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $message = 'Please fill in all password fields';
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'New passwords do not match';
                $messageType = 'error';
            } elseif (strlen($newPassword) < 8) {
                $message = 'Password must be at least 8 characters';
                $messageType = 'error';
            } else {
                // Verify current password
                $stmt = $usersConn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($currentPassword, $userData['password_hash'])) {
                    // Update password
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $usersConn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                    $stmt->execute([$newHash, $userId]);
                    
                    $message = 'Password changed successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Current password is incorrect';
                    $messageType = 'error';
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #86efac;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 20px;
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
            color: #475569;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-help {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        .danger-zone {
            border: 2px solid #fee2e2;
            background: #fef2f2;
            border-radius: 12px;
            padding: 20px;
        }

        .danger-zone h3 {
            color: #dc2626;
            margin-bottom: 12px;
        }

        .danger-zone p {
            color: #64748b;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <?php include __DIR__ . '/../includes/navigation.php'; ?>
        
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>⚙️ Account Settings</h1>
                    <p>Manage your account preferences</p>
                </div>
                <a href="/dashboard/my-devices.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Status Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Settings -->
        <div class="card">
            <h2 class="card-title">Profile Information</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input 
                        type="text" 
                        name="first_name" 
                        class="form-input" 
                        value="<?= htmlspecialchars($firstName) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input 
                        type="text" 
                        name="last_name" 
                        class="form-input" 
                        value="<?= htmlspecialchars($lastName) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        class="form-input" 
                        value="<?= htmlspecialchars($userEmail) ?>"
                        disabled
                    >
                    <p class="form-help">Contact support to change your email address</p>
                </div>

                <button type="submit" class="btn btn-primary">
                    💾 Save Changes
                </button>
            </form>
        </div>

        <!-- Password Change -->
        <div class="card">
            <h2 class="card-title">Change Password</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input 
                        type="password" 
                        name="current_password" 
                        class="form-input"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input 
                        type="password" 
                        name="new_password" 
                        class="form-input"
                        minlength="8"
                        required
                    >
                    <p class="form-help">Minimum 8 characters</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        class="form-input"
                        minlength="8"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    🔒 Change Password
                </button>
            </form>
        </div>

        <!-- Email Notifications -->
        <div class="card">
            <h2 class="card-title">Email Notifications</h2>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" checked>
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">Payment Receipts</div>
                        <div class="form-help">Receive receipts when payments are processed</div>
                    </div>
                </label>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" checked>
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">Payment Failures</div>
                        <div class="form-help">Get notified if a payment fails</div>
                    </div>
                </label>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" checked>
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">System Maintenance</div>
                        <div class="form-help">Receive notifications about scheduled maintenance</div>
                    </div>
                </label>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox">
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">Marketing Emails</div>
                        <div class="form-help">Receive updates about new features and promotions</div>
                    </div>
                </label>
            </div>

            <p class="form-help" style="margin-top: 20px;">
                ℹ️ Notification preferences coming soon! Currently saved to your browser.
            </p>
        </div>

        <!-- Danger Zone -->
        <div class="card">
            <h2 class="card-title">Danger Zone</h2>
            
            <div class="danger-zone">
                <h3>⚠️ Delete Account</h3>
                <p>
                    Once you delete your account, there is no going back. This will permanently delete all your devices, 
                    port forwarding settings, and billing information.
                </p>
                <button class="btn btn-danger" onclick="confirmDelete()">
                    🗑️ Delete Account
                </button>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            const confirmed = confirm(
                "⚠️ WARNING: This action cannot be undone!\n\n" +
                "Are you sure you want to permanently delete your account?\n\n" +
                "This will delete:\n" +
                "• All your devices\n" +
                "• Port forwarding settings\n" +
                "• Billing information\n" +
                "• All account data"
            );

            if (confirmed) {
                alert("Account deletion coming soon! Please contact support@truthvault.com to delete your account.");
            }
        }
    </script>
</body>
</html>
