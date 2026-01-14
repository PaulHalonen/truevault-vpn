# REAL-TIME SERVER MONITORING SYSTEM - SPECIFICATION

**Version:** 1.0  
**Date:** January 14, 2026  
**Critical:** Prevent Fly.io bandwidth overages  

---

## 🎯 SYSTEM OVERVIEW

### The Problem
**Fly.io Bandwidth Limits:**
- **Dallas Server:** 100GB outbound bandwidth/month (configurable)
- **Toronto Server:** 100GB outbound bandwidth/month (configurable)
- **Overage Cost:** ~$0.02/GB ($2 per 100GB over limit)

**Contabo Servers:**
- **NY Server:** UNLIMITED bandwidth ✓
- **St. Louis Server:** UNLIMITED bandwidth ✓ (VIP only)

### The Solution
**Real-time monitoring dashboard** that:
1. Tracks bandwidth usage 24/7
2. Shows live statistics for all 4 servers
3. Alerts at 75%, 90%, 95% of monthly limit
4. Automatically redirects users to NY when limit reached
5. Projects if limit will be exceeded this month
6. Tracks costs (if overage occurs)

---

## 📊 MANAGEMENT DASHBOARD - SERVER STATISTICS

### Main Dashboard View
```
┌─────────────────────────────────────────────────────────────┐
│ Server Statistics - Real-Time                                │
│ Last Updated: 2 seconds ago                      [Refresh]   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Monthly Overview (January 2026)                             │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ Total Bandwidth Used: 245.8 GB / Unlimited                  │
│ Total Cost: $0.00 (no overages) ✓                          │
│ Active Users: 83 customers                                  │
│ Active Connections: 127 devices                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 🟢 Server 1: New York (Contabo)                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Status: ● ONLINE  |  Uptime: 28 days  |  Load: 45%         │
│ IP: 66.94.103.91  |  Location: US-East                     │
│                                                             │
│ Bandwidth Usage (This Month):                               │
│ Outbound: 128.5 GB  [████████░░░░░░░░░░░░] UNLIMITED ✓    │
│ Inbound:   45.2 GB                                          │
│                                                             │
│ Current Traffic:                                            │
│ Download: 45.2 Mbps  |  Upload: 12.8 Mbps                  │
│                                                             │
│ Active Users: 45 customers (125 devices)                    │
│ Server Load: 45% CPU | 62% RAM | 38% Disk                 │
│                                                             │
│ [View Details] [Performance Graph] [Server Logs]           │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ ⚠️  Server 3: Dallas (Fly.io) - APPROACHING LIMIT           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Status: ● ONLINE  |  Uptime: 28 days  |  Load: 78%         │
│ IP: 66.241.124.4  |  Location: US-Central (Texas)          │
│                                                             │
│ ⚠️  BANDWIDTH ALERT - 92% OF MONTHLY LIMIT USED             │
│                                                             │
│ Bandwidth Usage (This Month):                               │
│ Outbound: 92.3 GB / 100 GB [████████████████████░] 92%     │
│ Inbound:   31.5 GB                                          │
│                                                             │
│ Remaining: 7.7 GB                                           │
│ Est. Days Left: 3 days (at current rate)                   │
│ Projected Usage: 108 GB (8 GB OVERAGE) ⚠️                   │
│ Overage Cost: ~$0.16                                        │
│                                                             │
│ Current Traffic:                                            │
│ Download: 8.2 Mbps  |  Upload: 3.1 Mbps                    │
│                                                             │
│ Active Users: 18 customers (42 devices)                     │
│ Server Load: 78% CPU | 71% RAM | 45% Disk                 │
│                                                             │
│ ACTIONS:                                                    │
│ ☑ Auto-redirect new users to NY server (enabled)          │
│ ☑ Throttle streaming traffic (enabled)                     │
│ ☑ Send email alerts to admin (enabled)                     │
│                                                             │
│ [View Details] [Manually Redirect Users] [Increase Limit]  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 🟢 Server 4: Toronto (Fly.io)                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Status: ● ONLINE  |  Uptime: 28 days  |  Load: 52%         │
│ IP: 66.241.125.247  |  Location: Canada                    │
│                                                             │
│ Bandwidth Usage (This Month):                               │
│ Outbound: 55.8 GB / 100 GB [███████████░░░░░░░░░] 56%      │
│ Inbound:   18.9 GB                                          │
│                                                             │
│ Remaining: 44.2 GB  ✓ HEALTHY                              │
│ Est. Days Left: 12 days (safe)                             │
│ Projected Usage: 78 GB (within limit) ✓                    │
│                                                             │
│ Current Traffic:                                            │
│ Download: 12.5 Mbps  |  Upload: 4.8 Mbps                   │
│                                                             │
│ Active Users: 20 customers (38 devices)                     │
│ Server Load: 52% CPU | 58% RAM | 42% Disk                 │
│                                                             │
│ [View Details] [Performance Graph] [Server Logs]           │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 🔒 Server 2: St. Louis (Contabo) - VIP ONLY                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Status: ● ONLINE  |  Uptime: 28 days  |  Load: 12%         │
│ IP: 144.126.133.253  |  Location: US-Central               │
│                                                             │
│ Bandwidth Usage (This Month):                               │
│ Outbound: 8.5 GB  [██░░░░░░░░░░░░░░░░░░] UNLIMITED ✓      │
│ Inbound:   2.1 GB                                           │
│                                                             │
│ Current Traffic:                                            │
│ Download: 2.1 Mbps  |  Upload: 0.8 Mbps                    │
│                                                             │
│ Active Users: 1 customer (2 devices) - seige235@yahoo.com  │
│ Server Load: 12% CPU | 28% RAM | 18% Disk                 │
│                                                             │
│ [View Details] [Performance Graph] [Server Logs]           │
└─────────────────────────────────────────────────────────────┘
```

