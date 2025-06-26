/**
 * Enhanced Analytics Tracking for Gravity Forms JS Embed
 */
(function() {
    'use strict';
    
    const GFEmbedAnalytics = {
        // Configuration
        config: {
            trackingEnabled: true,
            trackingInterval: 1000, // Send updates every second
            apiEndpoint: null, // Will be set dynamically
            sessionTimeout: 30 * 60 * 1000, // 30 minutes
            respectDoNotTrack: true,
            anonymizeIPs: true,
            consentRequired: false
        },
        
        // State
        state: {
            formId: null,
            startTime: Date.now(),
            fieldTimes: {},
            fieldInteractions: {},
            pendingEvents: [],
            currentPage: 1,
            pageStartTime: Date.now()
        },
        
        /**
         * Initialize analytics tracking
         */
        init(formId, formElement, options = {}) {
            if (!this.config.trackingEnabled) return;
            
            // Set API endpoint from GravityFormsEmbed if available
            if (window.GravityFormsEmbed && window.GravityFormsEmbed.apiUrl) {
                this.config.apiEndpoint = window.GravityFormsEmbed.apiUrl + '/analytics/track';
            } else {
                console.warn('GF Embed Analytics: API URL not available, analytics disabled');
                return;
            }
            
            // Store API key if provided
            if (options.apiKey) {
                this.state.apiKey = options.apiKey;
            }
            
            // Check privacy settings
            if (!this.checkPrivacyConsent()) {
                console.info('GF Embed Analytics: Tracking disabled due to privacy settings');
                return;
            }
            
            this.state.formId = formId;
            this.attachEventListeners(formElement);
            this.startSession();
            
            // Send pending events periodically
            setInterval(() => this.flushEvents(), this.config.trackingInterval);
            
            // Track page unload
            window.addEventListener('beforeunload', () => this.flushEvents());
        },
        
        /**
         * Start tracking session
         */
        startSession() {
            // Session is handled server-side
            this.state.startTime = Date.now();
        },
        
        /**
         * Attach event listeners to form
         */
        attachEventListeners(formElement) {
            // Field focus/blur tracking
            formElement.addEventListener('focusin', (e) => {
                const field = e.target.closest('.gfield');
                if (field) {
                    const fieldId = this.getFieldId(field);
                    if (fieldId) {
                        this.onFieldFocus(fieldId);
                    }
                }
            });
            
            formElement.addEventListener('focusout', (e) => {
                const field = e.target.closest('.gfield');
                if (field) {
                    const fieldId = this.getFieldId(field);
                    if (fieldId) {
                        this.onFieldBlur(fieldId);
                    }
                }
            });
            
            // Field change tracking
            formElement.addEventListener('change', (e) => {
                const field = e.target.closest('.gfield');
                if (field) {
                    const fieldId = this.getFieldId(field);
                    if (fieldId) {
                        this.onFieldChange(fieldId);
                    }
                }
            });
            
            // Field input tracking (for text fields)
            let inputTimeout;
            formElement.addEventListener('input', (e) => {
                const field = e.target.closest('.gfield');
                if (field) {
                    const fieldId = this.getFieldId(field);
                    if (fieldId) {
                        clearTimeout(inputTimeout);
                        inputTimeout = setTimeout(() => {
                            this.onFieldInput(fieldId);
                        }, 500);
                    }
                }
            });
            
            // Error tracking
            formElement.addEventListener('gfFieldError', (e) => {
                if (e.detail && e.detail.fieldId) {
                    this.onFieldError(e.detail.fieldId, e.detail.errorType, e.detail.errorMessage);
                }
            });
            
            // Page navigation (multi-page forms)
            formElement.addEventListener('gfPageChange', (e) => {
                if (e.detail) {
                    this.onPageChange(e.detail.previousPage, e.detail.currentPage);
                }
            });
            
            // Form submission
            formElement.addEventListener('submit', (e) => {
                this.onFormSubmit();
            });
        },
        
        /**
         * Get field ID from element
         */
        getFieldId(fieldElement) {
            const classes = fieldElement.className.split(' ');
            const fieldClass = classes.find(c => c.startsWith('field_'));
            if (fieldClass) {
                return fieldClass.replace('field_', '');
            }
            
            // Fallback to data attribute
            return fieldElement.dataset.fieldId || null;
        },
        
        /**
         * Handle field focus
         */
        onFieldFocus(fieldId) {
            this.state.fieldTimes[fieldId] = {
                startTime: Date.now(),
                totalTime: this.state.fieldTimes[fieldId]?.totalTime || 0
            };
            
            // Track interaction
            this.trackEvent('field_interaction', {
                field_id: fieldId,
                interaction_type: 'focus'
            });
        },
        
        /**
         * Handle field blur
         */
        onFieldBlur(fieldId) {
            if (this.state.fieldTimes[fieldId]?.startTime) {
                const duration = Date.now() - this.state.fieldTimes[fieldId].startTime;
                this.state.fieldTimes[fieldId].totalTime += duration;
                delete this.state.fieldTimes[fieldId].startTime;
                
                // Track time spent
                this.trackEvent('field_interaction', {
                    field_id: fieldId,
                    interaction_type: 'blur',
                    time_spent: Math.round(duration / 1000) // Convert to seconds
                });
            }
        },
        
        /**
         * Handle field change
         */
        onFieldChange(fieldId) {
            this.trackEvent('field_interaction', {
                field_id: fieldId,
                interaction_type: 'change'
            });
        },
        
        /**
         * Handle field input
         */
        onFieldInput(fieldId) {
            // Increment interaction count
            if (!this.state.fieldInteractions[fieldId]) {
                this.state.fieldInteractions[fieldId] = 0;
            }
            this.state.fieldInteractions[fieldId]++;
            
            // Track every 5 inputs to reduce noise
            if (this.state.fieldInteractions[fieldId] % 5 === 0) {
                this.trackEvent('field_interaction', {
                    field_id: fieldId,
                    interaction_type: 'input'
                });
            }
        },
        
        /**
         * Handle field error
         */
        onFieldError(fieldId, errorType, errorMessage) {
            this.trackEvent('field_error', {
                field_id: fieldId,
                error_type: errorType || 'validation',
                error_message: errorMessage || ''
            });
        },
        
        /**
         * Handle page change
         */
        onPageChange(previousPage, currentPage) {
            // Track time on previous page
            const timeOnPage = Date.now() - this.state.pageStartTime;
            
            this.trackEvent('page_progression', {
                page_number: previousPage,
                time_spent: Math.round(timeOnPage / 1000),
                completed: true
            });
            
            // Start tracking new page
            this.state.currentPage = currentPage;
            this.state.pageStartTime = Date.now();
            
            this.trackEvent('page_progression', {
                page_number: currentPage,
                time_spent: 0,
                completed: false
            });
        },
        
        /**
         * Handle form submission
         */
        onFormSubmit() {
            // Track final page time
            if (this.state.currentPage) {
                const timeOnPage = Date.now() - this.state.pageStartTime;
                this.trackEvent('page_progression', {
                    page_number: this.state.currentPage,
                    time_spent: Math.round(timeOnPage / 1000),
                    completed: true
                });
            }
            
            // Flush all pending events
            this.flushEvents();
        },
        
        /**
         * Track an event
         */
        trackEvent(eventType, data) {
            this.state.pendingEvents.push({
                event_type: eventType,
                form_id: this.state.formId,
                data: data,
                timestamp: Date.now()
            });
            
            // Flush if we have too many pending events
            if (this.state.pendingEvents.length >= 10) {
                this.flushEvents();
            }
        },
        
        /**
         * Send pending events to server
         */
        async flushEvents() {
            if (this.state.pendingEvents.length === 0) return;
            
            const events = [...this.state.pendingEvents];
            this.state.pendingEvents = [];
            
            // Send events in batches
            for (const event of events) {
                try {
                    await this.sendEvent(event);
                } catch (error) {
                    console.error('Failed to send analytics event:', error);
                    // Re-add failed event to pending
                    this.state.pendingEvents.push(event);
                }
            }
        },
        
        /**
         * Send event to server
         */
        async sendEvent(event) {
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            
            // Add API key if available
            if (this.state.apiKey) {
                headers['X-API-Key'] = this.state.apiKey;
            }
            
            const response = await fetch(this.config.apiEndpoint, {
                method: 'POST',
                headers: headers,
                credentials: 'include',
                body: JSON.stringify(event)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        },
        
        /**
         * Enable/disable tracking
         */
        setTrackingEnabled(enabled) {
            this.config.trackingEnabled = enabled;
        },
        
        /**
         * Check privacy consent
         */
        checkPrivacyConsent() {
            // Check Do Not Track header
            if (this.config.respectDoNotTrack && 
                (navigator.doNotTrack === '1' || window.doNotTrack === '1')) {
                return false;
            }
            
            // Check for consent cookie/localStorage (GDPR compliance)
            if (this.config.consentRequired) {
                const consent = localStorage.getItem('gf-analytics-consent') || 
                               this.getCookie('gf-analytics-consent');
                if (consent !== 'true') {
                    return false;
                }
            }
            
            // Check for global privacy flag
            if (window.gfEmbedPrivacyOptOut === true) {
                return false;
            }
            
            return true;
        },
        
        /**
         * Get cookie value
         */
        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },
        
        /**
         * Set analytics consent
         */
        setConsent(consent) {
            localStorage.setItem('gf-analytics-consent', consent ? 'true' : 'false');
            
            // Set cookie for 1 year
            const expires = new Date();
            expires.setFullYear(expires.getFullYear() + 1);
            document.cookie = `gf-analytics-consent=${consent ? 'true' : 'false'}; expires=${expires.toUTCString()}; path=/`;
            
            // Update config
            this.config.trackingEnabled = consent;
        },
        
        /**
         * Get analytics summary
         */
        getSummary() {
            const totalTime = Date.now() - this.state.startTime;
            const fieldTimes = {};
            
            // Calculate total time per field
            for (const [fieldId, times] of Object.entries(this.state.fieldTimes)) {
                let total = times.totalTime;
                if (times.startTime) {
                    // Field is currently focused
                    total += Date.now() - times.startTime;
                }
                fieldTimes[fieldId] = Math.round(total / 1000); // Convert to seconds
            }
            
            return {
                totalTime: Math.round(totalTime / 1000),
                fieldTimes: fieldTimes,
                fieldInteractions: this.state.fieldInteractions,
                currentPage: this.state.currentPage
            };
        }
    };
    
    // Expose to global scope
    window.GFEmbedAnalytics = GFEmbedAnalytics;
})();