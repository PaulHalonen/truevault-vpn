# SECTION 12: USER DASHBOARD (Part 2/2)

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Continuation of:** SECTION_12_USER_DASHBOARD_PART1.md  

---

## ⚙️ SETTINGS & PREFERENCES

### **Settings Page Layout**

```html
<!-- Settings Page -->
<div class="settings-page">
    <h2 class="page-title">Settings</h2>
    
    <!-- Account Settings -->
    <div class="card">
        <div class="card-header">
            <h3>Account Settings</h3>
        </div>
        <div class="card-body">
            <div class="setting-row">
                <div class="setting-info">
                    <label>Email Address</label>
                    <p class="setting-description">Your account email</p>
                </div>
                <div class="setting-control">
                    <input type="email" id="user-email" value="" disabled>
                    <button class="btn btn-secondary btn-sm" onclick="changeEmail()">Change</button>
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>Password</label>
                    <p class="setting-description">Change your password</p>
                </div>
                <div class="setting-control">
                    <button class="btn btn-secondary" onclick="showChangePassword()">
                        Change Password
                    </button>
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>Account Tier</label>
                    <p class="setting-description">Your subscription plan</p>
                </div>
                <div class="setting-control">
                    <span class="tier-badge" id="current-tier">Standard</span>
                    <button class="btn btn-primary btn-sm" onclick="showUpgrade()">Upgrade</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Connection Settings -->
    <div class="card">
        <div class="card-header">
            <h3>Connection Settings</h3>
        </div>
        <div class="card-body">
            <div class="setting-row">
                <div class="setting-info">
                    <label>Auto-Connect</label>
                    <p class="setting-description">Automatically connect when device starts</p>
                </div>
                <div class="setting-control">
                    <label class="toggle">
                        <input type="checkbox" id="auto-connect">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>Kill Switch</label>
                    <p class="setting-description">Block internet if VPN disconnects</p>
                </div>
                <div class="setting-control">
                    <label class="toggle">
                        <input type="checkbox" id="kill-switch">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>DNS Provider</label>
                    <p class="setting-description">Choose your DNS server</p>
                </div>
                <div class="setting-control">
                    <select id="dns-provider">
                        <option value="cloudflare">Cloudflare (1.1.1.1)</option>
                        <option value="google">Google (8.8.8.8)</option>
                        <option value="quad9">Quad9 (9.9.9.9)</option>
                        <option value="adguard">AdGuard (Ad Blocking)</option>
                    </select>
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>Protocol</label>
                    <p class="setting-description">VPN protocol (WireGuard recommended)</p>
                </div>
                <div class="setting-control">
                    <select id="protocol">
                        <option value="wireguard" selected>WireGuard (Fastest)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Privacy Settings -->
    <div class="card">
        <div class="card-header">
            <h3>Privacy & Security</h3>
        </div>
        <div class="card-body">
            <div class="setting-row">
                <div class="setting-info">
                    <label>Connection Logs</label>
                    <p class="setting-description">We don't keep connection logs</p>
                </div>
                <div class="setting-control">
                    <span class="status-badge status-success">No Logs</span>
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>Data Usage Stats</label>
                    <p class="setting-description">Track your bandwidth usage</p>
                </div>
                <div class="setting-control">
                    <label class="toggle">
                        <input type="checkbox" id="usage-stats" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>Email Notifications</label>
                    <p class="setting-description">Receive service updates</p>
                </div>
                <div class="setting-control">
                    <label class="toggle">
                        <input type="checkbox" id="email-notifications" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Advanced Settings -->
    <div class="card">
        <div class="card-header">
            <h3>Advanced Settings</h3>
        </div>
        <div class="card-body">
            <div class="setting-row">
                <div class="setting-info">
                    <label>MTU Size</label>
                    <p class="setting-description">Maximum transmission unit (1420 recommended)</p>
                </div>
                <div class="setting-control">
                    <input type="number" id="mtu-size" value="1420" min="1280" max="1500">
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>Persistent Keepalive</label>
                    <p class="setting-description">Keep connection alive (seconds)</p>
                </div>
                <div class="setting-control">
                    <input type="number" id="keepalive" value="25" min="0" max="60">
                </div>
            </div>
            
            <div class="setting-row">
                <div class="setting-info">
                    <label>Split Tunneling</label>
                    <p class="setting-description">Route only specific traffic through VPN</p>
                </div>
                <div class="setting-control">
                    <label class="toggle">
                        <input type="checkbox" id="split-tunnel">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="settings-actions">
        <button class="btn btn-primary" onclick="saveSettings()">
            💾 Save Changes
        </button>
        <button class="btn btn-secondary" onclick="resetSettings()">
            🔄 Reset to Defaults
        </button>
    </div>
</div>
```

