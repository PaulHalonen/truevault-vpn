# TrueVault VPN - Detailed Automation Workflows
## Complete Implementation Guide
**Created:** January 13, 2026 - 11:45 PM CST

---

# WORKFLOW 1: 7-Day Free Trial Signup

## Database Schema Addition

```sql
-- Add to subscriptions table
ALTER TABLE subscriptions ADD COLUMN trial_started DATE;
ALTER TABLE subscriptions ADD COLUMN trial_ends DATE;
ALTER TABLE subscriptions ADD COLUMN trial_converted INTEGER DEFAULT 0;

-- Trial tracking table
CREATE TABLE IF NOT EXISTS trial_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    trial_started DATE NOT NULL,
    trial_ends DATE NOT NULL,
    reminder_day1_sent INTEGER DEFAULT 0,
    reminder_day5_sent INTEGER DEFAULT 0,
    reminder_day6_sent INTEGER DEFAULT 0,
    converted INTEGER DEFAULT 0,
    converted_at DATETIME,
    expired INTEGER DEFAULT 0,
    expired_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## API: /api/auth/register.php (Trial Version)

```php
<?php
/**
 * TrueVault VPN - Registration with Free Trial
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../automation/engine.php';

Response::requireMethod('POST');
$input = Response::getJsonInput();

// Validate input
$errors = [];
if (empty($input['email'])) $errors[] = 'Email is required';
if (empty($input['password'])) $errors[] = 'Password is required';
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
if (strlen($input['password']) < 8) $errors[] = 'Password must be at least 8 characters';

if (!empty($errors)) {
    Response::error($errors, 400);
}

$email = strtolower(trim($input['email']));
$password = $input['password'];
$firstName = $input['first_name'] ?? '';
$lastName = $input['last_name'] ?? '';

// Check if email already exists
$existing = Database::queryOne('users', "SELECT id FROM users WHERE email = ?", [$email]);
if ($existing) {
    Response::error('Email already registered', 409);
}

// Check if VIP user
$isVIP = VIPManager::isVIP($email);
$vipDetails = $isVIP ? VIPManager::getVIPDetails($email) : null;

// Generate UUID
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

// Hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Determine plan type
$planType = $isVIP ? 'business' : 'trial';
$status = $isVIP ? 'active' : 'trial';

try {
    Database::beginTransaction('users');
    
    // Create user
    Database::execute('users',
        "INSERT INTO users (uuid, email, password_hash, first_name, last_name, status, plan_type, is_vip, created_at) 
         VALUES (?, ?, ?, ?, ?, 'active', ?, ?, datetime('now'))",
        [$uuid, $email, $passwordHash, $firstName, $lastName, $planType, $isVIP ? 1 : 0]
    );
    
    $userId = Database::lastInsertId('users');
    
    // Create subscription
    $trialEnds = date('Y-m-d', strtotime('+7 days'));
    
    if ($isVIP) {
        // VIP gets lifetime subscription
        Database::execute('billing',
            "INSERT INTO subscriptions (user_id, plan_type, status, current_period_start, current_period_end, created_at)
             VALUES (?, 'business', 'active', datetime('now'), datetime('now', '+100 years'), datetime('now'))",
            [$userId]
        );
    } else {
        // Regular user gets 7-day trial
        Database::execute('billing',
            "INSERT INTO subscriptions (user_id, plan_type, status, trial_started, trial_ends, current_period_start, current_period_end, created_at)
             VALUES (?, 'trial', 'trial', date('now'), ?, datetime('now'), datetime('now', '+7 days'), datetime('now'))",
            [$userId, $trialEnds]
        );
        
        // Create trial tracking record
        Database::execute('billing',
            "INSERT INTO trial_tracking (user_id, trial_started, trial_ends, created_at)
             VALUES (?, date('now'), ?, datetime('now'))",
            [$userId, $trialEnds]
        );
    }
    
    Database::commit('users');
    
    // Trigger automation workflow
    $workflowContext = [
        'user_id' => $userId,
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'is_vip' => $isVIP,
        'plan_type' => $planType,
        'trial_ends' => $trialEnds ?? null,
        'user' => [
            'id' => $userId,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName
        ]
    ];
    
    if ($isVIP) {
        AutomationEngine::trigger('vip_signup', $workflowContext);
    } else {
        AutomationEngine::trigger('trial_signup', $workflowContext);
    }
    
    // Generate JWT token
    require_once __DIR__ . '/../config/jwt.php';
    $token = JWTManager::generateToken($userId, $email, false);
    $refreshToken = JWTManager::generateRefreshToken($userId);
    
    Response::success([
        'token' => $token,
        'refresh_token' => $refreshToken,
        'user' => [
            'id' => $userId,
            'uuid' => $uuid,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'plan_type' => $planType,
            'is_vip' => $isVIP,
            'trial_ends' => $isVIP ? null : $trialEnds,
            'vip_badge' => $isVIP ? ($vipDetails['tier'] === 'vip_dedicated' ? '👑 VIP Dedicated' : '⭐ VIP') : null
        ]
    ], 'Registration successful! ' . ($isVIP ? 'Welcome VIP!' : 'Your 7-day free trial has started.'));
    
} catch (Exception $e) {
    Database::rollback('users');
    Response::serverError('Registration failed: ' . $e->getMessage());
}
```

## Automation Workflow: trial_signup

```php
<?php
// Add to AutomationEngine::$workflows

'trial_signup' => [
    'name' => '7-Day Free Trial Signup',
    'steps' => [
        // Immediate: Welcome email
        [
            'action' => 'send_email',
            'template' => 'welcome_trial',
            'delay' => 0
        ],
        // Immediate: Create regional identities
        [
            'action' => 'create_default_identities',
            'count' => 1, // Trial gets 1 identity
            'delay' => 0
        ],
        // Immediate: Generate scanner token
        [
            'action' => 'generate_scanner_token',
            'delay' => 0
        ],
        // +1 hour: Getting started email
        [
            'action' => 'send_email',
            'template' => 'trial_getting_started',
            'delay' => 3600
        ],
        // +24 hours: Day 1 tips
        [
            'action' => 'send_email',
            'template' => 'trial_day1_tips',
            'delay' => 86400
        ],
        // +5 days: Trial ending soon (2 days left)
        [
            'action' => 'send_email',
            'template' => 'trial_ending_soon',
            'delay' => 432000
        ],
        // +6 days: Last day warning
        [
            'action' => 'send_email',
            'template' => 'trial_last_day',
            'delay' => 518400
        ],
        // +7 days: Check if converted, else expire
        [
            'action' => 'check_trial_conversion',
            'delay' => 604800
        ]
    ]
],
```

## Email Templates

```php
<?php
// Email templates for trial workflow

$emailTemplates = [
    'welcome_trial' => [
        'subject' => '🎉 Welcome to TrueVault VPN - Your 7-Day Trial Starts Now!',
        'body' => "
Hi {first_name},

Welcome to TrueVault VPN! Your 7-day free trial is now active.

🔐 What You Get During Your Trial:
• Connect 1 device to our secure VPN
• Access to all shared servers
• 256-bit military-grade encryption
• Zero-log privacy policy

📱 Get Started in 3 Easy Steps:
1. Download our app or use WireGuard
2. Import your connection config (in your dashboard)
3. Connect and browse securely!

👉 Go to your dashboard: {dashboard_url}

Your trial ends on: {trial_ends}

Questions? Just reply to this email!

Welcome aboard,
The TrueVault Team

P.S. Love it? Upgrade anytime to unlock unlimited devices, mesh networking, and more!
"
    ],
    
    'trial_getting_started' => [
        'subject' => '📱 Set Up Your First Device - TrueVault VPN',
        'body' => "
Hi {first_name},

Ready to secure your first device? Here's how:

🖥️ FOR COMPUTER:
1. Download WireGuard: https://wireguard.com/install/
2. Go to your dashboard: {dashboard_url}
3. Click 'Connect' and download your config
4. Import the config into WireGuard
5. Click 'Activate' - You're protected!

📱 FOR PHONE/TABLET:
1. Install WireGuard from App Store or Google Play
2. Open TrueVault dashboard on your phone
3. Tap 'Connect' to download config
4. Open the config file - WireGuard will import it
5. Toggle ON - You're secure!

Need the Network Scanner?
Discover all devices on your network (cameras, printers, etc.)
Download: {dashboard_url}scanner

Questions? Just reply!

- TrueVault Team
"
    ],
    
    'trial_ending_soon' => [
        'subject' => '⏰ 2 Days Left on Your TrueVault Trial',
        'body' => "
Hi {first_name},

Your free trial ends in 2 days ({trial_ends}).

Don't lose access to:
✓ Secure VPN connection
✓ Your saved settings
✓ Your discovered devices

🚀 UPGRADE NOW and get:
• Unlimited devices (trial: 1 device)
• All regional identities
• Mesh networking with family
• Priority support
• Personal certificate authority

👉 Upgrade here: {dashboard_url}billing

Use code TRIAL20 for 20% off your first month!

Questions? Just reply!

- TrueVault Team
"
    ],
    
    'trial_last_day' => [
        'subject' => '🚨 FINAL DAY: Your TrueVault Trial Expires Tomorrow',
        'body' => "
Hi {first_name},

This is it - your trial ends TOMORROW ({trial_ends}).

After that:
❌ VPN connection will stop working
❌ Dashboard access suspended
❌ Device configs deactivated

But there's still time! Upgrade now to keep everything:

👉 {dashboard_url}billing

Personal Plan: $9.99/month
• 3 devices
• 3 regional identities
• Smart routing

Family Plan: $14.99/month (MOST POPULAR)
• Unlimited devices
• All regions
• Mesh networking (6 users)

Business Plan: $29.99/month
• Everything unlimited
• Admin dashboard
• API access

🎁 Special offer: Reply 'SAVE' and I'll extend your trial 3 more days!

- TrueVault Team
"
    ],
    
    'trial_expired' => [
        'subject' => 'Your TrueVault Trial Has Ended 😢',
        'body' => "
Hi {first_name},

Your 7-day trial has ended and your account is now suspended.

But don't worry - your settings and discovered devices are saved for 30 days!

Reactivate anytime: {dashboard_url}billing

We'd love to have you back. Here's a special comeback offer:

🎁 Use code COMEBACK30 for 30% off your first month!

Miss us already? Just reply and let's chat.

- TrueVault Team
"
    ]
];
```

## Trial Device Limit Check

```php
<?php
// In /api/vpn/connect.php - Add trial limit checking

function checkTrialLimits($userId, $email) {
    // VIP bypasses all limits
    if (VIPManager::isVIP($email)) {
        return ['allowed' => true, 'is_vip' => true];
    }
    
    // Get user's subscription
    $sub = Database::queryOne('billing',
        "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
        [$userId]
    );
    
    if (!$sub) {
        return ['allowed' => false, 'error' => 'No subscription found'];
    }
    
    // Check if trial expired
    if ($sub['status'] === 'trial' && $sub['trial_ends']) {
        if (strtotime($sub['trial_ends']) < time()) {
            return [
                'allowed' => false,
                'error' => 'Your free trial has expired. Please upgrade to continue.',
                'trial_expired' => true
            ];
        }
    }
    
    // Check device limit based on plan
    $limits = [
        'trial' => 1,
        'personal' => 3,
        'family' => -1, // unlimited
        'business' => -1 // unlimited
    ];
    
    $maxDevices = $limits[$sub['plan_type']] ?? 1;
    
    if ($maxDevices === -1) {
        return ['allowed' => true, 'unlimited' => true];
    }
    
    // Count active devices
    $deviceCount = Database::queryOne('users',
        "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1",
        [$userId]
    );
    
    if (($deviceCount['count'] ?? 0) >= $maxDevices) {
        return [
            'allowed' => false,
            'error' => "Device limit reached ({$maxDevices}). Upgrade to add more devices.",
            'current_devices' => $deviceCount['count'],
            'max_devices' => $maxDevices,
            'upgrade_required' => true
        ];
    }
    
    return [
        'allowed' => true,
        'current_devices' => $deviceCount['count'],
        'max_devices' => $maxDevices,
        'remaining' => $maxDevices - ($deviceCount['count'] ?? 0)
    ];
}
```

---

# WORKFLOW 2: Dedicated Server Purchase

## Database Schema Addition

```sql
-- Server pool for pre-provisioned dedicated servers
CREATE TABLE IF NOT EXISTS server_pool (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider TEXT NOT NULL, -- 'contabo', 'fly.io', 'vultr'
    provider_instance_id TEXT,
    region TEXT NOT NULL,
    ip_address TEXT,
    ipv6_address TEXT,
    status TEXT DEFAULT 'available' CHECK(status IN ('available', 'provisioning', 'assigned', 'maintenance', 'retired')),
    tier TEXT DEFAULT 'dedicated' CHECK(tier IN ('shared', 'dedicated', 'enterprise')),
    assigned_user_id INTEGER,
    assigned_at DATETIME,
    monthly_cost REAL,
    specs TEXT, -- JSON: {"cpu": 4, "ram": "8GB", "disk": "150GB"}
    wireguard_public_key TEXT,
    wireguard_private_key_encrypted TEXT,
    api_key_encrypted TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id)
);

-- Server provisioning queue
CREATE TABLE IF NOT EXISTS server_provisioning_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    payment_id INTEGER NOT NULL,
    plan_type TEXT NOT NULL,
    region_preference TEXT,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'provisioning', 'configuring', 'completed', 'failed')),
    server_pool_id INTEGER,
    vpn_server_id INTEGER,
    error_message TEXT,
    started_at DATETIME,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (server_pool_id) REFERENCES server_pool(id),
    FOREIGN KEY (vpn_server_id) REFERENCES vpn_servers(id)
);
```

## Automation Workflow: dedicated_server_purchase

```php
<?php
// Add to AutomationEngine::$workflows

'dedicated_server_purchase' => [
    'name' => 'Dedicated Server Purchase',
    'steps' => [
        // Step 1: Update subscription immediately
        [
            'action' => 'update_subscription_dedicated',
            'delay' => 0
        ],
        // Step 2: Queue server provisioning
        [
            'action' => 'queue_server_provisioning',
            'delay' => 0
        ],
        // Step 3: Send "processing" email
        [
            'action' => 'send_email',
            'template' => 'dedicated_processing',
            'delay' => 0
        ],
        // Step 4: Provision server (async - handled by background job)
        [
            'action' => 'provision_dedicated_server',
            'delay' => 0,
            'async' => true
        ],
        // Step 5: Generate invoice
        [
            'action' => 'generate_invoice',
            'delay' => 0
        ],
        // Step 6: Schedule renewal reminders
        [
            'action' => 'schedule_renewal_reminders',
            'delay' => 0
        ]
    ]
],
```

## Server Provisioning Engine

```php
<?php
/**
 * TrueVault VPN - Dedicated Server Provisioning Engine
 * Handles automatic server creation and configuration
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class ServerProvisioningEngine {
    
    private static $contaboApiUrl = 'https://api.contabo.com/v1';
    private static $contaboApiKey = null; // Set from environment
    
    /**
     * Provision a dedicated server for a user
     */
    public static function provisionServer($userId, $paymentId, $regionPreference = 'us-east') {
        // Create provisioning queue entry
        Database::execute('billing',
            "INSERT INTO server_provisioning_queue (user_id, payment_id, plan_type, region_preference, status, created_at)
             VALUES (?, ?, 'dedicated', ?, 'pending', datetime('now'))",
            [$userId, $paymentId, $regionPreference]
        );
        
        $queueId = Database::lastInsertId('billing');
        
        try {
            // Step 1: Check for available pre-provisioned server
            $availableServer = self::findAvailableServer($regionPreference);
            
            if ($availableServer) {
                // Use pre-provisioned server
                return self::assignPreProvisionedServer($queueId, $userId, $availableServer);
            }
            
            // Step 2: No available server - provision new one
            return self::provisionNewServer($queueId, $userId, $regionPreference);
            
        } catch (Exception $e) {
            // Mark as failed
            Database::execute('billing',
                "UPDATE server_provisioning_queue SET status = 'failed', error_message = ? WHERE id = ?",
                [$e->getMessage(), $queueId]
            );
            
            throw $e;
        }
    }
    
    /**
     * Find an available pre-provisioned server
     */
    private static function findAvailableServer($region) {
        return Database::queryOne('billing',
            "SELECT * FROM server_pool 
             WHERE status = 'available' AND tier = 'dedicated' AND region = ?
             ORDER BY created_at ASC LIMIT 1",
            [$region]
        );
    }
    
    /**
     * Assign a pre-provisioned server to user
     */
    private static function assignPreProvisionedServer($queueId, $userId, $server) {
        Database::execute('billing',
            "UPDATE server_provisioning_queue SET status = 'configuring', server_pool_id = ?, started_at = datetime('now') WHERE id = ?",
            [$server['id'], $queueId]
        );
        
        // Mark server as assigned
        Database::execute('billing',
            "UPDATE server_pool SET status = 'assigned', assigned_user_id = ?, assigned_at = datetime('now') WHERE id = ?",
            [$userId, $server['id']]
        );
        
        // Configure server for this user
        $vpnServerId = self::configureServerForUser($server, $userId);
        
        // Complete provisioning
        Database::execute('billing',
            "UPDATE server_provisioning_queue SET status = 'completed', vpn_server_id = ?, completed_at = datetime('now') WHERE id = ?",
            [$vpnServerId, $queueId]
        );
        
        // Send ready email
        self::sendServerReadyEmail($userId, $server['ip_address']);
        
        return [
            'success' => true,
            'server_id' => $vpnServerId,
            'ip_address' => $server['ip_address'],
            'region' => $server['region'],
            'status' => 'ready'
        ];
    }
    
    /**
     * Provision a new server via Contabo API
     */
    private static function provisionNewServer($queueId, $userId, $region) {
        Database::execute('billing',
            "UPDATE server_provisioning_queue SET status = 'provisioning', started_at = datetime('now') WHERE id = ?",
            [$queueId]
        );
        
        // Call Contabo API to create new VPS
        $contaboResponse = self::createContaboInstance($region, "truevault-user-$userId");
        
        if (!$contaboResponse['success']) {
            throw new Exception('Failed to provision server: ' . ($contaboResponse['error'] ?? 'Unknown error'));
        }
        
        $instanceId = $contaboResponse['data']['instanceId'];
        $ipAddress = $contaboResponse['data']['ipv4'];
        
        // Create server pool entry
        Database::execute('billing',
            "INSERT INTO server_pool (provider, provider_instance_id, region, ip_address, status, tier, assigned_user_id, assigned_at, created_at)
             VALUES ('contabo', ?, ?, ?, 'provisioning', 'dedicated', ?, datetime('now'), datetime('now'))",
            [$instanceId, $region, $ipAddress, $userId]
        );
        
        $serverPoolId = Database::lastInsertId('billing');
        
        Database::execute('billing',
            "UPDATE server_provisioning_queue SET server_pool_id = ? WHERE id = ?",
            [$serverPoolId, $queueId]
        );
        
        // Wait for server to be ready and configure it
        self::waitForServerAndConfigure($queueId, $serverPoolId, $userId, $ipAddress);
        
        return [
            'success' => true,
            'ip_address' => $ipAddress,
            'status' => 'provisioning',
            'message' => 'Your server is being set up. You will receive an email when ready (usually 2-5 minutes).'
        ];
    }
    
    /**
     * Create Contabo VPS instance via API
     */
    private static function createContaboInstance($region, $displayName) {
        $apiKey = getenv('CONTABO_API_KEY');
        $apiSecret = getenv('CONTABO_API_SECRET');
        
        if (!$apiKey || !$apiSecret) {
            throw new Exception('Contabo API credentials not configured');
        }
        
        // Get OAuth token
        $tokenResponse = self::getContaboToken($apiKey, $apiSecret);
        if (!$tokenResponse['success']) {
            return $tokenResponse;
        }
        
        $accessToken = $tokenResponse['access_token'];
        
        // Create instance
        $regionMap = [
            'us-east' => 'US-east',
            'us-central' => 'US-central',
            'us-west' => 'US-west',
            'eu-de' => 'EU-DE',
            'eu-uk' => 'EU-UK'
        ];
        
        $data = [
            'imageId' => 'ubuntu-24.04', // Ubuntu 24.04 LTS
            'productId' => 'V1', // VPS S (smallest)
            'region' => $regionMap[$region] ?? 'US-east',
            'displayName' => $displayName,
            'defaultUser' => 'root'
        ];
        
        $ch = curl_init(self::$contaboApiUrl . '/compute/instances');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'x-request-id: ' . uniqid('tv-', true)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            return ['success' => false, 'error' => $result['message'] ?? 'API error', 'code' => $httpCode];
        }
        
        return [
            'success' => true,
            'data' => $result['data'][0] ?? $result
        ];
    }
    
    /**
     * Get Contabo OAuth token
     */
    private static function getContaboToken($clientId, $clientSecret) {
        $ch = curl_init('https://auth.contabo.com/auth/realms/contabo/protocol/openid-connect/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['access_token'])) {
            return ['success' => true, 'access_token' => $result['access_token']];
        }
        
        return ['success' => false, 'error' => $result['error_description'] ?? 'Token error'];
    }
    
    /**
     * Wait for server to be ready, then configure it
     */
    private static function waitForServerAndConfigure($queueId, $serverPoolId, $userId, $ipAddress) {
        // This runs as a background job (scheduled task)
        Database::execute('automation',
            "INSERT INTO scheduled_tasks (workflow, step_data, context, execute_at, status, created_at)
             VALUES ('server_setup', ?, ?, datetime('now', '+1 minute'), 'pending', datetime('now'))",
            [
                json_encode(['action' => 'check_and_configure']),
                json_encode([
                    'queue_id' => $queueId,
                    'server_pool_id' => $serverPoolId,
                    'user_id' => $userId,
                    'ip_address' => $ipAddress,
                    'attempt' => 1,
                    'max_attempts' => 20 // 20 minutes max wait
                ])
            ]
        );
    }
    
    /**
     * Configure server for user (called by background job)
     */
    public static function configureServerForUser($server, $userId) {
        $ipAddress = $server['ip_address'];
        
        // 1. SSH into server and run setup script
        $setupScript = self::generateSetupScript($userId);
        
        // Execute via SSH (using PHP's ssh2 extension or shell_exec with ssh)
        $sshResult = self::executeSSH($ipAddress, 'root', $setupScript);
        
        if (!$sshResult['success']) {
            throw new Exception('Failed to configure server: ' . $sshResult['error']);
        }
        
        // 2. Get server's WireGuard public key
        $publicKey = self::getServerPublicKey($ipAddress);
        
        // 3. Update server pool with keys
        Database::execute('billing',
            "UPDATE server_pool SET wireguard_public_key = ?, status = 'assigned', updated_at = datetime('now') WHERE id = ?",
            [$publicKey, $server['id']]
        );
        
        // 4. Add to vpn_servers table
        $user = Database::queryOne('users', "SELECT email, first_name FROM users WHERE id = ?", [$userId]);
        
        Database::execute('servers',
            "INSERT INTO vpn_servers (name, provider, region, country, ip_address, wireguard_port, api_port, public_key, status, server_type, dedicated_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, 51820, 8080, ?, 'active', 'dedicated', ?, datetime('now'))",
            [
                ($user['first_name'] ?? 'User') . "'s Dedicated Server",
                $server['provider'] ?? 'contabo',
                $server['region'],
                self::getCountryFromRegion($server['region']),
                $ipAddress,
                $publicKey,
                $userId
            ]
        );
        
        $vpnServerId = Database::lastInsertId('servers');
        
        // 5. Update user's dedicated server reference
        Database::execute('users',
            "UPDATE users SET dedicated_server_id = ? WHERE id = ?",
            [$vpnServerId, $userId]
        );
        
        return $vpnServerId;
    }
    
    /**
     * Generate WireGuard setup script for new server
     */
    private static function generateSetupScript($userId) {
        return <<<'BASH'
#!/bin/bash
set -e

# Update system
apt-get update
apt-get upgrade -y

# Install WireGuard
apt-get install -y wireguard wireguard-tools

# Generate server keys
mkdir -p /etc/wireguard
wg genkey | tee /etc/wireguard/privatekey | wg pubkey > /etc/wireguard/publickey
chmod 600 /etc/wireguard/privatekey

PRIVATE_KEY=$(cat /etc/wireguard/privatekey)

# Create WireGuard config
cat > /etc/wireguard/wg0.conf << EOF
[Interface]
Address = 10.0.0.1/24
ListenPort = 51820
PrivateKey = $PRIVATE_KEY
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
EOF

# Enable IP forwarding
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
sysctl -p

# Start WireGuard
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0

# Install Python for peer API
apt-get install -y python3 python3-pip
pip3 install flask

# Create peer API directory
mkdir -p /opt/truevault
mkdir -p /var/lib/wireguard

# Create peer API script
cat > /opt/truevault/peer_api.py << 'APIEOF'
#!/usr/bin/env python3
from flask import Flask, request, jsonify
import subprocess
import json
import os
import ipaddress
from datetime import datetime

app = Flask(__name__)

WIREGUARD_INTERFACE = "wg0"
IP_POOL_START = "10.0.0.2"
IP_POOL_END = "10.0.0.254"
PEERS_FILE = "/var/lib/wireguard/peers.json"
API_KEY = os.environ.get('TRUEVAULT_API_KEY', 'changeme')

def load_peers():
    if os.path.exists(PEERS_FILE):
        with open(PEERS_FILE, 'r') as f:
            return json.load(f)
    return {}

def save_peers(peers):
    with open(PEERS_FILE, 'w') as f:
        json.dump(peers, f, indent=2)

def get_next_ip():
    peers = load_peers()
    used = set(p.get('ip') for p in peers.values())
    start = ipaddress.IPv4Address(IP_POOL_START)
    end = ipaddress.IPv4Address(IP_POOL_END)
    for i in range(int(start), int(end) + 1):
        ip = str(ipaddress.IPv4Address(i))
        if ip not in used:
            return ip
    return None

@app.route('/health')
def health():
    return jsonify({'status': 'healthy', 'timestamp': datetime.utcnow().isoformat()})

@app.route('/add_peer', methods=['POST'])
def add_peer():
    key = request.headers.get('X-API-Key')
    if key != API_KEY:
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.json
    public_key = data.get('public_key')
    if not public_key:
        return jsonify({'success': False, 'error': 'Public key required'}), 400
    
    assigned_ip = get_next_ip()
    if not assigned_ip:
        return jsonify({'success': False, 'error': 'No IPs available'}), 503
    
    try:
        subprocess.run(['wg', 'set', WIREGUARD_INTERFACE, 'peer', public_key, 'allowed-ips', f'{assigned_ip}/32'], check=True)
        subprocess.run(['wg-quick', 'save', WIREGUARD_INTERFACE], check=True)
        
        peers = load_peers()
        peers[public_key] = {'ip': assigned_ip, 'added_at': datetime.utcnow().isoformat(), 'user_id': data.get('user_id')}
        save_peers(peers)
        
        return jsonify({'success': True, 'assigned_ip': assigned_ip})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/remove_peer', methods=['POST'])
def remove_peer():
    key = request.headers.get('X-API-Key')
    if key != API_KEY:
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.json
    public_key = data.get('public_key')
    
    try:
        subprocess.run(['wg', 'set', WIREGUARD_INTERFACE, 'peer', public_key, 'remove'], check=True)
        subprocess.run(['wg-quick', 'save', WIREGUARD_INTERFACE], check=True)
        
        peers = load_peers()
        if public_key in peers:
            del peers[public_key]
            save_peers(peers)
        
        return jsonify({'success': True})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/get_public_key')
def get_public_key():
    try:
        with open('/etc/wireguard/publickey', 'r') as f:
            return jsonify({'success': True, 'public_key': f.read().strip()})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080)
