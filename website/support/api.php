<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Create ticket
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $category = $_POST['category'] ?? 'general';
    
    if (empty($email) || empty($subject) || empty($message)) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    // Get or create user by email
    $db = getSupportDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    $userId = $user ? $user['id'] : null;
    
    // Create ticket
    $result = createTicket($userId, $subject, $message, $category);
    
    // Handle file upload if present
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        handleTicketUpload($_FILES['attachment'], $result['ticket_id']);
    }
    
    jsonResponse([
        'success' => true,
        'ticket_id' => $result['ticket_id'],
        'ticket_number' => $result['ticket_number']
    ]);
}

// Get tickets
if ($action === 'list') {
    $status = $_GET['status'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    
    $tickets = getTickets($status, $userId);
    jsonResponse(['success' => true, 'tickets' => $tickets]);
}

// Get single ticket
if ($action === 'get') {
    $ticketId = $_GET['ticket_id'] ?? null;
    
    if (!$ticketId) {
        jsonResponse(['success' => false, 'error' => 'Missing ticket_id'], 400);
    }
    
    $ticket = getTicket($ticketId);
    
    if (!$ticket) {
        jsonResponse(['success' => false, 'error' => 'Ticket not found'], 404);
    }
    
    jsonResponse(['success' => true, 'ticket' => $ticket]);
}

// Add message to ticket
if ($action === 'reply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticket_id'] ?? null;
    $message = $_POST['message'] ?? '';
    $userId = $_POST['user_id'] ?? null;
    $adminId = $_POST['admin_id'] ?? null;
    $isInternal = $_POST['is_internal'] ?? 0;
    
    if (!$ticketId || empty($message)) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    $messageId = addTicketMessage($ticketId, $message, $userId, $adminId, $isInternal);
    
    // Handle file upload if present
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        handleTicketUpload($_FILES['attachment'], $ticketId, $messageId);
    }
    
    jsonResponse(['success' => true, 'message_id' => $messageId]);
}

// Update ticket status
if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticket_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$ticketId || !$status) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    $resolvedAt = ($status === 'resolved' || $status === 'closed') ? date('Y-m-d H:i:s') : null;
    updateTicketStatus($ticketId, $status, $resolvedAt);
    
    jsonResponse(['success' => true]);
}

// Get knowledge base articles
if ($action === 'kb_search') {
    $search = $_GET['search'] ?? null;
    $category = $_GET['category'] ?? null;
    
    $articles = getKBArticles($category, $search);
    jsonResponse(['success' => true, 'articles' => $articles]);
}

// Get canned responses
if ($action === 'canned_responses') {
    $category = $_GET['category'] ?? null;
    
    $responses = getCannedResponses($category);
    jsonResponse(['success' => true, 'responses' => $responses]);
}

// Default response
jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
?>
