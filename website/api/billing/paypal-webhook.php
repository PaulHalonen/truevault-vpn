<?php
/**
 * TrueVault VPN - PayPal Webhook Handler
 * 
 * PURPOSE: Handle PayPal webhook events
 * METHOD: POST (from PayPal)
 * AUTHENTICATION: PayPal signature verification
 * 
 * WEBHOOK URL: https://vpn.the-truth-publishing.com/api/billing/paypal-webhook.php
 * WEBHOOK ID: 46924926WL757580D
 * 
 * EVENTS HANDLED:
 * - BILLING.SUBSCRIPTION.ACTIVATED - Subscription activated
 * - BILLING.SUBSCRIPTION.CANCELLED - User cancelled
 * - BILLING.SUBSCRIPTION.SUSPENDED - Payment failed
 * - PAYMENT.SALE.COMPLETED - Payment received
 * - PAYMENT.SALE.REFUNDED - Payment refunded
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Headers
header('Content-Type: application/json');

// Load dependencies
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/PayPal.php';

// Get raw POST data
$rawInput = file_get_contents('php://input');

// Get all headers
$headers = getallheaders();

// Log incoming webhook
error_log('PayPal Webhook Received: ' . substr($rawInput, 0, 200));

try {
    // Decode webhook data
    $webhook = json_decode($rawInput, true);
    
    if (!$webhook || !isset($webhook['event_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook data']);
        exit;
    }
    
    $eventType = $webhook['event_type'];
    $eventId = $webhook['id'] ?? 'unknown';
    
    // Verify webhook signature
    $paypal = new PayPal();
    $webhookId = '46924926WL757580D';
    
    $isValid = $paypal->verifyWebhookSignature($webhookId, $headers, $rawInput);
    
    if (!$isValid) {
        error_log('PayPal Webhook Signature Verification Failed: ' . $eventId);
        http_response_code(403);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    
    // Get database connections
    $db = Database::getInstance();
    $paymentsConn = $db->getConnection('payments');
    $usersConn = $db->getConnection('users');
    
    // Handle different event types
    switch ($eventType) {
        
        case 'BILLING.SUBSCRIPTION.ACTIVATED':
            // Subscription successfully activated
            $resource = $webhook['resource'];
            $subscriptionId = $resource['id'];
            
            // Update subscription status
            $stmt = $paymentsConn->prepare("
                UPDATE subscriptions
                SET status = 'active',
                    activated_at = datetime('now')
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);
            
            // Get user ID from subscription
            $stmt = $paymentsConn->prepare("
                SELECT user_id, plan_id
                FROM subscriptions
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subscription) {
                // Update user status and tier
                $stmt = $usersConn->prepare("
                    UPDATE users
                    SET status = 'active',
                        tier = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$subscription['plan_id'], $subscription['user_id']]);
                
                error_log("Subscription activated: $subscriptionId for user {$subscription['user_id']}");
            }
            break;
            
        case 'BILLING.SUBSCRIPTION.CANCELLED':
            // User cancelled subscription
            $resource = $webhook['resource'];
            $subscriptionId = $resource['id'];
            
            // Update subscription status
            $stmt = $paymentsConn->prepare("
                UPDATE subscriptions
                SET status = 'cancelled',
                    cancelled_at = datetime('now')
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);
            
            // Get user ID
            $stmt = $paymentsConn->prepare("
                SELECT user_id
                FROM subscriptions
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subscription) {
                // Update user status
                $stmt = $usersConn->prepare("
                    UPDATE users
                    SET status = 'cancelled'
                    WHERE user_id = ?
                ");
                $stmt->execute([$subscription['user_id']]);
                
                error_log("Subscription cancelled: $subscriptionId for user {$subscription['user_id']}");
            }
            break;
            
        case 'BILLING.SUBSCRIPTION.SUSPENDED':
            // Subscription suspended (payment failed)
            $resource = $webhook['resource'];
            $subscriptionId = $resource['id'];
            
            // Update subscription status
            $stmt = $paymentsConn->prepare("
                UPDATE subscriptions
                SET status = 'suspended'
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);
            
            // Get user ID
            $stmt = $paymentsConn->prepare("
                SELECT user_id
                FROM subscriptions
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subscription) {
                // Update user status
                $stmt = $usersConn->prepare("
                    UPDATE users
                    SET status = 'suspended'
                    WHERE user_id = ?
                ");
                $stmt->execute([$subscription['user_id']]);
                
                error_log("Subscription suspended: $subscriptionId for user {$subscription['user_id']}");
            }
            break;
            
        case 'PAYMENT.SALE.COMPLETED':
            // Payment completed successfully
            $resource = $webhook['resource'];
            $saleId = $resource['id'];
            $amount = $resource['amount']['total'] ?? '0.00';
            $currency = $resource['amount']['currency'] ?? 'USD';
            
            // Extract subscription ID from billing agreement
            $billingAgreementId = $resource['billing_agreement_id'] ?? null;
            
            if ($billingAgreementId) {
                // Find subscription
                $stmt = $paymentsConn->prepare("
                    SELECT subscription_id, user_id
                    FROM subscriptions
                    WHERE paypal_subscription_id = ?
                ");
                $stmt->execute([$billingAgreementId]);
                $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($subscription) {
                    // Record payment
                    $stmt = $paymentsConn->prepare("
                        INSERT INTO payments (
                            subscription_id,
                            user_id,
                            paypal_sale_id,
                            amount,
                            currency,
                            status,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, 'completed', datetime('now'))
                    ");
                    $stmt->execute([
                        $subscription['subscription_id'],
                        $subscription['user_id'],
                        $saleId,
                        $amount,
                        $currency
                    ]);
                    
                    error_log("Payment completed: $saleId for subscription {$billingAgreementId} ($amount $currency)");
                }
            }
            break;
            
        case 'PAYMENT.SALE.REFUNDED':
            // Payment refunded
            $resource = $webhook['resource'];
            $refundId = $resource['id'];
            $saleId = $resource['sale_id'] ?? null;
            
            if ($saleId) {
                // Update payment status
                $stmt = $paymentsConn->prepare("
                    UPDATE payments
                    SET status = 'refunded',
                        refund_id = ?
                    WHERE paypal_sale_id = ?
                ");
                $stmt->execute([$refundId, $saleId]);
                
                error_log("Payment refunded: $saleId (Refund ID: $refundId)");
            }
            break;
            
        default:
            // Log unhandled event types
            error_log("Unhandled PayPal webhook event: $eventType");
            break;
    }
    
    // Return success
    http_response_code(200);
    echo json_encode(['success' => true, 'event_type' => $eventType]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('PayPal Webhook Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
}
