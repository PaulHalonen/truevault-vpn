<?php
/**
 * TrueVault VPN - Smart Ticket Dashboard
 * Task 17.10: Tier 3-4 Support Interface
 * Created: January 24, 2026
 * 
 * Features:
 * - All tickets with filters and search
 * - Auto-suggestions (KB match, canned responses, self-service)
 * - Customer context panel
 * - 1-click responses
 * - VIP prioritization
 * - Conversation threads
 */

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Database connections
$automationDb = new SQLite3(__DIR__ . '/databases/automation.db');
$settingsDb = new SQLite3(__DIR__ . '/../databases/settings.db');

// Include resolver classes
require_once __DIR__ . '/knowledge-base.php';
require_once __DIR__ . '/canned-responses.php';

// Get theme settings
$themeResult = $settingsDb->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'theme_%'");
$theme = [];
while ($row = $themeResult->fetchArray(SQLITE3_ASSOC)) {
    $theme[$row['setting_key']] = $row['setting_value'];
}

$primaryColor = $theme['theme_primary_color'] ?? '#00d4ff';
$secondaryColor = $theme['theme_secondary_color'] ?? '#7b2cbf';
$bgColor = $theme['theme_bg_color'] ?? '#0a0a0f';
$cardBg = $theme['theme_card_bg'] ?? 'rgba(255,255,255,0.03)';
$textColor = $theme['theme_text_color'] ?? '#ffffff';

