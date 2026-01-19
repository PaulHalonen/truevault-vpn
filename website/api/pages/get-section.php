<?php
/**
 * Get Section Data API
 */
define('TRUEVAULT_INIT', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/Database.php';

$sectionId = intval($_GET['section_id'] ?? 0);

if ($sectionId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid section ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $themesConn = $db->getConnection('themes');
    
    $stmt = $themesConn->prepare("SELECT * FROM page_sections WHERE id = ?");
    $stmt->execute([$sectionId]);
    $section = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$section) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Section not found']);
        exit;
    }
    
    $section['section_data'] = json_decode($section['section_data'], true);
    
    echo json_encode([
        'success' => true,
        'section' => $section
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
