#!/bin/bash
#
# TrueVault VPN Server Setup Script
# Run as root on each VPN server
#
# Usage: ./setup.sh
#

set -e

echo "=========================================="
echo " TrueVault VPN Server Setup"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "ERROR: Please run as root (sudo ./setup.sh)"
    exit 1
fi

# Get server configuration
echo "Enter server configuration:"
echo ""
read -p "Server Name (new-york/st-louis/dallas/toronto): " SERVER_NAME
read -p "Server Public IP Address: " SERVER_IP
read -p "API Secret (32+ characters, shared with PHP backend): " API_SECRET

# Determine subnet based on server name
case $SERVER_NAME in
    "new-york"|"newyork"|"ny")
        SUBNET_BASE="10.8.0"
        SERVER_NAME="new-york"
        ;;
    "st-louis"|"stlouis"|"stl")
        SUBNET_BASE="10.8.1"
        SERVER_NAME="st-louis"
        ;;
    "dallas"|"dal")
        SUBNET_BASE="10.8.2"
        SERVER_NAME="dallas"
        ;;
    "toronto"|"tor")
        SUBNET_BASE="10.8.3"
        SERVER_NAME="toronto"
        ;;
    *)
        echo "Unknown server name. Using default subnet 10.8.0"
        SUBNET_BASE="10.8.0"
        ;;
esac

echo ""
echo "Configuration:"
echo "  Server Name: $SERVER_NAME"
echo "  Server IP: $SERVER_IP"
echo "  Subnet: ${SUBNET_BASE}.0/24"
echo ""
read -p "Continue? (y/n): " CONFIRM
if [ "$CONFIRM" != "y" ]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "[1/8] Updating system..."
apt update && apt upgrade -y

echo ""
echo "[2/8] Installing dependencies..."
apt install -y python3 python3-pip python3-venv wireguard wireguard-tools ufw

echo ""
echo "[3/8] Enabling IP forwarding..."
if ! grep -q "net.ipv4.ip_forward=1" /etc/sysctl.conf; then
    echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
fi
if ! grep -q "net.ipv6.conf.all.forwarding=1" /etc/sysctl.conf; then
    echo "net.ipv6.conf.all.forwarding=1" >> /etc/sysctl.conf
fi
sysctl -p

echo ""
echo "[4/8] Setting up WireGuard..."

# Check if WireGuard keys already exist
if [ -f /etc/wireguard/server_private.key ]; then
    echo "  WireGuard keys already exist, using existing keys."
    SERVER_PRIVATE=$(cat /etc/wireguard/server_private.key)
    SERVER_PUBLIC=$(cat /etc/wireguard/server_public.key)
else
    echo "  Generating new WireGuard keys..."
    umask 077
    wg genkey | tee /etc/wireguard/server_private.key | wg pubkey > /etc/wireguard/server_public.key
    SERVER_PRIVATE=$(cat /etc/wireguard/server_private.key)
    SERVER_PUBLIC=$(cat /etc/wireguard/server_public.key)
fi

echo ""
echo "  Server Public Key: $SERVER_PUBLIC"
echo ""

# Create WireGuard config
cat > /etc/wireguard/wg0.conf << EOF
[Interface]
PrivateKey = $SERVER_PRIVATE
Address = ${SUBNET_BASE}.1/24
ListenPort = 51820
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Peers are added dynamically via API
EOF

chmod 600 /etc/wireguard/wg0.conf

echo ""
echo "[5/8] Setting up TrueVault API..."
mkdir -p /opt/truevault

# Create Python virtual environment
cd /opt/truevault
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install flask qrcode pillow

# Copy api.py if it exists in current directory, otherwise create placeholder
if [ -f ./api.py ]; then
    cp ./api.py /opt/truevault/api.py
else
    echo "WARNING: api.py not found in current directory."
    echo "You need to copy api.py to /opt/truevault/ manually."
fi

# Create environment file
cat > /opt/truevault/.env << EOF
SERVER_NAME=$SERVER_NAME
SERVER_IP=$SERVER_IP
WG_PORT=51820
API_PORT=8443
SUBNET_BASE=$SUBNET_BASE
DNS=1.1.1.1, 1.0.0.1
API_SECRET=$API_SECRET
DB_PATH=/opt/truevault/peers.db
EOF

chmod 600 /opt/truevault/.env

echo ""
echo "[6/8] Creating systemd service..."
cat > /etc/systemd/system/truevault-api.service << EOF
[Unit]
Description=TrueVault VPN Key Management API
After=network.target wg-quick@wg0.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/truevault
EnvironmentFile=/opt/truevault/.env
ExecStart=/opt/truevault/venv/bin/python /opt/truevault/api.py
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload

echo ""
echo "[7/8] Configuring firewall..."
ufw allow 51820/udp comment 'WireGuard VPN'
ufw allow 8443/tcp comment 'TrueVault API'
ufw allow 22/tcp comment 'SSH'
ufw --force enable

echo ""
echo "[8/8] Starting services..."
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0 || systemctl restart wg-quick@wg0

systemctl enable truevault-api
systemctl start truevault-api || echo "Note: API may fail if api.py is not present"

echo ""
echo "=========================================="
echo " SETUP COMPLETE!"
echo "=========================================="
echo ""
echo " Server Name:    $SERVER_NAME"
echo " Server IP:      $SERVER_IP"
echo " WireGuard Port: 51820/UDP"
echo " API Port:       8443/TCP"
echo " Subnet:         ${SUBNET_BASE}.0/24"
echo ""
echo " SERVER PUBLIC KEY:"
echo " $SERVER_PUBLIC"
echo ""
echo " IMPORTANT: Add this to your database!"
echo ""
echo " Test commands:"
echo "   wg show"
echo "   curl http://${SERVER_IP}:8443/api/health"
echo "   curl http://${SERVER_IP}:8443/api/server-info"
echo ""
echo " View logs:"
echo "   journalctl -u truevault-api -f"
echo ""
echo "=========================================="