### **Settings JavaScript**

```javascript
// File: js/settings-manager.js

class SettingsManager {
    constructor() {
        this.settings = {};
    }
    
    // Load user settings
    async loadSettings() {
        try {
            const response = await fetch('/api/settings.php?action=get');
            const data = await response.json();
            
            if (data.success) {
                this.settings = data.settings;
                this.populateSettings();
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
        }
    }
    
    // Populate form with current settings
    populateSettings() {
        // Account settings
        document.getElementById('user-email').value = this.settings.email || '';
        document.getElementById('current-tier').textContent = this.settings.tier || 'Standard';
        
        // Connection settings
        document.getElementById('auto-connect').checked = this.settings.auto_connect || false;
        document.getElementById('kill-switch').checked = this.settings.kill_switch || false;
        document.getElementById('dns-provider').value = this.settings.dns_provider || 'cloudflare';
        document.getElementById('protocol').value = this.settings.protocol || 'wireguard';
        
        // Privacy settings
        document.getElementById('usage-stats').checked = this.settings.usage_stats !== false;
        document.getElementById('email-notifications').checked = this.settings.email_notifications !== false;
        
        // Advanced settings
        document.getElementById('mtu-size').value = this.settings.mtu_size || 1420;
        document.getElementById('keepalive').value = this.settings.keepalive || 25;
        document.getElementById('split-tunnel').checked = this.settings.split_tunnel || false;
    }
    
    // Save settings
    async saveSettings() {
        const newSettings = {
            auto_connect: document.getElementById('auto-connect').checked,
            kill_switch: document.getElementById('kill-switch').checked,
            dns_provider: document.getElementById('dns-provider').value,
            protocol: document.getElementById('protocol').value,
            usage_stats: document.getElementById('usage-stats').checked,
            email_notifications: document.getElementById('email-notifications').checked,
            mtu_size: parseInt(document.getElementById('mtu-size').value),
            keepalive: parseInt(document.getElementById('keepalive').value),
            split_tunnel: document.getElementById('split-tunnel').checked
        };
        
        try {
            const response = await fetch('/api/settings.php?action=save', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(newSettings)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Settings saved successfully!', 'success');
                this.settings = newSettings;
            } else {
                showNotification(data.error || 'Failed to save settings', 'error');
            }
        } catch (error) {
            console.error('Save settings error:', error);
            showNotification('Failed to save settings', 'error');
        }
    }
    
    // Reset to defaults
    async resetSettings() {
        if (!confirm('Reset all settings to defaults?')) return;
        
        try {
            const response = await fetch('/api/settings.php?action=reset', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Settings reset to defaults', 'success');
                await this.loadSettings();
            }
        } catch (error) {
            console.error('Reset settings error:', error);
            showNotification('Failed to reset settings', 'error');
        }
    }
}

// Initialize
const settingsManager = new SettingsManager();
```

---

## 📊 USAGE STATISTICS

### **Statistics Page**

