# TRUEVAULT COMPLETE SYSTEM EXPANSION SPECIFICATION

**Date:** January 14, 2026  
**Version:** 3.0 EXPANSION  
**Status:** PLANNING & IMPLEMENTATION  

---

## 📋 EXECUTIVE SUMMARY

TrueVault VPN is expanding from a simple VPN service into a **Complete Business Automation Platform + VPN Service**. This document specifies all new features and systems to be implemented.

---

## 🎯 PHASE 1: NETWORK SCANNER + DEVICE MANAGEMENT (CURRENT)

### 1.1 Network Scanner Application

**Status:** ✅ Already exists (`truthvault_scanner.py`)  
**Purpose:** Discover all devices on user's home network  
**Platforms:** Windows, Mac, Linux (Python-based)

**Features:**
- Scans entire local network (192.168.x.x or 10.0.x.x)
- Identifies devices by MAC address vendor lookup
- Detects device types:
  - IP Cameras (Geeni, Wyze, Hikvision, Dahua, Amcrest, Reolink, Ring, Nest)
  - Gaming Consoles (Xbox, PlayStation, Nintendo)
  - Smart TVs (Roku, Samsung, LG, Amazon Fire)
  - Smart Home Devices (Amazon Echo, Google Home, Nest)
  - Printers (HP, Epson, Canon, Brother, Samsung)
  - Routers/Network Equipment
  - Computers/Phones/Tablets
- Checks common ports to determine capabilities
- Opens web interface at http://localhost:8888
- Allows user to select devices to add to VPN
- Syncs selected devices to TruthVault dashboard

**Distribution:**
- Download from dashboard: "Download Network Scanner" button
- Includes batch file (Windows) and shell script (Mac/Linux)
- Auto-opens browser when launched
- Prompts for email and auth token

**Files:**
- `truthvault_scanner.py` - Main scanner (663 lines)
- `run_scanner.bat` - Windows launcher
- `run_scanner.sh` - Mac/Linux launcher
- `README.txt` - Setup instructions

---

### 1.2 Enhanced Device Limits & Categories

#### Device Categories

**HOME NETWORK DEVICES (High Bandwidth):**
- IP Cameras
- Gaming Consoles (Xbox, PlayStation, Nintendo)
- Smart TVs
- Torrent Clients
- Large file transfers

**PERSONAL DEVICES (Low Bandwidth):**
- Laptops
- Desktop PCs
- Phones
- Tablets
- E-readers

#### Plan Limits

| Plan | Home Network Devices | Personal Devices | Total | Notes |
|------|---------------------|------------------|-------|-------|
| **Basic** | 3 | 3 | 6 | 1 camera max |
| **Family** | 5 | 5 | 10 | 2 cameras max |
| **Dedicated** | Unlimited* | Unlimited | Unlimited | *On dedicated server only |
| **VIP** | 10 | Unlimited | Unlimited | Access to VIP servers |

#### Server Restrictions by Device Type

**New York Server (Unlimited Bandwidth):**
```
✓ ALL device types allowed
✓ Cameras (24/7 streaming)
✓ Gaming consoles
✓ Torrents/P2P
✓ Large downloads
✓ Smart TVs
⚠️ Flagged by Netflix (DON'T use for streaming)
```

**Dallas Server (Limited Bandwidth, Streaming Optimized):**
```
✓ Netflix/streaming (NOT flagged)
✓ Light browsing
✓ Personal devices ONLY
✗ Cameras BLOCKED
✗ Gaming consoles BLOCKED
✗ Torrents BLOCKED
✗ Large downloads BLOCKED
```

**Toronto Server (Limited Bandwidth, Canadian Streaming):**
```
✓ Canadian streaming (NOT flagged)
✓ Light browsing
✓ Personal devices ONLY
✗ Cameras BLOCKED
✗ Gaming consoles BLOCKED
✗ Torrents BLOCKED
✗ Large downloads BLOCKED
```

**St. Louis Server (Dedicated, VIP Only):**
```
✓ ALL device types allowed
✓ Unlimited bandwidth
✓ Exclusive access (seige235@yahoo.com)
✓ No restrictions
```

**All Dedicated Servers (Purchased Plans):**
```
✓ ALL device types allowed
✓ Unlimited bandwidth
✓ Exclusive access
✓ No restrictions
✓ User has full control
```

---

### 1.3 Bandwidth-Based Routing Logic

#### Device Type Detection

**Frontend Detection:**
```javascript
function getDeviceCategory(deviceType) {
    const highBandwidth = [
        'camera', 'ip_camera', 'gaming_console', 
        'smart_tv', 'torrent', 'xbox', 'playstation', 'nintendo'
    ];
    
    return highBandwidth.includes(deviceType) ? 'high_bandwidth' : 'low_bandwidth';
}
```

