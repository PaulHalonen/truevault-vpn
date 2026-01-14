# 🚨 READ THIS FIRST - NEXT SESSION QUICK START

**Date:** January 14, 2026
**Project:** TrueVault VPN
**Location:** E:\Documents\GitHub\truevault-vpn\

---

## ⚠️ CRITICAL ISSUES TO FIX IMMEDIATELY

### 1. BILLING FOLDER NOT DEPLOYED!
Users cannot pay! The /api/billing/ folder is NOT on the server.

**FIX:**
```
Upload via FTP:
FROM: E:\Documents\GitHub\truevault-vpn\api\billing\
TO: /public_html/vpn.the-truth-publishing.com/api/billing/
```

### 2. DATABASE PATH MISMATCH
Code expects `/databases/category/name.db`
Server has `/data/name.db` (flat)

**FIX:** Update /api/config/database.php

### 3. PAYPAL WEBHOOK WRONG URL
Current: builder.the-truth-publishing.com
Should be: vpn.the-truth-publishing.com/api/billing/webhook.php

---

## 🔐 CREDENTIALS

### FTP
```
Host: the-truth-publishing.com
User: kahlen@the-truth-publishing.com
Pass: AndassiAthena8
Port: 21
```

### PayPal LIVE
```
Client: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Secret: EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
```

### JWT
```
Secret: TrueVault2026JWTSecretKey!@#$
```

---

## 🖥️ VPN SERVERS

| IP | Location | Type | Port |
|----|----------|------|------|
| 66.94.103.91 | New York | Shared | 51820 |
| 144.126.133.253 | St. Louis | VIP ONLY | 51820 |
| 66.241.124.4 | Dallas | Shared | 51820 |
| 66.241.125.247 | Toronto | Shared | 51820 |

---

## 🎯 VIP USERS (SECRET!)

- `paulhalonen@gmail.com` - Owner (all access)
- `seige235@yahoo.com` - VIP Dedicated (Server 2 exclusive)

VIPs sign up through NORMAL registration. System auto-detects and bypasses payment.

---

## 📋 PRIORITY CHECKLIST

1. [ ] Deploy /api/billing/ folder to server
2. [ ] Fix database paths in config
3. [ ] Update PayPal webhook URL
4. [ ] Test payment flow
5. [ ] Test VIP registration bypass
6. [ ] Create /api/port-forwarding/ API
7. [ ] Verify VPN servers running peer_api.py

---

## 📁 KEY FILES

- Blueprint: `/reference/TRUEVAULT_COMPLETE_BLUEPRINT_V7.md`
- Chat Log: `/chat_log.txt`
- Database Config: `/api/config/database.php`
- Auth Helper: `/api/helpers/auth.php`
- VIP Manager: `/api/helpers/vip.php`
- Billing Manager: `/api/billing/billing-manager.php`
- Frontend JS: `/public/assets/js/app.js`

---

## 💰 PRICING

- Basic: $9.99/mo (3 devices, 1 camera)
- Family: $14.99/mo (5 devices, 2 cameras)
- Dedicated: $29.99/mo (unlimited)

---

## 📝 ALWAYS UPDATE CHAT LOG

After each task, append to: `E:\Documents\GitHub\truevault-vpn\chat_log.txt`
