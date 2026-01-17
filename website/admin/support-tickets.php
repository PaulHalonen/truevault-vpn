<?php
/**
 * TrueVault VPN - Admin Support Ticket Management
 * View and respond to all support tickets
 */

session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Workflows.php';

// Simple admin auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin/');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $db = new Database('support');
    $mainDb = new Database('main');
    
    switch ($_POST['action']) {
        case 'get_tickets':
            $status = $_POST['status'] ?? 'all';
            $where = $status !== 'all' ? "WHERE t.status = '$status'" : "";
            
            $stmt = $db->query("
                SELECT t.*, u.email, u.name as user_name
                FROM support_tickets t
                LEFT JOIN main.users u ON t.user_id = u.id
                $where
                ORDER BY 
                    CASE t.priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'normal' THEN 3 
                        ELSE 4 
                    END,
                    t.updated_at DESC
                LIMIT 100
            ");
            // Note: Cross-database query may not work, fetch separately if needed
            
            $tickets = [];
            foreach ($db->query("SELECT * FROM support_tickets " . ($status !== 'all' ? "WHERE status = '$status'" : "") . " ORDER BY updated_at DESC LIMIT 100")->fetchAll() as $t) {
                $userStmt = $mainDb->prepare("SELECT email, name FROM users WHERE id = ?");
                $userStmt->execute([$t['user_id']]);
                $user = $userStmt->fetch();
                $t['email'] = $user['email'] ?? 'Unknown';
                $t['user_name'] = $user['name'] ?? 'Unknown';
                
                // Get message count
                $msgStmt = $db->prepare("SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = ?");
                $msgStmt->execute([$t['id']]);
                $t['message_count'] = $msgStmt->fetchColumn();
                
                $tickets[] = $t;
            }
            
            echo json_encode(['success' => true, 'tickets' => $tickets]);
            exit;
            
        case 'get_ticket':
            $ticketId = (int)$_POST['ticket_id'];
            
            $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ?");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                echo json_encode(['success' => false, 'error' => 'Ticket not found']);
                exit;
            }
            
            // Get user info
            $userStmt = $mainDb->prepare("SELECT email, name, tier FROM users WHERE id = ?");
            $userStmt->execute([$ticket['user_id']]);
            $user = $userStmt->fetch();
            $ticket['email'] = $user['email'] ?? 'Unknown';
            $ticket['user_name'] = $user['name'] ?? 'Unknown';
            $ticket['user_tier'] = $user['tier'] ?? 'free';
            
            // Get messages
            $msgStmt = $db->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
            $msgStmt->execute([$ticketId]);
            $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'ticket' => $ticket, 'messages' => $messages]);
            exit;
            
        case 'reply':
            $ticketId = (int)$_POST['ticket_id'];
            $message = trim($_POST['message'] ?? '');
            
            if (empty($message)) {
                echo json_encode(['success' => false, 'error' => 'Message required']);
                exit;
            }
            
            // Add staff message
            $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, is_staff, message) VALUES (?, 1, ?)");
            $stmt->execute([$ticketId, $message]);
            
            // Update ticket
            $stmt = $db->prepare("UPDATE support_tickets SET status = 'pending', updated_at = datetime('now') WHERE id = ?");
            $stmt->execute([$ticketId]);
            
            // Get user email for notification
            $stmt = $db->prepare("SELECT user_id FROM support_tickets WHERE id = ?");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            
            // Could send email notification here
            
            echo json_encode(['success' => true]);
            exit;
            
        case 'update_status':
            $ticketId = (int)$_POST['ticket_id'];
            $status = $_POST['status'];
            
            $validStatuses = ['open', 'pending', 'resolved', 'closed'];
            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'error' => 'Invalid status']);
                exit;
            }
            
            $resolvedAt = ($status === 'resolved' || $status === 'closed') ? "datetime('now')" : "NULL";
            
            $stmt = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = datetime('now'), resolved_at = $resolvedAt WHERE id = ?");
            $stmt->execute([$status, $ticketId]);
            
            // Trigger workflow if resolved
            if ($status === 'resolved') {
                try {
                    $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ?");
                    $stmt->execute([$ticketId]);
                    $t = $stmt->fetch();
                    
                    $userStmt = $mainDb->prepare("SELECT email, name FROM users WHERE id = ?");
                    $userStmt->execute([$t['user_id']]);
                    $user = $userStmt->fetch();
                    
                    $workflows = new Workflows();
                    $workflows->supportTicketResolved($ticketId, $t['user_id'], $user['email'], $user['name'] ?? 'Customer', $t['subject']);
                } catch (Exception $e) {
                    // Workflow error shouldn't fail status update
                }
            }
            
            echo json_encode(['success' => true]);
            exit;
            
        case 'update_priority':
            $ticketId = (int)$_POST['ticket_id'];
            $priority = $_POST['priority'];
            
            $validPriorities = ['low', 'normal', 'high', 'urgent'];
            if (!in_array($priority, $validPriorities)) {
                echo json_encode(['success' => false, 'error' => 'Invalid priority']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE support_tickets SET priority = ? WHERE id = ?");
            $stmt->execute([$priority, $ticketId]);
            
            echo json_encode(['success' => true]);
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Initialize database
$db = new Database('support');
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
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");

// Get stats
$stats = [
    'open' => $db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'pending'")->fetchColumn(),
    'urgent' => $db->query("SELECT COUNT(*) FROM support_tickets WHERE priority = 'urgent' AND status IN ('open', 'pending')")->fetchColumn(),
    'today' => $db->query("SELECT COUNT(*) FROM support_tickets WHERE date(created_at) = date('now')")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Admin - TrueVault VPN</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .admin-header { background: linear-gradient(90deg, #1a1a2e, #16213e); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { font-size: 1.5rem; }
        .admin-header a { color: #00d9ff; text-decoration: none; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; text-align: center; }
        .stat-value { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 13px; margin-top: 5px; }
        .stat-card.urgent .stat-value { background: linear-gradient(90deg, #ff6b6b, #ffc107); -webkit-background-clip: text; }
        
        .content-grid { display: grid; grid-template-columns: 1fr 400px; gap: 30px; }
        
        .tickets-panel { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .panel-header h2 { font-size: 1.2rem; }
        
        .filter-tabs { display: flex; gap: 10px; }
        .filter-tab { padding: 8px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #888; cursor: pointer; font-size: 13px; }
        .filter-tab:hover, .filter-tab.active { border-color: #00d9ff; color: #00d9ff; }
        
        .ticket-list { max-height: 600px; overflow-y: auto; }
        .ticket-row { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; transition: background 0.2s; }
        .ticket-row:hover { background: rgba(255,255,255,0.03); }
        .ticket-row.selected { background: rgba(0,217,255,0.1); border-left: 3px solid #00d9ff; }
        .ticket-priority { width: 8px; height: 8px; border-radius: 50%; margin-right: 15px; }
        .priority-urgent { background: #ff6b6b; }
        .priority-high { background: #ffc107; }
        .priority-normal { background: #00d9ff; }
        .priority-low { background: #6c757d; }
        .ticket-info { flex: 1; }
        .ticket-subject { font-weight: 500; color: #fff; margin-bottom: 4px; }
        .ticket-meta { font-size: 12px; color: #888; }
        .ticket-status { font-size: 11px; padding: 3px 8px; border-radius: 10px; font-weight: 600; }
        .status-open { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .status-pending { background: rgba(255,193,7,0.2); color: #ffc107; }
        .status-resolved { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-closed { background: rgba(108,117,125,0.2); color: #6c757d; }
        
        .detail-panel { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; height: fit-content; }
        .detail-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .detail-title { font-size: 1.1rem; margin-bottom: 10px; }
        .detail-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; }
        .detail-meta-item { color: #888; }
        .detail-meta-item span { color: #fff; display: block; }
        
        .detail-actions { display: flex; gap: 10px; margin-bottom: 20px; }
        .detail-actions select { padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #fff; font-size: 13px; }
        
        .messages-container { max-height: 300px; overflow-y: auto; margin-bottom: 20px; }
        .message { margin-bottom: 15px; }
        .message.user { text-align: left; }
        .message.staff { text-align: right; }
        .message-bubble { display: inline-block; max-width: 90%; padding: 10px 14px; border-radius: 10px; text-align: left; font-size: 13px; }
        .message.user .message-bubble { background: rgba(255,255,255,0.1); }
        .message.staff .message-bubble { background: rgba(0,217,255,0.2); }
        .message-text { color: #fff; white-space: pre-wrap; }
        .message-time { font-size: 10px; color: #666; margin-top: 5px; }
        
        .reply-form textarea { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-size: 13px; resize: vertical; min-height: 100px; margin-bottom: 10px; }
        .reply-form textarea:focus { outline: none; border-color: #00d9ff; }
        .btn-reply { width: 100%; padding: 10px; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-reply:hover { opacity: 0.9; }
        
        .empty-state { text-align: center; padding: 40px; color: #666; }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🎫 Support Tickets</h1>
        <a href="/admin/">← Back to Admin</a>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['open'] ?></div>
                <div class="stat-label">Open Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending Reply</div>
            </div>
            <div class="stat-card urgent">
                <div class="stat-value"><?= $stats['urgent'] ?></div>
                <div class="stat-label">Urgent</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['today'] ?></div>
                <div class="stat-label">Today</div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="tickets-panel">
                <div class="panel-header">
                    <h2>All Tickets</h2>
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all">All</button>
                        <button class="filter-tab" data-filter="open">Open</button>
                        <button class="filter-tab" data-filter="pending">Pending</button>
                        <button class="filter-tab" data-filter="resolved">Resolved</button>
                    </div>
                </div>
                <div class="ticket-list" id="ticketList">
                    <div class="empty-state">Loading tickets...</div>
                </div>
            </div>
            
            <div class="detail-panel" id="detailPanel">
                <div class="empty-state">
                    <p>Select a ticket to view details</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentTicket = null;
        let currentFilter = 'all';
        
        // Load tickets
        loadTickets();
        
        // Filter tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentFilter = tab.dataset.filter;
                loadTickets();
            });
        });
        
        async function loadTickets() {
            const formData = new FormData();
            formData.append('action', 'get_tickets');
            formData.append('status', currentFilter);
            
            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success && data.tickets.length > 0) {
                    document.getElementById('ticketList').innerHTML = data.tickets.map(t => `
                        <div class="ticket-row" onclick="viewTicket(${t.id})" data-id="${t.id}">
                            <div class="ticket-priority priority-${t.priority}"></div>
                            <div class="ticket-info">
                                <div class="ticket-subject">${escapeHtml(t.subject)}</div>
                                <div class="ticket-meta">${t.email} • ${formatDate(t.created_at)} • ${t.message_count} msgs</div>
                            </div>
                            <span class="ticket-status status-${t.status}">${t.status}</span>
                        </div>
                    `).join('');
                } else {
                    document.getElementById('ticketList').innerHTML = '<div class="empty-state">No tickets found</div>';
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        async function viewTicket(ticketId) {
            currentTicket = ticketId;
            
            // Highlight selected
            document.querySelectorAll('.ticket-row').forEach(r => r.classList.remove('selected'));
            document.querySelector(`.ticket-row[data-id="${ticketId}"]`)?.classList.add('selected');
            
            const formData = new FormData();
            formData.append('action', 'get_ticket');
            formData.append('ticket_id', ticketId);
            
            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    const t = data.ticket;
                    const msgs = data.messages;
                    
                    document.getElementById('detailPanel').innerHTML = `
                        <div class="detail-header">
                            <div class="detail-title">#${t.id}: ${escapeHtml(t.subject)}</div>
                            <div class="detail-meta">
                                <div class="detail-meta-item">User<span>${t.email}</span></div>
                                <div class="detail-meta-item">Tier<span>${t.user_tier}</span></div>
                                <div class="detail-meta-item">Category<span>${t.category || 'General'}</span></div>
                                <div class="detail-meta-item">Created<span>${formatDate(t.created_at)}</span></div>
                            </div>
                        </div>
                        
                        <div class="detail-actions">
                            <select onchange="updateStatus(${t.id}, this.value)">
                                <option value="open" ${t.status === 'open' ? 'selected' : ''}>Open</option>
                                <option value="pending" ${t.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="resolved" ${t.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                <option value="closed" ${t.status === 'closed' ? 'selected' : ''}>Closed</option>
                            </select>
                            <select onchange="updatePriority(${t.id}, this.value)">
                                <option value="low" ${t.priority === 'low' ? 'selected' : ''}>Low</option>
                                <option value="normal" ${t.priority === 'normal' ? 'selected' : ''}>Normal</option>
                                <option value="high" ${t.priority === 'high' ? 'selected' : ''}>High</option>
                                <option value="urgent" ${t.priority === 'urgent' ? 'selected' : ''}>Urgent</option>
                            </select>
                        </div>
                        
                        <div class="messages-container">
                            ${msgs.map(m => `
                                <div class="message ${m.is_staff ? 'staff' : 'user'}">
                                    <div class="message-bubble">
                                        <div class="message-text">${escapeHtml(m.message)}</div>
                                        <div class="message-time">${m.is_staff ? 'Staff' : 'User'} • ${formatDate(m.created_at)}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div class="reply-form">
                            <textarea id="replyMessage" placeholder="Type your reply..."></textarea>
                            <button class="btn-reply" onclick="sendReply()">Send Reply</button>
                        </div>
                    `;
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        async function sendReply() {
            const message = document.getElementById('replyMessage').value.trim();
            if (!message) return alert('Please enter a message');
            
            const formData = new FormData();
            formData.append('action', 'reply');
            formData.append('ticket_id', currentTicket);
            formData.append('message', message);
            
            const response = await fetch('', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                viewTicket(currentTicket);
                loadTickets();
            }
        }
        
        async function updateStatus(ticketId, status) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('ticket_id', ticketId);
            formData.append('status', status);
            
            await fetch('', { method: 'POST', body: formData });
            loadTickets();
        }
        
        async function updatePriority(ticketId, priority) {
            const formData = new FormData();
            formData.append('action', 'update_priority');
            formData.append('ticket_id', ticketId);
            formData.append('priority', priority);
            
            await fetch('', { method: 'POST', body: formData });
            loadTickets();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
    </script>
</body>
</html>
