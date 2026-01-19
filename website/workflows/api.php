<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Trigger workflow
if ($action === 'trigger' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $workflowId = $input['workflow_id'] ?? null;
    $data = $input['data'] ?? [];
    
    if (!$workflowId) {
        jsonResponse(['success' => false, 'error' => 'Workflow ID required'], 400);
    }
    
    $result = triggerWorkflow($workflowId, $data);
    jsonResponse($result);
}

// Process scheduled tasks (cron endpoint)
if ($action === 'process_scheduled') {
    $processed = processScheduledTasks();
    jsonResponse(['success' => true, 'processed' => $processed]);
}

// Get workflow details
if ($action === 'get_workflow') {
    $workflowId = $_GET['workflow_id'] ?? null;
    
    if (!$workflowId) {
        jsonResponse(['success' => false, 'error' => 'Workflow ID required'], 400);
    }
    
    $workflow = getWorkflow($workflowId);
    if ($workflow) {
        jsonResponse(['success' => true, 'workflow' => $workflow]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Workflow not found'], 404);
    }
}

// List all workflows
if ($action === 'list_workflows') {
    $activeOnly = ($_GET['active_only'] ?? 'true') === 'true';
    $workflows = getWorkflows($activeOnly);
    jsonResponse(['success' => true, 'workflows' => $workflows]);
}

// Get execution status
if ($action === 'get_execution') {
    $executionId = $_GET['execution_id'] ?? null;
    
    if (!$executionId) {
        jsonResponse(['success' => false, 'error' => 'Execution ID required'], 400);
    }
    
    $db = getWorkflowsDB();
    $stmt = $db->prepare("SELECT * FROM workflow_executions WHERE id = ?");
    $stmt->execute([$executionId]);
    $execution = $stmt->fetch();
    
    if ($execution) {
        // Get logs
        $stmt = $db->prepare("SELECT * FROM workflow_logs WHERE execution_id = ? ORDER BY created_at ASC");
        $stmt->execute([$executionId]);
        $logs = $stmt->fetchAll();
        
        $execution['logs'] = $logs;
        jsonResponse(['success' => true, 'execution' => $execution]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Execution not found'], 404);
    }
}

// Get workflow statistics
if ($action === 'stats') {
    $stats = getWorkflowStats();
    jsonResponse(['success' => true, 'stats' => $stats]);
}

// Create custom workflow
if ($action === 'create_workflow' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $workflowName = $input['workflow_name'] ?? null;
    $description = $input['description'] ?? '';
    $triggerType = $input['trigger_type'] ?? 'manual';
    $steps = $input['steps'] ?? [];
    
    if (!$workflowName || empty($steps)) {
        jsonResponse(['success' => false, 'error' => 'Workflow name and steps required'], 400);
    }
    
    $db = getWorkflowsDB();
    
    try {
        $db->beginTransaction();
        
        // Insert workflow
        $stmt = $db->prepare("
            INSERT INTO workflows (workflow_name, description, trigger_type, is_template)
            VALUES (?, ?, ?, 0)
        ");
        $stmt->execute([$workflowName, $description, $triggerType]);
        $workflowId = $db->lastInsertId();
        
        // Insert steps
        foreach ($steps as $i => $step) {
            $stmt = $db->prepare("
                INSERT INTO workflow_steps (workflow_id, step_number, step_type, step_config, delay_minutes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $workflowId,
                $i + 1,
                $step['step_type'],
                json_encode($step['config']),
                $step['delay_minutes'] ?? 0
            ]);
        }
        
        $db->commit();
        jsonResponse(['success' => true, 'workflow_id' => $workflowId]);
        
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// Update workflow
if ($action === 'update_workflow' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $workflowId = $input['workflow_id'] ?? null;
    $isActive = $input['is_active'] ?? null;
    
    if (!$workflowId) {
        jsonResponse(['success' => false, 'error' => 'Workflow ID required'], 400);
    }
    
    $db = getWorkflowsDB();
    
    try {
        if ($isActive !== null) {
            $stmt = $db->prepare("UPDATE workflows SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive ? 1 : 0, $workflowId]);
        }
        
        jsonResponse(['success' => true]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// Delete workflow
if ($action === 'delete_workflow' && $method === 'DELETE') {
    $workflowId = $_GET['workflow_id'] ?? null;
    
    if (!$workflowId) {
        jsonResponse(['success' => false, 'error' => 'Workflow ID required'], 400);
    }
    
    $db = getWorkflowsDB();
    
    try {
        $stmt = $db->prepare("DELETE FROM workflows WHERE id = ?");
        $stmt->execute([$workflowId]);
        
        jsonResponse(['success' => true]);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// Pause execution
if ($action === 'pause_execution' && $method === 'POST') {
    $executionId = $_POST['execution_id'] ?? null;
    
    if (!$executionId) {
        jsonResponse(['success' => false, 'error' => 'Execution ID required'], 400);
    }
    
    updateExecutionStatus($executionId, 'paused');
    jsonResponse(['success' => true]);
}

// Resume execution
if ($action === 'resume_execution' && $method === 'POST') {
    $executionId = $_POST['execution_id'] ?? null;
    
    if (!$executionId) {
        jsonResponse(['success' => false, 'error' => 'Execution ID required'], 400);
    }
    
    updateExecutionStatus($executionId, 'running');
    jsonResponse(['success' => true]);
}

// Default response
jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
?>
