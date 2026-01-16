<?php
/**
 * TrueVault VPN - PayPal Integration
 * 
 * Handles all PayPal API interactions:
 * - Create subscriptions
 * - Process webhooks
 * - Manage billing
 * 
 * @created January 2026
 * @version 1.0.0
 */

if (!defined('TRUEVAULT_INIT')) {
    die('Direct access not permitted');
}

class PayPal {
    
    private static $clientId;
    private static $secret;
    private static $baseUrl;
    private static $webhookId;
    
    // Subscription Plans - USD Pricing
    const PLANS = [
        'personal_monthly' => [
            'name' => 'Personal Monthly',
            'price_usd' => 9.97,
            'price_cad' => 13.97,
            'interval' => 'MONTH',
            'devices' => 3,
            'description' => 'VPN for 3 devices'
        ],
        'personal_annual' => [
            'name' => 'Personal Annual',
            'price_usd' => 99.97,
            'price_cad' => 139.97,
            'interval' => 'YEAR',
            'devices' => 3,
            'description' => 'VPN for 3 devices - Annual'
        ],
        'family_monthly' => [
            'name' => 'Family Monthly',
            'price_usd' => 14.97,
            'price_cad' => 20.97,
            'interval' => 'MONTH',
            'devices' => 6,
            'description' => 'VPN for 6 devices'
        ],
        'family_annual' => [
            'name' => 'Family Annual',
            'price_usd' => 140.97,
            'price_cad' => 197.97,
            'interval' => 'YEAR',
            'devices' => 6,
            'description' => 'VPN for 6 devices - Annual'
        ],
        'dedicated_monthly' => [
            'name' => 'Dedicated Server Monthly',
            'price_usd' => 39.97,
            'price_cad' => 55.97,
            'interval' => 'MONTH',
            'devices' => 999,
            'description' => 'Dedicated VPN server'
        ],
        'dedicated_annual' => [
            'name' => 'Dedicated Server Annual',
            'price_usd' => 399.97,
            'price_cad' => 559.97,
            'interval' => 'YEAR',
            'devices' => 999,
            'description' => 'Dedicated VPN server - Annual'
        ]
    ];
    
    /**
     * Initialize PayPal with credentials
     */
    public static function init() {
        self::$clientId = PAYPAL_CLIENT_ID;
        self::$secret = PAYPAL_SECRET;
        self::$webhookId = PAYPAL_WEBHOOK_ID;
        
        // Live mode
        self::$baseUrl = PAYPAL_MODE === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
    }
    
    /**
     * Get plan details
     */
    public static function getPlan($planKey) {
        return self::PLANS[$planKey] ?? null;
    }
    
    /**
     * Get all plans
     */
    public static function getAllPlans() {
        return self::PLANS;
    }
    
