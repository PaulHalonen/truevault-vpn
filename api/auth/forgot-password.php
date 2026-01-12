<?php
/**
 * TrueVault VPN - Forgot Password
 * POST /api/auth/forgot-password.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/mailer.php';
require_once __DIR__ . '/../helpers/logger.php';
require_once __DIR__ . '/../helpers/auth.php';

// Only allow POST
Response::requireMethod('POST');

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'email' => 'required|email'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

$email = strtolower(trim($input['email']));

try {
    // Always return success to prevent email enumeration
    $successMessage = 'If an account exists with this email, a password reset link has been sent.';
    
    // Find user
    $user = Auth::getUserByEmail($email);
    
    if (!$user) {
        // Don't reveal that email doesn't exist
        Response::success(null, $successMessage);
    }
    
    // Generate reset token
    $resetToken = Encryption::generateToken(32);
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Save token to database
    $db = DatabaseManager::getInstance()->users();
    $stmt = $db->prepare("
        UPDATE users 
        SET password_reset_token = ?, password_reset_expires = ?, updated_at = datetime('now')
        WHERE id = ?
    ");
    $stmt->execute([$resetToken, $expires, $user['id']]);
    
    // Send reset email
    $resetLink = "https://vpn.the-truth-publishing.com/reset-password?token=$resetToken";
    
    Mailer::send(
        $email,
        'Reset Your TrueVault Password',
        "<h1>Password Reset Request</h1>
        <p>Hi {$user['first_name']},</p>
        <p>We received a request to reset your password. Click the button below to create a new password:</p>
        <p><a href='$resetLink' class='button'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you didn't request this, please ignore this email.</p>"
    );
    
    Logger::info('Password reset requested', ['email' => $email]);
    
    Response::success(null, $successMessage);
    
} catch (Exception $e) {
    Logger::error('Forgot password failed: ' . $e->getMessage());
    Response::serverError('Request failed. Please try again.');
}
