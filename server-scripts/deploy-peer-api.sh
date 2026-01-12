#!/bin/bash
# ============================================
# TrueVault VPN - Server Deployment Script
# ============================================
# Run this on each VPN server to set up the peer API
# 
# Usage:
#   ./deploy-peer-api.sh [NY|STL|TX|CAN]
#
# Example:
#   ./deploy-peer-api.sh NY

set -e

SERVER_TYPE=${1:-NY}

# Server configurations
case $SERVER_TYPE in
    NY)
        NETWORK="10.0.0"
        NAME="TrueVault-NY"
        ;;
    STL)
        NETWORK="10.0.1"
        NAME="TrueVault-STL"
        ;;
    TX)
        NETWORK="10.10.1"
        NAME="TrueVault-TX"
        ;;
    CAN)
        NETWORK="10.10.0"
        NAME="TrueVault-CAN"
        ;;
    *)
        echo "Unknown server type: $SERVER_TYPE"
        echo "Usage: $0 [NY|STL|TX|CAN]"
        exit 1
        ;;
esac

echo "============================================"
echo "Deploying TrueVault Peer API"
echo "Server: $NAME"
echo "Network: $NETWORK.0/24"
echo "============================================"

# Create directory
mkdir -p /opt/truevault

# Install Python dependencies
echo "Installing dependencies..."
pip3 install flask requests --quiet || pip install flask requests --quiet

# Copy peer_api.py (assumes it's in current directory)
if [ -f "peer_api.py" ]; then
    cp peer_api.py /opt/truevault/
    echo "✓ Copied peer_api.py"
else
    echo "ERROR: peer_api.py not found in current directory"
    exit 1
fi

# Create systemd service
cat > /etc/systemd/system/truevault-peer-api.service << EOF
[Unit]
Description=TrueVault VPN Peer Management API
After=network.target wg-quick@wg0.service
Wants=wg-quick@wg0.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/truevault
Environment="TRUEVAULT_API_SECRET=TrueVault2026SecretKey"
Environment="PEER_API_PORT=8080"
Environment="SERVER_NAME=$NAME"
Environment="SERVER_NETWORK=$NETWORK"
ExecStart=/usr/bin/python3 /opt/truevault/peer_api.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

echo "✓ Created systemd service"

# Reload and start service
systemctl daemon-reload
systemctl enable truevault-peer-api
systemctl restart truevault-peer-api

echo "✓ Service started"

# Check status
sleep 2
if systemctl is-active --quiet truevault-peer-api; then
    echo "✓ Peer API is running"
    
    # Test health endpoint
    HEALTH=$(curl -s http://localhost:8080/health)
    echo "Health check: $HEALTH"
else
    echo "ERROR: Service failed to start"
    systemctl status truevault-peer-api
    exit 1
fi

echo ""
echo "============================================"
echo "Deployment Complete!"
echo "============================================"
echo "API URL: http://$(hostname -I | awk '{print $1}'):8080"
echo ""
echo "Test with:"
echo "  curl http://localhost:8080/health"
echo ""
echo "Logs:"
echo "  journalctl -u truevault-peer-api -f"
echo "============================================"
