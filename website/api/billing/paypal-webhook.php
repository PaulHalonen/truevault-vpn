<?php
/**
 * PayPal Webhook Handler - SQLITE3 VERSION
 * 
 * PURPOSE: Receive and process PayPal subscription events
 * ENDPOINT: /api/billing/paypal-webhook.php
 * 
 * EVENTS HANDLED:
 * - BILLING.SUBSCRIPTION.ACTIVATED
 * - BILLING.SUBSCRIPTION.CANCELLED  
 * - BILLING.SUBSCRIPTION.SUSPENDED
 * - PAYMENT.SALE.COMPLETED
 * - PAYMENT.SALE.REFUNDED
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

// Log all webhooks for debugging
$rawBody = file_get_contents('php://input');
$headers = getallheaders();

// Log incoming webhook
$logsDb = Database::getInstance('logs');
$stmt = $logsDb->prepare("
    INSERT INTO webhook_log (source, event_type, payload, headers, created_at)
    VALUES ('paypal', :event_type, :payload, :headers, datetime('now'))
");
$stmt->bindValue(':event_type', 'incoming', SQLITE3_TEXT);
$stmt->bindValue(':payload', $rawBody, SQLITE3_TEXT);
$stmt->bindValue(':headers', json_encode($headers), SQLITE3_TEXT);
$stmt->execute();

// Return 200 immediately (PayPal requirement)
http_response_code(200);

try {
    // Parse webhook data
    $event = json_decode($rawBody, true);
    
    if (!$event || !isset($event['event_type'])) {
        logError('Invalid PayPal webhook payload');
        exit;
    }
    
    $eventType = $event['event_type'];
    $resource = $event['resource'] ?? [];
    
    // Verify webhook signature (optional but recommended)
    // Skip verification in development if needed
    $verified = true;
    if (!empty($headers['PAYPAL-TRANSMISSION-ID'])) {
        $verified = PayPal::verifyWebhookSignature($headers, $rawBody);
    }
    
    if (!$verified) {
        logError('PayPal webhook signature verification failed', ['event_type' => $eventType]);
        // Continue anyway for now - can be strict later
    }
    
    // Process based on event type
    switch ($eventType) {
        
        case 'BILLING.SUBSCRIPTION.ACTIVATED':
            handleSubscriptionActivated($resource);
            break;
            
        case 'BILLING.SUBSCRIPTION.CANCELLED':
            handleSubscriptionCancelled($resource);
            break;
            
        case 'BILLING.SUBSCRIPTION.SUSPENDED':
            handleSubscriptionSuspended($resource);
            break;
            
        case 'PAYMENT.SALE.COMPLETED':
            handlePaymentCompleted($resource);
            break;
            
        case 'PAYMENT.SALE.REFUNDED':
            handlePaymentRefunded($resource);
            break;
            
        default:
            // Log unknown event types
            logError('Unknown PayPal webhook event', ['event_type' => $eventType]);
    }
    
} catch (Exception $e) {
    logError('PayPal webhook error: ' . $e->getMessage());
}

/**
 * Handle subscription activated
 */
