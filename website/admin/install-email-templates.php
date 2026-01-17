<?php
/**
 * TrueVault VPN - Email Templates Installer
 * Run ONCE to populate email_templates table, then DELETE this file
 * 
 * URL: /admin/install-email-templates.php
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/EmailTemplate.php';

// Simple auth check - must be admin
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Admin access required. <a href="/admin/">Login here</a>');
}

$template = new EmailTemplate();
$installed = 0;
$errors = [];

// ============================================
// TEMPLATE DEFINITIONS
// ============================================

$templates = [
    
    // ----------------------------------------
    // ONBOARDING TEMPLATES
    // ----------------------------------------
    
    [
        'name' => 'welcome_basic',
        'subject' => 'Welcome to TrueVault VPN!',
        'category' => 'onboarding',
        'body' => '
<h2>Welcome to TrueVault VPN, {first_name}!</h2>

<p>Thank you for joining TrueVault VPN. Your account is now active and ready to use.</p>

<div class="highlight">
    <strong>Your Account Details:</strong><br>
    Email: {email}<br>
    Plan: {plan_name}<br>
</div>

<h3>Getting Started</h3>

<p>Setting up your VPN is easy:</p>
<ol>
    <li>Log in to your <a href="{dashboard_url}">Dashboard</a></li>
    <li>Click "Add Device"</li>
    <li>Scan the QR code with WireGuard app (or download config file)</li>
    <li>Connect and enjoy secure browsing!</li>
</ol>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="btn">Go to Dashboard</a>
</p>

<p>Need help? Just reply to this email or visit our support page.</p>

<p>Welcome aboard!<br>
The TrueVault Team</p>'
    ],
    
    [
        'name' => 'welcome_formal',
        'subject' => 'Welcome to TrueVault VPN - Account Activated',
        'category' => 'onboarding',
        'body' => '
<h2>Welcome to TrueVault VPN</h2>

<p>Dear {first_name},</p>

<p>Thank you for choosing TrueVault VPN for your online privacy needs. Your account has been successfully created and is ready for use.</p>

<div class="highlight">
    <strong>Account Information</strong><br>
    Account Email: {email}<br>
    Subscription: {plan_name}<br>
    Activation Date: {date}
</div>

<h3>Quick Start Guide</h3>

<p>To begin using TrueVault VPN:</p>

<ol>
    <li><strong>Install WireGuard</strong> - Download from your device\'s app store</li>
    <li><strong>Access Your Dashboard</strong> - Log in at {dashboard_url}</li>
    <li><strong>Add Your Device</strong> - Click "Add Device" and follow the prompts</li>
    <li><strong>Connect</strong> - Scan QR code or import configuration file</li>
</ol>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="btn">Access Your Dashboard</a>
</p>

<h3>What\'s Included</h3>

<ul>
    <li>Military-grade encryption</li>
    <li>Multiple server locations</li>
    <li>Port forwarding capabilities</li>
    <li>24/7 customer support</li>
</ul>

<p>Should you have any questions, our support team is here to help.</p>

<p>Best regards,<br>
The TrueVault VPN Team</p>'
    ],
    
    // ----------------------------------------
    // PAYMENT TEMPLATES
    // ----------------------------------------
    
    [
        'name' => 'payment_success',
        'subject' => 'Payment Received - TrueVault VPN',
        'category' => 'billing',
        'body' => '
<h2>Payment Confirmed</h2>

<p>Hi {first_name},</p>

<p>We\'ve received your payment. Thank you!</p>

<div class="success">
    <strong>Payment Details:</strong><br>
    Amount: ${amount}<br>
    Date: {date}<br>
    Transaction ID: {transaction_id}
</div>

<p>Your subscription is active and your VPN access continues uninterrupted.</p>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="btn">View Your Account</a>
</p>

<p>Thanks for being a TrueVault customer!</p>

<p>Best,<br>
The TrueVault Team</p>'
    ],
    
    [
        'name' => 'payment_failed_reminder1',
        'subject' => 'Action Required: Payment Issue - TrueVault VPN',
        'category' => 'billing',
        'body' => '
<h2>Payment Update Needed</h2>

<p>Hi {first_name},</p>

<p>We tried to process your subscription payment, but it didn\'t go through.</p>

<div class="warning">
    <strong>Payment Details:</strong><br>
    Amount: ${amount}<br>
    Date Attempted: {date}
</div>

<p>This can happen for a few reasons:</p>
<ul>
    <li>Card expired or updated</li>
    <li>Insufficient funds</li>
    <li>Bank security hold</li>
</ul>

<p>Please update your payment method to keep your VPN access active:</p>

<p style="text-align: center;">
    <a href="{dashboard_url}billing" class="btn">Update Payment Method</a>
</p>

<p>If you\'ve already resolved this, you can ignore this email.</p>

<p>Questions? Just reply to this email.</p>

<p>Thanks,<br>
The TrueVault Team</p>'
    ],
    
    [
        'name' => 'payment_failed_reminder2',
        'subject' => 'Urgent: Your TrueVault VPN Access May Be Interrupted',
        'category' => 'billing',
        'body' => '
<h2>Important: Payment Still Pending</h2>

<p>Hi {first_name},</p>

<p>We still haven\'t been able to process your payment for TrueVault VPN.</p>

<div class="warning">
    <strong>Your access will be suspended in 4 days if payment is not received.</strong>
</div>

<p>Amount due: <strong>${amount}</strong></p>

<p>Please update your payment information as soon as possible:</p>

<p style="text-align: center;">
    <a href="{dashboard_url}billing" class="btn">Fix Payment Now</a>
</p>

<p>If you\'re having trouble, please reach out. We\'re happy to help.</p>

<p>Best,<br>
The TrueVault Team</p>'
    ],
    
    [
        'name' => 'payment_failed_final',
        'subject' => 'Final Notice: TrueVault VPN Subscription',
        'category' => 'billing',
        'body' => '
<h2>Final Payment Notice</h2>

<p>Hi {first_name},</p>

<p>This is our final reminder about your outstanding payment.</p>

<div class="warning">
    <strong>Your VPN access will be suspended tomorrow</strong> if we don\'t receive payment.
</div>

<p>Amount due: <strong>${amount}</strong></p>

<p style="text-align: center;">
    <a href="{dashboard_url}billing" class="btn">Pay Now</a>
</p>

<p>After suspension, you\'ll need to reactivate your account to restore access.</p>

<p>Please contact us if you need assistance or want to discuss options.</p>

<p>The TrueVault Team</p>'
    ],
    
    // ----------------------------------------
    // SUPPORT TEMPLATES
    // ----------------------------------------
    
    [
        'name' => 'ticket_received',
        'subject' => 'Support Ticket #{ticket_id} Received - TrueVault VPN',
        'category' => 'support',
        'body' => '
<h2>We Got Your Message</h2>

<p>Hi {first_name},</p>

<p>Thanks for contacting TrueVault support. We\'ve received your ticket and will respond as soon as possible.</p>

<div class="highlight">
    <strong>Ticket Details:</strong><br>
    Ticket #: {ticket_id}<br>
    Subject: {ticket_subject}<br>
    Submitted: {date} at {time}
</div>

<p>Our typical response time is within 24 hours. For urgent issues, we often respond much faster.</p>

<p>You can view your ticket status anytime:</p>

<p style="text-align: center;">
    <a href="{dashboard_url}support" class="btn">View Ticket Status</a>
</p>

<p>Thanks for your patience!</p>

<p>TrueVault Support Team</p>'
    ],
    
    [
        'name' => 'ticket_resolved',
        'subject' => 'Ticket #{ticket_id} Resolved - TrueVault VPN',
        'category' => 'support',
        'body' => '
<h2>Your Ticket Has Been Resolved</h2>

<p>Hi {first_name},</p>

<p>Good news! Your support ticket has been resolved.</p>

<div class="success">
    <strong>Ticket Details:</strong><br>
    Ticket #: {ticket_id}<br>
    Subject: {ticket_subject}<br>
    Status: Resolved
</div>

<p>If you have any follow-up questions or the issue persists, simply reply to this email or open a new ticket.</p>

<p style="text-align: center;">
    <a href="{dashboard_url}support" class="btn">View Ticket History</a>
</p>

<p>Thanks for using TrueVault VPN!</p>

<p>TrueVault Support Team</p>'
    ],
    
    // ----------------------------------------
    // COMPLAINT TEMPLATES
    // ----------------------------------------
    
    [
        'name' => 'complaint_acknowledge',
        'subject' => 'We\'re Sorry - TrueVault VPN',
        'category' => 'support',
        'body' => '
<h2>We Hear You</h2>

<p>Hi {first_name},</p>

<p>We\'re sorry to hear about your experience. Your feedback is important to us, and we take every complaint seriously.</p>

<p>We\'ve flagged your issue for priority review and will personally look into it.</p>

<div class="highlight">
    <strong>What happens next:</strong><br>
    • Your complaint has been escalated<br>
    • We\'re investigating the issue<br>
    • You\'ll hear back from us within 24 hours
</div>

<p>We truly value you as a customer and want to make this right.</p>

<p>Sincerely,<br>
The TrueVault Team</p>'
    ],
    
    // ----------------------------------------
    // SERVER ALERT TEMPLATES
    // ----------------------------------------
    
    [
        'name' => 'server_down',
        'subject' => 'Service Notice: TrueVault VPN',
        'category' => 'alerts',
        'body' => '
<h2>Service Interruption Notice</h2>

<p>Hi {first_name},</p>

<p>We\'re experiencing a temporary service interruption affecting the <strong>{server_name}</strong> server.</p>

<div class="warning">
    <strong>What\'s happening:</strong><br>
    Our team is actively working to restore service. Most issues are resolved within 30 minutes.
</div>

<h3>What you can do:</h3>
<ul>
    <li>Try connecting to a different server location</li>
    <li>Check your dashboard for real-time status updates</li>
</ul>

<p>We apologize for any inconvenience and will notify you when service is restored.</p>

<p>The TrueVault Team</p>'
    ],
    
    [
        'name' => 'server_restored',
        'subject' => 'Service Restored - TrueVault VPN',
        'category' => 'alerts',
        'body' => '
<h2>We\'re Back!</h2>

<p>Hi {first_name},</p>

<p>Good news! The <strong>{server_name}</strong> server is back online and operating normally.</p>

<div class="success">
    <strong>All systems operational.</strong><br>
    You can now connect as usual.
</div>

<p>We apologize for any inconvenience caused by the interruption.</p>

<p>If you experience any issues connecting, please try:</p>
<ol>
    <li>Disconnecting and reconnecting your VPN</li>
    <li>Restarting the WireGuard app</li>
</ol>

<p>Thanks for your patience!</p>

<p>The TrueVault Team</p>'
    ],
    
    // ----------------------------------------
    // RETENTION TEMPLATES
    // ----------------------------------------
    
    [
        'name' => 'cancellation_survey',
        'subject' => 'We\'re Sorry to See You Go - TrueVault VPN',
        'category' => 'retention',
        'body' => '
<h2>Your Feedback Matters</h2>

<p>Hi {first_name},</p>

<p>We noticed you\'ve requested to cancel your TrueVault VPN subscription. We\'re sorry to see you go.</p>

<p>Would you mind telling us why? Your feedback helps us improve.</p>

<div class="highlight">
    <strong>Quick survey (1 minute):</strong><br>
    • Was there a feature missing?<br>
    • Did you experience technical issues?<br>
    • Was pricing a concern?<br>
    • Did you find an alternative?
</div>

<p>Just reply to this email with your thoughts.</p>

<p>Your access continues until {end_date}. If you change your mind, you can reactivate anytime from your dashboard.</p>

<p>Thanks for giving us a try.</p>

<p>The TrueVault Team</p>'
    ],
    
    [
        'name' => 'retention_offer',
        'subject' => 'A Special Offer for You - TrueVault VPN',
        'category' => 'retention',
        'body' => '
<h2>Before You Go...</h2>

<p>Hi {first_name},</p>

<p>We noticed you\'re thinking about leaving TrueVault VPN. We\'d love to keep you as a customer.</p>

<div class="highlight">
    <strong>Special Offer:</strong><br>
    Stay with us and get <strong>50% off</strong> your next 3 months!
</div>

<p>That\'s just ${discounted_price}/month for your {plan_name} plan.</p>

<p style="text-align: center;">
    <a href="{dashboard_url}billing?offer=retention" class="btn">Claim Your Discount</a>
</p>

<p>This offer is only available for the next 48 hours.</p>

<p>We value your business and hope you\'ll give us another chance.</p>

<p>The TrueVault Team</p>'
    ],
    
    [
        'name' => 'winback_campaign',
        'subject' => 'We Miss You! Come Back to TrueVault VPN',
        'category' => 'retention',
        'body' => '
<h2>We\'ve Made Improvements!</h2>

<p>Hi {first_name},</p>

<p>It\'s been a while since we\'ve seen you. We\'ve been busy making TrueVault VPN even better.</p>

<h3>What\'s New:</h3>
<ul>
    <li>Faster server connections</li>
    <li>New server locations</li>
    <li>Improved port forwarding</li>
    <li>Better mobile experience</li>
</ul>

<div class="highlight">
    <strong>Welcome Back Offer:</strong><br>
    Rejoin today and get your first month for just <strong>$4.99</strong>!
</div>

<p style="text-align: center;">
    <a href="{login_url}?offer=winback" class="btn">Reactivate My Account</a>
</p>

<p>We hope to see you back!</p>

<p>The TrueVault Team</p>'
    ],
    
    // ----------------------------------------
    // ADMIN NOTIFICATION TEMPLATES
    // ----------------------------------------
    
    [
        'name' => 'admin_new_signup',
        'subject' => '[TrueVault] New Signup: {email}',
        'category' => 'admin',
        'body' => '
<h2>New User Signup</h2>

<p>A new user has registered:</p>

<div class="highlight">
    <strong>User Details:</strong><br>
    Email: {email}<br>
    Name: {first_name}<br>
    Plan: {plan_name}<br>
    Date: {date} at {time}
</div>

<p><a href="{admin_url}users">View in Admin Panel</a></p>'
    ],
    
    [
        'name' => 'admin_payment_received',
        'subject' => '[TrueVault] Payment Received: ${amount}',
        'category' => 'admin',
        'body' => '
<h2>Payment Received</h2>

<div class="success">
    <strong>Amount:</strong> ${amount}<br>
    <strong>User:</strong> {email}<br>
    <strong>Plan:</strong> {plan_name}<br>
    <strong>Transaction:</strong> {transaction_id}
</div>

<p><a href="{admin_url}billing">View in Admin Panel</a></p>'
    ],
    
    [
        'name' => 'admin_complaint',
        'subject' => '[URGENT] Customer Complaint: {email}',
        'category' => 'admin',
        'body' => '
<h2>Customer Complaint Received</h2>

<div class="warning">
    <strong>Requires Immediate Attention</strong>
</div>

<p><strong>From:</strong> {email} ({first_name})</p>
<p><strong>Subject:</strong> {ticket_subject}</p>

<p><strong>Message:</strong></p>
<div class="highlight">
    {message}
</div>

<p><a href="{admin_url}support">View Ticket</a></p>'
    ],
    
    [
        'name' => 'admin_server_alert',
        'subject' => '[ALERT] Server Issue: {server_name}',
        'category' => 'admin',
        'body' => '
<h2>Server Alert</h2>

<div class="warning">
    <strong>Server:</strong> {server_name}<br>
    <strong>IP:</strong> {server_ip}<br>
    <strong>Status:</strong> {status}<br>
    <strong>Time:</strong> {date} at {time}
</div>

<p>Please investigate immediately.</p>

<p><a href="{admin_url}servers">View Server Status</a></p>'
    ]
];

// ============================================
// INSTALL TEMPLATES
// ============================================

echo '<html><head><title>Install Email Templates</title>';
echo '<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { color: green; }
.error { color: red; }
h1 { color: #333; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f5f5f5; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
.warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
</style></head><body>';

echo '<h1>🔧 Email Templates Installer</h1>';

if (isset($_POST['install'])) {
    echo '<h2>Installing Templates...</h2>';
    echo '<table>';
    echo '<tr><th>Template</th><th>Category</th><th>Status</th></tr>';
    
    foreach ($templates as $t) {
        try {
            $result = $template->saveTemplate(
                $t['name'],
                $t['subject'],
                $t['body'],
                $t['category']
            );
            
            if ($result) {
                $installed++;
                echo "<tr><td>{$t['name']}</td><td>{$t['category']}</td><td class='success'>✓ Installed</td></tr>";
            } else {
                $errors[] = $t['name'];
                echo "<tr><td>{$t['name']}</td><td>{$t['category']}</td><td class='error'>✗ Failed</td></tr>";
            }
        } catch (Exception $e) {
            $errors[] = $t['name'] . ': ' . $e->getMessage();
            echo "<tr><td>{$t['name']}</td><td>{$t['category']}</td><td class='error'>✗ Error</td></tr>";
        }
    }
    
    echo '</table>';
    
    echo '<h2>Summary</h2>';
    echo "<p class='success'>✓ Successfully installed: {$installed} templates</p>";
    
    if (count($errors) > 0) {
        echo "<p class='error'>✗ Errors: " . count($errors) . "</p>";
        echo '<ul>';
        foreach ($errors as $err) {
            echo "<li class='error'>{$err}</li>";
        }
        echo '</ul>';
    }
    
    echo '<div class="warning">';
    echo '<strong>⚠️ IMPORTANT:</strong> Now delete this file for security!<br>';
    echo 'Path: <code>/admin/install-email-templates.php</code>';
    echo '</div>';
    
} else {
    echo '<p>This will install ' . count($templates) . ' email templates into the database.</p>';
    
    echo '<h3>Templates to Install:</h3>';
    echo '<table>';
    echo '<tr><th>Name</th><th>Subject</th><th>Category</th></tr>';
    
    foreach ($templates as $t) {
        echo "<tr><td>{$t['name']}</td><td>{$t['subject']}</td><td>{$t['category']}</td></tr>";
    }
    
    echo '</table>';
    
    echo '<form method="post">';
    echo '<button type="submit" name="install" class="btn">Install Templates</button>';
    echo '</form>';
}

echo '</body></html>';
