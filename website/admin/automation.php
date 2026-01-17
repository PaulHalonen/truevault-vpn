<?php
/**
 * TrueVault VPN - Admin Automation Dashboard
 * Monitor workflows, scheduled tasks, and email system
 */

session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AutomationEngine.php';
require_once __DIR__ . '/../includes/Email.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin/');
    exit;
}

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $engine = new AutomationEngine();
    $email = new Email();
    
    switch ($_POST['action']) {
        case 'get_stats':
            $logsDb = new Database('logs');
            
            // Workflow stats
            $workflowStats = $engine->getStats(7);
            
            // Email stats
            $emailStats = $email->getStats(7);
            
            // Pending tasks
            $pendingTasks = $engine->getPendingCount();
            
            // Today's executions
            $todayExec = $logsDb->query("
                SELECT COUNT(*) FROM workflow_executions 
                WHERE date(started_at) = date('now')
            ")->fetchColumn();
            
            // Today's emails
            $todayEmails = $logsDb->query("
                SELECT COUNT(*) FROM email_log 
                WHERE date(created_at) = date('now') AND status = 'sent'
            ")->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'workflows_today' => (int)$todayExec,
                    'emails_today' => (int)$todayEmails,
                    'pending_tasks' => (int)$pendingTasks,
                    'workflow_stats' => $workflowStats,
                    'email_stats' => $emailStats
                ]
            ]);
            exit;
            
        case 'get_executions':
            $executions = $engine->getRecentExecutions(50);
            echo json_encode(['success' => true, 'executions' => $executions]);
            exit;
            
        case 'get_scheduled':
            $tasks = $engine->getScheduledTasks('pending', 50);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            exit;
            
        case 'get_emails':
            $emails = $email->getRecent(50);
            echo json_encode(['success' => true, 'emails' => $emails]);
            exit;
            
        case 'cancel_task':
            $taskId = (int)$_POST['task_id'];
            $result = $engine->cancelTask($taskId);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'process_now':
            $processed = $engine->processScheduledTasks(20);
            $emailsSent = $email->processQueue(10);
            echo json_encode([
                'success' => true,
                'tasks_processed' => $processed,
                'emails_sent' => $emailsSent
            ]);
            exit;
            
        case 'test_email':
            $to = $_POST['email'] ?? '';
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid email']);
                exit;
            }
            
            $result = $email->send($to, 'TrueVault VPN Test Email', '
                <h2>Test Email</h2>
                <p>This is a test email from TrueVault VPN automation system.</p>
                <p>If you received this, your email configuration is working correctly!</p>
                <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
            ');
            
            echo json_encode(['success' => $result]);
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Get initial stats
$engine = new AutomationEngine();
$email = new Email();

$stats = [
    'pending' => $engine->getPendingCount(),
    'queue' => $email->getQueueStatus()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Dashboard - Admin - TrueVault VPN</title>
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
        
        .actions-bar { display: flex; gap: 15px; margin-bottom: 30px; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .btn:hover { opacity: 0.9; }
        
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .tab { padding: 12px 20px; background: none; border: none; color: #888; cursor: pointer; font-size: 14px; border-bottom: 2px solid transparent; }
        .tab:hover { color: #fff; }
        .tab.active { color: #00d9ff; border-bottom-color: #00d9ff; }
        
        .panel { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; display: none; }
        .panel.active { display: block; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: #888; font-weight: 500; font-size: 12px; text-transform: uppercase; }
        td { font-size: 13px; }
        
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-completed, .status-sent { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-running, .status-pending, .status-sending { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .status-failed { background: rgba(255,107,107,0.2); color: #ff6b6b; }
        .status-cancelled { background: rgba(108,117,125,0.2); color: #6c757d; }
        
        .empty-state { text-align: center; padding: 40px; color: #666; }
        
        .test-email-form { display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .test-email-form input { flex: 1; padding: 10px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #fff; }
        .test-email-form input:focus { outline: none; border-color: #00d9ff; }
        
        .btn-cancel { background: rgba(255,107,107,0.2); color: #ff6b6b; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; border: none; }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🤖 Automation Dashboard</h1>
        <a href="/admin/">← Back to Admin</a>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" id="statWorkflows">-</div>
                <div class="stat-label">Workflows Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="statEmails">-</div>
                <div class="stat-label">Emails Sent Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="statPending"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending Tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="statQueue"><?= array_sum($stats['queue'] ?? [0]) ?></div>
                <div class="stat-label">Email Queue</div>
            </div>
        </div>
        
        <div class="actions-bar">
            <button class="btn btn-primary" onclick="processNow()">▶️ Process Now</button>
            <button class="btn btn-secondary" onclick="loadAll()">🔄 Refresh</button>
        </div>
        
        <div class="tabs">
            <button class="tab active" data-tab="executions">Workflow Executions</button>
            <button class="tab" data-tab="scheduled">Scheduled Tasks</button>
            <button class="tab" data-tab="emails">Email Log</button>
        </div>
        
        <div class="panel active" id="panel-executions">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Workflow</th>
                        <th>Trigger</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody id="executionsList">
                    <tr><td colspan="7" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="panel" id="panel-scheduled">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task</th>
                        <th>Type</th>
                        <th>Scheduled For</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="scheduledList">
                    <tr><td colspan="6" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="panel" id="panel-emails">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Method</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody id="emailsList">
                    <tr><td colspan="6" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
            
            <div class="test-email-form">
                <input type="email" id="testEmail" placeholder="Enter email to test...">
                <button class="btn btn-secondary" onclick="sendTestEmail()">📧 Send Test</button>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
            });
        });
        
        // Load all data
        loadAll();
        
        async function loadAll() {
            loadStats();
            loadExecutions();
            loadScheduled();
            loadEmails();
        }
        
        async function loadStats() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_stats');
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('statWorkflows').textContent = data.stats.workflows_today;
                    document.getElementById('statEmails').textContent = data.stats.emails_today;
                    document.getElementById('statPending').textContent = data.stats.pending_tasks;
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        async function loadExecutions() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_executions');
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success && data.executions.length > 0) {
                    document.getElementById('executionsList').innerHTML = data.executions.map(e => `
                        <tr>
                            <td>#${e.id}</td>
                            <td>${e.workflow_name}</td>
                            <td>${e.trigger_event}</td>
                            <td>${e.user_email || '-'}</td>
                            <td><span class="status-badge status-${e.status}">${e.status}</span></td>
                            <td>${formatDate(e.started_at)}</td>
                            <td>${e.completed_at ? formatDate(e.completed_at) : '-'}</td>
                        </tr>
                    `).join('');
                } else {
                    document.getElementById('executionsList').innerHTML = '<tr><td colspan="7" class="empty-state">No workflow executions yet</td></tr>';
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        async function loadScheduled() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_scheduled');
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success && data.tasks.length > 0) {
                    document.getElementById('scheduledList').innerHTML = data.tasks.map(t => `
                        <tr>
                            <td>#${t.id}</td>
                            <td>${escapeHtml(t.task_name)}</td>
                            <td>${t.task_type}</td>
                            <td>${formatDate(t.execute_at)}</td>
                            <td><span class="status-badge status-${t.status}">${t.status}</span></td>
                            <td>
                                ${t.status === 'pending' ? `<button class="btn-cancel" onclick="cancelTask(${t.id})">Cancel</button>` : '-'}
                            </td>
                        </tr>
                    `).join('');
                } else {
                    document.getElementById('scheduledList').innerHTML = '<tr><td colspan="6" class="empty-state">No scheduled tasks</td></tr>';
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        async function loadEmails() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_emails');
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success && data.emails.length > 0) {
                    document.getElementById('emailsList').innerHTML = data.emails.map(e => `
                        <tr>
                            <td>#${e.id}</td>
                            <td>${e.method}</td>
                            <td>${escapeHtml(e.recipient)}</td>
                            <td>${escapeHtml(e.subject)}</td>
                            <td><span class="status-badge status-${e.status}">${e.status}</span></td>
                            <td>${e.sent_at ? formatDate(e.sent_at) : '-'}</td>
                        </tr>
                    `).join('');
                } else {
                    document.getElementById('emailsList').innerHTML = '<tr><td colspan="6" class="empty-state">No emails sent yet</td></tr>';
                }
            } catch (e) {
                console.error(e);
            }
        }
        
        async function processNow() {
            try {
                const formData = new FormData();
                formData.append('action', 'process_now');
                
                const response = await fetch('', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    alert(`Processed ${data.tasks_processed} tasks and sent ${data.emails_sent} emails`);
                    loadAll();
                }
            } catch (e) {
                alert('Error processing tasks');
            }
        }
        
        async function cancelTask(taskId) {
            if (!confirm('Cancel this task?')) return;
            
            const formData = new FormData();
            formData.append('action', 'cancel_task');
            formData.append('task_id', taskId);
            
            const response = await fetch('', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                loadScheduled();
                loadStats();
            }
        }
        
        async function sendTestEmail() {
            const email = document.getElementById('testEmail').value;
            if (!email) return alert('Enter an email address');
            
            const formData = new FormData();
            formData.append('action', 'test_email');
            formData.append('email', email);
            
            const response = await fetch('', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                alert('Test email sent! Check your inbox.');
                loadEmails();
            } else {
                alert('Failed to send: ' + (data.error || 'Unknown error'));
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleString('en-US', { 
                month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' 
            });
        }
        
        // Auto-refresh every 30 seconds
        setInterval(loadAll, 30000);
    </script>
</body>
</html>
