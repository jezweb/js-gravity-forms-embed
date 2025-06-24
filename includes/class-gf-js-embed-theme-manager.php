<?php
/**
 * Theme Manager class for CSS variable-based theming
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Theme_Manager {
    
    private static $instance = null;
    private $css_variables;
    private $cache_group = 'gf_js_embed_themes';
    private $cache_ttl = HOUR_IN_SECONDS;
    
    /**
     * Available CSS variable categories
     */
    private $variable_categories = [
        'colors' => [
            'primary' => [
                'label' => 'Primary Color',
                'variables' => ['--gf-primary-color', '--gf-primary-hover', '--gf-primary-focus'],
                'default' => '#0073aa'
            ],
            'text' => [
                'label' => 'Text Colors',
                'variables' => ['--gf-text-color', '--gf-text-muted', '--gf-text-light'],
                'default' => '#333'
            ],
            'background' => [
                'label' => 'Background Colors',
                'variables' => ['--gf-bg-color', '--gf-bg-alt', '--gf-bg-dark'],
                'default' => '#fff'
            ],
            'border' => [
                'label' => 'Border Colors',
                'variables' => ['--gf-border-color', '--gf-border-focus', '--gf-border-error'],
                'default' => '#ddd'
            ],
            'state' => [
                'label' => 'State Colors',
                'variables' => ['--gf-success-color', '--gf-error-color', '--gf-warning-color'],
                'default' => '#34a853'
            ]
        ],
        'typography' => [
            'font-family' => [
                'label' => 'Font Family',
                'variables' => ['--gf-font-family'],
                'default' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
            ],
            'font-sizes' => [
                'label' => 'Font Sizes',
                'variables' => ['--gf-font-size-base', '--gf-font-size-small', '--gf-font-size-large', '--gf-font-size-title'],
                'default' => '16px'
            ],
            'font-weights' => [
                'label' => 'Font Weights',
                'variables' => ['--gf-font-weight-normal', '--gf-font-weight-medium', '--gf-font-weight-bold'],
                'default' => '400'
            ]
        ],
        'spacing' => [
            'margins' => [
                'label' => 'Spacing',
                'variables' => ['--gf-spacing-xs', '--gf-spacing-sm', '--gf-spacing-md', '--gf-spacing-lg', '--gf-spacing-xl'],
                'default' => '10px'
            ],
            'padding' => [
                'label' => 'Input Padding',
                'variables' => ['--gf-input-padding', '--gf-button-padding'],
                'default' => '10px 12px'
            ]
        ],
        'design' => [
            'border-radius' => [
                'label' => 'Border Radius',
                'variables' => ['--gf-border-radius-sm', '--gf-border-radius-md', '--gf-border-radius-lg', '--gf-border-radius-xl'],
                'default' => '4px'
            ],
            'shadows' => [
                'label' => 'Shadows',
                'variables' => ['--gf-shadow-sm', '--gf-shadow-md', '--gf-shadow-lg', '--gf-shadow-focus'],
                'default' => '0 1px 3px rgba(0,0,0,0.1)'
            ]
        ]
    ];
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Hook into admin init to register theme management
        add_action('admin_init', [$this, 'init_theme_management']);
        
        // Initialize CSS variables manager
        $this->css_variables = GF_JS_Embed_CSS_Variables::get_instance();
        
        // Handle theme import from share URL
        add_action('admin_init', [$this, 'handle_share_import']);
    }
    
    /**
     * Initialize theme management
     */
    public function init_theme_management() {
        // Register REST API endpoints for theme management
        add_action('rest_api_init', [$this, 'register_theme_api_endpoints']);
    }
    
    /**
     * Register REST API endpoints for theme management
     */
    public function register_theme_api_endpoints() {
        // Get theme variables
        register_rest_route('gf-embed/v1', '/theme/variables', [
            'methods' => 'GET',
            'callback' => [$this, 'get_theme_variables'],
            'permission_callback' => [$this, 'check_theme_permissions']
        ]);
        
        // Save custom theme
        register_rest_route('gf-embed/v1', '/theme/save', [
            'methods' => 'POST',
            'callback' => [$this, 'save_custom_theme'],
            'permission_callback' => [$this, 'check_theme_permissions']
        ]);
        
        // Duplicate theme
        register_rest_route('gf-embed/v1', '/theme/duplicate', [
            'methods' => 'POST',
            'callback' => [$this, 'duplicate_theme'],
            'permission_callback' => [$this, 'check_theme_permissions'],
            'args' => [
                'source_theme' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'new_name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        // Bulk delete themes
        register_rest_route('gf-embed/v1', '/theme/bulk-delete', [
            'methods' => 'DELETE',
            'callback' => [$this, 'bulk_delete_themes'],
            'permission_callback' => [$this, 'check_theme_permissions'],
            'args' => [
                'themes' => [
                    'required' => true,
                    'type' => 'array'
                ]
            ]
        ]);
        
        // Generate theme CSS
        register_rest_route('gf-embed/v1', '/theme/css', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_theme_css'],
            'permission_callback' => [$this, 'check_theme_permissions']
        ]);
        
        // Get theme usage statistics
        register_rest_route('gf-embed/v1', '/theme/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_theme_stats'],
            'permission_callback' => [$this, 'check_theme_permissions']
        ]);
        
        // Batch export themes
        register_rest_route('gf-embed/v1', '/theme/batch-export', [
            'methods' => 'POST',
            'callback' => [$this, 'batch_export_themes'],
            'permission_callback' => [$this, 'check_theme_permissions'],
            'args' => [
                'themes' => [
                    'required' => true,
                    'type' => 'array'
                ],
                'format' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'json',
                    'enum' => ['json', 'zip']
                ]
            ]
        ]);
        
        // Batch import themes
        register_rest_route('gf-embed/v1', '/theme/batch-import', [
            'methods' => 'POST',
            'callback' => [$this, 'batch_import_themes'],
            'permission_callback' => [$this, 'check_theme_permissions']
        ]);
        
        // Generate shareable theme URL
        register_rest_route('gf-embed/v1', '/theme/share', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_share_url'],
            'permission_callback' => [$this, 'check_theme_permissions'],
            'args' => [
                'theme_name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
    }
    
    /**
     * Check permissions for theme management
     */
    public function check_theme_permissions() {
        return current_user_can('gravityforms_edit_forms');
    }
    
    /**
     * Get available theme variables
     */
    public function get_theme_variables($request) {
        return rest_ensure_response([
            'success' => true,
            'variables' => $this->css_variables->get_variable_definitions(),
            'categories' => $this->variable_categories,
            'predefined_themes' => $this->get_predefined_themes(),
            'defaults' => $this->css_variables->get_default_variables()
        ]);
    }
    
    /**
     * Save custom theme with enhanced metadata
     */
    public function save_custom_theme($request) {
        $theme_data = $request->get_json_params();
        
        if (empty($theme_data['name']) || empty($theme_data['variables'])) {
            return new WP_Error('invalid_data', 'Theme name and variables are required', ['status' => 400]);
        }
        
        // Get existing themes to check for updates
        $custom_themes = $this->get_custom_themes();
        $is_update = isset($custom_themes[$theme_data['name']]);
        
        // Sanitize theme data with enhanced metadata and validation
        try {
            $sanitized_theme = $this->sanitize_theme_data_enhanced($theme_data, $is_update, $custom_themes);
        } catch (Exception $e) {
            return new WP_Error('validation_failed', $e->getMessage(), ['status' => 400]);
        }
        
        // Save to database
        $custom_themes[$sanitized_theme['name']] = $sanitized_theme;
        update_option('gf_js_embed_custom_themes', $custom_themes);
        
        // Clear cache
        $this->clear_theme_cache();
        
        // Log the action
        do_action('gf_js_embed_theme_saved', $sanitized_theme['name'], $sanitized_theme, $is_update);
        
        $response_data = [
            'success' => true,
            'message' => $is_update ? __('Theme updated successfully', 'gf-js-embed') : __('Theme saved successfully', 'gf-js-embed'),
            'theme' => $sanitized_theme
        ];
        
        // Include validation warnings if any
        if (!empty($sanitized_theme['validation_warnings'])) {
            $response_data['warnings'] = $sanitized_theme['validation_warnings'];
        }
        
        // Include performance metrics
        if (!empty($sanitized_theme['performance_metrics'])) {
            $response_data['performance'] = $sanitized_theme['performance_metrics'];
        }
        
        return rest_ensure_response($response_data);
    }
    
    /**
     * Generate CSS from theme variables
     */
    public function generate_theme_css($request) {
        $variables = $request->get_json_params();
        
        if (empty($variables)) {
            return new WP_Error('invalid_data', 'Theme variables are required', ['status' => 400]);
        }
        
        $css = $this->build_css_from_variables($variables);
        
        return rest_ensure_response([
            'success' => true,
            'css' => $css
        ]);
    }
    
    /**
     * Get predefined themes with categories and metadata
     */
    public function get_predefined_themes() {
        // Try to get from cache first
        $cache_key = 'predefined_themes';
        $cached_themes = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached_themes !== false) {
            return $cached_themes;
        }
        
        $themes = [
            'business' => [
                'category' => 'business',
                'category_label' => __('Business', 'gf-js-embed'),
                'themes' => [
                    'default' => [
                        'name' => __('Default', 'gf-js-embed'),
                        'description' => __('Clean and modern default theme', 'gf-js-embed'),
                        'tags' => ['professional', 'clean'],
                        'variables' => [] // Uses CSS defaults
                    ],
                    'corporate' => [
                        'name' => __('Corporate', 'gf-js-embed'),
                        'description' => __('Professional corporate styling', 'gf-js-embed'),
                        'tags' => ['corporate', 'professional'],
                        'variables' => [
                            '--gf-primary-color' => '#003366',
                            '--gf-primary-hover' => '#004080',
                            '--gf-font-family' => 'Arial, sans-serif',
                            '--gf-border-color' => '#cccccc',
                            '--gf-border-radius-sm' => '3px'
                        ]
                    ],
                    'classic' => [
                        'name' => __('Classic', 'gf-js-embed'),
                        'description' => __('Traditional form styling', 'gf-js-embed'),
                        'tags' => ['traditional', 'classic'],
                        'variables' => [
                            '--gf-primary-color' => '#0073aa',
                            '--gf-font-family' => 'Georgia, serif',
                            '--gf-border-radius-sm' => '4px'
                        ]
                    ]
                ]
            ],
            'modern' => [
                'category' => 'modern',
                'category_label' => __('Modern', 'gf-js-embed'),
                'themes' => [
                    'minimal' => [
                        'name' => __('Minimal', 'gf-js-embed'),
                        'description' => __('Ultra-clean minimalist design', 'gf-js-embed'),
                        'tags' => ['minimal', 'clean'],
                        'variables' => [
                            '--gf-primary-color' => '#333333',
                            '--gf-border-color' => '#e0e0e0',
                            '--gf-border-radius-sm' => '2px',
                            '--gf-shadow-subtle' => 'none'
                        ]
                    ],
                    'sleek' => [
                        'name' => __('Sleek', 'gf-js-embed'),
                        'description' => __('Contemporary design with bold colors', 'gf-js-embed'),
                        'tags' => ['modern', 'sleek'],
                        'variables' => [
                            '--gf-primary-color' => '#6366f1',
                            '--gf-primary-hover' => '#4f46e5',
                            '--gf-border-radius-sm' => '8px',
                            '--gf-shadow-subtle' => '0 1px 3px rgba(0,0,0,0.1)'
                        ]
                    ],
                    'rounded' => [
                        'name' => __('Rounded', 'gf-js-embed'),
                        'description' => __('Soft rounded corners for modern look', 'gf-js-embed'),
                        'tags' => ['rounded', 'modern'],
                        'variables' => [
                            '--gf-border-radius-sm' => '25px',
                            '--gf-border-radius-md' => '15px',
                            '--gf-input-padding' => '12px 20px',
                            '--gf-button-padding' => '14px 30px'
                        ]
                    ]
                ]
            ],
            'creative' => [
                'category' => 'creative',
                'category_label' => __('Creative', 'gf-js-embed'),
                'themes' => [
                    'vibrant' => [
                        'name' => __('Vibrant', 'gf-js-embed'),
                        'description' => __('Bold and colorful design', 'gf-js-embed'),
                        'tags' => ['colorful', 'bold'],
                        'variables' => [
                            '--gf-primary-color' => '#ff6b6b',
                            '--gf-primary-hover' => '#ff5252',
                            '--gf-border-color' => '#ff9999',
                            '--gf-border-radius-sm' => '15px'
                        ]
                    ],
                    'dark' => [
                        'name' => __('Dark Mode', 'gf-js-embed'),
                        'description' => __('Perfect for dark-themed websites', 'gf-js-embed'),
                        'tags' => ['dark', 'modern'],
                        'variables' => [
                            '--gf-bg-color' => '#2d2d2d',
                            '--gf-text-color' => '#e0e0e0',
                            '--gf-text-muted' => '#b0b0b0',
                            '--gf-border-color' => '#4a4a4a',
                            '--gf-border-focus' => '#4a9eff',
                            '--gf-primary-color' => '#4a9eff',
                            '--gf-primary-hover' => '#3a8eef'
                        ]
                    ]
                ]
            ]
        ];
        
        /**
         * Filters predefined themes to allow adding or modifying themes
         * 
         * @since 2.0.0
         * 
         * @param array $themes Array of predefined theme categories
         */
        $themes = apply_filters('gf_js_embed_predefined_themes', $themes);
        
        // Cache the result
        wp_cache_set($cache_key, $themes, $this->cache_group, $this->cache_ttl);
        
        return $themes;
    }
    
    /**
     * Sanitize theme data
     */
    private function sanitize_theme_data($theme_data) {
        return [
            'name' => sanitize_text_field($theme_data['name']),
            'description' => sanitize_textarea_field($theme_data['description'] ?? ''),
            'variables' => $this->sanitize_css_variables($theme_data['variables']),
            'created' => current_time('mysql'),
            'modified' => current_time('mysql')
        ];
    }
    
    /**
     * Sanitize CSS variables
     */
    private function sanitize_css_variables($variables) {
        $sanitized = [];
        
        foreach ($variables as $property => $value) {
            // Use CSS variables manager for validation and sanitization
            $sanitized_value = $this->css_variables->sanitize_variable_value($property, $value);
            if ($sanitized_value) {
                $sanitized[$property] = $sanitized_value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Build CSS from variables with caching
     */
    public function build_css_from_variables($variables) {
        // Generate cache key based on variables
        $cache_key = 'css_' . md5(json_encode($variables));
        
        // Try to get from cache
        $cached_css = wp_cache_get($cache_key, $this->cache_group);
        if ($cached_css !== false) {
            return $cached_css;
        }
        
        // Generate CSS
        $css = $this->css_variables->generate_css_variables($variables);
        
        // Cache the result
        wp_cache_set($cache_key, $css, $this->cache_group, $this->cache_ttl);
        
        return $css;
    }
    
    /**
     * Get custom themes with metadata and caching
     */
    public function get_custom_themes() {
        // Try to get from cache first
        $cache_key = 'custom_themes';
        $cached_themes = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached_themes !== false) {
            return $cached_themes;
        }
        
        $themes = get_option('gf_js_embed_custom_themes', []);
        
        // Add metadata if missing
        foreach ($themes as $theme_id => &$theme) {
            if (!isset($theme['created_at'])) {
                $theme['created_at'] = current_time('mysql');
            }
            if (!isset($theme['updated_at'])) {
                $theme['updated_at'] = current_time('mysql');
            }
            if (!isset($theme['version'])) {
                $theme['version'] = '1.0.0';
            }
            if (!isset($theme['tags'])) {
                $theme['tags'] = [];
            }
            if (!isset($theme['usage_count'])) {
                $theme['usage_count'] = 0;
            }
        }
        
        // Cache the result
        wp_cache_set($cache_key, $themes, $this->cache_group, $this->cache_ttl);
        
        return $themes;
    }
    
    /**
     * Delete custom theme
     */
    public function delete_custom_theme($theme_name) {
        $custom_themes = get_option('gf_js_embed_custom_themes', []);
        
        if (isset($custom_themes[$theme_name])) {
            unset($custom_themes[$theme_name]);
            update_option('gf_js_embed_custom_themes', $custom_themes);
            
            // Clear cache
            $this->clear_theme_cache();
            
            /**
             * Fires after a theme is deleted
             * 
             * @since 2.0.0
             * 
             * @param string $theme_name Name of the deleted theme
             */
            do_action('gf_js_embed_theme_deleted', $theme_name);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear theme cache
     */
    private function clear_theme_cache() {
        wp_cache_delete('custom_themes', $this->cache_group);
        wp_cache_delete('predefined_themes', $this->cache_group);
        
        // Clear all CSS cache (pattern matching not available in object cache)
        // In production, you might want to use a more sophisticated cache clearing strategy
        wp_cache_flush_group($this->cache_group);
    }
    
    /**
     * Export theme as JSON
     */
    public function export_theme($theme_name) {
        $custom_themes = get_option('gf_js_embed_custom_themes', []);
        
        if (isset($custom_themes[$theme_name])) {
            return json_encode($custom_themes[$theme_name], JSON_PRETTY_PRINT);
        }
        
        return false;
    }
    
    /**
     * Import theme from JSON
     */
    public function import_theme($json_data, $overwrite = false) {
        $theme_data = json_decode($json_data, true);
        
        if (!$theme_data || empty($theme_data['name'])) {
            return new WP_Error('invalid_json', 'Invalid theme data');
        }
        
        $custom_themes = get_option('gf_js_embed_custom_themes', []);
        
        if (isset($custom_themes[$theme_data['name']]) && !$overwrite) {
            return new WP_Error('theme_exists', 'Theme already exists');
        }
        
        $sanitized_theme = $this->sanitize_theme_data($theme_data);
        $custom_themes[$sanitized_theme['name']] = $sanitized_theme;
        update_option('gf_js_embed_custom_themes', $custom_themes);
        
        return $sanitized_theme;
    }
    
    /**
     * Enhanced theme data sanitization with metadata
     */
    private function sanitize_theme_data_enhanced($theme_data, $is_update = false, $existing_themes = []) {
        // First validate the theme data
        $css_variables = GF_JS_Embed_CSS_Variables::get_instance();
        $validation_result = $css_variables->validate_theme_data($theme_data);
        
        if (!$validation_result['valid']) {
            throw new Exception(implode(', ', $validation_result['errors']));
        }
        
        // Store warnings for later use
        $warnings = $validation_result['warnings'] ?? [];
        
        $sanitized = [
            'name' => sanitize_text_field($theme_data['name']),
            'description' => sanitize_textarea_field($theme_data['description'] ?? ''),
            'variables' => $this->sanitize_css_variables($theme_data['variables']),
            'tags' => is_array($theme_data['tags'] ?? []) ? array_map('sanitize_text_field', $theme_data['tags']) : [],
            'parent_theme' => sanitize_text_field($theme_data['parent_theme'] ?? ''),
            'updated_at' => current_time('mysql'),
            'version' => $is_update ? $this->increment_version($existing_themes[$theme_data['name']]['version'] ?? '1.0.0') : '1.0.0',
            'validation_warnings' => $warnings
        ];
        
        // Preserve creation data for updates
        if ($is_update && isset($existing_themes[$theme_data['name']])) {
            $existing = $existing_themes[$theme_data['name']];
            $sanitized['created_at'] = $existing['created_at'] ?? current_time('mysql');
            $sanitized['usage_count'] = $existing['usage_count'] ?? 0;
        } else {
            $sanitized['created_at'] = current_time('mysql');
            $sanitized['usage_count'] = 0;
        }
        
        // Get performance metrics
        $performance = $css_variables->get_theme_performance_metrics($sanitized['variables']);
        $sanitized['performance_metrics'] = $performance;
        
        return $sanitized;
    }
    
    /**
     * Increment theme version
     */
    private function increment_version($current_version) {
        $parts = explode('.', $current_version);
        $parts[2] = isset($parts[2]) ? intval($parts[2]) + 1 : 1;
        return implode('.', array_pad($parts, 3, 0));
    }
    
    /**
     * Duplicate theme
     */
    public function duplicate_theme($request) {
        $source_theme = sanitize_text_field($request->get_param('source_theme'));
        $new_name = sanitize_text_field($request->get_param('new_name'));
        
        if (empty($source_theme) || empty($new_name)) {
            return new WP_Error('missing_params', __('Source theme and new name are required', 'gf-js-embed'), ['status' => 400]);
        }
        
        // Get source theme (check both custom and predefined)
        $custom_themes = $this->get_custom_themes();
        $source_data = null;
        
        if (isset($custom_themes[$source_theme])) {
            $source_data = $custom_themes[$source_theme];
        } else {
            // Check predefined themes
            $predefined = $this->get_predefined_themes();
            foreach ($predefined as $category => $category_data) {
                if (isset($category_data['themes'][$source_theme])) {
                    $source_data = $category_data['themes'][$source_theme];
                    break;
                }
            }
        }
        
        if (!$source_data) {
            return new WP_Error('theme_not_found', __('Source theme not found', 'gf-js-embed'), ['status' => 404]);
        }
        
        // Check if new name already exists
        if (isset($custom_themes[$new_name])) {
            return new WP_Error('theme_exists', __('A theme with this name already exists', 'gf-js-embed'), ['status' => 409]);
        }
        
        // Create new theme based on source
        $new_theme = [
            'name' => $new_name,
            'description' => sprintf(__('Copy of %s', 'gf-js-embed'), $source_data['name']),
            'variables' => $source_data['variables'] ?? [],
            'tags' => array_merge($source_data['tags'] ?? [], ['duplicate']),
            'parent_theme' => $source_theme,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'version' => '1.0.0',
            'usage_count' => 0
        ];
        
        // Save new theme
        $custom_themes[$new_name] = $new_theme;
        update_option('gf_js_embed_custom_themes', $custom_themes);
        
        return rest_ensure_response([
            'success' => true,
            'message' => __('Theme duplicated successfully', 'gf-js-embed'),
            'theme' => $new_theme
        ]);
    }
    
    /**
     * Bulk delete themes
     */
    public function bulk_delete_themes($request) {
        $theme_names = $request->get_param('themes');
        
        if (!is_array($theme_names) || empty($theme_names)) {
            return new WP_Error('invalid_themes', __('Theme list is required', 'gf-js-embed'), ['status' => 400]);
        }
        
        $custom_themes = get_option('gf_js_embed_custom_themes', []);
        $deleted_count = 0;
        $errors = [];
        
        foreach ($theme_names as $theme_name) {
            $theme_name = sanitize_text_field($theme_name);
            
            if (isset($custom_themes[$theme_name])) {
                unset($custom_themes[$theme_name]);
                $deleted_count++;
            } else {
                $errors[] = sprintf(__('Theme "%s" not found', 'gf-js-embed'), $theme_name);
            }
        }
        
        if ($deleted_count > 0) {
            update_option('gf_js_embed_custom_themes', $custom_themes);
        }
        
        return rest_ensure_response([
            'success' => true,
            'message' => sprintf(_n('%d theme deleted', '%d themes deleted', $deleted_count, 'gf-js-embed'), $deleted_count),
            'deleted_count' => $deleted_count,
            'errors' => $errors
        ]);
    }
    
    /**
     * Get theme usage statistics
     */
    public function get_theme_stats($request) {
        $custom_themes = $this->get_custom_themes();
        $predefined_themes = $this->get_predefined_themes();
        
        $stats = [
            'total_custom_themes' => count($custom_themes),
            'total_predefined_themes' => 0,
            'most_used_theme' => null,
            'recent_themes' => [],
            'themes_by_tag' => []
        ];
        
        // Count predefined themes
        foreach ($predefined_themes as $category => $category_data) {
            $stats['total_predefined_themes'] += count($category_data['themes']);
        }
        
        // Analyze custom themes
        $usage_counts = [];
        $recent_themes = [];
        $tags = [];
        
        foreach ($custom_themes as $theme_name => $theme_data) {
            $usage_counts[$theme_name] = $theme_data['usage_count'] ?? 0;
            
            // Recent themes (last 30 days)
            $created = strtotime($theme_data['created_at'] ?? '1970-01-01');
            if ($created > strtotime('-30 days')) {
                $recent_themes[] = [
                    'name' => $theme_name,
                    'created_at' => $theme_data['created_at'],
                    'usage_count' => $theme_data['usage_count'] ?? 0
                ];
            }
            
            // Tags analysis
            foreach ($theme_data['tags'] ?? [] as $tag) {
                $tags[$tag] = ($tags[$tag] ?? 0) + 1;
            }
        }
        
        // Most used theme
        if (!empty($usage_counts)) {
            $most_used = array_keys($usage_counts, max($usage_counts))[0];
            $stats['most_used_theme'] = [
                'name' => $most_used,
                'usage_count' => $usage_counts[$most_used]
            ];
        }
        
        // Sort recent themes by creation date
        usort($recent_themes, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        $stats['recent_themes'] = array_slice($recent_themes, 0, 5);
        $stats['themes_by_tag'] = $tags;
        
        return rest_ensure_response($stats);
    }
    
    /**
     * Increment theme usage count
     */
    public function increment_theme_usage($theme_name) {
        $custom_themes = get_option('gf_js_embed_custom_themes', []);
        
        if (isset($custom_themes[$theme_name])) {
            $custom_themes[$theme_name]['usage_count'] = ($custom_themes[$theme_name]['usage_count'] ?? 0) + 1;
            $custom_themes[$theme_name]['last_used'] = current_time('mysql');
            update_option('gf_js_embed_custom_themes', $custom_themes);
        }
    }
    
    /**
     * Search themes by name, description, or tags
     */
    public function search_themes($query, $include_predefined = true) {
        $results = [];
        $query = strtolower(trim($query));
        
        if (empty($query)) {
            return $results;
        }
        
        // Search custom themes
        $custom_themes = $this->get_custom_themes();
        foreach ($custom_themes as $theme_name => $theme_data) {
            $searchable = strtolower(implode(' ', [
                $theme_data['name'],
                $theme_data['description'] ?? '',
                implode(' ', $theme_data['tags'] ?? [])
            ]));
            
            if (strpos($searchable, $query) !== false) {
                $results[] = [
                    'type' => 'custom',
                    'id' => $theme_name,
                    'data' => $theme_data
                ];
            }
        }
        
        // Search predefined themes
        if ($include_predefined) {
            $predefined_themes = $this->get_predefined_themes();
            foreach ($predefined_themes as $category => $category_data) {
                foreach ($category_data['themes'] as $theme_id => $theme_data) {
                    $searchable = strtolower(implode(' ', [
                        $theme_data['name'],
                        $theme_data['description'] ?? '',
                        implode(' ', $theme_data['tags'] ?? [])
                    ]));
                    
                    if (strpos($searchable, $query) !== false) {
                        $results[] = [
                            'type' => 'predefined',
                            'category' => $category,
                            'id' => $theme_id,
                            'data' => $theme_data
                        ];
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Batch export themes
     */
    public function batch_export_themes($request) {
        $theme_names = $request->get_param('themes');
        $format = $request->get_param('format');
        
        if (!is_array($theme_names) || empty($theme_names)) {
            return new WP_Error('invalid_themes', __('Theme list is required', 'gf-js-embed'), ['status' => 400]);
        }
        
        $export_data = [
            'version' => '1.0',
            'exported' => current_time('mysql'),
            'site_url' => get_site_url(),
            'themes' => []
        ];
        
        $custom_themes = $this->get_custom_themes();
        $predefined_themes = $this->get_predefined_themes();
        
        foreach ($theme_names as $theme_name) {
            $theme_name = sanitize_text_field($theme_name);
            
            // Check custom themes first
            if (isset($custom_themes[$theme_name])) {
                $export_data['themes'][$theme_name] = $custom_themes[$theme_name];
            } else {
                // Check predefined themes
                foreach ($predefined_themes as $category => $category_data) {
                    if (isset($category_data['themes'][$theme_name])) {
                        $export_data['themes'][$theme_name] = array_merge(
                            $category_data['themes'][$theme_name],
                            ['category' => $category, 'type' => 'predefined']
                        );
                        break;
                    }
                }
            }
        }
        
        if (empty($export_data['themes'])) {
            return new WP_Error('no_themes_found', __('No valid themes found for export', 'gf-js-embed'), ['status' => 404]);
        }
        
        if ($format === 'zip') {
            // Generate ZIP file for download
            $zip_content = $this->create_theme_zip($export_data);
            
            return rest_ensure_response([
                'success' => true,
                'format' => 'zip',
                'data' => base64_encode($zip_content),
                'filename' => 'gf-themes-export-' . date('Y-m-d') . '.zip'
            ]);
        } else {
            // Return JSON
            return rest_ensure_response([
                'success' => true,
                'format' => 'json',
                'data' => $export_data,
                'filename' => 'gf-themes-export-' . date('Y-m-d') . '.json'
            ]);
        }
    }
    
    /**
     * Batch import themes
     */
    public function batch_import_themes($request) {
        $import_data = $request->get_json_params();
        
        if (!$import_data || !isset($import_data['themes'])) {
            // Try to get file upload
            $files = $request->get_file_params();
            if (isset($files['import_file'])) {
                $file_content = file_get_contents($files['import_file']['tmp_name']);
                $import_data = json_decode($file_content, true);
            }
        }
        
        if (!$import_data || !isset($import_data['themes'])) {
            return new WP_Error('invalid_data', __('Invalid import data', 'gf-js-embed'), ['status' => 400]);
        }
        
        $results = [
            'imported' => [],
            'skipped' => [],
            'errors' => []
        ];
        
        $custom_themes = $this->get_custom_themes();
        
        foreach ($import_data['themes'] as $theme_name => $theme_data) {
            try {
                // Check for conflicts
                if (isset($custom_themes[$theme_name])) {
                    // Generate new name if conflict
                    $new_name = $this->generate_unique_theme_name($theme_name, $custom_themes);
                    $theme_data['name'] = $new_name;
                    $results['imported'][] = [
                        'original_name' => $theme_name,
                        'new_name' => $new_name,
                        'reason' => 'renamed_due_to_conflict'
                    ];
                } else {
                    $results['imported'][] = [
                        'name' => $theme_name
                    ];
                }
                
                // Validate and save theme
                $sanitized_theme = $this->sanitize_theme_data_enhanced($theme_data);
                $custom_themes[$sanitized_theme['name']] = $sanitized_theme;
                
            } catch (Exception $e) {
                $results['errors'][] = [
                    'theme' => $theme_name,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Save all imported themes
        if (!empty($results['imported'])) {
            update_option('gf_js_embed_custom_themes', $custom_themes);
        }
        
        return rest_ensure_response([
            'success' => true,
            'message' => sprintf(
                __('Import complete: %d imported, %d skipped, %d errors', 'gf-js-embed'),
                count($results['imported']),
                count($results['skipped']),
                count($results['errors'])
            ),
            'details' => $results
        ]);
    }
    
    /**
     * Generate shareable theme URL
     */
    public function generate_share_url($request) {
        $theme_name = $request->get_param('theme_name');
        
        // Get theme data
        $custom_themes = $this->get_custom_themes();
        if (!isset($custom_themes[$theme_name])) {
            // Check predefined themes
            $theme_data = null;
            $predefined_themes = $this->get_predefined_themes();
            foreach ($predefined_themes as $category => $category_data) {
                if (isset($category_data['themes'][$theme_name])) {
                    $theme_data = $category_data['themes'][$theme_name];
                    break;
                }
            }
            
            if (!$theme_data) {
                return new WP_Error('theme_not_found', __('Theme not found', 'gf-js-embed'), ['status' => 404]);
            }
        } else {
            $theme_data = $custom_themes[$theme_name];
        }
        
        // Create share token
        $share_data = [
            'theme' => $theme_data,
            'created' => current_time('timestamp'),
            'expires' => current_time('timestamp') + (30 * DAY_IN_SECONDS) // 30 days expiry
        ];
        
        // Store share data as transient
        $share_token = wp_generate_password(32, false);
        set_transient('gf_theme_share_' . $share_token, $share_data, 30 * DAY_IN_SECONDS);
        
        // Generate share URL
        $share_url = add_query_arg([
            'gf_theme_import' => $share_token
        ], admin_url('admin.php?page=gf-js-embed-theme-customizer'));
        
        return rest_ensure_response([
            'success' => true,
            'share_url' => $share_url,
            'expires_in' => '30 days',
            'theme_name' => $theme_data['name']
        ]);
    }
    
    /**
     * Create ZIP file for theme export
     */
    private function create_theme_zip($export_data) {
        if (!class_exists('ZipArchive')) {
            // Fallback to JSON if ZIP not available
            return json_encode($export_data, JSON_PRETTY_PRINT);
        }
        
        $temp_file = tempnam(sys_get_temp_dir(), 'gf_themes_');
        $zip = new ZipArchive();
        
        if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
            return json_encode($export_data, JSON_PRETTY_PRINT);
        }
        
        // Add main themes.json
        $zip->addFromString('themes.json', json_encode($export_data, JSON_PRETTY_PRINT));
        
        // Add individual theme files
        foreach ($export_data['themes'] as $theme_name => $theme_data) {
            $theme_json = json_encode($theme_data, JSON_PRETTY_PRINT);
            $safe_name = sanitize_file_name($theme_name);
            $zip->addFromString("themes/{$safe_name}.json", $theme_json);
            
            // Add CSS preview
            $css = $this->build_css_from_variables($theme_data['variables'] ?? []);
            $zip->addFromString("themes/{$safe_name}.css", $css);
        }
        
        // Add README
        $readme = $this->generate_export_readme($export_data);
        $zip->addFromString('README.txt', $readme);
        
        $zip->close();
        
        $content = file_get_contents($temp_file);
        unlink($temp_file);
        
        return $content;
    }
    
    /**
     * Generate unique theme name to avoid conflicts
     */
    private function generate_unique_theme_name($base_name, $existing_themes) {
        $counter = 1;
        $new_name = $base_name;
        
        while (isset($existing_themes[$new_name])) {
            $new_name = $base_name . ' (' . $counter . ')';
            $counter++;
        }
        
        return $new_name;
    }
    
    /**
     * Generate README for theme export
     */
    private function generate_export_readme($export_data) {
        $theme_count = count($export_data['themes']);
        $date = date('Y-m-d H:i:s', strtotime($export_data['exported']));
        
        $readme = "Gravity Forms JS Embed - Theme Export\n";
        $readme .= "=====================================\n\n";
        $readme .= "Export Date: {$date}\n";
        $readme .= "Site URL: {$export_data['site_url']}\n";
        $readme .= "Theme Count: {$theme_count}\n\n";
        $readme .= "Included Themes:\n";
        $readme .= "---------------\n";
        
        foreach ($export_data['themes'] as $theme_name => $theme_data) {
            $readme .= "- {$theme_name}";
            if (!empty($theme_data['description'])) {
                $readme .= ": {$theme_data['description']}";
            }
            $readme .= "\n";
        }
        
        $readme .= "\n";
        $readme .= "Import Instructions:\n";
        $readme .= "-------------------\n";
        $readme .= "1. Go to Forms > Theme Customizer in your WordPress admin\n";
        $readme .= "2. Click the 'Import' button\n";
        $readme .= "3. Select this ZIP file or the themes.json file\n";
        $readme .= "4. Review and confirm the import\n\n";
        $readme .= "Note: If theme names conflict with existing themes, they will be automatically renamed.\n";
        
        return $readme;
    }
    
    /**
     * Handle theme import from share URL
     */
    public function handle_share_import() {
        if (!isset($_GET['gf_theme_import']) || !current_user_can('gravityforms_edit_forms')) {
            return;
        }
        
        $token = sanitize_text_field($_GET['gf_theme_import']);
        $share_data = get_transient('gf_theme_share_' . $token);
        
        if (!$share_data || $share_data['expires'] < current_time('timestamp')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('Invalid or expired theme share link.', 'gf-js-embed') . '</p></div>';
            });
            return;
        }
        
        // Import the shared theme
        try {
            $theme_data = $share_data['theme'];
            $custom_themes = $this->get_custom_themes();
            
            // Check for conflicts and rename if necessary
            if (isset($custom_themes[$theme_data['name']])) {
                $theme_data['name'] = $this->generate_unique_theme_name($theme_data['name'], $custom_themes);
            }
            
            // Save the theme
            $sanitized_theme = $this->sanitize_theme_data_enhanced($theme_data);
            $custom_themes[$sanitized_theme['name']] = $sanitized_theme;
            update_option('gf_js_embed_custom_themes', $custom_themes);
            
            // Delete the transient
            delete_transient('gf_theme_share_' . $token);
            
            // Show success message
            add_action('admin_notices', function() use ($sanitized_theme) {
                echo '<div class="notice notice-success"><p>' . sprintf(__('Theme "%s" imported successfully!', 'gf-js-embed'), esc_html($sanitized_theme['name'])) . '</p></div>';
            });
            
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>' . sprintf(__('Error importing theme: %s', 'gf-js-embed'), esc_html($e->getMessage())) . '</p></div>';
            });
        }
    }
}