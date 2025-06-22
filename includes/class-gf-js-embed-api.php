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
        
        // Enhanced rate limiting
        $identifier = $_SERVER['REMOTE_ADDR'];
        $form_id = $request->get_param('id');
        if (!GF_JS_Embed_Security::check_advanced_rate_limit($identifier, $form_id)) {
            return new WP_Error('rate_limit_exceeded', __('Rate limit exceeded. Please try again later.', 'gf-js-embed'), ['status' => 429]);
        }
        
        // API key validation if provided
        $api_key = $request->get_header('X-API-Key');
        if ($api_key && $form_id && !GF_JS_Embed_Security::validate_api_key($api_key, $form_id)) {
            GF_JS_Embed_Security::log_security_event('invalid_api_key', [
                'form_id' => $form_id,
                'provided_key' => substr($api_key, 0, 8) . '...'
            ]);
            return new WP_Error('invalid_api_key', __('Invalid API key', 'gf-js-embed'), ['status' => 401]);
        }
        
        return true;
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
            
            // Track submission
            GF_JS_Embed_Analytics::track_submission($form_id);
            
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
}