<?php
/**
 * Gravity Forms JS Embed - Local Test Harness
 * 
 * Run with: php test-harness.php
 */

// Set up environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define WordPress constants
define('ABSPATH', dirname(__DIR__) . '/');
define('HOUR_IN_SECONDS', 3600);
define('MINUTE_IN_SECONDS', 60);
define('WP_DEBUG', true);

// Color output helpers
$colors = [
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'magenta' => "\033[35m",
    'cyan' => "\033[36m",
    'reset' => "\033[0m"
];

function colorize($text, $color) {
    global $colors;
    return $colors[$color] . $text . $colors['reset'];
}

// Mock WordPress functions
$GLOBALS['wp_hooks'] = [];
$GLOBALS['wp_options'] = [];
$GLOBALS['wp_transients'] = [];

function add_action($tag, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['wp_hooks']['actions'][$tag][] = [
        'callback' => $callback,
        'priority' => $priority,
        'accepted_args' => $accepted_args
    ];
}

function add_filter($tag, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['wp_hooks']['filters'][$tag][] = [
        'callback' => $callback,
        'priority' => $priority,
        'accepted_args' => $accepted_args
    ];
}

function do_action($tag, ...$args) {
    if (isset($GLOBALS['wp_hooks']['actions'][$tag])) {
        foreach ($GLOBALS['wp_hooks']['actions'][$tag] as $action) {
            call_user_func_array($action['callback'], $args);
        }
    }
}

function apply_filters($tag, $value, ...$args) {
    if (isset($GLOBALS['wp_hooks']['filters'][$tag])) {
        foreach ($GLOBALS['wp_hooks']['filters'][$tag] as $filter) {
            $value = call_user_func_array($filter['callback'], array_merge([$value], $args));
        }
    }
    return $value;
}

// WordPress option functions
function get_option($option, $default = false) {
    return $GLOBALS['wp_options'][$option] ?? $default;
}

function update_option($option, $value) {
    $GLOBALS['wp_options'][$option] = $value;
    return true;
}

function delete_option($option) {
    unset($GLOBALS['wp_options'][$option]);
    return true;
}

// WordPress transient functions
function get_transient($transient) {
    if (isset($GLOBALS['wp_transients'][$transient])) {
        $data = $GLOBALS['wp_transients'][$transient];
        if ($data['expiration'] > time()) {
            return $data['value'];
        }
        unset($GLOBALS['wp_transients'][$transient]);
    }
    return false;
}

function set_transient($transient, $value, $expiration = 0) {
    $GLOBALS['wp_transients'][$transient] = [
        'value' => $value,
        'expiration' => time() + $expiration
    ];
    return true;
}

function delete_transient($transient) {
    unset($GLOBALS['wp_transients'][$transient]);
    return true;
}

