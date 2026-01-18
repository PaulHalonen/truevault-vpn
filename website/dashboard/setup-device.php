<?php
/**
 * Device Setup Page - SERVER-SIDE Key Generation
 * 
 * PURPOSE: 1-click device setup interface
 * ARCHITECTURE: Server generates WireGuard keys, not browser
 * 
 * WORKFLOW:
 * 1. User enters device name
 * 2. Clicks "Generate Config"
 * 3. Server generates keypair + complete config
 * 4. User downloads .conf file
 * 
 * @created January 18, 2026
 * @version 1.0.0 - SERVER-SIDE APPROACH
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../configs/config.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Get user info from session
$userEmail = $_SESSION['email'] ?? 'User';
$userName = $_SESSION['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Device - TrueVault VPN</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        input[type="text"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .help-text {
            font-size: 13px;
            color: #999;
            margin-top: 5px;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .status {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Server selection */
        .servers-section {
            margin-bottom: 30px;
        }
        
        .servers-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .server-card {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .server-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .server-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea10, #764ba210);
        }
        
        .server-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .server-flag {
            font-size: 32px;
        }
        
        .server-details h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 4px;
        }
        
        .server-ip {
            font-size: 12px;
            color: #666;
            font-family: monospace;
        }
        
        .server-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-dot.online {
            background: #4caf50;
            box-shadow: 0 0 6px #4caf50;
        }
        
        .status-dot.offline {
            background: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Setup New Device</h1>
        <p class="subtitle">Generate your VPN config in one click - server creates everything!</p>
        
        <div id="status" class="status"></div>
        <div id="spinner" class="spinner"></div>
        
        <!-- Server Selection -->
        <div class="servers-section">
            <h2 style="font-size: 20px; margin-bottom: 15px;">🌍 Choose Your Server</h2>
            <div id="serversGrid" class="servers-grid">
                <!-- Servers loaded via JavaScript -->
                <div style="text-align: center; padding: 20px; color: #999;">
                    Loading servers...
                </div>
            </div>
        </div>
        
        <form id="setupForm" onsubmit="generateConfig(event)">
            <div class="form-group">
                <label for="deviceName">Device Name</label>
                <input 
                    type="text" 
                    id="deviceName" 
                    name="deviceName"
                    placeholder="e.g., iPhone, MacBook, Work Laptop"
                    maxlength="50"
                    required
                >
                <div class="help-text">Give your device a friendly name</div>
            </div>
            
            <div class="form-group">
                <label for="deviceType">Device Type</label>
                <select id="deviceType" name="deviceType" required>
                    <option value="mobile">📱 Mobile Phone</option>
                    <option value="desktop">💻 Desktop Computer</option>
                    <option value="tablet">📲 Tablet</option>
                    <option value="router">🌐 Router</option>
                    <option value="other">❓ Other</option>
                </select>
            </div>
            
            <button type="submit" class="btn" id="generateBtn">
                🔑 Generate VPN Config
            </button>
        </form>
    </div>
    
    <script>
        /**
         * Generate VPN Config - SERVER-SIDE Approach
         * 
         * Server generates WireGuard keypair and complete config
         * No browser-side crypto needed!
         */
        
        let selectedServerId = null;
        
        // Load servers on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadServers();
        });
        
        /**
         * Load available servers
         */
        async function loadServers() {
            try {
                const response = await fetch('/api/servers/list.php', {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('truevault_token')
                    }
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load servers');
                }
                
                renderServers(data.servers);
                
            } catch (error) {
                console.error('Error loading servers:', error);
                document.getElementById('serversGrid').innerHTML = 
                    '<div style="text-align: center; padding: 20px; color: #f44336;">Failed to load servers</div>';
            }
        }
        
        /**
         * Render server list
         */
        function renderServers(servers) {
            const grid = document.getElementById('serversGrid');
            
            if (!servers || servers.length === 0) {
                grid.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;">No servers available</div>';
                return;
            }
            
            grid.innerHTML = servers.map(server => `
                <div class="server-card" onclick="selectServer(${server.id})">
                    <div class="server-info">
                        <div class="server-flag">${getCountryFlag(server.country)}</div>
                        <div class="server-details">
                            <h3>${server.name}</h3>
                            <div class="server-ip">${server.endpoint.split(':')[0]}</div>
                        </div>
                    </div>
                    <div class="server-status">
                        <div class="status-dot ${server.status}"></div>
                        <span style="color: ${server.status === 'online' ? '#4caf50' : '#f44336'};">
                            ${server.status === 'online' ? 'Online' : 'Offline'}
                        </span>
                    </div>
                </div>
            `).join('');
        }
        
        /**
         * Get country flag emoji
         */
        function getCountryFlag(country) {
            const flags = {
                'usa': '🇺🇸',
                'canada': '🇨🇦',
                'us': '🇺🇸',
                'ca': '🇨🇦'
            };
            return flags[country.toLowerCase()] || '🌐';
        }
        
        /**
         * Select server
         */
        function selectServer(serverId) {
            selectedServerId = serverId;
            
            // Update UI
            document.querySelectorAll('.server-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }
        
        async function generateConfig(event) {
            event.preventDefault();
            
            // Check if server is selected
            if (!selectedServerId) {
                showStatus('error', 'Please select a server first');
                return;
            }
            
            const deviceName = document.getElementById('deviceName').value.trim();
            const deviceType = document.getElementById('deviceType').value;
            const generateBtn = document.getElementById('generateBtn');
            
            // Disable button and show loading
            generateBtn.disabled = true;
            generateBtn.textContent = '⏳ Generating...';
            showSpinner(true);
            showStatus('info', 'Server is generating your VPN config...');
            
            try {
                // Call server API - server generates everything
                const response = await fetch('/api/devices/generate-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('truevault_token')
                    },
                    body: JSON.stringify({
                        device_name: deviceName,
                        device_type: deviceType,
                        server_id: selectedServerId
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to generate config');
                }
                
                // Download the config file
                downloadConfig(data.config, deviceName);
                
                showStatus('success', '✅ Config generated! Download started. You can now switch servers anytime.');
                generateBtn.textContent = '✅ Success! Generate Another';
                
                // Reset form
                setTimeout(() => {
                    document.getElementById('setupForm').reset();
                    selectedServerId = null;
                    document.querySelectorAll('.server-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    generateBtn.textContent = '🔑 Generate VPN Config';
                }, 3000);
                
            } catch (error) {
                console.error('Error:', error);
                showStatus('error', error.message);
                generateBtn.textContent = '🔑 Generate VPN Config';
            } finally {
                generateBtn.disabled = false;
                showSpinner(false);
            }
        }
        
        /**
         * Download config file
         */
        function downloadConfig(configContent, deviceName) {
            const filename = `${deviceName.replace(/[^a-z0-9]/gi, '_')}.conf`;
            const blob = new Blob([configContent], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        /**
         * Show status message
         */
        function showStatus(type, message) {
            const status = document.getElementById('status');
            status.className = `status ${type}`;
            status.textContent = message;
            status.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    status.style.display = 'none';
                }, 5000);
            }
        }
        
        /**
         * Show/hide loading spinner
         */
        function showSpinner(show) {
            document.getElementById('spinner').style.display = show ? 'block' : 'none';
        }
    </script>
</body>
</html>
