#!/bin/bash
# TrueVault VPN - Client Configuration Generator
# Run on VPN server to generate new client configs
# 
# Usage: ./create-client-config.sh <client_name> [client_ip]
# Example: ./create-client-config.sh user123 10.8.0.5
#
# Created: January 23, 2026

set -e

# Check arguments
if [ -z "$1" ]; then
    echo "Usage: $0 <client_name> [client_ip]"
    echo "Example: $0 user123 10.8.0.5"
    exit 1
fi

CLIENT_NAME=$1
CLIENT_IP=${2:-""}

# Configuration
WG_DIR="/etc/wireguard"
CLIENTS_DIR="$WG_DIR/clients"
SERVER_PUBLIC_KEY=$(cat $WG_DIR/server_public.key)
SERVER_ENDPOINT=$(curl -s ifconfig.me):51820
DNS_SERVERS="1.1.1.1, 8.8.8.8"

echo "============================================"
echo "  TrueVault VPN - Client Config Generator"
echo "============================================"
echo ""

# Create clients directory if not exists
mkdir -p $CLIENTS_DIR

# Check if client already exists
if [ -f "$CLIENTS_DIR/${CLIENT_NAME}_private.key" ]; then
    echo "WARNING: Client $CLIENT_NAME already exists!"
    echo "Using existing keys..."
    CLIENT_PRIVATE_KEY=$(cat $CLIENTS_DIR/${CLIENT_NAME}_private.key)
    CLIENT_PUBLIC_KEY=$(cat $CLIENTS_DIR/${CLIENT_NAME}_public.key)
else
    # Generate client keys
    echo "[1/4] Generating client keys..."
    wg genkey | tee $CLIENTS_DIR/${CLIENT_NAME}_private.key | wg pubkey > $CLIENTS_DIR/${CLIENT_NAME}_public.key
    chmod 600 $CLIENTS_DIR/${CLIENT_NAME}_private.key
    
    CLIENT_PRIVATE_KEY=$(cat $CLIENTS_DIR/${CLIENT_NAME}_private.key)
    CLIENT_PUBLIC_KEY=$(cat $CLIENTS_DIR/${CLIENT_NAME}_public.key)
fi

# Determine client IP
if [ -z "$CLIENT_IP" ]; then
    # Auto-assign IP based on existing peers
    LAST_IP=$(grep "AllowedIPs" $WG_DIR/wg0.conf 2>/dev/null | tail -1 | grep -oP '10\.8\.0\.\K[0-9]+' || echo "1")
    NEXT_IP=$((LAST_IP + 1))
    CLIENT_IP="10.8.0.$NEXT_IP"
    echo "[2/4] Auto-assigned IP: $CLIENT_IP"
else
    echo "[2/4] Using specified IP: $CLIENT_IP"
fi

# Add peer to server config
echo "[3/4] Adding peer to server..."
wg set wg0 peer $CLIENT_PUBLIC_KEY allowed-ips ${CLIENT_IP}/32

# Save config
wg-quick save wg0

# Generate client .conf file
echo "[4/4] Generating client config file..."

CONFIG_FILE="$CLIENTS_DIR/${CLIENT_NAME}.conf"
cat > $CONFIG_FILE << EOF
[Interface]
# TrueVault VPN - Client: $CLIENT_NAME
# Generated: $(date)
PrivateKey = $CLIENT_PRIVATE_KEY
Address = ${CLIENT_IP}/24
DNS = $DNS_SERVERS

[Peer]
PublicKey = $SERVER_PUBLIC_KEY
Endpoint = $SERVER_ENDPOINT
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
EOF

chmod 600 $CONFIG_FILE

# Generate QR code if qrencode is available
if command -v qrencode &> /dev/null; then
    echo ""
    echo "QR Code for mobile setup:"
    qrencode -t ANSIUTF8 < $CONFIG_FILE
    
    # Also save QR as PNG
    qrencode -t PNG -o $CLIENTS_DIR/${CLIENT_NAME}_qr.png < $CONFIG_FILE
    echo ""
    echo "QR code saved to: $CLIENTS_DIR/${CLIENT_NAME}_qr.png"
fi

echo ""
echo "============================================"
echo "  Client Configuration Complete!"
echo "============================================"
echo ""
echo "Client Name: $CLIENT_NAME"
echo "Client IP: $CLIENT_IP"
echo "Config File: $CONFIG_FILE"
echo ""
echo "Client Public Key:"
echo "$CLIENT_PUBLIC_KEY"
echo ""

# Output config content for easy copying
echo "--- Config Content ---"
cat $CONFIG_FILE
echo "--- End Config ---"
