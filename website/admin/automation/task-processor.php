<?php
/**
 * TrueVault VPN - Scheduled Task Processor
 * Task 17.4: Cron job to process delayed workflow steps
 * Created: January 24, 2026
 * 
 * CRON SETUP (every 5 minutes):
 * */5 * * * * php /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/admin/automation/task-processor.php
 */

// Allow CLI or web trigger
$isCli = php_sapi_name() === 'cli';
$isWebTrigger = isset($_GET['action']) && $_GET['action'] === 'process';

if (!$isCli && !$isWebTrigger) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied. Use CLI or ?action=process']);
    exit;
}

require_once __DIR__ . '/workflows.php';

class TaskProcessor {
    private $db;
    private $workflow;
    private $processed = 0;
    private $failed = 0;
    private $logFile;
    
    public function __construct() {
        $this->db = new SQLite3(__DIR__ . '/databases/automation.db');
        $this->db->enableExceptions(true);
        $this->workflow = new AutomationWorkflow();
        
        // Setup logging
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/task-processor.log';
    }
    
    public function __destruct() {
        if ($this->db) $this->db->close();
    }
    
    /**
     * Process all pending scheduled tasks
     */
    public function processAll() {
        $this->log("=== Task Processor Started ===");
        
        // Get due tasks
        $result = $this->db->query("
            SELECT * FROM scheduled_tasks 
            WHERE status = 'pending' 
            AND datetime(execute_at) <= datetime('now')
            ORDER BY execute_at ASC
            LIMIT 50
        ");
        
        $tasks = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tasks[] = $row;
        }
        
        $totalTasks = count($tasks);
        $this->log("Found {$totalTasks} pending tasks");
        
        if ($totalTasks === 0) {
            return $this->getResults();
        }
        
        foreach ($tasks as $task) {
            $this->processTask($task);
        }
        
        $this->log("Processing complete: {$this->processed} processed, {$this->failed} failed");
        $this->log("=== Task Processor Finished ===\n");
        
        return $this->getResults();
    }
    
    /**
     * Process a single task
     */
    private function processTask($task) {
        $taskId = $task['id'];
        $this->log("Processing task #{$taskId}: {$task['task_type']}");
        
        try {
            // Parse task data
            $taskData = json_decode($task['task_data'], true);
            $step = $taskData['step'] ?? [];
            $context = $taskData['context'] ?? [];
            
            // Execute the step using workflow engine
            $action = $step['action'] ?? '';
            
            switch ($action) {
                case 'send_email':
                    $this->sendEmail($step, $context);
                    break;
                    
                case 'update_status':
                    $this->updateStatus($step, $context);
                    break;
                    
                case 'suspend_service':
                    $this->suspendService($context);
                    break;
                    
                case 'escalate_ticket':
                    $this->escalateTicket($context);
                    break;
                    
                default:
                    $this->log("  Unknown action: {$action}");
            }
            
            // Mark as completed
            $this->markCompleted($taskId);
            $this->processed++;
            $this->log("  Task #{$taskId} completed");
            
        } catch (Exception $e) {
            $this->markFailed($taskId, $e->getMessage());
            $this->failed++;
            $this->log("  Task #{$taskId} FAILED: " . $e->getMessage());
        }
    }
    
    /**
     * Send email action
     */
    private function sendEmail($step, $context) {
        $templateName = $step['template'] ?? '';
        $to = $step['to'] ?? 'customer';
        
        // Get template
        $stmt = $this->db->prepare("SELECT * FROM email_templates WHERE name = :name AND active = 1");
        $stmt->bindValue(':name', $templateName, SQLITE3_TEXT);
        $result = $stmt->execute();
        $template = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$template) {
            throw new Exception("Template not found: {$templateName}");
        }
        
        // Determine recipient
        $recipientEmail = ($to === 'admin') 
            ? 'paulhalonen@gmail.com'
            : ($context['email'] ?? '');
            
        $recipientName = ($to === 'admin')
            ? 'Admin'
            : ($context['first_name'] ?? 'Customer');
        
        if (empty($recipientEmail)) {
            throw new Exception("No recipient email");
        }
        
        // Replace variables
        $subject = $this->replaceVariables($template['subject'], $context);
        $body = $this->replaceVariables($template['body'], $context);
        
        // Log email
        $stmt = $this->db->prepare("
            INSERT INTO email_log (recipient_email, recipient_name, subject, template_name, email_type, method, status, metadata)
            VALUES (:email, :name, :subject, :template, :type, 'smtp', 'pending', :metadata)
        ");
        $stmt->bindValue(':email', $recipientEmail, SQLITE3_TEXT);
        $stmt->bindValue(':name', $recipientName, SQLITE3_TEXT);
        $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
        $stmt->bindValue(':template', $templateName, SQLITE3_TEXT);
        $stmt->bindValue(':type', $to, SQLITE3_TEXT);
        $stmt->bindValue(':metadata', json_encode($context), SQLITE3_TEXT);
        $stmt->execute();
        $emailId = $this->db->lastInsertRowID();
        
        // Send email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: TrueVault VPN <noreply@the-truth-publishing.com>\r\n";
        
        $sent = @mail($recipientEmail, $subject, $body, $headers);
        
        // Update log
        $status = $sent ? 'sent' : 'failed';
        $this->db->exec("UPDATE email_log SET status = '{$status}', sent_at = datetime('now') WHERE id = {$emailId}");
        
        $this->log("  Email '{$templateName}' -> {$recipientEmail}: {$status}");
    }
    
