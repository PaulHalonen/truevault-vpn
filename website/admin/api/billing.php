<?php
/**
 * TrueVault VPN - Admin Billing API
 * 
 * GET ?type=subscriptions  - List subscriptions
 * GET ?type=invoices       - List invoices
 * GET ?type=payments       - List payments
 * PUT ?type=subscription&id=X - Update subscription
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify admin token
Auth::init(JWT_SECRET);

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
if (!$payload || ($payload['type'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$billingDb = Database::getInstance('billing');
$usersDb = Database::getInstance('users');

$type = $_GET['type'] ?? 'subscriptions';
$id = $_GET['id'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
$offset = ($page - 1) * $limit;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($type) {
            case 'subscriptions':
                $total = $billingDb->queryValue("SELECT COUNT(*) FROM subscriptions");
                $subscriptions = $billingDb->queryAll(
                    "SELECT s.*, u.email as user_email
                     FROM subscriptions s
                     LEFT JOIN (SELECT id, email FROM users) u ON s.user_id = u.id
                     ORDER BY s.created_at DESC
                     LIMIT {$limit} OFFSET {$offset}"
                );
                
                echo json_encode([
                    'success' => true,
                    'subscriptions' => $subscriptions,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                break;
                
            case 'invoices':
                $total = $billingDb->queryValue("SELECT COUNT(*) FROM invoices");
                $invoices = $billingDb->queryAll(
                    "SELECT i.*, u.email as user_email
                     FROM invoices i
                     LEFT JOIN (SELECT id, email FROM users) u ON i.user_id = u.id
                     ORDER BY i.created_at DESC
                     LIMIT {$limit} OFFSET {$offset}"
                );
                
                echo json_encode([
                    'success' => true,
                    'invoices' => $invoices,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                break;
                
            case 'payments':
                $total = $billingDb->queryValue("SELECT COUNT(*) FROM payments");
                $payments = $billingDb->queryAll(
                    "SELECT p.*, u.email as user_email
                     FROM payments p
                     LEFT JOIN (SELECT id, email FROM users) u ON p.user_id = u.id
                     ORDER BY p.created_at DESC
                     LIMIT {$limit} OFFSET {$offset}"
                );
                
                echo json_encode([
                    'success' => true,
                    'payments' => $payments,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                break;
                
            case 'summary':
                // Revenue summary
                $totalRevenue = $billingDb->queryValue(
                    "SELECT SUM(amount) FROM payments WHERE status = 'completed'"
                ) ?? 0;
                
                $monthlyRevenue = $billingDb->queryValue(
                    "SELECT SUM(amount) FROM payments WHERE status = 'completed' AND created_at >= DATE('now', '-30 days')"
                ) ?? 0;
                
                $todayRevenue = $billingDb->queryValue(
                    "SELECT SUM(amount) FROM payments WHERE status = 'completed' AND DATE(created_at) = DATE('now')"
                ) ?? 0;
                
                $activeSubscriptions = $billingDb->queryValue(
                    "SELECT COUNT(*) FROM subscriptions WHERE status = 'active'"
                ) ?? 0;
                
                $cancelledSubscriptions = $billingDb->queryValue(
                    "SELECT COUNT(*) FROM subscriptions WHERE status = 'cancelled'"
                ) ?? 0;
                
                // Plan breakdown
                $planBreakdown = $billingDb->queryAll(
                    "SELECT plan, billing_interval, COUNT(*) as count, SUM(amount) as total_amount 
                     FROM subscriptions 
                     WHERE status = 'active' 
                     GROUP BY plan, billing_interval"
                );
                
                echo json_encode([
                    'success' => true,
                    'summary' => [
                        'total_revenue' => round((float)$totalRevenue, 2),
                        'monthly_revenue' => round((float)$monthlyRevenue, 2),
                        'today_revenue' => round((float)$todayRevenue, 2),
                        'active_subscriptions' => (int)$activeSubscriptions,
                        'cancelled_subscriptions' => (int)$cancelledSubscriptions,
                        'plan_breakdown' => $planBreakdown
                    ]
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid type']);
        }
        break;
        
    case 'PUT':
        if ($type === 'subscription' && $id) {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];
            
            // Fields that can be updated
            if (isset($input['status'])) {
                $updateData['status'] = $input['status'];
                
                // Update user status accordingly
                $subscription = $billingDb->queryOne(
                    "SELECT user_id FROM subscriptions WHERE id = ?", [$id]
                );
                
                if ($subscription) {
                    $userStatus = $input['status'] === 'active' ? 'active' : 'suspended';
                    $usersDb->update('users',
                        ['status' => $userStatus, 'updated_at' => date('Y-m-d H:i:s')],
                        'id = ?',
                        [$subscription['user_id']]
                    );
                }
            }
            
            if (isset($input['plan'])) {
                $updateData['plan'] = $input['plan'];
            }
            
            if (isset($input['current_period_end'])) {
                $updateData['current_period_end'] = $input['current_period_end'];
            }
            
            $billingDb->update('subscriptions', $updateData, 'id = ?', [$id]);
            
            echo json_encode(['success' => true, 'message' => 'Subscription updated']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
