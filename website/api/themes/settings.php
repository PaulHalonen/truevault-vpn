<?php
/**
 * TrueVault VPN - Theme Settings API
 * Part 8 - Task 8.6
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = new SQLite3(DB_THEMES);
    $db->enableExceptions(true);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all settings
        $result = $db->query("SELECT * FROM theme_settings ORDER BY setting_key");
        $settings = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'type' => $row['setting_type'],
                'description' => $row['description']
            ];
        }
        $db->close();
        echo json_encode(['success' => true, 'settings' => $settings]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update setting
        $input = json_decode(file_get_contents('php://input'), true);
        $key = $input['key'] ?? null;
        $value = $input['value'] ?? null;
        
        if (!$key) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Setting key required']);
            exit;
        }
        
        // Check if setting exists
        $checkStmt = $db->prepare("SELECT id FROM theme_settings WHERE setting_key = :key");
        $checkStmt->bindValue(':key', $key, SQLITE3_TEXT);
        $exists = $checkStmt->execute()->fetchArray();
        
        if ($exists) {
            // Update
            $stmt = $db->prepare("UPDATE theme_settings SET setting_value = :value, updated_at = datetime('now') WHERE setting_key = :key");
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO theme_settings (setting_key, setting_value) VALUES (:key, :value)");
        }
        
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', (string)$value, SQLITE3_TEXT);
        $stmt->execute();
        $db->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Setting updated',
            'key' => $key,
            'value' => $value
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
