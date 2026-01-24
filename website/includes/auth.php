<?php
/**
 * TrueVault VPN - Authentication Helper
 * 
 * PURPOSE: JWT-based authentication for API endpoints
 * USES: SQLite3 (NOT PDO!)
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Security check
if (!defined('TRUEVAULT_INIT')) {
    define('TRUEVAULT_INIT', true);
}

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/JWT.php';

/**
 * Authenticate request using JWT token
 * 
 * @return array|null User data if authenticated, null otherwise
 */
function authenticateRequest() {
    // Get Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    // Check for Bearer token
    if (!preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    
    try {
        // Verify JWT token
        $jwt = new JWT();
        $payload = $jwt->verify($token);
        
        if (!$payload || !isset($payload['user_id'])) {
            return null;
        }
        
        // Get user from database
        $db = new SQLite3(DB_USERS);
        $db->enableExceptions(true);
        
        $stmt = $db->prepare("SELECT id, email, first_name, last_name, tier, status FROM users WHERE id = ? AND status = 'active'");
        $stmt->bindValue(1, $payload['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) {
            return null;
        }
        
        // Update last activity
        $updateStmt = $db->prepare("UPDATE sessions SET last_activity = CURRENT_TIMESTAMP WHERE session_token = ?");
        $updateStmt->bindValue(1, $token, SQLITE3_TEXT);
        $updateStmt->execute();
        
        return $user;
        
    } catch (Exception $e) {
        error_log("Auth error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get current authenticated user from session
 * 
 * @return array|null User data if session valid, null otherwise
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $db = new SQLite3(DB_USERS);
        $db->enableExceptions(true);
        
        $stmt = $db->prepare("SELECT id, email, first_name, last_name, tier, status FROM users WHERE id = ? AND status = 'active'");
        $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        return $user ?: null;
        
    } catch (Exception $e) {
        error_log("Session auth error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if user has required tier
 * 
 * @param array $user User data
 * @param string|array $requiredTier Required tier(s)
 * @return bool
 */
function hasRequiredTier($user, $requiredTier) {
    if (!$user) return false;
    
    $tierHierarchy = ['standard' => 1, 'pro' => 2, 'vip' => 3, 'admin' => 4];
    
    if (is_array($requiredTier)) {
        return in_array($user['tier'], $requiredTier);
    }
    
    $userLevel = $tierHierarchy[$user['tier']] ?? 0;
    $requiredLevel = $tierHierarchy[$requiredTier] ?? 0;
    
    return $userLevel >= $requiredLevel;
}

/**
 * Require authentication - redirects or returns error if not authenticated
 * 
 * @param bool $apiMode If true, return JSON error. If false, redirect.
 * @return array|void User data if authenticated
 */
function requireAuth($apiMode = false) {
    $user = authenticateRequest() ?: getCurrentUser();
    
    if (!$user) {
        if ($apiMode) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Authentication required']);
            exit;
        } else {
            header('Location: ' . BASE_URL . 'login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
    
    return $user;
}

/**
 * Generate authentication token for user
 * 
 * @param int $userId User ID
 * @return string JWT token
 */
function generateAuthToken($userId) {
    $jwt = new JWT();
    return $jwt->generate([
        'user_id' => $userId,
        'issued_at' => time(),
        'expires_at' => time() + JWT_EXPIRATION
    ]);
}
?>
