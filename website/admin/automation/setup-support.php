<?php
/**
 * TrueVault VPN - Support Automation Database Setup
 * Task 17.6: 5-Tier Support Failsafe System Tables
 * Created: January 24, 2026
 * 
 * Creates tables for:
 * - support_tickets (enhanced for tiered system)
 * - ticket_responses (conversation thread)
 * - canned_responses (pre-written replies)
 * - self_service_actions (portal capabilities)
 * - ticket_escalations (escalation history)
 * 
 * Also seeds:
 * - 25+ Knowledge Base entries
 * - 20+ Canned Responses
 * - 9 Self-Service Actions
 */

// Run only once - check for existing tables
$dbPath = __DIR__ . '/databases/automation.db';
$db = new SQLite3($dbPath);
$db->enableExceptions(true);

echo "<h1>TrueVault Support Automation Setup</h1>\n";
echo "<pre>\n";

// ============================================================
// TABLE 1: support_tickets
// ============================================================
echo "Creating support_tickets table...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS support_tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_number TEXT UNIQUE,
        customer_id INTEGER,
        customer_email TEXT NOT NULL,
        customer_name TEXT,
        subject TEXT NOT NULL,
        message TEXT NOT NULL,
        category TEXT,
        priority TEXT DEFAULT 'normal',
        status TEXT DEFAULT 'new',
        tier_resolved INTEGER,
        resolution_method TEXT,
        assigned_to TEXT,
        is_vip INTEGER DEFAULT 0,
        auto_resolution_id INTEGER,
        canned_response_id INTEGER,
        self_service_action TEXT,
        customer_rating INTEGER,
        response_count INTEGER DEFAULT 0,
        first_response_at TEXT,
        resolved_at TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
");

// Create indexes
$db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_number ON support_tickets(ticket_number)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_email ON support_tickets(customer_email)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_status ON support_tickets(status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_priority ON support_tickets(priority)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_vip ON support_tickets(is_vip)");
echo "✓ support_tickets table created with indexes\n";

// ============================================================
// TABLE 2: ticket_responses
// ============================================================
echo "Creating ticket_responses table...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS ticket_responses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER NOT NULL,
        sender_type TEXT NOT NULL,
        message TEXT NOT NULL,
        is_auto_response INTEGER DEFAULT 0,
        canned_response_id INTEGER,
        attachments TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
    )
");
$db->exec("CREATE INDEX IF NOT EXISTS idx_responses_ticket ON ticket_responses(ticket_id)");
echo "✓ ticket_responses table created\n";

// ============================================================
// TABLE 3: canned_responses
// ============================================================
echo "Creating canned_responses table...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS canned_responses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category TEXT NOT NULL,
        title TEXT NOT NULL,
        trigger_keywords TEXT,
        subject TEXT,
        body TEXT NOT NULL,
        variables TEXT,
        times_used INTEGER DEFAULT 0,
        success_rate REAL DEFAULT 0.0,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
");
$db->exec("CREATE INDEX IF NOT EXISTS idx_canned_category ON canned_responses(category)");
echo "✓ canned_responses table created\n";

// ============================================================
// TABLE 4: self_service_actions
// ============================================================
echo "Creating self_service_actions table...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS self_service_actions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        action_key TEXT UNIQUE NOT NULL,
        display_name TEXT NOT NULL,
        description TEXT,
        trigger_keywords TEXT,
        portal_url TEXT NOT NULL,
        instructions TEXT,
        category TEXT,
        is_active INTEGER DEFAULT 1,
        times_used INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
");
echo "✓ self_service_actions table created\n";

// ============================================================
// TABLE 5: ticket_escalations
// ============================================================
echo "Creating ticket_escalations table...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS ticket_escalations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER NOT NULL,
        from_tier INTEGER,
        to_tier INTEGER,
        reason TEXT,
        escalated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
    )
");
echo "✓ ticket_escalations table created\n";

// ============================================================
// SEED: 9 Self-Service Actions
// ============================================================
echo "\nSeeding self_service_actions...\n";

