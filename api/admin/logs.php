<?php
/**
 * TrueVault VPN - Admin Logs API
 * System logging and activity monitoring
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
                
                if (!empty($_GET['level'])) {
                    $where[] = "level = ?";
                    $params[] = $_GET['level'];
                }
                
                if (!empty($_GET['category'])) {
                    $where[] = "category = ?";
                    $params[] = $_GET['category'];
                }
                
                if (!empty($_GET['search'])) {
                    $where[] = "message LIKE ?";
                    $params[] = '%' . $_GET['search'] . '%';
                }
                
                if (!empty($_GET['since'])) {
                    $where[] = "created_at >= ?";
                    $params[] = $_GET['since'];
                }
                
                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                
                // Get total count
                $countStmt = $db->prepare("SELECT COUNT(*) FROM system_log $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get logs
                $sql = "SELECT * FROM system_log $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get stats
                $stats = [
                    'info' => $db->query("SELECT COUNT(*) FROM system_log WHERE level = 'info' AND created_at >= datetime('now', '-24 hours')")->fetchColumn(),
                    'success' => $db->query("SELECT COUNT(*) FROM system_log WHERE level = 'success' AND created_at >= datetime('now', '-24 hours')")->fetchColumn(),
                    'warning' => $db->query("SELECT COUNT(*) FROM system_log WHERE level = 'warning' AND created_at >= datetime('now', '-24 hours')")->fetchColumn(),
                    'error' => $db->query("SELECT COUNT(*) FROM system_log WHERE level = 'error' AND created_at >= datetime('now', '-24 hours')")->fetchColumn()
                ];
                
                Response::json([
                    'success' => true,
                    'logs' => $logs,
                    'stats' => $stats,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                
            } elseif ($action === 'activity') {
                // Get user activity logs
                $stmt = $db->query("
                    SELECT * FROM activity_log 
                    ORDER BY created_at DESC 
                    LIMIT 100
                ");
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'activities' => $activities]);
                
            } elseif ($action === 'admin') {
                // Get admin action logs
                $stmt = $db->query("
                    SELECT al.*, a.email as admin_email
                    FROM admin_log al
                    LEFT JOIN admins a ON al.admin_id = a.id
                    ORDER BY al.created_at DESC 
                    LIMIT 100
                ");
                $adminLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'admin_logs' => $adminLogs]);
                
            } elseif ($action === 'categories') {
                // Get available log categories
                $categories = $db->query("SELECT DISTINCT category FROM system_log ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
                Response::json(['success' => true, 'categories' => $categories]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'clear') {
                // Clear old logs (keep last 7 days)
                $days = $data['days'] ?? 7;
                $stmt = $db->prepare("DELETE FROM system_log WHERE created_at < datetime('now', '-' || ? || ' days')");
                $stmt->execute([$days]);
                $deleted = $stmt->rowCount();
                
                // Log the clear action
                $logStmt = $db->prepare("
                    INSERT INTO admin_log (admin_id, action, details, created_at)
                    VALUES (?, 'logs_cleared', ?, datetime('now'))
                ");
                $logStmt->execute([$admin['id'], json_encode(['deleted_count' => $deleted, 'older_than_days' => $days])]);
                
                Response::json(['success' => true, 'message' => "Cleared $deleted log entries"]);
                
            } elseif ($action === 'export') {
                // Export logs
                $where = [];
                $params = [];
                
                if (!empty($data['from'])) {
                    $where[] = "created_at >= ?";
                    $params[] = $data['from'];
                }
                if (!empty($data['to'])) {
                    $where[] = "created_at <= ?";
                    $params[] = $data['to'];
                }
                if (!empty($data['level'])) {
                    $where[] = "level = ?";
                    $params[] = $data['level'];
                }
                
                $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
                
                $stmt = $db->prepare("SELECT * FROM system_log $whereClause ORDER BY created_at DESC LIMIT 10000");
                $stmt->execute($params);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json(['success' => true, 'logs' => $logs, 'count' => count($logs)]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'DELETE':
            // Delete specific log entry (for testing/debugging)
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
