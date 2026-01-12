# TrueVault VPN - User Vision & Original Context
## Preserved for Future Development Chats
**Created:** January 11, 2026

---

## 👤 USER PROFILE

- **Name:** Kah-Len
- **Visual Impairment:** User has difficulty seeing, needs Claude to do editing
- **Contact:** paulhalonen@gmail.com
- **GitHub:** E:\Documents\GitHub\truevault-vpn

---

## 🎯 USER'S ORIGINAL REQUEST

> "You are to recreate a new VPN from the original concept but using all automation instead of the built-in AI support. Everything is to be automated. There needs to be a client VPN dashboard, a management dashboard, then a separate 'marketing, accounting / database creator dashboard'. All have to be 100% automated with a FileMaker Pro database creator with multiple sample files and real files created. Using GrapesJS, React and whatever else is needed to build it all.

> Look at every file in this project's instructions and files. There is a scanner program that will be downloaded to the user's computer that scans for all devices and cameras etc. The scanner will force the cloud cameras to be visible such as the Geeni cams etc. Then there has to be a camera dashboard on the client's VPN dashboard that shows all cameras with all camera settings, flood light, motion, 2-way sound etc... the dashboard device page will have port forwarding and switchable settings.

> Remember that this entire build will be on the VPN subdomain only! /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com and the databases are all SQLite. Not clumped together as one but properly structured. Have the VPN servers create the keys on the servers..."

---

## 📋 USER'S SPECIFIC REQUIREMENTS

### 1. Theme System
> "Make sure all time and date stamps are placed at the beginning and end of each chat that's appended in the chat_log.txt All themes, colors, buttons and all visual styles to be database driven. No hardcode anywhere. All themes and webpages must be editable through the admin CMS control panel. If you see any hardcode, change it to database driven code."

### 2. Chat Logging
> "Always see the chat logs for most recent work and always update (Append) to chat log in increments throughout the chat so nothing is lost or has to be redone. Not just beginning and end of the chat since the chat keeps refreshing."

### 3. Database Structure
> "The databases are all SQLite. Not clumped together as one but properly structured. There needs to be separated compartments made with proper structure."

### 4. Portability
> "This subdomain files, settings and databases need to be portable as it will be moved shortly after launch to a new location."

### 5. Key Generation
> "Have the VPN servers create the keys on the servers... (check for already created scripts on the servers)"

---

## 🖼️ ORIGINAL CONCEPT (From concept.txt - Dec 2025)

The original VPN concept brainstormed included:

1. **Smart Identity Router** - Maintains persistent "digital identities" for different regions. Same Canadian IP every time for banking, consistent browser fingerprints, timezone settings, behavioral patterns. Banks don't flag as suspicious.

2. **Mesh Family/Team Network** - Private overlay network connecting all devices and trusted people's devices. Appears as same local network regardless of location. Direct device access without port forwarding. Like Tailscale but simplified.

3. **Decentralized Bandwidth Marketplace** - Users contribute spare bandwidth, earn credits. Users who need bandwidth spend credits. No central servers to subpoena. Traffic routes through multiple residential IPs.

4. **Context-Aware Adaptive Routing** - System learns patterns:
   - Banking app → Route through trusted home-region server with minimal hops
   - News browsing → Privacy-optimized multi-hop route
   - Gaming → Prioritize lowest latency
   - Work → Enterprise-grade security route

5. **Personal Certificate Authority** - Each user gets their own PKI infrastructure with:
   - Personal Root Certificate
   - Unlimited Device Certificates
   - Regional Identity Certificates
   - Mesh Trust Certificates
   - Certificate Backup & Recovery
   - Hardware Key Support

---

## 💰 PRICING STRUCTURE (From Marketing Image)

| Plan | Price | Features |
|------|-------|----------|
| Personal | $9.99/mo | 3 devices, Personal Certificates, 3 Regional Identities, Smart Routing, 24/7 Support |
| Family | $14.99/mo | Unlimited Devices, Full Certificate Suite, All Regional Identities, Mesh Networking (6 users), Priority Support, Bandwidth Rewards |
| Business | $29.99/mo | Unlimited Everything, Enterprise Certificates, Team Mesh (25 users), Admin Dashboard, API Access, Dedicated Support |

---

## 🌐 SERVER INFRASTRUCTURE

### Contabo Server 1 (US-East) - SHARED
- **IP:** 66.94.103.91
- **IPv6:** 2605:a142:2299:0026:0000:0000:0000:0001
- **VNC:** 154.53.39.97:63031
- **Purpose:** Shared VPN server (bandwidth constrained)
- **Cost:** $6.75/month

### Contabo Server 2 (US-Central) - VIP DEDICATED
- **IP:** 144.126.133.253
- **IPv6:** 2605:a140:2299:0005:0000:0000:0000:0001
- **VNC:** 207.244.248.38:63098
- **Purpose:** DEDICATED for seige235@yahoo.com ONLY
- **Cost:** $6.15/month

