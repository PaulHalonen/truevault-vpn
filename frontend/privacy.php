<?php
require_once 'config.php';
require_once 'db.php';

$page_title = 'Privacy Policy - TrueVault VPN';
$page_description = 'How TrueVault VPN protects your privacy';

include 'header.php';
?>

<style>
    body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; }
    .privacy-container { max-width: 900px; margin: 2rem auto; padding: 0 2rem; }
    .page-header { text-align: center; margin: 3rem 0; }
    .page-header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
    .last-updated { text-align: center; color: #888; margin-bottom: 3rem; }
    .highlight-box { background: rgba(0,217,255,0.1); border-left: 4px solid #00d9ff; padding: 1.5rem; margin: 2rem 0; border-radius: 8px; }
    .content-section { margin: 2.5rem 0; }
    .content-section h2 { font-size: 1.8rem; margin-bottom: 1rem; color: #00d9ff; }
    .content-section p { color: #ccc; line-height: 1.8; margin-bottom: 1rem; }
    .content-section ul { margin-left: 2rem; color: #ccc; line-height: 1.8; }
</style>

<div class="privacy-container">
    <div class="page-header">
        <h1>Privacy Policy</h1>
    </div>
    <div class="last-updated">Last Updated: January 19, 2026</div>

    <div class="highlight-box">
        <h3 style="color: #00ff88; margin-bottom: 1rem;">🔒 Our Promise: Strict No-Logs Policy</h3>
        <p style="color: #fff; font-weight: 600;">
            We do NOT track, log, or store your browsing activity, connection times, IP addresses, or DNS queries. 
            What you do online is your business—not ours, not anyone else's.
        </p>
    </div>

    <div class="content-section">
        <h2>1. Information We Do NOT Collect</h2>
        <p>
            Unlike most VPN providers, we maintain a strict no-logs policy. We do NOT collect or store:
        </p>
        <ul>
            <li><strong>Browsing History:</strong> Websites you visit, pages you view, search queries</li>
            <li><strong>Connection Logs:</strong> When you connect/disconnect, session duration, server used</li>
            <li><strong>IP Addresses:</strong> Your real IP or the IP assigned during VPN sessions</li>
            <li><strong>Traffic Data:</strong> Bandwidth usage, protocols used, data transferred</li>
            <li><strong>DNS Queries:</strong> Domains you access, DNS requests made</li>
            <li><strong>Metadata:</strong> Connection timestamps, session identifiers, device fingerprints</li>
        </ul>
    </div>

    <div class="content-section">
        <h2>2. Information We DO Collect (Minimal)</h2>
        <p>
            To provide the Service, we collect only what's absolutely necessary:
        </p>
        <ul>
            <li><strong>Email Address:</strong> For account creation, password reset, and support communications</li>
            <li><strong>Payment Information:</strong> Processed securely through PayPal—we never see your credit card</li>
            <li><strong>Device Count:</strong> Number of devices on your account (for plan enforcement)</li>
            <li><strong>Account Creation Date:</strong> When you signed up (for billing purposes)</li>
        </ul>
        <p>
            That's it. No tracking cookies, no analytics scripts, no third-party surveillance.
        </p>
    </div>

    <div class="content-section">
        <h2>3. How We Use Your Information</h2>
        <p>
            The minimal data we collect is used only for:
        </p>
        <ul>
            <li>Creating and managing your account</li>
            <li>Processing payments and generating invoices</li>
            <li>Sending service-related emails (password resets, billing notices)</li>
            <li>Providing technical support</li>
            <li>Enforcing device limits per your plan</li>
        </ul>
        <p>
            We NEVER sell, rent, or share your data with advertisers or third parties.
        </p>
    </div>

    <div class="content-section">
        <h2>4. Data Storage and Security</h2>
        <p>
            All account data is stored in encrypted SQLite databases on secure servers. Payment processing is 
            handled entirely by PayPal—we never store credit card numbers.
        </p>
        <p>
            VPN connections use 256-bit AES encryption with perfect forward secrecy. Even if someone intercepts 
            your encrypted traffic, they cannot decrypt it—now or in the future.
        </p>
    </div>

    <div class="content-section">
        <h2>5. Third-Party Services</h2>
        <p>
            We use only one third-party service:
        </p>
        <ul>
            <li><strong>PayPal:</strong> For payment processing. See PayPal's privacy policy for their data handling.</li>
        </ul>
        <p>
            That's it. No Google Analytics, no Facebook Pixel, no advertising networks, no tracking.
        </p>
    </div>

    <div class="content-section">
        <h2>6. Legal Requests and Transparency</h2>
        <p>
            If law enforcement requests user data, we can only provide what we have: email addresses and payment dates. 
            We cannot provide browsing history, connection logs, or IP addresses because we don't store them.
        </p>
        <p>
            We will comply with valid legal requests but will challenge overly broad or unconstitutional demands.
        </p>
    </div>

    <div class="content-section">
        <h2>7. Your Rights</h2>
        <p>
            You have the right to:
        </p>
        <ul>
            <li><strong>Access:</strong> Request a copy of all data we have about you</li>
            <li><strong>Deletion:</strong> Request permanent deletion of your account and data</li>
            <li><strong>Correction:</strong> Update or correct your email address</li>
            <li><strong>Export:</strong> Download your account data in portable format</li>
        </ul>
        <p>
            Email us at <a href="mailto:privacy@vpn.the-truth-publishing.com" style="color: #00d9ff; text-decoration: none;">privacy@vpn.the-truth-publishing.com</a> 
            to exercise any of these rights.
        </p>
    </div>

    <div class="content-section">
        <h2>8. Children's Privacy</h2>
        <p>
            The Service is not intended for users under 18 years old. We do not knowingly collect data from children. 
            If you're a parent and believe your child has created an account, contact us immediately for deletion.
        </p>
    </div>

    <div class="content-section">
        <h2>9. Changes to This Policy</h2>
        <p>
            We may update this Privacy Policy to reflect service changes. Significant updates will be announced via 
            email. Continued use of the Service after changes constitutes acceptance.
        </p>
    </div>

    <div class="content-section">
        <h2>10. Contact Us</h2>
        <p>
            Questions about privacy? We're here to help:<br>
            <strong>Email:</strong> <a href="mailto:privacy@vpn.the-truth-publishing.com" style="color: #00d9ff; text-decoration: none;">privacy@vpn.the-truth-publishing.com</a>
        </p>
    </div>

    <div style="text-align: center; margin: 4rem 0;">
        <a href="/terms.php" style="color: #00d9ff; text-decoration: none; margin: 0 1rem;">Terms of Service</a>
        <a href="/contact.php" style="color: #00d9ff; text-decoration: none; margin: 0 1rem;">Contact Us</a>
    </div>
</div>

<?php include 'footer.php'; ?>