---

## 📈 BANDWIDTH USAGE GRAPH

### Historical Graph (Last 30 Days)
```
┌─────────────────────────────────────────────────────────────┐
│ Bandwidth Usage - January 2026                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Dallas Server (Fly.io - 100GB Limit)                        │
│ 100 GB ┤                                              ╱─ 92 │
│        │                                          ╱───      │
│  80 GB ┤                                      ╱───          │
│        │                                  ╱───              │
│  60 GB ┤                              ╱───                  │
│        │                          ╱───                      │
│  40 GB ┤                      ╱───                          │
│        │                  ╱───                              │
│  20 GB ┤              ╱───                                  │
│        │          ╱───                                      │
│   0 GB ┤──────────────────────────────────────────────────  │
│        1    5    10   15   20   25   28                    │
│        Jan                                                  │
│                                                             │
│ ⚠️  PROJECTION: Will exceed limit by Jan 31                 │
│ Projected: 108 GB (8 GB overage)                           │
│ Est. Cost: $0.16                                            │
│                                                             │
│ [Daily] [Weekly] [Monthly] [Yearly]                        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ Toronto Server (Fly.io - 100GB Limit)                       │
│ 100 GB ┤                                                    │
│        │                                                    │
│  80 GB ┤                                          ╱─ 78 ✓   │
│        │                                      ╱───          │
│  60 GB ┤                                  ╱───              │
│        │                              ╱───                  │
│  40 GB ┤                          ╱───                      │
│        │                      ╱───                          │
│  20 GB ┤                  ╱───                              │
│        │              ╱───                                  │
│   0 GB ┤──────────────────────────────────────────────────  │
│        1    5    10   15   20   25   31                    │
│        Jan                                                  │
│                                                             │
│ ✓ PROJECTION: Will stay within limit                       │
│ Projected: 78 GB (22 GB remaining)                         │
│                                                             │
│ [Daily] [Weekly] [Monthly] [Yearly]                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚨 ALERT SYSTEM

### Alert Thresholds
```
┌─────────────────────────────────────────────────────────────┐
│ Alert Configuration                                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Fly.io Servers (Dallas & Toronto):                          │
│                                                             │
│ ⚠️  75% Used (75 GB)                                        │
│    Action: Email warning to admin                          │
│    Message: "Server approaching bandwidth limit"           │
│                                                             │
│ ⚠️  90% Used (90 GB)                                        │
│    Action: Email urgent alert + SMS                        │
│    Message: "URGENT: Server at 90% of bandwidth limit"    │
│    Auto-Action: Start redirecting new users to NY          │
│                                                             │
│ 🚨 95% Used (95 GB)                                         │
│    Action: Email critical alert + SMS + Dashboard popup    │
│    Message: "CRITICAL: Server at 95% of bandwidth limit"  │
│    Auto-Action: Redirect ALL users to NY server           │
│    Auto-Action: Throttle existing users' bandwidth         │
│                                                             │
│ 🛑 100% Used (100 GB)                                       │
│    Action: Block new connections to server                 │
│    Message: "Server bandwidth limit reached"               │
│    Auto-Action: Force disconnect non-critical users        │
│    Display to users: "Please use NY server instead"        │
│                                                             │
│ [Save Configuration]  [Test Alerts]                        │
└─────────────────────────────────────────────────────────────┘
```

### Email Alert Example
```
Subject: ⚠️ URGENT: Dallas Server at 92% of Bandwidth Limit

