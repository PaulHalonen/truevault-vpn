# ADMIN TERMINAL SYSTEM - COMPLETE SPECIFICATION

**Version:** 3.0  
**Date:** January 14, 2026  
**Status:** Design Complete, Ready for Implementation  

---

## 🎯 OVERVIEW

The Admin Terminal is a powerful troubleshooting tool available ONLY on the **Management Dashboard** (not user dashboard). It allows administrators to access any user's VPN system for support and troubleshooting.

### Key Features
- **User Lookup:** Enter email to load user's VPN system
- **Two Modes:** Tech Mode (terminal) & Non-Tech Mode (GUI)
- **Complete Access:** View configs, logs, devices, connections
- **Safe Operations:** All changes are logged and reversible

---

## 🔐 ACCESS CONTROL

### Location
- **Management Dashboard ONLY**
- URL: `https://manage.the-truth-publishing.com/admin-terminal.html`
- Requires admin authentication (separate from user auth)

### Permissions
- Only users with `role='admin'` or `role='super_admin'` can access
- All actions are logged in `admin_activity_log`
- User email is required to access any user's system

### Security
- Admin cannot see user passwords
- Admin actions are audit-logged
- Admin cannot delete users (only suspend)
- Admin session timeout: 30 minutes

---

## 💻 TECH MODE (For Technical Admins)

### Purpose
Full command-line access for experienced administrators to troubleshoot complex issues.

### Interface
```
┌────────────────────────────────────────────────────────────────┐
│ Admin Terminal - Tech Mode                                     │
│ User: john@example.com | Status: Active | Plan: Family        │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ admin@truevault:~$ cat /user/john@example.com/config.conf    │
│ [Interface]                                                    │
│ PrivateKey = abc123...                                         │
│ Address = 10.0.0.15/32                                        │
│ DNS = 1.1.1.1, 8.8.8.8                                        │
│                                                                │
│ [Peer]                                                         │
│ PublicKey = xyz789...                                          │
│ Endpoint = 66.94.103.91:51820                                 │
│ AllowedIPs = 0.0.0.0/0                                        │
│                                                                │
│ admin@truevault:~$ █                                          │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### Available Commands

#### User Information
```bash
user info                    # Show user details (plan, status, devices)
user devices                 # List all user's devices
user subscription            # Show subscription details
user limits                  # Show device limits and usage
user history                 # Show account activity history
```

#### VPN Configuration
```bash
vpn status                   # Check VPN connection status
vpn config                   # Show WireGuard config
vpn keys                     # Show user's keys
vpn test                     # Test VPN connection
vpn restart                  # Restart user's VPN connection
```

#### Device Management
```bash
device list                  # List all devices
device info <device_id>      # Show device details
device logs <device_id>      # Show device connection logs
device reconnect <device_id> # Force device reconnection
device remove <device_id>    # Remove device
```

#### Server Operations
```bash
server status                # Show server status
server switch <server_id>    # Switch user to different server
server bandwidth             # Show bandwidth usage
server peers                 # Show active peers on server
```

#### Logs & Diagnostics
```bash
logs connection              # Show connection logs
logs errors                  # Show error logs
logs bandwidth               # Show bandwidth usage logs
ping <destination>           # Test connectivity
traceroute <destination>     # Trace route to destination
```

#### Database Access
```bash
db query <sql>               # Execute SQL query (read-only)
db users                     # List all users
db devices                   # List all devices
db subscriptions             # List all subscriptions
```

### Command Examples

**Example 1: User Reporting Connection Issues**
```bash
admin@truevault:~$ user info
Email: john@example.com
Status: Active
Plan: Family
Devices: 4/5 home network, 3/5 personal
Server: New York (NY-01)
Connected: Yes
Last Seen: 2 minutes ago

admin@truevault:~$ vpn test
Testing connection to New York server...
✓ Handshake successful
✓ Ping: 45ms
✓ Download: 95 Mbps
✓ Upload: 20 Mbps
Connection is healthy.

admin@truevault:~$ logs errors
[2026-01-14 05:30:15] Camera device handshake timeout
[2026-01-14 05:28:42] Device "Front Camera" connection lost
[2026-01-14 05:25:10] Peer keepalive failed (3 attempts)

admin@truevault:~$ device reconnect dev_cam_abc123
Reconnecting device "Front Camera"...
✓ Removed old peer
✓ Generated new handshake
✓ Added peer to server
✓ Device reconnected successfully
```

**Example 2: User Needs Server Switch**
```bash
admin@truevault:~$ user devices
Device ID        | Type         | Name           | Server
dev_cam_abc123   | Camera       | Front Camera   | New York
dev_xbox_def456  | Gaming       | Xbox Series X  | New York
dev_laptop_ghi789| Laptop       | Work Laptop    | New York

