# TRUEVAULT VPN - MASTER BUILD CHECKLIST (Part 3 - Continued)

**Continuation:** Day 3 Afternoon - Login, Password Reset, Sessions  
**Lines This Section:** ~750 lines  
**Time Estimate:** 3-4 hours  
**Created:** January 15, 2026 - 8:10 AM CST  
**CORRECTED:** January 21, 2026 - 4:50 AM CST - SQLITE3 (NOT PDO!)

---

## ⚠️ CRITICAL: THIS FILE USES SQLITE3 - NOT PDO!

All database code uses:
```php
$db = Database::getInstance('users');
$stmt = $db->prepare($sql);
$stmt->bindValue(':param', $value, SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
```

---

## DAY 3 AFTERNOON: LOGIN & SESSION MANAGEMENT

### **Task 3.5: Create Login API Endpoint**
**Lines:** ~300 lines  
**File:** `/api/auth/login.php`

- [✅] Create new file: `/api/auth/login.php`
- [✅] Add this complete code:

```php
<?php
/**
 * User Login API Endpoint - SQLITE3 VERSION
 * 
 * PURPOSE: Authenticate users and issue JWT tokens
 * METHOD: POST
 * ENDPOINT: /api/auth/login.php
 * 
 * CRITICAL: Uses SQLite3 class, NOT PDO!
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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
    
    if (empty($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Password is required']);
        exit;
    }
    
    if ($validator->hasErrors()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $validator->getFirstError()
        ]);
        exit;
    }
    
    $email = $validator->get('email');
    $password = $data['password'];
    
    // ============================================
    // STEP 3: GET USER FROM DATABASE (SQLite3)
    // ============================================
    
    $usersDb = Database::getInstance('users');
    
    $stmt = $usersDb->prepare("
        SELECT id, email, password, first_name, last_name, tier, 
               status, email_verified, login_attempts, locked_until,
               vip_approved, vip_server_id
        FROM users 
        WHERE email = :email
    ");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    // ============================================
    // STEP 4: CHECK IF USER EXISTS
    // ============================================
    
    if (!$user) {
        // Log failed attempt
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO security_events (event_type, email, ip_address, details, created_at)
            VALUES ('login_failed_unknown', :email, :ip, :details, datetime('now'))
        ");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':details', json_encode(['reason' => 'email not found']), SQLITE3_TEXT);
        $stmt->execute();
        
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit;
    }
    
    // ============================================
    // STEP 5: CHECK IF ACCOUNT IS LOCKED
    // ============================================
    
    if ($user['locked_until']) {
        $lockedUntil = strtotime($user['locked_until']);
        
        if ($lockedUntil > time()) {
            $minutesRemaining = ceil(($lockedUntil - time()) / 60);
            http_response_code(423);
            echo json_encode([
                'success' => false,
                'error' => "Account locked. Try again in {$minutesRemaining} minutes."
            ]);
            exit;
        } else {
            // Lock expired, reset
            $stmt = $usersDb->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = :id");
            $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
            $stmt->execute();
        }
    }
    
    // ============================================
    // STEP 6: CHECK ACCOUNT STATUS
    // ============================================
    
    if ($user['status'] === 'suspended') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Account suspended. Contact support.']);
        exit;
    }
    
    if ($user['status'] === 'cancelled') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Account cancelled.']);
        exit;
    }
    
    // ============================================
    // STEP 7: VERIFY PASSWORD
    // ============================================
    
    if (!password_verify($password, $user['password'])) {
        // Increment failed attempts
        $attempts = ($user['login_attempts'] ?? 0) + 1;
        $lockUntil = null;
        
        // Lock after 5 attempts for 15 minutes
        if ($attempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        }
        
        $stmt = $usersDb->prepare("UPDATE users SET login_attempts = :attempts, locked_until = :locked WHERE id = :id");
        $stmt->bindValue(':attempts', $attempts, SQLITE3_INTEGER);
        $stmt->bindValue(':locked', $lockUntil, $lockUntil ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        // Log failed attempt
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO security_events (event_type, email, ip_address, details, created_at)
            VALUES ('login_failed_password', :email, :ip, :details, datetime('now'))
        ");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':details', json_encode(['attempts' => $attempts]), SQLITE3_TEXT);
        $stmt->execute();
        
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit;
    }
    
    // ============================================
    // STEP 8: SUCCESSFUL LOGIN - RESET ATTEMPTS
    // ============================================
    
    $stmt = $usersDb->prepare("
        UPDATE users 
        SET login_attempts = 0, locked_until = NULL, last_login = datetime('now'), updated_at = datetime('now')
        WHERE id = :id
    ");
    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    // ============================================
    // STEP 9: GENERATE JWT TOKEN
    // ============================================
    
    $token = JWT::generate([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'tier' => $user['tier']
    ]);
    
    // ============================================
    // STEP 10: CREATE SESSION (SQLite3)
    // ============================================
    
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $usersDb->prepare("
        INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at, created_at)
        VALUES (:user_id, :token, :ip, :user_agent, :expires_at, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':token', $sessionToken, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->bindValue(':expires_at', $expiresAt, SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 11: LOG SUCCESSFUL LOGIN
    // ============================================
    
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address, created_at)
        VALUES (:user_id, 'login', 'user', :entity_id, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':entity_id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 12: RETURN SUCCESS
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
            'vip_approved' => (bool)$user['vip_approved']
        ],
        'token' => $token
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Login failed. Please try again.']);
}
```

**Verification:**
- [✅] Uses SQLite3 with bindValue() - NOT PDO!
- [✅] No syntax errors
- [✅] File uploaded

