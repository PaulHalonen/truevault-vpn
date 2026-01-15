# SECTION 14: SECURITY

**Created:** January 15, 2026  
**Status:** Complete Security Specification  
**Priority:** CRITICAL - Production Security  
**Complexity:** HIGH - Comprehensive Security Framework  

---

## 📋 TABLE OF CONTENTS

1. [Security Overview](#overview)
2. [Encryption Standards](#encryption)
3. [Authentication Security](#authentication)
4. [Input Validation](#validation)
5. [SQL Injection Prevention](#sql-injection)
6. [XSS Protection](#xss)
7. [CSRF Protection](#csrf)
8. [Password Security](#passwords)
9. [Session Management](#sessions)
10. [API Security](#api-security)
11. [File Upload Security](#uploads)
12. [Security Headers](#headers)
13. [Rate Limiting](#rate-limiting)
14. [Logging & Monitoring](#logging)
15. [SSL/TLS Configuration](#ssl)
16. [WireGuard Security](#wireguard)
17. [Vulnerability Prevention](#vulnerabilities)
18. [Security Checklist](#checklist)

---

## 🔒 SECURITY OVERVIEW

### **Security Philosophy**

TrueVault VPN follows **defense in depth** principles:
- Multiple layers of security
- Fail securely by default
- Least privilege access
- Input validation everywhere
- Comprehensive logging
- Regular security audits

### **Threat Model**

**Primary Threats:**
1. Unauthorized access to user data
2. Man-in-the-middle attacks
3. SQL injection attacks
4. Cross-site scripting (XSS)
5. Cross-site request forgery (CSRF)
6. Brute force authentication attacks
7. VPN traffic interception
8. WireGuard key compromise
9. Payment data theft
10. Port forwarding abuse

### **Security Principles**

**1. Never Trust User Input**
- Validate all input
- Sanitize for output
- Use parameterized queries
- Whitelist over blacklist

**2. Encrypt Everything**
- HTTPS everywhere (no HTTP)
- Database encryption at rest
- WireGuard encryption in transit
- Encrypted password storage

**3. Minimize Attack Surface**
- Disable unused features
- Close unnecessary ports
- Remove default accounts
- Regular security updates

**4. Log Everything Suspicious**
- Failed login attempts
- API rate limit violations
- Unusual access patterns
- Database query errors

---

## 🔐 ENCRYPTION STANDARDS

### **Data at Rest Encryption**

**Database Encryption:**
```php
<?php
// File: /includes/encryption.php

define('ENCRYPTION_KEY', 'your-32-byte-encryption-key-here-change-this');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

/**
 * Encrypt sensitive data before storing
 */
function encryptData($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    
    // Return IV + encrypted data (IV is not secret)
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt sensitive data after retrieval
 */
function decryptData($encryptedData) {
    $data = base64_decode($encryptedData);
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    
    return openssl_decrypt($encrypted, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}

/**
 * Encrypt WireGuard private key before storage
 */
function encryptPrivateKey($privateKey) {
    return encryptData($privateKey);
}

/**
 * Decrypt WireGuard private key for use
 */
function decryptPrivateKey($encryptedKey) {
    return decryptData($encryptedKey);
}
```

**What Gets Encrypted:**
- WireGuard private keys
- Payment tokens
- API keys
- Session data
- Personal information (optional)

**Encryption Key Management:**
```php
<?php
// Store encryption key in environment variable (NOT in code)
// .env file:
ENCRYPTION_KEY=your_randomly_generated_32_byte_key_here

// Load in PHP:
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY'));

// Generate new key:
// php -r "echo bin2hex(random_bytes(32));"
```

### **Data in Transit Encryption**

**HTTPS Only:**
```php
<?php
// Force HTTPS redirect
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirect, true, 301);
    exit;
}
```

**.htaccess Configuration:**
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set X-Frame-Options "DENY"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

**WireGuard Encryption:**
- ChaCha20-Poly1305 cipher
- Curve25519 for key exchange
- BLAKE2s for hashing
- Perfect forward secrecy
- (See Section 11 for details)

---

## 🔑 AUTHENTICATION SECURITY

### **Password Requirements**

**Strength Validation:**
```php
<?php
/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    // Minimum 8 characters
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    // Maximum 128 characters (prevent DoS)
    if (strlen($password) > 128) {
        $errors[] = 'Password must be less than 128 characters';
    }
    
    // At least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    // At least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    // At least one number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    // At least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    // Check against common passwords
    if (isCommonPassword($password)) {
        $errors[] = 'Password is too common. Please choose a stronger password';
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Check against common password list
 */
function isCommonPassword($password) {
    $commonPasswords = [
        'password', 'password123', '123456', '12345678', 'qwerty',
        'abc123', 'monkey', '1234567', 'letmein', 'trustno1',
        'dragon', 'baseball', 'iloveyou', 'master', 'sunshine'
    ];
    
    return in_array(strtolower($password), $commonPasswords);
}
```

### **Password Hashing**

**NEVER store plaintext passwords!**

```php
<?php
/**
 * Hash password securely
 */
function hashPassword($password) {
    // Uses bcrypt with cost factor 12
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 */
function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Complete login verification
 */
function authenticateUser($email, $password) {
    global $db_users;
    
    // Get user
    $stmt = $db_users->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Use same timing to prevent user enumeration
        password_verify($password, '$2y$12$fakehashtopreventtimingattacks');
        return false;
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        // Log failed attempt
        logFailedLogin($email, $_SERVER['REMOTE_ADDR']);
        return false;
    }
    
    // Check if hash needs upgrade
    if (needsRehash($user['password'])) {
        $newHash = hashPassword($password);
        $stmt = $db_users->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
    }
    
    // Log successful login
    logSuccessfulLogin($user['id'], $_SERVER['REMOTE_ADDR']);
    
    return $user;
}
```

### **Brute Force Protection**

```php
<?php
/**
 * Check for brute force attempts
 */
function checkBruteForce($email) {
    $lockoutFile = sys_get_temp_dir() . '/login_' . md5($email) . '.txt';
    
    $maxAttempts = 5;
    $lockoutDuration = 900; // 15 minutes
    
    // Load attempts
    $attempts = [];
    if (file_exists($lockoutFile)) {
        $attempts = json_decode(file_get_contents($lockoutFile), true) ?? [];
    }
    
    // Clean old attempts
    $cutoff = time() - $lockoutDuration;
    $attempts = array_filter($attempts, function($timestamp) use ($cutoff) {
        return $timestamp > $cutoff;
    });
    
    // Check if locked out
    if (count($attempts) >= $maxAttempts) {
        $oldestAttempt = min($attempts);
        $remainingTime = ($oldestAttempt + $lockoutDuration) - time();
        
        return [
            'locked' => true,
            'remaining' => $remainingTime
        ];
    }
    
    return ['locked' => false];
}

/**
 * Record failed login attempt
 */
function recordFailedAttempt($email) {
    $lockoutFile = sys_get_temp_dir() . '/login_' . md5($email) . '.txt';
    
    $attempts = [];
    if (file_exists($lockoutFile)) {
        $attempts = json_decode(file_get_contents($lockoutFile), true) ?? [];
    }
    
    $attempts[] = time();
    
    file_put_contents($lockoutFile, json_encode($attempts));
}

/**
 * Clear failed attempts on successful login
 */
function clearFailedAttempts($email) {
    $lockoutFile = sys_get_temp_dir() . '/login_' . md5($email) . '.txt';
    
    if (file_exists($lockoutFile)) {
        unlink($lockoutFile);
    }
}
```

### **Two-Factor Authentication (2FA)**

```php
<?php
/**
 * Generate TOTP secret for 2FA
 */
function generate2FASecret() {
    return bin2hex(random_bytes(20));
}

/**
 * Generate QR code for 2FA setup
 */
function generate2FAQRCode($email, $secret) {
    $issuer = 'TrueVault VPN';
    $otpauth = "otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}";
    
    // Use QR code library
    require_once __DIR__ . '/../vendor/phpqrcode/qrlib.php';
    
    ob_start();
    QRcode::png($otpauth, null, QR_ECLEVEL_L, 4);
    $imageData = ob_get_clean();
    
    return 'data:image/png;base64,' . base64_encode($imageData);
}

/**
 * Verify TOTP code
 */
function verify2FACode($secret, $code) {
    require_once __DIR__ . '/../vendor/otphp/otphp.php';
    
    $totp = TOTP::create($secret);
    return $totp->verify($code);
}
```

---

## ✅ INPUT VALIDATION

### **Validation Rules**

**All user input MUST be validated:**

```php
<?php
// File: /includes/validation.php

/**
 * Validate email address
 */
function validateEmail($email) {
    if (empty($email)) {
        return ['valid' => false, 'error' => 'Email is required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'error' => 'Invalid email format'];
    }
    
    // Check for disposable email domains
    $domain = explode('@', $email)[1];
    if (isDisposableEmail($domain)) {
        return ['valid' => false, 'error' => 'Disposable email addresses not allowed'];
    }
    
    return ['valid' => true];
}

/**
 * Validate IP address
 */
function validateIP($ip) {
    if (empty($ip)) {
        return ['valid' => false, 'error' => 'IP address is required'];
    }
    
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return ['valid' => false, 'error' => 'Invalid IP address format'];
    }
    
    // Check if private IP
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
        return ['valid' => true, 'private' => true];
    }
    
    return ['valid' => true, 'private' => false];
}

/**
 * Validate port number
 */
function validatePort($port) {
    if (!is_numeric($port)) {
        return ['valid' => false, 'error' => 'Port must be numeric'];
    }
    
    $port = (int)$port;
    
    if ($port < 1 || $port > 65535) {
        return ['valid' => false, 'error' => 'Port must be between 1 and 65535'];
    }
    
    // Block system ports (optional)
    if ($port < 1024) {
        return ['valid' => false, 'error' => 'System ports (1-1023) not allowed'];
    }
    
    return ['valid' => true];
}

/**
 * Validate device name
 */
function validateDeviceName($name) {
    if (empty($name)) {
        return ['valid' => false, 'error' => 'Device name is required'];
    }
    
    if (strlen($name) > 50) {
        return ['valid' => false, 'error' => 'Device name too long (max 50 characters)'];
    }
    
    // Allow alphanumeric, spaces, hyphens, underscores
    if (!preg_match('/^[a-zA-Z0-9 _-]+$/', $name)) {
        return ['valid' => false, 'error' => 'Device name contains invalid characters'];
    }
    
    return ['valid' => true];
}

/**
 * Sanitize for HTML output
 */
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize for database input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return trim(strip_tags($data));
}
```

### **Input Validation Example**

```php
<?php
// Example: Create device endpoint

$input = json_decode(file_get_contents('php://input'), true);

// Validate device name
$nameValidation = validateDeviceName($input['name'] ?? '');
if (!$nameValidation['valid']) {
    sendError($nameValidation['error'], 'DEV_2004', 400);
}

// Validate device type
$allowedTypes = ['phone', 'tablet', 'laptop', 'desktop', 'router', 'other'];
$deviceType = $input['device_type'] ?? 'other';
if (!in_array($deviceType, $allowedTypes)) {
    sendError('Invalid device type', 'DEV_2003', 400);
}

// Validate server ID
$serverId = filter_var($input['server_id'] ?? 0, FILTER_VALIDATE_INT);
if ($serverId === false || $serverId < 1) {
    sendError('Invalid server ID', 'SRV_3001', 400);
}

// All validation passed, proceed...
```

---

## 🛡️ SQL INJECTION PREVENTION

### **ALWAYS Use Prepared Statements**

**WRONG (Vulnerable):**
```php
<?php
// NEVER DO THIS!
$email = $_POST['email'];
$query = "SELECT * FROM users WHERE email = '$email'";
$result = $db->query($query);
```

**RIGHT (Safe):**
```php
<?php
// ALWAYS DO THIS!
$email = $_POST['email'];
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$result = $stmt->fetch();
```

### **Safe Query Examples**

```php
<?php
/**
 * Safe SELECT query
 */
function getUserByEmail($email) {
    global $db_users;
    
    $stmt = $db_users->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Safe INSERT query
 */
function createUser($email, $password, $firstName, $lastName) {
    global $db_users;
    
    $stmt = $db_users->prepare("
        INSERT INTO users (email, password, first_name, last_name, created_at)
        VALUES (?, ?, ?, ?, datetime('now'))
    ");
    
    $stmt->execute([$email, hashPassword($password), $firstName, $lastName]);
    
    return $db_users->lastInsertId();
}

/**
 * Safe UPDATE query
 */
function updateUserProfile($userId, $firstName, $lastName) {
    global $db_users;
    
    $stmt = $db_users->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$firstName, $lastName, $userId]);
    
    return $stmt->rowCount();
}

/**
 * Safe DELETE query
 */
function deleteDevice($deviceId, $userId) {
    global $db_devices;
    
    // Verify ownership before delete
    $stmt = $db_devices->prepare("
        DELETE FROM devices 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$deviceId, $userId]);
    
    return $stmt->rowCount();
}

/**
 * Safe query with multiple conditions
 */
function searchDevices($userId, $deviceType = null, $serverId = null) {
    global $db_devices;
    
    $query = "SELECT * FROM devices WHERE user_id = ?";
    $params = [$userId];
    
    if ($deviceType) {
        $query .= " AND device_type = ?";
        $params[] = $deviceType;
    }
    
    if ($serverId) {
        $query .= " AND current_server_id = ?";
        $params[] = $serverId;
    }
    
    $stmt = $db_devices->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

### **PDO Configuration**

```php
<?php
/**
 * Secure PDO configuration
 */
function initDatabase() {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
        PDO::ATTR_STRINGIFY_FETCHES => false, // Return proper types
    ];
    
    try {
        $db = new PDO('sqlite:' . DB_PATH, null, null, $options);
        return $db;
    } catch (PDOException $e) {
        logError('Database connection failed', $e);
        die('Database error');
    }
}
```

---

## 🚫 XSS PROTECTION

### **Output Escaping**

**ALWAYS escape output:**

```php
<?php
/**
 * Escape for HTML context
 */
function escapeHTML($data) {
    if (is_array($data)) {
        return array_map('escapeHTML', $data);
    }
    
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Escape for JavaScript context
 */
function escapeJS($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Escape for URL context
 */
function escapeURL($data) {
    return urlencode($data);
}

/**
 * Escape for CSS context
 */
function escapeCSS($data) {
    return preg_replace('/[^a-zA-Z0-9-_]/', '', $data);
}
```

**Usage Examples:**

```php
<!-- HTML Context -->
<div class="device-name"><?php echo escapeHTML($device['name']); ?></div>

<!-- HTML Attribute Context -->
<input type="text" value="<?php echo escapeHTML($device['name']); ?>">

<!-- JavaScript Context -->
<script>
const deviceName = <?php echo escapeJS($device['name']); ?>;
</script>

<!-- URL Context -->
<a href="/device?id=<?php echo escapeURL($device['id']); ?>">View</a>
```

### **Content Security Policy (CSP)**

```php
<?php
/**
 * Set Content Security Policy header
 */
function setCSPHeader() {
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: https:",
        "font-src 'self' https://fonts.gstatic.com",
        "connect-src 'self' https://vpn.the-truth-publishing.com",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'"
    ];
    
    header('Content-Security-Policy: ' . implode('; ', $csp));
}
```

### **XSS Prevention in JSON APIs**

```php
<?php
/**
 * Send JSON response safely
 */
function sendJSON($data) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    
    echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}
```

---

## 🔰 CSRF PROTECTION

### **CSRF Token Implementation**

```php
<?php
/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require CSRF token
 */
function requireCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (!verifyCSRFToken($token)) {
        sendError('Invalid CSRF token', 'SEC_001', 403);
    }
}
```

### **Usage in Forms**

```php
<!-- Include in every form -->
<form method="POST" action="/api/device.php">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <input type="text" name="device_name" required>
    <button type="submit">Create Device</button>
</form>
```

### **Usage in AJAX**

```javascript
// Get CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Include in all POST/PUT/DELETE requests
fetch('/api/device.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
        name: 'My Device',
        type: 'laptop'
    })
});
```

### **SameSite Cookie Protection**

```php
<?php
/**
 * Set secure session cookie
 */
function startSecureSession() {
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => 'vpn.the-truth-publishing.com',
        'secure' => true,  // HTTPS only
        'httponly' => true,  // No JavaScript access
        'samesite' => 'Strict'  // CSRF protection
    ];
    
    session_set_cookie_params($cookieParams);
    session_start();
}
```

---

## 🔑 PASSWORD SECURITY

### **Password Storage**

**NEVER store plaintext passwords!**

```php
<?php
/**
 * Complete password security implementation
 */
class PasswordSecurity {
    
    // Bcrypt cost (higher = more secure but slower)
    const COST = 12;
    
    /**
     * Hash password
     */
    public static function hash($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => self::COST]);
    }
    
    /**
     * Verify password
     */
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if needs rehash
     */
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => self::COST]);
    }
    
    /**
     * Generate strong random password
     */
    public static function generate($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}
```

### **Password Reset Security**

```php
<?php
/**
 * Generate password reset token
 */
function generateResetToken($userId) {
    global $db_users;
    
    // Generate secure random token
    $token = bin2hex(random_bytes(32));
    
    // Hash token for storage
    $hashedToken = hash('sha256', $token);
    
    // Store hashed token with expiration (1 hour)
    $expiry = date('Y-m-d H:i:s', time() + 3600);
    
    $stmt = $db_users->prepare("
        UPDATE users 
        SET reset_token = ?, reset_token_expiry = ?
        WHERE id = ?
    ");
    $stmt->execute([$hashedToken, $expiry, $userId]);
    
    // Return plaintext token (send via email)
    return $token;
}

/**
 * Verify password reset token
 */
function verifyResetToken($token) {
    global $db_users;
    
    // Hash provided token
    $hashedToken = hash('sha256', $token);
    
    // Find user with valid token
    $stmt = $db_users->prepare("
        SELECT * FROM users 
        WHERE reset_token = ? 
        AND reset_token_expiry > datetime('now')
    ");
    $stmt->execute([$hashedToken]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Complete password reset
 */
function resetPassword($token, $newPassword) {
    global $db_users;
    
    // Verify token
    $user = verifyResetToken($token);
    if (!$user) {
        return false;
    }
    
    // Validate new password
    $validation = validatePasswordStrength($newPassword);
    if ($validation !== true) {
        return false;
    }
    
    // Update password and clear reset token
    $hashedPassword = PasswordSecurity::hash($newPassword);
    
    $stmt = $db_users->prepare("
        UPDATE users 
        SET password = ?, reset_token = NULL, reset_token_expiry = NULL
        WHERE id = ?
    ");
    $stmt->execute([$hashedPassword, $user['id']]);
    
    // Log password change
    logSecurityEvent('password_reset', ['user_id' => $user['id']]);
    
    return true;
}
```

---

**END OF SECTION 14: SECURITY (Part 1/3)**

**Status:** In Progress (33% Complete)  
**Next:** Part 2 will cover Sessions, API Security, File Uploads, Headers, SSL/TLS  
**Lines:** ~1,500 lines  
**Created:** January 15, 2026 - 6:50 AM CST
