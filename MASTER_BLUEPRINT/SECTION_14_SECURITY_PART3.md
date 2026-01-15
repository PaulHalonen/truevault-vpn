# SECTION 14: SECURITY (Part 3/3)

**Created:** January 15, 2026  
**Status:** Complete Security Specification  
**Continuation of:** SECTION_14_SECURITY_PART2.md  

---

## 🔒 WIREGUARD SECURITY

### **Key Management**

```php
<?php
// File: /includes/wireguard-security.php

/**
 * Generate WireGuard key pair securely
 */
function generateWireGuardKeys() {
    // Generate private key
    $privateKey = exec('wg genkey');
    
    // Derive public key
    $publicKey = exec('echo ' . escapeshellarg($privateKey) . ' | wg pubkey');
    
    return [
        'private_key' => trim($private Key),
        'public_key' => trim($publicKey)
    ];
}

/**
 * Validate WireGuard key format
 */
function validateWireGuardKey($key) {
    // WireGuard keys are base64 encoded 32 bytes = 44 characters
    if (strlen($key) !== 44) {
        return false;
    }
    
    // Check base64 format
    if (!preg_match('/^[A-Za-z0-9+\/]{43}=$/', $key)) {
        return false;
    }
    
    return true;
}

/**
 * Securely store private key
 */
function storePrivateKey($deviceId, $privateKey) {
    global $db_devices;
    
    // Encrypt private key before storage
    $encryptedKey = encryptData($privateKey);
    
    $stmt = $db_devices->prepare("
        UPDATE devices 
        SET private_key = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$encryptedKey, $deviceId]);
}

/**
 * Retrieve and decrypt private key
 */
function getPrivateKey($deviceId) {
    global $db_devices;
    
    $stmt = $db_devices->prepare("
        SELECT private_key FROM devices WHERE id = ?
    ");
    $stmt->execute([$deviceId]);
    
    $encryptedKey = $stmt->fetchColumn();
    
    // Decrypt private key
    return decryptData($encryptedKey);
}

/**
 * Rotate WireGuard keys
 */
function rotateWireGuardKeys($deviceId) {
    global $db_devices;
    
    // Generate new keys
    $newKeys = generateWireGuardKeys();
    
    // Get device info
    $device = getDevice($deviceId);
    
    // Remove old peer from server
    removePeerFromServer($device['current_server_id'], $device['public_key']);
    
    // Store new keys
    $encryptedPrivateKey = encryptData($newKeys['private_key']);
    
    $stmt = $db_devices->prepare("
        UPDATE devices 
        SET public_key = ?, private_key = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $newKeys['public_key'],
        $encryptedPrivateKey,
        $deviceId
    ]);
    
    // Add new peer to server
    addPeerToServer(
        $device['current_server_id'],
        $newKeys['public_key'],
        $device['vpn_ip']
    );
    
    // Log key rotation
    logSecurityEvent('wireguard_key_rotation', [
        'device_id' => $deviceId,
        'old_public_key' => substr($device['public_key'], 0, 10) . '...'
    ]);
    
    return $newKeys;
}
```

### **WireGuard Server Configuration Security**

```bash
#!/bin/bash
# File: /server-scripts/secure-wireguard.sh

# Set proper permissions on WireGuard config
chmod 600 /etc/wireguard/wg0.conf
chown root:root /etc/wireguard/wg0.conf

# Set proper permissions on private key
chmod 600 /etc/wireguard/privatekey
chown root:root /etc/wireguard/privatekey

# Enable IP forwarding securely
sysctl -w net.ipv4.ip_forward=1
sysctl -w net.ipv6.conf.all.forwarding=1

# Configure firewall
ufw allow 51820/udp
ufw enable

# Disable unused network services
systemctl disable bluetooth
systemctl disable avahi-daemon

# Enable kernel security features
sysctl -w kernel.dmesg_restrict=1
sysctl -w kernel.kptr_restrict=2
sysctl -w net.ipv4.conf.all.log_martians=1
sysctl -w net.ipv4.conf.all.rp_filter=1
```

### **WireGuard Traffic Security**

