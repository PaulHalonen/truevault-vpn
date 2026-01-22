<?php
/**
 * TrueVault VPN - Automation Engine
 * Task 7.6 - Workflow processor for automated business operations
 * 
 * @created January 2026
 */

if (!defined('TRUEVAULT_INIT')) {
    die('Direct access not allowed');
}

class AutomationEngine {
    
    private static $currentExecution = null;
    private static $executionLog = [];
    
    /**
     * Trigger a workflow by event name
     */
    public static function trigger($eventName, $data = []) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            // Find matching workflow
            $stmt = $db->prepare("SELECT * FROM workflows WHERE trigger_event = :event AND is_active = 1");
            $stmt->bindValue(':event', $eventName, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            $workflow = $result->fetchArray(SQLITE3_ASSOC);
            $db->close();
            
            if (!$workflow) {
                self::log('info', "No workflow found for event: {$eventName}");
                return false;
            }
            
            return self::execute($workflow, $data);
            
        } catch (Exception $e) {
            self::log('error', "Trigger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute a workflow
     */
    public static function execute($workflow, $data = []) {
        try {
            // Start execution record
            $executionId = self::startExecution($workflow, $data);
            self::$currentExecution = $executionId;
            self::$executionLog = [];
            
            self::log('info', "Starting workflow: {$workflow['workflow_name']}");
            
            // Load the Workflows class and execute
            require_once __DIR__ . '/Workflows.php';
            
            $workflowMethod = self::camelCase($workflow['workflow_name']);
            
            if (method_exists('Workflows', $workflowMethod)) {
                $result = Workflows::$workflowMethod($data, $executionId);
                
                // Complete execution
                self::completeExecution($executionId, $result);
                
                return $result;
            } else {
                throw new Exception("Workflow method not found: {$workflowMethod}");
            }
            
        } catch (Exception $e) {
            self::failExecution(self::$currentExecution, $e->getMessage());
            self::log('error', "Execution failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Schedule a workflow step for later execution
     */
    public static function scheduleStep($executionId, $stepName, $stepData, $delayMinutes = 0) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $executeAt = date('Y-m-d H:i:s', strtotime("+{$delayMinutes} minutes"));
            
            $stmt = $db->prepare("
                INSERT INTO scheduled_workflow_steps (execution_id, step_name, step_data, execute_at)
                VALUES (:exec_id, :step, :data, :execute_at)
            ");
            
            $stmt->bindValue(':exec_id', $executionId, SQLITE3_INTEGER);
            $stmt->bindValue(':step', $stepName, SQLITE3_TEXT);
            $stmt->bindValue(':data', json_encode($stepData), SQLITE3_TEXT);
            $stmt->bindValue(':execute_at', $executeAt, SQLITE3_TEXT);
            
            $stmt->execute();
            $stepId = $db->lastInsertRowID();
            $db->close();
            
            self::log('info', "Scheduled step '{$stepName}' for {$executeAt}");
            
            return $stepId;
            
        } catch (Exception $e) {
            self::log('error', "Schedule step error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process all scheduled steps that are due
     */
    public static function processScheduled() {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $now = date('Y-m-d H:i:s');
            
            $result = $db->query("
                SELECT s.*, w.workflow_name
                FROM scheduled_workflow_steps s
                JOIN workflow_executions e ON s.execution_id = e.id
                JOIN workflows w ON e.workflow_id = w.id
                WHERE s.status = 'pending' AND s.execute_at <= '{$now}'
                ORDER BY s.execute_at ASC
                LIMIT 50
            ");
            
            $processed = 0;
            
            while ($step = $result->fetchArray(SQLITE3_ASSOC)) {
                self::executeStep($step);
                $processed++;
            }
            
            $db->close();
            
            return $processed;
            
        } catch (Exception $e) {
            error_log("Process scheduled error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Execute a single scheduled step
     */
    private static function executeStep($step) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            // Mark as running
            $db->exec("UPDATE scheduled_workflow_steps SET status = 'running' WHERE id = {$step['id']}");
            
            // Load workflows and execute step
            require_once __DIR__ . '/Workflows.php';
            
            $stepData = json_decode($step['step_data'], true) ?? [];
            $stepMethod = self::camelCase($step['step_name']);
            
            $result = null;
            if (method_exists('Workflows', $stepMethod)) {
                $result = Workflows::$stepMethod($stepData, $step['execution_id']);
            }
            
            // Mark as completed
            $stmt = $db->prepare("
                UPDATE scheduled_workflow_steps 
                SET status = 'completed', executed_at = CURRENT_TIMESTAMP, result = :result
                WHERE id = :id
            ");
            $stmt->bindValue(':result', json_encode($result), SQLITE3_TEXT);
            $stmt->bindValue(':id', $step['id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            // Log
            self::logAction($step['workflow_name'], $step['step_name'], $stepData['user_id'] ?? null, $stepData['email'] ?? null, "Step completed");
            
            $db->close();
            
            return true;
            
        } catch (Exception $e) {
            // Mark as failed
            $db = new SQLite3(DB_LOGS);
            $stmt = $db->prepare("UPDATE scheduled_workflow_steps SET status = 'failed', error_message = :error WHERE id = :id");
            $stmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
            $stmt->bindValue(':id', $step['id'], SQLITE3_INTEGER);
            $stmt->execute();
            $db->close();
            
            error_log("Step execution failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Start workflow execution record
     */
    private static function startExecution($workflow, $data) {
        $db = new SQLite3(DB_LOGS);
        $db->enableExceptions(true);
        
        $stmt = $db->prepare("
            INSERT INTO workflow_executions (workflow_id, workflow_name, trigger_event, user_id, trigger_data)
            VALUES (:wf_id, :name, :trigger, :user_id, :data)
        ");
        
        $stmt->bindValue(':wf_id', $workflow['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':name', $workflow['workflow_name'], SQLITE3_TEXT);
        $stmt->bindValue(':trigger', $workflow['trigger_event'], SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $data['user_id'] ?? null);
        $stmt->bindValue(':data', json_encode($data), SQLITE3_TEXT);
        
        $stmt->execute();
        $id = $db->lastInsertRowID();
        $db->close();
        
        return $id;
    }
    
    /**
     * Complete workflow execution
     */
    private static function completeExecution($executionId, $result = true) {
        $db = new SQLite3(DB_LOGS);
        $db->enableExceptions(true);
        
        $status = $result ? 'completed' : 'failed';
        $log = json_encode(self::$executionLog);
        
        $stmt = $db->prepare("
            UPDATE workflow_executions 
            SET status = :status, completed_at = CURRENT_TIMESTAMP, execution_log = :log
            WHERE id = :id
        ");
        
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':log', $log, SQLITE3_TEXT);
        $stmt->bindValue(':id', $executionId, SQLITE3_INTEGER);
        
        $stmt->execute();
        $db->close();
    }
    
    /**
     * Fail workflow execution
     */
    private static function failExecution($executionId, $error) {
        if (!$executionId) return;
        
        $db = new SQLite3(DB_LOGS);
        
        $stmt = $db->prepare("
            UPDATE workflow_executions 
            SET status = 'failed', completed_at = CURRENT_TIMESTAMP, error_message = :error
            WHERE id = :id
        ");
        
        $stmt->bindValue(':error', $error, SQLITE3_TEXT);
        $stmt->bindValue(':id', $executionId, SQLITE3_INTEGER);
        
        $stmt->execute();
        $db->close();
    }
    
    /**
     * Log workflow action to automation_log
     */
    public static function logAction($workflowName, $action, $userId = null, $email = null, $details = '', $status = 'success') {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $stmt = $db->prepare("
                INSERT INTO automation_log (workflow_name, action, target_user_id, target_email, details, status)
                VALUES (:workflow, :action, :user_id, :email, :details, :status)
            ");
            
            $stmt->bindValue(':workflow', $workflowName, SQLITE3_TEXT);
            $stmt->bindValue(':action', $action, SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $userId);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':details', $details, SQLITE3_TEXT);
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            
            $stmt->execute();
            $db->close();
            
        } catch (Exception $e) {
            error_log("Log action error: " . $e->getMessage());
        }
    }
    
    /**
     * Internal logging
     */
    private static function log($level, $message) {
        self::$executionLog[] = [
            'level' => $level,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($level === 'error') {
            error_log("[AutomationEngine] {$message}");
        }
    }
    
    /**
     * Convert snake_case to camelCase
     */
    private static function camelCase($string) {
        $result = str_replace('_', '', ucwords($string, '_'));
        return lcfirst($result);
    }
    
    /**
     * Get workflow statistics
     */
    public static function getStats($days = 7) {
        try {
            $db = new SQLite3(DB_LOGS);
            
            $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            $stats = [
                'total_executions' => 0,
                'completed' => 0,
                'failed' => 0,
                'running' => 0,
                'scheduled_pending' => 0,
                'by_workflow' => []
            ];
            
            // Execution stats
            $result = $db->query("SELECT status, COUNT(*) as count FROM workflow_executions WHERE started_at >= '{$since}' GROUP BY status");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $stats[$row['status']] = (int)$row['count'];
                $stats['total_executions'] += (int)$row['count'];
            }
            
            // By workflow
            $result = $db->query("SELECT workflow_name, COUNT(*) as count FROM workflow_executions WHERE started_at >= '{$since}' GROUP BY workflow_name");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $stats['by_workflow'][$row['workflow_name']] = (int)$row['count'];
            }
            
            // Scheduled pending
            $stats['scheduled_pending'] = (int)$db->querySingle("SELECT COUNT(*) FROM scheduled_workflow_steps WHERE status = 'pending'");
            
            $db->close();
            return $stats;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get recent executions
     */
    public static function getRecentExecutions($limit = 20) {
        try {
            $db = new SQLite3(DB_LOGS);
            
            $result = $db->query("
                SELECT * FROM workflow_executions 
                ORDER BY started_at DESC 
                LIMIT {$limit}
            ");
            
            $executions = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $executions[] = $row;
            }
            
            $db->close();
            return $executions;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Cancel scheduled step
     */
    public static function cancelScheduledStep($stepId) {
        try {
            $db = new SQLite3(DB_LOGS);
            $stmt = $db->prepare("UPDATE scheduled_workflow_steps SET status = 'cancelled' WHERE id = :id AND status = 'pending'");
            $stmt->bindValue(':id', $stepId, SQLITE3_INTEGER);
            $stmt->execute();
            $changes = $db->changes();
            $db->close();
            return $changes > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Retry failed execution
     */
    public static function retryExecution($executionId) {
        try {
            $db = new SQLite3(DB_LOGS);
            
            $result = $db->query("SELECT * FROM workflow_executions WHERE id = {$executionId}");
            $execution = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$execution || $execution['status'] !== 'failed') {
                return false;
            }
            
            // Get workflow
            $workflow = $db->query("SELECT * FROM workflows WHERE id = {$execution['workflow_id']}")->fetchArray(SQLITE3_ASSOC);
            $data = json_decode($execution['trigger_data'], true) ?? [];
            
            $db->close();
            
            // Re-execute
            return self::execute($workflow, $data);
            
        } catch (Exception $e) {
            return false;
        }
    }
}
