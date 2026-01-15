# SECTION 13: API ENDPOINTS

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Priority:** CRITICAL - Backend Foundation  
**Complexity:** HIGH - Complete API Documentation  

---

## 📋 TABLE OF CONTENTS

1. [API Overview](#overview)
2. [Authentication](#authentication)
3. [Device Management](#devices)
4. [Server Management](#servers)
5. [Port Forwarding](#port-forwarding)
6. [User Profile](#profile)
7. [Settings](#settings)
8. [Statistics](#statistics)
9. [Support](#support)
10. [Payment & Billing](#billing)
11. [VIP System](#vip)
12. [Admin Endpoints](#admin)
13. [Response Formats](#responses)
14. [Error Codes](#errors)

---

## 🌐 API OVERVIEW

### **Base URL**

```
Production: https://vpn.the-truth-publishing.com/api
Development: http://localhost/vpn.the-truth-publishing.com/api
```

### **Request Format**

**All requests must include:**
```http
Content-Type: application/json
Authorization: Bearer {token}
```

**Example Request:**
```javascript
fetch('/api/devices.php?action=list', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
    }
})
```

### **Response Format**

**Success Response:**
```json
{
    "success": true,
    "data": {...},
    "message": "Optional success message"
}
```

**Error Response:**
```json
{
    "success": false,
    "error": "Error message",
    "code": "ERROR_CODE"
}
```

### **Authentication**

**All endpoints (except `/auth.php`) require authentication:**
- JWT token in `Authorization: Bearer {token}` header
- Token expires after 30 days
- Refresh tokens available via `/api/auth.php?action=refresh`

### **Rate Limiting**

```
Standard users: 100 requests/minute
VIP users: 1000 requests/minute
Admin users: Unlimited
```

**Rate limit headers:**
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1642345678
```

---

## 🔐 AUTHENTICATION

### **POST /api/auth.php?action=register**

Register a new user account.

**Request:**
```json
{
    "email": "user@example.com",
    "password": "SecurePassword123!",
    "first_name": "John",
    "last_name": "Doe"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Account created successfully",
    "user": {
        "id": 123,
        "email": "user@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "tier": "standard"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Errors:**
- `EMAIL_EXISTS` - Email already registered
- `WEAK_PASSWORD` - Password doesn't meet requirements
- `INVALID_EMAIL` - Email format invalid

---

### **POST /api/auth.php?action=login**

Authenticate user and receive JWT token.

**Request:**
```json
{
    "email": "user@example.com",
    "password": "SecurePassword123!"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 123,
        "email": "user@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "tier": "standard",
        "subscription_status": "active"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_at": "2026-02-14T12:00:00Z"
}
```

**Errors:**
- `INVALID_CREDENTIALS` - Email or password incorrect
- `ACCOUNT_SUSPENDED` - Account has been suspended
- `EMAIL_NOT_VERIFIED` - Email verification required

---

### **POST /api/auth.php?action=refresh**

Refresh JWT token before expiration.

**Request:**
```json
{
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response:**
```json
{
    "success": true,
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_at": "2026-02-14T12:00:00Z"
}
```

---

### **POST /api/auth.php?action=logout**

Invalidate current token.

**Request:**
```json
{}
```

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

### **POST /api/auth.php?action=forgot_password**

Request password reset email.

**Request:**
```json
{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password reset email sent"
}
```

---

### **POST /api/auth.php?action=reset_password**

Reset password with token from email.

**Request:**
```json
{
    "token": "reset_token_from_email",
    "new_password": "NewSecurePassword123!"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password reset successfully"
}
```

---

## 📱 DEVICE MANAGEMENT

### **GET /api/devices.php?action=list**

Get all devices for authenticated user.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "devices": [
        {
            "id": 1,
            "user_id": 123,
            "name": "iPhone 15",
            "device_type": "phone",
            "vpn_ip": "10.8.0.15",
            "public_key": "base64_public_key",
            "current_server_id": 1,
            "server_name": "New York",
            "server_location": "New York, USA",
            "is_connected": true,
            "last_seen": "2026-01-15T06:15:00Z",
            "created_at": "2026-01-10T10:00:00Z",
            "bandwidth_used": 5242880000,
            "status": "active"
        }
    ]
}
```

---

### **POST /api/devices.php?action=create**

Create a new device.

**Request:**
```json
{
    "name": "Work Laptop",
    "device_type": "laptop",
    "server_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "message": "Device created successfully",
    "device": {
        "id": 2,
        "name": "Work Laptop",
        "device_type": "laptop",
        "vpn_ip": "10.8.0.16",
        "public_key": "generated_public_key",
        "private_key": "generated_private_key",
        "current_server_id": 1,
        "server_name": "New York",
        "created_at": "2026-01-15T06:20:00Z"
    }
}
```

**Errors:**
- `DEVICE_LIMIT_REACHED` - User has reached device limit for their tier
- `INVALID_SERVER` - Server ID doesn't exist
- `NAME_REQUIRED` - Device name is required

---

### **POST /api/devices.php?action=update**

Update device details.

**Request:**
```json
{
    "device_id": 2,
    "name": "Personal Laptop",
    "device_type": "laptop"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Device updated successfully",
    "device": {
        "id": 2,
        "name": "Personal Laptop",
        "device_type": "laptop",
        "updated_at": "2026-01-15T06:25:00Z"
    }
}
```

---

### **POST /api/devices.php?action=delete**

Remove a device.

**Request:**
```json
{
    "device_id": 2
}
```

**Response:**
```json
{
    "success": true,
    "message": "Device removed successfully"
}
```

**Note:** This also removes the device from the WireGuard server configuration.

---

### **POST /api/devices.php?action=switch_server**

Switch device to different server.

**Request:**
```json
{
    "device_id": 1,
    "server_id": 3
}
```

**Response:**
```json
{
    "success": true,
    "message": "Server switched successfully",
    "device": {
        "id": 1,
        "current_server_id": 3,
        "server_name": "Dallas",
        "vpn_ip": "10.8.2.15",
        "new_config": "[Interface]\nPrivateKey=...\n[Peer]\n..."
    }
}
```

**Note:** Returns new configuration with updated server endpoint.

---

### **GET /api/devices.php?action=status**

Get real-time connection status for device.

**Query Parameters:**
- `device_id` (required)

**Response:**
```json
{
    "success": true,
    "status": {
        "device_id": 1,
        "is_connected": true,
        "last_handshake": "2026-01-15T06:14:30Z",
        "server_name": "New York",
        "vpn_ip": "10.8.0.15",
        "bytes_received": 15728640,
        "bytes_sent": 5242880,
        "connected_since": "2026-01-15T04:30:00Z",
        "connection_duration": 6300
    }
}
```

---

### **GET /api/download-config.php**

Download WireGuard configuration file.

**Query Parameters:**
- `device_id` (required)

**Response:**
Downloads `.conf` file directly.

**File Content:**
```ini
[Interface]
PrivateKey = client_private_key_base64
Address = 10.8.0.15/32
DNS = 1.1.1.1, 1.0.0.1

[Peer]
PublicKey = server_public_key_base64
Endpoint = 66.94.103.91:51820
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
```

---

### **GET /api/qr-code.php**

Generate QR code for device configuration.

**Query Parameters:**
- `device_id` (required)

**Response:**
```json
{
    "success": true,
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
}
```

---

### **GET /api/config-text.php**

Get configuration as plain text.

**Query Parameters:**
- `device_id` (required)

**Response:**
```json
{
    "success": true,
    "config": "[Interface]\nPrivateKey = ...\nAddress = 10.8.0.15/32\n..."
}
```

---

## 🖥️ SERVER MANAGEMENT

### **GET /api/servers.php?action=list**

Get all available servers.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "servers": [
        {
            "id": 1,
            "name": "New York",
            "location": "New York, USA",
            "country_code": "US",
            "flag": "🇺🇸",
            "ip_address": "66.94.103.91",
            "endpoint": "66.94.103.91:51820",
            "public_key": "server_public_key_base64",
            "status": "online",
            "load": 45,
            "latency": 12,
            "speed": 10,
            "features": ["General Use", "P2P Allowed"],
            "recommended": true,
            "available_ips": 243,
            "max_bandwidth": "Limited"
        },
        {
            "id": 2,
            "name": "St. Louis VIP",
            "location": "St. Louis, USA",
            "country_code": "US",
            "flag": "🇺🇸",
            "ip_address": "144.126.133.253",
            "endpoint": "144.126.133.253:51820",
            "status": "online",
            "vip_only": true,
            "vip_email": "seige235@yahoo.com",
            "features": ["VIP Exclusive", "Dedicated"],
            "recommended": false
        },
        {
            "id": 3,
            "name": "Dallas Streaming",
            "location": "Dallas, USA",
            "country_code": "US",
            "flag": "🇺🇸",
            "ip_address": "66.241.124.4",
            "endpoint": "66.241.124.4:51820",
            "status": "online",
            "load": 32,
            "features": ["Streaming Optimized", "Netflix", "Hulu"],
            "recommended": false
        },
        {
            "id": 4,
            "name": "Toronto",
            "location": "Toronto, Canada",
            "country_code": "CA",
            "flag": "🇨🇦",
            "ip_address": "66.241.125.247",
            "endpoint": "66.241.125.247:51820",
            "status": "online",
            "load": 28,
            "features": ["Canadian Content"],
            "recommended": false
        }
    ]
}
```

---

### **GET /api/servers.php?action=status**

Get real-time server status.

**Query Parameters:**
- `server_id` (optional - returns all if not specified)

**Response:**
```json
{
    "success": true,
    "servers": [
        {
            "id": 1,
            "name": "New York",
            "status": "online",
            "uptime": 99.99,
            "load": 45,
            "active_connections": 127,
            "total_bandwidth": 1073741824,
            "last_checked": "2026-01-15T06:30:00Z"
        }
    ]
}
```

---

### **GET /api/servers.php?action=ping**

Test latency to servers.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "latency": [
        {
            "server_id": 1,
            "server_name": "New York",
            "latency_ms": 12,
            "status": "reachable"
        },
        {
            "server_id": 3,
            "server_name": "Dallas",
            "latency_ms": 45,
            "status": "reachable"
        }
    ]
}
```

---

## 🔌 PORT FORWARDING

### **GET /api/port-forwarding.php?action=list**

Get all port forwarding rules for user.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "rules": [
        {
            "id": 1,
            "user_id": 123,
            "device_name": "Living Room Camera",
            "device_type": "ip_camera",
            "icon": "📷",
            "local_ip": "192.168.1.105",
            "local_port": 80,
            "protocol": "tcp",
            "public_url": "https://vpn.the-truth-publishing.com/port/abc123",
            "public_port": 8105,
            "status": "active",
            "created_at": "2026-01-12T10:00:00Z",
            "last_accessed": "2026-01-15T06:00:00Z"
        }
    ]
}
```

---

### **POST /api/port-forwarding.php?action=create**

Create new port forwarding rule.

**Request:**
```json
{
    "device_name": "Garage Camera",
    "device_type": "ip_camera",
    "local_ip": "192.168.1.110",
    "local_port": 80,
    "protocol": "tcp"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Port forwarding rule created",
    "rule": {
        "id": 2,
        "public_url": "https://vpn.the-truth-publishing.com/port/def456",
        "public_port": 8110,
        "access_code": "def456",
        "status": "active"
    }
}
```

---

### **POST /api/port-forwarding.php?action=delete**

Remove port forwarding rule.

**Request:**
```json
{
    "rule_id": 2
}
```

**Response:**
```json
{
    "success": true,
    "message": "Port forwarding rule deleted"
}
```

---

### **POST /api/port-forwarding.php?action=toggle**

Enable/disable port forwarding rule.

**Request:**
```json
{
    "rule_id": 1,
    "status": "inactive"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Rule status updated",
    "status": "inactive"
}
```

---

### **POST /api/network-scanner.php**

Submit discovered devices from network scanner.

**Request:**
```json
{
    "devices": [
        {
            "id": "auto_192_168_1_105",
            "ip": "192.168.1.105",
            "mac": "D8:1D:2E:AA:BB:CC",
            "hostname": "geeni-camera",
            "vendor": "Geeni",
            "type": "ip_camera",
            "open_ports": [
                {"port": 80, "service": "http"},
                {"port": 554, "service": "rtsp"}
            ]
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Devices synced successfully",
    "imported": 5,
    "updated": 2,
    "skipped": 1
}
```

---

## 👤 USER PROFILE

### **GET /api/profile.php?action=get**

Get user profile information.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "profile": {
        "id": 123,
        "email": "user@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "tier": "standard",
        "subscription_status": "active",
        "created_at": "2025-12-01T10:00:00Z",
        "device_count": 3,
        "device_limit": 5,
        "total_bandwidth": 10737418240,
        "is_vip": false
    }
}
```

---

### **POST /api/profile.php?action=update**

Update user profile.

**Request:**
```json
{
    "first_name": "John",
    "last_name": "Smith"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Profile updated successfully"
}
```

---

### **POST /api/profile.php?action=change_email**

Change account email.

**Request:**
```json
{
    "new_email": "newemail@example.com",
    "password": "current_password"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Verification email sent to new address"
}
```

---

### **POST /api/profile.php?action=change_password**

Change account password.

**Request:**
```json
{
    "current_password": "OldPassword123!",
    "new_password": "NewPassword456!"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

**Errors:**
- `INCORRECT_PASSWORD` - Current password is wrong
- `WEAK_PASSWORD` - New password doesn't meet requirements

---

## ⚙️ SETTINGS

### **GET /api/settings.php?action=get**

Get user settings.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "settings": {
        "auto_connect": false,
        "kill_switch": false,
        "dns_provider": "cloudflare",
        "protocol": "wireguard",
        "usage_stats": true,
        "email_notifications": true,
        "mtu_size": 1420,
        "keepalive": 25,
        "split_tunnel": false
    }
}
```

---

### **POST /api/settings.php?action=save**

Save user settings.

**Request:**
```json
{
    "auto_connect": true,
    "kill_switch": true,
    "dns_provider": "google",
    "email_notifications": false
}
```

**Response:**
```json
{
    "success": true,
    "message": "Settings saved successfully"
}
```

---

### **POST /api/settings.php?action=reset**

Reset settings to defaults.

**Request:**
```json
{}
```

**Response:**
```json
{
    "success": true,
    "message": "Settings reset to defaults",
    "settings": {
        "auto_connect": false,
        "kill_switch": false,
        "dns_provider": "cloudflare",
        ...
    }
}
```

---

## 📊 STATISTICS

### **GET /api/statistics.php**

Get usage statistics.

**Query Parameters:**
- `period` (optional): 7, 30, or 90 (days, default: 7)

**Response:**
```json
{
    "success": true,
    "stats": {
        "total_download": 5242880000,
        "total_upload": 1073741824,
        "connection_time": 43200,
        "connection_count": 156,
        "chart_labels": ["Jan 8", "Jan 9", "Jan 10", ...],
        "download_data": [524288000, 629145600, ...],
        "upload_data": [104857600, 125829120, ...],
        "device_usage": [
            {
                "device_id": 1,
                "name": "iPhone 15",
                "icon": "📱",
                "total_bytes": 3145728000,
                "download_percent": 60
            }
        ],
        "server_usage": [
            {
                "server_id": 1,
                "name": "New York",
                "flag": "🇺🇸",
                "total_bytes": 4194304000,
                "usage_percent": 80
            }
        ]
    }
}
```

---

### **GET /api/statistics.php?action=realtime**

Get real-time bandwidth usage.

**Response:**
```json
{
    "success": true,
    "bandwidth": {
        "current_download_speed": 5242880,
        "current_upload_speed": 1048576,
        "active_devices": 2,
        "active_connections": 3
    }
}
```

---

## 🆘 SUPPORT

### **GET /api/support.php?action=list**

Get user's support tickets.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "tickets": [
        {
            "id": 1,
            "user_id": 123,
            "category": "technical",
            "subject": "Connection keeps dropping",
            "message": "My VPN connection drops every few minutes...",
            "status": "open",
            "priority": "normal",
            "created_at": "2026-01-14T10:00:00Z",
            "updated_at": "2026-01-15T05:00:00Z",
            "replies": [
                {
                    "id": 1,
                    "from": "support",
                    "message": "We're looking into this issue...",
                    "created_at": "2026-01-15T05:00:00Z"
                }
            ]
        }
    ]
}
```

---

### **POST /api/support.php?action=create**

Create new support ticket.

**Request:**
```json
{
    "category": "technical",
    "subject": "Cannot connect to Dallas server",
    "message": "When I try to connect to Dallas server, I get an error..."
}
```

**Response:**
```json
{
    "success": true,
    "message": "Support ticket created",
    "ticket": {
        "id": 2,
        "ticket_number": "TICKET-20260115-002",
        "status": "open",
        "created_at": "2026-01-15T06:30:00Z"
    }
}
```

---

### **POST /api/support.php?action=reply**

Reply to support ticket.

**Request:**
```json
{
    "ticket_id": 2,
    "message": "I've tried restarting my device but issue persists..."
}
```

**Response:**
```json
{
    "success": true,
    "message": "Reply added to ticket"
}
```

---

### **POST /api/support.php?action=close**

Close support ticket.

**Request:**
```json
{
    "ticket_id": 2
}
```

**Response:**
```json
{
    "success": true,
    "message": "Ticket closed successfully"
}
```

---

## 💳 PAYMENT & BILLING

### **GET /api/billing.php?action=subscription**

Get subscription details.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "subscription": {
        "plan": "standard",
        "plan_name": "Standard Plan",
        "price": 9.99,
        "billing_cycle": "monthly",
        "status": "active",
        "next_billing_date": "2026-02-15T00:00:00Z",
        "payment_method": {
            "type": "paypal",
            "email": "user@example.com"
        },
        "features": [
            "5 Devices",
            "Unlimited Bandwidth",
            "4 Server Locations",
            "Port Forwarding"
        ]
    }
}
```

---

### **GET /api/billing.php?action=invoices**

Get billing history.

**Request:** No body required

**Response:**
```json
{
    "success": true,
    "invoices": [
        {
            "id": 1,
            "invoice_number": "INV-20260115-001",
            "date": "2026-01-15T00:00:00Z",
            "description": "TrueVault VPN - Standard Plan",
            "amount": 9.99,
            "status": "paid",
            "payment_method": "PayPal",
            "download_url": "/api/invoice-download.php?id=1"
        }
    ]
}
```

---

### **POST /api/billing.php?action=upgrade**

Upgrade subscription plan.

**Request:**
```json
{
    "plan": "pro"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Plan upgraded successfully",
    "subscription": {
        "plan": "pro",
        "price": 14.99,
        "effective_date": "2026-01-15T06:35:00Z"
    }
}
```

---

### **POST /api/billing.php?action=cancel**

Cancel subscription.

**Request:**
```json
{
    "reason": "Too expensive"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Subscription cancelled",
    "end_date": "2026-02-15T00:00:00Z"
}
```

---

### **POST /api/paypal-webhook.php**

PayPal webhook handler (called by PayPal).

**Request:** (from PayPal)
```json
{
    "event_type": "PAYMENT.SALE.COMPLETED",
    "resource": {
        "id": "PAYID-...",
        "amount": {
            "total": "9.99",
            "currency": "USD"
        },
        ...
    }
}
```

**Response:**
```json
{
    "success": true
}
```

**Events Handled:**
- `PAYMENT.SALE.COMPLETED` - Payment successful
- `PAYMENT.SALE.DENIED` - Payment failed
- `BILLING.SUBSCRIPTION.CANCELLED` - Subscription cancelled
- `BILLING.SUBSCRIPTION.SUSPENDED` - Payment issues

---

## 👑 VIP SYSTEM

### **POST /api/vip.php?action=check**

Check if user is VIP.

**Request:**
```json
{
    "email": "seige235@yahoo.com"
}
```

**Response:**
```json
{
    "success": true,
    "is_vip": true,
    "vip_tier": "exclusive",
    "dedicated_server": {
        "id": 2,
        "name": "St. Louis VIP",
        "exclusive_access": true
    }
}
```

---

### **POST /api/vip.php?action=request**

Request VIP status.

**Request:**
```json
{
    "reason": "I need dedicated server for business use"
}
```

**Response:**
```json
{
    "success": true,
    "message": "VIP request submitted for review",
    "request_id": 5
}
```

---

## 🔧 ADMIN ENDPOINTS

**Note:** All admin endpoints require admin authentication.

### **GET /api/admin/users.php?action=list**

List all users (admin only).

**Query Parameters:**
- `page` (optional): Page number
- `limit` (optional): Results per page
- `search` (optional): Search term

**Response:**
```json
{
    "success": true,
    "users": [
        {
            "id": 123,
            "email": "user@example.com",
            "first_name": "John",
            "last_name": "Doe",
            "tier": "standard",
            "status": "active",
            "device_count": 3,
            "created_at": "2025-12-01T10:00:00Z",
            "last_login": "2026-01-15T06:00:00Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "total_pages": 10,
        "total_users": 247
    }
}
```

---

### **POST /api/admin/users.php?action=suspend**

Suspend user account.

**Request:**
```json
{
    "user_id": 123,
    "reason": "Payment overdue"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User suspended"
}
```

---

### **POST /api/admin/servers.php?action=restart**

Restart WireGuard server.

**Request:**
```json
{
    "server_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "message": "Server restarted successfully"
}
```

---

### **GET /api/admin/stats.php**

Get system-wide statistics.

**Response:**
```json
{
    "success": true,
    "stats": {
        "total_users": 247,
        "active_users": 198,
        "total_devices": 856,
        "active_connections": 342,
        "total_bandwidth_today": 1099511627776,
        "revenue_this_month": 2470.53,
        "server_status": {
            "online": 4,
            "offline": 0
        }
    }
}
```

---

## 📤 RESPONSE FORMATS

### **Standard Success Response**

```json
{
    "success": true,
    "data": {...},
    "message": "Operation completed successfully"
}
```

### **Standard Error Response**

```json
{
    "success": false,
    "error": "Human-readable error message",
    "code": "ERROR_CODE",
    "details": {
        "field": "email",
        "issue": "already_exists"
    }
}
```

### **List Response with Pagination**

```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "current_page": 1,
        "total_pages": 10,
        "total_items": 247,
        "items_per_page": 25
    }
}
```

---

## ❌ ERROR CODES

### **Authentication Errors (1000-1099)**

| Code | Message | HTTP Status |
|------|---------|-------------|
| `AUTH_1001` | Invalid credentials | 401 |
| `AUTH_1002` | Token expired | 401 |
| `AUTH_1003` | Token invalid | 401 |
| `AUTH_1004` | Account suspended | 403 |
| `AUTH_1005` | Email not verified | 403 |
| `AUTH_1006` | Email already exists | 409 |
| `AUTH_1007` | Weak password | 400 |

### **Device Errors (2000-2099)**

| Code | Message | HTTP Status |
|------|---------|-------------|
| `DEV_2001` | Device limit reached | 403 |
| `DEV_2002` | Device not found | 404 |
| `DEV_2003` | Invalid device type | 400 |
| `DEV_2004` | Name required | 400 |
| `DEV_2005` | Server not available | 400 |

### **Server Errors (3000-3099)**

| Code | Message | HTTP Status |
|------|---------|-------------|
| `SRV_3001` | Server not found | 404 |
| `SRV_3002` | Server offline | 503 |
| `SRV_3003` | Server full | 507 |
| `SRV_3004` | VIP server (restricted) | 403 |

### **Port Forwarding Errors (4000-4099)**

| Code | Message | HTTP Status |
|------|---------|-------------|
| `PF_4001` | Port forwarding limit reached | 403 |
| `PF_4002` | Invalid IP address | 400 |
| `PF_4003` | Port already in use | 409 |
| `PF_4004` | Rule not found | 404 |

### **Billing Errors (5000-5099)**

| Code | Message | HTTP Status |
|------|---------|-------------|
| `BILL_5001` | Payment failed | 402 |
| `BILL_5002` | Subscription not found | 404 |
| `BILL_5003` | Invalid plan | 400 |
| `BILL_5004` | Already subscribed | 409 |

### **System Errors (9000-9099)**

| Code | Message | HTTP Status |
|------|---------|-------------|
| `SYS_9001` | Internal server error | 500 |
| `SYS_9002` | Database error | 500 |
| `SYS_9003` | Rate limit exceeded | 429 |
| `SYS_9004` | Maintenance mode | 503 |

---

**END OF SECTION 13: API ENDPOINTS (Part 1/2)**

**Status:** In Progress (60% Complete)  
**Next:** Part 2 will include API implementation examples, authentication middleware, error handling  
**Lines:** ~1,400 lines  
**Created:** January 15, 2026 - 6:30 AM CST
