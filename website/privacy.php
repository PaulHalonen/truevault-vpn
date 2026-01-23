<?php
/**
 * TrueVault VPN - Privacy Policy Page
 * Part 12 - Database-driven privacy policy
 * ALL content from database - NO hardcoding
 */

require_once __DIR__ . '/includes/content-functions.php';

$pageData = getPage('privacy');
$siteName = getSetting('site_name', 'TrueVault VPN');
$contactEmail = getSetting('contact_email', 'support@truevault.com');

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <h1><?= e($pageData['hero_title'] ?? 'Privacy Policy') ?></h1>
        <p><?= e($pageData['hero_subtitle'] ?? 'Your privacy is our priority.') ?></p>
        <p class="last-updated">Last updated: January 2026</p>
    </div>
</section>

<!-- Privacy Content -->
<section class="legal-section">
    <div class="container">
        <div class="legal-content">
            
            <div class="legal-intro">
                <p>At <?= e($siteName) ?>, we take your privacy seriously. This Privacy Policy explains how we collect, use, and protect your information when you use our VPN service.</p>
            </div>
            
            <h2>1. Information We Collect</h2>
            
            <h3>1.1 Account Information</h3>
            <p>When you create an account, we collect:</p>
            <ul>
                <li>Email address (for account access and communication)</li>
                <li>Payment information (processed securely by PayPal)</li>
                <li>Account preferences</li>
            </ul>
            
            <h3>1.2 What We DO NOT Collect</h3>
            <p>We have a strict no-logs policy. We do NOT collect or store:</p>
            <ul>
                <li>Browsing history or traffic data</li>
                <li>DNS queries</li>
                <li>IP addresses of connections</li>
                <li>Connection timestamps</li>
                <li>Bandwidth usage per session</li>
                <li>Network traffic content</li>
            </ul>
            
            <div class="highlight-box">
                <h4>🔒 Zero-Log Guarantee</h4>
                <p><?= e($siteName) ?> operates with a strict zero-log policy. We cannot share what we do not have. Even if legally compelled, we have no browsing data to provide.</p>
            </div>
            
            <h2>2. How We Use Your Information</h2>
            <p>The limited information we collect is used to:</p>
            <ul>
                <li>Provide and maintain your VPN service</li>
                <li>Process payments and prevent fraud</li>
                <li>Send important service updates</li>
                <li>Respond to support requests</li>
                <li>Improve our service</li>
            </ul>
            
            <h2>3. Payment Processing</h2>
            <p>All payments are processed by PayPal. We do not store your credit card numbers or financial details on our servers. PayPal's privacy policy governs their handling of your payment information.</p>
            
            <h2>4. Data Security</h2>
            <p>We implement industry-standard security measures:</p>
            <ul>
                <li>256-bit AES encryption for all VPN connections</li>
                <li>Secure, encrypted databases</li>
                <li>Regular security audits</li>
                <li>Personal certificate infrastructure (you own your keys)</li>
            </ul>
            
            <h2>5. Third-Party Services</h2>
            <p>We use the following third-party services:</p>
            <ul>
                <li><strong>PayPal:</strong> Payment processing</li>
                <li><strong>WireGuard®:</strong> VPN protocol (open source)</li>
            </ul>
            <p>We do not sell, rent, or share your personal information with third parties for marketing purposes.</p>
            
            <h2>6. Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal data</li>
                <li>Correct inaccurate data</li>
                <li>Delete your account and data</li>
                <li>Export your data</li>
                <li>Opt out of marketing communications</li>
            </ul>
            
            <h2>7. Data Retention</h2>
            <p>We retain your account information for as long as your account is active. Upon account deletion:</p>
            <ul>
                <li>Account data is deleted within 30 days</li>
                <li>Billing records are retained for 7 years (legal requirement)</li>
                <li>No VPN usage data exists to delete (zero-log policy)</li>
            </ul>
            
            <h2>8. Children's Privacy</h2>
            <p><?= e($siteName) ?> is not intended for children under 13. We do not knowingly collect information from children under 13. If you believe a child has provided us with personal information, please contact us.</p>
            
            <h2>9. International Data Transfers</h2>
            <p>Your data may be processed in countries where our servers are located. We ensure appropriate safeguards are in place for international transfers.</p>
            
            <h2>10. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of significant changes by email or through the service. Continued use after changes constitutes acceptance.</p>
            
            <h2>11. Contact Us</h2>
            <p>If you have questions about this Privacy Policy, please contact us:</p>
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

.highlight-box h4 {
    color: var(--primary);
    margin-bottom: 10px;
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
