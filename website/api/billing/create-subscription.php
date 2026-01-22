<?php
/**
 * Create Subscription API - SQLITE3 VERSION
 * 
 * PURPOSE: Start PayPal subscription process
 * METHOD: POST
 * ENDPOINT: /api/billing/create-subscription.php
 * REQUIRES: Bearer token
 * 
 * REQUEST: { "plan": "standard" | "pro" }
 * RESPONSE: { "success": true, "approval_url": "https://paypal.com/..." }
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
    $userTier = $payload['tier'];
    
    // VIP users don't need subscriptions
    if ($userTier === 'vip') {
        echo json_encode([
            'success' => true,
            'message' => 'VIP accounts have free access - no subscription needed!'
        ]);
        exit;
    }
    
    // Get requested plan
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $plan = $data['plan'] ?? 'standard';
    
    if (!in_array($plan, ['standard', 'pro'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid plan. Choose: standard or pro']);
        exit;
    }
    
    // Check for existing active subscription
    $billingDb = Database::getInstance('billing');
    $stmt = $billingDb->prepare("
        SELECT id, paypal_subscription_id, status 
        FROM subscriptions 
        WHERE user_id = :user_id AND status IN ('active', 'pending')
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $existing = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($existing && $existing['status'] === 'active') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'You already have an active subscription',
            'subscription_id' => $existing['paypal_subscription_id']
        ]);
        exit;
    }
    
    // Build return URLs
    $baseUrl = 'https://vpn.the-truth-publishing.com';
    $returnUrl = $baseUrl . '/dashboard/subscription-success.php';
    $cancelUrl = $baseUrl . '/dashboard/subscription-cancelled.php';
    
    // Create PayPal subscription
    $result = PayPal::createSubscription($userId, $plan, $returnUrl, $cancelUrl);
    
    if ($result['success']) {
        // Log event
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (:user_id, 'subscription_started', 'subscription', 0, :details, :ip, datetime('now'))
        ");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':details', json_encode(['plan' => $plan, 'subscription_id' => $result['subscription_id']]), SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'subscription_id' => $result['subscription_id'],
            'approval_url' => $result['approval_url'],
            'message' => 'Redirect user to approval_url to complete payment'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to create subscription'
        ]);
    }
    
} catch (Exception $e) {
    logError('Create subscription failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
