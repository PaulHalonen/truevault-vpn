<?php
/**
 * Get billing history
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/billing-manager.php';

$user = Auth::requireAuth();
Response::requireMethod('GET');

$history = BillingManager::getBillingHistory($user['id']);

Response::success([
    'invoices' => $history
]);
