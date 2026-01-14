<?php
/**
 * TrueVault VPN - Subscription API
 * GET/POST /api/billing/subscription.php
 * 
 * Get current subscription or cancel
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/billing-manager.php';

$user = Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get current subscription
        $subscription = BillingManager::getCurrentSubscription($user['id']);
        
        if ($subscription) {
            Response::success($subscription, 'Subscription retrieved');
        } else {
            Response::success([
                'plan_type' => 'free',
                'status' => 'no_subscription',
                'message' => 'No active subscription'
            ], 'No subscription');
        }
        break;
        
    case 'DELETE':
        // Cancel subscription
        $input = Response::getJsonInput();
        $reason = $input['reason'] ?? null;
        
        $result = BillingManager::cancelSubscription($user['id'], $reason);
        
        if ($result['success']) {
            Response::success([
                'message' => $result['message']
            ], 'Subscription cancelled');
        } else {
            Response::error($result['error'], 400);
        }
        break;
        
    default:
        Response::error('Method not allowed', 405);
}