```html
<!-- Statistics Page -->
<div class="statistics-page">
    <h2 class="page-title">Usage Statistics</h2>
    
    <!-- Summary Cards -->
    <div class="stats-summary">
        <div class="summary-card">
            <div class="summary-icon">⬇️</div>
            <div class="summary-info">
                <span class="summary-value" id="total-download">0 GB</span>
                <span class="summary-label">Total Downloaded</span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon">⬆️</div>
            <div class="summary-info">
                <span class="summary-value" id="total-upload">0 GB</span>
                <span class="summary-label">Total Uploaded</span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon">⏱️</div>
            <div class="summary-info">
                <span class="summary-value" id="connection-time">0 hrs</span>
                <span class="summary-label">Connected Time</span>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon">🔌</div>
            <div class="summary-info">
                <span class="summary-value" id="connection-count">0</span>
                <span class="summary-label">Connections</span>
            </div>
        </div>
    </div>
    
    <!-- Chart -->
    <div class="card">
        <div class="card-header">
            <h3>Bandwidth Usage</h3>
            <div class="chart-controls">
                <button class="chart-period active" data-period="7">7 Days</button>
                <button class="chart-period" data-period="30">30 Days</button>
                <button class="chart-period" data-period="90">90 Days</button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="usage-chart"></canvas>
        </div>
    </div>
    
    <!-- Device Breakdown -->
    <div class="card">
        <div class="card-header">
            <h3>Usage by Device</h3>
        </div>
        <div class="card-body">
            <div id="device-usage-list" class="usage-list">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>
    
    <!-- Server Usage -->
    <div class="card">
        <div class="card-header">
            <h3>Usage by Server</h3>
        </div>
        <div class="card-body">
            <div id="server-usage-list" class="usage-list">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>
</div>
```

### **Statistics JavaScript**

```javascript
// File: js/statistics-manager.js

class StatisticsManager {
    constructor() {
        this.stats = null;
        this.chart = null;
        this.currentPeriod = 7;
    }
    
    // Load statistics
    async loadStats(period = 7) {
        this.currentPeriod = period;
        
        try {
            const response = await fetch(`/api/statistics.php?period=${period}`);
            const data = await response.json();
            
            if (data.success) {
                this.stats = data.stats;
                this.renderStats();
            }
        } catch (error) {
            console.error('Failed to load statistics:', error);
        }
    }
    
    // Render all statistics
    renderStats() {
        // Summary cards
        document.getElementById('total-download').textContent = this.formatBytes(this.stats.total_download);
        document.getElementById('total-upload').textContent = this.formatBytes(this.stats.total_upload);
        document.getElementById('connection-time').textContent = this.formatHours(this.stats.connection_time);
        document.getElementById('connection-count').textContent = this.stats.connection_count;
        
        // Chart
        this.renderChart();
        
        // Device breakdown
        this.renderDeviceUsage();
        
        // Server usage
        this.renderServerUsage();
    }
    
    // Render bandwidth chart
    renderChart() {
        const ctx = document.getElementById('usage-chart').getContext('2d');
        
        if (this.chart) {
            this.chart.destroy();
        }
        
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.stats.chart_labels,
                datasets: [
                    {
                        label: 'Download',
                        data: this.stats.download_data,
                        borderColor: '#00d9ff',
                        backgroundColor: 'rgba(0, 217, 255, 0.1)',
                        fill: true
                    },
                    {
                        label: 'Upload',
                        data: this.stats.upload_data,
                        borderColor: '#00ff88',
                        backgroundColor: 'rgba(0, 255, 136, 0.1)',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => this.formatBytes(value)
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `${context.dataset.label}: ${this.formatBytes(context.parsed.y)}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Render device usage
    renderDeviceUsage() {
        const container = document.getElementById('device-usage-list');
        
        container.innerHTML = this.stats.device_usage.map(device => `
            <div class="usage-row">
                <div class="usage-info">
                    <span class="usage-icon">${device.icon}</span>
                    <span class="usage-name">${device.name}</span>
                </div>
                <div class="usage-bars">
                    <div class="usage-bar">
                        <div class="bar-fill" style="width: ${device.download_percent}%"></div>
                    </div>
                    <span class="usage-value">${this.formatBytes(device.total_bytes)}</span>
                </div>
            </div>
        `).join('');
    }
    
    // Render server usage
    renderServerUsage() {
        const container = document.getElementById('server-usage-list');
        
        container.innerHTML = this.stats.server_usage.map(server => `
            <div class="usage-row">
                <div class="usage-info">
                    <span class="usage-icon">${server.flag}</span>
                    <span class="usage-name">${server.name}</span>
                </div>
                <div class="usage-bars">
                    <div class="usage-bar">
                        <div class="bar-fill" style="width: ${server.usage_percent}%"></div>
                    </div>
                    <span class="usage-value">${this.formatBytes(server.total_bytes)}</span>
                </div>
            </div>
        `).join('');
    }
    
    // Helper: Format bytes
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }
    
    // Helper: Format hours
    formatHours(seconds) {
        const hours = Math.floor(seconds / 3600);
        return hours + ' hrs';
    }
}

