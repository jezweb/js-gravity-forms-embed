<?php
/**
 * Rate Limiting class for API requests
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Rate_Limiter {
    
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
        add_action('init', [$this, 'init']);
    }
    
    /**
     * Initialize rate limiter
     */
    public function init() {
        // Create rate limit table if needed
        $this->create_rate_limit_table();
        
        // Clean up old rate limit entries
        add_action('gf_js_embed_cleanup_rate_limits', [$this, 'cleanup_rate_limits']);
        if (!wp_next_scheduled('gf_js_embed_cleanup_rate_limits')) {
            wp_schedule_event(time(), 'hourly', 'gf_js_embed_cleanup_rate_limits');
        }
    }
    
    /**
     * Create rate limit tracking table
     */
    private function create_rate_limit_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_rate_limits';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier varchar(255) NOT NULL,
            endpoint varchar(255) NOT NULL,
            form_id bigint(20) UNSIGNED DEFAULT NULL,
            request_count int(11) DEFAULT 1,
            window_start datetime DEFAULT CURRENT_TIMESTAMP,
            last_request datetime DEFAULT CURRENT_TIMESTAMP,
            blocked_until datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_limit (identifier, endpoint, form_id),
            KEY window_start (window_start),
            KEY blocked_until (blocked_until)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check if request is within rate limits
     */
    public function check_rate_limit($identifier, $endpoint, $form_id = null) {
        global $wpdb;
        
        $settings = get_option('gf_js_embed_settings', []);
        $rate_config = $this->get_rate_limit_config($endpoint, $form_id);
        
        // If rate limiting is disabled
        if (!$rate_config['enabled']) {
            return [
                'allowed' => true,
                'remaining' => $rate_config['limit'],
                'reset_time' => time() + $rate_config['window']
            ];
        }
        
        $table_name = $wpdb->prefix . 'gf_js_embed_rate_limits';
        $current_time = current_time('mysql');
        
        // Check if currently blocked
        $blocked_until = $wpdb->get_var($wpdb->prepare(
            "SELECT blocked_until FROM $table_name 
             WHERE identifier = %s AND endpoint = %s AND form_id = %s AND blocked_until > %s",
            $identifier, $endpoint, $form_id, $current_time
        ));
        
        if ($blocked_until) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => strtotime($blocked_until),
                'retry_after' => strtotime($blocked_until) - time()
            ];
        }
        
        // Get or create rate limit record
        $window_start = date('Y-m-d H:i:s', time() - $rate_config['window']);
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE identifier = %s AND endpoint = %s AND form_id = %s",
            $identifier, $endpoint, $form_id
        ));
        
        if (!$record) {
            // Create new record
            $wpdb->insert($table_name, [
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'form_id' => $form_id,
                'request_count' => 1,
                'window_start' => $current_time,
                'last_request' => $current_time
            ]);
            
            return [
                'allowed' => true,
                'remaining' => $rate_config['limit'] - 1,
                'reset_time' => time() + $rate_config['window']
            ];
        }
        
        // Check if we need to reset the window
        if (strtotime($record->window_start) < (time() - $rate_config['window'])) {
            // Reset window
            $wpdb->update($table_name, [
                'request_count' => 1,
                'window_start' => $current_time,
                'last_request' => $current_time,
                'blocked_until' => null
            ], [
                'id' => $record->id
            ]);
            
            return [
                'allowed' => true,
                'remaining' => $rate_config['limit'] - 1,
                'reset_time' => time() + $rate_config['window']
            ];
        }
        
        // Check if limit exceeded
        if ($record->request_count >= $rate_config['limit']) {
            // Apply progressive blocking
            $block_duration = $this->calculate_block_duration($record->request_count, $rate_config);
            $blocked_until = date('Y-m-d H:i:s', time() + $block_duration);
            
            $wpdb->update($table_name, [
                'request_count' => $record->request_count + 1,
                'last_request' => $current_time,
                'blocked_until' => $blocked_until
            ], [
                'id' => $record->id
            ]);
            
            // Log rate limit violation
            $this->log_rate_limit_violation($identifier, $endpoint, $form_id, $record->request_count);
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => time() + $block_duration,
                'retry_after' => $block_duration
            ];
        }
        
        // Increment counter
        $wpdb->update($table_name, [
            'request_count' => $record->request_count + 1,
            'last_request' => $current_time
        ], [
            'id' => $record->id
        ]);
        
        return [
            'allowed' => true,
            'remaining' => $rate_config['limit'] - ($record->request_count + 1),
            'reset_time' => strtotime($record->window_start) + $rate_config['window']
        ];
    }
    
    /**
     * Get rate limit configuration for endpoint
     */
    private function get_rate_limit_config($endpoint, $form_id = null) {
        $settings = get_option('gf_js_embed_settings', []);
        $form_settings = $form_id ? GF_JS_Embed_Admin::get_form_settings($form_id) : [];
        
        // Default configurations
        $defaults = [
            'enabled' => true,
            'limit' => 60,
            'window' => 60, // seconds
            'block_duration' => 300 // 5 minutes
        ];
        
        // Endpoint-specific configurations
        $endpoint_configs = [
            '/form/' => [
                'limit' => $settings['rate_limit_form_requests'] ?? 100,
                'window' => $settings['rate_limit_form_window'] ?? 60
            ],
            '/submit/' => [
                'limit' => $settings['rate_limit_submit_requests'] ?? 10,
                'window' => $settings['rate_limit_submit_window'] ?? 60,
                'block_duration' => 600 // 10 minutes for submissions
            ],
            '/analytics/track' => [
                'limit' => $settings['rate_limit_analytics_requests'] ?? 200,
                'window' => $settings['rate_limit_analytics_window'] ?? 60
            ],
            '/assets/' => [
                'limit' => $settings['rate_limit_assets_requests'] ?? 50,
                'window' => $settings['rate_limit_assets_window'] ?? 60
            ]
        ];
        
        // Find matching endpoint config
        $config = $defaults;
        foreach ($endpoint_configs as $pattern => $endpoint_config) {
            if (strpos($endpoint, $pattern) !== false) {
                $config = array_merge($config, $endpoint_config);
                break;
            }
        }
        
        // Form-specific overrides
        if ($form_id && !empty($form_settings['rate_limit_enabled'])) {
            if (!empty($form_settings['rate_limit_requests'])) {
                $config['limit'] = (int) $form_settings['rate_limit_requests'];
            }
            if (!empty($form_settings['rate_limit_window'])) {
                $config['window'] = (int) $form_settings['rate_limit_window'];
            }
        }
        
        // Global disable check
        if (isset($settings['enable_rate_limiting']) && !$settings['enable_rate_limiting']) {
            $config['enabled'] = false;
        }
        
        /**
         * Filters rate limit configuration
         * 
         * @since 2.0.0
         * 
         * @param array $config Rate limit configuration
         * @param string $identifier Client identifier (usually IP address)
         * @param int|null $form_id Form ID if applicable
         */
        return apply_filters('gf_js_embed_rate_limit', $config, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $form_id);
    }
    
    /**
     * Calculate progressive block duration
     */
    private function calculate_block_duration($violation_count, $rate_config) {
        $base_duration = $rate_config['block_duration'] ?? 300;
        
        // Progressive blocking: increase duration with repeated violations
        $multiplier = min(pow(2, max(0, $violation_count - $rate_config['limit'])), 16);
        
        return $base_duration * $multiplier;
    }
    
    /**
     * Log rate limit violation
     */
    private function log_rate_limit_violation($identifier, $endpoint, $form_id, $request_count) {
        $settings = get_option('gf_js_embed_settings', []);
        
        if (!empty($settings['log_rate_limit_violations'])) {
            error_log(sprintf(
                'GF JS Embed Rate Limit Violation: %s exceeded limit on %s (form: %s) with %d requests',
                $identifier,
                $endpoint,
                $form_id ?: 'N/A',
                $request_count
            ));
        }
        
        // Track in security log
        if (class_exists('GF_JS_Embed_Security')) {
            GF_JS_Embed_Security::log_security_event('rate_limit_violation', [
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'form_id' => $form_id,
                'request_count' => $request_count
            ]);
        }
    }
    
    /**
     * Add rate limit headers to response
     */
    public function add_rate_limit_headers($rate_limit_result) {
        header('X-RateLimit-Limit: ' . ($rate_limit_result['remaining'] + 1));
        header('X-RateLimit-Remaining: ' . $rate_limit_result['remaining']);
        header('X-RateLimit-Reset: ' . $rate_limit_result['reset_time']);
        
        if (!$rate_limit_result['allowed'] && isset($rate_limit_result['retry_after'])) {
            header('Retry-After: ' . $rate_limit_result['retry_after']);
        }
    }
    
    /**
     * Get rate limit status for identifier
     */
    public function get_rate_limit_status($identifier, $endpoint, $form_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_rate_limits';
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE identifier = %s AND endpoint = %s AND form_id = %s",
            $identifier, $endpoint, $form_id
        ));
        
        if (!$record) {
            return null;
        }
        
        $rate_config = $this->get_rate_limit_config($endpoint, $form_id);
        $window_elapsed = time() - strtotime($record->window_start);
        
        return [
            'requests_made' => $record->request_count,
            'limit' => $rate_config['limit'],
            'window_start' => $record->window_start,
            'window_elapsed' => $window_elapsed,
            'window_remaining' => max(0, $rate_config['window'] - $window_elapsed),
            'blocked_until' => $record->blocked_until,
            'is_blocked' => $record->blocked_until && strtotime($record->blocked_until) > time()
        ];
    }
    
    /**
     * Clear rate limits for identifier (admin function)
     */
    public function clear_rate_limits($identifier, $endpoint = null, $form_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_rate_limits';
        $where = ['identifier' => $identifier];
        
        if ($endpoint !== null) {
            $where['endpoint'] = $endpoint;
        }
        
        if ($form_id !== null) {
            $where['form_id'] = $form_id;
        }
        
        return $wpdb->delete($table_name, $where);
    }
    
    /**
     * Get rate limit statistics
     */
    public function get_rate_limit_stats($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_rate_limits';
        $date_from = date('Y-m-d H:i:s', time() - ($days * 24 * 60 * 60));
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                endpoint,
                COUNT(*) as total_requests,
                COUNT(CASE WHEN blocked_until IS NOT NULL THEN 1 END) as blocked_requests,
                AVG(request_count) as avg_requests_per_window,
                MAX(request_count) as max_requests_per_window
             FROM $table_name 
             WHERE window_start >= %s
             GROUP BY endpoint
             ORDER BY total_requests DESC",
            $date_from
        ), ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Cleanup old rate limit entries
     */
    public function cleanup_rate_limits() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_rate_limits';
        
        // Remove entries older than 24 hours
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name 
             WHERE window_start < %s AND (blocked_until IS NULL OR blocked_until < %s)",
            date('Y-m-d H:i:s', time() - (24 * 60 * 60)),
            current_time('mysql')
        ));
        
        // Log cleanup
        $deleted = $wpdb->rows_affected;
        if ($deleted > 0) {
            error_log("GF JS Embed: Cleaned up {$deleted} old rate limit entries");
        }
    }
    
    /**
     * Emergency rate limit bypass (for admin/troubleshooting)
     */
    public function emergency_bypass($identifier, $duration = 3600) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gf_js_embed_rate_limits';
        
        // Clear all current limits for this identifier
        $wpdb->delete($table_name, ['identifier' => $identifier]);
        
        // Set a temporary bypass flag
        set_transient("gf_rate_limit_bypass_{$identifier}", true, $duration);
        
        return true;
    }
    
    /**
     * Check for emergency bypass
     */
    public function has_emergency_bypass($identifier) {
        return get_transient("gf_rate_limit_bypass_{$identifier}") === true;
    }
}