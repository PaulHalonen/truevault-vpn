<?php
// Tables API - CRUD operations for custom tables
require_once '../config.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$db = getBuilderDB();

switch ($action) {
    case 'list':
        // List all custom tables
        $stmt = $db->query("SELECT * FROM custom_tables WHERE is_active = 1 ORDER BY display_name");
        $tables = $stmt->fetchAll();
        
        // Get record counts for each table
        foreach ($tables as &$table) {
            try {
                $countStmt = $db->query("SELECT COUNT(*) as count FROM data_{$table['table_name']}");
                $table['record_count'] = $countStmt->fetch()['count'];
            } catch (Exception $e) {
                $table['record_count'] = 0;
            }
        }
        
        jsonResponse(['success' => true, 'tables' => $tables]);
        break;
        
    case 'get':
        // Get single table with fields
        $id = $_GET['id'] ?? 0;
        
        $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
        $stmt->execute([$id]);
        $table = $stmt->fetch();
        
        if (!$table) {
            jsonResponse(['success' => false, 'error' => 'Table not found'], 404);
        }
        
        // Get fields
        $stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY field_order");
        $stmt->execute([$id]);
        $table['fields'] = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'table' => $table]);
        break;
        
    case 'create':
        // Create new table
        $input = json_decode(file_get_contents('php://input'), true);
        
        $tableName = preg_replace('/[^a-z0-9_]/', '', strtolower($input['table_name'] ?? ''));
        $displayName = $input['display_name'] ?? '';
        $description = $input['description'] ?? '';
        $icon = $input['icon'] ?? '📊';
        
        if (empty($tableName) || empty($displayName)) {
            jsonResponse(['success' => false, 'error' => 'Table name and display name required'], 400);
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO custom_tables (table_name, display_name, description, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tableName, $displayName, $description, $icon]);
            
            $tableId = $db->lastInsertId();
            
            // Create the actual data table
            createDynamicTable($tableName, []);
            
            jsonResponse(['success' => true, 'table_id' => $tableId]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Table already exists or database error'], 400);
        }
        break;
        
    case 'update':
        // Update table
        $id = $_GET['id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true);
        
        $displayName = $input['display_name'] ?? '';
        $description = $input['description'] ?? '';
        $icon = $input['icon'] ?? '📊';
        
        if (empty($displayName)) {
            jsonResponse(['success' => false, 'error' => 'Display name required'], 400);
        }
        
        $stmt = $db->prepare("UPDATE custom_tables SET display_name = ?, description = ?, icon = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$displayName, $description, $icon, $id]);
        
        jsonResponse(['success' => true]);
        break;
        
    case 'delete':
        // Delete table (soft delete)
        $id = $_GET['id'] ?? 0;
        
        // Get table name first
        $stmt = $db->prepare("SELECT table_name FROM custom_tables WHERE id = ?");
        $stmt->execute([$id]);
        $table = $stmt->fetch();
        
        if (!$table) {
            jsonResponse(['success' => false, 'error' => 'Table not found'], 404);
        }
        
        // Soft delete
        $stmt = $db->prepare("UPDATE custom_tables SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Optionally drop the data table
        // $db->exec("DROP TABLE IF EXISTS data_{$table['table_name']}");
        
        jsonResponse(['success' => true]);
        break;
        
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}
?>
