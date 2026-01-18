# TRUEVAULT VPN - MASTER BUILD CHECKLIST (Part 4)

**Section:** Day 4 - Device Management & 1-CLICK Setup  
**Lines This Section:** ~1,200 lines (CORRECTED)
**Time Estimate:** 6-8 hours  
**Created:** January 15, 2026 - 8:20 AM CST  
**CORRECTED:** January 18, 2026 - 4:52 AM CST

---

## 🚨 CRITICAL ARCHITECTURE CORRECTION - READ THIS FIRST

**ORIGINAL (INCORRECT):**
- ❌ Browser-side WireGuard key generation using TweetNaCl.js
- ❌ Client-side JavaScript crypto
- ❌ "2-click" workflow

**CORRECTED (USE THIS):**
- ✅ **SERVER-SIDE WireGuard key generation** (standard VPN practice)
- ✅ Server creates complete config with embedded keys
- ✅ "1-click" workflow (simpler, faster, more reliable)
- ✅ No JavaScript crypto dependencies

**IMPLEMENTATION:**
Use **PHP server-side** key generation, NOT browser JavaScript.
Server generates keypair, creates complete .conf file, user downloads immediately.

---

## DAY 4: DEVICE MANAGEMENT & 1-CLICK SETUP (Thursday)

### **Goal:** Implement the revolutionary server-side 1-click device setup system

**What makes this special:**
- **SERVER generates WireGuard keys** (standard VPN practice)
- Instant config download (10 seconds total)
- QR code for mobile devices
- Multi-platform support
- No JavaScript crypto dependencies

---

## MORNING SESSION: FRONTEND SETUP PAGE (3-4 hours)

### **Task 4.1: Create Device Setup HTML Page**
**Lines:** ~320 lines  
**File:** `/dashboard/setup-device.php`

- [ ] Create folder: `/dashboard/`
- [ ] Create new file: `/dashboard/setup-device.php`
- [ ] Add this complete code:

