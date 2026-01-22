#!/bin/bash
# TrueVault VPN - WireGuard Installation Script
# Run on new dedicated servers to setup WireGuard
# Created: January 23, 2026

set -e

echo "============================================"
echo "  TrueVault VPN - WireGuard Installer"
echo "============================================"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "ERROR: Please run as root"
    exit 1
fi

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VERSION=$VERSION_ID
else
    echo "ERROR: Cannot detect OS"
    exit 1
fi

echo "Detected OS: $OS $VERSION"
echo ""

# Update system
echo "[1/8] Updating system packages..."
apt update && apt upgrade -y

# Install WireGuard
echo "[2/8] Installing WireGuard..."
apt install -y wireguard wireguard-tools

# Install additional tools
echo "[3/8] Installing support tools..."
apt install -y qrencode iptables-persistent

# Enable IP forwarding
echo "[4/8] Enabling IP forwarding..."
cat >> /etc/sysctl.conf << EOF

# WireGuard IP forwarding
net.ipv4.ip_forward=1
net.ipv6.conf.all.forwarding=1
EOF
sysctl -p

# Generate server keys
echo "[5/8] Generating server keys..."
cd /etc/wireguard
umask 077

wg genkey | tee server_private.key | wg pubkey > server_public.key

SERVER_PRIVATE=$(cat server_private.key)
SERVER_PUBLIC=$(cat server_public.key)

echo ""
echo "Server Public Key: $SERVER_PUBLIC"
echo ""

# Detect main network interface
MAIN_INTERFACE=$(ip route | grep default | awk '{print $5}' | head -1)
echo "Detected network interface: $MAIN_INTERFACE"

# Create WireGuard config
echo "[6/8] Creating WireGuard configuration..."
cat > /etc/wireguard/wg0.conf << EOF
[Interface]
PrivateKey = $SERVER_PRIVATE
Address = 10.8.0.1/24
ListenPort = 51820
SaveConfig = true

# NAT and forwarding rules
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT
PostUp = iptables -A FORWARD -o wg0 -j ACCEPT
PostUp = iptables -t nat -A POSTROUTING -o $MAIN_INTERFACE -j MASQUERADE

PostDown = iptables -D FORWARD -i wg0 -j ACCEPT
PostDown = iptables -D FORWARD -o wg0 -j ACCEPT
PostDown = iptables -t nat -D POSTROUTING -o $MAIN_INTERFACE -j MASQUERADE

# Peers will be added dynamically below
EOF

chmod 600 /etc/wireguard/wg0.conf

# Configure firewall
echo "[7/8] Configuring firewall..."
ufw allow 22/tcp comment 'SSH'
ufw allow 51820/udp comment 'WireGuard'
ufw allow 8443/tcp comment 'TrueVault API'
ufw --force enable

# Enable and start WireGuard
echo "[8/8] Starting WireGuard service..."
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0

# Verify installation
echo ""
echo "============================================"
echo "  Installation Complete!"
echo "============================================"
echo ""
echo "WireGuard Status:"
wg show wg0
echo ""
echo "Server Public Key (SAVE THIS):"
echo "$SERVER_PUBLIC"
echo ""
echo "Configuration file: /etc/wireguard/wg0.conf"
echo ""

# Save public key to accessible location
echo "$SERVER_PUBLIC" > /root/wireguard_public_key.txt
echo "Public key saved to: /root/wireguard_public_key.txt"
