<?php
require_once 'config.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { max-width: 400px; width: 90%; }
        .login-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 3rem 2rem; }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo h1 { font-size: 2rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
        .logo p { color: #888; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: #00d9ff; }
        .btn { width: 100%; padding: 1rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,217,255,0.4); }
        .error-message { background: rgba(255,100,100,0.2); border: 1px solid #ff6464; color: #ff6464; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; }
        .back-link { text-align: center; margin-top: 1.5rem; }
        .back-link a { color: #00d9ff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="logo">
            <h1>🔒 TrueVault VPN</h1>
            <p>Admin Panel Login</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <?php
                switch ($error) {
                    case 'invalid':
                        echo 'Invalid email or password';
                        break;
                    case 'inactive':
                        echo 'Account is inactive';
                        break;
                    case 'session':
                        echo 'Session expired. Please login again.';
                        break;
                    default:
                        echo 'An error occurred';
                }
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="auth.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="back-link">
            <a href="/">← Back to Website</a>
        </div>
    </div>
</div>
</body>
</html>
