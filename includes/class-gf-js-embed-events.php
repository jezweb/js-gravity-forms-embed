<?php
/**
 * Event System Handler
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Events {
    
    private static $instance = null;
    private $registered_events = [];
    private $event_log = [];
    
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
        // AJAX endpoints for event handling
        add_action('wp_ajax_gf_js_embed_log_event', [$this, 'log_event']);
        add_action('wp_ajax_nopriv_gf_js_embed_log_event', [$this, 'log_event']);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        
        // Admin AJAX endpoints
        add_action('wp_ajax_gf_js_embed_get_events', [$this, 'get_events_ajax']);
        add_action('wp_ajax_gf_js_embed_clear_events', [$this, 'clear_events_ajax']);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_event_scripts']);
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_rest_endpoints() {
        // Event logging endpoint
        register_rest_route('gf-embed/v1', '/events', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_event_rest'],
            'permission_callback' => [$this, 'check_event_permissions'],
            'args' => [
                'event_type' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'form_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ],
                'data' => [
                    'required' => false,
                    'type' => 'object',
                    'sanitize_callback' => [$this, 'sanitize_event_data']
                ]
            ]
        ]);
        
        // Event retrieval endpoint
        register_rest_route('gf-embed/v1', '/events', [
            'methods' => 'GET',
            'callback' => [$this, 'get_events_rest'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'form_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ],
                'event_type' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'default' => 100
                ]
            ]
        ]);
    }
    
    /**
     * Check event permissions
     */
    public function check_event_permissions($request) {
        // Basic security checks
        $form_id = $request->get_param('form_id');
        
        if (!$form_id) {
            return false;
        }
        
        // Check if form exists and is accessible
        if (!function_exists('GFAPI::get_form')) {
            return false;
        }
        
        $form = GFAPI::get_form($form_id);
        if (!$form || !is_array($form)) {
            return false;
        }
        
        // Check if form is active
        if (!empty($form['is_active']) && $form['is_active'] == '0') {
            return false;
        }
        
        // Additional security checks can be added here
        return apply_filters('gf_js_embed_event_permissions', true, $request, $form);
    }
    
    /**
     * Check admin permissions
     */
    public function check_admin_permissions($request) {
        return current_user_can('gravityforms_view_entries');
    }
    
    /**
     * Sanitize event data
     */
    public function sanitize_event_data($data) {
        if (!is_array($data)) {
            return [];
        }
        
        // Recursively sanitize array data
        return $this->sanitize_array_recursive($data);
    }
    
    /**
     * Recursively sanitize array data
     */
    private function sanitize_array_recursive($array) {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_array_recursive($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Handle event via REST API
     */
    public function handle_event_rest($request) {
        $event_type = $request->get_param('event_type');
        $form_id = $request->get_param('form_id');
        $data = $request->get_param('data') ?: [];
        
        // Log the event
        $event_id = $this->log_event_to_database($event_type, $form_id, $data);
        
        if ($event_id) {
            return rest_ensure_response([
                'success' => true,
                'event_id' => $event_id,
                'timestamp' => current_time('mysql')
            ]);
        } else {
            return new WP_Error('event_failed', 'Failed to log event', ['status' => 500]);
        }
    }
    
    /**
     * Get events via REST API
     */
    public function get_events_rest($request) {
        $form_id = $request->get_param('form_id');
        $event_type = $request->get_param('event_type');
        $limit = $request->get_param('limit');
        
        $events = $this->get_events_from_database($form_id, $event_type, $limit);
        
        return rest_ensure_response([
            'success' => true,
            'events' => $events,
            'count' => count($events)
        ]);
    }
    
    /**
     * Log event via AJAX
     */
    public function log_event() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gf_js_embed_events')) {
            wp_die('Security check failed');
        }
        
        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        $form_id = absint($_POST['form_id'] ?? 0);
        $data = $this->sanitize_event_data($_POST['data'] ?? []);
        
        if (!$event_type || !$form_id) {
            wp_send_json_error('Missing required parameters');
        }
        
        $event_id = $this->log_event_to_database($event_type, $form_id, $data);
        
        if ($event_id) {
            wp_send_json_success([
                'event_id' => $event_id,
                'timestamp' => current_time('mysql')
            ]);
        } else {
            wp_send_json_error('Failed to log event');
        }
    }
    
    /**
     * Log event to database
     */
    private function log_event_to_database($event_type, $form_id, $data = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_events';
        
        // Create table if it doesn't exist
        $this->create_events_table();
        
        $result = $wpdb->insert(
            $table_name,
            [
                'form_id' => $form_id,
                'event_type' => $event_type,
                'event_data' => json_encode($data),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'created_at' => current_time('mysql')
            ],
            [
                '%d', // form_id
                '%s', // event_type
                '%s', // event_data
                '%s', // ip_address
                '%s', // user_agent
                '%s', // domain
                '%s'  // created_at
            ]
        );
        
        if ($result === false) {
            error_log('GF JS Embed: Failed to log event - ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get events from database
     */
    private function get_events_from_database($form_id = null, $event_type = null, $limit = 100) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_events';
        
        $where_conditions = [];
        $where_values = [];
        
        if ($form_id) {
            $where_conditions[] = 'form_id = %d';
            $where_values[] = $form_id;
        }
        
        if ($event_type) {
            $where_conditions[] = 'event_type = %s';
            $where_values[] = $event_type;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $limit = min(max(1, $limit), 1000); // Limit between 1 and 1000
        
        $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d";
        $where_values[] = $limit;
        
        $prepared_query = $wpdb->prepare($query, $where_values);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
        
        // Decode JSON data
        foreach ($results as &$result) {
            $result['event_data'] = json_decode($result['event_data'], true);
        }
        
        return $results;
    }
    
    /**
     * Create events table
     */
    private function create_events_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_events';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id bigint(20) UNSIGNED NOT NULL,
            event_type varchar(100) NOT NULL,
            event_data longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            domain varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY domain (domain)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get events via AJAX
     */
    public function get_events_ajax() {
        // Check permissions
        if (!current_user_can('gravityforms_view_entries')) {
            wp_send_json_error('Permission denied');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gf_js_embed_events')) {
            wp_send_json_error('Security check failed');
        }
        
        $form_id = absint($_POST['form_id'] ?? 0);
        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        $limit = absint($_POST['limit'] ?? 100);
        
        $events = $this->get_events_from_database($form_id, $event_type, $limit);
        
        wp_send_json_success([
            'events' => $events,
            'count' => count($events)
        ]);
    }
    
    /**
     * Clear events via AJAX
     */
    public function clear_events_ajax() {
        // Check permissions
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_send_json_error('Permission denied');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gf_js_embed_events')) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gf_js_embed_events';
        
        $form_id = absint($_POST['form_id'] ?? 0);
        
        if ($form_id) {
            $result = $wpdb->delete($table_name, ['form_id' => $form_id], ['%d']);
        } else {
            $result = $wpdb->query("DELETE FROM $table_name");
        }
        
        if ($result !== false) {
            wp_send_json_success([
                'message' => $form_id 
                    ? "Cleared events for form ID $form_id" 
                    : 'Cleared all events',
                'deleted_count' => $result
            ]);
        } else {
            wp_send_json_error('Failed to clear events');
        }
    }
    
    /**
     * Enqueue event scripts
     */
    public function enqueue_event_scripts() {
        // Only enqueue on pages that might have forms
        if (!is_admin()) {
            wp_enqueue_script(
                'gf-embed-events',
                GF_JS_EMBED_PLUGIN_URL . 'assets/js/gf-embed-events.js',
                ['jquery'],
                GF_JS_EMBED_VERSION,
                true
            );
            
            // Enqueue CSRF protection script
            wp_enqueue_script(
                'gf-embed-csrf',
                GF_JS_EMBED_PLUGIN_URL . 'assets/js/gf-embed-csrf.js',
                ['gf-embed-events'],
                GF_JS_EMBED_VERSION,
                true
            );
            
            wp_localize_script('gf-embed-events', 'gfEmbedEvents', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('gf-embed/v1/'),
                'nonce' => wp_create_nonce('gf_js_embed_events'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ]);
        }
    }
    
    /**
     * Get event statistics
     */
    public function get_event_statistics($form_id = null, $days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_events';
        
        $where_clause = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)";
        $where_values = [$days];
        
        if ($form_id) {
            $where_clause .= " AND form_id = %d";
            $where_values[] = $form_id;
        }
        
        $query = "
            SELECT 
                event_type,
                COUNT(*) as count,
                DATE(created_at) as date
            FROM $table_name 
            $where_clause
            GROUP BY event_type, DATE(created_at)
            ORDER BY date DESC, count DESC
        ";
        
        $prepared_query = $wpdb->prepare($query, $where_values);
        return $wpdb->get_results($prepared_query, ARRAY_A);
    }
    
    /**
     * Get most active forms
     */
    public function get_most_active_forms($limit = 10, $days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_events';
        
        $query = "
            SELECT 
                form_id,
                COUNT(*) as event_count,
                COUNT(DISTINCT event_type) as unique_events
            FROM $table_name 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY form_id
            ORDER BY event_count DESC
            LIMIT %d
        ";
        
        $prepared_query = $wpdb->prepare($query, [$days, $limit]);
        return $wpdb->get_results($prepared_query, ARRAY_A);
    }
    
    /**
     * Clean old events
     */
    public function clean_old_events($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_events';
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        return $result;
    }
}