Hi Kah-Len,

Your Dallas Fly.io server has used 92% of its monthly bandwidth limit.

SERVER DETAILS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Server: Dallas (Fly.io)
Location: US-Central (Texas)
IP: 66.241.124.4

BANDWIDTH USAGE:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Used: 92.3 GB / 100 GB (92%)
Remaining: 7.7 GB
Days Until Reset: 3 days

PROJECTION:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Projected Total: 108 GB
Estimated Overage: 8 GB
Overage Cost: ~$0.16

ACTIONS TAKEN AUTOMATICALLY:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ New users automatically redirected to NY server
✓ Streaming traffic throttled to reduce usage
✓ High-bandwidth activities limited

RECOMMENDATIONS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. Manually redirect heavy users to NY server
2. Consider upgrading Fly.io plan (160GB bandwidth)
3. Monitor usage daily until month resets

CURRENT STATUS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Active Users: 18 customers (42 devices)
Server Load: 78% CPU
Status: ONLINE

[View Dashboard] [Manually Redirect Users] [Upgrade Plan]

- TrueVault VPN Monitoring System
```

---

## 🔄 AUTOMATIC USER REDIRECTION

### Redirection Logic
```
WHEN: Dallas or Toronto server reaches 90% bandwidth

THEN:

Step 1: Update Server Recommendation
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• Mark Dallas/Toronto as "AVOID" in database
• Mark NY as "RECOMMENDED" for new connections
• Update server selection API

Step 2: Notify Active Users
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Send in-app notification:

"Your current server (Dallas) is experiencing high load.
For better performance, please switch to:

🟢 New York Server (Unlimited Bandwidth)
   IP: 66.94.103.91
   Location: US-East

[Switch to NY Server] [Remind Me Later]"

Step 3: Force Redirection (at 95%)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• Disconnect users after 5-minute warning
• Auto-reconnect to NY server
• Log all redirections

Step 4: Block New Connections (at 100%)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Show error message:

"Dallas Server Unavailable
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

This server has reached its monthly bandwidth limit.
Please use an alternative server:

✓ New York (Recommended)
  • Unlimited bandwidth
  • Low latency
  • US-East location

✓ Toronto
  • Canadian content access
  • 44 GB remaining

[Connect to NY] [Connect to Toronto]"
```

### User-Facing Server Selection
```
┌─────────────────────────────────────────────────────────────┐
│ Select VPN Server                                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 🌟 RECOMMENDED                                              │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ 🟢 New York (US-East)                               │   │
│ │                                                       │   │
│ │ Latency: 25ms                                        │   │
│ │ Load: 45% (Good)                                     │   │
│ │ Bandwidth: Unlimited ✓                               │   │
│ │                                                       │   │
│ │ [● Connect]                                          │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ OTHER SERVERS                                               │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ 🟡 Toronto (Canada)                                  │   │
│ │                                                       │   │
│ │ Latency: 32ms                                        │   │
│ │ Load: 52% (Good)                                     │   │
│ │ Bandwidth: 44 GB remaining this month                │   │
│ │                                                       │   │
│ │ [○ Connect]                                          │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ ⚠️  Dallas (US-Central) - AVOID                      │   │
│ │                                                       │   │
│ │ Latency: 28ms                                        │   │
│ │ Load: 78% (High)                                     │   │
│ │ Bandwidth: 7.7 GB remaining ⚠️                        │   │
│ │                                                       │   │
│ │ ⚠️  Server approaching bandwidth limit               │   │
│ │ Please use New York server instead                   │   │
│ │                                                       │   │
│ │ [○ Connect Anyway]                                   │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 BANDWIDTH PROJECTION ALGORITHM