```conf
# /etc/wireguard/wg0.conf

[Interface]
PrivateKey = SERVER_PRIVATE_KEY
Address = 10.8.0.1/24
ListenPort = 51820

# Security settings
MTU = 1420
Table = auto

# Post-up rules (iptables firewall)
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT
PostUp = iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostUp = ip6tables -A FORWARD -i wg0 -j ACCEPT
PostUp = ip6tables -t nat -A POSTROUTING -o eth0 -j MASQUERADE

# Post-down rules (cleanup)
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT
PostDown = iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
PostDown = ip6tables -D FORWARD -i wg0 -j ACCEPT
PostDown = ip6tables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Peer template
[Peer]
PublicKey = CLIENT_PUBLIC_KEY
AllowedIPs = 10.8.0.15/32
PersistentKeepalive = 25
```

---

## 🛡️ VULNERABILITY PREVENTION

### **Common Vulnerabilities**

**1. SQL Injection**
```php
<?php
// ✅ SAFE - Use prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// ❌ VULNERABLE - Never concatenate user input
$query = "SELECT * FROM users WHERE email = '$email'";
```

**2. Cross-Site Scripting (XSS)**
```php
<?php
// ✅ SAFE - Escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ❌ VULNERABLE - Raw output
echo $userInput;
```

**3. Cross-Site Request Forgery (CSRF)**
```php
<?php
// ✅ SAFE - Verify CSRF token
requireCSRFToken();

// ❌ VULNERABLE - No CSRF protection
```

**4. Session Fixation**
```php
<?php
// ✅ SAFE - Regenerate session ID on login
session_regenerate_id(true);

// ❌ VULNERABLE - Reuse session ID
```

**5. Insecure Deserialization**
```php
<?php
// ✅ SAFE - Use JSON
$data = json_decode($input, true);

// ❌ VULNERABLE - Unserialize user input
$data = unserialize($input);
```

**6. Directory Traversal**
```php
<?php
// ✅ SAFE - Validate and sanitize path
$file = basename($userInput);
$path = '/var/www/uploads/' . $file;

// ❌ VULNERABLE - Direct path usage
$path = '/var/www/uploads/' . $userInput;
```

**7. Command Injection**
```php
<?php
// ✅ SAFE - Use escapeshellarg
$safe = escapeshellarg($userInput);
exec("command " . $safe);

// ❌ VULNERABLE - Raw user input in command
exec("command " . $userInput);
```

**8. Insecure Direct Object References**
```php
<?php
// ✅ SAFE - Verify ownership
$device = getDevice($_GET['id']);
if ($device['user_id'] !== $currentUser['id']) {
    die('Access denied');
}

// ❌ VULNERABLE - No authorization check
$device = getDevice($_GET['id']);
```

**9. XML External Entity (XXE)**
```php
<?php
// ✅ SAFE - Disable external entities
libxml_disable_entity_loader(true);
$xml = simplexml_load_string($input);

// ❌ VULNERABLE - Default XML parsing
$xml = simplexml_load_string($input);
```

**10. Insecure File Upload**
```php
<?php
// ✅ SAFE - Validate file type and content
$validation = validateUpload($_FILES['file']);
if ($validation['valid']) {
    saveUploadedFile($_FILES['file']);
}

// ❌ VULNERABLE - No validation
move_uploaded_file($_FILES['file']['tmp_name'], '/uploads/' . $_FILES['file']['name']);
```

### **Security Scanning**

```bash
#!/bin/bash
# File: /server-scripts/security-scan.sh

echo "Running security scans..."

# Check for common vulnerabilities
echo "1. Checking file permissions..."
find /var/www -type f -perm 777 -ls

# Check for exposed sensitive files
echo "2. Checking for sensitive files..."
find /var/www -name ".env" -o -name "config.php" -ls

# Check for outdated software
echo "3. Checking for outdated packages..."
apt list --upgradable

# Check for open ports
echo "4. Checking open ports..."
netstat -tulpn

# Check for failed login attempts
echo "5. Checking failed logins..."
grep "Failed password" /var/log/auth.log | tail -20

# Check SSL certificate expiration
echo "6. Checking SSL certificate..."
echo | openssl s_client -connect vpn.the-truth-publishing.com:443 2>/dev/null | openssl x509 -noout -dates

echo "Security scan complete!"
```

---

## ✅ SECURITY CHECKLIST

### **Pre-Launch Security Checklist**

