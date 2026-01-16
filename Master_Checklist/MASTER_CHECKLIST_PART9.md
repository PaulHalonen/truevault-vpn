# MASTER CHECKLIST - PART 9: SERVER MANAGEMENT

**Blueprint Section:** SECTION_10_SERVER_MANAGEMENT.md  
**Created:** January 16, 2026  
**Estimated Time:** 8-12 hours  
**Priority:** CRITICAL - Infrastructure  

---

## 📋 OVERVIEW

This part covers complete VPN server infrastructure management including:
- Server database and inventory
- Contabo server configuration
- Fly.io server configuration
- WireGuard installation and setup
- Server health monitoring
- Automated failover
- Bandwidth tracking
- SSH key management
- Adding new servers

---

## 📌 PREREQUISITES

Before starting PART 9, ensure:
- [ ] PART 1-6 completed (basic VPN functionality)
- [ ] SSH access to all 4 servers working
- [ ] Contabo account access verified (paulhalonen@gmail.com)
- [ ] Fly.io account access verified (paulhalonen@gmail.com)
- [ ] Server credentials documented

---

## 🔧 TASK 9.1: Server Database Setup

**Time Estimate:** 1 hour

### 9.1.1 Create Server Tables
- [ ] Open servers.db in SQLite browser
- [ ] Create servers table with all fields:
  ```sql
  CREATE TABLE IF NOT EXISTS servers (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL UNIQUE,
      location TEXT NOT NULL,
      country_code TEXT,
      ip_address TEXT NOT NULL,
      port INTEGER DEFAULT 51820,
      public_key TEXT NOT NULL,
      endpoint TEXT NOT NULL,
      provider TEXT,
      provider_id TEXT,
      is_active BOOLEAN DEFAULT 1,
      is_visible BOOLEAN DEFAULT 1,
      max_users INTEGER DEFAULT 500,
      current_users INTEGER DEFAULT 0,
      access_level TEXT DEFAULT 'public',
      vip_email TEXT,
      allowed_users TEXT,
      streaming_optimized BOOLEAN DEFAULT 0,
      port_forwarding BOOLEAN DEFAULT 1,
      monthly_cost DECIMAL(10,2),
      currency TEXT DEFAULT 'USD',
      bandwidth_limit TEXT,
      bandwidth_used BIGINT DEFAULT 0,
      uptime_percentage DECIMAL(5,2),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      last_checked DATETIME
  );
  ```
- [ ] Verify table created successfully

### 9.1.2 Create Server Costs Table
- [ ] Create server_costs table:
  ```sql
  CREATE TABLE IF NOT EXISTS server_costs (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      server_id INTEGER NOT NULL,
      amount DECIMAL(10,2) NOT NULL,
      currency TEXT DEFAULT 'USD',
      billing_month TEXT NOT NULL,
      billing_date DATE,
      bandwidth_gb DECIMAL(10,2),
      user_count INTEGER,
      uptime_hours DECIMAL(10,2),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
  );
  ```

### 9.1.3 Create Server Logs Table
- [ ] Create server_logs table:
  ```sql
  CREATE TABLE IF NOT EXISTS server_logs (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      server_id INTEGER NOT NULL,
      status TEXT,
      details TEXT,
      checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
  );
  ```

### 9.1.4 Populate Initial Server Data
- [ ] Insert New York server (66.94.103.91)
- [ ] Insert St. Louis VIP server (144.126.133.253)
- [ ] Insert Dallas server (66.241.124.4)
- [ ] Insert Toronto server (66.241.125.247)
- [ ] Set St. Louis as VIP-only (seige235@yahoo.com)
- [ ] Set St. Louis is_visible = 0

**Verification:**
- [ ] SELECT * FROM servers returns 4 rows
- [ ] VIP server has correct access_level and vip_email

---

## 🔧 TASK 9.2: Contabo Server Configuration

**Time Estimate:** 2 hours

### 9.2.1 Document Server 1 (New York)
- [ ] Create /docs/servers/contabo-newyork.md
- [ ] Document: IP, MAC, IPv6, Host System, VNC URL
- [ ] Document: Disk space, RAM, CPU specs
- [ ] Document: Monthly cost ($6.75)
- [ ] Store VNC credentials securely

### 9.2.2 Document Server 2 (St. Louis - VIP)
- [ ] Create /docs/servers/contabo-stlouis.md
- [ ] Document: IP, MAC, IPv6, Host System, VNC URL
- [ ] Document: VIP assignment (seige235@yahoo.com)
- [ ] Document: Monthly cost ($6.15)
- [ ] Mark as DEDICATED - NOT FOR PUBLIC

### 9.2.3 Create Contabo API Helper
- [ ] Create /includes/contabo.php
- [ ] Implement getAccessToken() method
- [ ] Implement getServerStatus($serverId) method
- [ ] Implement restartServer($serverId) method
- [ ] Implement getUsageStats($serverId) method
- [ ] Store API credentials in database (NOT in code)

