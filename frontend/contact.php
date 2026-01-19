<?php
require_once 'config.php';
require_once 'db.php';

$page_title = 'Contact Us - TrueVault VPN';
$page_description = 'Get in touch with TrueVault VPN support';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Store in database
        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO contact_submissions (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, datetime('now'))");
            $stmt->execute([$name, $email, $subject, $message]);
            
            // TODO: Send email notification to admin
            
            $success = true;
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again or email us directly.';
        }
    }
}

include 'header.php';
?>

<style>
    body { background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; }
    .contact-container { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
    .page-header { text-align: center; margin: 3rem 0; }
    .page-header h1 { font-size: 3rem; margin-bottom: 1rem; }
    .contact-info { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin: 2rem 0; }
    .contact-info h3 { color: #00d9ff; margin-bottom: 1rem; }
    .contact-form { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 600; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: #fff; font-size: 1rem; }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #00d9ff; }
    .form-group textarea { min-height: 150px; resize: vertical; }
    .submit-btn { padding: 1rem 3rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.3s; }
    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,217,255,0.4); }
    .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
    .alert-success { background: rgba(0,255,136,0.2); border: 1px solid #00ff88; color: #00ff88; }
    .alert-error { background: rgba(255,100,100,0.2); border: 1px solid #ff6464; color: #ff6464; }
</style>

<div class="contact-container">
    <div class="page-header">
        <h1>Contact Us</h1>
        <p style="font-size: 1.2rem; color: #888;">We'd love to hear from you</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>✓ Message sent!</strong> We'll respond within 24 hours.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>✗ Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="contact-info">
        <h3>Get in Touch</h3>
        <p style="color: #ccc; line-height: 1.6;">
            Have a question about TrueVault VPN? Need technical support? Want to report a bug or request a feature? 
            Fill out the form below and we'll get back to you within 24 hours.
        </p>
        <div style="margin-top: 2rem;">
            <p><strong>Email:</strong> <a href="mailto:support@vpn.the-truth-publishing.com" style="color: #00d9ff; text-decoration: none;">support@vpn.the-truth-publishing.com</a></p>
            <p><strong>Response Time:</strong> Usually within 24 hours</p>
            <p><strong>Business Hours:</strong> 9 AM - 5 PM CST, Monday-Friday</p>
        </div>
    </div>

    <?php if (!$success): ?>
    <form method="POST" class="contact-form">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="subject">Subject</label>
            <select id="subject" name="subject">
                <option value="General Inquiry">General Inquiry</option>
                <option value="Technical Support">Technical Support</option>
                <option value="Billing Question">Billing Question</option>
                <option value="Feature Request">Feature Request</option>
                <option value="Bug Report">Bug Report</option>
                <option value="Partnership">Partnership Inquiry</option>
            </select>
        </div>

        <div class="form-group">
            <label for="message">Message *</label>
            <textarea id="message" name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="submit-btn">Send Message</button>
    </form>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
