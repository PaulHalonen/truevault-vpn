<?php
/**
 * Delete Section API
 */
define('TRUEVAULT_INIT', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/PageBuilder.php';

$input = json_decode(file_get_contents('php://input'), true);
$sectionId = intval($input['section_id'] ?? 0);

if ($sectionId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid section ID']);
    exit;
}

$success = PageBuilder::deleteSection($sectionId);

echo json_encode(['success' => $success]);
?>
