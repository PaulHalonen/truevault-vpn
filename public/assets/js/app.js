/**
 * TrueVault VPN - Main Application JavaScript
 */

(function() {
    'use strict';
    
    const API_BASE = '/api';
    
    // Token management
    const TokenManager = {
        get: function() {
            return localStorage.getItem('truevault_token');
        },
        
        set: function(token) {
            localStorage.setItem('truevault_token', token);
            localStorage.setItem('auth_token', token); // For scanner compatibility
        },
        
        getRefresh: function() {
            return localStorage.getItem('truevault_refresh_token');
        },
        
        setRefresh: function(token) {
            localStorage.setItem('truevault_refresh_token', token);
        },
        
        clear: function() {
            localStorage.removeItem('truevault_token');
            localStorage.removeItem('truevault_refresh_token');
            localStorage.removeItem('truevault_user');
            localStorage.removeItem('auth_token');
        },
        
        isLoggedIn: function() {
            return !!this.get();
        }
    };
    
    // User management
    const UserManager = {
        get: function() {
            const user = localStorage.getItem('truevault_user');
            return user ? JSON.parse(user) : null;
        },
        
        set: function(user) {
            localStorage.setItem('truevault_user', JSON.stringify(user));
        },
        
        clear: function() {
            localStorage.removeItem('truevault_user');
        }
    };
    
    // Toast notifications
    function showToast(message, type = 'info', duration = 3000) {
        // Remove existing toast
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();
        
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            color: white;
            background: ${type === 'success' ? '#00c853' : type === 'error' ? '#ff5252' : '#2196f3'};
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    // API request helper
    async function apiRequest(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : `${API_BASE}${endpoint}`;
        
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        const token = TokenManager.get();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const config = {
            method: options.method || 'GET',
            headers,
            ...options
        };
        
        if (options.body && typeof options.body !== 'string') {
            config.body = JSON.stringify(options.body);
        }
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            // Handle 401 - redirect to login
            if (response.status === 401) {
                // Don't redirect if already on login page
                if (!window.location.pathname.includes('login')) {
                    logout();
                }
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    // Logout function
    function logout() {
        TokenManager.clear();
        UserManager.clear();
        window.location.href = '/public/login.html';
    }
    
    // Check auth and redirect if needed
    function requireAuth() {
        if (!TokenManager.isLoggedIn()) {
            window.location.href = '/public/login.html?redirect=' + encodeURIComponent(window.location.pathname);
            return false;
        }
        return true;
    }
    
    // Render navigation
    function renderNavigation() {
        const navLinks = document.getElementById('nav-links');
        const userInfo = document.getElementById('user-info');
        
        if (!navLinks) return;
        
        const user = UserManager.get();
        const currentPath = window.location.pathname;
        
        const links = [
            { href: '/public/dashboard/index.html', icon: '📊', label: 'Overview' },
            { href: '/public/dashboard/connect.html', icon: '🔗', label: 'Connect' },
            { href: '/public/dashboard/servers.html', icon: '🌍', label: 'Servers' },
            { href: '/public/dashboard/identities.html', icon: '🎭', label: 'Identities' },
            { href: '/public/dashboard/certificates.html', icon: '📜', label: 'Certificates' },
            { href: '/public/dashboard/devices.html', icon: '📱', label: 'Devices' },
            { href: '/public/dashboard/cameras.html', icon: '📷', label: 'Cameras' },
            { href: '/public/dashboard/mesh.html', icon: '🕸️', label: 'Mesh Network' },
            { href: '/public/dashboard/scanner.html', icon: '📡', label: 'Network Scanner' },
            { href: '/public/dashboard/settings.html', icon: '⚙️', label: 'Settings' },
            { href: '/public/dashboard/billing.html', icon: '💳', label: 'Billing' }
        ];
        
        navLinks.innerHTML = links.map(link => {
            const isActive = currentPath.includes(link.href.split('/').pop().replace('.html', ''));
            return `<a href="${link.href}" class="nav-link ${isActive ? 'active' : ''}">
                <span class="nav-icon">${link.icon}</span>
                <span class="nav-label">${link.label}</span>
            </a>`;
        }).join('');
        
        // User info
        if (userInfo && user) {
            userInfo.innerHTML = `
                <div class="user-avatar">${(user.first_name || user.email || 'U').charAt(0).toUpperCase()}</div>
                <div class="user-details">
                    <div class="user-name">${user.first_name || 'User'} ${user.last_name || ''}</div>
                    <div class="user-plan">${user.plan_type || 'Personal'} Plan</div>
                </div>
            `;
        }
    }
    
    // Initialize dashboard
    function initDashboard() {
        // Check auth
        if (window.location.pathname.includes('/dashboard/') && !requireAuth()) {
            return;
        }
        
        // Render nav
        renderNavigation();
    }
    
    // Expose to global scope
    window.apiRequest = apiRequest;
    window.showToast = showToast;
    window.logout = logout;
    window.requireAuth = requireAuth;
    
    window.TrueVault = {
        TokenManager,
        UserManager,
        apiRequest,
        showToast,
        logout,
        requireAuth
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboard);
    } else {
        initDashboard();
    }
    
    // Add toast animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-notification {
            transition: all 0.3s ease;
        }
    `;
    document.head.appendChild(style);
})();