APIEOF

# Create systemd service
cat > /etc/systemd/system/truevault-api.service << 'SVCEOF'
[Unit]
Description=TrueVault Peer API
After=network.target

[Service]
Type=simple
User=root
Environment=TRUEVAULT_API_KEY=changeme
ExecStart=/usr/bin/python3 /opt/truevault/peer_api.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
SVCEOF

# Start API service
systemctl daemon-reload
systemctl enable truevault-api
systemctl start truevault-api

# Open firewall ports
ufw allow 51820/udp
ufw allow 8080/tcp
ufw --force enable

echo "Setup complete!"
BASH;
    }
    
    /**
     * Execute commands via SSH
     */
    private static function executeSSH($host, $user, $script) {
        // Save script to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'setup_');
        file_put_contents($tempFile, $script);
        
        // Execute via ssh
        $command = sprintf(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=30 %s@%s "bash -s" < %s 2>&1',
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($tempFile)
        );
        
        exec($command, $output, $returnCode);
        
        unlink($tempFile);
        
        if ($returnCode !== 0) {
            return ['success' => false, 'error' => implode("\n", $output)];
        }
        
        return ['success' => true, 'output' => implode("\n", $output)];
    }
    
    /**
     * Get server's WireGuard public key
     */
    private static function getServerPublicKey($ipAddress) {
        $ch = curl_init("http://$ipAddress:8080/get_public_key");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result && $result['success']) {
            return $result['public_key'];
        }
        
        throw new Exception('Could not get server public key');
    }
    
    /**
     * Send server ready email
     */
    private static function sendServerReadyEmail($userId, $ipAddress) {
        $user = Database::queryOne('users', "SELECT email, first_name FROM users WHERE id = ?", [$userId]);
        
        if (!$user) return;
        
        require_once __DIR__ . '/../helpers/mailer.php';
        
        $subject = "🎉 Your Dedicated VPN Server is Ready!";
        $body = "
Hi {$user['first_name']},

Great news! Your personal dedicated VPN server is now live and ready to use.

═══════════════════════════════════════════
SERVER DETAILS
═══════════════════════════════════════════

IP Address: $ipAddress
Location: US East
Status: ✓ Online and Ready

═══════════════════════════════════════════

This server is EXCLUSIVELY YOURS. No one else can use it. Enjoy:
• Unlimited bandwidth
• Maximum privacy
• No throttling
• Full control

👉 Download your connection config here:
https://vpn.the-truth-publishing.com/dashboard/connect.html

Your first device config is already generated and waiting!

Questions? Just reply to this email.

Welcome to the VIP experience!
— The TrueVault Team
";
        
        Mailer::send($user['email'], $subject, $body);
    }
    
    /**
     * Get country from region code
     */
    private static function getCountryFromRegion($region) {
        $map = [
            'us-east' => 'USA',
            'us-central' => 'USA',
            'us-west' => 'USA',
            'ca-east' => 'Canada',
            'ca-west' => 'Canada',
            'eu-de' => 'Germany',
            'eu-uk' => 'UK',
            'eu-nl' => 'Netherlands',
            'asia-sg' => 'Singapore',
            'asia-jp' => 'Japan'
        ];
        
        return $map[$region] ?? 'USA';
    }
}
```

---

# CRON JOB ADDITIONS

```php
<?php
// Add to /api/cron/process.php

