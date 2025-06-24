/**
 * Conditional Logic for GF JS Embed
 */
class GFConditionalLogic {
    constructor() {
        this.forms = new Map();
        this.debounceTimers = new Map();
        this.debounceDelay = gfConditionalLogicConfig.debounceDelay || 300;
        this.debug = false;
        
        this.init();
    }
    
    init() {
        // Listen for form registration
        if (typeof GFEvents !== 'undefined') {
            GFEvents.on('form.registered', (eventData) => {
                this.initializeForm(eventData.data.formId, eventData.data.form);
            });
            
            // Listen for field changes
            GFEvents.on('field.changed', (eventData) => {
                this.handleFieldChange(eventData.data.formId, eventData.data.fieldId, eventData.data.value);
            });
            
            // Listen for multi-page navigation
            GFEvents.on('multipage.afterNavigate', (eventData) => {
                this.evaluateAllRules(eventData.data.formId);
            });
        }
    }
    
    /**
     * Initialize conditional logic for a form
     */
    initializeForm(formId, formElement) {
        // Get form configuration
        const formData = this.getFormData(formId);
        if (!formData || !formData.conditional_logic || !formData.conditional_logic.enabled) {
            return;
        }
        
        const formLogic = {
            formId: formId,
            element: formElement,
            rules: formData.conditional_logic.rules,
            dependencies: formData.conditional_logic.dependencies,
            currentStates: formData.conditional_logic.initial_states || {},
            fieldElements: new Map()
        };
        
        this.forms.set(formId, formLogic);
        
        // Cache field elements
        this.cacheFieldElements(formId);
        
        // Apply initial states
        this.applyFieldStates(formId, formLogic.currentStates);
        
        // Set up field monitoring
        this.setupFieldMonitoring(formId);
        
        this.log('Initialized conditional logic for form:', formId);
        
        // Trigger initialization event
        if (typeof GFEvents !== 'undefined') {
            GFEvents.trigger('conditionalLogic.initialized', {
                formId: formId,
                rulesCount: formLogic.rules.length
            });
        }
    }
    