$selfServiceActions = [
    [
        'action_key' => 'reset_password',
        'display_name' => 'Reset Password',
        'description' => 'Change your account password securely',
        'trigger_keywords' => 'password,forgot,login,sign in,locked out,reset,can\'t login',
        'portal_url' => '/self-service/reset-password',
        'instructions' => '1. Click the button below\n2. Enter your current password (or use email verification)\n3. Enter your new password twice\n4. Click Save',
        'category' => 'account'
    ],
    [
        'action_key' => 'download_configs',
        'display_name' => 'Download VPN Configs',
        'description' => 'Get configuration files for all your devices',
        'trigger_keywords' => 'config,download,setup,install,wireguard,ovpn,configuration,file',
        'portal_url' => '/self-service/download-configs',
        'instructions' => '1. Select your device type\n2. Choose your preferred server\n3. Click Download\n4. Import the file into your VPN app',
        'category' => 'technical'
    ],
    [
        'action_key' => 'view_invoices',
        'display_name' => 'View Invoices',
        'description' => 'Access your billing history and download receipts',
        'trigger_keywords' => 'invoice,receipt,billing,payment history,statement,charge',
        'portal_url' => '/self-service/view-invoices',
        'instructions' => '1. View all past invoices\n2. Click any invoice to see details\n3. Download PDF for your records',
        'category' => 'billing'
    ],
    [
        'action_key' => 'update_payment',
        'display_name' => 'Update Payment Method',
        'description' => 'Change your credit card or payment method',
        'trigger_keywords' => 'card,payment method,billing,credit card,update payment,new card',
        'portal_url' => '/self-service/update-payment',
        'instructions' => '1. Click to open PayPal\n2. Update your payment method\n3. Changes apply to your next billing cycle',
        'category' => 'billing'
    ],
    [
        'action_key' => 'view_devices',
        'display_name' => 'View Connected Devices',
        'description' => 'See all devices connected to your account',
        'trigger_keywords' => 'devices,connected,sessions,logged in,active devices',
        'portal_url' => '/self-service/view-devices',
        'instructions' => '1. View all registered devices\n2. See last connection time\n3. Remove devices you no longer use',
        'category' => 'account'
    ],
    [
        'action_key' => 'regenerate_keys',
        'display_name' => 'Regenerate WireGuard Keys',
        'description' => 'Generate fresh encryption keys for enhanced security',
        'trigger_keywords' => 'key,regenerate,keypair,certificate,new key,expired',
        'portal_url' => '/self-service/regenerate-keys',
        'instructions' => '1. Click Regenerate Keys\n2. Download new config files\n3. Re-import on all your devices',
        'category' => 'technical'
    ],
    [
        'action_key' => 'pause_subscription',
        'display_name' => 'Pause Subscription',
        'description' => 'Temporarily pause your subscription (up to 30 days)',
        'trigger_keywords' => 'pause,hold,temporary,vacation,freeze',
        'portal_url' => '/self-service/pause-subscription',
        'instructions' => '1. Select pause duration (up to 30 days)\n2. Confirm pause\n3. Your subscription resumes automatically',
        'category' => 'billing'
    ],
    [
        'action_key' => 'cancel_subscription',
        'display_name' => 'Cancel Subscription',
        'description' => 'Cancel your subscription (we\'re sorry to see you go!)',
        'trigger_keywords' => 'cancel,stop,end,unsubscribe,terminate',
        'portal_url' => '/self-service/cancel-subscription',
        'instructions' => '1. Tell us why you\'re leaving (optional)\n2. Confirm cancellation\n3. Access continues until billing period ends',
        'category' => 'billing'
    ],
    [
        'action_key' => 'connection_test',
        'display_name' => 'Run Connection Test',
        'description' => 'Diagnose VPN connection issues automatically',
        'trigger_keywords' => 'not working,can\'t connect,connection,troubleshoot,diagnose,test,issue',
        'portal_url' => '/self-service/connection-test',
        'instructions' => '1. Click Start Test\n2. Follow the automated diagnostics\n3. Get personalized fix recommendations',
        'category' => 'technical'
    ]
];

foreach ($selfServiceActions as $action) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO self_service_actions 
        (action_key, display_name, description, trigger_keywords, portal_url, instructions, category) 
        VALUES (:key, :name, :desc, :keywords, :url, :instructions, :category)");
    $stmt->bindValue(':key', $action['action_key'], SQLITE3_TEXT);
    $stmt->bindValue(':name', $action['display_name'], SQLITE3_TEXT);
    $stmt->bindValue(':desc', $action['description'], SQLITE3_TEXT);
    $stmt->bindValue(':keywords', $action['trigger_keywords'], SQLITE3_TEXT);
    $stmt->bindValue(':url', $action['portal_url'], SQLITE3_TEXT);
    $stmt->bindValue(':instructions', $action['instructions'], SQLITE3_TEXT);
    $stmt->bindValue(':category', $action['category'], SQLITE3_TEXT);
    $stmt->execute();
}
echo "✓ 9 self-service actions seeded\n";

