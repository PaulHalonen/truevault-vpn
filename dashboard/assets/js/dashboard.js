/**
 * TrueVault VPN - Dashboard JavaScript
 * Handles API calls, authentication, and UI
 */

// API Configuration
const API_BASE = '/api';
const TOKEN_KEY = 'truevault_token';
const USER_KEY = 'truevault_user';

// State
let currentUser = null;
let isConnected = false;

// ============ Authentication ============

function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

function setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
}

function getUser() {
    const userData = localStorage.getItem(USER_KEY);
    return userData ? JSON.parse(userData) : null;
}

function setUser(user) {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    currentUser = user;
}

function clearAuth() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    currentUser = null;
}

function isAuthenticated() {
    return !!getToken();
}

function requireAuth() {
    if (!isAuthenticated()) {
        window.location.href = '/login.html';
        return false;
    }
    return true;
}

// ============ API Calls ============

async function apiCall(endpoint, method = 'GET', data = null) {
    const url = `${API_BASE}${endpoint}`;
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    const token = getToken();
    if (token) {
        options.headers['Authorization'] = `Bearer ${token}`;
    }
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (response.status === 401) {
            clearAuth();
            window.location.href = '/login.html?expired=1';
            return null;
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showToast('Connection error. Please try again.', 'error');
        return null;
    }
}

// Auth API
async function login(email, password) {
    const result = await apiCall('/auth/login.php', 'POST', { email, password });
    if (result?.success) {
        setToken(result.data.token);
        setUser(result.data.user);
    }
    return result;
}

async function register(data) {
    const result = await apiCall('/auth/register.php', 'POST', data);
    if (result?.success) {
        setToken(result.data.token);
        setUser(result.data.user);
    }
    return result;
}

async function logout() {
    await apiCall('/auth/logout.php', 'POST');
    clearAuth();
    window.location.href = '/';
}

// User API
async function getProfile() {
    return apiCall('/users/profile.php');
}

async function updateProfile(data) {
    return apiCall('/users/profile.php', 'PUT', data);
}

// VPN API
async function getServers() {
    return apiCall('/vpn/servers.php');
}

async function getVPNStatus() {
    return apiCall('/vpn/status.php');
}

async function connectVPN(serverId) {
    return apiCall('/vpn/connect.php', 'POST', { server_id: serverId });
}

async function disconnectVPN() {
    return apiCall('/vpn/disconnect.php', 'POST');
}

async function getVPNConfig(serverId) {
    return apiCall(`/vpn/config.php?server_id=${serverId}`);
}

// Devices API
async function getDevices() {
    return apiCall('/devices/index.php');
}

async function addDevice(data) {
    return apiCall('/devices/index.php', 'POST', data);
}

async function deleteDevice(id) {
    return apiCall(`/devices/index.php?id=${id}`, 'DELETE');
}

// Cameras API
async function getCameras() {
    return apiCall('/cameras/index.php');
}

async function addCamera(data) {
    return apiCall('/cameras/index.php', 'POST', data);
}

async function deleteCamera(id) {
    return apiCall(`/cameras/index.php?id=${id}`, 'DELETE');
}

// Subscription API
async function getSubscription() {
    return apiCall('/billing/subscription.php');
}

async function createCheckout(planId) {
    return apiCall('/billing/checkout.php', 'POST', { plan_id: planId });
}

// ============ UI Helpers ============

