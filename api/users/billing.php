<?php
/**
 * TrueVault VPN - User Billing API
 * Manages user subscriptions and payment methods
 * Uses Database helper class (SQLite3 version)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'status';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'status') {
                // Get current subscription status
                $subscription = Database::queryOne('subscriptions', "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [$user['id']]);
                
                // Get payment method (masked)
                $paymentMethods = [];
                try {
                    $paymentMethods = Database::query('payments', "
                        SELECT id, payment_method, card_last_four, card_brand, is_default, created_at
                        FROM payment_methods WHERE user_id = ? AND is_active = 1
                        ORDER BY is_default DESC, created_at DESC
                    ", [$user['id']]);
                } catch (Exception $e) {
                    // Payment methods table may not exist
                }
                
                // Plan details
                $plans = [
                    'personal' => ['name' => 'Personal', 'price' => 9.99, 'devices' => 3, 'identities' => 3],
                    'family' => ['name' => 'Family', 'price' => 14.99, 'devices' => 10, 'identities' => 10, 'mesh' => true],
                    'business' => ['name' => 'Business', 'price' => 29.99, 'devices' => 50, 'identities' => 50, 'mesh' => true, 'api' => true],
                    'vip' => ['name' => 'VIP', 'price' => 0, 'devices' => 100, 'identities' => 100, 'mesh' => true, 'api' => true, 'dedicated' => true]
                ];
                
                $currentPlan = $plans[$subscription['plan_type'] ?? $user['plan_type'] ?? 'personal'] ?? $plans['personal'];
                
                Response::success([
                    'subscription' => $subscription,
                    'plan_details' => $currentPlan,
                    'payment_methods' => $paymentMethods,
                    'available_plans' => $plans
                ]);
                
            } elseif ($action === 'invoices') {
                // Get invoice history
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limit = 10;
                $offset = ($page - 1) * $limit;
                
                $invoices = Database::query('payments', "
                    SELECT * FROM payments 
                    WHERE user_id = ? AND status IN ('completed', 'refunded')
                    ORDER BY created_at DESC
                    LIMIT $limit OFFSET $offset
                ", [$user['id']]);
                
                $countResult = Database::queryOne('payments', "SELECT COUNT(*) as total FROM payments WHERE user_id = ?", [$user['id']]);
                $total = $countResult['total'] ?? 0;
                
                Response::success([
                    'invoices' => $invoices,
                    'pagination' => [
                        'page' => $page,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                
            } elseif ($action === 'usage') {
                // Get usage statistics
                
                // Device count
                $deviceResult = Database::queryOne('devices', "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ?", [$user['id']]);
                $deviceCount = $deviceResult['count'] ?? 0;
                
                // Bandwidth usage (from VPN connections)
                $bwResult = Database::queryOne('vpn', "
                    SELECT COALESCE(SUM(data_transfer), 0) as bandwidth
                    FROM vpn_connections 
                    WHERE user_id = ? AND connected_at >= datetime('now', '-30 days')
                ", [$user['id']]);
                $bandwidth = $bwResult['bandwidth'] ?? 0;
                
                // Mesh members
                $meshResult = Database::queryOne('mesh', "
                    SELECT COUNT(*) as count FROM mesh_members mm
                    JOIN mesh_networks mn ON mm.network_id = mn.id
                    WHERE mn.owner_id = ?
                ", [$user['id']]);
                $meshMembers = $meshResult['count'] ?? 0;
                
                // Plan limits
                $limits = [
                    'personal' => ['devices' => 3, 'mesh' => 0, 'bandwidth' => 100],
                    'family' => ['devices' => 10, 'mesh' => 6, 'bandwidth' => 500],
                    'business' => ['devices' => 50, 'mesh' => 25, 'bandwidth' => 2000],
                    'vip' => ['devices' => 100, 'mesh' => 100, 'bandwidth' => 10000]
                ];
                $planLimits = $limits[$user['plan_type']] ?? $limits['personal'];
                
                Response::success([
                    'usage' => [
                        'devices' => ['used' => (int)$deviceCount, 'limit' => $planLimits['devices']],
                        'bandwidth_gb' => ['used' => round($bandwidth / 1073741824, 2), 'limit' => $planLimits['bandwidth']],
                        'mesh_members' => ['used' => (int)$meshMembers, 'limit' => $planLimits['mesh']]
                    ]
                ]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'upgrade') {
                // Upgrade/downgrade plan
                if (empty($data['plan'])) {
                    Response::error('Plan is required', 400);
                }
                
                $validPlans = ['personal', 'family', 'business'];
                if (!in_array($data['plan'], $validPlans)) {
                    Response::error('Invalid plan', 400);
                }
                
                $prices = ['personal' => 9.99, 'family' => 14.99, 'business' => 29.99];
                
                // Update subscription
                Database::execute('subscriptions', "
                    UPDATE subscriptions 
                    SET plan_type = ?, amount = ?, updated_at = datetime('now')
                    WHERE user_id = ?
                ", [$data['plan'], $prices[$data['plan']], $user['id']]);
                
                // Update user's plan type
                Database::execute('users', "UPDATE users SET plan_type = ? WHERE id = ?", [$data['plan'], $user['id']]);
                
                Response::success([
                    'message' => 'Plan updated successfully',
                    'new_plan' => $data['plan']
                ]);
                
            } elseif ($action === 'cancel') {
                // Cancel subscription
                Database::execute('subscriptions', "
                    UPDATE subscriptions 
                    SET status = 'cancelled', cancelled_at = datetime('now')
                    WHERE user_id = ?
                ", [$user['id']]);
                
                Response::success([
                    'message' => 'Subscription cancelled. You will retain access until your billing period ends.'
                ]);
                
            } elseif ($action === 'add-payment-method') {
                // Add payment method (in production, this would integrate with PayPal/Stripe)
                if (empty($data['token'])) {
                    Response::error('Payment token required', 400);
                }
                
                // Simulate adding payment method
                $result = Database::execute('payments', "
                    INSERT INTO payment_methods 
                    (user_id, payment_method, card_last_four, card_brand, is_default, is_active, created_at)
                    VALUES (?, 'card', ?, ?, 1, 1, datetime('now'))
                ", [
                    $user['id'],
                    $data['last_four'] ?? '4242',
                    $data['brand'] ?? 'Visa'
                ]);
                
                // Set other methods as non-default
                Database::execute('payments', "
                    UPDATE payment_methods SET is_default = 0 
                    WHERE user_id = ? AND id != ?
                ", [$user['id'], $result['lastInsertId']]);
                
                Response::success(['message' => 'Payment method added']);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'DELETE':
            if ($action === 'payment-method' && isset($_GET['id'])) {
                // Remove payment method
                Database::execute('payments', "
                    UPDATE payment_methods SET is_active = 0 
                    WHERE id = ? AND user_id = ?
                ", [$_GET['id'], $user['id']]);
                
                Response::success(['message' => 'Payment method removed']);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Billing API error: " . $e->getMessage());
    Response::error('Server error: ' . $e->getMessage(), 500);
}
