<?php
/**
 * TrueVault VPN - Part 7 Database Setup
 * Creates email, automation, and support ticket tables
 * Task 7.1, 7.5, 7.8
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Part 7 Database Setup</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #00d9ff; }
        h2 { color: #00ff88; margin-top: 20px; }
        .success { background: #155724; border: 1px solid #28a745; color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #721c24; border: 1px solid #dc3545; color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #0c5460; border: 1px solid #17a2b8; color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>📧 Part 7 - Email, Automation & Support Setup</h1>

<?php

$results = [];

// ============================================
// TASK 7.1: EMAIL TABLES (logs.db)
// ============================================
try {
    echo '<h2>📧 Task 7.1: Email Tables</h2>';
    
    $db = new SQLite3(DB_LOGS);
    $db->enableExceptions(true);
    
    // Email log table
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            method TEXT NOT NULL DEFAULT 'smtp',
            recipient TEXT NOT NULL,
            subject TEXT NOT NULL,
            body TEXT,
            template_name TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            error_message TEXT,
            sent_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Email queue table
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipient TEXT NOT NULL,
            subject TEXT NOT NULL,
            template_name TEXT NOT NULL,
            template_variables TEXT,
            email_type TEXT NOT NULL DEFAULT 'customer',
            priority INTEGER DEFAULT 5,
            status TEXT NOT NULL DEFAULT 'pending',
            scheduled_for DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME,
            attempts INTEGER DEFAULT 0,
            last_error TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Email templates table
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            template_name TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL,
            subject TEXT NOT NULL,
            body_html TEXT NOT NULL,
            body_text TEXT,
            category TEXT DEFAULT 'general',
            variables TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email_log_status ON email_log(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status, scheduled_for)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email_templates_name ON email_templates(template_name)");
    
    $db->close();
    echo '<div class="success">✅ Email tables created in logs.db</div>';
    $results['email_tables'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['email_tables'] = 'error';
}

// ============================================
// TASK 7.5: AUTOMATION TABLES (logs.db)
// ============================================
try {
    echo '<h2>🤖 Task 7.5: Automation Tables</h2>';
    
    $db = new SQLite3(DB_LOGS);
    $db->enableExceptions(true);
    
    // Workflow definitions
    $db->exec("
        CREATE TABLE IF NOT EXISTS workflows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_name TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL,
            description TEXT,
            trigger_event TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Workflow executions
    $db->exec("
        CREATE TABLE IF NOT EXISTS workflow_executions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_id INTEGER NOT NULL,
            workflow_name TEXT NOT NULL,
            trigger_event TEXT NOT NULL,
            user_id INTEGER,
            trigger_data TEXT,
            status TEXT NOT NULL DEFAULT 'running',
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            error_message TEXT,
            execution_log TEXT,
            FOREIGN KEY (workflow_id) REFERENCES workflows(id)
        )
    ");
    
    // Scheduled workflow steps
    $db->exec("
        CREATE TABLE IF NOT EXISTS scheduled_workflow_steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            execution_id INTEGER NOT NULL,
            step_name TEXT NOT NULL,
            step_data TEXT,
            execute_at DATETIME NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            executed_at DATETIME,
            result TEXT,
            error_message TEXT,
            FOREIGN KEY (execution_id) REFERENCES workflow_executions(id)
        )
    ");
    
    // Automation log
    $db->exec("
        CREATE TABLE IF NOT EXISTS automation_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_name TEXT NOT NULL,
            action TEXT NOT NULL,
            target_user_id INTEGER,
            target_email TEXT,
            details TEXT,
            status TEXT NOT NULL DEFAULT 'success',
            executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_workflow_executions_status ON workflow_executions(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_scheduled_steps_status ON scheduled_workflow_steps(status, execute_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_automation_log_workflow ON automation_log(workflow_name)");
    
    $db->close();
    echo '<div class="success">✅ Automation tables created in logs.db</div>';
    $results['automation_tables'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['automation_tables'] = 'error';
}

// ============================================
// TASK 7.8: SUPPORT TABLES (support.db)
// ============================================
try {
    echo '<h2>🎫 Task 7.8: Support Ticket Tables</h2>';
    
    // Create support.db if not exists
    $supportDb = dirname(DB_LOGS) . '/support.db';
    
    $db = new SQLite3($supportDb);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Support tickets table
    $db->exec("
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_number TEXT NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            user_email TEXT NOT NULL,
            subject TEXT NOT NULL,
            description TEXT NOT NULL,
            category TEXT DEFAULT 'general',
            priority TEXT NOT NULL DEFAULT 'normal',
            status TEXT NOT NULL DEFAULT 'open',
            assigned_to INTEGER,
            resolution TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME,
            closed_at DATETIME
        )
    ");
    
    // Ticket messages
    $db->exec("
        CREATE TABLE IF NOT EXISTS ticket_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            user_id INTEGER,
            is_staff INTEGER DEFAULT 0,
            sender_name TEXT,
            message TEXT NOT NULL,
            attachments TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
        )
    ");
    
    // Knowledge base
    $db->exec("
        CREATE TABLE IF NOT EXISTS knowledge_base (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            content TEXT NOT NULL,
            category TEXT NOT NULL,
            keywords TEXT,
            view_count INTEGER DEFAULT 0,
            helpful_count INTEGER DEFAULT 0,
            not_helpful_count INTEGER DEFAULT 0,
            is_published INTEGER DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Canned responses
    $db->exec("
        CREATE TABLE IF NOT EXISTS canned_responses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            category TEXT,
            shortcut TEXT UNIQUE,
            use_count INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Survey responses
    $db->exec("
        CREATE TABLE IF NOT EXISTS survey_responses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER,
            user_id INTEGER NOT NULL,
            survey_type TEXT NOT NULL,
            rating INTEGER,
            feedback TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_user ON support_tickets(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_status ON support_tickets(status, priority)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_number ON support_tickets(ticket_number)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_messages_ticket ON ticket_messages(ticket_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_kb_category ON knowledge_base(category)");
    
    $db->close();
    echo '<div class="success">✅ Support tables created in support.db</div>';
    $results['support_tables'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['support_tables'] = 'error';
}

// ============================================
// Insert Default Workflows
// ============================================
try {
    echo '<h2>⚙️ Inserting Default Workflows</h2>';
    
    $db = new SQLite3(DB_LOGS);
    $db->enableExceptions(true);
    
    $workflows = [
        ['new_customer_onboarding', 'New Customer Onboarding', 'Welcome sequence for new customers', 'user.registered'],
        ['payment_failed_escalation', 'Payment Failed Escalation', 'Reminder sequence for failed payments', 'payment.failed'],
        ['payment_success', 'Payment Success', 'Thank you and invoice delivery', 'payment.success'],
        ['support_ticket_created', 'Support Ticket Created', 'Auto-categorize and acknowledge', 'ticket.created'],
        ['support_ticket_resolved', 'Support Ticket Resolved', 'Resolution notification and survey', 'ticket.resolved'],
        ['complaint_handling', 'Complaint Handling', 'Apology and escalation workflow', 'complaint.received'],
        ['server_down_alert', 'Server Down Alert', 'Admin and customer notifications', 'server.down'],
        ['server_restored', 'Server Restored', 'All-clear notifications', 'server.restored'],
        ['cancellation_request', 'Cancellation Request', 'Retention and win-back sequence', 'subscription.cancelled'],
        ['monthly_invoicing', 'Monthly Invoicing', 'Generate and send invoices', 'billing.monthly'],
        ['vip_request_received', 'VIP Request Received', 'Notify admin of VIP request', 'vip.requested'],
        ['vip_approved', 'VIP Approved', 'Welcome VIP and provision resources', 'vip.approved']
    ];
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO workflows (workflow_name, display_name, description, trigger_event) VALUES (:name, :display, :desc, :trigger)");
    
    foreach ($workflows as $w) {
        $stmt->bindValue(':name', $w[0], SQLITE3_TEXT);
        $stmt->bindValue(':display', $w[1], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $w[2], SQLITE3_TEXT);
        $stmt->bindValue(':trigger', $w[3], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
    }
    
    $db->close();
    echo '<div class="success">✅ 12 workflows inserted</div>';
    $results['workflows'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['workflows'] = 'error';
}

// ============================================
// Summary
// ============================================
echo '<h2>📊 Summary</h2>';
echo '<div class="info">';
echo '<ul>';
foreach ($results as $task => $status) {
    $icon = $status === 'success' ? '✅' : '❌';
    echo "<li>$icon $task: $status</li>";
}
echo '</ul>';

$success = count(array_filter($results, fn($s) => $s === 'success'));
if ($success === count($results)) {
    echo '<p><strong>All Part 7 database tables created successfully!</strong></p>';
    echo '<p>Next: Create Email.php, AutomationEngine.php, Workflows.php</p>';
}
echo '</div>';

?>
</div>
</body>
</html>
