<?php
/**
 * Cancel subscription
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/billing-manager.php';

$user = Auth::requireAuth();
Response::requireMethod('POST');
$input = Response::getJsonInput();

$reason = $input['reason'] ?? null;
$result = BillingManager::cancelSubscription($user['id'], $reason);

if ($result['success']) {
    Response::success($result);
} else {
    Response::error($result['error'], 400);
}
