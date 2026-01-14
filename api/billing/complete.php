<?php
/**
 * TrueVault VPN - Complete Payment API
 * POST /api/billing/complete.php
 * 
 * Called after user approves PayPal payment
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/billing-manager.php';

Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['order_id'])) {
    Response::error('Order ID is required', 400);
}

try {
    $result = BillingManager::completePayment($input['order_id']);
    
    if ($result['success']) {
        Response::success([
            'message' => $result['message'],
            'invoice_id' => $result['invoice_id'] ?? null
        ], 'Payment completed successfully');
    } else {
        Response::error($result['error'] ?? 'Payment completion failed', 500);
    }
} catch (Exception $e) {
    Response::serverError('Payment error: ' . $e->getMessage());
}
