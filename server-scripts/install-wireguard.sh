#!/bin/bash
###############################################################################
# TrueVault VPN - WireGuard Installation Script
# Runs ON Contabo VPS to install and configure WireGuard
# 
# Usage: ./install-wireguard.sh
# 
# This script:
# 1. Updates system
# 2. Installs WireGuard
# 3. Generates server keys
# 4. Configures firewall
# 5. Starts WireGuard service
###############################################################################

set -e  # Exit on any error

echo "============================================"
echo "TrueVault VPN - WireGuard Installation"
echo "============================================"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "ERROR: Please run as root"
    exit 1
fi

# Get server's public IP
echo "Detecting server IP..."
SERVER_IP=$(curl -s ifconfig.me)
echo "Server IP: $SERVER_IP"
echo ""

# Update system
echo "Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

# Install WireGuard and dependencies
echo "Installing WireGuard..."
apt-get install -y wireguard wireguard-tools iptables curl qrencode

# Enable IP forwarding
echo "Enabling IP forwarding..."
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
echo "net.ipv6.conf.all.forwarding=1" >> /etc/sysctl.conf
sysctl -p > /dev/null

# Create WireGuard directory structure
echo "Creating directory structure..."
mkdir -p /etc/wireguard
mkdir -p /root/wireguard-clients
chmod 700 /etc/wireguard
chmod 700 /root/wireguard-clients

# Generate server keys
echo "Generating server keys..."
cd /etc/wireguard
umask 077
wg genkey | tee server_private.key | wg pubkey > server_public.key

SERVER_PRIVATE_KEY=$(cat server_private.key)
SERVER_PUBLIC_KEY=$(cat server_public.key)

chmod 600 server_private.key server_public.key

# Detect network interface
INTERFACE=$(ip route | grep default | awk '{print $5}' | head -n1)
if [ -z "$INTERFACE" ]; then
    INTERFACE="eth0"
fi
echo "Network interface: $INTERFACE"

# Create base server config (no clients yet)
echo "Creating server configuration..."
cat > /etc/wireguard/wg0.conf <<EOF
[Interface]
Address = 10.8.0.1/24
ListenPort = 51820
PrivateKey = $SERVER_PRIVATE_KEY
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o $INTERFACE -j MASQUERADE; ip6tables -A FORWARD -i wg0 -j ACCEPT; ip6tables -t nat -A POSTROUTING -o $INTERFACE -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o $INTERFACE -j MASQUERADE; ip6tables -D FORWARD -i wg0 -j ACCEPT; ip6tables -t nat -D POSTROUTING -o $INTERFACE -j MASQUERADE

# Clients will be added below this line
EOF

chmod 600 /etc/wireguard/wg0.conf

# Configure firewall
echo "Configuring firewall..."
if ! command -v ufw &> /dev/null; then
    apt-get install -y ufw
fi

ufw --force enable
ufw allow 22/tcp comment 'SSH'
ufw allow 51820/udp comment 'WireGuard'
ufw reload

# Start WireGuard
echo "Starting WireGuard..."
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0

# Verify it's running
sleep 2
if systemctl is-active --quiet wg-quick@wg0; then
    echo ""
    echo "============================================"
    echo "✅ WireGuard Installation Complete!"
    echo "============================================"
    echo "Server IP: $SERVER_IP"
    echo "Server Public Key: $SERVER_PUBLIC_KEY"
    echo "Listen Port: 51820"
    echo "Status: Running"
    echo ""
    echo "Next step: Use create-client-config.sh to add clients"
    echo "============================================"
    exit 0
else
    echo ""
    echo "❌ ERROR: WireGuard failed to start"
    systemctl status wg-quick@wg0
    exit 1
fi
