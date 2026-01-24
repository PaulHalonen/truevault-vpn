<?php
/**
 * TrueVault VPN - Support Automation API
 * Task 17.11: REST API for Support Operations
 * Created: January 24, 2026
 * 
 * Endpoints:
 * - tickets: CRUD operations
 * - conversation: Get ticket thread
 * - auto_resolve: Attempt KB resolution
 * - suggest: Get response suggestions
 * - self_service: Check for self-service actions
 * - stats: Dashboard statistics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
$automationDb = new SQLite3(__DIR__ . '/databases/automation.db');

// Get action
$action = $_GET['action'] ?? '';

// Response helper
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Auth check (simplified - in production use JWT)
function checkAuth() {
    // For now, check session or API key
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($apiKey !== 'tv_support_api_key_2026') {
            jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
        }
    }
}

switch ($action) {
    
    // ==================== TICKETS ====================
    
    case 'tickets':
        checkAuth();
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method === 'GET') {
            // List or get single ticket
            $ticketId = $_GET['id'] ?? null;
            
            if ($ticketId) {
                // Get single ticket
                $ticket = $automationDb->querySingle("SELECT * FROM support_tickets WHERE id = $ticketId", true);
                if ($ticket) {
                    jsonResponse(['success' => true, 'ticket' => $ticket]);
                } else {
                    jsonResponse(['success' => false, 'error' => 'Ticket not found'], 404);
                }
            } else {
                // List tickets with filters
                $status = $_GET['status'] ?? '';
                $category = $_GET['category'] ?? '';
                $priority = $_GET['priority'] ?? '';
                $limit = min(100, intval($_GET['limit'] ?? 50));
                $offset = intval($_GET['offset'] ?? 0);
                
                $sql = "SELECT * FROM support_tickets WHERE 1=1";
                if ($status) $sql .= " AND status = '$status'";
                if ($category) $sql .= " AND category = '$category'";
                if ($priority) $sql .= " AND priority = '$priority'";
                $sql .= " ORDER BY is_vip DESC, 
                          CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
                          created_at DESC";
                $sql .= " LIMIT $limit OFFSET $offset";
                
                $result = $automationDb->query($sql);
                $tickets = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $tickets[] = $row;
                }
                
                $total = $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets");
                jsonResponse(['success' => true, 'tickets' => $tickets, 'total' => $total]);
            }
        }
        
        if ($method === 'POST') {
            // Create new ticket
            $data = json_decode(file_get_contents('php://input'), true);
            
            $ticketNumber = 'TKT-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $email = $automationDb->escapeString($data['customer_email'] ?? '');
            $subject = $automationDb->escapeString($data['subject'] ?? '');
            $message = $automationDb->escapeString($data['message'] ?? '');
            $category = $automationDb->escapeString($data['category'] ?? 'general');
            $priority = $automationDb->escapeString($data['priority'] ?? 'normal');
            $customerId = intval($data['customer_id'] ?? 0);
            $isVip = $data['is_vip'] ?? false;
            
            $sql = "INSERT INTO support_tickets 
                (ticket_number, customer_id, customer_email, subject, message, category, priority, is_vip, status)
                VALUES ('$ticketNumber', $customerId, '$email', '$subject', '$message', '$category', '$priority', " . ($isVip ? 1 : 0) . ", 'new')";
            
            if ($automationDb->exec($sql)) {
                $id = $automationDb->lastInsertRowID();
                jsonResponse(['success' => true, 'ticket_id' => $id, 'ticket_number' => $ticketNumber], 201);
            } else {
                jsonResponse(['success' => false, 'error' => 'Failed to create ticket'], 500);
            }
        }
        
        if ($method === 'PUT') {
            // Update ticket
            $ticketId = $_GET['id'] ?? null;
            if (!$ticketId) {
                jsonResponse(['success' => false, 'error' => 'Ticket ID required'], 400);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $updates = [];
            
            $allowedFields = ['status', 'priority', 'category', 'tier_resolved', 'resolution_method', 'assigned_to'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $value = $automationDb->escapeString($data[$field]);
                    $updates[] = "$field = '$value'";
                }
            }
            
            if (empty($updates)) {
                jsonResponse(['success' => false, 'error' => 'No valid fields to update'], 400);
            }
            
            $updates[] = "updated_at = datetime('now')";
            
            if (isset($data['status']) && in_array($data['status'], ['resolved', 'closed'])) {
                $updates[] = "resolved_at = datetime('now')";
            }
            
            $sql = "UPDATE support_tickets SET " . implode(', ', $updates) . " WHERE id = $ticketId";
            
            if ($automationDb->exec($sql)) {
                jsonResponse(['success' => true, 'message' => 'Ticket updated']);
            } else {
                jsonResponse(['success' => false, 'error' => 'Update failed'], 500);
            }
        }
        break;
    
    // ==================== CONVERSATION ====================
    
    case 'conversation':
        checkAuth();
        $ticketId = intval($_GET['ticket_id'] ?? 0);
        
        if (!$ticketId) {
            jsonResponse(['success' => false, 'error' => 'Ticket ID required'], 400);
        }
        
        // Get ticket info
        $ticket = $automationDb->querySingle("SELECT * FROM support_tickets WHERE id = $ticketId", true);
        if (!$ticket) {
            jsonResponse(['success' => false, 'error' => 'Ticket not found'], 404);
        }
        
        // Get messages
        $result = $automationDb->query("SELECT * FROM ticket_responses WHERE ticket_id = $ticketId ORDER BY created_at ASC");
        $messages = [];
        
        // Add original ticket message as first
        $messages[] = [
            'id' => 0,
            'sender_type' => 'customer',
            'message' => $ticket['message'],
            'created_at' => $ticket['created_at'],
            'is_auto_response' => false
        ];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $messages[] = $row;
        }
        
        jsonResponse([
            'success' => true, 
            'ticket' => $ticket,
            'messages' => $messages
        ]);
        break;
    
    // ==================== ADD RESPONSE ====================
    
    case 'respond':
        checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'POST required'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $ticketId = intval($data['ticket_id'] ?? 0);
        $message = $automationDb->escapeString($data['message'] ?? '');
        $senderType = $data['sender_type'] ?? 'admin';
        $isAuto = $data['is_auto'] ?? false;
        $cannedId = intval($data['canned_id'] ?? 0) ?: 'NULL';
        
        if (!$ticketId || !$message) {
            jsonResponse(['success' => false, 'error' => 'Ticket ID and message required'], 400);
        }
        
        $sql = "INSERT INTO ticket_responses 
            (ticket_id, sender_type, message, is_auto_response, canned_response_id)
            VALUES ($ticketId, '$senderType', '$message', " . ($isAuto ? 1 : 0) . ", $cannedId)";
        
        if ($automationDb->exec($sql)) {
            // Update ticket
            $automationDb->exec("UPDATE support_tickets SET 
                response_count = response_count + 1,
                first_response_at = COALESCE(first_response_at, datetime('now')),
                updated_at = datetime('now')
                WHERE id = $ticketId");
            
            jsonResponse(['success' => true, 'response_id' => $automationDb->lastInsertRowID()]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to add response'], 500);
        }
        break;
    
    // ==================== AUTO RESOLUTION ====================
    
    case 'auto_resolve':
        checkAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $content = $data['content'] ?? '';
        $ticketId = $data['ticket_id'] ?? null;
        
        if (!$content) {
            jsonResponse(['success' => false, 'error' => 'Content required'], 400);
        }
        
        // Find best KB match
        $kbMatch = findKBMatch($automationDb, $content);
        
        if ($kbMatch && $kbMatch['score'] >= 0.6) {
            // If ticket ID provided, apply resolution
            if ($ticketId) {
                $kbId = $kbMatch['entry']['id'];
                $answer = $automationDb->escapeString($kbMatch['entry']['answer']);
                
                // Add auto-response
                $automationDb->exec("INSERT INTO ticket_responses 
                    (ticket_id, sender_type, message, is_auto_response)
                    VALUES ($ticketId, 'system', '$answer', 1)");
                
                // Update ticket
                $automationDb->exec("UPDATE support_tickets SET 
                    status = 'auto_resolved',
                    tier_resolved = 1,
                    resolution_method = 'auto',
                    auto_resolution_id = $kbId,
                    first_response_at = COALESCE(first_response_at, datetime('now')),
                    updated_at = datetime('now')
                    WHERE id = $ticketId");
                
                // Update KB usage
                $automationDb->exec("UPDATE knowledge_base SET times_used = times_used + 1 WHERE id = $kbId");
            }
            
            jsonResponse([
                'success' => true,
                'resolved' => true,
                'confidence' => $kbMatch['score'],
                'kb_entry' => $kbMatch['entry']
            ]);
        } else {
            jsonResponse([
                'success' => true,
                'resolved' => false,
                'best_match' => $kbMatch,
                'message' => 'No confident match found'
            ]);
        }
        break;
    
    // ==================== SUGGESTIONS ====================
    
    case 'suggest':
        checkAuth();
        $content = $_GET['content'] ?? '';
        $category = $_GET['category'] ?? '';
        
        if (!$content) {
            jsonResponse(['success' => false, 'error' => 'Content required'], 400);
        }
        
        // Get KB match
        $kbMatch = findKBMatch($automationDb, $content);
        
        // Get canned response suggestions
        $cannedSuggestions = findCannedMatches($automationDb, $content, $category);
        
        // Check for self-service action
        $selfService = findSelfServiceAction($automationDb, $content);
        
        jsonResponse([
            'success' => true,
            'kb_suggestion' => $kbMatch,
            'canned_suggestions' => $cannedSuggestions,
            'self_service_suggestion' => $selfService
        ]);
        break;
    
    // ==================== SELF SERVICE CHECK ====================
    
    case 'self_service':
        $content = $_GET['content'] ?? '';
        
        if (!$content) {
            jsonResponse(['success' => false, 'error' => 'Content required'], 400);
        }
        
        $action = findSelfServiceAction($automationDb, $content);
        
        if ($action) {
            jsonResponse([
                'success' => true,
                'can_self_service' => true,
                'action' => $action
            ]);
        } else {
            jsonResponse([
                'success' => true,
                'can_self_service' => false
            ]);
        }
        break;
    
    // ==================== KB SEARCH ====================
    
    case 'kb_search':
        $query = $_GET['query'] ?? '';
        $category = $_GET['category'] ?? '';
        
        if (!$query) {
            jsonResponse(['success' => false, 'error' => 'Query required'], 400);
        }
        
        $sql = "SELECT * FROM knowledge_base WHERE 
            (question LIKE '%$query%' OR keywords LIKE '%$query%' OR answer LIKE '%$query%')";
        if ($category) {
            $sql .= " AND category = '$category'";
        }
        $sql .= " ORDER BY times_used DESC LIMIT 10";
        
        $result = $automationDb->query($sql);
        $entries = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $entries[] = $row;
        }
        
        jsonResponse(['success' => true, 'entries' => $entries]);
        break;
    
    // ==================== CANNED RESPONSES ====================
    
    case 'canned':
        checkAuth();
        $category = $_GET['category'] ?? '';
        
        $sql = "SELECT * FROM canned_responses WHERE is_active = 1";
        if ($category) {
            $sql .= " AND category = '$category'";
        }
        $sql .= " ORDER BY times_used DESC";
        
        $result = $automationDb->query($sql);
        $responses = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $responses[] = $row;
        }
        
        jsonResponse(['success' => true, 'responses' => $responses]);
        break;
    
    // ==================== STATISTICS ====================
    
    case 'stats':
        checkAuth();
        
        $stats = [
            'tickets' => [
                'total' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets"),
                'open' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE status NOT IN ('resolved', 'closed')"),
                'new' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE status = 'new'"),
                'urgent' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE priority = 'urgent' AND status NOT IN ('resolved', 'closed')"),
                'vip' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE is_vip = 1 AND status NOT IN ('resolved', 'closed')"),
                'auto_resolved' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 1"),
                'self_service' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 2"),
                'canned' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 3"),
                'manual' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 4")
            ],
            'today' => [
                'created' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE date(created_at) = date('now')"),
                'resolved' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE date(resolved_at) = date('now')")
            ],
            'knowledge_base' => [
                'total' => $automationDb->querySingle("SELECT COUNT(*) FROM knowledge_base"),
                'total_uses' => $automationDb->querySingle("SELECT COALESCE(SUM(times_used), 0) FROM knowledge_base")
            ],
            'canned_responses' => [
                'total' => $automationDb->querySingle("SELECT COUNT(*) FROM canned_responses"),
                'active' => $automationDb->querySingle("SELECT COUNT(*) FROM canned_responses WHERE is_active = 1"),
                'total_uses' => $automationDb->querySingle("SELECT COALESCE(SUM(times_used), 0) FROM canned_responses")
            ],
            'resolution_distribution' => getResolutionDistribution($automationDb)
        ];
        
        jsonResponse(['success' => true, 'stats' => $stats]);
        break;
    
    // ==================== RECORD FEEDBACK ====================
    
    case 'feedback':
        checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'POST required'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $type = $data['type'] ?? ''; // 'kb' or 'canned'
        $id = intval($data['id'] ?? 0);
        $positive = $data['positive'] ?? true;
        
        if (!$type || !$id) {
            jsonResponse(['success' => false, 'error' => 'Type and ID required'], 400);
        }
        
        $table = $type === 'kb' ? 'knowledge_base' : 'canned_responses';
        
        // Update success rate (simplified weighted average)
        $current = $automationDb->querySingle("SELECT times_used, success_rate FROM $table WHERE id = $id", true);
        if ($current) {
            $uses = $current['times_used'];
            $rate = $current['success_rate'] ?? 0.5;
            $newRate = (($rate * $uses) + ($positive ? 1 : 0)) / ($uses + 1);
            
            $automationDb->exec("UPDATE $table SET success_rate = $newRate WHERE id = $id");
            jsonResponse(['success' => true, 'new_rate' => $newRate]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Entry not found'], 404);
        }
        break;
    
    default:
        jsonResponse([
            'success' => false, 
            'error' => 'Unknown action',
            'available_actions' => [
                'tickets', 'conversation', 'respond', 'auto_resolve', 
                'suggest', 'self_service', 'kb_search', 'canned', 
                'stats', 'feedback'
            ]
        ], 400);
}

// ==================== HELPER FUNCTIONS ====================

function findKBMatch($db, $content) {
    $content = strtolower($content);
    $stopWords = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'but', 'in', 'with', 'to', 'for', 'of', 'my', 'i', 'me', 'can', 'how', 'do', 'does', 'what', 'why', 'when', 'help', 'please', 'need'];
    
    $words = preg_split('/[\s\W]+/', $content);
    $keywords = array_filter($words, function($w) use ($stopWords) {
        return strlen($w) > 2 && !in_array($w, $stopWords);
    });
    
    if (empty($keywords)) return null;
    
    $result = $db->query("SELECT * FROM knowledge_base WHERE is_active = 1");
    $bestMatch = null;
    $bestScore = 0;
    
    while ($entry = $result->fetchArray(SQLITE3_ASSOC)) {
        $entryKeywords = array_map('trim', explode(',', strtolower($entry['keywords'])));
        $matchCount = 0;
        
        foreach ($keywords as $keyword) {
            foreach ($entryKeywords as $ek) {
                if (strpos($ek, $keyword) !== false || strpos($keyword, $ek) !== false) {
                    $matchCount++;
                    break;
                }
            }
        }
        
        $score = count($keywords) > 0 ? $matchCount / count($keywords) : 0;
        
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $entry;
        }
    }
    
    if ($bestMatch) {
        return ['entry' => $bestMatch, 'score' => $bestScore];
    }
    return null;
}

function findCannedMatches($db, $content, $category = null) {
    $content = strtolower($content);
    $words = preg_split('/[\s\W]+/', $content);
    
    $sql = "SELECT * FROM canned_responses WHERE is_active = 1";
    if ($category) {
        $sql .= " AND category = '$category'";
    }
    
    $result = $db->query($sql);
    $matches = [];
    
    while ($response = $result->fetchArray(SQLITE3_ASSOC)) {
        $keywords = array_map('trim', explode(',', strtolower($response['trigger_keywords'])));
        $matchCount = 0;
        
        foreach ($words as $word) {
            foreach ($keywords as $keyword) {
                if (strpos($keyword, $word) !== false || strpos($word, $keyword) !== false) {
                    $matchCount++;
                    break;
                }
            }
        }
        
        if ($matchCount > 0) {
            $score = count($keywords) > 0 ? $matchCount / count($keywords) : 0;
            $matches[] = ['response' => $response, 'score' => $score];
        }
    }
    
    // Sort by score desc
    usort($matches, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    return array_slice($matches, 0, 3);
}

function findSelfServiceAction($db, $content) {
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

function getResolutionDistribution($db) {
    $total = $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved IS NOT NULL");
    if ($total == 0) return [];
    
    return [
        'tier_1_auto' => [
            'count' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 1"),
            'percentage' => round(($db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 1") / $total) * 100, 1)
        ],
        'tier_2_self_service' => [
            'count' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 2"),
            'percentage' => round(($db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 2") / $total) * 100, 1)
        ],
        'tier_3_canned' => [
            'count' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 3"),
            'percentage' => round(($db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 3") / $total) * 100, 1)
        ],
        'tier_4_manual' => [
            'count' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 4"),
            'percentage' => round(($db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 4") / $total) * 100, 1)
        ],
        'tier_5_vip' => [
            'count' => $db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 5"),
            'percentage' => round(($db->querySingle("SELECT COUNT(*) FROM support_tickets WHERE tier_resolved = 5") / $total) * 100, 1)
        ]
    ];
}

$automationDb->close();
