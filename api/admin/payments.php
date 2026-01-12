<?php
/**
 * TrueVault VPN - Admin Payments API
 * Manages payment transactions and refunds
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
    $db = Database::getConnection('payments');
    $usersDb = Database::getConnection('users');
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get payments with filters
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
                $offset = ($page - 1) * $limit;
                
                $where = [];
                $params = [];
                
                if (!empty($_GET['status'])) {
                    $where[] = "p.status = ?";
                    $params[] = $_GET['status'];
                }
                
                if (!empty($_GET['period'])) {
                    switch ($_GET['period']) {
                        case 'today':
                            $where[] = "date(p.created_at) = date('now')";
                            break;
                        case 'week':
                            $where[] = "p.created_at >= datetime('now', '-7 days')";
                            break;
                        case 'month':
                            $where[] = "p.created_at >= datetime('now', '-30 days')";
                            break;
                        case 'year':
                            $where[] = "p.created_at >= datetime('now', '-1 year')";
                            break;
                    }
                }
                
                if (!empty($_GET['search'])) {
                    $where[] = "(u.email LIKE ? OR p.transaction_id LIKE ? OR p.invoice_number LIKE ?)";
                    $searchTerm = '%' . $_GET['search'] . '%';
                    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                }
                
                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                
                // Get total count
                $countSql = "
                    SELECT COUNT(*) FROM payments p
                    JOIN users u ON p.user_id = u.id
                    $whereClause
                ";
                $countStmt = $db->prepare($countSql);
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get payments
                $sql = "
                    SELECT p.*, u.email, u.first_name, u.last_name
                    FROM payments p
                    JOIN users u ON p.user_id = u.id
                    $whereClause
                    ORDER BY p.created_at DESC
                    LIMIT $limit OFFSET $offset
                ";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get stats
                $stats = [
                    'total_revenue_month' => $db->query("
                        SELECT COALESCE(SUM(amount), 0) FROM payments 
                        WHERE status = 'completed' AND created_at >= datetime('now', '-30 days')
                    ")->fetchColumn(),
                    'total_transactions' => $db->query("SELECT COUNT(*) FROM payments")->fetchColumn(),
                    'completed' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'completed'")->fetchColumn(),
                    'failed' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'failed'")->fetchColumn(),
                    'refunded' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'refunded'")->fetchColumn(),
                    'pending' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn()
                ];
                
                // Get monthly revenue for chart
                $monthlyRevenue = $db->query("
                    SELECT 
                        strftime('%Y-%m', created_at) as month,
                        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
                        COUNT(*) as transactions
                    FROM payments
                    WHERE created_at >= datetime('now', '-6 months')
                    GROUP BY month
                    ORDER BY month
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json([
                    'success' => true,
                    'payments' => $payments,
                    'stats' => $stats,
                    'monthly_revenue' => $monthlyRevenue,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single payment with details
                $stmt = $db->prepare("
                    SELECT p.*, u.email, u.first_name, u.last_name
                    FROM payments p
                    JOIN users u ON p.user_id = u.id
                    WHERE p.id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$payment) {
                    Response::error('Payment not found', 404);
                }
                
                Response::json(['success' => true, 'payment' => $payment]);
                
            } elseif ($action === 'export') {
                // Export payments to CSV format (returns data, client handles download)
                $stmt = $db->query("
                    SELECT p.*, u.email
                    FROM payments p
                    JOIN users u ON p.user_id = u.id
                    ORDER BY p.created_at DESC
                ");
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'payments' => $payments, 'format' => 'csv']);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'refund') {
                if (empty($data['id'])) {
                    Response::error('Payment ID required', 400);
                }
                
                // Get original payment
                $stmt = $db->prepare("SELECT * FROM payments WHERE id = ?");
                $stmt->execute([$data['id']]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$payment) {
                    Response::error('Payment not found', 404);
                }
                
                if ($payment['status'] !== 'completed') {
                    Response::error('Can only refund completed payments', 400);
                }
                
                $refundAmount = $data['amount'] ?? $payment['amount'];
                if ($refundAmount > $payment['amount']) {
                    Response::error('Refund amount cannot exceed payment amount', 400);
                }
                
                // Update payment status
                $stmt = $db->prepare("
                    UPDATE payments 
                    SET status = 'refunded', refunded_amount = ?, refunded_at = datetime('now')
                    WHERE id = ?
                ");
                $stmt->execute([$refundAmount, $data['id']]);
                
                // TODO: Process actual refund through PayPal API
                
                // Log the refund
                $logDb = Database::getConnection('logs');
                $logStmt = $logDb->prepare("
                    INSERT INTO admin_log (admin_id, action, target_type, target_id, details, created_at)
                    VALUES (?, 'payment_refunded', 'payment', ?, ?, datetime('now'))
                ");
                $logStmt->execute([$admin['id'], $data['id'], json_encode([
                    'original_amount' => $payment['amount'],
                    'refund_amount' => $refundAmount,
                    'reason' => $data['reason'] ?? 'Not specified'
                ])]);
                
                Response::json(['success' => true, 'message' => "Refund of \$$refundAmount processed"]);
                
            } elseif ($action === 'retry') {
                if (empty($data['id'])) {
                    Response::error('Payment ID required', 400);
                }
                
                // Get failed payment
                $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND status = 'failed'");
                $stmt->execute([$data['id']]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$payment) {
                    Response::error('Failed payment not found', 404);
                }
                
                // Create new payment attempt
                $stmt = $db->prepare("
                    INSERT INTO payments (user_id, amount, status, payment_method, description, created_at)
                    VALUES (?, ?, 'pending', ?, 'Retry of failed payment', datetime('now'))
                ");
                $stmt->execute([
                    $payment['user_id'],
                    $payment['amount'],
                    $payment['payment_method']
                ]);
                
                // TODO: Process actual payment through PayPal API
                
                Response::json([
                    'success' => true, 
                    'message' => 'Payment retry initiated',
                    'new_payment_id' => $db->lastInsertId()
                ]);
                
            } elseif ($action === 'manual') {
                // Create manual payment record
                $required = ['user_id', 'amount', 'description'];
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        Response::error("$field is required", 400);
                    }
                }
                
                $stmt = $db->prepare("
                    INSERT INTO payments 
                    (user_id, amount, status, payment_method, description, transaction_id, invoice_number, created_at)
                    VALUES (?, ?, 'completed', 'manual', ?, ?, ?, datetime('now'))
                ");
                $stmt->execute([
                    $data['user_id'],
                    $data['amount'],
                    $data['description'],
                    'MANUAL-' . time(),
                    'INV-' . date('Ymd') . '-' . rand(1000, 9999)
                ]);
                
                // Log the manual payment
                $logDb = Database::getConnection('logs');
                $logStmt = $logDb->prepare("
                    INSERT INTO admin_log (admin_id, action, target_type, target_id, details, created_at)
                    VALUES (?, 'manual_payment_created', 'payment', ?, ?, datetime('now'))
                ");
                $logStmt->execute([$admin['id'], $db->lastInsertId(), json_encode($data)]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Manual payment recorded',
                    'payment_id' => $db->lastInsertId()
                ]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Admin Payments API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
