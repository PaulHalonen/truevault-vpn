# TRUEVAULT VPN - MASTER BUILD CHECKLIST (Part 3 - Continued)

**Continuation:** Day 3 Afternoon - Login, Password Reset, Sessions  
**Lines This Section:** ~650 lines  
**Time Estimate:** 3-4 hours  
**Created:** January 15, 2026 - 8:10 AM CST  

---

## DAY 3 AFTERNOON: LOGIN & SESSION MANAGEMENT

### **Task 3.5: Create Login API Endpoint**
**Lines:** ~280 lines  
**File:** `/api/auth/login.php`

- [ ] Create new file: `/api/auth/login.php`
- [ ] Add this complete code:

```php
<?php
/**
 * User Login API Endpoint
 * 
 * PURPOSE: Authenticate users and issue JWT tokens
 * METHOD: POST
 * ENDPOINT: /api/auth/login.php
 * 
 * INPUT (JSON):
 * {
 *   "email": "user@example.com",
 *   "password": "SecurePass123"
 * }
 * 
 * OUTPUT (JSON):
 * {
 *   "success": true,
 *   "message": "Login successful",
 *   "user": {
 *     "id": 1,
 *     "email": "user@example.com",
 *     "tier": "standard",
 *     "first_name": "John",
 *     "last_name": "Doe"
 *   },
 *   "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
 * }
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../../configs/config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
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
    
    // Validate email
    $validator->email($data['email'] ?? '', 'email');
    
    // Check password is provided
    $validator->required($data['password'] ?? '', 'password');
    
    // Return validation errors
    if ($validator->hasErrors()) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $validator->getErrors()
        ]);
        exit;
    }
    
    $email = strtolower(trim($data['email']));
    $password = $data['password'];
    
    // ============================================
    // STEP 3: GET USER FROM DATABASE
    // ============================================
    
    $user = Database::queryOne('users',
        "SELECT 
            id, 
            email, 
            password_hash, 
            first_name, 
            last_name, 
            tier, 
            status,
            email_verified,
            login_attempts,
            locked_until
        FROM users 
        WHERE email = ?",
        [$email]
    );
    
    // ============================================
    // STEP 4: CHECK IF USER EXISTS
    // ============================================
    
    if (!$user) {
        // Don't reveal if email exists or not (security)
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email or password'
        ]);
        
        // Log failed attempt
        Database::execute('logs',
            "INSERT INTO security_events (
                event_type, severity, ip_address, user_agent, event_data
            ) VALUES (?, ?, ?, ?, ?)",
            [
                'login_failed_unknown_email',
                'low',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                json_encode(['email' => $email])
            ]
        );
        
        exit;
    }
    
    // ============================================
    // STEP 5: CHECK IF ACCOUNT IS LOCKED
    // ============================================
    
    if ($user['locked_until']) {
        $lockedUntil = strtotime($user['locked_until']);
        
        if ($lockedUntil > time()) {
            // Account is still locked
            $minutesRemaining = ceil(($lockedUntil - time()) / 60);
            
            http_response_code(423); // 423 Locked
            echo json_encode([
                'success' => false,
                'error' => "Account locked due to too many failed login attempts. Try again in $minutesRemaining minutes."
            ]);
            exit;
        } else {
            // Lock has expired, reset attempts
            Database::execute('users',
                "UPDATE users 
                SET login_attempts = 0, locked_until = NULL 
                WHERE id = ?",
                [$user['id']]
            );
        }
    }
    
    // ============================================
    // STEP 6: CHECK ACCOUNT STATUS
    // ============================================
    
    if ($user['status'] === 'suspended') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Your account has been suspended. Please contact support.'
        ]);
        exit;
    }
    
    if ($user['status'] === 'cancelled') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Your account has been cancelled. Please contact support to reactivate.'
        ]);
        exit;
    }
    
    // ============================================
    // STEP 7: VERIFY PASSWORD
    // ============================================
    
    if (!password_verify($password, $user['password_hash'])) {
        // Wrong password - increment failed attempts
        $loginAttempts = $user['login_attempts'] + 1;
        
        // Check if we should lock the account
        if ($loginAttempts >= MAX_LOGIN_ATTEMPTS) {
            // Lock account
            $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
            
            Database::execute('users',
                "UPDATE users 
                SET login_attempts = ?, locked_until = ? 
                WHERE id = ?",
                [$loginAttempts, $lockUntil, $user['id']]
            );
            
            // Log security event
            Database::execute('logs',
                "INSERT INTO security_events (
                    event_type, severity, user_id, ip_address, user_agent, event_data
                ) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    'account_locked',
                    'high',
                    $user['id'],
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    json_encode(['reason' => 'too_many_failed_attempts'])
                ]
            );
            
            http_response_code(423);
            echo json_encode([
                'success' => false,
                'error' => 'Too many failed login attempts. Account locked for 15 minutes.'
            ]);
            exit;
        } else {
            // Just increment attempts
            Database::execute('users',
                "UPDATE users 
                SET login_attempts = ? 
                WHERE id = ?",
                [$loginAttempts, $user['id']]
            );
            
            // Log failed attempt
            Database::execute('logs',
                "INSERT INTO security_events (
                    event_type, severity, user_id, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?)",
                [
                    'login_failed_wrong_password',
                    'medium',
                    $user['id'],
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            );
            
            $attemptsRemaining = MAX_LOGIN_ATTEMPTS - $loginAttempts;
            
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => "Invalid email or password. $attemptsRemaining attempts remaining."
            ]);
            exit;
        }
    }
    
    // ============================================
    // STEP 8: PASSWORD IS CORRECT - LOGIN SUCCESS
    // ============================================
    
    // Reset login attempts
    Database::execute('users',
        "UPDATE users 
        SET login_attempts = 0, 
            locked_until = NULL, 
            last_login = datetime('now')
        WHERE id = ?",
        [$user['id']]
    );
    
    // ============================================
    // STEP 9: CREATE SESSION RECORD
    // ============================================
    
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    Database::execute('users',
        "INSERT INTO sessions (
            user_id, 
            session_token, 
            ip_address, 
            user_agent, 
            expires_at
        ) VALUES (?, ?, ?, ?, ?)",
        [
            $user['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $expiresAt
        ]
    );
    
    // ============================================
    // STEP 10: LOG SUCCESSFUL LOGIN
    // ============================================
    
    Database::execute('logs',
        "INSERT INTO security_events (
            event_type, severity, user_id, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?)",
        [
            'login_success',
            'low',
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]
    );
    
    // ============================================
    // STEP 11: GENERATE JWT TOKEN
    // ============================================
    
    $tokenPayload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'tier' => $user['tier'],
        'session_token' => $sessionToken
    ];
    
    $token = JWT::encode($tokenPayload);
    
    // ============================================
    // STEP 12: RETURN SUCCESS RESPONSE
    // ============================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'tier' => $user['tier'],
            'email_verified' => (bool)$user['email_verified']
        ],
        'token' => $token
    ]);
    
} catch (Exception $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    
    error_log("Login error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed. Please try again.'
    ]);
}
?>
```

