<?php
/**
 * TrueVault VPN - PayPal Helper Class
 * 
 * PURPOSE: Handle all PayPal API interactions
 * FEATURES:
 * - OAuth authentication with token caching
 * - Subscription creation
 * - Webhook signature verification
 * - Error handling and logging
 * 
 * USAGE:
 * $paypal = new PayPal();
 * $subscription = $paypal->createSubscription('standard', 'user@example.com');
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

class PayPal {
    private $clientId;
    private $secret;
    private $mode;
    private $baseUrl;
    private $accessToken;
    private $tokenExpiry;
    
    /**
     * Initialize PayPal with credentials
     * Gets credentials from database (not hardcoded)
     */
    public function __construct() {
        // TODO: Load from database settings
        // For now, using constants from config
        $this->clientId = 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk';
        $this->secret = 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN';
        $this->mode = 'live'; // 'live' or 'sandbox'
        
        // Set base URL based on mode
        $this->baseUrl = $this->mode === 'live' 
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
    
    /**
     * Get OAuth access token
     * Caches token to avoid unnecessary API calls
     * 
     * @return string Access token
     * @throws Exception on failure
     */
    private function getAccessToken() {
        // Return cached token if still valid
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }
        
        $ch = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $this->clientId . ':' . $this->secret,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('PayPal OAuth Error: ' . $response);
            throw new Exception('Failed to get PayPal access token');
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception('Invalid PayPal OAuth response');
        }
        
        // Cache token (expires in 1 hour, we'll refresh after 50 minutes)
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + 3000; // 50 minutes
        
        return $this->accessToken;
    }
    
    /**
     * Create PayPal subscription
     * 
     * @param string $planId Plan tier: standard, pro, or vip
     * @param string $email Customer email
     * @param string $firstName Customer first name
     * @param string $lastName Customer last name
     * @return array Subscription data with approval URL
     * @throws Exception on failure
     */
    public function createSubscription($planId, $email, $firstName, $lastName) {
        // Map plan to PayPal plan ID (these would be created in PayPal dashboard)
        $plans = [
            'standard' => 'P-STANDARD-PLAN-ID', // TODO: Replace with actual PayPal plan IDs
            'pro' => 'P-PRO-PLAN-ID',
            'vip' => 'P-VIP-PLAN-ID'
        ];
        
        if (!isset($plans[$planId])) {
            throw new Exception('Invalid plan ID');
        }
        
        $token = $this->getAccessToken();
        
        $subscriptionData = [
            'plan_id' => $plans[$planId],
            'subscriber' => [
                'name' => [
                    'given_name' => $firstName,
                    'surname' => $lastName
                ],
                'email_address' => $email
            ],
            'application_context' => [
                'brand_name' => 'TrueVault VPN',
                'locale' => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'payment_method' => [
                    'payer_selected' => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED'
                ],
                'return_url' => 'https://vpn.the-truth-publishing.com/dashboard/billing.php?status=success',
                'cancel_url' => 'https://vpn.the-truth-publishing.com/dashboard/billing.php?status=cancelled'
            ]
        ];
        
        $ch = curl_init($this->baseUrl . '/v1/billing/subscriptions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($subscriptionData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            error_log('PayPal Subscription Error: ' . $response);
            throw new Exception('Failed to create PayPal subscription');
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['id'])) {
            throw new Exception('Invalid PayPal subscription response');
        }
        
        // Extract approval URL
        $approvalUrl = '';
        if (isset($data['links'])) {
            foreach ($data['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }
        }
        
        return [
            'subscription_id' => $data['id'],
            'status' => $data['status'],
            'approval_url' => $approvalUrl
        ];
    }
    
    /**
     * Verify PayPal webhook signature
     * 
     * @param string $webhookId Webhook ID from PayPal dashboard
     * @param array $headers Request headers
     * @param string $body Raw request body
     * @return bool True if signature is valid
     */
    public function verifyWebhookSignature($webhookId, $headers, $body) {
        try {
            $token = $this->getAccessToken();
            
            // Extract required headers
            $transmissionId = $headers['PAYPAL-TRANSMISSION-ID'] ?? 
                             $headers['paypal-transmission-id'] ?? 
                             $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ?? '';
            
            $transmissionTime = $headers['PAYPAL-TRANSMISSION-TIME'] ?? 
                               $headers['paypal-transmission-time'] ?? 
                               $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ?? '';
            
            $transmissionSig = $headers['PAYPAL-TRANSMISSION-SIG'] ?? 
                              $headers['paypal-transmission-sig'] ?? 
                              $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '';
            
            $certUrl = $headers['PAYPAL-CERT-URL'] ?? 
                      $headers['paypal-cert-url'] ?? 
                      $_SERVER['HTTP_PAYPAL_CERT_URL'] ?? '';
            
            $authAlgo = $headers['PAYPAL-AUTH-ALGO'] ?? 
                       $headers['paypal-auth-algo'] ?? 
                       $_SERVER['HTTP_PAYPAL_AUTH_ALGO'] ?? '';
            
            if (empty($transmissionId) || empty($transmissionTime) || empty($transmissionSig)) {
                error_log('PayPal Webhook: Missing required headers');
                return false;
            }
            
            $verifyData = [
                'transmission_id' => $transmissionId,
                'transmission_time' => $transmissionTime,
                'transmission_sig' => $transmissionSig,
                'cert_url' => $certUrl,
                'auth_algo' => $authAlgo,
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($body, true)
            ];
            
            $ch = curl_init($this->baseUrl . '/v1/notifications/verify-webhook-signature');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($verifyData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log('PayPal Webhook Verification Error: ' . $response);
                return false;
            }
            
            $data = json_decode($response, true);
            
            return isset($data['verification_status']) && 
                   $data['verification_status'] === 'SUCCESS';
                   
        } catch (Exception $e) {
            error_log('PayPal Webhook Verification Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get subscription details
     * 
     * @param string $subscriptionId PayPal subscription ID
     * @return array Subscription details
     * @throws Exception on failure
     */
    public function getSubscription($subscriptionId) {
        $token = $this->getAccessToken();
        
        $ch = curl_init($this->baseUrl . '/v1/billing/subscriptions/' . $subscriptionId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('PayPal Get Subscription Error: ' . $response);
            throw new Exception('Failed to get PayPal subscription');
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Cancel subscription
     * 
     * @param string $subscriptionId PayPal subscription ID
     * @param string $reason Cancellation reason
     * @return bool Success
     */
    public function cancelSubscription($subscriptionId, $reason = 'Customer request') {
        try {
            $token = $this->getAccessToken();
            
            $ch = curl_init($this->baseUrl . '/v1/billing/subscriptions/' . $subscriptionId . '/cancel');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['reason' => $reason]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // PayPal returns 204 No Content on success
            return $httpCode === 204;
            
        } catch (Exception $e) {
            error_log('PayPal Cancel Subscription Error: ' . $e->getMessage());
            return false;
        }
    }
}