```markdown
# TrueVault VPN - Security Checklist

## Infrastructure
- [ ] HTTPS enabled on all pages
- [ ] Valid SSL certificate installed
- [ ] HSTS header enabled
- [ ] HTTP redirects to HTTPS
- [ ] Firewall configured (only ports 80, 443, 51820)
- [ ] SSH key-only authentication
- [ ] Root login disabled
- [ ] Fail2ban installed and configured
- [ ] Server software up to date
- [ ] Automatic security updates enabled

## Application
- [ ] All passwords hashed with bcrypt (cost 12+)
- [ ] CSRF protection on all forms
- [ ] XSS protection (output escaping)
- [ ] SQL injection prevention (prepared statements)
- [ ] Input validation on all fields
- [ ] Rate limiting on all endpoints
- [ ] Session security (httponly, secure, samesite)
- [ ] Security headers configured
- [ ] Error messages don't leak information
- [ ] Debug mode disabled in production

## Authentication
- [ ] Strong password requirements enforced
- [ ] Account lockout after failed attempts
- [ ] Password reset tokens expire
- [ ] JWT tokens expire (30 days max)
- [ ] Session timeout (30 minutes)
- [ ] 2FA available (optional)
- [ ] Login attempts logged
- [ ] Suspicious activity monitored

## Data Protection
- [ ] Sensitive data encrypted at rest
- [ ] WireGuard private keys encrypted
- [ ] Database backups encrypted
- [ ] Payment data never stored
- [ ] User data minimization
- [ ] Data retention policy implemented
- [ ] GDPR compliance (if EU users)
- [ ] Privacy policy published

## WireGuard
- [ ] Strong encryption (ChaCha20-Poly1305)
- [ ] Unique keys per device
- [ ] Key rotation capability
- [ ] Server configs secured (chmod 600)
- [ ] Unused peers removed
- [ ] VPN kill switch available
- [ ] DNS leak prevention
- [ ] IPv6 leak prevention

## API Security
- [ ] All endpoints require authentication
- [ ] Rate limiting per user tier
- [ ] CORS configured correctly
- [ ] API versioning implemented
- [ ] Request signing for sensitive operations
- [ ] Webhooks verified (PayPal)
- [ ] API documentation secured
- [ ] Error responses sanitized

## File Upload
- [ ] File type validation
- [ ] File size limits
- [ ] MIME type verification
- [ ] File content scanning
- [ ] Secure filename generation
- [ ] Directory traversal prevention
- [ ] Upload directory outside webroot
- [ ] Uploaded files served securely

## Monitoring & Logging
- [ ] Security events logged
- [ ] Failed logins logged
- [ ] Suspicious activity alerts
- [ ] Log rotation configured
- [ ] Logs protected (chmod 640)
- [ ] Real-time monitoring enabled
- [ ] Intrusion detection system
- [ ] Regular log review process

## Backups
- [ ] Daily database backups
- [ ] Backup encryption
- [ ] Off-site backup storage
- [ ] Backup restoration tested
- [ ] Backup retention policy
- [ ] Configuration backups
- [ ] WireGuard configs backed up
- [ ] Backup integrity verified

## Compliance
- [ ] Terms of service published
- [ ] Privacy policy published
- [ ] GDPR data processing agreement
- [ ] Data breach notification plan
- [ ] Security incident response plan
- [ ] User data deletion process
- [ ] Data portability implemented
- [ ] Cookie consent banner

## Testing
- [ ] Penetration testing completed
- [ ] Vulnerability scan passed
- [ ] Security code review done
- [ ] XSS testing passed
- [ ] SQL injection testing passed
- [ ] CSRF testing passed
- [ ] Authentication testing passed
- [ ] Authorization testing passed

## Documentation
- [ ] Security procedures documented
- [ ] Incident response plan documented
- [ ] Admin passwords stored securely
- [ ] Emergency contacts documented
- [ ] Disaster recovery plan
- [ ] Security training for team
- [ ] Third-party security audit
- [ ] Bug bounty program considered
```

---

## 🧪 SECURITY TESTING

### **Automated Security Testing**

