<?php
/**
 * Multi-Page Forms Support class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_MultiPage {
    
    private static $instance = null;
    
    /**
     * Session key for form progress
     */
    const PROGRESS_SESSION_KEY = 'gf_js_embed_form_progress';
    
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
        
        // Add REST endpoints
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        
        // Filter form data to add pagination info
        add_filter('gf_js_embed_form_data', [$this, 'add_pagination_data'], 10, 2);
        
        // Handle partial form submissions
        add_action('gf_js_embed_partial_submit', [$this, 'handle_partial_submission'], 10, 3);
        
        // Clean up expired sessions
        add_action('gf_js_embed_cleanup', [$this, 'cleanup_expired_sessions']);
        
        // Add multi-page specific scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_multipage_scripts']);
        
        // Add admin interface
        add_filter('gf_js_embed_admin_settings_fields', [$this, 'add_admin_settings']);
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
     * Register REST endpoints
     */
    public function register_rest_endpoints() {
        $namespace = 'gf-embed/v1';
        
        // Save page progress
        register_rest_route($namespace, '/form/(?P<form_id>\d+)/save-progress', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_save_progress'],
            'permission_callback' => '__return_true',
            'args' => [
                'form_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ],
                'page' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ],
                'data' => [
                    'required' => true,
                    'type' => 'object'
                ]
            ]
        ]);
        
        // Get saved progress
        register_rest_route($namespace, '/form/(?P<form_id>\d+)/get-progress', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_get_progress'],
            'permission_callback' => '__return_true',
            'args' => [
                'form_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
        
        // Clear progress
        register_rest_route($namespace, '/form/(?P<form_id>\d+)/clear-progress', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_clear_progress'],
            'permission_callback' => '__return_true',
            'args' => [
                'form_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
        
        // Validate page
        register_rest_route($namespace, '/form/(?P<form_id>\d+)/validate-page', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_validate_page'],
            'permission_callback' => '__return_true',
            'args' => [
                'form_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ],
                'page' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ],
                'data' => [
                    'required' => true,
                    'type' => 'object'
                ]
            ]
        ]);
    }
    
    /**
     * Add pagination data to form
     */
    public function add_pagination_data($form_data, $form) {
        if (!isset($form['pagination']) || empty($form['pagination'])) {
            return $form_data;
        }
        
        // Get page breaks from form fields
        $pages = $this->get_form_pages($form);
        
        if (count($pages) <= 1) {
            return $form_data;
        }
        
        $form_data['multipage'] = [
            'enabled' => true,
            'pages' => $pages,
            'total_pages' => count($pages),
            'current_page' => 1,
            'progress_indicator' => $form['pagination']['progressbar_style'] ?? 'steps',
            'page_names' => $this->get_page_names($form),
            'navigation' => [
                'previous_button' => $form['pagination']['labels']['previousButton'] ?? 'Previous',
                'next_button' => $form['pagination']['labels']['nextButton'] ?? 'Next',
                'submit_button' => $form['button']['text'] ?? 'Submit'
            ],
            'validation' => [
                'validate_on_navigate' => true,
                'allow_previous_without_validation' => true
            ]
        ];
        
        // Get saved progress if available
        $progress = $this->get_form_progress($form['id']);
        if ($progress) {
            $form_data['multipage']['saved_progress'] = $progress;
        }
        
        return $form_data;
    }
    
    /**
     * Get form pages based on page breaks
     */
    private function get_form_pages($form) {
        $pages = [];
        $current_page = [];
        $page_number = 1;
        
        foreach ($form['fields'] as $field) {
            if ($field['type'] === 'page') {
                // Save current page if it has fields
                if (!empty($current_page)) {
                    $pages[] = [
                        'number' => $page_number,
                        'fields' => $current_page,
                        'title' => $field['label'] ?? "Page $page_number"
                    ];
                    $page_number++;
                    $current_page = [];
                }
            } else {
                $current_page[] = $field['id'];
            }
        }
        
        // Add last page
        if (!empty($current_page)) {
            $pages[] = [
                'number' => $page_number,
                'fields' => $current_page,
                'title' => "Page $page_number"
            ];
        }
        
        return $pages;
    }
    
    /**
     * Get page names from form
     */
    private function get_page_names($form) {
        $names = [];
        $page_number = 1;
        
        foreach ($form['fields'] as $field) {
            if ($field['type'] === 'page' && isset($field['label'])) {
                $names[$page_number] = $field['label'];
                $page_number++;
            }
        }
        
        return $names;
    }
    
    /**
     * Handle save progress request
     */
    public function handle_save_progress($request) {
        $form_id = $request->get_param('form_id');
        $page = $request->get_param('page');
        $data = $request->get_param('data');
        
        // Verify form exists
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return new WP_Error('form_not_found', 'Form not found', ['status' => 404]);
        }
        
        // Get existing progress or create new
        $progress = $this->get_form_progress($form_id) ?: [
            'form_id' => $form_id,
            'started_at' => time(),
            'last_updated' => time(),
            'current_page' => $page,
            'data' => []
        ];
        
        // Update progress
        $progress['last_updated'] = time();
        $progress['current_page'] = $page;
        
        // Merge data
        foreach ($data as $field_id => $value) {
            $progress['data'][$field_id] = $value;
        }
        
        // Save to session
        $this->save_form_progress($form_id, $progress);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'progress_saved' => true,
                'current_page' => $page,
                'last_updated' => $progress['last_updated']
            ]
        ], 200);
    }
    
    /**
     * Handle get progress request
     */
    public function handle_get_progress($request) {
        $form_id = $request->get_param('form_id');
        
        $progress = $this->get_form_progress($form_id);
        
        if (!$progress) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'has_progress' => false
                ]
            ], 200);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'has_progress' => true,
                'progress' => $progress
            ]
        ], 200);
    }
    
    /**
     * Handle clear progress request
     */
    public function handle_clear_progress($request) {
        $form_id = $request->get_param('form_id');
        
        $this->clear_form_progress($form_id);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'progress_cleared' => true
            ]
        ], 200);
    }
    
    /**
     * Handle validate page request
     */
    public function handle_validate_page($request) {
        $form_id = $request->get_param('form_id');
        $page = $request->get_param('page');
        $data = $request->get_param('data');
        
        // Get form
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return new WP_Error('form_not_found', 'Form not found', ['status' => 404]);
        }
        
        // Get fields for this page
        $pages = $this->get_form_pages($form);
        $page_fields = [];
        
        foreach ($pages as $p) {
            if ($p['number'] == $page) {
                $page_fields = $p['fields'];
                break;
            }
        }
        
        // Validate page fields
        $validation_errors = [];
        
        foreach ($form['fields'] as $field) {
            if (!in_array($field['id'], $page_fields)) {
                continue;
            }
            
            // Check required fields
            if ($field['isRequired'] && empty($data[$field['id']])) {
                $validation_errors[$field['id']] = [
                    'message' => $field['errorMessage'] ?? 'This field is required',
                    'type' => 'required'
                ];
            }
            
            // Additional field-specific validation
            $field_value = $data[$field['id']] ?? '';
            $validation_result = $this->validate_field($field, $field_value);
            
            if (!$validation_result['is_valid']) {
                $validation_errors[$field['id']] = [
                    'message' => $validation_result['message'],
                    'type' => $validation_result['type']
                ];
            }
        }
        
        $is_valid = empty($validation_errors);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'valid' => $is_valid,
                'errors' => $validation_errors,
                'page' => $page
            ]
        ], 200);
    }
    
    /**
     * Validate field value
     */
    private function validate_field($field, $value) {
        $result = ['is_valid' => true, 'message' => '', 'type' => ''];
        
        // Email validation
        if ($field['type'] === 'email' && !empty($value)) {
            if (!is_email($value)) {
                $result = [
                    'is_valid' => false,
                    'message' => 'Please enter a valid email address',
                    'type' => 'invalid_email'
                ];
            }
        }
        
        // Number validation
        if ($field['type'] === 'number' && !empty($value)) {
            if (!is_numeric($value)) {
                $result = [
                    'is_valid' => false,
                    'message' => 'Please enter a valid number',
                    'type' => 'invalid_number'
                ];
            }
        }
        
        // Apply filters for custom validation
        return apply_filters('gf_js_embed_validate_field', $result, $field, $value);
    }
    
    /**
     * Get form progress from session
     */
    private function get_form_progress($form_id) {
        if (!session_id() || !isset($_SESSION[self::PROGRESS_SESSION_KEY])) {
            return null;
        }
        
        $progress = $_SESSION[self::PROGRESS_SESSION_KEY][$form_id] ?? null;
        
        // Check if progress is expired (24 hours)
        if ($progress && (time() - $progress['last_updated']) > 86400) {
            unset($_SESSION[self::PROGRESS_SESSION_KEY][$form_id]);
            return null;
        }
        
        return $progress;
    }
    
    /**
     * Save form progress to session
     */
    private function save_form_progress($form_id, $progress) {
        if (!session_id()) {
            return false;
        }
        
        if (!isset($_SESSION[self::PROGRESS_SESSION_KEY])) {
            $_SESSION[self::PROGRESS_SESSION_KEY] = [];
        }
        
        $_SESSION[self::PROGRESS_SESSION_KEY][$form_id] = $progress;
        
        return true;
    }
    
    /**
     * Clear form progress
     */
    private function clear_form_progress($form_id) {
        if (!session_id() || !isset($_SESSION[self::PROGRESS_SESSION_KEY])) {
            return;
        }
        
        unset($_SESSION[self::PROGRESS_SESSION_KEY][$form_id]);
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanup_expired_sessions() {
        if (!session_id() || !isset($_SESSION[self::PROGRESS_SESSION_KEY])) {
            return;
        }
        
        $current_time = time();
        
        foreach ($_SESSION[self::PROGRESS_SESSION_KEY] as $form_id => $progress) {
            // Remove progress older than 24 hours
            if (($current_time - $progress['last_updated']) > 86400) {
                unset($_SESSION[self::PROGRESS_SESSION_KEY][$form_id]);
            }
        }
    }
    
    /**
     * Enqueue multi-page scripts
     */
    public function enqueue_multipage_scripts() {
        if (!is_admin()) {
            wp_enqueue_script(
                'gf-embed-multipage',
                GF_JS_EMBED_PLUGIN_URL . 'assets/js/gf-embed-multipage.js',
                ['gf-embed-events'],
                GF_JS_EMBED_VERSION,
                true
            );
            
            wp_localize_script('gf-embed-multipage', 'gfMultiPageConfig', [
                'restUrl' => rest_url('gf-embed/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'autoSave' => true,
                'autoSaveInterval' => 30000 // 30 seconds
            ]);
            
            // Enqueue CSS
            wp_enqueue_style(
                'gf-embed-multipage',
                GF_JS_EMBED_PLUGIN_URL . 'assets/css/gf-embed-multipage.css',
                [],
                GF_JS_EMBED_VERSION
            );
        }
    }
    
    /**
     * Handle partial form submission
     */
    public function handle_partial_submission($form_id, $page, $data) {
        // Log partial submission event
        do_action('gf_js_embed_log_event', [
            'form_id' => $form_id,
            'event_type' => 'partial_submission',
            'event_data' => [
                'page' => $page,
                'fields_count' => count($data)
            ]
        ]);
        
        // Save progress
        $this->save_form_progress($form_id, [
            'form_id' => $form_id,
            'started_at' => time(),
            'last_updated' => time(),
            'current_page' => $page,
            'data' => $data
        ]);
    }
}