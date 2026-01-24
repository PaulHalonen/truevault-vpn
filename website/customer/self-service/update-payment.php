<?php
/**
 * Self-Service: Update Payment Method
 * Redirect to PayPal for payment method update
 */
?>

<style>
    .payment-info {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .current-method {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .method-icon { font-size: 2rem; }
    .method-details h4 { margin-bottom: 5px; }
    .method-details p { color: #888; font-size: 0.9rem; }
    .paypal-button {
        background: #0070ba;
        color: #fff;
        padding: 15px 30px;
        border: none;
        border-radius: 25px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
        text-decoration: none;
    }
    .paypal-button:hover { background: #005ea6; }
    .security-note {
        margin-top: 20px;
        padding: 15px;
        background: rgba(0,200,100,0.1);
        border: 1px solid rgba(0,200,100,0.2);
        border-radius: 10px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #00c864;
        font-size: 0.85rem;
    }
</style>

<p style="color: #aaa; margin-bottom: 20px;">Update your payment method securely through PayPal:</p>

<div class="payment-info">
    <h4 style="margin-bottom: 15px; color: #888; font-size: 0.9rem;">Current Payment Method</h4>
    <div class="current-method">
        <span class="method-icon">💳</span>
        <div class="method-details">
            <h4>PayPal</h4>
            <p>Linked to: <?php echo htmlspecialchars($customerEmail ?: 'your@email.com'); ?></p>
        </div>
    </div>
</div>

<div style="text-align: center; padding: 20px;">
    <p style="color: #ccc; margin-bottom: 20px;">Click below to securely update your payment method via PayPal:</p>
    
    <a href="https://www.paypal.com/myaccount/autopay/" target="_blank" class="paypal-button">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .757-.64h6.302c2.79 0 4.724 1.793 4.453 4.413-.325 3.147-2.713 4.5-5.453 4.5H8.678l-.944 6.094a.641.641 0 0 1-.633.54h.025l-.05 2.71Z"/>
            <path d="M19.925 7.52c-.327 3.147-2.715 5.5-5.455 5.5h-2.325l-.944 6.094a.641.641 0 0 1-.633.54H7.076l-.05 2.71h3.41a.641.641 0 0 0 .633-.54l.894-5.76h2.29c3.148 0 5.632-2.295 5.97-5.5.18-1.733-.348-3.268-1.298-4.244Z"/>
        </svg>
        Update via PayPal
    </a>
</div>

<div class="security-note">
    <i class="fas fa-shield-alt"></i>
    <div>
        <strong>Secure Transaction</strong><br>
        You'll be redirected to PayPal's secure site. We never store your full payment details.
    </div>
</div>

<p style="color: #666; font-size: 0.8rem; margin-top: 20px; text-align: center;">
    Having trouble? <a href="/support/new-ticket?category=billing" style="color: <?php echo $primaryColor; ?>;">Contact billing support</a>
</p>
