/**
 * CSRF Protection for GF JS Embed
 */
class GFCSRFProtection {
    constructor() {
        this.tokens = new Map();
        this.refreshInterval = null;
        this.enabled = true;
        this.debug = false;
        
        this.init();
    }
    
    init() {
        // Listen for form registrations to get tokens
        if (typeof GFEvents !== 'undefined') {
            GFEvents.on('form.registered', (eventData) => {
                this.refreshTokenForForm(eventData.data.formId);
            });
            
            GFEvents.on('form.beforeSubmit', (eventData) => {
                this.addTokenToSubmission(eventData);
            });
        }
        
        // Set up automatic token refresh
        this.startTokenRefresh();
        
        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            this.stopTokenRefresh();
        });
    }
    
    /**
     * Generate a new token for a form
     */
    async generateToken(formId = null) {
        if (!this.enabled) {
            this.log('CSRF protection disabled');
            return null;
        }
        
        try {
            const response = await fetch(gfEmbedConfig.restUrl + 'gf-embed/v1/csrf-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': gfEmbedConfig.nonce
                },
                body: JSON.stringify({
                    form_id: formId
                })
            });
            
            const data = await response.json();
            
            if (data.success && data.data.token) {
                const tokenData = {
                    token: data.data.token,
                    formId: formId,
                    created: Date.now(),
                    timeout: data.data.timeout * 1000, // Convert to milliseconds
                    used: false
                };
                
                this.tokens.set(formId || 'global', tokenData);
                this.log('Generated CSRF token for form:', formId, tokenData.token);
                
                return tokenData.token;
            } else {
                this.log('Failed to generate CSRF token:', data);
                return null;
            }
        } catch (error) {
            this.log('Error generating CSRF token:', error);
            return null;
        }
    }
    
    /**
     * Get token for a specific form
     */
    getToken(formId = null) {
        const key = formId || 'global';
        const tokenData = this.tokens.get(key);
        
        if (!tokenData) {
            this.log('No token found for form:', formId);
            return null;
        }
        
        // Check if token is expired
        const age = Date.now() - tokenData.created;
        if (age > tokenData.timeout) {
            this.log('Token expired for form:', formId);
            this.tokens.delete(key);
            return null;
        }
        
        return tokenData.token;
    }
    
    /**
     * Validate a token
     */
    async validateToken(token, formId = null) {
        if (!this.enabled || !token) {
            return true;
        }
        
        try {
            const response = await fetch(gfEmbedConfig.restUrl + 'gf-embed/v1/csrf-validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': gfEmbedConfig.nonce
                },
                body: JSON.stringify({
                    token: token,
                    form_id: formId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.log('Token validation result:', data.data.valid);
                return data.data.valid;
            } else {
                this.log('Token validation failed:', data);
                return false;
            }
        } catch (error) {
            this.log('Error validating token:', error);
            return false;
        }
    }
    
    /**
     * Refresh token for a specific form
     */
    async refreshTokenForForm(formId = null) {
        this.log('Refreshing token for form:', formId);
        
        const token = await this.generateToken(formId);
        
        if (token && typeof GFEvents !== 'undefined') {
            GFEvents.trigger('csrf.tokenRefreshed', {
                formId: formId,
                token: token
            });
        }
        
        return token;
    }
    
    /**
     * Add CSRF token to form submission
     */
    addTokenToSubmission(eventData) {
        if (!this.enabled) {
            return;
        }
        
        const formId = eventData.data.formId;
        const token = this.getToken(formId);
        
        if (token) {
            // Add token to form data
            const form = eventData.data.form;
            if (form) {
                // Create hidden input for CSRF token
                let csrfInput = form.querySelector('input[name="csrf_token"]');
                if (!csrfInput) {
                    csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    form.appendChild(csrfInput);
                }
                csrfInput.value = token;
                
                this.log('Added CSRF token to form submission:', formId, token);
                
                // Mark token as used
                const tokenData = this.tokens.get(formId || 'global');
                if (tokenData) {
                    tokenData.used = true;
                }
            }
        } else {
            this.log('No valid CSRF token available for form:', formId);
            
            // Try to generate a new token immediately
            this.generateToken(formId).then(newToken => {
                if (newToken) {
                    this.log('Generated emergency CSRF token:', newToken);
                    
                    const form = eventData.data.form;
                    if (form) {
                        let csrfInput = form.querySelector('input[name="csrf_token"]');
                        if (!csrfInput) {
                            csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = 'csrf_token';
                            form.appendChild(csrfInput);
                        }
                        csrfInput.value = newToken;
                    }
                }
            });
        }
    }
    
    /**
     * Start automatic token refresh
     */
    startTokenRefresh() {
        // Refresh tokens every 10 minutes
        this.refreshInterval = setInterval(() => {
            this.refreshAllTokens();
        }, 10 * 60 * 1000);
    }
    
    /**
     * Stop automatic token refresh
     */
    stopTokenRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    /**
     * Refresh all active tokens
     */
    async refreshAllTokens() {
        const formIds = [];
        
        for (const [key, tokenData] of this.tokens) {
            // Only refresh tokens that are close to expiring
            const age = Date.now() - tokenData.created;
            const timeUntilExpiry = tokenData.timeout - age;
            
            if (timeUntilExpiry < 5 * 60 * 1000) { // Less than 5 minutes until expiry
                if (key === 'global') {
                    formIds.push(null);
                } else {
                    formIds.push(key);
                }
            }
        }
        
        for (const formId of formIds) {
            await this.refreshTokenForForm(formId);
        }
    }
    
    /**
     * Get all token statistics
     */
    getTokenStats() {
        const stats = {
            totalTokens: this.tokens.size,
            activeTokens: 0,
            expiredTokens: 0,
            usedTokens: 0
        };
        
        const now = Date.now();
        
        for (const tokenData of this.tokens.values()) {
            const age = now - tokenData.created;
            
            if (age > tokenData.timeout) {
                stats.expiredTokens++;
            } else if (tokenData.used) {
                stats.usedTokens++;
            } else {
                stats.activeTokens++;
            }
        }
        
        return stats;
    }
    
    /**
     * Clear all tokens
     */
    clearTokens() {
        this.tokens.clear();
        this.log('Cleared all CSRF tokens');
    }
    
    /**
     * Enable/disable CSRF protection
     */
    setEnabled(enabled) {
        this.enabled = enabled;
        this.log('CSRF protection', enabled ? 'enabled' : 'disabled');
        
        if (!enabled) {
            this.clearTokens();
            this.stopTokenRefresh();
        } else {
            this.startTokenRefresh();
        }
    }
    
    /**
     * Enable/disable debug logging
     */
    setDebug(debug) {
        this.debug = debug;
    }
    
    /**
     * Debug logging
     */
    log(...args) {
        if (this.debug) {
            console.log('[GF CSRF]', ...args);
        }
    }
    
    /**
     * Get token for manual use (e.g., AJAX requests)
     */
    async getTokenForRequest(formId = null) {
        let token = this.getToken(formId);
        
        if (!token) {
            token = await this.generateToken(formId);
        }
        
        return token;
    }
    
    /**
     * Add CSRF token to AJAX request data
     */
    async addTokenToAjaxData(data, formId = null) {
        if (!this.enabled) {
            return data;
        }
        
        const token = await this.getTokenForRequest(formId);
        
        if (token) {
            data.csrf_token = token;
        }
        
        return data;
    }
}

// Initialize CSRF protection
let GFCSRFInstance = null;

// Wait for page load
window.addEventListener('load', function() {
    // Initialize after a short delay to ensure other systems are ready
    setTimeout(() => {
        GFCSRFInstance = new GFCSRFProtection();
        
        // Make it globally accessible
        window.GFCSRF = GFCSRFInstance;
        
        // Trigger event to notify other components
        if (typeof GFEvents !== 'undefined') {
            GFEvents.trigger('csrf.initialized', {
                instance: GFCSRFInstance
            });
        }
    }, 100);
});