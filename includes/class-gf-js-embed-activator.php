<?php
/**
 * Plugin activation handler
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(GF_JS_EMBED_PLUGIN_FILE));
            wp_die(__('Gravity Forms JavaScript Embed requires PHP 7.4 or higher.', 'gf-js-embed'));
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.8', '<')) {
            deactivate_plugins(plugin_basename(GF_JS_EMBED_PLUGIN_FILE));
            wp_die(__('Gravity Forms JavaScript Embed requires WordPress 5.8 or higher.', 'gf-js-embed'));
        }
        
        // Create database tables if needed
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directory
        self::create_upload_directory();
        
        // Schedule cron jobs
        self::schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Analytics table
        $table_name = $wpdb->prefix . 'gf_js_embed_analytics';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id bigint(20) UNSIGNED NOT NULL,
            event_type varchar(50) NOT NULL,
            domain varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // API logs table
        $table_name = $wpdb->prefix . 'gf_js_embed_api_logs';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            form_id bigint(20) UNSIGNED DEFAULT NULL,
            response_code int(3) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            domain varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY created_at (created_at),
            KEY form_id (form_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        // Global settings
        add_option('gf_js_embed_settings', [
            'enable_analytics' => true,
            'enable_api_logs' => false,
            'rate_limit_requests' => 60,
            'rate_limit_window' => 60,
            'debug_mode' => false
        ]);
        
        // Version tracking
        add_option('gf_js_embed_version', GF_JS_EMBED_VERSION);
        add_option('gf_js_embed_db_version', '1.0.0');
    }
    
    /**
     * Create upload directory
     */
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $embed_dir = $upload_dir['basedir'] . '/gf-js-embed';
        
        if (!file_exists($embed_dir)) {
            wp_mkdir_p($embed_dir);
            
            // Add .htaccess to protect directory
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<FilesMatch '\\.(php|php\\d|phtml)$'>\n";
            $htaccess_content .= "    Order Allow,Deny\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</FilesMatch>\n";
            
            file_put_contents($embed_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private static function schedule_cron_jobs() {
        if (!wp_next_scheduled('gf_js_embed_cleanup')) {
            wp_schedule_event(time(), 'daily', 'gf_js_embed_cleanup');
        }
        
        if (!wp_next_scheduled('gf_js_embed_analytics_aggregate')) {
            wp_schedule_event(time(), 'hourly', 'gf_js_embed_analytics_aggregate');
        }
    }
}