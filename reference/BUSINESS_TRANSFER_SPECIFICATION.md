# INTERNATIONAL BUSINESS TRANSFER SYSTEM - CANADA → USA

**Version:** 2.0 (International Transfer Edition)  
**Date:** January 14, 2026  
**Critical:** Enables transfer from Canadian owner to USA owner  

---

## 🌍 TRANSFER OVERVIEW

### Current Setup (Canada - Kah-Len)
- **Currency:** CAD (Canadian Dollar)
- **Banking:** TD Canada Trust, Simplii Financial
- **Payment Methods:** E-Transfer (Interac), PayPal
- **Tax System:** Canadian GST/PST
- **Business:** Canadian-registered
- **Support Email:** paulhalonen@gmail.com

### New Setup (USA - New Owner)
- **Currency:** USD (US Dollar)
- **Banking:** US banks (Chase, Bank of America, Wells Fargo, etc.)
- **Payment Methods:** PayPal, Stripe, Zelle, Venmo, ACH
- **Tax System:** US Sales Tax (state-specific)
- **Business:** US-registered (EIN required)
- **Support Email:** newowner@example.com

---

## 🗄️ COMPLETE DATABASE-DRIVEN CONFIGURATION

### System Configuration Table
```sql
CREATE TABLE system_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_key TEXT UNIQUE NOT NULL,
    config_value TEXT,
    config_type TEXT, -- text, encrypted, json, url, boolean
    category TEXT, -- business, payment, banking, tax, servers, domain
    description TEXT,
    is_sensitive BOOLEAN DEFAULT 0,
    country_specific TEXT, -- CA, US, null (international)
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by TEXT
);
```

---

## 💰 MULTI-CURRENCY SYSTEM

### Currency Configuration
```sql
-- Current currency settings
INSERT INTO system_config (config_key, config_value, config_type, category, description) VALUES
('base_currency', 'CAD', 'text', 'business', 'Base currency for pricing'),
('currency_symbol', '$', 'text', 'business', 'Currency symbol'),
('currency_format', '{symbol}{amount} {code}', 'text', 'business', 'Display format'),
('accept_usd', '1', 'boolean', 'payment', 'Accept USD payments'),
('accept_cad', '1', 'boolean', 'payment', 'Accept CAD payments'),
('auto_convert_currency', '0', 'boolean', 'payment', 'Auto-convert at checkout');
```

### Pricing Table (Multi-Currency)
```sql
CREATE TABLE pricing (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_type TEXT NOT NULL,
    currency TEXT NOT NULL, -- CAD, USD
    amount DECIMAL(10,2) NOT NULL,
    billing_cycle TEXT DEFAULT 'monthly',
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Current pricing (Canadian)
INSERT INTO pricing (plan_type, currency, amount, billing_cycle) VALUES
('basic', 'CAD', 9.99, 'monthly'),
('basic', 'USD', 7.49, 'monthly'), -- Auto-converted
('family', 'CAD', 14.99, 'monthly'),
('family', 'USD', 11.24, 'monthly'),
('dedicated', 'CAD', 29.99, 'monthly'),
('dedicated', 'USD', 22.49, 'monthly');

-- After transfer (USA)
UPDATE system_config SET config_value = 'USD' WHERE config_key = 'base_currency';
UPDATE pricing SET is_active = 0 WHERE currency = 'CAD';
UPDATE pricing SET is_active = 1 WHERE currency = 'USD';
```

---

## 💳 PAYMENT METHOD CONFIGURATION

### Payment Methods Table
```sql
CREATE TABLE payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    method_id TEXT UNIQUE NOT NULL,
    display_name TEXT NOT NULL,
    description TEXT,
    available_countries TEXT, -- JSON array: ["CA", "US"] or null for all
    is_enabled BOOLEAN DEFAULT 1,
    requires_manual_approval BOOLEAN DEFAULT 0,
    setup_instructions TEXT,
    icon_url TEXT,
    sort_order INTEGER DEFAULT 0
);

-- Canadian payment methods
INSERT INTO payment_methods (method_id, display_name, description, available_countries, is_enabled, requires_manual_approval) VALUES
('paypal', 'PayPal / Credit Card', 'Instant activation, automatic renewal', null, 1, 0),
('etransfer_ca', 'E-Transfer (Interac)', 'Canada only, manual activation (1-2 hours)', '["CA"]', 1, 1);

-- USA payment methods (add after transfer)
INSERT INTO payment_methods (method_id, display_name, description, available_countries, is_enabled, requires_manual_approval) VALUES
('stripe', 'Credit Card (Stripe)', 'Instant activation, automatic renewal', null, 1, 0),
('zelle', 'Zelle', 'USA only, manual activation (1-2 hours)', '["US"]', 1, 1),
('venmo', 'Venmo', 'USA only, manual activation (1-2 hours)', '["US"]', 1, 1),
('ach', 'ACH Bank Transfer', 'USA only, 3-5 business days', '["US"]', 1, 1);
```

