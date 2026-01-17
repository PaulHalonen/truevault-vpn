<?php
/**
 * TrueVault VPN - Get Support Ticket Details API
 * GET /api/support/get.php?id=123
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
    
    $ticketId = (int)($_GET['id'] ?? 0);
    
    if (!$ticketId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
        exit;
    }
    
    $db = new Database('support');
    
    // Get ticket (verify ownership)
    $stmt = $db->prepare("
        SELECT * FROM support_tickets 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$ticketId, $user['id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }
    
    // Get messages
    $stmt = $db->prepare("
        SELECT id, user_id, is_staff, message, created_at
        FROM ticket_messages 
        WHERE ticket_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$ticketId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format messages
    foreach ($messages as &$msg) {
        $msg['is_staff'] = (bool)$msg['is_staff'];
        $msg['sender'] = $msg['is_staff'] ? 'TrueVault Support' : 'You';
    }
    
    echo json_encode([
        'success' => true,
        'ticket' => $ticket,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
