<?php
/**
 * Device Setup Page - 1-CLICK Server-Side Key Generation
 * 
 * PURPOSE: Simple device setup interface
 * FLOW: Enter name → Click → Download config (SERVER generates keys)
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Device - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            min-height: 100vh;
            padding: 20px;
            color: #fff;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        h1 { font-size: 24px; margin-bottom: 10px; color: #00d9ff; }
        .subtitle { color: #888; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #ccc; }
        input, select {
            width: 100%;
            padding: 14px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            font-size: 16px;
            background: rgba(0,0,0,0.3);
            color: #fff;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #00d9ff;
        }
        select option { background: #1a1a2e; }
        .help-text { font-size: 13px; color: #666; margin-top: 5px; }
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
        }
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,217,255,0.3);
        }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .status {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }
        .status.success { background: rgba(0,255,136,0.15); border: 1px solid #00ff88; color: #00ff88; }
        .status.error { background: rgba(255,100,100,0.15); border: 1px solid #ff6464; color: #ff6464; }
        .status.info { background: rgba(0,217,255,0.15); border: 1px solid #00d9ff; color: #00d9ff; }
        .spinner {
            border: 3px solid rgba(255,255,255,0.1);
            border-top: 3px solid #00d9ff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .result-box {
            background: rgba(0,0,0,0.3);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        .result-box h3 { color: #00ff88; margin-bottom: 15px; }
        .device-info { margin-bottom: 15px; }
        .device-info span { color: #888; }
        .device-info strong { color: #fff; }
        #qrcode { text-align: center; margin: 20px 0; padding: 20px; background: #fff; border-radius: 10px; display: inline-block; }
        .qr-container { text-align: center; margin-top: 20px; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #00d9ff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Setup New Device</h1>
        <p class="subtitle">1-Click Setup - We handle everything!</p>
        
        <div id="status" class="status"></div>
        <div id="spinner" class="spinner"></div>
        
        <div id="setupForm">
            <div class="form-group">
                <label for="deviceName">Device Name</label>
                <input type="text" id="deviceName" placeholder="e.g., My iPhone, Work Laptop" maxlength="50">
                <div class="help-text">Give your device a friendly name</div>
            </div>
            
            <div class="form-group">
                <label for="deviceType">Device Type</label>
                <select id="deviceType">
                    <option value="mobile">📱 Mobile Phone</option>
                    <option value="desktop">💻 Desktop/Laptop</option>
                    <option value="tablet">📲 Tablet</option>
                    <option value="router">🌐 Router</option>
                    <option value="other">❓ Other</option>
                </select>
            </div>
            
            <button class="btn btn-primary" id="setupBtn" onclick="setupDevice()">
                🚀 Setup Device & Download Config
            </button>
        </div>
        
        <div id="resultBox" class="result-box">
            <h3>✅ Device Ready!</h3>
            <div class="device-info">
                <div><span>Name:</span> <strong id="resultName"></strong></div>
                <div><span>IP Address:</span> <strong id="resultIP"></strong></div>
                <div><span>Server:</span> <strong id="resultServer"></strong></div>
            </div>
            
            <button class="btn btn-primary" onclick="downloadConfig()">
                📥 Download Config File
            </button>
            
            <div class="qr-container" id="qrContainer">
                <p style="color:#888; margin-bottom:10px;">Or scan QR code with WireGuard app:</p>
                <div id="qrcode"></div>
            </div>
            
            <button class="btn btn-primary" onclick="location.reload()" style="background: rgba(255,255,255,0.1); color: #fff; margin-top: 15px;">
                ➕ Setup Another Device
            </button>
        </div>
        
        <div class="back-link">
            <a href="/dashboard/">← Back to Dashboard</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        let deviceConfig = null;
        let deviceName = '';
        
        function showStatus(type, message) {
            const status = document.getElementById('status');
            status.className = 'status ' + type;
            status.textContent = message;
            status.style.display = 'block';
        }
        
        function hideStatus() {
            document.getElementById('status').style.display = 'none';
        }
        
        function showSpinner(show) {
            document.getElementById('spinner').style.display = show ? 'block' : 'none';
        }
        
        async function setupDevice() {
            deviceName = document.getElementById('deviceName').value.trim();
            const deviceType = document.getElementById('deviceType').value;
            
            if (!deviceName) {
                showStatus('error', 'Please enter a device name');
                return;
            }
            
            const token = localStorage.getItem('truevault_token');
            if (!token) {
                showStatus('error', 'Not logged in. Please login first.');
                setTimeout(() => window.location.href = '/login.php', 2000);
                return;
            }
            
            document.getElementById('setupBtn').disabled = true;
            showSpinner(true);
            showStatus('info', 'Creating your device... This takes about 5 seconds.');
            
            try {
                const response = await fetch('/api/devices/add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        device_name: deviceName,
                        device_type: deviceType
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to create device');
                }
                
                deviceConfig = data.config;
                
                document.getElementById('resultName').textContent = data.device.name;
                document.getElementById('resultIP').textContent = data.device.ip_address;
                document.getElementById('resultServer').textContent = data.device.server + ' (' + data.device.server_location + ')';
                
                document.getElementById('setupForm').style.display = 'none';
                document.getElementById('resultBox').style.display = 'block';
                hideStatus();
                
                // Generate QR code for mobile devices
                if (deviceType === 'mobile' || deviceType === 'tablet') {
                    document.getElementById('qrContainer').style.display = 'block';
                    new QRCode(document.getElementById('qrcode'), {
                        text: deviceConfig,
                        width: 200,
                        height: 200,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } else {
                    document.getElementById('qrContainer').style.display = 'none';
                }
                
            } catch (error) {
                showStatus('error', error.message);
                document.getElementById('setupBtn').disabled = false;
            }
            
            showSpinner(false);
        }
        
        function downloadConfig() {
            if (!deviceConfig) {
                showStatus('error', 'No config available');
                return;
            }
            
            const filename = deviceName.replace(/[^a-z0-9]/gi, '_') + '.conf';
            const blob = new Blob([deviceConfig], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showStatus('success', 'Config downloaded! Import it into your WireGuard app.');
        }
    </script>
</body>
</html>
