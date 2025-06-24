<?php
/**
 * Theme Customizer Admin Interface
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Theme_Customizer_Admin {
    
    private static $instance = null;
    private $theme_manager;
    private $css_variables;
    
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
        $this->theme_manager = GF_JS_Embed_Theme_Manager::get_instance();
        $this->css_variables = GF_JS_Embed_CSS_Variables::get_instance();
        
        // Initialize help system
        require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-theme-help.php';
        GF_JS_Embed_Theme_Help::get_instance();
        
        // Add admin hooks
        add_action('admin_menu', [$this, 'add_theme_customizer_menu'], 30);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_customizer_scripts']);
        
        // Add AJAX handlers
        add_action('wp_ajax_gf_js_embed_preview_theme', [$this, 'ajax_preview_theme']);
        add_action('wp_ajax_gf_js_embed_save_custom_theme', [$this, 'ajax_save_custom_theme']);
        add_action('wp_ajax_gf_js_embed_load_custom_theme', [$this, 'ajax_load_custom_theme']);
        add_action('wp_ajax_gf_js_embed_delete_custom_theme', [$this, 'ajax_delete_custom_theme']);
        add_action('wp_ajax_gf_js_embed_duplicate_theme', [$this, 'ajax_duplicate_theme']);
        add_action('wp_ajax_gf_js_embed_bulk_delete_themes', [$this, 'ajax_bulk_delete_themes']);
        add_action('wp_ajax_gf_js_embed_search_themes', [$this, 'ajax_search_themes']);
        add_action('wp_ajax_gf_js_embed_get_theme_stats', [$this, 'ajax_get_theme_stats']);
        add_action('wp_ajax_gf_js_embed_increment_theme_usage', [$this, 'ajax_increment_theme_usage']);
        add_action('wp_ajax_gf_js_embed_batch_export_themes', [$this, 'ajax_batch_export_themes']);
        add_action('wp_ajax_gf_js_embed_batch_import_themes', [$this, 'ajax_batch_import_themes']);
        add_action('wp_ajax_gf_js_embed_share_theme', [$this, 'ajax_share_theme']);
    }
    
    /**
     * Add theme customizer menu
     */
    public function add_theme_customizer_menu() {
        /**
         * Filters the required capability for accessing the theme customizer
         * 
         * @since 2.0.0
         * 
         * @param string $capability Default capability required (gravityforms_edit_forms)
         */
        $required_capability = apply_filters('gf_js_embed_theme_customizer_capability', 'gravityforms_edit_forms');
        
        // Only show if user has proper capabilities
        if (!current_user_can($required_capability)) {
            return;
        }
        
        add_submenu_page(
            'gf_edit_forms',
            __('Theme Customizer', 'gf-js-embed'),
            __('Theme Customizer', 'gf-js-embed'),
            $required_capability,
            'gf-js-embed-theme-customizer',
            [$this, 'render_customizer_page']
        );
    }
    
    /**
     * Enqueue customizer scripts and styles
     */
    public function enqueue_customizer_scripts($hook) {
        if (strpos($hook, 'gf-js-embed-theme-customizer') === false) {
            return;
        }
        
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue customizer styles
        wp_enqueue_style(
            'gf-js-embed-theme-customizer',
            GF_JS_EMBED_PLUGIN_URL . 'assets/css/theme-customizer.css',
            ['wp-color-picker'],
            GF_JS_EMBED_VERSION
        );
        
        // Enqueue customizer script
        wp_enqueue_script(
            'gf-js-embed-theme-customizer',
            GF_JS_EMBED_PLUGIN_URL . 'assets/js/theme-customizer.js',
            ['jquery', 'wp-color-picker', 'jquery-ui-slider'],
            GF_JS_EMBED_VERSION,
            true
        );
        
        // Localize script with data
        $help = GF_JS_Embed_Theme_Help::get_instance();
        $localize_data = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gf_js_embed_customizer'),
            'variables' => $this->css_variables->get_variable_definitions(),
            'categories' => $this->get_variable_categories(),
            'predefinedThemes' => $this->theme_manager->get_predefined_themes(),
            'customThemes' => $this->theme_manager->get_custom_themes(),
            'strings' => [
                'saving' => __('Saving...', 'gf-js-embed'),
                'saved' => __('Theme saved successfully!', 'gf-js-embed'),
                'error' => __('Error saving theme', 'gf-js-embed'),
                'confirmDelete' => __('Are you sure you want to delete this theme?', 'gf-js-embed'),
                'confirmBulkDelete' => __('Are you sure you want to delete the selected themes?', 'gf-js-embed'),
                'enterThemeName' => __('Enter a name for your custom theme:', 'gf-js-embed'),
                'enterNewThemeName' => __('Enter a name for the duplicated theme:', 'gf-js-embed'),
                'invalidThemeName' => __('Please enter a valid theme name', 'gf-js-embed'),
                'searchThemes' => __('Search themes...', 'gf-js-embed'),
                'noResults' => __('No themes found matching your search.', 'gf-js-embed'),
                'duplicating' => __('Duplicating theme...', 'gf-js-embed'),
                'duplicated' => __('Theme duplicated successfully!', 'gf-js-embed'),
                'deleting' => __('Deleting themes...', 'gf-js-embed'),
                'deleted' => __('Themes deleted successfully!', 'gf-js-embed'),
                'help' => __('Help', 'gf-js-embed'),
                'gettingStarted' => __('Getting Started', 'gf-js-embed'),
                'showHelp' => __('Show Help', 'gf-js-embed'),
                'hideHelp' => __('Hide Help', 'gf-js-embed'),
                'keyboardShortcuts' => __('Keyboard Shortcuts', 'gf-js-embed'),
                'faq' => __('Frequently Asked Questions', 'gf-js-embed')
            ],
            'help' => $help->get_all_help_content()
        ];
        
        $localize_data = apply_filters('gf_js_embed_theme_customizer_localize', $localize_data);
        wp_localize_script('gf-js-embed-theme-customizer', 'gfJsEmbedCustomizer', $localize_data);
    }
    
    /**
     * Get variable categories for the UI
     */
    private function get_variable_categories() {
        return [
            'colors' => [
                'label' => __('Colors', 'gf-js-embed'),
                'icon' => 'dashicons-art',
                'description' => __('Customize colors for text, backgrounds, borders, and states', 'gf-js-embed')
            ],
            'typography' => [
                'label' => __('Typography', 'gf-js-embed'),
                'icon' => 'dashicons-editor-textcolor',
                'description' => __('Adjust fonts, sizes, weights, and line heights', 'gf-js-embed')
            ],
            'spacing' => [
                'label' => __('Spacing', 'gf-js-embed'),
                'icon' => 'dashicons-editor-expand',
                'description' => __('Control margins, padding, and field spacing', 'gf-js-embed')
            ],
            'design' => [
                'label' => __('Design', 'gf-js-embed'),
                'icon' => 'dashicons-admin-customizer',
                'description' => __('Set border radius, shadows, and transitions', 'gf-js-embed')
            ]
        ];
    }
    
    /**
     * Render the main customizer page
     */
    public function render_customizer_page() {
        ?>
        <div class="wrap gf-js-embed-theme-customizer">
            <h1>
                <?php _e('Gravity Forms Theme Customizer', 'gf-js-embed'); ?>
                <button type="button" class="page-title-action" id="show-help-panel">
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php _e('Help', 'gf-js-embed'); ?>
                </button>
                <button type="button" class="page-title-action" id="show-shortcuts">
                    <span class="dashicons dashicons-admin-network"></span>
                    <?php _e('Shortcuts', 'gf-js-embed'); ?>
                </button>
            </h1>
            <p class="description"><?php _e('Create and customize themes for your embedded Gravity Forms using visual controls.', 'gf-js-embed'); ?></p>
            
            <!-- Help Panel -->
            <div id="gf-help-panel" class="gf-help-panel" style="display: none;">
                <button type="button" class="gf-help-close" aria-label="<?php echo esc_attr__('Close help panel', 'gf-js-embed'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
                <div class="gf-help-content">
                    <!-- Help content will be populated by JavaScript -->
                </div>
            </div>
            
            <div class="gf-customizer-container">
                <!-- Sidebar Controls -->
                <div class="gf-customizer-sidebar">
                    <div class="gf-customizer-header">
                        <div class="gf-customizer-actions">
                            <button type="button" class="button button-primary" id="save-custom-theme">
                                <?php _e('Save Theme', 'gf-js-embed'); ?>
                            </button>
                            <button type="button" class="button" id="reset-theme">
                                <?php _e('Reset', 'gf-js-embed'); ?>
                            </button>
                            <button type="button" class="button" id="export-theme">
                                <?php _e('Export', 'gf-js-embed'); ?>
                            </button>
                            <button type="button" class="button" id="import-theme">
                                <?php _e('Import', 'gf-js-embed'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Theme Selector -->
                    <div class="gf-customizer-section">
                        <h3><?php _e('Start with a Theme', 'gf-js-embed'); ?></h3>
                        
                        <!-- Theme Search -->
                        <div class="gf-theme-search">
                            <input type="text" id="theme-search" placeholder="<?php echo esc_attr__('Search themes...', 'gf-js-embed'); ?>" />
                            <button type="button" id="clear-search" class="dashicons dashicons-no-alt" title="<?php echo esc_attr__('Clear search', 'gf-js-embed'); ?>"></button>
                        </div>
                        
                        <!-- Theme Management Actions -->
                        <div class="gf-theme-actions">
                            <button type="button" id="select-all-themes" class="button button-small"><?php _e('Select All', 'gf-js-embed'); ?></button>
                            <button type="button" id="bulk-delete-themes" class="button button-small" disabled><?php _e('Delete Selected', 'gf-js-embed'); ?></button>
                            <button type="button" id="batch-export-themes" class="button button-small" disabled><?php _e('Export Selected', 'gf-js-embed'); ?></button>
                            <button type="button" id="batch-import-themes" class="button button-small"><?php _e('Import', 'gf-js-embed'); ?></button>
                            <button type="button" id="refresh-themes" class="button button-small"><?php _e('Refresh', 'gf-js-embed'); ?></button>
                        </div>
                        
                        <div class="gf-theme-selector">
                            <!-- Category Filters -->
                            <div class="gf-theme-filters">
                                <button type="button" class="gf-filter-btn active" data-filter="all"><?php _e('All', 'gf-js-embed'); ?></button>
                                <button type="button" class="gf-filter-btn" data-filter="business"><?php _e('Business', 'gf-js-embed'); ?></button>
                                <button type="button" class="gf-filter-btn" data-filter="modern"><?php _e('Modern', 'gf-js-embed'); ?></button>
                                <button type="button" class="gf-filter-btn" data-filter="creative"><?php _e('Creative', 'gf-js-embed'); ?></button>
                                <button type="button" class="gf-filter-btn" data-filter="custom"><?php _e('Custom', 'gf-js-embed'); ?></button>
                            </div>
                            
                            <div class="gf-predefined-themes">
                                <h4><?php _e('Predefined Themes', 'gf-js-embed'); ?></h4>
                                <div id="predefined-themes-list"></div>
                            </div>
                            <div class="gf-custom-themes">
                                <h4><?php _e('Your Custom Themes', 'gf-js-embed'); ?></h4>
                                <div id="custom-themes-list"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category Tabs -->
                    <div class="gf-customizer-section">
                        <div class="gf-customizer-tabs">
                            <?php foreach ($this->get_variable_categories() as $category_id => $category): ?>
                                <button type="button" class="gf-tab-button" data-category="<?php echo esc_attr($category_id); ?>">
                                    <span class="dashicons <?php echo esc_attr($category['icon']); ?>"></span>
                                    <?php echo esc_html($category['label']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Control Panels -->
                    <div class="gf-customizer-controls">
                        <?php $this->render_control_panels(); ?>
                    </div>
                </div>
                
                <!-- Preview Panel -->
                <div class="gf-customizer-preview">
                    <div class="gf-preview-header">
                        <h3><?php _e('Live Preview', 'gf-js-embed'); ?></h3>
                        <div class="gf-preview-controls">
                            <select id="preview-form-selector">
                                <option value=""><?php _e('Select a form to preview', 'gf-js-embed'); ?></option>
                                <?php $this->render_form_options(); ?>
                            </select>
                        </div>
                    </div>
                    <div class="gf-preview-frame">
                        <iframe id="theme-preview-frame" src="about:blank"></iframe>
                    </div>
                </div>
            </div>
            
            <!-- Hidden Import Input -->
            <input type="file" id="theme-import-input" accept=".json" style="display: none;">
        </div>
        <?php
    }
    
    /**
     * Render control panels for each category
     */
    private function render_control_panels() {
        $categories = $this->get_variable_categories();
        $variables = $this->css_variables->get_variable_definitions();
        
        foreach ($categories as $category_id => $category) {
            echo '<div class="gf-control-panel" data-category="' . esc_attr($category_id) . '">';
            echo '<h3>' . esc_html($category['label']) . '</h3>';
            echo '<p class="description">' . esc_html($category['description']) . '</p>';
            
            // Get variables for this category
            $category_variables = array_filter($variables, function($var) use ($category_id) {
                return $var['category'] === $category_id;
            });
            
            foreach ($category_variables as $var_name => $var_def) {
                $this->render_variable_control($var_name, $var_def);
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Render individual variable control
     */
    private function render_variable_control($var_name, $var_def) {
        $control_id = 'control-' . str_replace('--gf-', '', $var_name);
        
        echo '<div class="gf-variable-control" data-variable="' . esc_attr($var_name) . '">';
        echo '<label for="' . esc_attr($control_id) . '">' . esc_html($var_def['description']) . '</label>';
        
        switch ($var_def['type']) {
            case 'color':
                echo '<input type="text" id="' . esc_attr($control_id) . '" class="gf-color-picker" value="' . esc_attr($var_def['default']) . '" />';
                break;
                
            case 'size':
                $min = isset($var_def['min']) ? floatval($var_def['min']) : 0;
                $max = isset($var_def['max']) ? floatval($var_def['max']) : 100;
                $default = floatval($var_def['default']);
                
                echo '<div class="gf-size-control">';
                echo '<input type="range" id="' . esc_attr($control_id) . '" class="gf-size-slider" min="' . $min . '" max="' . $max . '" value="' . $default . '" />';
                echo '<input type="text" class="gf-size-input" value="' . esc_attr($var_def['default']) . '" />';
                echo '</div>';
                break;
                
            case 'font-family':
                echo '<select id="' . esc_attr($control_id) . '" class="gf-font-family-select">';
                $this->render_font_family_options($var_def['default']);
                echo '</select>';
                break;
                
            case 'font-weight':
                echo '<select id="' . esc_attr($control_id) . '" class="gf-font-weight-select">';
                $this->render_font_weight_options($var_def['default']);
                echo '</select>';
                break;
                
            case 'number':
                $min = isset($var_def['min']) ? $var_def['min'] : 0;
                $max = isset($var_def['max']) ? $var_def['max'] : 10;
                $step = 0.1;
                
                echo '<input type="number" id="' . esc_attr($control_id) . '" class="gf-number-input" min="' . $min . '" max="' . $max . '" step="' . $step . '" value="' . esc_attr($var_def['default']) . '" />';
                break;
                
            default:
                echo '<input type="text" id="' . esc_attr($control_id) . '" class="gf-text-input" value="' . esc_attr($var_def['default']) . '" />';
                break;
        }
        
        // Add validation indicator
        echo '<div class="gf-validation-indicator" style="display: none;">';
        echo '<span class="gf-validation-icon"></span>';
        echo '<span class="gf-validation-message"></span>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render font family options
     */
    private function render_font_family_options($current_value) {
        $fonts = [
            'System Default' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            'Arial' => 'Arial, sans-serif',
            'Helvetica' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
            'Georgia' => 'Georgia, serif',
            'Times New Roman' => '"Times New Roman", Times, serif',
            'Verdana' => 'Verdana, sans-serif',
            'Trebuchet MS' => '"Trebuchet MS", sans-serif',
            'Impact' => 'Impact, sans-serif',
            'Courier New' => '"Courier New", monospace'
        ];
        
        foreach ($fonts as $name => $family) {
            $selected = ($family === $current_value) ? 'selected' : '';
            echo '<option value="' . esc_attr($family) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }
    }
    
    /**
     * Render font weight options
     */
    private function render_font_weight_options($current_value) {
        $weights = [
            '100' => 'Thin (100)',
            '200' => 'Extra Light (200)',
            '300' => 'Light (300)',
            '400' => 'Normal (400)',
            '500' => 'Medium (500)',
            '600' => 'Semi Bold (600)',
            '700' => 'Bold (700)',
            '800' => 'Extra Bold (800)',
            '900' => 'Black (900)'
        ];
        
        foreach ($weights as $value => $label) {
            $selected = ($value === $current_value) ? 'selected' : '';
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
    }
    
    /**
     * Render form options for preview
     */
    private function render_form_options() {
        if (!class_exists('GFAPI')) {
            return;
        }
        
        $forms = GFAPI::get_forms();
        foreach ($forms as $form) {
            echo '<option value="' . esc_attr($form['id']) . '">' . esc_html($form['title']) . '</option>';
        }
    }
    
    /**
     * AJAX handler for theme preview
     */
    public function ajax_preview_theme() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        $variables = isset($_POST['variables']) ? $_POST['variables'] : [];
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id || !current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        // Generate CSS from variables
        $css = $this->css_variables->generate_css_variables($variables);
        
        // Get form HTML (simplified for preview)
        $form_html = $this->get_preview_form_html($form_id);
        
        // Return complete HTML page for iframe
        $html = $this->generate_preview_html($form_html, $css);
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * AJAX handler for saving custom theme
     */
    public function ajax_save_custom_theme() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $theme_name = sanitize_text_field($_POST['theme_name']);
        $variables = isset($_POST['variables']) ? $_POST['variables'] : [];
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if (empty($theme_name)) {
            wp_send_json_error(['message' => __('Theme name is required', 'gf-js-embed')]);
        }
        
        $theme_data = [
            'name' => $theme_name,
            'description' => $description,
            'variables' => $variables
        ];
        
        // Create a mock request for the theme manager
        $request = new WP_REST_Request('POST');
        $request->set_body_params($theme_data);
        
        $result = $this->theme_manager->save_custom_theme($request);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('Theme saved successfully!', 'gf-js-embed')]);
    }
    
    /**
     * AJAX handler for loading custom theme
     */
    public function ajax_load_custom_theme() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $theme_name = sanitize_text_field($_POST['theme_name']);
        $custom_themes = $this->theme_manager->get_custom_themes();
        
        if (isset($custom_themes[$theme_name])) {
            wp_send_json_success(['theme' => $custom_themes[$theme_name]]);
        } else {
            wp_send_json_error(['message' => __('Theme not found', 'gf-js-embed')]);
        }
    }
    
    /**
     * AJAX handler for deleting custom theme
     */
    public function ajax_delete_custom_theme() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $theme_name = sanitize_text_field($_POST['theme_name']);
        
        if (empty($theme_name)) {
            wp_send_json_error(['message' => __('Theme name is required', 'gf-js-embed')]);
        }
        
        $result = $this->theme_manager->delete_custom_theme($theme_name);
        
        if ($result) {
            wp_send_json_success(['message' => __('Theme deleted successfully!', 'gf-js-embed')]);
        } else {
            wp_send_json_error(['message' => __('Theme not found or could not be deleted', 'gf-js-embed')]);
        }
    }
    
    /**
     * Get preview form HTML
     */
    private function get_preview_form_html($form_id) {
        if (!class_exists('GFAPI')) {
            return '<p>Form preview not available</p>';
        }
        
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return '<p>Form not found</p>';
        }
        
        // Return a simplified form structure for preview
        return sprintf(
            '<div class="gform_wrapper" id="gform_wrapper_%d">
                <div class="gform_heading">
                    <h2 class="gform_title">%s</h2>
                    <span class="gform_description">%s</span>
                </div>
                <form method="post" id="gform_%d">
                    <div class="gform_body">
                        <ul id="gform_fields_%d" class="gform_fields">
                            <li class="gfield gfield_text">
                                <label class="gfield_label">Sample Text Field</label>
                                <div class="ginput_container">
                                    <input type="text" class="gform_input" placeholder="Enter your text here" />
                                </div>
                            </li>
                            <li class="gfield gfield_email">
                                <label class="gfield_label">Email Address</label>
                                <div class="ginput_container">
                                    <input type="email" class="gform_input" placeholder="Enter your email" />
                                </div>
                            </li>
                            <li class="gfield gfield_textarea">
                                <label class="gfield_label">Message</label>
                                <div class="ginput_container">
                                    <textarea class="gform_input" rows="4" placeholder="Enter your message"></textarea>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="gform_footer">
                        <input type="submit" class="gform_button button" value="Submit" />
                    </div>
                </form>
            </div>',
            $form_id,
            esc_html($form['title']),
            esc_html($form['description'] ?? ''),
            $form_id,
            $form_id
        );
    }
    
    /**
     * Generate complete HTML for preview iframe
     */
    private function generate_preview_html($form_html, $css) {
        return sprintf(
            '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Theme Preview</title>
                <style>
                    body { 
                        margin: 20px; 
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        background: var(--gf-bg-color, #fff);
                        color: var(--gf-text-color, #333);
                    }
                    %s
                    %s
                </style>
            </head>
            <body>
                %s
            </body>
            </html>',
            $css,
            $this->get_form_base_styles(),
            $form_html
        );
    }
    
    /**
     * Get base form styles for preview
     */
    private function get_form_base_styles() {
        return '
        .gform_wrapper {
            max-width: 600px;
            margin: 0 auto;
        }
        .gform_title {
            color: var(--gf-text-color);
            font-size: var(--gf-font-size-title);
            margin-bottom: var(--gf-spacing-md);
        }
        .gform_description {
            color: var(--gf-text-muted);
            font-size: var(--gf-font-size-small);
            display: block;
            margin-bottom: var(--gf-spacing-lg);
        }
        .gform_fields {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .gfield {
            margin-bottom: var(--gf-field-margin);
        }
        .gfield_label {
            display: block;
            font-weight: var(--gf-font-weight-medium);
            margin-bottom: var(--gf-spacing-sm);
            color: var(--gf-text-color);
        }
        .gform_input {
            width: 100%;
            padding: var(--gf-input-padding);
            border: var(--gf-input-border-width) solid var(--gf-border-color);
            border-radius: var(--gf-border-radius-sm);
            font-size: var(--gf-font-size-base);
            font-family: var(--gf-font-family);
            background: var(--gf-bg-color);
            color: var(--gf-text-color);
            transition: var(--gf-transition-fast);
            box-sizing: border-box;
        }
        .gform_input:focus {
            border-color: var(--gf-border-focus);
            box-shadow: var(--gf-shadow-focus);
            outline: none;
        }
        .gform_button {
            background: var(--gf-primary-color);
            color: #fff;
            border: none;
            padding: var(--gf-button-padding);
            border-radius: var(--gf-border-radius-sm);
            font-size: var(--gf-font-size-base);
            font-weight: var(--gf-font-weight-medium);
            cursor: pointer;
            transition: var(--gf-transition-fast);
        }
        .gform_button:hover {
            background: var(--gf-primary-hover);
        }
        .gform_footer {
            margin-top: var(--gf-spacing-lg);
        }
        ';
    }
    
    /**
     * AJAX handler for duplicating theme
     */
    public function ajax_duplicate_theme() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $source_theme = sanitize_text_field($_POST['source_theme']);
        $new_name = sanitize_text_field($_POST['new_name']);
        
        if (empty($source_theme) || empty($new_name)) {
            wp_send_json_error(['message' => __('Source theme and new name are required', 'gf-js-embed')]);
        }
        
        // Create a mock request for the theme manager
        $request = new WP_REST_Request('POST');
        $request->set_param('source_theme', $source_theme);
        $request->set_param('new_name', $new_name);
        
        $result = $this->theme_manager->duplicate_theme($request);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result->get_data());
    }
    
    /**
     * AJAX handler for bulk deleting themes
     */
    public function ajax_bulk_delete_themes() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $theme_names = $_POST['themes'] ?? [];
        
        if (!is_array($theme_names) || empty($theme_names)) {
            wp_send_json_error(['message' => __('No themes selected for deletion', 'gf-js-embed')]);
        }
        
        // Create a mock request for the theme manager
        $request = new WP_REST_Request('DELETE');
        $request->set_param('themes', $theme_names);
        
        $result = $this->theme_manager->bulk_delete_themes($request);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result->get_data());
    }
    
    /**
     * AJAX handler for searching themes
     */
    public function ajax_search_themes() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $include_predefined = !empty($_POST['include_predefined']);
        
        $results = $this->theme_manager->search_themes($query, $include_predefined);
        
        wp_send_json_success(['results' => $results]);
    }
    
    /**
     * AJAX handler for getting theme statistics
     */
    public function ajax_get_theme_stats() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $request = new WP_REST_Request('GET');
        $result = $this->theme_manager->get_theme_stats($request);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result->get_data());
    }
    
    /**
     * AJAX handler for incrementing theme usage
     */
    public function ajax_increment_theme_usage() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $theme_id = sanitize_text_field($_POST['theme_id']);
        
        if (empty($theme_id)) {
            wp_send_json_error(['message' => __('Theme ID is required', 'gf-js-embed')]);
        }
        
        $this->theme_manager->increment_theme_usage($theme_id);
        
        wp_send_json_success(['message' => __('Usage count updated', 'gf-js-embed')]);
    }
    
    /**
     * AJAX handler for batch exporting themes
     */
    public function ajax_batch_export_themes() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $theme_names = $_POST['themes'] ?? [];
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        
        if (!is_array($theme_names) || empty($theme_names)) {
            wp_send_json_error(['message' => __('No themes selected for export', 'gf-js-embed')]);
        }
        
        // Create a mock request for the theme manager
        $request = new WP_REST_Request('POST');
        $request->set_param('themes', $theme_names);
        $request->set_param('format', $format);
        
        $result = $this->theme_manager->batch_export_themes($request);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result->get_data());
    }
    
    /**
     * AJAX handler for batch importing themes
     */
    public function ajax_batch_import_themes() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        // Check for file upload
        if (!empty($_FILES['import_file'])) {
            $file = $_FILES['import_file'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(['message' => __('File upload failed', 'gf-js-embed')]);
            }
            
            $file_content = file_get_contents($file['tmp_name']);
            
            // Check if it's a ZIP file
            if ($file['type'] === 'application/zip' || pathinfo($file['name'], PATHINFO_EXTENSION) === 'zip') {
                // Extract themes.json from ZIP
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === TRUE) {
                    $themes_json = $zip->getFromName('themes.json');
                    if ($themes_json === false) {
                        wp_send_json_error(['message' => __('Invalid theme archive: themes.json not found', 'gf-js-embed')]);
                    }
                    $file_content = $themes_json;
                    $zip->close();
                } else {
                    wp_send_json_error(['message' => __('Could not open ZIP file', 'gf-js-embed')]);
                }
            }
            
            $import_data = json_decode($file_content, true);
            
            if (!$import_data) {
                wp_send_json_error(['message' => __('Invalid JSON data', 'gf-js-embed')]);
            }
            
        } else {
            // Check for JSON data in POST
            $import_data = json_decode(stripslashes($_POST['import_data'] ?? ''), true);
        }
        
        if (!$import_data) {
            wp_send_json_error(['message' => __('No import data provided', 'gf-js-embed')]);
        }
        
        // Create a mock request for the theme manager
        $request = new WP_REST_Request('POST');
        $request->set_body_params($import_data);
        
        $result = $this->theme_manager->batch_import_themes($request);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result->get_data());
    }
    
    /**
     * AJAX handler for sharing theme
     */
    public function ajax_share_theme() {
        check_ajax_referer('gf_js_embed_customizer', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_die('Unauthorized', 403);
        }
        
        $theme_name = sanitize_text_field($_POST['theme_name']);
        
        if (empty($theme_name)) {
            wp_send_json_error(['message' => __('Theme name is required', 'gf-js-embed')]);
        }
        
        // Create a mock request for the theme manager
        $request = new WP_REST_Request('POST');
        $request->set_param('theme_name', $theme_name);
        
        $result = $this->theme_manager->generate_share_url($request);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success($result->get_data());
    }
}