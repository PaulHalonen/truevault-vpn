<?php
/**
 * TrueVault VPN - Checkout API
 * POST /api/billing/checkout.php
 * 
 * Creates a PayPal checkout session for subscription
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/billing-manager.php';

// Require authentication
$user = Auth::requireAuth();

// Only POST
Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['plan_id'])) {
    Response::error('Plan ID is required', 400);
}

$planId = $input['plan_id'];

// Validate plan
$validPlans = ['basic', 'family', 'dedicated', 'vip_upgrade'];
if (!in_array($planId, $validPlans)) {
    Response::error('Invalid plan', 400);
}

try {
    $result = BillingManager::createCheckout($user['id'], $planId);
    
    if ($result['success']) {
        if (isset($result['vip']) && $result['vip']) {
            // VIP user - no payment needed
            Response::success([
                'vip' => true,
                'message' => $result['message']
            ], 'VIP access activated');
        } else {
            // Regular user - redirect to PayPal
            Response::success([
                'order_id' => $result['order_id'],
                'approval_url' => $result['approval_url']
            ], 'Checkout created - redirect to PayPal');
        }
    } else {
        Response::error($result['error'] ?? 'Checkout failed', 500);
    }
} catch (Exception $e) {
    Response::serverError('Checkout error: ' . $e->getMessage());
}
