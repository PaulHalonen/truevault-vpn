<?php
/**
 * Get Subscription Status API - SQLITE3 VERSION
 * 
 * PURPOSE: Return user's current subscription status
 * METHOD: GET
 * ENDPOINT: /api/billing/status.php
 * REQUIRES: Bearer token
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Authenticate user
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    $userTier = $payload['tier'];
    
    // Check if VIP (free access)
    if ($userTier === 'vip') {
        echo json_encode([
            'success' => true,
            'subscription' => [
                'status' => 'vip',
                'tier' => 'vip',
                'message' => 'VIP accounts have free unlimited access',
                'expires' => null
            ],
            'invoices' => []
        ]);
        exit;
    }
    
    // Get subscription
    $billingDb = Database::getInstance('billing');
    $stmt = $billingDb->prepare("
        SELECT id, paypal_subscription_id, plan_type, status, activated_at, cancelled_at, created_at
        FROM subscriptions 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    // Get recent invoices
    $stmt = $billingDb->prepare("
        SELECT invoice_number, amount, currency, status, paid_at, created_at
        FROM invoices 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $invoices = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $invoices[] = $row;
    }
    
    // Get pricing from database
    $adminDb = Database::getInstance('admin');
    $pricing = [];
    $stmt = $adminDb->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'price_%'");
    while ($row = $stmt->fetchArray(SQLITE3_ASSOC)) {
        $pricing[$row['setting_key']] = $row['setting_value'];
    }
    
    echo json_encode([
        'success' => true,
        'subscription' => $subscription ? [
            'id' => $subscription['id'],
            'paypal_id' => $subscription['paypal_subscription_id'],
            'plan' => $subscription['plan_type'],
            'status' => $subscription['status'],
            'activated_at' => $subscription['activated_at'],
            'cancelled_at' => $subscription['cancelled_at']
        ] : null,
        'tier' => $userTier,
        'invoices' => $invoices,
        'pricing' => [
            'standard' => $pricing['price_standard'] ?? '9.97',
            'pro' => $pricing['price_pro'] ?? '14.97',
            'business' => $pricing['price_business'] ?? '39.97'
        ]
    ]);
    
} catch (Exception $e) {
    logError('Get subscription status failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to get subscription status']);
}
