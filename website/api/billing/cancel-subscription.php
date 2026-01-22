<?php
/**
 * Cancel Subscription API - SQLITE3 VERSION
 * 
 * PURPOSE: Cancel user's PayPal subscription
 * METHOD: POST
 * ENDPOINT: /api/billing/cancel-subscription.php
 * REQUIRES: Bearer token
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Authenticate user
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    
    // Get reason from input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $reason = $data['reason'] ?? 'Customer requested cancellation';
    
    // Find active subscription
    $billingDb = Database::getInstance('billing');
    $stmt = $billingDb->prepare("
        SELECT id, paypal_subscription_id, plan_type 
        FROM subscriptions 
        WHERE user_id = :user_id AND status = 'active'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$subscription) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'No active subscription found']);
        exit;
    }
    
    // Cancel on PayPal
    $result = PayPal::cancelSubscription($subscription['paypal_subscription_id'], $reason);
    
    if ($result['success'] || $result['http_code'] === 204) {
        // Update local subscription
        $stmt = $billingDb->prepare("
            UPDATE subscriptions 
            SET status = 'cancelled', 
                cancelled_at = datetime('now'),
                cancellation_reason = :reason,
                updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->bindValue(':reason', $reason, SQLITE3_TEXT);
        $stmt->bindValue(':id', $subscription['id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        // Update user status
        $usersDb = Database::getInstance('users');
        $stmt = $usersDb->prepare("UPDATE users SET subscription_status = 'cancelled', updated_at = datetime('now') WHERE id = :id");
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Log event
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (:user_id, 'subscription_cancelled', 'subscription', :sub_id, :details, :ip, datetime('now'))
        ");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':sub_id', $subscription['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':details', json_encode(['reason' => $reason]), SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription cancelled successfully. You will retain access until the end of your billing period.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to cancel subscription with PayPal'
        ]);
    }
    
} catch (Exception $e) {
    logError('Cancel subscription failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
