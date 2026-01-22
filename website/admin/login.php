<?php
/**
 * Admin Login Page - SQLITE3 VERSION
 * 
 * PURPOSE: Secure admin authentication
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

session_start();

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        try {
            $adminDb = Database::getInstance('admin');
            
            $stmt = $adminDb->prepare("SELECT id, email, password_hash, role, status FROM admin_users WHERE email = :email");
            $stmt->bindValue(':email', strtolower($email), SQLITE3_TEXT);
            $result = $stmt->execute();
            $admin = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                if ($admin['status'] !== 'active') {
                    $error = 'Account is disabled';
                } else {
                    // Update last login
                    $stmt = $adminDb->prepare("UPDATE admin_users SET last_login = datetime('now') WHERE id = :id");
                    $stmt->bindValue(':id', $admin['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Set session
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    
                    // Log login
                    $logsDb = Database::getInstance('logs');
                    $stmt = $logsDb->prepare("
                        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
                        VALUES (:user_id, 'admin_login', 'admin', :admin_id, :details, :ip, datetime('now'))
                    ");
                    $stmt->bindValue(':user_id', 0, SQLITE3_INTEGER);
                    $stmt->bindValue(':admin_id', $admin['id'], SQLITE3_INTEGER);
                    $stmt->bindValue(':details', json_encode(['email' => $admin['email']]), SQLITE3_TEXT);
                    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
                    $stmt->execute();
                    
                    header('Location: /admin/dashboard.php');
                    exit;
                }
            } else {
                $error = 'Invalid email or password';
                
                // Log failed attempt
                $logsDb = Database::getInstance('logs');
                $stmt = $logsDb->prepare("
                    INSERT INTO security_events (event_type, severity, ip_address, event_data, created_at)
                    VALUES ('admin_login_failed', 'warning', :ip, :data, datetime('now'))
                ");
                $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
                $stmt->bindValue(':data', json_encode(['email' => $email]), SQLITE3_TEXT);
                $stmt->execute();
            }
        } catch (Exception $e) {
            $error = 'Login error. Please try again.';
            logError('Admin login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        h1 { color: #00d9ff; font-size: 24px; margin-bottom: 10px; text-align: center; }
        .subtitle { color: #888; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; color: #ccc; margin-bottom: 8px; font-weight: 500; }
        input {
            width: 100%;
            padding: 14px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            background: rgba(0,0,0,0.3);
            color: #fff;
            font-size: 16px;
        }
        input:focus { outline: none; border-color: #00d9ff; }
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            border: none;
            border-radius: 10px;
            color: #0f0f1a;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,217,255,0.3); }
        .error {
            background: rgba(255,100,100,0.15);
            border: 1px solid #ff6464;
            color: #ff6464;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #00d9ff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 Admin Login</h1>
        <p class="subtitle">TrueVault VPN Administration</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="back-link">
            <a href="/">← Back to Main Site</a>
        </div>
    </div>
</body>
</html>