**Verification Steps:**
- [ ] File created at /api/auth/login.php
- [ ] No syntax errors
- [ ] File uploaded to server
- [ ] Permissions set to 644

**Testing Checklist:**
- [ ] Test with correct credentials → Should get token
- [ ] Test with wrong password → Should increment attempts
- [ ] Test 5 wrong attempts → Account should lock for 15 minutes
- [ ] Test with suspended account → Should get error
- [ ] Check logs.db → security_events should log login attempts

---

### **Task 3.6: Create Logout API Endpoint**
**Lines:** ~80 lines  
**File:** `/api/auth/logout.php`

- [ ] Create new file: `/api/auth/logout.php`
- [ ] Add this complete code:

```php
<?php
/**
 * User Logout API Endpoint
 * 
 * PURPOSE: Invalidate user session and JWT token
 * METHOD: POST
 * ENDPOINT: /api/auth/logout.php
 * REQUIRES: Authorization header with Bearer token
 * 
 * OUTPUT (JSON):
 * {
 *   "success": true,
 *   "message": "Logged out successfully"
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
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // ============================================
    // STEP 1: GET TOKEN FROM HEADER
    // ============================================
    
    $token = JWT::getTokenFromHeader();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No token provided'
        ]);
        exit;
    }
    
    // ============================================
    // STEP 2: DECODE TOKEN
    // ============================================
    
    $payload = JWT::decode($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid token'
        ]);
        exit;
    }
    
    // ============================================
    // STEP 3: DELETE SESSION FROM DATABASE
    // ============================================
    
    if (isset($payload['session_token'])) {
        Database::execute('users',
            "DELETE FROM sessions WHERE session_token = ?",
            [$payload['session_token']]
        );
    }
    
    // ============================================
    // STEP 4: LOG LOGOUT EVENT
    // ============================================
    
    Database::execute('logs',
        "INSERT INTO security_events (
            event_type, severity, user_id, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?)",
        [
            'logout',
            'low',
            $payload['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]
    );
    
    // ============================================
    // STEP 5: RETURN SUCCESS
    // ============================================
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Logout failed'
    ]);
}
?>
```

