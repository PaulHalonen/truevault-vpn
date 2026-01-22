<?php
/**
 * TrueVault VPN - Update Theme API
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
    $checkStmt = $db->prepare("SELECT id FROM themes WHERE id = :id");
    $checkStmt->bindValue(':id', $themeId, SQLITE3_INTEGER);
    $result = $checkStmt->execute();
    
    if (!$result->fetchArray()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Theme not found']);
        exit;
    }
    
    // Build update query dynamically based on provided fields
    $updates = [];
    $params = [];
    
    if (isset($input['display_name'])) {
        $updates[] = "display_name = :display_name";
        $params[':display_name'] = [$input['display_name'], SQLITE3_TEXT];
    }
    
    if (isset($input['description'])) {
        $updates[] = "description = :description";
        $params[':description'] = [$input['description'], SQLITE3_TEXT];
    }
    
    if (isset($input['colors'])) {
        $updates[] = "colors = :colors";
        $params[':colors'] = [json_encode($input['colors']), SQLITE3_TEXT];
    }
    
    if (isset($input['fonts'])) {
        $updates[] = "fonts = :fonts";
        $params[':fonts'] = [json_encode($input['fonts']), SQLITE3_TEXT];
    }
    
    if (isset($input['spacing'])) {
        $updates[] = "spacing = :spacing";
        $params[':spacing'] = [json_encode($input['spacing']), SQLITE3_TEXT];
    }
    
    if (isset($input['borders'])) {
        $updates[] = "borders = :borders";
        $params[':borders'] = [json_encode($input['borders']), SQLITE3_TEXT];
    }
    
    if (isset($input['shadows'])) {
        $updates[] = "shadows = :shadows";
        $params[':shadows'] = [json_encode($input['shadows']), SQLITE3_TEXT];
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => true, 'message' => 'No changes to save']);
        exit;
    }
    
    $updates[] = "updated_at = datetime('now')";
    
    $sql = "UPDATE themes SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $themeId, SQLITE3_INTEGER);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value[0], $value[1]);
    }
    
    $stmt->execute();
    $db->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Theme updated successfully',
        'theme_id' => $themeId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
