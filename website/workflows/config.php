<?php
// Business Automation Workflow Engine Configuration
// USES: SQLite3 class (NOT PDO)
define('WORKFLOWS_DB_PATH', __DIR__ . '/../databases/workflows.db');

function getWorkflowsDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new SQLite3(WORKFLOWS_DB_PATH);
            $db->enableExceptions(true);
            $db->busyTimeout(5000);
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $db;
}

// Get all workflows
function getWorkflows($activeOnly = true) {
    $db = getWorkflowsDB();
    
    if ($activeOnly) {
        $result = $db->query("SELECT * FROM workflows WHERE is_active = 1 ORDER BY workflow_name");
    } else {
        $result = $db->query("SELECT * FROM workflows ORDER BY workflow_name");
    }
    
    $workflows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $workflows[] = $row;
    }
    return $workflows;
}

// Get single workflow with steps
function getWorkflow($id) {
    $db = getWorkflowsDB();
    
    $stmt = $db->prepare("SELECT * FROM workflows WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $workflow = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($workflow) {
        $stmt = $db->prepare("SELECT * FROM workflow_steps WHERE workflow_id = :id ORDER BY step_number");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $steps = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $steps[] = $row;
        }
        $workflow['steps'] = $steps;
    }
    
    return $workflow;
}