```php
<?php
// File: /tests/SecurityTest.php

class SecurityTest {
    
    /**
     * Test SQL injection prevention
     */
    public function testSQLInjection() {
        $maliciousInput = "' OR '1'='1";
        
        $result = authenticateUser($maliciousInput, 'password');
        
        if ($result) {
            echo "❌ FAIL: SQL injection vulnerability!\n";
            return false;
        }
        
        echo "✅ PASS: SQL injection prevented\n";
        return true;
    }
    
    /**
     * Test XSS prevention
     */
    public function testXSS() {
        $maliciousInput = "<script>alert('XSS')</script>";
        
        $output = escapeHTML($maliciousInput);
        
        if (strpos($output, '<script>') !== false) {
            echo "❌ FAIL: XSS vulnerability!\n";
            return false;
        }
        
        echo "✅ PASS: XSS prevented\n";
        return true;
    }
    
    /**
     * Test CSRF protection
     */
    public function testCSRF() {
        $_SESSION['csrf_token'] = 'valid_token';
        
        // Test with invalid token
        $result = verifyCSRFToken('invalid_token');
        
        if ($result) {
            echo "❌ FAIL: CSRF vulnerability!\n";
            return false;
        }
        
        echo "✅ PASS: CSRF protection working\n";
        return true;
    }
    
    /**
     * Test password hashing
     */
    public function testPasswordHashing() {
        $password = 'TestPassword123!';
        $hash = hashPassword($password);
        
        // Check hash format
        if (!password_get_info($hash)['algo']) {
            echo "❌ FAIL: Password not hashed!\n";
            return false;
        }
        
        // Check plaintext not stored
        if ($hash === $password) {
            echo "❌ FAIL: Password stored in plaintext!\n";
            return false;
        }
        
        echo "✅ PASS: Password hashing secure\n";
        return true;
    }
    
    /**
     * Test rate limiting
     */
    public function testRateLimiting() {
        $rateLimiter = new RateLimiter();
        
        // Make 6 requests (limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $result = $rateLimiter->check('test', 5, 60);
            
            if ($i < 5 && !$result['allowed']) {
                echo "❌ FAIL: Rate limit too strict!\n";
                return false;
            }
            
            if ($i >= 5 && $result['allowed']) {
                echo "❌ FAIL: Rate limit not enforced!\n";
                return false;
            }
        }
        
        echo "✅ PASS: Rate limiting working\n";
        return true;
    }
    
    /**
     * Test session security
     */
    public function testSessionSecurity() {
        startSecureSession();
        
        // Check session settings
        $params = session_get_cookie_params();
        
        if (!$params['secure']) {
            echo "❌ FAIL: Session not secure!\n";
            return false;
        }
        
        if (!$params['httponly']) {
            echo "❌ FAIL: Session accessible via JavaScript!\n";
            return false;
        }
        
        echo "✅ PASS: Session security configured\n";
        return true;
    }
    
    /**
     * Run all tests
     */
    public function runAll() {
        echo "Running security tests...\n\n";
        
        $tests = [
            'testSQLInjection',
            'testXSS',
            'testCSRF',
            'testPasswordHashing',
            'testRateLimiting',
            'testSessionSecurity'
        ];
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            if ($this->$test()) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "RESULTS: $passed passed, $failed failed\n";
        echo str_repeat('=', 50) . "\n";
        
        return $failed === 0;
    }
}

// Run tests
$test = new SecurityTest();
$test->runAll();
```

---

## 🚨 INCIDENT RESPONSE

### **Security Incident Response Plan**

```markdown
# Security Incident Response Plan

## 1. DETECTION
- Monitor security logs daily
- Set up alerts for suspicious activity
- Review failed login attempts
- Check for unusual API usage
- Monitor server resources

## 2. ASSESSMENT
- Determine severity (low/medium/high/critical)
- Identify affected systems
- Document initial findings
- Estimate impact on users
- Preserve evidence

## 3. CONTAINMENT
- Isolate affected systems
- Block malicious IPs
- Disable compromised accounts
- Rotate compromised keys
- Implement temporary access restrictions

## 4. ERADICATION
- Remove malware/backdoors
- Patch vulnerabilities
- Update passwords/keys
- Review and fix security gaps
- Verify all systems clean

## 5. RECOVERY
- Restore from clean backups
- Verify system integrity
- Monitor for re-infection
- Gradually restore services
- Communicate with users

## 6. LESSONS LEARNED
- Document incident timeline
- Analyze root cause
- Update security procedures
- Implement preventive measures
- Train team on lessons learned

## CONTACT INFORMATION
- Admin Email: admin@vpn.the-truth-publishing.com
- Emergency Phone: [REDACTED]
- Hosting Support: GoDaddy (account 26853687)
- Server Provider: Contabo (paulhalonen@gmail.com)
```

