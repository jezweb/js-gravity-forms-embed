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
        
        // Initialize components
        GF_JS_Embed_API::get_instance();
        GF_JS_Embed_Admin::get_instance();
        GF_JS_Embed_Security::get_instance();
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
}