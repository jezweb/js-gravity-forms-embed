/**
 * Theme Customizer JavaScript
 */

(function($) {
    'use strict';

    class GFThemeCustomizer {
        constructor() {
            this.currentVariables = {};
            this.currentTheme = null;
            this.previewFrame = null;
            this.debounceTimeout = null;
            this.selectedThemes = new Set();
            this.currentFilter = 'all';
            this.searchQuery = '';
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initColorPickers();
            this.initSliders();
            this.loadPredefinedThemes();
            this.loadCustomThemes();
            this.setupPreview();
            this.initHelpSystem();
            
            // Show first tab by default
            this.showTab('colors');
        }

        bindEvents() {
            // Tab switching
            $('.gf-tab-button').on('click', (e) => {
                const category = $(e.currentTarget).data('category');
                this.showTab(category);
            });

            // Theme selection
            $(document).on('click', '.gf-theme-card', (e) => {
                this.selectTheme(e.currentTarget);
            });

            // Control changes
            $(document).on('change input', '.gf-color-picker, .gf-size-slider, .gf-size-input, .gf-font-family-select, .gf-font-weight-select, .gf-number-input, .gf-text-input', (e) => {
                this.handleControlChange(e.currentTarget);
            });

            // Size control sync
            $(document).on('input', '.gf-size-slider', (e) => {
                const $slider = $(e.currentTarget);
                const $input = $slider.siblings('.gf-size-input');
                const unit = this.extractUnit($input.val());
                $input.val($slider.val() + unit);
                this.handleControlChange(e.currentTarget);
            });

            $(document).on('input', '.gf-size-input', (e) => {
                const $input = $(e.currentTarget);
                const $slider = $input.siblings('.gf-size-slider');
                const numericValue = parseFloat($input.val());
                if (!isNaN(numericValue)) {
                    $slider.val(numericValue);
                }
                this.handleControlChange(e.currentTarget);
            });

            // Action buttons
            $('#save-custom-theme').on('click', () => this.saveCustomTheme());
            $('#reset-theme').on('click', () => this.resetTheme());
            $('#export-theme').on('click', () => this.exportTheme());
            $('#import-theme').on('click', () => this.showImportDialog());

            // Help system
            $('#show-help-panel').on('click', () => this.toggleHelpPanel());
            $('#show-shortcuts').on('click', () => this.showShortcuts());
            $('.gf-help-close').on('click', () => this.closeHelpPanel());
            $(document).on('click', '.gf-theme-help-trigger', (e) => {
                const context = $(e.currentTarget).data('help-context');
                this.showContextualHelp(context);
            });
            $(document).on('click', '.gf-help-category-trigger', (e) => {
                const category = $(e.currentTarget).data('category');
                this.showCategoryHelp(category);
            });
            $(document).on('click', '.gf-faq-question', (e) => {
                $(e.currentTarget).parent('.gf-faq-item').toggleClass('active');
            });

            // Import handling
            $('#theme-import-input').on('change', (e) => this.importTheme(e));

            // Keyboard shortcuts
            this.bindKeyboardShortcuts();

            // Form selector for preview
            $('#preview-form-selector').on('change', (e) => {
                this.updatePreview();
            });

            // Custom theme actions
            $(document).on('click', '.delete-theme', (e) => {
                e.stopPropagation();
                this.deleteCustomTheme($(e.currentTarget).data('theme'));
            });
            
            $(document).on('click', '.duplicate-theme', (e) => {
                e.stopPropagation();
                this.duplicateTheme($(e.currentTarget).data('theme'));
            });
            
            // Theme selection checkboxes
            $(document).on('change', '.theme-checkbox', (e) => {
                this.handleThemeSelection(e);
            });
            
            // Search functionality
            $('#theme-search').on('input', (e) => {
                this.searchQuery = e.target.value;
                this.debounceSearch();
            });
            
            $('#clear-search').on('click', () => {
                $('#theme-search').val('');
                this.searchQuery = '';
                this.filterThemes();
            });
            
            // Filter buttons
            $('.gf-filter-btn').on('click', (e) => {
                const filter = $(e.currentTarget).data('filter');
                this.setFilter(filter);
            });
            
            // Bulk actions
            $('#select-all-themes').on('click', () => this.selectAllThemes());
            $('#bulk-delete-themes').on('click', () => this.bulkDeleteThemes());
            $('#refresh-themes').on('click', () => this.refreshThemes());
            
            // Batch export/import
            $('#batch-export-themes').on('click', () => this.batchExportThemes());
            $('#batch-import-themes').on('click', () => this.showBatchImportDialog());
            
            // Share theme
            $(document).on('click', '.share-theme', (e) => {
                e.stopPropagation();
                this.shareTheme($(e.currentTarget).data('theme'));
            });
        }

        initColorPickers() {
            $('.gf-color-picker').wpColorPicker({
                change: (event, ui) => {
                    this.handleControlChange(event.target);
                },
                clear: (event) => {
                    // Handle color clear
                    this.handleControlChange(event.target);
                }
            });
        }

        initSliders() {
            $('.gf-size-slider').each(function() {
                const $slider = $(this);
                const min = parseFloat($slider.attr('min'));
                const max = parseFloat($slider.attr('max'));
                const value = parseFloat($slider.val());
                
                $slider.slider({
                    min: min,
                    max: max,
                    value: value,
                    step: 1,
                    slide: function(event, ui) {
                        $(this).val(ui.value);
                        const $input = $(this).siblings('.gf-size-input');
                        const unit = this.extractUnit($input.val());
                        $input.val(ui.value + unit);
                    }
                });
            });
        }

        showTab(category) {
            // Update tab buttons
            $('.gf-tab-button').removeClass('active');
            $(`.gf-tab-button[data-category="${category}"]`).addClass('active');

            // Update panels
            $('.gf-control-panel').removeClass('active');
            $(`.gf-control-panel[data-category="${category}"]`).addClass('active');
        }

        loadPredefinedThemes() {
            const container = $('#predefined-themes-list');
            const themes = gfJsEmbedCustomizer.predefinedThemes;

            container.empty();
            
            // Load categorized predefined themes
            Object.keys(themes).forEach(categoryId => {
                const category = themes[categoryId];
                
                Object.keys(category.themes).forEach(themeId => {
                    const theme = category.themes[themeId];
                    const $card = this.createThemeCard(themeId, theme, false, categoryId);
                    container.append($card);
                });
            });
        }

        loadCustomThemes() {
            const container = $('#custom-themes-list');
            const themes = gfJsEmbedCustomizer.customThemes;

            container.empty();
            
            if (Object.keys(themes).length === 0) {
                container.append('<p class="no-themes">No custom themes yet. Create your first one!</p>');
                return;
            }

            Object.keys(themes).forEach(themeId => {
                const theme = themes[themeId];
                const $card = this.createThemeCard(themeId, theme, true);
                container.append($card);
            });
        }

        createThemeCard(themeId, theme, isCustom, category = '') {
            const tags = (theme.tags || []).join(', ');
            const version = theme.version || '1.0.0';
            const usageCount = theme.usage_count || 0;
            
            const $card = $(`
                <div class="gf-theme-card" data-theme="${themeId}" data-custom="${isCustom}" data-category="${category}" data-tags="${tags}">
                    <div class="theme-header">
                        ${isCustom ? `<input type="checkbox" class="theme-checkbox" data-theme="${themeId}" />` : ''}
                        <h5>${this.escapeHtml(theme.name)}</h5>
                        <div class="theme-meta">
                            ${isCustom ? `<span class="version">v${version}</span>` : ''}
                            ${category ? `<span class="category">${category}</span>` : ''}
                        </div>
                    </div>
                    <p>${this.escapeHtml(theme.description || '')}</p>
                    ${tags ? `<div class="theme-tags">${this.escapeHtml(tags)}</div>` : ''}
                    ${isCustom ? `
                        <div class="theme-stats">
                            <small>Used ${usageCount} times</small>
                            ${theme.created_at ? `<small>Created ${this.formatDate(theme.created_at)}</small>` : ''}
                        </div>
                    ` : ''}
                    <div class="theme-actions">
                        <button type="button" class="button button-small share-theme" data-theme="${themeId}" title="Share">
                            <span class="dashicons dashicons-share"></span>
                        </button>
                        <button type="button" class="button button-small duplicate-theme" data-theme="${themeId}" title="Duplicate">
                            <span class="dashicons dashicons-admin-page"></span>
                        </button>
                        ${isCustom ? `
                            <button type="button" class="button button-small delete-theme" data-theme="${themeId}" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `);

            return $card;
        }

        selectTheme(themeElement) {
            const $theme = $(themeElement);
            const themeId = $theme.data('theme');
            const isCustom = $theme.data('custom');
            const category = $theme.data('category');

            // Update UI
            $('.gf-theme-card').removeClass('active');
            $theme.addClass('active');

            // Load theme variables
            if (isCustom) {
                const customThemes = gfJsEmbedCustomizer.customThemes;
                if (customThemes[themeId]) {
                    this.currentVariables = { ...customThemes[themeId].variables };
                }
            } else {
                const predefinedThemes = gfJsEmbedCustomizer.predefinedThemes;
                if (category && predefinedThemes[category] && predefinedThemes[category].themes[themeId]) {
                    // Start with defaults and apply theme overrides
                    this.currentVariables = this.getDefaultVariables();
                    Object.assign(this.currentVariables, predefinedThemes[category].themes[themeId].variables);
                }
            }

            this.currentTheme = themeId;
            this.updateControls();
            this.updatePreview();
            
            // Increment usage count for custom themes
            if (isCustom) {
                this.incrementThemeUsage(themeId);
            }
        }

        getDefaultVariables() {
            const defaults = {};
            const variables = gfJsEmbedCustomizer.variables;
            
            Object.keys(variables).forEach(varName => {
                defaults[varName] = variables[varName].default;
            });
            
            return defaults;
        }

        updateControls() {
            Object.keys(this.currentVariables).forEach(varName => {
                const value = this.currentVariables[varName];
                const $control = $(`.gf-variable-control[data-variable="${varName}"]`);
                
                if ($control.length) {
                    const $input = $control.find('input, select, textarea').first();
                    
                    if ($input.hasClass('gf-color-picker')) {
                        $input.wpColorPicker('color', value);
                    } else if ($input.hasClass('gf-size-slider')) {
                        const numericValue = parseFloat(value);
                        $input.val(numericValue);
                        $input.siblings('.gf-size-input').val(value);
                    } else {
                        $input.val(value);
                    }
                }
            });
        }

        handleControlChange(control) {
            const $control = $(control).closest('.gf-variable-control');
            const varName = $control.data('variable');
            let value;

            if ($(control).hasClass('gf-color-picker')) {
                value = $(control).wpColorPicker('color');
            } else {
                value = $(control).val();
            }

            this.currentVariables[varName] = value;
            
            // Update validation indicator
            this.showValidationIndicator();
            
            // Debounce preview updates
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(() => {
                this.updatePreview();
            }, 300);
        }

        setupPreview() {
            this.previewFrame = document.getElementById('theme-preview-frame');
            
            // Load default theme initially
            this.currentVariables = this.getDefaultVariables();
            this.updateControls();
            this.updatePreview();
        }

        updatePreview() {
            const formId = $('#preview-form-selector').val();
            
            if (!formId) {
                this.previewFrame.src = 'about:blank';
                return;
            }

            this.showLoading();

            const data = {
                action: 'gf_js_embed_preview_theme',
                nonce: gfJsEmbedCustomizer.nonce,
                variables: this.currentVariables,
                form_id: formId
            };

            $.post(gfJsEmbedCustomizer.ajaxUrl, data)
                .done((response) => {
                    if (response.success && response.data.html) {
                        // Create blob URL for iframe content
                        const blob = new Blob([response.data.html], { type: 'text/html' });
                        const url = URL.createObjectURL(blob);
                        this.previewFrame.src = url;
                        
                        // Clean up previous blob URL
                        this.previewFrame.onload = () => {
                            URL.revokeObjectURL(url);
                        };
                    }
                })
                .fail(() => {
                    this.showNotification('Error loading preview', 'error');
                })
                .always(() => {
                    this.hideLoading();
                });
        }

        saveCustomTheme() {
            const themeName = prompt(gfJsEmbedCustomizer.strings.enterThemeName);
            
            if (!themeName || themeName.trim() === '') {
                this.showNotification(gfJsEmbedCustomizer.strings.invalidThemeName, 'error');
                return;
            }

            this.showLoading();

            const data = {
                action: 'gf_js_embed_save_custom_theme',
                nonce: gfJsEmbedCustomizer.nonce,
                theme_name: themeName.trim(),
                description: '',
                variables: this.currentVariables
            };

            $.post(gfJsEmbedCustomizer.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotification(gfJsEmbedCustomizer.strings.saved, 'success');
                        
                        // Show warnings if any
                        if (response.data.warnings && response.data.warnings.length > 0) {
                            this.showThemeWarnings(response.data.warnings);
                        }
                        
                        // Show performance metrics if available
                        if (response.data.performance) {
                            this.showPerformanceInfo(response.data.performance);
                        }
                        
                        // Refresh custom themes list
                        location.reload(); // Simple reload for now
                    } else {
                        this.showNotification(response.data.message || gfJsEmbedCustomizer.strings.error, 'error');
                    }
                })
                .fail(() => {
                    this.showNotification(gfJsEmbedCustomizer.strings.error, 'error');
                })
                .always(() => {
                    this.hideLoading();
                });
        }

        resetTheme() {
            this.currentVariables = this.getDefaultVariables();
            this.updateControls();
            this.updatePreview();
            $('.gf-theme-card').removeClass('active');
            this.currentTheme = null;
        }

        exportTheme() {
            if (!this.currentTheme || Object.keys(this.currentVariables).length === 0) {
                this.showNotification('Please select or customize a theme first', 'error');
                return;
            }

            const exportData = {
                name: this.currentTheme,
                description: '',
                variables: this.currentVariables,
                exported: new Date().toISOString()
            };

            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `gf-theme-${this.currentTheme}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            URL.revokeObjectURL(url);
            
            this.showNotification('Theme exported successfully', 'success');
        }

        showImportDialog() {
            $('#theme-import-input').click();
        }

        importTheme(event) {
            const file = event.target.files[0];
            
            if (!file) {
                return;
            }

            if (file.type !== 'application/json') {
                this.showNotification('Please select a valid JSON file', 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const themeData = JSON.parse(e.target.result);
                    
                    if (!themeData.variables || !themeData.name) {
                        this.showNotification('Invalid theme file format', 'error');
                        return;
                    }

                    // Load the imported theme
                    this.currentVariables = { ...themeData.variables };
                    this.currentTheme = themeData.name;
                    this.updateControls();
                    this.updatePreview();
                    
                    this.showNotification('Theme imported successfully', 'success');
                    
                } catch (error) {
                    this.showNotification('Error reading theme file', 'error');
                }
            };
            
            reader.readAsText(file);
            
            // Reset input
            event.target.value = '';
        }

        deleteCustomTheme(themeName) {
            if (!confirm(gfJsEmbedCustomizer.strings.confirmDelete)) {
                return;
            }

            const data = {
                action: 'gf_js_embed_delete_custom_theme',
                nonce: gfJsEmbedCustomizer.nonce,
                theme_name: themeName
            };

            $.post(gfJsEmbedCustomizer.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotification('Theme deleted successfully', 'success');
                        location.reload(); // Simple reload for now
                    } else {
                        this.showNotification('Error deleting theme', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Error deleting theme', 'error');
                });
        }

        extractUnit(value) {
            const match = value.toString().match(/[a-zA-Z%]+$/);
            return match ? match[0] : 'px';
        }

        showLoading() {
            $('.gf-customizer-preview').addClass('gf-loading');
        }

        hideLoading() {
            $('.gf-customizer-preview').removeClass('gf-loading');
        }

        showNotification(message, type = 'info') {
            const $notification = $(`<div class="gf-notification ${type}">${this.escapeHtml(message)}</div>`);
            $('body').append($notification);
            
            // Trigger animation
            setTimeout(() => {
                $notification.addClass('show');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 3000);
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }
        
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.filterThemes();
            }, 300);
        }
        
        setFilter(filter) {
            this.currentFilter = filter;
            $('.gf-filter-btn').removeClass('active');
            $(`.gf-filter-btn[data-filter="${filter}"]`).addClass('active');
            this.filterThemes();
        }
        
        filterThemes() {
            const $themes = $('.gf-theme-card');
            
            $themes.each((index, element) => {
                const $theme = $(element);
                const category = $theme.data('category') || '';
                const isCustom = $theme.data('custom');
                const name = $theme.find('h5').text().toLowerCase();
                const description = $theme.find('p').text().toLowerCase();
                const tags = $theme.data('tags') || '';
                
                let show = true;
                
                // Apply category filter
                if (this.currentFilter !== 'all') {
                    if (this.currentFilter === 'custom') {
                        show = isCustom;
                    } else {
                        show = category === this.currentFilter;
                    }
                }
                
                // Apply search filter
                if (show && this.searchQuery) {
                    const searchableText = `${name} ${description} ${tags}`.toLowerCase();
                    show = searchableText.includes(this.searchQuery.toLowerCase());
                }
                
                $theme.toggle(show);
            });
        }
        
        handleThemeSelection(event) {
            const themeId = $(event.target).data('theme');
            
            if (event.target.checked) {
                this.selectedThemes.add(themeId);
            } else {
                this.selectedThemes.delete(themeId);
            }
            
            // Update bulk action buttons
            $('#bulk-delete-themes').prop('disabled', this.selectedThemes.size === 0);
            $('#batch-export-themes').prop('disabled', this.selectedThemes.size === 0);
        }
        
        selectAllThemes() {
            const $visibleCheckboxes = $('.gf-theme-card:visible .theme-checkbox');
            const allSelected = $visibleCheckboxes.length > 0 && $visibleCheckboxes.filter(':checked').length === $visibleCheckboxes.length;
            
            $visibleCheckboxes.prop('checked', !allSelected);
            
            this.selectedThemes.clear();
            if (!allSelected) {
                $visibleCheckboxes.each((index, checkbox) => {
                    this.selectedThemes.add($(checkbox).data('theme'));
                });
            }
            
            $('#bulk-delete-themes').prop('disabled', this.selectedThemes.size === 0);
            $('#batch-export-themes').prop('disabled', this.selectedThemes.size === 0);
            $('#select-all-themes').text(allSelected ? 'Select All' : 'Deselect All');
        }
        
        duplicateTheme(sourceTheme) {
            const newName = prompt(gfJsEmbedCustomizer.strings.enterNewThemeName);
            
            if (!newName || newName.trim() === '') {
                this.showNotification(gfJsEmbedCustomizer.strings.invalidThemeName, 'error');
                return;
            }
            
            this.showLoading();
            
            const data = {
                action: 'gf_js_embed_duplicate_theme',
                nonce: gfJsEmbedCustomizer.nonce,
                source_theme: sourceTheme,
                new_name: newName.trim()
            };
            
            $.post(gfJsEmbedCustomizer.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotification(gfJsEmbedCustomizer.strings.duplicated, 'success');
                        this.refreshThemes();
                    } else {
                        this.showNotification(response.data.message || gfJsEmbedCustomizer.strings.error, 'error');
                    }
                })
                .fail(() => {
                    this.showNotification(gfJsEmbedCustomizer.strings.error, 'error');
                })
                .always(() => {
                    this.hideLoading();
                });
        }
        
        bulkDeleteThemes() {
            if (this.selectedThemes.size === 0) {
                return;
            }
            
            if (!confirm(gfJsEmbedCustomizer.strings.confirmBulkDelete)) {
                return;
            }
            
            this.showLoading();
            
            const data = {
                action: 'gf_js_embed_bulk_delete_themes',
                nonce: gfJsEmbedCustomizer.nonce,
                themes: Array.from(this.selectedThemes)
            };
            
            $.post(gfJsEmbedCustomizer.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotification(gfJsEmbedCustomizer.strings.deleted, 'success');
                        this.selectedThemes.clear();
                        this.refreshThemes();
                    } else {
                        this.showNotification(response.data.message || gfJsEmbedCustomizer.strings.error, 'error');
                    }
                })
                .fail(() => {
                    this.showNotification(gfJsEmbedCustomizer.strings.error, 'error');
                })
                .always(() => {
                    this.hideLoading();
                });
        }
        
        refreshThemes() {
            location.reload(); // Simple refresh for now
        }
        
        incrementThemeUsage(themeId) {
            // Silent background request to increment usage
            $.post(gfJsEmbedCustomizer.ajaxUrl, {
                action: 'gf_js_embed_increment_theme_usage',
                nonce: gfJsEmbedCustomizer.nonce,
                theme_id: themeId
            });
        }
        
        searchThemes(query) {
            const data = {
                action: 'gf_js_embed_search_themes',
                nonce: gfJsEmbedCustomizer.nonce,
                query: query,
                include_predefined: true
            };
            
            return $.post(gfJsEmbedCustomizer.ajaxUrl, data);
        }
        
        showThemeWarnings(warnings) {
            if (!warnings || warnings.length === 0) return;
            
            const warningHtml = warnings.map(warning => 
                `<li><span class=\"dashicons dashicons-warning\"></span> ${this.escapeHtml(warning)}</li>`
            ).join('');
            
            const $modal = $(`
                <div class="gf-warnings-modal">
                    <div class="gf-warnings-content">
                        <h3>Theme Validation Warnings</h3>
                        <p>Your theme was saved successfully, but there are some recommendations:</p>
                        <ul class="gf-warnings-list">${warningHtml}</ul>
                        <div class="gf-warnings-actions">
                            <button type="button" class="button" id="close-warnings">Got it</button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append($modal);
            
            $('#close-warnings').on('click', () => {
                $modal.fadeOut(() => $modal.remove());
            });
            
            // Auto close after 10 seconds
            setTimeout(() => {
                if ($modal.is(':visible')) {
                    $modal.fadeOut(() => $modal.remove());
                }
            }, 10000);
        }
        
        showPerformanceInfo(performance) {
            if (!performance) return;
            
            let message = `Theme complexity: ${performance.complexity_score} | CSS size: ${performance.css_size} bytes`;
            
            if (performance.warnings && performance.warnings.length > 0) {
                message += ' (Performance warnings detected)';
                this.showNotification(message, 'warning');
            } else {
                // Show as info only if no warnings
                console.log('Theme Performance:', performance);
            }
        }
        
        validateCurrentTheme() {
            if (Object.keys(this.currentVariables).length === 0) {
                return { valid: true, warnings: [] };
            }
            
            const warnings = [];
            
            // Check for potential issues
            const textColor = this.currentVariables['--gf-text-color'];
            const bgColor = this.currentVariables['--gf-bg-color'];
            
            if (textColor && bgColor && textColor === bgColor) {
                warnings.push('Text and background colors are the same');
            }
            
            // Check for very small font sizes
            const fontSize = this.currentVariables['--gf-font-size-base'];
            if (fontSize && parseFloat(fontSize) < 12) {
                warnings.push('Font size may be too small for readability');
            }
            
            return {
                valid: warnings.length === 0,
                warnings: warnings
            };
        }
        
        showValidationIndicator() {
            const validation = this.validateCurrentTheme();
            const $indicator = $('.gf-validation-indicator');
            
            if ($indicator.length === 0) {
                const $newIndicator = $(`
                    <div class="gf-validation-indicator">
                        <span class="indicator-icon"></span>
                        <span class="indicator-text"></span>
                    </div>
                `);
                $('.gf-customizer-header').append($newIndicator);
            }
            
            const $icon = $('.indicator-icon');
            const $text = $('.indicator-text');
            
            if (validation.valid) {
                $icon.removeClass('warning error').addClass('success dashicons dashicons-yes-alt');
                $text.text('Theme looks good');
                $('.gf-validation-indicator').removeClass('has-warnings has-errors').addClass('valid');
            } else {
                $icon.removeClass('success error').addClass('warning dashicons dashicons-warning');
                $text.text(`${validation.warnings.length} warning(s)`);
                $('.gf-validation-indicator').removeClass('valid has-errors').addClass('has-warnings');
                
                // Show tooltip with warnings
                $('.gf-validation-indicator').attr('title', validation.warnings.join(', '));
            }
        }
        
        batchExportThemes() {
            const themes = Array.from(this.selectedThemes);
            
            if (themes.length === 0) {
                this.showNotification(gfJsEmbedCustomizer.strings.selectThemesForExport || 'Please select themes to export', 'error');
                return;
            }
            
            // Ask for export format
            const format = confirm('Export as ZIP file? (OK for ZIP, Cancel for JSON)') ? 'zip' : 'json';
            
            this.showLoading();
            
            const data = {
                action: 'gf_js_embed_batch_export_themes',
                nonce: gfJsEmbedCustomizer.nonce,
                themes: themes,
                format: format
            };
            
            $.post(gfJsEmbedCustomizer.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.downloadExport(response.data);
                        this.showNotification(gfJsEmbedCustomizer.strings.exportSuccess || 'Themes exported successfully', 'success');
                    } else {
                        this.showNotification(response.data.message || gfJsEmbedCustomizer.strings.error, 'error');
                    }
                })
                .fail(() => {
                    this.showNotification(gfJsEmbedCustomizer.strings.error, 'error');
                })
                .always(() => {
                    this.hideLoading();
                });
        }
        
        downloadExport(exportData) {
            let blob, filename;
            
            if (exportData.format === 'zip') {
                // Decode base64 ZIP data
                const binaryString = atob(exportData.data);
                const bytes = new Uint8Array(binaryString.length);
                for (let i = 0; i < binaryString.length; i++) {
                    bytes[i] = binaryString.charCodeAt(i);
                }
                blob = new Blob([bytes], { type: 'application/zip' });
                filename = exportData.filename;
            } else {
                // JSON export
                blob = new Blob([JSON.stringify(exportData.data, null, 2)], { type: 'application/json' });
                filename = exportData.filename;
            }
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        showBatchImportDialog() {
            // Create import dialog
            const $dialog = $(`
                <div class="gf-import-dialog gf-warnings-modal">
                    <div class="gf-warnings-content">
                        <h3>${gfJsEmbedCustomizer.strings.importThemes || 'Import Themes'}</h3>
                        <p>${gfJsEmbedCustomizer.strings.importDescription || 'Select a theme export file (JSON or ZIP) to import:'}</p>
                        <div class="gf-import-options">
                            <input type="file" id="batch-import-file" accept=".json,.zip" />
                            <div class="import-actions">
                                <button type="button" class="button button-primary" id="do-batch-import">Import</button>
                                <button type="button" class="button" id="cancel-batch-import">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append($dialog);
            
            $('#do-batch-import').on('click', () => {
                const file = $('#batch-import-file')[0].files[0];
                if (!file) {
                    this.showNotification('Please select a file to import', 'error');
                    return;
                }
                this.doBatchImport(file);
                $dialog.remove();
            });
            
            $('#cancel-batch-import').on('click', () => {
                $dialog.remove();
            });
        }
        
        doBatchImport(file) {
            this.showLoading();
            
            const formData = new FormData();
            formData.append('action', 'gf_js_embed_batch_import_themes');
            formData.append('nonce', gfJsEmbedCustomizer.nonce);
            formData.append('import_file', file);
            
            $.ajax({
                url: gfJsEmbedCustomizer.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || 'Import successful', 'success');
                        
                        // Show import details
                        if (response.data.details) {
                            this.showImportResults(response.data.details);
                        }
                        
                        // Refresh themes
                        setTimeout(() => {
                            this.refreshThemes();
                        }, 2000);
                    } else {
                        this.showNotification(response.data.message || 'Import failed', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Import failed', 'error');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }
        
        showImportResults(details) {
            let message = '';
            
            if (details.imported && details.imported.length > 0) {
                message += `Imported: ${details.imported.length} theme(s)\n`;
                details.imported.forEach(item => {
                    if (item.new_name) {
                        message += `- ${item.original_name} → ${item.new_name} (renamed)\n`;
                    } else {
                        message += `- ${item.name}\n`;
                    }
                });
            }
            
            if (details.errors && details.errors.length > 0) {
                message += `\nErrors: ${details.errors.length}\n`;
                details.errors.forEach(error => {
                    message += `- ${error.theme}: ${error.error}\n`;
                });
            }
            
            console.log('Import Results:', message);
        }
        
        shareTheme(themeName) {
            this.showLoading();
            
            const data = {
                action: 'gf_js_embed_share_theme',
                nonce: gfJsEmbedCustomizer.nonce,
                theme_name: themeName
            };
            
            $.post(gfJsEmbedCustomizer.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.showShareDialog(response.data);
                    } else {
                        this.showNotification(response.data.message || 'Error generating share link', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Error generating share link', 'error');
                })
                .always(() => {
                    this.hideLoading();
                });
        }
        
        showShareDialog(shareData) {
            const $dialog = $(`
                <div class="gf-share-dialog gf-warnings-modal">
                    <div class="gf-warnings-content">
                        <h3>Share Theme: ${this.escapeHtml(shareData.theme_name)}</h3>
                        <p>Share this link to allow others to import your theme:</p>
                        <div class="share-url-container">
                            <input type="text" id="share-url" value="${shareData.share_url}" readonly />
                            <button type="button" class="button" id="copy-share-url">
                                <span class="dashicons dashicons-clipboard"></span> Copy
                            </button>
                        </div>
                        <p class="description">This link expires in ${shareData.expires_in}</p>
                        <div class="gf-warnings-actions">
                            <button type="button" class="button" id="close-share-dialog">Close</button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append($dialog);
            
            // Select the URL on focus
            $('#share-url').on('focus', function() {
                this.select();
            });
            
            // Copy to clipboard
            $('#copy-share-url').on('click', () => {
                const input = document.getElementById('share-url');
                input.select();
                document.execCommand('copy');
                this.showNotification('Share link copied to clipboard!', 'success');
            });
            
            $('#close-share-dialog').on('click', () => {
                $dialog.remove();
            });
        }

        // Help System Methods
        initHelpSystem() {
            this.helpContent = gfJsEmbedCustomizer.help || {};
            this.setupHelpTooltips();
        }

        setupHelpTooltips() {
            if (this.helpContent.tooltips) {
                Object.keys(this.helpContent.tooltips).forEach(variable => {
                    const $control = $(`.gf-variable-control[data-variable="${variable}"]`);
                    const $helpBtn = $control.find('.gf-theme-help-trigger');
                    if ($helpBtn.length) {
                        $helpBtn.attr('title', this.helpContent.tooltips[variable]);
                    }
                });
            }
        }

        toggleHelpPanel() {
            const $panel = $('#gf-help-panel');
            if ($panel.hasClass('active')) {
                this.closeHelpPanel();
            } else {
                this.openHelpPanel('getting_started');
            }
        }

        openHelpPanel(section = 'getting_started') {
            const $panel = $('#gf-help-panel');
            const $content = $panel.find('.gf-help-content');
            
            // Render help content based on section
            let html = '';
            switch(section) {
                case 'getting_started':
                    html = this.renderGettingStarted();
                    break;
                case 'shortcuts':
                    html = this.renderKeyboardShortcuts();
                    break;
                case 'faq':
                    html = this.renderFAQ();
                    break;
                default:
                    html = this.renderContextualHelp(section);
            }
            
            $content.html(html);
            $panel.addClass('active');
            
            // Add overlay
            if (!$('.gf-help-overlay').length) {
                $('body').append('<div class="gf-help-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99998;"></div>');
                $('.gf-help-overlay').on('click', () => this.closeHelpPanel());
            }
        }

        closeHelpPanel() {
            $('#gf-help-panel').removeClass('active');
            $('.gf-help-overlay').remove();
        }

        showContextualHelp(context) {
            this.openHelpPanel(context);
        }

        showCategoryHelp(category) {
            this.openHelpPanel('category_' + category);
        }

        showShortcuts() {
            this.openHelpPanel('shortcuts');
        }

        renderGettingStarted() {
            const content = this.helpContent.getting_started || {};
            let html = '<h2>' + (content.title || 'Getting Started') + '</h2>';
            
            if (content.steps) {
                html += '<div class="gf-getting-started-steps">';
                content.steps.forEach(step => {
                    html += `
                        <div class="gf-step">
                            <div class="gf-step-icon">
                                <span class="dashicons ${step.icon}"></span>
                            </div>
                            <div class="gf-step-content">
                                <h5>${step.title}</h5>
                                <p>${step.content}</p>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }
            
            if (content.tips && content.tips.length) {
                html += '<div class="gf-help-tips">';
                html += '<h5>Helpful Tips:</h5>';
                html += '<ul>';
                content.tips.forEach(tip => {
                    html += `<li>${tip}</li>`;
                });
                html += '</ul>';
                html += '</div>';
            }
            
            // Add navigation buttons
            html += '<div class="gf-help-nav" style="margin-top: 30px; text-align: center;">';
            html += `<button type="button" class="button" onclick="window.customizer.openHelpPanel('shortcuts')">${gfJsEmbedCustomizer.strings.keyboardShortcuts || 'Keyboard Shortcuts'}</button> `;
            html += `<button type="button" class="button" onclick="window.customizer.openHelpPanel('faq')">${gfJsEmbedCustomizer.strings.faq || 'FAQ'}</button>`;
            html += '</div>';
            
            return html;
        }

        renderKeyboardShortcuts() {
            const shortcuts = this.helpContent.shortcuts || {};
            let html = '<h2>' + (gfJsEmbedCustomizer.strings.keyboardShortcuts || 'Keyboard Shortcuts') + '</h2>';
            
            Object.keys(shortcuts).forEach(category => {
                const categoryData = shortcuts[category];
                html += `<h3>${categoryData.title}</h3>`;
                html += '<table class="gf-shortcuts-table">';
                
                Object.keys(categoryData.shortcuts).forEach(key => {
                    const description = categoryData.shortcuts[key];
                    const formattedKey = key.split(' ').map(k => `<kbd>${k}</kbd>`).join(' ');
                    html += `
                        <tr>
                            <td style="width: 40%;">${formattedKey}</td>
                            <td>${description}</td>
                        </tr>
                    `;
                });
                
                html += '</table>';
            });
            
            return html;
        }

        renderFAQ() {
            const faq = this.helpContent.faq || {};
            let html = '<h2>' + (gfJsEmbedCustomizer.strings.faq || 'Frequently Asked Questions') + '</h2>';
            
            html += '<div class="gf-faq-section">';
            Object.keys(faq).forEach(category => {
                const categoryData = faq[category];
                html += `<div class="gf-faq-category">`;
                html += `<h4>${categoryData.title}</h4>`;
                
                categoryData.items.forEach((item, index) => {
                    html += `
                        <div class="gf-faq-item">
                            <button class="gf-faq-question">${item.question}</button>
                            <div class="gf-faq-answer">${item.answer}</div>
                        </div>
                    `;
                });
                
                html += '</div>';
            });
            html += '</div>';
            
            return html;
        }

        renderContextualHelp(context) {
            const controls = this.helpContent.controls || {};
            let html = '<h2>' + (gfJsEmbedCustomizer.strings.help || 'Help') + '</h2>';
            
            // Find the relevant help content
            let helpData = null;
            Object.keys(controls).forEach(category => {
                if (controls[category].items && controls[category].items[context]) {
                    helpData = controls[category].items[context];
                }
            });
            
            if (helpData) {
                html += `<h3>${helpData.label}</h3>`;
                html += `<p>${helpData.help}</p>`;
                
                if (helpData.tips && helpData.tips.length) {
                    html += '<div class="gf-help-tips">';
                    html += '<h5>Tips:</h5>';
                    html += '<ul>';
                    helpData.tips.forEach(tip => {
                        html += `<li>${tip}</li>`;
                    });
                    html += '</ul>';
                    html += '</div>';
                }
            }
            
            return html;
        }

        bindKeyboardShortcuts() {
            $(document).on('keydown', (e) => {
                // Only work when not in an input field
                if ($(e.target).is('input, textarea, select')) {
                    return;
                }
                
                const key = e.key.toLowerCase();
                const ctrl = e.ctrlKey || e.metaKey;
                
                // Ctrl/Cmd + S: Save theme
                if (ctrl && key === 's') {
                    e.preventDefault();
                    this.saveCustomTheme();
                }
                
                // Ctrl/Cmd + Z: Undo (reset theme)
                if (ctrl && key === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    this.resetTheme();
                }
                
                // Ctrl/Cmd + N: New theme
                if (ctrl && key === 'n') {
                    e.preventDefault();
                    this.createNewTheme();
                }
                
                // Ctrl/Cmd + E: Export themes
                if (ctrl && key === 'e') {
                    e.preventDefault();
                    if ($('.theme-checkbox:checked').length > 0) {
                        this.batchExportThemes();
                    } else {
                        this.exportTheme();
                    }
                }
                
                // Escape: Close dialogs
                if (key === 'escape') {
                    this.closeHelpPanel();
                    $('.gf-warnings-modal').remove();
                }
            });
        }

        createNewTheme() {
            // Reset to default theme and clear current selection
            this.currentTheme = null;
            this.currentVariables = {};
            $('.gf-theme-card').removeClass('active');
            // Reset all controls to default
            Object.keys(gfJsEmbedCustomizer.variables).forEach(varName => {
                const varDef = gfJsEmbedCustomizer.variables[varName];
                this.currentVariables[varName] = varDef.default;
            });
            this.updateControls();
            this.updatePreview();
            this.showNotification(gfJsEmbedCustomizer.strings.newThemeCreated || 'New theme created. Customize and save it.');
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        if (typeof gfJsEmbedCustomizer !== 'undefined') {
            window.customizer = new GFThemeCustomizer();
        }
    });

})(jQuery);