### **Breach Notification Template**

```php
<?php
/**
 * Send security breach notification
 */
function notifySecurityBreach($affectedUsers, $breachDetails) {
    $subject = 'Important Security Notice';
    
    $message = "
Dear Valued Customer,

We are writing to inform you of a security incident that may have affected your account.

WHAT HAPPENED:
{$breachDetails['what_happened']}

WHAT INFORMATION WAS INVOLVED:
{$breachDetails['data_involved']}

WHAT WE ARE DOING:
{$breachDetails['our_actions']}

WHAT YOU SHOULD DO:
1. Change your password immediately
2. Enable two-factor authentication
3. Monitor your account for suspicious activity
4. Contact us if you notice anything unusual

We sincerely apologize for this incident and are committed to protecting your information.

Contact us: support@vpn.the-truth-publishing.com

Best regards,
TrueVault VPN Security Team
    ";
    
    foreach ($affectedUsers as $user) {
        mail($user['email'], $subject, $message);
    }
    
    // Log notification
    logSecurityEvent('breach_notification_sent', [
        'affected_users' => count($affectedUsers),
        'breach_type' => $breachDetails['type']
    ]);
}
```

---

## 📚 SECURITY RESOURCES

### **Recommended Security Tools**

1. **Fail2ban** - Intrusion prevention
2. **ModSecurity** - Web application firewall
3. **OSSEC** - Host intrusion detection
4. **ClamAV** - Antivirus scanning
5. **Lynis** - Security auditing
6. **Nmap** - Port scanning
7. **Wireshark** - Network analysis
8. **OWASP ZAP** - Penetration testing

### **Security Best Practices**

1. **Defense in Depth** - Multiple security layers
2. **Least Privilege** - Minimum necessary permissions
3. **Fail Securely** - Default to secure state
4. **Zero Trust** - Verify everything
5. **Regular Updates** - Keep software current
6. **Security by Design** - Build security in from start
7. **Continuous Monitoring** - Always watching
8. **Incident Preparedness** - Plan for breaches

### **Security Standards**

- **OWASP Top 10** - Common web vulnerabilities
- **CWE Top 25** - Software weaknesses
- **NIST Cybersecurity Framework** - Security best practices
- **PCI DSS** - Payment card data security
- **GDPR** - Data protection regulation
- **ISO 27001** - Information security management

---

## 🎯 SUMMARY

### **Key Security Measures**

✅ **Encryption Everywhere**
- HTTPS for all pages
- Database encryption at rest
- WireGuard for VPN traffic
- Encrypted password storage

✅ **Authentication Security**
- Strong password requirements
- Bcrypt hashing (cost 12)
- JWT tokens (30 day expiry)
- Brute force protection
- 2FA available

✅ **Input Validation**
- Validate all user input
- Prepared SQL statements
- Output escaping
- CSRF tokens
- File upload validation

✅ **Access Control**
- Session security
- Authorization checks
- Rate limiting
- IP monitoring
- Activity logging

✅ **WireGuard Security**
- Unique keys per device
- Encrypted key storage
- Key rotation capability
- Secure server config

✅ **Monitoring**
- Security event logging
- Failed login tracking
- Suspicious activity alerts
- Real-time monitoring
- Incident response plan

---

**END OF SECTION 14: SECURITY (Complete)**

**Status:** ✅ COMPLETE  
**Total Lines:** ~2,700 lines (Parts 1 + 2 + 3)  
**Created:** January 15, 2026 - 7:10 AM CST

**Features Covered:**
- Encryption standards (at rest & in transit)
- Authentication & password security
- Input validation & sanitization
- SQL injection prevention
- XSS & CSRF protection
- Session management
- API security
- File upload security
- Security headers
- Advanced rate limiting
- Logging & monitoring
- SSL/TLS configuration
- WireGuard security
- Vulnerability prevention
- Security testing
- Incident response
- Security checklist

**Next Section:** Section 15 (Error Handling) - THE FINAL SECTION!
