<?php
/**
 * TrueVault VPN - Gaming Controls API
 * Part 11 - Task 11.5
 * Gaming restriction management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

$user = authenticateRequest();
if (!$user) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'status';
$deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;

// Gaming port definitions
$GAMING_PORTS = [
    'xbox' => [3074, 3075, 3076, 53, 80, 88, 500, 3544, 4500],
    'playstation' => [3478, 3479, 3480, 5223, 8080, 9293, 9295],
    'steam' => range(27000, 27050),
    'nintendo' => [6667, 12400, 28910, 29900, 29901, 29920],
    'epic' => range(5795, 5847)
];

// Gaming domains
$GAMING_DOMAINS = [
    'xbox' => ['xbox.com', 'xboxlive.com', 'microsoft.com'],
    'playstation' => ['playstation.com', 'playstation.net', 'sonyentertainmentnetwork.com'],
    'steam' => ['steampowered.com', 'steamcommunity.com', 'steamcdn-a.akamaihd.net'],
    'nintendo' => ['nintendo.com', 'nintendo.net', 'nintendowifi.net'],
    'epic' => ['epicgames.com', 'epicgames.dev', 'fortnite.com']
];

try {
    $db = getDatabase();
    
    switch ($method) {
        case 'GET':
            if ($action === 'status') {
                // Get gaming restrictions for user/device
                $sql = "SELECT * FROM gaming_restrictions WHERE user_id = ?";
                $params = [$user['id']];
                if ($deviceId) { $sql .= " AND device_id = ?"; $params[] = $deviceId; }
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $restrictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Reset daily usage if new day
                foreach ($restrictions as &$r) {
                    if ($r['last_reset_date'] !== date('Y-m-d')) {
                        $r['minutes_used_today'] = 0;
                    }
                }
                
                echo json_encode(['success' => true, 'restrictions' => $restrictions, 'ports' => $GAMING_PORTS, 'domains' => $GAMING_DOMAINS]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'toggle') {
                // Quick toggle gaming on/off
                $platform = $input['platform'] ?? 'all';
                $enabled = isset($input['enabled']) ? ($input['enabled'] ? 1 : 0) : null;
                $devId = $input['device_id'] ?? null;
                
                // Check if record exists
                $stmt = $db->prepare("SELECT * FROM gaming_restrictions WHERE user_id = ? AND (device_id = ? OR (device_id IS NULL AND ? IS NULL))");
                $stmt->execute([$user['id'], $devId, $devId]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    if ($platform === 'all') {
                        $stmt = $db->prepare("UPDATE gaming_restrictions SET gaming_enabled = ?, last_toggled_at = CURRENT_TIMESTAMP, toggled_by = 'parent' WHERE id = ?");
                        $stmt->execute([$enabled, $existing['id']]);
                    } else {
                        $col = $platform . '_enabled';
                        $stmt = $db->prepare("UPDATE gaming_restrictions SET {$col} = ?, last_toggled_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$enabled, $existing['id']]);
                    }
                } else {
                    $stmt = $db->prepare("INSERT INTO gaming_restrictions (user_id, device_id, gaming_enabled, last_toggled_at, toggled_by) VALUES (?, ?, ?, CURRENT_TIMESTAMP, 'parent')");
                    $stmt->execute([$user['id'], $devId, $enabled ?? 1]);
                }
                
                echo json_encode(['success' => true, 'gaming_enabled' => $enabled]);
                
            } elseif ($action === 'block_temporary') {
                // Block gaming for X minutes
                $minutes = intval($input['minutes'] ?? 60);
                $devId = $input['device_id'] ?? null;
                $until = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
                
                $stmt = $db->prepare("
                    INSERT INTO gaming_restrictions (user_id, device_id, gaming_enabled, last_toggled_at, toggled_by) 
                    VALUES (?, ?, 0, CURRENT_TIMESTAMP, 'parent')
                    ON CONFLICT(user_id, device_id) DO UPDATE SET gaming_enabled = 0, last_toggled_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$user['id'], $devId]);
                
                // Store unblock time in device_rules override
                $stmt = $db->prepare("
                    INSERT INTO device_rules (user_id, device_id, override_enabled, override_type, override_until)
                    VALUES (?, ?, 1, 'gaming_blocked', ?)
                    ON CONFLICT(user_id, device_id) DO UPDATE SET override_enabled = 1, override_type = 'gaming_blocked', override_until = ?
                ");
                $stmt->execute([$user['id'], $devId, $until, $until]);
                
                echo json_encode(['success' => true, 'blocked_until' => $until, 'minutes' => $minutes]);
                
            } elseif ($action === 'extend') {
                // Extend gaming time by X minutes
                $minutes = intval($input['minutes'] ?? 60);
                $devId = $input['device_id'] ?? null;
                
                // Add to daily limit or create extension
                $stmt = $db->prepare("UPDATE gaming_restrictions SET daily_limit_minutes = COALESCE(daily_limit_minutes, 0) + ? WHERE user_id = ? AND (device_id = ? OR (device_id IS NULL AND ? IS NULL))");
                $stmt->execute([$minutes, $user['id'], $devId, $devId]);
                
                echo json_encode(['success' => true, 'extended_minutes' => $minutes]);
                
            } elseif ($action === 'set_limit') {
                // Set daily gaming limit
                $limit = intval($input['daily_limit_minutes']);
                $devId = $input['device_id'] ?? null;
                
                $stmt = $db->prepare("
                    INSERT INTO gaming_restrictions (user_id, device_id, daily_limit_minutes) VALUES (?, ?, ?)
                    ON CONFLICT(user_id, device_id) DO UPDATE SET daily_limit_minutes = ?
                ");
                $stmt->execute([$user['id'], $devId, $limit, $limit]);
                
                echo json_encode(['success' => true, 'daily_limit_minutes' => $limit]);
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
