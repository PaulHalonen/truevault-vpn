# SECTION 23: ENTERPRISE BUSINESS HUB
## Reference Document
**Status:** 📋 PLANNING  
**Created:** January 17, 2026

---

## 📍 LOCATION

The Enterprise Business Hub documentation is located in the root folder for easier access:

- **Blueprint:** `/ENTERPRISE_BLUEPRINT.md`
- **Checklist:** `/ENTERPRISE_CHECKLIST.md`

---

## 🎯 QUICK OVERVIEW

The Enterprise Business Hub transforms TrueVault from a consumer VPN into a complete business platform competing with:

| Competitor | Their Price | Our Price | Difference |
|------------|-------------|-----------|------------|
| GoodAccess | $74/mo (5 users) | $79.97/mo | We include DataForge |
| NordLayer | $95/mo (5 users) | $79.97/mo | 16% cheaper + more features |
| Perimeter 81 | $80/mo (10 min) | $79.97/mo | No minimums |
| FileMaker Pro | $588/year | $0 (included) | FREE with VPN |

---

## 🏗️ ARCHITECTURE SUMMARY

```
Desktop App (Electron)
├── Embedded Web Server (localhost:8080)
├── WireGuard VPN Client
├── SQLite Databases (local storage)
└── React Dashboard UI

User's Computer
├── ~/Documents/TrueVaultBusiness/
│   ├── config.json (branding, VPN config)
│   ├── company.db (employees, roles)
│   ├── hr.db (salary, time-off, reviews)
│   ├── dataforge.db (custom tables)
│   ├── audit.db (activity logs)
│   ├── sync.db (multi-device sync)
│   └── backups/
```

---

## 👥 ROLE HIERARCHY

```
OWNER (100) ──────────────────────────────────────────┐
│                                                      │
├── ADMIN (80) - IT/System administration              │
│                                                      │
├── HR_ADMIN (70) - Full HR access including salary    │
│   └── HR_STAFF (50) - Limited HR access              │
│                                                      │
├── MANAGER (40) - Team lead (sees direct reports)     │
│                                                      │
├── EMPLOYEE (20) - Self-service only                  │
│                                                      │
└── READONLY (10) - View-only access                   │
```

---

## 🖥️ SEPARATE PORTALS

| Portal | Access | Purpose |
|--------|--------|---------|
| /owner | Owner only | Billing, company settings, ownership |
| /admin | Owner, Admin | User management, SSO, VPN config, audit |
| /hr | Owner, HR_Admin, HR_Staff | Employee management, time-off, reviews |
| /manager | Owner, HR_Admin, Manager | Team management, approvals |
| /my | All users | Self-service profile, time-off, devices |
| /dataforge | Based on table permissions | Database builder |
| /vpn | All (config: Admin only) | VPN connection management |

---

## 📋 BUILD PHASES

| Phase | Description | Duration |
|-------|-------------|----------|
| 11 | Desktop App Foundation | 1 week |
| 12 | Authentication & Roles | 1 week |
| 13 | Owner Dashboard | 1 week |
| 14 | Admin Panel | 1 week |
| 15 | HR Module | 2 weeks |
| 16 | Manager Portal | 0.5 week |
| 17 | Employee Portal | 1 week |
| 18 | DataForge Builder | 2 weeks |
| 19 | Sync & Backup | 1 week |
| 20 | Installers & Distribution | 1 week |
| 21 | Demo & Testing | 1 week |
| 22 | Documentation & Launch | 1 week |

**Total: ~12 weeks (3 months)**

---

## 🔗 FULL DOCUMENTATION

For complete technical specifications, database schemas, API endpoints, and detailed checklists:

1. **ENTERPRISE_BLUEPRINT.md** - Technical specifications
2. **ENTERPRISE_CHECKLIST.md** - Build checklist with tasks

---

**Note:** This section file is a reference pointer. All detailed documentation is in the root-level ENTERPRISE_*.md files.