### How It Works
```python
# Calculate projected bandwidth usage

def project_bandwidth_usage(server_id, current_usage_gb, days_elapsed, days_remaining):
    """
    Project if server will exceed monthly bandwidth limit
    
    Args:
        server_id: Server identifier
        current_usage_gb: Bandwidth used so far this month (GB)
        days_elapsed: Days since month started
        days_remaining: Days until month ends
    
    Returns:
        dict with projection details
    """
    
    # Get server limit from database
    server_limit_gb = get_server_bandwidth_limit(server_id)  # e.g., 100 GB
    
    # Calculate daily average usage
    daily_average_gb = current_usage_gb / days_elapsed
    
    # Project total usage for the month
    projected_total_gb = current_usage_gb + (daily_average_gb * days_remaining)
    
    # Calculate overage
    overage_gb = max(0, projected_total_gb - server_limit_gb)
    overage_cost = overage_gb * 0.02  # $0.02 per GB
    
    # Determine status
    usage_percent = (current_usage_gb / server_limit_gb) * 100
    
    if usage_percent >= 95:
        status = "CRITICAL"
        action = "Force redirect users immediately"
    elif usage_percent >= 90:
        status = "WARNING"
        action = "Redirect new users, throttle existing"
    elif usage_percent >= 75:
        status = "ALERT"
        action = "Monitor closely, prepare to redirect"
    else:
        status = "HEALTHY"
        action = "No action needed"
    
    # Calculate days until limit reached (if trending toward overage)
    if daily_average_gb > 0 and projected_total_gb > server_limit_gb:
        remaining_bandwidth_gb = server_limit_gb - current_usage_gb
        days_until_limit = remaining_bandwidth_gb / daily_average_gb
    else:
        days_until_limit = None
    
    return {
        "current_usage_gb": current_usage_gb,
        "server_limit_gb": server_limit_gb,
        "usage_percent": usage_percent,
        "remaining_gb": server_limit_gb - current_usage_gb,
        "daily_average_gb": round(daily_average_gb, 2),
        "projected_total_gb": round(projected_total_gb, 2),
        "overage_gb": round(overage_gb, 2),
        "overage_cost": round(overage_cost, 2),
        "days_until_limit": days_until_limit,
        "status": status,
        "recommended_action": action
    }

# Example usage:
result = project_bandwidth_usage(
    server_id="dallas_flyio",
    current_usage_gb=92.3,
    days_elapsed=28,
    days_remaining=3
)

# Output:
{
    "current_usage_gb": 92.3,
    "server_limit_gb": 100,
    "usage_percent": 92.3,
    "remaining_gb": 7.7,
    "daily_average_gb": 3.3,
    "projected_total_gb": 102.2,
    "overage_gb": 2.2,
    "overage_cost": 0.04,
    "days_until_limit": 2.3,
    "status": "WARNING",
    "recommended_action": "Redirect new users, throttle existing"
}
```

---

## 🗄️ DATABASE SCHEMA

