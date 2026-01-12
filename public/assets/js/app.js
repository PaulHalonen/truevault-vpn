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
    
    // API helper
    const API = {
        request: async function(endpoint, options = {}) {
            const url = `${API_BASE}${endpoint}`;
            
            const headers = {
                'Content-Type': 'application/json',
                ...options.headers
            };
            
            const token = TokenManager.get();
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
            
            const config = {
                ...options,
                headers
            };
            
            if (options.body && typeof options.body === 'object') {
                config.body = JSON.stringify(options.body);
            }
            
            try {
                const response = await fetch(url, config);
                const data = await response.json();
                
                // Handle 401 - try to refresh token
                if (response.status === 401 && TokenManager.getRefresh()) {
                    const refreshed = await this.refreshToken();
                    if (refreshed) {
                        // Retry original request
                        headers['Authorization'] = `Bearer ${TokenManager.get()}`;
                        const retryResponse = await fetch(url, { ...config, headers });
                        return await retryResponse.json();
                    }
                }
                
                return data;
            } catch (error) {
                console.error('API Error:', error);
                return { success: false, error: 'Network error' };
            }
        },
        
        get: function(endpoint) {
            return this.request(endpoint, { method: 'GET' });
        },
        
        post: function(endpoint, body) {
            return this.request(endpoint, { method: 'POST', body });
        },
        
        put: function(endpoint, body) {
            return this.request(endpoint, { method: 'PUT', body });
        },
        
        delete: function(endpoint) {
            return this.request(endpoint, { method: 'DELETE' });
        },
        
        refreshToken: async function() {
            const refreshToken = TokenManager.getRefresh();
            if (!refreshToken) return false;
            
            try {
                const response = await fetch(`${API_BASE}/auth/refresh.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ refresh_token: refreshToken })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    TokenManager.set(data.data.token);
                    TokenManager.setRefresh(data.data.refresh_token);
                    UserManager.set(data.data.user);
                    return true;
                }
            } catch (error) {
                console.error('Token refresh failed:', error);
            }
            
            // Refresh failed, clear tokens
            TokenManager.clear();
            UserManager.clear();
            return false;
        }
    };
    
    // Auth functions
    const Auth = {
        login: async function(email, password) {
            const result = await API.post('/auth/login.php', { email, password });
            
            if (result.success) {
                TokenManager.set(result.data.token);
                TokenManager.setRefresh(result.data.refresh_token);
                UserManager.set(result.data.user);
            }
            
            return result;
        },
        
        register: async function(data) {
            const result = await API.post('/auth/register.php', data);
            
            if (result.success) {
                TokenManager.set(result.data.token);
                TokenManager.setRefresh(result.data.refresh_token);
                UserManager.set(result.data.user);
            }
            
            return result;
        },
        
        logout: async function() {
            await API.post('/auth/logout.php', {});
            TokenManager.clear();
            UserManager.clear();
            window.location.href = '/login.html';
        },
        
        isLoggedIn: function() {
            return TokenManager.isLoggedIn();
        },
        
        getUser: function() {
            return UserManager.get();
        },
        
        requireAuth: function() {
            if (!this.isLoggedIn()) {
                window.location.href = '/login.html?redirect=' + encodeURIComponent(window.location.pathname);
                return false;
            }
            return true;
        }
    };
    
    // Toast notifications
    const Toast = {
        container: null,
        
        init: function() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                document.body.appendChild(this.container);
            }
        },
        
        show: function(message, type = 'info', duration = 3000) {
            this.init();
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            
            this.container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        },
        
        success: function(message) {
            this.show(message, 'success');
        },
        
        error: function(message) {
            this.show(message, 'error');
        },
        
        info: function(message) {
            this.show(message, 'info');
        }
    };
    
    // Form helpers
    const Form = {
        serialize: function(form) {
            const data = {};
            const formData = new FormData(form);
            formData.forEach((value, key) => {
                data[key] = value;
            });
            return data;
        },
        
        validate: function(form, rules) {
            const errors = {};
            const data = this.serialize(form);
            
            for (const field in rules) {
                const value = data[field] || '';
                const fieldRules = rules[field].split('|');
                
                for (const rule of fieldRules) {
                    const [ruleName, ruleValue] = rule.split(':');
                    
                    switch (ruleName) {
                        case 'required':
                            if (!value.trim()) {
                                errors[field] = 'This field is required';
                            }
                            break;
                        case 'email':
                            if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                                errors[field] = 'Invalid email address';
                            }
                            break;
                        case 'min':
                            if (value.length < parseInt(ruleValue)) {
                                errors[field] = `Must be at least ${ruleValue} characters`;
                            }
                            break;
                        case 'confirmed':
                            if (value !== data[`${field}_confirmation`]) {
                                errors[field] = 'Passwords do not match';
                            }
                            break;
                    }
                    
                    if (errors[field]) break;
                }
            }
            
            return {
                isValid: Object.keys(errors).length === 0,
                errors
            };
        },
        
        showErrors: function(form, errors) {
            // Clear existing errors
            form.querySelectorAll('.form-error').forEach(el => el.remove());
            form.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
            
            // Show new errors
            for (const field in errors) {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) {
                    input.classList.add('has-error');
                    const errorEl = document.createElement('div');
                    errorEl.className = 'form-error';
                    errorEl.textContent = errors[field];
                    input.parentNode.appendChild(errorEl);
                }
            }
        },
        
        clearErrors: function(form) {
            form.querySelectorAll('.form-error').forEach(el => el.remove());
            form.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
        }
    };
    
    // Utility functions
    const Utils = {
        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
        },
        
        formatDuration: function(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            
            if (h > 0) return `${h}h ${m}m ${s}s`;
            if (m > 0) return `${m}m ${s}s`;
            return `${s}s`;
        },
        
        formatDate: function(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        
        formatDateTime: function(dateString) {
            return new Date(dateString).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },
        
        copyToClipboard: async function(text) {
            try {
                await navigator.clipboard.writeText(text);
                Toast.success('Copied to clipboard');
                return true;
            } catch (err) {
                Toast.error('Failed to copy');
                return false;
            }
        }
    };
    
    // Expose to global scope
    window.TrueVault = {
        API,
        Auth,
        Toast,
        Form,
        Utils,
        TokenManager,
        UserManager
    };
    
    // Update nav based on auth state
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelector('.nav-links');
        if (navLinks && Auth.isLoggedIn()) {
            const user = Auth.getUser();
            const loginBtn = navLinks.querySelector('a[href="/login.html"]');
            const registerBtn = navLinks.querySelector('a[href="/register.html"]');
            
            if (loginBtn) loginBtn.outerHTML = `<a href="/dashboard/">Dashboard</a>`;
            if (registerBtn) registerBtn.outerHTML = `<a href="#" onclick="TrueVault.Auth.logout(); return false;" class="btn btn-secondary">Logout</a>`;
        }
    });
})();
