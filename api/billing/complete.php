<?php
/**
 * Complete payment after PayPal approval
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/billing-manager.php';

$user = Auth::requireAuth();
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
