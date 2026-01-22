<?php
/**
 * TrueVault VPN - Activate Theme API
 * Part 8 - Task 8.6
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$themeId = $input['theme_id'] ?? null;

if (!$themeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Theme ID required']);
    exit;
}

try {
    $db = new SQLite3(DB_THEMES);
    $db->enableExceptions(true);
    
    // Verify theme exists
    $checkStmt = $db->prepare("SELECT id, display_name FROM themes WHERE id = :id");
    $checkStmt->bindValue(':id', $themeId, SQLITE3_INTEGER);
    $result = $checkStmt->execute();
    $theme = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$theme) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Theme not found']);
        exit;
    }
    
    // Deactivate all themes
    $db->exec("UPDATE themes SET is_active = 0");
    
    // Activate selected theme
    $activateStmt = $db->prepare("UPDATE themes SET is_active = 1 WHERE id = :id");
    $activateStmt->bindValue(':id', $themeId, SQLITE3_INTEGER);
    $activateStmt->execute();
    
    // Update current_theme_id in settings
    $settingStmt = $db->prepare("UPDATE theme_settings SET setting_value = :id, updated_at = datetime('now') WHERE setting_key = 'current_theme_id'");
    $settingStmt->bindValue(':id', (string)$themeId, SQLITE3_TEXT);
    $settingStmt->execute();
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Theme activated: ' . $theme['display_name'],
        'theme_id' => $themeId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
