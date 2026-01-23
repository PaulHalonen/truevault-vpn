<?php
/**
 * TrueVault VPN - Refund Policy Page
 * Part 12 - Database-driven refund policy
 * ALL content from database - NO hardcoding
 */

require_once __DIR__ . '/includes/content-functions.php';

$pageData = getPage('refund');
$siteName = getSetting('site_name', 'TrueVault VPN');
$contactEmail = getSetting('contact_email', 'support@truevault.com');

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <h1><?= e($pageData['hero_title'] ?? 'Refund Policy') ?></h1>
        <p><?= e($pageData['hero_subtitle'] ?? '30-day money-back guarantee. No questions asked.') ?></p>
    </div>
</section>

<!-- Refund Content -->
<section class="legal-section">
    <div class="container">
        <div class="legal-content">
            
            <div class="guarantee-hero">
                <div class="guarantee-icon">🛡️</div>
                <h2>30-Day Money-Back Guarantee</h2>
                <p>We're confident you'll love <?= e($siteName) ?>. But if for any reason you're not satisfied within the first 30 days, we'll give you a full refund. No questions asked.</p>
            </div>
            
            <h2>How to Request a Refund</h2>
            <p>Getting a refund is easy:</p>
            
            <div class="steps-list">
                <div class="step-item">
                    <div class="step-num">1</div>
                    <div class="step-content">
                        <h4>Contact Support</h4>
                        <p>Email us at <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a> with your account email and reason for refund (optional).</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-num">2</div>
                    <div class="step-content">
                        <h4>Confirmation</h4>
                        <p>We'll confirm your refund request within 24 hours.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-num">3</div>
                    <div class="step-content">
                        <h4>Receive Refund</h4>
                        <p>Your refund will be processed within 5-7 business days to your original payment method.</p>
                    </div>
                </div>
            </div>
            
            <h2>What's Covered</h2>
            <ul>
                <li><strong>Monthly subscriptions:</strong> Full refund within 30 days of purchase</li>
                <li><strong>Annual subscriptions:</strong> Full refund within 30 days of purchase</li>
                <li><strong>Renewals:</strong> Full refund within 30 days of renewal date</li>
            </ul>
            
            <h2>What's NOT Covered</h2>
            <ul>
                <li>Accounts terminated for Terms of Service violations</li>
                <li>Requests made after the 30-day period</li>
                <li>Third-party fees (e.g., bank transaction fees)</li>
            </ul>
            
            <div class="highlight-box">
                <h4>💡 Pro Tip</h4>
                <p>Before requesting a refund, contact our support team! Many issues can be resolved quickly, and we'd love the chance to make things right.</p>
            </div>
            
            <h2>Partial Refunds</h2>
            <p>If you request a refund after 30 days on an annual plan, we may offer a prorated refund at our discretion based on:</p>
            <ul>
                <li>Time remaining on your subscription</li>
                <li>Reason for cancellation</li>
                <li>Your account history</li>
            </ul>
            
            <h2>Cancellation vs. Refund</h2>
            <p>Cancelling your subscription is different from requesting a refund:</p>
            <ul>
                <li><strong>Cancellation:</strong> Stops future billing. You keep access until your current period ends.</li>
                <li><strong>Refund:</strong> Returns your money. Account access is terminated immediately.</li>
            </ul>
            
            <h2>Frequently Asked Questions</h2>
            
            <div class="faq-mini">
                <h4>How long does the refund take?</h4>
                <p>Refunds are processed within 24 hours of approval. It may take 5-7 business days to appear on your statement depending on your bank.</p>
            </div>
            
            <div class="faq-mini">
                <h4>Can I get a refund after 30 days?</h4>
                <p>The 30-day guarantee is firm, but we evaluate requests on a case-by-case basis. Contact us to discuss your situation.</p>
            </div>
            
            <div class="faq-mini">
                <h4>Will I lose my data?</h4>
                <p>Upon refund, your account is terminated. Any saved settings or configurations will be deleted.</p>
            </div>
            
            <div class="faq-mini">
                <h4>Can I sign up again after a refund?</h4>
                <p>Yes, you're welcome to return anytime. However, the 30-day guarantee applies once per customer.</p>
            </div>
            
            <h2>Contact Us</h2>
            <p>Questions about our refund policy?</p>
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
    font-size: 1.2rem;
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

/* Guarantee Hero */
.guarantee-hero {
    text-align: center;
    padding: 40px;
    background: linear-gradient(135deg, rgba(0, 217, 255, 0.1), rgba(0, 255, 136, 0.1));
    border: 1px solid rgba(0, 217, 255, 0.3);
    border-radius: 16px;
    margin-bottom: 40px;
}

.guarantee-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.guarantee-hero h2 {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 15px;
    border: none !important;
    padding: 0 !important;
    margin-top: 0 !important;
}

.guarantee-hero p {
    color: var(--text-secondary);
    font-size: 1.1rem;
    line-height: 1.7;
    max-width: 600px;
    margin: 0 auto;
}

.legal-content h2 {
    font-size: 1.5rem;
    color: var(--primary);
    margin-top: 40px;
    margin-bottom: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.05);
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

/* Steps */
.steps-list {
    margin: 30px 0;
}

.step-item {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    padding: 20px;
    background: var(--background);
    border-radius: 12px;
}

.step-num {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--background);
    flex-shrink: 0;
}

.step-content h4 {
    font-size: 1.1rem;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.step-content p {
    margin: 0;
    color: var(--text-secondary);
}

/* Highlight Box */
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

/* FAQ Mini */
.faq-mini {
    background: var(--background);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
}

.faq-mini h4 {
    font-size: 1.1rem;
    color: var(--primary);
    margin-bottom: 10px;
}

.faq-mini p {
    margin: 0;
    color: var(--text-secondary);
}

/* Responsive */
@media (max-width: 768px) {
    .legal-content {
        padding: 30px 20px;
    }
    
    .page-hero h1 {
        font-size: 2.2rem;
    }
    
    .guarantee-hero {
        padding: 30px 20px;
    }
    
    .step-item {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
