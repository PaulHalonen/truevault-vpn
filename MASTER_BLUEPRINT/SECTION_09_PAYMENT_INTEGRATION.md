# SECTION 9: PAYMENT INTEGRATION

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Priority:** CRITICAL - Revenue System  
**Complexity:** HIGH - PayPal API Integration  

---

## 📋 TABLE OF CONTENTS

1. [Overview](#overview)
2. [PayPal Live API](#paypal-api)
3. [Subscription Flow](#subscription-flow)
4. [Webhook System](#webhooks)
5. [Payment States](#payment-states)
6. [Failed Payments](#failed-payments)
7. [Refunds](#refunds)
8. [Upgrades & Downgrades](#upgrades)
9. [Cancellations](#cancellations)
10. [Database Schema](#database)
11. [Security](#security)
12. [Testing](#testing)

---

## 💳 OVERVIEW

### **Payment System Goals**

**100% Automated:**
- ✅ No manual payment processing
- ✅ Automatic subscription renewals
- ✅ Auto-retry failed payments
- ✅ Automatic access control
- ✅ Zero human intervention needed

**PayPal Integration:**
- ✅ PayPal Live API (production)
- ✅ Subscription plans
- ✅ Webhook events
- ✅ Instant Payment Notification (IPN)
- ✅ Refund automation

### **Current Configuration**

```
PayPal Account: paulhalonen@gmail.com
App Name: MyApp_ConnectionPoint_Systems_Inc
Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Secret Key: (stored securely in database)
Mode: LIVE (production)
Webhook URL: https://vpn.the-truth-publishing.com/api/paypal-webhook.php
```

---

## 🔌 PAYPAL LIVE API

### **API Credentials**

**Stored in database (main.db):**

```sql
CREATE TABLE IF NOT EXISTS payment_settings (
    id INTEGER PRIMARY KEY,
    provider TEXT DEFAULT 'paypal',
    mode TEXT DEFAULT 'live',  -- 'live' or 'sandbox'
    client_id TEXT,
    client_secret TEXT,        -- Encrypted
    webhook_id TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO payment_settings VALUES
(1, 'paypal', 'live', 
'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk',
'ENCRYPTED_SECRET_HERE',
'46924926WL757580D',
CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
```

### **Authentication**

**Get OAuth Access Token:**

```php
<?php
function getPayPalAccessToken() {
    global $db_main;
    
    // Get credentials from database
    $stmt = $db_main->query("SELECT * FROM payment_settings WHERE provider = 'paypal'");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $clientId = $config['client_id'];
    $clientSecret = decryptSecret($config['client_secret']);
    
    $mode = $config['mode'];
    $apiUrl = ($mode === 'live') 
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';
    
    // Request access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$clientId}:{$clientSecret}");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    return $data['access_token'] ?? null;
}
```

---

## 🔄 SUBSCRIPTION FLOW

### **Complete User Journey**

```
USER CLICKS "SUBSCRIBE"
    ↓
[Select Plan: Personal, Family, Business]
    ↓
[Redirected to PayPal checkout]
    ↓
USER LOGS INTO PAYPAL
    ↓
[Authorizes subscription]
    ↓
[PayPal redirects back to TrueVault]
    ↓
[Webhook receives subscription created event]
    ↓
[System activates account]
    ↓
[User receives welcome email]
    ↓
USER CAN NOW USE VPN
```

### **Create Subscription Plan**

**One-time setup (already done):**

```php
<?php
function createPayPalPlan($planName, $price) {
    $accessToken = getPayPalAccessToken();
    $apiUrl = 'https://api-m.paypal.com';
    
    $data = [
        'product_id' => 'PROD-TRUEVAULT-VPN',
        'name' => $planName,
        'status' => 'ACTIVE',
        'billing_cycles' => [
            [
                'frequency' => [
                    'interval_unit' => 'MONTH',
                    'interval_count' => 1
                ],
                'tenure_type' => 'REGULAR',
                'sequence' => 1,
                'total_cycles' => 0,  // Infinite
                'pricing_scheme' => [
                    'fixed_price' => [
                        'value' => $price,
                        'currency_code' => 'USD'
                    ]
                ]
            ]
        ],
        'payment_preferences' => [
            'auto_bill_outstanding' => true,
            'setup_fee_failure_action' => 'CONTINUE',
            'payment_failure_threshold' => 3
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/v1/billing/plans");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer {$accessToken}"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
```

**Plans created:**
```
Personal Plan:  PLAN-PERSONAL-9.99
Family Plan:    PLAN-FAMILY-14.99
Business Plan:  PLAN-BUSINESS-29.99
```

### **Create Subscription**

**When user clicks "Subscribe":**

```php
<?php
function createSubscription($userId, $planId) {
    $accessToken = getPayPalAccessToken();
    $apiUrl = 'https://api-m.paypal.com';
    
    $user = getUser($userId);
    
    $data = [
        'plan_id' => $planId,
        'subscriber' => [
            'name' => [
                'given_name' => $user['first_name'],
                'surname' => $user['last_name']
            ],
            'email_address' => $user['email']
        ],
        'application_context' => [
            'brand_name' => 'TrueVault VPN',
            'locale' => 'en-US',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'SUBSCRIBE_NOW',
            'return_url' => 'https://vpn.the-truth-publishing.com/subscription-success.php',
            'cancel_url' => 'https://vpn.the-truth-publishing.com/subscription-cancelled.php'
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/v1/billing/subscriptions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer {$accessToken}"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    // Return approval URL for user to approve subscription
    return $result['links'][0]['href'];  // Redirect user here
}
```

---

## 🔔 WEBHOOK SYSTEM

### **Webhook URL**

```
https://vpn.the-truth-publishing.com/api/paypal-webhook.php
Webhook ID: 46924926WL757580D
Events: All Events
```

### **Webhook Events**

**PayPal sends webhooks for:**

**Subscription Events:**
- `BILLING.SUBSCRIPTION.CREATED` - New subscription created
- `BILLING.SUBSCRIPTION.ACTIVATED` - Subscription activated (first payment)
- `BILLING.SUBSCRIPTION.UPDATED` - Plan changed
- `BILLING.SUBSCRIPTION.CANCELLED` - User cancelled
- `BILLING.SUBSCRIPTION.SUSPENDED` - Payment failed (auto-suspended)
- `BILLING.SUBSCRIPTION.EXPIRED` - Subscription ended

**Payment Events:**
- `PAYMENT.SALE.COMPLETED` - Payment successful
- `PAYMENT.SALE.DENIED` - Payment failed
- `PAYMENT.SALE.REFUNDED` - Refund processed

### **Webhook Handler**

**File:** `/api/paypal-webhook.php`

```php
<?php
// ============================================
// PAYPAL WEBHOOK HANDLER
// ============================================

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/paypal.php';

// Get webhook payload
$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

// Log webhook for debugging
logWebhook($payload);

// Verify webhook signature (security)
if (!verifyWebhookSignature($payload, $_SERVER)) {
    http_response_code(401);
    die('Invalid signature');
}

// Get event type
$eventType = $event['event_type'];

// Route to appropriate handler
switch ($eventType) {
    case 'BILLING.SUBSCRIPTION.ACTIVATED':
        handleSubscriptionActivated($event);
        break;
        
    case 'PAYMENT.SALE.COMPLETED':
        handlePaymentCompleted($event);
        break;
        
    case 'PAYMENT.SALE.DENIED':
        handlePaymentFailed($event);
        break;
        
    case 'BILLING.SUBSCRIPTION.CANCELLED':
        handleSubscriptionCancelled($event);
        break;
        
    case 'BILLING.SUBSCRIPTION.SUSPENDED':
        handleSubscriptionSuspended($event);
        break;
        
    case 'PAYMENT.SALE.REFUNDED':
        handleRefund($event);
        break;
        
    default:
        // Log unknown events
        logEvent("Unknown event type: {$eventType}");
}

http_response_code(200);
echo json_encode(['status' => 'received']);

// ============================================
// EVENT HANDLERS
// ============================================

function handleSubscriptionActivated($event) {
    global $db_users, $db_payments;
    
    $subscriptionId = $event['resource']['id'];
    $email = $event['resource']['subscriber']['email_address'];
    $planId = $event['resource']['plan_id'];
    
    // Get user
    $stmt = $db_users->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        logEvent("User not found: {$email}");
        return;
    }
    
    // Determine plan from PayPal plan ID
    $planMap = [
        'PLAN-PERSONAL-9.99' => 'personal',
        'PLAN-FAMILY-14.99' => 'family',
        'PLAN-BUSINESS-29.99' => 'business'
    ];
    $plan = $planMap[$planId] ?? 'personal';
    
    // Update user subscription
    $stmt = $db_users->prepare("
        UPDATE users 
        SET plan = ?,
            status = 'active',
            subscription_id = ?,
            subscription_status = 'active',
            subscription_start = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$plan, $subscriptionId, $user['id']]);
    
    // Log subscription
    $stmt = $db_payments->prepare("
        INSERT INTO subscriptions (user_id, subscription_id, plan, status, started_at)
        VALUES (?, ?, ?, 'active', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$user['id'], $subscriptionId, $plan]);
    
    // Send welcome email
    sendWelcomeEmail($user['email'], $plan);
    
    logEvent("Subscription activated: {$email} - {$plan}");
}

function handlePaymentCompleted($event) {
    global $db_payments;
    
    $saleId = $event['resource']['id'];
    $subscriptionId = $event['resource']['billing_agreement_id'];
    $amount = $event['resource']['amount']['total'];
    $currency = $event['resource']['amount']['currency'];
    
    // Get user from subscription
    $user = getUserBySubscription($subscriptionId);
    
    if (!$user) {
        logEvent("User not found for subscription: {$subscriptionId}");
        return;
    }
    
    // Record payment
    $stmt = $db_payments->prepare("
        INSERT INTO payments (
            user_id, subscription_id, transaction_id,
            amount, currency, status, payment_date
        ) VALUES (?, ?, ?, ?, ?, 'completed', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $user['id'], $subscriptionId, $saleId,
        $amount, $currency
    ]);
    
    // Ensure account is active
    updateUserStatus($user['id'], 'active');
    
    // Send receipt
    sendPaymentReceipt($user['email'], $amount);
    
    logEvent("Payment completed: {$user['email']} - \${$amount}");
}

function handlePaymentFailed($event) {
    global $db_payments;
    
    $subscriptionId = $event['resource']['billing_agreement_id'];
    
    // Get user
    $user = getUserBySubscription($subscriptionId);
    
    if (!$user) return;
    
    // Set to grace period
    updateUserStatus($user['id'], 'grace_period');
    
    // Record failed payment
    $stmt = $db_payments->prepare("
        INSERT INTO payments (
            user_id, subscription_id, status, payment_date
        ) VALUES (?, ?, 'failed', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$user['id'], $subscriptionId]);
    
    // Send payment failed email
    sendPaymentFailedEmail($user['email']);
    
    logEvent("Payment failed: {$user['email']}");
}

function handleSubscriptionCancelled($event) {
    global $db_users;
    
    $subscriptionId = $event['resource']['id'];
    
    // Get user
    $user = getUserBySubscription($subscriptionId);
    
    if (!$user) return;
    
    // Update status
    $stmt = $db_users->prepare("
        UPDATE users 
        SET subscription_status = 'cancelled',
            subscription_end = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    
    // Send cancellation confirmation
    sendCancellationEmail($user['email']);
    
    logEvent("Subscription cancelled: {$user['email']}");
}

function handleSubscriptionSuspended($event) {
    global $db_users;
    
    $subscriptionId = $event['resource']['id'];
    
    // Get user
    $user = getUserBySubscription($subscriptionId);
    
    if (!$user) return;
    
    // Suspend account
    updateUserStatus($user['id'], 'suspended');
    
    // Send suspended notice
    sendSuspensionEmail($user['email']);
    
    logEvent("Account suspended: {$user['email']}");
}

function handleRefund($event) {
    global $db_payments;
    
    $saleId = $event['resource']['sale_id'];
    $refundAmount = $event['resource']['amount']['total'];
    
    // Update payment record
    $stmt = $db_payments->prepare("
        UPDATE payments 
        SET status = 'refunded',
            refund_amount = ?,
            refunded_at = CURRENT_TIMESTAMP
        WHERE transaction_id = ?
    ");
    $stmt->execute([$refundAmount, $saleId]);
    
    logEvent("Refund processed: {$saleId} - \${$refundAmount}");
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function verifyWebhookSignature($payload, $headers) {
    // PayPal webhook signature verification
    // See: https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
    
    $webhookId = '46924926WL757580D';
    $accessToken = getPayPalAccessToken();
    
    $verifyData = [
        'transmission_id' => $headers['HTTP_PAYPAL_TRANSMISSION_ID'],
        'transmission_time' => $headers['HTTP_PAYPAL_TRANSMISSION_TIME'],
        'cert_url' => $headers['HTTP_PAYPAL_CERT_URL'],
        'auth_algo' => $headers['HTTP_PAYPAL_AUTH_ALGO'],
        'transmission_sig' => $headers['HTTP_PAYPAL_TRANSMISSION_SIG'],
        'webhook_id' => $webhookId,
        'webhook_event' => json_decode($payload, true)
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api-m.paypal.com/v1/notifications/verify-webhook-signature');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verifyData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer {$accessToken}"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return ($result['verification_status'] === 'SUCCESS');
}

function logWebhook($payload) {
    $logFile = __DIR__ . '/../logs/webhooks.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$payload}\n\n", FILE_APPEND);
}

function logEvent($message) {
    $logFile = __DIR__ . '/../logs/payment-events.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}
```

---

## 📊 PAYMENT STATES

### **User States**

```
┌─────────────────────────────────────────────────┐
│ PAYMENT STATES                                  │
├─────────────────────────────────────────────────┤
│                                                 │
│ 1. TRIAL (if offered)                          │
│    → Free for X days                            │
│    → Full access                                │
│    → Auto-converts to paid                      │
│                                                 │
│ 2. ACTIVE                                       │
│    → Subscription active                        │
│    → Payment current                            │
│    → Full VPN access                            │
│                                                 │
│ 3. GRACE_PERIOD                                 │
│    → Payment failed (0-7 days ago)             │
│    → Still has access                           │
│    → Auto-retry payments                        │
│    → Sent reminder emails                       │
│                                                 │
│ 4. SUSPENDED                                    │
│    → Payment failed (7+ days)                   │
│    → No VPN access                              │
│    → Can reactivate by paying                   │
│                                                 │
│ 5. CANCELLED                                    │
│    → User cancelled subscription                │
│    → Access until end of billing period         │
│    → Then suspended                             │
│                                                 │
│ 6. REFUNDED                                     │
│    → Refund issued                              │
│    → Immediate suspension                       │
│    → Can re-subscribe if desired                │
│                                                 │
└─────────────────────────────────────────────────┘
```

### **State Transitions**

```
NEW USER
    ↓
[Subscribes] → ACTIVE
    ↓
[Payment successful each month] → ACTIVE (continues)
    ↓
[Payment fails] → GRACE_PERIOD (7 days)
    ↓
├─ [Payment retry succeeds] → ACTIVE
└─ [Payment retry fails] → SUSPENDED
    ↓
[User updates payment] → ACTIVE
    
OR

[User cancels] → CANCELLED
    ↓
[End of billing period] → SUSPENDED

OR

[Admin issues refund] → REFUNDED → SUSPENDED
```

---

## ❌ FAILED PAYMENTS

### **Auto-Retry Schedule**

**PayPal automatically retries:**
```
Day 0: Payment fails
    ↓
Day 1: Retry attempt 1
    ↓
Day 3: Retry attempt 2
    ↓
Day 5: Retry attempt 3
    ↓
Day 7: Final attempt
    ↓
[All failed] → Subscription suspended by PayPal
```

### **Grace Period**

**User keeps access for 7 days:**

```php
function hasAccess($userId) {
    $user = getUser($userId);
    
    // VIP always has access
    if ($user['account_type'] === 'vip') {
        return true;
    }
    
    // Active subscription
    if ($user['status'] === 'active') {
        return true;
    }
    
    // Grace period (payment failed but < 7 days ago)
    if ($user['status'] === 'grace_period') {
        $daysSinceFailed = getDaysSinceLastPayment($userId);
        if ($daysSinceFailed <= 7) {
            return true;  // Still has access
        } else {
            // Grace period expired - suspend
            updateUserStatus($userId, 'suspended');
            return false;
        }
    }
    
    // Suspended or cancelled
    return false;
}
```

### **User Communication**

**Email sequence:**

**Day 0 (Immediately):**
```
Subject: Payment Failed - Update Required

Hi John,

We couldn't process your payment for TrueVault VPN.

Amount: $9.99
Reason: Insufficient funds

Please update your payment method to continue service.

[Update Payment Method]

Your access will continue for 7 days while we retry.

Thanks,
TrueVault VPN
```

**Day 3:**
```
Subject: Urgent: Update Payment Method

Hi John,

We've tried charging your account twice but haven't been successful.

Your service will be suspended in 4 days if payment isn't received.

[Update Payment Method Now]

Need help? Reply to this email.
```

**Day 7:**
```
Subject: Final Notice - Service Will Be Suspended Today

Hi John,

This is your final notice. Your VPN service will be suspended today if we don't receive payment.

[Update Payment Method - Last Chance]

You can reactivate anytime by updating your payment method.
```

---

## 💰 REFUNDS

### **Process Refund**

```php
<?php
function processRefund($transactionId, $amount = null, $reason = '') {
    $accessToken = getPayPalAccessToken();
    $apiUrl = 'https://api-m.paypal.com';
    
    // Get original sale
    $sale = getPayment($transactionId);
    
    // Determine refund amount
    if ($amount === null) {
        $amount = $sale['amount'];  // Full refund
    }
    
    $data = [
        'amount' => [
            'total' => $amount,
            'currency' => 'USD'
        ],
        'note_to_payer' => $reason
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/v1/payments/sale/{$transactionId}/refund");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer {$accessToken}"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result['state'] === 'completed') {
        // Suspend user account
        suspendUser($sale['user_id']);
        
        // Send refund confirmation
        sendRefundEmail($sale['user_email'], $amount);
        
        return ['success' => true, 'refund_id' => $result['id']];
    } else {
        return ['success' => false, 'error' => $result['message']];
    }
}
```

---

## ⬆️ UPGRADES & DOWNGRADES

### **Change Subscription Plan**

**User upgrades from Personal to Family:**

```php
<?php
function changePlan($userId, $newPlanId) {
    $accessToken = getPayPalAccessToken();
    $apiUrl = 'https://api-m.paypal.com';
    
    $user = getUser($userId);
    $subscriptionId = $user['subscription_id'];
    
    // Update subscription plan
    $data = [
        'plan_id' => $newPlanId
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/v1/billing/subscriptions/{$subscriptionId}/revise");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer {$accessToken}"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['id'])) {
        // Update user plan in database
        $newPlan = getPlanFromId($newPlanId);
        updateUserPlan($userId, $newPlan);
        
        // Send confirmation
        sendPlanChangeEmail($user['email'], $newPlan);
        
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => $result['message']];
    }
}
```

### **Proration**

**PayPal handles proration automatically:**
- Upgrade: Immediate access, charged prorated difference
- Downgrade: Takes effect next billing cycle

---

## 🚫 CANCELLATIONS

### **User-Initiated Cancellation**

```php
<?php
function cancelSubscription($userId, $reason = '') {
    $accessToken = getPayPalAccessToken();
    $apiUrl = 'https://api-m.paypal.com';
    
    $user = getUser($userId);
    $subscriptionId = $user['subscription_id'];
    
    $data = [
        'reason' => $reason ?: 'User requested cancellation'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/v1/billing/subscriptions/{$subscriptionId}/cancel");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bearer {$accessToken}"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 204) {
        // Update user status
        updateUserStatus($userId, 'cancelled');
        
        // Send cancellation email
        sendCancellationEmail($user['email']);
        
        // Send exit survey
        sendExitSurvey($user['email']);
        
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Cancellation failed'];
    }
}
```

### **Cancellation Flow**

```
User clicks "Cancel Subscription"
    ↓
[Confirmation modal]
"Are you sure? You'll lose access after [date]"
    ↓
[User confirms]
    ↓
[API cancels PayPal subscription]
    ↓
[User keeps access until end of billing period]
    ↓
[End of period] → Account suspended
    ↓
[Exit survey email sent]
    ↓
[Win-back campaign starts (30 days later)]
```

---

## 🗄️ DATABASE SCHEMA

### **Table: subscriptions**

```sql
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- PayPal Info
    subscription_id TEXT UNIQUE NOT NULL,
    plan_id TEXT NOT NULL,
    plan TEXT NOT NULL,  -- personal, family, business
    
    -- Status
    status TEXT DEFAULT 'active',  -- active, cancelled, suspended, expired
    
    -- Dates
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    next_billing_at DATETIME,
    cancelled_at DATETIME,
    suspended_at DATETIME,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### **Table: payments**

```sql
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id TEXT,
    
    -- Payment Info
    transaction_id TEXT UNIQUE,
    amount DECIMAL(10,2),
    currency TEXT DEFAULT 'USD',
    
    -- Status
    status TEXT DEFAULT 'pending',  -- pending, completed, failed, refunded
    
    -- Refund Info
    refund_amount DECIMAL(10,2),
    refunded_at DATETIME,
    
    -- Dates
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### **Table: webhook_log**

```sql
CREATE TABLE IF NOT EXISTS webhook_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_type TEXT NOT NULL,
    event_id TEXT UNIQUE,
    payload TEXT,  -- Full JSON
    processed BOOLEAN DEFAULT 0,
    processed_at DATETIME,
    error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔒 SECURITY

### **Webhook Signature Verification**

**Always verify webhook signatures:**
- ✅ Prevents fake webhooks
- ✅ Ensures PayPal sent it
- ✅ Protects against attacks

**If signature invalid:**
```php
if (!verifyWebhookSignature($payload, $_SERVER)) {
    http_response_code(401);
    logSecurityEvent('Invalid webhook signature', $_SERVER['REMOTE_ADDR']);
    die('Unauthorized');
}
```

### **Credential Storage**

**Never hardcode credentials:**
```php
// ❌ BAD
$clientSecret = 'EIc2idTcm_YjKf4pNxXpRr...';

// ✅ GOOD
$config = getPaymentConfig();
$clientSecret = decryptSecret($config['client_secret']);
```

### **HTTPS Required**

**All PayPal communication over HTTPS:**
- ✅ Webhook URL: HTTPS only
- ✅ Return URLs: HTTPS only
- ✅ API calls: HTTPS enforced

---

## 🧪 TESTING

### **Test Mode (Sandbox)**

**Switch to sandbox for testing:**

```sql
UPDATE payment_settings 
SET mode = 'sandbox',
    client_id = 'SANDBOX_CLIENT_ID',
    client_secret = 'SANDBOX_SECRET'
WHERE provider = 'paypal';
```

**Sandbox URLs:**
- API: `https://api-m.sandbox.paypal.com`
- Checkout: `https://www.sandbox.paypal.com`

### **Test Scenarios**

**Test these flows:**
1. ✅ New subscription (personal, family, business)
2. ✅ Successful payment
3. ✅ Failed payment
4. ✅ Payment retry
5. ✅ Upgrade plan
6. ✅ Downgrade plan
7. ✅ Cancellation
8. ✅ Refund
9. ✅ Webhook delivery
10. ✅ Subscription reactivation

### **Test Cards (Sandbox)**

**PayPal Sandbox Test Accounts:**
```
Buyer Account: sb-buyer@business.example.com
Password: testpass123

Seller Account: sb-seller@business.example.com
Password: testpass123
```

---

**END OF SECTION 9: PAYMENT INTEGRATION**

**Next Section:** Section 10 (Server Management)  
**Status:** Section 9 Complete ✅  
**Lines:** ~1,400 lines  
**Created:** January 15, 2026 - 4:55 AM CST
