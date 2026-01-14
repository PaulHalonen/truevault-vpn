<?php
/**
 * TrueVault VPN - Scanner Token API
 * Generate/retrieve auth token for network scanner
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

// Verify user token
$user = verifyToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = getDatabase('scanner');
    
    // Check for existing token
    $stmt = $db->prepare("SELECT token, created_at FROM scanner_tokens WHERE user_id = ? AND expires_at > datetime('now')");
    $stmt->execute([$user['id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing && $_SERVER['REQUEST_METHOD'] === 'GET') {
        // Return existing valid token
        echo json_encode([
            'success' => true,
            'token' => $existing['token'],
            'created_at' => $existing['created_at']
        ]);
        exit;
    }
    
    // Generate new token
    $token = bin2hex(random_bytes(32));
    
    // Delete old tokens
    $stmt = $db->prepare("DELETE FROM scanner_tokens WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    
    // Insert new token (valid for 24 hours)
    $stmt = $db->prepare("INSERT INTO scanner_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, datetime('now', '+24 hours'), datetime('now'))");
    $stmt->execute([$user['id'], $token]);
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'expires_in' => '24 hours',
        'message' => 'New token generated'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