// ============================================================
// SEED: 20+ Canned Responses
// ============================================================
echo "\nSeeding canned_responses...\n";

$cannedResponses = [
    // BILLING (5)
    [
        'category' => 'billing',
        'title' => 'Payment Retry Instructions',
        'trigger_keywords' => 'payment,failed,declined,card,retry',
        'subject' => 'Re: Payment Issue - Quick Fix',
        'body' => '<p>Hi {first_name},</p>
<p>I see your payment didn\'t go through. Here are some quick fixes:</p>
<ol>
<li><strong>Check card expiration</strong> - Make sure your card hasn\'t expired</li>
<li><strong>Verify funds</strong> - Ensure sufficient balance</li>
<li><strong>Update billing address</strong> - Must match card statement</li>
<li><strong>Try a different card</strong> - <a href="{dashboard_url}/billing">Update payment method</a></li>
</ol>
<p>Once updated, your payment will retry automatically within 24 hours.</p>
<p>Let me know if you need any help!</p>',
        'variables' => '["first_name", "dashboard_url"]'
    ],
    [
        'category' => 'billing',
        'title' => 'Refund Confirmation',
        'trigger_keywords' => 'refund,money back,refunded',
        'subject' => 'Re: Your Refund Has Been Processed',
        'body' => '<p>Hi {first_name},</p>
<p>Great news! Your refund of <strong>{amount}</strong> has been processed.</p>
<p>Please allow 3-5 business days for the funds to appear in your account, depending on your bank.</p>
<p>We\'re sorry to see you go. If you ever want to come back, we\'ll be here!</p>',
        'variables' => '["first_name", "amount"]'
    ],
    [
        'category' => 'billing',
        'title' => 'Plan Upgrade Confirmation',
        'trigger_keywords' => 'upgrade,upgraded,new plan',
        'subject' => 'Re: Welcome to {new_plan}!',
        'body' => '<p>Hi {first_name},</p>
<p>Awesome! You\'ve been upgraded to <strong>{new_plan}</strong>! 🎉</p>
<p>Your new benefits are active immediately:</p>
<ul>
<li>Increased device limit</li>
<li>Priority support</li>
<li>Access to premium servers</li>
</ul>
<p>Your account has been prorated - you only pay the difference for this billing cycle.</p>
<p>Enjoy your upgrade!</p>',
        'variables' => '["first_name", "new_plan"]'
    ],
    [
        'category' => 'billing',
        'title' => 'Invoice Resent',
        'trigger_keywords' => 'invoice,resend,receipt,email invoice',
        'subject' => 'Re: Your Invoice #{invoice_number}',
        'body' => '<p>Hi {first_name},</p>
<p>I\'ve resent your invoice <strong>#{invoice_number}</strong> to {email}.</p>
<p>You can also access all your invoices anytime at: <a href="{dashboard_url}/billing">Billing History</a></p>
<p>Let me know if you need anything else!</p>',
        'variables' => '["first_name", "invoice_number", "email", "dashboard_url"]'
    ],
    [
        'category' => 'billing',
        'title' => 'Promo Code Applied',
        'trigger_keywords' => 'promo,code,discount,coupon,applied',
        'subject' => 'Re: Promo Code Applied! 🎉',
        'body' => '<p>Hi {first_name},</p>
<p>Great news! Your promo code <strong>{promo_code}</strong> has been applied!</p>
<p>You saved <strong>{discount_amount}</strong> on your subscription.</p>
<p>The discount will appear on your next invoice.</p>
<p>Enjoy!</p>',
        'variables' => '["first_name", "promo_code", "discount_amount"]'
    ],
    
    // TECHNICAL (8)
    [
        'category' => 'technical',
        'title' => 'Server Switching Guide',
        'trigger_keywords' => 'server,switch,slow,speed,different server',
        'subject' => 'Re: Try a Different Server',
        'body' => '<p>Hi {first_name},</p>
<p>Let\'s try switching to a different server for better performance:</p>
<ol>
<li>Open the TrueVault app</li>
<li>Tap the current server name</li>
<li>Choose a server closer to your location</li>
<li>Click Connect</li>
</ol>
<p><strong>Pro tip:</strong> Servers with lower user counts usually perform better!</p>
<p>Let me know if this helps!</p>',
        'variables' => '["first_name"]'
    ],
    [
        'category' => 'technical',
        'title' => 'Clear Cache & Reinstall',
        'trigger_keywords' => 'reinstall,clear,cache,fresh,clean install',
        'subject' => 'Re: Fresh Start - Reinstall Guide',
        'body' => '<p>Hi {first_name},</p>
<p>Let\'s do a clean reinstall to fix this:</p>
<ol>
<li><strong>Uninstall</strong> the TrueVault app completely</li>
<li><strong>Restart</strong> your device</li>
<li><strong>Download fresh</strong> from <a href="{dashboard_url}/download">your dashboard</a></li>
<li><strong>Login</strong> with your credentials</li>
</ol>
<p>This clears any corrupted settings and usually fixes most issues!</p>',
        'variables' => '["first_name", "dashboard_url"]'
    ],
    [
        'category' => 'technical',
        'title' => 'Firewall/Antivirus Check',
        'trigger_keywords' => 'firewall,antivirus,blocked,security software',
        'subject' => 'Re: Check Your Security Software',
        'body' => '<p>Hi {first_name},</p>
<p>Your security software might be blocking the VPN. Here\'s how to fix it:</p>
<p><strong>Windows Defender:</strong></p>
<ol>
<li>Settings → Update & Security → Windows Security</li>
<li>Firewall & network protection → Allow an app</li>
<li>Add TrueVault to the allowed list</li>
</ol>
<p><strong>Other Antivirus:</strong> Add TrueVault to the "exceptions" or "whitelist"</p>
<p>Let me know which software you\'re using if you need specific steps!</p>',
        'variables' => '["first_name"]'
    ],
    [
        'category' => 'technical',
        'title' => 'Protocol Change Guide',
        'trigger_keywords' => 'protocol,wireguard,openvpn,ikev2,change protocol',
        'subject' => 'Re: Try a Different Protocol',
        'body' => '<p>Hi {first_name},</p>
<p>Let\'s try switching VPN protocols:</p>
<ol>
<li>Open Settings in the TrueVault app</li>
<li>Go to Connection → Protocol</li>
<li>Try this order:
    <ul>
    <li><strong>WireGuard</strong> - Fastest, most modern</li>
    <li><strong>IKEv2</strong> - Best for mobile</li>
    <li><strong>OpenVPN UDP</strong> - Most compatible</li>
    </ul>
</li>
</ol>
<p>WireGuard works best for most people!</p>',
        'variables' => '["first_name"]'
    ],
    [
        'category' => 'technical',
        'title' => 'Speed Test Instructions',
        'trigger_keywords' => 'speed,test,slow,bandwidth,performance',
        'subject' => 'Re: Let\'s Test Your Speed',
        'body' => '<p>Hi {first_name},</p>
<p>Let\'s diagnose your speed issue:</p>
<ol>
<li><strong>Disconnect VPN</strong> and run <a href="https://speedtest.net">speedtest.net</a></li>
<li>Note your download/upload speeds</li>
<li><strong>Connect VPN</strong> to nearest server</li>
<li>Run speedtest again</li>
</ol>
<p>Share both results with me and I\'ll help optimize your connection!</p>
<p><strong>Expected:</strong> VPN should be 70-90% of your base speed on WireGuard.</p>',
        'variables' => '["first_name"]'
    ],
    [
        'category' => 'technical',
        'title' => 'Router Reset Guide',
        'trigger_keywords' => 'router,reset,modem,network,home network',
        'subject' => 'Re: Router Reset May Help',
        'body' => '<p>Hi {first_name},</p>
<p>A quick router reset often fixes connection issues:</p>
<ol>
<li>Unplug your router power</li>
<li>Wait 30 seconds</li>
<li>Plug it back in</li>
<li>Wait 2-3 minutes for full restart</li>
<li>Try connecting again</li>
</ol>
<p>This clears your router\'s cache and often fixes VPN connectivity!</p>',
        'variables' => '["first_name"]'
    ],
    [
        'category' => 'technical',
        'title' => 'DNS Leak Fix',
        'trigger_keywords' => 'dns,leak,exposed,ip leak',
        'subject' => 'Re: DNS Leak Protection',
        'body' => '<p>Hi {first_name},</p>
<p>Let\'s fix that DNS leak:</p>
<ol>
<li>Go to <a href="{dashboard_url}/security">Dashboard → Security</a></li>
<li>Run the built-in leak test</li>
<li>Enable <strong>DNS Leak Protection</strong></li>
<li>Enable <strong>Kill Switch</strong> for extra safety</li>
</ol>
<p>These settings ensure your real IP is never exposed!</p>',
        'variables' => '["first_name", "dashboard_url"]'
    ],
    [
        'category' => 'technical',
        'title' => 'Kill Switch Enable',
        'trigger_keywords' => 'kill switch,disconnect,drops,safety',
        'subject' => 'Re: Enable Kill Switch for Safety',
        'body' => '<p>Hi {first_name},</p>
<p>The Kill Switch is your safety net - it blocks internet if VPN drops:</p>
<ol>
<li>Open TrueVault app → Settings</li>
<li>Go to Security section</li>
<li>Toggle <strong>Kill Switch</strong> ON</li>
</ol>
<p>Now if your VPN ever disconnects, your real IP stays protected!</p>',
        'variables' => '["first_name"]'
    ],
    
    // ACCOUNT (5)
    [
        'category' => 'account',
        'title' => 'Password Reset Sent',
        'trigger_keywords' => 'password,reset,sent,email,link',
        'subject' => 'Re: Password Reset Link Sent',
        'body' => '<p>Hi {first_name},</p>
<p>I\'ve sent a password reset link to <strong>{email}</strong>.</p>
<p>A few things to note:</p>
<ul>
<li>Check your spam/junk folder</li>
<li>Link expires in 1 hour</li>
<li>If you don\'t receive it, try the <a href="{dashboard_url}/forgot-password">Forgot Password</a> page</li>
</ul>
<p>Let me know if you still can\'t get in!</p>',
        'variables' => '["first_name", "email", "dashboard_url"]'
    ],
    [
        'category' => 'account',
        'title' => '2FA Setup Guide',
        'trigger_keywords' => '2fa,authenticator,two factor,security',
        'subject' => 'Re: Setting Up Two-Factor Authentication',
        'body' => '<p>Hi {first_name},</p>
<p>Great choice enabling 2FA! Here\'s how:</p>
<ol>
<li>Download an authenticator app (Google Authenticator, Authy, etc.)</li>
<li>Go to <a href="{dashboard_url}/security">Dashboard → Security</a></li>
<li>Click "Enable 2FA"</li>
<li>Scan the QR code with your authenticator</li>
<li><strong>IMPORTANT:</strong> Save your backup codes somewhere safe!</li>
</ol>
<p>Once enabled, you\'ll need your phone to login.</p>',
        'variables' => '["first_name", "dashboard_url"]'
    ],
    [
        'category' => 'account',
        'title' => 'Device Limit Reached',
        'trigger_keywords' => 'device,limit,too many,maximum,remove device',
        'subject' => 'Re: Device Limit Reached',
        'body' => '<p>Hi {first_name},</p>
<p>You\'ve hit your device limit of <strong>{device_limit} devices</strong>.</p>
<p>To connect a new device:</p>
<ol>
<li>Go to <a href="{dashboard_url}/devices">Dashboard → Devices</a></li>
<li>Find devices you no longer use</li>
<li>Click "Remove" on old devices</li>
<li>Now you can add your new device!</li>
</ol>
<p>Need more devices? Consider upgrading your plan!</p>',
        'variables' => '["first_name", "device_limit", "dashboard_url"]'
    ],
    [
        'category' => 'account',
        'title' => 'Account Deletion Confirmed',
        'trigger_keywords' => 'delete,deletion,remove account,close account',
        'subject' => 'Re: Account Deletion Scheduled',
        'body' => '<p>Hi {first_name},</p>
<p>As requested, your account is scheduled for deletion.</p>
<p>Here\'s what happens next:</p>
<ul>
<li>Your data will be removed in 30 days</li>
<li>You can cancel deletion by logging in before then</li>
<li>After 30 days, this action cannot be undone</li>
</ul>
<p>We\'re sorry to see you go. If you ever want to come back, you\'re always welcome!</p>',
        'variables' => '["first_name"]'
    ],
    [
        'category' => 'account',
        'title' => 'Email Change Confirmed',
        'trigger_keywords' => 'email,change,updated,new email',
        'subject' => 'Re: Email Address Updated',
        'body' => '<p>Hi {first_name},</p>
<p>Your email has been changed to <strong>{new_email}</strong>.</p>
<p>A verification link has been sent to your new address. Please click it to confirm.</p>
<p>From now on, use your new email to login.</p>',
        'variables' => '["first_name", "new_email"]'
    ],
    
    // GENERAL (2)
    [
        'category' => 'general',
        'title' => 'Thank You for Patience',
        'trigger_keywords' => 'patience,waiting,delay,sorry',
        'subject' => 'Re: Thank You for Your Patience',
        'body' => '<p>Hi {first_name},</p>
<p>Thank you so much for your patience! 🙏</p>
<p>We\'re actively working on your issue and will update you as soon as possible.</p>
<p>We really appreciate your understanding!</p>',
        'variables' => '["first_name"]'
    ],
    [
        'category' => 'general',
        'title' => 'Escalation Notice',
        'trigger_keywords' => 'escalate,escalated,senior,specialist',
        'subject' => 'Re: Your Issue Has Been Escalated',
        'body' => '<p>Hi {first_name},</p>
<p>I\'ve escalated your issue to our senior support team for specialized attention.</p>
<p>You can expect to hear back within <strong>24 hours</strong>.</p>
<p>Your ticket number is <strong>{ticket_id}</strong> - feel free to reference this in any future communication.</p>
<p>We\'re on it!</p>',
        'variables' => '["first_name", "ticket_id"]'
    ]
];

