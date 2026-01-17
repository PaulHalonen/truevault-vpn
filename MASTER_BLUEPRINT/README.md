

---

## 🏢 ENTERPRISE BUSINESS HUB (NEW!)

### Section 23: Enterprise Business Hub (Added January 17, 2026)

The Enterprise Business Hub extends TrueVault into a complete business platform:

**See:** `ENTERPRISE_BLUEPRINT.md` and `ENTERPRISE_CHECKLIST.md`

**Key Components:**
- **Localhost Desktop App** - Electron-based, runs on client's computer
- **7-Role Hierarchy** - Owner, Admin, HR_Admin, HR_Staff, Manager, Employee, Readonly
- **6 Separate Portals** - Each role sees appropriate UI
- **DataForge Builder** - FileMaker Pro alternative with 50+ templates
- **HR Module** - Full employee management, time-off, reviews
- **Multi-Device Sync** - Peer-to-peer over VPN mesh

**Pricing:**
- Corporate Plan: $79.97/month (5 seats included)
- Additional seats: $8/month each
- Beats GoodAccess ($74), NordLayer ($95), Perimeter 81 ($80+)

**Architecture:**
- Desktop app with embedded localhost:8080 server
- SQLite databases stored in ~/Documents/TrueVaultBusiness/
- WireGuard VPN integration
- No cloud dependency - data stays on client's hardware

**Documentation:**
- `ENTERPRISE_BLUEPRINT.md` - Full technical specifications (~3,000 lines)
- `ENTERPRISE_CHECKLIST.md` - Detailed build checklist (Phases 11-22)

---

