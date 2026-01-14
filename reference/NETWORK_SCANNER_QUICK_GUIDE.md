# NETWORK SCANNER + DEVICE MANAGEMENT - QUICK IMPLEMENTATION GUIDE

**Date:** January 14, 2026 05:30 UTC  
**Status:** Phase 1 Core Components Created  

---

## ✅ WHAT'S BEEN CREATED

### 1. System Expansion Specification
**File:** `reference/SYSTEM_EXPANSION_SPECIFICATION.md` (691 lines)
**Contains:**
- Complete expansion roadmap (7 phases)
- Network scanner integration details
- Enhanced device limits (home network + personal devices)
- Bandwidth-based routing rules
- Port forwarding system design
- Terminal access specifications
- Future features (Database Builder, Accounting, Marketing, Email)

### 2. Network Scanner API Endpoint  
**File:** `api/network-scanner.php` (292 lines)
**Features:**
- Receives scanned devices from desktop scanner
- Categorizes devices (home_network vs personal)
- Validates device limits by plan type
- Provides server recommendations based on bandwidth needs
- Stores devices in discovered_devices table
- Returns limits and remaining slots

**Endpoint:** `POST /api/network-scanner.php`

### 3. Enhanced Database Schema
**File:** `database/enhanced-device-schema.sql` (39 lines)
**New Tables:**
- `discovered_devices` - Stores network-scanned devices
- `port_forwards` - Port forwarding rules (for Phase 2)

---

## 🎯 NEW DEVICE LIMITS SYSTEM

### Device Categories

**HOME NETWORK DEVICES** (High Bandwidth):
- IP Cameras
- Gaming Consoles (Xbox, PlayStation, Nintendo)
- Smart TVs
- NAS devices

**PERSONAL DEVICES** (Low Bandwidth):
- Laptops
- Desktop PCs
- Phones
- Tablets

### Plan Limits

| Plan | Home Network | Personal | Total |
|------|--------------|----------|-------|
| Basic | 3 | 3 | 6 |
| Family | 5 | 5 | 10 |
| Dedicated | Unlimited* | Unlimited | Unlimited |
| VIP | 10 | Unlimited | Unlimited |

*Unlimited on dedicated server only

---

## 🌐 BANDWIDTH-BASED ROUTING

### Server Rules

**🗽 New York (Unlimited Bandwidth):**
```
✓ ALL devices allowed
✓ Cameras (24/7 streaming)
✓ Gaming consoles
✓ Torrents/P2P
✓ Large downloads
⚠️ Flagged by Netflix
```

**🤠 Dallas (Limited - Streaming Only):**
```
✓ Netflix/streaming (NOT flagged)
✓ Personal devices
✗ Cameras BLOCKED
✗ Gaming BLOCKED
✗ Torrents BLOCKED
```

**🍁 Toronto (Limited - Canadian Streaming):**
```
✓ Canadian streaming (NOT flagged)
✓ Personal devices
✗ Cameras BLOCKED
✗ Gaming BLOCKED
✗ Torrents BLOCKED
```

**🔒 Dedicated Servers (Unlimited):**
```
✓ Everything allowed
✓ Exclusive access
✓ No restrictions
```

---

## 🔧 HOW IT WORKS

### User Flow

1. **Download Scanner**
   - User clicks "Download Network Scanner" on dashboard
   - Downloads `truthvault_scanner.py` + batch/shell scripts
   - Scanner included in project files already

2. **Run Scanner**
   - User runs `run_scanner.bat` (Windows) or `run_scanner.sh` (Mac/Linux)
   - Enters email and auth token from dashboard
   - Scanner opens browser to `http://localhost:8888`

3. **Scan Network**
   - Scanner discovers all devices on local network
   - Identifies device types by MAC address
   - Checks ports to determine capabilities
   - Displays devices with icons

4. **Select Devices**
   - User selects devices to add (checkboxes)
   - Can filter by type (cameras, gaming, etc.)
   - See device counts vs. limits

5. **Sync to TruthVault**
   - User clicks "Sync to TruthVault"
   - Scanner sends devices to API via JWT auth
   - API categorizes devices
   - API validates limits
   - API provides server recommendations
   - Dashboard shows new devices

6. **Connect Devices**
   - User goes to dashboard > devices
   - Sees discovered devices with recommendations
   - Clicks "Add to VPN" on each device
   - System assigns to appropriate server
   - Downloads config for each device

---

## 📊 API INTEGRATION

### Scanner → API Flow

