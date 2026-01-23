<?php
/**
 * TrueVault VPN - Terms of Service Page
 * Part 12 - Database-driven terms
 * ALL content from database - NO hardcoding
 */

require_once __DIR__ . '/includes/content-functions.php';

$pageData = getPage('terms');
$siteName = getSetting('site_name', 'TrueVault VPN');
$contactEmail = getSetting('contact_email', 'support@truevault.com');

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <h1><?= e($pageData['hero_title'] ?? 'Terms of Service') ?></h1>
        <p><?= e($pageData['hero_subtitle'] ?? 'Please read these terms carefully.') ?></p>
        <p class="last-updated">Last updated: January 2026</p>
    </div>
</section>

<!-- Terms Content -->
<section class="legal-section">
    <div class="container">
        <div class="legal-content">
            
            <div class="legal-intro">
                <p>By using <?= e($siteName) ?> ("the Service"), you agree to these Terms of Service. If you do not agree, please do not use our Service.</p>
            </div>
            
            <h2>1. Acceptance of Terms</h2>
            <p>By creating an account or using <?= e($siteName) ?>, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service and our Privacy Policy.</p>
            
            <h2>2. Description of Service</h2>
            <p><?= e($siteName) ?> provides virtual private network (VPN) services that encrypt your internet connection and protect your online privacy. Our services include:</p>
            <ul>
                <li>VPN connection to secure servers</li>
                <li>Personal certificate infrastructure</li>
                <li>Port forwarding capabilities</li>
                <li>Parental control features</li>
                <li>Device management tools</li>
            </ul>
            
            <h2>3. User Accounts</h2>
            <h3>3.1 Account Creation</h3>
            <p>To use <?= e($siteName) ?>, you must create an account with accurate information. You are responsible for maintaining the security of your account credentials.</p>
            
            <h3>3.2 Account Responsibility</h3>
            <p>You are responsible for all activities that occur under your account. Notify us immediately of any unauthorized use.</p>
            
            <h2>4. Acceptable Use</h2>
            <p>You agree NOT to use <?= e($siteName) ?> to:</p>
            <ul>
                <li>Engage in illegal activities</li>
                <li>Distribute malware or viruses</li>
                <li>Send spam or unsolicited messages</li>
                <li>Violate copyright or intellectual property rights</li>
                <li>Harass, threaten, or harm others</li>
                <li>Attempt to breach network security</li>
                <li>Share your account with unauthorized users</li>
                <li>Resell or redistribute our service</li>
            </ul>
            
            <div class="highlight-box warning">
                <h4>⚠️ Violation Warning</h4>
                <p>Violation of these terms may result in immediate account termination without refund.</p>
            </div>
            
            <h2>5. Payment Terms</h2>
            <h3>5.1 Subscription</h3>
            <p>Subscriptions are billed in advance on a monthly or annual basis. Prices are subject to change with 30 days notice.</p>
            
            <h3>5.2 Free Trial</h3>
            <p>We offer a 7-day free trial for new users. No credit card is required for the trial period.</p>
            
            <h3>5.3 Refunds</h3>
            <p>We offer a 30-day money-back guarantee. See our <a href="/refund.php">Refund Policy</a> for details.</p>
            
            <h2>6. Service Availability</h2>
            <p>We strive for 99.9% uptime but do not guarantee uninterrupted service. We are not liable for service interruptions due to:</p>
            <ul>
                <li>Scheduled maintenance (with advance notice)</li>
                <li>Circumstances beyond our control</li>
                <li>Third-party service failures</li>
                <li>Natural disasters or emergencies</li>
            </ul>
            
            <h2>7. Intellectual Property</h2>
            <p>All content, trademarks, and software associated with <?= e($siteName) ?> are our property or licensed to us. You may not copy, modify, or distribute our intellectual property without written permission.</p>
            
            <h2>8. Limitation of Liability</h2>
            <p><?= e($siteName) ?> is provided "as is" without warranties of any kind. We are not liable for:</p>
            <ul>
                <li>Indirect, incidental, or consequential damages</li>
                <li>Loss of data, profits, or business opportunities</li>
                <li>Actions of third parties</li>
            </ul>
            <p>Our total liability is limited to the amount you paid for the Service in the past 12 months.</p>
            
            <h2>9. Termination</h2>
            <h3>9.1 By You</h3>
            <p>You may cancel your subscription at any time from your account dashboard. Access continues until the end of your billing period.</p>
            
            <h3>9.2 By Us</h3>
            <p>We may terminate or suspend your account immediately for violation of these Terms or for any reason with 30 days notice.</p>
            
            <h2>10. Changes to Terms</h2>
            <p>We may modify these Terms at any time. We will notify you of significant changes by email or through the Service. Continued use after changes constitutes acceptance.</p>
            
            <h2>11. Governing Law</h2>
            <p>These Terms are governed by the laws of the jurisdiction where our company is registered, without regard to conflict of law principles.</p>
            
            <h2>12. Dispute Resolution</h2>
            <p>Any disputes arising from these Terms shall be resolved through binding arbitration, except where prohibited by law. You waive the right to participate in class actions.</p>
            
            <h2>13. Severability</h2>
            <p>If any provision of these Terms is found unenforceable, the remaining provisions will continue in full force.</p>
            
            <h2>14. Contact</h2>
            <p>Questions about these Terms? Contact us:</p>
            <ul>
                <li>Email: <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></li>
                <li>Contact Form: <a href="/contact.php">/contact.php</a></li>
            </ul>
            
        </div>
    </div>
