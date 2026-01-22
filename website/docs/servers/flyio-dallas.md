# Fly.io Server 3: Dallas, Texas

**App Name:** truevault-dallas  
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
- ✅ Streaming (Netflix, Hulu, etc.)
- ✅ General Browsing
- ✅ Light Usage

---

## Connection Details

| Property | Value |
|----------|-------|
| Shared IPv4 | 66.241.124.4 |
| Release IP | 137.66.58.225 |
| WireGuard Port | 51820 |
| API Port | 8443 |
| Region | Dallas, Texas |

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
| API | 8080 | 8443 |

---

## Costs

| Item | Amount |
|------|--------|
| Machine | ~$5.00/month |
| Bandwidth | Included (limited) |

---

## fly.toml Configuration

```toml
app = "truevault-dallas"
primary_region = "dfw"

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
    port = 8443
    handlers = ["tls", "http"]
```

---

## API Configuration

- **API URL:** https://66.241.124.4:8443
- **API Secret:** TrueVault2026DallasSecretKey32!
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

## Why Streaming Optimized?

Fly.io servers are:
- NOT flagged by streaming services (Netflix, etc.)
- Good for bypassing geo-restrictions
- Lower latency for streaming

---

## Fly.io CLI Access

```bash
flyctl ssh console -a truevault-dallas
```

---

**Last Updated:** January 23, 2026
