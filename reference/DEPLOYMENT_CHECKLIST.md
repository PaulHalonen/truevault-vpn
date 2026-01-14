# TRUEVAULT VPN - DEPLOYMENT CHECKLIST
## What Needs to Be Fixed/Deployed

**Created:** January 14, 2026 - 5:20 AM CST

---

## 🚨 CRITICAL - USERS CANNOT PAY!

### Step 1: Deploy Billing Folder
```
FTP Upload:
FROM: E:\Documents\GitHub\truevault-vpn\api\billing\
TO:   /public_html/vpn.the-truth-publishing.com/api/billing/

Files to upload:
1. billing-manager.php
2. checkout.php
3. complete.php
4. webhook.php
5. cancel.php
6. history.php
7. subscription.php
8. cron.php
9. index.php
10. setup-billing.php
```

### Step 2: Fix Database Configuration
Edit `/api/config/database.php` to match server structure:
```php
// CHANGE FROM:
'users' => 'core/users.db'

// CHANGE TO:
'users' => 'users.db'

// And update basePath to /data/ instead of /databases/
```

### Step 3: Update PayPal Webhook
1. Go to: https://developer.paypal.com/dashboard/applications
2. Login: paulhalonen@gmail.com / Asasasas4!
3. Find webhook ID: 46924926WL757580D
4. Change URL to: https://vpn.the-truth-publishing.com/api/billing/webhook.php

---

## 📋 DEPLOYMENT STATUS CHECKLIST

### API Endpoints:
- [x] /api/auth/ - DEPLOYED
- [x] /api/cameras/ - DEPLOYED
- [x] /api/certificates/ - DEPLOYED
- [x] /api/config/ - DEPLOYED
- [x] /api/debug/ - DEPLOYED
- [x] /api/devices/ - DEPLOYED
- [x] /api/helpers/ - DEPLOYED
- [x] /api/identities/ - DEPLOYED
- [x] /api/mesh/ - DEPLOYED
- [x] /api/plans/ - DEPLOYED
- [x] /api/scanner/ - DEPLOYED
- [x] /api/theme/ - DEPLOYED
- [x] /api/users/ - DEPLOYED
- [x] /api/vip/ - DEPLOYED
- [x] /api/vpn/ - DEPLOYED
- [x] /api/admin/ - DEPLOYED
- [ ] /api/billing/ - NOT DEPLOYED ❌
- [ ] /api/port-forwarding/ - NOT DEPLOYED (empty folder)
- [ ] /api/payments/ - NOT DEPLOYED (empty folder)
- [ ] /api/cron/ - NOT DEPLOYED
- [ ] /api/automation/ - NOT DEPLOYED
- [ ] /api/servers/ - NOT DEPLOYED

### Databases:
- [x] All 25 databases present in /data/
- [ ] Database path configuration needs update

### Frontend:
- [x] /public/dashboard/ - 11 pages
- [x] /public/admin/ - 13 pages
- [x] /public/downloads/scanner/ - Scanner files
- [x] /public/assets/ - JS/CSS

### PayPal:
- [ ] Webhook URL incorrect (points to builder subdomain)
- [ ] Webhook ID: 46924926WL757580D exists
- [x] API credentials in billing-manager.php

### VPN Servers:
- [ ] Need to verify peer_api.py is running on all servers
- [x] Public keys configured in servers.php
- [x] Server 2 (STL) marked as VIP-only for seige235@yahoo.com

---

## 🔧 FTP COMMANDS FOR DEPLOYMENT

### Using PowerShell:
```powershell
# Upload entire billing folder
$ftpUrl = "ftp://the-truth-publishing.com/public_html/vpn.the-truth-publishing.com/api/billing/"
$localPath = "E:\Documents\GitHub\truevault-vpn\api\billing\"
$username = "kahlen@the-truth-publishing.com"
$password = "AndassiAthena8"

# Create directory first
$request = [System.Net.FtpWebRequest]::Create($ftpUrl)
$request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
$request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
$request.GetResponse()

# Then upload each file
Get-ChildItem $localPath -File | ForEach-Object {
    $file = $_
    $request = [System.Net.FtpWebRequest]::Create("$ftpUrl$($file.Name)")
    $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
    $request.UseBinary = $true
    $request.UsePassive = $true
    
    $content = [System.IO.File]::ReadAllBytes($file.FullName)
    $request.ContentLength = $content.Length
    
    $stream = $request.GetRequestStream()
    $stream.Write($content, 0, $content.Length)
    $stream.Close()
    
    Write-Host "Uploaded: $($file.Name)"
}
```

---

## 🧪 TEST AFTER DEPLOYMENT

### Test 1: Billing API Exists
```bash
curl https://vpn.the-truth-publishing.com/api/billing/index.php
# Should return JSON, NOT homepage HTML
```

### Test 2: Checkout Creates Order
```bash
curl -X POST https://vpn.the-truth-publishing.com/api/billing/checkout.php \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"plan_id": "basic"}'
# Should return PayPal order_id and approval_url
```

### Test 3: VIP Bypass
1. Register with seige235@yahoo.com
2. Should auto-create subscription (no payment page)
3. Should have access to Server 2 only

### Test 4: Webhook Receives Events
```bash
curl -X POST https://vpn.the-truth-publishing.com/api/billing/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"event_type": "TEST"}'
# Should return {"status": "processed"} or similar
```

---

## 📞 SUPPORT

Owner: Kah-Len (paulhalonen@gmail.com)
Goal: $6M/year revenue, 1-person operation
Brand: TrueVault VPN
