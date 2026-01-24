/**
 * TrueVault VPN - Help Bubbles & Tooltips System
 * Task 16.4: Interactive help bubbles with animated pointers
 * Created: January 24, 2026
 */

class TrueVaultHelpSystem {
    constructor(options = {}) {
        this.options = {
            apiUrl: '/admin/tutorials/api.php',
            bubbleClass: 'tv-help-bubble',
            pointerClass: 'tv-pointer',
            tooltipDelay: 300,
            animationDuration: 300,
            ...options
        };
        
        this.bubbles = [];
        this.activeTooltip = null;
        this.pointer = null;
        this.initialized = false;
        
        this.init();
    }
    
    async init() {
        // Create styles
        this.injectStyles();
        
        // Create pointer element
        this.createPointer();
        
        // Load bubbles for current page
        await this.loadBubbles();
        
        // Bind hover events
        this.bindEvents();
        
        // Show auto-show bubbles
        this.showAutoBubbles();
        
        this.initialized = true;
    }
    
    injectStyles() {
        if (document.getElementById('tv-help-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'tv-help-styles';
        styles.textContent = `
            /* Help Bubble Styles */
            .tv-help-bubble {
                position: absolute;
                background: linear-gradient(135deg, #1a1a2e, #16213e);
                border: 1px solid rgba(0, 212, 255, 0.3);
                border-radius: 12px;
                padding: 15px 20px;
                max-width: 300px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5), 0 0 20px rgba(0, 212, 255, 0.1);
                z-index: 10000;
                opacity: 0;
                transform: scale(0.9) translateY(10px);
                transition: all 0.3s ease;
                pointer-events: none;
            }
            
            .tv-help-bubble.visible {
                opacity: 1;
                transform: scale(1) translateY(0);
                pointer-events: auto;
            }
            
            .tv-help-bubble::before {
                content: '';
                position: absolute;
                width: 12px;
                height: 12px;
                background: linear-gradient(135deg, #1a1a2e, #16213e);
                border: 1px solid rgba(0, 212, 255, 0.3);
                transform: rotate(45deg);
            }
            
            .tv-help-bubble.position-top::before {
                bottom: -7px;
                left: 50%;
                margin-left: -6px;
                border-top: none;
                border-left: none;
            }
            
            .tv-help-bubble.position-bottom::before {
                top: -7px;
                left: 50%;
                margin-left: -6px;
                border-bottom: none;
                border-right: none;
            }
            
            .tv-help-bubble.position-left::before {
                right: -7px;
                top: 50%;
                margin-top: -6px;
                border-bottom: none;
                border-left: none;
            }
            
            .tv-help-bubble.position-right::before {
                left: -7px;
                top: 50%;
                margin-top: -6px;
                border-top: none;
                border-right: none;
            }
            
            .tv-bubble-title {
                font-size: 0.95rem;
                font-weight: 600;
                color: #00d4ff;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .tv-bubble-title i {
                font-size: 1rem;
            }
            
            .tv-bubble-content {
                font-size: 0.85rem;
                color: #ccc;
                line-height: 1.5;
            }
            
            .tv-bubble-close {
                position: absolute;
                top: 8px;
                right: 10px;
                width: 20px;
                height: 20px;
                border: none;
                background: rgba(255,255,255,0.1);
                border-radius: 50%;
                color: #888;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.7rem;
                transition: all 0.2s;
            }
            
            .tv-bubble-close:hover {
                background: rgba(255,80,80,0.3);
                color: #ff5050;
            }
            
            /* Animated Pointer */
            .tv-pointer {
                position: fixed;
                width: 50px;
                height: 50px;
                pointer-events: none;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .tv-pointer.visible {
                opacity: 1;
            }
            
            .tv-pointer-icon {
                width: 100%;
                height: 100%;
                animation: tv-pointer-bounce 1s ease-in-out infinite;
                filter: drop-shadow(0 0 10px rgba(0, 212, 255, 0.5));
            }
            
            .tv-pointer-ripple {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 30px;
                height: 30px;
                margin: -15px 0 0 -15px;
                border: 2px solid rgba(0, 212, 255, 0.5);
                border-radius: 50%;
                animation: tv-ripple 1.5s ease-out infinite;
            }
            
            @keyframes tv-pointer-bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-8px); }
            }
            
            @keyframes tv-ripple {
                0% {
                    transform: scale(0.5);
                    opacity: 1;
                }
                100% {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            /* Highlight effect for target elements */
            .tv-highlight {
                position: relative;
                z-index: 9998;
            }
            
            .tv-highlight::after {
                content: '';
                position: absolute;
                top: -4px;
                left: -4px;
                right: -4px;
                bottom: -4px;
                border: 2px solid rgba(0, 212, 255, 0.5);
                border-radius: 8px;
                box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
                animation: tv-highlight-pulse 1.5s ease-in-out infinite;
                pointer-events: none;
            }
            
            @keyframes tv-highlight-pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
            
            /* Help icon indicator */
            .tv-help-indicator {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 18px;
                height: 18px;
                background: linear-gradient(135deg, #00d4ff, #7b2cbf);
                border-radius: 50%;
                color: #fff;
                font-size: 0.7rem;
                font-weight: bold;
                cursor: help;
                margin-left: 6px;
                transition: transform 0.2s;
            }
            
            .tv-help-indicator:hover {
                transform: scale(1.1);
            }
        `;
        document.head.appendChild(styles);
    }
    
    createPointer() {
        this.pointer = document.createElement('div');
        this.pointer.className = this.options.pointerClass;
        this.pointer.innerHTML = `
            <svg class="tv-pointer-icon" viewBox="0 0 24 24" fill="#00d4ff">
                <path d="M13.64 21.97C13.14 22.21 12.54 22 12.31 21.5L10.13 16.76L7.62 18.78C7.45 18.92 7.24 19 7.02 19C6.55 19 6.15 18.61 6.15 18.14V5C6.15 4.45 6.6 4 7.15 4C7.37 4 7.59 4.08 7.77 4.21L19.57 13.15C19.97 13.45 20.07 14 19.77 14.4C19.63 14.59 19.43 14.72 19.2 14.77L15.39 15.5L17.57 20.24C17.81 20.74 17.6 21.33 17.1 21.57L13.64 21.97Z"/>
            </svg>
            <div class="tv-pointer-ripple"></div>
        `;
        document.body.appendChild(this.pointer);
    }
    
    async loadBubbles() {
        try {
            const currentPath = window.location.pathname;
            const response = await fetch(`${this.options.apiUrl}?action=get_bubbles&page=${encodeURIComponent(currentPath)}`);
            const data = await response.json();
            
            if (data.success && data.bubbles) {
                this.bubbles = data.bubbles;
            }
        } catch (error) {
            console.error('Failed to load help bubbles:', error);
        }
    }
    
    bindEvents() {
        // Bind hover events to elements with help bubbles
        this.bubbles.forEach(bubble => {
            if (bubble.show_on_hover) {
                const element = document.querySelector(bubble.element_selector);
                if (element) {
                    element.addEventListener('mouseenter', () => this.showBubble(bubble, element));
                    element.addEventListener('mouseleave', () => this.hideBubble());
                }
            }
        });
        
        // Add help indicators to elements
        this.bubbles.forEach(bubble => {
            const element = document.querySelector(bubble.element_selector);
            if (element && !bubble.show_on_hover) {
                this.addHelpIndicator(element, bubble);
            }
        });
        
        // Global click to dismiss
        document.addEventListener('click', (e) => {
            if (!e.target.closest(`.${this.options.bubbleClass}`) && 
                !e.target.closest('.tv-help-indicator')) {
                this.hideBubble();
            }
        });
        
        // Escape key to dismiss
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideBubble();
                this.hidePointer();
            }
        });
    }
    
    addHelpIndicator(element, bubble) {
        const indicator = document.createElement('span');
        indicator.className = 'tv-help-indicator';
        indicator.innerHTML = '?';
        indicator.addEventListener('click', (e) => {
            e.stopPropagation();
            this.showBubble(bubble, element);
        });
        
        // Insert after the element or inside if it's a container
        if (element.tagName === 'INPUT' || element.tagName === 'SELECT' || element.tagName === 'BUTTON') {
            element.parentNode.insertBefore(indicator, element.nextSibling);
        } else {
            element.appendChild(indicator);
        }
    }
    
    showBubble(bubble, targetElement) {
        // Remove existing bubble
        this.hideBubble();
        
        // Create bubble element
        const bubbleEl = document.createElement('div');
        bubbleEl.className = `${this.options.bubbleClass} position-${bubble.bubble_position || 'top'}`;
        bubbleEl.innerHTML = `
            <button class="tv-bubble-close" onclick="trueVaultHelp.hideBubble()">×</button>
            <div class="tv-bubble-title">
                <i class="fas fa-lightbulb"></i>
                ${bubble.bubble_title || 'Tip'}
            </div>
            <div class="tv-bubble-content">${bubble.bubble_content || ''}</div>
        `;
        
        document.body.appendChild(bubbleEl);
        this.activeTooltip = bubbleEl;
        
        // Position the bubble
        this.positionBubble(bubbleEl, targetElement, bubble.bubble_position || 'top');
        
        // Show with animation
        requestAnimationFrame(() => {
            bubbleEl.classList.add('visible');
        });
        
        // Highlight target element
        targetElement.classList.add('tv-highlight');
    }
    
    positionBubble(bubbleEl, targetElement, position) {
        const targetRect = targetElement.getBoundingClientRect();
        const bubbleRect = bubbleEl.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        let top, left;
        
        switch (position) {
            case 'top':
                top = targetRect.top + scrollTop - bubbleRect.height - 15;
                left = targetRect.left + scrollLeft + (targetRect.width / 2) - (bubbleRect.width / 2);
                break;
            case 'bottom':
                top = targetRect.bottom + scrollTop + 15;
                left = targetRect.left + scrollLeft + (targetRect.width / 2) - (bubbleRect.width / 2);
                break;
            case 'left':
                top = targetRect.top + scrollTop + (targetRect.height / 2) - (bubbleRect.height / 2);
                left = targetRect.left + scrollLeft - bubbleRect.width - 15;
                break;
            case 'right':
                top = targetRect.top + scrollTop + (targetRect.height / 2) - (bubbleRect.height / 2);
                left = targetRect.right + scrollLeft + 15;
                break;
        }
        
        // Keep bubble within viewport
        const padding = 10;
        left = Math.max(padding, Math.min(left, window.innerWidth - bubbleRect.width - padding));
        top = Math.max(padding, top);
        
        bubbleEl.style.top = `${top}px`;
        bubbleEl.style.left = `${left}px`;
    }
    
    hideBubble() {
        if (this.activeTooltip) {
            this.activeTooltip.classList.remove('visible');
            setTimeout(() => {
                if (this.activeTooltip) {
                    this.activeTooltip.remove();
                    this.activeTooltip = null;
                }
            }, this.options.animationDuration);
        }
        
        // Remove highlights
        document.querySelectorAll('.tv-highlight').forEach(el => {
            el.classList.remove('tv-highlight');
        });
    }
    
    showAutoBubbles() {
        const autoBubbles = this.bubbles.filter(b => b.auto_show);
        
        // Show first auto-show bubble after delay
        if (autoBubbles.length > 0) {
            setTimeout(() => {
                const bubble = autoBubbles[0];
                const element = document.querySelector(bubble.element_selector);
                if (element) {
                    this.showBubble(bubble, element);
                }
            }, 1000);
        }
    }
    
    // Public method to show pointer on specific element
    pointTo(selector) {
        const element = document.querySelector(selector);
        if (!element) return;
        
        const rect = element.getBoundingClientRect();
        
        this.pointer.style.left = `${rect.left + rect.width / 2 - 25}px`;
        this.pointer.style.top = `${rect.top - 60}px`;
        this.pointer.classList.add('visible');
        
        element.classList.add('tv-highlight');
    }
    
    // Public method to hide pointer
    hidePointer() {
        this.pointer.classList.remove('visible');
        document.querySelectorAll('.tv-highlight').forEach(el => {
            el.classList.remove('tv-highlight');
        });
    }
    
    // Show a custom tooltip programmatically
    showTooltip(element, title, content, position = 'top') {
        this.showBubble({
            bubble_title: title,
            bubble_content: content,
            bubble_position: position
        }, element);
    }
    
    // Start a guided tour
    async startTour(tourId) {
        try {
            const response = await fetch(`${this.options.apiUrl}?action=get_tour&tour_id=${tourId}`);
            const data = await response.json();
            
            if (data.success && data.steps) {
                this.runTour(data.steps);
            }
        } catch (error) {
            console.error('Failed to start tour:', error);
        }
    }
    
    runTour(steps, index = 0) {
        if (index >= steps.length) {
            this.hideBubble();
            this.hidePointer();
            return;
        }
        
        const step = steps[index];
        const element = document.querySelector(step.selector);
        
        if (element) {
            this.pointTo(step.selector);
            this.showBubble({
                bubble_title: `Step ${index + 1}: ${step.title}`,
                bubble_content: `${step.content}<br><br><button onclick="trueVaultHelp.runTour(window.__tourSteps, ${index + 1})" style="margin-top:10px;padding:8px 16px;background:linear-gradient(135deg,#00d4ff,#7b2cbf);border:none;border-radius:6px;color:#fff;cursor:pointer;font-weight:600;">${index < steps.length - 1 ? 'Next →' : 'Finish'}</button>`,
                bubble_position: step.position || 'bottom'
            }, element);
            
            window.__tourSteps = steps;
        } else {
            // Skip to next if element not found
            this.runTour(steps, index + 1);
        }
    }
}

// Auto-initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    window.trueVaultHelp = new TrueVaultHelpSystem();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TrueVaultHelpSystem;
}
