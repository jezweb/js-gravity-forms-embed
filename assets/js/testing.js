/**
 * JavaScript for Testing Dashboard
 */
(function($) {
    'use strict';

    var GFJSEmbedTesting = {
        
        testResults: {},
        currentTests: [],
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Individual test buttons
            $('.run-test').on('click', this.runSingleTest.bind(this));
            
            // Run all tests button
            $('#run-all-tests').on('click', this.runAllTests.bind(this));
            
            // Export results button
            $('#export-results').on('click', this.exportResults.bind(this));
        },
        
        runSingleTest: function(e) {
            e.preventDefault();
            var $button = $(e.currentTarget);
            var testType = $button.data('test');
            var $category = $button.closest('.test-category');
            
            this.runTest(testType, $category);
        },
        
        runAllTests: function(e) {
            e.preventDefault();
            var self = this;
            var $button = $(e.currentTarget);
            
            // Disable button and show loading
            $button.prop('disabled', true).find('.dashicons').addClass('dashicons-update-spin');
            
            // Show results container
            $('#test-results').slideDown();
            $('#results-container').html('<div class="testing-progress"><span class="spinner is-active"></span> ' + gfJSEmbedTesting.strings.running + '</div>');
            
            // Run all tests
            $.ajax({
                url: gfJSEmbedTesting.ajax_url,
                type: 'POST',
                data: {
                    action: 'gf_js_embed_run_test',
                    test_type: 'all',
                    nonce: gfJSEmbedTesting.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.testResults = response.data;
                        self.displayAllResults(response.data);
                        $('#export-results').prop('disabled', false);
                    } else {
                        self.showError(gfJSEmbedTesting.strings.error);
                    }
                },
                error: function() {
                    self.showError(gfJSEmbedTesting.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-spin');
                }
            });
        },
        
        runTest: function(testType, $category) {
            var self = this;
            var $button = $category.find('.run-test');
            var $status = $category.find('.category-status');
            var $results = $category.find('.category-results');
            
            // Update UI
            $button.prop('disabled', true);
            $status.show().find('.status-indicator').html('<span class="spinner is-active"></span>');
            $status.find('.status-message').text(gfJSEmbedTesting.strings.running);
            $results.slideUp();
            
            // Run test
            $.ajax({
                url: gfJSEmbedTesting.ajax_url,
                type: 'POST',
                data: {
                    action: 'gf_js_embed_run_test',
                    test_type: testType,
                    nonce: gfJSEmbedTesting.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayTestResults(response.data, $category);
                        self.testResults[testType] = response.data;
                        
                        // Enable export if we have results
                        if (Object.keys(self.testResults).length > 0) {
                            $('#export-results').prop('disabled', false);
                        }
                    } else {
                        self.showCategoryError($category, gfJSEmbedTesting.strings.error);
                    }
                },
                error: function() {
                    self.showCategoryError($category, gfJSEmbedTesting.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },
        
        displayTestResults: function(results, $category) {
            var $status = $category.find('.category-status');
            var $results = $category.find('.category-results');
            var summary = results.summary;
            
            // Update status indicator
            var statusClass = 'pass';
            var statusIcon = '✅';
            if (summary.failed > 0) {
                statusClass = 'fail';
                statusIcon = '❌';
            } else if (summary.warnings > 0) {
                statusClass = 'warning';
                statusIcon = '⚠️';
            }
            
            $status.find('.status-indicator').html('<span class="status-' + statusClass + '">' + statusIcon + '</span>');
            $status.find('.status-message').html(
                'Passed: ' + summary.passed + ' | ' +
                'Failed: ' + summary.failed + ' | ' +
                'Warnings: ' + summary.warnings
            );
            
            // Build results HTML
            var html = '<div class="test-results-list">';
            
            results.tests.forEach(function(test) {
                var testClass = 'test-result test-' + test.status;
                var icon = test.status === 'pass' ? '✅' : (test.status === 'fail' ? '❌' : '⚠️');
                
                html += '<div class="' + testClass + '">';
                html += '<div class="test-header">';
                html += '<span class="test-icon">' + icon + '</span>';
                html += '<span class="test-name">' + test.name + '</span>';
                html += '</div>';
                html += '<div class="test-message">' + test.message + '</div>';
                
                if (test.fix) {
                    html += '<div class="test-fix">💡 ' + test.fix + '</div>';
                }
                
                if (test.details) {
                    html += '<div class="test-details">';
                    if (typeof test.details === 'object') {
                        html += '<pre>' + JSON.stringify(test.details, null, 2) + '</pre>';
                    } else {
                        html += test.details;
                    }
                    html += '</div>';
                }
                
                html += '</div>';
            });
            
            if (results.note) {
                html += '<div class="test-note">ℹ️ ' + results.note + '</div>';
            }
            
            html += '</div>';
            
            $results.html(html).slideDown();
        },
        
        displayAllResults: function(allResults) {
            var html = '<div class="all-test-results">';
            
            // Overall summary
            var totalPassed = 0, totalFailed = 0, totalWarnings = 0;
            
            for (var category in allResults) {
                if (allResults[category].summary) {
                    totalPassed += allResults[category].summary.passed;
                    totalFailed += allResults[category].summary.failed;
                    totalWarnings += allResults[category].summary.warnings;
                }
            }
            
            html += '<div class="overall-summary">';
            html += '<h3>Overall Results</h3>';
            html += '<div class="summary-stats">';
            html += '<span class="stat-pass">✅ Passed: ' + totalPassed + '</span>';
            html += '<span class="stat-fail">❌ Failed: ' + totalFailed + '</span>';
            html += '<span class="stat-warning">⚠️ Warnings: ' + totalWarnings + '</span>';
            html += '</div>';
            html += '</div>';
            
            // Category results
            for (var category in allResults) {
                var results = allResults[category];
                html += '<div class="category-section">';
                html += '<h3>' + results.title + '</h3>';
                
                results.tests.forEach(function(test) {
                    var testClass = 'test-result test-' + test.status;
                    var icon = test.status === 'pass' ? '✅' : (test.status === 'fail' ? '❌' : '⚠️');
                    
                    html += '<div class="' + testClass + '">';
                    html += '<div class="test-header">';
                    html += '<span class="test-icon">' + icon + '</span>';
                    html += '<span class="test-name">' + test.name + '</span>';
                    html += '</div>';
                    html += '<div class="test-message">' + test.message + '</div>';
                    
                    if (test.fix) {
                        html += '<div class="test-fix">💡 ' + test.fix + '</div>';
                    }
                    
                    if (test.details) {
                        html += '<div class="test-details">';
                        if (typeof test.details === 'object') {
                            html += '<pre>' + JSON.stringify(test.details, null, 2) + '</pre>';
                        } else {
                            html += test.details;
                        }
                        html += '</div>';
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#results-container').html(html);
        },
        
        exportResults: function(e) {
            e.preventDefault();
            
            var exportData = {
                plugin_version: gfJSEmbedTesting.plugin_version || '',
                test_date: new Date().toISOString(),
                results: this.testResults
            };
            
            var dataStr = JSON.stringify(exportData, null, 2);
            var dataBlob = new Blob([dataStr], {type: 'application/json'});
            var url = URL.createObjectURL(dataBlob);
            
            var link = document.createElement('a');
            link.href = url;
            link.download = 'gf-js-embed-test-results-' + Date.now() + '.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show success message
            this.showNotice(gfJSEmbedTesting.strings.export_success, 'success');
        },
        
        showError: function(message) {
            $('#results-container').html('<div class="notice notice-error"><p>' + message + '</p></div>');
        },
        
        showCategoryError: function($category, message) {
            var $status = $category.find('.category-status');
            $status.find('.status-indicator').html('<span class="status-fail">❌</span>');
            $status.find('.status-message').text(message);
        },
        
        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    // Initialize when ready
    $(document).ready(function() {
        GFJSEmbedTesting.init();
    });
    
})(jQuery);