foreach ($cannedResponses as $response) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO canned_responses 
        (category, title, trigger_keywords, subject, body, variables) 
        VALUES (:category, :title, :keywords, :subject, :body, :variables)");
    $stmt->bindValue(':category', $response['category'], SQLITE3_TEXT);
    $stmt->bindValue(':title', $response['title'], SQLITE3_TEXT);
    $stmt->bindValue(':keywords', $response['trigger_keywords'], SQLITE3_TEXT);
    $stmt->bindValue(':subject', $response['subject'], SQLITE3_TEXT);
    $stmt->bindValue(':body', $response['body'], SQLITE3_TEXT);
    $stmt->bindValue(':variables', $response['variables'], SQLITE3_TEXT);
    $stmt->execute();
}
echo "✓ 20 canned responses seeded\n";

// ============================================================
// SEED: 25+ Knowledge Base Entries (in knowledge_base table)
// ============================================================
echo "\nSeeding knowledge_base entries...\n";

$kbEntries = [
    // BILLING (6)
    ['billing', 'payment,failed,declined,card,error,charge', 'Why did my payment fail?', 
     'Common reasons: 1) Card expired - check expiration date 2) Insufficient funds - verify balance 3) Billing address mismatch - must match card statement 4) Bank blocked - call your bank. Update payment method in Dashboard > Billing.',
     '["Check card expiration date", "Verify sufficient funds", "Confirm billing address matches card", "Contact bank if still failing", "Update payment method in dashboard"]'],
    
    ['billing', 'refund,money back,cancel,guarantee', 'How do I get a refund?',
     'We offer a 30-day money-back guarantee. To request a refund: Dashboard > Billing > Request Refund. Refunds process within 3-5 business days.',
     '["Go to Dashboard", "Click Billing", "Click Request Refund", "Confirm refund request", "Wait 3-5 business days"]'],
    
    ['billing', 'change,plan,upgrade,downgrade,switch', 'How do I change my plan?',
     'You can upgrade or downgrade anytime: Dashboard > Account > Change Plan. Upgrades take effect immediately (prorated). Downgrades apply at next billing cycle.',
     '["Go to Dashboard", "Click Account", "Click Change Plan", "Select new plan", "Confirm change"]'],
    
    ['billing', 'invoice,receipt,history,statement', 'Where are my invoices?',
     'All invoices are available at Dashboard > Billing > Invoice History. You can view, download PDF, or have them resent to your email.',
     '["Go to Dashboard", "Click Billing", "Click Invoice History", "Download or resend as needed"]'],
    
    ['billing', 'price,pricing,cost,how much,plans', 'What are your prices?',
     'Our plans: Personal ($9.97/mo or $99.97/yr) - 5 devices, Family ($14.97/mo or $140.97/yr) - 10 devices + family sharing, Dedicated ($39.97/mo or $399.97/yr) - Unlimited + dedicated server.',
     '["Personal: $9.97/mo - 5 devices", "Family: $14.97/mo - 10 devices", "Dedicated: $39.97/mo - Unlimited + dedicated server"]'],
    
    ['billing', 'promo,coupon,code,discount', 'How do I use a promo code?',
     'Enter promo codes during checkout, or apply to existing account: Dashboard > Billing > Apply Promo Code. Discounts apply to your next billing cycle.',
     '["Go to Dashboard", "Click Billing", "Click Apply Promo Code", "Enter code", "Click Apply"]'],
    
    // TECHNICAL (8)
    ['technical', 'slow,speed,lag,performance,bandwidth', 'Why is my VPN slow?',
     'Try these fixes: 1) Switch to a closer server 2) Use WireGuard protocol (fastest) 3) Check your base internet speed without VPN 4) Restart your device. VPN should be 70-90% of base speed.',
     '["Switch to closer server", "Change to WireGuard protocol", "Test speed without VPN first", "Restart device", "Contact support if still slow"]'],
    
    ['technical', 'connect,can\'t,unable,error,timeout,fail', 'I can\'t connect to VPN',
     'Connection troubleshooting: 1) Check your internet works without VPN 2) Restart the TrueVault app 3) Try a different server 4) Switch protocol (WireGuard/OpenVPN) 5) Restart your device.',
     '["Verify internet works without VPN", "Restart TrueVault app", "Try different server", "Switch protocol in settings", "Restart device"]'],
    
    ['technical', 'leak,ip,dns,exposed,privacy', 'My IP/DNS is leaking',
     'Run our leak test at Dashboard > Security > Leak Test. To prevent leaks: 1) Enable Kill Switch 2) Enable DNS Leak Protection 3) Use WireGuard protocol. All settings in Dashboard > Security.',
     '["Run leak test in Dashboard", "Enable Kill Switch", "Enable DNS Leak Protection", "Use WireGuard protocol"]'],
    
    ['technical', 'kill switch,disconnect,drops', 'What is the kill switch?',
     'Kill Switch blocks all internet traffic if VPN connection drops - protecting your real IP from exposure. Enable it: App Settings > Security > Kill Switch ON.',
     '["Open app settings", "Go to Security", "Toggle Kill Switch ON"]'],
    
    ['technical', 'split,tunneling,exclude,bypass', 'How do I use split tunneling?',
     'Split tunneling lets you exclude apps from VPN. Available on Windows and Android only. Settings > Split Tunneling > Add apps you want to bypass VPN.',
     '["Open Settings", "Go to Split Tunneling", "Add apps to exclude", "Save changes"]'],
    
    ['technical', 'streaming,netflix,blocked,video', 'Streaming service not working',
     'For streaming issues: 1) Clear browser cookies/cache 2) Try streaming-optimized servers 3) Try a different region server 4) Use browser instead of app. Some services actively block VPNs.',
     '["Clear browser cookies", "Use streaming-optimized server", "Try different region", "Use web browser instead of app"]'],
    
    ['technical', 'protocol,wireguard,openvpn,ikev2', 'Which protocol should I use?',
     'Protocol recommendations: WireGuard = fastest and most modern (recommended), OpenVPN = most compatible with networks, IKEv2 = best for mobile devices. Switch in Settings > Protocol.',
     '["WireGuard for speed", "OpenVPN for compatibility", "IKEv2 for mobile"]'],
    
    ['technical', 'router,setup,whole house,home', 'How do I set up VPN on my router?',
     'Router setup protects all devices automatically. Dashboard > Setup Guides > Router. Note: This may reduce speeds and not all routers support VPN. Consider individual device apps for better performance.',
     '["Go to Dashboard", "Click Setup Guides", "Select Router", "Follow router-specific instructions"]'],
    
    // ACCOUNT (6)
    ['account', 'email,change,update', 'How do I change my email?',
     'Change email: Dashboard > Account Settings > Change Email. You\'ll need to verify the new email address. Old email will receive notification of change.',
     '["Go to Dashboard", "Click Account Settings", "Click Change Email", "Enter new email", "Verify via link sent"]'],
    
    ['account', 'password,reset,forgot,change', 'How do I reset my password?',
     'Two options: 1) If logged in: Dashboard > Account > Change Password 2) If locked out: Click "Forgot Password" on login page, enter email, click reset link sent to you.',
     '["If logged in: Dashboard > Account > Change Password", "If locked out: Use Forgot Password link", "Check email for reset link"]'],
    
    ['account', '2fa,two factor,authenticator,security', 'How do I enable two-factor authentication?',
     'Enable 2FA: Dashboard > Security > Two-Factor Authentication. Use Google Authenticator, Authy, or similar app. IMPORTANT: Save your backup codes - you need them if you lose your phone!',
     '["Download authenticator app", "Dashboard > Security > Enable 2FA", "Scan QR code", "Save backup codes securely"]'],
    
    ['account', 'delete,account,close,remove', 'How do I delete my account?',
     'To delete: 1) First cancel your subscription 2) Then Dashboard > Account Settings > Delete Account. Data is removed after 30 days. You can cancel deletion by logging in before then.',
     '["Cancel subscription first", "Go to Account Settings", "Click Delete Account", "Confirm deletion", "Data removed after 30 days"]'],
    
    ['account', 'device,limit,too many,maximum', 'I hit my device limit',
     'Device limits by plan: Personal=5, Family=10, Dedicated=Unlimited. To add new device: Dashboard > Devices > Remove unused devices first. Or upgrade your plan for more devices.',
     '["Check plan device limit", "Go to Dashboard > Devices", "Remove unused devices", "Or upgrade plan"]'],
    
    ['account', 'username,name,display,profile', 'How do I change my username?',
     'Change display name: Dashboard > Profile > Edit. This changes how your name appears in the dashboard. Your login email remains the same.',
     '["Go to Dashboard", "Click Profile", "Click Edit", "Change display name", "Save"]'],
    
    // SETUP (3)
    ['setup', 'install,download,start,begin', 'How do I install TrueVault?',
     'Easy setup: 1) Login to Dashboard 2) Click Download 3) Select your device type 4) Run installer 5) Login with your credentials 6) Click Connect!',
     '["Login to Dashboard", "Click Download", "Select device", "Run installer", "Login in app", "Click Connect"]'],
    
    ['setup', 'config,wireguard,file,import', 'How do I download config files?',
     'For manual setup or WireGuard app: Dashboard > Devices > Download Config. Select your device type and server. Import the .conf file into your WireGuard app.',
     '["Go to Dashboard", "Click Devices", "Click Download Config", "Select device and server", "Import into WireGuard app"]'],
    
    ['setup', 'first,connection,getting started', 'First time connecting',
     'Welcome! Quick start: 1) Download app from Dashboard 2) Install and open 3) Login with your email/password 4) Click Connect 5) Choose a server near you for best speed. You\'re protected!',
     '["Download from Dashboard", "Install app", "Login", "Click Connect", "Choose nearby server"]'],
    
    // GENERAL (2)
    ['general', 'what,vpn,how,work,explain', 'What is a VPN and how does it work?',
     'A VPN (Virtual Private Network) encrypts your internet traffic and masks your IP address. Benefits: 1) Privacy - hide browsing from ISP 2) Security - protect on public WiFi 3) Access - bypass geo-restrictions. All traffic goes through our secure servers.',
     '["Encrypts your traffic", "Masks your IP", "Protects on public WiFi", "Bypasses geo-restrictions"]'],
    
    ['general', 'log,logging,privacy,store,data', 'What do you log?',
     'Strict no-logs policy. We only store: email (for account), payment status (active/expired), and support tickets. We do NOT log: browsing history, IP addresses, connection times, bandwidth usage, or DNS queries.',
     '["We store: email, payment status", "We do NOT log: browsing, IPs, connections", "Strict no-logs policy"]']
];

