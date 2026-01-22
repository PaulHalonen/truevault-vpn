# Fly.io Server 4: Toronto, Canada

**App Name:** truevault-toronto  
**Type:** shared-1x-cpu@256MB  
**Status:** Running  
**Access Level:** PUBLIC (All Users)

---

## ⚠️ BANDWIDTH LIMITED SERVER

This server has LIMITED bandwidth. The following are **NOT ALLOWED**:

- ❌ Port Forwarding
- ❌ Xbox/PlayStation Gaming
- ❌ uTorrent/BitTorrent/P2P
- ❌ High Bandwidth Services

**Allowed Uses:**
- ✅ Streaming (Canadian content: CBC, TSN, etc.)
- ✅ General Browsing
- ✅ Light Usage
- ✅ Canadian IP for geo-restricted content

---

## Connection Details

| Property | Value |
|----------|-------|
| Shared IPv4 | 66.241.125.247 |
| Release IP | 37.16.6.139 |
| WireGuard Port | 51820 |
| API Port | 8080 |
| Region | Toronto, Canada |

---

## Machine Specifications

| Spec | Value |
|------|-------|
| CPU | shared-1x-cpu |
| RAM | 256 MB |
| Type | Fly Machine |

---

## Services Configuration

| Service | Internal Port | External Port |
|---------|---------------|---------------|
| WireGuard | 51820 | 51820 |
| API | 8080 | 8080 |

---

## Costs

| Item | Amount |
|------|--------|
| Machine | ~$5.00/month |
| Bandwidth | Included (limited) |

---

## fly.toml Configuration

```toml
app = "truevault-toronto"
primary_region = "yyz"

[build]
  image = "truevault/wireguard:latest"

[[services]]
  internal_port = 51820
  protocol = "udp"
  [[services.ports]]
    port = 51820

[[services]]
  internal_port = 8080
  protocol = "tcp"
  [[services.ports]]
    port = 8080
```

---

## API Configuration

- **API URL:** https://66.241.125.247:8080
- **API Secret:** TrueVault2026TorontoSecretKey32
- **Endpoints:**
  - GET /health - Server health check
  - POST /api/add-peer - Add WireGuard peer
  - POST /api/remove-peer - Remove peer
  - GET /api/list-peers - List all peers

---

## Features Allowed

| Feature | Allowed |
|---------|---------|
| Port Forwarding | ❌ NO |
| Xbox/Gaming | ❌ NO |
| uTorrent/P2P | ❌ NO |
| Streaming | ✅ YES |
| High Bandwidth | ❌ NO |

---

## Why Canadian Server?

- Access Canadian-only content (CBC, CTV, TSN)
- Canadian IP address for privacy
- Good for users in northern US states
- Future expansion market (cloneable system)

---

## Fly.io CLI Access

```bash
flyctl ssh console -a truevault-toronto
```

---

**Last Updated:** January 23, 2026
