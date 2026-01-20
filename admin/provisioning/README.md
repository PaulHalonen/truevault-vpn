# TrueVault VPN - Server Provisioning Scripts

## 📁 Directory Structure

```
truevault-vpn/
├── server-scripts/              # Scripts that run ON Contabo VPS
│   ├── install-wireguard.sh     # Installs WireGuard on new server
│   └── create-client-config.sh  # Generates client .conf files
│
└── admin/provisioning/          # Scripts that run on web server
    ├── change-server-password.py # Changes Contabo server password
    └── auto-provision.php         # Main orchestrator
```

---

## 🚀 HOW IT WORKS

### **FLOW:**

1. **Customer buys Dedicated Server** → PayPal payment received
2. **System purchases Contabo VPS** → Contabo sends email with server details
3. **Email arrives at paulhalonen@gmail.com**
4. **auto-provision.php runs:**
   - Parses email for IP, location, temp password
   - Uses `change-server-password.py` to set password to `Andassi8`
   - SSHs into server and uploads `install-wireguard.sh`
   - Runs installation script
   - Runs `create-client-config.sh` to generate .conf file
   - Emails .conf to customer
   - Updates database (server status: "online")
5. **Customer receives email** with .conf file and instructions
6. **Dashboard shows server online** with download link

---

## 🔧 SCRIPT DETAILS

### **1. install-wireguard.sh** (Bash - runs ON VPS)

**Purpose:** Initial WireGuard setup on fresh Contabo server

**What it does:**
- Updates system packages
- Installs WireGuard + dependencies
- Enables IP forwarding
- Generates server keys
- Creates base WireGuard config
- Configures firewall (allow 51820/udp, 22/tcp)
- Starts WireGuard service

**Usage:**
```bash
# On Contabo VPS as root:
./install-wireguard.sh
```

**Requirements:**
- Fresh Ubuntu/Debian server
- Root access
- Internet connection

---

### **2. create-client-config.sh** (Bash - runs ON VPS)

**Purpose:** Generate .conf file for each customer

**What it does:**
- Generates client keys (private/public + preshared)
- Assigns next available IP (10.8.0.x)
- Adds peer to server WireGuard config
- Reloads WireGuard (no downtime)
- Creates client.conf file
- Generates QR code for mobile
- Outputs .conf to stdout (captured by PHP)

**Usage:**
```bash
# On Contabo VPS as root:
./create-client-config.sh <customer_id> <customer_email>

# Example:
./create-client-config.sh 123 user@example.com
```

**Output:** Prints .conf file content to stdout

**Storage:** Files saved to `/root/wireguard-clients/<customer_id>/`

---

### **3. change-server-password.py** (Python - runs on web server)

**Purpose:** Change Contabo server root password to standard password

**What it does:**
- Connects via SSH using temp password from Contabo email
- Changes root password to `Andassi8`
- Verifies new password works
- Reports success/failure

**Usage:**
```bash
# On web server:
python3 change-server-password.py <server_ip> <temp_password>

# Example:
python3 change-server-password.py 144.126.133.253 TempPass123
```

**Requirements:**
```bash
pip install paramiko
```

**Exit codes:**
- 0 = Success
- 1 = Failure

---

### **4. auto-provision.php** (PHP - runs on web server)

**Purpose:** Main orchestrator - ties everything together

**What it does:**
1. Parses Contabo email for server details
2. Calls `change-server-password.py` to set standard password
3. SSHs into server and uploads bash scripts
4. Runs `install-wireguard.sh`
5. Runs `create-client-config.sh` to generate .conf
6. Emails .conf file to customer with setup instructions
7. Updates database (server_ip, server_location, vpn_config, status="online")

**Usage:**

**Command line:**
```bash
php auto-provision.php <customer_id> <email> <server_ip> <temp_password> <location>

# Example:
php auto-provision.php 123 user@example.com 144.126.133.253 TempPass123 "St. Louis"
```

**Via PHP include:**
```php
require_once 'auto-provision.php';

$result = provisionServer(
    $customerId,
    $customerEmail,
    $serverIp,
    $tempPassword,
    $location
);

if ($result['success']) {
    echo "Success!\n";
    echo implode("\n", $result['log']);
} else {
    echo "Failed!\n";
    echo implode("\n", $result['log']);
}
```