**Backend Validation:**
```php
function validateDeviceServerAccess($deviceType, $serverId) {
    $highBandwidthDevices = ['camera', 'gaming_console', 'smart_tv'];
    $limitedServers = [3, 4]; // Dallas, Toronto
    
    if (in_array($deviceType, $highBandwidthDevices) && in_array($serverId, $limitedServers)) {
        return [
            'allowed' => false,
            'reason' => 'High-bandwidth devices require NY server or dedicated server'
        ];
    }
    
    return ['allowed' => true];
}
```

#### Auto-Server Selection

When user adds a camera or gaming device, system automatically:
1. Checks if they have access to NY server → Assign to NY
2. If not available → Check if they have dedicated server → Assign there
3. If neither → Show upgrade prompt: "Cameras require NY server or Dedicated plan"

---

### 1.4 Network Scanner API Integration

**New API Endpoint:** `/api/network-scanner.php`

**Purpose:** Receive scanned devices from desktop scanner application

**Request:**
```json
POST /api/network-scanner.php
Authorization: Bearer {user_jwt_token}

{
  "devices": [
    {
      "id": "auto_192_168_1_100",
      "ip": "192.168.1.100",
      "mac": "D8:1D:2E:AA:BB:CC",
      "hostname": "front-camera",
      "vendor": "Geeni",
      "type": "ip_camera",
      "type_name": "Geeni Camera",
      "icon": "📷",
      "open_ports": [
        {"port": 554, "service": "rtsp"},
        {"port": 80, "service": "http"}
      ],
      "discovered_at": "2026-01-14T12:00:00"
    },
    {
      "id": "auto_192_168_1_50",
      "ip": "192.168.1.50",
      "mac": "00:1A:2B:3C:4D:5E",
      "hostname": "xbox-series-x",
      "vendor": "Microsoft",
      "type": "gaming_console",
      "type_name": "Xbox Series X",
      "icon": "🎮",
      "open_ports": [
        {"port": 3074, "service": "xbox-live"}
      ],
      "discovered_at": "2026-01-14T12:00:00"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "devices_received": 2,
    "devices_added": 2,
    "devices_skipped": 0,
    "recommendations": [
      {
        "device_id": "auto_192_168_1_100",
        "device_name": "Geeni Camera",
        "recommended_server": "New York",
        "reason": "Camera requires unlimited bandwidth"
      },
      {
        "device_id": "auto_192_168_1_50",
        "device_name": "Xbox Series X",
        "recommended_server": "New York",
        "reason": "Gaming console requires low latency"
      }
    ],
    "limits": {
      "home_network_devices": {
        "current": 2,
        "max": 5,
        "remaining": 3
      },
      "personal_devices": {
        "current": 2,
        "max": 5,
        "remaining": 3
      }
    }
  }
}
```

**Backend Process:**
1. Authenticate user via JWT
2. Get user's subscription and device limits
3. Validate device count against limits
4. Store devices in `discovered_devices` table
5. Recommend appropriate servers based on device type
6. Return device list with recommendations

---

## 🔌 PHASE 2: PORT FORWARDING SYSTEM

### 2.1 Port Forwarding Overview

**Purpose:** Allow users to access their home network devices remotely through VPN

**Use Cases:**
- Access security cameras from anywhere
- Remote desktop to home computer
- Access NAS/file servers
- Remote gaming (Xbox Live, PlayStation Network)
- Access smart home devices
- Remote printer access

---

### 2.2 Port Forwarding Database Schema

**New Table:** `port_forwards`

```sql
CREATE TABLE port_forwards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT NOT NULL,
    local_ip TEXT NOT NULL,
    local_port INTEGER NOT NULL,
    external_port INTEGER NOT NULL,
    protocol TEXT DEFAULT 'tcp',
    status TEXT DEFAULT 'active',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Example Records:**
```sql
INSERT INTO port_forwards (user_id, device_id, device_name, device_type, local_ip, local_port, external_port, protocol, description)
VALUES 
    (1, 'cam_abc123', 'Front Door Camera', 'camera', '192.168.1.100', 554, 55400, 'tcp', 'RTSP stream'),
    (1, 'xbox_def456', 'Xbox Series X', 'gaming_console', '192.168.1.50', 3074, 30740, 'udp', 'Xbox Live');