**Verification Steps:**
- [ ] File created at /api/auth/logout.php
- [ ] No syntax errors
- [ ] File uploaded
- [ ] Permissions set to 644

**Testing:**
- [ ] Login first to get token
- [ ] POST to logout with Authorization: Bearer {token}
- [ ] Should return success
- [ ] Check sessions table → session should be deleted

---

### **Task 3.7: Create Password Reset Request Endpoint**
**Lines:** ~150 lines  
**File:** `/api/auth/request-reset.php`

- [ ] Create new file: `/api/auth/request-reset.php`
- [ ] Add this complete code:

```php
<?php
/**
 * Password Reset Request API Endpoint
 * 
 * PURPOSE: Generate password reset token and send email
 * METHOD: POST
 * ENDPOINT: /api/auth/request-reset.php
 * 
 * INPUT (JSON):
 * {
 *   "email": "user@example.com"
 * }
 * 
 * OUTPUT (JSON):
 * {
 *   "success": true,
 *   "message": "Password reset instructions sent to your email"
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
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // ============================================
    // STEP 1: GET INPUT
    // ============================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }
    
    // ============================================
    // STEP 2: VALIDATE EMAIL
    // ============================================
    
    $validator = new Validator();
    $validator->email($data['email'] ?? '', 'email');
    
    if ($validator->hasErrors()) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $validator->getErrors()
        ]);
        exit;
    }
    
    $email = strtolower(trim($data['email']));
    
    // ============================================
    // STEP 3: CHECK IF USER EXISTS
    // ============================================
    
    $user = Database::queryOne('users',
        "SELECT id, email, first_name FROM users WHERE email = ?",
        [$email]
    );
    
    // NOTE: Always return success even if email doesn't exist
    // This prevents email enumeration attacks
    
    if ($user) {
        // ============================================
        // STEP 4: GENERATE RESET TOKEN
        // ============================================
        
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // ============================================
        // STEP 5: DELETE OLD TOKENS
        // ============================================
        
        Database::execute('users',
            "DELETE FROM password_reset_tokens WHERE user_id = ?",
            [$user['id']]
        );
        
        // ============================================
        // STEP 6: INSERT NEW TOKEN
        // ============================================
        
        Database::execute('users',
            "INSERT INTO password_reset_tokens (
                user_id, token, expires_at
            ) VALUES (?, ?, ?)",
            [$user['id'], $token, $expiresAt]
        );
        
        // ============================================
        // STEP 7: SEND RESET EMAIL
        // ============================================
        
        $resetLink = BASE_URL . "reset-password.php?token=$token";
        
        $subject = "Password Reset Request - TrueVault VPN";
        
        $message = "Hello " . ($user['first_name'] ?: 'User') . ",\n\n";
        $message .= "You requested to reset your password for your TrueVault VPN account.\n\n";
        $message .= "Click the link below to reset your password:\n";
        $message .= "$resetLink\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you didn't request this, please ignore this email.\n\n";
        $message .= "Best regards,\n";
        $message .= "TrueVault VPN Team";
        
        $headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
        $headers .= "Reply-To: " . EMAIL_SUPPORT . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        mail($email, $subject, $message, $headers);
        
        // ============================================
        // STEP 8: LOG EVENT
        // ============================================
        
        Database::execute('logs',
            "INSERT INTO security_events (
                event_type, severity, user_id, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?)",
            [
                'password_reset_requested',
                'medium',
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );
    }
    
    // ============================================
    // STEP 9: RETURN SUCCESS (always)
    // ============================================
    
    // Always return success to prevent email enumeration
    echo json_encode([
        'success' => true,
        'message' => 'If an account exists with this email, password reset instructions have been sent.'
    ]);
    
} catch (Exception $e) {
    error_log("Password reset request error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process request'
    ]);
}
?>
```

**Verification Steps:**
- [ ] File created at /api/auth/request-reset.php
- [ ] No syntax errors
- [ ] File uploaded
- [ ] Permissions set to 644

**Testing:**
- [ ] POST with valid email
- [ ] Check password_reset_tokens table → token should be there
- [ ] Check email inbox → should receive reset email
- [ ] Token should expire after 1 hour

