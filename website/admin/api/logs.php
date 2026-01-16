<?php
/**
 * TrueVault VPN - Admin Logs API
 * 
 * GET ?type=activity   - Activity logs
 * GET ?type=errors     - Error logs
 * GET ?type=api        - API request logs
 * GET ?type=webhooks   - Webhook logs
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

// Verify admin token
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
if (!$payload || ($payload['type'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$logsDb = Database::getInstance('logs');

$type = $_GET['type'] ?? 'activity';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
$offset = ($page - 1) * $limit;

switch ($type) {
    case 'activity':
        $total = $logsDb->queryValue("SELECT COUNT(*) FROM activity_logs");
        $logs = $logsDb->queryAll(
            "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}"
        );
        break;
        
    case 'errors':
        $total = $logsDb->queryValue("SELECT COUNT(*) FROM error_logs");
        $logs = $logsDb->queryAll(
            "SELECT * FROM error_logs ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}"
        );
        break;
        
    case 'api':
        $total = $logsDb->queryValue("SELECT COUNT(*) FROM api_logs");
        $logs = $logsDb->queryAll(
            "SELECT * FROM api_logs ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}"
        );
        break;
        
    case 'webhooks':
        $total = $logsDb->queryValue("SELECT COUNT(*) FROM webhook_logs");
        $logs = $logsDb->queryAll(
            "SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}"
        );
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid log type']);
        exit;
}

echo json_encode([
    'success' => true,
    'type' => $type,
    'logs' => $logs,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => (int)$total,
        'pages' => ceil($total / $limit)
    ]
]);
