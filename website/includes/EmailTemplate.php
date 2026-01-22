<?php
/**
 * TrueVault VPN - Email Template Helper Class
 * Task 7.2 - Template rendering with variable replacement
 * 
 * @created January 2026
 */

if (!defined('TRUEVAULT_INIT')) {
    die('Direct access not allowed');
}

class EmailTemplate {
    
    // Default variables available in all templates
    private static $globalVars = [
        'site_name' => 'TrueVault VPN',
        'site_url' => 'https://vpn.the-truth-publishing.com',
        'support_email' => 'admin@the-truth-publishing.com',
        'current_year' => null,
        'dashboard_url' => 'https://vpn.the-truth-publishing.com/dashboard'
    ];
    
    /**
     * Get template by name
     */
    public static function get($templateName) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $stmt = $db->prepare("SELECT * FROM email_templates WHERE template_name = :name AND is_active = 1");
            $stmt->bindValue(':name', $templateName, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            $template = $result->fetchArray(SQLITE3_ASSOC);
            $db->close();
            
            return $template ?: null;
            
        } catch (Exception $e) {
            error_log("Template fetch error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all templates
     */
    public static function getAll($category = null) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            $sql = "SELECT * FROM email_templates WHERE is_active = 1";
            if ($category) {
                $sql .= " AND category = '{$category}'";
            }
            $sql .= " ORDER BY category, display_name";
            
            $result = $db->query($sql);
            
            $templates = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $templates[] = $row;
            }
            $db->close();
            
            return $templates;
            
        } catch (Exception $e) {
            error_log("Templates fetch error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Render template with variables
     */
    public static function render($content, $variables = []) {
        // Merge global variables
        self::$globalVars['current_year'] = date('Y');
        $allVars = array_merge(self::$globalVars, $variables);
        
        // Replace {variable} placeholders
        foreach ($allVars as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace('{' . $key . '}', $value, $content);
            }
        }
        
        // Handle conditional blocks: {if:variable}content{/if:variable}
        $content = preg_replace_callback(
            '/\{if:(\w+)\}(.*?)\{\/if:\1\}/s',
            function($matches) use ($allVars) {
                $varName = $matches[1];
                $innerContent = $matches[2];
                return !empty($allVars[$varName]) ? $innerContent : '';
            },
            $content
        );
        
        // Handle else blocks: {else:variable}content{/else:variable}
        $content = preg_replace_callback(
            '/\{else:(\w+)\}(.*?)\{\/else:\1\}/s',
            function($matches) use ($allVars) {
                $varName = $matches[1];
                $innerContent = $matches[2];
                return empty($allVars[$varName]) ? $innerContent : '';
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Create or update template
     */
    public static function save($templateName, $data) {
        try {
            $db = new SQLite3(DB_LOGS);
            $db->enableExceptions(true);
            
            // Check if exists
            $existing = self::get($templateName);
            
            if ($existing) {
                // Update
                $stmt = $db->prepare("
                    UPDATE email_templates SET
                        display_name = :display,
                        subject = :subject,
                        body_html = :html,
                        body_text = :text,
                        category = :category,
                        variables = :vars,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE template_name = :name
                ");
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO email_templates (template_name, display_name, subject, body_html, body_text, category, variables)
                    VALUES (:name, :display, :subject, :html, :text, :category, :vars)
                ");
            }
            
            $stmt->bindValue(':name', $templateName, SQLITE3_TEXT);
            $stmt->bindValue(':display', $data['display_name'] ?? $templateName, SQLITE3_TEXT);
            $stmt->bindValue(':subject', $data['subject'], SQLITE3_TEXT);
            $stmt->bindValue(':html', $data['body_html'], SQLITE3_TEXT);
            $stmt->bindValue(':text', $data['body_text'] ?? strip_tags($data['body_html']), SQLITE3_TEXT);
            $stmt->bindValue(':category', $data['category'] ?? 'general', SQLITE3_TEXT);
            $stmt->bindValue(':vars', json_encode($data['variables'] ?? []), SQLITE3_TEXT);
            
            $stmt->execute();
            $db->close();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Template save error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete template
     */
    public static function delete($templateName) {
        try {
            $db = new SQLite3(DB_LOGS);
            $stmt = $db->prepare("DELETE FROM email_templates WHERE template_name = :name");
            $stmt->bindValue(':name', $templateName, SQLITE3_TEXT);
            $stmt->execute();
            $db->close();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Preview template with sample data
     */
    public static function preview($templateName, $sampleData = []) {
        $template = self::get($templateName);
        if (!$template) {
            return null;
        }
        
        // Default sample data
        $defaults = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'plan_name' => 'Pro',
            'amount' => '14.99',
            'invoice_number' => 'INV-2026-0001',
            'ticket_id' => 'TKT-12345',
            'ticket_subject' => 'Sample Support Ticket',
            'server_name' => 'New York Shared'
        ];
        
        $variables = array_merge($defaults, $sampleData);
        
        return [
            'subject' => self::render($template['subject'], $variables),
            'body' => self::render($template['body_html'], $variables)
        ];
    }
    
    /**
     * Get base HTML wrapper
     */
    public static function getWrapper() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{subject}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { color: #00d9ff; margin: 0; font-size: 24px; }
        .header .tagline { color: #888; font-size: 14px; margin-top: 5px; }
        .content { background: #fff; padding: 30px; border-radius: 0 0 10px 10px; }
        .content h2 { color: #1a1a2e; margin-top: 0; }
        .btn { display: inline-block; padding: 12px 30px; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #1a1a2e; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
        .btn:hover { opacity: 0.9; }
        .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; }
        .footer a { color: #00d9ff; }
        .highlight { background: #f8f9fa; padding: 15px; border-left: 4px solid #00d9ff; margin: 20px 0; }
        .info-box { background: #e8f4fd; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .success-box { background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ TrueVault VPN</h1>
            <div class="tagline">Your Complete Digital Fortress</div>
        </div>
        <div class="content">
            {content}
        </div>
        <div class="footer">
            <p>© {current_year} TrueVault VPN. All rights reserved.</p>
            <p>
                <a href="{site_url}">Visit Website</a> | 
                <a href="{dashboard_url}">Dashboard</a> | 
                <a href="mailto:{support_email}">Support</a>
            </p>
            <p style="color: #aaa; font-size: 10px;">
                This email was sent to {email}. If you didn\'t request this, please ignore.
            </p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Wrap content in base template
     */
    public static function wrap($content, $subject = '') {
        $wrapper = self::getWrapper();
        $wrapper = str_replace('{content}', $content, $wrapper);
        $wrapper = str_replace('{subject}', $subject, $wrapper);
        return $wrapper;
    }
    
    /**
     * Get available template variables documentation
     */
    public static function getVariablesDocs() {
        return [
            'User Variables' => [
                '{first_name}' => 'User first name',
                '{last_name}' => 'User last name',
                '{email}' => 'User email address',
                '{user_id}' => 'User ID'
            ],
            'Subscription Variables' => [
                '{plan_name}' => 'Subscription plan name',
                '{amount}' => 'Payment amount',
                '{currency}' => 'Currency code',
                '{next_billing_date}' => 'Next billing date',
                '{expiry_date}' => 'Expiration date'
            ],
            'Invoice Variables' => [
                '{invoice_number}' => 'Invoice number',
                '{invoice_date}' => 'Invoice date',
                '{due_date}' => 'Payment due date',
                '{invoice_url}' => 'Link to view invoice'
            ],
            'Support Variables' => [
                '{ticket_id}' => 'Support ticket ID',
                '{ticket_subject}' => 'Ticket subject',
                '{ticket_status}' => 'Ticket status',
                '{ticket_url}' => 'Link to view ticket'
            ],
            'Server Variables' => [
                '{server_name}' => 'VPN server name',
                '{server_location}' => 'Server location',
                '{server_status}' => 'Server status'
            ],
            'Global Variables' => [
                '{site_name}' => 'Website name',
                '{site_url}' => 'Website URL',
                '{dashboard_url}' => 'Dashboard URL',
                '{support_email}' => 'Support email',
                '{current_year}' => 'Current year'
            ]
        ];
    }
}