// Initialize resolver classes
$kbResolver = new KnowledgeBaseResolver($automationDb);
$cannedSuggester = new CannedResponseSuggester($automationDb);

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Send canned response
    if ($action === 'send_canned') {
        $ticketId = $_POST['ticket_id'];
        $cannedId = $_POST['canned_id'];
        $ticket = getTicket($automationDb, $ticketId);
        $canned = $automationDb->querySingle("SELECT * FROM canned_responses WHERE id = $cannedId", true);
        
        if ($ticket && $canned) {
            // Replace variables
            $body = $cannedSuggester->fillVariables($canned['body'], [
                'first_name' => explode('@', $ticket['customer_email'])[0],
                'email' => $ticket['customer_email'],
                'ticket_id' => $ticket['ticket_number'],
                'dashboard_url' => 'https://vpn.the-truth-publishing.com/dashboard'
            ]);
            
            // Add response
            addResponse($automationDb, $ticketId, 'admin', $body, false, $cannedId);
            
            // Update ticket
            updateTicketStatus($automationDb, $ticketId, 'awaiting_response', 3, 'canned', $cannedId);
            
            // Record usage
            $cannedSuggester->recordUsage($cannedId);
            
            $message = 'Canned response sent!';
            $messageType = 'success';
        }
    }
    
    // Send auto-resolution
    if ($action === 'send_auto') {
        $ticketId = $_POST['ticket_id'];
        $kbId = $_POST['kb_id'];
        $ticket = getTicket($automationDb, $ticketId);
        $kb = $automationDb->querySingle("SELECT * FROM knowledge_base WHERE id = $kbId", true);
        
        if ($ticket && $kb) {
            // Build auto-resolution message
            $body = "<p>Hi " . explode('@', $ticket['customer_email'])[0] . ",</p>";
            $body .= "<p>I found a solution that might help with your issue:</p>";
            $body .= "<h3>" . htmlspecialchars($kb['question']) . "</h3>";
            $body .= "<p>" . htmlspecialchars($kb['answer']) . "</p>";
            
            // Add steps if available
            $steps = json_decode($kb['resolution_steps'], true);
            if (!empty($steps)) {
                $body .= "<p><strong>Steps to resolve:</strong></p><ol>";
                foreach ($steps as $step) {
                    $body .= "<li>" . htmlspecialchars($step) . "</li>";
                }
                $body .= "</ol>";
            }
            
            $body .= "<p>Did this solve your issue? Please reply to let us know!</p>";
            
            // Add response
            addResponse($automationDb, $ticketId, 'system', $body, true, null);
            
            // Update ticket
            updateTicketStatus($automationDb, $ticketId, 'auto_resolved', 1, 'auto', null, $kbId);
            
            // Record KB usage
            $automationDb->exec("UPDATE knowledge_base SET times_used = times_used + 1 WHERE id = $kbId");
            
            $message = 'Auto-resolution sent!';
            $messageType = 'success';
        }
    }
    
    // Send self-service redirect
    if ($action === 'send_self_service') {
        $ticketId = $_POST['ticket_id'];
        $actionKey = $_POST['action_key'];
        $ticket = getTicket($automationDb, $ticketId);
        $selfService = $automationDb->querySingle("SELECT * FROM self_service_actions WHERE action_key = '$actionKey'", true);
        
        if ($ticket && $selfService) {
            $body = "<p>Hi " . explode('@', $ticket['customer_email'])[0] . ",</p>";
            $body .= "<p>Great news! You can handle this yourself in just a few clicks:</p>";
            $body .= "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>";
            $body .= "<h3>" . htmlspecialchars($selfService['display_name']) . "</h3>";
            $body .= "<p>" . htmlspecialchars($selfService['description']) . "</p>";
            $body .= "<a href='https://vpn.the-truth-publishing.com" . $selfService['portal_url'] . "' ";
            $body .= "style='display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #00d4ff, #7b2cbf); ";
            $body .= "color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Do It Now →</a>";
            $body .= "</div>";
            $body .= "<p><strong>Still need help?</strong> Just reply to this email and a human will assist you.</p>";
            
            // Add response
            addResponse($automationDb, $ticketId, 'system', $body, true, null);
            
            // Update ticket
            $automationDb->exec("UPDATE support_tickets SET 
                status = 'pending_confirmation',
                tier_resolved = 2,
                resolution_method = 'self_service',
                self_service_action = '$actionKey',
                updated_at = datetime('now')
                WHERE id = $ticketId");
            
            // Record usage
            $automationDb->exec("UPDATE self_service_actions SET times_used = times_used + 1 WHERE action_key = '$actionKey'");
            
            $message = 'Self-service redirect sent!';
            $messageType = 'success';
        }
    }
    
    // Send manual reply
    if ($action === 'send_reply') {
        $ticketId = $_POST['ticket_id'];
        $replyText = $_POST['reply_text'];
        $saveAsCanned = isset($_POST['save_as_canned']);
        
        if ($ticketId && $replyText) {
            // Add response
            addResponse($automationDb, $ticketId, 'admin', $replyText, false, null);
            
            // Update ticket
            updateTicketStatus($automationDb, $ticketId, 'awaiting_response', 4, 'manual');
            
            // Save as canned if requested
            if ($saveAsCanned && !empty($_POST['canned_title'])) {
                $stmt = $automationDb->prepare("INSERT INTO canned_responses 
                    (category, title, body, variables) VALUES (:cat, :title, :body, '[]')");
                $stmt->bindValue(':cat', $_POST['canned_category'] ?? 'general', SQLITE3_TEXT);
                $stmt->bindValue(':title', $_POST['canned_title'], SQLITE3_TEXT);
                $stmt->bindValue(':body', $replyText, SQLITE3_TEXT);
                $stmt->execute();
            }
            
            $message = 'Reply sent!';
            $messageType = 'success';
        }
    }
    
    // Resolve ticket
    if ($action === 'resolve') {
        $ticketId = $_POST['ticket_id'];
        $automationDb->exec("UPDATE support_tickets SET 
            status = 'resolved',
            resolved_at = datetime('now'),
            updated_at = datetime('now')
            WHERE id = $ticketId");
        $message = 'Ticket resolved!';
        $messageType = 'success';
    }
    
    // Close ticket
    if ($action === 'close') {
        $ticketId = $_POST['ticket_id'];
        $automationDb->exec("UPDATE support_tickets SET 
            status = 'closed',
            resolved_at = datetime('now'),
            updated_at = datetime('now')
            WHERE id = $ticketId");
        $message = 'Ticket closed!';
        $messageType = 'success';
    }
    
    // Escalate (VIP)
    if ($action === 'escalate') {
        $ticketId = $_POST['ticket_id'];
        $ticket = getTicket($automationDb, $ticketId);
        $currentTier = $ticket['tier_resolved'] ?? 3;
        
        // Log escalation
        $automationDb->exec("INSERT INTO ticket_escalations 
            (ticket_id, from_tier, to_tier, reason) 
            VALUES ($ticketId, $currentTier, 5, 'Manual escalation to VIP handling')");
        
        // Update ticket
        $automationDb->exec("UPDATE support_tickets SET 
            priority = 'urgent',
            tier_resolved = 5,
            updated_at = datetime('now')
            WHERE id = $ticketId");
        
        $message = 'Ticket escalated to VIP handling!';
        $messageType = 'success';
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM support_tickets WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}
if ($categoryFilter) {
    $sql .= " AND category = :category";
    $params[':category'] = $categoryFilter;
}
if ($priorityFilter) {
    $sql .= " AND priority = :priority";
    $params[':priority'] = $priorityFilter;
}
if ($searchQuery) {
    $sql .= " AND (ticket_number LIKE :search OR subject LIKE :search OR customer_email LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

// Sort: VIP first, then urgent, then by date
$sql .= " ORDER BY is_vip DESC, 
          CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
          created_at DESC";

$stmt = $automationDb->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, SQLITE3_TEXT);
}
$result = $stmt->execute();

$tickets = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    // Get suggestions for each ticket
    $content = $row['subject'] . ' ' . $row['message'];
    
    // KB suggestion
    $kbMatch = $kbResolver->findBestMatch($content);
    $row['kb_suggestion'] = $kbMatch;
    
    // Canned suggestions
    $row['canned_suggestions'] = $cannedSuggester->getSuggestions($content, $row['category'], 3);
    
    // Self-service suggestion
    $row['self_service_suggestion'] = detectSelfService($automationDb, $content);
    
    // Response count
    $row['response_count'] = $automationDb->querySingle("SELECT COUNT(*) FROM ticket_responses WHERE ticket_id = {$row['id']}");
    
    $tickets[] = $row;
}

// Get stats
$stats = [
    'total' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets"),
    'open' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE status NOT IN ('resolved', 'closed')"),
    'urgent' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE priority = 'urgent' AND status NOT IN ('resolved', 'closed')"),
    'vip' => $automationDb->querySingle("SELECT COUNT(*) FROM support_tickets WHERE is_vip = 1 AND status NOT IN ('resolved', 'closed')")
];

// Helper functions
function getTicket($db, $id) {
    return $db->querySingle("SELECT * FROM support_tickets WHERE id = $id", true);
}

function addResponse($db, $ticketId, $senderType, $message, $isAuto, $cannedId) {
    $stmt = $db->prepare("INSERT INTO ticket_responses 
        (ticket_id, sender_type, message, is_auto_response, canned_response_id)
        VALUES (:tid, :sender, :msg, :auto, :canned)");
    $stmt->bindValue(':tid', $ticketId, SQLITE3_INTEGER);
    $stmt->bindValue(':sender', $senderType, SQLITE3_TEXT);
    $stmt->bindValue(':msg', $message, SQLITE3_TEXT);
    $stmt->bindValue(':auto', $isAuto ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(':canned', $cannedId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Update response count
    $db->exec("UPDATE support_tickets SET 
        response_count = response_count + 1,
        first_response_at = COALESCE(first_response_at, datetime('now')),
        updated_at = datetime('now')
        WHERE id = $ticketId");
}

function updateTicketStatus($db, $ticketId, $status, $tier, $method, $cannedId = null, $kbId = null) {
    $sql = "UPDATE support_tickets SET 
        status = '$status',
        tier_resolved = $tier,
        resolution_method = '$method',
        updated_at = datetime('now')";
    
    if ($cannedId) $sql .= ", canned_response_id = $cannedId";
    if ($kbId) $sql .= ", auto_resolution_id = $kbId";
    
    $sql .= " WHERE id = $ticketId";
    $db->exec($sql);
}

function detectSelfService($db, $content) {
    $content = strtolower($content);
    $actions = $db->query("SELECT * FROM self_service_actions WHERE is_active = 1");
    
    while ($action = $actions->fetchArray(SQLITE3_ASSOC)) {
        $keywords = array_map('trim', explode(',', strtolower($action['trigger_keywords'])));
        foreach ($keywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                return $action;
            }
        }
    }
    return null;
}

function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hrs ago';
    return floor($diff / 86400) . ' days ago';
}

function getStatusBadge($status) {
    $badges = [
        'new' => ['🆕', 'rgba(0,212,255,0.2)', '#00d4ff'],
        'auto_resolved' => ['🤖', 'rgba(0,200,100,0.2)', '#00c864'],
        'pending_confirmation' => ['⏳', 'rgba(255,180,0,0.2)', '#ffb400'],
        'awaiting_response' => ['💬', 'rgba(123,44,191,0.2)', '#9b59b6'],
        'in_progress' => ['🔄', 'rgba(0,212,255,0.2)', '#00d4ff'],
        'resolved' => ['✅', 'rgba(0,200,100,0.2)', '#00c864'],
        'closed' => ['📁', 'rgba(100,100,100,0.2)', '#888']
    ];
    $b = $badges[$status] ?? ['❓', 'rgba(100,100,100,0.2)', '#888'];
    return "<span style='padding: 4px 10px; background: {$b[1]}; color: {$b[2]}; border-radius: 12px; font-size: 0.8rem;'>{$b[0]} " . ucfirst(str_replace('_', ' ', $status)) . "</span>";
}

function getPriorityBadge($priority, $isVip) {
    if ($isVip) {
        return "<span style='padding: 4px 10px; background: rgba(255,215,0,0.2); color: #ffd700; border-radius: 12px; font-size: 0.8rem;'>👑 VIP</span>";
    }
    $colors = [
        'urgent' => ['⚡', 'rgba(255,80,80,0.2)', '#ff5050'],
        'high' => ['🔥', 'rgba(255,180,0,0.2)', '#ffb400'],
        'normal' => ['', 'transparent', 'transparent'],
        'low' => ['', 'transparent', 'transparent']
    ];
    $c = $colors[$priority] ?? $colors['normal'];
    if (!$c[0]) return '';
    return "<span style='padding: 4px 10px; background: {$c[1]}; color: {$c[2]}; border-radius: 12px; font-size: 0.8rem;'>{$c[0]} " . ucfirst($priority) . "</span>";
}

$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - TrueVault Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 1600px; margin: 0 auto; }
        
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: <?php echo $textColor; ?>;
            text-decoration: none;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Quick Stats */
        .quick-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .quick-stat {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .quick-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .quick-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .quick-stat-label {
            font-size: 0.8rem;
            color: #888;
        }
        
        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?php echo $textColor; ?>;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .filter-select {
            padding: 12px 15px;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?php echo $textColor; ?>;
            min-width: 130px;
        }
        
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.85rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            color: #fff;
        }
        
        .btn-secondary {
            background: <?php echo $cardBg; ?>;
            color: <?php echo $textColor; ?>;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-success {
            background: rgba(0,200,100,0.2);
            color: #00c864;
            border: 1px solid rgba(0,200,100,0.3);
        }
        
        .btn-warning {
            background: rgba(255,180,0,0.2);
            color: #ffb400;
            border: 1px solid rgba(255,180,0,0.3);
        }
        
        .btn-danger {
            background: rgba(255,80,80,0.2);
            color: #ff5050;
            border: 1px solid rgba(255,80,80,0.3);
        }
        
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }
        
        /* Alert */
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success { background: rgba(0,200,100,0.1); border: 1px solid rgba(0,200,100,0.3); color: #00c864; }
        .alert-error { background: rgba(255,80,80,0.1); border: 1px solid rgba(255,80,80,0.3); color: #ff5050; }
        
        /* Ticket List */
        .ticket-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .ticket-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s;
        }
        
        .ticket-card:hover { border-color: <?php echo $primaryColor; ?>40; }
        
        .ticket-card.vip { border-left: 3px solid #ffd700; }
        .ticket-card.urgent { border-left: 3px solid #ff5050; }
        
        .ticket-main {
            padding: 20px;
        }
        
        .ticket-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .ticket-badges {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .ticket-number {
            font-family: monospace;
            color: <?php echo $primaryColor; ?>;
            font-weight: 600;
        }
        
        .ticket-time {
            color: #666;
            font-size: 0.85rem;
        }
        
        .ticket-subject {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #fff;
        }
        
        .ticket-from {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .ticket-category {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .cat-billing { background: rgba(0,212,255,0.15); color: #00d4ff; }
        .cat-technical { background: rgba(255,180,0,0.15); color: #ffb400; }
        .cat-account { background: rgba(123,44,191,0.15); color: #9b59b6; }
        .cat-general { background: rgba(255,255,255,0.1); color: #aaa; }
        
        /* Suggestion Panels */
        .suggestions-area {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 15px 20px;
            background: rgba(0,0,0,0.2);
        }
        
        .suggestion-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
        }
        
        .suggestion-box:last-child { margin-bottom: 0; }
        
        .suggestion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .suggestion-type {
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .suggestion-type.kb { color: #00c864; }
        .suggestion-type.self-service { color: <?php echo $primaryColor; ?>; }
        .suggestion-type.canned { color: #ffb400; }
        
        .suggestion-content {
            font-size: 0.9rem;
            color: #ccc;
            margin-bottom: 8px;
        }
        
        .confidence-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .confidence-high { background: rgba(0,200,100,0.2); color: #00c864; }
        .confidence-medium { background: rgba(255,180,0,0.2); color: #ffb400; }
        
        .canned-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .canned-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: rgba(255,255,255,0.02);
            border-radius: 6px;
        }
        
        .canned-title {
            font-size: 0.85rem;
            color: #ccc;
        }
        
        /* Ticket Actions */
        .ticket-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            background: rgba(0,0,0,0.15);
        }
        
        /* Customer Context */
        .customer-context {
            display: flex;
            gap: 15px;
            padding: 10px 20px;
            background: rgba(0,0,0,0.1);
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
            color: #888;
            flex-wrap: wrap;
        }
        
        .context-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.85);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 20px;
        }
        
        .modal.show { display: flex; }
        
        .modal-content {
            background: #1a1a2e;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .modal-title { font-size: 1.2rem; font-weight: 600; }
        
        .modal-close {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .modal-body { padding: 20px; }
        
        .conversation-thread {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .message-bubble {
            margin-bottom: 15px;
            max-width: 85%;
        }
        
        .message-bubble.customer { margin-right: auto; }
        .message-bubble.admin { margin-left: auto; }
        .message-bubble.system { margin: 0 auto; max-width: 95%; }
        
        .message-header {
            font-size: 0.8rem;
            color: #888;
            margin-bottom: 5px;
        }
        
        .message-content {
            padding: 12px 15px;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .customer .message-content {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        
        .admin .message-content {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>30, <?php echo $secondaryColor; ?>30);
            color: #fff;
        }
        
        .system .message-content {
            background: rgba(0,200,100,0.1);
            border: 1px solid rgba(0,200,100,0.2);
            color: #00c864;
        }
        
        .reply-box textarea {
            width: 100%;
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
            font-size: 0.95rem;
        }
        
        .reply-options {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .save-canned-option {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) {
            .quick-stats { flex-direction: column; }
            .ticket-header { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="page-title">🎫 Support Tickets</h1>
            </div>
            <div>
                <a href="knowledge-base.php" class="btn btn-secondary">
                    <i class="fas fa-book"></i> Knowledge Base
                </a>
                <a href="canned-responses.php" class="btn btn-secondary">
                    <i class="fas fa-comments"></i> Canned Responses
                </a>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="quick-stat">
                <div class="quick-stat-icon" style="background: rgba(0,212,255,0.2); color: #00d4ff;">
                    <i class="fas fa-inbox"></i>
                </div>
                <div>
                    <div class="quick-stat-value"><?php echo $stats['open']; ?></div>
                    <div class="quick-stat-label">Open Tickets</div>
                </div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-icon" style="background: rgba(255,80,80,0.2); color: #ff5050;">
                    <i class="fas fa-bolt"></i>
                </div>
                <div>
                    <div class="quick-stat-value"><?php echo $stats['urgent']; ?></div>
                    <div class="quick-stat-label">Urgent</div>
                </div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-icon" style="background: rgba(255,215,0,0.2); color: #ffd700;">
                    <i class="fas fa-crown"></i>
                </div>
                <div>
                    <div class="quick-stat-value"><?php echo $stats['vip']; ?></div>
                    <div class="quick-stat-label">VIP Tickets</div>
                </div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-icon" style="background: rgba(0,200,100,0.2); color: #00c864;">
                    <i class="fas fa-check-double"></i>
                </div>
                <div>
                    <div class="quick-stat-value"><?php echo $stats['total']; ?></div>
                    <div class="quick-stat-label">Total</div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search tickets..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>🆕 New</option>
                <option value="auto_resolved" <?php echo $statusFilter === 'auto_resolved' ? 'selected' : ''; ?>>🤖 Auto-Resolved</option>
                <option value="pending_confirmation" <?php echo $statusFilter === 'pending_confirmation' ? 'selected' : ''; ?>>⏳ Pending</option>
                <option value="awaiting_response" <?php echo $statusFilter === 'awaiting_response' ? 'selected' : ''; ?>>💬 Awaiting</option>
                <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>✅ Resolved</option>
                <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>📁 Closed</option>
            </select>
            <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <option value="billing" <?php echo $categoryFilter === 'billing' ? 'selected' : ''; ?>>Billing</option>
                <option value="technical" <?php echo $categoryFilter === 'technical' ? 'selected' : ''; ?>>Technical</option>
                <option value="account" <?php echo $categoryFilter === 'account' ? 'selected' : ''; ?>>Account</option>
                <option value="general" <?php echo $categoryFilter === 'general' ? 'selected' : ''; ?>>General</option>
            </select>
            <select name="priority" class="filter-select" onchange="this.form.submit()">
                <option value="">All Priority</option>
                <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>⚡ Urgent</option>
                <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>🔥 High</option>
                <option value="normal" <?php echo $priorityFilter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="?" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
        
        <!-- Ticket List -->
        <?php if (empty($tickets)): ?>
        <div style="text-align: center; padding: 60px; color: #666;">
            <i class="fas fa-inbox" style="font-size: 4rem; margin-bottom: 20px;"></i>
            <p style="font-size: 1.2rem;">No tickets found</p>
            <p>All caught up! 🎉</p>
        </div>
        <?php else: ?>
        <div class="ticket-list">
            <?php foreach ($tickets as $ticket): ?>
            <div class="ticket-card <?php echo $ticket['is_vip'] ? 'vip' : ($ticket['priority'] === 'urgent' ? 'urgent' : ''); ?>">
                <div class="ticket-main">
                    <div class="ticket-header">
                        <div class="ticket-badges">
                            <span class="ticket-number"><?php echo $ticket['ticket_number'] ?? '#' . $ticket['id']; ?></span>
                            <?php echo getStatusBadge($ticket['status']); ?>
                            <?php echo getPriorityBadge($ticket['priority'], $ticket['is_vip']); ?>
                            <?php if ($ticket['category']): ?>
                            <span class="ticket-category cat-<?php echo $ticket['category']; ?>">
                                <?php echo ucfirst($ticket['category']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <span class="ticket-time"><?php echo getTimeAgo($ticket['created_at']); ?></span>
                    </div>
                    
                    <div class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                    <div class="ticket-from">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($ticket['customer_email']); ?>
                        <?php if ($ticket['response_count'] > 0): ?>
                        <span style="margin-left: 15px;"><i class="fas fa-comments"></i> <?php echo $ticket['response_count']; ?> responses</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Suggestions Area -->
                <?php if ($ticket['status'] === 'new' || $ticket['status'] === 'in_progress'): ?>
                <div class="suggestions-area">
                    <!-- KB Suggestion -->
                    <?php if ($ticket['kb_suggestion']): ?>
                    <div class="suggestion-box">
                        <div class="suggestion-header">
                            <span class="suggestion-type kb"><i class="fas fa-robot"></i> AUTO-RESOLUTION AVAILABLE</span>
                            <span class="confidence-badge <?php echo $ticket['kb_suggestion']['score'] >= 0.8 ? 'confidence-high' : 'confidence-medium'; ?>">
                                <?php echo round($ticket['kb_suggestion']['score'] * 100); ?>% match
                            </span>
                        </div>
                        <div class="suggestion-content">
                            <?php echo htmlspecialchars($ticket['kb_suggestion']['entry']['question']); ?>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="send_auto">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <input type="hidden" name="kb_id" value="<?php echo $ticket['kb_suggestion']['entry']['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-paper-plane"></i> Send Auto-Reply
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="previewKB(<?php echo htmlspecialchars(json_encode($ticket['kb_suggestion']['entry'])); ?>)">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Self-Service Suggestion -->
                    <?php if ($ticket['self_service_suggestion']): ?>
                    <div class="suggestion-box">
                        <div class="suggestion-header">
                            <span class="suggestion-type self-service"><i class="fas fa-magic"></i> SELF-SERVICE REDIRECT</span>
                        </div>
                        <div class="suggestion-content">
                            Customer can use: <strong><?php echo htmlspecialchars($ticket['self_service_suggestion']['display_name']); ?></strong>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="send_self_service">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <input type="hidden" name="action_key" value="<?php echo $ticket['self_service_suggestion']['action_key']; ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-external-link-alt"></i> Send Self-Service Link
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Canned Responses -->
                    <?php if (!empty($ticket['canned_suggestions'])): ?>
                    <div class="suggestion-box">
                        <div class="suggestion-header">
                            <span class="suggestion-type canned"><i class="fas fa-comment-dots"></i> SUGGESTED RESPONSES</span>
                        </div>
                        <div class="canned-list">
                            <?php foreach ($ticket['canned_suggestions'] as $canned): ?>
                            <div class="canned-item">
                                <span class="canned-title"><?php echo htmlspecialchars($canned['response']['title']); ?></span>
                                <div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="send_canned">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="canned_id" value="<?php echo $canned['response']['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="fas fa-paper-plane"></i> Send
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Customer Context -->
                <div class="customer-context">
                    <span class="context-item">
                        <i class="fas fa-calendar"></i> Created: <?php echo date('M j, g:ia', strtotime($ticket['created_at'])); ?>
                    </span>
                    <?php if ($ticket['tier_resolved']): ?>
                    <span class="context-item">
                        <i class="fas fa-layer-group"></i> Tier <?php echo $ticket['tier_resolved']; ?> (<?php echo $ticket['resolution_method'] ?? 'pending'; ?>)
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <div class="ticket-actions">
                    <button class="btn btn-sm btn-primary" onclick="openReplyModal(<?php echo $ticket['id']; ?>, '<?php echo addslashes($ticket['subject']); ?>', '<?php echo addslashes($ticket['customer_email']); ?>')">
                        <i class="fas fa-reply"></i> Reply
                    </button>
                    <?php if ($ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="resolve">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-check"></i> Resolve
                        </button>
                    </form>
                    <?php if (!$ticket['is_vip']): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="escalate">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="fas fa-arrow-up"></i> Escalate
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-secondary" onclick="viewConversation(<?php echo $ticket['id']; ?>)">
                        <i class="fas fa-history"></i> History
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Reply Modal -->
    <div class="modal" id="replyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-reply"></i> Reply to Ticket</h3>
                <button class="modal-close" onclick="closeReplyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="replyTicketInfo" style="margin-bottom: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <strong id="replySubject"></strong><br>
                    <small id="replyEmail" style="color: #888;"></small>
                </div>
                
                <form method="POST" id="replyForm">
                    <input type="hidden" name="action" value="send_reply">
                    <input type="hidden" name="ticket_id" id="replyTicketId">
                    
                    <div class="reply-box">
                        <textarea name="reply_text" placeholder="Type your reply here..." required></textarea>
                    </div>
                    
                    <div class="reply-options">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="save_as_canned" id="saveCannedCheck" onchange="toggleCannedOptions()">
                            Save as canned response
                        </label>
                        <div id="cannedOptions" style="display: none; flex: 1;">
                            <input type="text" name="canned_title" placeholder="Response title..." 
                                   style="padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #fff; width: 200px;">
                            <select name="canned_category" style="padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #fff; margin-left: 10px;">
                                <option value="billing">Billing</option>
                                <option value="technical">Technical</option>
                                <option value="account">Account</option>
                                <option value="general" selected>General</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeReplyModal()">Cancel</button>
                <button type="submit" form="replyForm" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
            </div>
        </div>
    </div>
    
    <!-- Conversation Modal -->
    <div class="modal" id="conversationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-comments"></i> Conversation History</h3>
                <button class="modal-close" onclick="closeConversationModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="conversationThread" class="conversation-thread">
                    <p style="color: #888; text-align: center;">Loading...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openReplyModal(ticketId, subject, email) {
            document.getElementById('replyTicketId').value = ticketId;
            document.getElementById('replySubject').textContent = subject;
            document.getElementById('replyEmail').textContent = email;
            document.getElementById('replyModal').classList.add('show');
        }
        
        function closeReplyModal() {
            document.getElementById('replyModal').classList.remove('show');
        }
        
        function toggleCannedOptions() {
            const checked = document.getElementById('saveCannedCheck').checked;
            document.getElementById('cannedOptions').style.display = checked ? 'block' : 'none';
        }
        
        function viewConversation(ticketId) {
            document.getElementById('conversationModal').classList.add('show');
            document.getElementById('conversationThread').innerHTML = '<p style="color: #888; text-align: center;">Loading...</p>';
            
            // Fetch conversation (simplified - in production use AJAX)
            fetch('support-api.php?action=conversation&ticket_id=' + ticketId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.messages) {
                        let html = '';
                        data.messages.forEach(msg => {
                            const type = msg.sender_type;
                            html += `
                                <div class="message-bubble ${type}">
                                    <div class="message-header">
                                        ${type === 'customer' ? '👤 Customer' : (type === 'admin' ? '👨‍💼 Admin' : '🤖 System')}
                                        • ${msg.created_at}
                                    </div>
                                    <div class="message-content">${msg.message}</div>
                                </div>
                            `;
                        });
                        document.getElementById('conversationThread').innerHTML = html || '<p style="color: #888; text-align: center;">No messages yet</p>';
                    }
                })
                .catch(() => {
                    document.getElementById('conversationThread').innerHTML = '<p style="color: #888; text-align: center;">Could not load conversation</p>';
                });
        }
        
        function closeConversationModal() {
            document.getElementById('conversationModal').classList.remove('show');
        }
        
        function previewKB(entry) {
            alert('KB Entry: ' + entry.question + '\n\n' + entry.answer);
        }
        
        // Close modals on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeReplyModal();
                closeConversationModal();
            }
        });
        
        // Close modals on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal')) {
                    closeReplyModal();
                    closeConversationModal();
                }
            });
        });
    </script>
</body>
</html>
<?php
$automationDb->close();
