# Contabo Server 1: New York (US-East)

**Server ID:** vmi2990026  
**Type:** Cloud VPS 10 SSD (no setup)  
**Status:** Running  
**Access Level:** PUBLIC (All Users)

---

## Connection Details

| Property | Value |
|----------|-------|
| IP Address | 66.94.103.91 |
| MAC Address | 00:50:56:5f:37:1f |
| IPv6 Address | 2605:a142:2299:0026:0000:0000:0000:0001/64 |
| WireGuard Port | 51820 |
| API Port | 8443 |
| Host System | 21597 |
| Region | US-east |
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
| Location Fee | $1.80/month |
| **Total** | **$6.75/month** |

---

## VNC Access

| Property | Value |
|----------|-------|
| VNC Enabled | Yes |
| VNC URL | 154.53.39.97:63031 |

---

## WireGuard Configuration

```
[Interface]
Address = 10.0.0.1/24
ListenPort = 51820
PrivateKey = [STORED SECURELY ON SERVER]

PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```

---

## API Configuration

- **API URL:** https://66.94.103.91:8443
- **API Secret:** TrueVault2026NYSecretKey32Chars!
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
ssh root@66.94.103.91
Password: Andassi8
```

---

## Billing Dates

- Created: December 25, 2025
- Next Payment Due: January 25, 2026
- Payment Method: Credit Card

---

**Last Updated:** January 23, 2026
