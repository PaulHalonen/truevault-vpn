# MASTER CHECKLIST - PART 9-A: SERVER-SIDE KEY GENERATION SETUP

**Blueprint Section:** SECTION_11A_SERVER_SIDE_KEY_GEN.md  
**Added:** January 17, 2026  
**Estimated Time:** 4-6 hours  
**Priority:** CRITICAL - Required Before Device Management Works  

---

## ⚠️ IMPORTANT

**Keys are generated SERVER-SIDE, not browser-side!**

When user clicks "Add Device":
1. User enters device name
2. User selects server
3. **VPN SERVER** generates the keypair
4. Server adds peer to WireGuard
5. Server returns config file + QR code
6. User downloads config or scans QR

---

## 🔧 TASK 9A.1: Prepare Server Scripts

**Time Estimate:** 30 minutes

### 9A.1.1 Create Local Script Files
- [ ] Create folder: `E:\Documents\GitHub\truevault-vpn\server-scripts\`
- [ ] Create `api.py` (copy from Blueprint SECTION_11A)
- [ ] Create `setup.sh` (copy from Blueprint SECTION_11A)
- [ ] Create `requirements.txt`:
  ```
  flask==3.0.0
  qrcode==7.4.2
  pillow==10.2.0
  ```

### 9A.1.2 Create Server Configuration Files
- [ ] Create `config-newyork.env`:
  ```
  SERVER_NAME=new-york
  SERVER_IP=66.94.103.91
  WG_PORT=51820
  API_PORT=8443
  SUBNET_BASE=10.8.0
  DNS=1.1.1.1, 1.0.0.1
  API_SECRET=[GENERATE_UNIQUE_SECRET]
  ```
- [ ] Create `config-stlouis.env` (SUBNET_BASE=10.8.1)
- [ ] Create `config-dallas.env` (SUBNET_BASE=10.8.2)
- [ ] Create `config-toronto.env` (SUBNET_BASE=10.8.3)

### 9A.1.3 Generate API Secrets
- [ ] Generate unique secret for each server (32+ chars)
- [ ] Store secrets in admin.db `system_settings` table
- [ ] **NEVER** commit secrets to Git

---

## 🔧 TASK 9A.2: Deploy to Contabo Server 1 (New York)

**Time Estimate:** 45 minutes

### 9A.2.1 SSH to Server
- [ ] `ssh root@66.94.103.91`
- [ ] Verify access works

### 9A.2.2 Install Dependencies
- [ ] `apt update && apt upgrade -y`
- [ ] `apt install -y python3 python3-pip python3-venv wireguard qrencode`

### 9A.2.3 Setup WireGuard (if not already done)
- [ ] Check: `wg show` - does wg0 exist?
- [ ] If NOT:
  ```bash
  wg genkey | tee /etc/wireguard/server_private.key | wg pubkey > /etc/wireguard/server_public.key
  ```
- [ ] **RECORD PUBLIC KEY:** `cat /etc/wireguard/server_public.key`
- [ ] Create `/etc/wireguard/wg0.conf`:
  ```ini
  [Interface]
  PrivateKey = [PRIVATE_KEY_HERE]
  Address = 10.8.0.1/24
  ListenPort = 51820
  PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
  PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
  ```
- [ ] `systemctl enable wg-quick@wg0`
- [ ] `systemctl start wg-quick@wg0`
- [ ] Verify: `wg show` shows interface

### 9A.2.4 Deploy API Script
- [ ] `mkdir -p /opt/truevault`
- [ ] Upload `api.py` to `/opt/truevault/`
- [ ] Upload `requirements.txt` to `/opt/truevault/`
- [ ] Create virtual environment:
  ```bash
  cd /opt/truevault
  python3 -m venv venv
  source venv/bin/activate
  pip install -r requirements.txt
  ```
- [ ] Create `/opt/truevault/.env` with New York config

### 9A.2.5 Create Systemd Service
- [ ] Create `/etc/systemd/system/truevault-api.service`:
  ```ini
  [Unit]
  Description=TrueVault VPN Key Management API
  After=network.target wg-quick@wg0.service

  [Service]
  Type=simple
  WorkingDirectory=/opt/truevault
  EnvironmentFile=/opt/truevault/.env
  ExecStart=/opt/truevault/venv/bin/python /opt/truevault/api.py
  Restart=always
  RestartSec=5

  [Install]
  WantedBy=multi-user.target
  ```
- [ ] `systemctl daemon-reload`
- [ ] `systemctl enable truevault-api`
- [ ] `systemctl start truevault-api`

### 9A.2.6 Configure Firewall
- [ ] `ufw allow 51820/udp`
- [ ] `ufw allow 8443/tcp`
- [ ] `ufw allow 22/tcp`
- [ ] `ufw --force enable`

### 9A.2.7 Test New York Server
- [ ] Test health: `curl http://66.94.103.91:8443/api/health`
- [ ] Test server-info: `curl http://66.94.103.91:8443/api/server-info`
- [ ] Verify WireGuard: `wg show wg0`
- [ ] Record public key in database