### Fly.io Server 3 (Dallas) - SHARED
- **IP:** 66.241.124.4 (Shared IPv4)
- **Release IP:** 137.66.58.225
- **Ports:** 51820 (WireGuard), 8443 (API)
- **Purpose:** Shared VPN server (bandwidth constrained)

### Fly.io Server 4 (Toronto) - SHARED
- **IP:** 66.241.125.247 (Shared IPv4)
- **Release IP:** 37.16.6.139
- **Ports:** 51820 (WireGuard), 8080 (API)
- **Purpose:** Shared VPN server (bandwidth constrained)

**Total Contabo Monthly Cost:** $12.90

---

## 🔐 ALL CREDENTIALS

### GoDaddy cPanel
- **Username:** 26853687
- **Password:** Asasasas4!

### FTP Access
- **Host:** the-truth-publishing.com
- **User:** kahlen@the-truth-publishing.com
- **Pass:** AndassiAthena8
- **Port:** 21

### PayPal API
- **Display App Name:** MyApp_ConnectionPoint_Systems_Inc
- **Client ID:** ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
- **Secret Key:** EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
- **Business Account Email:** paulhalonen@gmail.com
- **Webhook URL:** https://builder.the-truth-publishing.com/api/paypal-webhook.php
- **Webhook ID:** 46924926WL757580D

### Contabo Login
- **Username:** paulhalonen@gmail.com
- **Password:** Asasasas4!

### Fly.io Login
- **Username:** paulhalonen@gmail.com
- **Password:** Asasasas4!

### VIP User Information
- **Email:** seige235@yahoo.com
- **Dedicated Server:** 144.126.133.253 (US-Central)
- **Special Status:** Automatically approved VIP

---

## 📁 PROJECT FILES CONTEXT

### truthvault_scanner.py
A Python network scanner that:
- Scans local network for devices
- Identifies devices by MAC address vendor (extensive database of Geeni, Wyze, Hikvision, Dahua, Amcrest, Reolink, Ring, Nest, Amazon, Roku, Apple, Samsung, gaming consoles, printers, routers, Raspberry Pi)
- Checks common ports (80, 443, 554, 8080, 8554, 9100, 515, 631, 5000, 32400, 8096, 3389, 5900, 22, 23)
- Runs local web server on port 8888
- Syncs discovered devices to TrueVault API
- Has nice web UI for device selection and sync

### AUTOMATION_SYSTEM_USER_GUIDE.md
Documents an existing automation system with:
- 12 automated workflows
- 19 email templates
- JWT authentication
- Workflow engine with scheduled tasks
- Support for new customer onboarding, payment processing, support tickets, complaints, server alerts, cancellation handling, VIP approval

---

## ⚠️ IMPORTANT NOTES

1. **Location:** Everything goes in `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com` - NOT the main root folder which is the user's personal book website.

2. **VIP Server:** The Contabo US-Central server (144.126.133.253) is EXCLUSIVELY for the VIP user seige235@yahoo.com. It must reject all other connections.

3. **Database-Driven:** ALL styling, colors, fonts, buttons must come from the database. Zero hardcoded styles.

4. **SQLite Structure:** Multiple separate database files, NOT one big database. Properly compartmentalized.

5. **Key Generation:** WireGuard keys and certificates are generated ON THE VPN SERVERS, not on the web server.

6. **Portability:** The entire system must be easily movable to a new location after launch.

7. **Chat Logging:** Append to chat_log.txt throughout the conversation, not just at the end.

8. **Camera Controls:** The camera dashboard needs full control: floodlight, motion detection, 2-way audio, etc.

9. **Scanner:** Force cloud cameras (like Geeni) to be visible locally, not just through cloud.

10. **FileMaker Pro Style:** The database creator in the business dashboard should work like FileMaker Pro - visual schema design, form generation, sample data generation.

---

## 🎨 BRANDING

**Product Name:** TrueVault VPN (trademark)
**Tagline:** "Your Complete Digital Fortress"
**Key Messaging:**
- "Your Privacy. Your Keys. Your Control."
- "256-bit Military-Grade Encryption"
- "Zero Log Policy"
- "50+ Countries"
- "Unlimited Devices"

**Color Scheme (from marketing image):**
- Primary: Cyan/Teal (#00d9ff)
- Secondary: Green (#00ff88)
- Accent: Coral/Red (#ff6b6b)
- Background: Dark navy/purple (#0f0f1a)
- Cards: Semi-transparent white (rgba(255,255,255,0.04))

---

## 📞 SUPPORT CONTACT

- **Email:** paulhalonen@gmail.com
- **Website:** https://vpn.the-truth-publishing.com

---

**END OF USER VISION DOCUMENT**
