# MASTER CHECKLIST - PART 18: ENTERPRISE PORTAL (SIGNUP & LICENSE TRACKING ONLY)

**Created:** January 20, 2026 - 5:10 AM CST  
**Updated:** January 20, 2026 - Based on User Decision #4
**Status:** ⏳ NOT STARTED  
**Priority:** 🟡 MEDIUM - Sales portal for enterprise product  
**Estimated Time:** 2-3 hours  
**Estimated Lines:** ~400 lines  

---

## 📋 OVERVIEW

**CRITICAL CLARIFICATION:**
This is NOT the full Enterprise product build. This is ONLY the signup portal and license tracking interface that lives inside the TrueVault VPN dashboard.

**What This IS:**
- `/enterprise/` directory with signup page
- License purchase/activation system
- Download instructions for actual enterprise product
- License tracking in admin panel
- Inactive until customer purchases

**What This IS NOT:**
- Full enterprise product (HR, DataForge, etc.)
- Corporate VPN deployment
- Company management system

**The Actual Enterprise Product:**
- Separate codebase
- Deploys on client's own server
- Uses own license key
- Completely independent

**This Portal Just:**
- Sells licenses
- Provides download link
- Tracks active licenses
- Generates license keys

---

## 🎯 PURPOSE

**Customer Journey:**
1. Customer visits `/enterprise/` on TrueVault VPN
2. Sees enterprise product description
3. Signs up (purchases license)
4. Receives license key + download link
5. Downloads enterprise product zip
6. Deploys on their own server
7. Activates with license key

**TrueVault Tracks:**
- Who purchased
- License keys generated
- Activation status
- Renewal dates

---

## 💾 TASK 18.1: Create Enterprise Licenses Database

**Time:** 20 minutes  
**Lines:** ~80 lines  
**File:** `/databases/setup-licenses.php`

**Create licenses.db with 2 tables:**

```sql
-- TABLE 1: enterprise_licenses
CREATE TABLE IF NOT EXISTS enterprise_licenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,                -- FK to users in users.db
    license_key TEXT NOT NULL UNIQUE,        -- Generated key
    company_name TEXT NOT NULL,
    company_email TEXT NOT NULL,
    purchased_date TEXT DEFAULT CURRENT_TIMESTAMP,
    activation_date TEXT,
    expiration_date TEXT,                    -- Annual renewal
    status TEXT DEFAULT 'active',            -- active, expired, suspended
    max_seats INTEGER DEFAULT 5,             -- Included seats
    current_seats INTEGER DEFAULT 0,         -- Seats in use
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- TABLE 2: license_activations
CREATE TABLE IF NOT EXISTS license_activations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_id INTEGER NOT NULL,
    server_ip TEXT,
    server_hostname TEXT,
    activated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    last_checkin TEXT,
    version TEXT,
    FOREIGN KEY (license_id) REFERENCES enterprise_licenses(id)
);
```

**Seed Example Data:**
```sql
-- None needed (populated when customers purchase)
```

**Verification:**
- [ ] licenses.db created
- [ ] 2 tables exist
- [ ] Can insert test license

---

## 🔑 TASK 18.2: License Key Generator

**Time:** 30 minutes  
**Lines:** ~100 lines  
**File:** `/api/generate-license.php`

**Generate Unique License Keys:**

```php
<?php
// Generate license key format: TVPN-XXXX-XXXX-XXXX-XXXX

function generateLicenseKey() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No confusing chars
    $key = 'TVPN-';
    
    for ($i = 0; $i < 4; $i++) {
        for ($j = 0; $j < 4; $j++) {
            $key .= $chars[rand(0, strlen($chars) - 1)];
        }
        if ($i < 3) $key .= '-';
    }
    
    return $key; // e.g., TVPN-A3F7-9K2M-P4R6-X8Z3
}

// Generate and save license
function createLicense($user_id, $company_name, $company_email, $max_seats = 5) {
    global $db;
    
    $license_key = generateLicenseKey();
    
    // Check if key already exists (very rare)
    while ($db->licenseExists($license_key)) {
        $license_key = generateLicenseKey();
    }
    
    // Calculate expiration (1 year from now)
    $expiration = date('Y-m-d H:i:s', strtotime('+1 year'));
    
    $db->insert('enterprise_licenses', [
        'user_id' => $user_id,
        'license_key' => $license_key,
        'company_name' => $company_name,
        'company_email' => $company_email,
        'expiration_date' => $expiration,
        'max_seats' => $max_seats,
        'status' => 'active'
    ]);
    
    return $license_key;
}

// API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify admin
    requireAdmin();
    
    $license = createLicense(
        $_POST['user_id'],
        $_POST['company_name'],
        $_POST['company_email'],
        $_POST['max_seats'] ?? 5
    );
    
    echo json_encode(['success' => true, 'license_key' => $license]);
}
```

