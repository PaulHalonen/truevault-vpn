<?php
/**
 * TrueVault VPN - Checkout API
 * 
 * POST - Create checkout session
 * Body: { plan: "personal|family|dedicated", interval: "monthly|annual", currency: "USD|CAD" }
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/PayPal.php';

// Initialize Auth
Auth::init(JWT_SECRET);

// Verify authentication
$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$payload = Auth::verifyToken($token);
if (!$payload) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

$plan = $input['plan'] ?? '';
$interval = $input['interval'] ?? 'monthly';
$currency = strtoupper($input['currency'] ?? 'USD');

// Validate plan
if (!in_array($plan, ['personal', 'family', 'dedicated'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid plan']);
    exit;
}

// Validate interval
if (!in_array($interval, ['monthly', 'annual'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid billing interval']);
    exit;
}

// Validate currency
if (!in_array($currency, ['USD', 'CAD'])) {
    $currency = 'USD';
}

// Pricing table
$pricing = [
    'personal' => [
        'monthly' => ['USD' => 9.97, 'CAD' => 13.96],
        'annual' => ['USD' => 99.97, 'CAD' => 139.96],
        'devices' => 3,
        'name' => 'Personal'
    ],
    'family' => [
        'monthly' => ['USD' => 14.97, 'CAD' => 20.96],
        'annual' => ['USD' => 140.97, 'CAD' => 197.36],
        'devices' => 6,
        'name' => 'Family'
    ],
    'dedicated' => [
        'monthly' => ['USD' => 39.97, 'CAD' => 55.96],
        'annual' => ['USD' => 399.97, 'CAD' => 559.96],
        'devices' => 999,
        'name' => 'Dedicated Server'
    ]
];

$planData = $pricing[$plan];
$amount = $planData[$interval][$currency];
$planName = $planData['name'];
$maxDevices = $planData['devices'];

// Check if user is VIP (no payment needed)
$usersDb = Database::getInstance('users');
$user = $usersDb->queryOne("SELECT * FROM users WHERE id = ?", [$payload['user_id']]);

if ($user && $user['account_type'] === 'vip') {
    echo json_encode([
        'success' => true,
        'vip' => true,
        'message' => 'VIP accounts have free access'
    ]);
    exit;
}

// Create description
$intervalLabel = $interval === 'annual' ? 'Annual' : 'Monthly';
$description = "TrueVault VPN - {$planName} ({$intervalLabel})";

// URLs for PayPal redirect
$sessionData = base64_encode(json_encode([
    'user_id' => $payload['user_id'],
    'plan' => $plan,
    'interval' => $interval,
    'currency' => $currency,
    'amount' => $amount
]));

$returnUrl = BASE_URL . "dashboard/payment-success.html?session={$sessionData}";
$cancelUrl = BASE_URL . "dashboard/payment-cancel.html";

try {
    // Create PayPal order
    $result = PayPal::createOrder(
        $amount,
        $currency,
        $description,
        $returnUrl,
        $cancelUrl,
        (string)$payload['user_id']
    );
    
    if ($result['success']) {
        // Store pending order in database
        $billingDb = Database::getInstance('billing');
        
        $billingDb->insert('orders', [
            'user_id' => $payload['user_id'],
            'paypal_order_id' => $result['order_id'],
            'plan' => $plan,
            'billing_interval' => $interval,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'order_id' => $result['order_id'],
            'approval_url' => $result['approval_url'],
            'amount' => $amount,
            'amount_display' => '$' . number_format($amount, 2),
            'currency' => $currency,
            'plan' => $planName,
            'interval' => $interval,
            'devices' => $maxDevices
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to create checkout session'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Payment system error'
    ]);
}
