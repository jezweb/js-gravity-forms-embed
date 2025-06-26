/**
 * Gravity Forms JavaScript Embed SDK
 * 
 * @package GravityFormsJSEmbed
 * @version 0.1.0
 */
(function() {
    'use strict';
    
    // Gravity Forms JavaScript Embed SDK
    window.GravityFormsEmbed = {
        version: '0.1.0',
        apiUrl: '', // Will be set dynamically
        forms: {},
        translations: {},
        config: {},
        _initialized: false,
        
        /**
         * Set API URL from script source
         */
        setApiUrl: function() {
            if (this.apiUrl) return; // Already set
            
            // Try to extract from current script
            const scripts = document.querySelectorAll('script[src*="gf-js-embed"]');
            if (scripts.length > 0) {
                const scriptSrc = scripts[scripts.length - 1].src;
                const url = new URL(scriptSrc);
                this.apiUrl = url.origin + '/wp-json/gf-embed/v1';
            }
        },
        
        /**
         * Initialize the SDK
         */
        init: function() {
            if (this._initialized) return;
            this._initialized = true;
            
            // Set API URL
            this.setApiUrl();
            // Auto-initialize forms based on data attributes
            document.addEventListener('DOMContentLoaded', () => {
                // Find all containers with data-gf-form attribute
                const containers = document.querySelectorAll('[data-gf-form]');
                containers.forEach(container => {
                    const formId = container.getAttribute('data-gf-form');
                    const apiKey = container.getAttribute('data-gf-api-key');
                    const theme = container.getAttribute('data-gf-theme');
                    this.loadForm(formId, container, { apiKey, theme });
                });
                
                // Check for form ID in script URL
                const currentScript = document.currentScript || 
                    document.querySelector('script[src*="gf-js-embed"]');
                if (currentScript) {
                    const urlParams = new URLSearchParams(currentScript.src.split('?')[1]);
                    const formId = urlParams.get('form');
                    if (formId) {
                        const container = document.getElementById('gf-form-' + formId);
                        if (container) {
                            this.loadForm(formId, container);
                        }
                    }
                }
            });
        },
        
        /**
         * Load a form
         */
        loadForm: function(formId, container, options = {}) {
            // Show loading state
            this.showLoading(container);
            
            // Build request headers
            const headers = {
                'Content-Type': 'application/json'
            };
            
            if (options.apiKey) {
                headers['X-API-Key'] = options.apiKey;
            }
            
            // Fetch form data
            fetch(this.apiUrl + '/form/' + formId, {
                method: 'GET',
                headers: headers,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.forms[formId] = data.form;
                    this.renderForm(data.form, container, options);
                    this.loadAssets(formId, options);
                } else {
                    this.showError(container, data.message);
                }
            })
            .catch(error => {
                console.error('Error loading form:', error);
                this.showError(container, this.translations.error || 'Error loading form');
            });
        },
        
        /**
         * Show loading state
         */
        showLoading: function(container) {
            container.innerHTML = '<div class="gf-loading">' + 
                (this.translations.loading || 'Loading form...') + '</div>';
        },
        
        /**
         * Show error message
         */
        showError: function(container, message) {
            container.innerHTML = '<div class="gf-error">' + message + '</div>';
        },
        
        /**
         * Render form
         */
        renderForm: function(formData, container, options = {}) {
            const formHtml = this.buildFormHtml(formData, options);
            container.innerHTML = formHtml;
            
            // Store options on container for later use
            container._gfOptions = options;
            
            // Initialize form functionality
            this.initializeForm(formData, container);
        },
        
        /**
         * Build form HTML
         */
        buildFormHtml: function(formData, options = {}) {
            let formClasses = 'gf-embedded-form';
            if (options.theme) {
                formClasses += ' theme-' + options.theme;
            }
            if (formData.cssClass) {
                formClasses += ' ' + formData.cssClass;
            }
            
            let html = '<form id="gform_' + formData.id + '" class="' + formClasses + '" novalidate>';
            
            // Add title
            if (formData.title && formData.displayTitle) {
                html += '<h3 class="gf-form-title">' + this.escapeHtml(formData.title) + '</h3>';
            }
            
            // Add description
            if (formData.description && formData.displayDescription) {
                html += '<div class="gf-form-description">' + this.escapeHtml(formData.description) + '</div>';
            }
            
            // Add hidden fields
            html += '<input type="hidden" name="form_id" value="' + formData.id + '">';
            
            // Multi-page form progress
            if (formData.pagination) {
                html += this.buildPaginationProgress(formData);
            }
            
            // Render fields
            html += '<div class="gf-fields">';
            
            let currentPage = 1;
            formData.fields.forEach(field => {
                if (field.type === 'page') {
                    if (currentPage > 1) {
                        html += '</div>'; // Close previous page
                    }
                    html += '<div class="gf-page" data-page="' + currentPage + '" style="display:' + 
                           (currentPage === 1 ? 'block' : 'none') + ';">';
                    currentPage++;
                } else {
                    html += this.renderField(field, formData);
                }
            });
            
            // Close last page if multi-page
            if (currentPage > 1) {
                html += '</div>';
            }
            
            html += '</div>';
            
            // Submit button or page navigation
            if (formData.pagination) {
                html += this.buildPageNavigation(formData);
            } else {
                html += '<div class="gf-form-footer">';
                html += '<button type="submit" class="gf-button gf-button-submit">' + 
                        this.escapeHtml(formData.button.text || this.translations.submit || 'Submit') + 
                        '</button>';
                html += '</div>';
            }
            
            html += '</form>';
            
            return html;
        },
        
        /**
         * Render individual field
         */
        renderField: function(field, formData) {
            // Build CSS classes
            let cssClasses = ['gfield', 'field_' + field.id, 'gf-field', 'gf-field-' + field.type];
            
            // Add custom CSS classes
            if (field.cssClass) {
                cssClasses.push(field.cssClass);
            }
            
            // Add size class
            if (field.size) {
                cssClasses.push('gfield_size_' + field.size);
            }
            
            let html = '<div class="' + cssClasses.join(' ') + '" data-field-id="' + field.id + '">';
            
            // Label
            if (field.label && field.type !== 'html' && field.type !== 'section') {
                html += '<label class="gfield_label" for="input_' + field.id + '">';
                html += this.escapeHtml(field.label);
                if (field.isRequired) {
                    html += ' <span class="gf-required">*</span>';
                }
                html += '</label>';
            }
            
            // Field input container
            const subLabelPlacement = field.subLabelPlacement || formData.subLabelPlacement || 'below';
            html += '<div class="ginput_container ginput_container_' + field.type + ' gf-sublabel-' + subLabelPlacement + '">';
            html += this.renderFieldInput(field, formData);
            html += '</div>';
            
            // Description
            if (field.description && field.descriptionPlacement !== 'above') {
                html += '<div class="gf-field-description">' + 
                       this.escapeHtml(field.description) + '</div>';
            }
            
            html += '</div>';
            return html;
        },
        
        /**
         * Render field input based on type
         */
        renderFieldInput: function(field, formData) {
            const inputId = 'input_' + field.id;
            const inputName = 'input_' + field.id;
            let html = '';
            
            switch(field.type) {
                case 'text':
                case 'email':
                case 'phone':
                case 'url':
                    const inputSubLabel = field.inputSubLabel || '';
                    const inputSubLabelPlacement = field.subLabelPlacement || formData?.subLabelPlacement || 'below';
                    
                    // Add sublabel above if placement is 'above'
                    if (inputSubLabel && inputSubLabelPlacement === 'above' && inputSubLabelPlacement !== 'hidden') {
                        html += '<label for="' + inputId + '" class="gf-sublabel">';
                        html += this.escapeHtml(inputSubLabel);
                        html += '</label>';
                    }
                    
                    html += '<input type="' + field.type + '" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input" ' +
                          (field.placeholder ? 'placeholder="' + this.escapeHtml(field.placeholder) + '" ' : '') +
                          (field.defaultValue ? 'value="' + this.escapeHtml(field.defaultValue) + '" ' : '') +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    
                    // Add sublabel below if placement is 'below'
                    if (inputSubLabel && inputSubLabelPlacement === 'below' && inputSubLabelPlacement !== 'hidden') {
                        html += '<label for="' + inputId + '" class="gf-sublabel">';
                        html += this.escapeHtml(inputSubLabel);
                        html += '</label>';
                    }
                    break;
                    
                case 'email_confirm':
                    html = this.renderEmailConfirmField(field, formData);
                    break;
                    
                case 'number':
                    html = '<input type="number" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input" ' +
                          (field.rangeMin !== undefined ? 'min="' + field.rangeMin + '" ' : '') +
                          (field.rangeMax !== undefined ? 'max="' + field.rangeMax + '" ' : '') +
                          (field.placeholder ? 'placeholder="' + this.escapeHtml(field.placeholder) + '" ' : '') +
                          (field.defaultValue ? 'value="' + this.escapeHtml(field.defaultValue) + '" ' : '') +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    break;
                    
                case 'textarea':
                    const textareaSubLabel = field.inputSubLabel || '';
                    const subLabelPlacement = field.subLabelPlacement || formData?.subLabelPlacement || 'below';
                    
                    // Add sublabel above if placement is 'above'
                    if (textareaSubLabel && subLabelPlacement === 'above' && subLabelPlacement !== 'hidden') {
                        html += '<label for="' + inputId + '" class="gf-sublabel">';
                        html += this.escapeHtml(textareaSubLabel);
                        html += '</label>';
                    }
                    
                    html += '<textarea ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input" ' +
                          (field.placeholder ? 'placeholder="' + this.escapeHtml(field.placeholder) + '" ' : '') +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">' +
                          (field.defaultValue || '') + '</textarea>';
                    
                    // Add sublabel below if placement is 'below'
                    if (textareaSubLabel && subLabelPlacement === 'below' && subLabelPlacement !== 'hidden') {
                        html += '<label for="' + inputId + '" class="gf-sublabel">';
                        html += this.escapeHtml(textareaSubLabel);
                        html += '</label>';
                    }
                    break;
                    
                case 'select':
                    html = '<select ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input" ' +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    
                    if (field.placeholder) {
                        html += '<option value="">' + this.escapeHtml(field.placeholder) + '</option>';
                    }
                    
                    if (field.choices) {
                        field.choices.forEach(choice => {
                            html += '<option value="' + this.escapeHtml(choice.value) + '"' +
                                   (choice.isSelected ? ' selected' : '') + '>' +
                                   this.escapeHtml(choice.text) + '</option>';
                        });
                    }
                    html += '</select>';
                    break;
                    
                case 'multiselect':
                    html = '<select ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '[]" ' +
                          'class="gf-input" ' +
                          'multiple ' +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    
                    if (field.choices) {
                        field.choices.forEach(choice => {
                            html += '<option value="' + this.escapeHtml(choice.value) + '"' +
                                   (choice.isSelected ? ' selected' : '') + '>' +
                                   this.escapeHtml(choice.text) + '</option>';
                        });
                    }
                    html += '</select>';
                    break;
                    
                case 'radio':
                    if (field.choices) {
                        html = '<div class="gf-radio-choices">';
                        field.choices.forEach((choice, index) => {
                            const choiceId = inputId + '_' + index;
                            html += '<div class="gf-radio-choice">';
                            html += '<input type="radio" ' +
                                   'id="' + choiceId + '" ' +
                                   'name="' + inputName + '" ' +
                                   'value="' + this.escapeHtml(choice.value) + '" ' +
                                   (choice.isSelected ? 'checked ' : '') +
                                   (field.isRequired ? 'required ' : '') +
                                   'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                            html += '<label for="' + choiceId + '">' + 
                                   this.escapeHtml(choice.text) + '</label>';
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    break;
                    
                case 'checkbox':
                    if (field.choices) {
                        html = '<div class="gf-checkbox-choices">';
                        field.choices.forEach((choice, index) => {
                            const choiceId = inputId + '_' + index;
                            html += '<div class="gf-checkbox-choice">';
                            html += '<input type="checkbox" ' +
                                   'id="' + choiceId + '" ' +
                                   'name="' + inputName + '[]" ' +
                                   'value="' + this.escapeHtml(choice.value) + '" ' +
                                   (choice.isSelected ? 'checked ' : '') +
                                   'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                            html += '<label for="' + choiceId + '">' + 
                                   this.escapeHtml(choice.text) + '</label>';
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    break;
                    
                case 'date':
                    html = '<input type="date" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input gf-datepicker" ' +
                          (field.placeholder ? 'placeholder="' + this.escapeHtml(field.placeholder) + '" ' : '') +
                          (field.defaultValue ? 'value="' + this.escapeHtml(field.defaultValue) + '" ' : '') +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    break;
                    
                case 'time':
                    html = '<input type="time" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input" ' +
                          (field.defaultValue ? 'value="' + this.escapeHtml(field.defaultValue) + '" ' : '') +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    break;
                    
                case 'fileupload':
                    html = '<input type="file" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input gf-file-input" ' +
                          (field.allowedExtensions ? 'accept="' + field.allowedExtensions + '" ' : '') +
                          (field.multipleFiles ? 'multiple ' : '') +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    
                    if (field.maxFileSize) {
                        html += '<small class="gf-file-size-limit">' + 
                               'Max file size: ' + this.formatFileSize(field.maxFileSize) + 
                               '</small>';
                    }
                    break;
                    
                case 'hidden':
                    html = '<input type="hidden" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'value="' + this.escapeHtml(field.defaultValue || '') + '">';
                    break;
                    
                case 'html':
                    html = '<div class="gf-html-content">' + field.content + '</div>';
                    break;
                    
                case 'section':
                    html = '<h4 class="gf-section-title">' + this.escapeHtml(field.label) + '</h4>';
                    if (field.description) {
                        html += '<div class="gf-section-description">' + 
                               this.escapeHtml(field.description) + '</div>';
                    }
                    break;
                    
                case 'list':
                    html = this.renderListField(field);
                    break;
                    
                case 'signature':
                    html = this.renderSignatureField(field);
                    break;
                    
                case 'calculation':
                    html = '<input type="text" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input gf-calculation" ' +
                          'readonly ' +
                          'data-formula="' + this.escapeHtml(field.formula || '') + '" ' +
                          'value="0">';
                    break;
                    
                case 'website':
                    html = '<input type="url" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input" ' +
                          (field.placeholder ? 'placeholder="' + this.escapeHtml(field.placeholder) + '" ' : '') +
                          (field.defaultValue ? 'value="' + this.escapeHtml(field.defaultValue) + '" ' : '') +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    break;
                    
                case 'password':
                    html = '<input type="password" ' +
                          'id="' + inputId + '" ' +
                          'name="' + inputName + '" ' +
                          'class="gf-input" ' +
                          (field.placeholder ? 'placeholder="' + this.escapeHtml(field.placeholder) + '" ' : '') +
                          (field.isRequired ? 'required ' : '') +
                          'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
                    break;
                    
                case 'name':
                    html = this.renderNameField(field, formData);
                    break;
                    
                default:
                    console.warn('Unsupported field type:', field.type);
                    html = '<div class="gf-unsupported-field">Field type not supported: ' + 
                          field.type + '</div>';
            }
            
            return html;
        },
        
        /**
         * Initialize form functionality
         */
        initializeForm: function(formData, container) {
            const form = container.querySelector('form');
            if (!form) return;
            
            // Store form data
            form._gfData = formData;
            
            // Add start time for bot detection
            const startTimeInput = document.createElement('input');
            startTimeInput.type = 'hidden';
            startTimeInput.name = 'gf_embed_start_time';
            startTimeInput.value = Math.floor(Date.now() / 1000);
            form.appendChild(startTimeInput);
            
            // Initialize analytics tracking
            if (typeof GFEmbedAnalytics !== 'undefined') {
                const options = container._gfOptions || {};
                GFEmbedAnalytics.init(formData.id, form, options);
            }
            
            // Initialize validation
            this.initializeValidation(form, formData);
            
            // Initialize conditional logic
            if (formData.conditionalLogic || this.hasFieldConditionalLogic(formData)) {
                this.initializeConditionalLogic(form, formData);
            }
            
            // Initialize file uploads
            this.initializeFileUploads(form);
            
            // Initialize date pickers
            this.initializeDatePickers(form);
            
            // Initialize calculations
            this.initializeCalculations(form);
            
            // Initialize list fields
            this.initializeListFields(form);
            
            // Initialize signature fields
            this.initializeSignatureFields(form);
            
            // Initialize multi-page navigation
            if (formData.pagination) {
                this.initializeMultiPage(form, formData);
            }
            
            // Attach submit handler
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitForm(formData.id, form);
            });
            
            // Trigger form ready event
            this.triggerEvent('gfEmbedFormReady', { formId: formData.id, form: form });
        },
        
        /**
         * Submit form
         */
        submitForm: function(formId, formElement) {
            // Validate form
            if (!this.validateForm(formElement)) {
                return;
            }
            
            const formData = new FormData(formElement);
            const submitButton = formElement.querySelector('.gf-button-submit');
            
            // Disable submit button
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = this.translations.submitting || 'Submitting...';
            }
            
            // Add API key if present
            const container = formElement.closest('[data-gf-form]');
            const apiKey = container ? container.getAttribute('data-gf-api-key') : null;
            
            const headers = {};
            if (apiKey) {
                headers['X-API-Key'] = apiKey;
            }
            
            // Submit form
            fetch(this.apiUrl + '/submit/' + formId, {
                method: 'POST',
                body: formData,
                headers: headers,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.handleSubmissionSuccess(formElement, data);
                } else {
                    this.handleSubmissionError(formElement, data);
                }
            })
            .catch(error => {
                console.error('Submission error:', error);
                this.showFormError(formElement, this.translations.network_error || 
                                 'Network error. Please try again.');
                
                // Re-enable submit button
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = this.forms[formId].button.text || 
                                            this.translations.submit || 'Submit';
                }
            });
        },
        
        /**
         * Handle successful submission
         */
        handleSubmissionSuccess: function(formElement, data) {
            const confirmation = data.confirmation;
            
            // Trigger submission success event
            this.triggerEvent('gfEmbedSubmitSuccess', { 
                form: formElement, 
                entryId: data.entry_id,
                confirmation: confirmation 
            });
            
            // Handle confirmation
            if (confirmation.type === 'message') {
                formElement.innerHTML = '<div class="gf-confirmation">' + 
                                      confirmation.message + '</div>';
            } else if (confirmation.type === 'redirect') {
                window.location.href = confirmation.url;
            } else if (confirmation.type === 'page') {
                // Handle page confirmation (WordPress page)
                window.location.href = confirmation.url;
            }
        },
        
        /**
         * Handle submission error
         */
        handleSubmissionError: function(formElement, data) {
            const formId = formElement._gfData.id;
            
            // Show validation errors
            if (data.errors) {
                this.showValidationErrors(formElement, data.errors);
            }
            
            // Show general error message
            if (data.message) {
                this.showFormError(formElement, data.message);
            }
            
            // Re-enable submit button
            const submitButton = formElement.querySelector('.gf-button-submit');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = this.forms[formId].button.text || 
                                        this.translations.submit || 'Submit';
            }
            
            // Trigger error event
            this.triggerEvent('gfEmbedSubmitError', { form: formElement, errors: data.errors });
        },
        
        /**
         * Load form assets
         */
        loadAssets: function(formId, options = {}) {
            const url = new URL(this.apiUrl + '/assets/' + formId);
            if (options.theme) {
                url.searchParams.append('theme', options.theme);
            }
            
            const headers = {
                'Content-Type': 'application/json'
            };
            
            if (options.apiKey) {
                headers['X-API-Key'] = options.apiKey;
            }
            
            fetch(url.toString(), {
                method: 'GET',
                headers: headers,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                // Add CSS
                if (data.css) {
                    const style = document.createElement('style');
                    style.textContent = data.css;
                    style.setAttribute('data-gf-form-id', formId);
                    document.head.appendChild(style);
                }
                
                // Store translations
                if (data.translations) {
                    this.translations = Object.assign(this.translations, data.translations);
                }
                
                // Store config
                if (data.config) {
                    this.config = Object.assign(this.config, data.config);
                }
            })
            .catch(error => {
                console.error('Error loading assets:', error);
            });
        },
        
        /**
         * Initialize form validation
         */
        initializeValidation: function(form, formData) {
            // Add custom validation for specific field types
            form.querySelectorAll('input[type="email"]').forEach(input => {
                input.addEventListener('blur', () => {
                    if (input.value && !this.isValidEmail(input.value)) {
                        this.showFieldError(input, this.translations.invalid_email || 
                                          'Please enter a valid email');
                    } else {
                        this.clearFieldError(input);
                    }
                });
            });
            
            // Add more field-specific validation as needed
        },
        
        /**
         * Validate form before submission
         */
        validateForm: function(form) {
            let isValid = true;
            const errors = {};
            
            // Clear previous errors
            form.querySelectorAll('.gf-field-error').forEach(field => {
                field.classList.remove('gf-field-error');
            });
            form.querySelectorAll('.gf-error-message').forEach(error => {
                error.remove();
            });
            
            // Validate required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!this.isFieldVisible(field)) {
                    return; // Skip hidden fields
                }
                
                const fieldContainer = field.closest('.gf-field');
                const fieldId = fieldContainer ? fieldContainer.getAttribute('data-field-id') : null;
                
                if (!this.isFieldValid(field)) {
                    isValid = false;
                    if (fieldContainer) {
                        fieldContainer.classList.add('gf-field-error');
                    }
                    
                    const errorMessage = field.getAttribute('data-error-message') || 
                                       this.translations.required || 
                                       'This field is required';
                    this.showFieldError(field, errorMessage);
                    
                    if (fieldId) {
                        errors[fieldId] = errorMessage;
                    }
                }
            });
            
            // Custom field validation
            form.querySelectorAll('input[type="email"]').forEach(field => {
                if (field.value && !this.isValidEmail(field.value)) {
                    isValid = false;
                    const fieldContainer = field.closest('.gf-field');
                    if (fieldContainer) {
                        fieldContainer.classList.add('gf-field-error');
                    }
                    this.showFieldError(field, this.translations.invalid_email || 
                                      'Please enter a valid email');
                }
            });
            
            if (!isValid) {
                // Scroll to first error
                const firstError = form.querySelector('.gf-field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                // Trigger validation error event
                this.triggerEvent('gfEmbedValidationError', { form: form, errors: errors });
            }
            
            return isValid;
        },
        
        /**
         * Check if field is valid
         */
        isFieldValid: function(field) {
            const type = field.type;
            const value = field.value.trim();
            
            if (type === 'checkbox' || type === 'radio') {
                const name = field.name;
                return field.form.querySelector(`input[name="${name}"]:checked`) !== null;
            }
            
            return value !== '';
        },
        
        /**
         * Check if field is visible
         */
        isFieldVisible: function(field) {
            const fieldContainer = field.closest('.gf-field');
            if (!fieldContainer) return true;
            
            return fieldContainer.style.display !== 'none' && 
                   fieldContainer.offsetParent !== null;
        },
        
        /**
         * Show field error
         */
        showFieldError: function(field, message) {
            const fieldContainer = field.closest('.gf-field');
            if (!fieldContainer) return;
            
            // Track error in analytics
            const fieldId = fieldContainer.getAttribute('data-field-id');
            if (fieldId && typeof GFEmbedAnalytics !== 'undefined') {
                const form = field.closest('form');
                const formId = form._gfData ? form._gfData.id : null;
                if (formId) {
                    // Dispatch custom event for analytics to catch
                    const event = new CustomEvent('gfFieldError', {
                        detail: {
                            fieldId: fieldId,
                            errorType: 'validation',
                            errorMessage: message
                        }
                    });
                    form.dispatchEvent(event);
                }
            }
            
            // Remove existing error
            const existingError = fieldContainer.querySelector('.gf-error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Add new error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'gf-error-message';
            errorDiv.textContent = message;
            
            // Insert after field or field group
            const insertAfter = field.closest('.gf-radio-choices, .gf-checkbox-choices') || field;
            insertAfter.parentNode.insertBefore(errorDiv, insertAfter.nextSibling);
        },
        
        /**
         * Clear field error
         */
        clearFieldError: function(field) {
            const fieldContainer = field.closest('.gf-field');
            if (!fieldContainer) return;
            
            fieldContainer.classList.remove('gf-field-error');
            const error = fieldContainer.querySelector('.gf-error-message');
            if (error) {
                error.remove();
            }
        },
        
        /**
         * Show form-level error
         */
        showFormError: function(form, message) {
            // Remove existing form error
            const existingError = form.querySelector('.gf-form-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Add new error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'gf-form-error';
            errorDiv.innerHTML = '<p>' + message + '</p>';
            
            // Insert at beginning of form
            form.insertBefore(errorDiv, form.firstChild);
            
            // Scroll to error
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
        
        /**
         * Show validation errors
         */
        showValidationErrors: function(form, errors) {
            Object.keys(errors).forEach(fieldId => {
                const fieldContainer = form.querySelector(`[data-field-id="${fieldId}"]`);
                if (fieldContainer) {
                    fieldContainer.classList.add('gf-field-error');
                    const field = fieldContainer.querySelector('input, textarea, select');
                    if (field) {
                        this.showFieldError(field, errors[fieldId]);
                    }
                }
            });
        },
        
        /**
         * Initialize conditional logic
         */
        initializeConditionalLogic: function(form, formData) {
            // Set up change handlers for all fields
            form.addEventListener('change', (e) => {
                this.evaluateConditionalLogic(form, formData);
            });
            
            form.addEventListener('keyup', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    this.evaluateConditionalLogic(form, formData);
                }
            });
            
            // Initial evaluation
            this.evaluateConditionalLogic(form, formData);
        },
        
        /**
         * Evaluate conditional logic
         */
        evaluateConditionalLogic: function(form, formData) {
            formData.fields.forEach(field => {
                if (!field.conditionalLogic) return;
                
                const fieldContainer = form.querySelector(`[data-field-id="${field.id}"]`);
                if (!fieldContainer) return;
                
                const shouldShow = this.evaluateRules(form, field.conditionalLogic);
                
                if (shouldShow) {
                    fieldContainer.style.display = '';
                    // Re-enable required validation
                    const inputs = fieldContainer.querySelectorAll('input, textarea, select');
                    inputs.forEach(input => {
                        if (input.hasAttribute('data-was-required')) {
                            input.setAttribute('required', '');
                        }
                    });
                } else {
                    fieldContainer.style.display = 'none';
                    // Disable required validation for hidden fields
                    const inputs = fieldContainer.querySelectorAll('input, textarea, select');
                    inputs.forEach(input => {
                        if (input.hasAttribute('required')) {
                            input.setAttribute('data-was-required', 'true');
                            input.removeAttribute('required');
                        }
                    });
                }
            });
        },
        
        /**
         * Evaluate conditional logic rules
         */
        evaluateRules: function(form, conditionalLogic) {
            const { actionType, logicType, rules } = conditionalLogic;
            
            let result;
            if (logicType === 'all') {
                result = rules.every(rule => this.evaluateRule(form, rule));
            } else {
                result = rules.some(rule => this.evaluateRule(form, rule));
            }
            
            return actionType === 'show' ? result : !result;
        },
        
        /**
         * Evaluate single rule
         */
        evaluateRule: function(form, rule) {
            const field = form.querySelector(`[name="input_${rule.fieldId}"]`);
            if (!field) return false;
            
            const fieldValue = this.getFieldValue(field);
            const ruleValue = rule.value;
            
            switch (rule.operator) {
                case 'is':
                    return fieldValue == ruleValue;
                case 'isnot':
                    return fieldValue != ruleValue;
                case 'greater_than':
                    return parseFloat(fieldValue) > parseFloat(ruleValue);
                case 'less_than':
                    return parseFloat(fieldValue) < parseFloat(ruleValue);
                case 'contains':
                    return fieldValue.indexOf(ruleValue) !== -1;
                case 'starts_with':
                    return fieldValue.indexOf(ruleValue) === 0;
                case 'ends_with':
                    return fieldValue.indexOf(ruleValue) === fieldValue.length - ruleValue.length;
                default:
                    return false;
            }
        },
        
        /**
         * Get field value
         */
        getFieldValue: function(field) {
            if (field.type === 'checkbox' || field.type === 'radio') {
                const checked = field.form.querySelector(`[name="${field.name}"]:checked`);
                return checked ? checked.value : '';
            }
            return field.value;
        },
        
        /**
         * Check if form has field conditional logic
         */
        hasFieldConditionalLogic: function(formData) {
            return formData.fields.some(field => field.conditionalLogic);
        },
        
        /**
         * Initialize file uploads
         */
        initializeFileUploads: function(form) {
            form.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', (e) => {
                    this.validateFileUpload(e.target);
                });
            });
        },
        
        /**
         * Validate file upload
         */
        validateFileUpload: function(input) {
            const files = input.files;
            if (!files.length) return;
            
            const field = input.closest('.gf-field');
            const maxSize = input.getAttribute('data-max-size');
            const allowedTypes = input.getAttribute('accept');
            
            for (let file of files) {
                // Check file size
                if (maxSize && file.size > parseInt(maxSize)) {
                    this.showFieldError(input, this.translations.file_too_large || 
                                      'File size exceeds limit');
                    input.value = '';
                    return;
                }
                
                // Check file type
                if (allowedTypes) {
                    const allowed = allowedTypes.split(',').map(type => type.trim());
                    const fileExt = '.' + file.name.split('.').pop().toLowerCase();
                    const fileMime = file.type;
                    
                    if (!allowed.some(type => type === fileExt || type === fileMime)) {
                        this.showFieldError(input, this.translations.invalid_file_type || 
                                          'File type not allowed');
                        input.value = '';
                        return;
                    }
                }
            }
            
            this.clearFieldError(input);
        },
        
        /**
         * Initialize date pickers
         */
        initializeDatePickers: function(form) {
            // Basic HTML5 date input is used by default
            // Can be enhanced with a date picker library if needed
            form.querySelectorAll('.gf-datepicker').forEach(input => {
                // Add any date picker initialization here
            });
        },
        
        /**
         * Build pagination progress
         */
        buildPaginationProgress: function(formData) {
            if (!formData.pagination || formData.pagination.type !== 'steps') {
                return '';
            }
            
            let html = '<div class="gf-page-steps">';
            const pages = formData.pagination.pages || [];
            
            pages.forEach((page, index) => {
                html += '<div class="gf-page-step" data-page="' + (index + 1) + '">';
                html += '<span class="gf-page-step-number">' + (index + 1) + '</span>';
                html += '<span class="gf-page-step-label">' + this.escapeHtml(page) + '</span>';
                html += '</div>';
            });
            
            html += '</div>';
            return html;
        },
        
        /**
         * Build page navigation
         */
        buildPageNavigation: function(formData) {
            let html = '<div class="gf-page-buttons">';
            html += '<button type="button" class="gf-button gf-button-previous" style="display:none;">' +
                   this.translations.previous + '</button>';
            html += '<button type="button" class="gf-button gf-button-next">' +
                   this.translations.next + '</button>';
            html += '<button type="submit" class="gf-button gf-button-submit" style="display:none;">' +
                   this.escapeHtml(formData.button.text || this.translations.submit) + '</button>';
            html += '</div>';
            return html;
        },
        
        /**
         * Initialize multi-page form
         */
        initializeMultiPage: function(form, formData) {
            const pages = form.querySelectorAll('.gf-page');
            const steps = form.querySelectorAll('.gf-page-step');
            const prevButton = form.querySelector('.gf-button-previous');
            const nextButton = form.querySelector('.gf-button-next');
            const submitButton = form.querySelector('.gf-button-submit');
            
            let currentPage = 1;
            const totalPages = pages.length;
            
            // Next button handler
            nextButton.addEventListener('click', () => {
                if (this.validateCurrentPage(form, currentPage)) {
                    if (currentPage < totalPages) {
                        this.goToPage(currentPage + 1);
                    }
                }
            });
            
            // Previous button handler
            prevButton.addEventListener('click', () => {
                if (currentPage > 1) {
                    this.goToPage(currentPage - 1);
                }
            });
            
            // Go to page function
            const goToPage = (pageNum) => {
                // Track page change for analytics
                if (typeof GFEmbedAnalytics !== 'undefined') {
                    const event = new CustomEvent('gfPageChange', {
                        detail: {
                            previousPage: currentPage,
                            currentPage: pageNum
                        }
                    });
                    form.dispatchEvent(event);
                }
                
                // Hide all pages
                pages.forEach(page => page.style.display = 'none');
                
                // Show current page
                const currentPageEl = form.querySelector(`.gf-page[data-page="${pageNum}"]`);
                if (currentPageEl) {
                    currentPageEl.style.display = 'block';
                }
                
                // Update step indicators
                steps.forEach(step => {
                    const stepPage = parseInt(step.getAttribute('data-page'));
                    if (stepPage === pageNum) {
                        step.classList.add('active');
                    } else if (stepPage < pageNum) {
                        step.classList.add('completed');
                        step.classList.remove('active');
                    } else {
                        step.classList.remove('active', 'completed');
                    }
                });
                
                // Update navigation buttons
                prevButton.style.display = pageNum > 1 ? '' : 'none';
                
                if (pageNum === totalPages) {
                    nextButton.style.display = 'none';
                    submitButton.style.display = '';
                } else {
                    nextButton.style.display = '';
                    submitButton.style.display = 'none';
                }
                
                currentPage = pageNum;
                
                // Scroll to form top
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };
            
            this.goToPage = goToPage;
            
            // Initialize first page
            goToPage(1);
        },
        
        /**
         * Validate current page
         */
        validateCurrentPage: function(form, pageNum) {
            const currentPageEl = form.querySelector(`.gf-page[data-page="${pageNum}"]`);
            if (!currentPageEl) return true;
            
            let isValid = true;
            
            // Validate only fields in current page
            currentPageEl.querySelectorAll('[required]').forEach(field => {
                if (!this.isFieldValid(field)) {
                    isValid = false;
                    const fieldContainer = field.closest('.gf-field');
                    if (fieldContainer) {
                        fieldContainer.classList.add('gf-field-error');
                    }
                    this.showFieldError(field, this.translations.required || 'This field is required');
                }
            });
            
            if (!isValid) {
                const firstError = currentPageEl.querySelector('.gf-field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            
            return isValid;
        },
        
        /**
         * Utility: Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        /**
         * Utility: Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        /**
         * Utility: Validate email
         */
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        /**
         * Render list field
         */
        renderListField: function(field) {
            const inputId = 'input_' + field.id;
            const inputName = 'input_' + field.id;
            let html = '<div class="gf-list-field" data-field-id="' + field.id + '">';
            
            if (field.enableColumns && field.choices) {
                // Multi-column list
                html += '<table class="gf-list-table">';
                html += '<thead><tr>';
                field.choices.forEach(column => {
                    html += '<th>' + this.escapeHtml(column.text) + '</th>';
                });
                html += '<th class="gf-list-actions"></th></tr></thead>';
                html += '<tbody class="gf-list-rows">';
                html += this.renderListRow(field, 0);
                html += '</tbody></table>';
            } else {
                // Single column list
                html += '<div class="gf-list-items">';
                html += '<input type="text" class="gf-list-item" name="' + inputName + '[]" placeholder="Enter item...">';
                html += '</div>';
            }
            
            html += '<button type="button" class="gf-list-add-row">+ Add Row</button>';
            html += '</div>';
            
            return html;
        },
        
        /**
         * Render list row
         */
        renderListRow: function(field, rowIndex) {
            const inputName = 'input_' + field.id;
            let html = '<tr class="gf-list-row">';
            
            if (field.choices) {
                field.choices.forEach((column, colIndex) => {
                    html += '<td>';
                    html += '<input type="text" name="' + inputName + '[' + rowIndex + '][' + colIndex + ']" class="gf-list-input">';
                    html += '</td>';
                });
            }
            
            html += '<td class="gf-list-actions">';
            html += '<button type="button" class="gf-list-delete-row">×</button>';
            html += '</td></tr>';
            
            return html;
        },
        
        /**
         * Render signature field
         */
        renderSignatureField: function(field) {
            const inputId = 'input_' + field.id;
            const inputName = 'input_' + field.id;
            
            let html = '<div class="gf-signature-container">';
            html += '<canvas id="' + inputId + '_canvas" class="gf-signature-canvas" width="400" height="200"></canvas>';
            html += '<input type="hidden" id="' + inputId + '" name="' + inputName + '" value="">';
            html += '<div class="gf-signature-actions">';
            html += '<button type="button" class="gf-signature-clear">Clear</button>';
            html += '</div>';
            html += '</div>';
            
            return html;
        },
        
        /**
         * Render name field with Gravity Forms structure
         */
        renderNameField: function(field, formData) {
            const fieldId = field.id;
            let html = '<div class="gf-name-field ginput_complex">';
            
            // Determine which name parts to show based on field settings
            const nameFormat = field.nameFormat || 'normal'; // normal, simple, extended
            const inputs = field.inputs || [];
            const subLabelPlacement = field.subLabelPlacement || formData?.subLabelPlacement || 'below';
            
            if (nameFormat === 'simple') {
                // Simple format - single input
                html += '<input type="text" ';
                html += 'id="input_' + fieldId + '" ';
                html += 'name="input_' + fieldId + '" ';
                html += 'class="gf-input" ';
                html += (field.placeholder ? 'placeholder="' + this.escapeHtml(field.placeholder) + '" ' : '');
                html += (field.defaultValue ? 'value="' + this.escapeHtml(field.defaultValue) + '" ' : '');
                html += (field.isRequired ? 'required ' : '');
                html += 'aria-required="' + (field.isRequired ? 'true' : 'false') + '">';
            } else {
                // Normal or extended format - multiple inputs
                const nameFields = [];
                
                // Default name parts for normal format
                if (nameFormat === 'normal' || inputs.length === 0) {
                    nameFields.push(
                        { id: fieldId + '.3', label: 'First', sublabel: 'First', isHidden: false },
                        { id: fieldId + '.6', label: 'Last', sublabel: 'Last', isHidden: false }
                    );
                } else if (nameFormat === 'extended') {
                    // Extended format includes prefix, first, middle, last, suffix
                    nameFields.push(
                        { id: fieldId + '.2', label: 'Prefix', sublabel: 'Prefix', isHidden: false },
                        { id: fieldId + '.3', label: 'First', sublabel: 'First', isHidden: false },
                        { id: fieldId + '.4', label: 'Middle', sublabel: 'Middle', isHidden: false },
                        { id: fieldId + '.6', label: 'Last', sublabel: 'Last', isHidden: false },
                        { id: fieldId + '.8', label: 'Suffix', sublabel: 'Suffix', isHidden: false }
                    );
                } else {
                    // Use custom inputs if provided
                    inputs.forEach(input => {
                        if (!input.isHidden) {
                            nameFields.push({
                                id: input.id,
                                label: input.label,
                                sublabel: input.sublabel || input.label,
                                isHidden: input.isHidden
                            });
                        }
                    });
                }
                
                // Render each name part
                nameFields.forEach((namePart, index) => {
                    const inputId = 'input_' + namePart.id;
                    const inputName = 'input_' + namePart.id;
                    const spanClass = 'name_' + namePart.label.toLowerCase().replace(' ', '_');
                    
                    html += '<span class="' + spanClass + ' ginput_' + spanClass + ' gf-name-part">';
                    
                    // Add sublabel above if placement is 'above'
                    if (subLabelPlacement === 'above' && subLabelPlacement !== 'hidden') {
                        html += '<label for="' + inputId + '" class="gf-sublabel">';
                        html += this.escapeHtml(namePart.sublabel);
                        html += '</label>';
                    }
                    
                    // Add the input
                    if (namePart.id.includes('.2')) {
                        // Prefix - make it a dropdown
                        html += '<select ';
                        html += 'id="' + inputId + '" ';
                        html += 'name="' + inputName + '" ';
                        html += 'class="gf-input gf-name-prefix" ';
                        html += (field.isRequired && index === 0 ? 'required ' : '');
                        html += 'aria-label="' + this.escapeHtml(namePart.sublabel) + '">';
                        html += '<option value=""></option>';
                        html += '<option value="Mr.">Mr.</option>';
                        html += '<option value="Mrs.">Mrs.</option>';
                        html += '<option value="Ms.">Ms.</option>';
                        html += '<option value="Dr.">Dr.</option>';
                        html += '<option value="Prof.">Prof.</option>';
                        html += '</select>';
                    } else {
                        // Regular text input
                        html += '<input type="text" ';
                        html += 'id="' + inputId + '" ';
                        html += 'name="' + inputName + '" ';
                        html += 'class="gf-input gf-name-' + namePart.label.toLowerCase() + '" ';
                        html += (field.isRequired && (namePart.label === 'First' || namePart.label === 'Last') ? 'required ' : '');
                        html += 'aria-label="' + this.escapeHtml(namePart.sublabel) + '">';
                    }
                    
                    // Add sublabel below if placement is 'below'
                    if (subLabelPlacement === 'below' && subLabelPlacement !== 'hidden') {
                        html += '<label for="' + inputId + '" class="gf-sublabel">';
                        html += this.escapeHtml(namePart.sublabel);
                        html += '</label>';
                    }
                    
                    html += '</span>';
                });
            }
            
            html += '</div>';
            return html;
        },
        
        /**
         * Render email confirmation field
         */
        renderEmailConfirmField: function(field, formData) {
            const fieldId = field.id;
            const subLabelPlacement = field.subLabelPlacement || formData?.subLabelPlacement || 'below';
            let html = '<div class="gf-email-confirm-field ginput_complex">';
            
            // Get inputs configuration
            const inputs = field.inputs || [
                { id: fieldId + '', label: 'Enter Email', sublabel: 'Enter Email' },
                { id: fieldId + '.2', label: 'Confirm Email', sublabel: 'Confirm Email' }
            ];
            
            inputs.forEach((input, index) => {
                const inputId = 'input_' + input.id;
                const inputName = 'input_' + input.id;
                const spanClass = index === 0 ? 'ginput_left' : 'ginput_right';
                
                html += '<span class="' + spanClass + ' gf-email-confirm-part">';
                
                // Add sublabel above if placement is 'above'
                if (subLabelPlacement === 'above' && subLabelPlacement !== 'hidden') {
                    html += '<label for="' + inputId + '" class="gf-sublabel">';
                    html += this.escapeHtml(input.sublabel || input.label);
                    html += '</label>';
                }
                
                // Add the email input
                html += '<input type="email" ';
                html += 'id="' + inputId + '" ';
                html += 'name="' + inputName + '" ';
                html += 'class="gf-input" ';
                html += (field.isRequired ? 'required ' : '');
                html += 'aria-label="' + this.escapeHtml(input.sublabel || input.label) + '" ';
                html += (input.placeholder ? 'placeholder="' + this.escapeHtml(input.placeholder) + '" ' : '');
                if (index === 1) {
                    // Add data attribute to link confirmation field to primary email field
                    html += 'data-confirms="input_' + fieldId + '" ';
                }
                html += '>';
                
                // Add sublabel below if placement is 'below'
                if (subLabelPlacement === 'below' && subLabelPlacement !== 'hidden') {
                    html += '<label for="' + inputId + '" class="gf-sublabel">';
                    html += this.escapeHtml(input.sublabel || input.label);
                    html += '</label>';
                }
                
                html += '</span>';
            });
            
            html += '</div>';
            return html;
        },
        
        /**
         * Initialize calculations
         */
        initializeCalculations: function(form) {
            const calculationFields = form.querySelectorAll('.gf-calculation');
            
            calculationFields.forEach(calcField => {
                const formula = calcField.getAttribute('data-formula');
                if (formula) {
                    // Set up change handlers for input fields
                    form.addEventListener('input', () => {
                        this.updateCalculation(form, calcField, formula);
                    });
                    form.addEventListener('change', () => {
                        this.updateCalculation(form, calcField, formula);
                    });
                    
                    // Initial calculation
                    this.updateCalculation(form, calcField, formula);
                }
            });
        },
        
        /**
         * Update calculation field
         */
        updateCalculation: function(form, calcField, formula) {
            try {
                // Replace field placeholders with actual values
                let processedFormula = formula;
                const fieldMatches = formula.match(/{[^}]+}/g);
                
                if (fieldMatches) {
                    fieldMatches.forEach(match => {
                        const fieldRef = match.slice(1, -1); // Remove { }
                        const fieldValue = this.getCalculationFieldValue(form, fieldRef);
                        processedFormula = processedFormula.replace(match, fieldValue || '0');
                    });
                }
                
                // Basic formula evaluation (you might want to use a safer eval alternative)
                const result = this.evaluateFormula(processedFormula);
                calcField.value = isNaN(result) ? '0' : result.toFixed(2);
                
            } catch (error) {
                console.error('Calculation error:', error);
                calcField.value = '0';
            }
        },
        
        /**
         * Get field value for calculations
         */
        getCalculationFieldValue: function(form, fieldRef) {
            // Simple field reference (e.g., "1" for field ID 1)
            if (/^\d+$/.test(fieldRef)) {
                const field = form.querySelector('[name="input_' + fieldRef + '"]');
                if (field) {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        const checked = form.querySelector('[name="' + field.name + '"]:checked');
                        return checked ? parseFloat(checked.value) || 0 : 0;
                    }
                    return parseFloat(field.value) || 0;
                }
            }
            
            return 0;
        },
        
        /**
         * Safe formula evaluation
         */
        evaluateFormula: function(formula) {
            // Basic math operations only
            const cleanFormula = formula.replace(/[^0-9+\-*/.() ]/g, '');
            
            try {
                // Use Function constructor for safer evaluation than eval
                return new Function('return ' + cleanFormula)();
            } catch (error) {
                return 0;
            }
        },
        
        /**
         * Initialize list fields
         */
        initializeListFields: function(form) {
            form.querySelectorAll('.gf-list-field').forEach(listField => {
                const addButton = listField.querySelector('.gf-list-add-row');
                const table = listField.querySelector('.gf-list-table tbody');
                
                if (addButton && table) {
                    const fieldId = listField.getAttribute('data-field-id');
                    const field = form._gfData.fields.find(f => f.id == fieldId);
                    
                    addButton.addEventListener('click', () => {
                        const rowCount = table.querySelectorAll('.gf-list-row').length;
                        const newRow = this.renderListRow(field, rowCount);
                        table.insertAdjacentHTML('beforeend', newRow);
                        
                        // Add delete handler to new row
                        const deleteBtn = table.lastElementChild.querySelector('.gf-list-delete-row');
                        if (deleteBtn) {
                            deleteBtn.addEventListener('click', (e) => {
                                e.target.closest('tr').remove();
                            });
                        }
                    });
                    
                    // Add delete handlers to existing rows
                    table.querySelectorAll('.gf-list-delete-row').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.target.closest('tr').remove();
                        });
                    });
                }
            });
        },
        
        /**
         * Initialize signature fields
         */
        initializeSignatureFields: function(form) {
            form.querySelectorAll('.gf-signature-container').forEach(container => {
                const canvas = container.querySelector('canvas');
                const hiddenInput = container.querySelector('input[type="hidden"]');
                const clearButton = container.querySelector('.gf-signature-clear');
                
                if (canvas && hiddenInput) {
                    const ctx = canvas.getContext('2d');
                    let isDrawing = false;
                    let lastX = 0;
                    let lastY = 0;
                    
                    // Set up drawing
                    ctx.strokeStyle = '#000';
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    
                    // Mouse events
                    canvas.addEventListener('mousedown', (e) => {
                        isDrawing = true;
                        [lastX, lastY] = this.getCanvasCoordinates(canvas, e);
                    });
                    
                    canvas.addEventListener('mousemove', (e) => {
                        if (!isDrawing) return;
                        const [currentX, currentY] = this.getCanvasCoordinates(canvas, e);
                        
                        ctx.beginPath();
                        ctx.moveTo(lastX, lastY);
                        ctx.lineTo(currentX, currentY);
                        ctx.stroke();
                        
                        [lastX, lastY] = [currentX, currentY];
                        
                        // Update hidden input with base64 data
                        hiddenInput.value = canvas.toDataURL();
                    });
                    
                    canvas.addEventListener('mouseup', () => {
                        isDrawing = false;
                    });
                    
                    // Touch events for mobile
                    canvas.addEventListener('touchstart', (e) => {
                        e.preventDefault();
                        const touch = e.touches[0];
                        const mouseEvent = new MouseEvent('mousedown', {
                            clientX: touch.clientX,
                            clientY: touch.clientY
                        });
                        canvas.dispatchEvent(mouseEvent);
                    });
                    
                    canvas.addEventListener('touchmove', (e) => {
                        e.preventDefault();
                        const touch = e.touches[0];
                        const mouseEvent = new MouseEvent('mousemove', {
                            clientX: touch.clientX,
                            clientY: touch.clientY
                        });
                        canvas.dispatchEvent(mouseEvent);
                    });
                    
                    canvas.addEventListener('touchend', (e) => {
                        e.preventDefault();
                        const mouseEvent = new MouseEvent('mouseup', {});
                        canvas.dispatchEvent(mouseEvent);
                    });
                    
                    // Clear button
                    if (clearButton) {
                        clearButton.addEventListener('click', () => {
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                            hiddenInput.value = '';
                        });
                    }
                }
            });
        },
        
        /**
         * Get canvas coordinates from mouse event
         */
        getCanvasCoordinates: function(canvas, e) {
            const rect = canvas.getBoundingClientRect();
            return [
                e.clientX - rect.left,
                e.clientY - rect.top
            ];
        },
        
        /**
         * Trigger custom event
         */
        triggerEvent: function(eventName, detail) {
            const event = new CustomEvent(eventName, {
                detail: detail,
                bubbles: true,
                cancelable: true
            });
            document.dispatchEvent(event);
        }
    };
    
    // Initialize
    GravityFormsEmbed.init();
})();