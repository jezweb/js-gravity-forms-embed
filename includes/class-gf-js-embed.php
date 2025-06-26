<?php
/**
 * Main plugin class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JavaScript_Embed {
    
    private static $instance = null;
    
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'init']);
        
        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(GF_JS_EMBED_PLUGIN_FILE), [$this, 'add_plugin_action_links']);
        add_filter('plugin_row_meta', [$this, 'add_plugin_row_meta'], 10, 2);
        
        // Initialize components
        GF_JS_Embed_API::get_instance();
        GF_JS_Embed_Admin::get_instance();
        GF_JS_Embed_Security::get_instance();
        GF_JS_Embed_Rate_Limiter::get_instance();
        GF_JS_Embed_Events::get_instance();
        GF_JS_Embed_CSRF::get_instance();
        GF_JS_Embed_MultiPage::get_instance();
        GF_JS_Embed_Conditional_Logic::get_instance();
        GF_JS_Embed_Lazy_Loading::get_instance();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load plugin text domain
        load_plugin_textdomain('gf-js-embed', false, dirname(plugin_basename(GF_JS_EMBED_PLUGIN_FILE)) . '/languages');
        
        // Register the embed script endpoint
        add_rewrite_rule(
            '^gf-js-embed/v1/embed\.js$',
            'index.php?gf_js_embed=1',
            'top'
        );
        
        add_filter('query_vars', function($vars) {
            $vars[] = 'gf_js_embed';
            return $vars;
        });
        
        add_action('template_redirect', [$this, 'serve_embed_script']);
        
        // Check if we need to flush rewrite rules
        $this->maybe_flush_rewrite_rules();
        
        // Add cron job handlers
        add_action('gf_js_embed_analytics_cleanup', [$this, 'cleanup_analytics_data']);
        add_action('gf_js_embed_analytics_aggregate', [$this, 'aggregate_analytics_data']);
        
        // Register shortcode
        add_shortcode('gf_js_embed', [$this, 'render_shortcode']);
    }
    
    /**
     * Serve the embed JavaScript
     */
    public function serve_embed_script() {
        if (get_query_var('gf_js_embed')) {
            header('Content-Type: application/javascript');
            header('Cache-Control: public, max-age=3600');
            
            // Set CORS headers based on settings
            $this->set_cors_headers();
            
            // Include the JavaScript SDK
            include GF_JS_EMBED_PLUGIN_DIR . 'assets/js/gf-embed-sdk.js';
            
            // Include analytics if enabled
            $settings = get_option('gf_js_embed_settings', []);
            if (!empty($settings['enable_analytics'])) {
                echo "\n\n// Analytics Module\n";
                include GF_JS_EMBED_PLUGIN_DIR . 'assets/js/embed-analytics.js';
            }
            
            // Include event system
            echo "\n\n// Event System Module\n";
            include GF_JS_EMBED_PLUGIN_DIR . 'assets/js/gf-embed-events.js';
            
            exit;
        }
    }
    
    /**
     * Set CORS headers
     */
    private function set_cors_headers() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is allowed
        if (GF_JS_Embed_Security::is_domain_allowed($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Credentials: true');
        }
    }
    
    /**
     * Maybe flush rewrite rules if needed
     */
    private function maybe_flush_rewrite_rules() {
        $rules_version = get_option('gf_js_embed_rewrite_rules_version', '');
        $current_version = GF_JS_EMBED_VERSION;
        
        if ($rules_version !== $current_version) {
            flush_rewrite_rules();
            update_option('gf_js_embed_rewrite_rules_version', $current_version);
        }
    }
    
    /**
     * Get plugin version
     */
    public static function get_version() {
        return GF_JS_EMBED_VERSION;
    }
    
    /**
     * Get plugin URL
     */
    public static function get_plugin_url() {
        return GF_JS_EMBED_PLUGIN_URL;
    }
    
    /**
     * Get plugin directory
     */
    public static function get_plugin_dir() {
        return GF_JS_EMBED_PLUGIN_DIR;
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=gf_js_embed_analytics'),
            __('JS Embed', 'gf-js-embed')
        );
        
        $docs_link = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://github.com/jezweb/js-gravity-forms-embed#readme',
            __('Documentation', 'gf-js-embed')
        );
        
        array_unshift($links, $settings_link);
        $links[] = $docs_link;
        
        return $links;
    }
    
    /**
     * Add plugin row meta
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(GF_JS_EMBED_PLUGIN_FILE) !== $file) {
            return $links;
        }
        
        $row_meta = [
            'docs' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/jezweb/js-gravity-forms-embed#readme',
                __('View Documentation', 'gf-js-embed')
            ),
            'support' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/jezweb/js-gravity-forms-embed/issues',
                __('Support', 'gf-js-embed')
            ),
            'github' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://github.com/jezweb/js-gravity-forms-embed',
                __('GitHub', 'gf-js-embed')
            )
        ];
        
        return array_merge($links, $row_meta);
    }
    
    /**
     * Cleanup old analytics data
     */
    public function cleanup_analytics_data() {
        $settings = get_option('gf_js_embed_settings', []);
        $retention_days = $settings['analytics_retention_days'] ?? 90;
        
        GF_JS_Embed_Database::clean_old_data($retention_days);
    }
    
    /**
     * Aggregate analytics data for performance
     */
    public function aggregate_analytics_data() {
        // This could be used to pre-calculate common queries
        // For now, we'll just update a transient with recent stats
        
        global $wpdb;
        $views_table = $wpdb->prefix . 'gf_js_embed_views';
        $submissions_table = $wpdb->prefix . 'gf_js_embed_submissions';
        
        // Get total counts for caching
        $total_views = $wpdb->get_var("SELECT COUNT(*) FROM $views_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        set_transient('gf_js_embed_stats_cache', [
            'total_views_30d' => $total_views,
            'total_submissions_30d' => $total_submissions,
            'last_updated' => time()
        ], HOUR_IN_SECONDS);
    }
    
    /**
     * Render the embed shortcode
     */
    public function render_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts([
            'id' => '',
            'theme' => '',
            'title' => 'true',
            'description' => 'true',
            'ajax' => 'true',
            'tabindex' => '0',
            'field_values' => '',
            'use_current_page_as_redirect' => 'false',
            'api_key' => '',
            'container_id' => '',
            'class' => ''
        ], $atts, 'gf_js_embed');
        
        // Validate form ID
        if (empty($atts['id'])) {
            return '<p>' . __('Please specify a form ID.', 'gf-js-embed') . '</p>';
        }
        
        // Generate unique container ID if not provided
        if (empty($atts['container_id'])) {
            $atts['container_id'] = 'gf-embed-' . $atts['id'] . '-' . wp_rand(1000, 9999);
        }
        
        // Get API settings
        $settings = get_option('gf_js_embed_settings', []);
        $api_key = !empty($atts['api_key']) ? $atts['api_key'] : ($settings['api_keys'][0]['key'] ?? '');
        
        if (empty($api_key)) {
            return '<p>' . __('No API key configured for form embedding.', 'gf-js-embed') . '</p>';
        }
        
        // Get theme CSS if specified
        $theme_css = '';
        if (!empty($atts['theme'])) {
            $performance = GF_JS_Embed_Performance::get_instance();
            $theme_css = $performance->get_inline_theme_css($atts['theme']);
        }
        
        // Build embed configuration
        $config = [
            'formId' => intval($atts['id']),
            'targetId' => $atts['container_id'],
            'apiKey' => $api_key,
            'apiUrl' => rest_url('gf-js-embed/v1/'),
            'ajax' => $atts['ajax'] === 'true',
            'title' => $atts['title'] === 'true',
            'description' => $atts['description'] === 'true',
            'tabindex' => intval($atts['tabindex']),
            'useCurrentPageAsRedirect' => $atts['use_current_page_as_redirect'] === 'true'
        ];
        
        // Add field values if provided
        if (!empty($atts['field_values'])) {
            parse_str($atts['field_values'], $field_values);
            $config['fieldValues'] = $field_values;
        }
        
        // Add theme if specified
        if (!empty($atts['theme'])) {
            $config['theme'] = $atts['theme'];
            
            /**
             * Fires when a theme is applied to a form
             * 
             * @since 2.0.0
             * 
             * @param string $theme_name Name of the applied theme
             * @param int $form_id Gravity Forms form ID
             */
            do_action('gf_js_embed_theme_applied', $atts['theme'], intval($atts['id']));
        }
        
        // Generate output
        $output = sprintf(
            '<div id="%s" class="gf-js-embed-container loading %s"></div>',
            esc_attr($atts['container_id']),
            esc_attr($atts['class'])
        );
        
        // Add critical CSS to prevent layout shift
        $performance = GF_JS_Embed_Performance::get_instance();
        $critical_css = $performance->get_critical_css();
        $output .= sprintf('<style id="%s-critical">%s</style>', esc_attr($atts['container_id']), $critical_css);
        
        // Add theme CSS if available
        if (!empty($theme_css)) {
            $output .= sprintf(
                '<style id="%s-theme">%s</style>',
                esc_attr($atts['container_id']),
                $theme_css
            );
        }
        
        // Add initialization script
        $output .= sprintf(
            '<script>
                (function() {
                    // Load Gravity Forms Embed SDK if not already loaded
                    if (typeof GravityFormsEmbed === "undefined") {
                        var script = document.createElement("script");
                        script.src = "%s";
                        script.async = true;
                        script.onload = function() {
                            initializeForm();
                        };
                        document.head.appendChild(script);
                    } else {
                        initializeForm();
                    }
                    
                    function initializeForm() {
                        var config = %s;
                        var embed = new GravityFormsEmbed(config);
                        embed.render();
                        
                        // Remove loading class
                        var container = document.getElementById(config.targetId);
                        if (container) {
                            container.classList.remove("loading");
                        }
                    }
                })();
            </script>',
            esc_url(home_url('/gf-js-embed/v1/embed.js')),
            wp_json_encode($config)
        );
        
        /**
         * Filters the shortcode output before rendering
         * 
         * @since 2.1.0
         * 
         * @param string $output The generated shortcode output
         * @param array $atts Shortcode attributes
         * @param array $settings Form settings (if available)
         */
        $settings = [];
        if (!empty($atts['id'])) {
            $settings = GF_JS_Embed_Admin::get_form_settings($atts['id']);
        }
        
        return apply_filters('gf_js_embed_shortcode_output', $output, $atts, $settings);
    }
}