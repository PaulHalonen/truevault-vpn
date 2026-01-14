# TRUEVAULT VPN - TRANSFER-READY BUSINESS SETTINGS
## Owner Configuration & Business Transfer System
**Created:** January 14, 2026 - 1:45 AM CST
**Purpose:** Enable 100% headache-free ownership transfer

---

# 🎯 THE GOAL

When you sell/transfer this VPN business:
1. New owner logs into admin
2. Changes PayPal credentials to their account
3. Changes bank account info
4. Changes business contact info
5. **Done.** Business is theirs.

No code changes. No file editing. No developer needed.

---

# 📋 WHAT NEEDS TO BE TRANSFERABLE

## Payment Settings
- PayPal Client ID
- PayPal Secret Key
- PayPal Webhook ID
- PayPal Mode (sandbox/live)

## Bank Account (for records/accounting)
- Bank Name
- Account Holder Name
- Account Number (encrypted)
- Routing Number (encrypted)
- Account Type (checking/savings)
- Bank Country

## Business Information
- Business Name (displayed on site)
- Business Legal Name (for invoices)
- Business Email (support contact)
- Business Phone (optional)
- Business Address (for invoices)
- Tax ID / EIN (optional, for invoices)

## Technical Settings (usually don't change)
- Site URL
- API URL
- Support Email
- Admin Email (for alerts)
- Timezone

---

# 🗄️ DATABASE SCHEMA

## Add to settings.db

```sql
-- Business settings (transferable)
CREATE TABLE IF NOT EXISTS business_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT NOT NULL,        -- 'paypal', 'bank', 'business', 'technical'
    setting_key TEXT NOT NULL,
    setting_value TEXT,            -- Encrypted for sensitive fields
    is_encrypted INTEGER DEFAULT 0,
    is_required INTEGER DEFAULT 0,
    display_name TEXT,             -- Human-readable name for admin UI
    description TEXT,              -- Help text for admin UI
    display_order INTEGER DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER,            -- Admin user who changed it
    UNIQUE(category, setting_key)
);

-- Settings change history (audit trail)
CREATE TABLE IF NOT EXISTS settings_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_id INTEGER NOT NULL,
    old_value TEXT,                -- Encrypted if sensitive
    new_value TEXT,                -- Encrypted if sensitive
    changed_by INTEGER NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT,
    FOREIGN KEY (setting_id) REFERENCES business_settings(id)
);

-- Insert default settings structure
INSERT OR IGNORE INTO business_settings (category, setting_key, setting_value, is_encrypted, is_required, display_name, description, display_order) VALUES
-- PayPal Settings
('paypal', 'client_id', '', 0, 1, 'PayPal Client ID', 'From PayPal Developer Dashboard', 1),
('paypal', 'secret_key', '', 1, 1, 'PayPal Secret Key', 'Keep this secure - it will be encrypted', 2),
('paypal', 'webhook_id', '', 0, 1, 'PayPal Webhook ID', 'For payment notifications', 3),
('paypal', 'mode', 'sandbox', 0, 1, 'PayPal Mode', 'sandbox for testing, live for production', 4),
('paypal', 'webhook_url', '', 0, 0, 'Webhook URL', 'Auto-generated based on site URL', 5),

-- Bank Account Settings
('bank', 'bank_name', '', 0, 0, 'Bank Name', 'e.g., Chase, Bank of America', 1),
('bank', 'account_holder', '', 0, 0, 'Account Holder Name', 'Name on the bank account', 2),
('bank', 'account_number', '', 1, 0, 'Account Number', 'Will be encrypted', 3),
('bank', 'routing_number', '', 1, 0, 'Routing Number', 'Will be encrypted', 4),
('bank', 'account_type', 'checking', 0, 0, 'Account Type', 'checking or savings', 5),
('bank', 'bank_country', 'USA', 0, 0, 'Bank Country', 'Country where bank is located', 6),
('bank', 'swift_code', '', 0, 0, 'SWIFT/BIC Code', 'For international transfers', 7),

-- Business Information
('business', 'business_name', 'TrueVault VPN', 0, 1, 'Business Name', 'Displayed on website', 1),
('business', 'legal_name', '', 0, 0, 'Legal Business Name', 'For invoices and legal documents', 2),
('business', 'contact_email', '', 0, 1, 'Contact Email', 'Displayed for customer support', 3),
('business', 'support_email', '', 0, 0, 'Support Email', 'Where support tickets are sent', 4),
('business', 'contact_phone', '', 0, 0, 'Contact Phone', 'Optional - displayed on site', 5),
('business', 'address_line1', '', 0, 0, 'Address Line 1', 'Street address', 6),
('business', 'address_line2', '', 0, 0, 'Address Line 2', 'Suite, unit, etc.', 7),
('business', 'city', '', 0, 0, 'City', '', 8),
('business', 'state', '', 0, 0, 'State/Province', '', 9),
('business', 'postal_code', '', 0, 0, 'Postal/ZIP Code', '', 10),
('business', 'country', 'USA', 0, 0, 'Country', '', 11),
('business', 'tax_id', '', 1, 0, 'Tax ID / EIN', 'For invoices - will be encrypted', 12),

-- Technical Settings
('technical', 'site_url', 'https://vpn.the-truth-publishing.com', 0, 1, 'Site URL', 'Main website URL', 1),
('technical', 'api_url', 'https://vpn.the-truth-publishing.com/api', 0, 1, 'API URL', 'API endpoint URL', 2),
('technical', 'admin_email', '', 0, 1, 'Admin Alert Email', 'Where system alerts are sent', 3),
('technical', 'timezone', 'America/Chicago', 0, 1, 'Timezone', 'For scheduling and logs', 4),
('technical', 'currency', 'USD', 0, 1, 'Currency', 'USD, CAD, EUR, etc.', 5),
('technical', 'currency_symbol', '$', 0, 1, 'Currency Symbol', '$, C$, €, etc.', 6);
```

