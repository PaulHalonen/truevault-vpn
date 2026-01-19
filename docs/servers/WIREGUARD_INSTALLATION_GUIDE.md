# WireGuard Server Installation Guide
**TrueVault VPN - Complete Server Setup**

## 📋 Overview

This guide covers installing and configuring WireGuard on all 4 TrueVault VPN servers:
1. **Contabo vmi2990026** (New York) - 66.94.103.91
2. **Contabo vmi2990005** (St. Louis) - 144.126.133.253 (VIP only)
3. **Fly.io** (Dallas) - 66.241.124.4
4. **Fly.io** (Toronto) - 66.241.125.247

---

## 🔐 Server Access Credentials

### Contabo Servers
- **Username:** `paulhalonen@gmail.com`
- **Password:** `Asasasas4!`
- **Root User:** `root` (default)
- **VNC Access:** Available via Contabo control panel

### Fly.io Servers
- **CLI Login:** `fly auth login` (uses paulhalonen@gmail.com)
- **SSH Access:** `fly ssh console -a [app-name]`

---

## 📦 Part 1: Contabo Server Setup

### Step 1: Connect to Contabo Server

**Option A: Via VNC (Recommended for first login)**
1. Log in to Contabo: https://my.contabo.com
2. Go to "Your Services" > Select server
3. Click "VNC" button
4. Use root credentials from control panel

**Option B: Via SSH**
```bash
ssh root@66.94.103.91
# or
ssh root@144.126.133.253
```

### Step 2: Update System
```bash
apt update && apt upgrade -y
apt install -y wireguard iptables resolvconf
```

### Step 3: Enable IP Forwarding
```bash
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
echo "net.ipv6.conf.all.forwarding=1" >> /etc/sysctl.conf
sysctl -p
```

### Step 4: Generate WireGuard Keys
```bash
cd /etc/wireguard
umask 077
wg genkey | tee server_private.key | wg pubkey > server_public.key
```

**Save these keys!** You'll need to add them to the database.

### Step 5: Create WireGuard Configuration
```bash
nano /etc/wireguard/wg0.conf
```

**Paste this configuration:**
```ini
[Interface]
Address = 10.0.0.1/24
ListenPort = 51820
PrivateKey = [PASTE_PRIVATE_KEY_FROM_STEP_4]

# NAT and firewall rules
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT
PostUp = iptables -A FORWARD -o wg0 -j ACCEPT
PostUp = iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT
PostDown = iptables -D FORWARD -o wg0 -j ACCEPT
PostDown = iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

### Step 6: Start WireGuard
```bash
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0
systemctl status wg-quick@wg0
```

### Step 7: Configure Firewall
```bash
# Allow WireGuard port
ufw allow 51820/udp
ufw allow 22/tcp  # SSH
ufw enable
```

### Step 8: Verify Installation
```bash
wg show
# Should display interface wg0 with public key
```

---

## 🚀 Part 2: Fly.io Server Setup

### Step 1: Install Fly CLI
```bash
# On your local machine
curl -L https://fly.io/install.sh | sh
```

### Step 2: Login to Fly.io
```bash
fly auth login
# Will open browser for authentication
```

### Step 3: Create Dockerfile for WireGuard

**Create `Dockerfile` in your project:**
```dockerfile
FROM ubuntu:22.04

# Install WireGuard
RUN apt-get update && \
    apt-get install -y wireguard iptables iproute2 && \
    apt-get clean

# Copy WireGuard config
COPY wg0.conf /etc/wireguard/wg0.conf

# Enable IP forwarding
RUN echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf && \
    echo "net.ipv6.conf.all.forwarding=1" >> /etc/sysctl.conf

# Start WireGuard
CMD ["wg-quick", "up", "wg0"]
```

### Step 4: Create `wg0.conf` for Fly.io
```ini
[Interface]
Address = 10.0.0.1/24
ListenPort = 51820
PrivateKey = [GENERATE_NEW_KEY]

PostUp = iptables -A FORWARD -i wg0 -j ACCEPT
PostUp = iptables -A FORWARD -o wg0 -j ACCEPT
PostUp = iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT
PostDown = iptables -D FORWARD -o wg0 -j ACCEPT
PostDown = iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

### Step 5: Create `fly.toml`
```toml
app = "truevault-dallas"  # or truevault-toronto

[build]
  dockerfile = "Dockerfile"

[[services]]
  internal_port = 51820
  protocol = "udp"

  [[services.ports]]
    port = 51820
```

