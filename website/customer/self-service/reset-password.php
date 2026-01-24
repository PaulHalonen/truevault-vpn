<?php
/**
 * Self-Service: Reset Password
 * Allows password reset via email
 */

$resetMessage = '';
$resetType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_email'])) {
    $email = filter_var($_POST['reset_email'], FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        // In production, this would send a reset email
        // For now, simulate success
        $resetMessage = "If an account exists with that email, you'll receive a password reset link shortly.";
        $resetType = 'success';
        
        // Record action
        $automationDb = new SQLite3(__DIR__ . '/../../admin/automation/databases/automation.db');
        $automationDb->exec("UPDATE self_service_actions SET times_used = times_used + 1 WHERE action_key = 'reset_password'");
        $automationDb->close();
    } else {
        $resetMessage = "Please enter a valid email address.";
        $resetType = 'error';
    }
}
?>

<style>
    .reset-form { max-width: 400px; margin: 0 auto; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; color: #aaa; font-size: 0.9rem; }
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #fff;
        font-size: 1rem;
    }
    .form-group input:focus {
        outline: none;
        border-color: <?php echo $primaryColor; ?>;
    }
    .alert {
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-success { background: rgba(0,200,100,0.15); border: 1px solid rgba(0,200,100,0.3); color: #00c864; }
    .alert-error { background: rgba(255,80,80,0.15); border: 1px solid rgba(255,80,80,0.3); color: #ff5050; }
    .info-text { color: #888; font-size: 0.85rem; margin-top: 15px; text-align: center; }
</style>

<div class="reset-form">
    <?php if ($resetMessage): ?>
    <div class="alert alert-<?php echo $resetType; ?>">
        <?php echo htmlspecialchars($resetMessage); ?>
    </div>
    <?php endif; ?>
    
    <p style="color: #aaa; margin-bottom: 20px;">Enter your email address and we'll send you a link to reset your password.</p>
    
    <form method="POST">
        <div class="form-group">
            <label for="reset_email">Email Address</label>
            <input type="email" id="reset_email" name="reset_email" placeholder="your@email.com" required>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
            <i class="fas fa-paper-plane"></i> Send Reset Link
        </button>
    </form>
    
    <p class="info-text">
        <i class="fas fa-info-circle"></i> 
        Check your spam folder if you don't see the email within 5 minutes.
    </p>
</div>
