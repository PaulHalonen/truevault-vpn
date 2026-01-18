<?php
/**
 * Password Reset Request API Endpoint
 * 
 * PURPOSE: Generate password reset token and send email
 * METHOD: POST
 * ENDPOINT: /api/auth/request-reset.php
 * 
 * SECURITY: Always returns success to prevent email enumeration
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
    // Get input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }
    
    // Validate email
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
    
    // Check if user exists
    $user = Database::queryOne('users',
        "SELECT id, email, first_name FROM users WHERE email = ?",
        [$email]
    );
    
    // NOTE: Always return success even if email doesn't exist
    // This prevents email enumeration attacks
    
    if ($user) {
        // Generate reset token (64 characters, 1 hour expiration)
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Delete old tokens for this user
        Database::execute('users',
            "DELETE FROM password_reset_tokens WHERE user_id = ?",
            [$user['id']]
        );
        
        // Insert new token
        Database::execute('users',
            "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$user['id'], $token, $expiresAt]
        );
        
        // Send reset email
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
        
        // Log event
        Database::execute('logs',
            "INSERT INTO security_events (event_type, severity, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [
                'password_reset_requested',
                'medium',
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );
    }
    
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