### Step 6: Deploy to Fly.io
```bash
# For Dallas
fly launch --name truevault-dallas --region dfw

# For Toronto
fly launch --name truevault-toronto --region yyz
```

### Step 7: Verify Deployment
```bash
fly status -a truevault-dallas
fly logs -a truevault-dallas
```

---

## 🔧 Part 3: Adding Client Configurations

### Automatic via TrueVault Dashboard
Clients will be added automatically when they register through the TrueVault website. The system generates:
1. Client private/public key pair
2. WireGuard configuration file
3. QR code for mobile devices

### Manual Client Addition (if needed)
```bash
# Generate client keys
wg genkey | tee client_private.key | wg pubkey > client_public.key

# Add peer to wg0.conf
[Peer]
PublicKey = [CLIENT_PUBLIC_KEY]
AllowedIPs = 10.0.0.2/32
```

Then reload WireGuard:
```bash
wg syncconf wg0 <(wg-quick strip wg0)
```

---

## 📊 Part 4: Monitoring & Maintenance

### Check Server Status
```bash
wg show
systemctl status wg-quick@wg0
```

### View Connected Clients
```bash
wg show wg0 peers
```

### View Bandwidth Usage
```bash
wg show wg0 transfer
```

### Restart WireGuard
```bash
systemctl restart wg-quick@wg0
```

### View Logs
```bash
journalctl -u wg-quick@wg0 -f
```

---

## 🔐 Part 5: Security Hardening

### 1. Change SSH Port (Optional)
```bash
nano /etc/ssh/sshd_config
# Change Port 22 to Port 2222
systemctl restart sshd
```

### 2. Disable Root Login
```bash
nano /etc/ssh/sshd_config
# Set: PermitRootLogin no
systemctl restart sshd
```

### 3. Setup Fail2Ban
```bash
apt install fail2ban -y
systemctl enable fail2ban
systemctl start fail2ban
```

### 4. Configure UFW Firewall
```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 51820/udp
ufw allow 22/tcp
ufw enable
```

---

## 🔄 Part 6: Automated Health Checks

The TrueVault admin dashboard will automatically:
- Ping servers every 5 minutes
- Check WireGuard port accessibility
- Log response times
- Alert on failures
- Track uptime percentage

---

## 📝 Part 7: Server Keys for Database

After running Step 4 on each server, add the keys to the database:

### SQL Update Commands
```sql
-- Contabo New York
UPDATE servers 
SET public_key = '[YOUR_PUBLIC_KEY]', 
    private_key = '[YOUR_PRIVATE_KEY]'
WHERE id = 1;

-- Contabo St. Louis (VIP)
UPDATE servers 
SET public_key = '[YOUR_PUBLIC_KEY]', 
    private_key = '[YOUR_PRIVATE_KEY]'
WHERE id = 2;

-- Fly.io Dallas
UPDATE servers 
SET public_key = '[YOUR_PUBLIC_KEY]', 
    private_key = '[YOUR_PRIVATE_KEY]'
WHERE id = 3;

-- Fly.io Toronto
UPDATE servers 
SET public_key = '[YOUR_PUBLIC_KEY]', 
    private_key = '[YOUR_PRIVATE_KEY]'
WHERE id = 4;
```

---

## ✅ Verification Checklist

### For Each Server:
- [ ] WireGuard installed
- [ ] Keys generated and saved
- [ ] wg0.conf configured
- [ ] WireGuard service running
- [ ] IP forwarding enabled
- [ ] Firewall configured
- [ ] Port 51820 open
- [ ] Server accessible via ping
- [ ] Keys added to database
- [ ] Health check passing in admin dashboard

---

## 🆘 Troubleshooting

### WireGuard Won't Start
```bash
# Check logs
journalctl -u wg-quick@wg0 -n 50

# Check configuration
wg-quick strip wg0

# Manually start
wg-quick up wg0
```

### Can't Connect to Server
```bash
# Check if WireGuard is listening
netstat -unlp | grep 51820

# Check firewall
ufw status

# Test connectivity
ping -c 4 [SERVER_IP]
```

### Permission Issues
```bash
# Fix permissions
chmod 600 /etc/wireguard/wg0.conf
chmod 600 /etc/wireguard/*.key
```

---

## 📞 Support

**Issues?** Contact Kah-Len at paulhalonen@gmail.com

**Documentation:** Full blueprint at `/Master_Blueprint/SECTION_10_SERVER_MANAGEMENT.md`

---

**Last Updated:** January 18, 2026
**Version:** 1.0.0
