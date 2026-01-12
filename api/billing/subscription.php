<?php
/**
 * Get current subscription
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/billing-manager.php';

$user = Auth::requireAuth();
Response::requireMethod('GET');

$subscription = BillingManager::getCurrentSubscription($user['id']);

// Get plan details
$plans = [
    'basic' => ['name' => 'Basic', 'price' => 9.99, 'max_devices' => 3, 'max_cameras' => 1],
    'family' => ['name' => 'Family', 'price' => 14.99, 'max_devices' => 5, 'max_cameras' => 2],
    'dedicated' => ['name' => 'Dedicated', 'price' => 29.99, 'max_devices' => 999, 'max_cameras' => 12],
    'vip_basic' => ['name' => 'VIP Basic', 'price' => 0, 'max_devices' => 8, 'max_cameras' => 2],
    'vip_dedicated' => ['name' => 'VIP Dedicated', 'price' => 0, 'max_devices' => 999, 'max_cameras' => 12]
];

Response::success([
    'subscription' => $subscription,
    'plans' => $plans
]);
