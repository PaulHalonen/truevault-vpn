<?php
/**
 * TrueVault VPN - Email Helper Class
 * Task 7.2 - Dual email system (SMTP for customers, Gmail for admin)
 * 
 * @created January 2026
 */

if (!defined('TRUEVAULT_INIT')) {
    die('Direct access not allowed');
}

class Email {
    
    // SMTP Configuration (for customer emails)
    private static $smtpHost = 'the-truth-publishing.com';
    private static $smtpPort = 465;
    private static $smtpUser = 'admin@the-truth-publishing.com';
    private static $smtpPass = "A'ndassiAthena8";
    private static $smtpSecure = 'ssl';
    
    // Gmail Configuration (for admin notifications)
    private static $gmailUser = 'paulhalonen@gmail.com';
    private static $gmailAppPass = 'ezdq mgqk mrcn xovx';
    
    // Default from address
    private static $fromEmail = 'noreply@vpn.the-truth-publishing.com';
    private static $fromName = 'TrueVault VPN';
    
    /**
     * Send email to customer via SMTP
     */
    public static function sendToCustomer($to, $subject, $body, $isHtml = true) {
        return self::send($to, $subject, $body, $isHtml, 'smtp');
    }
    
    /**
     * Send email to admin via Gmail
     */
    public static function sendToAdmin($subject, $body, $isHtml = true) {
        return self::send(self::$gmailUser, $subject, $body, $isHtml, 'gmail');
    }
    
