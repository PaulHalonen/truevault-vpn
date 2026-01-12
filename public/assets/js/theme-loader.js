/**
 * TrueVault VPN - Theme Loader
 * Loads theme variables from database and applies to page
 */

(function() {
    'use strict';

    // Default theme fallback
    const defaultTheme = {
        colors: {
            primary: '#00d9ff',
            secondary: '#00ff88',
            accent: '#ff6b6b',
            background: '#0f0f1a',
            backgroundSecondary: '#1a1a2e',
            backgroundTertiary: '#252540',
            text: '#ffffff',
            textMuted: '#888888',
            success: '#00ff88',
            warning: '#ffbb00',
            error: '#ff5050',
            border: 'rgba(255,255,255,0.08)'
        },
        gradients: {
            primary: 'linear-gradient(90deg, #00d9ff, #00ff88)',
            background: 'linear-gradient(135deg, #0f0f1a, #1a1a2e)'
        },
        typography: {
            fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
            fontSizeBase: '16px'
        },
        buttons: {
            borderRadius: '8px',
            padding: '10px 20px'
        },
        cards: {
            borderRadius: '14px',
            padding: '20px'
        }
    };

    // Apply theme variables to CSS
    function applyTheme(variables) {
        const root = document.documentElement;

        // Apply colors
        if (variables.colors) {
            Object.entries(variables.colors).forEach(([key, value]) => {
                root.style.setProperty(`--${kebabCase(key)}`, value);
            });
        }

        // Apply gradients
        if (variables.gradients) {
            Object.entries(variables.gradients).forEach(([key, value]) => {
                root.style.setProperty(`--gradient-${kebabCase(key)}`, value);
            });
        }

        // Apply typography
        if (variables.typography) {
            if (variables.typography.fontFamily) {
                root.style.setProperty('--font-family', variables.typography.fontFamily);
            }
            if (variables.typography.fontSizeBase) {
                root.style.setProperty('--font-size-base', variables.typography.fontSizeBase);
            }
        }

        // Apply buttons
        if (variables.buttons) {
            if (variables.buttons.borderRadius) {
                root.style.setProperty('--btn-radius', variables.buttons.borderRadius);
            }
            if (variables.buttons.padding) {
                root.style.setProperty('--btn-padding', variables.buttons.padding);
            }
        }

        // Apply cards
        if (variables.cards) {
            if (variables.cards.borderRadius) {
                root.style.setProperty('--card-radius', variables.cards.borderRadius);
            }
            if (variables.cards.padding) {
                root.style.setProperty('--card-padding', variables.cards.padding);
            }
        }
    }

    // Convert camelCase to kebab-case
    function kebabCase(str) {
        return str.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
    }

    // Load theme from API
    async function loadTheme() {
        try {
            // Check cache first
            const cached = localStorage.getItem('truevault_theme');
            const cacheTime = localStorage.getItem('truevault_theme_time');
            
            // Use cache if less than 5 minutes old
            if (cached && cacheTime && (Date.now() - parseInt(cacheTime)) < 300000) {
                const theme = JSON.parse(cached);
                applyTheme(theme.variables || theme);
                console.log('Using cached theme');
                return;
            }

            // Fetch from API
            const response = await fetch('/api/theme/');
            
            if (!response.ok) {
                throw new Error('Failed to load theme');
            }

            const data = await response.json();
            
            if (data.success && data.data && data.data.variables) {
                applyTheme(data.data.variables);
                
                // Cache theme
                localStorage.setItem('truevault_theme', JSON.stringify(data.data));
                localStorage.setItem('truevault_theme_time', Date.now().toString());
                
                console.log('Theme loaded from API:', data.data.name);
            } else {
                throw new Error('Invalid theme data');
            }

        } catch (error) {
            console.log('Using cached or default theme', error.message);
            
            // Try to use cached theme
            const cached = localStorage.getItem('truevault_theme');
            if (cached) {
                const theme = JSON.parse(cached);
                applyTheme(theme.variables || theme);
            } else {
                // Apply default theme
                applyTheme(defaultTheme);
            }
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadTheme);
    } else {
        loadTheme();
    }

    // Export for manual refresh
    window.reloadTheme = loadTheme;

})();
