<?php
/**
 * TrueVault VPN - Add Message to Support Ticket API
 * POST /api/support/message.php
 */

require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

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

try {
    // Verify authentication
    $auth = new Auth();
    $user = $auth->validateToken();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $ticketId = (int)($input['ticket_id'] ?? 0);
    $message = trim($input['message'] ?? '');
    
    if (!$ticketId || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ticket ID and message are required']);
        exit;
    }
    
    $db = new Database('support');
    
    // Verify ticket ownership and status
    $stmt = $db->prepare("
        SELECT id, status FROM support_tickets 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$ticketId, $user['id']]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }
    
    // Check if ticket is closed
    if ($ticket['status'] === 'closed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cannot reply to closed ticket']);
        exit;
    }
    
    // Add message
    $stmt = $db->prepare("
        INSERT INTO ticket_messages (ticket_id, user_id, is_staff, message)
        VALUES (?, ?, 0, ?)
    ");
    $stmt->execute([$ticketId, $user['id'], $message]);
    
    $messageId = $db->lastInsertId();
    
    // Update ticket timestamp and set to open if was resolved
    $newStatus = ($ticket['status'] === 'resolved') ? 'open' : $ticket['status'];
    
    $stmt = $db->prepare("
        UPDATE support_tickets 
        SET updated_at = datetime('now'), status = ?
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $ticketId]);
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'message' => 'Reply added successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
