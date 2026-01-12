<?php
/**
 * TrueVault VPN - Admin Subscriptions API
 * Manages customer subscriptions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require admin authentication
$admin = Auth::requireAdmin();
if (!$admin) exit;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = Database::getConnection('subscriptions');
    $usersDb = Database::getConnection('users');
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get subscriptions with filters
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
                $offset = ($page - 1) * $limit;
                
                $where = [];
                $params = [];
                
                if (!empty($_GET['status'])) {
                    $where[] = "s.status = ?";
                    $params[] = $_GET['status'];
                }
                
                if (!empty($_GET['plan'])) {
                    $where[] = "s.plan_type = ?";
                    $params[] = $_GET['plan'];
                }
                
                if (!empty($_GET['search'])) {
                    $where[] = "(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                    $searchTerm = '%' . $_GET['search'] . '%';
                    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                }
                
                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                
                // Get total count
                $countSql = "
                    SELECT COUNT(*) FROM subscriptions s
                    JOIN users u ON s.user_id = u.id
                    $whereClause
                ";
                $countStmt = $db->prepare($countSql);
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get subscriptions
                $sql = "
                    SELECT s.*, u.email, u.first_name, u.last_name
                    FROM subscriptions s
                    JOIN users u ON s.user_id = u.id
                    $whereClause
                    ORDER BY s.created_at DESC
                    LIMIT $limit OFFSET $offset
                ";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get stats
                $stats = [
                    'total' => $db->query("SELECT COUNT(*) FROM subscriptions")->fetchColumn(),
                    'active' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn(),
                    'trial' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'trial'")->fetchColumn(),
                    'cancelled' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'cancelled'")->fetchColumn(),
                    'past_due' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'past_due'")->fetchColumn(),
                    'mrr' => $db->query("SELECT SUM(amount) FROM subscriptions WHERE status = 'active'")->fetchColumn() ?? 0
                ];
                
                // Plan distribution
                $planDist = $db->query("
                    SELECT plan_type, COUNT(*) as count, SUM(amount) as revenue
                    FROM subscriptions WHERE status = 'active'
                    GROUP BY plan_type
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json([
                    'success' => true,
                    'subscriptions' => $subscriptions,
                    'stats' => $stats,
                    'plan_distribution' => $planDist,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single subscription with history
                $stmt = $db->prepare("
                    SELECT s.*, u.email, u.first_name, u.last_name, u.created_at as user_created
                    FROM subscriptions s
                    JOIN users u ON s.user_id = u.id
                    WHERE s.id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$subscription) {
                    Response::error('Subscription not found', 404);
                }
                
                // Get payment history
                $paymentsDb = Database::getConnection('payments');
                $payStmt = $paymentsDb->prepare("
                    SELECT * FROM payments WHERE user_id = ?
                    ORDER BY created_at DESC LIMIT 10
                ");
                $payStmt->execute([$subscription['user_id']]);
                $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json([
                    'success' => true,
                    'subscription' => $subscription,
                    'payment_history' => $payments
                ]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                Response::error('Subscription ID required', 400);
            }
            
            // Update subscription
            $fields = [];
            $values = [];
            
            $allowedFields = ['status', 'plan_type', 'amount', 'next_billing_date'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                Response::error('No fields to update', 400);
            }
            
            $values[] = $data['id'];
            $stmt = $db->prepare("UPDATE subscriptions SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($values);
            
            // Also update user's plan_type if changed
            if (isset($data['plan_type'])) {
                $subStmt = $db->prepare("SELECT user_id FROM subscriptions WHERE id = ?");
                $subStmt->execute([$data['id']]);
                $userId = $subStmt->fetchColumn();
                
                if ($userId) {
                    $userStmt = $usersDb->prepare("UPDATE users SET plan_type = ? WHERE id = ?");
                    $userStmt->execute([$data['plan_type'], $userId]);
                }
            }
            
            // Log the change
            $logDb = Database::getConnection('logs');
            $logStmt = $logDb->prepare("
                INSERT INTO admin_log (admin_id, action, target_type, target_id, details, created_at)
                VALUES (?, 'subscription_updated', 'subscription', ?, ?, datetime('now'))
            ");
            $logStmt->execute([$admin['id'], $data['id'], json_encode($data)]);
            
            Response::json(['success' => true, 'message' => 'Subscription updated']);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'cancel') {
                if (empty($data['id'])) {
                    Response::error('Subscription ID required', 400);
                }
                
                $stmt = $db->prepare("UPDATE subscriptions SET status = 'cancelled', cancelled_at = datetime('now') WHERE id = ?");
                $stmt->execute([$data['id']]);
                
                Response::json(['success' => true, 'message' => 'Subscription cancelled']);
                
            } elseif ($action === 'reactivate') {
                if (empty($data['id'])) {
                    Response::error('Subscription ID required', 400);
                }
                
                $stmt = $db->prepare("
                    UPDATE subscriptions 
                    SET status = 'active', cancelled_at = NULL, next_billing_date = datetime('now', '+1 month')
                    WHERE id = ?
                ");
                $stmt->execute([$data['id']]);
                
                Response::json(['success' => true, 'message' => 'Subscription reactivated']);
                
            } elseif ($action === 'extend') {
                if (empty($data['id']) || empty($data['days'])) {
                    Response::error('Subscription ID and days required', 400);
                }
                
                $stmt = $db->prepare("
                    UPDATE subscriptions 
                    SET next_billing_date = datetime(next_billing_date, '+' || ? || ' days')
                    WHERE id = ?
                ");
                $stmt->execute([$data['days'], $data['id']]);
                
                Response::json(['success' => true, 'message' => "Subscription extended by {$data['days']} days"]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Admin Subscriptions API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