// Process server provisioning queue
$pendingServers = Database::query('billing',
    "SELECT * FROM server_provisioning_queue WHERE status IN ('pending', 'provisioning', 'configuring') AND created_at > datetime('now', '-1 hour')"
);

foreach ($pendingServers as $queue) {
    try {
        // Check if server is ready
        $server = Database::queryOne('billing',
            "SELECT * FROM server_pool WHERE id = ?",
            [$queue['server_pool_id']]
        );
        
        if ($server && $server['status'] === 'provisioning') {
            // Check if server is now responding
            $ch = curl_init("http://{$server['ip_address']}:8080/health");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                // Server is ready! Configure it
                require_once __DIR__ . '/../billing/provisioning.php';
                ServerProvisioningEngine::configureServerForUser($server, $queue['user_id']);
                
                Database::execute('billing',
                    "UPDATE server_provisioning_queue SET status = 'completed', completed_at = datetime('now') WHERE id = ?",
                    [$queue['id']]
                );
            }
        }
    } catch (Exception $e) {
        error_log("Server provisioning error: " . $e->getMessage());
    }
}

// Process trial expirations
$expiredTrials = Database::query('billing',
    "SELECT u.id, u.email, u.first_name, s.id as sub_id
     FROM users u
     JOIN subscriptions s ON u.id = s.user_id
     WHERE s.status = 'trial' AND s.trial_ends < date('now')
     AND NOT EXISTS (SELECT 1 FROM trial_tracking t WHERE t.user_id = u.id AND t.expired = 1)"
);

foreach ($expiredTrials as $trial) {
    // Mark trial as expired
    Database::execute('billing',
        "UPDATE subscriptions SET status = 'expired' WHERE id = ?",
        [$trial['sub_id']]
    );
    
    Database::execute('billing',
        "UPDATE trial_tracking SET expired = 1, expired_at = datetime('now') WHERE user_id = ?",
        [$trial['id']]
    );
    
    // Send expired email
    require_once __DIR__ . '/../helpers/mailer.php';
    Mailer::sendTemplate($trial['email'], 'trial_expired', [
        'first_name' => $trial['first_name'],
        'dashboard_url' => 'https://vpn.the-truth-publishing.com/dashboard/'
    ]);
}
```

---

# END OF DETAILED WORKFLOWS