// Initialize
const statisticsManager = new StatisticsManager();
```

---

## 🆘 SUPPORT SYSTEM

### **Support Page**

```html
<!-- Support Page -->
<div class="support-page">
    <h2 class="page-title">Support</h2>
    
    <!-- Quick Help -->
    <div class="card">
        <div class="card-header">
            <h3>Quick Help</h3>
        </div>
        <div class="card-body">
            <div class="help-grid">
                <a href="#" class="help-card" onclick="showHelp('setup')">
                    <div class="help-icon">📱</div>
                    <h4>Device Setup</h4>
                    <p>How to connect your devices</p>
                </a>
                
                <a href="#" class="help-card" onclick="showHelp('troubleshooting')">
                    <div class="help-icon">🔧</div>
                    <h4>Troubleshooting</h4>
                    <p>Fix common issues</p>
                </a>
                
                <a href="#" class="help-card" onclick="showHelp('billing')">
                    <div class="help-icon">💳</div>
                    <h4>Billing</h4>
                    <p>Payment and subscription</p>
                </a>
                
                <a href="#" class="help-card" onclick="showHelp('port-forwarding')">
                    <div class="help-icon">🔌</div>
                    <h4>Port Forwarding</h4>
                    <p>Camera and device access</p>
                </a>
            </div>
        </div>
    </div>
    
    <!-- FAQ -->
    <div class="card">
        <div class="card-header">
            <h3>Frequently Asked Questions</h3>
        </div>
        <div class="card-body">
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How many devices can I connect?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Standard plans allow 5 devices, Pro plans allow 10 devices, and VIP plans have unlimited devices.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>Why is my connection slow?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Try switching to a different server. Dallas is optimized for streaming, while New York provides the best overall speed.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I access my camera remotely?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Use the Port Forwarding feature to set up remote access to your IP cameras. Navigate to Port Forwarding > Discover to automatically find your cameras.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>Can I cancel anytime?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! You can cancel your subscription at any time from Account Settings. You'll continue to have access until the end of your billing period.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Support -->
    <div class="card">
        <div class="card-header">
            <h3>Contact Support</h3>
        </div>
        <div class="card-body">
            <form id="support-form" onsubmit="submitSupportTicket(event)">
                <div class="form-group">
                    <label>Category</label>
                    <select id="ticket-category" required>
                        <option value="">Select a category...</option>
                        <option value="technical">Technical Issue</option>
                        <option value="billing">Billing Question</option>
                        <option value="account">Account Issue</option>
                        <option value="feature">Feature Request</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" id="ticket-subject" placeholder="Brief description of your issue" required>
                </div>
                
                <div class="form-group">
                    <label>Message</label>
                    <textarea id="ticket-message" rows="6" placeholder="Please provide as much detail as possible..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    📨 Submit Ticket
                </button>
            </form>
        </div>
    </div>
    
    <!-- My Tickets -->
    <div class="card">
        <div class="card-header">
            <h3>My Support Tickets</h3>
        </div>
        <div class="card-body">
            <div id="tickets-list">
                <!-- Tickets load dynamically -->
            </div>
        </div>
    </div>