---

# 🖥️ ADMIN UI DESIGN

## New Admin Page: /admin/business.html

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  🛡️ TrueVault Admin                                              [Logout]  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ← Back to Dashboard                                                         │
│                                                                              │
│  BUSINESS SETTINGS                                                           │
│  Configure payment, banking, and business information                        │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────────┐│
│  │  💳 PAYPAL SETTINGS                                           [Edit]   ││
│  ├─────────────────────────────────────────────────────────────────────────┤│
│  │                                                                          ││
│  │  Client ID:      ActD2XQKe8EkUNI8eZa••••••••••••••••••••                ││
│  │  Secret Key:     ••••••••••••••••••••••••••••••••••••••••                ││
│  │  Webhook ID:     46924926WL757580D                                       ││
│  │  Mode:           🟢 Live                                                 ││
│  │                                                                          ││
│  │  ⚠️ Changing PayPal settings will affect all future payments.           ││
│  │                                                                          ││
│  └─────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────────┐│
│  │  🏦 BANK ACCOUNT                                              [Edit]   ││
│  ├─────────────────────────────────────────────────────────────────────────┤│
│  │                                                                          ││
│  │  Bank Name:      Chase Bank                                              ││
│  │  Account Holder: TrueVault LLC                                           ││
│  │  Account Number: ••••••••4521                                            ││
│  │  Routing Number: ••••••789                                               ││
│  │  Account Type:   Checking                                                ││
│  │  Country:        USA                                                     ││
│  │                                                                          ││
│  │  ℹ️ Bank info is for your records only. PayPal handles all payments.    ││
│  │                                                                          ││
│  └─────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────────┐│
│  │  🏢 BUSINESS INFORMATION                                      [Edit]   ││
│  ├─────────────────────────────────────────────────────────────────────────┤│
│  │                                                                          ││
│  │  Business Name:  TrueVault VPN                                           ││
│  │  Legal Name:     TrueVault LLC                                           ││
│  │  Contact Email:  support@truevault.com                                   ││
│  │  Phone:          +1 (555) 123-4567                                       ││
│  │  Address:        123 Privacy Lane, Suite 100                             ││
│  │                  Austin, TX 78701, USA                                   ││
│  │  Tax ID:         ••-•••4567                                              ││
│  │                                                                          ││
│  └─────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────────┐│
│  │  ⚙️ TECHNICAL SETTINGS                                        [Edit]   ││
│  ├─────────────────────────────────────────────────────────────────────────┤│
│  │                                                                          ││
│  │  Site URL:       https://vpn.the-truth-publishing.com                    ││
│  │  API URL:        https://vpn.the-truth-publishing.com/api                ││
│  │  Admin Email:    admin@truevault.com                                     ││
│  │  Timezone:       America/Chicago (CST)                                   ││
│  │  Currency:       USD ($)                                                 ││
│  │                                                                          ││
│  └─────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│  ─────────────────────────────────────────────────────────────────────────   │
│                                                                              │
│  📋 SETTINGS CHANGE LOG                                                      │
│                                                                              │
│  Jan 14, 2026 1:30 AM │ PayPal mode changed to "live" │ by admin@...       │
│  Jan 13, 2026 9:00 PM │ Business name updated │ by admin@...                │
│  Jan 12, 2026 3:00 PM │ Initial setup │ by admin@...                        │
│                                                                              │
│  [View Full History]                                                         │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Edit Modal (Example: PayPal Settings)