### Payment Configuration
```sql
-- PayPal (current - transfers to new owner)
INSERT INTO system_config (config_key, config_value, config_type, category, description, is_sensitive, country_specific) VALUES
('paypal_enabled', '1', 'boolean', 'payment', 'Enable PayPal payments', 0, null),
('paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'encrypted', 'payment', 'PayPal Client ID', 1, null),
('paypal_secret_key', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN', 'encrypted', 'payment', 'PayPal Secret Key', 1, null),
('paypal_business_email', 'paulhalonen@gmail.com', 'text', 'payment', 'PayPal Business Email', 0, null),

-- E-Transfer (Canada - remove after transfer)
('etransfer_enabled', '1', 'boolean', 'payment', 'Enable E-Transfer (Interac)', 0, 'CA'),
('etransfer_email', 'paulhalonen@gmail.com', 'text', 'payment', 'E-Transfer Recipient Email', 0, 'CA'),
('etransfer_autodeposit', '1', 'boolean', 'payment', 'Auto-deposit enabled', 0, 'CA'),

-- Stripe (USA - add after transfer)
('stripe_enabled', '0', 'boolean', 'payment', 'Enable Stripe payments', 0, null),
('stripe_public_key', '', 'text', 'payment', 'Stripe Publishable Key', 0, null),
('stripe_secret_key', '', 'encrypted', 'payment', 'Stripe Secret Key', 1, null),

-- Zelle (USA - add after transfer)
('zelle_enabled', '0', 'boolean', 'payment', 'Enable Zelle payments', 0, 'US'),
('zelle_email', '', 'text', 'payment', 'Zelle Email Address', 0, 'US'),
('zelle_phone', '', 'text', 'payment', 'Zelle Phone Number', 0, 'US'),

-- Venmo (USA - add after transfer)
('venmo_enabled', '0', 'boolean', 'payment', 'Enable Venmo payments', 0, 'US'),
('venmo_username', '', 'text', 'payment', 'Venmo Username (@username)', 0, 'US');
```

---

## 🏦 BANKING CONFIGURATION

### Banking Dashboard (Country-Specific)
```sql
-- Current banking (Canada)
INSERT INTO system_config (config_key, config_value, config_type, category, description, country_specific) VALUES
('bank1_name', 'TD Canada Trust', 'text', 'banking', 'Primary Bank Name', 'CA'),
('bank1_url', 'https://www.td.com/ca/en/personal-banking', 'url', 'banking', 'Online Banking URL', 'CA'),
('bank1_account_type', 'Business Checking', 'text', 'banking', 'Account Type', 'CA'),

('bank2_name', 'Simplii Financial', 'text', 'banking', 'Secondary Bank Name', 'CA'),
('bank2_url', 'https://www.simplii.com/en/home.html', 'url', 'banking', 'Online Banking URL', 'CA'),
('bank2_account_type', 'Savings', 'text', 'banking', 'Account Type', 'CA');

-- After transfer (USA - new owner configures)
UPDATE system_config SET 
    config_value = 'Chase Bank',
    country_specific = 'US'
WHERE config_key = 'bank1_name';

UPDATE system_config SET 
    config_value = 'https://www.chase.com',
    country_specific = 'US'
WHERE config_key = 'bank1_url';

-- Or add new banks
INSERT INTO system_config (config_key, config_value, config_type, category, description, country_specific) VALUES
('bank1_name', 'Chase Bank', 'text', 'banking', 'Primary Bank Name', 'US'),
('bank1_url', 'https://www.chase.com', 'url', 'banking', 'Online Banking URL', 'US'),

('bank2_name', 'Bank of America', 'text', 'banking', 'Secondary Bank Name', 'US'),
('bank2_url', 'https://www.bankofamerica.com', 'url', 'banking', 'Online Banking URL', 'US');
```