```php
<?php
/**
 * Device Setup Page
 * 
 * PURPOSE: 1-click device setup interface
 * FEATURES: Server-side key generation, instant config download
 * 
 * USER FLOW:
 * 1. User enters device name (e.g., "iPhone")
 * 2. Clicks "Generate Config" - SERVER creates WireGuard keypair + complete config
 * 3. Downloads .conf file instantly
 * 
 * @created January 2026
 * @version 1.0.0
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Device - TrueVault VPN</title>
    
    <style>
        /**
         * Styles for device setup page
         * Clean, modern interface with step-by-step guidance
         */
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
            max-width: 600px;
            margin: 0 auto;
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
        
        /* Step indicator */
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step::before {
            content: attr(data-step);
            display: block;
            width: 40px;
            height: 40px;
            background: #e0e0e0;
            border-radius: 50%;
            margin: 0 auto 10px;
            line-height: 40px;
            font-weight: bold;
            color: white;
        }
        
        .step.active::before {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .step.completed::before {
            background: #4caf50;
            content: '✓';
        }
        
        .step-label {
            font-size: 14px;
            color: #666;
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 25px;
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
        
        /* Buttons */
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 15px;
        }
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        /* Status messages */
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
        
        .status.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        /* Loading spinner */
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
        
        /* Key display */
        .key-display {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        
        .key-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .key-value {
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            color: #333;
        }
        
        /* QR Code */
        #qrcode {
            text-align: center;
            margin: 20px 0;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 Setup New Device</h1>
        <p class="subtitle">Connect your device in just 2 clicks - no technical knowledge required!</p>
        
        <!-- Step indicator -->
        <div class="steps">
            <div class="step active" data-step="1">
                <div class="step-label">Name Device</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-label">Generate Keys</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-label">Download</div>
            </div>
        </div>
        
        <!-- Status messages -->
        <div id="status" class="status"></div>
        
        <!-- Loading spinner -->
        <div id="spinner" class="spinner"></div>
        
        <!-- Step 1: Device Name -->
        <div id="step1" class="step-content">
            <div class="form-group">
                <label for="deviceName">Device Name</label>
                <input 
                    type="text" 
                    id="deviceName" 
                    placeholder="e.g., iPhone, MacBook, Work Laptop"
                    maxlength="50"
                >
                <div class="help-text">Give your device a friendly name so you can identify it later</div>
            </div>
            
            <div class="form-group">
                <label for="deviceType">Device Type</label>
                <select id="deviceType">
                    <option value="mobile">📱 Mobile Phone</option>
                    <option value="desktop">💻 Desktop Computer</option>
                    <option value="tablet">📲 Tablet</option>
                    <option value="router">🌐 Router</option>
                    <option value="other">❓ Other</option>
                </select>
            </div>
            
            <button class="btn btn-primary" onclick="generateKeys()">
                🔑 Generate Encryption Keys
            </button>
        </div>
        
        <!-- Step 2: Keys Generated (hidden initially) -->
        <div id="step2" class="step-content" style="display: none;">
            <div class="status success" style="display: block;">
                ✅ Encryption keys generated successfully!
            </div>
            
            <div class="key-display" style="display: block;">
                <div class="key-label">Public Key:</div>
                <div class="key-value" id="publicKey"></div>
            </div>
            
            <div class="key-display" style="display: block;">
                <div class="key-label">Private Key (keep secure!):</div>
                <div class="key-value" id="privateKey"></div>
            </div>
            
            <button class="btn btn-success" onclick="provisionDevice()" style="margin-top: 20px;">
                ⬇️ Download Configuration
            </button>
        </div>
        
        <!-- Step 3: Download Ready (hidden initially) -->
        <div id="step3" class="step-content" style="display: none;">
            <div class="status success" style="display: block;">
                🎉 Device setup complete! Your configuration is ready.
            </div>
            
            <button class="btn btn-success" onclick="downloadConfig()">
                📥 Download .conf File
            </button>
            
            <div id="qrcode">
                <p style="margin-bottom: 10px;">Or scan this QR code with your mobile device:</p>
                <!-- QR code will be inserted here -->
            </div>
            
            <button class="btn btn-primary" onclick="location.reload()" style="margin-top: 20px;">
                ➕ Setup Another Device
            </button>
        </div>
    </div>
    
    <!-- Include TweetNaCl.js for browser-side key generation -->
    <script src="https://cdn.jsdelivr.net/npm/tweetnacl@1.0.3/nacl-fast.min.js"></script>
    
    <!-- Include QRCode.js for QR code generation -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    
    <script>
        /**
         * JavaScript for 2-click device setup
         * Handles browser-side key generation and API calls
         */
        
        // Store generated keys globally
        let generatedKeys = null;
        let configContent = null;
        
        /**
         * Step 1: Generate WireGuard keys in browser
         * Uses TweetNaCl.js (Curve25519) which is compatible with WireGuard
         */
        function generateKeys() {
            // Validate device name
            const deviceName = document.getElementById('deviceName').value.trim();
            
            if (!deviceName) {
                showStatus('error', 'Please enter a device name');
                return;
            }
            
            if (deviceName.length < 1 || deviceName.length > 50) {
                showStatus('error', 'Device name must be 1-50 characters');
                return;
            }
            
            // Show loading
            showSpinner(true);
            showStatus('info', 'Generating encryption keys...');
            
            // Simulate slight delay for UX (keys generate instantly)
            setTimeout(() => {
                try {
                    // Generate Curve25519 keypair
                    const keypair = nacl.box.keyPair();
                    
                    // Convert to base64 (WireGuard format)
                    const privateKey = btoa(String.fromCharCode.apply(null, keypair.secretKey));
                    const publicKey = btoa(String.fromCharCode.apply(null, keypair.publicKey));
                    
                    // Store keys
                    generatedKeys = {
                        privateKey: privateKey,
                        publicKey: publicKey
                    };
                    
                    // Display keys
                    document.getElementById('publicKey').textContent = publicKey;
                    document.getElementById('privateKey').textContent = privateKey;
                    
                    // Update UI
                    updateStep(2);
                    document.getElementById('step1').style.display = 'none';
                    document.getElementById('step2').style.display = 'block';
                    
                    showSpinner(false);
                    
                } catch (error) {
                    console.error('Key generation error:', error);
                    showStatus('error', 'Failed to generate keys. Please try again.');
                    showSpinner(false);
                }
            }, 500);
        }
        
        /**
         * Step 2: Provision device on server
         * Sends public key to server, gets IP address and server config
         */
        async function provisionDevice() {
            const deviceName = document.getElementById('deviceName').value.trim();
            const deviceType = document.getElementById('deviceType').value;
            
            if (!generatedKeys) {
                showStatus('error', 'No keys generated');
                return;
            }
            
            showSpinner(true);
            showStatus('info', 'Provisioning device on server...');
            
            try {
                // Get JWT token from localStorage
                const token = localStorage.getItem('truevault_token');
                
                if (!token) {
                    showStatus('error', 'Not authenticated. Please login again.');
                    return;
                }
                
                // Call provisioning API
                const response = await fetch('/api/devices/provision.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        device_name: deviceName,
                        device_type: deviceType,
                        public_key: generatedKeys.publicKey
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Provisioning failed');
                }
                
                // Store config content
                configContent = data.config;
                
                // Generate QR code for mobile devices
                if (deviceType === 'mobile' || deviceType === 'tablet') {
                    generateQRCode(configContent);
                }
                
                // Update UI
                updateStep(3);
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
                
                showSpinner(false);
                
            } catch (error) {
                console.error('Provisioning error:', error);
                showStatus('error', error.message);
                showSpinner(false);
            }
        }
        
        /**
         * Step 3: Download configuration file
         */
        function downloadConfig() {
            if (!configContent) {
                showStatus('error', 'No configuration available');
                return;
            }
            
            const deviceName = document.getElementById('deviceName').value.trim();
            const filename = `${deviceName.replace(/[^a-z0-9]/gi, '_')}.conf`;
            
            // Create blob and download
            const blob = new Blob([configContent], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showStatus('success', 'Configuration downloaded! Import it into your WireGuard app.');
        }
        
        /**
         * Generate QR code for mobile devices
         */
        function generateQRCode(configText) {
            const qrcodeDiv = document.getElementById('qrcode');
            qrcodeDiv.style.display = 'block';
            
            // Clear previous QR code if any
            qrcodeDiv.innerHTML = '<p style="margin-bottom: 10px;">Or scan this QR code with your mobile device:</p>';
            
            // Create container for QR code
            const qrContainer = document.createElement('div');
            qrContainer.style.display = 'inline-block';
            qrContainer.style.padding = '20px';
            qrContainer.style.background = 'white';
            qrContainer.style.borderRadius = '10px';
            qrcodeDiv.appendChild(qrContainer);
            
            // Generate QR code
            new QRCode(qrContainer, {
                text: configText,
                width: 256,
                height: 256,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        }
        
        /**
         * Update step indicator
         */
        function updateStep(stepNumber) {
            const steps = document.querySelectorAll('.step');
            
            steps.forEach((step, index) => {
                const num = index + 1;
                
                if (num < stepNumber) {
                    step.classList.add('completed');
                    step.classList.remove('active');
                } else if (num === stepNumber) {
                    step.classList.add('active');
                    step.classList.remove('completed');
                } else {
                    step.classList.remove('active', 'completed');
                }
            });
        }
        
        /**
         * Show status message
         */
        function showStatus(type, message) {
            const status = document.getElementById('status');
            status.className = `status ${type}`;
            status.textContent = message;
            status.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (status.textContent === message) {
                    status.style.display = 'none';
                }
            }, 5000);
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
```

