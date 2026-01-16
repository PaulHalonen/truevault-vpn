<?php
/**
 * TrueVault VPN - API Test Page
 * 
 * Tests authentication endpoints and VIP detection
 * DELETE THIS FILE AFTER TESTING!
 * 
 * @created January 2026
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueVault VPN - API Test</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 {
            text-align: center;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle { text-align: center; color: #888; margin-bottom: 30px; }
        .card {
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .card h2 { font-size: 1.1rem; margin-bottom: 15px; color: #00d9ff; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #888; font-size: 0.9rem; }
        input, select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-size: 1rem;
        }
        input:focus { outline: none; border-color: #00d9ff; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-danger { background: rgba(255,80,80,0.3); color: #ff5050; }
        .response {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
        }
        .response.success { background: rgba(0,255,136,0.1); border: 1px solid rgba(0,255,136,0.3); }
        .response.error { background: rgba(255,100,100,0.1); border: 1px solid rgba(255,100,100,0.3); }
        .token-display {
            background: rgba(0,217,255,0.1);
            padding: 10px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.8rem;
            word-break: break-all;
            margin-top: 10px;
        }
        .warning {
            background: rgba(255,170,0,0.1);
            border: 1px solid rgba(255,170,0,0.3);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #ffaa00;
        }
        .vip-badge {
            display: inline-block;
            padding: 3px 10px;
            background: linear-gradient(90deg, #ffd700, #ff8c00);
            color: #000;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 10px;
        }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 TrueVault VPN</h1>
        <p class="subtitle">API Test Suite</p>
        
        <div class="warning">
            ⚠️ <strong>DELETE THIS FILE AFTER TESTING!</strong><br>
            This page exposes API endpoints for testing. Remove it before launch.
        </div>
        
        <!-- Current Token Display -->
        <div class="card">
            <h2>🎫 Current Token</h2>
            <div id="token-status">No token stored</div>
            <div id="token-display" class="token-display" style="display:none;"></div>
            <button class="btn btn-danger" onclick="clearToken()" style="margin-top:10px;">Clear Token</button>
        </div>
        
        <div class="grid">
            <!-- Register -->
            <div class="card">
                <h2>📝 Register</h2>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="reg-email" placeholder="test@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="reg-password" placeholder="minimum 8 characters">
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="reg-firstname" placeholder="John">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="reg-lastname" placeholder="Doe">
                </div>
                <button class="btn btn-primary" onclick="register()">Register</button>
                <button class="btn btn-secondary" onclick="fillVIP()">Fill VIP Email</button>
                <div id="reg-response" class="response" style="display:none;"></div>
            </div>
            
            <!-- Login -->
            <div class="card">
                <h2>🔑 Login</h2>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="login-email" placeholder="test@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="login-password" placeholder="your password">
                </div>
                <button class="btn btn-primary" onclick="login()">Login</button>
                <div id="login-response" class="response" style="display:none;"></div>
            </div>
            
            <!-- Get Profile -->
            <div class="card">
                <h2>👤 Get Profile (Me)</h2>
                <p style="color:#888;margin-bottom:15px;">Requires valid token</p>
                <button class="btn btn-primary" onclick="getProfile()">Get Profile</button>
                <div id="profile-response" class="response" style="display:none;"></div>
            </div>
            
            <!-- Verify Token -->
            <div class="card">
                <h2>✅ Verify Token</h2>
                <button class="btn btn-primary" onclick="verifyToken()">Verify</button>
                <button class="btn btn-secondary" onclick="logout()">Logout</button>
                <div id="verify-response" class="response" style="display:none;"></div>
            </div>
            
            <!-- Password Reset -->
            <div class="card">
                <h2>🔄 Password Reset</h2>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="reset-email" placeholder="test@example.com">
                </div>
                <button class="btn btn-primary" onclick="forgotPassword()">Request Reset</button>
                <div id="reset-response" class="response" style="display:none;"></div>
            </div>
            
            <!-- Change Password -->
            <div class="card">
                <h2>🔐 Change Password</h2>
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" id="current-password" placeholder="current">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" id="new-password" placeholder="new (min 8 chars)">
                </div>
                <button class="btn btn-primary" onclick="changePassword()">Change</button>
                <div id="change-response" class="response" style="display:none;"></div>
            </div>
        </div>
        
        <!-- VIP Test Info -->
        <div class="card">
            <h2>⭐ VIP Test Info</h2>
            <p style="margin-bottom:10px;">These emails are in the VIP list and will get instant access:</p>
            <ul style="margin-left:20px; color:#888;">
                <li><strong>paulhalonen@gmail.com</strong> (Owner) → 10 devices</li>
                <li><strong>seige235@yahoo.com</strong> (Dedicated Server 2) → 999 devices</li>
            </ul>
            <p style="margin-top:15px; color:#00d9ff;">
                <strong>VIP Device Limits:</strong>
            </p>
            <ul style="margin-left:20px; color:#888; margin-top:5px;">
                <li>Regular VIP = <span style="color:#00ff88">10 devices</span></li>
                <li>VIP with dedicated server = <span style="color:#00ff88">999 devices</span></li>
                <li>Standard user = <span style="color:#888">3 devices</span></li>
            </ul>
            <p style="margin-top:15px; color:#00ff88;">
                ✨ All VIP users get: account_type='vip', plan='vip', status='active'
            </p>
        </div>
    </div>
    
    <script>
        let currentToken = localStorage.getItem('truevault_token') || null;
        
        // Update token display on load
        updateTokenDisplay();
        
        function updateTokenDisplay() {
            const status = document.getElementById('token-status');
            const display = document.getElementById('token-display');
            
            if (currentToken) {
                status.innerHTML = '<span style="color:#00ff88">✓ Token stored</span>';
                display.style.display = 'block';
                display.textContent = currentToken;
            } else {
                status.innerHTML = '<span style="color:#888">No token stored</span>';
                display.style.display = 'none';
            }
        }
        
        function setToken(token) {
            currentToken = token;
            localStorage.setItem('truevault_token', token);
            updateTokenDisplay();
        }
        
        function clearToken() {
            currentToken = null;
            localStorage.removeItem('truevault_token');
            updateTokenDisplay();
        }
        
        function showResponse(id, data, isError = false) {
            const el = document.getElementById(id);
            el.style.display = 'block';
            el.className = 'response ' + (isError ? 'error' : 'success');
            el.textContent = JSON.stringify(data, null, 2);
        }
        
        function fillVIP() {
            document.getElementById('reg-email').value = 'seige235@yahoo.com';
            document.getElementById('reg-password').value = 'TestPassword123';
            document.getElementById('reg-firstname').value = 'VIP';
            document.getElementById('reg-lastname').value = 'User';
        }
        
        async function apiCall(action, method, body = null) {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };
            
            if (currentToken) {
                options.headers['Authorization'] = 'Bearer ' + currentToken;
            }
            
            if (body) {
                options.body = JSON.stringify(body);
            }
            
            const response = await fetch('/api/auth.php?action=' + action, options);
            return await response.json();
        }
        
        async function register() {
            const data = await apiCall('register', 'POST', {
                email: document.getElementById('reg-email').value,
                password: document.getElementById('reg-password').value,
                first_name: document.getElementById('reg-firstname').value,
                last_name: document.getElementById('reg-lastname').value
            });
            
            showResponse('reg-response', data, !data.success);
            
            if (data.token) {
                setToken(data.token);
            }
        }
        
        async function login() {
            const data = await apiCall('login', 'POST', {
                email: document.getElementById('login-email').value,
                password: document.getElementById('login-password').value
            });
            
            showResponse('login-response', data, !data.success);
            
            if (data.token) {
                setToken(data.token);
            }
        }
        
        async function getProfile() {
            const data = await apiCall('me', 'GET');
            showResponse('profile-response', data, !data.success);
        }
        
        async function verifyToken() {
            const data = await apiCall('verify', 'GET');
            showResponse('verify-response', data, !data.success);
        }
        
        async function logout() {
            const data = await apiCall('logout', 'POST');
            showResponse('verify-response', data, !data.success);
            if (data.success) {
                clearToken();
            }
        }
        
        async function forgotPassword() {
            const data = await apiCall('forgot', 'POST', {
                email: document.getElementById('reset-email').value
            });
            showResponse('reset-response', data, !data.success);
        }
        
        async function changePassword() {
            const data = await apiCall('change-password', 'POST', {
                current_password: document.getElementById('current-password').value,
                new_password: document.getElementById('new-password').value
            });
            showResponse('change-response', data, !data.success);
        }
    </script>
</body>
</html>
