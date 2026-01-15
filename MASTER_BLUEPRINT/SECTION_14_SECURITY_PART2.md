# SECTION 14: SECURITY (Part 2/3)

**Created:** January 15, 2026  
**Status:** Complete Security Specification  
**Continuation of:** SECTION_14_SECURITY_PART1.md  

---

## 🎫 SESSION MANAGEMENT

### **Secure Session Configuration**

```php
<?php
// File: /includes/session.php

/**
 * Start secure session
 */
function startSecureSession() {
    // Session configuration
    ini_set('session.cookie_httponly', 1);  // No JavaScript access
    ini_set('session.cookie_secure', 1);    // HTTPS only
    ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
    ini_set('session.use_strict_mode', 1);  // Reject uninitialized session IDs
    ini_set('session.use_only_cookies', 1);  // No session ID in URL
    ini_set('session.cookie_lifetime', 0);  // Session expires when browser closes
    
    // Set cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => 'vpn.the-truth-publishing.com',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/**
 * Destroy session securely
 */
function destroySession() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(
            session_name(),
            '',
            time() - 3600,
            '/',
            'vpn.the-truth-publishing.com',
            true,
            true
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Validate session
 */
function validateSession() {
    // Check if session is valid
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout (30 minutes of inactivity)
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > 1800) {
        destroySession();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Validate IP address (optional, but prevents session hijacking)
    if (isset($_SESSION['ip_address']) && 
        $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        destroySession();
        logSecurityEvent('session_hijack_attempt', [
            'session_ip' => $_SESSION['ip_address'],
            'request_ip' => $_SERVER['REMOTE_ADDR']
        ]);
        return false;
    }
    
    // Validate user agent (optional)
    if (isset($_SESSION['user_agent']) && 
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        destroySession();
        logSecurityEvent('session_hijack_attempt', [
            'session_ua' => $_SESSION['user_agent'],
            'request_ua' => $_SERVER['HTTP_USER_AGENT']
        ]);
        return false;
    }
    
    return true;
}

/**
 * Create user session
 */
function createUserSession($user) {
    // Regenerate session ID
    session_regenerate_id(true);
    
    // Set session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['tier'] = $user['tier'];
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['created'] = time();
    $_SESSION['last_activity'] = time();
    
    // Generate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

### **Session Fixation Prevention**

```php
<?php
/**
 * Prevent session fixation attacks
 */
function preventSessionFixation() {
    // Always regenerate session ID on login
    session_regenerate_id(true);
    
    // Always regenerate on privilege escalation
    // (e.g., user becomes admin)
}

/**
 * Complete login with session fixation prevention
 */
function secureLogin($email, $password) {
    // Authenticate user
    $user = authenticateUser($email, $password);
    
    if (!$user) {
        return false;
    }
    
    // Prevent session fixation
    session_regenerate_id(true);
    
    // Create secure session
    createUserSession($user);
    
    return true;
}
```

---

## 🔐 API SECURITY

### **API Authentication**

```php
<?php
// File: /includes/api-security.php

/**
 * Authenticate API request
 */
function authenticateAPIRequest() {
    // Get Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    // Extract token
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        sendAPIError('Missing authorization token', 'AUTH_1001', 401);
    }
    
    $token = $matches[1];
    
    // Verify JWT token
    $payload = verifyJWT($token);
    if (!$payload) {
        sendAPIError('Invalid or expired token', 'AUTH_1003', 401);
    }
    
    // Get user
    $user = getUser($payload['user_id']);
    if (!$user) {
        sendAPIError('User not found', 'AUTH_1001', 401);
    }
    
    // Check account status
    if ($user['status'] !== 'active') {
        sendAPIError('Account suspended', 'AUTH_1004', 403);
    }
    
    return $user;
}

/**
 * Rate limit API requests
 */
function rateLimitAPI($user) {
    $limits = [
        'standard' => 100,  // per minute
        'pro' => 200,
        'vip' => 1000,
        'admin' => 999999
    ];
    
    $limit = $limits[$user['tier']] ?? 100;
    
    // Check rate limit
    if (!checkRateLimit($user['id'], $limit)) {
        sendAPIError('Rate limit exceeded', 'SYS_9003', 429);
    }
}

/**
 * Validate API request origin
 */
function validateAPIOrigin() {
    $allowedOrigins = [
        'https://vpn.the-truth-publishing.com',
        'https://www.vpn.the-truth-publishing.com'
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if ($origin && !in_array($origin, $allowedOrigins)) {
        sendAPIError('Invalid origin', 'SEC_002', 403);
    }
}

/**
 * Require HTTPS for API
 */
function requireHTTPS() {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        sendAPIError('HTTPS required', 'SEC_003', 403);
    }
}

