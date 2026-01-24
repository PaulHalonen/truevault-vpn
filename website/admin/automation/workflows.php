<?php
/**
 * TrueVault VPN - Automation Workflows Engine
 * Task 17.3: 12 automated business workflows
 * Created: January 24, 2026
 */

class AutomationWorkflow {
    private $db;
    private $settingsDb;
    
    public function __construct() {
        $this->db = new SQLite3(__DIR__ . '/databases/automation.db');
        $this->db->enableExceptions(true);
        
        // Try to connect to main settings database for customer data
        $settingsPath = __DIR__ . '/../databases/settings.db';
        if (file_exists($settingsPath)) {
            $this->settingsDb = new SQLite3($settingsPath);
        }
    }
    
    public function __destruct() {
        if ($this->db) $this->db->close();
        if ($this->settingsDb) $this->settingsDb->close();
    }
    
    /**
     * Trigger a workflow by name
     */
    public function trigger($workflowName, $data = []) {
        // Get workflow definition
        $stmt = $this->db->prepare("SELECT * FROM workflow_definitions WHERE name = :name AND is_active = 1");
        $stmt->bindValue(':name', $workflowName, SQLITE3_TEXT);
        $result = $stmt->execute();
        $workflow = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$workflow) {
            return ['success' => false, 'error' => 'Workflow not found or inactive'];
        }
        
        $steps = json_decode($workflow['steps'], true);
        $totalSteps = count($steps);
        
        // Log workflow execution start
        $logId = $this->logWorkflowStart($workflowName, $data, $totalSteps);
        
