<?php
/**
 * TrueVault VPN - Billing API Endpoints
 * POST /api/billing/checkout.php - Create checkout session
 * POST /api/billing/complete.php - Complete payment
 * GET  /api/billing/subscription.php - Get current subscription
 * GET  /api/billing/history.php - Get billing history
 * POST /api/billing/cancel.php - Cancel subscription
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/billing-manager.php';

// Determine action from URL
$action = basename($_SERVER['SCRIPT_NAME'], '.php');

// Require auth for all billing endpoints
$user = Auth::requireAuth();

switch ($action) {
    
    case 'checkout':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['plan_id'])) {
            Response::error('Plan ID required', 400);
        }
        
        $result = BillingManager::createCheckout($user['id'], $input['plan_id']);
        
        if ($result['success']) {
            Response::success($result);
        } else {
            Response::error($result['error'], 400);
        }
        break;
    
    case 'complete':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['order_id'])) {
            Response::error('Order ID required', 400);
        }
        
        $result = BillingManager::completePayment($input['order_id']);
        
        if ($result['success']) {
            Response::success($result, 'Payment completed successfully');
        } else {
            Response::error($result['error'], 400);
        }
        break;
    
    case 'subscription':
        Response::requireMethod('GET');
        
        $subscription = BillingManager::getCurrentSubscription($user['id']);
        
        Response::success([
            'subscription' => $subscription,
            'plans' => $GLOBALS['PLANS']
        ]);
        break;
    
    case 'history':
        Response::requireMethod('GET');
        
        $history = BillingManager::getBillingHistory($user['id']);
        
        Response::success([
            'invoices' => $history
        ]);
        break;
    
    case 'cancel':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        $reason = $input['reason'] ?? null;
        $result = BillingManager::cancelSubscription($user['id'], $reason);
        
        if ($result['success']) {
            Response::success($result);
        } else {
            Response::error($result['error'], 400);
        }
        break;
    
    default:
        Response::error('Unknown billing action', 404);
}
