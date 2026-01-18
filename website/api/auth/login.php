<?php
/**
 * User Login API Endpoint
 * 
 * PURPOSE: Authenticate users and issue JWT tokens
 * METHOD: POST
 * ENDPOINT: /api/auth/login.php
 * 
 * SECURITY FEATURES:
 * - Brute force protection (5 failed attempts = 15 minute lockout)
 * - Account status verification (suspended/cancelled blocked)
 * - Password verification with bcrypt
 * - Session management
 * - Security event logging
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
    // Get and decode input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }
    
    // Validate input
    $validator = new Validator();
    $validator->email($data['email'] ?? '', 'email');
    $validator->required($data['password'] ?? '', 'password');
    
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
    
    // Get user from database
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
    
    // Check if user exists
    if (!$user) {
        // Don't reveal if email exists or not (security)
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email or password'
        ]);
        
        // Log failed attempt
        Database::execute('logs',
            "INSERT INTO security_events (event_type, severity, ip_address, user_agent, event_data) VALUES (?, ?, ?, ?, ?)",
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
    
    // Check if account is locked
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
                "UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?",
                [$user['id']]
            );
        }
    }
    
    // Check account status
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
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Wrong password - increment failed attempts
        $loginAttempts = $user['login_attempts'] + 1;
        
        // Check if we should lock the account
        if ($loginAttempts >= MAX_LOGIN_ATTEMPTS) {
            // Lock account for 15 minutes
            $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
            
            Database::execute('users',
                "UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?",
                [$loginAttempts, $lockUntil, $user['id']]
            );
            
            // Log security event
            Database::execute('logs',
                "INSERT INTO security_events (event_type, severity, user_id, ip_address, user_agent, event_data) VALUES (?, ?, ?, ?, ?, ?)",
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
                "UPDATE users SET login_attempts = ? WHERE id = ?",
                [$loginAttempts, $user['id']]
            );
            
            // Log failed attempt
            Database::execute('logs',
                "INSERT INTO security_events (event_type, severity, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
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
    
    // PASSWORD IS CORRECT - LOGIN SUCCESS
    
    // Reset login attempts and update last login
    Database::execute('users',
        "UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = datetime('now') WHERE id = ?",
        [$user['id']]
    );
    
    // Create session record
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    Database::execute('users',
        "INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)",
        [
            $user['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $expiresAt
        ]
    );
    
    // Log successful login
    Database::execute('logs',
        "INSERT INTO security_events (event_type, severity, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
        [
            'login_success',
            'low',
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]
    );
    
    // Generate JWT token
    $tokenPayload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'tier' => $user['tier'],
        'session_token' => $sessionToken
    ];
    
    $token = JWT::encode($tokenPayload);
    
    // Return success response
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
    error_log("Login error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed. Please try again.'
    ]);
}
?>
