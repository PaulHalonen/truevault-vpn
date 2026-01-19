<?php
// Data API - CRUD operations for data in custom tables
require_once '../config.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$db = getBuilderDB();

switch ($action) {
    case 'list':
        // List all records from a custom table
        $tableId = $_GET['table_id'] ?? 0;
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(100, max(10, intval($_GET['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;
        
        // Get table info
        $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch();
        
        if (!$table) {
            jsonResponse(['success' => false, 'error' => 'Table not found'], 404);
        }
        
        // Get fields
        $fieldsStmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY field_order");
        $fieldsStmt->execute([$tableId]);
        $fields = $fieldsStmt->fetchAll();
        
        // Get data
        try {
            $dataStmt = $db->query("SELECT * FROM data_{$table['table_name']} LIMIT {$perPage} OFFSET {$offset}");
            $records = $dataStmt->fetchAll();
            
            // Get total count
            $countStmt = $db->query("SELECT COUNT(*) as count FROM data_{$table['table_name']}");
            $totalRecords = $countStmt->fetch()['count'];
            
            jsonResponse([
                'success' => true,
                'table' => $table,
                'fields' => $fields,
                'records' => $records,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $perPage)
                ]
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Error fetching data: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'get':
        // Get single record
        $tableId = $_GET['table_id'] ?? 0;
        $recordId = $_GET['record_id'] ?? 0;
        
        // Get table info
        $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch();
        
        if (!$table) {
            jsonResponse(['success' => false, 'error' => 'Table not found'], 404);
        }
        
        // Get record
        try {
            $recordStmt = $db->prepare("SELECT * FROM data_{$table['table_name']} WHERE id = ?");
            $recordStmt->execute([$recordId]);
            $record = $recordStmt->fetch();
            
            if (!$record) {
                jsonResponse(['success' => false, 'error' => 'Record not found'], 404);
            }
            
            jsonResponse(['success' => true, 'record' => $record]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Error fetching record'], 500);
        }
        break;
        
    case 'create':
        // Create new record
        $tableId = $_POST['table_id'] ?? 0;
        $data = $_POST['data'] ?? [];
        
        // Get table info
        $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch();
        
        if (!$table) {
            jsonResponse(['success' => false, 'error' => 'Table not found'], 404);
        }
        
        // Get fields
        $fieldsStmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ?");
        $fieldsStmt->execute([$tableId]);
        $fields = $fieldsStmt->fetchAll();
        
        // Validate data
        $errors = [];
        foreach ($fields as $field) {
            $value = $data[$field['field_name']] ?? null;
            $validation = validateField($value, $field);
            if (!$validation['valid']) {
                $errors[$field['field_name']] = $validation['error'];
            }
        }
        
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'errors' => $errors], 400);
        }
        
        // Build INSERT query
        $fieldNames = array_column($fields, 'field_name');
        $values = [];
        foreach ($fieldNames as $fieldName) {
            $values[] = $data[$fieldName] ?? null;
        }
        
        $placeholders = implode(',', array_fill(0, count($fieldNames), '?'));
        $sql = "INSERT INTO data_{$table['table_name']} (" . implode(',', $fieldNames) . ") VALUES ($placeholders)";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
            $recordId = $db->lastInsertId();
            
            jsonResponse(['success' => true, 'record_id' => $recordId]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Error creating record: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'update':
        // Update record
        $tableId = $_POST['table_id'] ?? 0;
        $recordId = $_POST['record_id'] ?? 0;
        $data = $_POST['data'] ?? [];
        
        // Get table info
        $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch();
        
        if (!$table) {
            jsonResponse(['success' => false, 'error' => 'Table not found'], 404);
        }
        
        // Get fields
        $fieldsStmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ?");
        $fieldsStmt->execute([$tableId]);
        $fields = $fieldsStmt->fetchAll();
        
        // Validate data
        $errors = [];
        foreach ($fields as $field) {
            $value = $data[$field['field_name']] ?? null;
            $validation = validateField($value, $field);
            if (!$validation['valid']) {
                $errors[$field['field_name']] = $validation['error'];
            }
        }
        
        if (!empty($errors)) {
            jsonResponse(['success' => false, 'errors' => $errors], 400);
        }
        
        // Build UPDATE query
        $setParts = [];
        $values = [];
        foreach ($fields as $field) {
            $setParts[] = "{$field['field_name']} = ?";
            $values[] = $data[$field['field_name']] ?? null;
        }
        $values[] = $recordId;
        
        $sql = "UPDATE data_{$table['table_name']} SET " . implode(', ', $setParts) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
            
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Error updating record: ' . $e->getMessage()], 500);
        }
        break;
        
    case 'delete':
        // Delete record
        $tableId = $_GET['table_id'] ?? 0;
        $recordId = $_GET['record_id'] ?? 0;
        
        // Get table info
        $stmt = $db->prepare("SELECT * FROM custom_tables WHERE id = ?");
        $stmt->execute([$tableId]);
        $table = $stmt->fetch();
        
        if (!$table) {
            jsonResponse(['success' => false, 'error' => 'Table not found'], 404);
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM data_{$table['table_name']} WHERE id = ?");
            $stmt->execute([$recordId]);
            
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Error deleting record'], 500);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}
?>
