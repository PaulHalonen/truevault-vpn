<?php
/**
 * TrueVault VPN - Admin Support Tickets Management
 * Task 7.11 - Admin ticket dashboard
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e;
            color: #fff;
            min-height: 100vh;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header h1 { color: #00d9ff; }
        
        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 5px; }
        
        /* Filters */
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn:hover { background: rgba(255,255,255,0.1); }
        .filter-btn.active { background: #00d9ff; color: #0f0f1a; border-color: transparent; }
        
        /* Table */
        .table-container {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; }
        th {
            background: rgba(255,255,255,0.05);
            color: #888;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        tr { border-bottom: 1px solid rgba(255,255,255,0.05); }
        tr:hover { background: rgba(255,255,255,0.02); }
        tr:last-child { border-bottom: none; }
        
        /* Status badges */
        .status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-open { background: rgba(0,217,255,0.15); color: #00d9ff; }
        .status-in_progress { background: rgba(255,193,7,0.15); color: #ffc107; }
        .status-resolved { background: rgba(0,255,136,0.15); color: #00ff88; }
        .status-closed { background: rgba(136,136,136,0.15); color: #888; }
        
        /* Priority */
        .priority { font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; }
        .priority-low { background: rgba(136,136,136,0.15); color: #888; }
        .priority-normal { background: rgba(0,217,255,0.15); color: #00d9ff; }
        .priority-high { background: rgba(255,193,7,0.15); color: #ffc107; }
        .priority-urgent { background: rgba(255,107,107,0.15); color: #ff6b6b; }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary { background: #00d9ff; color: #0f0f1a; }
        .btn-success { background: #00ff88; color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn:hover { opacity: 0.9; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #1a1a2e;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 25px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .modal-close {
            background: none;
            border: none;
            color: #888;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Messages */
        .messages { display: flex; flex-direction: column; gap: 15px; margin: 20px 0; max-height: 300px; overflow-y: auto; }
        .message { padding: 15px; border-radius: 10px; max-width: 85%; }
        .message.user { background: rgba(0,217,255,0.1); align-self: flex-end; }
        .message.staff { background: rgba(0,255,136,0.1); align-self: flex-start; }
        .message-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.8rem; color: #888; }
        .message-content { color: #fff; line-height: 1.5; }
        
        /* Reply form */
        .reply-form { margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .reply-form textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 10px;
        }
        .reply-actions { display: flex; gap: 10px; }
        
        /* Ticket info */
        .ticket-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
        }
        .ticket-info-item label { display: block; color: #888; font-size: 0.8rem; margin-bottom: 5px; }
        .ticket-info-item span { color: #fff; font-weight: 500; }
        
        /* Actions */
        .action-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        
        @media (max-width: 768px) {
            table { font-size: 0.85rem; }
            th, td { padding: 8px 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Support Tickets</h1>
            <a href="/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-value" id="stat-total">0</div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="stat-open">0</div>
                <div class="stat-label">Open</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="stat-progress">0</div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="stat-resolved">0</div>
                <div class="stat-label">Resolved Today</div>
            </div>
        </div>
        
        <div class="filters">
            <button class="filter-btn active" data-status="all">All</button>
            <button class="filter-btn" data-status="open">Open</button>
            <button class="filter-btn" data-status="in_progress">In Progress</button>
            <button class="filter-btn" data-status="resolved">Resolved</button>
            <button class="filter-btn" data-status="closed">Closed</button>
            <span style="margin-left: auto;"></span>
            <button class="filter-btn" data-priority="urgent">🔴 Urgent</button>
            <button class="filter-btn" data-priority="high">🟠 High</button>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ticketTable">
                    <tr><td colspan="8" style="text-align: center; padding: 40px; color: #555;">Loading tickets...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- View Ticket Modal -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 id="modalSubject">Ticket Subject</h2>
                    <span id="modalNumber" style="color: #888; font-size: 0.9rem;">#TKT-000000</span>
                </div>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="ticket-info" id="ticketInfo">
                <div class="ticket-info-item">
                    <label>Status</label>
                    <span id="infoStatus">-</span>
                </div>
                <div class="ticket-info-item">
                    <label>Priority</label>
                    <span id="infoPriority">-</span>
                </div>
                <div class="ticket-info-item">
                    <label>Category</label>
                    <span id="infoCategory">-</span>
                </div>
                <div class="ticket-info-item">
                    <label>Customer</label>
                    <span id="infoCustomer">-</span>
                </div>
            </div>
            
            <div class="action-btns">
                <button class="btn btn-primary" onclick="updateStatus('in_progress')">📝 Mark In Progress</button>
                <button class="btn btn-success" onclick="updateStatus('resolved')">✅ Mark Resolved</button>
                <button class="btn btn-secondary" onclick="updateStatus('closed')">🔒 Close</button>
            </div>
            
            <div id="ticketMessages" class="messages"></div>
            
            <div class="reply-form">
                <textarea id="replyMessage" placeholder="Type your reply..."></textarea>
                <div class="reply-actions">
                    <button class="btn btn-primary" onclick="sendReply()">📤 Send Reply</button>
                    <select id="cannedResponse" onchange="insertCanned()">
                        <option value="">-- Canned Responses --</option>
                        <option value="greeting">Greeting</option>
                        <option value="thanks">Thank You</option>
                        <option value="resolved">Issue Resolved</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let tickets = [];
        let currentTicketId = null;
        let currentFilter = { status: 'all', priority: null };
        
        // Filter clicks
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.dataset.status) {
                    document.querySelectorAll('[data-status]').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter.status = this.dataset.status;
                }
                if (this.dataset.priority) {
                    this.classList.toggle('active');
                    currentFilter.priority = this.classList.contains('active') ? this.dataset.priority : null;
                }
                renderTickets();
            });
        });
        
        // Load tickets
        async function loadTickets() {
            try {
                const response = await fetch('/api/support/list-tickets.php');
                const data = await response.json();
                
                if (data.success) {
                    tickets = data.tickets;
                    updateStats();
                    renderTickets();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function updateStats() {
            document.getElementById('stat-total').textContent = tickets.length;
            document.getElementById('stat-open').textContent = tickets.filter(t => t.status === 'open').length;
            document.getElementById('stat-progress').textContent = tickets.filter(t => t.status === 'in_progress').length;
            
            const today = new Date().toISOString().split('T')[0];
            const resolvedToday = tickets.filter(t => t.resolved_at && t.resolved_at.startsWith(today)).length;
            document.getElementById('stat-resolved').textContent = resolvedToday;
        }
        
        function renderTickets() {
            let filtered = tickets;
            
            if (currentFilter.status !== 'all') {
                filtered = filtered.filter(t => t.status === currentFilter.status);
            }
            if (currentFilter.priority) {
                filtered = filtered.filter(t => t.priority === currentFilter.priority);
            }
            
            const tbody = document.getElementById('ticketTable');
            
            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #555;">No tickets found</td></tr>';
                return;
            }
            
            tbody.innerHTML = filtered.map(ticket => `
                <tr>
                    <td><span style="font-family: monospace;">#${ticket.ticket_number}</span></td>
                    <td><strong>${escapeHtml(ticket.subject)}</strong></td>
                    <td>${ticket.user_email}</td>
                    <td>${ticket.category}</td>
                    <td><span class="priority priority-${ticket.priority}">${ticket.priority}</span></td>
                    <td><span class="status status-${ticket.status}">${ticket.status.replace('_', ' ')}</span></td>
                    <td>${formatDate(ticket.created_at)}</td>
                    <td>
                        <button class="btn btn-primary" onclick="viewTicket(${ticket.id})">View</button>
                    </td>
                </tr>
            `).join('');
        }
        
        async function viewTicket(ticketId) {
            currentTicketId = ticketId;
            
            try {
                const response = await fetch('/api/support/get-ticket.php?id=' + ticketId);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('modalSubject').textContent = data.ticket.subject;
                    document.getElementById('modalNumber').textContent = '#' + data.ticket.ticket_number;
                    document.getElementById('infoStatus').innerHTML = `<span class="status status-${data.ticket.status}">${data.ticket.status.replace('_', ' ')}</span>`;
                    document.getElementById('infoPriority').innerHTML = `<span class="priority priority-${data.ticket.priority}">${data.ticket.priority}</span>`;
                    document.getElementById('infoCategory').textContent = data.ticket.category;
                    document.getElementById('infoCustomer').textContent = tickets.find(t => t.id == ticketId)?.user_email || 'Unknown';
                    
                    const messagesContainer = document.getElementById('ticketMessages');
                    messagesContainer.innerHTML = `
                        <div class="message user">
                            <div class="message-header">
                                <span>Customer</span>
                                <span>${formatDate(data.ticket.created_at)}</span>
                            </div>
                            <div class="message-content">${escapeHtml(data.ticket.description)}</div>
                        </div>
                    ` + data.messages.map(msg => `
                        <div class="message ${msg.is_staff ? 'staff' : 'user'}">
                            <div class="message-header">
                                <span>${msg.is_staff ? '👨‍💼 Support' : 'Customer'}</span>
                                <span>${formatDate(msg.created_at)}</span>
                            </div>
                            <div class="message-content">${escapeHtml(msg.message)}</div>
                        </div>
                    `).join('');
                    
                    document.getElementById('ticketModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function sendReply() {
            const message = document.getElementById('replyMessage').value.trim();
            if (!message) return;
            
            try {
                const response = await fetch('/api/support/add-message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ticket_id: currentTicketId,
                        message: message,
                        is_staff: true,
                        sender_name: 'Support'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    document.getElementById('replyMessage').value = '';
                    viewTicket(currentTicketId);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function updateStatus(newStatus) {
            // TODO: Add status update API
            alert('Status update: ' + newStatus + ' - API endpoint needed');
        }
        
        function insertCanned() {
            const select = document.getElementById('cannedResponse');
            const textarea = document.getElementById('replyMessage');
            
            const responses = {
                greeting: 'Hello! Thank you for contacting TrueVault Support. I would be happy to help you with your inquiry.',
                thanks: 'Thank you for your patience. If you have any other questions, please do not hesitate to ask.',
                resolved: 'I am glad I could help resolve this issue! If everything is working as expected, I will go ahead and close this ticket. Feel free to open a new one if you need further assistance.'
            };
            
            if (select.value && responses[select.value]) {
                textarea.value = responses[select.value];
            }
            select.value = '';
        }
        
        function closeModal() {
            document.getElementById('ticketModal').classList.remove('active');
        }
        
        // Close on outside click
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // Helpers
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
            if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
            return date.toLocaleDateString();
        }
        
        // Initial load
        loadTickets();
        
        // Refresh every 30 seconds
        setInterval(loadTickets, 30000);
    </script>
</body>
</html>
