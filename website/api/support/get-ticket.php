<?php
/**
 * TrueVault VPN - Get Support Ticket API
 * Task 7.9
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get ticket ID
$ticketId = $_GET['id'] ?? null;
$ticketNumber = $_GET['ticket_number'] ?? null;

if (!$ticketId && !$ticketNumber) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ticket ID or number required']);
    exit;
}

try {
    $supportDb = dirname(DB_LOGS) . '/support.db';
    $db = new SQLite3($supportDb);
    $db->enableExceptions(true);
    
    // Get ticket
    if ($ticketId) {
        $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = :id");
        $stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
    } else {
        $stmt = $db->prepare("SELECT * FROM support_tickets WHERE ticket_number = :num");
        $stmt->bindValue(':num', $ticketNumber, SQLITE3_TEXT);
    }
    
    $result = $stmt->execute();
    $ticket = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }
    
    // Get messages
    $msgStmt = $db->prepare("
        SELECT * FROM ticket_messages 
        WHERE ticket_id = :ticket_id 
        ORDER BY created_at ASC
    ");
    $msgStmt->bindValue(':ticket_id', $ticket['id'], SQLITE3_INTEGER);
    $msgResult = $msgStmt->execute();
    
    $messages = [];
    while ($msg = $msgResult->fetchArray(SQLITE3_ASSOC)) {
        $messages[] = [
            'id' => $msg['id'],
            'sender_name' => $msg['sender_name'],
            'is_staff' => (bool)$msg['is_staff'],
            'message' => $msg['message'],
            'attachments' => $msg['attachments'] ? json_decode($msg['attachments'], true) : [],
            'created_at' => $msg['created_at']
        ];
    }
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'ticket' => [
            'id' => $ticket['id'],
            'ticket_number' => $ticket['ticket_number'],
            'subject' => $ticket['subject'],
            'description' => $ticket['description'],
            'category' => $ticket['category'],
            'priority' => $ticket['priority'],
            'status' => $ticket['status'],
            'created_at' => $ticket['created_at'],
            'updated_at' => $ticket['updated_at'],
            'resolved_at' => $ticket['resolved_at']
        ],
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
