<?php
/**
 * Get Current User API Endpoint - SQLITE3 VERSION
 * 
 * PURPOSE: Return authenticated user's profile data
 * METHOD: GET
 * ENDPOINT: /api/auth/me.php
 * REQUIRES: Bearer token in Authorization header
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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use GET.']);
    exit;
}

try {
    // ============================================
    // STEP 1: REQUIRE AUTHENTICATION
    // ============================================
    
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    
    // ============================================
    // STEP 2: GET USER DATA (SQLite3)
    // ============================================
    
    $usersDb = Database::getInstance('users');
    
    $stmt = $usersDb->prepare("
        SELECT id, email, first_name, last_name, tier, status, 
               email_verified, vip_approved, created_at, last_login
        FROM users 
        WHERE id = :id
    ");
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // ============================================
    // STEP 3: GET DEVICE COUNT
    // ============================================
    
    $devicesDb = Database::getInstance('devices');
    
    $stmt = $devicesDb->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $deviceCount = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    // Get max devices for tier
    $maxDevices = [
        'standard' => 3,
        'pro' => 5,
        'vip' => 999,
        'admin' => 999
    ];
    
    // ============================================
    // STEP 4: GET SUBSCRIPTION STATUS
    // ============================================
    
    $billingDb = Database::getInstance('billing');
    
    $stmt = $billingDb->prepare("
        SELECT plan_id, status, next_billing_date, expires_at
        FROM subscriptions 
        WHERE user_id = :user_id
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $subscription = $result->fetchArray(SQLITE3_ASSOC);
    
    // ============================================
    // STEP 5: RETURN USER DATA
    // ============================================
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'tier' => $user['tier'],
            'status' => $user['status'],
            'email_verified' => (bool)$user['email_verified'],
            'vip_approved' => (bool)$user['vip_approved'],
            'created_at' => $user['created_at'],
            'last_login' => $user['last_login']
        ],
        'devices' => [
            'count' => (int)$deviceCount,
            'max' => $maxDevices[$user['tier']] ?? 3
        ],
        'subscription' => $subscription ? [
            'plan' => $subscription['plan_id'],
            'status' => $subscription['status'],
            'next_billing' => $subscription['next_billing_date'],
            'expires' => $subscription['expires_at']
        ] : null
    ]);
    
} catch (Exception $e) {
    logError('Get user failed: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve user data'
    ]);
}
