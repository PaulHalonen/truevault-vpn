<?php
/**
 * TrueVault VPN - Admin Logs API
 * System logs and audit trail
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
    $db = Database::getConnection('logs');
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get logs with filters
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
                $offset = ($page - 1) * $limit;
                
                $where = [];
                $params = [];
                
                // Filter by level
                if (!empty($_GET['level'])) {
                    $where[] = "level = ?";
                    $params[] = $_GET['level'];
                }
                
                // Filter by category
                if (!empty($_GET['category'])) {
                    $where[] = "category = ?";
                    $params[] = $_GET['category'];
                }
                
                // Filter by date range
                if (!empty($_GET['from'])) {
                    $where[] = "created_at >= ?";
                    $params[] = $_GET['from'];
                }
                if (!empty($_GET['to'])) {
                    $where[] = "created_at <= ?";
                    $params[] = $_GET['to'];
                }
                
                // Search
                if (!empty($_GET['search'])) {
                    $where[] = "(message LIKE ? OR details LIKE ?)";
                    $searchTerm = '%' . $_GET['search'] . '%';
                    $params = array_merge($params, [$searchTerm, $searchTerm]);
                }
                
                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                
                // Get total count
                $countSql = "SELECT COUNT(*) FROM system_log $whereClause";
                $countStmt = $db->prepare($countSql);
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get logs
                $sql = "
                    SELECT * FROM system_log 
                    $whereClause
                    ORDER BY created_at DESC
                    LIMIT $limit OFFSET $offset
                ";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get stats
                $stats = [
                    'total' => $db->query("SELECT COUNT(*) FROM system_log")->fetchColumn(),
                    'info' => $db->query("SELECT COUNT(*) FROM system_log WHERE level = 'info'")->fetchColumn(),
                    'success' => $db->query("SELECT COUNT(*) FROM system_log WHERE level = 'success'")->fetchColumn(),
                    'warning' => $db->query("SELECT COUNT(*) FROM system_log WHERE level = 'warning'")->fetchColumn(),
                    'error' => $db->query("SELECT COUNT(*) FROM system_log WHERE level = 'error'")->fetchColumn(),
                    'today' => $db->query("SELECT COUNT(*) FROM system_log WHERE date(created_at) = date('now')")->fetchColumn()
                ];
                
                // Get categories
                $categories = $db->query("
                    SELECT category, COUNT(*) as count 
                    FROM system_log 
                    GROUP BY category 
                    ORDER BY count DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json([
                    'success' => true,
                    'logs' => $logs,
                    'stats' => $stats,
                    'categories' => $categories,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                
            } elseif ($action === 'activity') {
                // Get user activity logs
                $stmt = $db->prepare("
                    SELECT al.*, u.email, u.first_name
                    FROM activity_log al
                    LEFT JOIN users u ON al.user_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT 100
                ");
                $stmt->execute();
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'activities' => $activities]);
                
            } elseif ($action === 'admin') {
                // Get admin action logs
                $stmt = $db->prepare("
                    SELECT al.*, a.email as admin_email, a.first_name as admin_name
                    FROM admin_log al
                    LEFT JOIN admin_users a ON al.admin_id = a.id
                    ORDER BY al.created_at DESC
                    LIMIT 100
                ");
                $stmt->execute();
                $adminLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'admin_logs' => $adminLogs]);
                
            } elseif ($action === 'export') {
                // Export logs
                $format = $_GET['format'] ?? 'json';
                
                $stmt = $db->prepare("
                    SELECT * FROM system_log 
                    ORDER BY created_at DESC
                    LIMIT 10000
                ");
                $stmt->execute();
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($format === 'csv') {
                    // Return data for client-side CSV generation
                    Response::json(['success' => true, 'logs' => $logs, 'format' => 'csv']);
                } else {
                    Response::json(['success' => true, 'logs' => $logs]);
                }
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'clear') {
                // Clear old logs (keep last 30 days)
                $days = $data['days'] ?? 30;
                
                $stmt = $db->prepare("
                    DELETE FROM system_log 
                    WHERE created_at < datetime('now', '-' || ? || ' days')
                ");
                $stmt->execute([$days]);
                $deleted = $stmt->rowCount();
                
                // Log this action
                $logStmt = $db->prepare("
                    INSERT INTO admin_log (admin_id, action, details, created_at)
                    VALUES (?, 'logs_cleared', ?, datetime('now'))
                ");
                $logStmt->execute([$admin['id'], json_encode(['deleted_count' => $deleted, 'days_kept' => $days])]);
                
                Response::json([
                    'success' => true,
                    'message' => "Cleared $deleted log entries older than $days days"
                ]);
                
            } elseif ($action === 'add') {
                // Add manual log entry (for testing/debugging)
                if (empty($data['message'])) {
                    Response::error('Message is required', 400);
                }
                
                $stmt = $db->prepare("
                    INSERT INTO system_log (level, category, message, details, created_at)
                    VALUES (?, ?, ?, ?, datetime('now'))
                ");
                $stmt->execute([
                    $data['level'] ?? 'info',
                    $data['category'] ?? 'admin',
                    $data['message'],
                    json_encode($data['details'] ?? [])
                ]);
                
                Response::json(['success' => true, 'message' => 'Log entry added']);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'DELETE':
            // Delete specific log entry
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                Response::error('Log ID required', 400);
            }
            
            $stmt = $db->prepare("DELETE FROM system_log WHERE id = ?");
            $stmt->execute([$id]);
            
            Response::json(['success' => true, 'message' => 'Log entry deleted']);
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Admin Logs API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
