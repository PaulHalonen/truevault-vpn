<?php
// This file is accessed from /admin/tickets.php
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../support/config.php';

requireAdminLogin();

$admin = getCurrentAdmin();
$ticketId = $_GET['id'] ?? null;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticketId = $_POST['ticket_id'];
    $status = $_POST['status'];
    updateTicketStatus($ticketId, $status, $status === 'resolved' ? date('Y-m-d H:i:s') : null);
    logActivity('ticket_status_updated', 'ticket', $ticketId, ['status' => $status]);
    header('Location: /admin/tickets.php?id=' . $ticketId);
    exit;
}

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    $ticketId = $_POST['ticket_id'];
    $message = $_POST['message'];
    $isInternal = isset($_POST['is_internal']) ? 1 : 0;
    addTicketMessage($ticketId, $message, null, $admin['id'], $isInternal);
    logActivity('ticket_reply_added', 'ticket', $ticketId);
    header('Location: /admin/tickets.php?id=' . $ticketId);
    exit;
}

// Get ticket or list
if ($ticketId) {
    $ticket = getTicket($ticketId);
} else {
    $statusFilter = $_GET['status'] ?? null;
    $tickets = getTickets($statusFilter, null, 100);
}

$stats = getSupportStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Admin</title>
    <style>
        body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; font-family: -apple-system, sans-serif; margin: 0; padding: 0; }
        .page-container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 1.5rem; text-align: center; }
        .stat-value { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 0.5rem; }
        .filters { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .filter-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .filter-btn.active { background: rgba(0,217,255,0.2); border-color: #00d9ff; }
        .tickets-table { width: 100%; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; border-bottom: 2px solid #00d9ff; }
        td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        tr:hover { background: rgba(255,255,255,0.05); cursor: pointer; }
        .priority-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; display: inline-block; }
        .priority-urgent { background: rgba(255,100,100,0.3); color: #ff6464; }
        .priority-high { background: rgba(255,150,100,0.3); color: #ff9664; }
        .priority-normal { background: rgba(100,150,255,0.2); color: #88b8ff; }
        .priority-low { background: rgba(150,150,150,0.2); color: #999; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; display: inline-block; }
        .status-open { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .status-in_progress { background: rgba(255,200,100,0.2); color: #ffb84d; }
        .status-resolved { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-closed { background: rgba(150,150,150,0.2); color: #999; }
        .ticket-detail { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .ticket-header { display: flex; justify-content: space-between; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid rgba(255,255,255,0.1); }
        .messages-list { background: rgba(0,0,0,0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .message { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .message.user { border-left: 3px solid #00d9ff; }
        .message.admin { border-left: 3px solid #00ff88; }
        .message.internal { background: rgba(255,200,100,0.1); border-left: 3px solid #ffb84d; }
        .message-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; color: #888; }
        .reply-form { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
    </style>
</head>
<body>
<div class="page-container">
    <div style="margin-bottom: 2rem;">
        <a href="/admin/index.php" class="back-btn">← Dashboard</a>
        <h1 style="display: inline; margin-left: 1rem;">Support Tickets</h1>
    </div>

    <?php if (!$ticketId): ?>
        <!-- Ticket List View -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_tickets'] ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['open_tickets'] ?></div>
                <div class="stat-label">Open Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['resolved_today'] ?></div>
                <div class="stat-label">Resolved Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['avg_response_time'] ?></div>
                <div class="stat-label">Avg Response Time</div>
            </div>
        </div>

        <div class="filters">
            <a href="/admin/tickets.php" class="filter-btn <?= !$statusFilter ? 'active' : '' ?>">All</a>
            <a href="/admin/tickets.php?status=open" class="filter-btn <?= $statusFilter === 'open' ? 'active' : '' ?>">Open</a>
            <a href="/admin/tickets.php?status=in_progress" class="filter-btn <?= $statusFilter === 'in_progress' ? 'active' : '' ?>">In Progress</a>
            <a href="/admin/tickets.php?status=resolved" class="filter-btn <?= $statusFilter === 'resolved' ? 'active' : '' ?>">Resolved</a>
        </div>

        <div class="tickets-table">
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>User</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr onclick="window.location.href='/admin/tickets.php?id=<?= $t['id'] ?>'">
                            <td><strong><?= $t['ticket_number'] ?></strong></td>
                            <td><?= htmlspecialchars($t['subject']) ?></td>
                            <td><?= htmlspecialchars($t['email'] ?? 'Guest') ?></td>
                            <td><?= ucfirst($t['category']) ?></td>
                            <td><span class="priority-badge priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                            <td><span class="status-badge status-<?= $t['status'] ?>"><?= ucfirst(str_replace('_', ' ', $t['status'])) ?></span></td>
                            <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <!-- Ticket Detail View -->
        <?php if ($ticket): ?>
            <div class="ticket-detail">
                <div class="ticket-header">
                    <div>
                        <h2><?= htmlspecialchars($ticket['subject']) ?></h2>
                        <p style="color: #888;">Ticket #<?= $ticket['ticket_number'] ?> • <?= htmlspecialchars($ticket['email'] ?? 'Guest') ?></p>
                    </div>
                    <div style="text-align: right;">
                        <span class="priority-badge priority-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span>
                        <span class="status-badge status-<?= $ticket['status'] ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span>
                        <div style="margin-top: 0.5rem; color: #666; font-size: 0.9rem;">
                            Created: <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <div class="messages-list">
                    <?php foreach ($ticket['messages'] as $msg): ?>
                        <div class="message <?= $msg['admin_id'] ? ($msg['is_internal'] ? 'internal' : 'admin') : 'user' ?>">
                            <div class="message-header">
                                <strong>
                                    <?php if ($msg['admin_id']): ?>
                                        <?= $msg['is_internal'] ? '🔒 Internal Note - ' : '' ?>
                                        <?= htmlspecialchars($msg['admin_name']) ?> (Admin)
                                    <?php else: ?>
                                        <?= htmlspecialchars($msg['user_email'] ?? 'Guest') ?>
                                    <?php endif; ?>
                                </strong>
                                <span><?= date('M j, Y g:i A', strtotime($msg['created_at'])) ?></span>
                            </div>
                            <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="reply-form">
                    <h3 style="margin-bottom: 1rem;">Reply</h3>
                    <form method="POST">
                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                        <div class="form-group">
                            <textarea name="message" rows="6" required placeholder="Type your reply..."></textarea>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="is_internal"> Internal note (not visible to user)</label>
                        </div>
                        <button type="submit" name="add_reply" class="btn">Send Reply</button>
                    </form>
                </div>

                <div class="reply-form" style="margin-top: 1rem;">
                    <h3 style="margin-bottom: 1rem;">Update Status</h3>
                    <form method="POST">
                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                        <div class="form-group">
                            <select name="status">
                                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="waiting" <?= $ticket['status'] === 'waiting' ? 'selected' : '' ?>>Waiting for User</option>
                                <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-secondary">Update Status</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem; color: #666;">Ticket not found</div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
