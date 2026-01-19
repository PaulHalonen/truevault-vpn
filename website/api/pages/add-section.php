<?php
/**
 * Add Section API
 */
define('TRUEVAULT_INIT', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/PageBuilder.php';

$input = json_decode(file_get_contents('php://input'), true);
$pageId = intval($input['page_id'] ?? 0);
$type = $input['type'] ?? '';
$data = $input['data'] ?? [];

if ($pageId <= 0 || empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$sectionId = PageBuilder::addSection($pageId, $type, $data);

echo json_encode([
    'success' => $sectionId !== false,
    'section_id' => $sectionId
]);
?>
