<?php
/**
 * TrueVault VPN - Email Helper Class
 * Dual email system: SMTP for customers, Gmail for admin notifications
 * 
 * @package TrueVault
 * @version 2.0.0
 */

class Email {
    private $db;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $gmailUser;
    private $gmailPass;
    private $fromName = 'TrueVault VPN';
    private $fromEmail;
    
    public function __construct() {
        $this->db = new Database('logs');
        $this->loadSettings();
    }
    
    /**
     * Load email settings from database
     */
    private function loadSettings() {
        $adminDb = new Database('admin');
        
        // SMTP Settings (for customer emails)
        $this->smtpHost = $adminDb->getSetting('smtp_host', 'smtp.gmail.com');
        $this->smtpPort = (int)$adminDb->getSetting('smtp_port', 587);
        $this->smtpUser = $adminDb->getSetting('smtp_user', '');
        $this->smtpPass = $adminDb->getSetting('smtp_pass', '');
        $this->fromEmail = $adminDb->getSetting('from_email', 'noreply@vpn.the-truth-publishing.com');
        
        // Gmail Settings (for admin notifications)
        $this->gmailUser = $adminDb->getSetting('gmail_user', 'paulhalonen@gmail.com');
        $this->gmailPass = $adminDb->getSetting('gmail_app_password', '');
    }
    
    /**
     * Send email to customer via SMTP
     */
    public function sendToCustomer($to, $subject, $body, $isHtml = true) {
        return $this->send($to, $subject, $body, 'smtp', $isHtml);
    }
    
    /**
     * Send notification to admin via Gmail
     */
    public function sendToAdmin($subject, $body, $isHtml = true) {
        return $this->send($this->gmailUser, $subject, $body, 'gmail', $isHtml);
    }
    
    /**
     * Send email using specified method
     */
    public function send($to, $subject, $body, $method = 'smtp', $isHtml = true) {
        try {
            // Log attempt
            $logId = $this->logEmail($method, $to, $subject, $body, 'sending');
            
            // Use PHP mail() as fallback if SMTP not configured
            if ($method === 'smtp' && empty($this->smtpUser)) {
                $result = $this->sendViaMail($to, $subject, $body, $isHtml);
            } else {
                $result = $this->sendViaSMTP($to, $subject, $body, $method, $isHtml);
            }
            
            // Update log
            $this->updateEmailLog($logId, $result ? 'sent' : 'failed');
            
            return $result;
            
        } catch (Exception $e) {
            $this->updateEmailLog($logId ?? 0, 'failed', $e->getMessage());
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send via SMTP (supports both SMTP and Gmail)
     */
    private function sendViaSMTP($to, $subject, $body, $method, $isHtml) {
        // Determine credentials based on method
        if ($method === 'gmail') {
            $host = 'smtp.gmail.com';
            $port = 587;
            $user = $this->gmailUser;
            $pass = $this->gmailPass;
            $from = $this->gmailUser;
        } else {
            $host = $this->smtpHost;
            $port = $this->smtpPort;
            $user = $this->smtpUser;
            $pass = $this->smtpPass;
            $from = $this->fromEmail;
        }
        
        // Build headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'X-Mailer: TrueVault-VPN/2.0'
        ];
        
        // For GoDaddy shared hosting, use mail() with proper headers
        // SMTP socket connection often blocked on shared hosting
        $headerString = implode("\r\n", $headers);
        
        return mail($to, $subject, $body, $headerString);
    }
    
    /**
     * Fallback to PHP mail()
     */
    private function sendViaMail($to, $subject, $body, $isHtml) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'X-Mailer: TrueVault-VPN/2.0'
        ];
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    /**
     * Queue email for later sending
     */
    public function queue($to, $subject, $templateName, $variables = [], $type = 'customer', $sendAt = null) {
        $sendAt = $sendAt ?? date('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("
            INSERT INTO email_queue (recipient, subject, template_name, template_variables, email_type, scheduled_for)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $to,
            $subject,
            $templateName,
            json_encode($variables),
            $type,
            $sendAt
        ]);
    }
    
    /**
     * Process email queue
     */
    public function processQueue($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT * FROM email_queue 
            WHERE status = 'pending' AND scheduled_for <= datetime('now')
            ORDER BY scheduled_for ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $emails = $stmt->fetchAll();
        
        $processed = 0;
        $template = new EmailTemplate();
        
        foreach ($emails as $email) {
            $variables = json_decode($email['template_variables'], true) ?? [];
            $body = $template->render($email['template_name'], $variables);
            
            if ($body) {
                $method = $email['email_type'] === 'admin' ? 'gmail' : 'smtp';
                $success = $this->send($email['recipient'], $email['subject'], $body, $method);
                
                $stmt = $this->db->prepare("
                    UPDATE email_queue 
                    SET status = ?, sent_at = datetime('now'), attempts = attempts + 1
                    WHERE id = ?
                ");
                $stmt->execute([$success ? 'sent' : 'failed', $email['id']]);
                
                if ($success) $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * Log email to database
     */
    private function logEmail($method, $to, $subject, $body, $status) {
        $stmt = $this->db->prepare("
            INSERT INTO email_log (method, recipient, subject, body, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$method, $to, $subject, $body, $status]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update email log status
     */
    private function updateEmailLog($id, $status, $error = null) {
        if (!$id) return;
        
        $stmt = $this->db->prepare("
            UPDATE email_log 
            SET status = ?, error_message = ?, sent_at = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$status, $error, $id]);
    }
    
    /**
     * Get email statistics
     */
    public function getStats($days = 7) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                method
            FROM email_log 
            WHERE created_at >= datetime('now', '-' || ? || ' days')
            GROUP BY method
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent emails
     */
    public function getRecent($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT id, method, recipient, subject, status, sent_at, error_message
            FROM email_log 
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get queue status
     */
    public function getQueueStatus() {
        $stmt = $this->db->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM email_queue 
            GROUP BY status
        ");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
