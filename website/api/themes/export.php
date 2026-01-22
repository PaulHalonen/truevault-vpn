<?php
/**
 * TrueVault VPN - Export Theme API
 * Part 8 - Task 8.6
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

$themeId = $_GET['id'] ?? null;

if (!$themeId) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Theme ID required']);
    exit;
}

try {
    $db = new SQLite3(DB_THEMES);
    $db->enableExceptions(true);
    
    $stmt = $db->prepare("SELECT * FROM themes WHERE id = :id");
    $stmt->bindValue(':id', $themeId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $theme = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$theme) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Theme not found']);
        exit;
    }
    
    // Parse JSON fields for clean export
    $exportData = [
        'theme_name' => $theme['theme_name'],
        'display_name' => $theme['display_name'],
        'description' => $theme['description'],
        'category' => $theme['category'],
        'season' => $theme['season'],
        'holiday' => $theme['holiday'],
        'colors' => json_decode($theme['colors'], true),
        'fonts' => json_decode($theme['fonts'], true),
        'spacing' => json_decode($theme['spacing'], true),
        'borders' => json_decode($theme['borders'], true),
        'shadows' => json_decode($theme['shadows'], true),
        'exported_at' => date('Y-m-d H:i:s'),
        'version' => '1.0'
    ];
    
    // Set headers for file download
    $filename = $theme['theme_name'] . '_theme.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
