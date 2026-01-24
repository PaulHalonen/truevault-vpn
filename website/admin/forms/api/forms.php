<?php
/**
 * TrueVault VPN - Forms API
 * Part 14 - Task 14.6
 * CRUD operations for forms
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../../configs/config.php';

header('Content-Type: application/json');
define('DB_FORMS', DB_PATH . 'forms.db');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = new SQLite3(DB_FORMS);
    $db->enableExceptions(true);
    
    switch ($method) {
        case 'GET':
            $formId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($formId) {
                $stmt = $db->prepare("SELECT * FROM forms WHERE id = ?");
                $stmt->bindValue(1, $formId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $form = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($form) {
                    $form['fields'] = json_decode($form['fields'], true);
                    $form['settings'] = json_decode($form['settings'], true);
                    echo json_encode(['success' => true, 'form' => $form]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Form not found']);
                }
            } else {
                // List all forms
                $result = $db->query("SELECT * FROM forms ORDER BY display_name");
                $forms = [];
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $forms[] = $row;
                }
                echo json_encode(['success' => true, 'forms' => $forms]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $formId = intval($input['id'] ?? 0);
            $displayName = $input['display_name'] ?? 'Untitled Form';
            $formName = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace(' ', '_', $displayName)));
            $category = $input['category'] ?? 'customer';
            $style = $input['style'] ?? 'business';
            $description = $input['description'] ?? '';
            $fields = json_encode($input['fields'] ?? []);
            $settings = json_encode($input['settings'] ?? []);
            
            if ($formId > 0) {
                // Update existing
                $stmt = $db->prepare("UPDATE forms SET display_name = ?, form_name = ?, category = ?, style = ?, description = ?, fields = ?, settings = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND is_template = 0");
                $stmt->bindValue(1, $displayName, SQLITE3_TEXT);
                $stmt->bindValue(2, $formName, SQLITE3_TEXT);
                $stmt->bindValue(3, $category, SQLITE3_TEXT);
                $stmt->bindValue(4, $style, SQLITE3_TEXT);
                $stmt->bindValue(5, $description, SQLITE3_TEXT);
                $stmt->bindValue(6, $fields, SQLITE3_TEXT);
                $stmt->bindValue(7, $settings, SQLITE3_TEXT);
                $stmt->bindValue(8, $formId, SQLITE3_INTEGER);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'form_id' => $formId]);
            } else {
                // Create new
                $stmt = $db->prepare("INSERT INTO forms (form_name, display_name, category, style, description, fields, settings, is_template) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                $stmt->bindValue(1, $formName, SQLITE3_TEXT);
                $stmt->bindValue(2, $displayName, SQLITE3_TEXT);
                $stmt->bindValue(3, $category, SQLITE3_TEXT);
                $stmt->bindValue(4, $style, SQLITE3_TEXT);
                $stmt->bindValue(5, $description, SQLITE3_TEXT);
                $stmt->bindValue(6, $fields, SQLITE3_TEXT);
                $stmt->bindValue(7, $settings, SQLITE3_TEXT);
                $stmt->execute();
                
                $newId = $db->lastInsertRowID();
                echo json_encode(['success' => true, 'form_id' => $newId]);
            }
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $formId = intval($input['id'] ?? 0);
            
            if ($formId > 0) {
                $stmt = $db->prepare("DELETE FROM forms WHERE id = ? AND is_template = 0");
                $stmt->bindValue(1, $formId, SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Form ID required']);
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