```
┌─────────────────────────────────────────────────────────────────┐
│  Edit PayPal Settings                                      [X]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ⚠️ WARNING: Changing these settings affects all payments!      │
│                                                                  │
│  PayPal Client ID *                                              │
│  [ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoO_______] │
│                                                                  │
│  PayPal Secret Key *                                             │
│  [________________________________________________] [👁️ Show]   │
│  Current: ••••••••••••••••••••••••                               │
│                                                                  │
│  PayPal Webhook ID *                                             │
│  [46924926WL757580D_____________________________________]       │
│                                                                  │
│  Mode *                                                          │
│  ○ Sandbox (Testing)                                             │
│  ● Live (Production)                                             │
│                                                                  │
│  ─────────────────────────────────────────────────────────────   │
│                                                                  │
│  Enter your admin password to confirm changes:                   │
│  [________________________________________________]             │
│                                                                  │
│  [  Cancel  ]                    [  Save Changes  ]              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

# 🔌 API ENDPOINTS

## Get All Settings (by category)

**GET /api/admin/settings.php?category=paypal**

```php
<?php
// api/admin/settings.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/encryption.php';

// Require admin auth
$admin = Auth::requireAdminAuth();
if (!$admin) exit;

$category = $_GET['category'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get settings
    $query = "SELECT id, category, setting_key, setting_value, is_encrypted, 
                     display_name, description, is_required
              FROM business_settings";
    $params = [];
    
    if ($category) {
        $query .= " WHERE category = ?";
        $params[] = $category;
    }
    
    $query .= " ORDER BY category, display_order";
    
    $settings = Database::query('settings', $query, $params);
    
    // Mask encrypted values for display
    foreach ($settings as &$setting) {
        if ($setting['is_encrypted'] && !empty($setting['setting_value'])) {
            // Show last 4 characters only
            $decrypted = Encryption::decrypt($setting['setting_value']);
            $setting['setting_value'] = '••••••••' . substr($decrypted, -4);
            $setting['is_masked'] = true;
        }
    }
    
    // Group by category
    $grouped = [];
    foreach ($settings as $setting) {
        $grouped[$setting['category']][] = $setting;
    }
    
    Response::success(['settings' => $grouped]);
}
```

## Update Settings

**PUT /api/admin/settings.php**

```php
<?php
// Continuation of api/admin/settings.php

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = Response::getJsonInput();
    
    // Require password confirmation for sensitive changes
    if (!isset($input['admin_password'])) {
        Response::error('Admin password required to change settings', 401);
    }
    
    // Verify admin password
    $adminUser = Database::queryOne('admin',
        "SELECT password_hash FROM admin_users WHERE id = ?",
        [$admin['id']]
    );
    
    if (!password_verify($input['admin_password'], $adminUser['password_hash'])) {
        Response::error('Invalid admin password', 401);
    }
    
    // Process each setting update
    $updates = $input['settings'] ?? [];
    $updated = [];
    
    foreach ($updates as $update) {
        $settingId = $update['id'] ?? null;
        $newValue = $update['value'] ?? '';
        
        if (!$settingId) continue;
        
        // Get current setting
        $current = Database::queryOne('settings',
            "SELECT * FROM business_settings WHERE id = ?",
            [$settingId]
        );
        
        if (!$current) continue;
        
        // Encrypt if needed
        $valueToStore = $newValue;
        if ($current['is_encrypted'] && !empty($newValue)) {
            $valueToStore = Encryption::encrypt($newValue);
        }
        
        // Log the change (encrypt old value if sensitive)
        $oldValueForLog = $current['setting_value'];
        if ($current['is_encrypted']) {
            $oldValueForLog = '[ENCRYPTED]';
        }
        
        Database::execute('settings',
            "INSERT INTO settings_history (setting_id, old_value, new_value, changed_by, ip_address)
             VALUES (?, ?, ?, ?, ?)",
            [$settingId, $oldValueForLog, $current['is_encrypted'] ? '[ENCRYPTED]' : $newValue, 
             $admin['id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']
        );
        
        // Update setting
        Database::execute('settings',
            "UPDATE business_settings SET setting_value = ?, updated_at = datetime('now'), updated_by = ?
             WHERE id = ?",
            [$valueToStore, $admin['id'], $settingId]
        );
        
        $updated[] = $current['setting_key'];
    }
    
    // Clear any cached settings
    Settings::clearCache();
    
    Response::success([
        'updated' => $updated,
        'message' => count($updated) . ' setting(s) updated successfully'
    ]);
}
```

## Get Setting Value (for use in code)

**Helper class update:**

```php
<?php
// api/config/settings.php - Updated

