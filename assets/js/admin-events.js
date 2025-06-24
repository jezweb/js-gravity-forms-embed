/**
 * Admin interface for event system
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize event monitoring if on the events page
        if ($('#event-monitoring').length) {
            initEventMonitoring();
        }
        
        // Event controls
        $('#refresh-events').on('click', refreshEvents);
        $('#clear-events').on('click', confirmClearEvents);
        $('#export-events').on('click', exportEvents);
        
        // Auto-refresh toggle
        $('#auto-refresh').on('change', toggleAutoRefresh);
        
        // Filter controls
        $('#event-filter-form').on('change', 'select, input', applyFilters);
        
        // Real-time event display toggle
        $('#real-time-toggle').on('change', toggleRealTime);
        
        // Event details modal
        $(document).on('click', '.event-details-btn', showEventDetails);
        
    });
    
    let autoRefreshInterval = null;
    let realTimeConnection = null;
    
    /**
     * Initialize event monitoring
     */
    function initEventMonitoring() {
        refreshEvents();
        updateEventStatistics();
        
        // Set up auto-refresh if enabled
        if ($('#auto-refresh').is(':checked')) {
            startAutoRefresh();
        }
        
        // Set up real-time monitoring if enabled
        if ($('#real-time-toggle').is(':checked')) {
            startRealTimeMonitoring();
        }
    }
    
    /**
     * Refresh events display
     */
    function refreshEvents() {
        const $container = $('#events-list');
        const $loading = $('#events-loading');
        
        $loading.show();
        
        const filters = {
            form_id: $('#filter-form-id').val(),
            event_type: $('#filter-event-type').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            limit: $('#filter-limit').val() || 100
        };
        
        $.ajax({
            url: gfEmbedEventsAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'gf_js_embed_get_events',
                nonce: gfEmbedEventsAdmin.nonce,
                ...filters
            },
            success: function(response) {
                $loading.hide();
                
                if (response.success) {
                    displayEvents(response.data.events);
                    updateEventCount(response.data.count);
                } else {
                    showError('Failed to load events: ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function() {
                $loading.hide();
                showError('Request failed. Please try again.');
            }
        });
    }
    
    /**
     * Display events in the list
     */
    function displayEvents(events) {
        const $container = $('#events-list');
        
        if (!events || events.length === 0) {
            $container.html('<div class=\"no-events\">No events found for the selected criteria.</div>');
            return;
        }
        
        let html = '<table class=\"wp-list-table widefat striped events-table\">';\n        html += '<thead>';\n        html += '<tr>';\n        html += '<th>Time</th>';\n        html += '<th>Form</th>';\n        html += '<th>Event Type</th>';\n        html += '<th>Domain</th>';\n        html += '<th>IP Address</th>';\n        html += '<th>Actions</th>';\n        html += '</tr>';\n        html += '</thead>';\n        html += '<tbody>';\n        \n        events.forEach(function(event) {\n            const date = new Date(event.created_at);\n            const formattedDate = date.toLocaleString();\n            \n            html += '<tr>';\n            html += '<td>' + formattedDate + '</td>';\n            html += '<td>Form #' + event.form_id + '</td>';\n            html += '<td><span class=\"event-type-badge event-type-' + event.event_type.replace(/[^a-z0-9]/gi, '-') + '\">' + event.event_type + '</span></td>';\n            html += '<td>' + (event.domain || 'N/A') + '</td>';\n            html += '<td>' + (event.ip_address || 'N/A') + '</td>';\n            html += '<td>';\n            html += '<button class=\"button-secondary event-details-btn\" data-event-id=\"' + event.id + '\">Details</button>';\n            html += '</td>';\n            html += '</tr>';\n        });\n        \n        html += '</tbody>';\n        html += '</table>';\n        \n        $container.html(html);\n    }\n    \n    /**\n     * Update event count display\n     */\n    function updateEventCount(count) {\n        $('#total-events-count').text(count);\n    }\n    \n    /**\n     * Update event statistics\n     */\n    function updateEventStatistics() {\n        $.ajax({\n            url: gfEmbedEventsAdmin.ajaxUrl,\n            method: 'POST',\n            data: {\n                action: 'gf_js_embed_get_event_stats',\n                nonce: gfEmbedEventsAdmin.nonce\n            },\n            success: function(response) {\n                if (response.success && response.data.stats) {\n                    displayEventStatistics(response.data.stats);\n                }\n            }\n        });\n    }\n    \n    /**\n     * Display event statistics\n     */\n    function displayEventStatistics(stats) {\n        const $container = $('#event-statistics');\n        \n        let html = '<h4>Event Statistics (Last 7 Days)</h4>';\n        \n        if (stats.length === 0) {\n            html += '<p>No events recorded in the last 7 days.</p>';\n        } else {\n            html += '<table class=\"widefat striped\">';\n            html += '<thead><tr><th>Event Type</th><th>Count</th><th>Latest</th></tr></thead>';\n            html += '<tbody>';\n            \n            stats.forEach(function(stat) {\n                const latestDate = stat.latest ? new Date(stat.latest).toLocaleDateString() : 'N/A';\n                html += '<tr>';\n                html += '<td>' + stat.event_type + '</td>';\n                html += '<td>' + stat.count + '</td>';\n                html += '<td>' + latestDate + '</td>';\n                html += '</tr>';\n            });\n            \n            html += '</tbody></table>';\n        }\n        \n        $container.html(html);\n    }\n    \n    /**\n     * Show event details in modal\n     */\n    function showEventDetails(e) {\n        const eventId = $(this).data('event-id');\n        \n        $.ajax({\n            url: gfEmbedEventsAdmin.ajaxUrl,\n            method: 'POST',\n            data: {\n                action: 'gf_js_embed_get_event_details',\n                nonce: gfEmbedEventsAdmin.nonce,\n                event_id: eventId\n            },\n            success: function(response) {\n                if (response.success) {\n                    displayEventDetailsModal(response.data.event);\n                } else {\n                    showError('Failed to load event details');\n                }\n            },\n            error: function() {\n                showError('Request failed. Please try again.');\n            }\n        });\n    }\n    \n    /**\n     * Display event details modal\n     */\n    function displayEventDetailsModal(event) {\n        const date = new Date(event.created_at);\n        const formattedDate = date.toLocaleString();\n        \n        let html = '<div class=\"event-details-modal\">';\n        html += '<h3>Event Details</h3>';\n        html += '<table class=\"form-table\">';\n        html += '<tr><th>Event ID</th><td>' + event.id + '</td></tr>';\n        html += '<tr><th>Form ID</th><td>' + event.form_id + '</td></tr>';\n        html += '<tr><th>Event Type</th><td>' + event.event_type + '</td></tr>';\n        html += '<tr><th>Timestamp</th><td>' + formattedDate + '</td></tr>';\n        html += '<tr><th>Domain</th><td>' + (event.domain || 'N/A') + '</td></tr>';\n        html += '<tr><th>IP Address</th><td>' + (event.ip_address || 'N/A') + '</td></tr>';\n        html += '<tr><th>User Agent</th><td>' + (event.user_agent || 'N/A') + '</td></tr>';\n        \n        if (event.event_data && Object.keys(event.event_data).length > 0) {\n            html += '<tr><th>Event Data</th><td><pre>' + JSON.stringify(event.event_data, null, 2) + '</pre></td></tr>';\n        }\n        \n        html += '</table>';\n        html += '</div>';\n        \n        // Create modal dialog\n        const $modal = $('<div class=\"gf-events-modal\">');\n        const $overlay = $('<div class=\"gf-events-modal-overlay\">');\n        const $content = $('<div class=\"gf-events-modal-content\">');\n        const $close = $('<button class=\"gf-events-modal-close\">&times;</button>');\n        \n        $content.html(html);\n        $content.prepend($close);\n        $modal.append($overlay, $content);\n        \n        $('body').append($modal);\n        \n        // Close handlers\n        $close.on('click', function() {\n            $modal.remove();\n        });\n        \n        $overlay.on('click', function() {\n            $modal.remove();\n        });\n        \n        $(document).on('keydown.gf-events-modal', function(e) {\n            if (e.keyCode === 27) { // ESC key\n                $modal.remove();\n                $(document).off('keydown.gf-events-modal');\n            }\n        });\n    }\n    \n    /**\n     * Clear events with confirmation\n     */\n    function confirmClearEvents() {\n        const formId = $('#filter-form-id').val();\n        const message = formId \n            ? 'Are you sure you want to clear all events for form #' + formId + '? This cannot be undone.'\n            : 'Are you sure you want to clear ALL events? This cannot be undone.';\n            \n        if (confirm(message)) {\n            clearEvents(formId);\n        }\n    }\n    \n    /**\n     * Clear events\n     */\n    function clearEvents(formId = null) {\n        $.ajax({\n            url: gfEmbedEventsAdmin.ajaxUrl,\n            method: 'POST',\n            data: {\n                action: 'gf_js_embed_clear_events',\n                nonce: gfEmbedEventsAdmin.nonce,\n                form_id: formId\n            },\n            success: function(response) {\n                if (response.success) {\n                    showSuccess(response.data.message);\n                    refreshEvents();\n                    updateEventStatistics();\n                } else {\n                    showError('Failed to clear events: ' + (response.data?.message || 'Unknown error'));\n                }\n            },\n            error: function() {\n                showError('Request failed. Please try again.');\n            }\n        });\n    }\n    \n    /**\n     * Export events\n     */\n    function exportEvents() {\n        const filters = {\n            form_id: $('#filter-form-id').val(),\n            event_type: $('#filter-event-type').val(),\n            date_from: $('#filter-date-from').val(),\n            date_to: $('#filter-date-to').val()\n        };\n        \n        // Create download URL with filters\n        const params = new URLSearchParams({\n            action: 'gf_js_embed_export_events',\n            nonce: gfEmbedEventsAdmin.nonce,\n            ...filters\n        });\n        \n        const url = gfEmbedEventsAdmin.ajaxUrl + '?' + params.toString();\n        \n        // Trigger download\n        const link = document.createElement('a');\n        link.href = url;\n        link.download = 'gf-events-export.csv';\n        document.body.appendChild(link);\n        link.click();\n        document.body.removeChild(link);\n    }\n    \n    /**\n     * Apply filters\n     */\n    function applyFilters() {\n        refreshEvents();\n    }\n    \n    /**\n     * Toggle auto-refresh\n     */\n    function toggleAutoRefresh() {\n        if ($(this).is(':checked')) {\n            startAutoRefresh();\n        } else {\n            stopAutoRefresh();\n        }\n    }\n    \n    /**\n     * Start auto-refresh\n     */\n    function startAutoRefresh() {\n        const interval = parseInt($('#auto-refresh-interval').val() || 30) * 1000;\n        \n        stopAutoRefresh(); // Clear any existing interval\n        \n        autoRefreshInterval = setInterval(function() {\n            refreshEvents();\n            updateEventStatistics();\n        }, interval);\n        \n        showInfo('Auto-refresh enabled (every ' + (interval / 1000) + ' seconds)');\n    }\n    \n    /**\n     * Stop auto-refresh\n     */\n    function stopAutoRefresh() {\n        if (autoRefreshInterval) {\n            clearInterval(autoRefreshInterval);\n            autoRefreshInterval = null;\n        }\n    }\n    \n    /**\n     * Toggle real-time monitoring\n     */\n    function toggleRealTime() {\n        if ($(this).is(':checked')) {\n            startRealTimeMonitoring();\n        } else {\n            stopRealTimeMonitoring();\n        }\n    }\n    \n    /**\n     * Start real-time monitoring\n     */\n    function startRealTimeMonitoring() {\n        // This would typically use WebSockets or Server-Sent Events\n        // For now, we'll use polling with a shorter interval\n        stopRealTimeMonitoring();\n        \n        realTimeConnection = setInterval(function() {\n            refreshEvents();\n        }, 5000); // Poll every 5 seconds\n        \n        showInfo('Real-time monitoring enabled');\n    }\n    \n    /**\n     * Stop real-time monitoring\n     */\n    function stopRealTimeMonitoring() {\n        if (realTimeConnection) {\n            clearInterval(realTimeConnection);\n            realTimeConnection = null;\n        }\n    }\n    \n    /**\n     * Show success message\n     */\n    function showSuccess(message) {\n        showNotice(message, 'success');\n    }\n    \n    /**\n     * Show error message\n     */\n    function showError(message) {\n        showNotice(message, 'error');\n    }\n    \n    /**\n     * Show info message\n     */\n    function showInfo(message) {\n        showNotice(message, 'info');\n    }\n    \n    /**\n     * Show notice\n     */\n    function showNotice(message, type) {\n        const $notice = $('<div class=\"notice notice-' + type + ' is-dismissible\"><p>' + message + '</p></div>');\n        \n        $('.wrap h1').after($notice);\n        \n        // Auto-dismiss after 5 seconds\n        setTimeout(function() {\n            $notice.fadeOut(function() {\n                $notice.remove();\n            });\n        }, 5000);\n        \n        // Add dismiss functionality\n        $notice.on('click', '.notice-dismiss', function() {\n            $notice.remove();\n        });\n    }\n    \n    // Cleanup on page unload\n    $(window).on('beforeunload', function() {\n        stopAutoRefresh();\n        stopRealTimeMonitoring();\n    });\n    \n})(jQuery);