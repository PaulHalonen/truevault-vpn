<?php
/**
 * TrueVault VPN - Add Message to Support Ticket
 * Task 7.9
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

$ticketId = $input['ticket_id'] ?? null;
$message = trim($input['message'] ?? '');
$userId = $input['user_id'] ?? null;
$isStaff = $input['is_staff'] ?? false;
$senderName = $input['sender_name'] ?? 'User';

// Validate
if (!$ticketId || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ticket ID and message required']);
    exit;
}

try {
    $supportDb = dirname(DB_LOGS) . '/support.db';
    $db = new SQLite3($supportDb);
    $db->enableExceptions(true);
    
    // Verify ticket exists
    $checkStmt = $db->prepare("SELECT id, status FROM support_tickets WHERE id = :id");
    $checkStmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
    $ticket = $checkStmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }
    
    // Insert message
    $stmt = $db->prepare("
        INSERT INTO ticket_messages (ticket_id, user_id, is_staff, sender_name, message, created_at)
        VALUES (:ticket_id, :user_id, :is_staff, :sender_name, :message, datetime('now'))
    ");
    $stmt->bindValue(':ticket_id', $ticketId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, $userId ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':is_staff', $isStaff ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(':sender_name', $senderName, SQLITE3_TEXT);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->execute();
    
    $messageId = $db->lastInsertRowID();
    
    // Update ticket updated_at
    $updateStmt = $db->prepare("UPDATE support_tickets SET updated_at = datetime('now') WHERE id = :id");
    $updateStmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
    $updateStmt->execute();
    
    // If staff reply and ticket was waiting, change status to in_progress
    if ($isStaff && $ticket['status'] === 'open') {
        $statusStmt = $db->prepare("UPDATE support_tickets SET status = 'in_progress' WHERE id = :id");
        $statusStmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
        $statusStmt->execute();
    }
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'message' => 'Reply added successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
