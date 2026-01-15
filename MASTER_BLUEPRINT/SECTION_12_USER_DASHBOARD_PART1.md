# SECTION 12: USER DASHBOARD

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Priority:** CRITICAL - Main User Interface  
**Complexity:** HIGH - Central Hub for All Features  

---

## 📋 TABLE OF CONTENTS

1. [Dashboard Overview](#overview)
2. [Dashboard Layout](#layout)
3. [Device Management](#devices)
4. [Connection Status](#status)
5. [Server Selection](#servers)
6. [Port Forwarding Interface](#port-forwarding)
7. [Settings & Preferences](#settings)
8. [Usage Statistics](#statistics)
9. [Support System](#support)
10. [Account Management](#account)
11. [Mobile Responsive Design](#mobile)
12. [Real-time Updates](#realtime)

---

## 🎨 DASHBOARD OVERVIEW

### **Purpose**

The user dashboard is the **central control center** where users:
- ✅ Add and manage devices
- ✅ View connection status
- ✅ Switch servers
- ✅ Configure port forwarding
- ✅ View usage statistics
- ✅ Get support
- ✅ Manage account settings

### **Design Philosophy**

**2-Click Maximum Rule:**
- Every action completes in 2 clicks or less
- No complex menus or nested navigation
- Instant visual feedback
- Clear, simple language (no jargon)

**Visual Hierarchy:**
```
Top Priority: Connection Status (always visible)
Second: Device List (quick access)
Third: Server Selection (easy switching)
Fourth: Advanced Features (expandable)
```

### **Color Scheme**

**Database-Driven Theme:**
```php
// All colors from settings table
$theme = [
    'primary' => getSetting('theme_primary_color', '#00d9ff'),
    'secondary' => getSetting('theme_secondary_color', '#00ff88'),
    'background' => getSetting('theme_bg_color', '#0f0f1a'),
    'card_bg' => getSetting('theme_card_bg', 'rgba(255,255,255,0.04)'),
    'text_primary' => getSetting('theme_text_primary', '#ffffff'),
    'text_secondary' => getSetting('theme_text_secondary', '#888888'),
    'success' => getSetting('theme_success', '#00ff88'),
    'warning' => getSetting('theme_warning', '#ffaa00'),
    'danger' => getSetting('theme_danger', '#ff6464'),
];
```

---

## 🏗️ DASHBOARD LAYOUT

### **Main Structure**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueVault VPN - Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="logo">
            <img src="logo.png" alt="TrueVault">
            <span>TrueVault VPN</span>
        </div>
        
        <div class="user-menu">
            <div class="user-info">
                <span id="user-email">loading...</span>
                <span class="user-tier" id="user-tier">loading...</span>
            </div>
            <button class="btn-logout" onclick="logout()">Logout</button>
        </div>
    </nav>
    
    <!-- Main Content Area -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <nav class="main-nav">
                <a href="#dashboard" class="nav-item active">
                    <i class="icon-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#devices" class="nav-item">
                    <i class="icon-devices"></i>
                    <span>My Devices</span>
                    <span class="badge" id="device-count">0</span>
                </a>
                <a href="#servers" class="nav-item">
                    <i class="icon-server"></i>
                    <span>Servers</span>
                </a>
                <a href="#port-forwarding" class="nav-item">
                    <i class="icon-port"></i>
                    <span>Port Forwarding</span>
                </a>
                <a href="#statistics" class="nav-item">
                    <i class="icon-chart"></i>
                    <span>Usage Stats</span>
                </a>
                <a href="#settings" class="nav-item">
                    <i class="icon-settings"></i>
                    <span>Settings</span>
                </a>
                <a href="#support" class="nav-item">
                    <i class="icon-support"></i>
                    <span>Support</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Dynamic content loads here -->
            <div id="content-area"></div>
        </main>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
```

### **CSS Structure**

```css
/* File: css/dashboard.css */

/* ============= BASE STYLES ============= */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-color);
    color: var(--text-primary);
}

:root {
    /* Colors loaded from database */
    --primary-color: #00d9ff;
    --secondary-color: #00ff88;
    --bg-color: #0f0f1a;
    --card-bg: rgba(255,255,255,0.04);
    --text-primary: #ffffff;
    --text-secondary: #888888;
    --success: #00ff88;
    --warning: #ffaa00;
    --danger: #ff6464;
}

/* ============= TOP NAVIGATION ============= */
.top-nav {
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.2rem;
    font-weight: 700;
}

.logo img {
    height: 35px;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.user-tier {
    font-size: 0.75rem;
    padding: 3px 10px;
    border-radius: 12px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    color: #000;
    font-weight: 600;
}

/* ============= DASHBOARD LAYOUT ============= */
.dashboard-container {
    display: flex;
    height: calc(100vh - 65px);
}

.sidebar {
    width: 250px;
    background: rgba(255,255,255,0.02);
    border-right: 1px solid rgba(255,255,255,0.08);
    padding: 20px 0;
}

.main-content {
    flex: 1;
    overflow-y: auto;
    padding: 30px;
}

/* ============= NAVIGATION ============= */
.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 25px;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.nav-item:hover {
    background: rgba(255,255,255,0.04);
    color: var(--text-primary);
}

.nav-item.active {
    background: rgba(0,217,255,0.1);
    color: var(--primary-color);
    border-left-color: var(--primary-color);
}

.nav-item .badge {
    margin-left: auto;
    background: var(--primary-color);
    color: #000;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* ============= BUTTONS ============= */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    color: #000;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,217,255,0.3);
}

.btn-secondary {
    background: rgba(255,255,255,0.08);
    color: var(--text-primary);
    border: 1px solid rgba(255,255,255,0.15);
}

.btn-danger {
    background: rgba(255,100,100,0.15);
    color: var(--danger);
    border: 1px solid var(--danger);
}

.btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    transform: none;
}

/* ============= CARDS ============= */
.card {
    background: var(--card-bg);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.card-title {
    font-size: 1.2rem;
    font-weight: 600;
}

/* ============= STATUS INDICATORS ============= */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-connected {
    background: rgba(0,255,136,0.15);
    color: var(--success);
    border: 1px solid var(--success);
}

.status-disconnected {
    background: rgba(136,136,136,0.15);
    color: var(--text-secondary);
    border: 1px solid var(--text-secondary);
}

.status-error {
    background: rgba(255,100,100,0.15);
    color: var(--danger);
    border: 1px solid var(--danger);
}

/* ============= RESPONSIVE ============= */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        border-right: none;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    
    .dashboard-container {
        flex-direction: column;
    }
    
    .main-content {
        padding: 15px;
    }
}
```

---

## 📱 DEVICE MANAGEMENT

### **Device List View**

```html
<!-- Main Dashboard Content -->
<div class="dashboard-home">
    <!-- Connection Status Banner -->
    <div class="status-banner" id="status-banner">
        <div class="status-info">
            <span class="status-icon">🔒</span>
            <div>
                <h2 id="status-text">Protected</h2>
                <p id="status-detail">Connected to New York</p>
            </div>
        </div>
        <button class="btn-disconnect" id="quick-action">Disconnect</button>
    </div>
    
    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📱</div>
            <div class="stat-info">
                <span class="stat-value" id="device-count">0</span>
                <span class="stat-label">Devices</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⬇️</div>
            <div class="stat-info">
                <span class="stat-value" id="data-download">0 GB</span>
                <span class="stat-label">Downloaded</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⬆️</div>
            <div class="stat-info">
                <span class="stat-value" id="data-upload">0 GB</span>
                <span class="stat-label">Uploaded</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🌐</div>
            <div class="stat-info">
                <span class="stat-value" id="server-location">--</span>
                <span class="stat-label">Current Server</span>
            </div>
        </div>
    </div>
    
    <!-- Devices Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Devices</h3>
            <button class="btn btn-primary" onclick="showAddDevice()">
                ➕ Add Device
            </button>
        </div>
        
        <div id="device-list" class="device-grid">
            <!-- Devices load here dynamically -->
        </div>
    </div>
</div>
```

### **Device Card Component**

```html
<!-- Single Device Card -->
<div class="device-card" data-device-id="{device_id}">
    <div class="device-header">
        <div class="device-icon">{icon}</div>
        <div class="device-info">
            <h4 class="device-name">{device_name}</h4>
            <p class="device-type">{device_type}</p>
        </div>
        <div class="device-status">
            <span class="status-badge status-{status}">{status_text}</span>
        </div>
    </div>
    
    <div class="device-details">
        <div class="detail-row">
            <span class="detail-label">VPN IP:</span>
            <span class="detail-value">{vpn_ip}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Server:</span>
            <span class="detail-value">{server_name}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Added:</span>
            <span class="detail-value">{added_date}</span>
        </div>
    </div>
    
    <div class="device-actions">
        <button class="btn btn-secondary btn-sm" onclick="downloadConfig({device_id})">
            📥 Config
        </button>
        <button class="btn btn-secondary btn-sm" onclick="showQRCode({device_id})">
            📱 QR Code
        </button>
        <button class="btn btn-secondary btn-sm" onclick="switchServer({device_id})">
            🔄 Switch Server
        </button>
        <button class="btn btn-danger btn-sm" onclick="removeDevice({device_id})">
            🗑️ Remove
        </button>
    </div>
</div>
```

### **Add Device Modal**

```html
<!-- Add Device Modal -->
<div class="modal" id="add-device-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Device</h2>
            <button class="btn-close" onclick="closeModal('add-device-modal')">✕</button>
        </div>
        
        <div class="modal-body">
            <div class="step-indicator">
                <div class="step active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-label">Device Info</span>
                </div>
                <div class="step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-label">Setup</span>
                </div>
            </div>
            
            <!-- Step 1: Device Information -->
            <div class="step-content" data-step="1">
                <div class="form-group">
                    <label>Device Name</label>
                    <input type="text" id="device-name" placeholder="e.g., iPhone 15, Work Laptop">
                </div>
                
                <div class="form-group">
                    <label>Device Type</label>
                    <select id="device-type">
                        <option value="phone">📱 Phone</option>
                        <option value="tablet">📱 Tablet</option>
                        <option value="laptop">💻 Laptop</option>
                        <option value="desktop">🖥️ Desktop</option>
                        <option value="router">🔀 Router</option>
                        <option value="other">❓ Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Server</label>
                    <select id="device-server">
                        <option value="1">🇺🇸 New York</option>
                        <option value="3">🇺🇸 Dallas (Streaming)</option>
                        <option value="4">🇨🇦 Toronto</option>
                    </select>
                </div>
                
                <button class="btn btn-primary btn-block" onclick="createDevice()">
                    Create Device
                </button>
            </div>
            
            <!-- Step 2: Setup Instructions -->
            <div class="step-content hidden" data-step="2">
                <div class="setup-method-selector">
                    <button class="setup-method active" data-method="qr">
                        📱 Scan QR Code
                    </button>
                    <button class="setup-method" data-method="download">
                        📥 Download Config
                    </button>
                    <button class="setup-method" data-method="manual">
                        ⚙️ Manual Setup
                    </button>
                </div>
                
                <!-- QR Code Method -->
                <div class="setup-instructions" data-method="qr">
                    <div class="qr-code-display">
                        <img id="setup-qr-code" src="" alt="QR Code">
                    </div>
                    <ol class="instruction-list">
                        <li>Download WireGuard app on your device</li>
                        <li>Open the app and tap "Add Tunnel"</li>
                        <li>Select "Scan from QR Code"</li>
                        <li>Scan the code above</li>
                        <li>Toggle the connection ON</li>
                    </ol>
                </div>
                
                <!-- Download Method -->
                <div class="setup-instructions hidden" data-method="download">
                    <button class="btn btn-primary btn-block" id="download-config-btn">
                        📥 Download Configuration File
                    </button>
                    <ol class="instruction-list">
                        <li>Download the configuration file above</li>
                        <li>Open WireGuard app on your device</li>
                        <li>Import the configuration file</li>
                        <li>Toggle the connection ON</li>
                    </ol>
                </div>
                
                <!-- Manual Method -->
                <div class="setup-instructions hidden" data-method="manual">
                    <div class="config-display">
                        <textarea id="manual-config" readonly></textarea>
                        <button class="btn btn-secondary" onclick="copyConfig()">
                            📋 Copy Configuration
                        </button>
                    </div>
                    <p>Paste this configuration into your WireGuard client.</p>
                </div>
                
                <button class="btn btn-secondary btn-block" onclick="finishSetup()">
                    ✅ Finish Setup
                </button>
            </div>
        </div>
    </div>
</div>
```

### **Device Management JavaScript**

```javascript
// File: js/device-management.js

class DeviceManager {
    constructor() {
        this.devices = [];
        this.currentDevice = null;
    }
    
    // Load all user devices
    async loadDevices() {
        try {
            const response = await fetch('/api/devices.php?action=list');
            const data = await response.json();
            
            if (data.success) {
                this.devices = data.devices;
                this.renderDevices();
            }
        } catch (error) {
            console.error('Failed to load devices:', error);
            showNotification('Failed to load devices', 'error');
        }
    }
    
    // Render device list
    renderDevices() {
        const container = document.getElementById('device-list');
        
        if (this.devices.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📱</div>
                    <h3>No Devices Yet</h3>
                    <p>Add your first device to get started</p>
                    <button class="btn btn-primary" onclick="deviceManager.showAddDevice()">
                        ➕ Add Device
                    </button>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.devices.map(device => this.renderDeviceCard(device)).join('');
        
        // Update device count
        document.getElementById('device-count').textContent = this.devices.length;
    }
    
    // Render single device card
    renderDeviceCard(device) {
        const statusClass = device.is_connected ? 'connected' : 'disconnected';
        const statusText = device.is_connected ? '✓ Connected' : 'Disconnected';
        
        return `
            <div class="device-card" data-device-id="${device.id}">
                <div class="device-header">
                    <div class="device-icon">${this.getDeviceIcon(device.device_type)}</div>
                    <div class="device-info">
                        <h4 class="device-name">${device.name}</h4>
                        <p class="device-type">${device.device_type}</p>
                    </div>
                    <span class="status-badge status-${statusClass}">${statusText}</span>
                </div>
                
                <div class="device-details">
                    <div class="detail-row">
                        <span class="detail-label">VPN IP:</span>
                        <span class="detail-value">${device.vpn_ip}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Server:</span>
                        <span class="detail-value">${device.server_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Added:</span>
                        <span class="detail-value">${this.formatDate(device.created_at)}</span>
                    </div>
                </div>
                
                <div class="device-actions">
                    <button class="btn btn-secondary btn-sm" onclick="deviceManager.downloadConfig(${device.id})">
                        📥 Config
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="deviceManager.showQRCode(${device.id})">
                        📱 QR
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="deviceManager.switchServer(${device.id})">
                        🔄 Switch
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deviceManager.removeDevice(${device.id})">
                        🗑️
                    </button>
                </div>
            </div>
        `;
    }
    
    // Show add device modal
    showAddDevice() {
        document.getElementById('add-device-modal').classList.add('active');
    }
    
    // Create new device
    async createDevice() {
        const name = document.getElementById('device-name').value;
        const type = document.getElementById('device-type').value;
        const serverId = document.getElementById('device-server').value;
        
        if (!name) {
            showNotification('Please enter a device name', 'error');
            return;
        }
        
        // Show loading
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = '⏳ Creating...';
        
        try {
            const response = await fetch('/api/devices.php?action=create', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    name: name,
                    device_type: type,
                    server_id: serverId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentDevice = data.device;
                this.showSetupInstructions();
                await this.loadDevices();
            } else {
                showNotification(data.error || 'Failed to create device', 'error');
            }
        } catch (error) {
            console.error('Create device error:', error);
            showNotification('Failed to create device', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Create Device';
        }
    }
    
    // Show setup instructions
    showSetupInstructions() {
        // Hide step 1, show step 2
        document.querySelector('[data-step="1"]').classList.remove('active');
        document.querySelector('[data-step="2"]').classList.add('active');
        document.querySelector('.step-content[data-step="1"]').classList.add('hidden');
        document.querySelector('.step-content[data-step="2"]').classList.remove('hidden');
        
        // Load QR code
        this.loadQRCode(this.currentDevice.id);
        
        // Set download button
        document.getElementById('download-config-btn').onclick = () => {
            this.downloadConfig(this.currentDevice.id);
        };
        
        // Load manual config
        this.loadManualConfig(this.currentDevice.id);
    }
    
    // Load QR code
    async loadQRCode(deviceId) {
        try {
            const response = await fetch(`/api/qr-code.php?device_id=${deviceId}`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('setup-qr-code').src = data.qr_code;
            }
        } catch (error) {
            console.error('QR code error:', error);
        }
    }
    
    // Load manual config
    async loadManualConfig(deviceId) {
        try {
            const response = await fetch(`/api/config-text.php?device_id=${deviceId}`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('manual-config').value = data.config;
            }
        } catch (error) {
            console.error('Config error:', error);
        }
    }
    
    // Download config file
    downloadConfig(deviceId) {
        window.location.href = `/api/download-config.php?device_id=${deviceId}`;
    }
    
    // Show QR code modal
    async showQRCode(deviceId) {
        // Implementation
    }
    
    // Switch server
    async switchServer(deviceId) {
        // Show server selection modal
        // Implementation in next section
    }
    
    // Remove device
    async removeDevice(deviceId) {
        if (!confirm('Are you sure you want to remove this device?')) {
            return;
        }
        
        try {
            const response = await fetch(`/api/devices.php?action=delete`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({device_id: deviceId})
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Device removed successfully', 'success');
                await this.loadDevices();
            } else {
                showNotification(data.error || 'Failed to remove device', 'error');
            }
        } catch (error) {
            console.error('Remove device error:', error);
            showNotification('Failed to remove device', 'error');
        }
    }
    
    // Helper: Get device icon
    getDeviceIcon(type) {
        const icons = {
            'phone': '📱',
            'tablet': '📱',
            'laptop': '💻',
            'desktop': '🖥️',
            'router': '🔀',
            'other': '❓'
        };
        return icons[type] || '❓';
    }
    
    // Helper: Format date
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }
}

// Initialize
const deviceManager = new DeviceManager();
```

---

## 🌐 SERVER SELECTION

### **Server Selection Modal**

```html
<!-- Server Selection Modal -->
<div class="modal" id="server-selection-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Choose Server</h2>
            <button class="btn-close" onclick="closeModal('server-selection-modal')">✕</button>
        </div>
        
        <div class="modal-body">
            <div class="server-list" id="server-list">
                <!-- Servers load dynamically -->
            </div>
        </div>
    </div>
</div>
```

### **Server Card Component**

```html
<!-- Single Server Card -->
<div class="server-card {recommended}" data-server-id="{server_id}" onclick="selectServer({server_id})">
    <div class="server-header">
        <div class="server-flag">{flag}</div>
        <div class="server-info">
            <h4 class="server-name">{server_name}</h4>
            <p class="server-location">{location}</p>
        </div>
        {recommended_badge}
    </div>
    
    <div class="server-stats">
        <div class="stat">
            <span class="stat-icon">⚡</span>
            <span class="stat-value">{latency}ms</span>
        </div>
        <div class="stat">
            <span class="stat-icon">👥</span>
            <span class="stat-value">{load}%</span>
        </div>
        <div class="stat">
            <span class="stat-icon">📊</span>
            <span class="stat-value">{speed}Gbps</span>
        </div>
    </div>
    
    <div class="server-features">
        {features_list}
    </div>
</div>
```

### **Server Management JavaScript**

```javascript
// File: js/server-manager.js

class ServerManager {
    constructor() {
        this.servers = [];
        this.selectedDevice = null;
    }
    
    // Load available servers
    async loadServers() {
        try {
            const response = await fetch('/api/servers.php?action=list');
            const data = await response.json();
            
            if (data.success) {
                this.servers = data.servers;
                return this.servers;
            }
        } catch (error) {
            console.error('Failed to load servers:', error);
        }
        return [];
    }
    
    // Show server selection for device
    async showServerSelection(deviceId) {
        this.selectedDevice = deviceId;
        await this.loadServers();
        this.renderServerList();
        document.getElementById('server-selection-modal').classList.add('active');
    }
    
    // Render server list
    renderServerList() {
        const container = document.getElementById('server-list');
        container.innerHTML = this.servers.map(server => this.renderServerCard(server)).join('');
    }
    
    // Render single server card
    renderServerCard(server) {
        const features = server.features || [];
        const featuresHTML = features.map(f => `<span class="feature-badge">${f}</span>`).join('');
        
        const recommended = server.recommended ? 'recommended' : '';
        const recommendedBadge = server.recommended ? '<span class="badge-recommended">⭐ Recommended</span>' : '';
        
        return `
            <div class="server-card ${recommended}" data-server-id="${server.id}" onclick="serverManager.selectServer(${server.id})">
                <div class="server-header">
                    <div class="server-flag">${server.flag}</div>
                    <div class="server-info">
                        <h4 class="server-name">${server.name}</h4>
                        <p class="server-location">${server.location}</p>
                    </div>
                    ${recommendedBadge}
                </div>
                
                <div class="server-stats">
                    <div class="stat">
                        <span class="stat-icon">⚡</span>
                        <span class="stat-value">${server.latency || '--'}ms</span>
                    </div>
                    <div class="stat">
                        <span class="stat-icon">👥</span>
                        <span class="stat-value">${server.load || '--'}%</span>
                    </div>
                    <div class="stat">
                        <span class="stat-icon">📊</span>
                        <span class="stat-value">${server.speed || '--'}Gbps</span>
                    </div>
                </div>
                
                <div class="server-features">
                    ${featuresHTML}
                </div>
            </div>
        `;
    }
    
    // Select server and switch device
    async selectServer(serverId) {
        if (!this.selectedDevice) {
            showNotification('No device selected', 'error');
            return;
        }
        
        try {
            const response = await fetch('/api/devices.php?action=switch_server', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    device_id: this.selectedDevice,
                    server_id: serverId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Server switched successfully!', 'success');
                closeModal('server-selection-modal');
                await deviceManager.loadDevices();
            } else {
                showNotification(data.error || 'Failed to switch server', 'error');
            }
        } catch (error) {
            console.error('Switch server error:', error);
            showNotification('Failed to switch server', 'error');
        }
    }
}

// Initialize
const serverManager = new ServerManager();
```

---

**END OF SECTION 12: USER DASHBOARD (Part 1/2)**

**Status:** In Progress (50% Complete)  
**Next:** Part 2 will include Settings, Statistics, Support, Account Management  
**Lines:** ~1,000 lines  
**Created:** January 15, 2026 - 6:05 AM CST