    /**
     * Send email using specified method
     */
    public static function send($to, $subject, $body, $isHtml = true, $method = 'smtp') {
        try {
            // Log attempt
            self::logEmail($method, $to, $subject, $body, 'pending');
            
            if ($method === 'gmail') {
                $result = self::sendViaGmail($to, $subject, $body, $isHtml);
            } else {
                $result = self::sendViaSMTP($to, $subject, $body, $isHtml);
            }
            
            // Update log status
            self::updateEmailLog($to, $subject, $result ? 'sent' : 'failed');
            
            return $result;
            
        } catch (Exception $e) {
            self::updateEmailLog($to, $subject, 'failed', $e->getMessage());
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send via SMTP (customer emails)
     */
    private static function sendViaSMTP($to, $subject, $body, $isHtml) {
        // Load settings from database
        $settings = self::loadSettings();
        
        $host = $settings['smtp_host'] ?? self::$smtpHost;
        $port = $settings['smtp_port'] ?? self::$smtpPort;
        $user = $settings['smtp_user'] ?? self::$smtpUser;
        $pass = $settings['smtp_pass'] ?? self::$smtpPass;
        $from = $settings['email_from'] ?? self::$fromEmail;
        $fromName = $settings['email_from_name'] ?? self::$fromName;
        
        // Build headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = $isHtml 
            ? "Content-type: text/html; charset=UTF-8"
            : "Content-type: text/plain; charset=UTF-8";
        $headers[] = "From: {$fromName} <{$from}>";
        $headers[] = "Reply-To: {$from}";
        $headers[] = "X-Mailer: TrueVault-VPN/1.0";
        
        // Use PHP mail() with SMTP context
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        // For shared hosting, use mail() function
        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        
        return $result;
    }
    
    /**
     * Send via Gmail (admin notifications)
     */
    private static function sendViaGmail($to, $subject, $body, $isHtml) {
        // Gmail SMTP settings
        $host = 'ssl://smtp.gmail.com';
        $port = 465;
        $user = self::$gmailUser;
        $pass = str_replace(' ', '', self::$gmailAppPass);
        
        // Build headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = $isHtml 
            ? "Content-type: text/html; charset=UTF-8"
            : "Content-type: text/plain; charset=UTF-8";
        $headers[] = "From: TrueVault Admin <{$user}>";
        $headers[] = "Reply-To: {$user}";
        
        // For Gmail, we'll use direct SMTP socket connection
        $smtp = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$smtp) {
            throw new Exception("Gmail connection failed: $errstr");
        }
        
        // Read greeting
        self::smtpGetResponse($smtp);
        
        // EHLO
        fwrite($smtp, "EHLO localhost\r\n");
        self::smtpGetResponse($smtp);
        
        // AUTH LOGIN
        fwrite($smtp, "AUTH LOGIN\r\n");
        self::smtpGetResponse($smtp);
        
        fwrite($smtp, base64_encode($user) . "\r\n");
        self::smtpGetResponse($smtp);
        
        fwrite($smtp, base64_encode($pass) . "\r\n");
        self::smtpGetResponse($smtp);
        
        // MAIL FROM
        fwrite($smtp, "MAIL FROM:<{$user}>\r\n");
        self::smtpGetResponse($smtp);
        
        // RCPT TO
        fwrite($smtp, "RCPT TO:<{$to}>\r\n");
        self::smtpGetResponse($smtp);
        
        // DATA
        fwrite($smtp, "DATA\r\n");
        self::smtpGetResponse($smtp);
        
        // Message
        $message = implode("\r\n", $headers) . "\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "\r\n";
        $message .= $body;
        $message .= "\r\n.\r\n";
        
        fwrite($smtp, $message);
        self::smtpGetResponse($smtp);
        
        // QUIT
        fwrite($smtp, "QUIT\r\n");
        fclose($smtp);
        
        return true;
    }
    
    /**
     * Get SMTP response
     */
    private static function smtpGetResponse($smtp) {
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
    
    /**
     * Send using template
     */
    public static function sendTemplate($to, $templateName, $variables = [], $method = 'smtp') {
        $template = EmailTemplate::get($templateName);
        
        if (!$template) {
            error_log("Email template not found: {$templateName}");
            return false;
        }
        
        $subject = EmailTemplate::render($template['subject'], $variables);
        $body = EmailTemplate::render($template['body_html'], $variables);
        
        return self::send($to, $subject, $body, true, $method);
    }
    
    /**
     * Queue email for later sending
     */
    public static function queue($to, $templateName, $variables = [], $scheduledFor = null, $type = 'customer', $priority = 5) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $template = EmailTemplate::get($templateName);
            $subject = $template ? EmailTemplate::render($template['subject'], $variables) : $templateName;
            
            $stmt = $db->prepare("
                INSERT INTO email_queue (recipient, subject, template_name, template_variables, email_type, priority, scheduled_for)
                VALUES (:to, :subject, :template, :vars, :type, :priority, :scheduled)
            ");
            
            $stmt->bindValue(':to', $to, SQLITE3_TEXT);
            $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
            $stmt->bindValue(':template', $templateName, SQLITE3_TEXT);
            $stmt->bindValue(':vars', json_encode($variables), SQLITE3_TEXT);
            $stmt->bindValue(':type', $type, SQLITE3_TEXT);
            $stmt->bindValue(':priority', $priority, SQLITE3_INTEGER);
            $stmt->bindValue(':scheduled', $scheduledFor ?? date('Y-m-d H:i:s'), SQLITE3_TEXT);
            
            $stmt->execute();
            $id = $db->lastInsertRowID();
            $db->close();
            
            return $id;
            
        } catch (Exception $e) {
            error_log("Email queue error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process queued emails
     */
    public static function processQueue($limit = 10) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $now = date('Y-m-d H:i:s');
            
            $result = $db->query("
                SELECT * FROM email_queue 
                WHERE status = 'pending' AND scheduled_for <= '{$now}'
                ORDER BY priority ASC, scheduled_for ASC
                LIMIT {$limit}
            ");
            
            $processed = 0;
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $variables = json_decode($row['template_variables'], true) ?? [];
                $method = $row['email_type'] === 'admin' ? 'gmail' : 'smtp';
                
                $success = self::sendTemplate($row['recipient'], $row['template_name'], $variables, $method);
                
                $status = $success ? 'sent' : 'failed';
                $attempts = $row['attempts'] + 1;
                
                // Update queue status
                $updateStmt = $db->prepare("
                    UPDATE email_queue 
                    SET status = :status, attempts = :attempts, sent_at = :sent
                    WHERE id = :id
                ");
                $updateStmt->bindValue(':status', $status, SQLITE3_TEXT);
                $updateStmt->bindValue(':attempts', $attempts, SQLITE3_INTEGER);
                $updateStmt->bindValue(':sent', $success ? $now : null);
                $updateStmt->bindValue(':id', $row['id'], SQLITE3_INTEGER);
                $updateStmt->execute();
                
                $processed++;
            }
            
            $db->close();
            return $processed;
            
        } catch (Exception $e) {
            error_log("Queue processing error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Log email to database
     */
    private static function logEmail($method, $to, $subject, $body, $status, $error = null) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $stmt = $db->prepare("
                INSERT INTO email_log (method, recipient, subject, body, status, error_message)
                VALUES (:method, :to, :subject, :body, :status, :error)
            ");
            
            $stmt->bindValue(':method', $method, SQLITE3_TEXT);
            $stmt->bindValue(':to', $to, SQLITE3_TEXT);
            $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
            $stmt->bindValue(':body', substr($body, 0, 5000), SQLITE3_TEXT);
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->bindValue(':error', $error, SQLITE3_TEXT);
            
            $stmt->execute();
            $db->close();
            
        } catch (Exception $e) {
            error_log("Email log error: " . $e->getMessage());
        }
    }
    
    /**
     * Update email log status
     */
    private static function updateEmailLog($to, $subject, $status, $error = null) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $stmt = $db->prepare("
                UPDATE email_log 
                SET status = :status, error_message = :error, sent_at = CURRENT_TIMESTAMP
                WHERE recipient = :to AND subject = :subject AND status = 'pending'
                ORDER BY id DESC LIMIT 1
            ");
            
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->bindValue(':error', $error, SQLITE3_TEXT);
            $stmt->bindValue(':to', $to, SQLITE3_TEXT);
            $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
            
            $stmt->execute();
            $db->close();
            
        } catch (Exception $e) {
            error_log("Email log update error: " . $e->getMessage());
        }
    }
    
    /**
     * Load email settings from database
     */
    private static function loadSettings() {
        try {
            $db = new SQLite3(DB_ADMIN);
            $result = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'email_%'");
            
            $settings = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            $db->close();
            
            return $settings;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get email statistics
     */
    public static function getStats($days = 7) {
        try {
            $db = new SQLite3(DB_LOGS);
            
            $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            $stats = [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'pending' => 0,
                'queued' => 0
            ];
            
            // Email log stats
            $result = $db->query("SELECT status, COUNT(*) as count FROM email_log WHERE created_at >= '{$since}' GROUP BY status");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $stats[$row['status']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }
            
            // Queue stats
            $queueResult = $db->querySingle("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
            $stats['queued'] = (int)$queueResult;
            
            $db->close();
            return $stats;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
