<?php
/**
 * TrueVault VPN - Admin Create Server API
 * 
 * PURPOSE: Create new VPN server (admin only)
 * METHOD: POST
 * AUTHENTICATION: JWT (admin or vip tier required)
 * 
 * REQUEST BODY:
 * {
 *   "name": "USA (Dallas)",
 *   "country": "United States",
 *   "country_code": "US",
 *   "region": "Texas",
 *   "endpoint": "66.241.124.4:51820",
 *   "provider": "fly.io",
 *   "public_key": "base64_public_key",
 *   "private_key": "base64_private_key",
 *   "max_users": 50,
 *   "status": "online",
 *   "is_dedicated": 0
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "Server created successfully",
 *   "server": {...}
 * }
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
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

// Load dependencies
require_once __DIR__ . '/../../../configs/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';

try {
    // Authenticate user
    $user = Auth::require();
    $userTier = $user['tier'] ?? 'standard';
    
    // Check admin access
    if (!in_array($userTier, ['admin', 'vip'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. Admin or VIP access required.'
        ]);
        exit;
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Validate required fields
    $required = ['name', 'country', 'country_code', 'region', 'endpoint', 'provider', 'max_users'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    // Extract data
    $name = trim($input['name']);
    $country = trim($input['country']);
    $countryCode = strtoupper(trim($input['country_code']));
    $region = trim($input['region']);
    $endpoint = trim($input['endpoint']);
    $provider = trim($input['provider']);
    $maxUsers = (int)$input['max_users'];
    $status = $input['status'] ?? 'online';
    $isDedicated = isset($input['is_dedicated']) ? (int)$input['is_dedicated'] : 0;
    
    // Validate country code (2 letters)
    if (strlen($countryCode) !== 2) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Country code must be 2 letters (e.g., US, CA, GB)'
        ]);
        exit;
    }
    
    // Validate endpoint format (IP:Port)
    if (!preg_match('/^[\d\.]+:\d+$/', $endpoint)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid endpoint format. Use IP:Port (e.g., 66.241.124.4:51820)'
        ]);
        exit;
    }
    
    // Validate status
    $validStatuses = ['online', 'offline', 'maintenance'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid status. Must be: ' . implode(', ', $validStatuses)
        ]);
        exit;
    }
    
    // Generate WireGuard keypair if not provided
    if (isset($input['public_key']) && isset($input['private_key'])) {
        $publicKey = trim($input['public_key']);
        $privateKey = trim($input['private_key']);
    } else {
        // Generate new keypair
        $keypair = sodium_crypto_box_keypair();
        $privateKeyRaw = sodium_crypto_box_secretkey($keypair);
        $publicKeyRaw = sodium_crypto_box_publickey($keypair);
        $privateKey = base64_encode($privateKeyRaw);
        $publicKey = base64_encode($publicKeyRaw);
        sodium_memzero($keypair);
    }
    
    // Get database connection
    $db = Database::getInstance();
    $serversConn = $db->getConnection('servers');
    
    // Check if endpoint already exists
    $stmt = $serversConn->prepare("SELECT server_id FROM servers WHERE endpoint = ?");
    $stmt->execute([$endpoint]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Server with this endpoint already exists'
        ]);
        exit;
    }
    
    // Default IP pool (10.8.0.x)
    $ipPoolStart = '10.8.0.2';
    $ipPoolEnd = '10.8.0.254';
    $ipPoolCurrent = '10.8.0.2';
    
    // Insert server
    $stmt = $serversConn->prepare("
        INSERT INTO servers (
            name, country, country_code, region, endpoint, provider,
            public_key, private_key, max_users, status, is_dedicated,
            ip_pool_start, ip_pool_end, ip_pool_current, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
    ");
    
    $stmt->execute([
        $name,
        $country,
        $countryCode,
        $region,
        $endpoint,
        $provider,
        $publicKey,
        $privateKey,
        $maxUsers,
        $status,
        $isDedicated,
        $ipPoolStart,
        $ipPoolEnd,
        $ipPoolCurrent
    ]);
    
    $serverId = $serversConn->lastInsertId();
    
    // Get created server
    $stmt = $serversConn->prepare("
        SELECT server_id, name, country, country_code, region, endpoint,
               provider, public_key, max_users, status, is_dedicated, created_at
        FROM servers
        WHERE server_id = ?
    ");
    $stmt->execute([$serverId]);
    $newServer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Server created successfully',
        'server' => $newServer
    ]);
    
    // Log creation
    error_log("Admin created server: $name by {$user['email']}");
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Admin Create Server Error: ' . $e->getMessage());
}
