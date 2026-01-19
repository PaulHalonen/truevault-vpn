<?php
// Support Ticket System Configuration
// USES: SQLite3 class (NOT PDO)
define('SUPPORT_DB_PATH', __DIR__ . '/../databases/main.db');
define('UPLOAD_DIR', __DIR__ . '/../uploads/tickets/');

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

function getSupportDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new SQLite3(SUPPORT_DB_PATH);
            $db->enableExceptions(true);
            $db->busyTimeout(5000);
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $db;
}

// Generate unique ticket number
function generateTicketNumber() {
    return 'TKT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

// Create support ticket
function createTicket($userId, $subject, $message, $category = 'general') {
    $db = getSupportDB();
    $ticketNumber = generateTicketNumber();
    
    // Determine priority based on keywords
    $priority = 'normal';
    $urgentKeywords = ['urgent', 'emergency', 'critical', 'asap', 'immediately'];
    foreach ($urgentKeywords as $keyword) {
        if (stripos($subject . ' ' . $message, $keyword) !== false) {
            $priority = 'urgent';
            break;
        }
    }
    
    // Create ticket
    $stmt = $db->prepare("
        INSERT INTO support_tickets (user_id, ticket_number, subject, category, priority)
        VALUES (:user_id, :ticket_number, :subject, :category, :priority)
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':ticket_number', $ticketNumber, SQLITE3_TEXT);
    $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
    $stmt->bindValue(':category', $category, SQLITE3_TEXT);
    $stmt->bindValue(':priority', $priority, SQLITE3_TEXT);
    $stmt->execute();
    $ticketId = $db->lastInsertRowID();
    
    // Add initial message
    $stmt = $db->prepare("
        INSERT INTO ticket_messages (ticket_id, user_id, message)
        VALUES (:ticket_id, :user_id, :message)
    ");
    $stmt->bindValue(':ticket_id', $ticketId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->execute();
    
    return ['ticket_id' => $ticketId, 'ticket_number' => $ticketNumber];
}

// Get tickets
function getTickets($status = null, $userId = null, $limit = 50) {
    $db = getSupportDB();
    
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "status = :status";
        $params[':status'] = [$status, SQLITE3_TEXT];
    }
    
    if ($userId) {
        $where[] = "user_id = :user_id";
        $params[':user_id'] = [$userId, SQLITE3_INTEGER];
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $db->prepare("
        SELECT st.*, u.email, u.first_name, u.last_name
        FROM support_tickets st
        LEFT JOIN users u ON st.user_id = u.id
        $whereClause
        ORDER BY 
            CASE st.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'normal' THEN 3
                WHEN 'low' THEN 4
            END,
            st.created_at DESC
        LIMIT :limit
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value[0], $value[1]);
    }
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $tickets = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tickets[] = $row;
    }
    return $tickets;
}

// Get single ticket with messages
function getTicket($ticketId) {
    $db = getSupportDB();
    
    // Get ticket
    $stmt = $db->prepare("
        SELECT st.*, u.email, u.first_name, u.last_name
        FROM support_tickets st
        LEFT JOIN users u ON st.user_id = u.id
        WHERE st.id = :id
    ");
    $stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $ticket = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$ticket) {
        return null;
    }
    
    // Get messages
    $stmt = $db->prepare("
        SELECT tm.*, 
               u.email as user_email, u.first_name as user_first_name,
               a.email as admin_email, a.name as admin_name
        FROM ticket_messages tm
        LEFT JOIN users u ON tm.user_id = u.id
        LEFT JOIN admin_users a ON tm.admin_id = a.id
        WHERE tm.ticket_id = :id
        ORDER BY tm.created_at ASC
    ");
    $stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $messages = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $messages[] = $row;
    }
    $ticket['messages'] = $messages;
    
    return $ticket;
}

// Add message to ticket
function addTicketMessage($ticketId, $message, $userId = null, $adminId = null, $isInternal = 0) {
    $db = getSupportDB();
    
    $stmt = $db->prepare("
        INSERT INTO ticket_messages (ticket_id, user_id, admin_id, message, is_internal)
        VALUES (:ticket_id, :user_id, :admin_id, :message, :is_internal)
    ");
    $stmt->bindValue(':ticket_id', $ticketId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':admin_id', $adminId, SQLITE3_INTEGER);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->bindValue(':is_internal', $isInternal, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Update ticket timestamp
    $stmt = $db->prepare("UPDATE support_tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

// Update ticket status
function updateTicketStatus($ticketId, $status, $resolvedAt = null) {
    $db = getSupportDB();
    
    if ($resolvedAt) {
        $stmt = $db->prepare("
            UPDATE support_tickets 
            SET status = :status, resolved_at = :resolved_at, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':resolved_at', $resolvedAt, SQLITE3_TEXT);
        $stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            UPDATE support_tickets 
            SET status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':id', $ticketId, SQLITE3_INTEGER);
        $stmt->execute();
    }
}

// Get knowledge base articles
function getKBArticles($category = null, $search = null) {
    $db = getSupportDB();
    
    $where = ['is_published = 1'];
    $params = [];
    
    if ($category) {
        $where[] = "category = :category";
        $params[':category'] = [$category, SQLITE3_TEXT];
    }
    
    if ($search) {
        $where[] = "(title LIKE :search1 OR content LIKE :search2 OR tags LIKE :search3)";
        $searchTerm = "%$search%";
        $params[':search1'] = [$searchTerm, SQLITE3_TEXT];
        $params[':search2'] = [$searchTerm, SQLITE3_TEXT];
        $params[':search3'] = [$searchTerm, SQLITE3_TEXT];
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    
    $stmt = $db->prepare("
        SELECT * FROM knowledge_base
        $whereClause
        ORDER BY views DESC, helpful_count DESC
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value[0], $value[1]);
    }
    
    $result = $stmt->execute();
    $articles = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $articles[] = $row;
    }
    return $articles;
}

// Get canned responses
function getCannedResponses($category = null) {
    $db = getSupportDB();
    
    if ($category) {
        $stmt = $db->prepare("SELECT * FROM canned_responses WHERE category = :category ORDER BY usage_count DESC");
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $result = $stmt->execute();
    } else {
        $result = $db->query("SELECT * FROM canned_responses ORDER BY category, title");
    }
    
    $responses = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $responses[] = $row;
    }
    return $responses;
}

// Get support statistics
function getSupportStats() {
    $db = getSupportDB();
    
    $stats = [];
    
    // Total tickets
    $result = $db->query("SELECT COUNT(*) as count FROM support_tickets");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_tickets'] = $row['count'];
    
    // Open tickets
    $result = $db->query("SELECT COUNT(*) as count FROM support_tickets WHERE status IN ('open', 'in_progress', 'waiting')");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['open_tickets'] = $row['count'];
    
    // Resolved today
    $result = $db->query("SELECT COUNT(*) as count FROM support_tickets WHERE status = 'resolved' AND DATE(resolved_at) = DATE('now')");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['resolved_today'] = $row['count'];
    
    // Average response time (placeholder)
    $stats['avg_response_time'] = '2.5 hours';
    
    return $stats;
}

// Handle file upload
function handleTicketUpload($file, $ticketId, $messageId = null) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        return ['success' => false, 'error' => 'File too large (max 10MB)'];
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $db = getSupportDB();
        $stmt = $db->prepare("
            INSERT INTO ticket_attachments (ticket_id, message_id, filename, filepath, filesize, mime_type)
            VALUES (:ticket_id, :message_id, :filename, :filepath, :filesize, :mime_type)
        ");
        $stmt->bindValue(':ticket_id', $ticketId, SQLITE3_INTEGER);
        $stmt->bindValue(':message_id', $messageId, SQLITE3_INTEGER);
        $stmt->bindValue(':filename', $file['name'], SQLITE3_TEXT);
        $stmt->bindValue(':filepath', $filepath, SQLITE3_TEXT);
        $stmt->bindValue(':filesize', $file['size'], SQLITE3_INTEGER);
        $stmt->bindValue(':mime_type', $file['type'], SQLITE3_TEXT);
        $stmt->execute();
        
        return ['success' => true, 'attachment_id' => $db->lastInsertRowID()];
    }
    
    return ['success' => false, 'error' => 'Failed to save file'];
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
