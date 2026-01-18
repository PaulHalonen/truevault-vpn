<?php
/**
 * TrueVault VPN - Email Notification System
 * 
 * PURPOSE: Send transactional emails to users
 * FEATURES:
 * - Welcome emails
 * - Payment receipts
 * - Payment failed notifications
 * - Service suspension alerts
 * - Password reset emails
 * - VIP upgrade notifications
 * - System maintenance alerts
 * 
 * USAGE:
 * $email = new Email();
 * $email->sendWelcome($userEmail, $firstName);
 * $email->sendPaymentReceipt($userEmail, $amount, $invoiceNumber);
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

class Email {
    private $fromEmail = 'noreply@truthvault.com';
    private $fromName = 'TrueVault VPN';
    
    /**
     * Send email using PHP mail() function
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @return bool Success
     */
    private function send($to, $subject, $htmlBody) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$this->fromName} <{$this->fromEmail}>",
            "Reply-To: support@truthvault.com"
        ];
        
        $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
        
        // Log email
        error_log("Email sent to $to: $subject - " . ($success ? 'SUCCESS' : 'FAILED'));
        
        return $success;
    }
    
    /**
     * Get email template wrapper
     */
    private function getTemplate($content) {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; color: #333; line-height: 1.6; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 TrueVault VPN</h1>
        </div>
        <div class="content">
            $content
        </div>
        <div class="footer">
            <p>TrueVault VPN - Your Complete Digital Fortress</p>
            <p><a href="https://vpn.the-truth-publishing.com">Dashboard</a> | <a href="mailto:support@truthvault.com">Support</a></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Send welcome email to new user
     */
    public function sendWelcome($email, $firstName) {
        $content = <<<HTML
<h2>Welcome to TrueVault VPN, $firstName!</h2>
<p>Thank you for joining TrueVault VPN! We're excited to have you as part of our community.</p>
<p>Here's what you can do next:</p>
<ul>
    <li><strong>Set up your first device</strong> - Connect in under 10 seconds</li>
    <li><strong>Choose your plan</strong> - Standard, Pro, or VIP</li>
    <li><strong>Explore port forwarding</strong> - Access your home devices remotely</li>
</ul>
<a href="https://vpn.the-truth-publishing.com/dashboard/my-devices.php" class="button">Get Started</a>
<p>Need help? Our support team is here 24/7 at support@truthvault.com</p>
HTML;
        
        return $this->send($email, 'Welcome to TrueVault VPN!', $this->getTemplate($content));
    }
    
    /**
     * Send payment receipt
     */
    public function sendPaymentReceipt($email, $firstName, $amount, $plan) {
        $content = <<<HTML
<h2>Payment Receipt</h2>
<p>Hi $firstName,</p>
<p>Thank you for your payment! Your subscription is now active.</p>
<p><strong>Plan:</strong> $plan</p>
<p><strong>Amount:</strong> $$amount USD</p>
<p><strong>Status:</strong> Active ✅</p>
<a href="https://vpn.the-truth-publishing.com/dashboard/billing.php" class="button">View Billing</a>
<p>Your service is now active and ready to use!</p>
HTML;
        
        return $this->send($email, 'Payment Receipt - TrueVault VPN', $this->getTemplate($content));
    }
    
    /**
     * Send payment failed notification
     */
    public function sendPaymentFailed($email, $firstName, $amount) {
        $content = <<<HTML
<h2>Payment Failed</h2>
<p>Hi $firstName,</p>
<p>We were unable to process your recent payment of $$amount.</p>
<p><strong>What to do:</strong></p>
<ul>
    <li>Check your payment method</li>
    <li>Update your billing information</li>
    <li>Contact your bank if needed</li>
</ul>
<a href="https://vpn.the-truth-publishing.com/dashboard/billing.php" class="button">Update Payment</a>
<p>Your service will remain active for 7 days while we retry the payment.</p>
HTML;
        
        return $this->send($email, 'Payment Failed - Action Required', $this->getTemplate($content));
    }
    
    /**
     * Send service suspension alert
     */
    public function sendSuspension($email, $firstName) {
        $content = <<<HTML
<h2>Service Suspended</h2>
<p>Hi $firstName,</p>
<p>Your TrueVault VPN service has been suspended due to payment issues.</p>
<p><strong>To restore service:</strong></p>
<ul>
    <li>Update your payment method</li>
    <li>Complete the pending payment</li>
    <li>Service will resume immediately</li>
</ul>
<a href="https://vpn.the-truth-publishing.com/dashboard/billing.php" class="button">Restore Service</a>
<p>Need help? Contact us at support@truthvault.com</p>
HTML;
        
        return $this->send($email, 'Service Suspended - TrueVault VPN', $this->getTemplate($content));
    }
    
    /**
     * Send VIP upgrade notification
     */
    public function sendVIPUpgrade($email, $firstName) {
        $content = <<<HTML
<h2>🌟 Welcome to VIP!</h2>
<p>Hi $firstName,</p>
<p>Congratulations! You've been upgraded to VIP status.</p>
<p><strong>Your VIP Benefits:</strong></p>
<ul>
    <li>✅ Dedicated server access</li>
    <li>✅ Unlimited devices</li>
    <li>✅ Priority support</li>
    <li>✅ Maximum bandwidth</li>
</ul>
<a href="https://vpn.the-truth-publishing.com/dashboard/my-devices.php" class="button">Access VIP Dashboard</a>
<p>Enjoy your premium experience!</p>
HTML;
        
        return $this->send($email, '🌟 You\'re Now VIP! - TrueVault VPN', $this->getTemplate($content));
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($email, $firstName, $resetToken) {
        $resetLink = "https://vpn.the-truth-publishing.com/auth/reset-password.php?token=$resetToken";
        
        $content = <<<HTML
<h2>Password Reset Request</h2>
<p>Hi $firstName,</p>
<p>We received a request to reset your password. Click the button below to create a new password:</p>
<a href="$resetLink" class="button">Reset Password</a>
<p>This link will expire in 1 hour.</p>
<p>If you didn't request this, you can safely ignore this email.</p>
HTML;
        
        return $this->send($email, 'Password Reset - TrueVault VPN', $this->getTemplate($content));
    }
    
    /**
     * Send system maintenance notification
     */
    public function sendMaintenance($email, $firstName, $scheduledTime) {
        $content = <<<HTML
<h2>Scheduled Maintenance</h2>
<p>Hi $firstName,</p>
<p>We're performing scheduled maintenance to improve our service.</p>
<p><strong>Scheduled Time:</strong> $scheduledTime</p>
<p><strong>Expected Duration:</strong> 30 minutes</p>
<p>Your service may be briefly interrupted during this time. We apologize for any inconvenience.</p>
<p>Thank you for your patience!</p>
HTML;
        
        return $this->send($email, 'Scheduled Maintenance - TrueVault VPN', $this->getTemplate($content));
    }
    
    /**
     * Send device limit reached notification
     */
    public function sendDeviceLimitReached($email, $firstName, $currentPlan, $deviceLimit) {
        $content = <<<HTML
<h2>Device Limit Reached</h2>
<p>Hi $firstName,</p>
<p>You've reached the device limit for your $currentPlan plan ($deviceLimit devices).</p>
<p><strong>Options:</strong></p>
<ul>
    <li>Remove an existing device</li>
    <li>Upgrade to a higher plan for more devices</li>
</ul>
<a href="https://vpn.the-truth-publishing.com/dashboard/billing.php" class="button">Upgrade Plan</a>
<p>Need help? Contact support@truthvault.com</p>
HTML;
        
        return $this->send($email, 'Device Limit Reached - TrueVault VPN', $this->getTemplate($content));
    }
}