```sql
-- Server configuration
CREATE TABLE vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_name TEXT UNIQUE NOT NULL, -- 'ny_contabo', 'stl_contabo', 'dallas_flyio', 'toronto_flyio'
    display_name TEXT NOT NULL, -- 'New York', 'St. Louis', 'Dallas', 'Toronto'
    server_ip TEXT NOT NULL,
    location TEXT, -- 'US-East', 'US-Central', 'Canada'
    provider TEXT, -- 'contabo', 'flyio'
    has_bandwidth_limit BOOLEAN DEFAULT 0,
    bandwidth_limit_gb INTEGER, -- NULL = unlimited, or 100, 160, etc.
    bandwidth_overage_cost_per_gb DECIMAL(10,4) DEFAULT 0.02,
    is_active BOOLEAN DEFAULT 1,
    is_vip_only BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert servers
INSERT INTO vpn_servers (server_name, display_name, server_ip, location, provider, has_bandwidth_limit, bandwidth_limit_gb) VALUES
('ny_contabo', 'New York', '66.94.103.91', 'US-East', 'contabo', 0, NULL),
('stl_contabo', 'St. Louis (VIP)', '144.126.133.253', 'US-Central', 'contabo', 0, NULL),
('dallas_flyio', 'Dallas', '66.241.124.4', 'US-Central', 'flyio', 1, 100),
('toronto_flyio', 'Toronto', '66.241.125.247', 'Canada', 'flyio', 1, 100);

-- Bandwidth usage tracking (hourly snapshots)
CREATE TABLE server_bandwidth_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    inbound_mb DECIMAL(10,2), -- MB received
    outbound_mb DECIMAL(10,2), -- MB sent (this is what counts toward limit)
    active_connections INTEGER,
    active_users INTEGER,
    cpu_usage_percent INTEGER,
    ram_usage_percent INTEGER,
    disk_usage_percent INTEGER,
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id)
);

-- Create index for fast queries
CREATE INDEX idx_bandwidth_timestamp ON server_bandwidth_usage(server_id, timestamp);

-- Monthly bandwidth summary
CREATE TABLE server_bandwidth_monthly (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    year INTEGER NOT NULL,
    month INTEGER NOT NULL, -- 1-12
    total_inbound_gb DECIMAL(10,2) DEFAULT 0,
    total_outbound_gb DECIMAL(10,2) DEFAULT 0,
    bandwidth_limit_gb INTEGER,
    overage_gb DECIMAL(10,2) DEFAULT 0,
    overage_cost DECIMAL(10,2) DEFAULT 0,
    reset_date DATE,
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id),
    UNIQUE(server_id, year, month)
);

-- Bandwidth alerts
CREATE TABLE bandwidth_alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    alert_type TEXT, -- 'warning_75', 'urgent_90', 'critical_95', 'limit_reached_100'
    usage_percent DECIMAL(5,2),
    usage_gb DECIMAL(10,2),
    limit_gb INTEGER,
    message TEXT,
    action_taken TEXT, -- 'email_sent', 'redirect_enabled', 'throttle_enabled', 'connections_blocked'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    acknowledged BOOLEAN DEFAULT 0,
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id)
);

-- Server status (real-time)
CREATE TABLE server_status (
    server_id INTEGER PRIMARY KEY,
    is_online BOOLEAN DEFAULT 1,
    last_ping DATETIME,
    uptime_days INTEGER,
    current_load_percent INTEGER,
    current_download_mbps DECIMAL(10,2),
    current_upload_mbps DECIMAL(10,2),
    active_users INTEGER,
    active_devices INTEGER,
    status TEXT, -- 'healthy', 'warning', 'critical', 'offline'
    recommendation TEXT, -- 'recommended', 'avoid', 'unavailable'
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id)
);

-- User server preferences (for tracking who's on which server)
CREATE TABLE user_server_connections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id INTEGER,
    server_id INTEGER NOT NULL,
    connected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    disconnected_at DATETIME,
    bandwidth_used_mb DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (device_id) REFERENCES user_devices(id),
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id)
);
```

---

## 🚀 API ENDPOINTS

### Real-Time Statistics
```
GET /api/servers/statistics.php
    Returns live statistics for all servers

Response:
{
    "servers": [
        {
            "id": 1,
            "name": "ny_contabo",
            "display_name": "New York",
            "location": "US-East",
            "is_online": true,
            "uptime_days": 28,
            "current_load_percent": 45,
            "current_download_mbps": 45.2,
            "current_upload_mbps": 12.8,
            "active_users": 45,
            "active_devices": 125,
            "bandwidth": {
                "has_limit": false,
                "monthly_used_gb": 128.5,
                "monthly_limit_gb": null,
                "usage_percent": null,
                "remaining_gb": null,
                "status": "unlimited"
            },
            "recommendation": "recommended"
        },
        {
            "id": 3,
            "name": "dallas_flyio",
            "display_name": "Dallas",
            "location": "US-Central",
            "is_online": true,
            "uptime_days": 28,
            "current_load_percent": 78,
            "current_download_mbps": 8.2,
            "current_upload_mbps": 3.1,
            "active_users": 18,
            "active_devices": 42,
            "bandwidth": {
                "has_limit": true,
                "monthly_used_gb": 92.3,
                "monthly_limit_gb": 100,
                "usage_percent": 92.3,
                "remaining_gb": 7.7,
                "status": "critical",
                "projected_total_gb": 108,
                "overage_gb": 8,
                "overage_cost": 0.16,
                "days_until_limit": 2.3
            },
            "recommendation": "avoid"
        }
    ],
    "summary": {
        "total_bandwidth_used_gb": 245.8,
        "total_overage_cost": 0.16,
        "active_users": 83,
        "active_devices": 207,
        "servers_at_risk": 1
    }
}
```

### Bandwidth History
```
GET /api/servers/bandwidth-history.php?server_id=3&period=30days

Response:
{
    "server_id": 3,
    "server_name": "dallas_flyio",
    "period": "30days",
    "data": [
        {"date": "2026-01-01", "outbound_gb": 2.1, "inbound_gb": 0.8},
        {"date": "2026-01-02", "outbound_gb": 2.8, "inbound_gb": 0.9},
        ...
        {"date": "2026-01-28", "outbound_gb": 3.5, "inbound_gb": 1.2}
    ],
    "totals": {
        "outbound_gb": 92.3,
        "inbound_gb": 31.5
    },
    "limit": {
        "monthly_limit_gb": 100,
        "remaining_gb": 7.7,
        "usage_percent": 92.3
    }
}
```

