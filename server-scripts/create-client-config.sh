#!/bin/bash
###############################################################################
# TrueVault VPN - Client Configuration Generator
# Runs ON Contabo VPS to create client configs
# 
# Usage: ./create-client-config.sh <customer_id> <customer_email>
# 
# This script:
# 1. Generates client keys
# 2. Adds peer to server config
# 3. Creates .conf file
# 4. Outputs .conf to stdout (for web server to capture)
###############################################################################

set -e  # Exit on any error

CUSTOMER_ID="$1"
CUSTOMER_EMAIL="$2"

if [ -z "$CUSTOMER_ID" ] || [ -z "$CUSTOMER_EMAIL" ]; then
    echo "ERROR: Usage: $0 <customer_id> <customer_email>"
    exit 1
fi

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "ERROR: Please run as root"
    exit 1
fi

echo "============================================" >&2
echo "Creating WireGuard config for client" >&2
echo "Customer ID: $CUSTOMER_ID" >&2
echo "Email: $CUSTOMER_EMAIL" >&2
echo "============================================" >&2
echo "" >&2

# Get server info
cd /etc/wireguard
SERVER_PUBLIC_KEY=$(cat server_public.key)
SERVER_IP=$(curl -s ifconfig.me)

# Get next available IP address
LAST_IP=$(grep "AllowedIPs" wg0.conf | tail -n1 | grep -oP '10\.8\.0\.\K[0-9]+' || echo "1")
CLIENT_IP=$((LAST_IP + 1))

if [ $CLIENT_IP -gt 254 ]; then
    echo "ERROR: No more IP addresses available (max 254 clients)" >&2
    exit 1
fi

CLIENT_DIR="/root/wireguard-clients/$CUSTOMER_ID"
mkdir -p "$CLIENT_DIR"
cd "$CLIENT_DIR"

# Generate client keys
echo "Generating client keys..." >&2
wg genkey | tee client_private.key | wg pubkey > client_public.key

CLIENT_PRIVATE_KEY=$(cat client_private.key)
CLIENT_PUBLIC_KEY=$(cat client_public.key)

# Generate preshared key for extra security
PSK=$(wg genpsk)
echo "$PSK" > preshared.key

chmod 600 client_private.key client_public.key preshared.key

# Add peer to server config
echo "Adding peer to server config..." >&2
cat >> /etc/wireguard/wg0.conf <<EOF

# Client: $CUSTOMER_EMAIL ($CUSTOMER_ID)
[Peer]
PublicKey = $CLIENT_PUBLIC_KEY
PresharedKey = $PSK
AllowedIPs = 10.8.0.$CLIENT_IP/32
EOF

# Reload WireGuard
echo "Reloading WireGuard..." >&2
wg syncconf wg0 <(wg-quick strip wg0)

# Create client .conf file
echo "Generating client configuration file..." >&2
cat > client.conf <<EOF
[Interface]
PrivateKey = $CLIENT_PRIVATE_KEY
Address = 10.8.0.$CLIENT_IP/32
DNS = 1.1.1.1, 1.0.0.1

[Peer]
PublicKey = $SERVER_PUBLIC_KEY
PresharedKey = $PSK
Endpoint = $SERVER_IP:51820
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
EOF

chmod 600 client.conf

# Generate QR code for mobile devices
echo "Generating QR code..." >&2
qrencode -t ansiutf8 < client.conf > qr.txt

# Save metadata
cat > metadata.json <<EOF
{
  "customer_id": "$CUSTOMER_ID",
  "customer_email": "$CUSTOMER_EMAIL",
  "client_ip": "10.8.0.$CLIENT_IP",
  "server_ip": "$SERVER_IP",
  "created_at": "$(date -u +"%Y-%m-%d %H:%M:%S UTC")",
  "client_public_key": "$CLIENT_PUBLIC_KEY"
}
EOF

echo "" >&2
echo "============================================" >&2
echo "✅ Client configuration created!" >&2
echo "============================================" >&2
echo "Client IP: 10.8.0.$CLIENT_IP" >&2
echo "Config saved to: $CLIENT_DIR/client.conf" >&2
echo "QR code saved to: $CLIENT_DIR/qr.txt" >&2
echo "" >&2
echo "Outputting client.conf to stdout..." >&2
echo "============================================" >&2

# Output the conf file to stdout (this is what PHP will capture)
cat client.conf

exit 0
