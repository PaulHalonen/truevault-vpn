<?php
/**
 * TrueVault VPN - Gaming Restrictions API
 * 
 * Controls access to gaming servers (Xbox, PlayStation, Steam, Nintendo)
 * 
 * Endpoints:
 * GET    /api/parental/gaming.php - Get gaming restrictions
 * POST   /api/parental/gaming.php - Block/unblock gaming platform
 * PUT    /api/parental/gaming.php - Update restrictions
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$auth = Auth::authenticate();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $auth['user']['id'];
$method = $_SERVER['REQUEST_METHOD'];

// Gaming server domains to block
$GAMING_SERVERS = [
    'xbox' => [
        'xbox.com', 'xboxlive.com', 'xbox-service.com', 'xboxab.com',
        'xbl.io', 'xboxservices.com', 'live.com'
    ],
    'playstation' => [
        'playstation.com', 'playstation.net', 'sonyentertainmentnetwork.com',
        'psnprofiles.com', 'sony.com', 'snei.com'
    ],
    'steam' => [
        'steampowered.com', 'steamcommunity.com', 'steamstatic.com',
        'steamgames.com', 'steamusercontent.com', 'valvesoftware.com'
    ],
    'nintendo' => [
        'nintendo.com', 'nintendo.net', 'nintendowifi.net',
        'noa.com', 'nintendoswitch.com'
    ]
];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection('parental');
    
    switch ($method) {
        case 'GET':
            handleGet($conn, $userId);
            break;
        case 'POST':
            handlePost($conn, $userId, $GAMING_SERVERS);
            break;
        case 'PUT':
            handlePut($conn, $userId, $GAMING_SERVERS);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

function handleGet($conn, $userId) {
    // Get all gaming restrictions
    $stmt = $conn->prepare("
        SELECT * FROM gaming_restrictions 
        WHERE user_id = ?
        ORDER BY platform
    ");
    $stmt->execute([$userId]);
    $restrictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize default platforms if not exists
    $platforms = ['xbox', 'playstation', 'steam', 'nintendo'];
    $existing = array_column($restrictions, 'platform');
    
    foreach ($platforms as $platform) {
        if (!in_array($platform, $existing)) {
            $restrictions[] = [
                'platform' => $platform,
                'is_blocked' => false,
                'daily_limit_minutes' => null
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'restrictions' => $restrictions
    ]);
}

function handlePost($conn, $userId, $gamingServers) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['platform']) || !isset($input['is_blocked'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Platform and is_blocked required']);
        return;
    }
    
    $platform = strtolower($input['platform']);
    $isBlocked = (bool)$input['is_blocked'];
    $deviceId = $input['device_id'] ?? null;
    
    // Validate platform
    if (!isset($gamingServers[$platform])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid platform']);
        return;
    }
    
    // Upsert restriction
    $stmt = $conn->prepare("
        INSERT INTO gaming_restrictions 
        (user_id, device_id, platform, is_blocked, last_toggled_at, toggled_by)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?)
        ON CONFLICT(user_id, device_id, platform) DO UPDATE SET
            is_blocked = excluded.is_blocked,
            last_toggled_at = CURRENT_TIMESTAMP,
            toggled_by = excluded.toggled_by
    ");
    
    $stmt->execute([
        $userId,
        $deviceId,
        $platform,
        $isBlocked ? 1 : 0,
        'parent'
    ]);
    
    // Add/remove domains from blocked list
    $parentalConn = $conn;
    $domains = $gamingServers[$platform];
    
    if ($isBlocked) {
        // Add domains to blocked_domains
        foreach ($domains as $domain) {
            $stmt = $parentalConn->prepare("
                INSERT OR IGNORE INTO blocked_domains 
                (user_id, domain, category, added_by, added_at)
                VALUES (?, ?, 'gaming', 'auto_gaming', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$userId, $domain]);
        }
    } else {
        // Remove domains from blocked_domains
        foreach ($domains as $domain) {
            $stmt = $parentalConn->prepare("
                DELETE FROM blocked_domains 
                WHERE user_id = ? AND domain = ? AND added_by = 'auto_gaming'
            ");
            $stmt->execute([$userId, $domain]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($platform) . ' ' . ($isBlocked ? 'blocked' : 'unblocked'),
        'domains_affected' => count($domains)
    ]);
}

function handlePut($conn, $userId, $gamingServers) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['platform'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Platform required']);
        return;
    }
    
    $platform = strtolower($input['platform']);
    $deviceId = $input['device_id'] ?? null;
    
    // Update daily limit
    $stmt = $conn->prepare("
        UPDATE gaming_restrictions 
        SET daily_limit_minutes = ?,
            notes = ?
        WHERE user_id = ? 
        AND platform = ?
        AND (device_id = ? OR (device_id IS NULL AND ? IS NULL))
    ");
    
    $stmt->execute([
        $input['daily_limit_minutes'] ?? null,
        $input['notes'] ?? null,
        $userId,
        $platform,
        $deviceId,
        $deviceId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Gaming restriction updated'
    ]);
}
?>
