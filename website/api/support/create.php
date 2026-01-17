<?php
/**
 * TrueVault VPN - Create Support Ticket API
 * POST /api/support/create.php
 */

require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Workflows.php';

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
    
    $subject = trim($input['subject'] ?? '');
    $description = trim($input['description'] ?? $input['message'] ?? '');
    $category = $input['category'] ?? null;
    
    // Validate
    if (empty($subject) || empty($description)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Subject and description are required']);
        exit;
    }
    
    // Initialize support database
    $db = new Database('support');
    
    // Ensure tables exist
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
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS ticket_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            user_id INTEGER,
            is_staff INTEGER DEFAULT 0,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
        )
    ");
    
    // Create ticket
    $stmt = $db->prepare("
        INSERT INTO support_tickets (user_id, subject, description, category)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $subject,
        $description,
        $category
    ]);
    
    $ticketId = $db->lastInsertId();
    
    // Add initial message
    $stmt = $db->prepare("
        INSERT INTO ticket_messages (ticket_id, user_id, is_staff, message)
        VALUES (?, ?, 0, ?)
    ");
    $stmt->execute([$ticketId, $user['id'], $description]);
    
    // Trigger workflow
    try {
        $workflows = new Workflows();
        $workflows->supportTicketCreated(
            $ticketId,
            $user['id'],
            $user['email'],
            $user['name'] ?? 'Customer',
            $subject,
            $description
        );
    } catch (Exception $e) {
        // Workflow error shouldn't fail ticket creation
        error_log("Workflow error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'ticket_id' => $ticketId,
        'message' => 'Support ticket created successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