    /**
     * Get OAuth access token
     */
    private static function getAccessToken() {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => self::$baseUrl . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => self::$clientId . ':' . self::$secret,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('PayPal connection error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get PayPal access token');
        }
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    /**
     * Make API request to PayPal
     */
    private static function apiRequest($method, $endpoint, $data = null) {
        $token = self::getAccessToken();
        
        if (!$token) {
            throw new Exception('No PayPal access token');
        }
        
        $ch = curl_init();
        
        $options = [
            CURLOPT_URL => self::$baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'PATCH') {
            $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    /**
     * Create a PayPal order for one-time payment
     */
    public static function createOrder($amount, $currency, $description, $returnUrl, $cancelUrl, $customId = null) {
        self::init();
        
        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', '')
                    ],
                    'description' => $description
                ]
            ],
            'application_context' => [
                'brand_name' => 'TrueVault VPN',
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl
            ]
        ];
        
        if ($customId) {
            $orderData['purchase_units'][0]['custom_id'] = $customId;
        }
        
        $result = self::apiRequest('POST', '/v2/checkout/orders', $orderData);
        
        if ($result['status'] === 201) {
            return [
                'success' => true,
                'order_id' => $result['data']['id'],
                'approval_url' => self::getApprovalUrl($result['data']['links'])
            ];
        }
        
        return [
            'success' => false,
            'error' => $result['data']['message'] ?? 'Failed to create order'
        ];
    }
    
    /**
     * Capture an approved order
     */
    public static function captureOrder($orderId) {
        self::init();
        
        $result = self::apiRequest('POST', "/v2/checkout/orders/{$orderId}/capture");
        
        if ($result['status'] === 201) {
            $capture = $result['data']['purchase_units'][0]['payments']['captures'][0] ?? null;
            
            return [
                'success' => true,
                'transaction_id' => $capture['id'] ?? null,
                'status' => $result['data']['status'],
                'payer' => $result['data']['payer'] ?? null,
                'amount' => $capture['amount'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'error' => $result['data']['message'] ?? 'Failed to capture order'
        ];
    }
    
    /**
     * Create a subscription
     */
    public static function createSubscription($planId, $returnUrl, $cancelUrl, $customId = null) {
        self::init();
        
        $subscriptionData = [
            'plan_id' => $planId,
            'application_context' => [
                'brand_name' => 'TrueVault VPN',
                'locale' => 'en-US',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'payment_method' => [
                    'payer_selected' => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED'
                ],
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl
            ]
        ];
        
        if ($customId) {
            $subscriptionData['custom_id'] = $customId;
        }
        
        $result = self::apiRequest('POST', '/v1/billing/subscriptions', $subscriptionData);
        
        if ($result['status'] === 201) {
            return [
                'success' => true,
                'subscription_id' => $result['data']['id'],
                'approval_url' => self::getApprovalUrl($result['data']['links'])
            ];
        }
        
        return [
            'success' => false,
            'error' => $result['data']['message'] ?? 'Failed to create subscription'
        ];
    }
    
    /**
     * Get subscription details
     */
    public static function getSubscription($subscriptionId) {
        self::init();
        
        $result = self::apiRequest('GET', "/v1/billing/subscriptions/{$subscriptionId}");
        
        if ($result['status'] === 200) {
            return [
                'success' => true,
                'subscription' => $result['data']
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Failed to get subscription'
        ];
    }
    
    /**
     * Cancel a subscription
     */
    public static function cancelSubscription($subscriptionId, $reason = 'Customer requested cancellation') {
        self::init();
        
        $result = self::apiRequest('POST', "/v1/billing/subscriptions/{$subscriptionId}/cancel", [
            'reason' => $reason
        ]);
        
        if ($result['status'] === 204) {
            return ['success' => true];
        }
        
        return [
            'success' => false,
            'error' => $result['data']['message'] ?? 'Failed to cancel subscription'
        ];
    }
    
    /**
     * Suspend a subscription
     */
    public static function suspendSubscription($subscriptionId, $reason = 'Payment failed') {
        self::init();
        
        $result = self::apiRequest('POST', "/v1/billing/subscriptions/{$subscriptionId}/suspend", [
            'reason' => $reason
        ]);
        
        if ($result['status'] === 204) {
            return ['success' => true];
        }
        
        return [
            'success' => false,
            'error' => $result['data']['message'] ?? 'Failed to suspend subscription'
        ];
    }
    
    /**
     * Reactivate a suspended subscription
     */
    public static function activateSubscription($subscriptionId, $reason = 'Customer reactivated') {
        self::init();
        
        $result = self::apiRequest('POST', "/v1/billing/subscriptions/{$subscriptionId}/activate", [
            'reason' => $reason
        ]);
        
        if ($result['status'] === 204) {
            return ['success' => true];
        }
        
        return [
            'success' => false,
            'error' => $result['data']['message'] ?? 'Failed to activate subscription'
        ];
    }
    
    /**
     * Get approval URL from links array
     */
    private static function getApprovalUrl($links) {
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }
    
    /**
     * Verify webhook signature
     */
    public static function verifyWebhook($headers, $body) {
        self::init();
        
        $verifyData = [
            'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
            'cert_url' => $headers['PAYPAL-CERT-URL'] ?? '',
            'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'webhook_id' => self::$webhookId,
            'webhook_event' => json_decode($body, true)
        ];
        
        $result = self::apiRequest('POST', '/v1/notifications/verify-webhook-signature', $verifyData);
        
        if ($result['status'] === 200 && ($result['data']['verification_status'] ?? '') === 'SUCCESS') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Process webhook event
     */
    public static function processWebhook($eventType, $resource) {
        require_once __DIR__ . '/Database.php';
        
        $db = Database::getInstance('billing');
        $usersDb = Database::getInstance('users');
        
        switch ($eventType) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                return self::handleSubscriptionActivated($resource, $db, $usersDb);
                
            case 'BILLING.SUBSCRIPTION.CANCELLED':
                return self::handleSubscriptionCancelled($resource, $db, $usersDb);
                
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                return self::handleSubscriptionSuspended($resource, $db, $usersDb);
                
            case 'PAYMENT.SALE.COMPLETED':
                return self::handlePaymentCompleted($resource, $db);
                
            case 'PAYMENT.SALE.DENIED':
            case 'PAYMENT.SALE.REFUNDED':
                return self::handlePaymentFailed($resource, $db, $usersDb);
                
            default:
                // Log unhandled event
                return ['handled' => false, 'event' => $eventType];
        }
    }
    
    /**
     * Handle subscription activated
     */
    private static function handleSubscriptionActivated($resource, $db, $usersDb) {
        $subscriptionId = $resource['id'];
        $customId = $resource['custom_id'] ?? null; // This should be user_id
        
        if (!$customId) {
            return ['error' => 'No custom_id (user_id) in subscription'];
        }
        
        // Update subscription status
        $db->update('subscriptions', 
            ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')],
            'paypal_subscription_id = ?',
            [$subscriptionId]
        );
        
        // Update user status
        $usersDb->update('users',
            ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$customId]
        );
        
        return ['success' => true, 'action' => 'subscription_activated'];
    }
    
    /**
     * Handle subscription cancelled
     */
    private static function handleSubscriptionCancelled($resource, $db, $usersDb) {
        $subscriptionId = $resource['id'];
        
        // Get subscription to find user
        $subscription = $db->queryOne(
            "SELECT user_id FROM subscriptions WHERE paypal_subscription_id = ?",
            [$subscriptionId]
        );
        
        if ($subscription) {
            // Update subscription
            $db->update('subscriptions',
                ['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                'paypal_subscription_id = ?',
                [$subscriptionId]
            );
            
            // Update user - keep active until end of billing period
            // They can still use service until subscription_ends_at
        }
        
        return ['success' => true, 'action' => 'subscription_cancelled'];
    }
    
    /**
     * Handle subscription suspended
     */
    private static function handleSubscriptionSuspended($resource, $db, $usersDb) {
        $subscriptionId = $resource['id'];
        
        $subscription = $db->queryOne(
            "SELECT user_id FROM subscriptions WHERE paypal_subscription_id = ?",
            [$subscriptionId]
        );
        
        if ($subscription) {
            $db->update('subscriptions',
                ['status' => 'suspended', 'updated_at' => date('Y-m-d H:i:s')],
                'paypal_subscription_id = ?',
                [$subscriptionId]
            );
            
            $usersDb->update('users',
                ['status' => 'suspended', 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$subscription['user_id']]
            );
        }
        
        return ['success' => true, 'action' => 'subscription_suspended'];
    }
    
    /**
     * Handle payment completed
     */
    private static function handlePaymentCompleted($resource, $db) {
        $amount = $resource['amount']['total'] ?? 0;
        $currency = $resource['amount']['currency'] ?? 'USD';
        $transactionId = $resource['id'];
        $subscriptionId = $resource['billing_agreement_id'] ?? null;
        
        // Find subscription
        $subscription = null;
        if ($subscriptionId) {
            $subscription = $db->queryOne(
                "SELECT * FROM subscriptions WHERE paypal_subscription_id = ?",
                [$subscriptionId]
            );
        }
        
        // Record payment
        $db->insert('payments', [
            'user_id' => $subscription['user_id'] ?? null,
            'subscription_id' => $subscription['id'] ?? null,
            'paypal_transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'completed',
            'payment_method' => 'paypal',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Generate invoice
        if ($subscription) {
            self::generateInvoice($subscription['user_id'], $amount, $currency, $transactionId);
        }
        
        return ['success' => true, 'action' => 'payment_recorded'];
    }
    
    /**
     * Handle payment failed
     */
    private static function handlePaymentFailed($resource, $db, $usersDb) {
        $subscriptionId = $resource['billing_agreement_id'] ?? null;
        
        if ($subscriptionId) {
            $subscription = $db->queryOne(
                "SELECT * FROM subscriptions WHERE paypal_subscription_id = ?",
                [$subscriptionId]
            );
            
            if ($subscription) {
                // Record failed payment
                $db->insert('payments', [
                    'user_id' => $subscription['user_id'],
                    'subscription_id' => $subscription['id'],
                    'amount' => $resource['amount']['total'] ?? 0,
                    'currency' => $resource['amount']['currency'] ?? 'USD',
                    'status' => 'failed',
                    'payment_method' => 'paypal',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Update subscription failure count
                $db->query(
                    "UPDATE subscriptions SET payment_failures = payment_failures + 1, updated_at = ? WHERE id = ?",
                    [date('Y-m-d H:i:s'), $subscription['id']]
                );
            }
        }
        
        return ['success' => true, 'action' => 'payment_failed_recorded'];
    }
    
    /**
     * Generate invoice
     */
    private static function generateInvoice($userId, $amount, $currency, $transactionId) {
        $db = Database::getInstance('billing');
        
        // Generate invoice number
        $year = date('Y');
        $lastInvoice = $db->queryValue(
            "SELECT MAX(CAST(SUBSTR(invoice_number, -6) AS INTEGER)) FROM invoices WHERE invoice_number LIKE ?",
            ["TV{$year}%"]
        );
        $nextNum = ($lastInvoice ?? 0) + 1;
        $invoiceNumber = "TV{$year}" . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
        
        $db->insert('invoices', [
            'user_id' => $userId,
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'paid',
            'paypal_transaction_id' => $transactionId,
            'issued_at' => date('Y-m-d H:i:s'),
            'paid_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $invoiceNumber;
    }
}