**Verification Checklist:**
- [ ] `/api/health` returns `{"status": "online"}`
- [ ] `/api/server-info` returns public key
- [ ] WireGuard interface wg0 is UP
- [ ] Public key recorded: `___________________________`

---

## 🔧 TASK 9A.3: Deploy to Contabo Server 2 (St. Louis - VIP)

**Time Estimate:** 45 minutes

Repeat steps from 9A.2, with these differences:

### 9A.3.1 Configuration
- [ ] SUBNET_BASE = `10.8.1`
- [ ] SERVER_NAME = `st-louis`
- [ ] SERVER_IP = `144.126.133.253`

### 9A.3.2 Special VIP Configuration
- [ ] This server is **DEDICATED** to seige235@yahoo.com
- [ ] API should reject requests for other users (optional enforcement)
- [ ] Max 999 devices (VIP limit)

### 9A.3.3 Deploy and Test
- [ ] SSH: `ssh root@144.126.133.253`
- [ ] Follow same steps as New York
- [ ] Test: `curl http://144.126.133.253:8443/api/health`

**Verification:**
- [ ] API running
- [ ] Public key recorded: `___________________________`

---

## 🔧 TASK 9A.4: Deploy to Fly.io Server 3 (Dallas)

**Time Estimate:** 45 minutes

**NOTE:** Fly.io servers are containers - different deployment process!

### 9A.4.1 Check Existing Configuration
- [ ] `flyctl status -a truevault-dallas`
- [ ] Check if WireGuard is already running
- [ ] Check existing fly.toml

### 9A.4.2 Update Fly.io App
If WireGuard image is already deployed, we need to add the API:

Option A: Add API to existing container
- [ ] Modify Dockerfile to include Python API
- [ ] Update fly.toml to expose port 8443

Option B: Use separate secrets management
- [ ] `flyctl secrets set API_SECRET=xxx -a truevault-dallas`
- [ ] `flyctl secrets set SERVER_NAME=dallas -a truevault-dallas`

### 9A.4.3 Alternative: Use Fly.io Built-in WireGuard
Fly.io may have WireGuard pre-configured. Check:
- [ ] `flyctl ssh console -a truevault-dallas`
- [ ] `wg show`
- [ ] If exists, note configuration

### 9A.4.4 Test Dallas
- [ ] `curl http://66.241.124.4:8443/api/health`
- [ ] Record public key: `___________________________`

---

## 🔧 TASK 9A.5: Deploy to Fly.io Server 4 (Toronto)

**Time Estimate:** 45 minutes

Repeat Fly.io steps for Toronto:

### 9A.5.1 Configuration
- [ ] SUBNET_BASE = `10.8.3`
- [ ] SERVER_NAME = `toronto`
- [ ] SERVER_IP = `66.241.125.247`

