<?php
/**
 * TrueVault VPN - List Support Tickets API
 * Task 7.9
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/JWT.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Verify authentication
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authentication required');
    }
    
    $payload = JWT::verify($matches[1]);
    if (!$payload) {
        throw new Exception('Invalid token');
    }
    
    $userId = $payload['user_id'];
    
    // Get filters
    $status = $_GET['status'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Build query
    $supportDb = dirname(DB_LOGS) . '/support.db';
    $db = new SQLite3($supportDb);
    $db->enableExceptions(true);
    
    $where = "user_id = {$userId}";
    if ($status) {
        $safeStatus = $db->escapeString($status);
        $where .= " AND status = '{$safeStatus}'";
    }
    
    // Get total count
    $total = $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE {$where}");
    
    // Get tickets
    $result = $db->query("
        SELECT id, ticket_number, subject, category, priority, status, created_at, updated_at, resolved_at
        FROM support_tickets 
        WHERE {$where}
        ORDER BY 
            CASE WHEN status = 'open' THEN 0 ELSE 1 END,
            CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END,
            created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    
    $tickets = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tickets[] = $row;
    }
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
