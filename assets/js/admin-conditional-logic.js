/**
 * Admin interface for Conditional Logic
 */
(function($) {
    'use strict';
    
    let currentFormId = null;
    let currentRules = [];
    let availableFields = [];
    
    $(document).ready(function() {
        
        // Initialize conditional logic settings if on the form settings page
        if ($('#conditional-logic-settings').length) {
            initConditionalLogicSettings();
        }
        
        // Rule management
        $('#add-conditional-rule').on('click', addNewRule);
        $(document).on('click', '.remove-rule', removeRule);
        $(document).on('click', '.add-condition', addCondition);
        $(document).on('click', '.remove-condition', removeCondition);
        
        // Field changes
        $(document).on('change', '.rule-action', updateRulePreview);
        $(document).on('change', '.logic-type', updateRulePreview);
        $(document).on('change', '.condition-field', updateConditionValueField);
        $(document).on('change', '.condition-operator', updateConditionValueField);
        
        // Save rules
        $('#save-conditional-logic').on('click', saveConditionalLogic);
        
        // Test rules
        $('#test-conditional-logic').on('click', testConditionalLogic);
        
        // Import/Export
        $('#export-rules').on('click', exportRules);
        $('#import-rules').on('click', function() {
            $('#import-rules-file').click();
        });
        $('#import-rules-file').on('change', importRules);
        
    });
    
    /**
     * Initialize conditional logic settings
     */
    function initConditionalLogicSettings() {
        // Get form ID from page
        currentFormId = $('#form-id').val() || getFormIdFromUrl();
        
        if (!currentFormId) {
            showError('No form ID found');
            return;
        }
        
        // Load form fields
        loadFormFields();
        
        // Load existing rules
        loadExistingRules();
    }
    
    /**
     * Get form ID from URL
     */
    function getFormIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id');
    }
    
    /**
     * Load form fields
     */
    function loadFormFields() {
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_form_fields',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: currentFormId
            },
            success: function(response) {
                if (response.success) {
                    availableFields = response.data.fields;
                    updateFieldSelects();
                } else {
                    showError('Failed to load form fields');
                }
            },
            error: function() {
                showError('Failed to load form fields');
            }
        });
    }
    
    /**
     * Load existing rules
     */
    function loadExistingRules() {
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_conditional_logic',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: currentFormId
            },
            success: function(response) {
                if (response.success) {
                    currentRules = response.data.rules || [];
                    renderRules();
                }
            }
        });
    }
    
    /**
     * Add new rule
     */
    function addNewRule() {
        const newRule = {
            id: 'rule_' + Date.now(),
            field_id: '',
            action: 'show',
            logic_type: 'all',
            conditions: [
                {
                    field_id: '',
                    operator: 'is',
                    value: ''
                }
            ]
        };
        
        currentRules.push(newRule);
        renderRules();
    }
    
    /**
     * Remove rule
     */
    function removeRule() {
        const ruleId = $(this).data('rule-id');
        currentRules = currentRules.filter(rule => rule.id !== ruleId);
        renderRules();
    }
    
    /**
     * Add condition to rule
     */
    function addCondition() {
        const ruleId = $(this).data('rule-id');
        const rule = currentRules.find(r => r.id === ruleId);
        
        if (rule) {
            rule.conditions.push({
                field_id: '',
                operator: 'is',
                value: ''
            });
            renderRules();
        }
    }
    
    /**
     * Remove condition from rule
     */
    function removeCondition() {
        const ruleId = $(this).data('rule-id');
        const conditionIndex = $(this).data('condition-index');
        const rule = currentRules.find(r => r.id === ruleId);
        
        if (rule && rule.conditions.length > 1) {
            rule.conditions.splice(conditionIndex, 1);
            renderRules();
        }
    }
    
    /**
     * Render all rules
     */
    function renderRules() {
        const $container = $('#conditional-logic-rules');
        
        if (currentRules.length === 0) {
            $container.html('<p>No conditional logic rules configured. Click "Add Rule" to create one.</p>');
            return;
        }
        
        let html = '';
        
        currentRules.forEach((rule, ruleIndex) => {
            html += renderRule(rule, ruleIndex);
        });
        
        $container.html(html);
        
        // Update field selects with available fields
        updateFieldSelects();
        
        // Restore values
        restoreRuleValues();
    }
    
    /**
     * Render single rule
     */
    function renderRule(rule, index) {
        let html = `
        <div class="conditional-rule" data-rule-id="${rule.id}">
            <div class="rule-header">
                <h3>Rule ${index + 1}</h3>
                <button type="button" class="button-secondary remove-rule" data-rule-id="${rule.id}">Remove Rule</button>
            </div>
            
            <div class="rule-target">
                <label>Target Field:</label>
                <select class="rule-target-field" data-rule-id="${rule.id}">
                    <option value="">Select Field...</option>
                </select>
                
                <label>Action:</label>
                <select class="rule-action" data-rule-id="${rule.id}">
                    <option value="show">Show Field</option>
                    <option value="hide">Hide Field</option>
                    <option value="enable">Enable Field</option>
                    <option value="disable">Disable Field</option>
                    <option value="require">Make Required</option>
                    <option value="unrequire">Make Optional</option>
                </select>
            </div>
            
            <div class="rule-conditions">
                <h4>When:</h4>
                <div class="logic-type">
                    <label>
                        <input type="radio" name="logic_type_${rule.id}" class="logic-type" value="all" data-rule-id="${rule.id}">
                        All of the following match
                    </label>
                    <label>
                        <input type="radio" name="logic_type_${rule.id}" class="logic-type" value="any" data-rule-id="${rule.id}">
                        Any of the following match
                    </label>
                </div>
                
                <div class="conditions-list">
        `;
        
        rule.conditions.forEach((condition, condIndex) => {
            html += renderCondition(rule.id, condition, condIndex);
        });
        
        html += `
                </div>
                <button type="button" class="button add-condition" data-rule-id="${rule.id}">Add Condition</button>
            </div>
            
            <div class="rule-preview">
                <strong>Preview:</strong> <span class="preview-text"></span>
            </div>
        </div>
        `;
        
        return html;
    }
    
    /**
     * Render single condition
     */
    function renderCondition(ruleId, condition, index) {
        return `
        <div class="condition-row" data-condition-index="${index}">
            <select class="condition-field" data-rule-id="${ruleId}" data-condition-index="${index}">
                <option value="">Select Field...</option>
            </select>
            
            <select class="condition-operator" data-rule-id="${ruleId}" data-condition-index="${index}">
                <option value="is">Is</option>
                <option value="isnot">Is Not</option>
                <option value="contains">Contains</option>
                <option value="starts_with">Starts With</option>
                <option value="ends_with">Ends With</option>
                <option value="greater_than">Greater Than</option>
                <option value="less_than">Less Than</option>
                <option value="is_empty">Is Empty</option>
                <option value="is_not_empty">Is Not Empty</option>
            </select>
            
            <span class="condition-value-wrapper">
                <input type="text" class="condition-value" data-rule-id="${ruleId}" data-condition-index="${index}" placeholder="Value">
            </span>
            
            ${index > 0 ? `<button type="button" class="button-secondary remove-condition" data-rule-id="${ruleId}" data-condition-index="${index}">Remove</button>` : ''}
        </div>
        `;
    }
    
    /**
     * Update field selects with available fields
     */
    function updateFieldSelects() {
        // Update target field selects
        $('.rule-target-field').each(function() {
            const $select = $(this);
            const currentValue = $select.data('current-value');
            
            $select.empty();
            $select.append('<option value="">Select Field...</option>');
            
            availableFields.forEach(field => {
                $select.append(`<option value="${field.id}">${field.label}</option>`);
            });
            
            if (currentValue) {
                $select.val(currentValue);
            }
        });
        
        // Update condition field selects
        $('.condition-field').each(function() {
            const $select = $(this);
            const currentValue = $select.data('current-value');
            
            $select.empty();
            $select.append('<option value="">Select Field...</option>');
            
            availableFields.forEach(field => {
                $select.append(`<option value="${field.id}">${field.label}</option>`);
            });
            
            if (currentValue) {
                $select.val(currentValue);
            }
        });
    }
    
    /**
     * Restore rule values after rendering
     */
    function restoreRuleValues() {
        currentRules.forEach(rule => {
            // Restore target field
            $(`.rule-target-field[data-rule-id="${rule.id}"]`).val(rule.field_id);
            
            // Restore action
            $(`.rule-action[data-rule-id="${rule.id}"]`).val(rule.action);
            
            // Restore logic type
            $(`input[name="logic_type_${rule.id}"][value="${rule.logic_type}"]`).prop('checked', true);
            
            // Restore conditions
            rule.conditions.forEach((condition, index) => {
                $(`.condition-field[data-rule-id="${rule.id}"][data-condition-index="${index}"]`).val(condition.field_id);
                $(`.condition-operator[data-rule-id="${rule.id}"][data-condition-index="${index}"]`).val(condition.operator);
                $(`.condition-value[data-rule-id="${rule.id}"][data-condition-index="${index}"]`).val(condition.value);
            });
        });
        
        // Update previews
        $('.conditional-rule').each(function() {
            updateRulePreview.call($(this).find('.rule-action')[0]);
        });
    }
    
    /**
     * Update condition value field based on operator
     */
    function updateConditionValueField() {
        const $this = $(this);
        const operator = $this.hasClass('condition-operator') ? $this.val() : 
                        $this.closest('.condition-row').find('.condition-operator').val();
        const $valueWrapper = $this.closest('.condition-row').find('.condition-value-wrapper');
        
        if (operator === 'is_empty' || operator === 'is_not_empty') {
            $valueWrapper.hide();
        } else {
            $valueWrapper.show();
            
            // Change input type based on field type if needed
            const fieldId = $this.closest('.condition-row').find('.condition-field').val();
            const field = availableFields.find(f => f.id == fieldId);
            
            if (field) {
                const $input = $valueWrapper.find('input');
                
                if (field.type === 'number' && (operator === 'greater_than' || operator === 'less_than')) {
                    $input.attr('type', 'number');
                } else {
                    $input.attr('type', 'text');
                }
                
                // Add field choices as datalist if available
                if (field.choices && field.choices.length > 0) {
                    const datalistId = 'choices_' + field.id;
                    if (!$('#' + datalistId).length) {
                        const datalist = $('<datalist>').attr('id', datalistId);
                        field.choices.forEach(choice => {
                            datalist.append($('<option>').val(choice.value).text(choice.text));
                        });
                        $('body').append(datalist);
                    }
                    $input.attr('list', datalistId);
                } else {
                    $input.removeAttr('list');
                }
            }
        }
    }
    
    /**
     * Update rule preview
     */
    function updateRulePreview() {
        const $rule = $(this).closest('.conditional-rule');
        const ruleId = $rule.data('rule-id');
        const rule = currentRules.find(r => r.id === ruleId);
        
        if (!rule) return;
        
        // Update rule object from form values
        rule.field_id = $rule.find('.rule-target-field').val();
        rule.action = $rule.find('.rule-action').val();
        rule.logic_type = $rule.find('.logic-type:checked').val() || 'all';
        
        // Update conditions
        $rule.find('.condition-row').each(function(index) {
            if (rule.conditions[index]) {
                rule.conditions[index].field_id = $(this).find('.condition-field').val();
                rule.conditions[index].operator = $(this).find('.condition-operator').val();
                rule.conditions[index].value = $(this).find('.condition-value').val();
            }
        });
        
        // Generate preview text
        const targetField = availableFields.find(f => f.id == rule.field_id);
        const targetFieldName = targetField ? targetField.label : 'Unknown Field';
        
        const actionText = {
            'show': 'Show',
            'hide': 'Hide',
            'enable': 'Enable',
            'disable': 'Disable',
            'require': 'Make required',
            'unrequire': 'Make optional'
        }[rule.action] || rule.action;
        
        let previewText = `${actionText} "${targetFieldName}" when `;
        
        if (rule.conditions.length === 0 || !rule.conditions[0].field_id) {
            previewText += '(no conditions set)';
        } else {
            const validConditions = rule.conditions.filter(c => c.field_id);
            const conditionTexts = validConditions.map(condition => {
                const condField = availableFields.find(f => f.id == condition.field_id);
                const fieldName = condField ? condField.label : 'Unknown Field';
                const operatorText = {
                    'is': 'is',
                    'isnot': 'is not',
                    'contains': 'contains',
                    'starts_with': 'starts with',
                    'ends_with': 'ends with',
                    'greater_than': 'is greater than',
                    'less_than': 'is less than',
                    'is_empty': 'is empty',
                    'is_not_empty': 'is not empty'
                }[condition.operator] || condition.operator;
                
                if (condition.operator === 'is_empty' || condition.operator === 'is_not_empty') {
                    return `"${fieldName}" ${operatorText}`;
                } else {
                    return `"${fieldName}" ${operatorText} "${condition.value}"`;
                }
            });
            
            if (conditionTexts.length > 0) {
                previewText += rule.logic_type === 'any' ? 
                    conditionTexts.join(' OR ') : 
                    conditionTexts.join(' AND ');
            } else {
                previewText += '(no valid conditions)';
            }
        }
        
        $rule.find('.preview-text').text(previewText);
    }
    
    /**
     * Save conditional logic rules
     */
    function saveConditionalLogic() {
        // Validate rules
        const validRules = currentRules.filter(rule => {
            return rule.field_id && rule.conditions.some(c => c.field_id);
        });
        
        if (currentRules.length > 0 && validRules.length === 0) {
            showError('Please complete at least one rule before saving');
            return;
        }
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_save_conditional_logic',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: currentFormId,
                rules: JSON.stringify(validRules)
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Conditional logic saved successfully');
                } else {
                    showError('Failed to save conditional logic');
                }
            },
            error: function() {
                showError('Save request failed. Please try again.');
            }
        });
    }
    
    /**
     * Test conditional logic
     */
    function testConditionalLogic() {
        // Open test page in new window
        const testUrl = gfJsEmbedAdmin.pluginUrl + 'tests/test-conditional-logic.html?form_id=' + currentFormId;
        window.open(testUrl, '_blank');
    }
    
    /**
     * Export rules
     */
    function exportRules() {
        const exportData = {
            form_id: currentFormId,
            rules: currentRules,
            exported_at: new Date().toISOString()
        };
        
        const dataStr = JSON.stringify(exportData, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `conditional-logic-form-${currentFormId}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
        showSuccess('Rules exported successfully');
    }
    
    /**
     * Import rules
     */
    function importRules(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                const importData = JSON.parse(event.target.result);
                
                if (!importData.rules || !Array.isArray(importData.rules)) {
                    showError('Invalid import file format');
                    return;
                }
                
                if (confirm('This will replace all existing rules. Continue?')) {
                    currentRules = importData.rules;
                    renderRules();
                    showSuccess('Rules imported successfully');
                }
            } catch (error) {
                showError('Failed to parse import file');
            }
        };
        
        reader.readAsText(file);
        
        // Clear the input
        $(this).val('');
    }
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        showNotice(message, 'success');
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        showNotice(message, 'error');
    }
    
    /**
     * Show notice
     */
    function showNotice(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $notice.remove();
            });
        }, 5000);
        
        // Add dismiss functionality
        $notice.on('click', '.notice-dismiss', function() {
            $notice.remove();
        });
    }
    
})(jQuery);