admin@truevault:~$ server switch 3
Switching user to Dallas server...
⚠️  Warning: 2 high-bandwidth devices detected (Camera, Gaming)
    These devices require New York server for optimal performance.
    
Continue? (y/n): n

admin@truevault:~$ server switch 3 --device dev_laptop_ghi789
Switching device "Work Laptop" to Dallas server...
✓ Removed peer from New York
✓ Added peer to Dallas
✓ Generated new config
✓ Device switched successfully
New config available at: /user/john@example.com/devices/laptop/config.conf
```

---

## 🎨 NON-TECH MODE (For Business Owners)

### Purpose
Graphical interface with guided instructions for common tasks. Perfect for business owners who need to help users but aren't technical.

### Interface
```
┌────────────────────────────────────────────────────────────────┐
│ Admin Support Panel - Non-Tech Mode                           │
│                                                                │
│ 👤 User: john@example.com                                     │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                                │
│ 📊 User Status                                                │
│ ┌────────────────────────────────────────────────────────┐   │
│ │ ✅ VPN Active        🌍 Server: New York              │   │
│ │ 💳 Plan: Family      📱 Devices: 7/10                 │   │
│ │ 🔗 Connected: Yes    ⏱️ Last Seen: 2 min ago          │   │
│ └────────────────────────────────────────────────────────┘   │
│                                                                │
│ 🛠️ Quick Actions                                              │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                                │
│ [🔄 Restart VPN Connection]                                   │
│ ℹ️ Fixes most connection issues. Takes 10 seconds.           │
│                                                                │
│ [📱 View All Devices]                                         │
│ ℹ️ See all devices and their status. Fix device issues.      │
│                                                                │
│ [📊 Check Bandwidth Usage]                                    │
│ ℹ️ See how much data user has used. Identify problems.       │
│                                                                │
│ [🌐 Switch Server Location]                                   │
│ ℹ️ Move user to different server. Improves speed/access.     │
│                                                                │
│ [📋 View Error Logs]                                          │
│ ℹ️ See what went wrong. Get troubleshooting suggestions.     │
│                                                                │
│ [💬 Send Message to User]                                     │
│ ℹ️ Email user with update or request more info.              │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### Guided Workflows

#### Workflow 1: Restart VPN Connection
```
Step 1: Click [🔄 Restart VPN Connection]

┌────────────────────────────────────────────────────────────────┐
│ Restart VPN Connection                                         │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ This will:                                                     │
│ ✓ Disconnect user temporarily (5-10 seconds)                  │
│ ✓ Clear connection cache                                      │
│ ✓ Reconnect with fresh handshake                              │
│ ✓ Test connection                                             │
│                                                                │
│ ⚠️ User will lose connection briefly                          │
│                                                                │
│ [Cancel]                            [Restart Connection]      │
└────────────────────────────────────────────────────────────────┘

Step 2: User clicks [Restart Connection]

┌────────────────────────────────────────────────────────────────┐
│ Restarting Connection...                                       │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ ✓ Disconnected user                                           │
│ ✓ Cleared connection cache                                    │
│ ⏳ Reconnecting...                                            │
│                                                                │
│ [████████████████░░░░░░░░] 75%                                │
└────────────────────────────────────────────────────────────────┘

Step 3: Success!

┌────────────────────────────────────────────────────────────────┐
│ ✅ Connection Restarted Successfully!                          │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ User's VPN is now connected and working properly.             │
│                                                                │
│ Connection Test Results:                                      │
│ ✓ Handshake: Successful                                       │
│ ✓ Ping: 45ms (Good)                                          │
│ ✓ Speed: 95 Mbps download, 20 Mbps upload                    │
│                                                                │
│ What to do next:                                              │
│ 📧 Send user an email letting them know it's fixed            │
│ 💬 Or wait for user to confirm it's working                   │
│                                                                │
│ [Send Email]    [View Logs]    [Done]                        │
└────────────────────────────────────────────────────────────────┘
```

