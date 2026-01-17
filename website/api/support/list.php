<?php
/**
 * TrueVault VPN - List Support Tickets API
 * GET /api/support/list.php
 */

require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Verify authentication
    $auth = new Auth();
    $user = $auth->validateToken();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    $db = new Database('support');
    
    // Ensure table exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            description TEXT NOT NULL,
            category TEXT,
            priority TEXT NOT NULL DEFAULT 'normal',
            status TEXT NOT NULL DEFAULT 'open',
            assigned_to TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME
        )
    ");
    
    // Get filter parameters
    $status = $_GET['status'] ?? null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Build query
    $where = "WHERE user_id = ?";
    $params = [$user['id']];
    
    if ($status && in_array($status, ['open', 'pending', 'resolved', 'closed'])) {
        $where .= " AND status = ?";
        $params[] = $status;
    }
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM support_tickets $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // Get tickets
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare("
        SELECT id, subject, category, priority, status, created_at, updated_at, resolved_at
        FROM support_tickets 
        $where
        ORDER BY 
            CASE status 
                WHEN 'open' THEN 1 
                WHEN 'pending' THEN 2 
                WHEN 'resolved' THEN 3 
                ELSE 4 
            END,
            updated_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get message counts for each ticket
    foreach ($tickets as &$ticket) {
        $msgStmt = $db->prepare("SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = ?");
        $msgStmt->execute([$ticket['id']]);
        $ticket['message_count'] = (int)$msgStmt->fetchColumn();
    }
    
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