</div>
```

### **Support JavaScript**

```javascript
// File: js/support-manager.js

class SupportManager {
    constructor() {
        this.tickets = [];
    }
    
    // Load user tickets
    async loadTickets() {
        try {
            const response = await fetch('/api/support.php?action=list');
            const data = await response.json();
            
            if (data.success) {
                this.tickets = data.tickets;
                this.renderTickets();
            }
        } catch (error) {
            console.error('Failed to load tickets:', error);
        }
    }
    
    // Render tickets list
    renderTickets() {
        const container = document.getElementById('tickets-list');
        
        if (this.tickets.length === 0) {
            container.innerHTML = '<p class="empty-message">No support tickets yet</p>';
            return;
        }
        
        container.innerHTML = this.tickets.map(ticket => `
            <div class="ticket-card" data-ticket-id="${ticket.id}">
                <div class="ticket-header">
                    <div class="ticket-info">
                        <h4 class="ticket-subject">${ticket.subject}</h4>
                        <p class="ticket-meta">
                            Ticket #${ticket.id} • ${ticket.category} • ${this.formatDate(ticket.created_at)}
                        </p>
                    </div>
                    <span class="status-badge status-${ticket.status}">${ticket.status}</span>
                </div>
                <div class="ticket-preview">
                    ${ticket.message.substring(0, 150)}...
                </div>
                <button class="btn btn-secondary btn-sm" onclick="viewTicket(${ticket.id})">
                    View Details
                </button>
            </div>
        `).join('');
    }
    
    // Submit new ticket
    async submitTicket(category, subject, message) {
        try {
            const response = await fetch('/api/support.php?action=create', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    category: category,
                    subject: subject,
                    message: message
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Support ticket submitted! We\'ll respond within 24 hours.', 'success');
                document.getElementById('support-form').reset();
                await this.loadTickets();
            } else {
                showNotification(data.error || 'Failed to submit ticket', 'error');
            }
        } catch (error) {
            console.error('Submit ticket error:', error);
            showNotification('Failed to submit ticket', 'error');
        }
    }
    
    // Helper: Format date
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
}

// Initialize
const supportManager = new SupportManager();

// Form submit handler
function submitSupportTicket(event) {
    event.preventDefault();
    
    const category = document.getElementById('ticket-category').value;
    const subject = document.getElementById('ticket-subject').value;
    const message = document.getElementById('ticket-message').value;
    
    supportManager.submitTicket(category, subject, message);
}

// FAQ toggle
function toggleFAQ(element) {
    const item = element.closest('.faq-item');
    item.classList.toggle('active');
}
```

---

## 👤 ACCOUNT MANAGEMENT

### **Account Page**

```html
<!-- Account Page -->
<div class="account-page">
    <h2 class="page-title">Account</h2>
    
    <!-- Account Overview -->
    <div class="card">
        <div class="card-header">
            <h3>Account Overview</h3>
        </div>
        <div class="card-body">
            <div class="account-info-grid">
                <div class="info-item">
                    <label>Email</label>
                    <span id="account-email">loading...</span>
                </div>
                <div class="info-item">
                    <label>Account Status</label>
                    <span class="status-badge status-success">Active</span>
                </div>
                <div class="info-item">
                    <label>Plan</label>
                    <span id="account-plan">loading...</span>
                </div>
                <div class="info-item">
                    <label>Member Since</label>
                    <span id="member-since">loading...</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Subscription -->
    <div class="card">
        <div class="card-header">
            <h3>Subscription</h3>
        </div>
        <div class="card-body">
            <div class="subscription-info">
                <div class="plan-details">
                    <h4 id="current-plan-name">Standard Plan</h4>
                    <p id="plan-price">$9.99/month</p>
                    <p id="next-billing">Next billing: January 25, 2026</p>
                </div>
                <div class="plan-actions">
                    <button class="btn btn-primary" onclick="showUpgradePlans()">
                        ⬆️ Upgrade Plan
                    </button>
                    <button class="btn btn-secondary" onclick="manageBilling()">
                        💳 Manage Billing
                    </button>
                </div>
            </div>
            
            <div class="plan-features">
                <h5>Your Plan Includes:</h5>
                <ul id="plan-features-list">
                    <!-- Populated dynamically -->
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Billing History -->
    <div class="card">
        <div class="card-header">
            <h3>Billing History</h3>
        </div>
        <div class="card-body">
            <div id="billing-history" class="billing-list">
                <!-- Invoices load dynamically -->
            </div>
        </div>
    </div>
    
    <!-- Danger Zone -->
    <div class="card card-danger">
        <div class="card-header">
            <h3>⚠️ Danger Zone</h3>
        </div>
        <div class="card-body">
            <div class="danger-actions">
                <div class="danger-item">
                    <div class="danger-info">
                        <h4>Cancel Subscription</h4>
                        <p>Cancel your subscription (access continues until end of billing period)</p>
                    </div>
                    <button class="btn btn-danger" onclick="cancelSubscription()">
                        Cancel Subscription
                    </button>
                </div>
                
                <div class="danger-item">
                    <div class="danger-info">
                        <h4>Delete Account</h4>
                        <p>Permanently delete your account and all data</p>
                    </div>
                    <button class="btn btn-danger" onclick="deleteAccount()">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
