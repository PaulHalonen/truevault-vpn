<?php
/**
 * User Registration API Endpoint - SQLITE3 VERSION
 * 
 * PURPOSE: Register new users with VIP auto-detection
 * METHOD: POST
 * ENDPOINT: /api/auth/register.php
 * 
 * CRITICAL: Uses SQLite3 class, NOT PDO!
 * 
 * REQUEST BODY:
 * {
 *   "email": "user@example.com",
 *   "password": "SecurePass123",
 *   "first_name": "John",
 *   "last_name": "Doe"
 * }
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../../configs/config.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    // ============================================
    // STEP 1: GET AND DECODE INPUT
    // ============================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }
    
    // ============================================
    // STEP 2: VALIDATE INPUT
    // ============================================
    
    $validator = new Validator();
    
    $validator->email($data['email'] ?? '', 'email');
    $validator->password($data['password'] ?? '', 'password');
    $validator->string($data['first_name'] ?? '', 'first_name', 1, 50);
    $validator->string($data['last_name'] ?? '', 'last_name', 1, 50);
    
    if ($validator->hasErrors()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $validator->getFirstError(),
            'errors' => $validator->getErrors()
        ]);
        exit;
    }
    
    $email = $validator->get('email');
    $password = $validator->get('password');
    $firstName = $validator->get('first_name');
    $lastName = $validator->get('last_name');
    
    // ============================================
    // STEP 3: CHECK IF EMAIL EXISTS (SQLite3)
    // ============================================
    
    $usersDb = Database::getInstance('users');
    
    $stmt = $usersDb->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    if ($result->fetchArray(SQLITE3_ASSOC)) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'An account with this email already exists',
            'code' => 'EMAIL_EXISTS'
        ]);
        exit;
    }
    
    // ============================================
    // STEP 4: CHECK VIP STATUS (SQLite3)
    // ============================================
    
    $adminDb = Database::getInstance('admin');
    $isVip = false;
    $vipServerId = null;
    $tier = 'standard';
    
    $stmt = $adminDb->prepare("SELECT * FROM vip_list WHERE email = :email AND status = 'active'");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $vipRecord = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($vipRecord) {
        $isVip = true;
        $tier = 'vip';
        $vipServerId = $vipRecord['dedicated_server_id'];
        
        // Log VIP registration
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO security_events (event_type, severity, email, ip_address, details, created_at)
            VALUES ('vip_registration', 'low', :email, :ip, :details, datetime('now'))
        ");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':details', json_encode(['vip_email' => $email, 'access_level' => $vipRecord['access_level']]), SQLITE3_TEXT);
        $stmt->execute();
    }
    
    // ============================================
    // STEP 5: CREATE USER ACCOUNT (SQLite3)
    // ============================================
    
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    
    $stmt = $usersDb->prepare("
        INSERT INTO users (
            email, password_hash, first_name, last_name, tier,
            vip_approved, vip_server_id, status, email_verified,
            created_at, updated_at
        ) VALUES (
            :email, :password_hash, :first_name, :last_name, :tier,
            :vip_approved, :vip_server_id, 'active', 0,
            datetime('now'), datetime('now')
        )
    ");
    
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password_hash', $hashedPassword, SQLITE3_TEXT);
    $stmt->bindValue(':first_name', $firstName, SQLITE3_TEXT);
    $stmt->bindValue(':last_name', $lastName, SQLITE3_TEXT);
    $stmt->bindValue(':tier', $tier, SQLITE3_TEXT);
    $stmt->bindValue(':vip_approved', $isVip ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(':vip_server_id', $vipServerId, $vipServerId ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->execute();
    
    $userId = $usersDb->lastInsertRowID();
    
    // ============================================
    // STEP 6: GENERATE JWT TOKEN
    // ============================================
    
    $token = JWT::generate([
        'user_id' => $userId,
        'email' => $email,
        'tier' => $tier
    ]);
    
    // ============================================
    // STEP 7: CREATE SESSION (SQLite3)
    // ============================================
    
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $usersDb->prepare("
        INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at, last_activity)
        VALUES (:user_id, :session_token, :ip, :ua, :expires, datetime('now'), datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':session_token', $sessionToken, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->bindValue(':ua', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->bindValue(':expires', $expiresAt, SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 8: LOG REGISTRATION (SQLite3)
    // ============================================
    
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
        VALUES (:user_id, 'register', 'user', :entity_id, :details, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':entity_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':details', json_encode(['tier' => $tier, 'vip' => $isVip, 'dedicated_server_id' => $vipServerId]), SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 9: RETURN SUCCESS
    // ============================================
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => $isVip ? 'VIP account created successfully!' : 'Account created successfully',
        'user' => [
            'id' => $userId,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'tier' => $tier,
            'vip_approved' => $isVip,
            'dedicated_server_id' => $vipServerId
        ],
        'token' => $token
    ]);
    
} catch (Exception $e) {
    // Log error
    logError('Registration failed: ' . $e->getMessage(), [
        'email' => $data['email'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Registration failed. Please try again.'
    ]);
}