// Trigger workflow execution
function triggerWorkflow($workflowId, $triggerData = []) {
    $db = getWorkflowsDB();
    
    $workflow = getWorkflow($workflowId);
    if (!$workflow || !$workflow['is_active']) {
        return ['success' => false, 'error' => 'Workflow not found or inactive'];
    }
    
    // Create execution record
    $stmt = $db->prepare("
        INSERT INTO workflow_executions (workflow_id, trigger_data, current_step)
        VALUES (:workflow_id, :trigger_data, 1)
    ");
    $stmt->bindValue(':workflow_id', $workflowId, SQLITE3_INTEGER);
    $stmt->bindValue(':trigger_data', json_encode($triggerData), SQLITE3_TEXT);
    $stmt->execute();
    $executionId = $db->lastInsertRowID();
    
    // Log workflow start
    logWorkflow($executionId, null, 'info', 'Workflow started', $triggerData);
    
    // Execute first step
    if (!empty($workflow['steps'])) {
        executeWorkflowStep($executionId, $workflow['steps'][0], $triggerData);
    }
    
    return ['success' => true, 'execution_id' => $executionId];
}

// Execute a single workflow step
function executeWorkflowStep($executionId, $step, $data) {
    $db = getWorkflowsDB();
    $config = json_decode($step['step_config'], true);
    
    try {
        // Apply delay if specified
        if ($step['delay_minutes'] > 0) {
            scheduleTask($executionId, $step['id'], $step['delay_minutes']);
            logWorkflow($executionId, $step['id'], 'info', "Step delayed by {$step['delay_minutes']} minutes");
            return;
        }
        
        // Check conditions if present
        if ($step['condition_rules']) {
            $conditionMet = evaluateCondition(json_decode($step['condition_rules'], true), $data);
            if (!$conditionMet) {
                logWorkflow($executionId, $step['id'], 'info', 'Condition not met, skipping step');
                moveToNextStep($executionId, $step);
                return;
            }
        }
        
        // Execute based on step type
        switch ($step['step_type']) {
            case 'email':
                sendWorkflowEmail($config, $data);
                logWorkflow($executionId, $step['id'], 'success', 'Email sent');
                break;
                
            case 'action':
                executeAction($config, $data);
                logWorkflow($executionId, $step['id'], 'success', 'Action executed: ' . $config['action']);
                break;
                
            case 'delay':
                $delayMinutes = $config['duration_minutes'] ?? 60;
                scheduleTask($executionId, $step['id'], $delayMinutes);
                logWorkflow($executionId, $step['id'], 'info', "Delayed by $delayMinutes minutes");
                return;
                
            case 'condition':
                // Condition already evaluated above
                logWorkflow($executionId, $step['id'], 'success', 'Condition evaluated');
                break;
                
            case 'api_call':
                callExternalAPI($config, $data);
                logWorkflow($executionId, $step['id'], 'success', 'API called');
                break;
        }
        
        // Move to next step
        moveToNextStep($executionId, $step);
        
    } catch (Exception $e) {
        logWorkflow($executionId, $step['id'], 'error', 'Step failed: ' . $e->getMessage());
        updateExecutionStatus($executionId, 'failed', $e->getMessage());
    }
}

// Move to next workflow step
function moveToNextStep($executionId, $currentStep) {
    $db = getWorkflowsDB();
    
    // Get execution
    $stmt = $db->prepare("SELECT * FROM workflow_executions WHERE id = :id");
    $stmt->bindValue(':id', $executionId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $execution = $result->fetchArray(SQLITE3_ASSOC);
    
    // Get workflow steps
    $workflow = getWorkflow($execution['workflow_id']);
    $currentIndex = array_search($currentStep['id'], array_column($workflow['steps'], 'id'));
    
    // Check if there's a next step
    if ($currentIndex !== false && isset($workflow['steps'][$currentIndex + 1])) {
        $nextStep = $workflow['steps'][$currentIndex + 1];
        $stmt = $db->prepare("UPDATE workflow_executions SET current_step = :step WHERE id = :id");
        $stmt->bindValue(':step', $nextStep['step_number'], SQLITE3_INTEGER);
        $stmt->bindValue(':id', $executionId, SQLITE3_INTEGER);
        $stmt->execute();
        
        $triggerData = json_decode($execution['trigger_data'], true);
        executeWorkflowStep($executionId, $nextStep, $triggerData);
    } else {
        // Workflow complete
        updateExecutionStatus($executionId, 'completed');
        logWorkflow($executionId, null, 'success', 'Workflow completed');
    }
}

// Schedule delayed task
function scheduleTask($executionId, $stepId, $delayMinutes) {
    $db = getWorkflowsDB();
    $scheduledTime = date('Y-m-d H:i:s', strtotime("+$delayMinutes minutes"));
    
    $stmt = $db->prepare("
        INSERT INTO scheduled_tasks (execution_id, step_id, scheduled_time)
        VALUES (:execution_id, :step_id, :scheduled_time)
    ");
    $stmt->bindValue(':execution_id', $executionId, SQLITE3_INTEGER);
    $stmt->bindValue(':step_id', $stepId, SQLITE3_INTEGER);
    $stmt->bindValue(':scheduled_time', $scheduledTime, SQLITE3_TEXT);
    $stmt->execute();
}

// Process scheduled tasks
function processScheduledTasks() {
    $db = getWorkflowsDB();
    
    $result = $db->query("
        SELECT * FROM scheduled_tasks
        WHERE status = 'pending' AND scheduled_time <= datetime('now')
        ORDER BY scheduled_time
        LIMIT 50
    ");
    
    $tasks = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tasks[] = $row;
    }
    
    foreach ($tasks as $task) {
        try {
            // Mark as processing
            $stmt = $db->prepare("UPDATE scheduled_tasks SET status = 'processing' WHERE id = :id");
            $stmt->bindValue(':id', $task['id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            // Get execution and step
            $stmt = $db->prepare("SELECT * FROM workflow_executions WHERE id = :id");
            $stmt->bindValue(':id', $task['execution_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $execution = $result->fetchArray(SQLITE3_ASSOC);
            
            $stmt = $db->prepare("SELECT * FROM workflow_steps WHERE id = :id");
            $stmt->bindValue(':id', $task['step_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $step = $result->fetchArray(SQLITE3_ASSOC);
            
            // Execute step
            $triggerData = json_decode($execution['trigger_data'], true);
            executeWorkflowStep($task['execution_id'], $step, $triggerData);
            
            // Mark as completed
            $stmt = $db->prepare("UPDATE scheduled_tasks SET status = 'completed' WHERE id = :id");
            $stmt->bindValue(':id', $task['id'], SQLITE3_INTEGER);
            $stmt->execute();
            
        } catch (Exception $e) {
            // Handle retry logic
            $retryCount = $task['retry_count'] + 1;
            if ($retryCount >= $task['max_retries']) {
                $stmt = $db->prepare("UPDATE scheduled_tasks SET status = 'failed' WHERE id = :id");
                $stmt->bindValue(':id', $task['id'], SQLITE3_INTEGER);
                $stmt->execute();
                logWorkflow($task['execution_id'], $task['step_id'], 'error', 'Task failed after max retries');
            } else {
                $stmt = $db->prepare("
                    UPDATE scheduled_tasks 
                    SET status = 'pending', retry_count = :retry, scheduled_time = datetime('now', '+5 minutes')
                    WHERE id = :id
                ");
                $stmt->bindValue(':retry', $retryCount, SQLITE3_INTEGER);
                $stmt->bindValue(':id', $task['id'], SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }
    
    return count($tasks);
}

// Send workflow email
function sendWorkflowEmail($config, $data) {
    $to = replaceVariables($config['to'], $data);
    $subject = replaceVariables($config['subject'], $data);
    $template = $config['template'] ?? 'default';
    
    // Get template content (simplified)
    $message = "This is an automated message from TrueVault VPN workflow.\n\n";
    $message .= "Template: $template\n";
    
    $headers = "From: noreply@vpn.the-truth-publishing.com\r\n";
    mail($to, $subject, $message, $headers);
}

// Execute action
function executeAction($config, $data) {
    $action = $config['action'];
    
    switch ($action) {
        case 'update_customer_status':
            // Update customer status in database
            break;
            
        case 'generate_invoice':
            // Generate invoice
            break;
            
        case 'create_customer_folder':
            // Create folder structure
            break;
            
        case 'log_transaction':
            // Log transaction
            break;
            
        case 'suspend_service':
            // Suspend customer service
            break;
            
        case 'check_knowledge_base':
            // Search knowledge base
            break;
            
        case 'escalate_ticket':
            // Escalate support ticket
            break;
    }
}

// Call external API
function callExternalAPI($config, $data) {
    $url = replaceVariables($config['url'], $data);
    $method = $config['method'] ?? 'POST';
    $payload = $config['payload'] ?? [];
    
    // Make API call (simplified)
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// Evaluate condition
function evaluateCondition($condition, $data) {
    $check = $condition['check'];
    $operator = $condition['operator'] ?? '=';
    $value = $condition['value'] ?? null;
    
    // Get actual value from data
    $actualValue = $data[$check] ?? null;
    
    switch ($operator) {
        case '=':
        case 'equals':
            return $actualValue == $value;
        case '!=':
            return $actualValue != $value;
        case '>':
            return $actualValue > $value;
        case '<':
            return $actualValue < $value;
        case '>=':
            return $actualValue >= $value;
        case '<=':
            return $actualValue <= $value;
        default:
            return false;
    }
}

// Replace variables in strings
function replaceVariables($string, $data) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $subKey => $subValue) {
                $string = str_replace("{{" . $key . "." . $subKey . "}}", $subValue, $string);
            }
        } else {
            $string = str_replace("{{" . $key . "}}", $value, $string);
        }
    }
    return $string;
}

// Log workflow activity
function logWorkflow($executionId, $stepId, $level, $message, $data = null) {
    $db = getWorkflowsDB();
    $stmt = $db->prepare("
        INSERT INTO workflow_logs (execution_id, step_id, log_level, message, data)
        VALUES (:execution_id, :step_id, :level, :message, :data)
    ");
    $stmt->bindValue(':execution_id', $executionId, SQLITE3_INTEGER);
    $stmt->bindValue(':step_id', $stepId, SQLITE3_INTEGER);
    $stmt->bindValue(':level', $level, SQLITE3_TEXT);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->bindValue(':data', $data ? json_encode($data) : null, SQLITE3_TEXT);
    $stmt->execute();
}

// Update execution status
function updateExecutionStatus($executionId, $status, $errorMessage = null) {
    $db = getWorkflowsDB();
    
    if ($status === 'completed' || $status === 'failed') {
        $stmt = $db->prepare("
            UPDATE workflow_executions 
            SET status = :status, completed_at = CURRENT_TIMESTAMP, error_message = :error
            WHERE id = :id
        ");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':error', $errorMessage, SQLITE3_TEXT);
        $stmt->bindValue(':id', $executionId, SQLITE3_INTEGER);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("UPDATE workflow_executions SET status = :status WHERE id = :id");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':id', $executionId, SQLITE3_INTEGER);
        $stmt->execute();
    }
}

// Get workflow statistics
function getWorkflowStats() {
    $db = getWorkflowsDB();
    
    $stats = [];
    
    $result = $db->query("SELECT COUNT(*) as count FROM workflows WHERE is_active = 1");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['active_workflows'] = $row['count'];
    
    $result = $db->query("SELECT COUNT(*) as count FROM workflow_executions WHERE status = 'running'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['running_executions'] = $row['count'];
    
    $result = $db->query("SELECT COUNT(*) as count FROM workflow_executions WHERE DATE(started_at) = DATE('now')");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['executions_today'] = $row['count'];
    
    $result = $db->query("SELECT COUNT(*) as count FROM scheduled_tasks WHERE status = 'pending'");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['pending_tasks'] = $row['count'];
    
    return $stats;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