```

### **Account Management JavaScript**

```javascript
// File: js/account-manager.js

class AccountManager {
    constructor() {
        this.account = null;
    }
    
    // Load account data
    async loadAccount() {
        try {
            const response = await fetch('/api/account.php?action=get');
            const data = await response.json();
            
            if (data.success) {
                this.account = data.account;
                this.renderAccount();
            }
        } catch (error) {
            console.error('Failed to load account:', error);
        }
    }
    
    // Render account information
    renderAccount() {
        // Overview
        document.getElementById('account-email').textContent = this.account.email;
        document.getElementById('account-plan').textContent = this.account.plan_name;
        document.getElementById('member-since').textContent = this.formatDate(this.account.created_at);
        
        // Subscription
        document.getElementById('current-plan-name').textContent = this.account.plan_name;
        document.getElementById('plan-price').textContent = `$${this.account.plan_price}/month`;
        document.getElementById('next-billing').textContent = `Next billing: ${this.formatDate(this.account.next_billing_date)}`;
        
        // Plan features
        const featuresList = document.getElementById('plan-features-list');
        featuresList.innerHTML = this.account.plan_features.map(feature => 
            `<li>✓ ${feature}</li>`
        ).join('');
        
        // Billing history
        this.renderBillingHistory();
    }
    