---

## 💼 BUSINESS INFORMATION

### Business Configuration
```sql
-- Current business (Canada)
INSERT INTO system_config (config_key, config_value, config_type, category, description, country_specific) VALUES
('business_name', 'TrueVault VPN', 'text', 'business', 'Legal Business Name', null),
('business_owner', 'Kah-Len Halonen', 'text', 'business', 'Owner Name', null),
('business_country', 'CA', 'text', 'business', 'Country of Operation', null),
('business_registration', 'Canadian Business Number', 'text', 'business', 'Business Registration Number', 'CA'),
('business_address', '123 Main St, Toronto, ON, Canada', 'text', 'business', 'Business Address', 'CA'),

-- Tax settings (Canada)
('tax_enabled', '1', 'boolean', 'tax', 'Enable tax collection', null),
('tax_system', 'canadian_gst_pst', 'text', 'tax', 'Tax system type', 'CA'),
('gst_number', '', 'text', 'tax', 'GST Registration Number', 'CA'),
('gst_rate', '5.0', 'text', 'tax', 'GST Rate (%)', 'CA'),
('pst_rate_on', '8.0', 'text', 'tax', 'Ontario PST Rate (%)', 'CA'),

-- Support contact (current)
('support_email', 'paulhalonen@gmail.com', 'text', 'business', 'Support Email', null),
('support_phone', '', 'text', 'business', 'Support Phone Number', null);

-- After transfer (USA)
UPDATE system_config SET config_value = 'US' WHERE config_key = 'business_country';
UPDATE system_config SET config_value = 'us_sales_tax' WHERE config_key = 'tax_system';
UPDATE system_config SET config_value = 'New Owner Name' WHERE config_key = 'business_owner';
UPDATE system_config SET config_value = 'newowner@example.com' WHERE config_key = 'support_email';

-- Add US-specific fields
INSERT INTO system_config (config_key, config_value, config_type, category, description, country_specific) VALUES
('ein_number', '', 'text', 'business', 'EIN (Employer Identification Number)', 'US'),
('state_registration', '', 'text', 'business', 'State Business Registration', 'US'),
('sales_tax_states', '[]', 'json', 'tax', 'States where sales tax applies', 'US'),
('default_sales_tax_rate', '0.0', 'text', 'tax', 'Default Sales Tax Rate (%)', 'US');
```

---

## 🔄 TRANSFER WIZARD (Admin Panel)

### Step-by-Step Transfer Process

**Location:** `/manage/transfer-wizard.html`

```
┌────────────────────────────────────────────────────────────┐
│ Business Transfer Wizard                                   │
│ Transfer TrueVault VPN to New Owner                        │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ Progress: [■■■■░░░░░░] Step 1 of 10                       │
│                                                            │
│ Step 1: New Owner Information                              │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                            │
│ Owner Name:                                                │
│ [____________________]                                     │
│                                                            │
│ Support Email:                                             │
│ [____________________]                                     │
│                                                            │
│ Country:                                                   │
│ ⚪ Canada (CAD)    ● United States (USD)                  │
│                                                            │
│ [Cancel]                               [Next: Currency →] │
└────────────────────────────────────────────────────────────┘
```

### Transfer Steps

**Step 1: Owner Information**
- New owner name
- Support email
- Country selection (CA/US)

**Step 2: Currency & Pricing**
- Select base currency (CAD/USD)
- Review pricing conversion
- Set active currency

