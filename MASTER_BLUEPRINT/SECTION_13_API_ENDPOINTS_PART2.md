# SECTION 13: API ENDPOINTS (Part 2/2)

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Continuation of:** SECTION_13_API_ENDPOINTS_PART1.md  

---

## 📋 PART 2 CONTENTS

1. [API Implementation Examples](#implementation)
2. [Authentication Middleware](#middleware)
3. [Database Helper Functions](#database)
4. [Rate Limiting](#rate-limiting)
5. [CORS Configuration](#cors)
6. [Logging & Monitoring](#logging)
7. [Testing API Endpoints](#testing)
8. [API Versioning](#versioning)

---

## 💻 API IMPLEMENTATION EXAMPLES

### **Complete API Endpoint Structure**

```php
<?php
// File: /api/devices.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Authenticate user
$user = authenticateRequest();
if (!$user) {
    sendError('Unauthorized', 'AUTH_1001', 401);
}

// Get action
$action = $_GET['action'] ?? '';

// Route to appropriate handler
switch ($action) {
    case 'list':
        listDevices($user);
        break;
    
    case 'create':
        createDevice($user);
        break;
    
    case 'update':
        updateDevice($user);
        break;
    
    case 'delete':
        deleteDevice($user);
        break;
    
    case 'switch_server':
        switchServer($user);
        break;
    
    case 'status':
        getDeviceStatus($user);
        break;
    
    default:
        sendError('Invalid action', 'SYS_9001', 400);
}

// ============== HANDLERS ==============

function listDevices($user) {
    global $db_devices;
    
    try {
        $stmt = $db_devices->prepare("
            SELECT d.*, s.name as server_name, s.location as server_location
            FROM devices d
            LEFT JOIN servers s ON d.current_server_id = s.id
            WHERE d.user_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check connection status for each device
        foreach ($devices as &$device) {
            $status = checkDeviceConnection($device['id']);
            $device['is_connected'] = $status['connected'];
            $device['last_seen'] = $status['last_seen'];
            $device['bandwidth_used'] = $status['total_bytes'];
        }
        
        sendSuccess(['devices' => $devices]);
        
    } catch (Exception $e) {
        logError('List devices error', $e);
        sendError('Failed to load devices', 'SYS_9002', 500);
    }
}

function createDevice($user) {
    global $db_devices;
    
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';
    $deviceType = $input['device_type'] ?? 'other';
    $serverId = $input['server_id'] ?? 1;
    
    // Validate
    if (empty($name)) {
        sendError('Device name required', 'DEV_2004', 400);
    }
    
    // Check device limit
    $limit = getDeviceLimit($user['tier']);
    $stmt = $db_devices->prepare("SELECT COUNT(*) FROM devices WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $count = $stmt->fetchColumn();
    
    if ($count >= $limit) {
        sendError('Device limit reached', 'DEV_2001', 403);
    }
    
    // Validate server
    $server = getServer($serverId);
    if (!$server) {
        sendError('Invalid server', 'SRV_3001', 404);
    }
    
    // Check VIP restriction
    if ($server['vip_only'] && !isVIPEmail($user['email'])) {
        sendError('VIP server restricted', 'SRV_3004', 403);
    }
    
    try {
        // Generate WireGuard keys
        $keys = generateWireGuardKeys();
        
        // Allocate IP
        $vpnIp = allocateIPAddress($serverId);
        
        // Create device
        $stmt = $db_devices->prepare("
            INSERT INTO devices (
                user_id, name, device_type, vpn_ip,
                public_key, private_key, current_server_id,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        $stmt->execute([
            $user['id'],
            $name,
            $deviceType,
            $vpnIp,
            $keys['public_key'],
            $keys['private_key'],
            $serverId
        ]);
        
        $deviceId = $db_devices->lastInsertId();
        
        // Add peer to server
        addPeerToServer($serverId, $keys['public_key'], $vpnIp);
        
        // Get created device
        $device = getDevice($deviceId);
        
        sendSuccess([
            'message' => 'Device created successfully',
            'device' => $device
        ]);
        
    } catch (Exception $e) {
        logError('Create device error', $e);
        sendError('Failed to create device', 'SYS_9002', 500);
    }
}

function updateDevice($user) {
    global $db_devices;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = $input['device_id'] ?? 0;
    $name = $input['name'] ?? '';
    $deviceType = $input['device_type'] ?? '';
    
    // Verify ownership
    $device = getDevice($deviceId);
    if (!$device || $device['user_id'] !== $user['id']) {
        sendError('Device not found', 'DEV_2002', 404);
    }
    
    try {
        $updates = [];
        $params = [];
        
        if (!empty($name)) {
            $updates[] = "name = ?";
            $params[] = $name;
        }
        
        if (!empty($deviceType)) {
            $updates[] = "device_type = ?";
            $params[] = $deviceType;
        }
        
        if (empty($updates)) {
            sendError('No updates provided', 'SYS_9001', 400);
        }
        
        $params[] = $deviceId;
        
        $stmt = $db_devices->prepare("
            UPDATE devices 
            SET " . implode(', ', $updates) . "
            WHERE id = ?
        ");
        $stmt->execute($params);
        
        $device = getDevice($deviceId);
        
        sendSuccess([
            'message' => 'Device updated successfully',
            'device' => $device
        ]);
        
    } catch (Exception $e) {
        logError('Update device error', $e);
        sendError('Failed to update device', 'SYS_9002', 500);
    }
}

function deleteDevice($user) {
    global $db_devices;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = $input['device_id'] ?? 0;
    
    // Verify ownership
    $device = getDevice($deviceId);
    if (!$device || $device['user_id'] !== $user['id']) {
        sendError('Device not found', 'DEV_2002', 404);
    }
    
    try {
        // Remove from server
        removePeerFromServer($device['current_server_id'], $device['public_key']);
        
        // Delete device
        $stmt = $db_devices->prepare("DELETE FROM devices WHERE id = ?");
        $stmt->execute([$deviceId]);
        
        sendSuccess(['message' => 'Device removed successfully']);
        
    } catch (Exception $e) {
        logError('Delete device error', $e);
        sendError('Failed to delete device', 'SYS_9002', 500);
    }
}

function switchServer($user) {
    global $db_devices;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = $input['device_id'] ?? 0;
    $newServerId = $input['server_id'] ?? 0;
    
    // Verify ownership
    $device = getDevice($deviceId);
    if (!$device || $device['user_id'] !== $user['id']) {
        sendError('Device not found', 'DEV_2002', 404);
    }
    
    // Validate server
    $newServer = getServer($newServerId);
    if (!$newServer) {
        sendError('Invalid server', 'SRV_3001', 404);
    }
    
    // Check VIP restriction
    if ($newServer['vip_only'] && !isVIPEmail($user['email'])) {
        sendError('VIP server restricted', 'SRV_3004', 403);
    }
    
    try {
        // Remove from old server
        removePeerFromServer($device['current_server_id'], $device['public_key']);
        
        // Allocate new IP
        $newVpnIp = allocateIPAddress($newServerId);
        
        // Add to new server
        addPeerToServer($newServerId, $device['public_key'], $newVpnIp);
        
        // Update database
        $stmt = $db_devices->prepare("
            UPDATE devices 
            SET current_server_id = ?, vpn_ip = ?
            WHERE id = ?
        ");
        $stmt->execute([$newServerId, $newVpnIp, $deviceId]);
        
        // Generate new config
        $newConfig = generateClientConfig($deviceId);
        
        $device = getDevice($deviceId);
        
        sendSuccess([
            'message' => 'Server switched successfully',
            'device' => $device,
            'new_config' => $newConfig
        ]);
        
    } catch (Exception $e) {
        logError('Switch server error', $e);
        sendError('Failed to switch server', 'SYS_9002', 500);
    }
}

function getDeviceStatus($user) {
    $deviceId = $_GET['device_id'] ?? 0;
    
    // Verify ownership
    $device = getDevice($deviceId);
    if (!$device || $device['user_id'] !== $user['id']) {
        sendError('Device not found', 'DEV_2002', 404);
    }
    
    try {
        $status = checkDeviceConnection($deviceId);
        
        sendSuccess(['status' => $status]);
        
    } catch (Exception $e) {
        logError('Get device status error', $e);
        sendError('Failed to get status', 'SYS_9002', 500);
    }
}
```

---

## 🔐 AUTHENTICATION MIDDLEWARE

### **Authentication Functions**

```php
<?php
// File: /includes/auth.php

require_once __DIR__ . '/jwt.php';

/**
 * Authenticate incoming API request
 * Returns user object if authenticated, false otherwise
 */
function authenticateRequest() {
    // Get Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    // Check format: "Bearer {token}"
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    
    // Verify JWT token
    $payload = verifyJWT($token);
    if (!$payload) {
        return false;
    }
    
    // Get user from database
    global $db_users;
    $stmt = $db_users->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return false;
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        return false;
    }
    
    return $user;
}

/**
 * Require admin privileges
 */
function requireAdmin() {
    $user = authenticateRequest();
    
    if (!$user) {
        sendError('Unauthorized', 'AUTH_1001', 401);
    }
    
    if ($user['role'] !== 'admin') {
        sendError('Admin access required', 'AUTH_1004', 403);
    }
    
    return $user;
}

/**
 * Check if user is VIP
 */
function isVIP($user) {
    return in_array($user['email'], getVIPEmails());
}

/**
 * Generate JWT token
 */
function generateAuthToken($userId) {
    $payload = [
        'user_id' => $userId,
        'iat' => time(),
        'exp' => time() + (30 * 24 * 60 * 60) // 30 days
    ];
    
    return createJWT($payload);
}
```

### **JWT Helper Functions**

```php
<?php
// File: /includes/jwt.php

define('JWT_SECRET', 'your-secret-key-change-this-in-production');

/**
 * Create JWT token
 */
function createJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode($payload);
    
    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload);
    
    $signature = hash_hmac(
        'sha256',
        $base64UrlHeader . "." . $base64UrlPayload,
        JWT_SECRET,
        true
    );
    
    $base64UrlSignature = base64UrlEncode($signature);
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Verify JWT token
 */
function verifyJWT($token) {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    // Verify signature
    $expectedSignature = hash_hmac(
        'sha256',
        $header . "." . $payload,
        JWT_SECRET,
        true
    );
    
    if (!hash_equals(base64UrlDecode($signature), $expectedSignature)) {
        return false;
    }
    
    // Decode payload
    $payloadData = json_decode(base64UrlDecode($payload), true);
    
    // Check expiration
    if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
        return false;
    }
    
    return $payloadData;
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}
```

---

## 🗄️ DATABASE HELPER FUNCTIONS

```php
<?php
// File: /includes/database.php

// Database connections
$db_users = null;
$db_devices = null;
$db_servers = null;

function initDatabases() {
    global $db_users, $db_devices, $db_servers;
    
    $dbPath = __DIR__ . '/../databases/';
    
    try {
        $db_users = new PDO('sqlite:' . $dbPath . 'users.db');
        $db_users->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $db_devices = new PDO('sqlite:' . $dbPath . 'devices.db');
        $db_devices->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $db_servers = new PDO('sqlite:' . $dbPath . 'servers.db');
        $db_servers->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
    } catch (PDOException $e) {
        logError('Database connection error', $e);
        die('Database connection failed');
    }
}

// Initialize on load
initDatabases();

/**
 * Get user by ID
 */
function getUser($userId) {
    global $db_users;
    
    $stmt = $db_users->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get device by ID
 */
function getDevice($deviceId) {
    global $db_devices;
    
    $stmt = $db_devices->prepare("
        SELECT d.*, s.name as server_name, s.location as server_location,
               s.public_key as server_public_key, s.endpoint
        FROM devices d
        LEFT JOIN servers s ON d.current_server_id = s.id
        WHERE d.id = ?
    ");
    $stmt->execute([$deviceId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get server by ID
 */
function getServer($serverId) {
    global $db_servers;
    
    $stmt = $db_servers->prepare("SELECT * FROM servers WHERE id = ?");
    $stmt->execute([$serverId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get device limit for tier
 */
function getDeviceLimit($tier) {
    $limits = [
        'standard' => 5,
        'pro' => 10,
        'vip' => 999
    ];
    
    return $limits[$tier] ?? 5;
}
```

---

## ⏱️ RATE LIMITING

```php
<?php
// File: /includes/rate-limit.php

/**
 * Check rate limit for user
 * Returns true if allowed, false if exceeded
 */
function checkRateLimit($userId, $tier = 'standard') {
    $limits = [
        'standard' => 100,  // requests per minute
        'pro' => 200,
        'vip' => 1000,
        'admin' => 999999
    ];
    
    $limit = $limits[$tier] ?? 100;
    
    // Use Redis or file-based cache
    $cacheFile = sys_get_temp_dir() . "/rate_limit_{$userId}.txt";
    
    $now = time();
    $windowStart = $now - 60; // 1 minute window
    
    // Load existing requests
    $requests = [];
    if (file_exists($cacheFile)) {
        $requests = json_decode(file_get_contents($cacheFile), true) ?? [];
    }
    
    // Filter to current window
    $requests = array_filter($requests, function($timestamp) use ($windowStart) {
        return $timestamp > $windowStart;
    });
    
    // Check limit
    if (count($requests) >= $limit) {
        // Set rate limit headers
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . ($now + 60));
        
        return false;
    }
    
    // Add current request
    $requests[] = $now;
    
    // Save
    file_put_contents($cacheFile, json_encode($requests));
    
    // Set rate limit headers
    header('X-RateLimit-Limit: ' . $limit);
    header('X-RateLimit-Remaining: ' . ($limit - count($requests)));
    header('X-RateLimit-Reset: ' . ($now + 60));
    
    return true;
}

/**
 * Enforce rate limit (send error if exceeded)
 */
function enforceRateLimit($user) {
    if (!checkRateLimit($user['id'], $user['tier'])) {
        sendError('Rate limit exceeded', 'SYS_9003', 429);
    }
}
```

---

## 🌐 CORS CONFIGURATION

```php
<?php
// File: /includes/cors.php

/**
 * Set CORS headers
 */
function setCORSHeaders() {
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
    
    // Access-Control headers
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        }
        
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        
        exit(0);
    }
}

// Call at start of every API file
setCORSHeaders();
```

---

## 📝 LOGGING & MONITORING

```php
<?php
// File: /includes/logging.php

/**
 * Log error
 */
function logError($message, $exception = null) {
    $logFile = __DIR__ . '/../logs/error.log';
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    
    if ($exception) {
        $logMessage .= "\n" . $exception->getMessage();
        $logMessage .= "\n" . $exception->getTraceAsString();
    }
    
    $logMessage .= "\n" . str_repeat('-', 80) . "\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Log API request
 */
function logAPIRequest($user, $endpoint, $action) {
    $logFile = __DIR__ . '/../logs/api.log';
    
    $timestamp = date('Y-m-d H:i:s');
    $userId = $user ? $user['id'] : 'anonymous';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logMessage = "[{$timestamp}] User {$userId} | IP {$ip} | {$endpoint}?action={$action}\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Log security event
 */
function logSecurityEvent($event, $details = []) {
    $logFile = __DIR__ . '/../logs/security.log';
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logMessage = "[{$timestamp}] {$event} | IP {$ip}";
    
    if (!empty($details)) {
        $logMessage .= " | " . json_encode($details);
    }
    
    $logMessage .= "\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
```

---

## 🧪 TESTING API ENDPOINTS

### **cURL Examples**

**Register:**
```bash
curl -X POST https://vpn.the-truth-publishing.com/api/auth.php?action=register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPassword123!",
    "first_name": "Test",
    "last_name": "User"
  }'
```

**Login:**
```bash
curl -X POST https://vpn.the-truth-publishing.com/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPassword123!"
  }'
```

**List Devices:**
```bash
curl -X GET https://vpn.the-truth-publishing.com/api/devices.php?action=list \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Create Device:**
```bash
curl -X POST https://vpn.the-truth-publishing.com/api/devices.php?action=create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "name": "Test Device",
    "device_type": "laptop",
    "server_id": 1
  }'
```

### **JavaScript Testing**

```javascript
// Test suite
class APITester {
    constructor(baseUrl, token = null) {
        this.baseUrl = baseUrl;
        this.token = token;
    }
    
    async request(endpoint, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (this.token) {
            options.headers['Authorization'] = 'Bearer ' + this.token;
        }
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(this.baseUrl + endpoint, options);
        return await response.json();
    }
    
    async testAuth() {
        console.log('Testing authentication...');
        
        // Register
        const registerResult = await this.request('/auth.php?action=register', 'POST', {
            email: 'test' + Date.now() + '@example.com',
            password: 'TestPassword123!',
            first_name: 'Test',
            last_name: 'User'
        });
        
        console.log('Register:', registerResult);
        
        if (registerResult.success) {
            this.token = registerResult.token;
            console.log('✓ Registration successful');
        } else {
            console.error('✗ Registration failed');
        }
    }
    
    async testDevices() {
        console.log('Testing devices...');
        
        // List devices
        const listResult = await this.request('/devices.php?action=list');
        console.log('List devices:', listResult);
        
        // Create device
        const createResult = await this.request('/devices.php?action=create', 'POST', {
            name: 'Test Device',
            device_type: 'laptop',
            server_id: 1
        });
        console.log('Create device:', createResult);
        
        if (createResult.success) {
            console.log('✓ Device creation successful');
        }
    }
    
    async runAll() {
        await this.testAuth();
        await this.testDevices();
        console.log('All tests complete!');
    }
}

// Run tests
const tester = new APITester('https://vpn.the-truth-publishing.com/api');
tester.runAll();
```

---

## 🔄 API VERSIONING

### **Version Strategy**

**URL-based versioning:**
```
/api/v1/devices.php
/api/v2/devices.php
```

**Header-based versioning:**
```http
X-API-Version: 1.0
```

### **Version Router**

```php
<?php
// File: /api/router.php

$version = $_SERVER['HTTP_X_API_VERSION'] ?? '1.0';

switch ($version) {
    case '1.0':
        require __DIR__ . '/v1/devices.php';
        break;
    
    case '2.0':
        require __DIR__ . '/v2/devices.php';
        break;
    
    default:
        sendError('Unsupported API version', 'SYS_9001', 400);
}
```

### **Deprecation Notice**

```php
<?php
// Old endpoint
header('X-API-Deprecated: true');
header('X-API-Sunset: 2026-12-31');
header('X-API-Replacement: /api/v2/devices.php');
```

---

## 📦 HELPER FUNCTIONS

```php
<?php
// File: /includes/functions.php

/**
 * Send success response
 */
function sendSuccess($data = [], $message = null) {
    $response = ['success' => true];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    if ($message) {
        $response['message'] = $message;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Send error response
 */
function sendError($message, $code = 'ERROR', $httpCode = 400) {
    http_response_code($httpCode);
    
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code
    ]);
    
    exit;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Format bytes
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
```

---

**END OF SECTION 13: API ENDPOINTS (Complete)**

**Status:** ✅ COMPLETE  
**Total Lines:** ~1,600 lines (Part 1 + Part 2)  
**Created:** January 15, 2026 - 6:40 AM CST

**Features Covered:**
- Complete API endpoint documentation
- Request/response formats
- Authentication & authorization
- JWT token implementation
- Database helpers
- Rate limiting
- CORS configuration
- Error handling
- Logging & monitoring
- Testing examples
- API versioning strategy

**Next Section:** Section 14 (Security)
