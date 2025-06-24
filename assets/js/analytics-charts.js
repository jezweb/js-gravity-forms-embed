/**
 * Analytics Charts for Gravity Forms JS Embed
 */
(function($) {
    'use strict';
    
    const GFAnalyticsCharts = {
        
        /**
         * Initialize charts
         */
        init() {
            this.loadChartLibrary().then(() => {
                this.initializeCharts();
            });
        },
        
        /**
         * Load Chart.js library
         */
        async loadChartLibrary() {
            if (typeof Chart !== 'undefined') {
                return Promise.resolve();
            }
            
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },
        
        /**
         * Initialize all charts on the page
         */
        initializeCharts() {
            // Time series chart
            const timeSeriesCanvas = document.getElementById('gf-analytics-timeseries');
            if (timeSeriesCanvas) {
                this.createTimeSeriesChart(timeSeriesCanvas);
            }
            
            // Device breakdown chart
            const deviceCanvas = document.getElementById('gf-analytics-devices');
            if (deviceCanvas) {
                this.createDeviceChart(deviceCanvas);
            }
            
            // Browser breakdown chart
            const browserCanvas = document.getElementById('gf-analytics-browsers');
            if (browserCanvas) {
                this.createBrowserChart(browserCanvas);
            }
            
            // Conversion funnel chart
            const funnelCanvas = document.getElementById('gf-analytics-funnel');
            if (funnelCanvas) {
                this.createFunnelChart(funnelCanvas);
            }
            
            // Field heatmap
            const heatmapContainer = document.getElementById('gf-analytics-heatmap');
            if (heatmapContainer) {
                this.createFieldHeatmap(heatmapContainer);
            }
        },
        
        /**
         * Create time series chart
         */
        createTimeSeriesChart(canvas) {
            const formId = canvas.dataset.formId;
            const ctx = canvas.getContext('2d');
            
            this.fetchAnalyticsData(formId, 'timeseries').then(data => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: Object.keys(data.views || {}),
                        datasets: [{
                            label: 'Views',
                            data: Object.values(data.views || {}),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.1
                        }, {
                            label: 'Submissions',
                            data: Object.values(data.submissions || {}),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Views and Submissions Over Time'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        },
        
        /**
         * Create device breakdown chart
         */
        createDeviceChart(canvas) {
            const formId = canvas.dataset.formId;
            const ctx = canvas.getContext('2d');
            
            this.fetchAnalyticsData(formId, 'overview').then(data => {
                const devices = data.devices || [];
                const labels = devices.map(d => d.device_type);
                const counts = devices.map(d => d.count);
                
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: [
                                '#FF6384',
                                '#36A2EB',
                                '#FFCE56',
                                '#4BC0C0',
                                '#9966FF'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Device Types'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            });
        },
        
        /**
         * Create browser breakdown chart
         */
        createBrowserChart(canvas) {
            const formId = canvas.dataset.formId;
            const ctx = canvas.getContext('2d');
            
            this.fetchAnalyticsData(formId, 'overview').then(data => {
                const browsers = data.browsers || [];
                const labels = browsers.map(b => b.browser);
                const counts = browsers.map(b => b.count);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Users',
                            data: counts,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Browser Usage'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        },
        
        /**
         * Create conversion funnel chart
         */
        createFunnelChart(canvas) {
            const formId = canvas.dataset.formId;
            const ctx = canvas.getContext('2d');
            
            this.fetchAnalyticsData(formId, 'overview').then(data => {
                const views = data.views || 0;
                const submissions = data.submissions || 0;
                const conversionRate = data.conversion_rate || 0;
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Views', 'Submissions'],
                        datasets: [{
                            label: 'Count',
                            data: [views, submissions],
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(255, 99, 132, 0.6)'
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                                'rgba(255, 99, 132, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: `Conversion Funnel (${conversionRate}% conversion rate)`
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        },
        
        /**
         * Create field heatmap
         */
        createFieldHeatmap(container) {
            const formId = container.dataset.formId;
            
            this.fetchAnalyticsData(formId, 'heatmap').then(data => {
                let html = '<div class="gf-heatmap-grid">';
                
                if (Object.keys(data).length === 0) {
                    html += '<p>No interaction data available yet.</p>';
                } else {
                    // Create header
                    html += '<div class="gf-heatmap-header">';
                    html += '<div class="gf-heatmap-cell">Field ID</div>';
                    html += '<div class="gf-heatmap-cell">Interactions</div>';
                    html += '<div class="gf-heatmap-cell">Avg Time (s)</div>';
                    html += '<div class="gf-heatmap-cell">Errors</div>';
                    html += '<div class="gf-heatmap-cell">Error Rate</div>';
                    html += '</div>';
                    
                    // Create rows
                    Object.entries(data).forEach(([fieldId, fieldData]) => {
                        const errorClass = fieldData.error_rate > 10 ? 'high-error' : 
                                         fieldData.error_rate > 5 ? 'medium-error' : 'low-error';
                        
                        html += `<div class="gf-heatmap-row ${errorClass}">`;
                        html += `<div class="gf-heatmap-cell">${fieldId}</div>`;
                        html += `<div class="gf-heatmap-cell">${fieldData.interactions}</div>`;
                        html += `<div class="gf-heatmap-cell">${fieldData.avg_time}</div>`;
                        html += `<div class="gf-heatmap-cell">${fieldData.errors}</div>`;
                        html += `<div class="gf-heatmap-cell">${fieldData.error_rate}%</div>`;
                        html += '</div>';
                    });
                }
                
                html += '</div>';
                container.innerHTML = html;
            });
        },
        
        /**
         * Fetch analytics data from API
         */
        async fetchAnalyticsData(formId, metric) {
            try {
                const url = `/wp-json/gf-embed/v1/analytics/${formId}?metric=${metric}`;
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                return result.data || {};
            } catch (error) {
                console.error('Failed to fetch analytics data:', error);
                return {};
            }
        },
        
        /**
         * Refresh all charts
         */
        refresh() {
            // Remove existing charts
            Chart.helpers.each(Chart.instances, (instance) => {
                instance.destroy();
            });
            
            // Reinitialize
            this.initializeCharts();
        },
        
        /**
         * Update date range for all charts
         */
        updateDateRange(dateFrom, dateTo) {
            this.dateFrom = dateFrom;
            this.dateTo = dateTo;
            this.refresh();
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(() => {
        GFAnalyticsCharts.init();
        
        // Add date range picker functionality
        $('.gf-analytics-date-range').on('change', function() {
            const dateFrom = $('#gf-analytics-date-from').val();
            const dateTo = $('#gf-analytics-date-to').val();
            
            if (dateFrom && dateTo) {
                GFAnalyticsCharts.updateDateRange(dateFrom, dateTo);
            }
        });
        
        // Add refresh button functionality
        $('.gf-analytics-refresh').on('click', function() {
            GFAnalyticsCharts.refresh();
        });
    });
    
    // Expose to global scope
    window.GFAnalyticsCharts = GFAnalyticsCharts;
    
})(jQuery);