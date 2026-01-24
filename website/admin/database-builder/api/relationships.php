<?php
/**
 * TrueVault VPN - Relationships API
 * Part 13 - Task 13.7
 * CRUD for table relationships
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
            $result = $db->query("SELECT r.*, pt.display_name as parent_name, ct.display_name as child_name 
                FROM table_relationships r 
                JOIN custom_tables pt ON r.parent_table_id = pt.id 
                JOIN custom_tables ct ON r.child_table_id = ct.id");
            $relationships = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $relationships[] = $row;
            }
            echo json_encode(['success' => true, 'relationships' => $relationships]);
            break;
            
        case 'POST':
            $parentTableId = intval($_POST['parent_table_id']);
            $childTableId = intval($_POST['child_table_id']);
            $relationshipType = $_POST['relationship_type'] ?? 'one_to_many';
            $parentField = $_POST['parent_field'] ?? 'id';
            $childField = $_POST['child_field'] ?? '';
            $cascadeDelete = isset($_POST['cascade_delete']) ? 1 : 0;
            
            if (!$parentTableId || !$childTableId || !$childField) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                break;
            }
            
            // Check for circular references
            if ($parentTableId === $childTableId) {
                echo json_encode(['success' => false, 'error' => 'Cannot create self-referencing relationship']);
                break;
            }
            
            $stmt = $db->prepare("INSERT INTO table_relationships (parent_table_id, child_table_id, relationship_type, parent_field, child_field, cascade_delete) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $parentTableId, SQLITE3_INTEGER);
            $stmt->bindValue(2, $childTableId, SQLITE3_INTEGER);
            $stmt->bindValue(3, $relationshipType, SQLITE3_TEXT);
            $stmt->bindValue(4, $parentField, SQLITE3_TEXT);
            $stmt->bindValue(5, $childField, SQLITE3_TEXT);
            $stmt->bindValue(6, $cascadeDelete, SQLITE3_INTEGER);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'relationship_id' => $db->lastInsertRowID()]);
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $relationshipId = intval($input['relationship_id'] ?? 0);
            
            if ($relationshipId > 0) {
                $stmt = $db->prepare("DELETE FROM table_relationships WHERE id = ?");
                $stmt->bindValue(1, $relationshipId, SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Relationship ID required']);
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