    // Render billing history
    renderBillingHistory() {
        const container = document.getElementById('billing-history');
        
        if (!this.account.invoices || this.account.invoices.length === 0) {
            container.innerHTML = '<p class="empty-message">No billing history yet</p>';
            return;
        }
        
        container.innerHTML = this.account.invoices.map(invoice => `
            <div class="invoice-row">
                <div class="invoice-info">
                    <span class="invoice-date">${this.formatDate(invoice.date)}</span>
                    <span class="invoice-description">${invoice.description}</span>
                </div>
                <div class="invoice-actions">
                    <span class="invoice-amount">$${invoice.amount}</span>
                    <button class="btn btn-secondary btn-sm" onclick="downloadInvoice(${invoice.id})">
                        📥 Download
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    // Cancel subscription
    async cancelSubscription() {
        const confirmed = confirm(
            'Are you sure you want to cancel your subscription?\n\n' +
            'You will continue to have access until the end of your current billing period.'
        );
        
        if (!confirmed) return;
        
        try {
            const response = await fetch('/api/subscription.php?action=cancel', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Subscription cancelled. Access continues until ' + data.end_date, 'success');
                await this.loadAccount();
            } else {
                showNotification(data.error || 'Failed to cancel subscription', 'error');
            }
        } catch (error) {
            console.error('Cancel subscription error:', error);
            showNotification('Failed to cancel subscription', 'error');
        }
    }
    
    // Delete account
    async deleteAccount() {
        const confirmed = confirm(
            '⚠️ WARNING: This will permanently delete your account and ALL data.\n\n' +
            'This action CANNOT be undone!\n\n' +
            'Type "DELETE" to confirm:'
        );
        
        if (!confirmed) return;
        
        const verification = prompt('Type DELETE to confirm:');
        if (verification !== 'DELETE') {
            alert('Account deletion cancelled.');
            return;
        }
        
        try {
            const response = await fetch('/api/account.php?action=delete', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Your account has been deleted. You will now be logged out.');
                window.location.href = '/';
            } else {
                showNotification(data.error || 'Failed to delete account', 'error');
            }
        } catch (error) {
            console.error('Delete account error:', error);
            showNotification('Failed to delete account', 'error');
        }
    }
    
    // Helper: Format date
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }
}

// Initialize
const accountManager = new AccountManager();
```

---

## 📱 MOBILE RESPONSIVE DESIGN

### **Responsive CSS**

```css
/* Mobile-First Responsive Design */

/* Tablet (768px and below) */
@media (max-width: 768px) {
    /* Navigation */
    .sidebar {
        position: fixed;
        left: -250px;
        top: 65px;
        height: calc(100vh - 65px);
        z-index: 1000;
        transition: left 0.3s;
    }
    
    .sidebar.active {
        left: 0;
    }
    
    /* Overlay */
    .mobile-overlay {
        display: none;
        position: fixed;
        top: 65px;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }
    
    .mobile-overlay.active {
        display: block;
    }
    
    /* Main content */
    .main-content {
        padding: 15px;
    }
    
    /* Cards */
    .card {
        padding: 15px;
    }
    
    /* Stats grid */
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    /* Device grid */
    .device-grid {
        grid-template-columns: 1fr;
    }
    
    /* Modal */
    .modal-content {
        width: 95%;
        max-height: 90vh;
        overflow-y: auto;
    }
}

/* Mobile (480px and below) */
@media (max-width: 480px) {
    /* Top nav */
    .top-nav {
        padding: 10px 15px;
    }
    
    .logo span {
        display: none; /* Hide text, show icon only */
    }
    
    .user-email {
        font-size: 0.85rem;
    }
    
    /* Stats grid */
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    /* Buttons */
    .btn {
        padding: 8px 15px;
        font-size: 0.85rem;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.75rem;
    }
    
    /* Device actions */
    .device-actions {
        flex-direction: column;
        gap: 8px;
    }
    
    .device-actions .btn {
        width: 100%;
    }
}
```

### **Mobile Menu Toggle**

```javascript
// Mobile menu toggle
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close menu when clicking overlay
document.querySelector('.mobile-overlay')?.addEventListener('click', () => {
    toggleMobileMenu();
});

// Add hamburger button to top nav (mobile only)
if (window.innerWidth <= 768) {
    const hamburger = document.createElement('button');
    hamburger.className = 'hamburger-btn';
    hamburger.innerHTML = '☰';
    hamburger.onclick = toggleMobileMenu;
    document.querySelector('.logo').before(hamburger);
}
```

---

## ⚡ REAL-TIME UPDATES

### **WebSocket Connection**

```javascript
// File: js/realtime-manager.js

class RealtimeManager {
    constructor() {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
    }
    
    // Connect to WebSocket server
    connect() {
        const wsUrl = `wss://${window.location.host}/ws`;
        
        try {
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = () => {
                console.log('WebSocket connected');
                this.reconnectAttempts = 0;
                this.authenticate();
            };
            
            this.ws.onmessage = (event) => {
                this.handleMessage(JSON.parse(event.data));
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
            
            this.ws.onclose = () => {
                console.log('WebSocket disconnected');
                this.reconnect();
            };
        } catch (error) {
            console.error('WebSocket connection failed:', error);
            this.reconnect();
        }
    }
    
    // Authenticate WebSocket connection
    authenticate() {
        const token = localStorage.getItem('auth_token');
        this.send('auth', {token: token});
    }
    
    // Send message
    send(type, data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({type: type, data: data}));
        }
    }
    