### Server Recommendation
```
GET /api/servers/recommended.php

Response:
{
    "recommended_server": {
        "id": 1,
        "name": "ny_contabo",
        "display_name": "New York",
        "ip": "66.94.103.91",
        "reason": "Unlimited bandwidth, low latency, good load"
    },
    "avoid_servers": [
        {
            "id": 3,
            "name": "dallas_flyio",
            "display_name": "Dallas",
            "reason": "Approaching monthly bandwidth limit (92% used)"
        }
    ]
}
```

### Manual Redirect Users
```
POST /api/servers/redirect-users.php
Body: {
    "from_server_id": 3,
    "to_server_id": 1,
    "user_ids": [45, 67, 89], // or "all"
    "notify_users": true
}

Response:
{
    "success": true,
    "users_redirected": 3,
    "message": "Successfully redirected 3 users from Dallas to New York"
}
```

### Update Bandwidth Limit
```
POST /api/servers/update-limit.php
Body: {
    "server_id": 3,
    "new_limit_gb": 160
}

Response:
{
    "success": true,
    "message": "Bandwidth limit updated to 160 GB"
}
```

---

## ⚙️ BACKGROUND TASKS

### Cron Job: Update Statistics (Every 5 Minutes)
```bash
*/5 * * * * php /path/to/api/servers/update-statistics.php
```

**What it does:**
1. Query each server for current stats (CPU, RAM, bandwidth)
2. Calculate bandwidth usage since last check
3. Update `server_bandwidth_usage` table
4. Update `server_status` table
5. Check if any alerts should be triggered
6. Send alerts if thresholds exceeded

### Cron Job: Monthly Reset (1st of Month)
```bash
0 0 1 * * php /path/to/api/servers/reset-monthly-bandwidth.php
```

**What it does:**
1. Archive current month's data to `server_bandwidth_monthly`
2. Reset bandwidth counters for Fly.io servers
3. Clear "avoid" recommendations
4. Send monthly report email to admin

---

## 💰 COST TRACKING

### Overage Cost Dashboard
```
┌─────────────────────────────────────────────────────────────┐
│ Monthly Cost Summary - January 2026                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ BASE COSTS:                                                 │
│ • Contabo NY:       $6.75/month  ✓ Paid                    │
│ • Contabo St. Louis: $6.15/month  ✓ Paid                   │
│ • Fly.io Dallas:    $0.00/month  ✓ Included                │
│ • Fly.io Toronto:   $0.00/month  ✓ Included                │
│ ───────────────────────────────────────────                │
│ TOTAL BASE:         $12.90/month                            │
│                                                             │
│ OVERAGE CHARGES:                                            │
│ • Dallas:   8 GB over × $0.02/GB = $0.16                   │
│ • Toronto:  0 GB over × $0.02/GB = $0.00                   │
│ ───────────────────────────────────────────                │
│ TOTAL OVERAGE:      $0.16                                   │
│                                                             │
│ GRAND TOTAL:        $13.06/month                            │
│                                                             │
│ Revenue This Month: $1,245.00                               │
│ Profit Margin:      99.0% 🎉                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 FLY.IO BANDWIDTH LIMITS - RESEARCH

### Standard Plans
**Fly.io Pricing (as of 2026):**
- **Hobby Plan:** $5/month + usage
  - Includes: 100GB outbound bandwidth
  - Overage: $0.02/GB

- **Launch Plan:** $19/month + usage
  - Includes: 160GB outbound bandwidth
  - Overage: $0.02/GB

**Your Current Setup (shared-cpu-1x@256MB):**
- Likely on Hobby or Launch plan
- Conservative estimate: **100GB/month included**
- Recommendation: Check Fly.io dashboard for exact limit

### How to Check Your Actual Limit
```
1. Log in to Fly.io dashboard
2. Go to Billing > Usage
3. Look for "Included Bandwidth" amount
4. Update database with actual limit:

   UPDATE vpn_servers 
   SET bandwidth_limit_gb = 100  -- or 160
   WHERE server_name = 'dallas_flyio';
```

---

**Status:** Complete Specification - Ready for Implementation  
**Priority:** CRITICAL (prevent overage charges)  
**Fly.io Bandwidth:** ~100GB/month per server (configurable)  
**Estimated Implementation Time:** 3-4 days
