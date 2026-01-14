/**
 * TrueVault VPN - Dashboard Controller
 * Handles all dashboard functionality including servers, devices, cameras, billing
 */

const Dashboard = {
    user: null,
    servers: [],
    devices: [],
    cameras: [],
    subscription: null,
    
    /**
     * Initialize dashboard
     */
    async init() {
        console.log('Initializing TrueVault Dashboard...');
        
        // Check authentication
        if (!Auth.isLoggedIn()) {
            window.location.href = '/login.html';
            return;
        }
        
        this.user = Auth.getUser();
        
        // Load all data
        await Promise.all([
            this.loadServers(),
            this.loadDevices(),
            this.loadSubscription()
        ]);
        
        // Render UI
        this.renderUserInfo();
        this.renderServers();
        this.renderDevices();
        this.renderCameras();
        this.renderSubscription();
        
        // Setup event listeners
        this.setupEventListeners();
        
        console.log('Dashboard initialized');
    },
    
    /**
     * Load available servers
     */
    async loadServers() {
        try {
            const response = await API.get('/api/vpn/servers.php');
            if (response.success) {
                this.servers = response.data.servers;
                this.user = { ...this.user, ...response.data.user };
            }
        } catch (error) {
            console.error('Failed to load servers:', error);
            Toast.error('Failed to load servers');
        }
    },
    
    /**
     * Load user devices and cameras
     */
    async loadDevices() {
        try {
            const response = await API.get('/api/devices/list.php');
            if (response.success) {
                this.devices = response.data.devices;
                this.cameras = response.data.cameras;
            }
        } catch (error) {
            console.error('Failed to load devices:', error);
        }
    },
    
    /**
     * Load subscription info
     */
    async loadSubscription() {
        try {
            const response = await API.get('/api/billing/subscription.php');
            if (response.success) {
                this.subscription = response.data.subscription;
            }
        } catch (error) {
            console.error('Failed to load subscription:', error);
        }
    },
    
    /**
     * Render user info section
     */
    renderUserInfo() {
        const container = document.getElementById('user-info');
        if (!container) return;
        
        let vipBadge = '';
        if (this.user.is_vip) {
            vipBadge = this.user.vip_tier === 'vip_dedicated' 
                ? '<span class="badge vip-dedicated">👑 VIP Dedicated</span>'
                : '<span class="badge vip">⭐ VIP</span>';
        }
        
        container.innerHTML = `
            <div class="user-header">
                <div class="user-avatar">${this.user.first_name?.[0] || this.user.email[0].toUpperCase()}</div>
                <div class="user-details">
                    <h3>${this.user.first_name || 'User'} ${vipBadge}</h3>
                    <p>${this.user.email}</p>
                </div>
            </div>
        `;
    },
    
    /**
     * Render servers section
     */
    renderServers() {
        const container = document.getElementById('servers-list');
        if (!container) return;
        
        if (this.servers.length === 0) {
            container.innerHTML = '<p class="empty">No servers available</p>';
            return;
        }
        
        container.innerHTML = this.servers.map(server => `
            <div class="server-card ${server.access === 'exclusive' ? 'exclusive' : ''}" data-server-id="${server.id}">
                <div class="server-header">
                    <span class="server-icon">${server.icon}</span>
                    <div class="server-info">
                        <h4>${server.display_name}</h4>
                        <p class="server-location">${server.location}</p>
                    </div>
                    <span class="server-status ${server.status}">${server.status}</span>
                </div>
                
                <div class="server-rules">
                    <strong>${server.rules.title}</strong>
                    <p>${server.rules.description}</p>
                    
                    ${server.rules.allowed.length ? `
                        <div class="rules-allowed">
                            ${server.rules.allowed.map(r => `<span>${r}</span>`).join('')}
                        </div>
                    ` : ''}
                    
                    ${server.rules.not_allowed.length ? `
                        <div class="rules-not-allowed">
                            ${server.rules.not_allowed.map(r => `<span>${r}</span>`).join('')}
                        </div>
                    ` : ''}
                </div>
                
                <div class="server-actions">
                    <button class="btn btn-primary" onclick="Dashboard.getConfig(${server.id})">
                        📥 Get Config
                    </button>
                    <span class="bandwidth-indicator ${server.bandwidth}">
                        ${server.bandwidth === 'unlimited' ? '∞ Unlimited' : '⚡ Limited'}
                    </span>
                </div>
            </div>
        `).join('');
    },
    
    /**
     * Get WireGuard config for server
     */
    async getConfig(serverId) {
        try {
            Toast.info('Generating config...');
            
            const response = await API.get(`/api/vpn/config.php?server_id=${serverId}`);
            
            if (response.success) {
                // Show config modal
                this.showConfigModal(response.data);
            } else {
                Toast.error(response.message || 'Failed to generate config');
            }
        } catch (error) {
            Toast.error('Failed to generate config');
        }
    },
    
    /**
     * Show config download modal
     */
    showConfigModal(data) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal config-modal">
                <div class="modal-header">
                    <h3>📄 ${data.filename}</h3>
                    <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">×</button>
                </div>
                
                <div class="modal-body">
                    <div class="server-info-box">
                        <strong>${data.server.name}</strong> - ${data.server.location}
                        ${data.server.bandwidth_limited ? '<span class="badge warning">Limited Bandwidth</span>' : ''}
                    </div>
                    
                    <div class="instructions-box">
                        <h4>${data.instructions.title}</h4>
                        <p>${data.instructions.description}</p>
                        <div class="instruction-tags">
                            ${data.instructions.allowed.map(a => `<span class="tag allowed">${a}</span>`).join('')}
                            ${data.instructions.not_allowed.map(a => `<span class="tag not-allowed">${a}</span>`).join('')}
                        </div>
                    </div>
                    
                    <div class="config-box">
                        <textarea id="config-content" readonly>${data.config}</textarea>
                        <button class="btn btn-secondary copy-btn" onclick="Dashboard.copyConfig()">
                            📋 Copy to Clipboard
                        </button>
                    </div>
                    
                    <div class="import-instructions">
                        <h4>How to Import:</h4>
                        <ol>
                            <li>Open WireGuard app on your device</li>
                            <li>Click "Add Tunnel" → "Import from file or archive" OR</li>
                            <li>Click "Add Tunnel" → "Add empty tunnel" and paste config</li>
                            <li>Save and connect!</li>
                        </ol>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    },
    
    /**
     * Copy config to clipboard
     */
    async copyConfig() {
        const textarea = document.getElementById('config-content');
        try {
            await navigator.clipboard.writeText(textarea.value);
            Toast.success('Config copied to clipboard!');
        } catch (error) {
            textarea.select();
            document.execCommand('copy');
            Toast.success('Config copied!');
        }
    },
    
    /**
     * Render devices section
     */
    renderDevices() {
        const container = document.getElementById('devices-list');
        if (!container) return;
        
        const limits = this.subscription?.max_devices || 0;
        const used = this.devices.length;
        
        let html = `
            <div class="devices-header">
                <h4>My Devices</h4>
                <span class="device-count">${used}/${limits} devices</span>
            </div>
        `;
        
        if (this.devices.length === 0) {
            html += '<p class="empty">No devices registered. Use the scanner to discover devices.</p>';
        } else {
            html += '<div class="devices-grid">';
            html += this.devices.map(device => `
                <div class="device-card">
                    <div class="device-icon">${this.getDeviceIcon(device.type)}</div>
                    <div class="device-info">
                        <strong>${device.name}</strong>
                        <span class="device-type">${device.type}</span>
                        ${device.ip_address ? `<span class="device-ip">${device.ip_address}</span>` : ''}
                    </div>
                    <button class="btn-icon" onclick="Dashboard.removeDevice('${device.device_id}')" title="Remove">🗑️</button>
                </div>
            `).join('');
            html += '</div>';
        }
        
        container.innerHTML = html;
    },
    
    /**
     * Render cameras section
     */
    renderCameras() {
        const container = document.getElementById('cameras-list');
        if (!container) return;
        
        const limits = this.subscription?.max_cameras || 0;
        const used = this.cameras.length;
        
        let html = `
            <div class="cameras-header">
                <h4>My IP Cameras</h4>
                <span class="camera-count">${used}/${limits} cameras</span>
            </div>
        `;
        
        if (this.cameras.length === 0) {
            html += '<p class="empty">No cameras registered. Basic/Family plans can only use NY server for cameras.</p>';
        } else {
            html += '<div class="cameras-grid">';
            html += this.cameras.map(camera => `
                <div class="camera-card">
                    <div class="camera-preview">📹</div>
                    <div class="camera-info">
                        <strong>${camera.name}</strong>
                        <span class="camera-type">${camera.type}</span>
                        <span class="camera-ip">${camera.ip_address}:${camera.port}</span>
                    </div>
                    ${camera.forwarding ? `
                        <div class="camera-forwarding">
                            <small>External: ${camera.forwarding.external_ip}:${camera.forwarding.external_port}</small>
                        </div>
                    ` : ''}
                </div>
            `).join('');
            html += '</div>';
        }
        
        container.innerHTML = html;
    },
    
    /**
     * Render subscription section
     */
    renderSubscription() {
        const container = document.getElementById('subscription-info');
        if (!container) return;
        
        if (!this.subscription) {
            container.innerHTML = `
                <div class="no-subscription">
                    <p>No active subscription</p>
                    <a href="/pricing.html" class="btn btn-primary">View Plans</a>
                </div>
            `;
            return;
        }
        
        if (this.subscription.is_vip) {
            container.innerHTML = `
                <div class="subscription-card vip">
                    <div class="plan-badge">${this.subscription.vip_badge}</div>
                    <h4>Free Lifetime Access</h4>
                    <p>You have VIP access with no expiration!</p>
                    <div class="plan-limits">
                        <span>📱 ${this.subscription.max_devices} devices</span>
                        <span>📹 ${this.subscription.max_cameras} cameras</span>
                    </div>
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <div class="subscription-card">
                <h4>${this.subscription.plan_type} Plan</h4>
                <p>Status: <span class="status ${this.subscription.status}">${this.subscription.status}</span></p>
                ${this.subscription.end_date ? `<p>Renews: ${new Date(this.subscription.end_date).toLocaleDateString()}</p>` : ''}
                <div class="plan-limits">
                    <span>📱 ${this.subscription.max_devices} devices</span>
                    <span>📹 ${this.subscription.max_cameras} cameras</span>
                </div>
                <button class="btn btn-secondary" onclick="Dashboard.manageBilling()">Manage Billing</button>
            </div>
        `;
    },
    
    /**
     * Get device icon based on type
     */
    getDeviceIcon(type) {
        const icons = {
            'phone': '📱',
            'tablet': '📱',
            'laptop': '💻',
            'desktop': '🖥️',
            'gaming': '🎮',
            'streaming': '📺',
            'smart_home': '🏠',
            'router': '📶',
            'printer': '🖨️',
            'unknown': '❓'
        };
        return icons[type] || icons['unknown'];
    },
    
    /**
     * Remove a device
     */
    async removeDevice(deviceId) {
        if (!confirm('Remove this device?')) return;
        
        try {
            const response = await API.post('/api/devices/remove.php', { device_id: deviceId });
            if (response.success) {
                Toast.success('Device removed');
                await this.loadDevices();
                this.renderDevices();
            } else {
                Toast.error(response.message || 'Failed to remove device');
            }
        } catch (error) {
            Toast.error('Failed to remove device');
        }
    },
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Logout button
        document.getElementById('logout-btn')?.addEventListener('click', () => {
            Auth.logout();
            window.location.href = '/login.html';
        });
        
        // Refresh button
        document.getElementById('refresh-btn')?.addEventListener('click', () => {
            this.init();
        });
    },
    
    /**
     * Manage billing
     */
    manageBilling() {
        window.location.href = '/billing.html';
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => Dashboard.init());
