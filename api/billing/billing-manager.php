<?php
/**
 * TrueVault VPN - Complete Billing System
 * Handles PayPal subscriptions, payments, and access control
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

// PayPal Configuration
define('PAYPAL_CLIENT_ID', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk');
define('PAYPAL_SECRET', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN');
define('PAYPAL_MODE', 'live'); // 'sandbox' or 'live'
define('PAYPAL_API_URL', PAYPAL_MODE === 'live' 
    ? 'https://api-m.paypal.com' 
    : 'https://api-m.sandbox.paypal.com');

/**
 * Plan definitions
 */
$PLANS = [
    'basic' => [
        'name' => 'Basic',
        'price' => 9.99,
        'max_devices' => 3,
        'max_cameras' => 1,
        'camera_server' => 'ny',
        'features' => ['3 devices', '1 IP camera (NY only)', 'All shared servers', 'Network scanner']
    ],
    'family' => [
        'name' => 'Family',
        'price' => 14.99,
        'max_devices' => 5,
        'max_cameras' => 2,
        'camera_server' => 'ny',
        'features' => ['5 devices', '2 IP cameras', 'All shared servers', 'Device swapping', 'Priority support']
    ],
    'dedicated' => [
        'name' => 'Dedicated',
        'price' => 29.99,
        'max_devices' => 999,
        'max_cameras' => 12,
        'camera_server' => 'any',
        'features' => ['Unlimited devices', '12 IP cameras', 'Own dedicated server', 'Static IP', 'Port forwarding', 'Terminal access']
    ],
    'vip_upgrade' => [
        'name' => 'VIP Dedicated Upgrade',
        'price' => 9.97,
        'max_devices' => 999,
        'max_cameras' => 12,
        'camera_server' => 'any',
        'features' => ['VIP exclusive rate', 'Unlimited devices', '12 IP cameras', 'Own dedicated server']
    ]
];

class PayPalAPI {
    private static $accessToken = null;
    
    /**
     * Get PayPal access token
     */
    public static function getAccessToken() {
        if (self::$accessToken) {
            return self::$accessToken;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['access_token'])) {
            self::$accessToken = $data['access_token'];
            return self::$accessToken;
        }
        
        return null;
    }
    
    /**
     * Make PayPal API request
     */
    public static function request($method, $endpoint, $data = null) {
        $token = self::getAccessToken();
        if (!$token) {
            return ['error' => 'Failed to get PayPal access token'];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    /**
     * Create a subscription
     */
    public static function createSubscription($planId, $userId, $email) {
        $planDetails = $GLOBALS['PLANS'][$planId] ?? null;
        if (!$planDetails) {
            return ['error' => 'Invalid plan'];
        }
        
        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => "truevault_{$userId}_{$planId}_" . time(),
                'description' => "TrueVault VPN - {$planDetails['name']} Plan",
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($planDetails['price'], 2, '.', '')
                ],
                'custom_id' => json_encode([
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'email' => $email
                ])
            ]],
            'application_context' => [
                'brand_name' => 'TrueVault VPN',
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW',
                'return_url' => 'https://vpn.the-truth-publishing.com/payment-success.html',
                'cancel_url' => 'https://vpn.the-truth-publishing.com/payment-cancel.html'
            ]
        ];
        
        return self::request('POST', '/v2/checkout/orders', $data);
    }
    
    /**
     * Capture payment after approval
     */
    public static function capturePayment($orderId) {
        return self::request('POST', "/v2/checkout/orders/{$orderId}/capture");
    }
}

class BillingManager {
    
