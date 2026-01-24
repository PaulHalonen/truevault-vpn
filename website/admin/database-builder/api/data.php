<?php
/**
 * TrueVault VPN - Data API
 * Part 13 - Task 13.7
 * CRUD for table records
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../../configs/config.php';

header('Content-Type: application/json');
define('DB_BUILDER', DB_PATH . 'builder.db');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = new SQLite3(DB_BUILDER);
    $db->enableExceptions(true);
    
    // Get table name from ID
    function getTableName($db, $tableId) {
        $stmt = $db->prepare("SELECT table_name FROM custom_tables WHERE id = ?");
        $stmt->bindValue(1, intval($tableId), SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? 'data_' . $row['table_name'] : null;
    }
    
    // Get field definitions
    function getFields($db, $tableId) {
        $stmt = $db->prepare("SELECT * FROM custom_fields WHERE table_id = ? ORDER BY sort_order");
        $stmt->bindValue(1, intval($tableId), SQLITE3_INTEGER);
        $result = $stmt->execute();
        $fields = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $fields[$row['field_name']] = $row;
        }
        return $fields;
    }
    
    switch ($method) {
        case 'GET':
            $tableId = intval($_GET['table_id'] ?? 0);
            $recordId = isset($_GET['record_id']) ? intval($_GET['record_id']) : null;
            $tableName = getTableName($db, $tableId);
            
            if (!$tableName) {
                echo json_encode(['success' => false, 'error' => 'Table not found']);
                break;
            }
            
            if ($recordId) {
                // Get single record
                $stmt = $db->prepare("SELECT * FROM {$tableName} WHERE id = ?");
                $stmt->bindValue(1, $recordId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $record = $result->fetchArray(SQLITE3_ASSOC);
                echo json_encode(['success' => true, 'record' => $record]);
            } else {
                // List all records
                $result = $db->query("SELECT * FROM {$tableName} ORDER BY id DESC");
                $records = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $records[] = $row;
                }
                echo json_encode(['success' => true, 'records' => $records]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $tableId = intval($input['table_id'] ?? 0);
            $recordId = isset($input['record_id']) && $input['record_id'] ? intval($input['record_id']) : null;
            $tableName = getTableName($db, $tableId);
            $fields = getFields($db, $tableId);
            
            if (!$tableName) {
                echo json_encode(['success' => false, 'error' => 'Table not found']);
                break;
            }
            
            // Build data array from input
            $data = [];
            foreach ($fields as $fieldName => $fieldDef) {
                if (isset($input[$fieldName])) {
                    $data[$fieldName] = $input[$fieldName];
                }
            }
            
            if (empty($data)) {
                echo json_encode(['success' => false, 'error' => 'No data provided']);
                break;
            }
            
            if ($recordId) {
                // Update existing record
                $sets = [];
                foreach ($data as $key => $value) {
                    $sets[] = "{$key} = :{$key}";
                }
                $sets[] = "updated_at = CURRENT_TIMESTAMP";
                $sql = "UPDATE {$tableName} SET " . implode(', ', $sets) . " WHERE id = :id";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':id', $recordId, SQLITE3_INTEGER);
                foreach ($data as $key => $value) {
                    $stmt->bindValue(":{$key}", $value, SQLITE3_TEXT);
                }
                $stmt->execute();
                echo json_encode(['success' => true, 'record_id' => $recordId]);
            } else {
                // Insert new record
                $columns = implode(', ', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                $sql = "INSERT INTO {$tableName} ({$columns}) VALUES ({$placeholders})";
                $stmt = $db->prepare($sql);
                foreach ($data as $key => $value) {
                    $stmt->bindValue(":{$key}", $value, SQLITE3_TEXT);
                }
                $stmt->execute();
                $newId = $db->lastInsertRowID();
                
                // Update record count
                $stmt = $db->prepare("UPDATE custom_tables SET record_count = record_count + 1 WHERE id = ?");
                $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'record_id' => $newId]);
            }
            break;
            
        case 'PATCH':
            // Inline edit single field
            $input = json_decode(file_get_contents('php://input'), true);
            $tableId = intval($input['table_id'] ?? 0);
            $recordId = intval($input['record_id'] ?? 0);
            $field = preg_replace('/[^a-z0-9_]/', '', $input['field'] ?? '');
            $value = $input['value'] ?? '';
            $tableName = getTableName($db, $tableId);
            
            if (!$tableName || !$recordId || !$field) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                break;
            }
            
            $stmt = $db->prepare("UPDATE {$tableName} SET {$field} = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bindValue(1, $value, SQLITE3_TEXT);
            $stmt->bindValue(2, $recordId, SQLITE3_INTEGER);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $tableId = intval($input['table_id'] ?? 0);
            $tableName = getTableName($db, $tableId);
            
            if (!$tableName) {
                echo json_encode(['success' => false, 'error' => 'Table not found']);
                break;
            }
            
            if (isset($input['record_ids']) && is_array($input['record_ids'])) {
                // Bulk delete
                $ids = array_map('intval', $input['record_ids']);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("DELETE FROM {$tableName} WHERE id IN ({$placeholders})");
                foreach ($ids as $i => $id) {
                    $stmt->bindValue($i + 1, $id, SQLITE3_INTEGER);
                }
                $stmt->execute();
                $deleted = $db->changes();
                
                // Update record count
                $stmt = $db->prepare("UPDATE custom_tables SET record_count = record_count - ? WHERE id = ?");
                $stmt->bindValue(1, $deleted, SQLITE3_INTEGER);
                $stmt->bindValue(2, $tableId, SQLITE3_INTEGER);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'deleted' => $deleted]);
            } elseif (isset($input['record_id'])) {
                // Single delete
                $recordId = intval($input['record_id']);
                $stmt = $db->prepare("DELETE FROM {$tableName} WHERE id = ?");
                $stmt->bindValue(1, $recordId, SQLITE3_INTEGER);
                $stmt->execute();
                
                // Update record count
                $stmt = $db->prepare("UPDATE custom_tables SET record_count = record_count - 1 WHERE id = ?");
                $stmt->bindValue(1, $tableId, SQLITE3_INTEGER);
                $stmt->execute();
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Record ID required']);
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