/**
 * Complete API security check
 */
function secureAPIEndpoint() {
    // Require HTTPS
    requireHTTPS();
    
    // Validate origin
    validateAPIOrigin();
    
    // Authenticate user
    $user = authenticateAPIRequest();
    
    // Rate limit
    rateLimitAPI($user);
    
    // Log request
    logAPIRequest($user, $_SERVER['REQUEST_URI']);
    
    return $user;
}
```

### **API Request Signing**

```php
<?php
/**
 * Sign API request (for high-security operations)
 */
function signAPIRequest($data, $secretKey) {
    $signature = hash_hmac('sha256', json_encode($data), $secretKey);
    return $signature;
}

/**
 * Verify API request signature
 */
function verifyAPISignature($data, $signature, $secretKey) {
    $expectedSignature = hash_hmac('sha256', json_encode($data), $secretKey);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Example: Secure payment request
 */
function processPayment($data) {
    // Verify signature
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $secretKey = getPaymentSecretKey();
    
    if (!verifyAPISignature($data, $signature, $secretKey)) {
        sendAPIError('Invalid signature', 'SEC_004', 403);
    }
    
    // Process payment...
}
```

---

## 📁 FILE UPLOAD SECURITY

### **Secure File Upload**

```php
<?php
// File: /includes/upload-security.php

/**
 * Validate uploaded file
 */
function validateUpload($file) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Invalid file upload'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload failed: ' . $file['error']];
    }
    
    // Check file size (10 MB max)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large (max 10 MB)'];
    }
    
    // Validate MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    // Validate file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Invalid file extension'];
    }
    
    return ['valid' => true];
}

/**
 * Securely save uploaded file
 */
function saveUploadedFile($file, $directory = '/uploads/') {
    // Validate upload
    $validation = validateUpload($file);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    // Generate secure filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    
    // Create directory if it doesn't exist
    $uploadPath = __DIR__ . '/..' . $directory;
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Move file
    $destination = $uploadPath . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    // Set permissions
    chmod($destination, 0644);
    
    return [
        'success' => true,
        'filename' => $filename,
        'path' => $directory . $filename
    ];
}

/**
 * Prevent directory traversal
 */
function sanitizeFilePath($path) {
    // Remove null bytes
    $path = str_replace("\0", '', $path);
    
    // Remove parent directory references
    $path = str_replace(['../', '..\\'], '', $path);
    
    // Remove absolute path indicators
    $path = ltrim($path, '/\\');
    
    return $path;
}

/**
 * Serve file securely
 */
function serveFile($filename) {
    // Sanitize filename
    $filename = sanitizeFilePath($filename);
    
    // Build full path
    $filepath = __DIR__ . '/../uploads/' . $filename;
    
    // Check if file exists
    if (!file_exists($filepath) || !is_file($filepath)) {
        http_response_code(404);
        die('File not found');
    }
    
    // Get MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filepath);
    finfo_close($finfo);
    
    // Set headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filepath));
    header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
    header('X-Content-Type-Options: nosniff');
    
    // Output file
    readfile($filepath);
    exit;
}
```

### **Image Upload Security**

```php
<?php
/**
 * Validate image upload
 */
function validateImageUpload($file) {
    // Basic validation
    $validation = validateUpload($file);
    if (!$validation['valid']) {
        return $validation;
    }
    
    // Verify it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return ['valid' => false, 'error' => 'File is not a valid image'];
    }
    
    // Check image dimensions (prevent memory exhaustion)
    list($width, $height) = $imageInfo;
    $maxDimension = 4096;
    
    if ($width > $maxDimension || $height > $maxDimension) {
        return ['valid' => false, 'error' => 'Image dimensions too large'];
    }
    
    // Re-encode image to strip metadata (EXIF, etc.)
    $cleanImage = reencodeImage($file['tmp_name'], $imageInfo[2]);
    if (!$cleanImage) {
        return ['valid' => false, 'error' => 'Failed to process image'];
    }
    
    return ['valid' => true];
}

/**
 * Re-encode image to remove metadata
 */
function reencodeImage($filepath, $imageType) {
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($filepath);
            if ($image) {
                imagejpeg($image, $filepath, 90);
                imagedestroy($image);
                return true;
            }
            break;
        
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($filepath);
            if ($image) {
                imagepng($image, $filepath, 9);
                imagedestroy($image);
                return true;
            }
            break;
        
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($filepath);
            if ($image) {
                imagegif($image, $filepath);
                imagedestroy($image);
                return true;
            }
            break;
    }
    
    return false;
}
```

---

## 🛡️ SECURITY HEADERS

### **Essential Security Headers**

```php
<?php
// File: /includes/security-headers.php

