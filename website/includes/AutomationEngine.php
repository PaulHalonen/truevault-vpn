<?php
/**
 * TrueVault VPN - Automation Engine
 * Processes workflows and scheduled tasks
 * 
 * @package TrueVault
 * @version 2.0.0
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Email.php';
require_once __DIR__ . '/EmailTemplate.php';

class AutomationEngine {
    private $db;
    private $email;
    private $template;
    private $currentExecution = null;
    
    public function __construct() {
        $this->db = new Database('logs');
        $this->email = new Email();
        $this->template = new EmailTemplate();
        $this->ensureTablesExist();
    }
    
    /**
     * Ensure automation tables exist
     */
    private function ensureTablesExist() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS workflow_executions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                workflow_name TEXT NOT NULL,
                trigger_event TEXT NOT NULL,
                user_id INTEGER,
                user_email TEXT,
                status TEXT NOT NULL DEFAULT 'running',
                started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME,
                error_message TEXT,
                execution_data TEXT
            )
        ");
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS scheduled_tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                execution_id INTEGER,
                task_name TEXT NOT NULL,
                task_type TEXT NOT NULL,
                task_data TEXT,
                execute_at DATETIME NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                executed_at DATETIME,
                result TEXT,
                FOREIGN KEY (execution_id) REFERENCES workflow_executions(id)
            )
        ");
        
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_scheduled_status ON scheduled_tasks(status, execute_at)");
    }
    
    /**
     * Start a new workflow execution
     */
    public function startWorkflow($workflowName, $triggerEvent, $userId = null, $userEmail = null, $data = []) {
        $stmt = $this->db->prepare("
            INSERT INTO workflow_executions (workflow_name, trigger_event, user_id, user_email, execution_data)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $workflowName,
            $triggerEvent,
            $userId,
            $userEmail,
            json_encode($data)
        ]);
        
        $this->currentExecution = $this->db->lastInsertId();
        
        $this->log("Started workflow: $workflowName (trigger: $triggerEvent)");
        
        return $this->currentExecution;
    }
    
    /**
     * Complete current workflow
     */
    public function completeWorkflow($success = true, $errorMessage = null) {
        if (!$this->currentExecution) return;
        
        $stmt = $this->db->prepare("
            UPDATE workflow_executions 
            SET status = ?, completed_at = datetime('now'), error_message = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $success ? 'completed' : 'failed',
            $errorMessage,
            $this->currentExecution
        ]);
        
        $this->log("Completed workflow execution #{$this->currentExecution}");
        $this->currentExecution = null;
    }
    
    /**
     * Send email immediately
     */
    public function sendEmail($to, $templateName, $variables = [], $toAdmin = false) {
        $body = $this->template->render($templateName, $variables);
        $subject = $this->template->getSubject($templateName, $variables);
        
        if (!$body) {
            $this->log("Template not found: $templateName", 'error');
            return false;
        }
        
        if ($toAdmin) {
            $result = $this->email->sendToAdmin($subject, $body);
        } else {
            $result = $this->email->send($to, $subject, $body);
        }
        
        $this->log("Email sent to $to using template $templateName: " . ($result ? 'success' : 'failed'));
        
        return $result;
    }
    
    /**
     * Schedule email for later
     */
    public function scheduleEmail($to, $templateName, $variables = [], $delayMinutes = 60, $toAdmin = false) {
        $executeAt = date('Y-m-d H:i:s', strtotime("+{$delayMinutes} minutes"));
        
        $stmt = $this->db->prepare("
            INSERT INTO scheduled_tasks (execution_id, task_name, task_type, task_data, execute_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->currentExecution,
            "Send email: $templateName to $to",
            'email',
            json_encode([
                'to' => $to,
                'template' => $templateName,
                'variables' => $variables,
                'to_admin' => $toAdmin
            ]),
            $executeAt
        ]);
        
        $this->log("Scheduled email '$templateName' to $to for $executeAt");
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Schedule a generic task
     */
    public function scheduleTask($taskName, $taskType, $taskData, $delayMinutes = 60) {
        $executeAt = date('Y-m-d H:i:s', strtotime("+{$delayMinutes} minutes"));
        
        $stmt = $this->db->prepare("
            INSERT INTO scheduled_tasks (execution_id, task_name, task_type, task_data, execute_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->currentExecution,
            $taskName,
            $taskType,
            json_encode($taskData),
            $executeAt
        ]);
        
        $this->log("Scheduled task '$taskName' for $executeAt");
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Process all pending scheduled tasks
     */
    public function processScheduledTasks($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT * FROM scheduled_tasks 
            WHERE status = 'pending' AND execute_at <= datetime('now')
            ORDER BY execute_at ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $tasks = $stmt->fetchAll();
        
        $processed = 0;
        
        foreach ($tasks as $task) {
            $success = $this->executeTask($task);
            
            $stmt = $this->db->prepare("
                UPDATE scheduled_tasks 
                SET status = ?, executed_at = datetime('now'), result = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $success ? 'completed' : 'failed',
                $success ? 'OK' : 'Failed',
                $task['id']
            ]);
            
            if ($success) $processed++;
        }
        
        return $processed;
    }
    
    /**
     * Execute a single scheduled task
     */
    private function executeTask($task) {
        $data = json_decode($task['task_data'], true) ?? [];
        
        switch ($task['task_type']) {
            case 'email':
                return $this->sendEmail(
                    $data['to'],
                    $data['template'],
                    $data['variables'] ?? [],
                    $data['to_admin'] ?? false
                );
                
            case 'update_user_status':
                return $this->updateUserStatus($data['user_id'], $data['status']);
                
            case 'suspend_account':
                return $this->suspendAccount($data['user_id']);
                
            case 'webhook':
                return $this->callWebhook($data['url'], $data['payload'] ?? []);
                
            default:
                $this->log("Unknown task type: {$task['task_type']}", 'warning');
                return false;
        }
    }
    
    /**
     * Update user status
     */
    public function updateUserStatus($userId, $status) {
        try {
            $mainDb = new Database('main');
            $stmt = $mainDb->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $userId]);
            
            $this->log("Updated user #$userId status to: $status");
            return true;
        } catch (Exception $e) {
            $this->log("Failed to update user status: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Suspend user account
     */
    public function suspendAccount($userId) {
        try {
            $mainDb = new Database('main');
            $stmt = $mainDb->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Also deactivate all their devices
            $devicesDb = new Database('devices');
            $stmt = $devicesDb->prepare("UPDATE devices SET is_active = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $this->log("Suspended account for user #$userId");
            return true;
        } catch (Exception $e) {
            $this->log("Failed to suspend account: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Call external webhook
     */
    private function callWebhook($url, $payload) {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $success = $httpCode >= 200 && $httpCode < 300;
            $this->log("Webhook to $url: HTTP $httpCode");
            
            return $success;
        } catch (Exception $e) {
            $this->log("Webhook error: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Log automation activity
     */
    public function log($message, $level = 'info') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO automation_log (level, message, execution_id, created_at)
                VALUES (?, ?, ?, datetime('now'))
            ");
            $stmt->execute([$level, $message, $this->currentExecution]);
        } catch (Exception $e) {
            // Create table if it doesn't exist
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS automation_log (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    level TEXT DEFAULT 'info',
                    message TEXT NOT NULL,
                    execution_id INTEGER,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            // Try again
            $stmt = $this->db->prepare("
                INSERT INTO automation_log (level, message, execution_id, created_at)
                VALUES (?, ?, ?, datetime('now'))
            ");
            $stmt->execute([$level, $message, $this->currentExecution]);
        }
    }
    
    /**
     * Get workflow statistics
     */
    public function getStats($days = 7) {
        $stmt = $this->db->prepare("
            SELECT 
                workflow_name,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM workflow_executions
            WHERE started_at >= datetime('now', '-' || ? || ' days')
            GROUP BY workflow_name
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get pending scheduled tasks count
     */
    public function getPendingCount() {
        $stmt = $this->db->query("
            SELECT COUNT(*) FROM scheduled_tasks 
            WHERE status = 'pending'
        ");
        return $stmt->fetchColumn();
    }
    
    /**
     * Get recent executions
     */
    public function getRecentExecutions($limit = 20) {
        $stmt = $this->db->prepare("
            SELECT * FROM workflow_executions
            ORDER BY started_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get scheduled tasks
     */
    public function getScheduledTasks($status = 'pending', $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT * FROM scheduled_tasks
            WHERE status = ?
            ORDER BY execute_at ASC
            LIMIT ?
        ");
        $stmt->execute([$status, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Cancel scheduled task
     */
    public function cancelTask($taskId) {
        $stmt = $this->db->prepare("
            UPDATE scheduled_tasks 
            SET status = 'cancelled'
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$taskId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get automation log
     */
    public function getLog($limit = 100) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM automation_log
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
