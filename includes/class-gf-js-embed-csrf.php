<?php
/**
 * CSRF Protection class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_CSRF {
    
    private static $instance = null;
    
    /**
     * Session key for CSRF tokens
     */
    const TOKEN_SESSION_KEY = 'gf_js_embed_csrf_tokens';
    
    /**
     * Token timeout in seconds (30 minutes)
     */
    const TOKEN_TIMEOUT = 1800;
    
    /**
     * Maximum tokens per session
     */
    const MAX_TOKENS_PER_SESSION = 20;
    
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
        // Initialize session if needed
        add_action('init', [$this, 'init_session']);
        
        // Add CSRF token to API responses
        add_filter('gf_js_embed_api_response', [$this, 'add_csrf_token_to_response'], 10, 2);
        
        // Validate CSRF token on form submissions
        add_action('gf_js_embed_before_form_submit', [$this, 'validate_csrf_token'], 1);
        
        // Add REST endpoint for token generation
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        
        // Clean up expired tokens
        add_action('gf_js_embed_cleanup', [$this, 'cleanup_expired_tokens']);
    }
    
    /**
     * Initialize session if needed
     */
    public function init_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * Generate a new CSRF token
     */
    public function generate_token($form_id = null) {
        // Ensure session is available
        if (!session_id()) {
            return false;
        }
        
        $token = wp_generate_password(32, false);
        $token_data = [
            'token' => $token,
            'created' => time(),
            'form_id' => $form_id,
            'used' => false
        ];
        
        // Initialize session array if needed
        if (!isset($_SESSION[self::TOKEN_SESSION_KEY])) {
            $_SESSION[self::TOKEN_SESSION_KEY] = [];
        }
        
        // Clean up old tokens first
        $this->cleanup_expired_tokens();
        
        // Limit the number of tokens per session
        if (count($_SESSION[self::TOKEN_SESSION_KEY]) >= self::MAX_TOKENS_PER_SESSION) {
            // Remove oldest tokens
            usort($_SESSION[self::TOKEN_SESSION_KEY], function($a, $b) {
                return $a['created'] - $b['created'];
            });
            $_SESSION[self::TOKEN_SESSION_KEY] = array_slice($_SESSION[self::TOKEN_SESSION_KEY], -10);
        }
        
        // Store token
        $_SESSION[self::TOKEN_SESSION_KEY][] = $token_data;
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public function validate_token($token, $form_id = null, $consume = true) {
        // Check if CSRF protection is enabled
        $settings = get_option('gf_js_embed_settings', []);
        if (!isset($settings['enable_csrf_protection']) || !$settings['enable_csrf_protection']) {
            return true; // CSRF protection disabled
        }
        
        if (empty($token)) {
            return false;
        }
        
        // Ensure session is available
        if (!session_id() || !isset($_SESSION[self::TOKEN_SESSION_KEY])) {
            return false;
        }
        
        // Find and validate token
        foreach ($_SESSION[self::TOKEN_SESSION_KEY] as $index => $token_data) {
            if ($token_data['token'] === $token) {
                // Check if token is expired
                if ((time() - $token_data['created']) > self::TOKEN_TIMEOUT) {
                    unset($_SESSION[self::TOKEN_SESSION_KEY][$index]);
                    return false;
                }
                
                // Check if token was already used
                if ($token_data['used'] && $consume) {
                    return false;
                }
                
                // Check form ID if provided
                if ($form_id !== null && $token_data['form_id'] !== null && $token_data['form_id'] != $form_id) {
                    return false;
                }
                
                // Mark token as used if consuming
                if ($consume) {
                    $_SESSION[self::TOKEN_SESSION_KEY][$index]['used'] = true;
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add CSRF token to API response
     */
    public function add_csrf_token_to_response($response, $request) {
        // Only add tokens for form requests
        if (isset($response['form_html']) || isset($response['form_data'])) {
            $form_id = $request->get_param('form_id');
            $token = $this->generate_token($form_id);
            
            if ($token) {
                $response['csrf_token'] = $token;
                $response['csrf_timeout'] = self::TOKEN_TIMEOUT;
            }
        }
        
        return $response;
    }
    
    /**
     * Validate CSRF token on form submission
     */
    public function validate_csrf_token($form_data) {
        $token = isset($_POST['csrf_token']) ? sanitize_text_field($_POST['csrf_token']) : '';
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : null;
        
        if (!$this->validate_token($token, $form_id)) {
            wp_die(
                __('Security check failed. Please refresh the page and try again.', 'gf-js-embed'),
                __('Security Error', 'gf-js-embed'),
                ['response' => 403]
            );
        }
    }
    
    /**
     * Register REST endpoints
     */
    public function register_rest_endpoints() {
        // Token generation endpoint
        register_rest_route('gf-embed/v1', '/csrf-token', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_token_request'],
            'permission_callback' => '__return_true',
            'args' => [
                'form_id' => [
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
        
        // Token validation endpoint
        register_rest_route('gf-embed/v1', '/csrf-validate', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_token_validation'],
            'permission_callback' => '__return_true',
            'args' => [
                'token' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'form_id' => [
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
    }
    
    /**
     * Handle token generation request
     */
    public function handle_token_request($request) {
        $form_id = $request->get_param('form_id');
        $token = $this->generate_token($form_id);
        
        if ($token) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'timeout' => self::TOKEN_TIMEOUT,
                    'form_id' => $form_id
                ]
            ], 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Failed to generate CSRF token', 'gf-js-embed')
            ], 500);
        }
    }
    
    /**
     * Handle token validation request
     */
    public function handle_token_validation($request) {
        $token = $request->get_param('token');
        $form_id = $request->get_param('form_id');
        
        $is_valid = $this->validate_token($token, $form_id, false); // Don't consume on validation
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'valid' => $is_valid,
                'form_id' => $form_id
            ]
        ], 200);
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanup_expired_tokens() {
        if (!session_id() || !isset($_SESSION[self::TOKEN_SESSION_KEY])) {
            return;
        }
        
        $current_time = time();
        $_SESSION[self::TOKEN_SESSION_KEY] = array_filter($_SESSION[self::TOKEN_SESSION_KEY], function($token_data) use ($current_time) {
            return ($current_time - $token_data['created']) <= self::TOKEN_TIMEOUT;
        });
        
        // Re-index array
        $_SESSION[self::TOKEN_SESSION_KEY] = array_values($_SESSION[self::TOKEN_SESSION_KEY]);
    }
    
    /**
     * Get token statistics
     */
    public function get_token_stats() {
        if (!session_id() || !isset($_SESSION[self::TOKEN_SESSION_KEY])) {
            return [
                'total_tokens' => 0,
                'active_tokens' => 0,
                'expired_tokens' => 0,
                'used_tokens' => 0
            ];
        }
        
        $tokens = $_SESSION[self::TOKEN_SESSION_KEY];
        $current_time = time();
        
        $stats = [
            'total_tokens' => count($tokens),
            'active_tokens' => 0,
            'expired_tokens' => 0,
            'used_tokens' => 0
        ];
        
        foreach ($tokens as $token_data) {
            $is_expired = ($current_time - $token_data['created']) > self::TOKEN_TIMEOUT;
            
            if ($is_expired) {
                $stats['expired_tokens']++;
            } elseif ($token_data['used']) {
                $stats['used_tokens']++;
            } else {
                $stats['active_tokens']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if CSRF protection is enabled
     */
    public function is_enabled() {
        $settings = get_option('gf_js_embed_settings', []);
        return isset($settings['enable_csrf_protection']) && $settings['enable_csrf_protection'];
    }
    
    /**
     * Get token for JavaScript
     */
    public function get_token_for_js($form_id = null) {
        if (!$this->is_enabled()) {
            return null;
        }
        
        return $this->generate_token($form_id);
    }
}