### 9.2.4 Test Contabo API Integration
- [ ] Test authentication
- [ ] Test server status retrieval
- [ ] Test usage stats retrieval
- [ ] Document any API limitations

**Verification:**
- [ ] contabo.php returns valid server status
- [ ] API credentials stored in business_settings table

---

## 🔧 TASK 9.3: Fly.io Server Configuration

**Time Estimate:** 2 hours

### 9.3.1 Document Server 3 (Dallas)
- [ ] Create /docs/servers/flyio-dallas.md
- [ ] Document: App name, Region, IP addresses
- [ ] Document: Machine size, Services, Ports
- [ ] Document: Streaming optimized configuration
- [ ] Save fly.toml configuration

### 9.3.2 Document Server 4 (Toronto)
- [ ] Create /docs/servers/flyio-toronto.md
- [ ] Document: App name, Region, IP addresses
- [ ] Document: Canadian content optimization
- [ ] Save fly.toml configuration

### 9.3.3 Create Fly.io API Helper
- [ ] Create /includes/flyio.php
- [ ] Implement GraphQL client
- [ ] Implement getAppStatus($appName) method
- [ ] Implement restartApp($appName) method
- [ ] Implement getAppMetrics($appName) method
- [ ] Store API token in database

### 9.3.4 Test Fly.io API Integration
- [ ] Test authentication
- [ ] Test app status retrieval
- [ ] Test metrics retrieval
- [ ] Document GraphQL queries used

**Verification:**
- [ ] flyio.php returns valid app status for both servers
- [ ] Can retrieve metrics for both Dallas and Toronto

---

## 🔧 TASK 9.4: WireGuard Server Setup

**Time Estimate:** 2 hours

### 9.4.1 Create WireGuard Installation Script
- [ ] Create /scripts/setup-wireguard.sh
- [ ] Include: System update
- [ ] Include: WireGuard installation
- [ ] Include: IP forwarding enablement
- [ ] Include: Key generation
- [ ] Include: wg0.conf creation
- [ ] Include: systemd service setup
- [ ] Include: UFW firewall rules

### 9.4.2 Document Server Public Keys
- [ ] SSH to New York, get public key
- [ ] SSH to St. Louis, get public key
- [ ] SSH to Dallas, get public key
- [ ] SSH to Toronto, get public key
- [ ] Store all public keys in servers table
- [ ] Store private keys securely (NOT in database)

### 9.4.3 Create Peer Management Functions
- [ ] Create /includes/wireguard.php
- [ ] Implement addPeerToServer($serverId, $deviceId, $publicKey, $allowedIPs)
- [ ] Implement removePeerFromServer($serverId, $publicKey)
- [ ] Implement getPeerList($serverId)
- [ ] Implement reloadServerConfig($serverId)

### 9.4.4 Test Peer Management
- [ ] Test adding a peer to test server
- [ ] Verify peer appears in wg show output
- [ ] Test removing peer
- [ ] Verify peer removed from wg show output

**Verification:**
- [ ] Can SSH to all 4 servers
- [ ] wg show returns valid output on all servers
- [ ] Peer management functions work correctly

---

## 🔧 TASK 9.5: Server Health Monitoring

**Time Estimate:** 2 hours

### 9.5.1 Create Health Check Script
- [ ] Create /cron/check-servers.php
- [ ] Implement checkAllServers() function
- [ ] Implement checkServerHealth($server) function
- [ ] Implement pingServer($ip) function
- [ ] Implement checkWireGuardPort($ip, $port) function
- [ ] Implement getServerLoad($server) function
- [ ] Implement getDiskUsage($server) function

### 9.5.2 Create Status Update Functions
- [ ] Implement updateServerStatus($serverId, $status)
- [ ] Log status to server_logs table
- [ ] Calculate uptime_percentage
- [ ] Track last_checked timestamp

### 9.5.3 Create Alert System
- [ ] Implement alertAdmin($server, $status) function
- [ ] Send email on server down
- [ ] Send SMS for critical failures (optional)
- [ ] Log all alerts to database

### 9.5.4 Setup Cron Job
- [ ] Add cron entry: */5 * * * * php /path/to/cron/check-servers.php
- [ ] Test cron execution
- [ ] Verify logs are created
- [ ] Test alert triggers

**Verification:**
- [ ] Cron runs every 5 minutes
- [ ] server_logs table populates
- [ ] Admin receives alert when server offline

---

## 🔧 TASK 9.6: Automated Failover

**Time Estimate:** 1 hour

### 9.6.1 Create Failover Handler
- [ ] Create /includes/failover.php
- [ ] Implement handleServerFailover($failedServerId)
- [ ] Implement findBackupServer($excludeId)
- [ ] Implement migrateDeviceToServer($deviceId, $newServerId)
- [ ] Implement notifyUserServerChange()

### 9.6.2 Test Failover Logic
- [ ] Simulate server failure
- [ ] Verify users migrated to backup
- [ ] Verify users notified
- [ ] Verify logs created
- [ ] Test restore process

