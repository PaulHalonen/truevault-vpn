<?php
/**
 * TrueVault VPN - Create Support Ticket API
 * Task 7.9
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/AutomationEngine.php';

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
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authentication required');
    }
    
    $payload = JWT::verify($matches[1]);
    if (!$payload) {
        throw new Exception('Invalid token');
    }
    
    $userId = $payload['user_id'];
    
    // Get user info
    $db = new SQLite3(DB_USERS);
    $user = $db->query("SELECT email, first_name, tier FROM users WHERE id = {$userId}")->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $subject = trim($input['subject'] ?? '');
    $description = trim($input['description'] ?? '');
    $category = $input['category'] ?? 'general';
    
    if (empty($subject) || empty($description)) {
        throw new Exception('Subject and description are required');
    }
    
    // Generate ticket number
    $ticketNumber = 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Determine priority
    $priority = ($user['tier'] === 'vip') ? 'high' : 'normal';
    
    // Create ticket
    $supportDb = dirname(DB_LOGS) . '/support.db';
    $db = new SQLite3($supportDb);
    $db->enableExceptions(true);
    
    $stmt = $db->prepare("
        INSERT INTO support_tickets (ticket_number, user_id, user_email, subject, description, category, priority)
        VALUES (:number, :user_id, :email, :subject, :desc, :category, :priority)
    ");
    
    $stmt->bindValue(':number', $ticketNumber, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':email', $user['email'], SQLITE3_TEXT);
    $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
    $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
    $stmt->bindValue(':category', $category, SQLITE3_TEXT);
    $stmt->bindValue(':priority', $priority, SQLITE3_TEXT);
    
    $stmt->execute();
    $ticketId = $db->lastInsertRowID();
    $db->close();
    
    // Trigger automation workflow
    AutomationEngine::trigger('ticket.created', [
        'ticket_id' => $ticketNumber,
        'user_id' => $userId,
        'email' => $user['email'],
        'first_name' => $user['first_name'] ?? 'Customer',
        'subject' => $subject,
        'description' => $description
    ]);
    
    echo json_encode([
        'success' => true,
        'ticket' => [
            'id' => $ticketId,
            'ticket_number' => $ticketNumber,
            'subject' => $subject,
            'status' => 'open',
            'priority' => $priority,
            'category' => $category
        ],
        'message' => 'Support ticket created successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
