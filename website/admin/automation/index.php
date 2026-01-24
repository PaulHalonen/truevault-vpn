<?php
/**
 * TrueVault VPN - Business Automation Dashboard
 * Task 17.6: Admin control center
 * Created: January 24, 2026
 */

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Include workflows
require_once __DIR__ . '/workflows.php';

// Database connections
$automationDb = new SQLite3(__DIR__ . '/databases/automation.db');
$settingsDb = new SQLite3(__DIR__ . '/../databases/settings.db');

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

// Get workflow engine instance
$workflow = new AutomationWorkflow();
$stats = $workflow->getStats();
$recentExecutions = $workflow->getRecentExecutions(10);
$pendingTasks = $workflow->getPendingTasks(10);

// Get workflow definitions
$workflowsResult = $automationDb->query("SELECT * FROM workflow_definitions ORDER BY execution_count DESC");
$workflows = [];
while ($row = $workflowsResult->fetchArray(SQLITE3_ASSOC)) {
    $workflows[] = $row;
}

// Get email stats
$emailStats = [
    'total_sent' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE status = 'sent'"),
    'sent_today' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE status = 'sent' AND date(sent_at) = date('now')"),
    'failed_today' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE status = 'failed' AND date(created_at) = date('now')"),
    'pending' => $automationDb->querySingle("SELECT COUNT(*) FROM email_log WHERE status = 'pending'")
];

// Handle manual workflow trigger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_workflow'])) {
    $event = $_POST['event'] ?? '';
    $testData = [
        'first_name' => 'Test',
        'email' => 'test@example.com',
        'customer_id' => 1,
        'plan_name' => 'Personal',
        'amount' => '9.97',
        'dashboard_url' => 'https://vpn.the-truth-publishing.com/dashboard'
    ];
    $result = $workflow->trigger($event, $testData);
    $triggerMessage = $result['success'] ? 'Workflow triggered successfully!' : 'Error: ' . ($result['error'] ?? 'Unknown');
}

