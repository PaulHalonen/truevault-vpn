<?php
/**
 * PayPal Webhook Handler - SQLITE3 VERSION
 * 
 * PURPOSE: Process PayPal webhook events
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

// Normalize header keys to uppercase
$normalizedHeaders = [];
foreach ($headers as $key => $value) {
    $normalizedHeaders[strtoupper(str_replace('-', '-', $key))] = $value;
}

// Log incoming webhook
$logsDb = Database::getInstance('logs');
$stmt = $logsDb->prepare("
    INSERT INTO webhook_log (source, event_type, payload, received_at)
    VALUES ('paypal', :event_type, :payload, datetime('now'))
");

$event = json_decode($body, true);
$eventType = $event['event_type'] ?? 'unknown';

$stmt->bindValue(':event_type', $eventType, SQLITE3_TEXT);
$stmt->bindValue(':payload', $body, SQLITE3_TEXT);
$stmt->execute();
$webhookLogId = $logsDb->lastInsertRowID();

try {
    // Verify webhook signature (skip in development if needed)
    $adminDb = Database::getInstance('admin');
    $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'paypal_verify_webhooks'");
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $verifyWebhooks = ($row['setting_value'] ?? 'true') === 'true';
    
    if ($verifyWebhooks) {
        $isValid = PayPal::verifyWebhookSignature($normalizedHeaders, $body);
        if (!$isValid) {
            logError('PayPal webhook signature verification failed', ['webhook_log_id' => $webhookLogId]);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
    }
    
    // Process event
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
            // Log unhandled event type
            logError('Unhandled PayPal event', ['event_type' => $eventType]);
    }
    
    // Update webhook log as processed
    $stmt = $logsDb->prepare("UPDATE webhook_log SET processed = 1, processed_at = datetime('now') WHERE id = :id");
    $stmt->bindValue(':id', $webhookLogId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Always return 200 to PayPal
    http_response_code(200);
    echo json_encode(['status' => 'processed']);
    
} catch (Exception $e) {
    logError('PayPal webhook error: ' . $e->getMessage(), ['webhook_log_id' => $webhookLogId]);
    
    // Update webhook log with error
    $stmt = $logsDb->prepare("UPDATE webhook_log SET error = :error WHERE id = :id");
    $stmt->bindValue(':error', $e->getMessage(), SQLITE3_TEXT);
    $stmt->bindValue(':id', $webhookLogId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Still return 200 to prevent PayPal retries
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Handle subscription activated
 */
function handleSubscriptionActivated($subscriptionId, $customId, $resource) {
    $billingDb = Database::getInstance('billing');
    $usersDb = Database::getInstance('users');
    
    // Find subscription by PayPal ID
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
        // Create new subscription record
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
    
    logAudit($userId, 'subscription_activated', 'subscription', $subscriptionId);
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
    
    // Get user ID and downgrade
    $stmt = $billingDb->prepare("SELECT user_id FROM subscriptions WHERE paypal_subscription_id = :id");
    $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($subscription) {
        $stmt = $usersDb->prepare("UPDATE users SET subscription_status = 'cancelled', updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':id', $subscription['user_id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        logAudit($subscription['user_id'], 'subscription_cancelled', 'subscription', $subscriptionId);
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
        SET status = 'suspended', updated_at = datetime('now')
        WHERE paypal_subscription_id = :id
    ");
    $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Get user and update status
    $stmt = $billingDb->prepare("SELECT user_id FROM subscriptions WHERE paypal_subscription_id = :id");
    $stmt->bindValue(':id', $subscriptionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($subscription) {
        $stmt = $usersDb->prepare("UPDATE users SET subscription_status = 'suspended', updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':id', $subscription['user_id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        logAudit($subscription['user_id'], 'subscription_suspended', 'subscription', $subscriptionId);
    }
}

/**
 * Handle payment completed
 */
function handlePaymentCompleted($resource) {
    $billingDb = Database::getInstance('billing');
    
    $amount = $resource['amount']['total'] ?? '0.00';
    $currency = $resource['amount']['currency'] ?? 'USD';
    $transactionId = $resource['id'] ?? '';
    $billingAgreementId = $resource['billing_agreement_id'] ?? '';
    
    // Find subscription
    $stmt = $billingDb->prepare("SELECT id, user_id, plan_type FROM subscriptions WHERE paypal_subscription_id = :id");
    $stmt->bindValue(':id', $billingAgreementId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$subscription) {
        return; // Can't find subscription
    }
    
    // Check for duplicate transaction
    $stmt = $billingDb->prepare("SELECT id FROM invoices WHERE transaction_id = :id");
    $stmt->bindValue(':id', $transactionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result->fetchArray()) {
        return; // Already processed
    }
    
    // Create invoice
    $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($subscription['user_id'], 5, '0', STR_PAD_LEFT) . '-' . substr(uniqid(), -4);
    
    $stmt = $billingDb->prepare("
        INSERT INTO invoices (user_id, subscription_id, invoice_number, amount, currency, status, transaction_id, paid_at, created_at)
        VALUES (:user_id, :sub_id, :invoice_num, :amount, :currency, 'paid', :trans_id, datetime('now'), datetime('now'))
    ");
    $stmt->bindValue(':user_id', $subscription['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':sub_id', $subscription['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':invoice_num', $invoiceNumber, SQLITE3_TEXT);
    $stmt->bindValue(':amount', $amount, SQLITE3_TEXT);
    $stmt->bindValue(':currency', $currency, SQLITE3_TEXT);
    $stmt->bindValue(':trans_id', $transactionId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Update subscription next billing date
    $stmt = $billingDb->prepare("UPDATE subscriptions SET next_billing_date = date('now', '+1 month'), updated_at = datetime('now') WHERE id = :id");
    $stmt->bindValue(':id', $subscription['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    logAudit($subscription['user_id'], 'payment_received', 'invoice', $invoiceNumber, ['amount' => $amount]);
}

/**
 * Handle payment refunded
 */
function handlePaymentRefunded($resource) {
    $billingDb = Database::getInstance('billing');
    
    $transactionId = $resource['sale_id'] ?? $resource['id'] ?? '';
    $refundAmount = $resource['amount']['total'] ?? '0.00';
    
    // Find and update invoice
    $stmt = $billingDb->prepare("
        UPDATE invoices 
        SET status = 'refunded', 
            refunded_at = datetime('now'),
            refund_amount = :amount
        WHERE transaction_id = :trans_id
    ");
    $stmt->bindValue(':amount', $refundAmount, SQLITE3_TEXT);
    $stmt->bindValue(':trans_id', $transactionId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Get user for logging
    $stmt = $billingDb->prepare("SELECT user_id FROM invoices WHERE transaction_id = :id");
    $stmt->bindValue(':id', $transactionId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $invoice = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($invoice) {
        logAudit($invoice['user_id'], 'payment_refunded', 'invoice', $transactionId, ['amount' => $refundAmount]);
    }
}

/**
 * Log audit event
 */
function logAudit($userId, $action, $entityType, $entityId, $details = []) {
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