    /**
     * Replace template variables
     */
    private function replaceVariables($text, $data) {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace('{' . $key . '}', $value, $text);
            }
        }
        $text = str_replace('{dashboard_url}', 'https://vpn.the-truth-publishing.com/dashboard', $text);
        $text = str_replace('{admin_url}', 'https://vpn.the-truth-publishing.com/admin', $text);
        return $text;
    }
    
    /**
     * Update customer status
     */
    private function updateStatus($step, $context) {
        $status = $step['status'] ?? '';
        $customerId = $context['customer_id'] ?? null;
        
        if (!$customerId || !$status) {
            $this->log("  Status update skipped: missing data");
            return;
        }
        
        // Update in main database if available
        $settingsPath = __DIR__ . '/../databases/settings.db';
        if (file_exists($settingsPath)) {
            $settingsDb = new SQLite3($settingsPath);
            $stmt = $settingsDb->prepare("UPDATE users SET status = :status WHERE id = :id");
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
            $stmt->execute();
            $settingsDb->close();
        }
        
        $this->log("  Status updated to '{$status}' for customer #{$customerId}");
    }
    
    /**
     * Suspend service
     */
    private function suspendService($context) {
        $customerId = $context['customer_id'] ?? null;
        
        if (!$customerId) {
            $this->log("  Suspend skipped: no customer ID");
            return;
        }
        
        $settingsPath = __DIR__ . '/../databases/settings.db';
        if (file_exists($settingsPath)) {
            $settingsDb = new SQLite3($settingsPath);
            $stmt = $settingsDb->prepare("UPDATE users SET status = 'suspended' WHERE id = :id");
            $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
            $stmt->execute();
            $settingsDb->close();
        }
        
        $this->log("  Service suspended for customer #{$customerId}");
    }
    
    /**
     * Escalate ticket
     */
    private function escalateTicket($context) {
        $ticketId = $context['ticket_id'] ?? null;
        $this->log("  Ticket #{$ticketId} escalated");
    }
    
    /**
     * Mark task as completed
     */
    private function markCompleted($taskId) {
        $stmt = $this->db->prepare("
            UPDATE scheduled_tasks 
            SET status = 'completed', executed_at = datetime('now') 
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    /**
     * Mark task as failed
     */
    private function markFailed($taskId, $error) {
        $stmt = $this->db->prepare("
            UPDATE scheduled_tasks 
            SET status = 'failed', executed_at = datetime('now'), error_message = :error 
            WHERE id = :id
        ");
        $stmt->bindValue(':error', $error, SQLITE3_TEXT);
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    /**
     * Log message
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}\n";
        
        if (php_sapi_name() === 'cli') {
            echo $line;
        }
        
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }
    
    /**
     * Get results
     */
    private function getResults() {
        return [
            'success' => true,
            'processed' => $this->processed,
            'failed' => $this->failed,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get stats
     */
    public function getStats() {
        return [
            'pending' => $this->db->querySingle("SELECT COUNT(*) FROM scheduled_tasks WHERE status = 'pending'"),
            'completed_today' => $this->db->querySingle("SELECT COUNT(*) FROM scheduled_tasks WHERE status = 'completed' AND date(executed_at) = date('now')"),
            'failed_today' => $this->db->querySingle("SELECT COUNT(*) FROM scheduled_tasks WHERE status = 'failed' AND date(executed_at) = date('now')"),
            'overdue' => $this->db->querySingle("SELECT COUNT(*) FROM scheduled_tasks WHERE status = 'pending' AND datetime(execute_at) < datetime('now', '-1 hour')")
        ];
    }
    
    /**
     * Get upcoming tasks
     */
    public function getUpcoming($limit = 20) {
        $result = $this->db->query("
            SELECT id, workflow_name, task_type, execute_at
            FROM scheduled_tasks 
            WHERE status = 'pending'
            ORDER BY execute_at ASC 
            LIMIT {$limit}
        ");
        
        $tasks = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tasks[] = $row;
        }
        return $tasks;
    }
    
    /**
     * Cleanup old completed/failed tasks (keep 30 days)
     */
    public function cleanup() {
        $this->db->exec("DELETE FROM scheduled_tasks WHERE status IN ('completed', 'failed') AND datetime(executed_at) < datetime('now', '-30 days')");
        return $this->db->changes();
    }
}

// Execute
$processor = new TaskProcessor();

if ($isCli) {
    $action = $argv[1] ?? 'process';
    
    switch ($action) {
        case 'process':
            $result = $processor->processAll();
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            break;
            
        case 'stats':
            echo json_encode($processor->getStats(), JSON_PRETTY_PRINT) . "\n";
            break;
            
        case 'upcoming':
            echo json_encode($processor->getUpcoming(), JSON_PRETTY_PRINT) . "\n";
            break;
            
        case 'cleanup':
            $deleted = $processor->cleanup();
            echo "Deleted {$deleted} old tasks\n";
            break;
            
        default:
            echo "Usage: php task-processor.php [process|stats|upcoming|cleanup]\n";
    }
    
} elseif ($isWebTrigger) {
    header('Content-Type: application/json');
    
    $sub = $_GET['sub'] ?? 'process';
    
    switch ($sub) {
        case 'process':
            echo json_encode($processor->processAll());
            break;
            
        case 'stats':
            echo json_encode(['success' => true, 'stats' => $processor->getStats()]);
            break;
            
        case 'upcoming':
            echo json_encode(['success' => true, 'tasks' => $processor->getUpcoming()]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid sub-action']);
    }
}
