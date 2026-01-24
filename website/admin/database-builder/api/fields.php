<?php
/**
 * TrueVault VPN - Fields API
 * Part 13 - Task 13.7
 * CRUD for custom fields
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
            if (isset($_GET['table_id'])) {
                $stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY sort_order");
                $stmt->bindValue(1, intval($_GET['table_id']), SQLITE3_INTEGER);
                $result = $stmt->execute();
                $fields = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $fields[] = $row;
                }
                echo json_encode(['success' => true, 'fields' => $fields]);
            } elseif (isset($_GET['id'])) {
                $stmt = $db->prepare("SELECT * FROM custom_fields WHERE id = ?");
                $stmt->bindValue(1, intval($_GET['id']), SQLITE3_INTEGER);
                $result = $stmt->execute();
                $field = $result->fetchArray(SQLITE3_ASSOC);
                echo json_encode(['success' => true, 'field' => $field]);
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                $tableId = intval($_POST['table_id']);
                $fieldName = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['field_name']));
                $displayName = trim($_POST['display_name']);
                $fieldType = $_POST['field_type'] ?? 'text';
                $isRequired = isset($_POST['is_required']) ? 1 : 0;
                $isUnique = isset($_POST['is_unique']) ? 1 : 0;
                $defaultValue = $_POST['default_value'] ?? '';
                $helpText = $_POST['help_text'] ?? '';
                $placeholder = $_POST['placeholder'] ?? '';
                $options = $_POST['options'] ?? '';
                
                // Get next sort order
                $stmt = $db->prepare("SELECT MAX(sort_order) as max_order FROM custom_fields WHERE table_id = ?");
                $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);
                $sortOrder = ($row['max_order'] ?? 0) + 1;
                
                // Insert field definition
                $stmt = $db->prepare("INSERT INTO custom_fields (table_id, field_name, display_name, field_type, sort_order, is_required, is_unique, default_value, help_text, placeholder, options) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
                $stmt->bindValue(2, $fieldName, SQLITE3_TEXT);
                $stmt->bindValue(3, $displayName, SQLITE3_TEXT);
                $stmt->bindValue(4, $fieldType, SQLITE3_TEXT);
                $stmt->bindValue(5, $sortOrder, SQLITE3_INTEGER);
                $stmt->bindValue(6, $isRequired, SQLITE3_INTEGER);
                $stmt->bindValue(7, $isUnique, SQLITE3_INTEGER);
                $stmt->bindValue(8, $defaultValue, SQLITE3_TEXT);
                $stmt->bindValue(9, $helpText, SQLITE3_TEXT);
                $stmt->bindValue(10, $placeholder, SQLITE3_TEXT);
                $stmt->bindValue(11, $options, SQLITE3_TEXT);
                $stmt->execute();
                
                $fieldId = $db->lastInsertRowID();
                
                // Get table name and add column to data table
                $stmt = $db->prepare("SELECT table_name FROM custom_tables WHERE id = ?");
                $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $table = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($table) {
                    $sqlType = 'TEXT'; // Default to TEXT for SQLite flexibility
                    if (in_array($fieldType, ['number', 'rating'])) $sqlType = 'INTEGER';
                    if ($fieldType === 'currency') $sqlType = 'REAL';
                    if ($fieldType === 'checkbox') $sqlType = 'INTEGER';
                    
                    $db->exec("ALTER TABLE data_{$table['table_name']} ADD COLUMN {$fieldName} {$sqlType}");
                }
                
                echo json_encode(['success' => true, 'field_id' => $fieldId]);
                
            } elseif ($action === 'update') {
                $fieldId = intval($_POST['field_id']);
                $displayName = trim($_POST['display_name']);
                $isRequired = isset($_POST['is_required']) ? 1 : 0;
                $isUnique = isset($_POST['is_unique']) ? 1 : 0;
                $defaultValue = $_POST['default_value'] ?? '';
                $helpText = $_POST['help_text'] ?? '';
                $placeholder = $_POST['placeholder'] ?? '';
                $options = $_POST['options'] ?? '';
                
                $stmt = $db->prepare("UPDATE custom_fields SET display_name = ?, is_required = ?, is_unique = ?, default_value = ?, help_text = ?, placeholder = ?, options = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bindValue(1, $displayName, SQLITE3_TEXT);
                $stmt->bindValue(2, $isRequired, SQLITE3_INTEGER);
                $stmt->bindValue(3, $isUnique, SQLITE3_INTEGER);
                $stmt->bindValue(4, $defaultValue, SQLITE3_TEXT);
                $stmt->bindValue(5, $helpText, SQLITE3_TEXT);
                $stmt->bindValue(6, $placeholder, SQLITE3_TEXT);
                $stmt->bindValue(7, $options, SQLITE3_TEXT);
                $stmt->bindValue(8, $fieldId, SQLITE3_INTEGER);
                $stmt->execute();
                
                echo json_encode(['success' => true]);
                
            } elseif ($action === 'reorder') {
                $input = json_decode(file_get_contents('php://input'), true);
                $order = $input['order'] ?? [];
                
                foreach ($order as $index => $fieldId) {
                    $stmt = $db->prepare("UPDATE custom_fields SET sort_order = ? WHERE id = ?");
                    $stmt->bindValue(1, $index, SQLITE3_INTEGER);
                    $stmt->bindValue(2, intval($fieldId), SQLITE3_INTEGER);
                    $stmt->execute();
                }
                
                echo json_encode(['success' => true]);
            }
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $fieldId = intval($input['field_id'] ?? 0);
            
            if ($fieldId > 0) {
                $stmt = $db->prepare("DELETE FROM custom_fields WHERE id = ?");
                $stmt->bindValue(1, $fieldId, SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Field ID required']);
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
