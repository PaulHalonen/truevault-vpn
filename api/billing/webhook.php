<?php
/**
 * TrueVault VPN - PayPal Webhook Handler
 * Receives and processes PayPal events automatically
 * 
 * Webhook URL: https://vpn.the-truth-publishing.com/api/billing/webhook.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/billing-manager.php';

// Log all webhooks
$rawInput = file_get_contents('php://input');
$webhookId = $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? 'unknown';

Database::execute('billing',
    "INSERT INTO webhook_log (webhook_id, payload, received_at) VALUES (?, ?, datetime('now'))",
    [$webhookId, $rawInput]
);

// Parse event
$event = json_decode($rawInput, true);

if (!$event || !isset($event['event_type'])) {
    http_response_code(400);
    exit('Invalid payload');
}

$eventType = $event['event_type'];
$resource = $event['resource'] ?? [];

// Log event type
Database::execute('billing',
    "UPDATE webhook_log SET event_type = ?, processed = 0 WHERE webhook_id = ?",
    [$eventType, $webhookId]
);

try {
    switch ($eventType) {
        
        // Payment completed
        case 'CHECKOUT.ORDER.APPROVED':
        case 'PAYMENT.CAPTURE.COMPLETED':
            $orderId = $resource['id'] ?? $resource['supplementary_data']['related_ids']['order_id'] ?? null;
            if ($orderId) {
                BillingManager::completePayment($orderId);
            }
            break;
        
        // Payment failed
        case 'PAYMENT.CAPTURE.DENIED':
        case 'PAYMENT.CAPTURE.DECLINED':
            $customId = $resource['custom_id'] ?? null;
            if ($customId) {
                $data = json_decode($customId, true);
                if (isset($data['user_id'])) {
                    BillingManager::handlePaymentFailure($data['user_id']);
                }
            }
            break;
        
        // Subscription cancelled
        case 'BILLING.SUBSCRIPTION.CANCELLED':
            $customId = $resource['custom_id'] ?? null;
            if ($customId) {
                $data = json_decode($customId, true);
                if (isset($data['user_id'])) {
                    BillingManager::cancelSubscription($data['user_id'], 'PayPal cancellation');
                }
            }
            break;
        
        // Subscription suspended (payment issues)
        case 'BILLING.SUBSCRIPTION.SUSPENDED':
            $customId = $resource['custom_id'] ?? null;
            if ($customId) {
                $data = json_decode($customId, true);
                if (isset($data['user_id'])) {
                    BillingManager::handlePaymentFailure($data['user_id']);
                }
            }
            break;
        
        // Refund issued
        case 'PAYMENT.CAPTURE.REFUNDED':
            $customId = $resource['custom_id'] ?? null;
            if ($customId) {
                $data = json_decode($customId, true);
                if (isset($data['user_id'])) {
                    // Immediate revocation on refund
                    PeerManager::revokeAllAccess($data['user_id']);
                    Database::execute('users',
                        "UPDATE users SET status = 'refunded' WHERE id = ?",
                        [$data['user_id']]
                    );
                }
            }
            break;
        
        // Dispute opened
        case 'CUSTOMER.DISPUTE.CREATED':
            // Log for manual review
            Database::execute('billing',
                "INSERT INTO disputes (dispute_id, reason, status, payload, created_at)
                 VALUES (?, ?, 'open', ?, datetime('now'))",
                [$resource['dispute_id'] ?? 'unknown', $resource['reason'] ?? 'unknown', json_encode($resource)]
            );
            break;
        
        default:
            // Log unhandled event types
            break;
    }
    
    // Mark as processed
    Database::execute('billing',
        "UPDATE webhook_log SET processed = 1, processed_at = datetime('now') WHERE webhook_id = ?",
        [$webhookId]
    );
    
    http_response_code(200);
    echo json_encode(['status' => 'processed']);
    
} catch (Exception $e) {
    // Log error
    Database::execute('billing',
        "UPDATE webhook_log SET error = ? WHERE webhook_id = ?",
        [$e->getMessage(), $webhookId]
    );
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
