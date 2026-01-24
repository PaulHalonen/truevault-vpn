<?php
/**
 * Self-Service: Cancel Subscription
 * Cancel TrueVault VPN subscription
 */

$cancelStep = $_POST['step'] ?? 1;
$cancelMessage = '';
?>

<style>
    .cancel-warning {
        background: rgba(255,80,80,0.1);
        border: 1px solid rgba(255,80,80,0.3);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .cancel-warning h4 { color: #ff5050; margin-bottom: 10px; }
    .benefit-lost {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
        margin-bottom: 8px;
        color: #ccc;
    }
    .benefit-lost i { color: #ff5050; }
    .retention-offer {
        background: linear-gradient(135deg, rgba(0,212,255,0.1), rgba(123,44,191,0.1));
        border: 2px solid <?php echo $primaryColor; ?>;
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        margin: 20px 0;
    }
    .retention-offer h3 { color: <?php echo $primaryColor; ?>; margin-bottom: 10px; }
    .retention-offer .discount { font-size: 2rem; font-weight: 700; color: #00c864; }
    .reason-select {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #fff;
        margin-bottom: 15px;
    }
    .feedback-text {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #fff;
        min-height: 100px;
        resize: vertical;
    }
</style>

<?php if ($cancelStep == 3): // Final confirmation ?>

<div style="text-align: center; padding: 30px;">
    <div style="font-size: 4rem; margin-bottom: 20px;">😢</div>
    <h3 style="margin-bottom: 10px;">We're sorry to see you go</h3>
    <p style="color: #888; margin-bottom: 20px;">Your subscription has been cancelled. You'll have access until the end of your current billing period.</p>
    <p style="color: #666; font-size: 0.9rem;">Changed your mind? You can reactivate anytime from your dashboard.</p>
</div>

<?php elseif ($cancelStep == 2): // Show retention offer ?>

<div class="retention-offer">
    <h3>🎁 Wait! We have a special offer for you</h3>
    <p style="color: #ccc; margin-bottom: 15px;">We'd hate to lose you. How about this?</p>
    <div class="discount">50% OFF</div>
    <p style="color: #888; margin: 10px 0;">Your next 3 months at half price!</p>
    <form method="POST" style="display: inline;">
        <input type="hidden" name="step" value="1">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-gift"></i> Accept Offer & Stay
        </button>
    </form>
</div>

<form method="POST">
    <input type="hidden" name="step" value="3">
    <h4 style="margin: 20px 0 10px;">Help us improve - why are you leaving?</h4>
    <select name="reason" class="reason-select" required>
        <option value="">Select a reason...</option>
        <option value="too_expensive">Too expensive</option>
        <option value="not_using">Not using it enough</option>
        <option value="technical_issues">Technical issues</option>
        <option value="found_alternative">Found a better alternative</option>
        <option value="no_longer_need">No longer need a VPN</option>
        <option value="other">Other reason</option>
    </select>
    
    <textarea name="feedback" class="feedback-text" placeholder="Any additional feedback? (optional)"></textarea>
    
    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <a href="?" class="btn btn-secondary" style="flex: 1; justify-content: center;">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>
        <button type="submit" class="btn btn-danger" style="flex: 1; justify-content: center;">
            <i class="fas fa-times"></i> Confirm Cancellation
        </button>
    </div>
</form>

<?php else: // Step 1 - Warning ?>

<div class="cancel-warning">
    <h4><i class="fas fa-exclamation-triangle"></i> Before you cancel...</h4>
    <p style="color: #ccc; margin-bottom: 15px;">You'll lose access to these benefits:</p>
    
    <div class="benefit-lost"><i class="fas fa-times-circle"></i> Encrypted VPN protection on all devices</div>
    <div class="benefit-lost"><i class="fas fa-times-circle"></i> Access to servers in 50+ countries</div>
    <div class="benefit-lost"><i class="fas fa-times-circle"></i> Your saved configurations and settings</div>
    <div class="benefit-lost"><i class="fas fa-times-circle"></i> Priority customer support</div>
</div>

<div style="background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
    <h4 style="margin-bottom: 15px;">Maybe one of these would help?</h4>
    
    <a href="?action=pause_subscription" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,180,0,0.1); border-radius: 8px; text-decoration: none; color: #ccc; margin-bottom: 10px;">
        <span style="font-size: 1.5rem;">⏸️</span>
        <div>
            <strong style="color: #ffb400;">Pause instead?</strong>
            <div style="font-size: 0.85rem;">Take a break for up to 30 days</div>
        </div>
    </a>
    
    <a href="/support/new-ticket" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(0,212,255,0.1); border-radius: 8px; text-decoration: none; color: #ccc;">
        <span style="font-size: 1.5rem;">💬</span>
        <div>
            <strong style="color: <?php echo $primaryColor; ?>;">Having issues?</strong>
            <div style="font-size: 0.85rem;">Talk to support - we want to help!</div>
        </div>
    </a>
</div>

<form method="POST">
    <input type="hidden" name="step" value="2">
    <div style="display: flex; gap: 10px;">
        <a href="/dashboard" class="btn btn-secondary" style="flex: 1; justify-content: center;">
            <i class="fas fa-home"></i> Keep My Subscription
        </a>
        <button type="submit" class="btn btn-danger" style="flex: 1; justify-content: center;">
            Continue to Cancel <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</form>

<?php endif; ?>
