<?php
/**
 * TrueVault VPN - Subscription Management API
 * 
 * GET              - Get current subscription
 * POST ?action=capture  - Capture approved payment
 * POST ?action=cancel   - Cancel subscription
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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

$userId = $payload['user_id'];
$action = $_GET['action'] ?? '';

$billingDb = Database::getInstance('billing');
$usersDb = Database::getInstance('users');

// Plan details
$planDetails = [
    'personal' => ['name' => 'Personal', 'devices' => 3],
    'family' => ['name' => 'Family', 'devices' => 6],
    'dedicated' => ['name' => 'Dedicated Server', 'devices' => 999]
];

// Handle different actions
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get current subscription
        $subscription = $billingDb->queryOne(
            "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        // Get user info
        $user = $usersDb->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
        
        // Get payment history
        $payments = $billingDb->queryAll(
            "SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
            [$userId]
        );
        
        // Get invoices
        $invoices = $billingDb->queryAll(
            "SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
            [$userId]
        );
        
        // Check if VIP
        $isVIP = $user && $user['account_type'] === 'vip';
        
        if ($isVIP) {
            echo json_encode([
                'success' => true,
                'is_vip' => true,
                'subscription' => [
                    'plan' => 'vip',
                    'plan_name' => 'VIP Access',
                    'status' => 'active',
                    'max_devices' => (int)$user['max_devices'],
                    'billing_interval' => null,
                    'amount' => 0,
                    'currency' => 'USD',
                    'current_period_end' => null,
                    'message' => 'Lifetime VIP access'
                ],
                'payments' => [],
                'invoices' => []
            ]);
            exit;
        }
        
        if (!$subscription) {
            echo json_encode([
                'success' => true,
                'subscription' => null,
                'trial' => [
                    'active' => $user['status'] === 'trial',
                    'ends_at' => $user['trial_ends_at'] ?? null
                ],
                'payments' => [],
                'invoices' => []
            ]);
            exit;
        }
        
        // Get plan details
        $plan = $planDetails[$subscription['plan']] ?? ['name' => $subscription['plan'], 'devices' => 3];
        
        echo json_encode([
            'success' => true,
            'subscription' => [
                'id' => $subscription['id'],
                'plan' => $subscription['plan'],
                'plan_name' => $plan['name'],
                'status' => $subscription['status'],
                'max_devices' => $plan['devices'],
                'billing_interval' => $subscription['billing_interval'],
                'amount' => $subscription['amount'],
                'currency' => $subscription['currency'],
                'current_period_start' => $subscription['current_period_start'],
                'current_period_end' => $subscription['current_period_end'],
                'cancelled_at' => $subscription['cancelled_at'],
                'auto_renew' => $subscription['status'] !== 'cancelled'
            ],
            'payments' => array_map(function($p) {
                return [
                    'id' => $p['id'],
                    'amount' => $p['amount'],
                    'currency' => $p['currency'],
                    'status' => $p['status'],
                    'date' => $p['created_at'],
                    'transaction_id' => $p['paypal_transaction_id']
                ];
            }, $payments),
            'invoices' => array_map(function($i) {
                return [
                    'id' => $i['id'],
                    'number' => $i['invoice_number'],
                    'amount' => $i['amount'],
                    'currency' => $i['currency'],
                    'status' => $i['status'],
                    'date' => $i['created_at']
                ];
            }, $invoices)
        ]);
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($action === 'capture') {
            // Capture approved payment
            $orderId = $input['order_id'] ?? '';
            
            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Order ID required']);
                exit;
            }
            
            // Get order from database
            $order = $billingDb->queryOne(
                "SELECT * FROM orders WHERE paypal_order_id = ? AND user_id = ?",
                [$orderId, $userId]
            );
            
            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Order not found']);
                exit;
            }
            
            if ($order['status'] !== 'pending') {
                echo json_encode(['success' => false, 'error' => 'Order already processed']);
                exit;
            }
            
            try {
                // Capture the payment
                $result = PayPal::captureOrder($orderId);
                
                if ($result['success']) {
                    // Update order
                    $billingDb->update('orders', [
                        'status' => 'completed',
                        'transaction_id' => $result['transaction_id'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$order['id']]);
                    
                    // Get plan details
                    $plan = $planDetails[$order['plan']] ?? ['name' => $order['plan'], 'devices' => 3];
                    
                    // Calculate period end
                    $periodEnd = $order['billing_interval'] === 'annual'
                        ? date('Y-m-d H:i:s', strtotime('+1 year'))
                        : date('Y-m-d H:i:s', strtotime('+1 month'));
                    
                    // Create subscription record
                    $billingDb->insert('subscriptions', [
                        'user_id' => $userId,
                        'plan' => $order['plan'],
                        'billing_interval' => $order['billing_interval'],
                        'amount' => $order['amount'],
                        'currency' => $order['currency'],
                        'status' => 'active',
                        'current_period_start' => date('Y-m-d H:i:s'),
                        'current_period_end' => $periodEnd,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Record payment
                    $billingDb->insert('payments', [
                        'user_id' => $userId,
                        'amount' => $order['amount'],
                        'currency' => $order['currency'],
                        'payment_method' => 'paypal',
                        'paypal_transaction_id' => $result['transaction_id'],
                        'status' => 'completed',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Generate invoice
                    $year = date('Y');
                    $lastInvoice = $billingDb->queryValue(
                        "SELECT MAX(CAST(SUBSTR(invoice_number, -6) AS INTEGER)) FROM invoices WHERE invoice_number LIKE ?",
                        ["TV{$year}%"]
                    );
                    $nextNum = ($lastInvoice ?? 0) + 1;
                    $invoiceNumber = "TV{$year}" . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
                    
                    $billingDb->insert('invoices', [
                        'user_id' => $userId,
                        'invoice_number' => $invoiceNumber,
                        'amount' => $order['amount'],
                        'currency' => $order['currency'],
                        'status' => 'paid',
                        'paid_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Update user
                    $usersDb->update('users', [
                        'status' => 'active',
                        'plan' => $order['plan'],
                        'max_devices' => $plan['devices'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$userId]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment successful! Your subscription is now active.',
                        'transaction_id' => $result['transaction_id'],
                        'invoice_number' => $invoiceNumber,
                        'plan' => $plan['name'],
                        'period_end' => $periodEnd
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => $result['error'] ?? 'Payment capture failed'
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Payment processing error'
                ]);
            }
            
        } elseif ($action === 'cancel') {
            // Cancel subscription
            $subscription = $billingDb->queryOne(
                "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1",
                [$userId]
            );
            
            if (!$subscription) {
                echo json_encode(['success' => false, 'error' => 'No active subscription']);
                exit;
            }
            
            $reason = $input['reason'] ?? 'Customer requested cancellation';
            
            // If has PayPal subscription, cancel it
            if (!empty($subscription['paypal_subscription_id'])) {
                PayPal::cancelSubscription($subscription['paypal_subscription_id'], $reason);
            }
            
            // Update local record - user keeps access until period ends
            $billingDb->update('subscriptions', [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancel_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$subscription['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Subscription cancelled. You will have access until ' . date('F j, Y', strtotime($subscription['current_period_end']))
            ]);
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