### 9A.5.2 Deploy and Test
- [ ] `flyctl ssh console -a truevault-toronto`
- [ ] Follow same steps as Dallas
- [ ] Test: `curl http://66.241.125.247:8443/api/health`

**Verification:**
- [ ] API running
- [ ] Public key recorded: `___________________________`

---

## 🔧 TASK 9A.6: Update Database with Server Keys

**Time Estimate:** 30 minutes

### 9A.6.1 Update servers.db
- [ ] Open servers.db
- [ ] Update New York public_key
- [ ] Update St. Louis public_key
- [ ] Update Dallas public_key
- [ ] Update Toronto public_key

```sql
UPDATE servers SET public_key = '[NY_KEY]' WHERE name = 'New York';
UPDATE servers SET public_key = '[STL_KEY]' WHERE name = 'St. Louis';
UPDATE servers SET public_key = '[DAL_KEY]' WHERE name = 'Dallas';
UPDATE servers SET public_key = '[TOR_KEY]' WHERE name = 'Toronto';
```

### 9A.6.2 Store API Secrets in admin.db
- [ ] Add to system_settings:
```sql
INSERT INTO system_settings (setting_key, setting_value) VALUES
('api_secret_newyork', '[SECRET]'),
('api_secret_stlouis', '[SECRET]'),
('api_secret_dallas', '[SECRET]'),
('api_secret_toronto', '[SECRET]');
```

### 9A.6.3 Update PHP Backend Config
- [ ] Create `/includes/server-config.php` that reads secrets from database
- [ ] **NEVER** hardcode secrets in PHP files

---

## 🔧 TASK 9A.7: Update PHP Backend for Server-Side Keys

**Time Estimate:** 1 hour

### 9A.7.1 Create Server API Helper
- [ ] Create `/includes/ServerAPI.php`:
```php
<?php
class ServerAPI {
    public function callServer($serverIp, $endpoint, $data, $apiSecret) {
        $url = "http://{$serverIp}:8443{$endpoint}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiSecret
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function createPeer($serverId, $userId, $deviceName) {
        $server = $this->getServer($serverId);
        $secret = $this->getApiSecret($server['name']);
        
        return $this->callServer(
            $server['ip_address'],
            '/api/create-peer',
            ['user_id' => $userId, 'device_name' => $deviceName],
            $secret
        );
    }
}
```

### 9A.7.2 Update Device Creation API
- [ ] Modify `/api/devices/create.php` to use ServerAPI
- [ ] Remove any browser-side key generation code
- [ ] Return config and QR code from server response

### 9A.7.3 Test End-to-End
- [ ] Create test user
- [ ] Call device create API
- [ ] Verify config returned
- [ ] Verify peer appears on server (`wg show`)

---

## ✅ COMPLETION CHECKLIST

Before marking Part 9A complete:

- [ ] All 4 servers have API running on port 8443
- [ ] All 4 servers have WireGuard running on port 51820
- [ ] All 4 server public keys recorded in database
- [ ] All 4 API secrets stored securely in admin.db
- [ ] PHP backend can call server APIs
- [ ] Test device creation works end-to-end
- [ ] Config file downloads correctly
- [ ] QR code generates correctly

---

## 📋 SERVER STATUS RECORD

Fill in after completing each server:

| Server | IP | API Status | WG Status | Public Key |
|--------|-----|-----------|-----------|------------|
| New York | 66.94.103.91 | ✅ Running | ✅ Active | `lbriy+env0wv6VmEJscnjoREswmiQdn7D+lKGai9n3s=` |
| St. Louis | 144.126.133.253 | ✅ Running | ✅ Active | `qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=` |
| Dallas | 66.241.124.4 | ⬜ Not Deployed | ⬜ Unknown | |
| Toronto | 66.241.125.247 | ⬜ Not Deployed | ⬜ Unknown | |

---

**END OF PART 9A**

**Next:** Return to Part 9 remaining tasks (monitoring, failover)
