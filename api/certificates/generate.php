<?php
/**
 * TrueVault VPN - Generate Certificate API
 * Generate a new certificate for user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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

$input = json_decode(file_get_contents('php://input'), true);

$type = $input['type'] ?? 'device';
$name = $input['name'] ?? '';

$validTypes = ['root', 'device', 'regional', 'mesh'];
if (!in_array($type, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid certificate type']);
    exit;
}

// Default names by type
$defaultNames = [
    'root' => 'Root Certificate',
    'device' => 'Device Certificate',
    'regional' => 'Regional Identity',
    'mesh' => 'Mesh Trust Certificate'
];

if (empty($name)) {
    $name = $defaultNames[$type] . ' - ' . date('M j, Y');
}

try {
    $db = getDatabase('certificates');
    
    // Check certificate limits based on plan
    $userDb = getDatabase('users');
    $stmt = $userDb->prepare("SELECT plan, is_vip FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $limits = [
        'basic' => ['root' => 1, 'device' => 3, 'regional' => 3, 'mesh' => 0],
        'family' => ['root' => 1, 'device' => 10, 'regional' => 10, 'mesh' => 6],
        'dedicated' => ['root' => 1, 'device' => 25, 'regional' => 25, 'mesh' => 20]
    ];
    
    if (!$userData['is_vip']) {
        $plan = $userData['plan'] ?? 'basic';
        $limit = $limits[$plan][$type] ?? 0;
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM certificates WHERE user_id = ? AND type = ?");
        $stmt->execute([$user['id'], $type]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count >= $limit) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Certificate limit reached for {$type} type"]);
            exit;
        }
    }
    
    // Generate certificate (simplified - in production would use OpenSSL)
    $fingerprint = strtoupper(bin2hex(random_bytes(20)));
    $fingerprint = implode(':', str_split($fingerprint, 2));
    
    $stmt = $db->prepare("INSERT INTO certificates (user_id, type, name, fingerprint, status, expires_at, created_at) VALUES (?, ?, ?, ?, 'active', datetime('now', '+1 year'), datetime('now'))");
    $stmt->execute([$user['id'], $type, $name, $fingerprint]);
    
    $certId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'certificate' => [
            'id' => $certId,
            'type' => $type,
            'name' => $name,
            'fingerprint' => $fingerprint,
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
        ],
        'message' => 'Certificate generated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
