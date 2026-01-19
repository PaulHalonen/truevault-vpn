<?php
require_once 'config.php';
require_once 'db.php';

$page_title = 'Terms of Service - TrueVault VPN';
$page_description = 'Terms and conditions for using TrueVault VPN';

include 'header.php';
?>

<style>
    body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; }
    .terms-container { max-width: 900px; margin: 2rem auto; padding: 0 2rem; }
    .page-header { text-align: center; margin: 3rem 0; }
    .page-header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
    .last-updated { text-align: center; color: #888; margin-bottom: 3rem; }
    .content-section { margin: 2.5rem 0; }
    .content-section h2 { font-size: 1.8rem; margin-bottom: 1rem; color: #00d9ff; }
    .content-section p { color: #ccc; line-height: 1.8; margin-bottom: 1rem; }
    .content-section ul { margin-left: 2rem; color: #ccc; line-height: 1.8; }
</style>

<div class="terms-container">
    <div class="page-header">
        <h1>Terms of Service</h1>
    </div>
    <div class="last-updated">Last Updated: January 19, 2026</div>

    <div class="content-section">
        <h2>1. Acceptance of Terms</h2>
        <p>
            By accessing or using TrueVault VPN ("the Service"), you agree to be bound by these Terms of Service. 
            If you disagree with any part of these terms, you may not access the Service.
        </p>
    </div>

    <div class="content-section">
        <h2>2. Description of Service</h2>
        <p>
            TrueVault VPN provides virtual private network (VPN) services, port forwarding, parental controls, 
            and related business automation tools. The Service allows users to securely connect to the internet 
            through encrypted servers.
        </p>
    </div>

    <div class="content-section">
        <h2>3. Acceptable Use Policy</h2>
        <p>You agree NOT to use the Service for:</p>
        <ul>
            <li>Illegal activities including but not limited to hacking, fraud, or copyright infringement</li>
            <li>Spamming, phishing, or distributing malware</li>
            <li>Accessing or distributing child abuse material</li>
            <li>Port scanning or network attacks against third parties</li>
            <li>Torrenting copyrighted material (legal torrenting is permitted)</li>
            <li>Reselling or sharing your account credentials</li>
        </ul>
        <p>
            Violation of this policy may result in immediate account termination without refund.
        </p>
    </div>

    <div class="content-section">
        <h2>4. Privacy and Logging</h2>
        <p>
            We maintain a strict no-logs policy. We do NOT store, collect, or share:
        </p>
        <ul>
            <li>Browsing history or website visits</li>
            <li>Traffic data or DNS queries</li>
            <li>Connection timestamps</li>
            <li>Bandwidth usage patterns</li>
            <li>IP addresses assigned during sessions</li>
        </ul>
        <p>
            We only collect payment information (for billing) and email addresses (for account recovery). 
            See our Privacy Policy for complete details.
        </p>
    </div>

    <div class="content-section">
        <h2>5. Billing and Refunds</h2>
        <p>
            All plans are billed monthly or annually in advance. We accept PayPal and major credit cards. 
            You may cancel at any time through your account dashboard.
        </p>
        <p>
            <strong>Free Trial:</strong> All new accounts receive a 7-day free trial. Cancel before the trial ends 
            and you won't be charged.
        </p>
        <p>
            <strong>Refund Policy:</strong> 30-day money-back guarantee. If you're not satisfied within the first 
            30 days, contact us for a full refund.
        </p>
    </div>

    <div class="content-section">
        <h2>6. Account Termination</h2>
        <p>
            We reserve the right to suspend or terminate accounts that violate these terms, engage in abusive 
            behavior, or excessive resource usage that impacts other users. You'll receive a warning email before 
            termination unless the violation is severe (illegal activity).
        </p>
    </div>

    <div class="content-section">
        <h2>7. Service Availability</h2>
        <p>
            We strive for 99.9% uptime but cannot guarantee uninterrupted service. Scheduled maintenance will be 
            announced 24 hours in advance. We are not liable for service interruptions caused by factors beyond 
            our control.
        </p>
    </div>

    <div class="content-section">
        <h2>8. Limitation of Liability</h2>
        <p>
            The Service is provided "as is" without warranties of any kind. We are not responsible for:
        </p>
        <ul>
            <li>Data loss or security breaches on your devices</li>
            <li>Websites or services that block VPN traffic</li>
            <li>Internet speed or connection quality issues</li>
            <li>Legal consequences of your activities while using the Service</li>
        </ul>
        <p>
            Our total liability is limited to the amount you paid for the Service in the past 12 months.
        </p>
    </div>

    <div class="content-section">
        <h2>9. Changes to Terms</h2>
        <p>
            We may update these Terms at any time. Significant changes will be announced via email. Continued 
            use of the Service after changes constitutes acceptance of the new Terms.
        </p>
    </div>

    <div class="content-section">
        <h2>10. Governing Law</h2>
        <p>
            These Terms are governed by the laws of the United States. Any disputes will be resolved through 
            binding arbitration in accordance with the rules of the American Arbitration Association.
        </p>
    </div>

    <div class="content-section">
        <h2>11. Contact Information</h2>
        <p>
            Questions about these Terms? Contact us:<br>
            <strong>Email:</strong> <a href="mailto:legal@vpn.the-truth-publishing.com" style="color: #00d9ff; text-decoration: none;">legal@vpn.the-truth-publishing.com</a>
        </p>
    </div>

    <div style="text-align: center; margin: 4rem 0;">
        <a href="/privacy.php" style="color: #00d9ff; text-decoration: none; margin: 0 1rem;">Privacy Policy</a>
        <a href="/contact.php" style="color: #00d9ff; text-decoration: none; margin: 0 1rem;">Contact Us</a>
    </div>
</div>

<?php include 'footer.php'; ?>
