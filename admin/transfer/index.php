<?php
/**
 * TrueVault VPN - Business Transfer Admin Panel
 * Created: January 19, 2026
 * Purpose: Enable 30-minute business ownership transfer via GUI
 * 
 * This panel allows:
 * - Update all business settings (PayPal, Email, Servers)
 * - Test each connection before transfer
 * - Add/remove servers
 * - Complete transfer with verification
 * - Emergency rollback if needed
 */

session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // For development, allow access. In production, redirect to login
    // header('Location: /admin/login.php');
    // exit;
}

// Database connection
$db_path = __DIR__ . '/../../databases/vpn.db';
$db = new SQLite3($db_path);

// Ensure business_settings table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS business_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type TEXT CHECK(setting_type IN ('text', 'email', 'password', 'url', 'boolean', 'number')),
        is_encrypted BOOLEAN DEFAULT 0,
        category TEXT CHECK(category IN ('general', 'payment', 'email', 'server', 'transfer')),
        display_name TEXT NOT NULL,
        description TEXT,
        requires_verification BOOLEAN DEFAULT 0,
        verification_status TEXT CHECK(verification_status IN ('pending', 'verified', 'failed', NULL)),
        last_verified DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_by TEXT
    )
");

// Ensure audit table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS business_settings_audit (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT NOT NULL,
        old_value TEXT,
        new_value TEXT,
        changed_by TEXT,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address TEXT,
        change_reason TEXT
    )
");

// Check if default settings exist
$count = $db->querySingle("SELECT COUNT(*) FROM business_settings");
if ($count == 0) {
    // Insert default settings
    $defaults = [
        // General
        ['business_name', 'TrueVault VPN', 'text', 0, 'general', 'Business Name', 'Name shown to customers', 0],
        ['owner_name', 'Kah-Len Halonen', 'text', 0, 'general', 'Owner Name', 'Current business owner', 0],
        ['business_domain', 'vpn.the-truth-publishing.com', 'url', 0, 'general', 'Business Domain', 'Website domain', 1],
        
        // Payment
        ['paypal_client_id', '', 'text', 0, 'payment', 'PayPal Client ID', 'PayPal API Client ID', 1],
        ['paypal_secret', '', 'password', 1, 'payment', 'PayPal Secret Key', 'PayPal API Secret', 1],
        ['paypal_webhook_id', '', 'text', 0, 'payment', 'PayPal Webhook ID', 'Webhook identifier', 1],
        ['paypal_account_email', '', 'email', 0, 'payment', 'PayPal Account Email', 'Business account email', 0],
        
        // Email
        ['customer_email', '', 'email', 0, 'email', 'Customer Email', 'Email for customer communications', 1],
        ['customer_email_password', '', 'password', 1, 'email', 'Email Password', 'SMTP/IMAP password', 1],
        ['smtp_server', 'the-truth-publishing.com', 'text', 0, 'email', 'SMTP Server', 'Outgoing mail server', 1],
        ['smtp_port', '465', 'number', 0, 'email', 'SMTP Port', 'SMTP port', 0],
        ['imap_server', 'the-truth-publishing.com', 'text', 0, 'email', 'IMAP Server', 'Incoming mail server', 0],
        ['imap_port', '993', 'number', 0, 'email', 'IMAP Port', 'IMAP port', 0],
        ['email_from_name', 'TrueVault VPN Team', 'text', 0, 'email', 'From Name', 'Sender name', 0],
        
        // Server
        ['server_provider_email', '', 'email', 0, 'server', 'Server Provider Email', 'Contabo/Fly.io notifications', 0],
        ['server_root_password', '', 'password', 1, 'server', 'Server Root Password', 'Standard root password', 0],
        
        // Transfer
        ['transfer_mode_active', '0', 'boolean', 0, 'transfer', 'Transfer Mode', 'Is transfer in progress', 0],
        ['setup_complete', '1', 'boolean', 0, 'transfer', 'Setup Complete', 'Initial setup done', 0],
        ['previous_owner', '', 'text', 0, 'transfer', 'Previous Owner', 'Previous owner name', 0],
        ['transfer_date', '', 'text', 0, 'transfer', 'Transfer Date', 'Date of transfer', 0]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO business_settings 
        (setting_key, setting_value, setting_type, is_encrypted, category, display_name, description, requires_verification)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($defaults as $setting) {
        $stmt->bindValue(1, $setting[0]);
        $stmt->bindValue(2, $setting[1]);
        $stmt->bindValue(3, $setting[2]);
        $stmt->bindValue(4, $setting[3]);
        $stmt->bindValue(5, $setting[4]);
        $stmt->bindValue(6, $setting[5]);
        $stmt->bindValue(7, $setting[6]);
        $stmt->bindValue(8, $setting[7]);
        $stmt->execute();
        $stmt->reset();
    }
}

// Get all settings
function getSettings($db) {
    $settings = [];
    $result = $db->query("SELECT * FROM business_settings ORDER BY category, id");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $settings[$row['setting_key']] = $row;
    }
    return $settings;
}

// Get servers
function getServers($db) {
    $servers = [];
    // Check if servers table exists
    $tableExists = $db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='servers'");
    if ($tableExists) {
        $result = $db->query("SELECT * FROM servers ORDER BY location");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $servers[] = $row;
        }
    }
    return $servers;
}

