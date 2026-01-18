# TrueVault VPN - Server Scripts

These scripts must be deployed to each VPN server for the key generation system to work.

## Files

| File | Purpose |
|------|---------|
| `api.py` | Python Flask API for key generation |
| `setup.sh` | Automated server setup script |
| `requirements.txt` | Python dependencies |
| `env-*.template` | Environment configuration templates |

## How Key Generation Works

1. User clicks "Add Device" in dashboard
2. User enters device name and selects server
3. PHP backend calls VPN server API: `POST /api/create-peer`
4. **VPN SERVER** generates WireGuard keypair
5. Server adds peer to WireGuard interface
6. Server returns config file + QR code
7. User downloads config or scans QR

## Deployment Steps

### For Contabo Servers (New York, St. Louis)

```bash
# 1. SSH to server
ssh root@66.94.103.91  # or 144.126.133.253

# 2. Upload files
scp api.py setup.sh requirements.txt root@66.94.103.91:/root/

# 3. Run setup
chmod +x setup.sh
./setup.sh

# 4. Copy api.py to correct location
cp api.py /opt/truevault/

# 5. Restart API
systemctl restart truevault-api

# 6. Test
curl http://66.94.103.91:8443/api/health
```

### For Fly.io Servers (Dallas, Toronto)

Fly.io uses containers, so deployment is different:

```bash
# Check existing app
flyctl status -a truevault-dallas

# SSH into container
flyctl ssh console -a truevault-dallas

# Check if WireGuard is running
wg show
```

## API Endpoints

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/health` | GET | No | Health check |
| `/api/server-info` | GET | No | Get server public key |
| `/api/create-peer` | POST | Yes | Generate keys, create peer |
| `/api/remove-peer` | POST | Yes | Remove peer |
| `/api/list-peers` | GET | Yes | List all peers |
| `/api/get-config` | POST | Yes | Regenerate config for existing peer |

## Authentication

API calls require Bearer token:
```
Authorization: Bearer YOUR_API_SECRET
```

The API_SECRET is set in `/opt/truevault/.env` on each server.

## Server Configuration

| Server | IP | Subnet | Port |
|--------|-----|--------|------|
| New York | 66.94.103.91 | 10.8.0.0/24 | 51820 |
| St. Louis (VIP) | 144.126.133.253 | 10.8.1.0/24 | 51820 |
| Dallas | 66.241.124.4 | 10.8.2.0/24 | 51820 |
| Toronto | 66.241.125.247 | 10.8.3.0/24 | 51820 |

## Troubleshooting

### Check WireGuard status
```bash
wg show
systemctl status wg-quick@wg0
```

### Check API status
```bash
systemctl status truevault-api
journalctl -u truevault-api -f
```

### Test API
```bash
# Health check (no auth)
curl http://SERVER_IP:8443/api/health

# Server info (no auth)
curl http://SERVER_IP:8443/api/server-info

# Create peer (requires auth)
curl -X POST http://SERVER_IP:8443/api/create-peer \
  -H "Authorization: Bearer YOUR_API_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "device_name": "test"}'
```

### Restart services
```bash
systemctl restart wg-quick@wg0
systemctl restart truevault-api
```
