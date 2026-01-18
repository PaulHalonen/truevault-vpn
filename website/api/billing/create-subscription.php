<?php
/**
 * TrueVault VPN - Create Subscription API
 * 
 * PURPOSE: Create PayPal subscription for user
 * METHOD: POST
 * AUTHENTICATION: JWT required
 * 
 * REQUEST BODY:
 * {
 *   "plan": "standard|pro|vip"
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "subscription_id": "I-XXXXXXXXXX",
 *   "approval_url": "https://www.paypal.com/...",
 *   "message": "Redirect user to approval_url"
 * }
 * 
 * WORKFLOW:
 * 1. Authenticate user
 * 2. Check if user already has active subscription
 * 3. Create PayPal subscription
 * 4. Store subscription in database
 * 5. Return approval URL for user to complete payment
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/PayPal.php';

try {
    // Authenticate user
    $user = Auth::require();
    $userId = $user['user_id'];
    $email = $user['email'];
    $firstName = $user['first_name'];
    $lastName = $user['last_name'];
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['plan'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required field: plan'
        ]);
        exit;
    }
    
    $plan = trim($input['plan']);
    
    // Validate plan
    $validPlans = ['standard', 'pro', 'vip'];
    if (!in_array($plan, $validPlans)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid plan. Must be: ' . implode(', ', $validPlans)
        ]);
        exit;
    }
    
    // Get database connection
    $db = Database::getInstance();
    $paymentsConn = $db->getConnection('payments');
    
    // Check if user already has active subscription
    $stmt = $paymentsConn->prepare("
        SELECT subscription_id, status
        FROM subscriptions
        WHERE user_id = ? AND status IN ('active', 'pending')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $existingSub = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingSub) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'User already has an active subscription',
            'existing_subscription' => $existingSub
        ]);
        exit;
    }
    
    // Create PayPal subscription
    $paypal = new PayPal();
    
    try {
        $subscription = $paypal->createSubscription($plan, $email, $firstName, $lastName);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'PayPal error: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Store subscription in database
    $stmt = $paymentsConn->prepare("
        INSERT INTO subscriptions (
            user_id,
            paypal_subscription_id,
            plan_id,
            status,
            created_at
        ) VALUES (?, ?, ?, 'pending', datetime('now'))
    ");
    
    $stmt->execute([
        $userId,
        $subscription['subscription_id'],
        $plan
    ]);
    
    $subscriptionDbId = $paymentsConn->lastInsertId();
    
    // Log subscription creation
    error_log("Subscription created for user $userId: {$subscription['subscription_id']} (Plan: $plan)");
    
    // Return success with approval URL
    echo json_encode([
        'success' => true,
        'subscription_id' => $subscription['subscription_id'],
        'approval_url' => $subscription['approval_url'],
        'message' => 'Redirect user to approval_url to complete payment'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Create Subscription Error: ' . $e->getMessage());
}