```

---

### 2.3 Port Forwarding UI (Drag & Drop)

**Page:** `dashboard/port-forwarding.html`

**Layout:**
```
┌─────────────────────────────────────────────────────────┐
│ Port Forwarding Management                              │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ ┌─────────────────┐       ┌──────────────────────────┐ │
│ │ Your Devices    │       │ Forwarding Rules         │ │
│ │ ━━━━━━━━━━━━━━━ │       │ ━━━━━━━━━━━━━━━━━━━━━━━ │ │
│ │                 │       │                          │ │
│ │ 📷 Front Camera │ ───→  │ 📷 Front Camera          │ │
│ │ 🎮 Xbox Series X│       │    192.168.1.100:554     │ │
│ │ 📺 Living Room TV│      │    → vpn.com:55400       │ │
│ │ 💻 Home PC      │       │    [⚙️ Edit] [❌ Remove] │ │
│ │ 🖨️ HP Printer    │       │                          │ │
│ │                 │       │ 🎮 Xbox Series X         │ │
│ │ [+ Add Device]  │       │    192.168.1.50:3074     │ │
│ │                 │       │    → vpn.com:30740       │ │
│ └─────────────────┘       │    [⚙️ Edit] [❌ Remove] │ │
│                           │                          │ │
│ Drag device here →        │ [+ Add Forwarding Rule]  │ │
│                           └──────────────────────────┘ │
│                                                         │
│ ┌─────────────────────────────────────────────────────┐│
│ │ 💡 Quick Setup Templates                            ││
│ │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━││
│ │ [📷 Security Camera] [🎮 Gaming Console]            ││
│ │ [💻 Remote Desktop] [🖨️ Network Printer]            ││
│ └─────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────┘
```

**Drag & Drop Functionality:**
```javascript
// Allow dropping device onto forwarding area
function allowDrop(ev) {
    ev.preventDefault();
}

// Start dragging device
function drag(ev) {
    ev.dataTransfer.setData("device_id", ev.target.dataset.deviceId);
}

// Drop device to create forwarding rule
function drop(ev) {
    ev.preventDefault();
    const deviceId = ev.dataTransfer.getData("device_id");
    showPortForwardingModal(deviceId);
}
```

---

### 2.4 Port Forwarding Templates

**Pre-configured templates for common devices:**

#### Security Camera Template
```json
{
  "name": "Security Camera",
  "ports": [
    {"local": 554, "external": 55400, "protocol": "tcp", "description": "RTSP stream"},
    {"local": 80, "external": 8000, "protocol": "tcp", "description": "Web interface"}
  ]
}
```

#### Gaming Console Template (Xbox)
```json
{
  "name": "Xbox Series X",
  "ports": [
    {"local": 3074, "external": 30740, "protocol": "udp", "description": "Xbox Live"},
    {"local": 88, "external": 8800, "protocol": "udp", "description": "Kerberos"},
    {"local": 500, "external": 5000, "protocol": "udp", "description": "IPSec"}
  ]
}
```

#### Remote Desktop Template
```json
{
  "name": "Remote Desktop",
  "ports": [
    {"local": 3389, "external": 33890, "protocol": "tcp", "description": "RDP"}
  ]
}
```

#### Network Printer Template
```json
{
  "name": "Network Printer",
  "ports": [
    {"local": 9100, "external": 91000, "protocol": "tcp", "description": "Raw printing"},
    {"local": 631, "external": 6310, "protocol": "tcp", "description": "IPP"}
  ]
}
```

---

### 2.5 Port Forwarding API Endpoints

#### Create Port Forward
```
POST /api/port-forwarding/create.php
Authorization: Bearer {jwt_token}

Request:
{
  "device_id": "cam_abc123",
  "device_name": "Front Door Camera",
  "device_type": "camera",
  "local_ip": "192.168.1.100",
  "local_port": 554,
  "external_port": 55400,
  "protocol": "tcp",
  "description": "RTSP stream"
}

Response:
{
  "success": true,
  "data": {
    "forward_id": 123,
    "access_url": "vpn.the-truth-publishing.com:55400",
    "status": "active"
  }
}
```

#### List Port Forwards
```
GET /api/port-forwarding/list.php
Authorization: Bearer {jwt_token}

Response:
{
  "success": true,
  "data": {
    "forwards": [
      {
        "id": 123,
        "device_name": "Front Door Camera",
        "local_ip": "192.168.1.100",
        "local_port": 554,
        "external_port": 55400,
        "protocol": "tcp",
        "access_url": "vpn.the-truth-publishing.com:55400",
        "status": "active"
      }
    ]
  }
}
```

#### Delete Port Forward
```
POST /api/port-forwarding/delete.php
Authorization: Bearer {jwt_token}

Request:
{
  "forward_id": 123
}