**Verification:**
- [ ] Can generate unique keys
- [ ] Keys are in correct format (TVPN-XXXX-XXXX-XXXX-XXXX)
- [ ] No duplicate keys
- [ ] Saved to database

---

## 🌐 TASK 18.3: Enterprise Portal Page

**Time:** 45 minutes  
**Lines:** ~150 lines  
**File:** `/enterprise/index.php`

**Simple Sales/Signup Page:**

```php
<?php
session_start();
require_once '../includes/db.php';

// Check if user logged in
$logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>TrueVault Enterprise - Corporate VPN & Business Tools</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div class="enterprise-hero">
    <h1>🏢 TrueVault Enterprise</h1>
    <p>Complete Business Platform: Corporate VPN, HR Management, Custom Databases</p>
    <p class="pricing">$79.97/month • Includes 5 seats • Additional seats $8/month</p>
</div>

<div class="enterprise-features">
    <h2>What You Get:</h2>
    <ul>
        <li>✅ Dedicated Corporate VPN Server</li>
        <li>✅ HR Management System (Employees, Departments, Time-Off)</li>
        <li>✅ DataForge Custom Database Builder (FileMaker Alternative)</li>
        <li>✅ 7-Tier Role-Based Access Control</li>
        <li>✅ Real-Time Collaboration (WebSocket)</li>
        <li>✅ React PWA (Web + Mobile)</li>
        <li>✅ Self-Hosted (Your Own Server)</li>
    </ul>
</div>

<div class="enterprise-cta">
    <?php if ($logged_in): ?>
        <button onclick="purchaseEnterprise()" class="btn-primary">
            Purchase Enterprise License - $79.97/month
        </button>
    <?php else: ?>
        <a href="/login.php" class="btn-primary">Sign In to Purchase</a>
    <?php endif; ?>
</div>

<div class="enterprise-faq">
    <h2>How It Works:</h2>
    <ol>
        <li>Purchase license ($79.97/month)</li>
        <li>Receive license key + download link</li>
        <li>Download enterprise product (zip file)</li>
        <li>Deploy on your own server (VPS, dedicated, etc.)</li>
        <li>Activate with license key</li>
        <li>Invite employees (up to 5 included)</li>
    </ol>
</div>

<script>
async function purchaseEnterprise() {
    // Simple purchase flow
    const company = prompt('Company Name:');
    const email = prompt('Company Email:');
    
    if (!company || !email) return;
    
    // Call PayPal subscription API
    const response = await fetch('/api/purchase-enterprise.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({company_name: company, company_email: email})
    });
    
    const data = await response.json();
    
    if (data.success) {
        alert('License Generated! Check your email for download link.');
        window.location.href = '/dashboard/enterprise-license.php';
    } else {
        alert('Error: ' + data.error);
    }
}
</script>

</body>
</html>
```

**Verification:**
- [ ] Page loads at `/enterprise/`
- [ ] Shows product description
- [ ] Purchase button works (logged in users only)
- [ ] Redirects to login if not logged in

---

## 💳 TASK 18.4: Purchase & License Delivery

**Time:** 30 minutes  
**Lines:** ~120 lines  
**File:** `/api/purchase-enterprise.php`

**Handle Enterprise License Purchase:**

```php
<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/paypal.php';

// Verify logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$company_name = $data['company_name'] ?? '';
$company_email = $data['company_email'] ?? '';

if (!$company_name || !$company_email) {
    echo json_encode(['success' => false, 'error' => 'Missing company info']);
    exit;
}

// Create PayPal subscription plan ($79.97/month)
$subscription = PayPal::createSubscription([
    'plan_id' => 'ENTERPRISE_PLAN_ID', // From PayPal dashboard
    'quantity' => 1,
    'user_id' => $_SESSION['user_id']
]);

if ($subscription['status'] === 'ACTIVE') {
    // Generate license
    $license_key = createLicense(
        $_SESSION['user_id'],
        $company_name,
        $company_email,
        5 // Default 5 seats
    );
    
    // Send email with license + download link
    $email_body = "
        <h1>Your TrueVault Enterprise License</h1>
        <p>Company: {$company_name}</p>
        <p><strong>License Key:</strong> {$license_key}</p>
        <p><strong>Download:</strong> <a href='https://vpn.the-truth-publishing.com/downloads/truevault-enterprise.zip'>Download Enterprise Product</a></p>
        <h2>Installation Instructions:</h2>
        <ol>
            <li>Download the zip file</li>
            <li>Upload to your server (VPS, dedicated, etc.)</li>
            <li>Run setup script</li>
            <li>Enter your license key when prompted</li>
            <li>Create your first admin account</li>
        </ol>
        <p>Support: admin@the-truth-publishing.com</p>
    ";
    
    sendEmail($company_email, 'Your TrueVault Enterprise License', $email_body);
    sendEmail('paulhalonen@gmail.com', "New Enterprise Customer: {$company_name}", $email_body);
    
    echo json_encode([
        'success' => true,
        'license_key' => $license_key,
        'download_url' => '/downloads/truevault-enterprise.zip'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Payment failed']);
}
```

