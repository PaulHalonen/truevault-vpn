# TrueVault VPN - Deployment Guide

## 📦 Repository
- **GitHub:** https://github.com/PaulHalonen/truevault-vpn
- **Local:** E:\Documents\GitHub\truevault-vpn
- **Production:** vpn.the-truth-publishing.com

## 🚀 Quick Deployment Steps

### Step 1: Upload Files via FTP
```
FTP Host: the-truth-publishing.com
FTP User: kahlen@the-truth-publishing.com
FTP Pass: AndassiAthena8
FTP Port: 21

Upload to: /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com
```

### Step 2: Create Database Directory
```bash
mkdir -p /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/data
chmod 755 /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/data
```

### Step 3: Run Database Setup
Visit in browser:
```
https://vpn.the-truth-publishing.com/api/config/setup-databases.php
```

This creates all 21 SQLite databases with tables and default data.

### Step 4: Test the Site
- Landing page: https://vpn.the-truth-publishing.com/
- Login: https://vpn.the-truth-publishing.com/login
- Admin: https://vpn.the-truth-publishing.com/admin/

### Step 5: Admin Login
```
Email: kahlen@truthvault.com
Password: password
⚠️ CHANGE THIS PASSWORD IMMEDIATELY!
```

---

## 📁 File Structure

```
vpn.the-truth-publishing.com/
├── .htaccess              # Main routing
├── api/                   # Backend API
│   ├── .htaccess          # API CORS/routing
│   ├── admin/             # Admin endpoints
│   ├── auth/              # Authentication
│   ├── certificates/      # Certificate management
│   ├── config/            # Configuration
│   ├── devices/           # Device management
│   ├── helpers/           # Helper classes
│   ├── identities/        # Regional identities
│   ├── mesh/              # Mesh networking
│   ├── scanner/           # Network scanner sync
│   ├── theme/             # Theme API
│   ├── users/             # User management
│   └── vpn/               # VPN operations
├── data/                  # SQLite databases (created by setup)
├── public/                # Frontend files
│   ├── .htaccess
│   ├── index.html         # Landing page
│   ├── login.html         # Login page
│   ├── register.html      # Registration
│   ├── admin/             # Admin dashboard (13 pages)
│   ├── dashboard/         # User dashboard (11 pages)
│   └── assets/            # CSS/JS
└── reference/             # Documentation
```

---

## 🗄️ Databases (21 Total)

| Database | Purpose |
|----------|---------|
| users.db | User accounts |
| admin_users.db | Admin accounts |
| subscriptions.db | Subscription plans |
| payments.db | Payment history |
| vpn.db | VPN servers & connections |
| certificates.db | SSL/VPN certificates |
| devices.db | User devices |
| identities.db | Regional identities |
| mesh.db | Mesh networking |
| cameras.db | IP cameras |
| themes.db | UI themes |
| pages.db | CMS pages |
| emails.db | Email templates |
| media.db | Media files |
| logs.db | System logs |
| settings.db | System settings |
| automation.db | Workflow automation |
| notifications.db | User notifications |
| analytics.db | Analytics data |
| bandwidth.db | Bandwidth usage |
| support.db | Support tickets |

---

## 🖥️ VPN Servers

| ID | Name | IP | Location | Type |
|----|------|-----|----------|------|
| 1 | US-East | 66.94.103.91 | New York | Shared |
| 2 | US-Central VIP | 144.126.133.253 | St. Louis | VIP Only* |
| 3 | Dallas | 66.241.124.4 | Dallas | Shared |
| 4 | Canada | 66.241.125.247 | Toronto | Shared |

*VIP Server (ID: 2) is exclusively for seige235@yahoo.com

---

## 🎨 Theme System

All styles are database-driven (zero hardcoding):
- Colors, fonts, radii stored in `themes.db`
- CSS variables injected via `theme-loader.js`
- Admin can edit themes at `/admin/themes.html`
- Changes apply instantly across all pages

---

## 🔐 Security Notes

1. **Change default admin password immediately**
2. **Update JWT secret in settings**
3. **Configure HTTPS (SSL certificate)**
4. **Set proper file permissions:**
   ```bash
   chmod 755 /path/to/vpn.the-truth-publishing.com
   chmod 644 /path/to/vpn.the-truth-publishing.com/*.php
   chmod 755 /path/to/vpn.the-truth-publishing.com/data
   chmod 644 /path/to/vpn.the-truth-publishing.com/data/*.db
   ```

---

## 💳 PayPal Integration

```
Mode: Live
Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Webhook: https://builder.the-truth-publishing.com/api/paypal-webhook.php
```

---

## 📊 Progress Summary

| Phase | Status | Items |
|-------|--------|-------|
| Phase 1: Structure | ✅ Complete | Directories, configs |
| Phase 2: APIs | ✅ Complete | 45 API files |
| Phase 3: Frontend | ✅ Complete | 27 HTML pages |
| Phase 4: Deployment | 🔄 Ready | FTP upload needed |

**Total Files:** 87+
**Ready for Production:** ✅ YES

---

## 🆘 Troubleshooting

### "Database not found"
Run the setup script: `/api/config/setup-databases.php`

### "Permission denied"
Check file permissions, especially on `/data` directory

### "500 Server Error"
Check PHP error logs, enable error display temporarily

### "Login not working"
Verify JWT secret in settings matches auth.php

---

## 📞 Support

- Email: paulhalonen@gmail.com
- GitHub: https://github.com/PaulHalonen/truevault-vpn

---

*Last Updated: January 11, 2026*