// WordPress utility functions
function __($text, $domain = '') { return $text; }
function _e($text, $domain = '') { echo $text; }
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_js($text) { return str_replace(["'", '"', "\n", "\r"], ["\'", '\"', '\n', '\r'], $text); }
function esc_url($url) { return filter_var($url, FILTER_SANITIZE_URL); }
function esc_textarea($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function sanitize_text_field($str) { return filter_var($str, FILTER_SANITIZE_STRING); }
function sanitize_textarea_field($str) { return filter_var($str, FILTER_SANITIZE_STRING); }
function sanitize_email($email) { return filter_var($email, FILTER_SANITIZE_EMAIL); }
function wp_parse_args($args, $defaults = []) { return array_merge($defaults, $args); }

function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    if ($special_chars) {
        $chars .= '!@#$%^&*()';
    }
    if ($extra_special_chars) {
        $chars .= '-_ []{}<>~`+=,.;:/?|';
    }
    
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function wp_generate_uuid4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function wp_create_nonce($action = -1) {
    return substr(md5($action . time()), 0, 10);
}

function wp_verify_nonce($nonce, $action = -1) {
    return true; // Simplified for testing
}

function wp_kses_post($content) {
    return strip_tags($content, '<p><br><strong><em><a><ul><ol><li><blockquote><h1><h2><h3><h4><h5><h6>');
}

function wp_die($message = '', $title = '', $args = []) {
    echo colorize("\n💀 wp_die(): $message\n", 'red');
    exit(1);
}

function checked($checked, $current = true, $echo = true) {
    $result = $checked == $current ? ' checked="checked"' : '';
    if ($echo) echo $result;
    return $result;
}

function selected($selected, $current = true, $echo = true) {
    $result = $selected == $current ? ' selected="selected"' : '';
    if ($echo) echo $result;
    return $result;
}

// Mock Gravity Forms classes
class GFAPI {
    private static $forms = [];
    
    public static function get_form($form_id) {
        return self::$forms[$form_id] ?? null;
    }
    
    public static function form_exists($form_id) {
        return isset(self::$forms[$form_id]);
    }
    
    public static function add_form($form) {
        self::$forms[$form['id']] = $form;
        return $form['id'];
    }
    
    public static function get_forms() {
        return array_values(self::$forms);
    }
}

class GFFormDisplay {
    public static function validate($form, $values) {
        // Simple validation mock
        $is_valid = true;
        $failed_validation_page = [];
        
        foreach ($form['fields'] as $field) {
            if ($field->isRequired && empty($values['input_' . $field->id])) {
                $is_valid = false;
                $failed_validation_page[$field->id] = true;
            }
        }
        
        return [
            'is_valid' => $is_valid,
            'failed_validation_page' => $failed_validation_page
        ];
    }
    
    public static function get_confirmation($form, $entry) {
        return [
            'type' => 'message',
            'message' => 'Thank you for your submission!'
        ];
    }
}

class GFCommon {
    public static function get_currency() {
        return 'USD';
    }
    
    public static function add_message($message) {
        echo colorize("📢 $message\n", 'green');
    }
}

// Mock field object
class GF_Field {
    public $id;
    public $type;
    public $label;
    public $isRequired = false;
    public $placeholder = '';
    
    public function __construct($id, $type, $label, $required = false) {
        $this->id = $id;
        $this->type = $type;
        $this->label = $label;
        $this->isRequired = $required;
    }
}

// Start of test harness
echo colorize("\n🧪 Gravity Forms JS Embed - Test Harness\n", 'cyan');
echo str_repeat("=", 50) . "\n\n";

// Load plugin files
$includes_dir = dirname(__DIR__) . '/includes/';
$files_to_load = [
    'class-gf-js-embed-security.php',
    'class-gf-js-embed-analytics.php',
    'class-gf-js-embed-styling.php',
    'class-gf-js-embed-i18n.php'
];

foreach ($files_to_load as $file) {
    $file_path = $includes_dir . $file;
    if (file_exists($file_path)) {
        echo colorize("✓ Loading: $file\n", 'green');
        require_once $file_path;
    } else {
        echo colorize("✗ Missing: $file\n", 'red');
    }
}

echo "\n";

// Interactive test menu
function show_menu() {
    echo colorize("\n📋 Test Menu:\n", 'yellow');
    echo "1. Test Security Features\n";
    echo "2. Test Analytics Tracking\n";
    echo "3. Test CSS Generation\n";
    echo "4. Test Internationalization\n";
    echo "5. Run All Tests\n";
    echo "6. Interactive Shell\n";
    echo "0. Exit\n";
    echo "\nChoice: ";
}

// Test functions
function test_security() {
    echo colorize("\n🔒 Testing Security Features\n", 'blue');
    echo str_repeat("-", 40) . "\n";
    
    // Test API key generation
    echo "\n1. API Key Generation:\n";
    $api_key = GF_JS_Embed_Security::generate_api_key();
    echo "   Generated: " . colorize($api_key, 'green') . "\n";
    
    // Test domain validation
    echo "\n2. Domain Validation:\n";
    $test_domains = [
        'https://example.com' => true,
        'http://localhost:3000' => true,
        'https://evil.com' => false
    ];
    
    // Set up allowed domains
    update_option('gf_js_embed_form_1', [
        'allowed_domains' => ['example.com', '*.localhost']
    ]);
    
    foreach ($test_domains as $domain => $expected) {
        $allowed = GF_JS_Embed_Security::is_domain_allowed($domain, 1);
        $status = $allowed ? '✓ Allowed' : '✗ Blocked';
        $color = $allowed === $expected ? 'green' : 'red';
        echo "   $domain: " . colorize($status, $color) . "\n";
    }
    
    // Test honeypot generation
    echo "\n3. Honeypot Field:\n";
    $honeypot = GF_JS_Embed_Security::generate_honeypot_field(1);
    echo "   Field name: " . colorize($honeypot['name'], 'green') . "\n";
    echo "   HTML: " . colorize(substr($honeypot['html'], 0, 50) . '...', 'gray') . "\n";
    
    // Test rate limiting
    echo "\n4. Rate Limiting:\n";
    $ip = '192.168.1.1';
    for ($i = 1; $i <= 5; $i++) {
        $allowed = GF_JS_Embed_Security::check_advanced_rate_limit($ip, 1);
        $status = $allowed ? '✓ Allowed' : '✗ Blocked';
        $color = $allowed ? 'green' : 'red';
        echo "   Request $i: " . colorize($status, $color) . "\n";
    }
    
    // Test suspicious activity detection
    echo "\n5. Spam Detection:\n";
    $test_data = [
        'normal' => ['input_1' => 'John Doe', 'input_2' => 'john@example.com'],
        'spam' => ['input_1' => 'Buy VIAGRA now!!!', 'input_2' => 'casino@spam.com'],
        'script' => ['input_1' => '<script>alert("xss")</script>', 'input_2' => 'test@test.com']
    ];
    
    foreach ($test_data as $type => $data) {
        $flags = GF_JS_Embed_Security::detect_suspicious_activity($data);
        $status = empty($flags) ? '✓ Clean' : '✗ Suspicious: ' . implode(', ', $flags);
        $color = empty($flags) ? 'green' : 'red';
        echo "   $type data: " . colorize($status, $color) . "\n";
    }
}

function test_analytics() {
    echo colorize("\n📊 Testing Analytics\n", 'blue');
    echo str_repeat("-", 40) . "\n";
    
    // Track some views
    echo "\n1. Tracking Views:\n";
    for ($i = 1; $i <= 3; $i++) {
        GF_JS_Embed_Analytics::track_view(1, "domain$i.com");
        echo "   Tracked view from domain$i.com\n";
    }
    
    // Track submissions
    echo "\n2. Tracking Submissions:\n";
    GF_JS_Embed_Analytics::track_submission(1, "domain1.com");
    echo "   Tracked submission from domain1.com\n";
    
    // Get analytics
    echo "\n3. Analytics Summary:\n";
    $analytics = GF_JS_Embed_Analytics::get_form_analytics(1);
    echo "   Total Views: " . colorize($analytics['total_views'], 'green') . "\n";
    echo "   Total Submissions: " . colorize($analytics['total_submissions'], 'green') . "\n";
    echo "   Conversion Rate: " . colorize($analytics['conversion_rate'] . '%', 'yellow') . "\n";
    echo "   Active Domains: " . colorize(count($analytics['domains']), 'cyan') . "\n";
}

function test_styling() {
    echo colorize("\n🎨 Testing Styling\n", 'blue');
    echo str_repeat("-", 40) . "\n";
    
    $settings = [
        'theme' => 'minimal',
        'custom_css' => '.gf-button { background: red; }'
    ];
    
    echo "\n1. Generating CSS for theme: " . colorize($settings['theme'], 'yellow') . "\n";
    $css = GF_JS_Embed_Styling::get_form_css(1, $settings);
    
    // Show first few lines of CSS
    $lines = explode("\n", $css);
    foreach (array_slice($lines, 0, 5) as $line) {
        if (trim($line)) {
            echo "   " . trim($line) . "\n";
        }
    }
    echo "   ... (" . strlen($css) . " bytes total)\n";
}

function test_i18n() {
    echo colorize("\n🌐 Testing Internationalization\n", 'blue');
    echo str_repeat("-", 40) . "\n";
    
    echo "\n1. Getting Translations:\n";
    $translations = GF_JS_Embed_i18n::get_translations();
    
    $sample_keys = ['loading', 'submit', 'required', 'invalid_email'];
    foreach ($sample_keys as $key) {
        if (isset($translations[$key])) {
            echo "   $key: " . colorize($translations[$key], 'green') . "\n";
        }
    }
    
    echo "\n2. Date Format Conversion:\n";
    $php_format = 'Y-m-d';
    $js_format = GF_JS_Embed_i18n::get_js_date_format($php_format);
    echo "   PHP format: $php_format\n";
    echo "   JS format: " . colorize($js_format, 'green') . "\n";
}

function run_all_tests() {
    test_security();
    test_analytics();
    test_styling();
    test_i18n();
}

function interactive_shell() {
    echo colorize("\n🐚 Interactive PHP Shell\n", 'magenta');
    echo "Type 'exit' to return to menu\n\n";
    
    while (true) {
        echo colorize("php> ", 'cyan');
        $input = trim(fgets(STDIN));
        
        if ($input === 'exit') {
            break;
        }
        
        try {
            eval($input);
            echo "\n";
        } catch (Exception $e) {
            echo colorize("Error: " . $e->getMessage() . "\n", 'red');
        }
    }
}

// Main loop
if (php_sapi_name() === 'cli') {
    while (true) {
        show_menu();
        $choice = trim(fgets(STDIN));
        
        switch ($choice) {
            case '1':
                test_security();
                break;
            case '2':
                test_analytics();
                break;
            case '3':
                test_styling();
                break;
            case '4':
                test_i18n();
                break;
            case '5':
                run_all_tests();
                break;
            case '6':
                interactive_shell();
                break;
            case '0':
                echo colorize("\n👋 Goodbye!\n", 'green');
                exit(0);
            default:
                echo colorize("\n❌ Invalid choice\n", 'red');
        }
    }
} else {
    echo colorize("This script should be run from the command line\n", 'red');
}