**Scanner sends:**
```json
POST /api/network-scanner.php
Authorization: Bearer {user_jwt}

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
      "icon": "📷"
    }
  ]
}
```

**API responds:**
```json
{
  "success": true,
  "data": {
    "devices_added": 1,
    "recommendations": [
      {
        "device_id": "auto_192_168_1_100",
        "device_name": "Geeni Camera",
        "recommended_server": "New York",
        "reason": "High-bandwidth device requires unlimited bandwidth server"
      }
    ],
    "limits": {
      "home_network_devices": {
        "current": 1,
        "max": 3,
        "remaining": 2
      }
    }
  }
}
```

---

## 🚀 DEPLOYMENT STEPS

### 1. Upload API Endpoint
```
Upload: api/network-scanner.php
To: /public_html/vpn.the-truth-publishing.com/api/network-scanner.php
```

### 2. Run Database Migration
```sql
-- Connect to devices.db
sqlite3 devices.db < database/enhanced-device-schema.sql
```

### 3. Package Scanner for Download
```
Create ZIP file containing:
- truthvault_scanner.py (from /mnt/project/)
- run_scanner.bat (from /mnt/project/)
- run_scanner.sh (from /mnt/project/)
- README.txt (from /mnt/project/)

Upload to: /public_html/vpn.the-truth-publishing.com/downloads/network-scanner.zip
```

### 4. Add Download Button to Dashboard
```html
<a href="/downloads/network-scanner.zip" class="btn btn-primary">
  📥 Download Network Scanner
</a>
```

### 5. Create Discovered Devices Page
**File:** `dashboard/discovered-devices.html`

**Features:**
- List all scanned devices
- Show device type, IP, MAC, hostname
- Show category (home network / personal)
- Show server recommendation
- "Add to VPN" button for each
- Device count vs. limits

---

## 📋 TESTING CHECKLIST

- [ ] Upload network-scanner.php API
- [ ] Run database migration (add tables)
- [ ] Package scanner ZIP file
- [ ] Add download button to dashboard
- [ ] Test scanner download
- [ ] Test scanner execution (Windows)
- [ ] Test scanner execution (Mac/Linux)
- [ ] Test device discovery
- [ ] Test device sync to API
- [ ] Test device limit validation
- [ ] Test bandwidth-based recommendations
- [ ] Test adding devices to VPN
- [ ] Verify camera → NY server assignment
- [ ] Verify gaming → NY server assignment
- [ ] Verify personal → any server allowed
- [ ] Test exceeding device limits
- [ ] Test upgrade prompts

---

## 🔮 WHAT'S NEXT (PHASE 2)

### Port Forwarding System
- Drag-and-drop device management UI
- Port forwarding configuration
- Pre-built templates (camera, gaming, remote desktop)
- External URL generation
- Enable/disable forwarding per device

**Files to create:**
- `api/port-forwarding/create.php`
- `api/port-forwarding/list.php`
- `api/port-forwarding/delete.php`
- `dashboard/port-forwarding.html`

### Terminal Access
- User terminal (sandboxed)
- Admin terminal (full access)
- Web-based terminal emulator (xterm.js)

---

## 📁 FILES REFERENCE

**Created This Session:**
1. `reference/SYSTEM_EXPANSION_SPECIFICATION.md` - Complete roadmap
2. `api/network-scanner.php` - Scanner API endpoint
3. `database/enhanced-device-schema.sql` - Database schema

**Already Exists (Project Files):**
1. `/mnt/project/truthvault_scanner.py` - Network scanner app
2. `/mnt/project/run_scanner.bat` - Windows launcher
3. `/mnt/project/run_scanner.sh` - Mac/Linux launcher
4. `/mnt/project/README.txt` - Setup instructions

---

## 💡 IMPORTANT NOTES

1. **Scanner Already Built:** The network scanner application is complete and functional. Just needs to be packaged and distributed.

2. **Device Limits Are Soft:** System validates limits at sync time, but doesn't forcefully disconnect devices. Allows grace period for users to remove devices.

3. **Bandwidth Detection:** System uses device type to determine bandwidth requirements. Cameras and gaming consoles are automatically flagged as high-bandwidth.

4. **Server Auto-Assignment:** When adding device to VPN, system automatically selects appropriate server based on device type and user's plan.

5. **Future Scalability:** Database schema supports port forwarding (Phase 2) and device categories for future bandwidth management features.

---

**Status:** Core Phase 1 components ready for deployment!  
**Next:** Upload API, package scanner, test complete flow  
**Timeline:** Phase 1 deployment: 1-2 days, Phase 2 (port forwarding): 2-3 days
