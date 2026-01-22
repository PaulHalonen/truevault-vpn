<?php
/**
 * User Login Page
 * @created January 2026
 */
define('TRUEVAULT_INIT', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #fff;
        }
        .container {
            width: 100%;
            max-width: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { font-size: 28px; color: #00d9ff; margin-bottom: 5px; }
        .logo p { color: #888; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #ccc; font-weight: 500; }
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
            padding: 14px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            border: none;
            border-radius: 10px;
            color: #0f0f1a;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .status { padding: 12px; border-radius: 8px; margin-bottom: 20px; display: none; }
        .status.error { background: rgba(255,100,100,0.15); border: 1px solid #ff6464; color: #ff6464; }
        .status.success { background: rgba(0,255,136,0.15); border: 1px solid #00ff88; color: #00ff88; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #00d9ff; text-decoration: none; margin: 0 10px; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🛡️ TrueVault VPN</h1>
            <p>Secure, Private, Yours</p>
        </div>
        
        <div id="status" class="status"></div>
        
        <form id="loginForm" onsubmit="login(event)">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" required placeholder="you@example.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" required placeholder="Your password">
            </div>
            
            <button type="submit" class="btn" id="loginBtn">Login</button>
        </form>
        
        <div class="links">
            <a href="/register.php">Create Account</a>
            <a href="/forgot-password.php">Forgot Password?</a>
        </div>
    </div>
    
    <script>
        // Check if already logged in
        if (localStorage.getItem('truevault_token')) {
            window.location.href = '/dashboard/';
        }
        
        async function login(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const btn = document.getElementById('loginBtn');
            const status = document.getElementById('status');
            
            btn.disabled = true;
            btn.textContent = 'Logging in...';
            status.style.display = 'none';
            
            try {
                const response = await fetch('/api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    localStorage.setItem('truevault_token', data.token);
                    status.className = 'status success';
                    status.textContent = 'Login successful! Redirecting...';
                    status.style.display = 'block';
                    setTimeout(() => window.location.href = '/dashboard/', 1000);
                } else {
                    status.className = 'status error';
                    status.textContent = data.error || 'Login failed';
                    status.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Login';
                }
            } catch (error) {
                status.className = 'status error';
                status.textContent = 'Connection error. Please try again.';
                status.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Login';
            }
        }
    </script>
</body>
</html>
