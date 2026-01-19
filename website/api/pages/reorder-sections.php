<?php
/**
 * Reorder Sections API
 */
define('TRUEVAULT_INIT', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/PageBuilder.php';

$input = json_decode(file_get_contents('php://input'), true);
$pageId = intval($input['page_id'] ?? 0);
$order = $input['order'] ?? [];

if ($pageId <= 0 || empty($order)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$success = PageBuilder::reorderSections($pageId, $order);

echo json_encode(['success' => $success]);
?>
