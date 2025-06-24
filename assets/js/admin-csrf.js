/**
 * Admin interface for CSRF protection
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize CSRF monitoring if on the CSRF page
        if ($('#csrf-monitoring').length) {
            initCSRFMonitoring();
        }
        
        // CSRF controls
        $('#test-csrf-protection').on('click', testCSRFProtection);
        $('#clear-csrf-tokens').on('click', confirmClearTokens);
        $('#refresh-csrf-stats').on('click', refreshCSRFStats);
        
        // Settings controls
        $('#csrf-settings-form').on('submit', saveCSRFSettings);
        $('#enable-csrf-protection').on('change', toggleCSRFProtection);
        
        // Token management
        $('#generate-test-token').on('click', generateTestToken);
        $('#validate-test-token').on('click', validateTestToken);
        
    });
    
    /**
     * Initialize CSRF monitoring
     */
    function initCSRFMonitoring() {
        refreshCSRFStats();
        updateCSRFStatus();
        
        // Set up auto-refresh if enabled
        if ($('#auto-refresh-csrf').is(':checked')) {
            startAutoRefresh();
        }
    }
    
    /**
     * Refresh CSRF statistics
     */
    function refreshCSRFStats() {
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_csrf_stats',
                nonce: gfJsEmbedAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayCSRFStats(response.data.stats);
                } else {
                    showError('Failed to load CSRF statistics: ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function() {
                showError('Request failed. Please try again.');
            }
        });
    }
    
    /**
     * Display CSRF statistics
     */
    function displayCSRFStats(stats) {
        const $container = $('#csrf-statistics');
        
        let html = '<h4>CSRF Protection Statistics</h4>';
        
        html += '<table class="widefat striped">';
        html += '<tbody>';
        html += '<tr><td>Protection Status</td><td>' + (stats.enabled ? 'Enabled' : 'Disabled') + '</td></tr>';
        html += '<tr><td>Active Sessions</td><td>' + (stats.activeSessions || 0) + '</td></tr>';
        html += '<tr><td>Total Tokens Generated</td><td>' + (stats.totalTokens || 0) + '</td></tr>';
        html += '<tr><td>Tokens Validated</td><td>' + (stats.validatedTokens || 0) + '</td></tr>';
        html += '<tr><td>Failed Validations</td><td>' + (stats.failedValidations || 0) + '</td></tr>';
        html += '<tr><td>Token Timeout</td><td>' + (stats.tokenTimeout || 1800) + ' seconds</td></tr>';
        html += '</tbody>';
        html += '</table>';
        
        $container.html(html);
    }
    
    /**
     * Update CSRF protection status
     */
    function updateCSRFStatus() {
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_csrf_status',
                nonce: gfJsEmbedAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const status = response.data.status;
                    const $indicator = $('#csrf-status-indicator');
                    const $text = $('#csrf-status-text');
                    
                    if (status.enabled) {
                        $indicator.removeClass('status-inactive').addClass('status-active');
                        $text.text('Enabled');
                    } else {
                        $indicator.removeClass('status-active').addClass('status-inactive');
                        $text.text('Disabled');
                    }
                }
            }
        });
    }
    
    /**
     * Test CSRF protection
     */
    function testCSRFProtection() {
        showInfo('Starting CSRF protection test...');
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_test_csrf',
                nonce: gfJsEmbedAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const results = response.data.results;
                    displayTestResults(results);
                } else {
                    showError('CSRF test failed: ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function() {
                showError('CSRF test request failed. Please try again.');
            }
        });
    }
    
    /**
     * Display test results
     */
    function displayTestResults(results) {
        const $container = $('#csrf-test-results');
        
        let html = '<h4>CSRF Protection Test Results</h4>';
        html += '<table class="widefat striped">';
        html += '<thead><tr><th>Test</th><th>Result</th><th>Details</th></tr></thead>';
        html += '<tbody>';
        
        results.forEach(function(result) {
            const statusClass = result.passed ? 'test-passed' : 'test-failed';
            const statusText = result.passed ? '✓ Passed' : '✗ Failed';
            
            html += '<tr class="' + statusClass + '">';
            html += '<td>' + result.test + '</td>';
            html += '<td>' + statusText + '</td>';
            html += '<td>' + (result.details || '') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $container.html(html);
        $container.show();
    }
    
    /**
     * Generate test token
     */
    function generateTestToken() {
        const formId = $('#test-form-id').val() || null;
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_generate_test_token',
                nonce: gfJsEmbedAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    $('#test-token-display').val(response.data.token);
                    $('#test-token-info').html(
                        'Token generated for form: ' + (formId || 'global') + '<br>' +
                        'Created: ' + new Date().toLocaleTimeString() + '<br>' +
                        'Expires in: ' + (response.data.timeout / 60) + ' minutes'
                    );
                    showSuccess('Test token generated successfully');
                } else {
                    showError('Failed to generate test token: ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function() {
                showError('Test token generation request failed. Please try again.');
            }
        });
    }
    
    /**
     * Validate test token
     */
    function validateTestToken() {
        const token = $('#test-token-display').val();
        const formId = $('#test-form-id').val() || null;
        
        if (!token) {
            showError('No token to validate. Generate a token first.');
            return;
        }
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_validate_test_token',
                nonce: gfJsEmbedAdmin.nonce,
                token: token,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    const isValid = response.data.valid;
                    if (isValid) {
                        showSuccess('Token is valid');
                        $('#test-token-info').append('<br><span style="color: green;">✓ Token validation passed</span>');
                    } else {
                        showError('Token is invalid');
                        $('#test-token-info').append('<br><span style="color: red;">✗ Token validation failed</span>');
                    }
                } else {
                    showError('Token validation failed: ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function() {
                showError('Token validation request failed. Please try again.');
            }
        });
    }
    
    /**
     * Clear CSRF tokens with confirmation
     */
    function confirmClearTokens() {
        if (confirm('Are you sure you want to clear all CSRF tokens? This will invalidate all current user sessions and may cause form submission errors until new tokens are generated.')) {
            clearCSRFTokens();
        }
    }
    
    /**
     * Clear CSRF tokens
     */
    function clearCSRFTokens() {
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_clear_csrf_tokens',
                nonce: gfJsEmbedAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('CSRF tokens cleared successfully');
                    refreshCSRFStats();
                } else {
                    showError('Failed to clear CSRF tokens: ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function() {
                showError('Clear tokens request failed. Please try again.');
            }
        });
    }
    
    /**
     * Save CSRF settings
     */
    function saveCSRFSettings(e) {
        e.preventDefault();
        
        const formData = {
            action: 'gf_js_embed_save_csrf_settings',
            nonce: gfJsEmbedAdmin.nonce,
            enable_csrf_protection: $('#enable-csrf-protection').is(':checked'),
            csrf_token_timeout: $('#csrf-token-timeout').val(),
            csrf_max_tokens_per_session: $('#csrf-max-tokens-per-session').val()
        };
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showSuccess('CSRF settings saved successfully');
                    updateCSRFStatus();
                } else {
                    showError('Failed to save CSRF settings: ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function() {
                showError('Save settings request failed. Please try again.');
            }
        });
    }
    
    /**
     * Toggle CSRF protection
     */
    function toggleCSRFProtection() {
        const isEnabled = $(this).is(':checked');
        
        if (!isEnabled) {
            if (!confirm('Disabling CSRF protection will make your forms vulnerable to cross-site request forgery attacks. Are you sure you want to continue?')) {
                $(this).prop('checked', true);
                return;
            }
        }
        
        updateCSRFStatus();
    }
    
    /**
     * Start auto-refresh
     */
    function startAutoRefresh() {
        setInterval(function() {
            refreshCSRFStats();
        }, 30000); // Refresh every 30 seconds
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
     * Show info message
     */
    function showInfo(message) {
        showNotice(message, 'info');
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