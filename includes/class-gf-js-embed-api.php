<?php
/**
 * API handler class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_API {
    
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
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $namespace = 'gf-embed/v1';
        
        // Get form configuration
        register_rest_route($namespace, '/form/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_form_data'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        // Submit form
        register_rest_route($namespace, '/submit/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'submit_form'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        
        // Get form assets
        register_rest_route($namespace, '/assets/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_form_assets'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        
        // Track analytics events
        register_rest_route($namespace, '/analytics/track', [
            'methods' => 'POST',
            'callback' => [$this, 'track_analytics_event'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        
        // Get analytics data
        register_rest_route($namespace, '/analytics/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_analytics_data'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    /**
     * Check permissions for API requests
     */
    public function check_permissions($request) {
        // Handle CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (GF_JS_Embed_Security::is_domain_allowed($origin)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-CSRF-Token');
                exit;
            }
        }
        
        // Set CORS headers
        $this->set_cors_headers();
        
        // Rate limiting check
        $identifier = $this->get_rate_limit_identifier($request);
        $endpoint = $this->get_endpoint_from_request($request);
        $form_id = $request->get_param('id');
        
        $rate_limiter = GF_JS_Embed_Rate_Limiter::get_instance();
        $rate_limit_result = $rate_limiter->check_rate_limit($identifier, $endpoint, $form_id);
        
        // Add rate limit headers
        $rate_limiter->add_rate_limit_headers($rate_limit_result);
        
        if (!$rate_limit_result['allowed']) {
            return new WP_Error('rate_limit_exceeded', 
                sprintf(__('Rate limit exceeded. Try again in %d seconds.', 'gf-js-embed'), 
                    $rate_limit_result['retry_after'] ?? 60), 
                ['status' => 429]
            );
        }
        
        // API key validation
        $form_id = $request->get_param('id');
        if ($form_id) {
            $settings = GF_JS_Embed_Admin::get_form_settings($form_id);
            $api_key = $request->get_header('X-API-Key') ?: $request->get_param('api_key');
            
            // If form has API key configured, it's required
            if (!empty($settings['api_key'])) {
                if (!$api_key) {
                    return new WP_Error('missing_api_key', __('API key required', 'gf-js-embed'), ['status' => 401]);
                }
                
                if (!GF_JS_Embed_Security::validate_api_key($api_key, $form_id)) {
                    GF_JS_Embed_Security::log_security_event('invalid_api_key', [
                        'form_id' => $form_id,
                        'provided_key' => substr($api_key, 0, 8) . '...'
                    ]);
                    return new WP_Error('invalid_api_key', __('Invalid API key', 'gf-js-embed'), ['status' => 401]);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get rate limit identifier from request
     */
    private function get_rate_limit_identifier($request) {
        // Use multiple factors for identification
        $factors = [
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? ''
        ];
        
        // Check for emergency bypass first
        $rate_limiter = GF_JS_Embed_Rate_Limiter::get_instance();
        if ($rate_limiter->has_emergency_bypass($_SERVER['REMOTE_ADDR'])) {
            return 'bypass_' . $_SERVER['REMOTE_ADDR'];
        }
        
        // Use API key if available for more lenient limits
        $api_key = $request->get_header('X-API-Key') ?: $request->get_param('api_key');
        if ($api_key) {
            $factors[] = 'api_' . substr(hash('sha256', $api_key), 0, 16);
        }
        
        // Create composite identifier
        return hash('sha256', implode('|', array_filter($factors)));
    }
    
    /**
     * Get endpoint identifier for rate limiting
     */
    private function get_endpoint_from_request($request) {
        $route = $request->get_route();
        
        // Normalize route for rate limiting
        $endpoint_map = [
            '/gf-embed/v1/form/' => '/form/',
            '/gf-embed/v1/submit/' => '/submit/',
            '/gf-embed/v1/assets/' => '/assets/',
            '/gf-embed/v1/analytics/track' => '/analytics/track',
            '/gf-embed/v1/analytics/' => '/analytics/'
        ];
        
        foreach ($endpoint_map as $pattern => $normalized) {
            if (strpos($route, $pattern) !== false) {
                return $normalized;
            }
        }
        
        return $route;
    }
    
    /**
     * Set CORS headers for API responses
     */
    private function set_cors_headers() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (GF_JS_Embed_Security::is_domain_allowed($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
    }
    
    /**
     * Get form data
     */
    public function get_form_data($request) {
        $form_id = $request['id'];
        
        // Check if form exists
        if (!GFAPI::form_exists($form_id)) {
            return new WP_REST_Response(['success' => false, 'message' => __('Form not found', 'gf-js-embed')], 404);
        }
        
        $form = GFAPI::get_form($form_id);
        
        // Check if embedding is enabled
        $settings = GF_JS_Embed_Admin::get_form_settings($form_id);
        if (!$settings['enabled']) {
            return new WP_REST_Response(['success' => false, 'message' => __('Embedding disabled for this form', 'gf-js-embed')], 403);
        }
        
        // Check domain whitelist
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        if (!GF_JS_Embed_Security::is_domain_allowed($origin, $form_id)) {
            return new WP_REST_Response(['success' => false, 'message' => __('Domain not allowed', 'gf-js-embed')], 403);
        }
        
        // Track analytics
        GF_JS_Embed_Analytics::track_view($form_id, parse_url($origin, PHP_URL_HOST));
        
        // Prepare form data
        $form_data = $this->prepare_form_data($form, $settings);
        
        return new WP_REST_Response(['success' => true, 'form' => $form_data]);
    }
    
    /**
     * Prepare form data for API response
     */
    private function prepare_form_data($form, $settings) {
        $form_data = [
            'id' => $form['id'],
            'title' => $form['title'],
            'description' => $form['description'],
            'displayTitle' => $settings['display_title'],
            'displayDescription' => $settings['display_description'],
            'button' => [
                'text' => $form['button']['text'] ?? __('Submit', 'gf-js-embed'),
                'type' => $form['button']['type'] ?? 'text'
            ],
            'fields' => [],
            'cssClass' => $form['cssClass'] ?? '',
            'enableAnimation' => $form['enableAnimation'] ?? false,
            'validationSummary' => $form['validationSummary'] ?? false
        ];
        
        // Process fields
        foreach ($form['fields'] as $field) {
            $form_data['fields'][] = $this->prepare_field_data($field);
        }
        
        // Add pagination info for multi-page forms
        if (!empty($form['pagination'])) {
            $form_data['pagination'] = $form['pagination'];
        }
        
        // Add conditional logic rules
        if (!empty($form['conditionalLogic'])) {
            $form_data['conditionalLogic'] = $form['conditionalLogic'];
        }
        
        return $form_data;
    }
    
    /**
     * Prepare field data
     */
    private function prepare_field_data($field) {
        $field_data = [
            'id' => $field->id,
            'type' => $field->type,
            'label' => $field->label,
            'description' => $field->description,
            'isRequired' => $field->isRequired,
            'placeholder' => $field->placeholder,
            'cssClass' => $field->cssClass,
            'size' => $field->size,
            'defaultValue' => $field->defaultValue,
            'errorMessage' => $field->errorMessage
        ];
        
        // Add field-specific properties
        switch ($field->type) {
            case 'select':
            case 'multiselect':
            case 'radio':
            case 'checkbox':
                $field_data['choices'] = [];
                if (!empty($field->choices)) {
                    foreach ($field->choices as $choice) {
                        $field_data['choices'][] = [
                            'text' => $choice['text'],
                            'value' => $choice['value'],
                            'isSelected' => $choice['isSelected'] ?? false,
                            'price' => $choice['price'] ?? ''
                        ];
                    }
                }
                break;
                
            case 'fileupload':
                $field_data['allowedExtensions'] = $field->allowedExtensions;
                $field_data['maxFileSize'] = $field->maxFileSize;
                $field_data['multipleFiles'] = $field->multipleFiles;
                break;
                
            case 'date':
                $field_data['dateType'] = $field->dateType;
                $field_data['dateFormat'] = $field->dateFormat;
                $field_data['calendarIconType'] = $field->calendarIconType;
                break;
                
            case 'time':
                $field_data['timeFormat'] = $field->timeFormat;
                break;
                
            case 'number':
                $field_data['numberFormat'] = $field->numberFormat;
                $field_data['rangeMin'] = $field->rangeMin;
                $field_data['rangeMax'] = $field->rangeMax;
                break;
                
            case 'page':
                $field_data['nextButton'] = [
                    'type' => $field->nextButton['type'] ?? 'text',
                    'text' => $field->nextButton['text'] ?? __('Next', 'gf-js-embed')
                ];
                $field_data['previousButton'] = [
                    'type' => $field->previousButton['type'] ?? 'text',
                    'text' => $field->previousButton['text'] ?? __('Previous', 'gf-js-embed')
                ];
                break;
        }
        
        // Add conditional logic if present
        if (!empty($field->conditionalLogic)) {
            $field_data['conditionalLogic'] = $field->conditionalLogic;
        }
        
        return $field_data;
    }
    
    /**
     * Submit form
     */
    public function submit_form($request) {
        $form_id = $request['id'];
        
        // Verify form exists and embedding is enabled
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return new WP_REST_Response(['success' => false, 'message' => __('Form not found', 'gf-js-embed')], 404);
        }
        
        $settings = GF_JS_Embed_Admin::get_form_settings($form_id);
        if (!$settings['enabled']) {
            return new WP_REST_Response(['success' => false, 'message' => __('Embedding disabled', 'gf-js-embed')], 403);
        }
        
        // Get all submitted data
        $submitted_data = $request->get_params();
        
        // Perform comprehensive security scan
        $security_scan = GF_JS_Embed_Security::perform_security_scan($form_id, $submitted_data);
        
        if (!$security_scan['passed']) {
            return new WP_REST_Response([
                'success' => false, 
                'message' => __('Submission blocked by security filters', 'gf-js-embed'),
                'code' => 'security_blocked'
            ], 403);
        }
        
        // Validate CSRF token if provided
        $csrf_token = $request->get_header('X-CSRF-Token') ?: $submitted_data['gf_csrf_token'] ?? '';
        if ($csrf_token && !GF_JS_Embed_Security::validate_csrf_token($form_id, $csrf_token)) {
            return new WP_REST_Response([
                'success' => false, 
                'message' => __('Security token validation failed', 'gf-js-embed')
            ], 403);
        }
        
        // Sanitize form data
        $sanitized_data = GF_JS_Embed_Security::sanitize_form_data($submitted_data, $form['fields']);
        
        // Process submission
        $input_values = [];
        foreach ($sanitized_data as $key => $value) {
            if (strpos($key, 'input_') === 0) {
                $input_values[$key] = $value;
            }
        }
        
        // Validate form
        $validation = GFFormDisplay::validate($form, $input_values);
        
        if ($validation['is_valid']) {
            // Create entry
            $entry = [
                'form_id' => $form_id,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'source_url' => $_SERVER['HTTP_REFERER'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_by' => null,
                'status' => 'active'
            ];
            
            foreach ($input_values as $key => $value) {
                $field_id = str_replace('input_', '', $key);
                $entry[$field_id] = $value;
            }
            
            $entry_id = GFAPI::add_entry($entry);
            
            if (is_wp_error($entry_id)) {
                return new WP_REST_Response([
                    'success' => false, 
                    'message' => __('Submission failed', 'gf-js-embed')
                ], 500);
            }
            
            // Track submission with entry ID
            $domain = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
            GF_JS_Embed_Analytics::track_submission($form_id, $domain, $entry_id);
            
            // Get confirmation
            $confirmation = GFFormDisplay::get_confirmation($form, $entry);
            
            return new WP_REST_Response([
                'success' => true,
                'entry_id' => $entry_id,
                'confirmation' => [
                    'type' => $confirmation['type'] ?? 'message',
                    'message' => $confirmation['message'] ?? __('Thank you for your submission.', 'gf-js-embed'),
                    'url' => $confirmation['url'] ?? '',
                    'pageId' => $confirmation['pageId'] ?? ''
                ]
            ]);
        } else {
            // Return validation errors
            $errors = [];
            foreach ($form['fields'] as $field) {
                if (!empty($validation['failed_validation_page'][$field->id])) {
                    $errors[$field->id] = $field->validation_message ?: __('This field is required.', 'gf-js-embed');
                }
            }
            
            return new WP_REST_Response([
                'success' => false,
                'errors' => $errors,
                'message' => __('Please correct the errors below.', 'gf-js-embed')
            ], 400);
        }
    }
    
    /**
     * Get form assets
     */
    public function get_form_assets($request) {
        $form_id = $request['id'];
        $settings = GF_JS_Embed_Admin::get_form_settings($form_id);
        
        // Override theme if provided in request
        $theme = $request->get_param('theme');
        if ($theme !== null) {
            $settings['theme'] = sanitize_text_field($theme);
        }
        
        // Get custom CSS
        $css = GF_JS_Embed_Styling::get_form_css($form_id, $settings);
        
        // Get translations
        $translations = GF_JS_Embed_i18n::get_translations(get_locale());
        
        return new WP_REST_Response([
            'css' => $css,
            'translations' => $translations,
            'config' => [
                'dateFormat' => get_option('date_format'),
                'timeFormat' => get_option('time_format'),
                'startOfWeek' => get_option('start_of_week'),
                'currency' => GFCommon::get_currency()
            ]
        ]);
    }
    
    /**
     * Track analytics event
     */
    public function track_analytics_event($request) {
        $event_type = $request->get_param('event_type');
        $form_id = $request->get_param('form_id');
        $data = $request->get_param('data') ?? [];
        
        // Validate required parameters
        if (!$event_type || !$form_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Missing required parameters', 'gf-js-embed')
            ], 400);
        }
        
        // Process different event types
        switch ($event_type) {
            case 'field_interaction':
                GF_JS_Embed_Analytics::track_field_interaction(
                    $form_id,
                    $data['field_id'],
                    $data['interaction_type'],
                    $data['time_spent'] ?? 0
                );
                break;
                
            case 'field_error':
                GF_JS_Embed_Analytics::track_field_error(
                    $form_id,
                    $data['field_id'],
                    $data['error_type'],
                    $data['error_message'] ?? ''
                );
                break;
                
            case 'page_progression':
                GF_JS_Embed_Analytics::track_page_progression(
                    $form_id,
                    $data['page_number'],
                    $data['time_spent'] ?? 0,
                    $data['completed'] ?? false
                );
                break;
                
            default:
                return new WP_REST_Response([
                    'success' => false,
                    'message' => __('Invalid event type', 'gf-js-embed')
                ], 400);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Event tracked successfully', 'gf-js-embed')
        ]);
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics_data($request) {
        $form_id = $request->get_param('id');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        $metric = $request->get_param('metric') ?? 'all';
        
        if (!GFAPI::form_exists($form_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Form not found', 'gf-js-embed')
            ], 404);
        }
        
        $analytics = [];
        
        switch ($metric) {
            case 'overview':
                $analytics = GF_JS_Embed_Analytics::get_enhanced_analytics($form_id, $date_from, $date_to);
                break;
                
            case 'heatmap':
                $analytics = GF_JS_Embed_Analytics::get_field_heatmap($form_id, $date_from, $date_to);
                break;
                
            case 'timeseries':
                $days = $request->get_param('days') ?? 30;
                $type = $request->get_param('type') ?? 'views';
                $analytics = GF_JS_Embed_Database::get_time_series($form_id, $type, $days);
                break;
                
            case 'interactions':
                $analytics = GF_JS_Embed_Database::get_field_interactions($form_id, $date_from, $date_to);
                break;
                
            case 'errors':
                $analytics = GF_JS_Embed_Database::get_field_errors($form_id, $date_from, $date_to);
                break;
                
            case 'all':
            default:
                $analytics = [
                    'overview' => GF_JS_Embed_Analytics::get_enhanced_analytics($form_id, $date_from, $date_to),
                    'heatmap' => GF_JS_Embed_Analytics::get_field_heatmap($form_id, $date_from, $date_to),
                    'timeseries' => [
                        'views' => GF_JS_Embed_Database::get_time_series($form_id, 'views', 30),
                        'submissions' => GF_JS_Embed_Database::get_time_series($form_id, 'submissions', 30)
                    ]
                ];
                break;
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $analytics
        ]);
    }
}