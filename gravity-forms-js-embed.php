<?php
/**
 * Plugin Name: Gravity Forms JavaScript Embed
 * Plugin URI: https://github.com/jezweb/js-gravity-forms-embed
 * Description: Embed Gravity Forms on any website using JavaScript instead of iframes. Provides a modern, performant alternative to iframe embedding with full support for all Gravity Forms features.
 * Version: 0.2.2
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Jezweb
 * Author URI: https://www.jezweb.com.au
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gf-js-embed
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GF_JS_EMBED_VERSION', '0.2.2');
define('GF_JS_EMBED_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GF_JS_EMBED_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GF_JS_EMBED_PLUGIN_FILE', __FILE__);

// Check if Gravity Forms is active
add_action('plugins_loaded', 'gf_js_embed_check_requirements');

function gf_js_embed_check_requirements() {
    if (!class_exists('GFForms')) {
        add_action('admin_notices', 'gf_js_embed_missing_gf_notice');
        return;
    }
    
    // Load the plugin
    gf_js_embed_load_plugin();
}

function gf_js_embed_missing_gf_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Gravity Forms JavaScript Embed requires Gravity Forms to be installed and activated.', 'gf-js-embed'); ?></p>
    </div>
    <?php
}

function gf_js_embed_load_plugin() {
    // Track load time
    define('GF_JS_EMBED_LOAD_TIME', (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
    
    // Load required files
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed.php';
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-api.php';
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-admin.php';
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-security.php';
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-analytics.php';
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-styling.php';
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-i18n.php';
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-testing.php';
    
    // Initialize the plugin
    GF_JavaScript_Embed::get_instance();
    
    // Initialize testing dashboard
    if (is_admin()) {
        GF_JS_Embed_Testing::get_instance();
    }
}

// Activation hook
register_activation_hook(__FILE__, 'gf_js_embed_activate');

function gf_js_embed_activate() {
    // Create database tables if needed
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-activator.php';
    GF_JS_Embed_Activator::activate();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'gf_js_embed_deactivate');

function gf_js_embed_deactivate() {
    // Clean up
    flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'gf_js_embed_uninstall');

function gf_js_embed_uninstall() {
    // Remove all plugin data
    require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-uninstall.php';
    GF_JS_Embed_Uninstall::uninstall();
}