class Settings {
    private static $cache = [];
    
    /**
     * Get a single setting value
     */
    public static function get($category, $key, $default = null) {
        $cacheKey = "{$category}.{$key}";
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        $setting = Database::queryOne('settings',
            "SELECT setting_value, is_encrypted FROM business_settings 
             WHERE category = ? AND setting_key = ?",
            [$category, $key]
        );
        
        if (!$setting) {
            return $default;
        }
        
        $value = $setting['setting_value'];
        
        // Decrypt if needed
        if ($setting['is_encrypted'] && !empty($value)) {
            $value = Encryption::decrypt($value);
        }
        
        self::$cache[$cacheKey] = $value;
        return $value;
    }
    
    /**
     * Get all settings in a category
     */
    public static function getCategory($category) {
        $settings = Database::query('settings',
            "SELECT setting_key, setting_value, is_encrypted FROM business_settings 
             WHERE category = ?",
            [$category]
        );
        
        $result = [];
        foreach ($settings as $setting) {
            $value = $setting['setting_value'];
            if ($setting['is_encrypted'] && !empty($value)) {
                $value = Encryption::decrypt($value);
            }
            $result[$setting['setting_key']] = $value;
        }
        
        return $result;
    }
    
    /**
     * Clear settings cache (after update)
     */
    public static function clearCache() {
        self::$cache = [];
    }
    
    /**
     * Convenience methods
     */
    public static function getPayPalClientId() {
        return self::get('paypal', 'client_id');
    }
    
    public static function getPayPalSecretKey() {
        return self::get('paypal', 'secret_key');
    }
    
    public static function getPayPalMode() {
        return self::get('paypal', 'mode', 'sandbox');
    }
    