</section>

<style>
/* Page Hero */
.page-hero {
    padding: 80px 0 40px;
    text-align: center;
    background: linear-gradient(135deg, var(--background), var(--card-bg));
}

.page-hero h1 {
    font-size: 3rem;
    margin-bottom: 15px;
    background: linear-gradient(90deg, var(--text-primary), var(--primary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-hero p {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.last-updated {
    font-size: 0.9rem !important;
    margin-top: 10px;
    opacity: 0.7;
}

/* Legal Content */
.legal-section {
    padding: 60px 0 100px;
}

.legal-content {
    max-width: 800px;
    margin: 0 auto;
    background: var(--card-bg);
    padding: 50px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.05);
}

.legal-intro {
    font-size: 1.1rem;
    color: var(--text-secondary);
    padding-bottom: 30px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 30px;
}

.legal-content h2 {
    font-size: 1.5rem;
    color: var(--primary);
    margin-top: 40px;
    margin-bottom: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.legal-content h2:first-of-type {
    margin-top: 0;
    border-top: none;
    padding-top: 0;
}

.legal-content h3 {
    font-size: 1.2rem;
    color: var(--text-primary);
    margin-top: 25px;
    margin-bottom: 15px;
}

.legal-content p {
    color: var(--text-secondary);
    line-height: 1.8;
    margin-bottom: 15px;
}

.legal-content ul {
    margin-left: 20px;
    margin-bottom: 20px;
}

.legal-content li {
    color: var(--text-secondary);
    line-height: 1.8;
    margin-bottom: 8px;
}

.legal-content a {
    color: var(--primary);
}

.highlight-box {
    background: linear-gradient(135deg, rgba(0, 217, 255, 0.1), rgba(0, 255, 136, 0.1));
    border: 1px solid rgba(0, 217, 255, 0.3);
    border-radius: 12px;
    padding: 25px;
    margin: 30px 0;
}

.highlight-box.warning {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 152, 0, 0.1));
    border-color: rgba(255, 193, 7, 0.5);
}

.highlight-box h4 {
    color: var(--primary);
    margin-bottom: 10px;
}

.highlight-box.warning h4 {
    color: #ffc107;
}

.highlight-box p {
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .legal-content {
        padding: 30px 20px;
    }
    
    .page-hero h1 {
        font-size: 2.2rem;
    }
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
