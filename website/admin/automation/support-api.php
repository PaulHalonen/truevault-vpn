<?php
/**
 * TrueVault VPN - Support Automation API
 * Task 17.11: REST API for Support System
 * Created: January 24, 2026
 * 
 * Endpoints:
 * - GET ?action=conversation&ticket_id=X - Get ticket conversation
 * - GET ?action=ticket&id=X - Get single ticket details
 * - GET ?action=tickets - List tickets with filters
 * - POST ?action=create_ticket - Create new ticket
 * - POST ?action=add_response - Add response to ticket
 * - GET ?action=suggest_kb&content=X - Get KB suggestions
 * - GET ?action=suggest_canned&content=X - Get canned suggestions
 * - GET ?action=stats - Get support statistics
 * - POST ?action=feedback - Record resolution feedback
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
$dbPath = __DIR__ . '/databases/automation.db';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database not found']);
    exit;
}

$db = new SQLite3($dbPath);

// Include resolver classes
require_once __DIR__ . '/knowledge-base.php';
require_once __DIR__ . '/canned-responses.php';

$kbResolver = new KnowledgeBaseResolver($db);
$cannedSuggester = new CannedResponseSuggester($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    
    // ==================== GET CONVERSATION ====================
    case 'conversation':
        $ticketId = intval($_GET['ticket_id'] ?? 0);
        if (!$ticketId) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            exit;
        }
        
        // Get ticket
        $ticket = $db->querySingle("SELECT * FROM support_tickets WHERE id = $ticketId", true);
        if (!$ticket) {
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
            exit;
        }
        
        // Get responses
        $responses = [];
        $result = $db->query("SELECT * FROM ticket_responses WHERE ticket_id = $ticketId ORDER BY created_at ASC");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $responses[] = $row;
        }
        
        // Add original message as first item
        array_unshift($responses, [
            'id' => 0,
            'ticket_id' => $ticketId,
            'sender_type' => 'customer',
            'message' => $ticket['message'],
            'created_at' => $ticket['created_at'],
            'is_auto_response' => 0
        ]);
        
        echo json_encode([
            'success' => true,
            'ticket' => $ticket,
            'messages' => $responses
        ]);
        break;
    
    // ==================== GET SINGLE TICKET ====================
    case 'ticket':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            exit;
        }
        
        $ticket = $db->querySingle("SELECT * FROM support_tickets WHERE id = $id", true);
        if (!$ticket) {
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
            exit;
        }
        
        // Get suggestions
        $content = $ticket['subject'] . ' ' . $ticket['message'];
        $ticket['kb_suggestion'] = $kbResolver->findBestMatch($content);
        $ticket['canned_suggestions'] = $cannedSuggester->getSuggestions($content, $ticket['category'], 3);
        $ticket['self_service_suggestion'] = detectSelfServiceAPI($db, $content);
        
        // Response count
        $ticket['response_count'] = $db->querySingle("SELECT COUNT(*) FROM ticket_responses WHERE ticket_id = $id");
        
        echo json_encode(['success' => true, 'ticket' => $ticket]);
        break;
    
    // ==================== LIST TICKETS ====================
    case 'tickets':
        $status = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $search = $_GET['search'] ?? '';
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $sql = "SELECT * FROM support_tickets WHERE 1=1";
        $countSql = "SELECT COUNT(*) FROM support_tickets WHERE 1=1";
        
        if ($status) {
            $status = SQLite3::escapeString($status);
            $sql .= " AND status = '$status'";
            $countSql .= " AND status = '$status'";
        }
        if ($category) {
            $category = SQLite3::escapeString($category);
            $sql .= " AND category = '$category'";
            $countSql .= " AND category = '$category'";
        }
        if ($priority) {
            $priority = SQLite3::escapeString($priority);
            $sql .= " AND priority = '$priority'";
            $countSql .= " AND priority = '$priority'";
        }
        if ($search) {
            $search = SQLite3::escapeString($search);
            $sql .= " AND (ticket_number LIKE '%$search%' OR subject LIKE '%$search%' OR customer_email LIKE '%$search%')";
            $countSql .= " AND (ticket_number LIKE '%$search%' OR subject LIKE '%$search%' OR customer_email LIKE '%$search%')";
        }
        
        $sql .= " ORDER BY is_vip DESC, 
                  CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
                  created_at DESC
                  LIMIT $limit OFFSET $offset";
        
        $total = $db->querySingle($countSql);
        $tickets = [];
        $result = $db->query($sql);
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tickets[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'tickets' => $tickets,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
        break;
    
    // ==================== CREATE TICKET ====================
    case 'create_ticket':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $email = $data['email'] ?? '';
        $subject = $data['subject'] ?? '';
        $message = $data['message'] ?? '';
        $category = $data['category'] ?? 'general';
        $customerId = intval($data['customer_id'] ?? 0);
        
        if (!$email || !$subject || !$message) {
            echo json_encode(['success' => false, 'error' => 'Email, subject, and message required']);
            exit;
        }
        
        // Generate ticket number
        $ticketNumber = 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Check if VIP
        $isVip = 0;
        if ($customerId) {
            // In real implementation, check VIP status from customers table
            // For now, check if email is in VIP list
            $vipEmails = ['seige235@yahoo.com', 'paulhalonen@gmail.com'];
            $isVip = in_array(strtolower($email), $vipEmails) ? 1 : 0;
        }
        
        // Auto-categorize based on keywords
        $content = strtolower($subject . ' ' . $message);
        if (strpos($content, 'payment') !== false || strpos($content, 'invoice') !== false || 
            strpos($content, 'refund') !== false || strpos($content, 'charge') !== false ||
            strpos($content, 'billing') !== false || strpos($content, 'subscription') !== false) {
            $category = 'billing';
        } elseif (strpos($content, 'connect') !== false || strpos($content, 'slow') !== false ||
                  strpos($content, 'error') !== false || strpos($content, 'not working') !== false ||
                  strpos($content, 'vpn') !== false || strpos($content, 'server') !== false) {
            $category = 'technical';
        } elseif (strpos($content, 'password') !== false || strpos($content, 'email') !== false ||
                  strpos($content, 'account') !== false || strpos($content, 'login') !== false ||
                  strpos($content, 'device') !== false) {
            $category = 'account';
        }
        
        // Set priority based on keywords
        $priority = 'normal';
        if (strpos($content, 'urgent') !== false || strpos($content, 'emergency') !== false ||
            strpos($content, 'asap') !== false || strpos($content, 'critical') !== false) {
            $priority = 'urgent';
        } elseif (strpos($content, 'important') !== false || strpos($content, 'please help') !== false) {
            $priority = 'high';
        }
        
        // VIP gets high priority minimum
        if ($isVip && $priority === 'normal') {
            $priority = 'high';
        }
        
        $stmt = $db->prepare("INSERT INTO support_tickets 
            (ticket_number, customer_id, customer_email, category, priority, subject, message, status, is_vip)
            VALUES (:num, :cid, :email, :cat, :pri, :subj, :msg, 'new', :vip)");
        
        $stmt->bindValue(':num', $ticketNumber, SQLITE3_TEXT);
        $stmt->bindValue(':cid', $customerId, SQLITE3_INTEGER);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':cat', $category, SQLITE3_TEXT);
        $stmt->bindValue(':pri', $priority, SQLITE3_TEXT);
        $stmt->bindValue(':subj', $subject, SQLITE3_TEXT);
        $stmt->bindValue(':msg', $message, SQLITE3_TEXT);
        $stmt->bindValue(':vip', $isVip, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            $ticketId = $db->lastInsertRowID();
            
            // Try auto-resolution (Tier 1)
            $kbMatch = $kbResolver->findBestMatch($subject . ' ' . $message);
            $autoResolved = false;
            
            if ($kbMatch && $kbMatch['score'] >= 0.7) {
                // High confidence - auto-resolve
                $kbEntry = $kbMatch['entry'];
                
                // Build auto-response
                $autoBody = "Hi " . explode('@', $email)[0] . ",\n\n";
                $autoBody .= "I found a solution that might help with your issue:\n\n";
                $autoBody .= "**" . $kbEntry['question'] . "**\n\n";
                $autoBody .= $kbEntry['answer'] . "\n\n";
                
                $steps = json_decode($kbEntry['resolution_steps'], true);
                if (!empty($steps)) {
                    $autoBody .= "**Steps to resolve:**\n";
                    foreach ($steps as $i => $step) {
                        $autoBody .= ($i + 1) . ". " . $step . "\n";
                    }
                    $autoBody .= "\n";
                }
                
                $autoBody .= "Did this solve your issue? Please reply to let us know!\n\n";
                $autoBody .= "If you need more help, a human support agent will assist you.";
                
                // Add auto-response
                $respStmt = $db->prepare("INSERT INTO ticket_responses 
                    (ticket_id, sender_type, message, is_auto_response) 
                    VALUES (:tid, 'system', :msg, 1)");
                $respStmt->bindValue(':tid', $ticketId, SQLITE3_INTEGER);
                $respStmt->bindValue(':msg', $autoBody, SQLITE3_TEXT);
                $respStmt->execute();
                
                // Update ticket as auto-resolved
                $db->exec("UPDATE support_tickets SET 
                    status = 'auto_resolved',
                    tier_resolved = 1,
                    resolution_method = 'auto',
                    auto_resolution_id = {$kbEntry['id']},
                    response_count = 1,
                    first_response_at = datetime('now')
                    WHERE id = $ticketId");
                
                // Update KB usage
                $db->exec("UPDATE knowledge_base SET times_used = times_used + 1 WHERE id = {$kbEntry['id']}");
                
                $autoResolved = true;
            }
            
            // Check for self-service redirect (Tier 2)
            $selfService = detectSelfServiceAPI($db, $subject . ' ' . $message);
            
            echo json_encode([
                'success' => true,
                'ticket_id' => $ticketId,
                'ticket_number' => $ticketNumber,
                'category' => $category,
                'priority' => $priority,
                'is_vip' => $isVip,
                'auto_resolved' => $autoResolved,
                'kb_match' => $kbMatch,
                'self_service_available' => $selfService ? true : false,
                'self_service_action' => $selfService
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create ticket']);
        }
        break;
    
    // ==================== ADD RESPONSE ====================
    case 'add_response':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $ticketId = intval($data['ticket_id'] ?? 0);
        $senderType = $data['sender_type'] ?? 'customer';
        $message = $data['message'] ?? '';
        
        if (!$ticketId || !$message) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID and message required']);
            exit;
        }
        
        // Verify ticket exists
        $ticket = $db->querySingle("SELECT * FROM support_tickets WHERE id = $ticketId", true);
        if (!$ticket) {
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO ticket_responses 
            (ticket_id, sender_type, message, is_auto_response) 
            VALUES (:tid, :sender, :msg, 0)");
        $stmt->bindValue(':tid', $ticketId, SQLITE3_INTEGER);
        $stmt->bindValue(':sender', $senderType, SQLITE3_TEXT);
        $stmt->bindValue(':msg', $message, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $responseId = $db->lastInsertRowID();
            
            // Update ticket
            $newStatus = $senderType === 'customer' ? 'in_progress' : 'awaiting_response';
            $db->exec("UPDATE support_tickets SET 
                status = '$newStatus',
                response_count = response_count + 1,
                first_response_at = COALESCE(first_response_at, datetime('now')),
                updated_at = datetime('now')
                WHERE id = $ticketId");
            
            // If customer replied to auto-resolved, check if they need more help
            if ($senderType === 'customer' && $ticket['status'] === 'auto_resolved') {
                $lowerMsg = strtolower($message);
                if (strpos($lowerMsg, 'not work') !== false || strpos($lowerMsg, 'still') !== false ||
                    strpos($lowerMsg, 'didn\'t help') !== false || strpos($lowerMsg, 'need help') !== false) {
                    // Re-open for human handling
                    $db->exec("UPDATE support_tickets SET 
                        status = 'in_progress',
                        tier_resolved = 3
                        WHERE id = $ticketId");
                    
                    // Record negative feedback
                    if ($ticket['auto_resolution_id']) {
                        $kbResolver->recordFeedback($ticket['auto_resolution_id'], false);
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'response_id' => $responseId,
                'ticket_status' => $newStatus
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add response']);
        }
        break;
    
    // ==================== KB SUGGESTIONS ====================
    case 'suggest_kb':
        $content = $_GET['content'] ?? '';
        if (!$content) {
            echo json_encode(['success' => false, 'error' => 'Content required']);
            exit;
        }
        
        $match = $kbResolver->findBestMatch($content);
        
        echo json_encode([
            'success' => true,
            'suggestion' => $match
        ]);
        break;
    
    // ==================== CANNED SUGGESTIONS ====================
    case 'suggest_canned':
        $content = $_GET['content'] ?? '';
        $category = $_GET['category'] ?? null;
        $limit = intval($_GET['limit'] ?? 3);
        
        if (!$content) {
            echo json_encode(['success' => false, 'error' => 'Content required']);
            exit;
        }
        
        $suggestions = $cannedSuggester->getSuggestions($content, $category, $limit);
        
        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
        break;
    
    // ==================== SUPPORT STATS ====================
    case 'stats':
        $period = $_GET['period'] ?? 'today';
        
        switch ($period) {
            case 'week':
                $dateFilter = "datetime('now', '-7 days')";
                break;
            case 'month':
                $dateFilter = "datetime('now', '-30 days')";
                break;
            default:
                $dateFilter = "datetime('now', 'start of day')";
        }
        
        $stats = [
            // Ticket counts
            'total_tickets' => $db->querySingle("SELECT COUNT(*) FROM support_tickets"),
            'open_tickets' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE status NOT IN ('resolved', 'closed')"),
            'new_tickets' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE created_at >= $dateFilter"),
            'resolved_tickets' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE resolved_at >= $dateFilter"),
            
            // Priority breakdown
            'urgent_count' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE priority = 'urgent' AND status NOT IN ('resolved', 'closed')"),
            'vip_count' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE is_vip = 1 AND status NOT IN ('resolved', 'closed')"),
            
            // Resolution stats
            'auto_resolved' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 1 AND resolved_at >= $dateFilter"),
            'self_service' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 2 AND resolved_at >= $dateFilter"),
            'canned_resolved' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 3 AND resolved_at >= $dateFilter"),
            'manual_resolved' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 4 AND resolved_at >= $dateFilter"),
            
            // Category breakdown
            'by_category' => [],
            
            // Response times
            'avg_first_response' => null,
            'avg_resolution_time' => null
        ];
        
        // Category breakdown
        $catResult = $db->query("SELECT category, COUNT(*) as count FROM support_tickets WHERE created_at >= $dateFilter GROUP BY category");
        while ($row = $catResult->fetchArray(SQLITE3_ASSOC)) {
            $stats['by_category'][$row['category']] = $row['count'];
        }
        
        // Calculate resolution rates
        $totalResolved = $stats['auto_resolved'] + $stats['self_service'] + $stats['canned_resolved'] + $stats['manual_resolved'];
        if ($totalResolved > 0) {
            $stats['auto_resolution_rate'] = round(($stats['auto_resolved'] / $totalResolved) * 100, 1);
            $stats['self_service_rate'] = round(($stats['self_service'] / $totalResolved) * 100, 1);
            $stats['human_touch_rate'] = round((($stats['canned_resolved'] + $stats['manual_resolved']) / $totalResolved) * 100, 1);
        }
        
        echo json_encode(['success' => true, 'stats' => $stats, 'period' => $period]);
        break;
    
    // ==================== RECORD FEEDBACK ====================
    case 'feedback':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $ticketId = intval($data['ticket_id'] ?? 0);
        $helpful = isset($data['helpful']) ? (bool)$data['helpful'] : null;
        $rating = intval($data['rating'] ?? 0);
        $comment = $data['comment'] ?? '';
        
        if (!$ticketId) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            exit;
        }
        
        $ticket = $db->querySingle("SELECT * FROM support_tickets WHERE id = $ticketId", true);
        if (!$ticket) {
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
            exit;
        }
        
        // Update ticket rating
        if ($rating >= 1 && $rating <= 5) {
            $db->exec("UPDATE support_tickets SET customer_rating = $rating WHERE id = $ticketId");
        }
        
        // Record KB feedback if applicable
        if ($ticket['auto_resolution_id'] && $helpful !== null) {
            $kbResolver->recordFeedback($ticket['auto_resolution_id'], $helpful);
        }
        
        // Record canned response feedback if applicable
        if ($ticket['canned_response_id'] && $helpful !== null) {
            $cannedSuggester->recordFeedback($ticket['canned_response_id'], $helpful);
        }
        
        echo json_encode(['success' => true, 'message' => 'Feedback recorded']);
        break;
    
    // ==================== SELF-SERVICE ACTIONS ====================
    case 'self_service_actions':
        $result = $db->query("SELECT * FROM self_service_actions WHERE is_active = 1 ORDER BY display_order");
        $actions = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $actions[] = $row;
        }
        echo json_encode(['success' => true, 'actions' => $actions]);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action',
            'available_actions' => [
                'GET conversation' => 'Get ticket conversation thread',
                'GET ticket' => 'Get single ticket with suggestions',
                'GET tickets' => 'List tickets with filters',
                'POST create_ticket' => 'Create new support ticket',
                'POST add_response' => 'Add response to ticket',
                'GET suggest_kb' => 'Get KB suggestions for content',
                'GET suggest_canned' => 'Get canned response suggestions',
                'GET stats' => 'Get support statistics',
                'POST feedback' => 'Record resolution feedback',
                'GET self_service_actions' => 'List available self-service actions'
            ]
        ]);
}

// Helper function
function detectSelfServiceAPI($db, $content) {
    $content = strtolower($content);
    $result = $db->query("SELECT * FROM self_service_actions WHERE is_active = 1");
    
    while ($action = $result->fetchArray(SQLITE3_ASSOC)) {
        $keywords = array_map('trim', explode(',', strtolower($action['trigger_keywords'])));
        foreach ($keywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                return $action;
            }
        }
    }
    return null;
}

$db->close();