Response:
{
  "success": true,
  "message": "Port forwarding rule deleted"
}
```

---

## 💻 PHASE 3: TERMINAL ACCESS (USER & ADMIN)

### 3.1 User Terminal (Sandboxed)

**Purpose:** Give users command-line access to their VPN settings

**Location:** Dashboard > Terminal

**Capabilities:**
- View their own VPN configuration
- Test connections (ping, traceroute)
- DNS lookup (nslookup, dig)
- View their device list
- View connection logs
- Basic network diagnostics

**Restrictions:**
- Cannot access system files
- Cannot view other users' data
- Cannot execute arbitrary commands
- Pre-defined command whitelist only

**Allowed Commands:**
```bash
# Connection testing
ping <host>
traceroute <host>
mtr <host>

# DNS
nslookup <domain>
dig <domain>

# VPN info
vpn status
vpn devices
vpn servers
vpn logs

# Network diagnostics
ifconfig
ip addr
netstat
ss
```

---

### 3.2 Admin Terminal (Full Access)

**Purpose:** Full system access for administrators

**Location:** Admin Panel > Terminal

**Capabilities:**
- Full Linux/Unix shell access
- Database queries
- Server management
- User management
- Log analysis
- System monitoring
- File management

**Implementation:**
- Web-based terminal emulator (xterm.js)
- Secure websocket connection
- Command logging for audit
- Session timeout (30 minutes)

---

## 🗄️ PHASE 4: DATABASE BUILDER (FileMaker-Pro Style)

**Status:** Future implementation  
**Priority:** Medium  
**Estimated Time:** 5-7 days

### Features:
- Visual table designer
- Drag-and-drop field creation
- Relationship mapping
- Form builder
- Report designer
- Query builder
- Import/export tools

**Technology:**
- GrapesJS for visual design
- SQLite for database backend
- PHP for server-side logic
- React for frontend components

---

## 💰 PHASE 5: ACCOUNTING SYSTEM

**Status:** Future implementation  
**Priority:** High  
**Estimated Time:** 3-5 days

### Features:
- Revenue tracking
- Invoice management
- Payment processing
- Expense tracking
- Profit/loss reports
- Tax reporting
- Financial dashboards

**Integration:**
- PayPal API (existing)
- Stripe API (add later)
- Subscription tracking
- Automated billing

---

## 📧 PHASE 6: MARKETING AUTOMATION

**Status:** Future implementation  
**Priority:** High  
**Estimated Time:** 3-4 days

### Features:
- 360 days of pre-written ads
- Auto-posting 3x per week
- Press release distribution
- Email campaigns
- Social media scheduling
- Analytics tracking

**Distribution Channels:**
- Free classified sites
- Press release sites
- Social media platforms
- Email newsletters

---

## 📨 PHASE 7: EMAIL MANAGEMENT SYSTEM

**Status:** Future implementation  
**Priority:** Medium  
**Estimated Time:** 2-3 days

### Features:
- Client communication
- Marketing emails
- Vendor emails
- Transactional emails
- Templates
- Auto-responders
- Tracking

---

## 📄 IMPLEMENTATION STATUS

| Phase | Component | Status | Priority | Est. Time |
|-------|-----------|--------|----------|-----------|
| 1 | Network Scanner | ✅ Exists | Critical | Complete |
| 1 | Enhanced Device Limits | ⏳ In Progress | Critical | 1 day |
| 1 | Bandwidth Routing | ⏳ In Progress | Critical | 1 day |
| 1 | Scanner API Integration | ⏳ Pending | Critical | 1 day |
| 2 | Port Forwarding UI | ⏳ Pending | High | 2 days |
| 2 | Port Forwarding API | ⏳ Pending | High | 1 day |
| 3 | User Terminal | ⏳ Pending | Medium | 2 days |
| 3 | Admin Terminal | ⏳ Pending | Medium | 1 day |
| 4 | Database Builder | ⏳ Future | Medium | 7 days |
| 5 | Accounting System | ⏳ Future | High | 5 days |
| 6 | Marketing Automation | ⏳ Future | High | 4 days |
| 7 | Email Management | ⏳ Future | Medium | 3 days |

---

## 🚀 NEXT IMMEDIATE STEPS

1. **Copy Network Scanner to Repository** ✅
2. **Create Network Scanner API** (`/api/network-scanner.php`)
3. **Update Device Limits Logic** (2 categories: home network + personal)
4. **Implement Bandwidth-Based Routing**
5. **Create Port Forwarding System** (drag & drop UI)
6. **Test Complete Flow**

---

**Document Version:** 1.0  
**Last Updated:** January 14, 2026  
**Author:** System Architect  
**Status:** ACTIVE DEVELOPMENT
