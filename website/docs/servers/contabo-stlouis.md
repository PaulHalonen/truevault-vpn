# Contabo Server 2: St. Louis (US-Central) - DEDICATED

**Server ID:** vmi2990005  
**Type:** Cloud VPS 10 SSD (no setup)  
**Status:** Running  
**Access Level:** DEDICATED - seige235@yahoo.com ONLY

---

## ⚠️ CRITICAL: DEDICATED SERVER

This server is **EXCLUSIVELY** assigned to: **seige235@yahoo.com**

- ❌ NOT visible to any other users
- ❌ NOT visible to other VIP users
- ❌ NOT available for public use
- ✅ ONLY the dedicated owner can see/access this server

---

## Connection Details

| Property | Value |
|----------|-------|
| IP Address | 144.126.133.253 |
| MAC Address | 00:50:56:5f:37:1c |
| IPv6 Address | 2605:a140:2299:0005:0000:0000:0000:0001/64 |
| WireGuard Port | 51820 |
| API Port | 8443 |
| Host System | 22638 |
| Region | US-central |
| Default User | root |

---

## Server Specifications

| Spec | Value |
|------|-------|
| Disk Space | 150 GB SSD |
| RAM | 4 GB |
| CPU | 2 vCPU |
| Bandwidth | Unlimited (fair use ~1TB) |

---

## Costs

| Item | Amount |
|------|--------|
| Base VPS | $4.95/month |
| Location Fee | $1.20/month |
| **Total** | **$6.15/month** |

---

## VNC Access

| Property | Value |
|----------|-------|
| VNC Enabled | Yes |
| VNC URL | 207.244.248.38:63098 |

---

## WireGuard Configuration

```
[Interface]
Address = 10.0.1.1/24
ListenPort = 51820
PrivateKey = [STORED SECURELY ON SERVER]

PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

---

## API Configuration

- **API URL:** https://144.126.133.253:8443
- **API Secret:** TrueVault2026STLSecretKey32Char!
- **Endpoints:**
  - GET /health - Server health check
  - POST /api/add-peer - Add WireGuard peer
  - POST /api/remove-peer - Remove peer
  - GET /api/list-peers - List all peers

---

## Features Allowed

| Feature | Allowed |
|---------|---------|
| Port Forwarding | ✅ YES |
| Xbox/Gaming | ✅ YES |
| uTorrent/P2P | ✅ YES |
| Streaming | ✅ YES |
| High Bandwidth | ✅ YES |

---

## SSH Access

```bash
ssh root@144.126.133.253
Password: Andassi8
```

---

## Dedicated Owner

| Property | Value |
|----------|-------|
| Email | seige235@yahoo.com |
| Plan | Dedicated Server Plan |
| Assigned Date | December 25, 2025 |

---

## Business Transfer Note

**THIS SERVER DOES NOT TRANSFER WITH BUSINESS SALE**

When business is sold to seige235@yahoo.com:
- This server remains with the dedicated owner
- Not included in business valuation
- Owner keeps full control

---

## Billing Dates

- Created: December 25, 2025
- Next Payment Due: January 25, 2026
- Payment Method: Credit Card

---

**Last Updated:** January 23, 2026
