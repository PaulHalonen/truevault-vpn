# TrueVault VPN - Complete System Architecture & Action Plan
## Master Build Document v1.0
**Created:** January 11, 2026
**Project Location:** `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com`
**Local Dev:** `E:\Documents\GitHub\truevault-vpn`
**Database Type:** SQLite (Separate files, NOT clumped)

---

# TABLE OF CONTENTS

1. [Executive Summary](#1-executive-summary)
2. [Vision & Core Concept](#2-vision--core-concept)
3. [System Architecture Overview](#3-system-architecture-overview)
4. [Database Architecture](#4-database-architecture)
5. [Server Infrastructure](#5-server-infrastructure)
6. [Three Dashboard System](#6-three-dashboard-system)
7. [Network Scanner System](#7-network-scanner-system)
8. [Camera Dashboard System](#8-camera-dashboard-system)
9. [VPN Core Features](#9-vpn-core-features)
10. [Automation Engine](#10-automation-engine)
11. [Certificate Authority System](#11-certificate-authority-system)
12. [API Architecture](#12-api-architecture)
13. [Security Implementation](#13-security-implementation)
14. [Technology Stack](#14-technology-stack)
15. [File Structure](#15-file-structure)
16. [Action Plan Checklist](#16-action-plan-checklist)
17. [Credentials & Access](#17-credentials--access)

---

# 1. EXECUTIVE SUMMARY

## What We're Building
**TrueVault VPN™** - A next-generation VPN service that goes beyond traditional IP masking to provide:
- **Smart Identity Router** - Persistent digital identities per region
- **Family/Team Mesh Network** - Private overlay network connecting all devices
- **Personal Certificate Authority** - Each user gets their own PKI infrastructure
- **Context-Aware Adaptive Routing** - Automated traffic optimization
- **Network Device Discovery** - Scan and manage home devices including IP cameras
- **Camera Dashboard** - Full control of discovered cameras with live feeds

## Key Differentiators
1. **You Own Your Keys** - Personal certificate infrastructure
2. **Invisible Mode** - Traffic obfuscation that bypasses VPN detection
3. **Decentralized Architecture** - No single point of failure
4. **Device Management** - Port forwarding, camera control, IoT management
5. **100% Automated** - No human intervention needed for operations

---

# 2. VISION & CORE CONCEPT

## Original Concept Features

### 2.1 Smart Identity Router
Maintains **persistent "digital identities"** for different regions:
- Same Canadian IP every time for Canadian banking
- Consistent browser fingerprints
- Timezone settings match region
- Banks don't flag as "suspicious VPN usage"

### 2.2 Mesh Family/Team Network
Private overlay network connecting all devices:
- Appears as same local network regardless of physical location
- Direct device access without port forwarding
- Share printers across the country
- Like Tailscale but simplified

### 2.3 Decentralized Bandwidth Marketplace
- Users contribute spare bandwidth, earn credits
- No central servers to subpoena
- Traffic routes through multiple residential IPs

### 2.4 Context-Aware Adaptive Routing (Automated)
System learns patterns:
- Banking app → Trusted home-region server (minimal hops, speed)
- News browsing → Privacy-optimized multi-hop route
- Gaming → Prioritize lowest latency
- Streaming → Optimize for bandwidth

## Pricing Tiers

| Feature | Personal ($9.99) | Family ($14.99) | Business ($29.99) |
|---------|-----------------|-----------------|-------------------|
| Devices | 3 | Unlimited | Unlimited |
| Certificates | Personal | Full Suite | Enterprise |
| Regional Identities | 3 | All | All |
| Mesh Network | No | 6 users | 25 users |
| Support | 24/7 | Priority | Dedicated |
| Admin Dashboard | No | No | Yes |
| API Access | No | No | Yes |

---

# 3. SYSTEM ARCHITECTURE OVERVIEW

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         TrueVault VPN System                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐ │
│  │ CLIENT DASHBOARD│  │MANAGEMENT DASH  │  │ MARKETING/ACCOUNTING/DB     │ │
│  │                 │  │                 │  │ CREATOR DASHBOARD           │ │
│  │ • VPN Connect   │  │ • User Mgmt     │  │ • FileMaker Pro Style       │ │
│  │ • Camera View   │  │ • Server Mgmt   │  │ • Database Creator          │ │
│  │ • Device Mgmt   │  │ • Cert Mgmt     │  │ • Sample File Generator     │ │
│  │ • Port Forward  │  │ • Analytics     │  │ • Marketing CMS             │ │
│  │ • Mesh Network  │  │ • Automation    │  │ • Accounting System         │ │
│  │ • Certificates  │  │ • Logs          │  │ • GrapesJS Page Builder     │ │
│  └────────┬────────┘  └────────┬────────┘  └─────────────┬───────────────┘ │
│           │                    │                          │                 │
│           └────────────────────┼──────────────────────────┘                 │
│                                │                                            │
│                    ┌───────────┴───────────┐                                │
│                    │   UNIFIED API LAYER   │                                │
│                    │   (PHP + React)       │                                │
│                    └───────────┬───────────┘                                │
│                                │                                            │
│  ┌─────────────────────────────┼─────────────────────────────────────────┐ │
│  │                     SQLite Databases                                   │ │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐    │ │
│  │  │ users.db │ │devices.db│ │ certs.db │ │ vpn.db   │ │ cms.db   │    │ │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘    │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────────┐ │
│  │                        VPN SERVER CLUSTER                              │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────┐ │ │
│  │  │ Contabo US-E │  │Contabo US-C  │  │ Fly.io Dallas│  │Fly.io Tor. │ │ │
│  │  │ 66.94.103.91 │  │144.126.133.253│ │66.241.124.4  │  │66.241.125. │ │ │
│  │  │   (Shared)   │  │  (VIP Only)  │  │  (Shared)    │  │  247       │ │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘  └────────────┘ │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

# 4. DATABASE ARCHITECTURE

## 4.1 Database Separation Strategy

All databases are **SQLite** and **separate files** for:
- Portability (easy migration)
- Isolation (one corrupted DB doesn't affect others)
- Performance (smaller files, faster queries)

## 4.2 Database Files

```
/databases/
├── core/
│   ├── users.db           # User accounts, authentication
│   ├── sessions.db        # Active sessions, tokens
│   └── admin.db           # Admin users, roles, permissions
├── vpn/
│   ├── connections.db     # Active VPN connections
│   ├── servers.db         # VPN server configurations
│   ├── certificates.db    # User certificates, CA data
│   ├── identities.db      # Regional identity profiles
│   └── routing.db         # Smart routing rules
├── devices/
│   ├── discovered.db      # Discovered network devices
│   ├── cameras.db         # Camera configurations
│   ├── port_forwarding.db # Port forwarding rules
│   └── mesh_network.db    # Mesh network configurations
├── billing/
│   ├── subscriptions.db   # User subscriptions
│   ├── invoices.db        # Generated invoices
│   ├── payments.db        # Payment records
│   └── transactions.db    # Transaction logs
├── cms/
│   ├── pages.db           # CMS pages (GrapesJS)
│   ├── themes.db          # Theme configurations
│   ├── templates.db       # Email/page templates
│   └── media.db           # Media library
├── automation/
│   ├── workflows.db       # Workflow definitions
│   ├── tasks.db           # Scheduled tasks
│   ├── logs.db            # Automation logs
│   └── emails.db          # Email queue/history
└── analytics/
    ├── usage.db           # Usage statistics
    ├── bandwidth.db       # Bandwidth tracking
    └── events.db          # Event logs
```

## 4.3 Key Database Schemas

### users.db
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    plan_type TEXT DEFAULT 'personal',
    status TEXT DEFAULT 'pending',
    is_vip INTEGER DEFAULT 0,
    vip_server_id INTEGER,
    device_limit INTEGER DEFAULT 3,
    mesh_user_limit INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    email_verified INTEGER DEFAULT 0,
    two_factor_enabled INTEGER DEFAULT 0,
    two_factor_secret TEXT
);

CREATE TABLE user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, setting_key)
);

CREATE TABLE user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_uuid TEXT UNIQUE NOT NULL,
    device_name TEXT,
    device_type TEXT,
    public_key TEXT,
    last_connected DATETIME,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### themes.db (DATABASE-DRIVEN STYLING)
```sql
CREATE TABLE themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_name TEXT NOT NULL,
    theme_slug TEXT UNIQUE NOT NULL,
    is_active INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE theme_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    setting_category TEXT NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT NOT NULL,
    FOREIGN KEY (theme_id) REFERENCES themes(id),
    UNIQUE(theme_id, setting_category, setting_key)
);

-- Default theme settings:
-- colors: primary, secondary, accent, background, text, success, warning, error
-- typography: font_family, heading_font, font_size_base, line_height
-- buttons: border_radius, padding, hover_effect
-- layout: max_width, sidebar_width, spacing
```

### cameras.db
```sql
CREATE TABLE discovered_cameras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    mac_address TEXT,
    vendor TEXT,
    model TEXT,
    camera_name TEXT,
    rtsp_port INTEGER DEFAULT 554,
    http_port INTEGER DEFAULT 80,
    https_port INTEGER DEFAULT 443,
    username TEXT,
    password_encrypted TEXT,
    stream_url TEXT,
    snapshot_url TEXT,
    is_online INTEGER DEFAULT 1,
    last_seen DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE camera_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT,
    FOREIGN KEY (camera_id) REFERENCES discovered_cameras(id),
    UNIQUE(camera_id, setting_key)
);

CREATE TABLE camera_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,
    event_data TEXT,
    thumbnail_path TEXT,
    video_clip_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES discovered_cameras(id)
);
```

### servers.db
```sql
CREATE TABLE vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_name TEXT NOT NULL,
    server_type TEXT NOT NULL,
    provider TEXT NOT NULL,
    region TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    ipv6_address TEXT,
    wireguard_port INTEGER DEFAULT 51820,
    api_port INTEGER DEFAULT 8080,
    public_key TEXT,
    status TEXT DEFAULT 'active',
    max_connections INTEGER DEFAULT 100,
    current_connections INTEGER DEFAULT 0,
    bandwidth_limit_mbps INTEGER,
    is_vip_only INTEGER DEFAULT 0,
    vip_user_email TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_health_check DATETIME
);
```

---

# 5. SERVER INFRASTRUCTURE

## 5.1 Current Servers

### Contabo Server 1 (US-East) - SHARED
- **IP:** 66.94.103.91
- **IPv6:** 2605:a142:2299:0026:0000:0000:0000:0001
- **VNC:** 154.53.39.97:63031
- **Purpose:** Shared VPN server (bandwidth constrained)
- **SSH:** root@66.94.103.91

### Contabo Server 2 (US-Central) - VIP DEDICATED
- **IP:** 144.126.133.253
- **IPv6:** 2605:a140:2299:0005:0000:0000:0000:0001
- **VNC:** 207.244.248.38:63098
- **Purpose:** DEDICATED for seige235@yahoo.com ONLY
- **SSH:** root@144.126.133.253

### Fly.io Server 3 (Dallas) - SHARED
- **IP:** 66.241.124.4 (Shared IPv4)
- **Release IP:** 137.66.58.225
- **Ports:** 51820 (WireGuard), 8443 (API)
- **Purpose:** Shared VPN server

### Fly.io Server 4 (Toronto) - SHARED
- **IP:** 66.241.125.247 (Shared IPv4)
- **Release IP:** 37.16.6.139
- **Ports:** 51820 (WireGuard), 8080 (API)
- **Purpose:** Shared VPN server

---

# 6. THREE DASHBOARD SYSTEM

## Dashboard 1: CLIENT VPN DASHBOARD
**URL:** `vpn.the-truth-publishing.com/dashboard`
- VPN Connection Management
- Device Management
- Camera Dashboard (live feeds, controls)
- Port Forwarding
- Mesh Network Setup
- Certificate Management
- Regional Identity Selection

## Dashboard 2: MANAGEMENT DASHBOARD
**URL:** `vpn.the-truth-publishing.com/admin`
- User Management (CRUD)
- Server Management
- Certificate Authority Management
- Subscription Management
- System Logs
- Automation Workflow Management
- Theme/CMS Management (database-driven)

## Dashboard 3: BUSINESS DASHBOARD
**URL:** `vpn.the-truth-publishing.com/business`
- **FileMaker Pro Style Database Creator**
  - Visual schema designer
  - Field type definitions
  - Form generator
  - Sample data generator
- **GrapesJS Page Builder**
- **Accounting System**
- **Marketing CMS**

---

# 7. NETWORK SCANNER SYSTEM

## Scanner Features
- Scans local network for devices
- Identifies devices by MAC address vendor
- Supports: Geeni, Wyze, Hikvision, Dahua, Amcrest, Reolink, Ring, Nest, printers, gaming consoles
- Checks common ports (80, 443, 554, 8080, etc.)
- Syncs discovered devices to dashboard
- Forces cloud cameras to be visible locally (Geeni/Tuya)

---

# 8. CAMERA DASHBOARD SYSTEM

## Camera Controls
- Live video feed
- Snapshot capture
- Recording
- Motion detection toggle
- Floodlight control
- Two-way audio
- Night vision mode
- PTZ controls (if supported)
- Event history

---

# 9. VPN CORE FEATURES

## WireGuard Implementation
- Keys generated ON THE VPN SERVERS
- Server-side configuration management
- Client config generation via API

## Smart Identity Router
- Persistent regional IPs
- Browser fingerprint consistency
- Timezone/locale matching

## Mesh Network
- Direct device-to-device connections
- End-to-end encryption
- Invite by email/QR code

---

# 10. AUTOMATION ENGINE

## Workflows
1. New User Signup
2. Scanner Sync
3. Payment Success
4. Payment Failed
5. Certificate Generation
6. VPN Connection
7. Server Health Check
8. Subscription Expiring

---

# 11. CERTIFICATE AUTHORITY SYSTEM

## PKI Architecture
- TrueVault Root CA
- User Personal CA (per user)
- Device Certificates
- Regional Identity Certificates
- Mesh Trust Certificates

---

# 12. API ARCHITECTURE

```
/api/
├── auth/           (login, register, logout, refresh)
├── users/          (profile, settings, devices)
├── vpn/            (servers, connect, disconnect, status)
├── certificates/   (generate, list, download, revoke)
├── devices/        (list, sync, delete)
├── cameras/        (list, stream, control, snapshot, events)
├── port-forwarding/(rules, toggle)
├── mesh/           (invite, members, remove)
├── billing/        (subscription, invoices, webhook)
├── admin/          (users, servers, stats, logs)
├── scanner/        (auth, sync, download)
└── automation/     (engine, workflows, cron)
```

---

# 13. TECHNOLOGY STACK

## Frontend
- React 18
- Tailwind CSS (database-driven theme)
- GrapesJS (page builder)
- Chart.js
- HLS.js (camera streams)

## Backend
- PHP 8.2
- SQLite 3
- JWT Authentication
- PHPMailer

## VPN Servers
- WireGuard
- Ubuntu 24.04
- OpenSSL (certificates)
- Python Flask (API)

---

# 14. FILE STRUCTURE

```
/truevault-vpn/
├── index.html
├── .htaccess
├── api/
│   ├── config/
│   ├── auth/
│   ├── users/
│   ├── vpn/
│   ├── certificates/
│   ├── devices/
│   ├── cameras/
│   ├── port-forwarding/
│   ├── mesh/
│   ├── billing/
│   ├── admin/
│   ├── scanner/
│   ├── automation/
│   └── helpers/
├── dashboard/
├── admin/
├── business/
├── databases/
├── downloads/
├── templates/
├── uploads/
├── logs/
└── reference/
```

---

# 15. CREDENTIALS & ACCESS

## Web Hosting (GoDaddy)
- **cPanel:** 26853687 / Asasasas4!

## FTP Access
- **Host:** the-truth-publishing.com
- **User:** kahlen@the-truth-publishing.com
- **Pass:** AndassiAthena8
- **Port:** 21

## PayPal API
- **Client ID:** ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
- **Secret:** EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
- **Email:** paulhalonen@gmail.com

## Contabo/Fly.io
- **Login:** paulhalonen@gmail.com / Asasasas4!

## VIP User
- **Email:** seige235@yahoo.com
- **Server:** 144.126.133.253 (DEDICATED)

---

# 16. ACTION PLAN CHECKLIST

## PHASE 1: FOUNDATION
- [x] Create GitHub repository
- [x] Clone to local: E:\Documents\GitHub\truevault-vpn
- [x] Create directory structure
- [ ] SSH into servers, verify WireGuard
- [ ] Create all SQLite databases with schemas
- [ ] Insert default data (theme, servers, admin)

## PHASE 2: API DEVELOPMENT
- [ ] Core infrastructure (database.php, jwt.php, helpers)
- [ ] Auth API
- [ ] User API
- [ ] VPN API
- [ ] Certificate API
- [ ] Device/Camera API
- [ ] Port Forwarding API
- [ ] Mesh API
- [ ] Billing API
- [ ] Admin API
- [ ] Scanner API

## PHASE 3: CLIENT DASHBOARD
- [ ] React setup with database-driven theme
- [ ] Login/Registration
- [ ] Dashboard overview
- [ ] VPN Connect page
- [ ] Devices page
- [ ] Camera Dashboard
- [ ] Port Forwarding
- [ ] Mesh Network
- [ ] Certificates
- [ ] Settings

## PHASE 4: MANAGEMENT DASHBOARD
- [ ] Admin layout
- [ ] User management
- [ ] Server management
- [ ] Theme editor (database-driven)
- [ ] System logs

## PHASE 5: BUSINESS DASHBOARD
- [ ] FileMaker-style database creator
- [ ] GrapesJS page builder
- [ ] Accounting system

## PHASE 6: AUTOMATION ENGINE
- [ ] Workflow engine
- [ ] All workflows
- [ ] Email templates
- [ ] Cron setup

## PHASE 7: SCANNER
- [ ] Camera stream discovery
- [ ] Geeni/Tuya local control
- [ ] Scanner download system

## PHASE 8: TESTING & POLISH
- [ ] End-to-end testing
- [ ] Security audit
- [ ] Documentation

## PHASE 9: LAUNCH
- [ ] Final deployment
- [ ] Monitoring setup

---

# CRITICAL RULES

1. **NO HARDCODED STYLES** - All colors, fonts, buttons from database
2. **SEPARATE DATABASES** - Each database is its own SQLite file
3. **VIP SERVER** - 144.126.133.253 is ONLY for seige235@yahoo.com
4. **KEYS ON SERVER** - Certificate generation happens on VPN servers
5. **PORTABLE** - Everything must be easy to migrate

---

**END OF MASTER PLAN**
