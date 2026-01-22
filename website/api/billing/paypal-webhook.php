<?php
/**
 * PayPal Webhook Handler - SQLITE3 VERSION
 * 
 * PURPOSE: Receive and process PayPal webhook events
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

// PayPal sends POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Get raw body and headers
$body = file_get_contents('php://input');
$headers = getallheaders();

// Normalize header keys (PayPal uses various cases)
$normalizedHeaders = [];
foreach ($headers as $key => $value) {
    $normalizedHeaders[strtoupper($key)] = $value;
}

// Log incoming webhook
$logsDb = Database::getInstance('logs');
$stmt = $logsDb->prepare("
    INSERT INTO webhook_log (source, event_type, payload, headers, created_at)
    VALUES ('paypal', :event_type, :payload, :headers, datetime('now'))
");

$event = json_decode($body, true);
$eventType = $event['event_type'] ?? 'unknown';

$stmt->bindValue(':event_type', $eventType, SQLITE3_TEXT);
$stmt->bindValue(':payload', $body, SQLITE3_TEXT);
$stmt->bindValue(':headers', json_encode($normalizedHeaders), SQLITE3_TEXT);
$stmt->execute();
$webhookLogId = $logsDb->lastInsertRowID();

try {
    // Verify webhook signature (skip in development if needed)
    $verifySignature = true; // Set to false for testing
    
    if ($verifySignature) {
        $isValid = PayPal::verifyWebhookSignature($normalizedHeaders, $body);
        if (!$isValid) {
            logError('PayPal webhook signature verification failed', ['webhook_log_id' => $webhookLogId]);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
    }
    
    // Process event based on type
    $resource = $event['resource'] ?? [];
    $subscriptionId = $resource['id'] ?? $resource['billing_agreement_id'] ?? null;
    $customId = $resource['custom_id'] ?? null; // User ID we passed when creating
    
    switch ($eventType) {
        
        case 'BILLING.SUBSCRIPTION.ACTIVATED':
            handleSubscriptionActivated($subscriptionId, $customId, $resource);
            break;
            
        case 'BILLING.SUBSCRIPTION.CANCELLED':
            handleSubscriptionCancelled($subscriptionId, $resource);
            break;
            
        case 'BILLING.SUBSCRIPTION.SUSPENDED':
            handleSubscriptionSuspended($subscriptionId, $resource);
            break;
            
        case 'PAYMENT.SALE.COMPLETED':
            handlePaymentCompleted($resource);
            break;
            
        case 'PAYMENT.SALE.REFUNDED':
            handlePaymentRefunded($resource);
            break;
            
        default:
            // Log unknown event type but return success
            logError('Unknown PayPal webhook event', ['event_type' => $eventType]);
    }
    
    // Update webhook log with processing result
    $stmt = $logsDb->prepare("UPDATE webhook_log SET processed = 1, processed_at = datetime('now') WHERE id = :id");
    $stmt->bindValue(':id', $webhookLogId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Return 200 to acknowledge receipt
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    logError('PayPal webhook processing error: ' . $e->getMessage(), ['webhook_log_id' => $webhookLogId]);
    
    // Update webhook log with error
    $stmt = $logsDb->prepare("UPDATE webhook_log SET error = :error WHERE id = :id");
    $stmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
    $stmt->bindValue(':id', $webhookLogId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Still return 200 to prevent PayPal retries (we logged the error)
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Handle subscription activated
 */