$settings = getSettings($db);
$servers = getServers($db);

// Calculate progress
function calculateProgress($settings) {
    $checks = [
        'business' => !empty($settings['business_name']['setting_value']) && !empty($settings['owner_name']['setting_value']),
        'payment' => !empty($settings['paypal_client_id']['setting_value']) && !empty($settings['paypal_account_email']['setting_value']),
        'email' => !empty($settings['customer_email']['setting_value']) && !empty($settings['smtp_server']['setting_value']),
        'server' => !empty($settings['server_provider_email']['setting_value']),
        'verified' => false // Will check verification statuses
    ];
    
    // Check verifications
    $verified_count = 0;
    $required_verifications = 0;
    foreach ($settings as $key => $setting) {
        if ($setting['requires_verification']) {
            $required_verifications++;
            if ($setting['verification_status'] === 'verified') {
                $verified_count++;
            }
        }
    }
    
    $checks['verified'] = $required_verifications > 0 && $verified_count === $required_verifications;
    
    $complete = array_filter($checks);
    return [
        'percentage' => round((count($complete) / count($checks)) * 100),
        'checks' => $checks
    ];
}

$progress = calculateProgress($settings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Transfer - TrueVault VPN Admin</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Header -->
    <header class="transfer-header">
        <div class="header-left">
            <span class="icon">🔄</span>
            <div>
                <h1>Business Transfer</h1>
                <span class="subtitle">Complete ownership transfer in 30 minutes</span>
            </div>
        </div>
        <div class="header-right">
            <a href="/admin/" class="btn-back">
                <span>←</span>
                <span>Back to Admin</span>
            </a>
        </div>
    </header>
    
    <div class="container">
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-title">
                <h3>Transfer Progress</h3>
                <span class="percentage"><?php echo $progress['percentage']; ?>%</span>
            </div>
            <div class="progress-bar-outer">
                <div class="progress-bar-inner" style="width: <?php echo $progress['percentage']; ?>%"></div>
            </div>
            <div class="progress-steps">
                <div class="progress-step <?php echo $progress['checks']['business'] ? 'completed' : ''; ?>">
                    <div class="step-icon"><?php echo $progress['checks']['business'] ? '✓' : '1'; ?></div>
                    <span class="step-label">Business Info</span>
                </div>
                <div class="progress-step <?php echo $progress['checks']['payment'] ? 'completed' : ''; ?>">
                    <div class="step-icon"><?php echo $progress['checks']['payment'] ? '✓' : '2'; ?></div>
                    <span class="step-label">Payment</span>
                </div>
                <div class="progress-step <?php echo $progress['checks']['email'] ? 'completed' : ''; ?>">
                    <div class="step-icon"><?php echo $progress['checks']['email'] ? '✓' : '3'; ?></div>
                    <span class="step-label">Email</span>
                </div>
                <div class="progress-step <?php echo $progress['checks']['server'] ? 'completed' : ''; ?>">
                    <div class="step-icon"><?php echo $progress['checks']['server'] ? '✓' : '4'; ?></div>
                    <span class="step-label">Servers</span>
                </div>
                <div class="progress-step <?php echo $progress['checks']['verified'] ? 'completed' : ''; ?>">
                    <div class="step-icon"><?php echo $progress['checks']['verified'] ? '✓' : '5'; ?></div>
                    <span class="step-label">Verify</span>
                </div>
            </div>
        </div>
        
        <!-- SECTION 1: Business Information -->
        <section class="section" id="section-business">
            <div class="section-header">
                <h2><span class="icon">🏢</span> Section 1: Business Information</h2>
                <span class="section-status <?php echo $progress['checks']['business'] ? 'verified' : 'pending'; ?>">
                    <?php echo $progress['checks']['business'] ? '✓ Complete' : '⏳ Pending'; ?>
                </span>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Business Name <span class="required">*</span></label>
                    <input type="text" id="business_name" name="business_name" 
                           value="<?php echo htmlspecialchars($settings['business_name']['setting_value'] ?? ''); ?>"
                           placeholder="e.g., TrueVault VPN">
                    <span class="hint">Name displayed to customers throughout the site</span>
                </div>
                
                <div class="form-group">
                    <label>Owner Name <span class="required">*</span></label>
                    <input type="text" id="owner_name" name="owner_name" 
                           value="<?php echo htmlspecialchars($settings['owner_name']['setting_value'] ?? ''); ?>"
                           placeholder="e.g., John Smith">
                    <span class="hint">New owner's full name</span>
                </div>
                
                <div class="form-group">
                    <label>Business Domain <span class="required">*</span></label>
                    <input type="url" id="business_domain" name="business_domain" 
                           value="<?php echo htmlspecialchars($settings['business_domain']['setting_value'] ?? ''); ?>"
                           placeholder="e.g., vpn.example.com">
                    <span class="hint">Primary website domain (no https://)</span>
                </div>
                
                <div class="form-group">
                    <label>Support Contact Email</label>
                    <input type="email" id="support_email" name="support_email" 
                           value="<?php echo htmlspecialchars($settings['customer_email']['setting_value'] ?? ''); ?>"
                           placeholder="support@example.com">
                    <span class="hint">Where customers will send support requests</span>
                </div>
            </div>
            
            <div class="section-actions">
                <button type="button" class="btn btn-primary" onclick="saveSection('business')">
                    <span>💾</span> Save Business Info
                </button>
            </div>
        </section>
        
        <!-- SECTION 2: Payment Configuration -->
        <section class="section" id="section-payment">
            <div class="section-header">
                <h2><span class="icon">💳</span> Section 2: Payment Configuration</h2>
                <span class="section-status <?php echo $progress['checks']['payment'] ? 'verified' : 'pending'; ?>">
                    <?php echo $progress['checks']['payment'] ? '✓ Complete' : '⏳ Pending'; ?>
                </span>
            </div>
            
            <div class="info-box">
                <span class="icon">ℹ️</span>
                <div>
                    <h4>PayPal Business Account Required</h4>
                    <p>You need a PayPal Business account with API access enabled. The webhook URL must point to: 
                       <code>https://<?php echo htmlspecialchars($settings['business_domain']['setting_value'] ?? 'your-domain.com'); ?>/api/paypal-webhook.php</code></p>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>PayPal Client ID <span class="required">*</span></label>
                    <input type="text" id="paypal_client_id" name="paypal_client_id" 
                           value="<?php echo htmlspecialchars($settings['paypal_client_id']['setting_value'] ?? ''); ?>"
                           placeholder="Your PayPal Client ID">
                    <span class="hint">From PayPal Developer Dashboard > Apps & Credentials</span>
                </div>
                
                <div class="form-group">
                    <label>PayPal Secret Key <span class="required">*</span></label>
                    <div class="input-group">
                        <input type="password" id="paypal_secret" name="paypal_secret" 
                               value="<?php echo $settings['paypal_secret']['setting_value'] ? '••••••••' : ''; ?>"
                               placeholder="Your PayPal Secret">
                        <button type="button" class="btn-test" onclick="togglePassword('paypal_secret')">👁️</button>
                    </div>
                    <span class="hint">Keep this secret! Never share with anyone.</span>
                </div>
                
                <div class="form-group">
                    <label>PayPal Webhook ID <span class="required">*</span></label>
                    <input type="text" id="paypal_webhook_id" name="paypal_webhook_id" 
                           value="<?php echo htmlspecialchars($settings['paypal_webhook_id']['setting_value'] ?? ''); ?>"
                           placeholder="Webhook ID from PayPal">
                    <span class="hint">Created automatically when you add the webhook URL</span>
                </div>
                
                <div class="form-group">
                    <label>PayPal Account Email <span class="required">*</span></label>
                    <input type="email" id="paypal_account_email" name="paypal_account_email" 
                           value="<?php echo htmlspecialchars($settings['paypal_account_email']['setting_value'] ?? ''); ?>"
                           placeholder="your-business@email.com">
                    <span class="hint">The email address of your PayPal Business account</span>
                </div>
            </div>
            
            <div class="section-actions">
                <button type="button" class="btn btn-secondary" onclick="testPayPal()">
                    <span>🧪</span> Test PayPal Connection
                </button>
                <button type="button" class="btn btn-primary" onclick="saveSection('payment')">
                    <span>💾</span> Save Payment Settings
                </button>
            </div>
        </section>
        
        <!-- SECTION 3: Customer Email -->
        <section class="section" id="section-email">
            <div class="section-header">
                <h2><span class="icon">📧</span> Section 3: Customer Email Configuration</h2>
                <span class="section-status <?php echo $progress['checks']['email'] ? 'verified' : 'pending'; ?>">
                    <?php echo $progress['checks']['email'] ? '✓ Complete' : '⏳ Pending'; ?>
                </span>
            </div>
            
            <div class="info-box">
                <span class="icon">ℹ️</span>
                <div>
                    <h4>Email Setup Required</h4>
                    <p>This email will send all customer communications: welcome emails, receipts, password resets, etc. 
                       You need SMTP access from your email provider.</p>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Customer Email Address <span class="required">*</span></label>
                    <input type="email" id="customer_email" name="customer_email" 
                           value="<?php echo htmlspecialchars($settings['customer_email']['setting_value'] ?? ''); ?>"
                           placeholder="admin@your-domain.com">
                    <span class="hint">Email address for customer communications</span>
                </div>
                
                <div class="form-group">
                    <label>Email Password <span class="required">*</span></label>
                    <div class="input-group">
                        <input type="password" id="customer_email_password" name="customer_email_password" 
                               value="<?php echo $settings['customer_email_password']['setting_value'] ? '••••••••' : ''; ?>"
                               placeholder="Email password">
                        <button type="button" class="btn-test" onclick="togglePassword('customer_email_password')">👁️</button>
                    </div>
                    <span class="hint">SMTP/IMAP password</span>
                </div>
                
                <div class="form-group">
                    <label>SMTP Server <span class="required">*</span></label>
                    <input type="text" id="smtp_server" name="smtp_server" 
                           value="<?php echo htmlspecialchars($settings['smtp_server']['setting_value'] ?? ''); ?>"
                           placeholder="mail.your-domain.com">
                    <span class="hint">Outgoing mail server (usually same as domain)</span>
                </div>
                
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" id="smtp_port" name="smtp_port" 
                           value="<?php echo htmlspecialchars($settings['smtp_port']['setting_value'] ?? '465'); ?>"
                           placeholder="465">
                    <span class="hint">Usually 465 (SSL) or 587 (TLS)</span>
                </div>
                
                <div class="form-group">
                    <label>IMAP Server</label>
                    <input type="text" id="imap_server" name="imap_server" 
                           value="<?php echo htmlspecialchars($settings['imap_server']['setting_value'] ?? ''); ?>"
                           placeholder="mail.your-domain.com">
                    <span class="hint">For receiving emails (support tickets)</span>
                </div>
                
                <div class="form-group">
                    <label>IMAP Port</label>
                    <input type="number" id="imap_port" name="imap_port" 
                           value="<?php echo htmlspecialchars($settings['imap_port']['setting_value'] ?? '993'); ?>"
                           placeholder="993">
                    <span class="hint">Usually 993 for SSL</span>
                </div>
                
                <div class="form-group">
                    <label>From Name</label>
                    <input type="text" id="email_from_name" name="email_from_name" 
                           value="<?php echo htmlspecialchars($settings['email_from_name']['setting_value'] ?? ''); ?>"
                           placeholder="Your Business Team">
                    <span class="hint">Name shown in customer's inbox</span>
                </div>
            </div>
            
            <div class="section-actions">
                <button type="button" class="btn btn-secondary" onclick="testSMTP()">
                    <span>📤</span> Test Email Sending
                </button>
                <button type="button" class="btn btn-secondary" onclick="testIMAP()">
                    <span>📥</span> Test Email Receiving
                </button>
                <button type="button" class="btn btn-primary" onclick="saveSection('email')">
                    <span>💾</span> Save Email Settings
                </button>
            </div>
        </section>
        
        <!-- SECTION 4: Server Configuration -->
        <section class="section" id="section-server">
            <div class="section-header">
                <h2><span class="icon">🖥️</span> Section 4: Server Configuration</h2>
                <span class="section-status <?php echo $progress['checks']['server'] ? 'verified' : 'pending'; ?>">
                    <?php echo $progress['checks']['server'] ? '✓ Complete' : '⏳ Pending'; ?>
                </span>
            </div>
            
            <div class="info-box">
                <span class="icon">ℹ️</span>
                <div>
                    <h4>Server Access</h4>
                    <p>These credentials are used for auto-provisioning dedicated servers. 
                       The system will use your server provider account to purchase and configure new VPS instances.</p>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Server Provider Email</label>
                    <input type="email" id="server_provider_email" name="server_provider_email" 
                           value="<?php echo htmlspecialchars($settings['server_provider_email']['setting_value'] ?? ''); ?>"
                           placeholder="your-contabo-account@email.com">
                    <span class="hint">Email for Contabo/Fly.io notifications (receives provisioning emails)</span>
                </div>
                
                <div class="form-group">
                    <label>Standard Server Root Password</label>
                    <div class="input-group">
                        <input type="password" id="server_root_password" name="server_root_password" 
                               value="<?php echo $settings['server_root_password']['setting_value'] ? '••••••••' : ''; ?>"
                               placeholder="Root password for all VPS">
                        <button type="button" class="btn-test" onclick="togglePassword('server_root_password')">👁️</button>
                    </div>
                    <span class="hint">This password will be set on all newly provisioned servers</span>
                </div>
            </div>
            
            <div class="section-actions">
                <button type="button" class="btn btn-primary" onclick="saveSection('server')">
                    <span>💾</span> Save Server Settings
                </button>
            </div>
        </section>
        
        <!-- SECTION 5: Server Migration -->
        <section class="section" id="section-migration">
            <div class="section-header">
                <h2><span class="icon">🔄</span> Section 5: Server Migration</h2>
                <span class="section-status pending">
                    ⏳ <?php echo count($servers); ?> Servers
                </span>
            </div>
            
            <div class="warning-box">
                <span class="icon">⚠️</span>
                <div>
                    <h4>Server Migration Required</h4>
                    <p>The current owner's servers will be terminated after transfer. 
                       You must add your own VPS servers before completing the transfer. 
                       VIP server (St. Louis - seige235@yahoo.com) is excluded from transfer.</p>
                </div>
            </div>
            
            <h3 style="margin-bottom: 15px;">Current Servers (Previous Owner)</h3>
            <div class="server-list" id="currentServers">
                <?php if (empty($servers)): ?>
                <p style="color: var(--text-secondary); padding: 20px;">No servers configured yet.</p>
                <?php else: ?>
                    <?php foreach ($servers as $server): ?>
                    <div class="server-item old" data-server-id="<?php echo $server['id']; ?>">
                        <div class="server-info">
                            <div class="server-icon">🖥️</div>
                            <div class="server-details">
                                <h4><?php echo htmlspecialchars($server['name'] ?? $server['location']); ?></h4>
                                <span class="meta"><?php echo htmlspecialchars($server['ip_address']); ?> • <?php echo htmlspecialchars($server['provider'] ?? 'Unknown'); ?></span>
                            </div>
                        </div>
                        <div class="server-actions">
                            <?php if (!empty($server['vip_email'])): ?>
                            <span class="server-badge vip">⭐ VIP (Not Transferable)</span>
                            <?php else: ?>
                            <label class="server-badge marked">
                                <input type="checkbox" class="mark-removal" data-server="<?php echo $server['id']; ?>"> Mark for Removal
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <h3 style="margin: 25px 0 15px;">Add New Servers (New Owner)</h3>
            <div class="server-list" id="newServers">
                <p style="color: var(--text-secondary); padding: 20px;">No new servers added yet. Click button below to add your servers.</p>
            </div>
            
            <div class="section-actions">
                <button type="button" class="btn btn-success" onclick="showAddServerModal()">
                    <span>➕</span> Add New Server
                </button>
            </div>
        </section>
        
        <!-- SECTION 6: Verification -->
        <section class="section" id="section-verification">
            <div class="section-header">
                <h2><span class="icon">✅</span> Section 6: Verification</h2>
                <span class="section-status <?php echo $progress['checks']['verified'] ? 'verified' : 'pending'; ?>">
                    <?php echo $progress['checks']['verified'] ? '✓ All Verified' : '⏳ Verification Required'; ?>
                </span>
            </div>
            
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                All items must be verified (green checkmark) before completing the transfer.
            </p>
            
            <div class="verification-list">
                <div class="verification-item pending" id="verify-paypal">
                    <div class="verification-icon">⏳</div>
                    <div class="verification-content">
                        <h4>PayPal Connection</h4>
                        <span class="status">Not tested</span>
                    </div>
                    <button class="verification-action" onclick="testPayPal()">Test Now</button>
                </div>
                
                <div class="verification-item pending" id="verify-smtp">
                    <div class="verification-icon">⏳</div>
                    <div class="verification-content">
                        <h4>Email Sending (SMTP)</h4>
                        <span class="status">Not tested</span>
                    </div>
                    <button class="verification-action" onclick="testSMTP()">Test Now</button>
                </div>
                
                <div class="verification-item pending" id="verify-imap">
                    <div class="verification-icon">⏳</div>
                    <div class="verification-content">
                        <h4>Email Receiving (IMAP)</h4>
                        <span class="status">Not tested</span>
                    </div>
                    <button class="verification-action" onclick="testIMAP()">Test Now</button>
                </div>
                
                <div class="verification-item pending" id="verify-servers-new">
                    <div class="verification-icon">⏳</div>
                    <div class="verification-content">
                        <h4>At Least 1 New Server Added</h4>
                        <span class="status">No new servers</span>
                    </div>
                    <button class="verification-action" onclick="showAddServerModal()">Add Server</button>
                </div>
                
                <div class="verification-item pending" id="verify-servers-old">
                    <div class="verification-icon">⏳</div>
                    <div class="verification-content">
                        <h4>Old Servers Marked for Removal</h4>
                        <span class="status">Not marked</span>
                    </div>
                    <button class="verification-action" onclick="scrollToSection('section-migration')">Review Servers</button>
                </div>
                
                <div class="verification-item pending" id="verify-ssh">
                    <div class="verification-icon">⏳</div>
                    <div class="verification-content">
                        <h4>SSH Access to New Servers</h4>
                        <span class="status">Not tested</span>
                    </div>
                    <button class="verification-action" onclick="testSSH()">Test SSH</button>
                </div>
                
                <div class="verification-item pending" id="verify-webhook">
                    <div class="verification-icon">⏳</div>
                    <div class="verification-content">
                        <h4>PayPal Webhook URL Configured</h4>
                        <span class="status">Not verified</span>
                    </div>
                    <button class="verification-action" onclick="verifyWebhook()">Verify</button>
                </div>
                
                <div class="verification-item pending" id="verify-dns">
                    <div class="verification-icon">⏳</div>
                    <div class="verification-content">
                        <h4>DNS Propagation</h4>
                        <span class="status">Not checked</span>
                    </div>
                    <button class="verification-action" onclick="checkDNS()">Check DNS</button>
                </div>
            </div>
            
            <div class="section-actions">
                <button type="button" class="btn btn-primary" onclick="runAllVerifications()">
                    <span>🔍</span> Run All Verifications
                </button>
            </div>
        </section>
        
        <!-- SECTION 7: Complete Transfer -->
        <section class="section final-section" id="section-complete">
            <div class="section-header">
                <h2><span class="icon">🚀</span> Section 7: Complete Transfer</h2>
            </div>
            
            <div class="warning-box">
                <span class="icon">⚠️</span>
                <div>
                    <h4>This Action Cannot Be Undone Easily</h4>
                    <p>Completing the transfer will disconnect the previous owner's PayPal, email, and servers. 
                       An emergency rollback is available but should only be used within 24 hours of transfer.</p>
                </div>
            </div>
            
            <div class="confirmation-list">
                <div class="confirmation-item">
                    <input type="checkbox" id="confirm1">
                    <label for="confirm1">I understand the previous owner's servers will be removed from this system</label>
                </div>
                <div class="confirmation-item">
                    <input type="checkbox" id="confirm2">
                    <label for="confirm2">I understand the previous owner's PayPal will be disconnected</label>
                </div>
                <div class="confirmation-item">
                    <input type="checkbox" id="confirm3">
                    <label for="confirm3">I have verified all my settings are correct and tested</label>
                </div>
                <div class="confirmation-item">
                    <input type="checkbox" id="confirm4">
                    <label for="confirm4">I have at least one VPS server ready to accept customer connections</label>
                </div>
                <div class="confirmation-item">
                    <input type="checkbox" id="confirm5">
                    <label for="confirm5">I accept full responsibility for this business from this point forward</label>
                </div>
            </div>
            
            <div class="transfer-buttons">
                <button type="button" class="btn btn-danger" id="btnCompleteTransfer" onclick="completeTransfer()" disabled>
                    <span>🔒</span> Complete Transfer
                </button>
                <button type="button" class="btn btn-secondary" onclick="showRollbackModal()">
                    <span>↩️</span> Emergency Rollback
                </button>
            </div>
        </section>
    </div>
    
    <!-- Add Server Modal -->
    <div class="modal-overlay" id="addServerModal">
        <div class="modal">
            <div class="modal-header">
                <h3>➕ Add New Server</h3>
                <button class="modal-close" onclick="closeModal('addServerModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Server IP Address <span class="required">*</span></label>
                    <input type="text" id="new_server_ip" placeholder="e.g., 192.168.1.100">
                </div>
                <div class="form-group">
                    <label>Server Name <span class="required">*</span></label>
                    <input type="text" id="new_server_name" placeholder="e.g., New York 1">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" id="new_server_location" placeholder="e.g., New York, USA">
                </div>
                <div class="form-group">
                    <label>Provider</label>
                    <select id="new_server_provider">
                        <option value="Contabo">Contabo</option>
                        <option value="Fly.io">Fly.io</option>
                        <option value="DigitalOcean">DigitalOcean</option>
                        <option value="Linode">Linode</option>
                        <option value="Vultr">Vultr</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>SSH Port</label>
                    <input type="number" id="new_server_ssh_port" value="22" placeholder="22">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addServerModal')">Cancel</button>
                <button class="btn btn-success" onclick="addNewServer()">
                    <span>🧪</span> Test & Add Server
                </button>
            </div>
        </div>
    </div>
    
    <!-- Rollback Modal -->
    <div class="modal-overlay" id="rollbackModal">
        <div class="modal">
            <div class="modal-header">
                <h3>⚠️ Emergency Rollback</h3>
                <button class="modal-close" onclick="closeModal('rollbackModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="warning-box">
                    <span class="icon">🚨</span>
                    <div>
                        <h4>Rollback Warning</h4>
                        <p>This will restore all previous owner settings and reconnect their servers. 
                           Only use this if the transfer has failed and you need to restore service immediately.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label>Reason for Rollback <span class="required">*</span></label>
                    <textarea id="rollback_reason" rows="3" placeholder="Explain why rollback is needed..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('rollbackModal')">Cancel</button>
                <button class="btn btn-danger" onclick="executeRollback()">
                    <span>↩️</span> Execute Rollback
                </button>
            </div>
        </div>
    </div>

    <script>
    // Toast notification system
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<span>${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span> ${message}`;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
    
    // Toggle password visibility
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        field.type = field.type === 'password' ? 'text' : 'password';
    }
    
    // Modal functions
    function showAddServerModal() {
        document.getElementById('addServerModal').classList.add('active');
    }
    
    function showRollbackModal() {
        document.getElementById('rollbackModal').classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    // Scroll to section
    function scrollToSection(sectionId) {
        document.getElementById(sectionId).scrollIntoView({ behavior: 'smooth' });
    }
    
    // Save section data
    async function saveSection(section) {
        const data = {};
        
        switch(section) {
            case 'business':
                data.business_name = document.getElementById('business_name').value;
                data.owner_name = document.getElementById('owner_name').value;
                data.business_domain = document.getElementById('business_domain').value;
                break;
            case 'payment':
                data.paypal_client_id = document.getElementById('paypal_client_id').value;
                data.paypal_secret = document.getElementById('paypal_secret').value;
                data.paypal_webhook_id = document.getElementById('paypal_webhook_id').value;
                data.paypal_account_email = document.getElementById('paypal_account_email').value;
                break;
            case 'email':
                data.customer_email = document.getElementById('customer_email').value;
                data.customer_email_password = document.getElementById('customer_email_password').value;
                data.smtp_server = document.getElementById('smtp_server').value;
                data.smtp_port = document.getElementById('smtp_port').value;
                data.imap_server = document.getElementById('imap_server').value;
                data.imap_port = document.getElementById('imap_port').value;
                data.email_from_name = document.getElementById('email_from_name').value;
                break;
            case 'server':
                data.server_provider_email = document.getElementById('server_provider_email').value;
                data.server_root_password = document.getElementById('server_root_password').value;
                break;
        }
        
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'save_settings', section: section, data: data })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(`${section.charAt(0).toUpperCase() + section.slice(1)} settings saved successfully!`, 'success');
                updateProgress();
            } else {
                showToast(result.error || 'Failed to save settings', 'error');
            }
        } catch (error) {
            showToast('Error saving settings: ' + error.message, 'error');
        }
    }
    
    // Test functions
    async function testPayPal() {
        updateVerificationStatus('verify-paypal', 'testing', 'Testing...');
        
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'test_paypal',
                    client_id: document.getElementById('paypal_client_id').value,
                    secret: document.getElementById('paypal_secret').value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                updateVerificationStatus('verify-paypal', 'success', 'Connected successfully');
                showToast('PayPal connection verified!', 'success');
            } else {
                updateVerificationStatus('verify-paypal', 'failed', result.error || 'Connection failed');
                showToast('PayPal test failed: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            updateVerificationStatus('verify-paypal', 'failed', 'Error: ' + error.message);
            showToast('PayPal test error: ' + error.message, 'error');
        }
    }
    
    async function testSMTP() {
        updateVerificationStatus('verify-smtp', 'testing', 'Testing...');
        
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'test_smtp',
                    email: document.getElementById('customer_email').value,
                    password: document.getElementById('customer_email_password').value,
                    server: document.getElementById('smtp_server').value,
                    port: document.getElementById('smtp_port').value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                updateVerificationStatus('verify-smtp', 'success', 'Email sending works');
                showToast('SMTP test email sent successfully!', 'success');
            } else {
                updateVerificationStatus('verify-smtp', 'failed', result.error || 'Failed');
                showToast('SMTP test failed: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            updateVerificationStatus('verify-smtp', 'failed', 'Error: ' + error.message);
            showToast('SMTP test error: ' + error.message, 'error');
        }
    }
    
    async function testIMAP() {
        updateVerificationStatus('verify-imap', 'testing', 'Testing...');
        
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'test_imap',
                    email: document.getElementById('customer_email').value,
                    password: document.getElementById('customer_email_password').value,
                    server: document.getElementById('imap_server').value,
                    port: document.getElementById('imap_port').value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                updateVerificationStatus('verify-imap', 'success', 'Email receiving works');
                showToast('IMAP connection verified!', 'success');
            } else {
                updateVerificationStatus('verify-imap', 'failed', result.error || 'Failed');
                showToast('IMAP test failed: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            updateVerificationStatus('verify-imap', 'failed', 'Error: ' + error.message);
            showToast('IMAP test error: ' + error.message, 'error');
        }
    }
    
    async function testSSH() {
        updateVerificationStatus('verify-ssh', 'testing', 'Testing...');
        showToast('Testing SSH connections to new servers...', 'info');
        
        // This would test SSH to all new servers added
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'test_ssh' })
            });
            
            const result = await response.json();
            
            if (result.success) {
                updateVerificationStatus('verify-ssh', 'success', 'SSH access verified');
                showToast('SSH connections verified!', 'success');
            } else {
                updateVerificationStatus('verify-ssh', 'failed', result.error || 'Failed');
                showToast('SSH test failed: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            updateVerificationStatus('verify-ssh', 'failed', 'Error: ' + error.message);
        }
    }
    
    async function verifyWebhook() {
        updateVerificationStatus('verify-webhook', 'testing', 'Verifying...');
        
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'verify_webhook',
                    webhook_id: document.getElementById('paypal_webhook_id').value
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                updateVerificationStatus('verify-webhook', 'success', 'Webhook configured');
                showToast('PayPal webhook verified!', 'success');
            } else {
                updateVerificationStatus('verify-webhook', 'failed', result.error || 'Not configured');
                showToast('Webhook verification failed: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            updateVerificationStatus('verify-webhook', 'failed', 'Error: ' + error.message);
        }
    }
    
    async function checkDNS() {
        updateVerificationStatus('verify-dns', 'testing', 'Checking...');
        
        const domain = document.getElementById('business_domain').value;
        
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'check_dns',
                    domain: domain
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                updateVerificationStatus('verify-dns', 'success', 'DNS propagated');
                showToast('DNS propagation confirmed!', 'success');
            } else {
                updateVerificationStatus('verify-dns', 'failed', result.error || 'Not propagated');
                showToast('DNS check: ' + (result.error || 'May take 24-48 hours'), 'warning');
            }
        } catch (error) {
            updateVerificationStatus('verify-dns', 'failed', 'Error: ' + error.message);
        }
    }
    
    function updateVerificationStatus(elementId, status, message) {
        const item = document.getElementById(elementId);
        const icon = item.querySelector('.verification-icon');
        const statusText = item.querySelector('.status');
        
        item.className = 'verification-item ' + status;
        
        switch(status) {
            case 'testing':
                icon.innerHTML = '⏳';
                break;
            case 'success':
                icon.innerHTML = '✓';
                break;
            case 'failed':
                icon.innerHTML = '✗';
                break;
            default:
                icon.innerHTML = '⏳';
        }
        
        statusText.textContent = message;
    }
    
    async function runAllVerifications() {
        showToast('Running all verifications...', 'info');
        
        await testPayPal();
        await new Promise(r => setTimeout(r, 500));
        await testSMTP();
        await new Promise(r => setTimeout(r, 500));
        await testIMAP();
        await new Promise(r => setTimeout(r, 500));
        await testSSH();
        await new Promise(r => setTimeout(r, 500));
        await verifyWebhook();
        await new Promise(r => setTimeout(r, 500));
        await checkDNS();
        
        showToast('All verifications complete!', 'success');
    }
    
    // Add new server
    async function addNewServer() {
        const serverData = {
            ip: document.getElementById('new_server_ip').value,
            name: document.getElementById('new_server_name').value,
            location: document.getElementById('new_server_location').value,
            provider: document.getElementById('new_server_provider').value,
            ssh_port: document.getElementById('new_server_ssh_port').value || 22
        };
        
        if (!serverData.ip || !serverData.name) {
            showToast('Please enter server IP and name', 'error');
            return;
        }
        
        showToast('Testing server connection...', 'info');
        
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add_server', server: serverData })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Server added successfully!', 'success');
                closeModal('addServerModal');
                
                // Add to UI
                const newServersList = document.getElementById('newServers');
                const html = `
                    <div class="server-item new" data-server-id="${result.server_id}">
                        <div class="server-info">
                            <div class="server-icon">🖥️</div>
                            <div class="server-details">
                                <h4>${serverData.name}</h4>
                                <span class="meta">${serverData.ip} • ${serverData.provider}</span>
                            </div>
                        </div>
                        <div class="server-actions">
                            <button class="btn btn-danger btn-sm" onclick="removeNewServer(${result.server_id})">Remove</button>
                        </div>
                    </div>
                `;
                
                if (newServersList.querySelector('p')) {
                    newServersList.innerHTML = '';
                }
                newServersList.insertAdjacentHTML('beforeend', html);
                
                updateVerificationStatus('verify-servers-new', 'success', 'Server(s) added');
                
                // Clear form
                document.getElementById('new_server_ip').value = '';
                document.getElementById('new_server_name').value = '';
                document.getElementById('new_server_location').value = '';
            } else {
                showToast('Failed to add server: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            showToast('Error adding server: ' + error.message, 'error');
        }
    }
    
    async function removeNewServer(serverId) {
        if (!confirm('Remove this server?')) return;
        
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remove_server', server_id: serverId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.querySelector(`[data-server-id="${serverId}"]`).remove();
                showToast('Server removed', 'success');
            }
        } catch (error) {
            showToast('Error removing server', 'error');
        }
    }
    
    // Update progress
    async function updateProgress() {
        try {
            const response = await fetch('verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_progress' })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.querySelector('.percentage').textContent = result.percentage + '%';
                document.querySelector('.progress-bar-inner').style.width = result.percentage + '%';
            }
        } catch (error) {
            console.error('Error updating progress:', error);
        }
    }
    
    // Confirmation checkbox handling
    const confirmBoxes = document.querySelectorAll('.confirmation-item input[type="checkbox"]');
    const btnCompleteTransfer = document.getElementById('btnCompleteTransfer');
    
    confirmBoxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const allChecked = Array.from(confirmBoxes).every(cb => cb.checked);
            btnCompleteTransfer.disabled = !allChecked;
        });
    });
    
    // Complete transfer
    async function completeTransfer() {
        if (!confirm('Are you absolutely sure you want to complete this transfer? This action will disconnect the previous owner.')) {
            return;
        }
        
        if (!confirm('FINAL CONFIRMATION: Click OK to complete the ownership transfer.')) {
            return;
        }
        
        btnCompleteTransfer.disabled = true;
        btnCompleteTransfer.innerHTML = '<span class="spinner"></span> Processing Transfer...';
        
        try {
            const response = await fetch('process-transfer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'complete_transfer' })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('🎉 Transfer completed successfully!', 'success');
                
                // Show success message
                document.querySelector('.container').innerHTML = `
                    <div class="success-message">
                        <div class="icon">🎉</div>
                        <h2>Transfer Complete!</h2>
                        <p>Congratulations! You are now the owner of this business.</p>
                        <p>The previous owner has been disconnected from all systems.</p>
                        <a href="/admin/" class="btn btn-primary">Go to Admin Dashboard</a>
                    </div>
                `;
            } else {
                showToast('Transfer failed: ' + (result.error || 'Unknown error'), 'error');
                btnCompleteTransfer.disabled = false;
                btnCompleteTransfer.innerHTML = '<span>🔒</span> Complete Transfer';
            }
        } catch (error) {
            showToast('Error during transfer: ' + error.message, 'error');
            btnCompleteTransfer.disabled = false;
            btnCompleteTransfer.innerHTML = '<span>🔒</span> Complete Transfer';
        }
    }
    
    // Emergency rollback
    async function executeRollback() {
        const reason = document.getElementById('rollback_reason').value;
        
        if (!reason.trim()) {
            showToast('Please provide a reason for the rollback', 'error');
            return;
        }
        
        if (!confirm('WARNING: This will restore previous owner settings. Are you sure?')) {
            return;
        }
        
        try {
            const response = await fetch('rollback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'rollback', reason: reason })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Rollback completed. Previous settings restored.', 'success');
                closeModal('rollbackModal');
                location.reload();
            } else {
                showToast('Rollback failed: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            showToast('Error during rollback: ' + error.message, 'error');
        }
    }
    
    // Close modal on outside click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
    
    // Initial progress update
    updateProgress();
    </script>
</body>
</html>
