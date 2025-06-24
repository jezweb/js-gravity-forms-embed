/**
 * Admin interface for rate limiting controls
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Toggle rate limit settings visibility
        $('#js_embed_rate_limit_enabled').on('change', function() {
            const $settings = $('#rate_limit_settings');
            if ($(this).is(':checked')) {
                $settings.slideDown();
            } else {
                $settings.slideUp();
            }
        });
        
        // Rate limit testing functionality
        if ($('#rate-limit-test').length) {
            $('#test-rate-limit').on('click', function() {
                testRateLimit();
            });
            
            $('#clear-rate-limits').on('click', function() {
                if (confirm('Are you sure you want to clear all rate limit data? This cannot be undone.')) {
                    clearRateLimits();
                }
            });
        }
        
        // Auto-refresh rate limit stats
        if ($('#rate-limit-stats').length) {
            setInterval(refreshRateLimitStats, 30000); // Refresh every 30 seconds
        }
    });
    
    /**
     * Test rate limiting functionality
     */
    function testRateLimit() {
        const $button = $('#test-rate-limit');
        const $results = $('#test-results');
        
        $button.prop('disabled', true).text('Testing...');
        $results.html('<div class="notice notice-info"><p>Running rate limit test...</p></div>');
        
        // Make multiple rapid requests to test rate limiting
        const testRequests = [];
        const maxRequests = 10;
        
        for (let i = 0; i < maxRequests; i++) {
            testRequests.push(
                $.ajax({
                    url: gfJsEmbedAdmin.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'gf_js_embed_test_rate_limit',
                        nonce: gfJsEmbedAdmin.nonce,
                        request_number: i + 1
                    }
                })
            );
        }
        
        Promise.allSettled(testRequests).then(function(results) {
            let successCount = 0;
            let rateLimitedCount = 0;
            let errorCount = 0;
            
            results.forEach(function(result, index) {
                if (result.status === 'fulfilled') {
                    if (result.value.success) {
                        successCount++;
                    } else if (result.value.data && result.value.data.code === 'rate_limited') {
                        rateLimitedCount++;
                    } else {
                        errorCount++;
                    }
                } else {
                    errorCount++;
                }
            });
            
            let resultHtml = '<div class="rate-limit-test-results">';
            resultHtml += '<h4>Rate Limit Test Results</h4>';
            resultHtml += '<table class="widefat">';
            resultHtml += '<tr><th>Result</th><th>Count</th></tr>';
            resultHtml += '<tr><td>Successful Requests</td><td>' + successCount + '</td></tr>';
            resultHtml += '<tr><td>Rate Limited</td><td>' + rateLimitedCount + '</td></tr>';
            resultHtml += '<tr><td>Errors</td><td>' + errorCount + '</td></tr>';
            resultHtml += '</table>';
            
            if (rateLimitedCount > 0) {
                resultHtml += '<div class="notice notice-success"><p><strong>Success!</strong> Rate limiting is working correctly.</p></div>';
            } else {
                resultHtml += '<div class="notice notice-warning"><p><strong>Warning:</strong> No requests were rate limited. Check your settings.</p></div>';
            }
            
            resultHtml += '</div>';
            
            $results.html(resultHtml);
            $button.prop('disabled', false).text('Test Rate Limiting');
        });
    }
    
    /**
     * Clear rate limit data
     */
    function clearRateLimits() {
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_clear_rate_limits',
                nonce: gfJsEmbedAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#rate-limit-results').html(
                        '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                    );
                    refreshRateLimitStats();
                } else {
                    $('#rate-limit-results').html(
                        '<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>'
                    );
                }
            },
            error: function() {
                $('#rate-limit-results').html(
                    '<div class="notice notice-error"><p>Request failed. Please try again.</p></div>'
                );
            }
        });
    }
    
    /**
     * Refresh rate limit statistics
     */
    function refreshRateLimitStats() {
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_rate_limit_stats',
                nonce: gfJsEmbedAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data.stats) {
                    updateStatsDisplay(response.data.stats);
                }
            }
        });
    }
    
    /**
     * Update stats display
     */
    function updateStatsDisplay(stats) {
        const $container = $('#rate-limit-stats');
        if (!$container.length) return;
        
        let html = '<h4>Rate Limit Statistics (Last 7 Days)</h4>';
        html += '<table class="widefat striped">';
        html += '<thead><tr><th>Endpoint</th><th>Total Requests</th><th>Blocked Requests</th><th>Block Rate</th><th>Avg Requests/Window</th></tr></thead>';
        html += '<tbody>';
        
        if (stats.length === 0) {
            html += '<tr><td colspan="5">No rate limit data available</td></tr>';
        } else {
            stats.forEach(function(stat) {
                const blockRate = stat.total_requests > 0 
                    ? ((stat.blocked_requests / stat.total_requests) * 100).toFixed(1)
                    : '0.0';
                
                html += '<tr>';
                html += '<td>' + stat.endpoint + '</td>';
                html += '<td>' + stat.total_requests + '</td>';
                html += '<td>' + stat.blocked_requests + '</td>';
                html += '<td>' + blockRate + '%</td>';
                html += '<td>' + parseFloat(stat.avg_requests_per_window).toFixed(1) + '</td>';
                html += '</tr>';
            });
        }
        
        html += '</tbody></table>';
        html += '<p class="description">Statistics are updated every hour. Last updated: ' + new Date().toLocaleTimeString() + '</p>';
        
        $container.html(html);
    }
    
    /**
     * Initialize rate limit charts if Chart.js is available
     */
    function initRateLimitCharts() {
        if (typeof Chart === 'undefined') return;
        
        const canvas = document.getElementById('rate-limit-chart');
        if (!canvas) return;
        
        $.ajax({
            url: gfJsEmbedAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_rate_limit_chart_data',
                nonce: gfJsEmbedAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    createRateLimitChart(canvas, response.data);
                }
            }
        });
    }
    
    /**
     * Create rate limit chart
     */
    function createRateLimitChart(canvas, data) {
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Total Requests',
                    data: data.requests,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Blocked Requests',
                    data: data.blocked,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Requests'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Rate Limiting Activity'
                    }
                }
            }
        });
    }
    
    // Initialize charts when Chart.js loads
    if (typeof Chart !== 'undefined') {
        initRateLimitCharts();
    } else {
        // Wait for Chart.js to load
        $(window).on('load', function() {
            setTimeout(initRateLimitCharts, 1000);
        });
    }
    
})(jQuery);