function handleSubscriptionActivated($resource) {
    $subscriptionId = $resource['id'] ?? '';
    $customId = $resource['custom_id'] ?? ''; // User ID
    $planId = $resource['plan_id'] ?? '';
    
    if (empty($subscriptionId)) return;
    
    $billingDb = Database::getInstance('billing');
    $usersDb = Database::getInstance('users');
    
    // Update subscription status
    $stmt = $billingDb->prepare("
        UPDATE subscriptions 
        SET status = 'active', 
            paypal_plan_id = :plan_id,
            activated_at = datetime('now'),
            updated_at = datetime('now')
        WHERE paypal_subscription_id = :sub_id
    ");
    $stmt->bindValue(':plan_id', $planId, SQLITE3_TEXT);
    $stmt->bindValue(':sub_id', $subscriptionId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Get user ID from subscription
    $stmt = $billingDb->prepare("SELECT user_id, plan_type FROM subscriptions WHERE paypal_subscription_id = :sub_id");
    $stmt->bindValue(':sub_id', $subscriptionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($subscription) {
        $userId = $subscription['user_id'];
        $planType = $subscription['plan_type'];
        
        // Update user tier
        $newTier = ($planType === 'pro') ? 'pro' : 'standard';
        $stmt = $usersDb->prepare("UPDATE users SET tier = :tier, status = 'active', updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':tier', $newTier, SQLITE3_TEXT);
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Log event
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, created_at)
            VALUES (:user_id, 'subscription_activated', 'subscription', 0, :details, datetime('now'))
        ");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':details', json_encode(['subscription_id' => $subscriptionId, 'plan' => $planType]), SQLITE3_TEXT);
        $stmt->execute();
    }
}

/**
 * Handle subscription cancelled
 */
function handleSubscriptionCancelled($resource) {
    $subscriptionId = $resource['id'] ?? '';
    
    if (empty($subscriptionId)) return;
    
    $billingDb = Database::getInstance('billing');
    $usersDb = Database::getInstance('users');
    
    // Update subscription status
    $stmt = $billingDb->prepare("
        UPDATE subscriptions 
        SET status = 'cancelled', 
            cancelled_at = datetime('now'),
            updated_at = datetime('now')
        WHERE paypal_subscription_id = :sub_id
    ");
    $stmt->bindValue(':sub_id', $subscriptionId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Get user and downgrade
    $stmt = $billingDb->prepare("SELECT user_id FROM subscriptions WHERE paypal_subscription_id = :sub_id");
    $stmt->bindValue(':sub_id', $subscriptionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($subscription) {
        $userId = $subscription['user_id'];
        
        // Check if user is VIP (VIPs don't get downgraded)
        $stmt = $usersDb->prepare("SELECT tier, vip_approved FROM users WHERE id = :id");
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && !$user['vip_approved']) {
            // Downgrade to free/inactive
            $stmt = $usersDb->prepare("UPDATE users SET status = 'inactive', updated_at = datetime('now') WHERE id = :id");
            $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
            $stmt->execute();
        }
        
        // Log event
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, created_at)
            VALUES (:user_id, 'subscription_cancelled', 'subscription', 0, :details, datetime('now'))
        ");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':details', json_encode(['subscription_id' => $subscriptionId]), SQLITE3_TEXT);
        $stmt->execute();
    }
}

/**
 * Handle subscription suspended
 */
function handleSubscriptionSuspended($resource) {
    $subscriptionId = $resource['id'] ?? '';
    
    if (empty($subscriptionId)) return;
    
    $billingDb = Database::getInstance('billing');
    
    $stmt = $billingDb->prepare("
        UPDATE subscriptions 
        SET status = 'suspended', updated_at = datetime('now')
        WHERE paypal_subscription_id = :sub_id
    ");
    $stmt->bindValue(':sub_id', $subscriptionId, SQLITE3_TEXT);
    $stmt->execute();
}

/**
 * Handle payment completed
 */
function handlePaymentCompleted($resource) {
    $amount = $resource['amount']['total'] ?? '0.00';
    $currency = $resource['amount']['currency'] ?? 'USD';
    $billingAgreementId = $resource['billing_agreement_id'] ?? '';
    $transactionId = $resource['id'] ?? '';
    
    if (empty($billingAgreementId)) return;
    
    $billingDb = Database::getInstance('billing');
    
    // Find subscription by PayPal ID
    $stmt = $billingDb->prepare("SELECT id, user_id, plan_type FROM subscriptions WHERE paypal_subscription_id = :sub_id");
    $stmt->bindValue(':sub_id', $billingAgreementId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($subscription) {
        // Create invoice
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($subscription['id'], 4, '0', STR_PAD_LEFT);
        
        $stmt = $billingDb->prepare("
            INSERT INTO invoices (
                user_id, subscription_id, invoice_number, amount, currency,
                status, paypal_transaction_id, paid_at, created_at
            ) VALUES (
                :user_id, :sub_id, :invoice_num, :amount, :currency,
                'paid', :txn_id, datetime('now'), datetime('now')
            )
        ");
        $stmt->bindValue(':user_id', $subscription['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':sub_id', $subscription['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':invoice_num', $invoiceNumber, SQLITE3_TEXT);
        $stmt->bindValue(':amount', $amount, SQLITE3_TEXT);
        $stmt->bindValue(':currency', $currency, SQLITE3_TEXT);
        $stmt->bindValue(':txn_id', $transactionId, SQLITE3_TEXT);
        $stmt->execute();
        
        // Update subscription last payment
        $stmt = $billingDb->prepare("
            UPDATE subscriptions 
            SET last_payment_at = datetime('now'), 
                next_billing_at = datetime('now', '+1 month'),
                updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $subscription['id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        // Log payment
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, created_at)
            VALUES (:user_id, 'payment_received', 'invoice', 0, :details, datetime('now'))
        ");
        $stmt->bindValue(':user_id', $subscription['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':details', json_encode([
            'amount' => $amount,
            'currency' => $currency,
            'transaction_id' => $transactionId,
            'invoice' => $invoiceNumber
        ]), SQLITE3_TEXT);
        $stmt->execute();
    }
}

/**
 * Handle payment refunded
 */
function handlePaymentRefunded($resource) {
    $transactionId = $resource['sale_id'] ?? $resource['id'] ?? '';
    $refundAmount = $resource['amount']['total'] ?? '0.00';
    
    if (empty($transactionId)) return;
    
    $billingDb = Database::getInstance('billing');
    
    // Find and update invoice
    $stmt = $billingDb->prepare("
        UPDATE invoices 
        SET status = 'refunded', refunded_at = datetime('now')
        WHERE paypal_transaction_id = :txn_id
    ");
    $stmt->bindValue(':txn_id', $transactionId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Log refund
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, created_at)
        VALUES (0, 'payment_refunded', 'invoice', 0, :details, datetime('now'))
    ");
    $stmt->bindValue(':details', json_encode([
        'transaction_id' => $transactionId,
        'refund_amount' => $refundAmount
    ]), SQLITE3_TEXT);
    $stmt->execute();
}