#### Workflow 2: Fix Camera Connection
```
Step 1: Click [📱 View All Devices]

┌────────────────────────────────────────────────────────────────┐
│ User's Devices                                                 │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ 📷 Front Door Camera                                          │
│    Status: ❌ Disconnected                                    │
│    Problem: Connection lost 15 minutes ago                     │
│    Server: New York                                            │
│    [Fix Camera Connection] [View Camera Logs]                 │
│                                                                │
│ 🎮 Xbox Series X                                              │
│    Status: ✅ Connected                                       │
│    Server: New York                                            │
│    [View Details]                                              │
│                                                                │
│ 💻 Work Laptop                                                │
│    Status: ✅ Connected                                       │
│    Server: New York                                            │
│    [View Details]                                              │
│                                                                │
└────────────────────────────────────────────────────────────────┘

Step 2: Click [Fix Camera Connection]

┌────────────────────────────────────────────────────────────────┐
│ Fix Camera Connection - Step-by-Step                           │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Problem: Front Door Camera lost connection                     │
│                                                                │
│ I'll walk you through fixing this:                            │
│                                                                │
│ 1️⃣ First, let me reconnect the camera                        │
│    [Reconnect Camera]                                          │
│                                                                │
│ 2️⃣ If that doesn't work, we'll reset the camera             │
│    (You'll need to tell user to unplug/replug camera)        │
│                                                                │
│ 3️⃣ Last resort: Generate new config for camera              │
│    (User will need to re-setup camera)                        │
│                                                                │
└────────────────────────────────────────────────────────────────┘

Step 3: Click [Reconnect Camera]

┌────────────────────────────────────────────────────────────────┐
│ Reconnecting Camera...                                         │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ ✓ Removed old connection                                      │
│ ✓ Generated new handshake                                     │
│ ✓ Added camera back to VPN                                    │
│ ⏳ Testing camera connection...                               │
│                                                                │
│ [████████████████████████] 100%                               │
│                                                                │
│ ✅ Camera Reconnected Successfully!                            │
│                                                                │
│ The camera should be working now. If user still has issues:   │
│ 1. Ask them to unplug camera for 10 seconds                   │
│ 2. Plug camera back in                                         │
│ 3. Wait 30 seconds for camera to reconnect                     │
│                                                                │
│ [Send Instructions to User]    [Done]                         │
└────────────────────────────────────────────────────────────────┘
```

#### Workflow 3: Switch User's Server
```
Step 1: Click [🌐 Switch Server Location]

┌────────────────────────────────────────────────────────────────┐
│ Switch Server Location                                         │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Current Server: 🗽 New York (Unlimited Bandwidth)             │
│                                                                │
│ Available Servers:                                             │
│                                                                │
│ ⚪ 🗽 New York                                                 │
│    Speed: Excellent | Bandwidth: Unlimited                     │
│    Best for: Cameras, Gaming, Downloads                        │
│    ⚠️ Flagged by Netflix                                      │
│                                                                │
│ ⚪ 🤠 Dallas                                                   │
│    Speed: Good | Bandwidth: Limited                            │
│    Best for: Streaming (Netflix OK), Browsing                  │
│    ❌ Cannot use: Cameras, Gaming                             │
│                                                                │
│ ⚪ 🍁 Toronto                                                  │
│    Speed: Good | Bandwidth: Limited                            │
│    Best for: Canadian Streaming, Browsing                      │
│    ❌ Cannot use: Cameras, Gaming                             │
│                                                                │
│ [Cancel]                                    [Next]            │
└────────────────────────────────────────────────────────────────┘

Step 2: User selects Dallas, clicks [Next]

┌────────────────────────────────────────────────────────────────┐
│ ⚠️ Cannot Switch to Dallas                                    │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ This user has high-bandwidth devices that won't work on       │
│ Dallas server:                                                 │
│                                                                │
│ • 📷 Front Door Camera (requires unlimited bandwidth)         │
│ • 🎮 Xbox Series X (requires unlimited bandwidth)            │
│                                                                │
│ Options:                                                       │
│                                                                │
│ 1️⃣ Keep user on New York server (recommended)                │
│                                                                │
│ 2️⃣ Move ONLY low-bandwidth devices to Dallas                 │
│    (like laptop, phone) and keep camera/gaming on New York    │
│                                                                │
│ 3️⃣ Upgrade user to Dedicated server                          │
│    (unlimited bandwidth on any location)                       │
│                                                                │
│ [Go Back]    [Move Some Devices]    [Upgrade User]           │
└────────────────────────────────────────────────────────────────┘
```

---

## 🔍 USER LOOKUP SYSTEM