---

### **Task 3.6: Create Logout API Endpoint**
**Lines:** ~100 lines  
**File:** `/api/auth/logout.php`

```php
<?php
/**
 * User Logout API Endpoint - SQLITE3 VERSION
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get token from header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No token provided']);
        exit;
    }
    
    $token = $matches[1];
    $payload = JWT::verify($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    // Delete session (SQLite3)
    $usersDb = Database::getInstance('users');
    $stmt = $usersDb->prepare("DELETE FROM sessions WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $payload['user_id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    // Log logout
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address, created_at)
        VALUES (:user_id, 'logout', 'user', :entity_id, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $payload['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':entity_id', $payload['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Logout failed']);
}
```

---

### **Task 3.7: Create Password Reset Request**
**Lines:** ~150 lines  
**File:** `/api/auth/forgot-password.php`

```php
<?php
/**
 * Password Reset Request - SQLITE3 VERSION
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $validator = new Validator();
    $validator->email($data['email'] ?? '', 'email');
    
    if ($validator->hasErrors()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validator->getFirstError()]);
        exit;
    }
    
    $email = $validator->get('email');
    
    // Check user exists (SQLite3)
    $usersDb = Database::getInstance('users');
    $stmt = $usersDb->prepare("SELECT id, first_name FROM users WHERE email = :email AND status = 'active'");
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    // Always return success (don't reveal if email exists)
    if ($user) {
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token (SQLite3)
        $stmt = $usersDb->prepare("
            UPDATE users 
            SET password_reset_token = :token, password_reset_expires = :expires, updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->bindValue(':token', $resetToken, SQLITE3_TEXT);
        $stmt->bindValue(':expires', $expiresAt, SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        // Send email (placeholder - implement with your email system)
        $resetLink = SITE_URL . "/reset-password?token=" . $resetToken;
        
        // Log request
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, details, ip_address, created_at)
            VALUES (:user_id, 'password_reset_request', 'user', :details, :ip, datetime('now'))
        ");
        $stmt->bindValue(':user_id', $user['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':details', json_encode(['email' => $email]), SQLITE3_TEXT);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->execute();
        
        // TODO: Actually send email
        // sendEmail($email, 'Password Reset', "Click here to reset: $resetLink");
    }
    
    // Always return same response
    echo json_encode([
        'success' => true,
        'message' => 'If an account exists with that email, a reset link has been sent.'
    ]);
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Request failed']);
}
```

---

### **Task 3.8: Create Auth Middleware**
**Lines:** ~100 lines  
**File:** `/includes/AuthMiddleware.php`

```php
<?php
/**
 * Authentication Middleware - SQLITE3 VERSION
 * 
 * PURPOSE: Verify JWT tokens and load user data
 * 
 * USAGE:
 *   require_once 'includes/AuthMiddleware.php';
 *   $user = AuthMiddleware::authenticate();
 *   // $user is now available with all user data
 */

if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class AuthMiddleware {
    
    /**
     * Authenticate request and return user data
     * 
     * @param bool $required If true, fail if not authenticated
     * @return array|null User data or null
     */
    public static function authenticate($required = true) {
        // Get Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            if ($required) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            return null;
        }
        
        $token = $matches[1];
        
        // Verify JWT
        $payload = JWT::verify($token);
        
        if (!$payload) {
            if ($required) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
                exit;
            }
            return null;
        }
        
        // Get full user data (SQLite3)
        $usersDb = Database::getInstance('users');
        $stmt = $usersDb->prepare("
            SELECT id, email, first_name, last_name, tier, status, 
                   vip_approved, vip_server_id, email_verified
            FROM users 
            WHERE id = :id AND status = 'active'
        ");
        $stmt->bindValue(':id', $payload['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$user) {
            if ($required) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'User not found or inactive']);
                exit;
            }
            return null;
        }
        
        return $user;
    }
    
    /**
     * Require admin access
     */
    public static function requireAdmin() {
        $user = self::authenticate(true);
        
        if ($user['tier'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin access required']);
            exit;
        }
        
        return $user;
    }
    
    /**
     * Require VIP access
     */
    public static function requireVip() {
        $user = self::authenticate(true);
        
        if (!$user['vip_approved'] && $user['tier'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'VIP access required']);
            exit;
        }
        
        return $user;
    }
}
```

---

## DAY 3 AFTERNOON COMPLETION CHECKLIST

**Files Created (4 files):**
- [✅] /api/auth/login.php (~300 lines) - SQLite3
- [✅] /api/auth/logout.php (~100 lines) - SQLite3
- [✅] /api/auth/forgot-password.php (~150 lines) - SQLite3
- [✅] /includes/AuthMiddleware.php (~100 lines) - SQLite3

**Total Lines Part 3 Continued:** ~650 lines

---

## ✅ PART 3 COMPLETE STATUS

**Part 3 + Part 3 Continued Total:**
- Task 3.1: Database.php (180 lines) ✅ SQLite3
- Task 3.2: JWT.php (120 lines) ✅
- Task 3.3: Validator.php (180 lines) ✅
- Task 3.4: register.php (280 lines) ✅ SQLite3
- Task 3.5: login.php (300 lines) ✅ SQLite3
- Task 3.6: logout.php (100 lines) ✅ SQLite3
- Task 3.7: forgot-password.php (150 lines) ✅ SQLite3
- Task 3.8: AuthMiddleware.php (100 lines) ✅ SQLite3

**Grand Total Part 3:** ~1,410 lines  
**All using SQLite3 - NO PDO!**

---

**Status:** ✅ READY TO BUILD - SQLITE3 CORRECTED
