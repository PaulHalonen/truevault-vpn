<?php
/**
 * TrueVault VPN - Templates API
 * Part 13 - Task 13.9
 * CRUD for templates
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../../configs/config.php';

header('Content-Type: application/json');
define('DB_BUILDER', DB_PATH . 'builder.db');

try {
    $db = new SQLite3(DB_BUILDER);
    $db->enableExceptions(true);
    
    if (isset($_GET['id'])) {
        // Get single template
        $stmt = $db->prepare("SELECT * FROM dataforge_templates WHERE id = ?");
        $stmt->bindValue(1, intval($_GET['id']), SQLITE3_INTEGER);
        $result = $stmt->execute();
        $template = $result->fetchArray(SQLITE3_ASSOC);
        
        // Increment usage count
        if ($template) {
            $db->exec("UPDATE dataforge_templates SET usage_count = usage_count + 1 WHERE id = " . $template['id']);
        }
        
        echo json_encode(['success' => true, 'template' => $template]);
        
    } elseif (isset($_GET['category'])) {
        // List templates by category
        $stmt = $db->prepare("SELECT * FROM dataforge_templates WHERE category = ? ORDER BY name");
        $stmt->bindValue(1, $_GET['category'], SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $templates = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $templates[] = $row;
        }
        
        echo json_encode(['success' => true, 'templates' => $templates]);
        
    } else {
        // List all templates
        $result = $db->query("SELECT * FROM dataforge_templates ORDER BY category, name");
        $templates = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $templates[] = $row;
        }
        
        echo json_encode(['success' => true, 'templates' => $templates]);
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