/**
 * Set all security headers
 */
function setSecurityHeaders() {
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // HTTPS only (HSTS)
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    setCSP();
    
    // Permissions policy
    setPermissionsPolicy();
}

/**
 * Content Security Policy
 */
function setCSP() {
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://js.stripe.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "img-src 'self' data: https:",
        "font-src 'self' https://fonts.gstatic.com",
        "connect-src 'self' https://vpn.the-truth-publishing.com https://api.paypal.com",
        "frame-src 'self' https://js.stripe.com https://www.paypal.com",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "upgrade-insecure-requests"
    ];
    
    header('Content-Security-Policy: ' . implode('; ', $csp));
}

/**
 * Permissions Policy
 */
function setPermissionsPolicy() {
    $policy = [
        'geolocation=()',
        'microphone=()',
        'camera=()',
        'payment=(self)',
        'usb=()',
        'magnetometer=()',
        'gyroscope=()',
        'accelerometer=()'
    ];
    
    header('Permissions-Policy: ' . implode(', ', $policy));
}

/**
 * Set cache control headers
 */
function setCacheHeaders($type = 'no-cache') {
    switch ($type) {
        case 'no-cache':
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            break;
        
        case 'public':
            header('Cache-Control: public, max-age=3600');
            break;
        
        case 'private':
            header('Cache-Control: private, max-age=3600');
            break;
    }
}
```

---

## ⏱️ RATE LIMITING (DETAILED)

### **Advanced Rate Limiting**

```php
<?php
// File: /includes/rate-limiting.php

class RateLimiter {
    
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = sys_get_temp_dir() . '/rate_limit/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Check rate limit
     */
    public function check($identifier, $maxRequests, $windowSeconds) {
        $cacheFile = $this->cacheDir . md5($identifier) . '.txt';
        
        // Load request history
        $requests = [];
        if (file_exists($cacheFile)) {
            $requests = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        // Filter to current window
        $windowStart = time() - $windowSeconds;
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Check if exceeded
        if (count($requests) >= $maxRequests) {
            $oldestRequest = min($requests);
            $resetTime = $oldestRequest + $windowSeconds;
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset' => $resetTime,
                'retry_after' => $resetTime - time()
            ];
        }
        
        // Add current request
        $requests[] = time();
        file_put_contents($cacheFile, json_encode($requests));
        
        return [
            'allowed' => true,
            'remaining' => $maxRequests - count($requests),
            'reset' => time() + $windowSeconds,
            'retry_after' => 0
        ];
    }
    
    /**
     * Set rate limit headers
     */
    public function setHeaders($result, $maxRequests) {
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset']);
        
        if (!$result['allowed']) {
            header('Retry-After: ' . $result['retry_after']);
        }
    }
    
    /**
     * Enforce rate limit
     */
    public function enforce($identifier, $maxRequests, $windowSeconds) {
        $result = $this->check($identifier, $maxRequests, $windowSeconds);
        $this->setHeaders($result, $maxRequests);
        
        if (!$result['allowed']) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'code' => 'SYS_9003',
                'retry_after' => $result['retry_after']
            ]);
            exit;
        }
    }
}

/**
 * Rate limit by user
 */
function rateLimitUser($user) {
    $limits = [
        'standard' => ['requests' => 100, 'window' => 60],
        'pro' => ['requests' => 200, 'window' => 60],
        'vip' => ['requests' => 1000, 'window' => 60],
        'admin' => ['requests' => 999999, 'window' => 60]
    ];
    
    $limit = $limits[$user['tier']] ?? $limits['standard'];
    
    $rateLimiter = new RateLimiter();
    $rateLimiter->enforce(
        'user_' . $user['id'],
        $limit['requests'],
        $limit['window']
    );
}

/**
 * Rate limit by IP
 */
function rateLimitIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $rateLimiter = new RateLimiter();
    $rateLimiter->enforce(
        'ip_' . $ip,
        300,  // 300 requests
        60    // per minute
    );
}

/**
 * Rate limit sensitive actions
 */