function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <span class="toast-message">${message}</span>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function showModal(title, content, buttons = []) {
    const existingModal = document.querySelector('.modal-overlay');
    if (existingModal) existingModal.remove();
    
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">${title}</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">${content}</div>
            ${buttons.length ? `
                <div class="modal-footer">
                    ${buttons.map(btn => `
                        <button class="btn ${btn.class || 'btn-secondary'}" onclick="${btn.onclick}">${btn.text}</button>
                    `).join('')}
                </div>
            ` : ''}
        </div>
    `;
    
    document.body.appendChild(modal);
    
    setTimeout(() => modal.classList.add('active'), 10);
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
}

function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 200);
    }
}

function showLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    if (element) {
        element.dataset.originalContent = element.innerHTML;
        element.innerHTML = '<span class="loading"></span>';
        element.disabled = true;
    }
}

function hideLoading(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    if (element && element.dataset.originalContent) {
        element.innerHTML = element.dataset.originalContent;
        element.disabled = false;
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ============ Dashboard Initialization ============

async function initDashboard() {
    if (!requireAuth()) return;
    
    currentUser = getUser();
    
    // Update UI with user info
    updateUserInfo();
    
    // Set active nav item
    setActiveNav();
    
    // Load page-specific data
    await loadPageData();
}

function updateUserInfo() {
    if (!currentUser) return;
    
    const userName = document.getElementById('userName');
    const userPlan = document.getElementById('userPlan');
    const userAvatar = document.getElementById('userAvatar');
    
    if (userName) {
        userName.textContent = `${currentUser.first_name} ${currentUser.last_name}`;
    }
    
    if (userPlan) {
        if (currentUser.is_vip) {
            userPlan.innerHTML = '<span class="vip-badge">VIP</span>';
        } else {
            userPlan.textContent = currentUser.plan || 'Free';
        }
    }
    
    if (userAvatar) {
        userAvatar.textContent = currentUser.first_name?.charAt(0) || 'U';
    }
}

function setActiveNav() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href') === currentPage) {
            item.classList.add('active');
        }
    });
}

async function loadPageData() {
    const page = window.location.pathname.split('/').pop() || 'index.html';
    
    switch(page) {
        case 'index.html':
            await loadDashboardHome();
            break;
        case 'servers.html':
            await loadServers();
            break;
        case 'devices.html':
            await loadDevices();
            break;
        case 'cameras.html':
            await loadCameras();
            break;
        case 'connect.html':
            await loadConnectPage();
            break;
        case 'settings.html':
            await loadSettings();
            break;
    }
}

// ============ Page-Specific Functions ============

async function loadDashboardHome() {
    // Load stats
    const [subscription, devices, cameras, servers] = await Promise.all([
        getSubscription(),
        getDevices(),
        getCameras(),
        getServers()
    ]);
    
    // Update stat cards
    const deviceCount = document.getElementById('deviceCount');
    const cameraCount = document.getElementById('cameraCount');
    
    if (deviceCount && devices?.data) {
        deviceCount.textContent = devices.data.length;
    }
    
    if (cameraCount && cameras?.data) {
        cameraCount.textContent = cameras.data.length;
    }
}

async function loadServers() {
    const result = await getServers();
    const container = document.getElementById('serverGrid');
    
    if (!container) return;
    
    if (!result?.success || !result.data?.length) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🖥️</div>
                <h3 class="empty-title">No Servers Available</h3>
                <p class="empty-text">Please try again later</p>
            </div>
        `;
        return;
    }
    
    const flags = {
        'US-East': '🇺🇸',
        'US-Central': '🇺🇸',
        'US-South': '🇺🇸',
        'Canada': '🇨🇦'
    };
    
    container.innerHTML = result.data.map(server => `
        <div class="server-card ${server.is_vip ? 'vip-only' : ''}" data-server-id="${server.id}">
            <div class="server-header">
                <span class="server-flag">${flags[server.name] || '🌐'}</span>
                <div>
                    <h3 class="server-name">${server.name}</h3>
                    <span class="server-location">${server.location}</span>
                </div>
                ${server.is_vip ? '<span class="badge badge-vip">VIP</span>' : ''}
            </div>
            <div class="server-stats">
                <div class="server-stat">
                    <span class="server-stat-value">${server.load || '0'}%</span>
                    <span class="server-stat-label">Load</span>
                </div>
                <div class="server-stat">
                    <span class="server-stat-value">${server.ping || '--'}ms</span>
                    <span class="server-stat-label">Ping</span>
                </div>
                <div class="server-stat">
                    <span class="server-stat-value">${server.users || 0}</span>
                    <span class="server-stat-label">Users</span>
                </div>
            </div>
            <button class="connect-btn" onclick="connectToServer(${server.id})">
                Connect
            </button>
        </div>
    `).join('');
}

async function connectToServer(serverId) {
    const btn = event.target;
    showLoading(btn);
    
    const result = await connectVPN(serverId);
    
    hideLoading(btn);
    
    if (result?.success) {
        showToast('Connected successfully!', 'success');
        updateConnectionStatus(true);
    } else {
        showToast(result?.error || 'Connection failed', 'error');
    }
}

function updateConnectionStatus(connected) {
    isConnected = connected;
    const statusEl = document.getElementById('connectionStatus');
    const statusText = document.getElementById('connectionStatusText');
    
    if (statusEl) {
        const dot = statusEl.querySelector('.status-dot');
        if (dot) {
            dot.className = `status-dot ${connected ? 'connected' : 'disconnected'}`;
        }
        statusEl.querySelector('span:last-child').textContent = connected ? 'Connected' : 'Disconnected';
    }
    
    if (statusText) {
        statusText.textContent = connected ? 'Connected' : 'Disconnected';
    }
}

async function loadDevices() {
    const result = await getDevices();
    const container = document.getElementById('deviceList');
    
    if (!container) return;
    
    if (!result?.success || !result.data?.length) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📱</div>
                <h3 class="empty-title">No Devices</h3>
                <p class="empty-text">Add your first device to get started</p>
                <button class="btn btn-primary" onclick="showAddDevice()">Add Device</button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = result.data.map(device => `
        <div class="device-card" data-device-id="${device.id}">
            <div class="device-icon">${getDeviceIcon(device.type)}</div>
            <div class="device-info">
                <div class="device-name">${device.name}</div>
                <div class="device-details">${device.type} • ${device.last_seen || 'Never connected'}</div>
            </div>
            <span class="badge ${device.status === 'active' ? 'badge-success' : 'badge-warning'}">${device.status}</span>
            <button class="btn btn-sm btn-danger" onclick="deleteDevice(${device.id})">Remove</button>
        </div>
    `).join('');
}

function getDeviceIcon(type) {
    const icons = {
        'phone': '📱',
        'tablet': '📱',
        'laptop': '💻',
        'desktop': '🖥️',
        'router': '📶',
        'other': '📟'
    };
    return icons[type] || icons.other;
}

// ============ Sidebar Toggle ============

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}

// ============ Initialize on Load ============

document.addEventListener('DOMContentLoaded', initDashboard);
