<?php
/**
 * TrueVault VPN - Get Theme Colors API
 * 
 * Returns all colors for a specific theme
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/Theme.php';

// Get theme ID
$themeId = intval($_GET['theme_id'] ?? 0);

if ($themeId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid theme ID']);
    exit;
}

try {
    $colors = Theme::getAllColors($themeId);
    
    echo json_encode([
        'success' => true,
        'theme_id' => $themeId,
        'colors' => $colors
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