    public static function getBusinessName() {
        return self::get('business', 'business_name', 'TrueVault VPN');
    }
    
    public static function getSiteUrl() {
        return self::get('technical', 'site_url');
    }
    
    public static function getAdminEmail() {
        return self::get('technical', 'admin_email');
    }
}
```

---

# 🔄 UPDATE EXISTING CODE

## PayPal Integration (use database settings)

**Before (hardcoded):**
```php
$clientId = 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk';
$secretKey = 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN';
```

**After (from database):**
```php
require_once __DIR__ . '/../config/settings.php';

$clientId = Settings::getPayPalClientId();
$secretKey = Settings::getPayPalSecretKey();
$mode = Settings::getPayPalMode();

$baseUrl = ($mode === 'live') 
    ? 'https://api-m.paypal.com' 
    : 'https://api-m.sandbox.paypal.com';
```

## Email Templates (use business name)

**Before:**
```html
<p>Thank you for choosing TrueVault VPN!</p>
```

**After:**
```php
$businessName = Settings::getBusinessName();
echo "<p>Thank you for choosing {$businessName}!</p>";
```

---

# 📋 TRANSFER CHECKLIST

When transferring ownership:

## For You (Seller):
- [ ] Ensure all settings are in database (not config files)
- [ ] Export current settings for your records
- [ ] Document any server-specific credentials
- [ ] Prepare handoff document with:
  - Admin login credentials
  - Server access (Contabo, Fly.io logins)
  - Domain registrar access
  - This documentation

## For New Owner (Buyer):
1. [ ] Login to admin dashboard
2. [ ] Go to Business Settings
3. [ ] Update PayPal Settings:
   - [ ] Enter their PayPal Client ID
   - [ ] Enter their PayPal Secret Key
   - [ ] Create new webhook, enter Webhook ID
   - [ ] Set mode to Live
4. [ ] Update Bank Account:
   - [ ] Enter their bank details
5. [ ] Update Business Information:
   - [ ] Change business name (if desired)
   - [ ] Update contact email
   - [ ] Update address
6. [ ] Update Technical Settings:
   - [ ] Change admin email to theirs
7. [ ] Test a small payment through PayPal
8. [ ] Done!

**Time to transfer: ~30 minutes**

---

# 🇨🇦 CLONING FOR CANADIAN VPN

When you clone this for Canada:

1. **Copy entire codebase** to new server
2. **Run database setup** (creates fresh empty DBs)
3. **Go to Business Settings**:
   - Set new PayPal credentials
   - Set Canadian bank account
   - Set business name: "TrueVault Canada" or new name
   - Set currency: CAD, symbol: C$
   - Set timezone: America/Toronto
4. **Update VPN servers** in database (Canadian locations)
5. **Done!**

Same code, different configuration. No code changes needed.

---

# ✅ ADD TO CHECKLIST

```markdown
## PHASE 9.5: BUSINESS SETTINGS (Transfer-Ready)

### Database
- [ ] Create business_settings table
- [ ] Create settings_history table
- [ ] Insert default settings structure
- [ ] Migrate PayPal credentials from config to database

### API
- [ ] api/admin/settings.php - GET settings by category
- [ ] api/admin/settings.php - PUT update settings
- [ ] api/admin/settings-history.php - GET change history
- [ ] Update Settings helper class with getters

### Admin UI
- [ ] public/admin/business.html - New business settings page
- [ ] PayPal settings section with edit modal
- [ ] Bank account section with edit modal
- [ ] Business info section with edit modal
- [ ] Technical settings section with edit modal
- [ ] Settings change log display
- [ ] Password confirmation for changes

### Integration
- [ ] Update PayPal code to use Settings::getPayPal*()
- [ ] Update email templates to use Settings::getBusinessName()
- [ ] Update invoice generation to use business info
- [ ] Test settings changes apply immediately
```

---

**END OF TRANSFER-READY DOCUMENTATION**