**Step 3: Payment Methods**
- Configure PayPal (new owner's account)
- Enable/disable country-specific methods
- E-Transfer (CA) or Zelle/Venmo (US)
- Optional: Add Stripe

**Step 4: Banking Setup**
- Add bank names and URLs
- Configure quick links
- Set account types

**Step 5: Business Registration**
- Business name (if changing)
- Business number/EIN
- Business address
- Tax configuration

**Step 6: Tax Settings**
- Canadian GST/PST OR US Sales Tax
- Tax rates by region
- Tax collection rules

**Step 7: Server Credentials**
- Keep existing servers OR
- Update server credentials
- Test server connections

**Step 8: Domain & Hosting**
- GoDaddy transfer OR
- Update DNS settings
- FTP credentials

**Step 9: Email & SMTP**
- Update support email
- Configure SMTP (if changing)
- Test email sending

**Step 10: Review & Execute**
- Review all changes
- Create backup
- Execute transfer
- Test everything

---

## 📋 TRANSFER CHECKLIST

### Before Transfer (Current Owner - Kah-Len)

#### Business Preparation
- [ ] Choose transfer date
- [ ] Notify active customers (optional)
- [ ] Prepare all credentials list
- [ ] Create full database backup
- [ ] Export financial reports
- [ ] Document any custom configurations

#### Account Cleanup
- [ ] Cancel any personal integrations
- [ ] Remove personal data
- [ ] Clear browser sessions
- [ ] Export customer list
- [ ] Archive support tickets

#### Legal & Financial
- [ ] Prepare sale agreement
- [ ] Transfer PayPal business account OR provide new owner's PayPal
- [ ] Final financial statement
- [ ] Tax documentation (if needed)
- [ ] Cancel Canadian business registration (if selling entity)

### During Transfer (Both Parties)

#### Database Configuration
- [ ] Run Transfer Wizard
- [ ] Update owner information
- [ ] Change currency (CAD → USD)
- [ ] Configure payment methods
- [ ] Update banking links
- [ ] Set business registration info
- [ ] Configure tax settings

#### Account Transfers
- [ ] Transfer PayPal access
  - Option A: Transfer business account ownership
  - Option B: Change API credentials to new owner's PayPal
- [ ] Transfer GoDaddy account
  - Change account email
  - Update payment method
- [ ] Transfer server access (Contabo, Fly.io)
  - Change email addresses
  - Update payment methods
- [ ] Transfer domain DNS
- [ ] Transfer email account

#### Testing Phase
- [ ] Test payment processing (PayPal)
- [ ] Test new payment methods (Zelle/Venmo if added)
- [ ] Test customer signup flow
- [ ] Test VPN connections
- [ ] Test admin panel access
- [ ] Send test support email
- [ ] Generate test invoice

### After Transfer (New Owner)

#### Immediate Actions
- [ ] Change all passwords
- [ ] Enable 2FA on all accounts
- [ ] Update privacy policy (if needed)
- [ ] Update terms of service (if needed)
- [ ] Update contact information on website

#### Financial Setup
- [ ] Set up US bank accounts (if not done)
- [ ] Configure Stripe (recommended)
- [ ] Set up Zelle/Venmo (optional)
- [ ] Apply for EIN (if needed)
- [ ] Register business in state (if needed)

#### Operational Setup
- [ ] Review all customers
- [ ] Test support email flow
- [ ] Test billing cycle
- [ ] Monitor server health
- [ ] Set up monitoring alerts

#### Marketing & Communication
- [ ] Update social media accounts
- [ ] Send announcement email (optional)
- [ ] Update marketing materials
- [ ] Resume marketing campaigns

---

## 🔐 SECURITY BEST PRACTICES

### Password Management
```sql
-- All sensitive data encrypted in database
CREATE TABLE encrypted_credentials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_name TEXT NOT NULL,
    username TEXT,
    password_encrypted TEXT, -- AES-256 encrypted
    notes_encrypted TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Encryption Key
- Master encryption key stored in `/config/encryption.key`
- New owner generates new encryption key
- All credentials re-encrypted with new key

### Access Control
- Change admin password immediately
- Enable 2FA for admin panel
- Generate new JWT secret
- Rotate API keys

---

## 📁 TRANSFER PACKAGE

### What Gets Transferred

**1. Database Files (SQLite)**
- `users.db` - User accounts and authentication
- `subscriptions.db` - Customer subscriptions
- `devices.db` - Device management
- `billing.db` - Payment records
- `logs.db` - Activity logs
- `config.db` - System configuration

**2. Code & Files**
- Complete website files
- API endpoints
- Admin panel
- Network scanner application
- Documentation

**3. Credentials Document**
```
TRUEVAULT VPN - CREDENTIALS PACKAGE
Transfer Date: [DATE]
From: Kah-Len (Canada) → To: [New Owner] (USA)

=== PayPal ===
Business Email: paulhalonen@gmail.com
→ TRANSFER TO: [new owner's PayPal email]
→ OR: Update API credentials in Transfer Wizard

=== Servers ===
Contabo Account: paulhalonen@gmail.com / Asasasas4!
Fly.io Account: paulhalonen@gmail.com / Asasasas4!
→ Change email and password after transfer

=== Domain & Hosting ===
GoDaddy Username: 26853687
GoDaddy Password: Asasasas4!
FTP Host: the-truth-publishing.com
FTP User: kahlen@the-truth-publishing.com
FTP Pass: AndassiAthena8
→ Transfer GoDaddy account OR update credentials

=== Admin Access ===
Admin Dashboard: https://manage.the-truth-publishing.com
Admin Email: kahlen@truthvault.com
Admin Password: [provided separately]
→ CHANGE IMMEDIATELY after transfer

=== Encryption Key ===
Location: /config/encryption.key
Current Key: [provided separately]
→ Generate NEW key and re-encrypt all data

=== Additional Notes ===
- All database-driven (easy updates via Transfer Wizard)
- Full documentation in /reference/ folder
- Testing checklist included
- Support contacts: [provided separately]
```

**4. Documentation**
- All specifications (2,600+ lines created)
- Transfer guide (this document)
- Admin manual
- API documentation
- User guides
- Marketing templates

---

## 🌐 DOMAIN-INDEPENDENT LINKS

### Current Issue
Hardcoded domain links break when moving servers:
```php
// BAD - breaks on new server
$resetLink = "https://vpn.the-truth-publishing.com/reset-password?token=" . $token;
```

### Solution: Database-Driven URLs
```php
// GOOD - works on any domain
$domain = Config::get('primary_domain'); // From database
$resetLink = "https://{$domain}/reset-password?token=" . $token;
```

### URL Helper Function
```php
function getAppUrl($path = '') {
    $domain = Config::get('primary_domain');
    $protocol = Config::get('use_https', '1') ? 'https' : 'http';
    $url = "{$protocol}://{$domain}";
    
    if ($path) {
        $url .= '/' . ltrim($path, '/');
    }
    
    return $url;
}

// Usage
$dashboardUrl = getAppUrl('dashboard');
// Works on ANY domain: vpn.the-truth-publishing.com, vpn.newdomain.com, etc.
```

### Email Template URLs
```php
// Email templates use placeholder
$emailBody = "
Click here to reset your password:
{{app_url}}/reset-password?token={{token}}

Visit your dashboard:
{{app_url}}/dashboard
";

// System replaces {{app_url}} with actual domain
$emailBody = str_replace('{{app_url}}', getAppUrl(), $emailBody);
```

---

## ⏱️ ESTIMATED TRANSFER TIME

### Preparation (1-2 weeks before)
- Documentation review: 2 hours
- Credential gathering: 1 hour
- Backup creation: 30 minutes
- Customer notification (optional): 1 hour

### Transfer Day (30 minutes - 2 hours)
- Run Transfer Wizard: 15 minutes
- Update payment accounts: 15 minutes
- Test all systems: 30 minutes
- Final verification: 15 minutes

### Post-Transfer (1-2 days)
- Monitor systems: Ongoing
- Handle support questions: As needed
- Fine-tune settings: 1 hour

**Total Active Work: 3-5 hours**  
**Total Calendar Time: 1-2 weeks**

---

## 📞 TRANSFER SUPPORT

### Documentation Locations
- **This Guide:** `/reference/BUSINESS_TRANSFER_SPECIFICATION.md`
- **Transfer Wizard:** `/manage/transfer-wizard.html`
- **Credentials Template:** `/reference/TRANSFER_CREDENTIALS_TEMPLATE.txt`
- **Testing Checklist:** `/reference/TRANSFER_TESTING_CHECKLIST.md`

### Contact During Transfer
- **Current Owner:** paulhalonen@gmail.com
- **Emergency Support:** [provided separately]
- **Documentation:** Complete specifications in `/reference/`

---

**Status:** Complete Specification - Ready for Implementation  
**Priority:** Critical (enables business sale)  
**Country Support:** Canada → USA (or any country)  
**Transfer Time:** 30 minutes - 2 hours  
**Implementation Time:** 3-4 days