    // Handle incoming messages
    handleMessage(message) {
        switch (message.type) {
            case 'connection_status':
                this.updateConnectionStatus(message.data);
                break;
            case 'usage_update':
                this.updateUsageStats(message.data);
                break;
            case 'device_added':
                deviceManager.loadDevices();
                showNotification('New device added', 'success');
                break;
            case 'server_switched':
                deviceManager.loadDevices();
                showNotification('Server switched successfully', 'success');
                break;
            default:
                console.log('Unknown message type:', message.type);
        }
    }
    
    // Update connection status
    updateConnectionStatus(data) {
        const statusBanner = document.getElementById('status-banner');
        if (!statusBanner) return;
        
        if (data.connected) {
            statusBanner.classList.add('connected');
            statusBanner.classList.remove('disconnected');
            document.getElementById('status-text').textContent = 'Protected';
            document.getElementById('status-detail').textContent = `Connected to ${data.server_name}`;
        } else {
            statusBanner.classList.remove('connected');
            statusBanner.classList.add('disconnected');
            document.getElementById('status-text').textContent = 'Not Protected';
            document.getElementById('status-detail').textContent = 'No active connections';
        }
    }
    
    // Update usage stats
    updateUsageStats(data) {
        document.getElementById('data-download').textContent = formatBytes(data.download);
        document.getElementById('data-upload').textContent = formatBytes(data.upload);
    }
    
    // Reconnect with exponential backoff
    reconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            return;
        }
        
        const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
        this.reconnectAttempts++;
        
        setTimeout(() => {
            console.log(`Reconnecting... (attempt ${this.reconnectAttempts})`);
            this.connect();
        }, delay);
    }
    
    // Disconnect
    disconnect() {
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
    }
}

// Initialize
const realtimeManager = new RealtimeManager();

// Connect when page loads
document.addEventListener('DOMContentLoaded', () => {
    realtimeManager.connect();
});

// Disconnect when page unloads
window.addEventListener('beforeunload', () => {
    realtimeManager.disconnect();
});
```

### **Long Polling Alternative**

```javascript
// For environments without WebSocket support

class PollingManager {
    constructor() {
        this.pollInterval = 5000; // 5 seconds
        this.polling = false;
    }
    
    start() {
        this.polling = true;
        this.poll();
    }
    
    async poll() {
        if (!this.polling) return;
        
        try {
            // Check connection status
            const statusResponse = await fetch('/api/status.php');
            const statusData = await statusResponse.json();
            
            if (statusData.success) {
                this.updateStatus(statusData);
            }
            
            // Check for notifications
            const notifResponse = await fetch('/api/notifications.php');
            const notifData = await notifResponse.json();
            
            if (notifData.success && notifData.notifications.length > 0) {
                this.handleNotifications(notifData.notifications);
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
        
        // Schedule next poll
        setTimeout(() => this.poll(), this.pollInterval);
    }
    
    stop() {
        this.polling = false;
    }
    
    updateStatus(data) {
        realtimeManager.updateConnectionStatus(data.connection);
        realtimeManager.updateUsageStats(data.usage);
    }
    
    handleNotifications(notifications) {
        notifications.forEach(notif => {
            showNotification(notif.message, notif.type);
        });
    }
}
```

---

**END OF SECTION 12: USER DASHBOARD (Complete)**

**Status:** ✅ COMPLETE  
**Total Lines:** ~1,700 lines (Part 1 + Part 2)  
**Created:** January 15, 2026 - 6:15 AM CST

**Features Covered:**
- Complete dashboard layout and navigation
- Device management with add/remove/configure
- Server selection and switching
- Settings and preferences
- Usage statistics with charts
- Support ticket system with FAQ
- Account management and billing
- Mobile responsive design
- Real-time updates via WebSocket

**Next Section:** Section 13 (API Endpoints)
