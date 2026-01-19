<?php
/**
 * TrueVault VPN - Parental Whitelist API
 * 
 * Manages always-allowed domains and temporary blocks
 * 
 * Endpoints:
 * GET    /api/parental/whitelist.php - List whitelist
 * POST   /api/parental/whitelist.php - Add to whitelist
 * DELETE /api/parental/whitelist.php - Remove from whitelist
 * 
 * GET    /api/parental/whitelist.php?temp_blocks=1 - List temp blocks
 * POST   /api/parental/whitelist.php?action=temp_block - Add temp block
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$auth = Auth::authenticate();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $auth['user']['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection('parental');
    
    if (isset($_GET['temp_blocks'])) {
        handleTempBlocks($conn, $userId, $method);
    } else {
        switch ($method) {
            case 'GET':
                handleGet($conn, $userId);
                break;
            case 'POST':
                handlePost($conn, $userId);
                break;
            case 'DELETE':
                handleDelete($conn, $userId);
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

function handleGet($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT * FROM parental_whitelist 
        WHERE user_id = ?
        ORDER BY added_at DESC
    ");
    $stmt->execute([$userId]);
    $whitelist = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'whitelist' => $whitelist,
        'count' => count($whitelist)
    ]);
}

function handlePost($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['domain'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Domain required']);
        return;
    }
    
    $domain = strtolower(trim($input['domain']));
    
    // Validate domain format
    if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid domain format']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO parental_whitelist 
            (user_id, domain, category, notes)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $domain,
            $input['category'] ?? 'educational',
            $input['notes'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Domain added to whitelist',
            'id' => $conn->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Domain already whitelisted']);
        } else {
            throw $e;
        }
    }
}

function handleDelete($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID required']);
        return;
    }
    
    $stmt = $conn->prepare("
        DELETE FROM parental_whitelist 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([(int)$input['id'], $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Domain removed from whitelist'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Entry not found']);
    }
}

function handleTempBlocks($conn, $userId, $method) {
    if ($method === 'GET') {
        // List active temporary blocks
        $stmt = $conn->prepare("
            SELECT * FROM temporary_blocks 
            WHERE user_id = ? 
            AND blocked_until > datetime('now')
            ORDER BY blocked_until DESC
        ");
        $stmt->execute([$userId]);
        $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clean up expired blocks
        $stmt = $conn->prepare("
            DELETE FROM temporary_blocks 
            WHERE user_id = ? AND blocked_until <= datetime('now')
        ");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'temp_blocks' => $blocks,
            'count' => count($blocks)
        ]);
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['domain']) || !isset($input['blocked_until'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Domain and blocked_until required']);
            return;
        }
        
        $domain = strtolower(trim($input['domain']));
        
        $stmt = $conn->prepare("
            INSERT INTO temporary_blocks 
            (user_id, domain, blocked_until, reason, added_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $domain,
            $input['blocked_until'],
            $input['reason'] ?? 'Parent override',
            'parent'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Temporary block added',
            'id' => $conn->lastInsertId()
        ]);
        
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID required']);
            return;
        }
        
        $stmt = $conn->prepare("
            DELETE FROM temporary_blocks 
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([(int)$input['id'], $userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Temporary block removed'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Block not found']);
        }
    }
}
?>
