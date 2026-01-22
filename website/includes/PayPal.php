<?php
/**
 * PayPal API Helper Class - SQLITE3 VERSION
 * 
 * PURPOSE: Handle all PayPal API interactions
 * FEATURES: OAuth, subscriptions, webhook verification
 * 
 * CREDENTIALS: Loaded from database (system_settings)
 * 
 * @created January 2026
 * @version 1.0.0
 */

if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class PayPal {
    
    private static $accessToken = null;
    private static $tokenExpiry = 0;
    
    // API endpoints
    const LIVE_URL = 'https://api-m.paypal.com';
    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    
    /**
     * Get PayPal credentials from database
     */
    private static function getCredentials() {
        $adminDb = Database::getInstance('admin');
        
        $settings = [];
        $result = $adminDb->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('paypal_client_id', 'paypal_secret', 'paypal_mode')");
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return [
            'client_id' => $settings['paypal_client_id'] ?? '',
            'secret' => $settings['paypal_secret'] ?? '',
            'mode' => $settings['paypal_mode'] ?? 'sandbox'
        ];
    }
    
    /**
     * Get API base URL based on mode
     */
    private static function getBaseUrl() {
        $creds = self::getCredentials();
        return $creds['mode'] === 'live' ? self::LIVE_URL : self::SANDBOX_URL;
    }
    
    /**
     * Get OAuth access token
     */
    public static function getAccessToken() {
        // Return cached token if still valid
        if (self::$accessToken && time() < self::$tokenExpiry - 60) {
            return self::$accessToken;
        }
        
        $creds = self::getCredentials();
        
        if (empty($creds['client_id']) || empty($creds['secret'])) {
            throw new Exception('PayPal credentials not configured');
        }
        
        $url = self::getBaseUrl() . '/v1/oauth2/token';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $creds['client_id'] . ':' . $creds['secret'],
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: en_US'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            logError('PayPal OAuth failed', ['http_code' => $httpCode, 'response' => $response]);
            throw new Exception('PayPal authentication failed');
        }
        
        $data = json_decode($response, true);
        
        self::$accessToken = $data['access_token'];
        self::$tokenExpiry = time() + ($data['expires_in'] ?? 3600);
        
        return self::$accessToken;
    }
    
    /**
     * Make authenticated API request
     */
    public static function request($method, $endpoint, $data = null) {
        $token = self::getAccessToken();
        $url = self::getBaseUrl() . $endpoint;
        
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($data && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            logError('PayPal API error', [
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'response' => $result
            ]);
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $result
        ];
    }
    
    /**
     * Create subscription
     */
    public static function createSubscription($userId, $planType, $returnUrl, $cancelUrl) {
        // Get plan ID from database
        $adminDb = Database::getInstance('admin');
        $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
        $stmt->bindValue(':key', "paypal_plan_{$planType}", SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        $planId = $row['setting_value'] ?? null;
        
        if (!$planId) {
            throw new Exception("PayPal plan not configured for: {$planType}");
        }
        
        $data = [
            'plan_id' => $planId,
            'subscriber' => [
                'name' => ['given_name' => 'TrueVault', 'surname' => 'User']
            ],
            'application_context' => [
                'brand_name' => 'TrueVault VPN',
                'locale' => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl
            ],
            'custom_id' => (string)$userId
        ];
        
        $response = self::request('POST', '/v1/billing/subscriptions', $data);
        
        if ($response['success']) {
            // Store pending subscription
            $billingDb = Database::getInstance('billing');
            $stmt = $billingDb->prepare("
                INSERT INTO subscriptions (user_id, paypal_subscription_id, plan_type, status, created_at, updated_at)
                VALUES (:user_id, :paypal_id, :plan_type, 'pending', datetime('now'), datetime('now'))
            ");
            $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':paypal_id', $response['data']['id'], SQLITE3_TEXT);
            $stmt->bindValue(':plan_type', $planType, SQLITE3_TEXT);
            $stmt->execute();
            
            // Find approval URL
            $approvalUrl = null;
            foreach ($response['data']['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }
            
            return [
                'success' => true,
                'subscription_id' => $response['data']['id'],
                'approval_url' => $approvalUrl
            ];
        }
        
        return ['success' => false, 'error' => $response['data']['message'] ?? 'Subscription creation failed'];
    }
    
    /**
     * Verify webhook signature
     */
    public static function verifyWebhookSignature($headers, $body) {
        $adminDb = Database::getInstance('admin');
        $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'paypal_webhook_id'");
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $webhookId = $row['setting_value'] ?? '';
        
        if (empty($webhookId)) {
            logError('PayPal webhook ID not configured');
            return false;
        }
        
        $data = [
            'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'cert_url' => $headers['PAYPAL-CERT-URL'] ?? '',
            'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($body, true)
        ];
        
        $response = self::request('POST', '/v1/notifications/verify-webhook-signature', $data);
        
        return $response['success'] && ($response['data']['verification_status'] ?? '') === 'SUCCESS';
    }
    
    /**
     * Get subscription details
     */
    public static function getSubscription($subscriptionId) {
        return self::request('GET', "/v1/billing/subscriptions/{$subscriptionId}");
    }
    
    /**
     * Cancel subscription
     */
    public static function cancelSubscription($subscriptionId, $reason = 'Customer requested cancellation') {
        return self::request('POST', "/v1/billing/subscriptions/{$subscriptionId}/cancel", [
            'reason' => $reason
        ]);
    }
    
    /**
     * Suspend subscription
     */
    public static function suspendSubscription($subscriptionId, $reason = 'Payment failed') {
        return self::request('POST', "/v1/billing/subscriptions/{$subscriptionId}/suspend", [
            'reason' => $reason
        ]);
    }
    
    /**
     * Reactivate subscription
     */
    public static function reactivateSubscription($subscriptionId) {
        return self::request('POST', "/v1/billing/subscriptions/{$subscriptionId}/activate", [
            'reason' => 'Reactivating subscription'
        ]);
    }
}
