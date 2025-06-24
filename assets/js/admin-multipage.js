/**
 * Admin interface for Multi-Page Forms
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize multi-page settings if on the form settings page
        if ($('#multipage-settings').length) {
            initMultiPageSettings();
        }
        
        // Progress indicator type change
        $('#progress-indicator-type').on('change', updateProgressPreview);
        
        // Page break management
        $('#add-page-break').on('click', addPageBreak);
        $(document).on('click', '.remove-page-break', removePageBreak);
        
        // Navigation labels
        $('#save-navigation-labels').on('click', saveNavigationLabels);
        
        // Auto-save settings
        $('#enable-auto-save').on('change', toggleAutoSaveSettings);
        
        // Test multi-page functionality
        $('#test-multipage').on('click', testMultiPageForm);
        
    });
    
    /**
     * Initialize multi-page settings
     */
    function initMultiPageSettings() {
        loadFormPages();
        updateProgressPreview();
        updatePageBreaksList();
    }
    
    /**
     * Load form pages
     */
    function loadFormPages() {
        const formId = $('#form-id').val();
        if (!formId) return;
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_form_pages',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    displayFormPages(response.data.pages);
                }
            }
        });
    }
    
    /**
     * Display form pages
     */
    function displayFormPages(pages) {
        const $container = $('#form-pages-list');
        
        if (!pages || pages.length === 0) {
            $container.html('<p>No pages found. Add page breaks to create a multi-page form.</p>');
            return;
        }
        
        let html = '<table class="widefat striped">';
        html += '<thead>';
        html += '<tr>';
        html += '<th>Page</th>';
        html += '<th>Title</th>';
        html += '<th>Fields</th>';
        html += '<th>Actions</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
        pages.forEach(function(page, index) {
            html += '<tr>';
            html += '<td>' + page.number + '</td>';
            html += '<td>';
            html += '<input type="text" class="page-title" data-page="' + page.number + '" value="' + (page.title || '') + '" />';
            html += '</td>';
            html += '<td>' + page.fields.length + ' fields</td>';
            html += '<td>';
            html += '<button class="button-secondary edit-page" data-page="' + page.number + '">Edit</button> ';
            if (index > 0) {
                html += '<button class="button-secondary remove-page-break" data-page="' + page.number + '">Remove Break</button>';
            }
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody>';
        html += '</table>';
        
        $container.html(html);
        
        // Save page titles on change
        $('.page-title').on('change', function() {
            savePageTitle($(this).data('page'), $(this).val());
        });
    }
    
    /**
     * Update progress preview
     */
    function updateProgressPreview() {
        const type = $('#progress-indicator-type').val();
        const $preview = $('#progress-preview');
        
        let html = '';
        
        if (type === 'steps') {
            html = '<div class="gf-progress-steps preview">';
            html += '<div class="gf-progress-step completed"><span class="step-number">1</span><span class="step-name">Step 1</span></div>';
            html += '<div class="gf-progress-step active"><span class="step-number">2</span><span class="step-name">Step 2</span></div>';
            html += '<div class="gf-progress-step"><span class="step-number">3</span><span class="step-name">Step 3</span></div>';
            html += '</div>';
        } else if (type === 'bar') {
            html = '<div class="gf-progress-bar preview">';
            html += '<div class="gf-progress-fill" style="width: 66%"></div>';
            html += '<span class="gf-progress-text">Page 2 of 3</span>';
            html += '</div>';
        } else {
            html = '<p>No progress indicator</p>';
        }
        
        $preview.html(html);
    }
    
    /**
     * Add page break
     */
    function addPageBreak() {
        const afterField = $('#page-break-after-field').val();
        const pageTitle = $('#new-page-title').val();
        
        if (!afterField) {
            showError('Please select a field to add the page break after.');
            return;
        }
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_add_page_break',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: $('#form-id').val(),
                after_field: afterField,
                page_title: pageTitle
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Page break added successfully');
                    loadFormPages();
                    updatePageBreaksList();
                } else {
                    showError('Failed to add page break: ' + response.data.message);
                }
            }
        });
    }
    
    /**
     * Remove page break
     */
    function removePageBreak() {
        const pageNumber = $(this).data('page');
        
        if (!confirm('Are you sure you want to remove this page break?')) {
            return;
        }
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_remove_page_break',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: $('#form-id').val(),
                page_number: pageNumber
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Page break removed successfully');
                    loadFormPages();
                    updatePageBreaksList();
                } else {
                    showError('Failed to remove page break: ' + response.data.message);
                }
            }
        });
    }
    
    /**
     * Save page title
     */
    function savePageTitle(pageNumber, title) {
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_save_page_title',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: $('#form-id').val(),
                page_number: pageNumber,
                title: title
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Page title saved');
                }
            }
        });
    }
    
    /**
     * Save navigation labels
     */
    function saveNavigationLabels() {
        const labels = {
            previous: $('#label-previous').val(),
            next: $('#label-next').val(),
            submit: $('#label-submit').val()
        };
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_save_navigation_labels',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: $('#form-id').val(),
                labels: labels
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Navigation labels saved');
                } else {
                    showError('Failed to save navigation labels');
                }
            }
        });
    }
    
    /**
     * Toggle auto-save settings
     */
    function toggleAutoSaveSettings() {
        const isEnabled = $(this).is(':checked');
        $('#auto-save-interval').prop('disabled', !isEnabled);
    }
    
    /**
     * Test multi-page form
     */
    function testMultiPageForm() {
        const formId = $('#form-id').val();
        if (!formId) {
            showError('No form selected');
            return;
        }
        
        // Open test page in new window
        const testUrl = gfJsEmbedAdmin.pluginUrl + 'tests/test-multipage.html?form_id=' + formId;
        window.open(testUrl, '_blank');
    }
    
    /**
     * Update page breaks list
     */
    function updatePageBreaksList() {
        // This would update the dropdown for adding new page breaks
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_form_fields',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: $('#form-id').val()
            },
            success: function(response) {
                if (response.success) {
                    const $select = $('#page-break-after-field');
                    $select.empty();
                    $select.append('<option value="">Select a field...</option>');
                    
                    response.data.fields.forEach(function(field) {
                        $select.append('<option value="' + field.id + '">' + field.label + '</option>');
                    });
                }
            }
        });
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