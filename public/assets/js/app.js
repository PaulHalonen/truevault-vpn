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
        window.location.href = '/login.html';
    }
    
    // Check auth and redirect if needed
    function requireAuth() {
        if (!TokenManager.isLoggedIn()) {
            window.location.href = '/login.html?redirect=' + encodeURIComponent(window.location.pathname);
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
    
    // Auth module
    const Auth = {
        login: async function(email, password) {
            try {
                const response = await fetch(`${API_BASE}/auth/login.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const result = await response.json();
                
                // API returns { success, message, data: { user, token, refresh_token, ... } }
                if (result.success && result.data && result.data.token) {
                    TokenManager.set(result.data.token);
                    if (result.data.refresh_token) TokenManager.setRefresh(result.data.refresh_token);
                    if (result.data.user) UserManager.set(result.data.user);
                    // Store servers and subscription for dashboard use
                    if (result.data.servers) localStorage.setItem('truevault_servers', JSON.stringify(result.data.servers));
                    if (result.data.subscription) localStorage.setItem('truevault_subscription', JSON.stringify(result.data.subscription));
                    return { success: true };
                }
                return { success: false, error: result.message || 'Login failed' };
            } catch (error) {
                console.error('Login error:', error);
                return { success: false, error: 'Network error. Please try again.' };
            }
        },
        
        register: async function(email, password, firstName, lastName) {
            try {
                const response = await fetch(`${API_BASE}/auth/register.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password, first_name: firstName, last_name: lastName })
                });
                const result = await response.json();
                
                // API returns { success, message, data: { user, token, refresh_token, ... } }
                if (result.success && result.data && result.data.token) {
                    TokenManager.set(result.data.token);
                    if (result.data.refresh_token) TokenManager.setRefresh(result.data.refresh_token);
                    if (result.data.user) UserManager.set(result.data.user);
                    if (result.data.servers) localStorage.setItem('truevault_servers', JSON.stringify(result.data.servers));
                    if (result.data.subscription) localStorage.setItem('truevault_subscription', JSON.stringify(result.data.subscription));
                    return { success: true };
                }
                return { success: false, error: result.message || 'Registration failed' };
            } catch (error) {
                console.error('Register error:', error);
                return { success: false, error: 'Network error. Please try again.' };
            }
        },
        
        logout: function() {
            logout();
        },
        
        isLoggedIn: function() {
            return TokenManager.isLoggedIn();
        },
        
        getUser: function() {
            return UserManager.get();
        },
        
        requireAuth: function() {
            return requireAuth();
        }
    };
    
    // Form utilities
    const Form = {
        validate: function(form, rules) {
            const errors = {};
            let isValid = true;
            
            for (const [field, ruleStr] of Object.entries(rules)) {
                const input = form.querySelector(`[name="${field}"]`);
                if (!input) continue;
                
                const value = input.value.trim();
                const fieldRules = ruleStr.split('|');
                
                for (const rule of fieldRules) {
                    if (rule === 'required' && !value) {
                        errors[field] = 'This field is required';
                        isValid = false;
                        break;
                    }
                    if (rule === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        errors[field] = 'Please enter a valid email';
                        isValid = false;
                        break;
                    }
                    if (rule.startsWith('min:')) {
                        const min = parseInt(rule.split(':')[1]);
                        if (value.length < min) {
                            errors[field] = `Must be at least ${min} characters`;
                            isValid = false;
                            break;
                        }
                    }
                }
            }
            
            return { isValid, errors };
        },
        
        showErrors: function(form, errors) {
            // Clear previous errors
            form.querySelectorAll('.error-message').forEach(el => el.remove());
            form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
            
            for (const [field, message] of Object.entries(errors)) {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) {
                    input.classList.add('input-error');
                    const errorEl = document.createElement('div');
                    errorEl.className = 'error-message';
                    errorEl.style.cssText = 'color: var(--colors-error, #ff5050); font-size: 0.85rem; margin-top: 5px;';
                    errorEl.textContent = message;
                    input.parentNode.appendChild(errorEl);
                }
            }
        },
        
        serialize: function(form) {
            const data = {};
            const formData = new FormData(form);
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }
            return data;
        }
    };
    
    // Toast helper
    const Toast = {
        success: (msg) => showToast(msg, 'success'),
        error: (msg) => showToast(msg, 'error'),
        info: (msg) => showToast(msg, 'info')
    };
    
    // API helper
    const API = {
        get: async function(endpoint) {
            return await apiRequest(endpoint, { method: 'GET' });
        },
        post: async function(endpoint, data) {
            return await apiRequest(endpoint, { method: 'POST', body: data });
        },
        put: async function(endpoint, data) {
            return await apiRequest(endpoint, { method: 'PUT', body: data });
        },
        delete: async function(endpoint) {
            return await apiRequest(endpoint, { method: 'DELETE' });
        }
    };
    
    // Utils
    const Utils = {
        formatBytes: function(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        formatDuration: function(seconds) {
            if (!seconds || seconds < 0) return '0m';
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            if (h > 0) return `${h}h ${m}m`;
            return `${m}m`;
        },
        formatDate: function(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        copyToClipboard: async function(text) {
            try {
                await navigator.clipboard.writeText(text);
                Toast.success('Copied to clipboard!');
                return true;
            } catch (e) {
                Toast.error('Failed to copy');
                return false;
            }
        }
    };
    
    // Expose to global scope
    window.apiRequest = apiRequest;
    window.showToast = showToast;
    window.logout = logout;
    window.requireAuth = requireAuth;
    
    window.TrueVault = {
        TokenManager,
        UserManager,
        Auth,
        Form,
        Toast,
        API,
        Utils,
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