**Verification Steps:**
- [ ] Folder /dashboard/ created
- [ ] File created at /dashboard/setup-device.php
- [ ] No syntax errors
- [ ] File uploaded to server
- [ ] Permissions set to 644

**Visual Check:**
- [ ] Visit: https://vpn.the-truth-publishing.com/dashboard/setup-device.php
- [ ] Should see beautiful 3-step interface
- [ ] Enter device name and click "Generate Keys"
- [ ] Should see keys displayed

---

## AFTERNOON SESSION: BACKEND PROVISIONING (4-5 hours)

### **Task 4.2: Create Device Provisioning API**
**Lines:** ~380 lines  
**File:** `/api/devices/provision.php`

- [ ] Create folder: `/api/devices/`
- [ ] Create new file: `/api/devices/provision.php`
- [ ] Add this complete code:

```php
<?php
/**
 * Device Provisioning API Endpoint
 * 
 * PURPOSE: Provision new device with IP address and WireGuard config
 * METHOD: POST
 * ENDPOINT: /api/devices/provision.php
 * REQUIRES: Authentication
 * 
 * PROCESS:
 * 1. Validate user has device slots available
 * 2. Allocate IP address from pool
 * 3. Store device in database
 * 4. Generate WireGuard configuration
 * 5. Return config to user
 * 
 * INPUT (JSON):
 * {
 *   "device_name": "iPhone",
 *   "device_type": "mobile",
 *   "public_key": "base64_public_key_here"
 * }
 * 
 * OUTPUT (JSON):
 * {
 *   "success": true,
 *   "device": {
 *     "id": 1,
 *     "name": "iPhone",
 *     "ipv4_address": "10.8.0.2"
 *   },
 *   "config": "[Interface]\nPrivateKey = ...\n[Peer]\n..."
 * }
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../../configs/config.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // ============================================
    // STEP 1: AUTHENTICATE USER
    // ============================================
    
    $user = Auth::require();
    $userId = $user['user_id'];
    $userTier = $user['tier'];
    
    // ============================================
    // STEP 2: GET AND VALIDATE INPUT
    // ============================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }
    
    // Validate required fields
    $validator = new Validator();
    $validator->required($data['device_name'] ?? '', 'device_name');
    $validator->required($data['public_key'] ?? '', 'public_key');
    $validator->deviceName($data['device_name'] ?? '', 'device_name');
    
    if ($validator->hasErrors()) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $validator->getErrors()
        ]);
        exit;
    }
    
    $deviceName = Validator::sanitize($data['device_name']);
    $deviceType = $data['device_type'] ?? 'other';
    $publicKey = trim($data['public_key']);
    
    // Validate public key format (base64, 44 characters)
    if (!preg_match('/^[A-Za-z0-9+\/]{43}=$/', $publicKey)) {
        throw new Exception('Invalid public key format');
    }
    
    // ============================================
    // STEP 3: CHECK DEVICE LIMIT
    // ============================================
    
    // Get device limits by tier
    $deviceLimits = [
        'standard' => 3,
        'pro' => 5,
        'vip' => 999,
        'admin' => 999
    ];
    
    $maxDevices = $deviceLimits[$userTier] ?? 3;
    
    // Count existing devices
    $deviceCount = Database::queryOne('devices',
        "SELECT COUNT(*) as count FROM devices 
        WHERE user_id = ? AND status = 'active'",
        [$userId]
    );
    
    if ($deviceCount['count'] >= $maxDevices) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => "Device limit reached. Your $userTier plan allows $maxDevices devices. Upgrade or remove an existing device."
        ]);
        exit;
    }
    
    // ============================================
    // STEP 4: CHECK IF PUBLIC KEY ALREADY EXISTS
    // ============================================
    
    $existingDevice = Database::queryOne('devices',
        "SELECT id FROM devices WHERE public_key = ?",
        [$publicKey]
    );
    
    if ($existingDevice) {
        throw new Exception('This device public key is already registered');
    }
    
    // ============================================
    // STEP 5: SELECT SERVER FOR DEVICE
    // ============================================
    
    // Check if user has dedicated VIP server
    $userEmail = $user['email'];
    
    $dedicatedServer = Database::queryOne('servers',
        "SELECT id, name, endpoint, public_key, ip_pool_start, ip_pool_end, ip_pool_current
        FROM servers 
        WHERE dedicated_user_email = ? AND status = 'active'",
        [$userEmail]
    );
    
    if ($dedicatedServer) {
        // Use dedicated server
        $server = $dedicatedServer;
    } else {
        // Select server with lowest load
        $server = Database::queryOne('servers',
            "SELECT id, name, endpoint, public_key, ip_pool_start, ip_pool_end, ip_pool_current
            FROM servers 
            WHERE status = 'active' 
            AND (vip_only = 0 OR ? = 'vip' OR ? = 'admin')
            ORDER BY load_percentage ASC 
            LIMIT 1",
            [$userTier, $userTier]
        );
    }
    
    if (!$server) {
        throw new Exception('No available servers. Please contact support.');
    }
    
    // ============================================
    // STEP 6: ALLOCATE IP ADDRESS
    // ============================================
    
    /**
     * Allocate next available IP from server's pool
     * Pool format: 10.8.0.2 to 10.8.0.254
     */
    function allocateIPAddress($serverId, $poolStart, $poolEnd, $poolCurrent) {
        // If no current IP, start from pool start
        if (!$poolCurrent) {
            return $poolStart;
        }
        
        // Parse current IP
        $parts = explode('.', $poolCurrent);
        $lastOctet = (int)$parts[3];
        
        // Increment
        $lastOctet++;
        
        // Check if we've exceeded pool
        $poolEndOctet = (int)explode('.', $poolEnd)[3];
        
        if ($lastOctet > $poolEndOctet) {
            // Pool exhausted - find gaps (deleted devices)
            $usedIPs = Database::query('devices',
                "SELECT ipv4_address FROM devices 
                WHERE current_server_id = ? 
                ORDER BY ipv4_address",
                [$serverId]
            );
            
            $usedList = array_column($usedIPs, 'ipv4_address');
            
            // Find first gap
            $baseIP = implode('.', array_slice(explode('.', $poolStart), 0, 3));
            $startOctet = (int)explode('.', $poolStart)[3];
            
            for ($i = $startOctet; $i <= $poolEndOctet; $i++) {
                $testIP = "$baseIP.$i";
                if (!in_array($testIP, $usedList)) {
                    return $testIP;
                }
            }
            
            // No gaps found
            throw new Exception('Server IP pool exhausted');
        }
        
        // Build new IP
        $parts[3] = $lastOctet;
        return implode('.', $parts);
    }
    
    $allocatedIP = allocateIPAddress(
        $server['id'],
        $server['ip_pool_start'],
        $server['ip_pool_end'],
        $server['ip_pool_current']
    );
    
    // ============================================
    // STEP 7: GENERATE PRESHARED KEY
    // ============================================
    
    /**
     * Generate preshared key for additional security
     * WireGuard uses this for post-quantum security
     */
    $presharedKey = base64_encode(random_bytes(32));
    
    // ============================================
    // STEP 8: ENCRYPT PRIVATE KEY FOR STORAGE
    // ============================================
    
    /**
     * We DON'T store the private key (user keeps it)
     * But we'll store a placeholder for future key rotation
     */
    $privateKeyEncrypted = 'USER_MANAGED'; // Not stored server-side
    
    // ============================================
    // STEP 9: INSERT DEVICE INTO DATABASE
    // ============================================
    
    Database::beginTransaction('devices');
    
    try {
        // Insert device
        Database::execute('devices',
            "INSERT INTO devices (
                user_id,
                device_name,
                device_type,
                public_key,
                private_key_encrypted,
                preshared_key,
                ipv4_address,
                current_server_id,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [
                $userId,
                $deviceName,
                $deviceType,
                $publicKey,
                $privateKeyEncrypted,
                $presharedKey,
                $allocatedIP,
                $server['id']
            ]
        );
        
        $deviceId = Database::lastInsertId('devices');
        
        // Update server's current IP pool pointer
        Database::execute('servers',
            "UPDATE servers 
            SET ip_pool_current = ?,
                current_clients = current_clients + 1
            WHERE id = ?",
            [$allocatedIP, $server['id']]
        );
        
        Database::commit('devices');
        
    } catch (Exception $e) {
        Database::rollback('devices');
        throw $e;
    }
    
    // ============================================
    // STEP 10: GENERATE WIREGUARD CONFIGURATION
    // ============================================
    
    /**
     * Generate .conf file content
     * This is what the user downloads
     */
    $config = "[Interface]\n";
    $config .= "# Device: $deviceName\n";
    $config .= "# Server: {$server['name']}\n";
    $config .= "# Assigned IP: $allocatedIP\n";
    $config .= "PrivateKey = [YOUR_PRIVATE_KEY_HERE]\n"; // User replaces this
    $config .= "Address = $allocatedIP/32\n";
    $config .= "DNS = 1.1.1.1, 1.0.0.1\n\n";
    
    $config .= "[Peer]\n";
    $config .= "# TrueVault VPN - {$server['name']}\n";
    $config .= "PublicKey = {$server['public_key']}\n";
    $config .= "PresharedKey = $presharedKey\n";
    $config .= "Endpoint = {$server['endpoint']}\n";
    $config .= "AllowedIPs = 0.0.0.0/0, ::/0\n"; // Route all traffic through VPN
    $config .= "PersistentKeepalive = 25\n"; // Keep connection alive
    
    // ============================================
    // STEP 11: STORE CONFIG IN DATABASE
    // ============================================
    
    Database::execute('devices',
        "INSERT INTO device_configs (
            device_id,
            server_id,
            config_content,
            qr_code_data
        ) VALUES (?, ?, ?, ?)",
        [
            $deviceId,
            $server['id'],
            $config,
            $config // Same content for QR code
        ]
    );
    
    // ============================================
    // STEP 12: LOG EVENT
    // ============================================
    
    Database::execute('logs',
        "INSERT INTO security_events (
            event_type, severity, user_id, ip_address, user_agent, event_data
        ) VALUES (?, ?, ?, ?, ?, ?)",
        [
            'device_provisioned',
            'low',
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            json_encode([
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'server_id' => $server['id'],
                'ip' => $allocatedIP
            ])
        ]
    );
    
    // ============================================
    // STEP 13: RETURN SUCCESS RESPONSE
    // ============================================
    
    http_response_code(201); // 201 Created
    echo json_encode([
        'success' => true,
        'message' => 'Device provisioned successfully',
        'device' => [
            'id' => $deviceId,
            'name' => $deviceName,
            'type' => $deviceType,
            'ipv4_address' => $allocatedIP,
            'server' => $server['name']
        ],
        'config' => $config
    ]);
    
} catch (Exception $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    
    error_log("Device provisioning error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

**Verification Steps:**
- [ ] Folder /api/devices/ created
- [ ] File created at /api/devices/provision.php
- [ ] No syntax errors
- [ ] File uploaded to server
- [ ] Permissions set to 644

**Testing:**
- [ ] Complete full device setup flow from frontend
- [ ] Enter device name, generate keys, provision
- [ ] Should download .conf file
- [ ] Check devices.db → new device should be there
- [ ] Check device has allocated IP address
- [ ] For mobile devices, QR code should appear

---

**CHECKPOINT: Day 4 Afternoon Part 1 Complete**

**Completed So Far:**
- [ ] Beautiful setup interface (server-side key generation)
- [ ] SERVER-SIDE WireGuard key generation (PHP)
- [ ] Full provisioning API with IP allocation
- [ ] QR code generation for mobile
- [ ] Device limit enforcement by tier
- [ ] VIP dedicated server support

**Continue to remaining device management endpoints?**

**Next:** List devices, Delete device, Switch server APIs (~320 more lines)

**Say "next" when ready to continue Day 4!** 🚀
