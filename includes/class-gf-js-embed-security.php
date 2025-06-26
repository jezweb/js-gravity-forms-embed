<?php
/**
 * Security handler class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Security {
    
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
        // Initialize security features
        add_action('init', [$this, 'init_security_features']);
        add_action('wp_ajax_nopriv_gf_js_embed_security_check', [$this, 'security_check_endpoint']);
        add_action('wp_ajax_gf_js_embed_security_check', [$this, 'security_check_endpoint']);
    }
    
    /**
     * Initialize security features
     */
    public function init_security_features() {
        // Set security headers
        $this->set_security_headers();
        
        // Clean up old rate limit data
        $this->cleanup_rate_limit_data();
    }
    
    /**
     * Check if domain is allowed
     */
    public static function is_domain_allowed($origin, $form_id = null) {
        // Extract domain from origin
        $domain = parse_url($origin, PHP_URL_HOST);
        
        if (!$domain) {
            self::trigger_security_violation('invalid_origin', [
                'origin' => $origin,
                'reason' => 'Unable to parse domain from origin'
            ], $form_id);
            return false;
        }
        
        // If form_id is provided, check form-specific settings
        if ($form_id) {
            $settings = GF_JS_Embed_Admin::get_form_settings($form_id);
            $allowed_domains = $settings['allowed_domains'];
            
            // Apply filter to allow modification of allowed domains
            $allowed_domains = apply_filters('gf_js_embed_allowed_domains', $allowed_domains, $form_id);
            
            // If no domains specified or * is in the list, allow all
            if (empty($allowed_domains) || in_array('*', $allowed_domains)) {
                return true;
            }
            
            // Check exact match or wildcard match
            foreach ($allowed_domains as $allowed) {
                if (self::domain_matches($domain, $allowed)) {
                    return true;
                }
            }
            
            // Domain not allowed - trigger security violation
            self::trigger_security_violation('domain_not_allowed', [
                'domain' => $domain,
                'origin' => $origin,
                'allowed_domains' => $allowed_domains
            ], $form_id);
            
            return false;
        }
        
        // Global check (if no form_id provided)
        return apply_filters('gf_js_embed_allow_domain', true, $domain);
    }
    
    /**
     * Check if domain matches pattern
     */
    private static function domain_matches($domain, $pattern) {
        // Remove protocol if present
        $pattern = preg_replace('#^https?://#', '', $pattern);
        
        // Exact match
        if ($domain === $pattern) {
            return true;
        }
        
        // Wildcard match (*.example.com)
        if (strpos($pattern, '*.') === 0) {
            $base_domain = substr($pattern, 2);
            return $domain === $base_domain || substr($domain, -strlen($base_domain) - 1) === '.' . $base_domain;
        }
        
        return false;
    }
    
    /**
     * Generate API key
     */
    public static function generate_api_key() {
        return 'gfjs_' . bin2hex(random_bytes(16));
    }
    
    /**
     * Validate API key
     */
    public static function validate_api_key($api_key, $form_id) {
        $settings = GF_JS_Embed_Admin::get_form_settings($form_id);
        
        if (empty($settings['api_key'])) {
            return true; // No API key required
        }
        
        $is_valid = hash_equals($settings['api_key'], $api_key);
        
        if (!$is_valid) {
            self::trigger_security_violation('invalid_api_key', [
                'provided_key' => substr($api_key, 0, 8) . '...',
                'form_id' => $form_id
            ], $form_id);
        }
        
        return $is_valid;
    }
    
    /**
     * Check rate limit
     */
    public static function check_rate_limit($identifier, $max_requests = 60, $window = 60) {
        $key = 'gf_js_embed_rate_' . md5($identifier);
        $attempts = get_transient($key) ?: 0;
        
        if ($attempts >= $max_requests) {
            return false;
        }
        
        set_transient($key, $attempts + 1, $window);
        return true;
    }
    
    /**
     * Sanitize form input
     */
    public static function sanitize_input($input, $field_type = 'text') {
        switch ($field_type) {
            case 'email':
                return sanitize_email($input);
                
            case 'url':
                return esc_url_raw($input);
                
            case 'textarea':
                return sanitize_textarea_field($input);
                
            case 'number':
                return is_numeric($input) ? $input : '';
                
            case 'html':
                return wp_kses_post($input);
                
            default:
                return sanitize_text_field($input);
        }
    }
    
    /**
     * Generate nonce for form
     */
    public static function generate_form_nonce($form_id) {
        return wp_create_nonce('gf_js_embed_submit_' . $form_id);
    }
    
    /**
     * Verify form nonce
     */
    public static function verify_form_nonce($nonce, $form_id) {
        return wp_verify_nonce($nonce, 'gf_js_embed_submit_' . $form_id);
    }
    
    /**
     * Get allowed file types
     */
    public static function get_allowed_file_types() {
        $allowed_types = get_allowed_mime_types();
        
        // Additional safety - remove potentially dangerous types
        $dangerous_types = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'js', 'exe', 'bat', 'cmd'];
        
        foreach ($allowed_types as $ext => $mime) {
            foreach ($dangerous_types as $dangerous) {
                if (strpos($ext, $dangerous) !== false) {
                    unset($allowed_types[$ext]);
                }
            }
        }
        
        return apply_filters('gf_js_embed_allowed_file_types', $allowed_types);
    }
    
    /**
     * Validate file upload
     */
    public static function validate_file_upload($file, $allowed_extensions = []) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check allowed extensions
        if (!empty($allowed_extensions)) {
            $allowed_extensions = array_map('strtolower', $allowed_extensions);
            if (!in_array($file_ext, $allowed_extensions)) {
                return new WP_Error('invalid_file_type', __('File type not allowed.', 'gf-js-embed'));
            }
        }
        
        // Check against WordPress allowed types
        $allowed_types = self::get_allowed_file_types();
        $file_type = wp_check_filetype($file['name'], $allowed_types);
        
        if (!$file_type['type']) {
            return new WP_Error('invalid_file_type', __('File type not allowed.', 'gf-js-embed'));
        }
        
        // Additional security checks
        if (preg_match('/\.(php|phtml|php\d|phps|exe|bat|cmd|sh)$/i', $file['name'])) {
            return new WP_Error('dangerous_file_type', __('File type not allowed for security reasons.', 'gf-js-embed'));
        }
        
        return true;
    }
    
    /**
     * Log security event
     */
    public static function log_security_event($event_type, $details = []) {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'details' => $details
        ];
        
        // Store in database or file
        $logs = get_option('gf_js_embed_security_logs', []);
        
        // Keep only last 1000 entries
        if (count($logs) >= 1000) {
            array_shift($logs);
        }
        
        $logs[] = $log_entry;
        update_option('gf_js_embed_security_logs', $logs);
        
        // Trigger action for external logging
        do_action('gf_js_embed_security_event', $event_type, $log_entry);
    }
    
    /**
     * Get security headers
     */
    public static function get_security_headers() {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Robots-Tag' => 'noindex, nofollow'
        ];
    }
    
    /**
     * Set security headers
     */
    private function set_security_headers() {
        // Only set headers for our API endpoints
        if (strpos($_SERVER['REQUEST_URI'] ?? '', 'gf-embed') !== false) {
            foreach (self::get_security_headers() as $header => $value) {
                header($header . ': ' . $value);
            }
        }
    }
    
    /**
     * Generate honeypot field
     */
    public static function generate_honeypot_field($form_id) {
        $field_name = 'gf_honeypot_' . wp_generate_password(8, false);
        
        // Store the honeypot field name for this form
        set_transient('gf_honeypot_' . $form_id, $field_name, HOUR_IN_SECONDS);
        
        return [
            'name' => $field_name,
            'html' => '<input type="text" name="' . esc_attr($field_name) . '" value="" style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" tabindex="-1" autocomplete="off">'
        ];
    }
    
    /**
     * Validate honeypot field
     */
    public static function validate_honeypot($form_id, $submitted_data) {
        // Get form settings to check if honeypot is enabled
        $settings = GF_JS_Embed_Admin::get_form_settings($form_id);
        if (!$settings['honeypot_enabled']) {
            return true; // Honeypot validation disabled
        }
        
        $honeypot_field = get_transient('gf_honeypot_' . $form_id);
        
        if (!$honeypot_field) {
            // No honeypot field set - allow submission but log warning
            error_log('GF JS Embed: No honeypot field found for form ' . $form_id);
            return true;
        }
        
        // Check if honeypot field was filled (indicates bot)
        if (!empty($submitted_data[$honeypot_field])) {
            self::log_security_event('honeypot_triggered', [
                'form_id' => $form_id,
                'field_value' => $submitted_data[$honeypot_field]
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Enhanced rate limiting with progressive penalties
     */
    public static function check_advanced_rate_limit($identifier, $form_id = null) {
        $base_limit = 60; // requests per hour
        $window = HOUR_IN_SECONDS;
        
        // Form-specific limits
        if ($form_id) {
            $settings = GF_JS_Embed_Admin::get_form_settings($form_id);
            $base_limit = $settings['rate_limit'] ?? $base_limit;
        }
        
        $key = 'gf_embed_rate_' . md5($identifier);
        $attempts = get_transient($key) ?: [];
        
        // Clean old attempts
        $current_time = time();
        $attempts = array_filter($attempts, function($timestamp) use ($current_time, $window) {
            return ($current_time - $timestamp) < $window;
        });
        
        // Check if over limit
        if (count($attempts) >= $base_limit) {
            // Progressive penalty - increase ban time based on repeated violations
            $violation_key = 'gf_embed_violations_' . md5($identifier);
            $violations = get_transient($violation_key) ?: 0;
            $violations++;
            
            $ban_time = min(3600 * $violations, 86400); // Max 24 hours
            set_transient($violation_key, $violations, $ban_time);
            
            self::log_security_event('rate_limit_exceeded', [
                'identifier' => $identifier,
                'attempts' => count($attempts),
                'limit' => $base_limit,
                'violations' => $violations,
                'ban_time' => $ban_time
            ]);
            
            return false;
        }
        
        // Add current attempt
        $attempts[] = $current_time;
        set_transient($key, $attempts, $window);
        
        return true;
    }
    
    /**
     * Check for suspicious patterns
     */
    public static function detect_suspicious_activity($data) {
        $flags = [];
        
        // Check for common spam patterns
        $spam_patterns = [
            '/\b(viagra|cialis|casino|poker|loan|mortgage)\b/i',
            '/\b[A-Z]{10,}/', // All caps words
            '/\b\w*\d+\w*\d+\w*/', // Mixed numbers and letters
            '/<\s*script/i', // Script tags
            '/href\s*=/i', // Links
        ];
        
        foreach ($data as $field => $value) {
            if (!is_string($value)) continue;
            
            foreach ($spam_patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $flags[] = "spam_pattern_{$field}";
                }
            }
            
            // Check for excessive length
            if (strlen($value) > 10000) {
                $flags[] = "excessive_length_{$field}";
            }
            
            // Check for too many URLs
            if (substr_count(strtolower($value), 'http') > 3) {
                $flags[] = "multiple_urls_{$field}";
            }
        }
        
        // Check submission speed (form filled too quickly)
        if (isset($data['gf_embed_start_time'])) {
            $fill_time = time() - intval($data['gf_embed_start_time']);
            if ($fill_time < 5) { // Less than 5 seconds
                $flags[] = 'too_fast_submission';
            }
        }
        
        if (!empty($flags)) {
            self::log_security_event('suspicious_activity', [
                'flags' => $flags,
                'data_keys' => array_keys($data)
            ]);
        }
        
        return $flags;
    }
    
    /**
     * Enhanced input sanitization with context awareness
     */
    public static function sanitize_form_data($data, $form_fields = []) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Skip honeypot and security fields
            if (strpos($key, 'gf_honeypot_') === 0 || strpos($key, 'gf_embed_') === 0) {
                continue;
            }
            
            // Find field type for context-aware sanitization
            $field_type = 'text'; // default
            if (preg_match('/input_(\d+)/', $key, $matches)) {
                $field_id = $matches[1];
                $field = array_filter($form_fields, function($f) use ($field_id) {
                    return $f->id == $field_id;
                });
                if (!empty($field)) {
                    $field_type = reset($field)->type;
                }
            }
            
            $sanitized[$key] = self::sanitize_input($value, $field_type);
        }
        
        return $sanitized;
    }
    
    /**
     * Generate CSRF token for form
     */
    public static function generate_csrf_token($form_id) {
        $token = wp_generate_password(32, false);
        set_transient('gf_embed_csrf_' . $form_id . '_' . session_id(), $token, 30 * MINUTE_IN_SECONDS);
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validate_csrf_token($form_id, $token) {
        $stored_token = get_transient('gf_embed_csrf_' . $form_id . '_' . session_id());
        
        if (!$stored_token || !hash_equals($stored_token, $token)) {
            self::log_security_event('csrf_validation_failed', [
                'form_id' => $form_id,
                'session_id' => session_id()
            ]);
            return false;
        }
        
        // Delete token after use
        delete_transient('gf_embed_csrf_' . $form_id . '_' . session_id());
        return true;
    }
    
    /**
     * Check for bot-like behavior
     */
    public static function is_likely_bot($data) {
        $bot_indicators = 0;
        
        // Check user agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $bot_agents = ['bot', 'crawler', 'spider', 'scraper'];
        foreach ($bot_agents as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                $bot_indicators++;
                break;
            }
        }
        
        // Check for missing common headers
        $expected_headers = ['HTTP_ACCEPT', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_USER_AGENT'];
        foreach ($expected_headers as $header) {
            if (empty($_SERVER[$header])) {
                $bot_indicators++;
            }
        }
        
        // Check form fill patterns
        if (isset($data['gf_embed_start_time'])) {
            $fill_time = time() - intval($data['gf_embed_start_time']);
            
            // Too fast (less than 3 seconds) or suspiciously exact timing
            if ($fill_time < 3 || $fill_time % 10 === 0) {
                $bot_indicators++;
            }
        }
        
        // Check if all fields have identical values
        $input_values = array_filter($data, function($key) {
            return strpos($key, 'input_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        
        if (count(array_unique($input_values)) === 1 && count($input_values) > 1) {
            $bot_indicators++; // All fields have same value
        }
        
        return $bot_indicators >= 2;
    }
    
    /**
     * Clean up old rate limit data
     */
    private function cleanup_rate_limit_data() {
        // Only run cleanup occasionally
        if (rand(1, 100) > 5) return; // 5% chance
        
        global $wpdb;
        
        // Clean up old transients
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE %s 
             AND option_value < %d",
            '_transient_timeout_gf_embed_%',
            time()
        ));
    }
    
    /**
     * Security check endpoint for frontend
     */
    public function security_check_endpoint() {
        $form_id = intval($_POST['form_id'] ?? 0);
        $domain = $_POST['domain'] ?? '';
        
        if (!$form_id) {
            wp_die('Invalid form ID', 'Security Check', ['response' => 400]);
        }
        
        $checks = [
            'domain_allowed' => self::is_domain_allowed($domain, $form_id),
            'rate_limit_ok' => self::check_advanced_rate_limit($_SERVER['REMOTE_ADDR'], $form_id),
            'honeypot' => self::generate_honeypot_field($form_id),
            'csrf_token' => self::generate_csrf_token($form_id),
            'timestamp' => time()
        ];
        
        wp_send_json_success($checks);
    }
    
    /**
     * Enhanced security scan
     */
    public static function perform_security_scan($form_id, $data) {
        $results = [
            'passed' => true,
            'flags' => [],
            'risk_score' => 0
        ];
        
        // Domain check
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        if (!self::is_domain_allowed($origin, $form_id)) {
            $results['flags'][] = 'domain_not_allowed';
            $results['risk_score'] += 10;
        }
        
        // Rate limiting
        if (!self::check_advanced_rate_limit($_SERVER['REMOTE_ADDR'], $form_id)) {
            $results['flags'][] = 'rate_limit_exceeded';
            $results['risk_score'] += 8;
        }
        
        // Honeypot validation
        if (!self::validate_honeypot($form_id, $data)) {
            $results['flags'][] = 'honeypot_triggered';
            $results['risk_score'] += 10;
        }
        
        // Bot detection
        if (self::is_likely_bot($data)) {
            $results['flags'][] = 'bot_detected';
            $results['risk_score'] += 7;
        }
        
        // Suspicious activity
        $suspicious_flags = self::detect_suspicious_activity($data);
        if (!empty($suspicious_flags)) {
            $results['flags'] = array_merge($results['flags'], $suspicious_flags);
            $results['risk_score'] += count($suspicious_flags) * 2;
        }
        
        // Overall assessment
        if ($results['risk_score'] >= 10) {
            $results['passed'] = false;
        }
        
        // Log high-risk submissions
        if ($results['risk_score'] >= 15) {
            self::log_security_event('high_risk_submission', [
                'form_id' => $form_id,
                'risk_score' => $results['risk_score'],
                'flags' => $results['flags']
            ]);
        }
        
        return $results;
    }
    
    /**
     * Trigger security violation hook
     * 
     * @since 2.0.0
     * 
     * @param string $violation_type Type of security violation
     * @param array $details Details about the violation
     * @param int|null $form_id Form ID if applicable
     */
    public static function trigger_security_violation($violation_type, $details = [], $form_id = null) {
        // Add standard details
        $details = array_merge([
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        ], $details);
        
        // Log the event
        self::log_security_event($violation_type, $details);
        
        /**
         * Fires when a security violation is detected
         * 
         * @since 2.0.0
         * 
         * @param string $violation_type Type of violation (invalid_api_key, domain_not_allowed, etc.)
         * @param array $details Violation details including IP, timestamp, etc.
         * @param int|null $form_id Form ID if applicable
         */
        do_action('gf_js_embed_security_violation', $violation_type, $details, $form_id);
    }
    
    /**
     * Get security settings with filter
     * 
     * @since 2.0.0
     * 
     * @param int $form_id Form ID
     * @return array Security settings
     */
    public static function get_security_settings($form_id) {
        $form_settings = GF_JS_Embed_Admin::get_form_settings($form_id);
        
        $security_settings = [
            'rate_limit_enabled' => $form_settings['rate_limit_enabled'] ?? true,
            'rate_limit_requests' => $form_settings['rate_limit_requests'] ?? 60,
            'rate_limit_window' => $form_settings['rate_limit_window'] ?? 60,
            'honeypot_enabled' => $form_settings['honeypot_enabled'] ?? true,
            'csrf_enabled' => $form_settings['csrf_enabled'] ?? true,
            'spam_detection' => $form_settings['spam_detection'] ?? true,
            'bot_detection' => $form_settings['bot_detection'] ?? true,
            'security_level' => $form_settings['security_level'] ?? 'medium',
            'allowed_domains' => $form_settings['allowed_domains'] ?? [],
            'api_key' => $form_settings['api_key'] ?? ''
        ];
        
        /**
         * Filters security settings for a form
         * 
         * @since 2.0.0
         * 
         * @param array $security_settings Current security settings
         * @param int $form_id Form ID
         */
        return apply_filters('gf_js_embed_security_settings', $security_settings, $form_id);
    }
}