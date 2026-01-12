<?php
/**
 * TrueVault VPN - Mailer Helper
 * Email sending and queue management
 */

require_once __DIR__ . '/../config/database.php';

class Mailer {
    private static $fromEmail = 'noreply@truthvault.com';
    private static $fromName = 'TrueVault VPN';
    
    /**
     * Send an email immediately
     */
    public static function send($to, $subject, $bodyHtml, $bodyText = null) {
        // Headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . self::$fromName . ' <' . self::$fromEmail . '>',
            'Reply-To: support@truthvault.com',
            'X-Mailer: TrueVault VPN'
        ];
        
        // Send mail
        $success = mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
        
        // Log the email
        self::logEmail($to, $subject, $success ? 'sent' : 'failed');
        
        return $success;
    }
    
    /**
     * Send email using a template
     */
    public static function sendTemplate($to, $templateSlug, $variables = [], $toName = null) {
        // Get template from database
        try {
            $db = DatabaseManager::getInstance()->templates();
            $stmt = $db->prepare("SELECT * FROM email_templates WHERE template_slug = ? AND is_active = 1");
            $stmt->execute([$templateSlug]);
            $template = $stmt->fetch();
            
            if (!$template) {
                throw new Exception("Email template not found: $templateSlug");
            }
            
            // Replace variables in subject and body
            $subject = self::replaceVariables($template['subject'], $variables);
            $bodyHtml = self::replaceVariables($template['body_html'], $variables);
            $bodyText = $template['body_text'] ? self::replaceVariables($template['body_text'], $variables) : null;
            
            // Wrap in HTML template
            $bodyHtml = self::wrapInHtmlTemplate($bodyHtml);
            
            return self::send($to, $subject, $bodyHtml, $bodyText);
        } catch (Exception $e) {
            self::logEmail($to, "Template: $templateSlug", 'failed', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add email to queue for later sending
     */
    public static function queue($to, $subject, $bodyHtml, $scheduledAt = null, $priority = 5) {
        try {
            $db = DatabaseManager::getInstance()->emails();
            $stmt = $db->prepare("
                INSERT INTO email_queue (to_email, subject, body_html, scheduled_at, priority, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$to, $subject, $bodyHtml, $scheduledAt, $priority]);
            
            return $db->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Add template email to queue
     */
    public static function queueTemplate($to, $templateSlug, $variables = [], $scheduledAt = null, $priority = 5) {
        try {
            $db = DatabaseManager::getInstance()->emails();
            $templatesDb = DatabaseManager::getInstance()->templates();
            
            // Get template
            $stmt = $templatesDb->prepare("SELECT * FROM email_templates WHERE template_slug = ?");
            $stmt->execute([$templateSlug]);
            $template = $stmt->fetch();
            
            if (!$template) {
                return false;
            }
            
            $stmt = $db->prepare("
                INSERT INTO email_queue (to_email, subject, body_html, template_id, template_variables, scheduled_at, priority, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $subject = self::replaceVariables($template['subject'], $variables);
            $bodyHtml = self::wrapInHtmlTemplate(self::replaceVariables($template['body_html'], $variables));
            
            $stmt->execute([
                $to, 
                $subject, 
                $bodyHtml, 
                $template['id'], 
                json_encode($variables), 
                $scheduledAt, 
                $priority
            ]);
            
            return $db->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Process email queue
     */
    public static function processQueue($limit = 50) {
        try {
            $db = DatabaseManager::getInstance()->emails();
            
            // Get pending emails
            $stmt = $db->prepare("
                SELECT * FROM email_queue 
                WHERE status = 'pending' 
                AND (scheduled_at IS NULL OR scheduled_at <= datetime('now'))
                AND attempts < max_attempts
                ORDER BY priority ASC, created_at ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $emails = $stmt->fetchAll();
            
            $sent = 0;
            $failed = 0;
            
            foreach ($emails as $email) {
                // Update status to sending
                $updateStmt = $db->prepare("UPDATE email_queue SET status = 'sending', attempts = attempts + 1 WHERE id = ?");
                $updateStmt->execute([$email['id']]);
                
                // Send email
                $success = self::send($email['to_email'], $email['subject'], $email['body_html']);
                
                if ($success) {
                    $updateStmt = $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = datetime('now') WHERE id = ?");
                    $updateStmt->execute([$email['id']]);
                    $sent++;
                } else {
                    $status = $email['attempts'] + 1 >= $email['max_attempts'] ? 'failed' : 'pending';
                    $updateStmt = $db->prepare("UPDATE email_queue SET status = ?, error_message = 'Send failed' WHERE id = ?");
                    $updateStmt->execute([$status, $email['id']]);
                    $failed++;
                }
            }
            
            return ['sent' => $sent, 'failed' => $failed];
        } catch (Exception $e) {
            return ['sent' => 0, 'failed' => 0, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Replace template variables
     */
    private static function replaceVariables($text, $variables) {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
            $text = str_replace('{{ ' . $key . ' }}', $value, $text);
        }
        return $text;
    }
    
    /**
     * Wrap content in HTML email template
     */
    private static function wrapInHtmlTemplate($content) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueVault VPN</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f0f1a; color: #ffffff; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.04); border-radius: 14px; padding: 30px; }
        h1 { color: #00d9ff; }
        a { color: #00ff88; }
        .button { display: inline-block; padding: 12px 24px; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        ' . $content . '
        <div class="footer">
            <p>TrueVault VPN - Your Complete Digital Fortress</p>
            <p>© ' . date('Y') . ' TrueVault. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Log email to database
     */
    private static function logEmail($to, $subject, $status, $error = null) {
        try {
            $db = DatabaseManager::getInstance()->emails();
            $stmt = $db->prepare("
                INSERT INTO email_history (to_email, subject, status, sent_at)
                VALUES (?, ?, ?, datetime('now'))
            ");
            $stmt->execute([$to, $subject, $status]);
        } catch (Exception $e) {
            // Silently fail logging
        }
    }
}
