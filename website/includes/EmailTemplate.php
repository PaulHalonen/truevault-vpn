<?php
/**
 * TrueVault VPN - Email Template Engine
 * Renders email templates with variable substitution
 * 
 * @package TrueVault
 * @version 2.0.0
 */

class EmailTemplate {
    private $db;
    private $templates = [];
    
    public function __construct() {
        $this->db = new Database('admin');
        $this->ensureTablesExist();
    }
    
    /**
     * Ensure email_templates table exists
     */
    private function ensureTablesExist() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS email_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                subject TEXT NOT NULL,
                body_html TEXT NOT NULL,
                body_text TEXT,
                category TEXT DEFAULT 'general',
                variables TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    /**
     * Render a template with variables
     */
    public function render($templateName, $variables = []) {
        $template = $this->getTemplate($templateName);
        
        if (!$template) {
            error_log("Email template not found: $templateName");
            return null;
        }
        
        // Add default variables
        $variables = array_merge([
            'company_name' => 'TrueVault VPN',
            'support_email' => 'support@vpn.the-truth-publishing.com',
            'dashboard_url' => 'https://vpn.the-truth-publishing.com/dashboard/',
            'login_url' => 'https://vpn.the-truth-publishing.com/login.html',
            'year' => date('Y'),
            'date' => date('F j, Y'),
            'time' => date('g:i A')
        ], $variables);
        
        // Replace variables in template
        $body = $template['body_html'];
        
        foreach ($variables as $key => $value) {
            $body = str_replace('{' . $key . '}', htmlspecialchars($value), $body);
            $body = str_replace('{{' . $key . '}}', htmlspecialchars($value), $body);
        }
        
        // Wrap in email wrapper
        return $this->wrapInEmailLayout($body, $template['subject']);
    }
    
    /**
     * Get template by name
     */
    public function getTemplate($name) {
        // Check cache first
        if (isset($this->templates[$name])) {
            return $this->templates[$name];
        }
        
        $stmt = $this->db->prepare("
            SELECT * FROM email_templates 
            WHERE name = ? AND is_active = 1
        ");
        $stmt->execute([$name]);
        $template = $stmt->fetch();
        
        if ($template) {
            $this->templates[$name] = $template;
        }
        
        return $template;
    }
    
    /**
     * Get rendered subject line
     */
    public function getSubject($templateName, $variables = []) {
        $template = $this->getTemplate($templateName);
        
        if (!$template) {
            return 'TrueVault VPN';
        }
        
        $subject = $template['subject'];
        
        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
        }
        
        return $subject;
    }
    
    /**
     * Wrap content in email layout
     */
    private function wrapInEmailLayout($content, $subject = '') {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($subject) . '</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            color: #00d9ff;
            margin: 0;
            font-size: 24px;
        }
        .email-body {
            padding: 30px;
        }
        .email-footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn:hover {
            opacity: 0.9;
        }
        h2 { color: #1a1a2e; }
        a { color: #00d9ff; }
        .highlight {
            background: #f0f9ff;
            border-left: 4px solid #00d9ff;
            padding: 15px;
            margin: 15px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🔒 TrueVault VPN</h1>
        </div>
        <div class="email-body">
            ' . $content . '
        </div>
        <div class="email-footer">
            <p>&copy; ' . date('Y') . ' TrueVault VPN. All rights reserved.</p>
            <p>
                <a href="https://vpn.the-truth-publishing.com">Website</a> | 
                <a href="https://vpn.the-truth-publishing.com/dashboard/">Dashboard</a> | 
                <a href="mailto:support@vpn.the-truth-publishing.com">Support</a>
            </p>
            <p style="font-size: 11px; color: #999;">
                You received this email because you have an account with TrueVault VPN.
            </p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Save or update a template
     */
    public function saveTemplate($name, $subject, $bodyHtml, $category = 'general', $variables = []) {
        $existing = $this->getTemplate($name);
        
        if ($existing) {
            $stmt = $this->db->prepare("
                UPDATE email_templates 
                SET subject = ?, body_html = ?, category = ?, variables = ?, updated_at = datetime('now')
                WHERE name = ?
            ");
            return $stmt->execute([$subject, $bodyHtml, $category, json_encode($variables), $name]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO email_templates (name, subject, body_html, category, variables)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$name, $subject, $bodyHtml, $category, json_encode($variables)]);
        }
    }
    
    /**
     * Get all templates
     */
    public function getAllTemplates() {
        $stmt = $this->db->query("
            SELECT id, name, subject, category, is_active, updated_at
            FROM email_templates 
            ORDER BY category, name
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Delete a template
     */
    public function deleteTemplate($name) {
        $stmt = $this->db->prepare("DELETE FROM email_templates WHERE name = ?");
        return $stmt->execute([$name]);
    }
    
    /**
     * Toggle template active status
     */
    public function toggleTemplate($name, $active = true) {
        $stmt = $this->db->prepare("
            UPDATE email_templates SET is_active = ? WHERE name = ?
        ");
        return $stmt->execute([$active ? 1 : 0, $name]);
    }
}