**Verification:**
- [ ] PayPal subscription created
- [ ] License key generated
- [ ] Email sent with license + download link
- [ ] Admin notified

---

## 📊 TASK 18.5: License Management (Admin Panel)

**Time:** 30 minutes  
**Lines:** ~100 lines  
**File:** `/admin/enterprise-licenses.php`

**Admin Can View/Manage Licenses:**

```php
<?php
// Admin only
requireAdmin();

$licenses = $db->query("SELECT * FROM enterprise_licenses ORDER BY purchased_date DESC");
?>

<div class="admin-section">
    <h2>Enterprise Licenses</h2>
    
    <button onclick="createManualLicense()">+ Create Manual License</button>
    
    <table class="admin-table">
        <tr>
            <th>Company</th>
            <th>License Key</th>
            <th>Purchased</th>
            <th>Expires</th>
            <th>Status</th>
            <th>Seats</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($licenses as $lic): ?>
        <tr>
            <td><?= htmlspecialchars($lic['company_name']) ?></td>
            <td><code><?= $lic['license_key'] ?></code></td>
            <td><?= date('M j, Y', strtotime($lic['purchased_date'])) ?></td>
            <td><?= date('M j, Y', strtotime($lic['expiration_date'])) ?></td>
            <td>
                <span class="status-<?= $lic['status'] ?>">
                    <?= ucfirst($lic['status']) ?>
                </span>
            </td>
            <td><?= $lic['current_seats'] ?> / <?= $lic['max_seats'] ?></td>
            <td>
                <button onclick="editLicense(<?= $lic['id'] ?>)">Edit</button>
                <button onclick="suspendLicense(<?= $lic['id'] ?>)">Suspend</button>
                <button onclick="resendEmail(<?= $lic['id'] ?>)">Resend Email</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
function createManualLicense() {
    const company = prompt('Company Name:');
    const email = prompt('Company Email:');
    const seats = prompt('Max Seats:', '5');
    
    if (!company || !email) return;
    
    fetch('/api/generate-license.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({company_name: company, company_email: email, max_seats: seats})
    }).then(r => r.json()).then(data => {
        alert('License Created: ' + data.license_key);
        location.reload();
    });
}
</script>
```

**Verification:**
- [ ] Admin can view all licenses
- [ ] Can create manual licenses
- [ ] Can suspend licenses
- [ ] Can resend emails

---

## 📂 TASK 18.6: Download File Placeholder

**Time:** 10 minutes  
**Lines:** ~20 lines  
**File:** `/downloads/truevault-enterprise.zip`

**Create Placeholder Download:**

For now, just create a placeholder zip that says:

```
TRUEVAULT ENTERPRISE PRODUCT

This is a placeholder for the actual enterprise product.

The full enterprise product will be built separately and includes:
- Corporate VPN
- HR Management
- DataForge Database Builder
- Role-Based Access Control
- Real-Time Sync

This is distributed as a separate build that deploys on client servers.
```

**Verification:**
- [ ] File exists at `/downloads/truevault-enterprise.zip`
- [ ] Can be downloaded
- [ ] Contains placeholder text

---

## ✅ FINAL VERIFICATION - PART 18

**Portal:**
- [ ] `/enterprise/` page loads
- [ ] Product description clear
- [ ] Purchase button works
- [ ] Redirects to login if needed

**License System:**
- [ ] License keys generated correctly
- [ ] Format is TVPN-XXXX-XXXX-XXXX-XXXX
- [ ] Saved to database
- [ ] Email sent with license + download

**Admin Panel:**
- [ ] Can view all licenses
- [ ] Can create manual licenses
- [ ] Can suspend/reactivate
- [ ] Can resend emails

**Download:**
- [ ] Placeholder zip exists
- [ ] Can be downloaded
- [ ] Contains instructions

---

## 📊 TIME ESTIMATE

**Part 18 Total:** 2-3 hours (was 8-10 hours)

**Breakdown:**
- Task 18.1: Database (20 min)
- Task 18.2: License Generator (30 min)
- Task 18.3: Portal Page (45 min)
- Task 18.4: Purchase Flow (30 min)
- Task 18.5: Admin Panel (30 min)
- Task 18.6: Download Placeholder (10 min)

**Updated Total Project:** 165-200 hours → **159-194 hours** (saved 6 hours!)

---

## 🎯 SUMMARY

**This Part 18 IS:**
- ✅ Simple signup portal
- ✅ License generation & tracking
- ✅ Download delivery
- ✅ Admin management

**This Part 18 IS NOT:**
- ❌ Full enterprise product
- ❌ HR management
- ❌ DataForge builder
- ❌ Corporate VPN deployment

**The Actual Enterprise Product:**
- Separate codebase (built later)
- Deploys on client's server
- Uses license key for activation
- Independent from VPN dashboard

**This portal just:**
- Sells it
- Delivers it
- Tracks it

**DONE!** ✅

