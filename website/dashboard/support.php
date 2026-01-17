<?php
/**
 * TrueVault VPN - User Support Dashboard
 * View and manage support tickets
 */

session_start();
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$user = $auth->validateSession();

if (!$user) {
    header('Location: /login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - TrueVault VPN</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .support-container { max-width: 1000px; margin: 0 auto; }
        .ticket-form { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .ticket-form h2 { margin-bottom: 20px; color: #00d9ff; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
            color: #fff; font-size: 14px;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: #00d9ff;
        }
        .btn-submit {
            background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a;
            border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600;
            cursor: pointer; font-size: 14px;
        }
        .btn-submit:hover { opacity: 0.9; }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .tickets-list { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 25px; }
        .tickets-list h2 { margin-bottom: 20px; color: #fff; }
        .ticket-item {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);
            border-radius: 8px; padding: 15px 20px; margin-bottom: 12px; cursor: pointer;
            transition: all 0.2s;
        }
        .ticket-item:hover { border-color: #00d9ff; background: rgba(0,217,255,0.05); }
        .ticket-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .ticket-subject { font-weight: 600; color: #fff; }
        .ticket-id { color: #666; font-size: 12px; }
        .ticket-meta { display: flex; gap: 15px; font-size: 13px; color: #888; }
        .ticket-status {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 11px; font-weight: 600; text-transform: uppercase;
        }
        .status-open { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .status-pending { background: rgba(255,193,7,0.2); color: #ffc107; }
        .status-resolved { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-closed { background: rgba(108,117,125,0.2); color: #6c757d; }
        
        .empty-state { text-align: center; padding: 40px; color: #666; }
        .empty-state .icon { font-size: 48px; margin-bottom: 15px; }
        
        /* Ticket Detail Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; padding: 20px; overflow-y: auto; }
        .modal.active { display: flex; justify-content: center; align-items: flex-start; }
        .modal-content { background: #1a1a2e; border-radius: 12px; width: 100%; max-width: 700px; margin: 40px auto; }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: #fff; margin: 0; }
        .modal-close { background: none; border: none; color: #666; font-size: 24px; cursor: pointer; }
        .modal-close:hover { color: #fff; }
        .modal-body { padding: 25px; }
        
        .ticket-info { background: rgba(255,255,255,0.03); border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .ticket-info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .ticket-info-label { color: #888; }
        .ticket-info-value { color: #fff; }
        
        .messages-container { max-height: 400px; overflow-y: auto; margin-bottom: 20px; }
        .message { margin-bottom: 15px; }
        .message.user { text-align: right; }
        .message.staff { text-align: left; }
        .message-bubble {
            display: inline-block; max-width: 80%; padding: 12px 16px;
            border-radius: 12px; text-align: left;
        }
        .message.user .message-bubble { background: rgba(0,217,255,0.2); border-bottom-right-radius: 4px; }
        .message.staff .message-bubble { background: rgba(255,255,255,0.1); border-bottom-left-radius: 4px; }
        .message-text { color: #fff; margin-bottom: 5px; white-space: pre-wrap; }
        .message-time { font-size: 11px; color: #666; }
        
        .reply-form { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }
        .reply-form textarea { margin-bottom: 15px; }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-btn {
            padding: 8px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px; color: #888; font-size: 13px; cursor: pointer;
        }
        .filter-btn:hover, .filter-btn.active { border-color: #00d9ff; color: #00d9ff; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>
    
    <div class="dashboard-container">
        <div class="support-container">
            
            <!-- New Ticket Form -->
            <div class="ticket-form">
                <h2>📝 Submit a Support Ticket</h2>
                <form id="ticketForm">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" placeholder="Brief description of your issue" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category">
                            <option value="">Select a category...</option>
                            <option value="technical">Technical Issue</option>
                            <option value="billing">Billing Question</option>
                            <option value="account">Account Help</option>
                            <option value="general">General Inquiry</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" placeholder="Please describe your issue in detail..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit" id="submitBtn">Submit Ticket</button>
                </form>
            </div>
            
            <!-- Tickets List -->
            <div class="tickets-list">
                <h2>🎫 Your Tickets</h2>
                
                <div class="filter-bar">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="open">Open</button>
                    <button class="filter-btn" data-filter="pending">Pending</button>
                    <button class="filter-btn" data-filter="resolved">Resolved</button>
                </div>
                
                <div id="ticketsList">
                    <div class="empty-state">
                        <div class="icon">📭</div>
                        <p>Loading tickets...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ticket Detail Modal -->
    <div class="modal" id="ticketModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Ticket Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Loaded dynamically -->
            </div>
        </div>
    </div>
    
    <script>
        const API = '/api/support';
        let currentFilter = 'all';
        let currentTicketId = null;
        
        // Load tickets on page load
        loadTickets();
        
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
                loadTickets();
            });
        });
        
        // Submit ticket form
        document.getElementById('ticketForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Submitting...';
            
            try {
                const response = await fetch(`${API}/create.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + getToken()
                    },
                    body: JSON.stringify({
                        subject: document.getElementById('subject').value,
                        category: document.getElementById('category').value,
                        description: document.getElementById('description').value
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Ticket submitted successfully! We\'ll respond within 24 hours.');
                    document.getElementById('ticketForm').reset();
                    loadTickets();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Failed to submit ticket. Please try again.');
            }
            
            btn.disabled = false;
            btn.textContent = 'Submit Ticket';
        });
        
        async function loadTickets() {
            const container = document.getElementById('ticketsList');
            
            try {
                let url = `${API}/list.php`;
                if (currentFilter !== 'all') {
                    url += `?status=${currentFilter}`;
                }
                
                const response = await fetch(url, {
                    headers: { 'Authorization': 'Bearer ' + getToken() }
                });
                const data = await response.json();
                
                if (data.success && data.tickets.length > 0) {
                    container.innerHTML = data.tickets.map(ticket => `
                        <div class="ticket-item" onclick="viewTicket(${ticket.id})">
                            <div class="ticket-header">
                                <span class="ticket-subject">${escapeHtml(ticket.subject)}</span>
                                <span class="ticket-id">#${ticket.id}</span>
                            </div>
                            <div class="ticket-meta">
                                <span class="ticket-status status-${ticket.status}">${ticket.status}</span>
                                <span>${ticket.category || 'General'}</span>
                                <span>${formatDate(ticket.created_at)}</span>
                                <span>${ticket.message_count} message${ticket.message_count !== 1 ? 's' : ''}</span>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <p>No tickets found</p>
                        </div>
                    `;
                }
            } catch (error) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="icon">⚠️</div>
                        <p>Failed to load tickets</p>
                    </div>
                `;
            }
        }
        
        async function viewTicket(ticketId) {
            currentTicketId = ticketId;
            const modal = document.getElementById('ticketModal');
            const body = document.getElementById('modalBody');
            
            modal.classList.add('active');
            body.innerHTML = '<div style="text-align:center;padding:40px;color:#888;">Loading...</div>';
            
            try {
                const response = await fetch(`${API}/get.php?id=${ticketId}`, {
                    headers: { 'Authorization': 'Bearer ' + getToken() }
                });
                const data = await response.json();
                
                if (data.success) {
                    const ticket = data.ticket;
                    const messages = data.messages;
                    
                    document.getElementById('modalTitle').textContent = `Ticket #${ticket.id}`;
                    
                    body.innerHTML = `
                        <div class="ticket-info">
                            <div class="ticket-info-row">
                                <span class="ticket-info-label">Subject:</span>
                                <span class="ticket-info-value">${escapeHtml(ticket.subject)}</span>
                            </div>
                            <div class="ticket-info-row">
                                <span class="ticket-info-label">Status:</span>
                                <span class="ticket-status status-${ticket.status}">${ticket.status}</span>
                            </div>
                            <div class="ticket-info-row">
                                <span class="ticket-info-label">Category:</span>
                                <span class="ticket-info-value">${ticket.category || 'General'}</span>
                            </div>
                            <div class="ticket-info-row">
                                <span class="ticket-info-label">Created:</span>
                                <span class="ticket-info-value">${formatDate(ticket.created_at)}</span>
                            </div>
                        </div>
                        
                        <div class="messages-container">
                            ${messages.map(msg => `
                                <div class="message ${msg.is_staff ? 'staff' : 'user'}">
                                    <div class="message-bubble">
                                        <div class="message-text">${escapeHtml(msg.message)}</div>
                                        <div class="message-time">${msg.sender} • ${formatDate(msg.created_at)}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        
                        ${ticket.status !== 'closed' ? `
                            <div class="reply-form">
                                <textarea id="replyMessage" placeholder="Type your reply..."></textarea>
                                <button class="btn-submit" onclick="sendReply()">Send Reply</button>
                            </div>
                        ` : '<p style="color:#888;text-align:center;">This ticket is closed.</p>'}
                    `;
                } else {
                    body.innerHTML = '<div style="text-align:center;padding:40px;color:#ff6b6b;">Failed to load ticket</div>';
                }
            } catch (error) {
                body.innerHTML = '<div style="text-align:center;padding:40px;color:#ff6b6b;">Error loading ticket</div>';
            }
        }
        
        async function sendReply() {
            const message = document.getElementById('replyMessage').value.trim();
            
            if (!message) {
                alert('Please enter a message');
                return;
            }
            
            try {
                const response = await fetch(`${API}/message.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + getToken()
                    },
                    body: JSON.stringify({
                        ticket_id: currentTicketId,
                        message: message
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    viewTicket(currentTicketId); // Reload ticket
                    loadTickets(); // Refresh list
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Failed to send reply');
            }
        }
        
        function closeModal() {
            document.getElementById('ticketModal').classList.remove('active');
            currentTicketId = null;
        }
        
        // Close modal on background click
        document.getElementById('ticketModal').addEventListener('click', (e) => {
            if (e.target.id === 'ticketModal') closeModal();
        });
        
        function getToken() {
            return localStorage.getItem('auth_token') || '';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>
</html>
