<?php
/**
 * TrueVault VPN - User Registration
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/mailer.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'email' => 'required|email',
    'password' => 'required|min:8',
    'first_name' => 'required|min:1|max:50',
    'last_name' => 'required|min:1|max:50'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

$email = strtolower(trim($input['email']));
$password = $input['password'];
$firstName = trim($input['first_name']);
$lastName = trim($input['last_name']);

try {
    $db = DatabaseManager::getInstance()->users();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        Response::error('Email already registered', 409);
    }
    
    // Generate UUID and hash password
    $uuid = Encryption::generateUUID();
    $passwordHash = Encryption::hashPassword($password);
    $verificationToken = Encryption::generateToken(32);
    
    // Check if this is a VIP user (seige235@yahoo.com)
    $isVip = ($email === 'seige235@yahoo.com') ? 1 : 0;
    $vipServerId = null;
    
    if ($isVip) {
        // Get VIP server ID
        $serversDb = DatabaseManager::getInstance()->servers();
        $stmt = $serversDb->prepare("SELECT id FROM vpn_servers WHERE vip_user_email = ?");
        $stmt->execute([$email]);
        $vipServer = $stmt->fetch();
        if ($vipServer) {
            $vipServerId = $vipServer['id'];
        }
    }
    
    // Determine plan type (trial for new users)
    $planType = 'trial';
    $deviceLimit = 3;
    $meshUserLimit = 0;
    
    // Insert user
    $stmt = $db->prepare("
        INSERT INTO users (uuid, email, password_hash, first_name, last_name, plan_type, status, is_vip, vip_server_id, device_limit, mesh_user_limit, email_verification_token)
        VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $uuid,
        $email,
        $passwordHash,
        $firstName,
        $lastName,
        $planType,
        $isVip,
        $vipServerId,
        $deviceLimit,
        $meshUserLimit,
        $verificationToken
    ]);
    
    $userId = $db->lastInsertId();
    
    // Generate JWT token
    $token = JWTManager::generateToken($userId, $email, false);
    $refreshToken = JWTManager::generateRefreshToken($userId);
    
    // Create session
    $sessionsDb = DatabaseManager::getInstance()->sessions();
    $stmt = $sessionsDb->prepare("
        INSERT INTO sessions (user_id, token, refresh_token, ip_address, user_agent, expires_at)
        VALUES (?, ?, ?, ?, ?, datetime('now', '+7 days'))
    ");
    $stmt->execute([
        $userId,
        $token,
        $refreshToken,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Create trial subscription
    $subsDb = DatabaseManager::getInstance()->subscriptions();
    $stmt = $subsDb->prepare("SELECT id FROM subscription_plans WHERE plan_slug = 'trial'");
    $stmt->execute();
    $trialPlan = $stmt->fetch();
    
    if ($trialPlan) {
        $stmt = $subsDb->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, billing_cycle, current_period_start, current_period_end, trial_ends_at)
            VALUES (?, ?, 'trial', 'monthly', datetime('now'), datetime('now', '+7 days'), datetime('now', '+7 days'))
        ");
        $stmt->execute([$userId, $trialPlan['id']]);
    }
    
    // Send welcome email
    Mailer::queueTemplate($email, 'welcome', [
        'first_name' => $firstName,
        'email' => $email,
        'verification_link' => "https://vpn.the-truth-publishing.com/verify?token=$verificationToken"
    ]);
    
    // Log registration
    Logger::auth('registration', $email, true);
    
    // Get user data for response
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Remove sensitive data
    unset($user['password_hash']);
    unset($user['email_verification_token']);
    unset($user['two_factor_secret']);
    
    Response::created([
        'user' => $user,
        'token' => $token,
        'refresh_token' => $refreshToken,
        'expires_in' => 60 * 60 * 24 * 7 // 7 days
    ], 'Registration successful');
    
} catch (Exception $e) {
    Logger::error('Registration failed: ' . $e->getMessage());
    Response::serverError('Registration failed. Please try again.');
}
