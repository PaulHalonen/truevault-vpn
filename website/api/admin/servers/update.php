<?php
/**
 * TrueVault VPN - Admin Update Server API
 * 
 * PURPOSE: Update existing VPN server (admin only)
 * METHOD: POST
 * AUTHENTICATION: JWT (admin or vip tier required)
 * 
 * REQUEST BODY:
 * {
 *   "server_id": 1,
 *   "name": "USA (Dallas)",
 *   "country": "United States",
 *   "country_code": "US",
 *   "region": "Texas",
 *   "endpoint": "66.241.124.4:51820",
 *   "provider": "fly.io",
 *   "max_users": 50,
 *   "status": "online",
 *   "is_dedicated": 0
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "Server updated successfully",
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
    $required = ['server_id', 'name', 'country', 'country_code', 'region', 'endpoint', 'provider', 'max_users', 'status'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    // Extract data
    $serverId = (int)$input['server_id'];
    $name = trim($input['name']);
    $country = trim($input['country']);
    $countryCode = strtoupper(trim($input['country_code']));
    $region = trim($input['region']);
    $endpoint = trim($input['endpoint']);
    $provider = trim($input['provider']);
    $maxUsers = (int)$input['max_users'];
    $status = trim($input['status']);
    $isDedicated = isset($input['is_dedicated']) ? (int)$input['is_dedicated'] : 0;
    
    // Validate country code
    if (strlen($countryCode) !== 2) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Country code must be 2 letters'
        ]);
        exit;
    }
    
    // Validate endpoint format
    if (!preg_match('/^[\d\.]+:\d+$/', $endpoint)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid endpoint format. Use IP:Port'
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
    
    // Get database connection
    $db = Database::getInstance();
    $serversConn = $db->getConnection('servers');
    
    // Check if server exists
    $stmt = $serversConn->prepare("SELECT server_id FROM servers WHERE server_id = ?");
    $stmt->execute([$serverId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Server not found'
        ]);
        exit;
    }
    
    // Check if endpoint is taken by another server
    $stmt = $serversConn->prepare("SELECT server_id FROM servers WHERE endpoint = ? AND server_id != ?");
    $stmt->execute([$endpoint, $serverId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint already in use by another server'
        ]);
        exit;
    }
    
    // Update server
    $stmt = $serversConn->prepare("
        UPDATE servers
        SET name = ?,
            country = ?,
            country_code = ?,
            region = ?,
            endpoint = ?,
            provider = ?,
            max_users = ?,
            status = ?,
            is_dedicated = ?
        WHERE server_id = ?
    ");
    
    $stmt->execute([
        $name,
        $country,
        $countryCode,
        $region,
        $endpoint,
        $provider,
        $maxUsers,
        $status,
        $isDedicated,
        $serverId
    ]);
    
    // Get updated server
    $stmt = $serversConn->prepare("
        SELECT server_id, name, country, country_code, region, endpoint,
               provider, public_key, max_users, status, is_dedicated, created_at
        FROM servers
        WHERE server_id = ?
    ");
    $stmt->execute([$serverId]);
    $updatedServer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Server updated successfully',
        'server' => $updatedServer
    ]);
    
    // Log update
    error_log("Admin updated server: $name by {$user['email']}");
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Admin Update Server Error: ' . $e->getMessage());
}