### Interface
```
┌────────────────────────────────────────────────────────────────┐
│ Admin Terminal                                                 │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Enter user's email to access their VPN system:                │
│                                                                │
│ ┌──────────────────────────────────────────────────────┐     │
│ │ john@example.com                                     │     │
│ └──────────────────────────────────────────────────────┘     │
│                                                                │
│ [Load User System]                                            │
│                                                                │
│ Recent Users:                                                  │
│ • jane@example.com (2 min ago)                                │
│ • support@company.com (10 min ago)                            │
│ • admin@test.com (1 hour ago)                                 │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### After Loading User
```
┌────────────────────────────────────────────────────────────────┐
│ Admin Terminal - john@example.com                             │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Choose Mode:                                                   │
│                                                                │
│ ┌─────────────────────────┐  ┌─────────────────────────┐     │
│ │  💻 Tech Mode           │  │  🎨 Non-Tech Mode       │     │
│ │                         │  │                         │     │
│ │  Full terminal access   │  │  Guided interface       │     │
│ │  All commands available │  │  Step-by-step help      │     │
│ │  For technical admins   │  │  For business owners    │     │
│ │                         │  │                         │     │
│ │  [Launch Terminal]      │  │  [Launch GUI]           │     │
│ └─────────────────────────┘  └─────────────────────────┘     │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

---

## 📊 AUDIT LOGGING

All admin actions are logged:

```sql
CREATE TABLE admin_activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER NOT NULL,
    admin_email TEXT NOT NULL,
    target_user_id INTEGER NOT NULL,
    target_user_email TEXT NOT NULL,
    action TEXT NOT NULL,
    details TEXT,
    mode TEXT, -- 'tech' or 'non_tech'
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id),
    FOREIGN KEY (target_user_id) REFERENCES users(id)
);
```

Example log entries:
```
admin_email: kahlen@truthvault.com
target_user: john@example.com
action: device_reconnect
details: Reconnected device "Front Door Camera" (dev_cam_abc123)
mode: non_tech
```

---

## 🚀 API ENDPOINTS

### Load User System
```
POST /api/admin/load-user.php
Authorization: Bearer {admin_jwt}

Request:
{
  "email": "john@example.com"
}

Response:
{
  "success": true,
  "data": {
    "user": {
      "id": 123,
      "email": "john@example.com",
      "status": "active",
      "plan": "family"
    },
    "subscription": {
      "plan_type": "family",
      "max_devices": 10,
      "current_devices": 7
    },
    "devices": [...],
    "server": {
      "id": 1,
      "name": "New York",
      "status": "online"
    },
    "recent_errors": [...]
  }
}
```

### Execute Tech Command
```
POST /api/admin/exec-command.php
Authorization: Bearer {admin_jwt}

Request:
{
  "user_email": "john@example.com",
  "command": "device reconnect dev_cam_abc123"
}

Response:
{
  "success": true,
  "output": "✓ Device reconnected successfully",
  "logged": true
}
```

### Execute Non-Tech Action
```
POST /api/admin/execute-action.php
Authorization: Bearer {admin_jwt}

Request:
{
  "user_email": "john@example.com",
  "action": "restart_vpn"
}

Response:
{
  "success": true,
  "message": "VPN connection restarted successfully",
  "results": {
    "handshake": "successful",
    "ping": "45ms",
    "speed": "95 Mbps"
  },
  "next_steps": [
    "Send email to user",
    "Wait for user confirmation",
    "Monitor connection for 5 minutes"
  ]
}
```

---

## 📋 IMPLEMENTATION CHECKLIST

### Backend API (PHP)
- [ ] `/api/admin/load-user.php` - Load user system
- [ ] `/api/admin/exec-command.php` - Execute tech commands
- [ ] `/api/admin/execute-action.php` - Execute non-tech actions
- [ ] `/api/admin/get-logs.php` - Get user logs
- [ ] Create `admin_activity_log` table

### Frontend Pages
- [ ] `manage/admin-terminal.html` - Main admin terminal page
- [ ] `manage/admin-terminal-tech.html` - Tech mode interface
- [ ] `manage/admin-terminal-gui.html` - Non-tech mode interface

### JavaScript
- [ ] `admin-terminal.js` - User lookup and mode selection
- [ ] `admin-terminal-tech.js` - Terminal emulator (xterm.js)
- [ ] `admin-terminal-gui.js` - Guided workflow UI

### Security
- [ ] Admin role verification
- [ ] Audit logging for all actions
- [ ] Session timeout (30 minutes)
- [ ] Command whitelist (tech mode)
- [ ] Action validation (non-tech mode)

---

## 🎯 BENEFITS

### For Technical Admins
✅ Full system access  
✅ All troubleshooting tools  
✅ Fast problem resolution  
✅ Direct database access  

### For Business Owners (Non-Tech)
✅ No command-line knowledge needed  
✅ Step-by-step guidance  
✅ Safe operations (can't break anything)  
✅ Professional support experience  

### For Users
✅ Fast support response  
✅ Problems fixed quickly  
✅ Minimal downtime  
✅ Better overall experience  

---

**Status:** Design Complete, Ready for Implementation  
**Priority:** High (essential for support operations)  
**Estimated Time:** 3-4 days (backend + frontend + testing)
