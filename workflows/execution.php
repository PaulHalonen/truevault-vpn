<?php
require_once 'config.php';

$executionId = $_GET['id'] ?? null;
if (!$executionId) {
    header('Location: /workflows/');
    exit;
}

$db = getWorkflowsDB();

// Get execution details
$stmt = $db->prepare("
    SELECT we.*, w.workflow_name 
    FROM workflow_executions we
    LEFT JOIN workflows w ON we.workflow_id = w.id
    WHERE we.id = ?
");
$stmt->execute([$executionId]);
$execution = $stmt->fetch();

if (!$execution) {
    header('Location: /workflows/');
    exit;
}

// Get execution logs
$stmt = $db->prepare("
    SELECT * FROM workflow_logs
    WHERE execution_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$executionId]);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workflow Execution #<?= $executionId ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .back-btn { display: inline-block; padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; margin-bottom: 2rem; }
        .header { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .header h1 { font-size: 2rem; margin-bottom: 1rem; }
        .status-banner { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 600; }
        .status-running { background: rgba(0,217,255,0.2); border: 2px solid #00d9ff; color: #00d9ff; }
        .status-completed { background: rgba(0,255,136,0.2); border: 2px solid #00ff88; color: #00ff88; }
        .status-failed { background: rgba(255,100,100,0.2); border: 2px solid #ff6464; color: #ff6464; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .info-item { background: rgba(255,255,255,0.03); border-radius: 8px; padding: 1rem; }
        .info-label { color: #888; font-size: 0.85rem; margin-bottom: 0.5rem; }
        .info-value { font-size: 1.1rem; font-weight: 600; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .section-title { font-size: 1.5rem; margin-bottom: 1.5rem; color: #00d9ff; }
        .logs-container { max-height: 600px; overflow-y: auto; }
        .log-entry { background: rgba(255,255,255,0.03); border-left: 4px solid #666; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .log-entry.info { border-left-color: #00d9ff; }
        .log-entry.success { border-left-color: #00ff88; }
        .log-entry.warning { border-left-color: #ffb84d; }
        .log-entry.error { border-left-color: #ff6464; }
        .log-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .log-level { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .level-info { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .level-success { background: rgba(0,255,136,0.2); color: #00ff88; }
        .level-warning { background: rgba(255,184,77,0.2); color: #ffb84d; }
        .level-error { background: rgba(255,100,100,0.2); color: #ff6464; }
        .log-time { color: #888; font-size: 0.85rem; }
        .log-message { color: #fff; line-height: 1.6; }
        .log-data { background: rgba(0,0,0,0.3); border-radius: 6px; padding: 0.75rem; margin-top: 0.75rem; font-family: monospace; font-size: 0.85rem; overflow-x: auto; }
        .empty-state { text-align: center; padding: 3rem; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <a href="/workflows/" class="back-btn">← Back to Workflows</a>
    
    <div class="header">
        <h1>📊 Execution #<?= $executionId ?></h1>
        <div class="status-banner status-<?= $execution['status'] ?>">
            Status: <?= strtoupper($execution['status']) ?>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Workflow</div>
                <div class="info-value"><?= htmlspecialchars($execution['workflow_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Started</div>
                <div class="info-value"><?= date('M j, Y g:i A', strtotime($execution['started_at'])) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Completed</div>
                <div class="info-value">
                    <?= $execution['completed_at'] ? date('M j, Y g:i A', strtotime($execution['completed_at'])) : 'Running...' ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Current Step</div>
                <div class="info-value">#<?= $execution['current_step'] ?></div>
            </div>
        </div>
        
        <?php if ($execution['error_message']): ?>
            <div style="background: rgba(255,100,100,0.1); border: 1px solid #ff6464; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                <strong style="color: #ff6464;">Error:</strong>
                <p style="margin-top: 0.5rem;"><?= htmlspecialchars($execution['error_message']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-title">📋 Execution Logs (<?= count($logs) ?>)</div>
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
                <p>No logs yet</p>
            </div>
        <?php else: ?>
            <div class="logs-container">
                <?php foreach ($logs as $log): ?>
                    <div class="log-entry <?= $log['log_level'] ?>">
                        <div class="log-header">
                            <span class="log-level level-<?= $log['log_level'] ?>">
                                <?= strtoupper($log['log_level']) ?>
                            </span>
                            <span class="log-time">
                                <?= date('g:i:s A', strtotime($log['created_at'])) ?>
                            </span>
                        </div>
                        <div class="log-message">
                            <?php if ($log['step_id']): ?>
                                <strong>Step <?= $log['step_id'] ?>:</strong>
                            <?php endif; ?>
                            <?= htmlspecialchars($log['message']) ?>
                        </div>
                        <?php if ($log['data']): ?>
                            <div class="log-data"><?= htmlspecialchars($log['data']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh if workflow is still running
<?php if ($execution['status'] === 'running'): ?>
    setTimeout(() => {
        location.reload();
    }, 5000);
<?php endif; ?>
</script>
</body>
</html>