$automationDb->close();
$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Automation - TrueVault Admin</title>
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
        
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>30, <?php echo $secondaryColor; ?>30);
            border-radius: 12px;
            font-size: 1.3rem;
            color: <?php echo $primaryColor; ?>;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: #888;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        /* Grid Layout */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        /* Cards */
        .card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i { color: <?php echo $primaryColor; ?>; }
        
        /* Recent Executions Table */
        .table-container { overflow-x: auto; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        th {
            color: #888;
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        td { font-size: 0.9rem; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-completed { background: rgba(0,200,100,0.2); color: #00c864; }
        .status-running { background: rgba(0,212,255,0.2); color: #00d4ff; }
        .status-failed { background: rgba(255,80,80,0.2); color: #ff5050; }
        .status-pending { background: rgba(255,180,0,0.2); color: #ffb400; }
        
        /* Workflow List */
        .workflow-list { list-style: none; }
        
        .workflow-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .workflow-item:last-child { border-bottom: none; }
        
        .workflow-info { flex: 1; }
        .workflow-name { font-weight: 600; margin-bottom: 4px; }
        .workflow-desc { color: #888; font-size: 0.85rem; }
        .workflow-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #888;
            font-size: 0.85rem;
        }
        
        .workflow-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        /* Scheduled Tasks */
        .task-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .task-item:last-child { border-bottom: none; }
        
        .task-time {
            text-align: center;
            min-width: 80px;
        }
        
        .task-time-value { font-weight: 600; color: <?php echo $primaryColor; ?>; }
        .task-time-label { font-size: 0.75rem; color: #888; }
        
        .task-info { flex: 1; }
        .task-type { font-weight: 500; }
        .task-workflow { color: #888; font-size: 0.85rem; }
        
        /* Alert Message */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(0,200,100,0.1);
            border: 1px solid rgba(0,200,100,0.3);
            color: #00c864;
        }
        
        .alert-error {
            background: rgba(255,80,80,0.1);
            border: 1px solid rgba(255,80,80,0.3);
            color: #ff5050;
        }
        
        /* Trigger Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show { display: flex; }
        
        .modal-content {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: <?php echo $textColor; ?>;
            font-size: 1rem;
        }
        
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <a href="../" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="page-title">Business Automation</h1>
            </div>
            <div class="quick-actions">
                <button class="btn btn-primary" onclick="showTriggerModal()">
                    <i class="fas fa-play"></i> Trigger Workflow
                </button>
                <button class="btn btn-secondary" onclick="processNow()">
                    <i class="fas fa-sync"></i> Process Tasks
                </button>
                <a href="email-log.php" class="btn btn-secondary">
                    <i class="fas fa-envelope"></i> Email Log
                </a>
                <a href="knowledge-base.php" class="btn btn-secondary">
                    <i class="fas fa-book"></i> Knowledge Base
                </a>
            </div>
        </div>
        
        <?php if (isset($triggerMessage)): ?>
        <div class="alert <?php echo strpos($triggerMessage, 'Error') === false ? 'alert-success' : 'alert-error'; ?>">
            <i class="fas <?php echo strpos($triggerMessage, 'Error') === false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($triggerMessage); ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-robot"></i></div>
                <div class="stat-value"><?php echo $stats['active_workflows']; ?></div>
                <div class="stat-label">Active Workflows</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-bolt"></i></div>
                <div class="stat-value"><?php echo $stats['executions_today']; ?></div>
                <div class="stat-label">Executions Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $stats['scheduled_pending']; ?></div>
                <div class="stat-label">Scheduled Tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                <div class="stat-value"><?php echo $emailStats['sent_today']; ?></div>
                <div class="stat-label">Emails Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?php echo $stats['failed_today']; ?></div>
                <div class="stat-label">Failed Today</div>
            </div>
        </div>
        
        <div class="grid-2">
            <!-- Available Workflows -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-cogs"></i> Available Workflows</h2>
                </div>
                <ul class="workflow-list">
                    <?php foreach (array_slice($workflows, 0, 8) as $wf): ?>
                    <li class="workflow-item">
                        <div class="workflow-info">
                            <div class="workflow-name"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $wf['name']))); ?></div>
                            <div class="workflow-desc"><?php echo htmlspecialchars($wf['description']); ?></div>
                        </div>
                        <div class="workflow-meta">
                            <span><i class="fas fa-play"></i> <?php echo $wf['execution_count']; ?></span>
                        </div>
                        <div class="workflow-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="trigger_workflow" value="1">
                                <input type="hidden" name="event" value="<?php echo htmlspecialchars($wf['trigger_event']); ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Test trigger">
                                    <i class="fas fa-play"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Scheduled Tasks -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-calendar-check"></i> Upcoming Tasks</h2>
                    <span class="status-badge status-pending"><?php echo $stats['scheduled_pending']; ?> pending</span>
                </div>
                
                <?php if (empty($pendingTasks)): ?>
                <p style="color: #666; text-align: center; padding: 30px;">No scheduled tasks</p>
                <?php else: ?>
                <div class="task-list">
                    <?php foreach ($pendingTasks as $task): 
                        $executeAt = strtotime($task['execute_at']);
                        $now = time();
                        $diff = $executeAt - $now;
                        if ($diff < 0) {
                            $timeLabel = 'Overdue';
                        } elseif ($diff < 3600) {
                            $timeLabel = round($diff / 60) . ' min';
                        } elseif ($diff < 86400) {
                            $timeLabel = round($diff / 3600, 1) . ' hrs';
                        } else {
                            $timeLabel = round($diff / 86400, 1) . ' days';
                        }
                    ?>
                    <div class="task-item">
                        <div class="task-time">
                            <div class="task-time-value"><?php echo $timeLabel; ?></div>
                            <div class="task-time-label"><?php echo date('M j, g:i a', $executeAt); ?></div>
                        </div>
                        <div class="task-info">
                            <div class="task-type"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $task['task_type']))); ?></div>
                            <div class="task-workflow"><?php echo htmlspecialchars($task['workflow_name']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Executions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-history"></i> Recent Executions</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Workflow</th>
                            <th>Trigger</th>
                            <th>Steps</th>
                            <th>Status</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentExecutions as $exec): 
                            $startTime = strtotime($exec['started_at']);
                            $endTime = $exec['completed_at'] ? strtotime($exec['completed_at']) : time();
                            $duration = $endTime - $startTime;
                        ?>
                        <tr>
                            <td><?php echo date('M j, g:i a', $startTime); ?></td>
                            <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $exec['workflow_name']))); ?></td>
                            <td><?php echo htmlspecialchars($exec['trigger_type']); ?></td>
                            <td><?php echo $exec['steps_completed']; ?>/<?php echo $exec['steps_total'] ?? '?'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $exec['status']; ?>">
                                    <?php echo ucfirst($exec['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $duration; ?>s</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Trigger Modal -->
    <div class="modal" id="triggerModal">
        <div class="modal-content">
            <h3 class="modal-title"><i class="fas fa-play"></i> Trigger Workflow</h3>
            <form method="POST">
                <input type="hidden" name="trigger_workflow" value="1">
                <div class="form-group">
                    <label>Select Event</label>
                    <select name="event" required>
                        <option value="">Choose a workflow trigger...</option>
                        <?php foreach ($workflows as $wf): ?>
                        <option value="<?php echo htmlspecialchars($wf['trigger_event']); ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $wf['trigger_event']))); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideTriggerModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Trigger</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showTriggerModal() {
            document.getElementById('triggerModal').classList.add('show');
        }
        
        function hideTriggerModal() {
            document.getElementById('triggerModal').classList.remove('show');
        }
        
        async function processNow() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            try {
                const response = await fetch('task-processor.php?action=process&sub=process');
                const data = await response.json();
                alert(`Processed ${data.processed} tasks (${data.failed} failed)`);
                location.reload();
            } catch (error) {
                alert('Error processing tasks: ' + error.message);
            }
            
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync"></i> Process Tasks';
        }
        
        // Close modal on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') hideTriggerModal();
        });
        
        // Close modal on background click
        document.getElementById('triggerModal').addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) hideTriggerModal();
        });
    </script>
</body>
</html>
