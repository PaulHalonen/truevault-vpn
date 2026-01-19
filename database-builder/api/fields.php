<?php
// Fields API - CRUD operations for table fields
require_once '../config.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$db = getBuilderDB();

switch ($action) {
    case 'list':
        // List fields for a table
        $tableId = $_GET['table_id'] ?? 0;
        
        $stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY field_order");
        $stmt->execute([$tableId]);
        $fields = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'fields' => $fields]);
        break;
        
    case 'create':
        // Create new field
        $input = json_decode(file_get_contents('php://input'), true);
        
        $tableId = $input['table_id'] ?? 0;
        $fieldName = preg_replace('/[^a-z0-9_]/', '', strtolower($input['field_name'] ?? ''));
        $displayName = $input['display_name'] ?? '';
        $fieldType = $input['field_type'] ?? 'text';
        $fieldOrder = $input['field_order'] ?? 0;
        $isRequired = $input['is_required'] ?? 0;
        $defaultValue = $input['default_value'] ?? null;
        $validationRules = json_encode($input['validation_rules'] ?? []);
        $options = json_encode($input['options'] ?? []);
        $helpText = $input['help_text'] ?? '';
        
        if (empty($tableId) || empty($fieldName) || empty($displayName)) {
            jsonResponse(['success' => false, 'error' => 'Required fields missing'], 400);
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO custom_fields 
                (table_id, field_name, display_name, field_type, field_order, is_required, default_value, validation_rules, options, help_text)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tableId, $fieldName, $displayName, $fieldType, $fieldOrder, $isRequired, $defaultValue, $validationRules, $options, $helpText]);
            
            $fieldId = $db->lastInsertId();
            
            // Add column to data table
            $tableStmt = $db->prepare("SELECT table_name FROM custom_tables WHERE id = ?");
            $tableStmt->execute([$tableId]);
            $table = $tableStmt->fetch();
            
            if ($table) {
                $sqlType = 'TEXT';
                if (in_array($fieldType, ['number', 'currency', 'rating'])) {
                    $sqlType = 'REAL';
                } elseif ($fieldType === 'checkbox') {
                    $sqlType = 'INTEGER';
                }
                
                $db->exec("ALTER TABLE data_{$table['table_name']} ADD COLUMN {$fieldName} {$sqlType}");
            }
            
            jsonResponse(['success' => true, 'field_id' => $fieldId]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 400);
        }
        break;
        
    case 'update':
        // Update field
        $id = $_GET['id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true);
        
        $displayName = $input['display_name'] ?? '';
        $fieldOrder = $input['field_order'] ?? 0;
        $isRequired = $input['is_required'] ?? 0;
        $defaultValue = $input['default_value'] ?? null;
        $validationRules = json_encode($input['validation_rules'] ?? []);
        $options = json_encode($input['options'] ?? []);
        $helpText = $input['help_text'] ?? '';
        
        if (empty($displayName)) {
            jsonResponse(['success' => false, 'error' => 'Display name required'], 400);
        }
        
        $stmt = $db->prepare("
            UPDATE custom_fields 
            SET display_name = ?, field_order = ?, is_required = ?, default_value = ?, 
                validation_rules = ?, options = ?, help_text = ?
            WHERE id = ?
        ");
        $stmt->execute([$displayName, $fieldOrder, $isRequired, $defaultValue, $validationRules, $options, $helpText, $id]);
        
        jsonResponse(['success' => true]);
        break;
        
    case 'delete':
        // Delete field
        $id = $_GET['id'] ?? 0;
        
        // Get field info
        $stmt = $db->prepare("SELECT * FROM custom_fields WHERE id = ?");
        $stmt->execute([$id]);
        $field = $stmt->fetch();
        
        if (!$field) {
            jsonResponse(['success' => false, 'error' => 'Field not found'], 404);
        }
        
        // Get table name
        $tableStmt = $db->prepare("SELECT table_name FROM custom_tables WHERE id = ?");
        $tableStmt->execute([$field['table_id']]);
        $table = $tableStmt->fetch();
        
        // Delete field
        $stmt = $db->prepare("DELETE FROM custom_fields WHERE id = ?");
        $stmt->execute([$id]);
        
        // Note: SQLite doesn't support DROP COLUMN easily, so we just remove from metadata
        // The column remains in the data table but is no longer used
        
        jsonResponse(['success' => true]);
        break;
        
    case 'reorder':
        // Reorder fields
        $input = json_decode(file_get_contents('php://input'), true);
        $fieldOrders = $input['field_orders'] ?? [];
        
        foreach ($fieldOrders as $fieldId => $order) {
            $stmt = $db->prepare("UPDATE custom_fields SET field_order = ? WHERE id = ?");
            $stmt->execute([$order, $fieldId]);
        }
        
        jsonResponse(['success' => true]);
        break;
        
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}
?>
