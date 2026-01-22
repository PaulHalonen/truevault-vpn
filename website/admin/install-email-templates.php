<?php
/**
 * TrueVault VPN - Email Templates Installer
 * Task 7.4 - Creates all 19 email templates
 * RUN ONCE then DELETE this file
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Install Email Templates</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #00d9ff; }
        .success { background: #155724; border: 1px solid #28a745; color: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .error { background: #721c24; border: 1px solid #dc3545; color: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .warning { background: #856404; border: 1px solid #ffc107; color: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>📧 Installing 19 Email Templates</h1>
    
<?php

$templates = [
    // ============================================
    // ONBOARDING TEMPLATES (3)
    // ============================================
    [
        'name' => 'welcome_basic',
        'display' => 'Welcome - Basic',
        'category' => 'onboarding',
        'subject' => 'Welcome to TrueVault VPN, {first_name}!',
        'body' => '
<h2>Welcome to TrueVault VPN! 🎉</h2>
<p>Hi {first_name},</p>
<p>Thank you for joining TrueVault VPN! Your account is now active and ready to use.</p>

<div class="highlight">
    <strong>Your Account Details:</strong><br>
    Email: {email}<br>
    Plan: {plan_name}<br>
</div>

<p>Here\'s how to get started:</p>
<ol>
    <li>Download the VPN config for your device</li>
    <li>Import it into your VPN client (WireGuard)</li>
    <li>Connect and enjoy secure browsing!</li>
</ol>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="btn">Go to Dashboard</a>
</p>

<p>If you have any questions, our support team is here to help!</p>

<p>Welcome aboard,<br>The TrueVault Team</p>'
    ],
    
    [
        'name' => 'welcome_formal',
        'display' => 'Welcome - Formal (Pro)',
        'category' => 'onboarding',
        'subject' => 'Welcome to TrueVault VPN Pro - Your Account is Ready',
        'body' => '
<h2>Welcome to TrueVault VPN Pro</h2>
<p>Dear {first_name},</p>
<p>Thank you for choosing TrueVault VPN Pro. We are pleased to confirm that your account has been successfully activated.</p>

<div class="info-box">
    <strong>Account Information</strong><br><br>
    <strong>Email:</strong> {email}<br>
    <strong>Plan:</strong> {plan_name}<br>
    <strong>Status:</strong> Active<br>
    <strong>Devices:</strong> Up to 5 devices
</div>

<h3>Pro Features Available to You:</h3>
<ul>
    <li>✓ All server locations worldwide</li>
    <li>✓ Priority bandwidth allocation</li>
    <li>✓ Advanced parental controls</li>
    <li>✓ Port forwarding capabilities</li>
    <li>✓ Priority customer support</li>
</ul>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="btn">Access Your Dashboard</a>
</p>

<p>Should you require any assistance, please do not hesitate to contact our support team.</p>

<p>Best regards,<br>The TrueVault VPN Team</p>'
    ],
    
    [
        'name' => 'welcome_vip',
        'display' => 'Welcome - VIP (Secret)',
        'category' => 'onboarding',
        'subject' => 'Your Premium Account is Ready - Welcome',
        'body' => '
<h2>Welcome to Your Premium Experience</h2>
<p>Dear {first_name},</p>
<p>We are honored to welcome you to our premium service tier. Your account has been configured with our highest level of service and dedicated resources.</p>

<div class="success-box">
    <strong>Premium Account Activated</strong><br><br>
    Your account includes dedicated server access, unlimited bandwidth, and priority support.
</div>

<h3>Your Premium Benefits:</h3>
<ul>
    <li>✓ Dedicated server resources</li>
    <li>✓ Unlimited bandwidth - no throttling</li>
    <li>✓ Direct support line</li>
    <li>✓ Unlimited devices</li>
    <li>✓ All features unlocked</li>
    <li>✓ Early access to new features</li>
</ul>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="btn">Access Your Dashboard</a>
</p>

<p>Thank you for your trust in our service. We are committed to providing you with an exceptional experience.</p>

<p>With appreciation,<br>The TrueVault Team</p>'
    ],
    
    // ============================================
    // PAYMENT TEMPLATES (5)
    // ============================================
    [
        'name' => 'payment_success_basic',
        'display' => 'Payment Success - Basic',
        'category' => 'payment',
        'subject' => 'Payment Received - Thank You!',
        'body' => '
<h2>Payment Confirmed! ✅</h2>
<p>Hi {first_name},</p>
<p>We\'ve received your payment. Thank you!</p>

<div class="success-box">
    <strong>Payment Details:</strong><br>
    Amount: ${amount}<br>
    Plan: {plan_name}<br>
    Invoice: {invoice_number}
</div>

<p>Your subscription is active and your service continues uninterrupted.</p>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="btn">View Dashboard</a>
</p>

<p>Thanks for being a TrueVault customer!</p>'
    ],
    
    [
        'name' => 'payment_success_formal',
        'display' => 'Payment Success - Formal',
        'category' => 'payment',
        'subject' => 'Payment Confirmation - Invoice #{invoice_number}',
        'body' => '
<h2>Payment Confirmation</h2>
<p>Dear {first_name},</p>
<p>This email confirms that we have successfully processed your payment.</p>

<div class="info-box">
    <strong>Transaction Details</strong><br><br>
    <strong>Invoice Number:</strong> {invoice_number}<br>
    <strong>Amount Paid:</strong> ${amount}<br>
    <strong>Plan:</strong> {plan_name}<br>
    <strong>Next Billing Date:</strong> {next_billing_date}
</div>

<p>Your subscription remains active. Thank you for your continued trust in TrueVault VPN.</p>

<p style="text-align: center;">
    <a href="{dashboard_url}/billing" class="btn">View Billing History</a>
</p>

<p>Best regards,<br>TrueVault Billing Department</p>'
    ],
    
    [
        'name' => 'payment_failed_reminder1',
        'display' => 'Payment Failed - Reminder 1 (Day 0)',
        'category' => 'payment',
        'subject' => 'Action Needed: Payment Issue with Your Account',
        'body' => '
<h2>Payment Issue Detected</h2>
<p>Hi {first_name},</p>
<p>We tried to process your payment but encountered an issue. Don\'t worry - your service is still active!</p>

<div class="warning-box">
    <strong>Payment Details:</strong><br>
    Amount: ${amount}<br>
    Invoice: {invoice_number}
</div>

<p>This can happen for several reasons:</p>
<ul>
    <li>Expired card</li>
    <li>Insufficient funds</li>
    <li>Bank security hold</li>
</ul>

<p>Please update your payment method to keep your service running smoothly.</p>

<p style="text-align: center;">
    <a href="{dashboard_url}/billing" class="btn">Update Payment Method</a>
</p>

<p>Need help? Just reply to this email!</p>'
    ],
    
    [
        'name' => 'payment_failed_reminder2',
        'display' => 'Payment Failed - Reminder 2 (Day 3)',
        'category' => 'payment',
        'subject' => 'Urgent: Your TrueVault VPN Service May Be Interrupted',
        'body' => '
<h2>⚠️ Service Interruption Warning</h2>
<p>Hi {first_name},</p>
<p>We still haven\'t been able to process your payment. Your service will be suspended soon if not resolved.</p>

<div class="warning-box">
    <strong>Outstanding Balance:</strong> ${amount}<br>
    <strong>Invoice:</strong> {invoice_number}<br>
    <strong>Days Overdue:</strong> 3 days
</div>

<p><strong>What happens next:</strong></p>
<ul>
    <li>Day 7: Final warning sent</li>
    <li>Day 8: Service suspended</li>
</ul>

<p style="text-align: center;">
    <a href="{dashboard_url}/billing" class="btn">Pay Now</a>
</p>

<p>If you\'re having trouble, please contact us - we\'re here to help!</p>'
    ],
    
    [
        'name' => 'payment_failed_final',
        'display' => 'Payment Failed - Final Warning (Day 7)',
        'category' => 'payment',
        'subject' => '🚨 FINAL WARNING: Service Suspension Tomorrow',
        'body' => '
<h2>🚨 Final Warning - Immediate Action Required</h2>
<p>Dear {first_name},</p>
<p><strong>Your TrueVault VPN service will be suspended tomorrow unless payment is received.</strong></p>

<div class="warning-box" style="background: #f8d7da; border-color: #dc3545;">
    <strong>Account Status: PAST DUE</strong><br><br>
    <strong>Amount Owed:</strong> ${amount}<br>
    <strong>Invoice:</strong> {invoice_number}<br>
    <strong>Suspension Date:</strong> Tomorrow
</div>

<p>To avoid service interruption:</p>

<p style="text-align: center;">
    <a href="{dashboard_url}/billing" class="btn" style="background: #dc3545; color: #fff;">Pay Now to Keep Service Active</a>
</p>

<p>Once suspended, you\'ll lose access to:</p>
<ul>
    <li>All VPN connections</li>
    <li>Port forwarding rules</li>
    <li>Camera dashboard access</li>
    <li>Parental control features</li>
</ul>

<p>Please act now to avoid interruption. Contact us if you need assistance.</p>'
    ],
    
    // ============================================
    // SUPPORT TEMPLATES (2)
    // ============================================
    [
        'name' => 'ticket_received',
        'display' => 'Support Ticket Received',
        'category' => 'support',
        'subject' => 'We Got Your Message - Ticket #{ticket_id}',
        'body' => '
<h2>Support Request Received 📬</h2>
<p>Hi {first_name},</p>
<p>We\'ve received your support request and will get back to you shortly.</p>

<div class="info-box">
    <strong>Ticket Details:</strong><br><br>
    <strong>Ticket ID:</strong> {ticket_id}<br>
    <strong>Subject:</strong> {ticket_subject}<br>
    <strong>Status:</strong> Open<br>
    <strong>Priority:</strong> {ticket_priority}
</div>

<p>Our support team typically responds within 24 hours. You can check your ticket status anytime:</p>

<p style="text-align: center;">
    <a href="{dashboard_url}/support?ticket={ticket_id}" class="btn">View Ticket</a>
</p>

<p>In the meantime, you might find these resources helpful:</p>
<ul>
    <li><a href="{site_url}/faq">FAQ</a></li>
    <li><a href="{site_url}/guides">Setup Guides</a></li>
</ul>

<p>We\'ll be in touch soon!</p>'
    ],
    
    [
        'name' => 'ticket_resolved',
        'display' => 'Support Ticket Resolved',
        'category' => 'support',
        'subject' => 'Your Ticket Has Been Resolved - #{ticket_id}',
        'body' => '
<h2>Ticket Resolved ✅</h2>
<p>Hi {first_name},</p>
<p>Good news! Your support ticket has been resolved.</p>

<div class="success-box">
    <strong>Ticket ID:</strong> {ticket_id}<br>
    <strong>Subject:</strong> {ticket_subject}<br>
    <strong>Status:</strong> Resolved
</div>

<p><strong>Resolution:</strong></p>
<div class="highlight">
    {resolution}
</div>

<p style="text-align: center;">
    <a href="{dashboard_url}/support?ticket={ticket_id}" class="btn">View Full Conversation</a>
</p>

<p><strong>Was this helpful?</strong> We\'d love your feedback!</p>
<p>
    <a href="{site_url}/feedback?ticket={ticket_id}&rating=good">👍 Yes, this helped!</a> | 
    <a href="{site_url}/feedback?ticket={ticket_id}&rating=bad">👎 I still need help</a>
</p>

<p>Thank you for using TrueVault VPN!</p>'
    ],
    
    // ============================================
    // COMPLAINT TEMPLATES (2)
    // ============================================
    [
        'name' => 'complaint_acknowledge',
        'display' => 'Complaint Acknowledgment',
        'category' => 'complaint',
        'subject' => 'We Hear You - Your Feedback Matters',
        'body' => '
<h2>We\'re Sorry to Hear That</h2>
<p>Dear {first_name},</p>
<p>Thank you for taking the time to share your concerns with us. We take all feedback seriously and sincerely apologize for any frustration you\'ve experienced.</p>

<div class="info-box">
    <strong>Your complaint has been logged and escalated for immediate review.</strong><br><br>
    Reference: {ticket_id}
</div>

<p>A senior member of our team will personally review your case and reach out to you within 24 hours.</p>

<p>We are committed to making this right. Your satisfaction is our priority.</p>

<p>With sincere apologies,<br>The TrueVault Team</p>'
    ],
    
    [
        'name' => 'complaint_resolved',
        'display' => 'Complaint Resolved',
        'category' => 'complaint',
        'subject' => 'Following Up on Your Concern',
        'body' => '
<h2>Thank You for Your Patience</h2>
<p>Dear {first_name},</p>
<p>We wanted to follow up regarding your recent concern.</p>

<div class="success-box">
    <strong>Resolution:</strong><br>
    {resolution}
</div>

<p>We hope this addresses your concerns. We truly value you as a customer and have taken steps to prevent similar issues in the future.</p>

<p>If there\'s anything else we can do to improve your experience, please don\'t hesitate to reach out.</p>

<p>Thank you for giving us the opportunity to make things right.</p>

<p>Warm regards,<br>The TrueVault Team</p>'
    ],
    
    // ============================================
    // SERVER TEMPLATES (2)
    // ============================================
    [
        'name' => 'server_down',
        'display' => 'Server Down Alert',
        'category' => 'server',
        'subject' => '⚠️ Service Alert: {server_name} Temporarily Unavailable',
        'body' => '
<h2>⚠️ Service Interruption Notice</h2>
<p>Hi {first_name},</p>
<p>We\'re writing to let you know that one of our VPN servers is currently experiencing issues.</p>

<div class="warning-box">
    <strong>Affected Server:</strong> {server_name}<br>
    <strong>Location:</strong> {server_location}<br>
    <strong>Status:</strong> Offline<br>
    <strong>Detected:</strong> {timestamp}
</div>

<p><strong>What you can do:</strong></p>
<ul>
    <li>Switch to a different server location</li>
    <li>Your connection will auto-reconnect once restored</li>
</ul>

<p>Our team is actively working to restore service. We expect this to be resolved shortly.</p>

<p>We apologize for any inconvenience and thank you for your patience.</p>'
    ],
    
    [
        'name' => 'server_restored',
        'display' => 'Server Restored',
        'category' => 'server',
        'subject' => '✅ Service Restored: {server_name} Back Online',
        'body' => '
<h2>✅ Service Restored</h2>
<p>Hi {first_name},</p>
<p>Good news! The server issue has been resolved and all services are back to normal.</p>

<div class="success-box">
    <strong>Server:</strong> {server_name}<br>
    <strong>Location:</strong> {server_location}<br>
    <strong>Status:</strong> Online ✓<br>
    <strong>Restored:</strong> {timestamp}
</div>

<p>Your VPN connection should automatically reconnect. If you experience any issues, try disconnecting and reconnecting.</p>

<p>We apologize for any inconvenience this may have caused. Thank you for your patience!</p>'
    ],
    
    // ============================================
    // RETENTION TEMPLATES (3)
    // ============================================
    [
        'name' => 'cancellation_survey',
        'display' => 'Cancellation Survey',
        'category' => 'retention',
        'subject' => 'We\'re Sorry to See You Go',
        'body' => '
<h2>We\'re Sad to See You Leave 😢</h2>
<p>Hi {first_name},</p>
<p>We received your cancellation request. Before you go, would you mind telling us why?</p>

<p><strong>Please help us improve by sharing your reason:</strong></p>
<ul>
    <li><a href="{site_url}/feedback?reason=price">💰 Too expensive</a></li>
    <li><a href="{site_url}/feedback?reason=features">🔧 Missing features I need</a></li>
    <li><a href="{site_url}/feedback?reason=speed">🐢 Speed issues</a></li>
    <li><a href="{site_url}/feedback?reason=support">😞 Support experience</a></li>
    <li><a href="{site_url}/feedback?reason=other">📝 Other reason</a></li>
</ul>

<p>Your feedback helps us serve our customers better.</p>

<p><strong>Changed your mind?</strong> You can reactivate anytime before your service ends on {expiry_date}.</p>

<p style="text-align: center;">
    <a href="{dashboard_url}/subscription" class="btn">Keep My Account</a>
</p>

<p>Thank you for being a TrueVault customer.</p>'
    ],
    
    [
        'name' => 'retention_offer',
        'display' => 'Retention Offer',
        'category' => 'retention',
        'subject' => 'Wait! We Have a Special Offer for You',
        'body' => '
<h2>Before You Go... 🎁</h2>
<p>Hi {first_name},</p>
<p>We noticed you\'re thinking about leaving, and we don\'t want to lose you!</p>

<div class="success-box">
    <strong>🎉 SPECIAL OFFER JUST FOR YOU</strong><br><br>
    <span style="font-size: 24px; font-weight: bold;">50% OFF</span><br>
    Your next 3 months
</div>

<p>That\'s just <strong>${discounted_amount}/month</strong> instead of ${amount}/month!</p>

<p style="text-align: center;">
    <a href="{dashboard_url}/subscription?offer=retention50" class="btn">Claim This Offer</a>
</p>

<p>This offer is valid for 48 hours only.</p>

<p>We hope you\'ll give us another chance!</p>'
    ],
    
    [
        'name' => 'winback_campaign',
        'display' => 'Win-back Campaign',
        'category' => 'retention',
        'subject' => 'We Miss You, {first_name}! Here\'s a Gift 🎁',
        'body' => '
<h2>We Miss You! 💙</h2>
<p>Hi {first_name},</p>
<p>It\'s been a while since you left TrueVault VPN, and we wanted to reach out.</p>

<p>A lot has changed since you left:</p>
<ul>
    <li>✨ New server locations added</li>
    <li>🚀 Faster connection speeds</li>
    <li>📷 Enhanced camera dashboard</li>
    <li>👨‍👩‍👧‍👦 Better parental controls</li>
</ul>

<div class="success-box">
    <strong>🎁 WELCOME BACK OFFER</strong><br><br>
    <span style="font-size: 24px; font-weight: bold;">7 DAYS FREE</span><br>
    + 30% off your first month back
</div>

<p style="text-align: center;">
    <a href="{site_url}/reactivate?code=COMEBACK30" class="btn">Come Back to TrueVault</a>
</p>

<p>We\'d love to have you back in the TrueVault family!</p>'
    ],
    
    // ============================================
    // VIP TEMPLATES (2) - SECRET
    // ============================================
    [
        'name' => 'vip_request_received',
        'display' => 'VIP Request Received (Admin)',
        'category' => 'vip',
        'subject' => 'Premium Access Request Received',
        'body' => '
<h2>Request Received</h2>
<p>Dear {first_name},</p>
<p>We have received your inquiry regarding premium access. Our team will review your request and get back to you shortly.</p>

<div class="info-box">
    <strong>Reference:</strong> {ticket_id}<br>
    <strong>Status:</strong> Under Review
</div>

<p>You will receive a follow-up email once a decision has been made.</p>

<p>Thank you for your interest.</p>

<p>Best regards,<br>TrueVault Team</p>'
    ],
    
    [
        'name' => 'vip_welcome_package',
        'display' => 'VIP Welcome Package (Secret)',
        'category' => 'vip',
        'subject' => 'Your Premium Access Has Been Activated',
        'body' => '
<h2>Premium Access Confirmed</h2>
<p>Dear {first_name},</p>
<p>We are pleased to inform you that your premium access has been approved and activated.</p>

<div class="success-box">
    <strong>Account Upgraded Successfully</strong><br><br>
    All premium features are now available in your dashboard.
</div>

<h3>Your Premium Benefits:</h3>
<ul>
    <li>✓ Dedicated server resources</li>
    <li>✓ Unlimited bandwidth</li>
    <li>✓ Priority support channel</li>
    <li>✓ All features unlocked</li>
    <li>✓ Unlimited devices</li>
</ul>

<p style="text-align: center;">
    <a href="{dashboard_url}" class="btn">Access Dashboard</a>
</p>

<p>Thank you for being a valued member of our community.</p>

<p>Best regards,<br>The TrueVault Team</p>'
    ]
];

// Install templates
$db = new SQLite3(DB_LOGS);
$db->enableExceptions(true);

$installed = 0;
$errors = 0;

foreach ($templates as $t) {
    try {
        // Wrap body in template
        $fullBody = EmailTemplate::wrap($t['body'], $t['subject']);
        
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO email_templates 
            (template_name, display_name, subject, body_html, body_text, category)
            VALUES (:name, :display, :subject, :html, :text, :category)
        ");
        
        $stmt->bindValue(':name', $t['name'], SQLITE3_TEXT);
        $stmt->bindValue(':display', $t['display'], SQLITE3_TEXT);
        $stmt->bindValue(':subject', $t['subject'], SQLITE3_TEXT);
        $stmt->bindValue(':html', $fullBody, SQLITE3_TEXT);
        $stmt->bindValue(':text', strip_tags($t['body']), SQLITE3_TEXT);
        $stmt->bindValue(':category', $t['category'], SQLITE3_TEXT);
        
        $stmt->execute();
        
        echo '<div class="success">✅ ' . htmlspecialchars($t['display']) . '</div>';
        $installed++;
        
    } catch (Exception $e) {
        echo '<div class="error">❌ ' . htmlspecialchars($t['name']) . ': ' . $e->getMessage() . '</div>';
        $errors++;
    }
}

$db->close();

echo '<h2>Summary</h2>';
echo "<p>Installed: {$installed} templates</p>";
echo "<p>Errors: {$errors}</p>";

if ($errors === 0) {
    echo '<div class="warning">⚠️ DELETE THIS FILE after running! It contains template data that should not be exposed.</div>';
}

?>
</div>
</body>
</html>
