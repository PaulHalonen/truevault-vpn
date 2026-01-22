<?php
/**
 * TrueVault VPN - User Support Ticket Dashboard
 * Task 7.10 - Customer-facing support interface
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Check authentication (simplified for now)
session_start();
$userId = $_SESSION['user_id'] ?? 1; // Default for testing
$userEmail = $_SESSION['email'] ?? 'test@example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - TrueVault VPN</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            color: #fff;
            min-height: 100vh;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header h1 {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,217,255,0.3); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-secondary:hover { background: rgba(255,255,255,0.2); }
        
        /* Cards */
        .card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card h2 { color: #00d9ff; margin-bottom: 15px; font-size: 1.2rem; }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab:hover { background: rgba(255,255,255,0.1); }
        .tab.active { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; border-color: transparent; }
        
        /* Ticket List */
        .ticket-list { display: flex; flex-direction: column; gap: 15px; }
        .ticket-item {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ticket-item:hover { background: rgba(255,255,255,0.05); border-color: #00d9ff; }
        .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .ticket-subject { font-weight: 600; color: #fff; }
        .ticket-number { font-size: 0.8rem; color: #666; font-family: monospace; }
        .ticket-meta { display: flex; gap: 15px; font-size: 0.85rem; color: #888; }
        
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
        .priority-low { background: rgba(0,255,136,0.15); color: #00ff88; }
        .priority-normal { background: rgba(0,217,255,0.15); color: #00d9ff; }
        .priority-high { background: rgba(255,193,7,0.15); color: #ffc107; }
        .priority-urgent { background: rgba(255,107,107,0.15); color: #ff6b6b; }
        
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
            max-width: 600px;
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
        
        /* Form */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #888; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #00d9ff;
        }
        
        /* Messages */
        .messages { display: flex; flex-direction: column; gap: 15px; margin: 20px 0; }
        .message {
            padding: 15px;
            border-radius: 10px;
            max-width: 85%;
        }
        .message.user { background: rgba(0,217,255,0.1); align-self: flex-end; }
        .message.staff { background: rgba(0,255,136,0.1); align-self: flex-start; }
        .message-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.8rem; color: #888; }
        .message-content { color: #fff; line-height: 1.5; }
        
        /* Empty state */
        .empty {
            text-align: center;
            padding: 40px;
            color: #555;
        }
        .empty-icon { font-size: 3rem; margin-bottom: 15px; }
        
        /* Reply form */
        .reply-form {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .reply-form textarea {
            flex: 1;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            resize: none;
        }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; }
            .tabs { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Support Center</h1>
            <button class="btn btn-primary" onclick="openNewTicket()">+ New Ticket</button>
        </div>
        
        <div class="tabs">
            <div class="tab active" data-status="all">All Tickets</div>
            <div class="tab" data-status="open">Open</div>
            <div class="tab" data-status="in_progress">In Progress</div>
            <div class="tab" data-status="resolved">Resolved</div>
        </div>
        
        <div class="card">
            <div id="ticketList" class="ticket-list">
                <div class="empty">
                    <div class="empty-icon">🎫</div>
                    <p>Loading tickets...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Ticket Modal -->
    <div id="newTicketModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📝 Create New Ticket</h2>
                <button class="modal-close" onclick="closeModal('newTicketModal')">&times;</button>
            </div>
            <form id="newTicketForm">
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" required placeholder="Brief description of your issue">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="general">General Inquiry</option>
                        <option value="billing">Billing</option>
                        <option value="technical">Technical Support</option>
                        <option value="account">Account Issue</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required placeholder="Please describe your issue in detail..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Ticket</button>
            </form>
        </div>
    </div>
    
    <!-- View Ticket Modal -->
    <div id="viewTicketModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 id="ticketSubject">Ticket Subject</h2>
                    <span id="ticketNumber" class="ticket-number">#TKT-000000</span>
                </div>
                <button class="modal-close" onclick="closeModal('viewTicketModal')">&times;</button>
            </div>
            <div id="ticketStatus" style="margin-bottom: 15px;"></div>
            <div id="ticketMessages" class="messages"></div>
            <div class="reply-form">
                <textarea id="replyMessage" placeholder="Type your reply..."></textarea>
                <button class="btn btn-primary" onclick="sendReply()">Send</button>
            </div>
        </div>
    </div>
    
    <script>
        const userId = <?= json_encode($userId) ?>;
        const userEmail = <?= json_encode($userEmail) ?>;
        let currentTicketId = null;
        let currentStatus = 'all';
        
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentStatus = this.dataset.status;
                loadTickets();
            });
        });
        
        // Load tickets
        async function loadTickets() {
            try {
                const response = await fetch('/api/support/list-tickets.php?user_id=' + userId);
                const data = await response.json();
                
                if (data.success) {
                    let tickets = data.tickets;
                    if (currentStatus !== 'all') {
                        tickets = tickets.filter(t => t.status === currentStatus);
                    }
                    renderTickets(tickets);
                }
            } catch (error) {
                console.error('Error loading tickets:', error);
            }
        }
        
        function renderTickets(tickets) {
            const container = document.getElementById('ticketList');
            
            if (tickets.length === 0) {
                container.innerHTML = `
                    <div class="empty">
                        <div class="empty-icon">🎫</div>
                        <p>No tickets found</p>
                        <button class="btn btn-primary" style="margin-top: 15px;" onclick="openNewTicket()">Create Your First Ticket</button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = tickets.map(ticket => `
                <div class="ticket-item" onclick="viewTicket(${ticket.id})">
                    <div class="ticket-header">
                        <div>
                            <div class="ticket-subject">${escapeHtml(ticket.subject)}</div>
                            <div class="ticket-number">#${ticket.ticket_number}</div>
                        </div>
                        <span class="status status-${ticket.status}">${ticket.status.replace('_', ' ')}</span>
                    </div>
                    <div class="ticket-meta">
                        <span class="priority priority-${ticket.priority}">${ticket.priority}</span>
                        <span>📁 ${ticket.category}</span>
                        <span>📅 ${formatDate(ticket.created_at)}</span>
                    </div>
                </div>
            `).join('');
        }
        
        function openNewTicket() {
            document.getElementById('newTicketModal').classList.add('active');
        }
        
        async function viewTicket(ticketId) {
            currentTicketId = ticketId;
            
            try {
                const response = await fetch('/api/support/get-ticket.php?id=' + ticketId);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('ticketSubject').textContent = data.ticket.subject;
                    document.getElementById('ticketNumber').textContent = '#' + data.ticket.ticket_number;
                    document.getElementById('ticketStatus').innerHTML = `
                        <span class="status status-${data.ticket.status}">${data.ticket.status.replace('_', ' ')}</span>
                        <span class="priority priority-${data.ticket.priority}" style="margin-left: 10px;">${data.ticket.priority}</span>
                    `;
                    
                    const messagesContainer = document.getElementById('ticketMessages');
                    messagesContainer.innerHTML = `
                        <div class="message user">
                            <div class="message-header">
                                <span>You</span>
                                <span>${formatDate(data.ticket.created_at)}</span>
                            </div>
                            <div class="message-content">${escapeHtml(data.ticket.description)}</div>
                        </div>
                    ` + data.messages.map(msg => `
                        <div class="message ${msg.is_staff ? 'staff' : 'user'}">
                            <div class="message-header">
                                <span>${msg.is_staff ? '👨‍💼 Support' : 'You'}</span>
                                <span>${formatDate(msg.created_at)}</span>
                            </div>
                            <div class="message-content">${escapeHtml(msg.message)}</div>
                        </div>
                    `).join('');
                    
                    document.getElementById('viewTicketModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error loading ticket:', error);
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
                        user_id: userId,
                        is_staff: false,
                        sender_name: 'Customer'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    document.getElementById('replyMessage').value = '';
                    viewTicket(currentTicketId);
                }
            } catch (error) {
                console.error('Error sending reply:', error);
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // New ticket form
        document.getElementById('newTicketForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                user_id: userId,
                user_email: userEmail,
                subject: formData.get('subject'),
                description: formData.get('description'),
                category: formData.get('category'),
                priority: formData.get('priority')
            };
            
            try {
                const response = await fetch('/api/support/create-ticket.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                if (result.success) {
                    closeModal('newTicketModal');
                    this.reset();
                    loadTickets();
                    alert('Ticket created: #' + result.ticket_number);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error creating ticket:', error);
            }
        });
        
        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Helpers
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
        
        // Initial load
        loadTickets();
    </script>
</body>
</html>
