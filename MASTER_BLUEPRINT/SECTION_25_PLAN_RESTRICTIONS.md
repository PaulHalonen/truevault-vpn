# SECTION 25: PLAN RESTRICTIONS & SERVER ACCESS RULES

**Created:** January 22, 2026  
**Status:** CRITICAL - Business Rules  
**Priority:** HIGHEST - Must be enforced everywhere  

---

## 📋 PLAN OVERVIEW

### **Basic Plan - $9.97/month**
| Feature | Limit |
|---------|-------|
| VPN Devices (laptop, phone, tablet) | 3 |
| Home Network Devices (scanned) | 3 |
| Cameras Allowed | 1 only |
| Port Forwarding Devices | 2 max |
| Port Forwarding Server | NY Contabo ONLY |
| Parental Controls | ✅ Yes |

### **Family Plan - $14.97/month**
| Feature | Limit |
|---------|-------|
| VPN Devices | 5 |
| Home Network Devices (scanned) | 5 |
| Cameras Allowed | 2 only |
| Port Forwarding Devices | 5 max |
| Port Forwarding Server | NY Contabo ONLY |
| Parental Controls | ✅ Yes |

### **Dedicated Server Plan - $29.97/month**
| Feature | Limit |
|---------|-------|
| VPN Devices | 99 |
| Home Network Devices | 99 |
| Cameras Allowed | Unlimited |
| Port Forwarding Devices | Unlimited |
| Port Forwarding Server | Own Dedicated + NY |
| Parental Controls | ✅ Yes |
| Dedicated Server | ✅ Yes (assigned to email) |

---

## 🖥️ SERVER ACCESS RULES

### **Server 1: New York (66.94.103.91) - Contabo**
- **Access:** ALL plans (Basic, Family, Dedicated)
- **Bandwidth:** UNLIMITED
- **Port Forwarding:** ✅ ALLOWED
- **Xbox/Gaming:** ✅ ALLOWED
- **uTorrent/P2P:** ✅ ALLOWED
- **Streaming:** ✅ ALLOWED

### **Server 2: St. Louis (144.126.133.253) - Contabo**
- **Access:** DEDICATED to seige235@yahoo.com ONLY
- **Visibility:** HIDDEN from ALL other users (including VIPs)
- **Bandwidth:** UNLIMITED
- **Port Forwarding:** ✅ ALLOWED
- **Xbox/Gaming:** ✅ ALLOWED
- **uTorrent/P2P:** ✅ ALLOWED

### **Server 3: Dallas (66.241.124.4) - Fly.io**
- **Access:** ALL plans
- **Bandwidth:** LIMITED (fair use)
- **Port Forwarding:** ❌ NOT ALLOWED
- **Xbox/Gaming:** ❌ BLOCKED
- **uTorrent/P2P:** ❌ BLOCKED
- **Streaming:** ✅ Good for Netflix (not VPN-flagged)

### **Server 4: Toronto (66.241.125.247) - Fly.io**
- **Access:** ALL plans
- **Bandwidth:** LIMITED (fair use)
- **Port Forwarding:** ❌ NOT ALLOWED
- **Xbox/Gaming:** ❌ BLOCKED
- **uTorrent/P2P:** ❌ BLOCKED
- **Streaming:** ✅ Good for Canadian content (CBC, TSN)

---

## 🔒 DEDICATED SERVER RULES

**CRITICAL: Dedicated servers are NOT "VIP servers"**

1. A dedicated server is assigned to ONE specific email address
2. ONLY that email can see or access the dedicated server
3. Other VIPs/admins CANNOT see dedicated servers (except in admin panel)
4. Dedicated server owners can ALSO use all shared servers
5. They can mix/match between their dedicated + shared servers

**Example: seige235@yahoo.com**
- Can see: NY, Dallas, Toronto (shared) + St. Louis (dedicated)
- Other users see: NY, Dallas, Toronto only
- St. Louis is INVISIBLE to everyone else

---

## 📷 CAMERA RESTRICTIONS

| Plan | Max Cameras | Notes |
|------|-------------|-------|
| Basic | 1 | Part of 3 home network device limit |
| Family | 2 | Part of 5 home network device limit |
| Dedicated | Unlimited | No restrictions |

**Camera Port Forwarding:**
- ONLY allowed on NY Contabo server
- NEVER allowed on Fly.io servers (Dallas/Toronto)
- Dedicated plan owners can use their dedicated server too

---

## 🎮 HIGH-BANDWIDTH SERVICE RESTRICTIONS

**Xbox, PlayStation, Nintendo, uTorrent, BitTorrent:**

| Server | Allowed? |
|--------|----------|
| NY Contabo | ✅ YES |
| St. Louis (Dedicated) | ✅ YES |
| Dallas Fly.io | ❌ BLOCKED |
| Toronto Fly.io | ❌ BLOCKED |

**Why?** Fly.io has limited bandwidth allocation. Gaming and P2P consume too much.

---

## 🔄 HOME NETWORK DEVICE RULES

1. Network scanner discovers ALL devices on home network
2. User can SELECT devices up to their plan limit
3. User can SWITCH selected devices anytime
4. Only SELECTED devices can use port forwarding
5. Port forwarding ONLY works on NY Contabo (or dedicated server)

**Selection Process:**
```
Scanner finds: 15 devices
Basic plan limit: 3 devices
User selects: Xbox, Camera, Printer
User can swap: Remove Printer, Add Smart TV
```

---

**END OF SECTION 25**