foreach ($kbEntries as $entry) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO knowledge_base 
        (category, keywords, question, answer, resolution_steps) 
        VALUES (:category, :keywords, :question, :answer, :steps)");
    $stmt->bindValue(':category', $entry[0], SQLITE3_TEXT);
    $stmt->bindValue(':keywords', $entry[1], SQLITE3_TEXT);
    $stmt->bindValue(':question', $entry[2], SQLITE3_TEXT);
    $stmt->bindValue(':answer', $entry[3], SQLITE3_TEXT);
    $stmt->bindValue(':steps', $entry[4], SQLITE3_TEXT);
    $stmt->execute();
}
echo "✓ 25 knowledge base entries seeded\n";

// ============================================================
// SUMMARY
// ============================================================
echo "\n========================================\n";
echo "SUPPORT AUTOMATION SETUP COMPLETE!\n";
echo "========================================\n";
echo "Tables created: 5\n";
echo "- support_tickets\n";
echo "- ticket_responses\n";
echo "- canned_responses\n";
echo "- self_service_actions\n";
echo "- ticket_escalations\n";
echo "\nData seeded:\n";
echo "- 9 self-service actions\n";
echo "- 20 canned responses\n";
echo "- 25 knowledge base entries\n";
echo "\n</pre>";

$db->close();
