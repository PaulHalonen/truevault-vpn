/**
 * TrueVault VPN - Theme Loader
 * Loads theme CSS variables from database API
 */

(function() {
    'use strict';
    
    const API_BASE = '/api';
    
    async function loadTheme() {
        try {
            const response = await fetch(`${API_BASE}/theme/index.php`);
            const data = await response.json();
            
            if (data.success && data.data.css_variables) {
                // Update the theme-variables style tag
                let styleTag = document.getElementById('theme-variables');
                
                if (!styleTag) {
                    styleTag = document.createElement('style');
                    styleTag.id = 'theme-variables';
                    document.head.insertBefore(styleTag, document.head.firstChild);
                }
                
                styleTag.textContent = data.data.css_variables;
                
                // Store theme in localStorage for faster loading
                localStorage.setItem('truevault_theme', JSON.stringify({
                    css: data.data.css_variables,
                    settings: data.data.settings,
                    timestamp: Date.now()
                }));
                
                console.log('Theme loaded from database');
            }
        } catch (error) {
            console.log('Using cached or default theme');
            loadCachedTheme();
        }
    }
    
    function loadCachedTheme() {
        try {
            const cached = localStorage.getItem('truevault_theme');
            if (cached) {
                const theme = JSON.parse(cached);
                // Use cache if less than 1 hour old
                if (Date.now() - theme.timestamp < 3600000) {
                    let styleTag = document.getElementById('theme-variables');
                    if (styleTag) {
                        styleTag.textContent = theme.css;
                    }
                }
            }
        } catch (e) {
            console.log('No cached theme available');
        }
    }
    
    // Load cached theme immediately for instant display
    loadCachedTheme();
    
    // Then fetch latest from server
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadTheme);
    } else {
        loadTheme();
    }
    
    // Expose for manual refresh
    window.TrueVaultTheme = {
        reload: loadTheme,
        clearCache: function() {
            localStorage.removeItem('truevault_theme');
        }
    };
})();
