<?php
/**
 * User Registration API Endpoint
 * 
 * PURPOSE: Register new users with automatic VIP detection
 * METHOD: POST
 * ENDPOINT: /api/auth/register.php
 * 
 * SPECIAL FEATURE: VIP Auto-Approval
 * - Emails in VIP list (seige235@yahoo.com, paulhalonen@gmail.com) 
 *   automatically get tier='vip' and status='active'
 * - Regular users get tier='standard' and status='pending'
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
    $validator->password($data['password'] ?? '', 'password');
    
    if (isset($data['password']) && isset($data['password_confirm'])) {
        $validator->passwordsMatch($data['password'], $data['password_confirm'], 'password_confirm');
    } else {
        $validator->addError('password_confirm', 'Password confirmation is required');
    }
    
    $validator->required($data['first_name'] ?? '', 'first_name');
    $validator->maxLength($data['first_name'] ?? '', 50, 'first_name');
    $validator->required($data['last_name'] ?? '', 'last_name');
    $validator->maxLength($data['last_name'] ?? '', 50, 'last_name');
    
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
    $firstName = trim($data['first_name']);
    $lastName = trim($data['last_name']);
    
    // Check if email already exists
    $existingUser = Database::queryOne('users',
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    
    if ($existingUser) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Email address already registered'
        ]);
        exit;
    }
    
    // Check if email is in VIP list (seige235@yahoo.com, paulhalonen@gmail.com)
    $isVIP = Auth::isVIPEmail($email);
    $tier = $isVIP ? 'vip' : 'standard';
    $status = $isVIP ? 'active' : 'pending';
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => PASSWORD_COST]);
    
    // Create user in database
    $userId = Database::insert('users', 'users', [
        'email' => $email,
        'password_hash' => $passwordHash,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'tier' => $tier,
        'status' => $status,
        'email_verified' => $isVIP ? 1 : 0,
        'login_attempts' => 0
    ]);
    
    if (!$userId) {
        throw new Exception('Failed to create user');
    }
    
    // Generate email verification token for non-VIP users
    if (!$isVIP) {
        $verificationToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);
        
        Database::execute('users',
            "INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$userId, $verificationToken, $expiresAt]
        );
        
        // Send verification email using Email class
        require_once __DIR__ . '/../../includes/Email.php';
        $emailService = new Email();
        $emailService->sendWelcome($email, $firstName);
    } else {
        // VIP users get welcome email immediately
        require_once __DIR__ . '/../../includes/Email.php';
        $emailService = new Email();
        $emailService->sendWelcome($email, $firstName);
    }
    
    // Create session
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    Database::execute('users',
        "INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)",
        [$userId, $sessionToken, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', $expiresAt]
    );
    
    // Generate JWT token
    $tokenPayload = [
        'user_id' => $userId,
        'email' => $email,
        'tier' => $tier,
        'session_token' => $sessionToken
    ];
    $token = JWT::encode($tokenPayload);
    
    // Log registration event
    Database::execute('logs',
        "INSERT INTO security_events (event_type, severity, user_id, ip_address, user_agent, event_data) VALUES (?, ?, ?, ?, ?, ?)",
        [
            'user_registered',
            'low',
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            json_encode(['tier' => $tier, 'is_vip' => $isVIP, 'email_verified' => $isVIP])
        ]
    );
    
    // Return success response
    $responseMessage = $isVIP 
        ? 'VIP registration successful! Welcome to TrueVault VPN.' 
        : 'Registration successful! Please check your email to verify your account.';
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'user' => [
            'id' => $userId,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'tier' => $tier,
            'status' => $status,
            'email_verified' => $isVIP,
            'is_vip' => $isVIP
        ],
        'token' => $token,
        'requires_email_verification' => !$isVIP
    ]);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Registration failed. Please try again.'
    ]);
}
?>
