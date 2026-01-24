<?php
/**
 * TrueVault VPN - Tables API
 * Part 13 - Task 13.7
 * CRUD for custom tables
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../../configs/config.php';

header('Content-Type: application/json');
define('DB_BUILDER', DB_PATH . 'builder.db');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $db = new SQLite3(DB_BUILDER);
    $db->enableExceptions(true);
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single table
                $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
                $stmt->bindValue(1, intval($_GET['id']), SQLITE3_INTEGER);
                $result = $stmt->execute();
                $table = $result->fetchArray(SQLITE3_ASSOC);
                echo json_encode(['success' => true, 'table' => $table]);
            } else {
                // List all tables
                $result = $db->query("SELECT * FROM custom_tables WHERE status = 'active' ORDER BY display_name");
                $tables = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $tables[] = $row;
                }
                echo json_encode(['success' => true, 'tables' => $tables]);
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                $tableName = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['table_name']));
                $displayName = trim($_POST['display_name']);
                $description = trim($_POST['description'] ?? '');
                $icon = $_POST['icon'] ?? '📋';
                $color = $_POST['color'] ?? '#3b82f6';
                
                if (empty($tableName) || empty($displayName)) {
                    echo json_encode(['success' => false, 'error' => 'Table name and display name required']);
                    break;
                }
                
                $stmt = $db->prepare("INSERT INTO custom_tables (table_name, display_name, description, icon, color) VALUES (?, ?, ?, ?, ?)");
                $stmt->bindValue(1, $tableName, SQLITE3_TEXT);
                $stmt->bindValue(2, $displayName, SQLITE3_TEXT);
                $stmt->bindValue(3, $description, SQLITE3_TEXT);
                $stmt->bindValue(4, $icon, SQLITE3_TEXT);
                $stmt->bindValue(5, $color, SQLITE3_TEXT);
                $stmt->execute();
                
                $tableId = $db->lastInsertRowID();
                
                // Create the actual data table
                $db->exec("CREATE TABLE IF NOT EXISTS data_{$tableName} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
                )");
                
                echo json_encode(['success' => true, 'table_id' => $tableId]);
                
            } elseif ($action === 'update') {
                $tableId = intval($_POST['table_id']);
                $displayName = trim($_POST['display_name']);
                $description = trim($_POST['description'] ?? '');
                $icon = $_POST['icon'] ?? '📋';
                $color = $_POST['color'] ?? '#3b82f6';
                
                $stmt = $db->prepare("UPDATE custom_tables SET display_name = ?, description = ?, icon = ?, color = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bindValue(1, $displayName, SQLITE3_TEXT);
                $stmt->bindValue(2, $description, SQLITE3_TEXT);
                $stmt->bindValue(3, $icon, SQLITE3_TEXT);
                $stmt->bindValue(4, $color, SQLITE3_TEXT);
                $stmt->bindValue(5, $tableId, SQLITE3_INTEGER);
                $stmt->execute();
                
                echo json_encode(['success' => true]);
            }
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $tableId = intval($input['table_id'] ?? 0);
            
            if ($tableId > 0) {
                // Get table name first
                $stmt = $db->prepare("SELECT table_name FROM custom_tables WHERE id = ?");
                $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $table = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($table) {
                    // Soft delete the table registry
                    $stmt = $db->prepare("UPDATE custom_tables SET status = 'deleted' WHERE id = ?");
                    $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Drop the actual data table
                    $db->exec("DROP TABLE IF EXISTS data_{$table['table_name']}");
                }
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Table ID required']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