function handleSubscriptionActivated($subscriptionId, $customId, $resource) {
    $billingDb = Database::getInstance('billing');
    $usersDb = Database::getInstance('users');
    
    // Find subscription in our database
    $stmt = $billingDb->prepare("SELECT id, user_id, plan_type FROM subscriptions WHERE paypal_subscription_id = :id");
    $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    $userId = $subscription['user_id'] ?? $customId;
    $planType = $subscription['plan_type'] ?? 'standard';
    
    if ($subscription) {
        // Update existing subscription
        $stmt = $billingDb->prepare("
            UPDATE subscriptions 
            SET status = 'active', 
                activated_at = datetime('now'),
                updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $subscription['id'], SQLITE3_INTEGER);
        $stmt->execute();
    } else {
        // Create subscription record
        $stmt = $billingDb->prepare("
            INSERT INTO subscriptions (user_id, paypal_subscription_id, plan_type, status, activated_at, created_at, updated_at)
            VALUES (:user_id, :paypal_id, :plan_type, 'active', datetime('now'), datetime('now'), datetime('now'))
        ");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':paypal_id', $subscriptionId, SQLITE3_TEXT);
        $stmt->bindValue(':plan_type', $planType, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    // Update user tier
    if ($userId) {
        $tier = ($planType === 'pro') ? 'pro' : 'standard';
        $stmt = $usersDb->prepare("UPDATE users SET tier = :tier, subscription_status = 'active', updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':tier', $tier, SQLITE3_TEXT);
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // Log event
    logAudit($userId, 'subscription_activated', 'subscription', $subscriptionId, ['plan' => $planType]);
}

/**
 * Handle subscription cancelled
 */
function handleSubscriptionCancelled($subscriptionId, $resource) {
    $billingDb = Database::getInstance('billing');
    $usersDb = Database::getInstance('users');
    
    // Update subscription
    $stmt = $billingDb->prepare("
        UPDATE subscriptions 
        SET status = 'cancelled', 
            cancelled_at = datetime('now'),
            updated_at = datetime('now')
        WHERE paypal_subscription_id = :id
    ");
    $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Get user ID
    $stmt = $billingDb->prepare("SELECT user_id FROM subscriptions WHERE paypal_subscription_id = :id");
    $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($subscription) {
        // Update user status (keep tier until subscription period ends)
        $stmt = $usersDb->prepare("UPDATE users SET subscription_status = 'cancelled', updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':id', $subscription['user_id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        logAudit($subscription['user_id'], 'subscription_cancelled', 'subscription', $subscriptionId, []);
    }
}

/**
 * Handle subscription suspended
 */
function handleSubscriptionSuspended($subscriptionId, $resource) {
    $billingDb = Database::getInstance('billing');
    $usersDb = Database::getInstance('users');
    
    // Update subscription
    $stmt = $billingDb->prepare("
        UPDATE subscriptions 
        SET status = 'suspended', 
            updated_at = datetime('now')
        WHERE paypal_subscription_id = :id
    ");
    $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Get user ID
    $stmt = $billingDb->prepare("SELECT user_id FROM subscriptions WHERE paypal_subscription_id = :id");
    $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($subscription) {
        // Downgrade user to free tier
        $stmt = $usersDb->prepare("UPDATE users SET tier = 'free', subscription_status = 'suspended', updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':id', $subscription['user_id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        logAudit($subscription['user_id'], 'subscription_suspended', 'subscription', $subscriptionId, []);
    }
}

/**
 * Handle payment completed
 */
function handlePaymentCompleted($resource) {
    $billingDb = Database::getInstance('billing');
    
    $amount = $resource['amount']['total'] ?? '0.00';
    $currency = $resource['amount']['currency'] ?? 'USD';
    $paymentId = $resource['id'] ?? '';
    $subscriptionId = $resource['billing_agreement_id'] ?? '';
    
    // Get subscription info
    $userId = null;
    if ($subscriptionId) {
        $stmt = $billingDb->prepare("SELECT user_id FROM subscriptions WHERE paypal_subscription_id = :id");
        $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
        $result = $stmt->execute();
        $subscription = $result->fetchArray(SQLITE3_ASSOC);
        $userId = $subscription['user_id'] ?? null;
    }
    
    // Create invoice
    $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(md5($paymentId), 0, 6));
    
    $stmt = $billingDb->prepare("
        INSERT INTO invoices (user_id, invoice_number, amount, currency, status, paypal_payment_id, paid_at, created_at)
        VALUES (:user_id, :invoice_number, :amount, :currency, 'paid', :payment_id, datetime('now'), datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':invoice_number', $invoiceNumber, SQLITE3_TEXT);
    $stmt->bindValue(':amount', $amount, SQLITE3_TEXT);
    $stmt->bindValue(':currency', $currency, SQLITE3_TEXT);
    $stmt->bindValue(':payment_id', $paymentId, SQLITE3_TEXT);
    $stmt->execute();
    
    if ($userId) {
        logAudit($userId, 'payment_completed', 'invoice', $invoiceNumber, ['amount' => $amount, 'currency' => $currency]);
    }
}

/**
 * Handle payment refunded
 */
function handlePaymentRefunded($resource) {
    $billingDb = Database::getInstance('billing');
    
    $paymentId = $resource['sale_id'] ?? $resource['id'] ?? '';
    $refundAmount = $resource['amount']['total'] ?? '0.00';
    
    // Update invoice if found
    $stmt = $billingDb->prepare("
        UPDATE invoices 
        SET status = 'refunded', 
            refunded_at = datetime('now'),
            refund_amount = :amount
        WHERE paypal_payment_id = :payment_id
    ");
    $stmt->bindValue(':amount', $refundAmount, SQLITE3_TEXT);
    $stmt->bindValue(':payment_id', $paymentId, SQLITE3_TEXT);
    $stmt->execute();
    
    logError('Payment refunded', ['payment_id' => $paymentId, 'amount' => $refundAmount]);
}

/**
 * Helper: Log audit event
 */
function logAudit($userId, $action, $entityType, $entityId, $details) {
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
        VALUES (:user_id, :action, :entity_type, :entity_id, :details, 'webhook', datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':action', $action, SQLITE3_TEXT);
    $stmt->bindValue(':entity_type', $entityType, SQLITE3_TEXT);
    $stmt->bindValue(':entity_id', $entityId, SQLITE3_TEXT);
    $stmt->bindValue(':details', json_encode($details), SQLITE3_TEXT);
    $stmt->execute();
}
