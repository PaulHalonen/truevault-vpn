<?php
/**
 * User Login API Endpoint - SQLITE3 VERSION
 * 
 * PURPOSE: Authenticate users and return JWT token
 * METHOD: POST
 * ENDPOINT: /api/auth/login.php
 * 
 * CRITICAL: Uses SQLite3 class, NOT PDO!
 * 
 * REQUEST BODY:
 * {
 *   "email": "user@example.com",
 *   "password": "SecurePass123"
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
    $validator->password($data['password'] ?? '', 'password', false); // Don't require strong for login
    
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
    
    // ============================================
    // STEP 3: FIND USER (SQLite3)
    // ============================================
    
    $usersDb = Database::getInstance('users');
    
    $stmt = $usersDb->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$user) {
        // Log failed attempt
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO security_events (event_type, severity, email, ip_address, details, created_at)
            VALUES ('login_failed', 'medium', :email, :ip, :details, datetime('now'))
        ");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':details', json_encode(['reason' => 'user_not_found']), SQLITE3_TEXT);
        $stmt->execute();
        
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit;
    }
    
    // ============================================
    // STEP 4: CHECK ACCOUNT STATUS
    // ============================================
    
    // Check if account is locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remainingSeconds = strtotime($user['locked_until']) - time();
        $remainingMinutes = ceil($remainingSeconds / 60);
        
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => "Account temporarily locked. Try again in {$remainingMinutes} minute(s).",
            'code' => 'ACCOUNT_LOCKED'
        ]);
        exit;
    }
    
    // Check account status
    if ($user['status'] === 'suspended') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Account has been suspended. Please contact support.',
            'code' => 'ACCOUNT_SUSPENDED'
        ]);
        exit;
    }
    
    if ($user['status'] === 'cancelled') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Account has been cancelled.',
            'code' => 'ACCOUNT_CANCELLED'
        ]);
        exit;
    }
    
    // ============================================
    // STEP 5: VERIFY PASSWORD
    // ============================================
    
    if (!password_verify($password, $user['password_hash'])) {
        // Increment login attempts
        $newAttempts = ($user['login_attempts'] ?? 0) + 1;
        $lockUntil = null;
        
        // Lock after MAX_LOGIN_ATTEMPTS
        if ($newAttempts >= MAX_LOGIN_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
        }
        
        $stmt = $usersDb->prepare("
            UPDATE users 
            SET login_attempts = :attempts, locked_until = :locked
            WHERE id = :id
        ");
        $stmt->bindValue(':attempts', $newAttempts, SQLITE3_INTEGER);
        $stmt->bindValue(':locked', $lockUntil, $lockUntil ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        // Log failed attempt
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO security_events (event_type, severity, user_id, email, ip_address, details, created_at)
            VALUES ('login_failed', 'medium', :user_id, :email, :ip, :details, datetime('now'))
        ");
        $stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':details', json_encode(['reason' => 'wrong_password', 'attempts' => $newAttempts]), SQLITE3_TEXT);
        $stmt->execute();
        
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit;
    }
    
    // ============================================
    // STEP 6: SUCCESSFUL LOGIN - RESET ATTEMPTS
    // ============================================
    
    $stmt = $usersDb->prepare("
        UPDATE users 
        SET login_attempts = 0, locked_until = NULL, last_login = datetime('now'), updated_at = datetime('now')
        WHERE id = :id
    ");
    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    // ============================================
    // STEP 7: GENERATE JWT TOKEN
    // ============================================
    
    $token = JWT::generate([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'tier' => $user['tier']
    ]);
    
    // ============================================
    // STEP 8: CREATE SESSION
    // ============================================
    
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $usersDb->prepare("
        INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at, last_activity)
        VALUES (:user_id, :session_token, :ip, :ua, :expires, datetime('now'), datetime('now'))
    ");
    $stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':session_token', $sessionToken, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->bindValue(':ua', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->bindValue(':expires', $expiresAt, SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 9: LOG SUCCESSFUL LOGIN
    // ============================================
    
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
        VALUES (:user_id, 'login', 'user', :entity_id, :details, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':entity_id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':details', json_encode(['tier' => $user['tier']]), SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 10: RETURN SUCCESS
    // ============================================
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'tier' => $user['tier'],
            'vip_approved' => (bool)$user['vip_approved'],
            'email_verified' => (bool)$user['email_verified']
        ],
        'token' => $token
    ]);
    
} catch (Exception $e) {
    // Log error
    logError('Login failed: ' . $e->getMessage(), [
        'email' => $data['email'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed. Please try again.'
    ]);
}
