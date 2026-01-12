<?php
/**
 * Create checkout session
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/billing-manager.php';

$user = Auth::requireAuth();
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
