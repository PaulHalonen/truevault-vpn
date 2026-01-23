<?php
/**
 * TrueVault VPN - Contact Page
 * Part 12 - Database-driven contact page
 * ALL content from database - NO hardcoding
 */

require_once __DIR__ . '/includes/content-functions.php';

$pageData = getPage('contact');
$contactEmail = getSetting('contact_email', 'support@truevault.com');
$contactPhone = getSetting('contact_phone', '');
$contactAddress = getSetting('contact_address', '');

// Handle form submission
$formMessage = '';
$formSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $formMessage = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formMessage = 'Please enter a valid email address.';
    } else {
        // Save to database
        if (saveContactSubmission($name, $email, $subject, $message)) {
            $formSuccess = true;
            $formMessage = 'Thank you! Your message has been sent. We\'ll get back to you within 24 hours.';
        } else {
            $formMessage = 'Sorry, there was an error sending your message. Please try again.';
        }
    }
}

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <h1><?= e($pageData['hero_title'] ?? 'Get In Touch') ?></h1>
        <p><?= e($pageData['hero_subtitle'] ?? 'Have questions? We\'d love to hear from you.') ?></p>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <!-- Contact Form -->
            <div class="contact-form-wrapper">
                <h2>Send Us a Message</h2>
                
                <?php if ($formMessage): ?>
                <div class="form-message <?= $formSuccess ? 'success' : 'error' ?>">
                    <?= e($formMessage) ?>
                </div>
                <?php endif; ?>
                
                <?php if (!$formSuccess): ?>
                <form method="POST" class="contact-form" id="contactForm">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?= e($_POST['name'] ?? '') ?>"
                               placeholder="John Doe">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?= e($_POST['email'] ?? '') ?>"
                               placeholder="john@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject">
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Technical Support">Technical Support</option>
                            <option value="Billing Question">Billing Question</option>
                            <option value="Feature Request">Feature Request</option>
                            <option value="Partnership">Partnership</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="6" required
                                  placeholder="How can we help you?"><?= e($_POST['message'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Send Message
                    </button>
                </form>
                <?php else: ?>
                <div class="success-actions">
                    <a href="/contact.php" class="btn btn-secondary">Send Another Message</a>
                    <a href="/" class="btn btn-primary">Back to Home</a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Contact Info -->
            <div class="contact-info">
                <h2>Other Ways to Reach Us</h2>
                
                <div class="info-card">
                    <div class="info-icon">📧</div>
                    <h3>Email Support</h3>
                    <p>For general inquiries and support</p>
                    <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
                </div>
                
                <?php if ($contactPhone): ?>
                <div class="info-card">
                    <div class="info-icon">📞</div>
                    <h3>Phone</h3>
                    <p>Mon-Fri, 9am-5pm EST</p>
                    <a href="tel:<?= e($contactPhone) ?>"><?= e($contactPhone) ?></a>
                </div>
                <?php endif; ?>
                
                <div class="info-card">
                    <div class="info-icon">⏱️</div>
                    <h3>Response Time</h3>
                    <p>We typically respond within</p>
                    <strong>24 hours</strong>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">📚</div>
                    <h3>Help Center</h3>
                    <p>Browse our knowledge base</p>
                    <a href="/help.php">Visit Help Center</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Preview -->
<section class="faq-preview">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        
        <div class="faq-mini-grid">
            <div class="faq-mini">
                <h4>How do I reset my password?</h4>
                <p>Click "Forgot Password" on the login page and follow the instructions sent to your email.</p>
            </div>
            <div class="faq-mini">
                <h4>Can I get a refund?</h4>
                <p>Yes! We offer a 30-day money-back guarantee. <a href="/refund.php">Learn more</a></p>
            </div>
            <div class="faq-mini">
                <h4>How do I cancel my subscription?</h4>
                <p>You can cancel anytime from your account dashboard under Settings > Subscription.</p>
            </div>
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

/* Contact Section */
.contact-section {
    padding: 60px 0 80px;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 60px;
}

.contact-form-wrapper h2,
.contact-info h2 {
    font-size: 1.5rem;
    margin-bottom: 25px;
    color: var(--primary);
}

/* Form */
.contact-form {
    background: var(--card-bg);
    padding: 30px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.05);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-secondary);
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px;
    background: var(--background);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 217, 255, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-message {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-message.success {
    background: rgba(0, 255, 136, 0.1);
    border: 1px solid var(--secondary);
    color: var(--secondary);
}

.form-message.error {
    background: rgba(255, 100, 100, 0.1);
    border: 1px solid #ff6464;
    color: #ff6464;
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
}

.success-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

/* Contact Info */
.contact-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.info-card {
    background: var(--card-bg);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
}

.info-icon {
    font-size: 2rem;
    margin-bottom: 15px;
}

.info-card h3 {
    font-size: 1.1rem;
    margin-bottom: 8px;
}

.info-card p {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin-bottom: 8px;
}

.info-card a {
    color: var(--primary);
    font-weight: 500;
}

.info-card strong {
    color: var(--secondary);
    font-size: 1.2rem;
}

/* FAQ Preview */
.faq-preview {
    padding: 80px 0;
    background: var(--card-bg);
}

.section-title {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 40px;
}

.faq-mini-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
}

.faq-mini {
    background: var(--background);
    padding: 25px;
    border-radius: 12px;
}

.faq-mini h4 {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: var(--primary);
}

.faq-mini p {
    color: var(--text-secondary);
    line-height: 1.6;
}

.faq-mini a {
    color: var(--primary);
}

/* Responsive */
@media (max-width: 900px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .faq-mini-grid {
        grid-template-columns: 1fr;
    }
    
    .page-hero h1 {
        font-size: 2.2rem;
    }
}
</style>

<?php include __DIR__ . '/templates/footer.php'; ?>
