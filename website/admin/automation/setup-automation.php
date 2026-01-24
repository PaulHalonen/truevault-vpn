<?php
/**
 * TrueVault VPN - Automation Setup Script
 * Creates database tables, seeds email templates, knowledge base, and workflows
 * Created: January 24, 2026
 * 
 * RUN ONCE: Visit this URL to set up automation:
 * https://vpn.the-truth-publishing.com/admin/automation/setup-automation.php
 */

header('Content-Type: text/html; charset=utf-8');

// Create databases directory
$dbDir = __DIR__ . '/databases';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Create logs directory
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

echo "<!DOCTYPE html><html><head><title>Automation Setup</title>
<style>
body { font-family: monospace; background: #0a0a0f; color: #00ff88; padding: 30px; }
.success { color: #00ff88; }
.error { color: #ff5050; }
.info { color: #00d4ff; }
h1 { color: #00d4ff; }
pre { background: #1a1a2e; padding: 15px; border-radius: 8px; overflow-x: auto; }
</style></head><body>";
echo "<h1>🚀 TrueVault Automation Setup</h1>";
echo "<pre>";

try {
    $db = new SQLite3($dbDir . '/automation.db');
    $db->enableExceptions(true);
    
    echo "<span class='info'>Creating database tables...</span>\n\n";
    
    // =============================================
    // CREATE TABLES
    // =============================================
    
    // Email Log
    $db->exec("CREATE TABLE IF NOT EXISTS email_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        recipient_email TEXT NOT NULL,
        recipient_name TEXT,
        subject TEXT NOT NULL,
        template_name TEXT,
        email_type TEXT DEFAULT 'customer',
        method TEXT DEFAULT 'smtp',
        status TEXT DEFAULT 'pending',
        sent_at TEXT,
        opened_at TEXT,
        clicked_at TEXT,
        error_message TEXT,
        metadata TEXT,
        created_at TEXT DEFAULT (datetime('now'))
    )");
    echo "<span class='success'>✓ email_log table created</span>\n";
    
    // Scheduled Tasks
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        workflow_name TEXT NOT NULL,
        task_type TEXT NOT NULL,
        execute_at TEXT NOT NULL,
        task_data TEXT,
        status TEXT DEFAULT 'pending',
        executed_at TEXT,
        error_message TEXT,
        retry_count INTEGER DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now'))
    )");
    echo "<span class='success'>✓ scheduled_tasks table created</span>\n";
    
    // Automation Log
    $db->exec("CREATE TABLE IF NOT EXISTS automation_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        workflow_name TEXT NOT NULL,
        trigger_type TEXT,
        trigger_data TEXT,
        status TEXT DEFAULT 'running',
        steps_completed INTEGER DEFAULT 0,
        steps_total INTEGER DEFAULT 0,
        started_at TEXT DEFAULT (datetime('now')),
        completed_at TEXT,
        error_message TEXT
    )");
    echo "<span class='success'>✓ automation_log table created</span>\n";
    
    // Knowledge Base
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_base (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category TEXT NOT NULL,
        keywords TEXT NOT NULL,
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        resolution_steps TEXT,
        success_rate INTEGER DEFAULT 90,
        times_used INTEGER DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now'))
    )");
    echo "<span class='success'>✓ knowledge_base table created</span>\n";
    
    // Email Templates
    $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        tier TEXT DEFAULT 'basic',
        category TEXT NOT NULL,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        variables TEXT,
        active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT (datetime('now'))
    )");
    echo "<span class='success'>✓ email_templates table created</span>\n";
    
    // Workflow Definitions
    $db->exec("CREATE TABLE IF NOT EXISTS workflow_definitions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        description TEXT,
        trigger_event TEXT NOT NULL,
        steps TEXT NOT NULL,
        is_active INTEGER DEFAULT 1,
        execution_count INTEGER DEFAULT 0,
        last_executed_at TEXT,
        created_at TEXT DEFAULT (datetime('now'))
    )");
    echo "<span class='success'>✓ workflow_definitions table created</span>\n";
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email_status ON email_log(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email_created ON email_log(created_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tasks_status ON scheduled_tasks(status, execute_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_kb_category ON knowledge_base(category)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_kb_keywords ON knowledge_base(keywords)");
    echo "<span class='success'>✓ Indexes created</span>\n\n";
    
    // =============================================
    // SEED EMAIL TEMPLATES (19 templates)
    // =============================================
    
    echo "<span class='info'>Seeding email templates...</span>\n";
    
    $templates = [
        // ONBOARDING
        ['welcome_basic', 'basic', 'onboarding', 'Welcome to TrueVault VPN!', getWelcomeBasic()],
        ['welcome_formal', 'formal', 'onboarding', 'Welcome to TrueVault VPN - Getting Started', getWelcomeFormal()],
        ['welcome_vip', 'vip', 'onboarding', 'Your Premium TrueVault Experience Awaits', getWelcomeVip()],
        
        // PAYMENTS
        ['payment_success_basic', 'basic', 'payment', 'Payment Confirmed - TrueVault VPN', getPaymentSuccessBasic()],
        ['payment_success_formal', 'formal', 'payment', 'TrueVault VPN Invoice #{invoice_number}', getPaymentSuccessFormal()],
        ['payment_failed_reminder1', 'basic', 'payment', 'Action Needed: Update Your Payment Method', getPaymentReminder1()],
        ['payment_failed_reminder2', 'formal', 'payment', 'Urgent: Your TrueVault Subscription', getPaymentReminder2()],
        ['payment_failed_final', 'formal', 'payment', 'Final Notice: Service Suspension Tomorrow', getPaymentFinal()],
        
        // SUPPORT
        ['ticket_received', 'formal', 'support', 'Support Ticket #{ticket_id} - We\'re On It!', getTicketReceived()],
        ['ticket_resolved', 'formal', 'support', 'Ticket #{ticket_id} Resolved - How Did We Do?', getTicketResolved()],
        
        // COMPLAINTS
        ['complaint_acknowledge', 'formal', 'complaint', 'We\'re Sorry - Your Feedback Matters', getComplaintAcknowledge()],
        ['complaint_resolved', 'formal', 'complaint', 'Follow-Up: Your Recent Concern', getComplaintResolved()],
        
        // SERVER
        ['server_down', 'vip', 'server', '🔴 ALERT: Server Outage Detected', getServerDown()],
        ['server_restored', 'basic', 'server', '✅ Server Restored - All Systems Normal', getServerRestored()],
        
        // RETENTION
        ['cancellation_survey', 'formal', 'retention', 'We\'re Sad to See You Go', getCancellationSurvey()],
        ['retention_offer', 'vip', 'retention', 'Wait! A Special Offer Just for You', getRetentionOffer()],
        ['winback_campaign', 'vip', 'retention', 'We Miss You! Come Back for 60% Off', getWinbackCampaign()],
        
        // VIP
        ['vip_request_received', 'formal', 'vip', 'VIP Request Received - Under Review', getVipRequestReceived()],
        ['vip_welcome_package', 'vip', 'vip', '🌟 Welcome to VIP - Your Dedicated Server Awaits', getVipWelcomePackage()]
    ];
    
    $stmt = $db->prepare("INSERT OR REPLACE INTO email_templates (name, tier, category, subject, body, variables) VALUES (:name, :tier, :category, :subject, :body, :vars)");
    
    foreach ($templates as $t) {
        $stmt->bindValue(':name', $t[0], SQLITE3_TEXT);
        $stmt->bindValue(':tier', $t[1], SQLITE3_TEXT);
        $stmt->bindValue(':category', $t[2], SQLITE3_TEXT);
        $stmt->bindValue(':subject', $t[3], SQLITE3_TEXT);
        $stmt->bindValue(':body', $t[4], SQLITE3_TEXT);
        $stmt->bindValue(':vars', '["first_name","email","dashboard_url"]', SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
        echo "<span class='success'>✓ Template: {$t[0]}</span>\n";
    }
    
    // =============================================
    // SEED KNOWLEDGE BASE (20 entries)
    // =============================================
    
    echo "\n<span class='info'>Seeding knowledge base...</span>\n";
    
    $kbEntries = [
        // BILLING
        ['billing', 'payment,failed,declined,card,expired', 'Why did my payment fail?', 'Common reasons include: expired card, insufficient funds, or billing address mismatch. Update your payment method in Dashboard > Billing.', '["Check card expiration date","Verify billing address matches card","Ensure sufficient funds","Try a different payment method"]'],
        ['billing', 'refund,money,back,cancel', 'Can I get a refund?', 'We offer a 30-day money-back guarantee. Contact support within 30 days of purchase for a full refund.', '["Submit refund request via dashboard","Provide order/invoice number","Refunds processed in 5-7 business days"]'],
        ['billing', 'upgrade,downgrade,change,plan', 'How do I change my plan?', 'Go to Dashboard > Account > Change Plan. Upgrades take effect immediately; downgrades apply at next billing cycle.', '["Log into dashboard","Go to Account Settings","Click Change Plan","Select new plan","Confirm change"]'],
        ['billing', 'invoice,receipt,bill', 'Where can I find my invoices?', 'All invoices are available in Dashboard > Billing > Invoice History. You can download PDFs for your records.', '["Go to Dashboard > Billing","Click Invoice History","Download PDF receipts"]'],
        
        // TECHNICAL
        ['technical', 'slow,speed,connection,lag', 'Why is my VPN connection slow?', 'Try switching to a closer server, using WireGuard protocol (fastest), or restarting your device. Also ensure no bandwidth-heavy apps are running.', '["Switch to a closer server","Change to WireGuard protocol","Restart the VPN app","Restart your device","Check for other bandwidth usage"]'],
        ['technical', 'connect,connection,failed,error', 'I cannot connect to VPN', 'First check your internet connection. Then try: restarting the app, switching servers, or changing protocols. If issues persist, contact support.', '["Verify internet connection works","Restart TrueVault app","Try a different server","Switch to a different protocol","Reinstall the app if needed"]'],
        ['technical', 'leak,ip,dns,webrtc', 'How do I check for IP/DNS leaks?', 'Use our built-in leak test at Dashboard > Security > Leak Test. Ensure Kill Switch and DNS Leak Protection are enabled.', '["Go to Dashboard > Security","Run built-in leak test","Enable Kill Switch","Enable DNS Leak Protection"]'],
        ['technical', 'kill,switch,disconnect', 'What is Kill Switch?', 'Kill Switch blocks all internet traffic if VPN connection drops unexpectedly. Enable it in Settings > Security > Kill Switch.', '["Open TrueVault settings","Go to Security tab","Toggle Kill Switch ON"]'],
        ['technical', 'split,tunnel,exclude,app', 'Can I exclude apps from VPN?', 'Yes! Split Tunneling lets you choose which apps use VPN. Available on Windows and Android in Settings > Split Tunneling.', '["Open Settings > Split Tunneling","Add apps to exclude","Save changes"]'],
        ['technical', 'streaming,netflix,hulu,blocked', 'VPN not working with streaming services?', 'Try a different server location optimized for streaming. Clear browser cookies after connecting. Some services actively block VPNs.', '["Try streaming-optimized servers","Clear browser cookies","Try different server locations","Check our supported streaming list"]'],
        
        // ACCOUNT
        ['account', 'email,change,update', 'How do I change my email address?', 'Go to Dashboard > Account Settings > Change Email. You will need to verify both old and new email addresses.', '["Log into dashboard","Go to Account Settings","Click Change Email","Verify via both email addresses"]'],
        ['account', 'password,change,reset,forgot', 'How do I change/reset my password?', 'In dashboard: Account Settings > Change Password. Forgot password? Use the "Forgot Password" link on login page.', '["Go to Account Settings","Click Change Password","Enter old and new password","Or use Forgot Password link if locked out"]'],
        ['account', '2fa,two,factor,authenticator', 'How do I enable two-factor authentication?', 'Go to Dashboard > Security > Two-Factor Authentication. Scan QR code with authenticator app. Save backup codes!', '["Go to Dashboard > Security","Click Enable 2FA","Scan QR code with authenticator app","Save backup codes securely"]'],
        ['account', 'delete,close,remove,account', 'How do I delete my account?', 'First cancel your subscription. Then go to Account Settings > Delete Account. This action is permanent.', '["Cancel active subscription first","Go to Account Settings","Click Delete Account","Confirm deletion"]'],
        ['account', 'device,limit,maximum,devices', 'How many devices can I connect?', 'Personal plan: 5 devices. Family plan: 10 devices. Dedicated plan: Unlimited devices. Manage devices in Dashboard.', '["Check your plan limits","Manage devices in Dashboard > Devices","Remove old devices to add new ones"]'],
        
        // SETUP
        ['setup', 'install,download,setup,start', 'How do I install TrueVault VPN?', 'Download from your dashboard for your device. Run installer, log in with your account, and click Connect. That\'s it!', '["Log into dashboard","Download app for your device","Run installer","Log in with account","Click Connect"]'],
        ['setup', 'router,home,network', 'Can I use VPN on my router?', 'Yes! Go to Dashboard > Setup Guides > Router. Note: Router VPN may slow down network speeds.', '["Check router compatibility","Download router config from dashboard","Follow setup guide","Note: May reduce speeds"]'],
        ['setup', 'protocol,wireguard,openvpn,ikev2', 'Which VPN protocol should I use?', 'WireGuard is fastest and recommended. OpenVPN is most compatible. IKEv2 is best for mobile devices.', '["WireGuard: Fastest, recommended","OpenVPN: Most compatible, slightly slower","IKEv2: Best for mobile, quick reconnects"]'],
        
        // GENERAL
        ['general', 'vpn,what,how,works', 'What is a VPN and why do I need it?', 'A VPN encrypts your internet traffic and masks your IP address. Benefits: privacy, security, access geo-restricted content.', '["Encrypts all internet traffic","Hides your real IP address","Protects on public WiFi","Access content from other regions"]'],
        ['general', 'logs,logging,privacy,data', 'Does TrueVault keep logs?', 'We have a strict no-logs policy. We do not track, collect, or store your browsing activity. Only account info (email, payment) is stored.', '["Strict no-logs policy","No browsing data collected","Only account info stored"]']
    ];
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO knowledge_base (category, keywords, question, answer, resolution_steps) VALUES (:cat, :key, :q, :a, :steps)");
    
    foreach ($kbEntries as $kb) {
        $stmt->bindValue(':cat', $kb[0], SQLITE3_TEXT);
        $stmt->bindValue(':key', $kb[1], SQLITE3_TEXT);
        $stmt->bindValue(':q', $kb[2], SQLITE3_TEXT);
        $stmt->bindValue(':a', $kb[3], SQLITE3_TEXT);
        $stmt->bindValue(':steps', $kb[4], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
    }
    echo "<span class='success'>✓ 20 knowledge base entries added</span>\n";
    
    // =============================================
    // SEED WORKFLOW DEFINITIONS (12 workflows)
    // =============================================
    
    echo "\n<span class='info'>Seeding workflow definitions...</span>\n";
    
    $workflows = [
        [
            'new_customer_onboarding',
            'Welcome new customers with emails and setup guidance',
            'customer_signup',
            json_encode([
                ['action' => 'send_email', 'template' => 'welcome_basic', 'to' => 'customer', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'welcome_formal', 'to' => 'customer', 'delay' => 3600],
                ['action' => 'generate_invoice', 'delay' => 0]
            ])
        ],
        [
            'payment_failed_escalation',
            '8-day escalation sequence for failed payments',
            'payment_failed',
            json_encode([
                ['action' => 'send_email', 'template' => 'payment_failed_reminder1', 'to' => 'customer', 'delay' => 0],
                ['action' => 'update_status', 'status' => 'grace_period', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'payment_failed_reminder2', 'to' => 'customer', 'delay' => 259200],
                ['action' => 'send_email', 'template' => 'payment_failed_final', 'to' => 'customer', 'delay' => 604800],
                ['action' => 'suspend_service', 'delay' => 691200]
            ])
        ],
        [
            'payment_success',
            'Thank customer and generate invoice on successful payment',
            'payment_success',
            json_encode([
                ['action' => 'generate_invoice', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'payment_success_basic', 'to' => 'customer', 'delay' => 0],
                ['action' => 'update_status', 'status' => 'active', 'delay' => 0]
            ])
        ],
        [
            'support_ticket_created',
            'Auto-categorize, check knowledge base, send acknowledgment',
            'ticket_created',
            json_encode([
                ['action' => 'categorize_ticket', 'delay' => 0],
                ['action' => 'check_knowledge_base', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'ticket_received', 'to' => 'customer', 'delay' => 0],
                ['action' => 'assign_priority', 'delay' => 0],
                ['action' => 'escalate_ticket', 'delay' => 86400]
            ])
        ],
        [
            'support_ticket_resolved',
            'Notify customer and request feedback',
            'ticket_resolved',
            json_encode([
                ['action' => 'send_email', 'template' => 'ticket_resolved', 'to' => 'customer', 'delay' => 0]
            ])
        ],
        [
            'complaint_handling',
            'Apologize, flag for review, schedule follow-up',
            'complaint_received',
            json_encode([
                ['action' => 'send_email', 'template' => 'complaint_acknowledge', 'to' => 'customer', 'delay' => 0],
                ['action' => 'flag_for_review', 'priority' => 'high', 'delay' => 0],
                ['action' => 'notify_admin', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'complaint_resolved', 'to' => 'customer', 'delay' => 604800]
            ])
        ],
        [
            'server_down_alert',
            'Alert admin and notify affected customers',
            'server_down',
            json_encode([
                ['action' => 'send_email', 'template' => 'server_down', 'to' => 'admin', 'delay' => 0],
                ['action' => 'notify_admin', 'delay' => 0]
            ])
        ],
        [
            'server_restored',
            'Notify admin and customers that server is back',
            'server_up',
            json_encode([
                ['action' => 'send_email', 'template' => 'server_restored', 'to' => 'admin', 'delay' => 0]
            ])
        ],
        [
            'cancellation_request',
            'Send survey, retention offer, and win-back campaign',
            'cancellation_requested',
            json_encode([
                ['action' => 'send_email', 'template' => 'cancellation_survey', 'to' => 'customer', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'retention_offer', 'to' => 'customer', 'delay' => 3600],
                ['action' => 'send_email', 'template' => 'winback_campaign', 'to' => 'customer', 'delay' => 2592000]
            ])
        ],
        [
            'monthly_invoicing',
            'Generate and send invoices for all active customers',
            'end_of_month',
            json_encode([
                ['action' => 'generate_invoices', 'delay' => 0],
                ['action' => 'send_invoice_emails', 'delay' => 0]
            ])
        ],
        [
            'vip_request_received',
            'Acknowledge VIP request and flag for review',
            'vip_request',
            json_encode([
                ['action' => 'send_email', 'template' => 'vip_request_received', 'to' => 'customer', 'delay' => 0],
                ['action' => 'flag_for_review', 'priority' => 'high', 'delay' => 0]
            ])
        ],
        [
            'vip_approved',
            'Upgrade tier and send VIP welcome package',
            'vip_approved',
            json_encode([
                ['action' => 'upgrade_tier', 'tier' => 'vip', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'vip_welcome_package', 'to' => 'customer', 'delay' => 0],
                ['action' => 'provision_dedicated_server', 'delay' => 0]
            ])
        ]
    ];
    
    $stmt = $db->prepare("INSERT OR REPLACE INTO workflow_definitions (name, description, trigger_event, steps) VALUES (:name, :desc, :trigger, :steps)");
    
    foreach ($workflows as $w) {
        $stmt->bindValue(':name', $w[0], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $w[1], SQLITE3_TEXT);
        $stmt->bindValue(':trigger', $w[2], SQLITE3_TEXT);
        $stmt->bindValue(':steps', $w[3], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
        echo "<span class='success'>✓ Workflow: {$w[0]}</span>\n";
    }
    
    $db->close();
    
    echo "\n<span class='success'>========================================</span>\n";
    echo "<span class='success'>✅ AUTOMATION SETUP COMPLETE!</span>\n";
    echo "<span class='success'>========================================</span>\n\n";
    echo "<span class='info'>Next Steps:</span>\n";
    echo "1. Set up cron job for task processor:\n";
    echo "   */5 * * * * php /path/to/admin/automation/task-processor.php\n\n";
    echo "2. Access automation dashboard:\n";
    echo "   https://vpn.the-truth-publishing.com/admin/automation/\n\n";
    
} catch (Exception $e) {
    echo "<span class='error'>ERROR: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}

echo "</pre></body></html>";

// =============================================
// EMAIL TEMPLATE FUNCTIONS
// =============================================

function getEmailWrapper($content, $bgColor = '#0a0a0f', $accentColor = '#00d4ff') {
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='margin:0;padding:0;background:{$bgColor};font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;'><table width='100%' cellpadding='0' cellspacing='0'><tr><td align='center' style='padding:40px 20px;'><table width='600' cellpadding='0' cellspacing='0' style='background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.1);border-radius:16px;overflow:hidden;'>{$content}</table></td></tr></table></body></html>";
}

function getWelcomeBasic() {
    return getEmailWrapper("<tr><td style='padding:40px;text-align:center;'><h1 style='color:#00d4ff;margin:0 0 20px;'>Welcome to TrueVault VPN!</h1><p style='color:#ffffff;font-size:16px;line-height:1.6;'>Hi {first_name},</p><p style='color:#aaaaaa;font-size:15px;line-height:1.6;'>Thanks for joining TrueVault VPN! Your account is ready. Download our apps, connect to any server, and enjoy secure, private browsing.</p><a href='{dashboard_url}' style='display:inline-block;margin:30px 0;padding:15px 40px;background:linear-gradient(90deg,#00d4ff,#7b2cbf);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;'>Go to Dashboard</a><p style='color:#666;font-size:13px;'>Questions? Reply to this email anytime.</p></td></tr>");
}

function getWelcomeFormal() {
    return getEmailWrapper("<tr><td style='background:linear-gradient(90deg,#00d4ff,#7b2cbf);padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;'>TrueVault VPN</h1></td></tr><tr><td style='padding:40px;'><p style='color:#ffffff;font-size:16px;'>Dear {first_name},</p><p style='color:#aaaaaa;font-size:15px;line-height:1.8;'>Thank you for choosing TrueVault VPN. Your subscription is now active and your account is ready for use.</p><h3 style='color:#00d4ff;margin:25px 0 15px;'>Getting Started</h3><ol style='color:#aaaaaa;line-height:2;'><li>Download our app from your dashboard</li><li>Log in with your credentials</li><li>Select a server and click Connect</li></ol><a href='{dashboard_url}' style='display:inline-block;margin:25px 0;padding:15px 35px;background:#00d4ff;color:#000;text-decoration:none;border-radius:8px;font-weight:600;'>Access Your Dashboard</a></td></tr>");
}

function getWelcomeVip() {
    return getEmailWrapper("<tr><td style='background:linear-gradient(135deg,#b8860b,#daa520);padding:40px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:28px;'>Welcome to Your Premium Experience</h1></td></tr><tr><td style='padding:40px;'><p style='color:#ffffff;font-size:16px;'>Dear {first_name},</p><p style='color:#aaaaaa;font-size:15px;line-height:1.8;'>Thank you for choosing our premium service. You now have access to a dedicated server with guaranteed performance and priority support.</p><div style='background:rgba(184,134,11,0.1);border:1px solid #b8860b;border-radius:12px;padding:25px;margin:25px 0;'><h3 style='color:#daa520;margin:0 0 15px;'>Your Dedicated Server</h3><p style='color:#ffffff;margin:5px 0;'><strong>Location:</strong> {server_name}</p><p style='color:#ffffff;margin:5px 0;'><strong>IP Address:</strong> {server_ip}</p></div><a href='{dashboard_url}' style='display:inline-block;margin:20px 0;padding:15px 40px;background:linear-gradient(90deg,#b8860b,#daa520);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;'>Access Premium Dashboard</a></td></tr>", '#0a0a0f', '#daa520');
}

function getPaymentSuccessBasic() {
    return getEmailWrapper("<tr><td style='padding:40px;text-align:center;'><div style='font-size:50px;margin-bottom:20px;'>✅</div><h1 style='color:#00c864;margin:0 0 20px;'>Payment Successful!</h1><p style='color:#ffffff;font-size:16px;'>Hi {first_name},</p><p style='color:#aaaaaa;font-size:15px;'>Your payment of <strong style='color:#00c864;'>\${amount}</strong> has been processed successfully.</p><p style='color:#666;font-size:13px;margin-top:30px;'>Thank you for being a TrueVault customer!</p></td></tr>");
}

function getPaymentSuccessFormal() {
    return getEmailWrapper("<tr><td style='background:linear-gradient(90deg,#00d4ff,#7b2cbf);padding:25px;text-align:center;'><h2 style='color:#fff;margin:0;'>Invoice #{invoice_number}</h2></td></tr><tr><td style='padding:40px;'><p style='color:#ffffff;'>Dear {first_name},</p><p style='color:#aaaaaa;'>Thank you for your payment. Here are your transaction details:</p><table style='width:100%;margin:25px 0;border-collapse:collapse;'><tr><td style='padding:12px;color:#888;border-bottom:1px solid rgba(255,255,255,0.1);'>Amount</td><td style='padding:12px;color:#00c864;font-weight:bold;text-align:right;border-bottom:1px solid rgba(255,255,255,0.1);'>\${amount}</td></tr><tr><td style='padding:12px;color:#888;border-bottom:1px solid rgba(255,255,255,0.1);'>Date</td><td style='padding:12px;color:#fff;text-align:right;border-bottom:1px solid rgba(255,255,255,0.1);'>{payment_date}</td></tr><tr><td style='padding:12px;color:#888;'>Status</td><td style='padding:12px;color:#00c864;text-align:right;'>Paid</td></tr></table></td></tr>");
}

function getPaymentReminder1() {
    return getEmailWrapper("<tr><td style='border-left:4px solid #ffb400;padding:40px;'><h2 style='color:#ffb400;margin:0 0 20px;'>Payment Update Needed</h2><p style='color:#ffffff;'>Hi {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>We weren't able to process your payment of \${amount}. This can happen if your card expired or there were insufficient funds.</p><p style='color:#aaaaaa;'>Please update your payment method to continue enjoying TrueVault VPN.</p><a href='{dashboard_url}/billing' style='display:inline-block;margin:25px 0;padding:12px 30px;background:#ffb400;color:#000;text-decoration:none;border-radius:6px;font-weight:600;'>Update Payment Method</a></td></tr>");
}

function getPaymentReminder2() {
    return getEmailWrapper("<tr><td style='border-left:4px solid #ff8c00;padding:40px;'><h2 style='color:#ff8c00;margin:0 0 20px;'>⚠️ Urgent: Payment Required</h2><p style='color:#ffffff;'>Hi {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>Your TrueVault VPN subscription payment of \${amount} is still outstanding. To avoid service interruption, please update your payment method immediately.</p><div style='background:rgba(255,140,0,0.1);border:1px solid #ff8c00;border-radius:8px;padding:15px;margin:20px 0;'><p style='color:#ff8c00;margin:0;font-weight:600;'>Service will be suspended in 4 days if payment is not received.</p></div><a href='{dashboard_url}/billing' style='display:inline-block;margin:15px 0;padding:12px 30px;background:#ff8c00;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>Pay Now</a></td></tr>");
}

function getPaymentFinal() {
    return getEmailWrapper("<tr><td style='border-left:4px solid #ff5050;padding:40px;'><h2 style='color:#ff5050;margin:0 0 20px;'>🔴 Final Notice: Service Suspension Tomorrow</h2><p style='color:#ffffff;'>Hi {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>This is your final notice. Your outstanding payment of \${amount} has not been received, and your service will be suspended tomorrow.</p><p style='color:#aaaaaa;'>To keep your account active and avoid losing your settings, please pay immediately.</p><a href='{dashboard_url}/billing' style='display:inline-block;margin:25px 0;padding:15px 40px;background:#ff5050;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>Pay Now to Prevent Suspension</a></td></tr>");
}

function getTicketReceived() {
    return getEmailWrapper("<tr><td style='padding:40px;'><h2 style='color:#00d4ff;margin:0 0 20px;'>Support Ticket #{ticket_id}</h2><p style='color:#ffffff;'>Hi {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>We've received your support request and our team is on it!</p><div style='background:rgba(0,212,255,0.1);border:1px solid #00d4ff;border-radius:8px;padding:20px;margin:20px 0;'><p style='color:#aaaaaa;margin:0 0 10px;'><strong style='color:#fff;'>Subject:</strong> {ticket_subject}</p><p style='color:#aaaaaa;margin:0;'><strong style='color:#fff;'>Priority:</strong> {priority}</p></div><p style='color:#aaaaaa;'>We typically respond within 24 hours. You can check your ticket status anytime in your dashboard.</p></td></tr>");
}

function getTicketResolved() {
    return getEmailWrapper("<tr><td style='padding:40px;text-align:center;'><div style='font-size:50px;margin-bottom:20px;'>✅</div><h2 style='color:#00c864;margin:0 0 20px;'>Ticket #{ticket_id} Resolved!</h2><p style='color:#ffffff;'>Hi {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>Great news! Your support ticket regarding \"{ticket_subject}\" has been resolved.</p><p style='color:#aaaaaa;'>We'd love to hear how we did. Your feedback helps us improve!</p><a href='{feedback_url}' style='display:inline-block;margin:25px 0;padding:12px 30px;background:#00d4ff;color:#000;text-decoration:none;border-radius:6px;font-weight:600;'>Rate Your Experience</a></td></tr>");
}

function getComplaintAcknowledge() {
    return getEmailWrapper("<tr><td style='padding:40px;'><h2 style='color:#ff8c00;margin:0 0 20px;'>We're Sorry</h2><p style='color:#ffffff;'>Dear {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>We sincerely apologize for the experience that led to your concern. Your feedback is extremely important to us, and we take all complaints seriously.</p><p style='color:#aaaaaa;'>Your issue has been escalated to our senior team and will be addressed within 24 hours.</p><p style='color:#666;font-size:13px;margin-top:30px;'>Reference: #{ticket_id}</p></td></tr>");
}

function getComplaintResolved() {
    return getEmailWrapper("<tr><td style='padding:40px;'><h2 style='color:#00d4ff;margin:0 0 20px;'>Following Up On Your Concern</h2><p style='color:#ffffff;'>Dear {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>We wanted to follow up on your recent concern. We've taken steps to address the issue and ensure it doesn't happen again.</p><div style='background:rgba(0,200,100,0.1);border:1px solid #00c864;border-radius:8px;padding:20px;margin:20px 0;'><p style='color:#00c864;margin:0;font-weight:600;'>As a token of our appreciation for your patience, we've applied a credit to your account.</p></div><p style='color:#aaaaaa;'>Thank you for giving us the opportunity to make things right.</p></td></tr>");
}

function getServerDown() {
    return getEmailWrapper("<tr><td style='background:#ff5050;padding:25px;text-align:center;'><h2 style='color:#fff;margin:0;'>🔴 SERVER ALERT</h2></td></tr><tr><td style='padding:40px;'><h3 style='color:#ff5050;margin:0 0 20px;'>Server Outage Detected</h3><table style='width:100%;margin:20px 0;'><tr><td style='padding:10px;color:#888;'>Server:</td><td style='padding:10px;color:#fff;'>{server_name}</td></tr><tr><td style='padding:10px;color:#888;'>IP:</td><td style='padding:10px;color:#fff;'>{server_ip}</td></tr><tr><td style='padding:10px;color:#888;'>Location:</td><td style='padding:10px;color:#fff;'>{server_location}</td></tr><tr><td style='padding:10px;color:#888;'>Down Since:</td><td style='padding:10px;color:#ff5050;'>{down_since}</td></tr><tr><td style='padding:10px;color:#888;'>Affected Users:</td><td style='padding:10px;color:#fff;'>{affected_users}</td></tr></table><a href='{admin_url}' style='display:inline-block;margin:15px 0;padding:12px 30px;background:#ff5050;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>View Admin Dashboard</a></td></tr>");
}

function getServerRestored() {
    return getEmailWrapper("<tr><td style='background:#00c864;padding:25px;text-align:center;'><h2 style='color:#fff;margin:0;'>✅ ALL SYSTEMS NORMAL</h2></td></tr><tr><td style='padding:40px;text-align:center;'><p style='color:#ffffff;font-size:16px;'>Hi {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>Good news! {server_name} is back online and operating normally.</p><p style='color:#888;'>Downtime duration: {downtime_duration}</p></td></tr>");
}

function getCancellationSurvey() {
    return getEmailWrapper("<tr><td style='padding:40px;'><h2 style='color:#7b2cbf;margin:0 0 20px;'>We're Sad to See You Go</h2><p style='color:#ffffff;'>Hi {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>We noticed you've requested to cancel your TrueVault subscription. Before you go, could you tell us why?</p><div style='margin:25px 0;'><a href='{survey_url}?reason=price' style='display:block;padding:12px;margin:8px 0;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#fff;text-decoration:none;'>💰 Too expensive</a><a href='{survey_url}?reason=technical' style='display:block;padding:12px;margin:8px 0;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#fff;text-decoration:none;'>🔧 Technical issues</a><a href='{survey_url}?reason=features' style='display:block;padding:12px;margin:8px 0;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#fff;text-decoration:none;'>✨ Missing features</a><a href='{survey_url}?reason=other' style='display:block;padding:12px;margin:8px 0;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#fff;text-decoration:none;'>📝 Other reason</a></div></td></tr>");
}

function getRetentionOffer() {
    return getEmailWrapper("<tr><td style='background:linear-gradient(135deg,#b8860b,#daa520);padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;font-size:24px;'>Wait! Special Offer Inside</h1></td></tr><tr><td style='padding:40px;text-align:center;'><p style='color:#ffffff;font-size:16px;'>Hi {first_name},</p><p style='color:#aaaaaa;font-size:15px;'>Before you go, we'd like to offer you something special...</p><div style='background:rgba(184,134,11,0.1);border:2px solid #daa520;border-radius:12px;padding:30px;margin:25px 0;'><h2 style='color:#daa520;margin:0 0 10px;'>50% OFF</h2><p style='color:#fff;margin:0;font-size:18px;'>for the next 3 months</p><p style='color:#888;margin:15px 0 0;'>Use code: <strong style='color:#daa520;'>STAYWITHUS</strong></p></div><a href='{dashboard_url}/billing?code=STAYWITHUS' style='display:inline-block;padding:15px 40px;background:linear-gradient(90deg,#b8860b,#daa520);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;'>Claim Offer</a></td></tr>", '#0a0a0f', '#daa520');
}

function getWinbackCampaign() {
    return getEmailWrapper("<tr><td style='background:linear-gradient(135deg,#00d4ff,#7b2cbf);padding:30px;text-align:center;'><h1 style='color:#fff;margin:0;'>We Miss You! 💜</h1></td></tr><tr><td style='padding:40px;text-align:center;'><p style='color:#ffffff;font-size:16px;'>Hi {first_name},</p><p style='color:#aaaaaa;'>It's been a while! We've made lots of improvements since you left, and we'd love to have you back.</p><div style='background:linear-gradient(135deg,rgba(0,212,255,0.1),rgba(123,44,191,0.1));border:2px solid #7b2cbf;border-radius:12px;padding:30px;margin:25px 0;'><h2 style='color:#00d4ff;margin:0 0 10px;'>60% OFF</h2><p style='color:#fff;margin:0;font-size:18px;'>for 3 months when you return</p><p style='color:#888;margin:15px 0 0;'>Code: <strong style='color:#00d4ff;'>WELCOMEBACK</strong></p></div><a href='{signup_url}?code=WELCOMEBACK' style='display:inline-block;padding:15px 40px;background:linear-gradient(90deg,#00d4ff,#7b2cbf);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;'>Come Back & Save</a></td></tr>");
}

function getVipRequestReceived() {
    return getEmailWrapper("<tr><td style='padding:40px;'><h2 style='color:#daa520;margin:0 0 20px;'>⭐ VIP Request Received</h2><p style='color:#ffffff;'>Dear {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>Thank you for your interest in our VIP tier! Your request has been received and is under review.</p><p style='color:#aaaaaa;'>Our team will evaluate your application within 24-48 hours. You'll receive an email with the results.</p><div style='background:rgba(218,165,32,0.1);border:1px solid #daa520;border-radius:8px;padding:20px;margin:20px 0;'><h4 style='color:#daa520;margin:0 0 10px;'>VIP Benefits Include:</h4><ul style='color:#aaaaaa;margin:0;padding-left:20px;'><li>Dedicated server with guaranteed performance</li><li>Priority support (30-minute response)</li><li>Unlimited devices</li><li>Advanced security features</li></ul></div></td></tr>", '#0a0a0f', '#daa520');
}

function getVipWelcomePackage() {
    return getEmailWrapper("<tr><td style='background:linear-gradient(135deg,#b8860b,#daa520);padding:40px;text-align:center;'><h1 style='color:#fff;margin:0;'>🌟 Welcome to VIP 🌟</h1></td></tr><tr><td style='padding:40px;'><p style='color:#ffffff;font-size:16px;'>Dear {first_name},</p><p style='color:#aaaaaa;line-height:1.6;'>Congratulations! Your VIP access has been approved. You now have access to our most premium features and a dedicated server just for you.</p><div style='background:rgba(184,134,11,0.1);border:1px solid #b8860b;border-radius:12px;padding:25px;margin:25px 0;'><h3 style='color:#daa520;margin:0 0 15px;'>Your Dedicated Server</h3><p style='color:#ffffff;margin:5px 0;'><strong>Server:</strong> {server_name}</p><p style='color:#ffffff;margin:5px 0;'><strong>IP:</strong> {server_ip}</p><p style='color:#ffffff;margin:5px 0;'><strong>Location:</strong> St. Louis, US-Central</p></div><p style='color:#aaaaaa;'>Your dedicated support line is available 24/7. Simply reply to this email for priority assistance.</p><a href='{dashboard_url}' style='display:inline-block;margin:20px 0;padding:15px 40px;background:linear-gradient(90deg,#b8860b,#daa520);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;'>Access VIP Dashboard</a></td></tr>", '#0a0a0f', '#daa520');
}
