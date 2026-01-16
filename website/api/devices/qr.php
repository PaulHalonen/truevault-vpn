<?php
/**
 * TrueVault VPN - Device QR Code API
 * 
 * GET ?device_id=X - Generate QR code for device config
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify token
Auth::init(JWT_SECRET);

$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$payload = Auth::verifyToken($token);
if (!$payload) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$userId = $payload['user_id'];
$deviceId = $_GET['device_id'] ?? null;

if (!$deviceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Device ID required']);
    exit;
}

$devicesDb = Database::getInstance('devices');
$serversDb = Database::getInstance('servers');
$mainDb = Database::getInstance('main');

// Get device (verify ownership)
$device = $devicesDb->queryOne(
    "SELECT * FROM devices WHERE device_id = ? AND user_id = ?",
    [$deviceId, $userId]
);

if (!$device) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Device not found']);
    exit;
}

// Get server info
$server = $serversDb->queryOne("SELECT * FROM servers WHERE id = ?", [$device['current_server_id']]);

if (!$server) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server configuration error']);
    exit;
}

// Get DNS
$dns = $mainDb->queryValue("SELECT value FROM settings WHERE key = 'vpn_dns'") ?? '1.1.1.1, 8.8.8.8';

// Build WireGuard config string
// Note: Private key should be provided by the client, using placeholder here
$configString = "[Interface]\n";
$configString .= "PrivateKey = YOUR_PRIVATE_KEY\n";
$configString .= "Address = {$device['assigned_ip']}/32\n";
$configString .= "DNS = {$dns}\n\n";
$configString .= "[Peer]\n";
$configString .= "PublicKey = {$server['public_key']}\n";
$configString .= "Endpoint = {$server['ip_address']}:" . ($server['port'] ?? 51820) . "\n";
$configString .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
$configString .= "PersistentKeepalive = 25";

// Generate QR code using Google Charts API (simple solution)
// In production, use a local QR library
$qrData = urlencode($configString);
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $qrData;

// Alternatively, return base64 placeholder if external service not available
// For production, use phpqrcode or similar library

echo json_encode([
    'success' => true,
    'qr_code' => $qrUrl,
    'config_preview' => str_replace('YOUR_PRIVATE_KEY', '[Hidden]', $configString)
]);
