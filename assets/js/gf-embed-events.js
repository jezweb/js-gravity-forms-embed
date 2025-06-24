/**
 * Gravity Forms JavaScript Event System
 * Provides comprehensive event handling for form interactions
 */
(function(window) {
    'use strict';

    if (typeof window.GravityFormsEmbed === 'undefined') {
        window.GravityFormsEmbed = {};
    }

    /**
     * Event System Class
     */
    class GFEventSystem {
        constructor() {
            this.events = {};
            this.globalListeners = [];
            this.formInstances = new Map();
            this.debug = false;
            
            this.init();
        }

        /**
         * Initialize the event system
         */
        init() {
            this.bindGlobalEvents();
            this.setupMutationObserver();
        }

        /**
         * Enable/disable debug mode
         */
        setDebug(enabled) {
            this.debug = enabled;
            this.log('Debug mode', enabled ? 'enabled' : 'disabled');
        }

        /**
         * Log debug information
         */
        log(...args) {
            if (this.debug) {
                console.log('[GF Events]', ...args);
            }
        }

        /**
         * Register an event listener
         */
        on(event, callback, context = null) {
            if (typeof callback !== 'function') {
                throw new Error('Event callback must be a function');
            }

            if (!this.events[event]) {
                this.events[event] = [];
            }

            const listener = {
                callback,
                context,
                id: this.generateId()
            };

            this.events[event].push(listener);
            this.log('Registered listener for event:', event);

            return listener.id;
        }

        /**
         * Register a one-time event listener
         */
        once(event, callback, context = null) {
            const wrappedCallback = (...args) => {
                this.off(event, wrappedCallback);
                callback.apply(context, args);
            };

            return this.on(event, wrappedCallback, context);
        }

        /**
         * Remove an event listener
         */
        off(event, callbackOrId = null) {
            if (!this.events[event]) {
                return false;
            }

            if (callbackOrId === null) {
                // Remove all listeners for this event
                delete this.events[event];
                this.log('Removed all listeners for event:', event);
                return true;
            }

            const isId = typeof callbackOrId === 'string';
            const originalLength = this.events[event].length;

            this.events[event] = this.events[event].filter(listener => {
                if (isId) {
                    return listener.id !== callbackOrId;
                } else {
                    return listener.callback !== callbackOrId;
                }
            });

            const removed = originalLength !== this.events[event].length;
            if (removed) {
                this.log('Removed listener for event:', event);
            }

            return removed;
        }

        /**
         * Trigger an event
         */
        trigger(event, data = {}, element = null) {
            this.log('Triggering event:', event, data);

            if (!this.events[event]) {
                return false;
            }

            const eventData = {
                type: event,
                timestamp: Date.now(),
                element,
                data,
                preventDefault: false,
                stopPropagation: false
            };

            // Add preventDefault and stopPropagation methods
            eventData.preventDefault = () => {
                eventData.preventDefault = true;
            };

            eventData.stopPropagation = () => {
                eventData.stopPropagation = true;
            };

            for (const listener of this.events[event]) {
                try {
                    listener.callback.call(listener.context, eventData);
                    
                    if (eventData.stopPropagation) {
                        break;
                    }
                } catch (error) {
                    console.error('[GF Events] Error in event listener:', error);
                }
            }

            return !eventData.preventDefault;
        }

        /**
         * Register a form instance
         */
        registerForm(formId, formElement) {
            if (!formElement) {
                this.log('Warning: Trying to register form without element');
                return;
            }

            const instance = {
                id: formId,
                element: formElement,
                fields: new Map(),
                state: 'ready',
                errors: [],
                data: {}
            };

            this.formInstances.set(formId, instance);
            this.bindFormEvents(instance);
            
            this.trigger('form.registered', { formId, formElement }, formElement);
            this.log('Registered form:', formId);
        }

        /**
         * Unregister a form instance
         */
        unregisterForm(formId) {
            if (this.formInstances.has(formId)) {
                const instance = this.formInstances.get(formId);
                this.trigger('form.unregistered', { formId }, instance.element);
                this.formInstances.delete(formId);
                this.log('Unregistered form:', formId);
            }
        }

        /**
         * Get a form instance
         */
        getForm(formId) {
            return this.formInstances.get(formId);
        }

        /**
         * Get all form instances
         */
        getAllForms() {
            return Array.from(this.formInstances.values());
        }

        /**
         * Bind global events
         */
        bindGlobalEvents() {
            // Page lifecycle events
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.trigger('page.ready');
                });
            } else {
                // DOM already loaded
                setTimeout(() => this.trigger('page.ready'), 0);
            }

            window.addEventListener('load', () => {
                this.trigger('page.loaded');
            });

            window.addEventListener('beforeunload', () => {
                this.trigger('page.unload');
            });

            // Global form events
            document.addEventListener('submit', (e) => {
                if (e.target.classList.contains('gf-embed-form')) {
                    this.handleFormSubmit(e);
                }
            });

            document.addEventListener('change', (e) => {
                const form = e.target.closest('.gf-embed-form');
                if (form) {
                    this.handleFieldChange(e, form);
                }
            });

            document.addEventListener('input', (e) => {
                const form = e.target.closest('.gf-embed-form');
                if (form) {
                    this.handleFieldInput(e, form);
                }
            });

            document.addEventListener('focus', (e) => {
                const form = e.target.closest('.gf-embed-form');
                if (form) {
                    this.handleFieldFocus(e, form);
                }
            });

            document.addEventListener('blur', (e) => {
                const form = e.target.closest('.gf-embed-form');
                if (form) {
                    this.handleFieldBlur(e, form);
                }
            });
        }

        /**
         * Bind form-specific events
         */
        bindFormEvents(instance) {
            const { element, id } = instance;

            // Find all form fields
            const fields = element.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                const fieldId = field.id || field.name;
                if (fieldId) {
                    instance.fields.set(fieldId, {
                        element: field,
                        type: field.type || field.tagName.toLowerCase(),
                        value: field.value,
                        valid: true,
                        errors: []
                    });
                }
            });

            this.log('Bound events for form:', id, 'with', fields.length, 'fields');
        }

        /**
         * Handle form submission
         */
        handleFormSubmit(e) {
            const form = e.target;
            const formId = form.dataset.formId || form.id;
            const instance = this.getForm(formId);

            const eventData = {
                formId,
                form,
                instance,
                originalEvent: e
            };

            // Trigger pre-submit event
            const shouldContinue = this.trigger('form.beforeSubmit', eventData, form);
            
            if (!shouldContinue) {
                e.preventDefault();
                this.log('Form submission prevented by beforeSubmit event');
                return;
            }

            // Update form state
            if (instance) {
                instance.state = 'submitting';
                this.trigger('form.submitting', eventData, form);
            }
        }

        /**
         * Handle field change events
         */
        handleFieldChange(e, form) {
            const field = e.target;
            const formId = form.dataset.formId || form.id;
            const instance = this.getForm(formId);
            const fieldId = field.id || field.name;

            const eventData = {
                formId,
                fieldId,
                field,
                value: field.value,
                previousValue: instance?.fields.get(fieldId)?.value,
                form,
                instance,
                originalEvent: e
            };

            // Update field value in instance
            if (instance && instance.fields.has(fieldId)) {
                instance.fields.get(fieldId).value = field.value;
            }

            this.trigger('field.changed', eventData, field);
            this.trigger(`field.${fieldId}.changed`, eventData, field);
        }

        /**
         * Handle field input events
         */
        handleFieldInput(e, form) {
            const field = e.target;
            const formId = form.dataset.formId || form.id;
            const fieldId = field.id || field.name;

            const eventData = {
                formId,
                fieldId,
                field,
                value: field.value,
                form,
                originalEvent: e
            };

            this.trigger('field.input', eventData, field);
            this.trigger(`field.${fieldId}.input`, eventData, field);
        }

        /**
         * Handle field focus events
         */
        handleFieldFocus(e, form) {
            const field = e.target;
            const formId = form.dataset.formId || form.id;
            const fieldId = field.id || field.name;

            const eventData = {
                formId,
                fieldId,
                field,
                form,
                originalEvent: e
            };

            this.trigger('field.focused', eventData, field);
            this.trigger(`field.${fieldId}.focused`, eventData, field);
        }

        /**
         * Handle field blur events
         */
        handleFieldBlur(e, form) {
            const field = e.target;
            const formId = form.dataset.formId || form.id;
            const fieldId = field.id || field.name;

            const eventData = {
                formId,
                fieldId,
                field,
                form,
                originalEvent: e
            };

            this.trigger('field.blurred', eventData, field);
            this.trigger(`field.${fieldId}.blurred`, eventData, field);

            // Trigger validation on blur
            this.validateField(field, form);
        }

        /**
         * Validate a field
         */
        validateField(field, form) {
            const formId = form.dataset.formId || form.id;
            const instance = this.getForm(formId);
            const fieldId = field.id || field.name;

            const eventData = {
                formId,
                fieldId,
                field,
                value: field.value,
                form,
                instance,
                errors: [],
                valid: true
            };

            // Trigger validation event
            this.trigger('field.validating', eventData, field);

            // Basic HTML5 validation
            if (field.checkValidity && !field.checkValidity()) {
                eventData.valid = false;
                eventData.errors.push(field.validationMessage);
            }

            // Update instance field data
            if (instance && instance.fields.has(fieldId)) {
                const fieldData = instance.fields.get(fieldId);
                fieldData.valid = eventData.valid;
                fieldData.errors = eventData.errors;
            }

            // Trigger validation complete event
            this.trigger('field.validated', eventData, field);
        }

        /**
         * Set up mutation observer for dynamic content
         */
        setupMutationObserver() {
            if (typeof MutationObserver === 'undefined') {
                return;
            }

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Check for new forms
                            if (node.classList?.contains('gf-embed-form')) {
                                this.handleDynamicForm(node);
                            }

                            // Check for forms in added subtree
                            const forms = node.querySelectorAll?.('.gf-embed-form');
                            forms?.forEach(form => this.handleDynamicForm(form));
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            this.log('Mutation observer set up for dynamic forms');
        }

        /**
         * Handle dynamically added forms
         */
        handleDynamicForm(formElement) {
            const formId = formElement.dataset.formId || formElement.id;
            if (formId && !this.formInstances.has(formId)) {
                this.registerForm(formId, formElement);
                this.trigger('form.dynamicAdded', { formId }, formElement);
            }
        }

        /**
         * Generate unique ID
         */
        generateId() {
            return 'gf_event_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
        }

        /**
         * Get form data
         */
        getFormData(formId) {
            const instance = this.getForm(formId);
            if (!instance) {
                return null;
            }

            const formData = new FormData(instance.element);
            const data = {};

            for (const [key, value] of formData.entries()) {
                if (data[key]) {
                    // Handle multiple values (checkboxes, etc.)
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }

            return data;
        }

        /**
         * Set field value
         */
        setFieldValue(formId, fieldId, value) {
            const instance = this.getForm(formId);
            if (!instance || !instance.fields.has(fieldId)) {
                return false;
            }

            const fieldData = instance.fields.get(fieldId);
            const field = fieldData.element;

            const previousValue = field.value;
            field.value = value;
            fieldData.value = value;

            // Trigger change event
            const eventData = {
                formId,
                fieldId,
                field,
                value,
                previousValue,
                form: instance.element,
                instance,
                programmatic: true
            };

            this.trigger('field.changed', eventData, field);
            this.trigger(`field.${fieldId}.changed`, eventData, field);

            return true;
        }

        /**
         * Get field value
         */
        getFieldValue(formId, fieldId) {
            const instance = this.getForm(formId);
            if (!instance || !instance.fields.has(fieldId)) {
                return null;
            }

            return instance.fields.get(fieldId).value;
        }

        /**
         * Add form error
         */
        addFormError(formId, error) {
            const instance = this.getForm(formId);
            if (!instance) {
                return false;
            }

            instance.errors.push(error);
            this.trigger('form.error', { formId, error, errors: instance.errors }, instance.element);
            return true;
        }

        /**
         * Clear form errors
         */
        clearFormErrors(formId) {
            const instance = this.getForm(formId);
            if (!instance) {
                return false;
            }

            instance.errors = [];
            this.trigger('form.errorsCleared', { formId }, instance.element);
            return true;
        }

        /**
         * Set form state
         */
        setFormState(formId, state) {
            const instance = this.getForm(formId);
            if (!instance) {
                return false;
            }

            const previousState = instance.state;
            instance.state = state;

            this.trigger('form.stateChanged', {
                formId,
                state,
                previousState,
                instance
            }, instance.element);

            return true;
        }
    }

    // Create global instance
    window.GravityFormsEmbed.Events = new GFEventSystem();

    // Create convenient aliases
    window.GFEvents = window.GravityFormsEmbed.Events;

    // Auto-register forms on page ready
    window.GravityFormsEmbed.Events.on('page.ready', () => {
        const forms = document.querySelectorAll('.gf-embed-form');
        forms.forEach(form => {
            const formId = form.dataset.formId || form.id;
            if (formId) {
                window.GravityFormsEmbed.Events.registerForm(formId, form);
            }
        });
    });

    // Log system ready
    console.log('[GF Events] Event system initialized');

})(window);