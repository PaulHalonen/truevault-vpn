<?php
require_once 'config.php';

$workflowId = $_GET['id'] ?? null;
if (!$workflowId) {
    header('Location: /workflows/');
    exit;
}

$workflow = getWorkflow($workflowId);
if (!$workflow) {
    header('Location: /workflows/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($workflow['workflow_name']) ?> - Workflow</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .back-btn { display: inline-block; padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; text-decoration: none; margin-bottom: 2rem; }
        .header { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .header-meta { display: flex; gap: 2rem; color: #888; font-size: 0.95rem; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .section-title { font-size: 1.5rem; margin-bottom: 1.5rem; color: #00d9ff; }
        .workflow-steps { display: flex; flex-direction: column; gap: 1rem; }
        .step-card { background: rgba(255,255,255,0.03); border-left: 4px solid #00d9ff; border-radius: 8px; padding: 1.5rem; position: relative; }
        .step-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .step-number { width: 40px; height: 40px; background: rgba(0,217,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #00d9ff; font-size: 1.2rem; }
        .step-type { padding: 0.4rem 1rem; background: rgba(0,255,136,0.2); border-radius: 6px; font-size: 0.85rem; color: #00ff88; font-weight: 600; }
        .step-content { margin-left: 56px; }
        .step-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .step-config { background: rgba(0,0,0,0.3); border-radius: 8px; padding: 1rem; margin-top: 1rem; font-family: monospace; font-size: 0.9rem; white-space: pre-wrap; word-break: break-word; }
        .delay-badge { display: inline-block; padding: 0.3rem 0.8rem; background: rgba(255,184,77,0.2); border-radius: 6px; font-size: 0.85rem; color: #ffb84d; margin-top: 0.5rem; }
        .arrow { text-align: center; color: #00d9ff; font-size: 2rem; margin: 0.5rem 0; }
        .actions { display: flex; gap: 1rem; }
        .btn { padding: 1rem 2rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <a href="/workflows/" class="back-btn">← Back to Workflows</a>
    
    <div class="header">
        <h1><?= htmlspecialchars($workflow['workflow_name']) ?></h1>
        <p style="color: #888; margin: 1rem 0;"><?= htmlspecialchars($workflow['description']) ?></p>
        <div class="header-meta">
            <span>📌 Trigger: <?= strtoupper($workflow['trigger_type']) ?></span>
            <span>🔄 Status: <?= $workflow['is_active'] ? 'Active' : 'Inactive' ?></span>
            <span>📅 Created: <?= date('M j, Y', strtotime($workflow['created_at'])) ?></span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">📋 Workflow Steps (<?= count($workflow['steps']) ?>)</div>
        <div class="workflow-steps">
            <?php foreach ($workflow['steps'] as $i => $step): 
                $config = json_decode($step['step_config'], true);
            ?>
                <div class="step-card">
                    <div class="step-header">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div class="step-number"><?= $step['step_number'] ?></div>
                            <div>
                                <div class="step-type"><?= strtoupper(str_replace('_', ' ', $step['step_type'])) ?></div>
                                <?php if ($step['delay_minutes'] > 0): ?>
                                    <div class="delay-badge">
                                        ⏱️ Delay: <?= $step['delay_minutes'] ?> min
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-content">
                        <?php if ($step['step_type'] === 'email'): ?>
                            <div class="step-title">📧 Send Email</div>
                            <p>To: <strong><?= htmlspecialchars($config['to'] ?? 'N/A') ?></strong></p>
                            <p>Subject: <strong><?= htmlspecialchars($config['subject'] ?? 'N/A') ?></strong></p>
                            <p>Template: <?= htmlspecialchars($config['template'] ?? 'N/A') ?></p>
                        <?php elseif ($step['step_type'] === 'action'): ?>
                            <div class="step-title">⚡ Execute Action</div>
                            <p>Action: <strong><?= htmlspecialchars($config['action'] ?? 'N/A') ?></strong></p>
                        <?php elseif ($step['step_type'] === 'delay'): ?>
                            <div class="step-title">⏸️ Pause Workflow</div>
                            <p>Duration: <strong><?= $config['duration_minutes'] ?? 0 ?> minutes</strong></p>
                        <?php elseif ($step['step_type'] === 'condition'): ?>
                            <div class="step-title">🔍 Check Condition</div>
                            <p>Condition: <strong><?= htmlspecialchars($config['check'] ?? 'N/A') ?></strong></p>
                        <?php endif; ?>
                        
                        <div class="step-config"><?= json_encode($config, JSON_PRETTY_PRINT) ?></div>
                    </div>
                </div>
                
                <?php if ($i < count($workflow['steps']) - 1): ?>
                    <div class="arrow">↓</div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-title">🎯 Actions</div>
        <div class="actions">
            <button class="btn btn-primary" onclick="triggerWorkflow()">
                ▶️ Trigger Workflow
            </button>
            <button class="btn btn-secondary" onclick="viewExecutions()">
                📊 View Executions
            </button>
        </div>
    </div>
</div>

<script>
function triggerWorkflow() {
    if (!confirm('Trigger this workflow with test data?')) return;
    
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
        body: JSON.stringify({workflow_id: <?= $workflowId ?>, data: testData})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Workflow triggered! Execution ID: ' + data.execution_id);
            window.location.href = '/workflows/execution.php?id=' + data.execution_id;
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    });
}

function viewExecutions() {
    // Redirect to filtered executions view
    window.location.href = '/workflows/?workflow=<?= $workflowId ?>';
}
</script>
</body>
</html>