    /**
     * Create checkout session for a plan
     */
    public static function createCheckout($userId, $planId) {
        $user = Auth::getUserById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Check if VIP - they bypass payment
        if (VIPManager::isVIP($user['email'])) {
            $vipDetails = VIPManager::getVIPDetails($user['email']);
            
            // VIP basic users can still upgrade to dedicated
            if ($planId !== 'vip_upgrade' && $vipDetails['tier'] === 'vip_basic') {
                // Auto-activate VIP basic subscription
                self::activateSubscription($userId, 'vip_basic', null);
                return [
                    'success' => true,
                    'vip' => true,
                    'message' => 'VIP access activated - no payment required'
                ];
            }
        }
        
        $result = PayPalAPI::createSubscription($planId, $userId, $user['email']);
        
        if (isset($result['error'])) {
            return ['success' => false, 'error' => $result['error']];
        }
        
        if ($result['status'] === 201 && isset($result['data']['id'])) {
            // Store pending order
            Database::execute('billing',
                "INSERT INTO pending_orders (user_id, order_id, plan_id, amount, status, created_at)
                 VALUES (?, ?, ?, ?, 'pending', datetime('now'))",
                [$userId, $result['data']['id'], $planId, $GLOBALS['PLANS'][$planId]['price']]
            );
            
            // Find approval URL
            $approvalUrl = null;
            foreach ($result['data']['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }
            
            return [
                'success' => true,
                'order_id' => $result['data']['id'],
                'approval_url' => $approvalUrl
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to create checkout'];
    }
    
    /**
     * Complete payment after user approves
     */
    public static function completePayment($orderId) {
        // Get pending order
        $order = Database::queryOne('billing',
            "SELECT * FROM pending_orders WHERE order_id = ? AND status = 'pending'",
            [$orderId]
        );
        
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }
        
        // Capture payment
        $result = PayPalAPI::capturePayment($orderId);
        
        if ($result['status'] === 201 && $result['data']['status'] === 'COMPLETED') {
            // Update order status
            Database::execute('billing',
                "UPDATE pending_orders SET status = 'completed', completed_at = datetime('now') WHERE order_id = ?",
                [$orderId]
            );
            
            // Create invoice
            $invoiceId = self::createInvoice($order['user_id'], $order['plan_id'], $order['amount'], $orderId);
            
            // Activate subscription
            self::activateSubscription($order['user_id'], $order['plan_id'], $orderId);
            
            // Provision VPN access
            PeerManager::provisionUser($order['user_id']);
            
            return [
                'success' => true,
                'message' => 'Payment completed',
                'invoice_id' => $invoiceId
            ];
        }
        
        // Mark as failed
        Database::execute('billing',
            "UPDATE pending_orders SET status = 'failed' WHERE order_id = ?",
            [$orderId]
        );
        
        return ['success' => false, 'error' => 'Payment capture failed'];
    }
    
    /**
     * Activate subscription
     */
    public static function activateSubscription($userId, $planId, $paymentId) {
        $plan = $GLOBALS['PLANS'][$planId] ?? $GLOBALS['PLANS']['basic'];
        
        // Deactivate existing subscription
        Database::execute('billing',
            "UPDATE subscriptions SET status = 'superseded' WHERE user_id = ? AND status = 'active'",
            [$userId]
        );
        
        // Calculate end date (1 month from now)
        $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        // Create new subscription
        Database::execute('billing',
            "INSERT INTO subscriptions (user_id, plan_type, status, payment_id, max_devices, max_cameras, start_date, end_date, created_at)
             VALUES (?, ?, 'active', ?, ?, ?, datetime('now'), ?, datetime('now'))",
            [$userId, $planId, $paymentId, $plan['max_devices'], $plan['max_cameras'], $endDate]
        );
        
        // Update user's plan
        Database::execute('users',
            "UPDATE users SET plan_type = ?, status = 'active' WHERE id = ?",
            [$planId, $userId]
        );
        
        return true;
    }
    
    /**
     * Cancel subscription
     */
    public static function cancelSubscription($userId, $reason = null) {
        // Get active subscription
        $sub = Database::queryOne('billing',
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'",
            [$userId]
        );
        
        if (!$sub) {
            return ['success' => false, 'error' => 'No active subscription'];
        }
        
        // Mark as cancelled (will remain active until end_date)
        Database::execute('billing',
            "UPDATE subscriptions SET status = 'cancelled', cancelled_at = datetime('now'), cancel_reason = ? WHERE id = ?",
            [$reason, $sub['id']]
        );
        
        // Schedule peer removal for end_date
        self::scheduleAccessRevocation($userId, $sub['end_date']);
        
        return ['success' => true, 'message' => 'Subscription cancelled. Access continues until ' . $sub['end_date']];
    }
    
    /**
     * Handle payment failure
     */
    public static function handlePaymentFailure($userId) {
        // Get user
        $user = Auth::getUserById($userId);
        if (!$user) return;
        
        // Check if VIP - they never lose access
        if (VIPManager::isVIP($user['email'])) {
            return; // VIPs keep access regardless
        }
        
        // Update subscription status
        Database::execute('billing',
            "UPDATE subscriptions SET status = 'payment_failed', updated_at = datetime('now') WHERE user_id = ? AND status = 'active'",
            [$userId]
        );
        
        // Grace period - 7 days
        $graceEndDate = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        // Schedule access revocation
        self::scheduleAccessRevocation($userId, $graceEndDate);
        
        // Log
        Database::execute('billing',
            "INSERT INTO payment_failures (user_id, failure_date, grace_end_date, notified)
             VALUES (?, datetime('now'), ?, 0)",
            [$userId, $graceEndDate]
        );
        
        // TODO: Send email notification
    }
    
    /**
     * Schedule access revocation
     */
    public static function scheduleAccessRevocation($userId, $revokeDate) {
        Database::execute('billing',
            "INSERT OR REPLACE INTO scheduled_revocations (user_id, revoke_at, status)
             VALUES (?, ?, 'pending')",
            [$userId, $revokeDate]
        );
    }
    
    /**
     * Process scheduled revocations (run via cron)
     */
    public static function processRevocations() {
        $pending = Database::queryAll('billing',
            "SELECT * FROM scheduled_revocations WHERE revoke_at <= datetime('now') AND status = 'pending'"
        );
        
        foreach ($pending as $rev) {
            // Check if user has paid since
            $sub = Database::queryOne('billing',
                "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'",
                [$rev['user_id']]
            );
            
            if ($sub) {
                // User has active subscription, cancel revocation
                Database::execute('billing',
                    "UPDATE scheduled_revocations SET status = 'cancelled' WHERE id = ?",
                    [$rev['id']]
                );
                continue;
            }
            
            // Revoke access - remove all peers for this user
            PeerManager::revokeAllAccess($rev['user_id']);
            
            // Update user status
            Database::execute('users',
                "UPDATE users SET status = 'suspended' WHERE id = ?",
                [$rev['user_id']]
            );
            
            // Mark revocation as completed
            Database::execute('billing',
                "UPDATE scheduled_revocations SET status = 'completed', completed_at = datetime('now') WHERE id = ?",
                [$rev['id']]
            );
        }
        
        return count($pending);
    }
    
    /**
     * Create invoice
     */
    public static function createInvoice($userId, $planId, $amount, $paymentId) {
        $invoiceNumber = 'TV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        Database::execute('billing',
            "INSERT INTO invoices (user_id, invoice_number, plan_id, amount, payment_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'paid', datetime('now'))",
            [$userId, $invoiceNumber, $planId, $amount, $paymentId]
        );
        
        return $invoiceNumber;
    }
    
    /**
     * Get user's billing history
     */
    public static function getBillingHistory($userId) {
        return Database::queryAll('billing',
            "SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }
    
    /**
     * Get current subscription
     */
    public static function getCurrentSubscription($userId) {
        $user = Auth::getUserById($userId);
        
        // Check VIP first
        if ($user && VIPManager::isVIP($user['email'])) {
            $vipDetails = VIPManager::getVIPDetails($user['email']);
            return [
                'plan_type' => $vipDetails['tier'],
                'status' => 'active',
                'is_vip' => true,
                'vip_badge' => $vipDetails['tier'] === 'vip_dedicated' ? '👑 VIP Dedicated' : '⭐ VIP',
                'max_devices' => $vipDetails['max_devices'],
                'max_cameras' => $vipDetails['max_cameras'],
                'end_date' => null, // Never expires
                'bypass_payment' => true
            ];
        }
        
        return Database::queryOne('billing',
            "SELECT * FROM subscriptions WHERE user_id = ? AND status IN ('active', 'cancelled') ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
    }
}

class PeerManager {
    
    // Server API endpoints
    private static $servers = [
        1 => ['name' => 'NY', 'ip' => '66.94.103.91', 'api_port' => 8080, 'network' => '10.0.0'],
        2 => ['name' => 'STL', 'ip' => '144.126.133.253', 'api_port' => 8080, 'network' => '10.0.1', 'vip_only' => 'seige235@yahoo.com'],
        3 => ['name' => 'TX', 'ip' => '66.241.124.4', 'api_port' => 8080, 'network' => '10.10.1'],
        4 => ['name' => 'CAN', 'ip' => '66.241.125.247', 'api_port' => 8080, 'network' => '10.10.0']
    ];
    
    private static $apiSecret = 'TrueVault2026SecretKey';
    
    /**
     * Make API request to a VPN server
     */
    private static function serverRequest($serverId, $method, $endpoint, $data = null) {
        $server = self::$servers[$serverId] ?? null;
        if (!$server) {
            return ['success' => false, 'error' => 'Unknown server'];
        }
        
        $url = "http://{$server['ip']}:{$server['api_port']}{$endpoint}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . self::$apiSecret
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 0) {
            return ['success' => false, 'error' => 'Server unreachable'];
        }
        
        return json_decode($response, true) ?: ['success' => false, 'error' => 'Invalid response'];
    }
    
    /**
     * Provision VPN access for a user
     */
    public static function provisionUser($userId) {
        $user = Auth::getUserById($userId);
        if (!$user) return false;
        
        // Generate WireGuard keypair if not exists
        $userKey = self::getOrCreateUserKey($userId);
        
        // Determine which servers user can access
        $accessibleServers = self::getAccessibleServers($user['email']);
        
        // Add peer to each accessible server
        foreach ($accessibleServers as $serverId => $server) {
            $result = self::serverRequest($serverId, 'POST', '/peers/add', [
                'public_key' => $userKey['public_key'],
                'user_id' => $userId
            ]);
            
            if ($result['success']) {
                // Store peer record
                Database::execute('vpn',
                    "INSERT OR REPLACE INTO user_peers (user_id, server_id, public_key, assigned_ip, status, created_at)
                     VALUES (?, ?, ?, ?, 'active', datetime('now'))",
                    [$userId, $serverId, $userKey['public_key'], $result['allowed_ip']]
                );
            }
        }
        
        return true;
    }
    
    /**
     * Revoke all VPN access for a user
     */
    public static function revokeAllAccess($userId) {
        // Get user's public key
        $userKey = Database::queryOne('certificates',
            "SELECT public_key FROM user_certificates WHERE user_id = ? AND type = 'wireguard' AND status = 'active'",
            [$userId]
        );
        
        if (!$userKey) return;
        
        // Remove from all servers
        foreach (self::$servers as $serverId => $server) {
            self::serverRequest($serverId, 'POST', '/peers/remove', [
                'public_key' => $userKey['public_key']
            ]);
        }
        
        // Update peer records
        Database::execute('vpn',
            "UPDATE user_peers SET status = 'revoked', revoked_at = datetime('now') WHERE user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Get or create user's WireGuard key
     */
    public static function getOrCreateUserKey($userId) {
        $existing = Database::queryOne('certificates',
            "SELECT * FROM user_certificates WHERE user_id = ? AND type = 'wireguard' AND status = 'active'",
            [$userId]
        );
        
        if ($existing) {
            return $existing;
        }
        
        // Generate new keypair
        $privateKey = self::generatePrivateKey();
        $publicKey = self::generatePublicKey($privateKey);
        
        Database::execute('certificates',
            "INSERT INTO user_certificates (user_id, name, type, public_key, private_key, status, created_at)
             VALUES (?, 'WireGuard Key', 'wireguard', ?, ?, 'active', datetime('now'))",
            [$userId, $publicKey, $privateKey]
        );
        
        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }
    
    /**
     * Get servers accessible by user
     */
    public static function getAccessibleServers($email) {
        $accessible = [];
        
        foreach (self::$servers as $serverId => $server) {
            // Check VIP-only servers
            if (isset($server['vip_only'])) {
                if (strtolower($email) === strtolower($server['vip_only'])) {
                    $accessible[$serverId] = $server;
                }
                continue;
            }
            
            $accessible[$serverId] = $server;
        }
        
        return $accessible;
    }
    
    /**
     * Generate WireGuard private key
     */
    private static function generatePrivateKey() {
        $bytes = random_bytes(32);
        $bytes[0] = chr(ord($bytes[0]) & 248);
        $bytes[31] = chr((ord($bytes[31]) & 127) | 64);
        return base64_encode($bytes);
    }
    
    /**
     * Generate WireGuard public key from private key
     */
    private static function generatePublicKey($privateKey) {
        // Use sodium if available
        if (function_exists('sodium_crypto_scalarmult_base')) {
            $privateBytes = base64_decode($privateKey);
            $publicBytes = sodium_crypto_scalarmult_base($privateBytes);
            return base64_encode($publicBytes);
        }
        
        // Fallback - in production, use proper Curve25519
        $hash = hash('sha256', base64_decode($privateKey), true);
        return base64_encode($hash);
    }
}
