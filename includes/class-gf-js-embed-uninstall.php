<?php
/**
 * Plugin uninstall handler
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Uninstall {
    
    /**
     * Uninstall the plugin
     */
    public static function uninstall() {
        // Check if we should remove data
        $settings = get_option('gf_js_embed_settings', []);
        $remove_data = isset($settings['remove_data_on_uninstall']) && $settings['remove_data_on_uninstall'];
        
        if (!$remove_data) {
            return;
        }
        
        // Remove options
        self::remove_options();
        
        // Remove database tables
        self::remove_tables();
        
        // Remove uploaded files
        self::remove_files();
        
        // Remove cron jobs
        self::remove_cron_jobs();
        
        // Clear any cached data
        self::clear_cache();
    }
    
    /**
     * Remove all plugin options
     */
    private static function remove_options() {
        global $wpdb;
        
        // Remove all options starting with our prefix
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gf_js_embed_%'");
        
        // Remove form-specific settings
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gf_embed_form_%'");
    }
    
    /**
     * Remove database tables
     */
    private static function remove_tables() {
        global $wpdb;
        
        // Legacy tables
        $tables = [
            $wpdb->prefix . 'gf_js_embed_analytics',
            $wpdb->prefix . 'gf_js_embed_api_logs'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // New analytics tables
        require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-database.php';
        GF_JS_Embed_Database::drop_tables();
    }
    
    /**
     * Remove uploaded files
     */
    private static function remove_files() {
        $upload_dir = wp_upload_dir();
        $embed_dir = $upload_dir['basedir'] . '/gf-js-embed';
        
        if (file_exists($embed_dir)) {
            self::remove_directory($embed_dir);
        }
    }
    
    /**
     * Recursively remove directory
     */
    private static function remove_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::remove_directory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Remove cron jobs
     */
    private static function remove_cron_jobs() {
        wp_clear_scheduled_hook('gf_js_embed_cleanup');
        wp_clear_scheduled_hook('gf_js_embed_analytics_aggregate');
    }
    
    /**
     * Clear any cached data
     */
    private static function clear_cache() {
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_gf_js_embed_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_gf_js_embed_%'");
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
}