function rateLimitSensitiveAction($action, $identifier) {
    $limits = [
        'login' => ['requests' => 5, 'window' => 300],  // 5 per 5 minutes
        'register' => ['requests' => 3, 'window' => 3600],  // 3 per hour
        'password_reset' => ['requests' => 3, 'window' => 3600],
        'device_create' => ['requests' => 10, 'window' => 300]
    ];
    
    $limit = $limits[$action] ?? ['requests' => 10, 'window' => 60];
    
    $rateLimiter = new RateLimiter();
    $rateLimiter->enforce(
        $action . '_' . $identifier,
        $limit['requests'],
        $limit['window']
    );
}
```

---

## 📊 LOGGING & MONITORING

### **Security Event Logging**

```php
<?php
// File: /includes/security-logging.php

class SecurityLogger {
    
    private $logDir;
    
    public function __construct() {
        $this->logDir = __DIR__ . '/../logs/';
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Log security event
     */
    public function log($event, $severity, $details = []) {
        $logFile = $this->logDir . 'security.log';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'severity' => $severity,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logMessage = json_encode($entry) . "\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // Alert on critical events
        if ($severity === 'critical') {
            $this->alert($event, $details);
        }
    }
    
    /**
     * Send alert for critical events
     */
    private function alert($event, $details) {
        // Send email to admin
        $to = 'admin@vpn.the-truth-publishing.com';
        $subject = 'SECURITY ALERT: ' . $event;
        $message = "Security event detected:\n\n";
        $message .= "Event: $event\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $message .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $message .= "Details: " . json_encode($details) . "\n";
        
        mail($to, $subject, $message);
    }
}

/**
 * Log failed login
 */
function logFailedLogin($email, $ip) {
    $logger = new SecurityLogger();
    $logger->log('failed_login', 'warning', [
        'email' => $email,
        'ip' => $ip
    ]);
}

/**
 * Log successful login
 */
function logSuccessfulLogin($userId, $ip) {
    $logger = new SecurityLogger();
    $logger->log('successful_login', 'info', [
        'user_id' => $userId,
        'ip' => $ip
    ]);
}

/**
 * Log suspicious activity
 */
function logSuspiciousActivity($activity, $details = []) {
    $logger = new SecurityLogger();
    $logger->log($activity, 'critical', $details);
}

/**
 * Log data access
 */
function logDataAccess($userId, $resource) {
    $logger = new SecurityLogger();
    $logger->log('data_access', 'info', [
        'user_id' => $userId,
        'resource' => $resource
    ]);
}
```

### **Activity Monitoring**

```php
<?php
/**
 * Monitor for suspicious patterns
 */
function monitorActivity($user) {
    // Check for rapid requests
    $requestCount = countRecentRequests($user['id'], 60);
    if ($requestCount > 50) {
        logSuspiciousActivity('rapid_requests', [
            'user_id' => $user['id'],
            'count' => $requestCount
        ]);
    }
    
    // Check for multiple failed logins
    $failedLogins = countFailedLogins($user['email'], 300);
    if ($failedLogins > 3) {
        logSuspiciousActivity('multiple_failed_logins', [
            'email' => $user['email'],
            'count' => $failedLogins
        ]);
    }
    
    // Check for unusual access patterns
    if (isUnusualAccessPattern($user)) {
        logSuspiciousActivity('unusual_access_pattern', [
            'user_id' => $user['id']
        ]);
    }
}
```

---

## 🔐 SSL/TLS CONFIGURATION

### **Apache SSL Configuration**

```apache
# File: /etc/apache2/sites-available/vpn.conf

<VirtualHost *:443>
    ServerName vpn.the-truth-publishing.com
    DocumentRoot /home/user/public_html/vpn.the-truth-publishing.com
    
    # SSL Engine
    SSLEngine on
    
    # Certificates
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/chain.crt
    
    # SSL Protocol
    SSLProtocol -all +TLSv1.2 +TLSv1.3
    
    # SSL Cipher Suite
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder on
    
    # HSTS
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    
    # OCSP Stapling
    SSLUseStapling on
    SSLStaplingCache "shmcb:logs/ssl_stapling(32768)"
    
    # Security Headers
    Header always set X-Frame-Options "DENY"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName vpn.the-truth-publishing.com
    Redirect permanent / https://vpn.the-truth-publishing.com/
</VirtualHost>
```

### **Let's Encrypt SSL**

```bash
#!/bin/bash
# Install Certbot
apt-get install certbot python3-certbot-apache

# Obtain certificate
certbot --apache -d vpn.the-truth-publishing.com

# Auto-renewal (add to cron)
certbot renew --quiet
```

---

**END OF SECTION 14: SECURITY (Part 2/3)**

**Status:** In Progress (66% Complete)  
**Next:** Part 3 will cover WireGuard Security, Vulnerability Prevention, Security Checklist  
**Lines:** ~1,200 lines  
**Created:** January 15, 2026 - 7:00 AM CST
