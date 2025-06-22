<?php
/**
 * Testing Dashboard class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Testing {
    
    private static $instance = null;
    
    /**
     * Test results storage
     */
    private $test_results = [];
    
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
        add_action('admin_menu', [$this, 'add_testing_menu'], 30);
        add_action('wp_ajax_gf_js_embed_run_test', [$this, 'ajax_run_test']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_testing_scripts']);
    }
    
    /**
     * Add testing menu
     */
    public function add_testing_menu() {
        add_submenu_page(
            'gf_edit_forms',
            __('JavaScript Embed Testing', 'gf-js-embed'),
            __('JS Embed Testing', 'gf-js-embed'),
            'manage_options',
            'gf_js_embed_testing',
            [$this, 'testing_page']
        );
    }
    
    /**
     * Enqueue testing scripts
     */
    public function enqueue_testing_scripts($hook) {
        if ($hook !== 'forms_page_gf_js_embed_testing') {
            return;
        }
        
        wp_enqueue_script(
            'gf-js-embed-testing',
            GF_JS_EMBED_PLUGIN_URL . 'assets/js/testing.js',
            ['jquery'],
            GF_JS_EMBED_VERSION,
            true
        );
        
        wp_localize_script('gf-js-embed-testing', 'gfJSEmbedTesting', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gf_js_embed_testing'),
            'strings' => [
                'running' => __('Running tests...', 'gf-js-embed'),
                'complete' => __('Tests complete!', 'gf-js-embed'),
                'error' => __('An error occurred while running tests.', 'gf-js-embed'),
                'export_success' => __('Test results exported successfully.', 'gf-js-embed')
            ]
        ]);
        
        wp_enqueue_style(
            'gf-js-embed-testing',
            GF_JS_EMBED_PLUGIN_URL . 'assets/css/testing.css',
            [],
            GF_JS_EMBED_VERSION
        );
    }
    
    /**
     * Testing page
     */
    public function testing_page() {
        ?>
        <div class="wrap">
            <h1>
                <?php _e('JavaScript Embed Testing Dashboard', 'gf-js-embed'); ?>
                <a href="<?php echo admin_url('admin.php?page=gf_js_embed_analytics'); ?>" class="page-title-action">
                    <?php _e('Back to Analytics', 'gf-js-embed'); ?>
                </a>
            </h1>
            
            <div class="notice notice-info">
                <p><?php _e('Use this testing dashboard to validate your plugin configuration, test forms, and diagnose any issues.', 'gf-js-embed'); ?></p>
            </div>
            
            <div class="gf-testing-dashboard">
                <div class="test-categories">
                    <?php $this->render_test_category('system', __('System Health Check', 'gf-js-embed'), '🏥'); ?>
                    <?php $this->render_test_category('forms', __('Form Configuration Tests', 'gf-js-embed'), '📋'); ?>
                    <?php $this->render_test_category('api', __('API Endpoint Tests', 'gf-js-embed'), '🚀'); ?>
                    <?php $this->render_test_category('javascript', __('JavaScript SDK Tests', 'gf-js-embed'), '📱'); ?>
                    <?php $this->render_test_category('security', __('Security Tests', 'gf-js-embed'), '🔒'); ?>
                    <?php $this->render_test_category('performance', __('Performance Tests', 'gf-js-embed'), '⚡'); ?>
                </div>
                
                <div class="test-actions">
                    <button id="run-all-tests" class="button button-primary button-hero">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Run All Tests', 'gf-js-embed'); ?>
                    </button>
                    
                    <button id="export-results" class="button button-secondary button-hero" disabled>
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Results', 'gf-js-embed'); ?>
                    </button>
                </div>
                
                <div id="test-results" class="test-results" style="display: none;">
                    <h2><?php _e('Test Results', 'gf-js-embed'); ?></h2>
                    <div id="results-container"></div>
                </div>
            </div>
            
            <div id="test-wizard" class="test-wizard" style="display: none;">
                <h2><?php _e('Guided Testing Wizard', 'gf-js-embed'); ?> 🧙‍♂️</h2>
                <div class="wizard-steps">
                    <div class="wizard-step" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-title"><?php _e('System Check', 'gf-js-embed'); ?></span>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-title"><?php _e('Form Discovery', 'gf-js-embed'); ?></span>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-title"><?php _e('API Validation', 'gf-js-embed'); ?></span>
                    </div>
                    <div class="wizard-step" data-step="4">
                        <span class="step-number">4</span>
                        <span class="step-title"><?php _e('Security Check', 'gf-js-embed'); ?></span>
                    </div>
                    <div class="wizard-step" data-step="5">
                        <span class="step-number">5</span>
                        <span class="step-title"><?php _e('Final Report', 'gf-js-embed'); ?></span>
                    </div>
                </div>
                <div id="wizard-content"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render test category
     */
    private function render_test_category($id, $title, $icon) {
        ?>
        <div class="test-category" data-category="<?php echo esc_attr($id); ?>">
            <div class="category-header">
                <span class="category-icon"><?php echo $icon; ?></span>
                <h3><?php echo esc_html($title); ?></h3>
                <button class="button run-test" data-test="<?php echo esc_attr($id); ?>">
                    <?php _e('Run', 'gf-js-embed'); ?>
                </button>
            </div>
            <div class="category-status" style="display: none;">
                <div class="status-indicator"></div>
                <div class="status-message"></div>
            </div>
            <div class="category-results" style="display: none;"></div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for running tests
     */
    public function ajax_run_test() {
        check_ajax_referer('gf_js_embed_testing', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $test_type = sanitize_text_field($_POST['test_type'] ?? '');
        $results = [];
        
        switch ($test_type) {
            case 'system':
                $results = $this->run_system_tests();
                break;
            case 'forms':
                $results = $this->run_form_tests();
                break;
            case 'api':
                $results = $this->run_api_tests();
                break;
            case 'javascript':
                $results = $this->run_javascript_tests();
                break;
            case 'security':
                $results = $this->run_security_tests();
                break;
            case 'performance':
                $results = $this->run_performance_tests();
                break;
            case 'all':
                $results = $this->run_all_tests();
                break;
            default:
                wp_send_json_error('Invalid test type');
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Run system health tests
     */
    private function run_system_tests() {
        $tests = [];
        
        // Test 1: Gravity Forms Active
        $gf_active = class_exists('GFForms');
        $tests[] = [
            'name' => __('Gravity Forms Plugin', 'gf-js-embed'),
            'status' => $gf_active ? 'pass' : 'fail',
            'message' => $gf_active 
                ? __('Gravity Forms is active and available', 'gf-js-embed')
                : __('Gravity Forms is not installed or activated', 'gf-js-embed'),
            'fix' => !$gf_active ? __('Install and activate Gravity Forms plugin', 'gf-js-embed') : ''
        ];
        
        // Test 2: WordPress Version
        global $wp_version;
        $wp_compatible = version_compare($wp_version, '5.8', '>=');
        $tests[] = [
            'name' => __('WordPress Version', 'gf-js-embed'),
            'status' => $wp_compatible ? 'pass' : 'warning',
            'message' => sprintf(__('WordPress %s detected', 'gf-js-embed'), $wp_version),
            'fix' => !$wp_compatible ? __('Update WordPress to version 5.8 or higher', 'gf-js-embed') : ''
        ];
        
        // Test 3: PHP Version
        $php_compatible = version_compare(PHP_VERSION, '7.4', '>=');
        $tests[] = [
            'name' => __('PHP Version', 'gf-js-embed'),
            'status' => $php_compatible ? 'pass' : 'fail',
            'message' => sprintf(__('PHP %s detected', 'gf-js-embed'), PHP_VERSION),
            'fix' => !$php_compatible ? __('Update PHP to version 7.4 or higher', 'gf-js-embed') : ''
        ];
        
        // Test 4: File Permissions
        $plugin_dir = GF_JS_EMBED_PLUGIN_DIR;
        $writable = is_writable($plugin_dir . 'languages/');
        $tests[] = [
            'name' => __('File Permissions', 'gf-js-embed'),
            'status' => $writable ? 'pass' : 'warning',
            'message' => $writable 
                ? __('Plugin directories are writable', 'gf-js-embed')
                : __('Some directories may not be writable', 'gf-js-embed'),
            'fix' => !$writable ? __('Check file permissions for the plugin directory', 'gf-js-embed') : ''
        ];
        
        // Test 5: Database Access
        $db_test = $this->test_database_access();
        $tests[] = [
            'name' => __('Database Access', 'gf-js-embed'),
            'status' => $db_test ? 'pass' : 'fail',
            'message' => $db_test 
                ? __('Database connection successful', 'gf-js-embed')
                : __('Database connection failed', 'gf-js-embed'),
            'fix' => !$db_test ? __('Check database credentials and permissions', 'gf-js-embed') : ''
        ];
        
        // Test 6: REST API
        $rest_enabled = rest_get_server() !== null;
        $tests[] = [
            'name' => __('REST API', 'gf-js-embed'),
            'status' => $rest_enabled ? 'pass' : 'fail',
            'message' => $rest_enabled 
                ? __('WordPress REST API is enabled', 'gf-js-embed')
                : __('WordPress REST API is disabled', 'gf-js-embed'),
            'fix' => !$rest_enabled ? __('Enable WordPress REST API in your configuration', 'gf-js-embed') : ''
        ];
        
        // Test 7: User Capabilities
        $can_manage = current_user_can('manage_options');
        $can_edit_forms = current_user_can('gravityforms_edit_forms');
        $tests[] = [
            'name' => __('User Capabilities', 'gf-js-embed'),
            'status' => ($can_manage || $can_edit_forms) ? 'pass' : 'warning',
            'message' => __('Current user has appropriate capabilities', 'gf-js-embed'),
            'details' => [
                'manage_options' => $can_manage,
                'gravityforms_edit_forms' => $can_edit_forms
            ]
        ];
        
        return [
            'category' => 'system',
            'title' => __('System Health Check', 'gf-js-embed'),
            'tests' => $tests,
            'summary' => $this->generate_summary($tests)
        ];
    }
    
    /**
     * Run form configuration tests
     */
    private function run_form_tests() {
        $tests = [];
        
        if (!class_exists('GFAPI')) {
            return [
                'category' => 'forms',
                'title' => __('Form Configuration Tests', 'gf-js-embed'),
                'tests' => [[
                    'name' => __('Gravity Forms API', 'gf-js-embed'),
                    'status' => 'fail',
                    'message' => __('Gravity Forms API not available', 'gf-js-embed'),
                    'fix' => __('Install and activate Gravity Forms', 'gf-js-embed')
                ]],
                'summary' => ['total' => 1, 'passed' => 0, 'failed' => 1, 'warnings' => 0]
            ];
        }
        
        // Get all forms
        $forms = GFAPI::get_forms();
        $total_forms = count($forms);
        $enabled_forms = 0;
        $form_issues = [];
        
        foreach ($forms as $form) {
            $settings = GF_JS_Embed_Admin::get_form_settings($form['id']);
            
            if ($settings['enabled']) {
                $enabled_forms++;
                
                // Check form configuration
                $form_test = [
                    'name' => sprintf(__('Form: %s (ID: %d)', 'gf-js-embed'), $form['title'], $form['id']),
                    'status' => 'pass',
                    'message' => __('Form is properly configured for embedding', 'gf-js-embed'),
                    'details' => []
                ];
                
                // Check domain whitelist
                if (empty($settings['allowed_domains']) || in_array('*', $settings['allowed_domains'])) {
                    $form_test['status'] = 'warning';
                    $form_test['details'][] = __('All domains allowed - consider restricting for security', 'gf-js-embed');
                }
                
                // Check security settings
                if (!$settings['honeypot_enabled'] || !$settings['csrf_enabled']) {
                    $form_test['status'] = 'warning';
                    $form_test['details'][] = __('Some security features are disabled', 'gf-js-embed');
                }
                
                // Check for API key
                if (empty($settings['api_key'])) {
                    $form_test['details'][] = __('No API key configured (optional)', 'gf-js-embed');
                }
                
                $tests[] = $form_test;
            }
        }
        
        // Summary test
        $tests[] = [
            'name' => __('Forms Summary', 'gf-js-embed'),
            'status' => $enabled_forms > 0 ? 'pass' : 'warning',
            'message' => sprintf(
                __('%d of %d forms enabled for JavaScript embedding', 'gf-js-embed'),
                $enabled_forms,
                $total_forms
            ),
            'fix' => $enabled_forms === 0 ? __('Enable JavaScript embedding for at least one form', 'gf-js-embed') : ''
        ];
        
        return [
            'category' => 'forms',
            'title' => __('Form Configuration Tests', 'gf-js-embed'),
            'tests' => $tests,
            'summary' => $this->generate_summary($tests)
        ];
    }
    
    /**
     * Run API endpoint tests
     */
    private function run_api_tests() {
        $tests = [];
        
        // Get a test form
        $test_form = null;
        if (class_exists('GFAPI')) {
            $forms = GFAPI::get_forms();
            foreach ($forms as $form) {
                $settings = GF_JS_Embed_Admin::get_form_settings($form['id']);
                if ($settings['enabled']) {
                    $test_form = $form;
                    break;
                }
            }
        }
        
        // Test 1: REST API namespace registration
        $namespaces = rest_get_server()->get_namespaces();
        $namespace_exists = in_array('gf-embed/v1', $namespaces);
        
        $tests[] = [
            'name' => __('REST API Namespace', 'gf-js-embed'),
            'status' => $namespace_exists ? 'pass' : 'fail',
            'message' => $namespace_exists 
                ? __('gf-embed/v1 namespace is registered', 'gf-js-embed')
                : __('gf-embed/v1 namespace not found', 'gf-js-embed'),
            'fix' => !$namespace_exists ? __('Check if plugin activated properly', 'gf-js-embed') : ''
        ];
        
        // Test 2: Form data endpoint
        if ($test_form) {
            $form_url = rest_url('gf-embed/v1/form/' . $test_form['id']);
            $response = wp_remote_get($form_url, [
                'timeout' => 10,
                'sslverify' => false
            ]);
            
            $is_error = is_wp_error($response);
            $status_code = wp_remote_retrieve_response_code($response);
            
            $tests[] = [
                'name' => __('Form Data Endpoint', 'gf-js-embed'),
                'status' => (!$is_error && $status_code === 200) ? 'pass' : 'fail',
                'message' => (!$is_error && $status_code === 200)
                    ? sprintf(__('Form endpoint responding correctly (Status: %d)', 'gf-js-embed'), $status_code)
                    : sprintf(__('Form endpoint error (Status: %s)', 'gf-js-embed'), $is_error ? 'Error' : $status_code),
                'details' => [
                    'url' => $form_url,
                    'response_time' => $is_error ? 'N/A' : wp_remote_retrieve_header($response, 'x-response-time')
                ]
            ];
        }
        
        // Test 3: Submit endpoint structure
        $submit_routes = rest_get_server()->get_routes();
        $submit_route_exists = isset($submit_routes['/gf-embed/v1/submit/(?P<id>[\d]+)']);
        
        $tests[] = [
            'name' => __('Submit Endpoint', 'gf-js-embed'),
            'status' => $submit_route_exists ? 'pass' : 'fail',
            'message' => $submit_route_exists 
                ? __('Form submission endpoint is registered', 'gf-js-embed')
                : __('Form submission endpoint not found', 'gf-js-embed')
        ];
        
        // Test 4: CORS headers
        if ($test_form) {
            $headers = wp_remote_retrieve_headers($response ?? []);
            $cors_present = isset($headers['access-control-allow-origin']);
            
            $tests[] = [
                'name' => __('CORS Headers', 'gf-js-embed'),
                'status' => $cors_present ? 'pass' : 'warning',
                'message' => $cors_present 
                    ? __('CORS headers are present', 'gf-js-embed')
                    : __('CORS headers not detected', 'gf-js-embed'),
                'fix' => !$cors_present ? __('Check domain whitelist settings', 'gf-js-embed') : ''
            ];
        }
        
        return [
            'category' => 'api',
            'title' => __('API Endpoint Tests', 'gf-js-embed'),
            'tests' => $tests,
            'summary' => $this->generate_summary($tests)
        ];
    }
    
    /**
     * Run JavaScript SDK tests
     */
    private function run_javascript_tests() {
        $tests = [];
        
        // Test 1: SDK file exists
        $sdk_path = GF_JS_EMBED_PLUGIN_DIR . 'assets/js/gf-embed-sdk.js';
        $sdk_exists = file_exists($sdk_path);
        $sdk_size = $sdk_exists ? filesize($sdk_path) : 0;
        
        $tests[] = [
            'name' => __('SDK File', 'gf-js-embed'),
            'status' => $sdk_exists ? 'pass' : 'fail',
            'message' => $sdk_exists 
                ? sprintf(__('SDK file exists (%s)', 'gf-js-embed'), size_format($sdk_size))
                : __('SDK file not found', 'gf-js-embed'),
            'fix' => !$sdk_exists ? __('Reinstall the plugin', 'gf-js-embed') : ''
        ];
        
        // Test 2: SDK endpoint accessibility
        $sdk_url = home_url('/gf-js-embed/v1/embed.js');
        $response = wp_remote_head($sdk_url, [
            'timeout' => 10,
            'sslverify' => false
        ]);
        
        $sdk_accessible = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        
        $tests[] = [
            'name' => __('SDK Endpoint', 'gf-js-embed'),
            'status' => $sdk_accessible ? 'pass' : 'fail',
            'message' => $sdk_accessible 
                ? __('SDK endpoint is accessible', 'gf-js-embed')
                : __('SDK endpoint not accessible', 'gf-js-embed'),
            'details' => ['url' => $sdk_url]
        ];
        
        // Test 3: Minified version
        $min_path = GF_JS_EMBED_PLUGIN_DIR . 'assets/js/gf-embed-sdk.min.js';
        $min_exists = file_exists($min_path);
        
        $tests[] = [
            'name' => __('Minified SDK', 'gf-js-embed'),
            'status' => $min_exists ? 'pass' : 'warning',
            'message' => $min_exists 
                ? __('Minified SDK available', 'gf-js-embed')
                : __('Minified SDK not found', 'gf-js-embed'),
            'fix' => !$min_exists ? __('Run build process to create minified version', 'gf-js-embed') : ''
        ];
        
        // Test 4: SDK content validation
        if ($sdk_exists) {
            $sdk_content = file_get_contents($sdk_path);
            $has_api_class = strpos($sdk_content, 'GravityFormsEmbed') !== false;
            $has_event_handlers = strpos($sdk_content, 'addEventListener') !== false;
            
            $tests[] = [
                'name' => __('SDK Structure', 'gf-js-embed'),
                'status' => ($has_api_class && $has_event_handlers) ? 'pass' : 'warning',
                'message' => __('SDK code structure validation', 'gf-js-embed'),
                'details' => [
                    'API Class' => $has_api_class ? '✓' : '✗',
                    'Event Handlers' => $has_event_handlers ? '✓' : '✗'
                ]
            ];
        }
        
        return [
            'category' => 'javascript',
            'title' => __('JavaScript SDK Tests', 'gf-js-embed'),
            'tests' => $tests,
            'summary' => $this->generate_summary($tests),
            'note' => __('Full SDK functionality tests require browser environment', 'gf-js-embed')
        ];
    }
    
    /**
     * Run security tests
     */
    private function run_security_tests() {
        $tests = [];
        
        // Test 1: Rate limiting configuration
        $rate_limit_enabled = get_option('gf_js_embed_rate_limiting', true);
        $tests[] = [
            'name' => __('Rate Limiting', 'gf-js-embed'),
            'status' => $rate_limit_enabled ? 'pass' : 'warning',
            'message' => $rate_limit_enabled 
                ? __('Rate limiting is enabled', 'gf-js-embed')
                : __('Rate limiting is disabled', 'gf-js-embed'),
            'fix' => !$rate_limit_enabled ? __('Enable rate limiting for API protection', 'gf-js-embed') : ''
        ];
        
        // Test 2: Domain whitelist check
        $unrestricted_forms = 0;
        if (class_exists('GFAPI')) {
            $forms = GFAPI::get_forms();
            foreach ($forms as $form) {
                $settings = GF_JS_Embed_Admin::get_form_settings($form['id']);
                if ($settings['enabled'] && (empty($settings['allowed_domains']) || in_array('*', $settings['allowed_domains']))) {
                    $unrestricted_forms++;
                }
            }
        }
        
        $tests[] = [
            'name' => __('Domain Restrictions', 'gf-js-embed'),
            'status' => $unrestricted_forms === 0 ? 'pass' : 'warning',
            'message' => $unrestricted_forms === 0 
                ? __('All forms have domain restrictions', 'gf-js-embed')
                : sprintf(__('%d forms allow all domains', 'gf-js-embed'), $unrestricted_forms),
            'fix' => $unrestricted_forms > 0 ? __('Configure domain whitelist for better security', 'gf-js-embed') : ''
        ];
        
        // Test 3: Security features status
        $security_features = [
            'honeypot' => 0,
            'csrf' => 0,
            'spam_detection' => 0,
            'bot_detection' => 0
        ];
        
        if (class_exists('GFAPI')) {
            $forms = GFAPI::get_forms();
            foreach ($forms as $form) {
                $settings = GF_JS_Embed_Admin::get_form_settings($form['id']);
                if ($settings['enabled']) {
                    if ($settings['honeypot_enabled']) $security_features['honeypot']++;
                    if ($settings['csrf_enabled']) $security_features['csrf']++;
                    if ($settings['spam_detection']) $security_features['spam_detection']++;
                    if ($settings['bot_detection']) $security_features['bot_detection']++;
                }
            }
        }
        
        $tests[] = [
            'name' => __('Security Features', 'gf-js-embed'),
            'status' => min($security_features) > 0 ? 'pass' : 'warning',
            'message' => __('Security feature usage across forms', 'gf-js-embed'),
            'details' => $security_features
        ];
        
        // Test 4: SSL/HTTPS check
        $is_ssl = is_ssl();
        $tests[] = [
            'name' => __('SSL/HTTPS', 'gf-js-embed'),
            'status' => $is_ssl ? 'pass' : 'warning',
            'message' => $is_ssl 
                ? __('Site is using HTTPS', 'gf-js-embed')
                : __('Site is not using HTTPS', 'gf-js-embed'),
            'fix' => !$is_ssl ? __('Enable SSL certificate for secure form submissions', 'gf-js-embed') : ''
        ];
        
        // Test 5: Nonce verification
        $nonce_test = wp_create_nonce('gf_js_embed_test');
        $nonce_valid = wp_verify_nonce($nonce_test, 'gf_js_embed_test');
        
        $tests[] = [
            'name' => __('Nonce System', 'gf-js-embed'),
            'status' => $nonce_valid ? 'pass' : 'fail',
            'message' => $nonce_valid 
                ? __('WordPress nonce system working correctly', 'gf-js-embed')
                : __('WordPress nonce system issue detected', 'gf-js-embed')
        ];
        
        return [
            'category' => 'security',
            'title' => __('Security Tests', 'gf-js-embed'),
            'tests' => $tests,
            'summary' => $this->generate_summary($tests)
        ];
    }
    
    /**
     * Run performance tests
     */
    private function run_performance_tests() {
        $tests = [];
        
        // Test 1: Database query performance
        $start_time = microtime(true);
        $this->test_database_access();
        $db_time = (microtime(true) - $start_time) * 1000;
        
        $tests[] = [
            'name' => __('Database Performance', 'gf-js-embed'),
            'status' => $db_time < 100 ? 'pass' : ($db_time < 500 ? 'warning' : 'fail'),
            'message' => sprintf(__('Database query time: %.2fms', 'gf-js-embed'), $db_time),
            'fix' => $db_time > 500 ? __('Database queries are slow, check server performance', 'gf-js-embed') : ''
        ];
        
        // Test 2: Memory usage
        $memory_usage = memory_get_peak_usage(true);
        $memory_limit = $this->get_memory_limit();
        $memory_percent = ($memory_usage / $memory_limit) * 100;
        
        $tests[] = [
            'name' => __('Memory Usage', 'gf-js-embed'),
            'status' => $memory_percent < 50 ? 'pass' : ($memory_percent < 80 ? 'warning' : 'fail'),
            'message' => sprintf(
                __('Using %s of %s available memory (%.1f%%)', 'gf-js-embed'),
                size_format($memory_usage),
                size_format($memory_limit),
                $memory_percent
            ),
            'fix' => $memory_percent > 80 ? __('Consider increasing PHP memory limit', 'gf-js-embed') : ''
        ];
        
        // Test 3: API response time
        if (class_exists('GFAPI')) {
            $forms = GFAPI::get_forms();
            if (!empty($forms)) {
                $test_form = $forms[0];
                $api_url = rest_url('gf-embed/v1/form/' . $test_form['id']);
                
                $start_time = microtime(true);
                $response = wp_remote_get($api_url, ['timeout' => 10, 'sslverify' => false]);
                $api_time = (microtime(true) - $start_time) * 1000;
                
                $tests[] = [
                    'name' => __('API Response Time', 'gf-js-embed'),
                    'status' => $api_time < 200 ? 'pass' : ($api_time < 1000 ? 'warning' : 'fail'),
                    'message' => sprintf(__('API response time: %.2fms', 'gf-js-embed'), $api_time),
                    'fix' => $api_time > 1000 ? __('API responses are slow, check server performance', 'gf-js-embed') : ''
                ];
            }
        }
        
        // Test 4: Plugin load time
        $load_time = defined('GF_JS_EMBED_LOAD_TIME') ? GF_JS_EMBED_LOAD_TIME : 0;
        $tests[] = [
            'name' => __('Plugin Load Time', 'gf-js-embed'),
            'status' => $load_time < 50 ? 'pass' : ($load_time < 200 ? 'warning' : 'fail'),
            'message' => sprintf(__('Plugin initialization: %.2fms', 'gf-js-embed'), $load_time),
            'details' => ['timestamp' => current_time('mysql')]
        ];
        
        return [
            'category' => 'performance',
            'title' => __('Performance Tests', 'gf-js-embed'),
            'tests' => $tests,
            'summary' => $this->generate_summary($tests)
        ];
    }
    
    /**
     * Run all tests
     */
    private function run_all_tests() {
        return [
            'system' => $this->run_system_tests(),
            'forms' => $this->run_form_tests(),
            'api' => $this->run_api_tests(),
            'javascript' => $this->run_javascript_tests(),
            'security' => $this->run_security_tests(),
            'performance' => $this->run_performance_tests()
        ];
    }
    
    /**
     * Test database access
     */
    private function test_database_access() {
        global $wpdb;
        try {
            $result = $wpdb->get_var("SELECT 1");
            return $result == 1;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get memory limit in bytes
     */
    private function get_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
            $value = $matches[1];
            switch ($matches[2]) {
                case 'G':
                    $value *= 1024;
                case 'M':
                    $value *= 1024;
                case 'K':
                    $value *= 1024;
            }
            return $value;
        }
        return 134217728; // Default 128M
    }
    
    /**
     * Generate test summary
     */
    private function generate_summary($tests) {
        $summary = [
            'total' => count($tests),
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0
        ];
        
        foreach ($tests as $test) {
            switch ($test['status']) {
                case 'pass':
                    $summary['passed']++;
                    break;
                case 'fail':
                    $summary['failed']++;
                    break;
                case 'warning':
                    $summary['warnings']++;
                    break;
            }
        }
        
        return $summary;
    }
    
    /**
     * Export test results
     */
    public function export_test_results($results) {
        $export = [
            'plugin_version' => GF_JS_EMBED_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'test_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'results' => $results
        ];
        
        return json_encode($export, JSON_PRETTY_PRINT);
    }
}