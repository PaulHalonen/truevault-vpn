<?php
require_once 'config.php';

$workflows = getWorkflows(true);
$stats = getWorkflowStats();

// Get recent executions
$db = getWorkflowsDB();
$stmt = $db->query("
    SELECT we.*, w.workflow_name 
    FROM workflow_executions we
    LEFT JOIN workflows w ON we.workflow_id = w.id
    ORDER BY we.started_at DESC
    LIMIT 20
");
$recentExecutions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workflow Automation - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .header { text-align: center; margin-bottom: 3rem; }
        .header h1 { font-size: 3rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
        .header p { color: #888; font-size: 1.2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; text-align: center; }
        .stat-value { font-size: 3rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.95rem; margin-top: 0.5rem; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .section-title { font-size: 1.8rem; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #00d9ff; }
        .workflows-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .workflow-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 1.5rem; transition: 0.3s; }
        .workflow-card:hover { transform: translateY(-3px); border-color: #00d9ff; }
        .workflow-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .workflow-name { font-size: 1.3rem; font-weight: 700; color: #00d9ff; }
        .workflow-trigger { padding: 0.25rem 0.75rem; background: rgba(0,217,255,0.2); border-radius: 6px; font-size: 0.75rem; color: #00d9ff; }
        .workflow-desc { color: #888; font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.5; }
        .workflow-actions { display: flex; gap: 0.5rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .executions-table { width: 100%; border-collapse: collapse; }
        .executions-table th { background: rgba(255,255,255,0.05); padding: 1rem; text-align: left; font-weight: 600; border-bottom: 2px solid rgba(255,255,255,0.1); }
        .executions-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .status-running { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .status-completed { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-failed { background: rgba(255,100,100,0.2); color: #ff6464; }
        .empty-state { text-align: center; padding: 3rem; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>⚙️ Workflow Automation</h1>
        <p>Automate your business processes</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['active_workflows'] ?></div>
            <div class="stat-label">Active Workflows</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['running_executions'] ?></div>
            <div class="stat-label">Currently Running</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['executions_today'] ?></div>
            <div class="stat-label">Executed Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['pending_tasks'] ?></div>
            <div class="stat-label">Pending Tasks</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">🔄 Available Workflows</div>
        <div class="workflows-grid">
            <?php foreach ($workflows as $workflow): ?>
                <div class="workflow-card">
                    <div class="workflow-header">
                        <div class="workflow-name"><?= htmlspecialchars($workflow['workflow_name']) ?></div>
                        <div class="workflow-trigger"><?= strtoupper($workflow['trigger_type']) ?></div>
                    </div>
                    <div class="workflow-desc"><?= htmlspecialchars($workflow['description']) ?></div>
                    <div class="workflow-actions">
                        <button class="btn btn-primary" onclick="triggerWorkflow(<?= $workflow['id'] ?>)">
                            ▶️ Trigger
                        </button>
                        <button class="btn btn-secondary" onclick="viewWorkflow(<?= $workflow['id'] ?>)">
                            👁️ View
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-title">📋 Recent Executions</div>
        <?php if (empty($recentExecutions)): ?>
            <div class="empty-state">
                <div style="font-size: 4rem; margin-bottom: 1rem;">⏸️</div>
                <p>No workflow executions yet</p>
            </div>
        <?php else: ?>
            <table class="executions-table">
                <thead>
                    <tr>
                        <th>Workflow</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Completed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentExecutions as $exec): ?>
                        <tr>
                            <td><?= htmlspecialchars($exec['workflow_name']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $exec['status'] ?>">
                                    <?= strtoupper($exec['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y g:i A', strtotime($exec['started_at'])) ?></td>
                            <td><?= $exec['completed_at'] ? date('M j, Y g:i A', strtotime($exec['completed_at'])) : '-' ?></td>
                            <td>
                                <button class="btn btn-secondary" style="padding: 0.5rem 1rem;" onclick="viewExecution(<?= $exec['id'] ?>)">
                                    View Logs
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function triggerWorkflow(workflowId) {
    if (!confirm('Trigger this workflow?')) return;
    
    const testData = {
        customer: {
            id: 123,
            email: 'test@example.com',
            first_name: 'Test User',
            plan: 'personal'
        }
    };
    
    fetch('/workflows/api.php?action=trigger', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({workflow_id: workflowId, data: testData})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Workflow triggered successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    });
}

function viewWorkflow(workflowId) {
    window.location.href = '/workflows/view.php?id=' + workflowId;
}

function viewExecution(executionId) {
    window.location.href = '/workflows/execution.php?id=' + executionId;
}
</script>
</body>
</html>