**Verification:**
- [ ] Failover triggers automatically on server down
- [ ] Users receive migration notification
- [ ] New configs generated for migrated users

---

## 🔧 TASK 9.7: Bandwidth Management

**Time Estimate:** 1 hour

### 9.7.1 Create Bandwidth Tracking
- [ ] Create /includes/bandwidth.php
- [ ] Implement trackBandwidth($serverId)
- [ ] Implement getBandwidthUsage($serverId)
- [ ] Implement checkBandwidthLimits($serverId)
- [ ] Store bandwidth in servers.bandwidth_used

### 9.7.2 Setup Bandwidth Monitoring
- [ ] Install vnstat on all servers (if not present)
- [ ] Create bandwidth tracking cron
- [ ] Alert when approaching limits (Contabo ~1TB)
- [ ] Track daily/weekly/monthly trends

**Verification:**
- [ ] Bandwidth data populates in database
- [ ] Alerts sent when approaching limits

---

## 🔧 TASK 9.8: SSH Key Management

**Time Estimate:** 30 minutes

### 9.8.1 Generate Admin SSH Key
- [ ] Generate ed25519 key pair
- [ ] Store private key securely
- [ ] Document key location

### 9.8.2 Deploy Keys to All Servers
- [ ] Add public key to New York authorized_keys
- [ ] Add public key to St. Louis authorized_keys
- [ ] Add public key to Dallas authorized_keys
- [ ] Add public key to Toronto authorized_keys

### 9.8.3 Create SSH Helper
- [ ] Create executeSSH($host, $command, $user) function
- [ ] Test SSH execution to all servers
- [ ] Handle errors gracefully

**Verification:**
- [ ] Can SSH to all servers without password
- [ ] executeSSH() function works correctly

---

## 🔧 TASK 9.9: Admin Server Management UI

**Time Estimate:** 1 hour

### 9.9.1 Create Admin Server Dashboard
- [ ] Create /admin/servers.php
- [ ] Show all 4 servers with status
- [ ] Display: IP, Location, Users, Bandwidth, Uptime
- [ ] Add refresh button
- [ ] Add restart button (with confirmation)

### 9.9.2 Create Server Detail View
- [ ] Create /admin/server-detail.php
- [ ] Show full server specifications
- [ ] Show recent logs
- [ ] Show connected users
- [ ] Show bandwidth charts

### 9.9.3 Create Add Server Form
- [ ] Create /admin/add-server.php
- [ ] Form fields for all server properties
- [ ] Validate inputs
- [ ] Test connection before saving

**Verification:**
- [ ] Admin can view all servers
- [ ] Admin can restart servers
- [ ] Admin can add new servers

---

## 🔧 TASK 9.10: Cost Tracking

**Time Estimate:** 30 minutes

### 9.10.1 Create Cost Report Function
- [ ] Implement generateCostReport($month)
- [ ] Calculate total monthly costs
- [ ] Calculate cost per user
- [ ] Track cost trends

### 9.10.2 Add Cost Tracking to Admin
- [ ] Display monthly costs on admin dashboard
- [ ] Show cost breakdown by server
- [ ] Alert when costs exceed budget

**Verification:**
- [ ] Cost report shows $23/month total
- [ ] Breakdown matches: $6.75 + $6.15 + $5 + $5

---

## ✅ PART 9 COMPLETION CHECKLIST

### Database
- [ ] servers table created with all fields
- [ ] server_costs table created
- [ ] server_logs table created
- [ ] All 4 servers populated

### Provider Integration
- [ ] Contabo API helper working
- [ ] Fly.io API helper working
- [ ] Server status retrieval working

### WireGuard
- [ ] Installation script created
- [ ] All public keys documented
- [ ] Peer management working
- [ ] SSH access to all servers

### Monitoring
- [ ] Health check cron running
- [ ] Status updates in database
- [ ] Alerts triggering correctly
- [ ] Failover logic tested

### Admin UI
- [ ] Server dashboard created
- [ ] Server detail view created
- [ ] Add server form created
- [ ] Cost tracking displayed

---

## 🧪 TESTING CHECKLIST

- [ ] Ping all 4 servers
- [ ] SSH to all 4 servers
- [ ] Get WireGuard status from all servers
- [ ] Simulate server down, verify alert
- [ ] Simulate failover, verify migration
- [ ] Check bandwidth tracking accuracy
- [ ] Verify cost calculations
- [ ] Test admin UI all functions

---

## 📝 DOCUMENTATION

After completing PART 9:
- [ ] Update BUILD_PROGRESS.md
- [ ] Update chat_log.txt
- [ ] Document any server changes
- [ ] Update MAPPING.md if needed

---

## ⏭️ NEXT STEPS

After PART 9 complete, proceed to:
- **PART 10:** Android Helper App (SECTION_21)

---

**PART 9 STATUS:** ⬜ NOT STARTED  
**Last Updated:** January 16, 2026

