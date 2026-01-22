<?php
/**
 * TrueVault VPN - Get Theme API
 * Part 8 - Task 8.6
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$themeId = $_GET['id'] ?? null;
$active = isset($_GET['active']);

try {
    $db = new SQLite3(DB_THEMES);
    $db->enableExceptions(true);
    
    if ($active) {
        // Get active theme
        $result = $db->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
    } elseif ($themeId) {
        // Get specific theme
        $stmt = $db->prepare("SELECT * FROM themes WHERE id = :id");
        $stmt->bindValue(':id', $themeId, SQLITE3_INTEGER);
        $result = $stmt->execute();
    } else {
        // Get all themes
        $result = $db->query("SELECT * FROM themes ORDER BY category, display_name");
        $themes = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['colors'] = json_decode($row['colors'], true);
            $row['fonts'] = json_decode($row['fonts'], true);
            $row['spacing'] = json_decode($row['spacing'], true);
            $row['borders'] = json_decode($row['borders'], true);
            $row['shadows'] = json_decode($row['shadows'], true);
            $themes[] = $row;
        }
        $db->close();
        echo json_encode(['success' => true, 'themes' => $themes]);
        exit;
    }
    
    $theme = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$theme) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Theme not found']);
        exit;
    }
    
    // Parse JSON fields
    $theme['colors'] = json_decode($theme['colors'], true);
    $theme['fonts'] = json_decode($theme['fonts'], true);
    $theme['spacing'] = json_decode($theme['spacing'], true);
    $theme['borders'] = json_decode($theme['borders'], true);
    $theme['shadows'] = json_decode($theme['shadows'], true);
    
    echo json_encode(['success' => true, 'theme' => $theme]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
