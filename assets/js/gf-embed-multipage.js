/**
 * Multi-Page Forms Support for GF JS Embed
 */
class GFMultiPageHandler {
    constructor() {
        this.forms = new Map();
        this.autoSaveEnabled = gfMultiPageConfig.autoSave || true;
        this.autoSaveInterval = gfMultiPageConfig.autoSaveInterval || 30000;
        this.autoSaveTimers = new Map();
        
        this.init();
    }
    
    init() {
        // Listen for form registration
        if (typeof GFEvents !== 'undefined') {
            GFEvents.on('form.registered', (eventData) => {
                this.initializeForm(eventData.data.formId, eventData.data.form);
            });
            
            // Listen for form submission to clear progress
            GFEvents.on('form.submitted', (eventData) => {
                this.clearProgress(eventData.data.formId);
            });
        }
        
        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            this.saveAllProgress();
        });
    }
    
    /**
     * Initialize multi-page form
     */
    initializeForm(formId, formElement) {
        // Check if form has multi-page data
        const formData = this.getFormData(formId);
        if (!formData || !formData.multipage || !formData.multipage.enabled) {
            return;
        }
        
        const multiPageForm = {
            formId: formId,
            element: formElement,
            config: formData.multipage,
            currentPage: 1,
            data: {},
            validation: {},
            initialized: false
        };
        
        this.forms.set(formId, multiPageForm);
        
        // Load saved progress
        this.loadProgress(formId).then(() => {
            this.setupForm(formId);
            this.renderPage(formId, multiPageForm.currentPage);
            
            // Start auto-save if enabled
            if (this.autoSaveEnabled) {
                this.startAutoSave(formId);
            }
        });
    }
    
    /**
     * Get form data
     */
    getFormData(formId) {
        // This would typically come from the form initialization
        // For now, we'll check if the form element has the data
        const form = document.querySelector(`[data-form-id="${formId}"]`);
        if (!form || !form.dataset.formConfig) {
            return null;
        }
        
        try {
            return JSON.parse(form.dataset.formConfig);
        } catch (e) {
            console.error('Failed to parse form config:', e);
            return null;
        }
    }
    
    /**
     * Setup form for multi-page functionality
     */
    setupForm(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Create navigation container
        const navContainer = document.createElement('div');
        navContainer.className = 'gf-multipage-navigation';
        navContainer.innerHTML = this.renderNavigation(form);
        
        // Create progress indicator
        const progressContainer = document.createElement('div');
        progressContainer.className = 'gf-multipage-progress';
        progressContainer.innerHTML = this.renderProgressIndicator(form);
        
        // Insert containers
        form.element.insertBefore(progressContainer, form.element.firstChild);
        form.element.appendChild(navContainer);
        
        // Set up event listeners
        this.setupNavigationEvents(formId);
        this.setupFieldEvents(formId);
        
        form.initialized = true;
        
        // Trigger initialization event
        if (typeof GFEvents !== 'undefined') {
            GFEvents.trigger('multipage.initialized', {
                formId: formId,
                totalPages: form.config.total_pages,
                currentPage: form.currentPage
            });
        }
    }
    
    /**
     * Render navigation buttons
     */
    renderNavigation(form) {
        const isFirstPage = form.currentPage === 1;
        const isLastPage = form.currentPage === form.config.total_pages;
        
        let html = '<div class="gf-page-navigation">';
        
        // Previous button
        if (!isFirstPage) {
            html += `<button type="button" class="gf-previous-button" data-form-id="${form.formId}">
                ${form.config.navigation.previous_button}
            </button>`;
        }
        
        // Next/Submit button
        if (isLastPage) {
            html += `<button type="submit" class="gf-submit-button" data-form-id="${form.formId}">
                ${form.config.navigation.submit_button}
            </button>`;
        } else {
            html += `<button type="button" class="gf-next-button" data-form-id="${form.formId}">
                ${form.config.navigation.next_button}
            </button>`;
        }
        
        html += '</div>';
        
        return html;
    }
    
    /**
     * Render progress indicator
     */
    renderProgressIndicator(form) {
        const progress = (form.currentPage / form.config.total_pages) * 100;
        
        let html = '';
        
        if (form.config.progress_indicator === 'steps') {
            html += '<div class="gf-progress-steps">';
            for (let i = 1; i <= form.config.total_pages; i++) {
                const isActive = i === form.currentPage;
                const isCompleted = i < form.currentPage;
                const className = isActive ? 'active' : (isCompleted ? 'completed' : '');
                const pageName = form.config.page_names[i] || `Step ${i}`;
                
                html += `<div class="gf-progress-step ${className}" data-page="${i}">
                    <span class="step-number">${i}</span>
                    <span class="step-name">${pageName}</span>
                </div>`;
            }
            html += '</div>';
        } else {
            html += `<div class="gf-progress-bar">
                <div class="gf-progress-fill" style="width: ${progress}%"></div>
                <span class="gf-progress-text">Page ${form.currentPage} of ${form.config.total_pages}</span>
            </div>`;
        }
        
        return html;
    }
    
    /**
     * Setup navigation event listeners
     */
    setupNavigationEvents(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Previous button
        const prevButton = form.element.querySelector('.gf-previous-button');
        if (prevButton) {
            prevButton.addEventListener('click', () => {
                this.navigateToPreviousPage(formId);
            });
        }
        
        // Next button
        const nextButton = form.element.querySelector('.gf-next-button');
        if (nextButton) {
            nextButton.addEventListener('click', () => {
                this.navigateToNextPage(formId);
            });
        }
        
        // Progress step clicks
        const progressSteps = form.element.querySelectorAll('.gf-progress-step');
        progressSteps.forEach(step => {
            step.addEventListener('click', (e) => {
                const targetPage = parseInt(e.currentTarget.dataset.page);
                if (targetPage < form.currentPage || form.config.validation.allow_previous_without_validation) {
                    this.navigateToPage(formId, targetPage);
                }
            });
        });
    }
    
    /**
     * Setup field event listeners
     */
    setupFieldEvents(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Listen for field changes to save data
        form.element.addEventListener('change', (e) => {
            if (e.target.matches('input, select, textarea')) {
                this.saveFieldValue(formId, e.target);
            }
        });
        
        // Also listen for input events for text fields
        form.element.addEventListener('input', (e) => {
            if (e.target.matches('input[type="text"], input[type="email"], textarea')) {
                this.saveFieldValue(formId, e.target);
            }
        });
    }
    
    /**
     * Save field value
     */
    saveFieldValue(formId, field) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        const fieldName = field.name || field.id;
        if (!fieldName) return;
        
        let value;
        if (field.type === 'checkbox') {
            value = field.checked;
        } else if (field.type === 'radio') {
            if (field.checked) {
                value = field.value;
            } else {
                return; // Don't save unchecked radio buttons
            }
        } else {
            value = field.value;
        }
        
        form.data[fieldName] = value;
        
        // Trigger field change event
        if (typeof GFEvents !== 'undefined') {
            GFEvents.trigger('multipage.fieldChanged', {
                formId: formId,
                fieldName: fieldName,
                value: value,
                page: form.currentPage
            });
        }
    }
    
    /**
     * Navigate to previous page
     */
    async navigateToPreviousPage(formId) {
        const form = this.forms.get(formId);
        if (!form || form.currentPage <= 1) return;
        
        // Save current page data
        await this.saveCurrentPageData(formId);
        
        // Navigate to previous page
        await this.navigateToPage(formId, form.currentPage - 1);
    }
    
    /**
     * Navigate to next page
     */
    async navigateToNextPage(formId) {
        const form = this.forms.get(formId);
        if (!form || form.currentPage >= form.config.total_pages) return;
        
        // Validate current page if required
        if (form.config.validation.validate_on_navigate) {
            const isValid = await this.validateCurrentPage(formId);
            if (!isValid) {
                return;
            }
        }
        
        // Save current page data
        await this.saveCurrentPageData(formId);
        
        // Navigate to next page
        await this.navigateToPage(formId, form.currentPage + 1);
    }
    
    /**
     * Navigate to specific page
     */
    async navigateToPage(formId, pageNumber) {
        const form = this.forms.get(formId);
        if (!form || pageNumber < 1 || pageNumber > form.config.total_pages) return;
        
        // Trigger before navigate event
        if (typeof GFEvents !== 'undefined') {
            const event = GFEvents.trigger('multipage.beforeNavigate', {
                formId: formId,
                fromPage: form.currentPage,
                toPage: pageNumber
            });
            
            if (event.defaultPrevented) {
                return;
            }
        }
        
        // Update current page
        form.currentPage = pageNumber;
        
        // Render new page
        this.renderPage(formId, pageNumber);
        
        // Update navigation and progress
        this.updateNavigation(formId);
        this.updateProgress(formId);
        
        // Scroll to top of form
        form.element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Trigger after navigate event
        if (typeof GFEvents !== 'undefined') {
            GFEvents.trigger('multipage.afterNavigate', {
                formId: formId,
                currentPage: pageNumber,
                totalPages: form.config.total_pages
            });
        }
    }
    
    /**
     * Render specific page
     */
    renderPage(formId, pageNumber) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        const page = form.config.pages[pageNumber - 1];
        if (!page) return;
        
        // Hide all fields
        const allFields = form.element.querySelectorAll('.gfield');
        allFields.forEach(field => {
            field.style.display = 'none';
        });
        
        // Show fields for current page
        page.fields.forEach(fieldId => {
            const field = form.element.querySelector(`#field_${form.formId}_${fieldId}`);
            if (field) {
                field.style.display = '';
            }
        });
        
        // Restore field values
        this.restoreFieldValues(formId);
    }
    
    /**
     * Restore field values from saved data
     */
    restoreFieldValues(formId) {
        const form = this.forms.get(formId);
        if (!form || !form.data) return;
        
        Object.entries(form.data).forEach(([fieldName, value]) => {
            const field = form.element.querySelector(`[name="${fieldName}"]`);
            if (!field) return;
            
            if (field.type === 'checkbox') {
                field.checked = value;
            } else if (field.type === 'radio') {
                const radio = form.element.querySelector(`[name="${fieldName}"][value="${value}"]`);
                if (radio) radio.checked = true;
            } else {
                field.value = value;
            }
        });
    }
    
    /**
     * Update navigation buttons
     */
    updateNavigation(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        const navContainer = form.element.querySelector('.gf-multipage-navigation');
        if (navContainer) {
            navContainer.innerHTML = this.renderNavigation(form);
            this.setupNavigationEvents(formId);
        }
    }
    
    /**
     * Update progress indicator
     */
    updateProgress(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        const progressContainer = form.element.querySelector('.gf-multipage-progress');
        if (progressContainer) {
            progressContainer.innerHTML = this.renderProgressIndicator(form);
        }
    }
    
    /**
     * Validate current page
     */
    async validateCurrentPage(formId) {
        const form = this.forms.get(formId);
        if (!form) return true;
        
        // Get current page data
        const pageData = this.getCurrentPageData(formId);
        
        try {
            const response = await fetch(`${gfMultiPageConfig.restUrl}form/${form.formId}/validate-page`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': gfMultiPageConfig.nonce
                },
                body: JSON.stringify({
                    form_id: form.formId,
                    page: form.currentPage,
                    data: pageData
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.data.valid) {
                // Clear validation errors
                this.clearValidationErrors(formId);
                return true;
            } else {
                // Show validation errors
                this.showValidationErrors(formId, result.data.errors || {});
                return false;
            }
        } catch (error) {
            console.error('Validation error:', error);
            return true; // Allow navigation on error
        }
    }
    
    /**
     * Get current page data
     */
    getCurrentPageData(formId) {
        const form = this.forms.get(formId);
        if (!form) return {};
        
        const page = form.config.pages[form.currentPage - 1];
        if (!page) return {};
        
        const pageData = {};
        
        // Collect data from current page fields
        page.fields.forEach(fieldId => {
            const fieldElement = form.element.querySelector(`#field_${form.formId}_${fieldId}`);
            if (!fieldElement) return;
            
            const inputs = fieldElement.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const fieldName = input.name || input.id;
                if (!fieldName) return;
                
                if (input.type === 'checkbox') {
                    pageData[fieldId] = input.checked;
                } else if (input.type === 'radio') {
                    if (input.checked) {
                        pageData[fieldId] = input.value;
                    }
                } else {
                    pageData[fieldId] = input.value;
                }
            });
        });
        
        return pageData;
    }
    
    /**
     * Save current page data
     */
    async saveCurrentPageData(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Get current page data
        const pageData = this.getCurrentPageData(formId);
        
        // Merge with existing data
        Object.assign(form.data, pageData);
        
        // Save progress to server
        await this.saveProgress(formId);
    }
    
    /**
     * Save progress to server
     */
    async saveProgress(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        try {
            const response = await fetch(`${gfMultiPageConfig.restUrl}form/${form.formId}/save-progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': gfMultiPageConfig.nonce
                },
                body: JSON.stringify({
                    form_id: form.formId,
                    page: form.currentPage,
                    data: form.data
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Trigger progress saved event
                if (typeof GFEvents !== 'undefined') {
                    GFEvents.trigger('multipage.progressSaved', {
                        formId: formId,
                        currentPage: form.currentPage
                    });
                }
            }
        } catch (error) {
            console.error('Failed to save progress:', error);
        }
    }
    
    /**
     * Load saved progress
     */
    async loadProgress(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        try {
            const response = await fetch(`${gfMultiPageConfig.restUrl}form/${form.formId}/get-progress`, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': gfMultiPageConfig.nonce
                }
            });
            
            const result = await response.json();
            
            if (result.success && result.data.has_progress) {
                const progress = result.data.progress;
                form.currentPage = progress.current_page || 1;
                form.data = progress.data || {};
                
                // Ask user if they want to resume
                if (this.shouldPromptResume(progress)) {
                    const resume = confirm('You have unsaved progress on this form. Would you like to continue where you left off?');
                    if (!resume) {
                        await this.clearProgress(formId);
                        form.currentPage = 1;
                        form.data = {};
                    }
                }
            }
        } catch (error) {
            console.error('Failed to load progress:', error);
        }
    }
    
    /**
     * Should prompt user to resume
     */
    shouldPromptResume(progress) {
        // Don't prompt if progress is very recent (less than 1 minute)
        const lastUpdated = progress.last_updated * 1000; // Convert to milliseconds
        const timeSinceUpdate = Date.now() - lastUpdated;
        return timeSinceUpdate > 60000; // 1 minute
    }
    
    /**
     * Clear progress
     */
    async clearProgress(formId) {
        try {
            await fetch(`${gfMultiPageConfig.restUrl}form/${formId}/clear-progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': gfMultiPageConfig.nonce
                },
                body: JSON.stringify({
                    form_id: formId
                })
            });
        } catch (error) {
            console.error('Failed to clear progress:', error);
        }
    }
    
    /**
     * Show validation errors
     */
    showValidationErrors(formId, errors) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Clear existing errors
        this.clearValidationErrors(formId);
        
        // Show new errors
        Object.entries(errors).forEach(([fieldId, error]) => {
            const fieldElement = form.element.querySelector(`#field_${form.formId}_${fieldId}`);
            if (!fieldElement) return;
            
            fieldElement.classList.add('gfield_error');
            
            // Add error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'validation_message';
            errorDiv.textContent = error.message;
            fieldElement.appendChild(errorDiv);
        });
        
        // Scroll to first error
        const firstError = form.element.querySelector('.gfield_error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    /**
     * Clear validation errors
     */
    clearValidationErrors(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        form.element.querySelectorAll('.gfield_error').forEach(field => {
            field.classList.remove('gfield_error');
            const errorMessage = field.querySelector('.validation_message');
            if (errorMessage) {
                errorMessage.remove();
            }
        });
    }
    
    /**
     * Start auto-save
     */
    startAutoSave(formId) {
        // Clear existing timer
        this.stopAutoSave(formId);
        
        // Set new timer
        const timer = setInterval(() => {
            this.saveProgress(formId);
        }, this.autoSaveInterval);
        
        this.autoSaveTimers.set(formId, timer);
    }
    
    /**
     * Stop auto-save
     */
    stopAutoSave(formId) {
        const timer = this.autoSaveTimers.get(formId);
        if (timer) {
            clearInterval(timer);
            this.autoSaveTimers.delete(formId);
        }
    }
    
    /**
     * Save all progress (called on page unload)
     */
    saveAllProgress() {
        this.forms.forEach((form, formId) => {
            this.saveCurrentPageData(formId);
        });
    }
}

// Initialize multi-page handler
let GFMultiPage = null;

// Wait for page load
window.addEventListener('load', function() {
    // Initialize after a short delay to ensure other systems are ready
    setTimeout(() => {
        GFMultiPage = new GFMultiPageHandler();
        
        // Make it globally accessible
        window.GFMultiPage = GFMultiPage;
        
        // Trigger event to notify other components
        if (typeof GFEvents !== 'undefined') {
            GFEvents.trigger('multipage.loaded', {
                instance: GFMultiPage
            });
        }
    }, 100);
});