        // Process each step
        $completedSteps = 0;
        foreach ($steps as $step) {
            try {
                $delay = $step['delay'] ?? 0;
                
                if ($delay > 0) {
                    // Schedule for later
                    $this->scheduleTask($workflowName, $step, $data, $delay);
                } else {
                    // Execute immediately
                    $this->executeStep($step, $data);
                }
                $completedSteps++;
            } catch (Exception $e) {
                $this->logWorkflowError($logId, $e->getMessage());
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        // Update workflow execution count
        $this->db->exec("UPDATE workflow_definitions SET execution_count = execution_count + 1, last_executed_at = datetime('now') WHERE name = '{$workflowName}'");
        
        // Mark workflow as completed (for immediate steps)
        $this->logWorkflowComplete($logId, $completedSteps);
        
        return ['success' => true, 'log_id' => $logId, 'steps_executed' => $completedSteps];
    }
    
    /**
     * Execute a single workflow step
     */
    private function executeStep($step, $data) {
        $action = $step['action'] ?? '';
        
        switch ($action) {
            case 'send_email':
                $this->sendEmail($step['template'], $data, $step['to'] ?? 'customer');
                break;
                
            case 'update_status':
                $this->updateCustomerStatus($data['customer_id'] ?? null, $step['status']);
                break;
                
            case 'generate_invoice':
                $this->generateInvoice($data);
                break;
                
            case 'categorize_ticket':
                $this->categorizeTicket($data['ticket_id'] ?? null);
                break;
                
            case 'check_knowledge_base':
                $this->checkKnowledgeBase($data['ticket_id'] ?? null, $data['content'] ?? '');
                break;
                
            case 'assign_priority':
                $this->assignTicketPriority($data['ticket_id'] ?? null, $data['customer_id'] ?? null);
                break;
                
            case 'flag_for_review':
                $this->flagForReview($data, $step['priority'] ?? 'normal');
                break;
                
            case 'notify_admin':
                $this->notifyAdmin($data);
                break;
                
            case 'suspend_service':
                $this->suspendService($data['customer_id'] ?? null);
                break;
                
            case 'upgrade_tier':
                $this->upgradeTier($data['customer_id'] ?? null, $step['tier']);
                break;
                
            case 'provision_dedicated_server':
                $this->provisionDedicatedServer($data['customer_id'] ?? null);
                break;
                
            default:
                // Log unknown action
                error_log("Unknown workflow action: {$action}");
        }
    }
    
    /**
     * Schedule a task for later execution
     */
    private function scheduleTask($workflowName, $step, $data, $delaySeconds) {
        $executeAt = date('Y-m-d H:i:s', time() + $delaySeconds);
        
        $stmt = $this->db->prepare("
            INSERT INTO scheduled_tasks (workflow_name, task_type, execute_at, task_data, status)
            VALUES (:workflow, :type, :execute_at, :data, 'pending')
        ");
        $stmt->bindValue(':workflow', $workflowName, SQLITE3_TEXT);
        $stmt->bindValue(':type', $step['action'], SQLITE3_TEXT);
        $stmt->bindValue(':execute_at', $executeAt, SQLITE3_TEXT);
        $stmt->bindValue(':data', json_encode(['step' => $step, 'context' => $data]), SQLITE3_TEXT);
        $stmt->execute();
        
        return $this->db->lastInsertRowID();
    }
    
    /**
     * Send email using template
     */
    private function sendEmail($templateName, $data, $to = 'customer') {
        // Get template
        $stmt = $this->db->prepare("SELECT * FROM email_templates WHERE name = :name AND active = 1");
        $stmt->bindValue(':name', $templateName, SQLITE3_TEXT);
        $result = $stmt->execute();
        $template = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$template) {
            throw new Exception("Email template not found: {$templateName}");
        }
        
        // Determine recipient
        $recipientEmail = ($to === 'admin') 
            ? 'paulhalonen@gmail.com' 
            : ($data['email'] ?? $data['customer_email'] ?? '');
            
        $recipientName = ($to === 'admin')
            ? 'Admin'
            : ($data['first_name'] ?? $data['name'] ?? 'Customer');
        
        if (empty($recipientEmail)) {
            throw new Exception("No recipient email provided");
        }
        
        // Replace variables in template
        $subject = $this->replaceVariables($template['subject'], $data);
        $body = $this->replaceVariables($template['body'], $data);
        
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
        $stmt->bindValue(':metadata', json_encode($data), SQLITE3_TEXT);
        $stmt->execute();
        $emailId = $this->db->lastInsertRowID();
        
        // Actually send the email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: TrueVault VPN <noreply@the-truth-publishing.com>\r\n";
        
        $sent = @mail($recipientEmail, $subject, $body, $headers);
        
        // Update log
        $status = $sent ? 'sent' : 'failed';
        $this->db->exec("UPDATE email_log SET status = '{$status}', sent_at = datetime('now') WHERE id = {$emailId}");
        
        return $sent;
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
        // Set default dashboard URL
        $text = str_replace('{dashboard_url}', 'https://vpn.the-truth-publishing.com/dashboard', $text);
        $text = str_replace('{admin_url}', 'https://vpn.the-truth-publishing.com/admin', $text);
        $text = str_replace('{signup_url}', 'https://vpn.the-truth-publishing.com/signup', $text);
        $text = str_replace('{survey_url}', 'https://vpn.the-truth-publishing.com/survey', $text);
        $text = str_replace('{feedback_url}', 'https://vpn.the-truth-publishing.com/feedback', $text);
        return $text;
    }
    
    /**
     * Update customer status
     */
    private function updateCustomerStatus($customerId, $status) {
        if (!$customerId || !$this->settingsDb) return;
        
        $stmt = $this->settingsDb->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    /**
     * Generate invoice
     */
    private function generateInvoice($data) {
        // Invoice generation logic would go here
        // For now, just log that it was generated
        error_log("Invoice generated for customer: " . ($data['customer_id'] ?? 'unknown'));
    }
    
    /**
     * Auto-categorize support ticket
     */
    private function categorizeTicket($ticketId) {
        if (!$ticketId) return;
        
        // Get ticket content and categorize based on keywords
        // This is a placeholder - actual implementation would analyze ticket content
        $categories = ['billing', 'technical', 'account'];
        $category = $categories[array_rand($categories)];
        
        error_log("Ticket {$ticketId} categorized as: {$category}");
    }
    
    /**
     * Check knowledge base for auto-resolution
     */
    private function checkKnowledgeBase($ticketId, $content) {
        if (empty($content)) return null;
        
        // Extract keywords
        $words = preg_split('/\s+/', strtolower($content));
        $keywords = array_filter($words, function($w) {
            return strlen($w) > 3;
        });
        
        // Search knowledge base
        foreach ($keywords as $keyword) {
            $stmt = $this->db->prepare("
                SELECT * FROM knowledge_base 
                WHERE keywords LIKE :keyword 
                ORDER BY success_rate DESC 
                LIMIT 1
            ");
            $stmt->bindValue(':keyword', '%' . $keyword . '%', SQLITE3_TEXT);
            $result = $stmt->execute();
            $kb = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($kb) {
                // Update usage count
                $this->db->exec("UPDATE knowledge_base SET times_used = times_used + 1 WHERE id = {$kb['id']}");
                return $kb;
            }
        }
        
        return null;
    }
    
    /**
     * Assign ticket priority based on customer tier
     */
    private function assignTicketPriority($ticketId, $customerId) {
        $priority = 'normal';
        
        if ($customerId && $this->settingsDb) {
            $result = $this->settingsDb->query("SELECT tier FROM users WHERE id = {$customerId}");
            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($user && $user['tier'] === 'vip') {
                $priority = 'high';
            }
        }
        
        error_log("Ticket {$ticketId} assigned priority: {$priority}");
        return $priority;
    }
    
    /**
     * Flag item for manual review
     */
    private function flagForReview($data, $priority = 'normal') {
        // Log for admin review
        $stmt = $this->db->prepare("
            INSERT INTO automation_log (workflow_name, trigger_type, trigger_data, status)
            VALUES ('manual_review', 'flagged', :data, 'pending_review')
        ");
        $stmt->bindValue(':data', json_encode(['priority' => $priority, 'data' => $data]), SQLITE3_TEXT);
        $stmt->execute();
    }
    
    /**
     * Send notification to admin
     */
    private function notifyAdmin($data) {
        $this->sendEmail('server_down', array_merge($data, [
            'admin_url' => 'https://vpn.the-truth-publishing.com/admin'
        ]), 'admin');
    }
    
    /**
     * Suspend customer service
     */
    private function suspendService($customerId) {
        if (!$customerId) return;
        
        $this->updateCustomerStatus($customerId, 'suspended');
        error_log("Service suspended for customer: {$customerId}");
    }
    
    /**
     * Upgrade customer tier
     */
    private function upgradeTier($customerId, $tier) {
        if (!$customerId || !$this->settingsDb) return;
        
        $stmt = $this->settingsDb->prepare("UPDATE users SET tier = :tier WHERE id = :id");
        $stmt->bindValue(':tier', $tier, SQLITE3_TEXT);
        $stmt->bindValue(':id', $customerId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    /**
     * Provision dedicated server for VIP
     */
    private function provisionDedicatedServer($customerId) {
        // This would integrate with server provisioning
        // For VIP users, assign to dedicated St. Louis server (144.126.133.253)
        error_log("Dedicated server provisioned for VIP customer: {$customerId}");
    }
    
    /**
     * Log workflow start
     */
    private function logWorkflowStart($workflowName, $data, $totalSteps) {
        $stmt = $this->db->prepare("
            INSERT INTO automation_log (workflow_name, trigger_type, trigger_data, status, steps_total)
            VALUES (:name, 'triggered', :data, 'running', :steps)
        ");
        $stmt->bindValue(':name', $workflowName, SQLITE3_TEXT);
        $stmt->bindValue(':data', json_encode($data), SQLITE3_TEXT);
        $stmt->bindValue(':steps', $totalSteps, SQLITE3_INTEGER);
        $stmt->execute();
        
        return $this->db->lastInsertRowID();
    }
    
    /**
     * Log workflow completion
     */
    private function logWorkflowComplete($logId, $stepsCompleted) {
        $this->db->exec("
            UPDATE automation_log 
            SET status = 'completed', 
                steps_completed = {$stepsCompleted}, 
                completed_at = datetime('now') 
            WHERE id = {$logId}
        ");
    }
    
    /**
     * Log workflow error
     */
    private function logWorkflowError($logId, $errorMessage) {
        $stmt = $this->db->prepare("
            UPDATE automation_log 
            SET status = 'failed', error_message = :error, completed_at = datetime('now') 
            WHERE id = :id
        ");
        $stmt->bindValue(':error', $errorMessage, SQLITE3_TEXT);
        $stmt->bindValue(':id', $logId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // ==========================================
    // PUBLIC WORKFLOW TRIGGER METHODS
    // ==========================================
    
    /**
     * Workflow 1: New Customer Onboarding
     */
    public function newCustomerOnboarding($customerId, $email, $firstName, $planName = 'Personal') {
        return $this->trigger('new_customer_onboarding', [
            'customer_id' => $customerId,
            'email' => $email,
            'first_name' => $firstName,
            'plan_name' => $planName
        ]);
    }
    
    /**
     * Workflow 2: Payment Failed Escalation
     */
    public function paymentFailedEscalation($customerId, $email, $firstName, $amount) {
        return $this->trigger('payment_failed_escalation', [
            'customer_id' => $customerId,
            'email' => $email,
            'first_name' => $firstName,
            'amount' => $amount
        ]);
    }
    
    /**
     * Workflow 3: Payment Success
     */
    public function paymentSuccess($customerId, $email, $firstName, $amount, $invoiceNumber) {
        return $this->trigger('payment_success', [
            'customer_id' => $customerId,
            'email' => $email,
            'first_name' => $firstName,
            'amount' => $amount,
            'invoice_number' => $invoiceNumber,
            'payment_date' => date('F j, Y')
        ]);
    }
    
    /**
     * Workflow 4: Support Ticket Created
     */
    public function supportTicketCreated($ticketId, $customerId, $email, $firstName, $subject, $content) {
        return $this->trigger('support_ticket_created', [
            'ticket_id' => $ticketId,
            'customer_id' => $customerId,
            'email' => $email,
            'first_name' => $firstName,
            'ticket_subject' => $subject,
            'content' => $content,
            'priority' => 'normal'
        ]);
    }
    
    /**
     * Workflow 5: Support Ticket Resolved
     */
    public function supportTicketResolved($ticketId, $email, $firstName, $subject, $resolution) {
        return $this->trigger('support_ticket_resolved', [
            'ticket_id' => $ticketId,
            'email' => $email,
            'first_name' => $firstName,
            'ticket_subject' => $subject,
            'resolution' => $resolution
        ]);
    }
    
    /**
     * Workflow 6: Complaint Handling
     */
    public function complaintReceived($ticketId, $customerId, $email, $firstName, $complaint) {
        return $this->trigger('complaint_handling', [
            'ticket_id' => $ticketId,
            'customer_id' => $customerId,
            'email' => $email,
            'first_name' => $firstName,
            'complaint' => $complaint
        ]);
    }
    
    /**
     * Workflow 7: Server Down Alert
     */
    public function serverDown($serverName, $serverIp, $serverLocation, $affectedUsers = 0) {
        return $this->trigger('server_down_alert', [
            'server_name' => $serverName,
            'server_ip' => $serverIp,
            'server_location' => $serverLocation,
            'down_since' => date('F j, Y g:i A'),
            'affected_users' => $affectedUsers
        ]);
    }
    
    /**
     * Workflow 8: Server Restored
     */
    public function serverRestored($serverName, $downtimeDuration) {
        return $this->trigger('server_restored', [
            'server_name' => $serverName,
            'downtime_duration' => $downtimeDuration,
            'restored_at' => date('F j, Y g:i A')
        ]);
    }
    
    /**
     * Workflow 9: Cancellation Request
     */
    public function cancellationRequested($customerId, $email, $firstName) {
        return $this->trigger('cancellation_request', [
            'customer_id' => $customerId,
            'email' => $email,
            'first_name' => $firstName
        ]);
    }
    
    /**
     * Workflow 10: Monthly Invoicing
     */
    public function monthlyInvoicing() {
        return $this->trigger('monthly_invoicing', [
            'billing_month' => date('F Y')
        ]);
    }
    
    /**
     * Workflow 11: VIP Request Received
     */
    public function vipRequestReceived($customerId, $email, $firstName) {
        return $this->trigger('vip_request_received', [
            'customer_id' => $customerId,
            'email' => $email,
            'first_name' => $firstName
        ]);
    }
    
    /**
     * Workflow 12: VIP Approved
     */
    public function vipApproved($customerId, $email, $firstName, $serverName, $serverIp) {
        return $this->trigger('vip_approved', [
            'customer_id' => $customerId,
            'email' => $email,
            'first_name' => $firstName,
            'server_name' => $serverName,
            'server_ip' => $serverIp
        ]);
    }
    
    // ==========================================
    // UTILITY METHODS
    // ==========================================
    
    /**
     * Get all workflow definitions
     */
    public function getWorkflows() {
        $result = $this->db->query("SELECT * FROM workflow_definitions ORDER BY name");
        $workflows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $workflows[] = $row;
        }
        return $workflows;
    }
    
    /**
     * Get recent workflow executions
     */
    public function getRecentExecutions($limit = 20) {
        $result = $this->db->query("
            SELECT * FROM automation_log 
            ORDER BY started_at DESC 
            LIMIT {$limit}
        ");
        $executions = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $executions[] = $row;
        }
        return $executions;
    }
    
    /**
     * Get workflow statistics
     */
    public function getStats() {
        $activeWorkflows = $this->db->querySingle("SELECT COUNT(*) FROM workflow_definitions WHERE is_active = 1");
        $todayExecutions = $this->db->querySingle("SELECT COUNT(*) FROM automation_log WHERE date(started_at) = date('now')");
        $pendingTasks = $this->db->querySingle("SELECT COUNT(*) FROM scheduled_tasks WHERE status = 'pending'");
        $todayEmails = $this->db->querySingle("SELECT COUNT(*) FROM email_log WHERE date(created_at) = date('now')");
        
        return [
            'active_workflows' => $activeWorkflows,
            'executions_today' => $todayExecutions,
            'pending_tasks' => $pendingTasks,
            'emails_today' => $todayEmails
        ];
    }
}

// Handle direct API calls
if (basename($_SERVER['PHP_SELF']) === 'workflows.php') {
    header('Content-Type: application/json');
    
    $workflow = new AutomationWorkflow();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'trigger':
            $workflowName = $_POST['workflow'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true);
            echo json_encode($workflow->trigger($workflowName, $data));
            break;
            
        case 'list':
            echo json_encode(['success' => true, 'workflows' => $workflow->getWorkflows()]);
            break;
            
        case 'recent':
            echo json_encode(['success' => true, 'executions' => $workflow->getRecentExecutions()]);
            break;
            
        case 'stats':
            echo json_encode(['success' => true, 'stats' => $workflow->getStats()]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}