    /**
     * Get form data
     */
    getFormData(formId) {
        // This would typically come from the form initialization
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
     * Cache field elements for faster access
     */
    cacheFieldElements(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Find all field containers
        const fields = form.element.querySelectorAll('.gfield');
        fields.forEach(field => {
            // Extract field ID from element ID (e.g., field_1_2 -> 2)
            const match = field.id.match(/field_\d+_(\d+)/);
            if (match) {
                const fieldId = parseInt(match[1]);
                form.fieldElements.set(fieldId, field);
            }
        });
    }
    
    /**
     * Setup field monitoring
     */
    setupFieldMonitoring(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // For fields without events, set up manual monitoring
        form.element.addEventListener('change', (e) => {
            if (e.target.matches('input, select, textarea')) {
                const fieldId = this.getFieldIdFromElement(e.target);
                if (fieldId && !e.defaultPrevented) {
                    this.handleFieldChange(formId, fieldId, this.getFieldValue(e.target));
                }
            }
        });
        
        // Also monitor input events for text fields
        form.element.addEventListener('input', (e) => {
            if (e.target.matches('input[type="text"], input[type="email"], input[type="number"], textarea')) {
                const fieldId = this.getFieldIdFromElement(e.target);
                if (fieldId && !e.defaultPrevented) {
                    // Debounce text input
                    this.debounceFieldChange(formId, fieldId, this.getFieldValue(e.target));
                }
            }
        });
    }
    
    /**
     * Get field ID from element
     */
    getFieldIdFromElement(element) {
        // Try to get from parent field container
        const fieldContainer = element.closest('.gfield');
        if (fieldContainer) {
            const match = fieldContainer.id.match(/field_\d+_(\d+)/);
            if (match) {
                return parseInt(match[1]);
            }
        }
        
        // Try to get from element name
        if (element.name) {
            const match = element.name.match(/input_(\d+)/);
            if (match) {
                return parseInt(match[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Get field value
     */
    getFieldValue(element) {
        if (element.type === 'checkbox') {
            return element.checked ? element.value : '';
        } else if (element.type === 'radio') {
            // For radio buttons, get the checked value
            const name = element.name;
            const checked = element.form.querySelector(`input[name="${name}"]:checked`);
            return checked ? checked.value : '';
        } else {
            return element.value;
        }
    }
    
    /**
     * Handle field change with debouncing
     */
    debounceFieldChange(formId, fieldId, value) {
        const key = `${formId}-${fieldId}`;
        
        // Clear existing timer
        if (this.debounceTimers.has(key)) {
            clearTimeout(this.debounceTimers.get(key));
        }
        
        // Set new timer
        const timer = setTimeout(() => {
            this.handleFieldChange(formId, fieldId, value);
            this.debounceTimers.delete(key);
        }, this.debounceDelay);
        
        this.debounceTimers.set(key, timer);
    }
    
    /**
     * Handle field change
     */
    handleFieldChange(formId, fieldId, value) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Check if this field affects any other fields
        const dependencies = form.dependencies[fieldId];
        if (!dependencies || dependencies.length === 0) {
            return;
        }
        
        this.log('Field change detected:', fieldId, 'affects:', dependencies);
        
        // Evaluate rules for dependent fields
        this.evaluateFieldRules(formId, dependencies);
    }
    
    /**
     * Evaluate rules for specific fields
     */
    async evaluateFieldRules(formId, fieldIds) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Get current field values
        const fieldValues = this.getFormFieldValues(formId);
        
        // Evaluate via API
        try {
            const response = await fetch(`${gfConditionalLogicConfig.restUrl}conditional-logic/evaluate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': gfConditionalLogicConfig.nonce
                },
                body: JSON.stringify({
                    form_id: formId,
                    field_values: fieldValues
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update states for affected fields only
                const newStates = result.data.states;
                const statesToUpdate = {};
                
                fieldIds.forEach(fieldId => {
                    if (newStates[fieldId]) {
                        statesToUpdate[fieldId] = newStates[fieldId];
                    }
                });
                
                this.applyFieldStates(formId, statesToUpdate);
                
                // Update stored states
                Object.assign(form.currentStates, statesToUpdate);
            }
        } catch (error) {
            console.error('Failed to evaluate conditional logic:', error);
        }
    }
    
    /**
     * Evaluate all rules for a form
     */
    async evaluateAllRules(formId) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        // Get all field IDs that have rules
        const fieldIds = form.rules.map(rule => rule.field_id);
        
        if (fieldIds.length > 0) {
            await this.evaluateFieldRules(formId, fieldIds);
        }
    }
    
    /**
     * Get all field values from form
     */
    getFormFieldValues(formId) {
        const form = this.forms.get(formId);
        if (!form) return {};
        
        const values = {};
        
        // Get all input elements
        const inputs = form.element.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const fieldId = this.getFieldIdFromElement(input);
            if (fieldId) {
                // Handle different input types
                if (input.type === 'radio') {
                    if (input.checked) {
                        values[fieldId] = input.value;
                    }
                } else if (input.type === 'checkbox') {
                    // For checkboxes, collect all checked values
                    if (!values[fieldId]) {
                        values[fieldId] = [];
                    }
                    if (input.checked) {
                        if (Array.isArray(values[fieldId])) {
                            values[fieldId].push(input.value);
                        } else {
                            values[fieldId] = input.value;
                        }
                    }
                } else {
                    values[fieldId] = input.value;
                }
            }
        });
        
        // Convert checkbox arrays to strings if needed
        Object.keys(values).forEach(key => {
            if (Array.isArray(values[key])) {
                values[key] = values[key].join(',');
            }
        });
        
        return values;
    }
    
    /**
     * Apply field states
     */
    applyFieldStates(formId, states) {
        const form = this.forms.get(formId);
        if (!form) return;
        
        Object.entries(states).forEach(([fieldId, state]) => {
            const fieldElement = form.fieldElements.get(parseInt(fieldId));
            if (!fieldElement) return;
            
            // Apply visibility
            if (state.visible !== undefined) {
                if (state.visible) {
                    fieldElement.style.display = '';
                    fieldElement.classList.remove('gf-hidden');
                } else {
                    fieldElement.style.display = 'none';
                    fieldElement.classList.add('gf-hidden');
                }
                
                this.log('Field', fieldId, 'visibility:', state.visible);
            }
            
            // Apply enabled/disabled state
            if (state.enabled !== undefined) {
                const inputs = fieldElement.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.disabled = !state.enabled;
                });
                
                if (state.enabled) {
                    fieldElement.classList.remove('gf-disabled');
                } else {
                    fieldElement.classList.add('gf-disabled');
                }
                
                this.log('Field', fieldId, 'enabled:', state.enabled);
            }
            
            // Apply required state
            if (state.required !== undefined) {
                const inputs = fieldElement.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.required = state.required;
                });
                
                // Update visual indicators
                const label = fieldElement.querySelector('label');
                if (label) {
                    const requiredSpan = label.querySelector('.gfield_required');
                    if (state.required && !requiredSpan) {
                        label.innerHTML += ' <span class="gfield_required">*</span>';
                    } else if (!state.required && requiredSpan) {
                        requiredSpan.remove();
                    }
                }
                
                this.log('Field', fieldId, 'required:', state.required);
            }
        });
        
        // Trigger state change event
        if (typeof GFEvents !== 'undefined') {
            GFEvents.trigger('conditionalLogic.statesChanged', {
                formId: formId,
                states: states
            });
        }
    }
    
    /**
     * Get current field states
     */
    getFieldStates(formId) {
        const form = this.forms.get(formId);
        return form ? form.currentStates : {};
    }
    
    /**
     * Check if field is visible
     */
    isFieldVisible(formId, fieldId) {
        const form = this.forms.get(formId);
        if (!form) return true;
        
        const state = form.currentStates[fieldId];
        return state ? state.visible !== false : true;
    }
    
    /**
     * Check if field is enabled
     */
    isFieldEnabled(formId, fieldId) {
        const form = this.forms.get(formId);
        if (!form) return true;
        
        const state = form.currentStates[fieldId];
        return state ? state.enabled !== false : true;
    }
    
    /**
     * Check if field is required
     */
    isFieldRequired(formId, fieldId) {
        const form = this.forms.get(formId);
        if (!form) return false;
        
        const state = form.currentStates[fieldId];
        return state ? state.required === true : false;
    }
    
    /**
     * Force re-evaluation of all rules
     */
    reevaluate(formId) {
        this.evaluateAllRules(formId);
    }
    
    /**
     * Set debug mode
     */
    setDebug(enabled) {
        this.debug = enabled;
    }
    
    /**
     * Debug logging
     */
    log(...args) {
        if (this.debug) {
            console.log('[GF Conditional Logic]', ...args);
        }
    }
}

// Initialize conditional logic
let GFConditionalLogicInstance = null;

// Wait for page load
window.addEventListener('load', function() {
    // Initialize after a short delay to ensure other systems are ready
    setTimeout(() => {
        GFConditionalLogicInstance = new GFConditionalLogic();
        
        // Make it globally accessible
        window.GFConditionalLogic = GFConditionalLogicInstance;
        
        // Trigger event to notify other components
        if (typeof GFEvents !== 'undefined') {
            GFEvents.trigger('conditionalLogic.loaded', {
                instance: GFConditionalLogicInstance
            });
        }
    }, 100);
});