---

### **Task 3.8: Create Authentication Middleware**
**Lines:** ~90 lines  
**File:** `/includes/Auth.php`

- [ ] Create new file: `/includes/Auth.php`
- [ ] Add this complete code:

```php
<?php
/**
 * Authentication Middleware
 * 
 * PURPOSE: Verify JWT tokens and protect API endpoints
 * USAGE: Include at top of protected endpoints
 * 
 * @created January 2026
 * @version 1.0.0
 */

class Auth {
    
    /**
     * Require authentication
     * Validates JWT token and returns user data
     * 
     * @return array User data from token
     * @throws Exception if authentication fails
     */
    public static function require() {
        // Get token from Authorization header
        $token = JWT::getTokenFromHeader();
        
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Authentication required'
            ]);
            exit;
        }
        
        // Validate token
        try {
            $payload = JWT::validate($token);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid or expired token'
            ]);
            exit;
        }
        
        // Verify session exists in database
        if (isset($payload['session_token'])) {
            $session = Database::queryOne('users',
                "SELECT id FROM sessions 
                WHERE session_token = ? 
                AND expires_at > datetime('now')",
                [$payload['session_token']]
            );
            
            if (!$session) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Session expired'
                ]);
                exit;
            }
            
            // Update last activity
            Database::execute('users',
                "UPDATE sessions 
                SET last_activity = datetime('now') 
                WHERE session_token = ?",
                [$payload['session_token']]
            );
        }
        
        return $payload;
    }
    
    /**
     * Require specific user tier
     * 
     * @param array $allowedTiers Array of allowed tiers
     * @param array $user User data from Auth::require()
     */
    public static function requireTier($allowedTiers, $user) {
        if (!in_array($user['tier'], $allowedTiers)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Insufficient permissions'
            ]);
            exit;
        }
    }
}
?>
```

**Verification Steps:**
- [ ] File created at /includes/Auth.php
- [ ] No syntax errors
- [ ] File uploaded
- [ ] Permissions set to 644

---

**END OF DAY 3 COMPLETE!**

---

## DAY 3 COMPLETION CHECKLIST

**Before moving to Day 4, verify:**

### **Files Created (8 files):**
- [ ] /includes/Database.php (150 lines)
- [ ] /includes/JWT.php (120 lines)
- [ ] /includes/Validator.php (180 lines)
- [ ] /includes/Auth.php (90 lines)
- [ ] /api/auth/register.php (250 lines)
- [ ] /api/auth/login.php (280 lines)
- [ ] /api/auth/logout.php (80 lines)
- [ ] /api/auth/request-reset.php (150 lines)

**Total Lines Day 3:** ~1,300 lines

### **Testing Completed:**
- [ ] Registration works (test with Postman)
- [ ] Login works and returns JWT token
- [ ] Logout invalidates session
- [ ] Password reset sends email
- [ ] VIP users auto-approved on registration
- [ ] Account locks after 5 failed login attempts
- [ ] All events logged in logs.db

### **Database Verification:**
- [ ] New users appear in users.db
- [ ] Sessions created in sessions table
- [ ] Security events logged in logs.db
- [ ] VIP users have tier = 'vip'
- [ ] Password reset tokens in password_reset_tokens table

### **Security Checks:**
- [ ] JWT secret changed from default
- [ ] Passwords hashed with bcrypt
- [ ] SQL injection protected (prepared statements)
- [ ] Email enumeration prevented (always returns success)
- [ ] Rate limiting works (account lockout)
- [ ] CORS headers set properly

### **GitHub Commit:**
- [ ] Commit all Day 3 files
- [ ] Message: "Day 3 Complete - Full authentication system with JWT, login, registration, password reset"

---

## 📊 PROGRESS UPDATE

**Completed:**
- ✅ Day 1: Project setup, folders, config files
- ✅ Day 2: All 8 databases created and secured
- ✅ Day 3: Complete authentication system

**Remaining:**
- ⏳ Day 4: Device management & 2-click setup
- ⏳ Day 5: Admin panel & PayPal integration
- ⏳ Day 6: Port forwarding, camera dashboard, testing

**Lines Completed:** ~2,800 lines of code with comments
**Estimated Total:** ~5,000 lines complete checklist

---

**Status:** Day 3 Complete - Ready for Day 4  
**Next:** Device Management & 2-Click Setup System  
**Say "next" when ready to continue!** 🚀