**Requirements:**
- PHP 7.4+ with SSH2 extension
- Access to vpn.db database
- Python 3 with paramiko installed

---

## 🔐 SECURITY

### **Standard Password:**
- All Contabo servers use: `Andassi8`
- Changed automatically during provisioning
- Used for SSH automation

### **WireGuard Security:**
- Each client gets unique keys (private/public)
- Preshared keys for extra security
- IP forwarding properly configured
- Firewall blocks everything except WireGuard + SSH

### **File Permissions:**
- All keys: `chmod 600`
- WireGuard config: `chmod 600`
- Client directories: `chmod 700`

---

## 📧 EMAIL PARSING

The PHP script expects Contabo emails in this format:

```
IP address       server type                 Location              user name  password
144.126.133.253  Cloud VPS 10 SSD (no setup) St. Louis (US-central) root      as chosen by you
```

**Extracted fields:**
- IP: `144.126.133.253`
- Location: `St. Louis` or `US-central`
- IPv6: `2605:a140:2299:0005:0000:0000:0000:0001/64`
- Password: Must be provided separately (from order form)

---

## 🧪 TESTING

### **Test manually:**

```bash
# 1. SSH into a fresh Contabo server
ssh root@144.126.133.253

# 2. Run installer
wget https://vpn.the-truth-publishing.com/server-scripts/install-wireguard.sh
chmod +x install-wireguard.sh
./install-wireguard.sh

# 3. Create client config
wget https://vpn.the-truth-publishing.com/server-scripts/create-client-config.sh
chmod +x create-client-config.sh
./create-client-config.sh 999 test@example.com

# 4. Download conf file
cat /root/wireguard-clients/999/client.conf
```

### **Test full automation:**

```bash
# From web server:
php admin/provisioning/auto-provision.php \
    123 \
    user@example.com \
    144.126.133.253 \
    TempPass123 \
    "St. Louis"
```

---

## 🐛 TROUBLESHOOTING

### **Problem: SSH connection fails**
- Check server firewall allows port 22
- Verify password is correct
- Wait 1-2 minutes after Contabo email (server may still be booting)

### **Problem: WireGuard won't start**
- Check kernel supports WireGuard: `modprobe wireguard`
- Check logs: `journalctl -u wg-quick@wg0`
- Verify config syntax: `wg-quick strip wg0`

### **Problem: Client can't connect**
- Check server firewall: `ufw status`
- Verify WireGuard is running: `wg show`
- Check IP forwarding: `sysctl net.ipv4.ip_forward`
- Test connectivity: `ping 10.8.0.1` from client

### **Problem: Email not sent**
- Check PHP mail() is configured
- Verify FROM address: `noreply@vpn.the-truth-publishing.com`
- Check spam folder
- Test: `php -r "mail('test@example.com', 'Test', 'Body');"`

---

## 📝 DATABASE SCHEMA

The `customers` table needs these columns:

```sql
ALTER TABLE customers ADD COLUMN server_ip TEXT;
ALTER TABLE customers ADD COLUMN server_location TEXT;
ALTER TABLE customers ADD COLUMN vpn_config TEXT;
ALTER TABLE customers ADD COLUMN server_status TEXT DEFAULT 'pending';
ALTER TABLE customers ADD COLUMN provisioned_at DATETIME;
```

---

## 🔄 NEXT STEPS

1. **Install Python dependencies on web server:**
   ```bash
   pip install paramiko
   ```

2. **Install PHP SSH2 extension:**
   ```bash
   sudo apt install php-ssh2
   ```

3. **Make bash scripts executable:**
   ```bash
   chmod +x server-scripts/*.sh
   ```

4. **Test password changer:**
   ```bash
   python3 admin/provisioning/change-server-password.py 144.126.133.253 YourTempPass
   ```

5. **Test full provisioning:**
   ```bash
   php admin/provisioning/auto-provision.php 999 test@example.com 144.126.133.253 TempPass "St. Louis"
   ```

---

## 📞 SUPPORT

If scripts fail, check logs:
- Web server: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- VPS server: `/var/log/syslog` and `journalctl -u wg-quick@wg0`

---

**✅ Scripts ready for deployment!**
