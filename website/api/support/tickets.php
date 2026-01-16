<?php
/**
 * TrueVault VPN - Support Tickets API
 * 
 * GET  - List user's tickets
 * POST - Create new ticket
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify token
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
if (!$payload) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$userId = $payload['user_id'];
$supportDb = Database::getInstance('support');
$usersDb = Database::getInstance('users');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // List tickets
        $tickets = $supportDb->queryAll(
            "SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
        
        echo json_encode(['success' => true, 'tickets' => $tickets]);
        break;
        
    case 'POST':
        // Create ticket
        $input = json_decode(file_get_contents('php://input'), true);
        
        $subject = trim($input['subject'] ?? '');
        $category = $input['category'] ?? 'other';
        $message = trim($input['message'] ?? '');
        
        if (!$subject || !$message) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Subject and message required']);
            exit;
        }
        
        // Generate ticket ID
        $ticketId = 'TKT' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        
        // Get user email
        $user = $usersDb->queryOne("SELECT email FROM users WHERE id = ?", [$userId]);
        
        $supportDb->insert('tickets', [
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'email' => $user['email'],
            'subject' => $subject,
            'category' => $category,
            'message' => $message,
            'status' => 'open',
            'priority' => 'normal',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Log activity
        $logsDb = Database::getInstance('logs');
        $logsDb->insert('activity_logs', [
            'user_id' => $userId,
            'action' => 'ticket_created',
            'details' => json_encode(['ticket_id' => $ticketId, 'category' => $category]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Ticket created',
            'ticket_id' => $ticketId
        ]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
