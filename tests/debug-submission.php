<?php
/**
 * Debug form submission issues
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../../../../wp-load.php';

// Check if user is logged in
if (!current_user_can('manage_options')) {
    wp_die('You need to be logged in as an administrator to view this page.');
}

$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 1;

// Load required classes
require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-security.php';
require_once GF_JS_EMBED_PLUGIN_DIR . 'includes/class-gf-js-embed-admin.php';

// Get form settings
$settings = GF_JS_Embed_Admin::get_form_settings($form_id);

// Simulate submission data
$test_data = [
    'input_1' => 'Test Name',
    'input_2' => 'test@example.com',
    'gf_embed_start_time' => time() - 10 // 10 seconds ago
];

// Set test environment
$_SERVER['HTTP_ORIGIN'] = 'http://localhost:10018';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Form Submission - Gravity Forms JS Embed</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; margin: 0 auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
        .pass { color: green; }
        .fail { color: red; }
        .warning { color: orange; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Debug Form Submission for Form <?php echo $form_id; ?></h1>
    
    <div class="section">
        <h2>Form Settings</h2>
        <pre><?php print_r($settings); ?></pre>
    </div>
    
    <div class="section">
        <h2>Security Checks</h2>
        
        <?php
        // Check domain
        $domain_allowed = GF_JS_Embed_Security::is_domain_allowed($_SERVER['HTTP_ORIGIN'], $form_id);
        echo '<p>Domain Check (' . $_SERVER['HTTP_ORIGIN'] . '): ';
        echo $domain_allowed ? '<span class="pass">✓ PASS</span>' : '<span class="fail">✗ FAIL</span>';
        echo '</p>';
        
        // Check rate limiting
        $rate_limit_ok = GF_JS_Embed_Security::check_advanced_rate_limit($_SERVER['REMOTE_ADDR'], $form_id);
        echo '<p>Rate Limit Check: ';
        echo $rate_limit_ok ? '<span class="pass">✓ PASS</span>' : '<span class="fail">✗ FAIL</span>';
        echo '</p>';
        
        // Check honeypot
        $honeypot_ok = GF_JS_Embed_Security::validate_honeypot($form_id, $test_data);
        echo '<p>Honeypot Check: ';
        echo $honeypot_ok ? '<span class="pass">✓ PASS</span>' : '<span class="fail">✗ FAIL</span>';
        echo '</p>';
        
        // Check bot detection
        $is_bot = GF_JS_Embed_Security::is_likely_bot($test_data);
        echo '<p>Bot Detection: ';
        echo !$is_bot ? '<span class="pass">✓ PASS (Not a bot)</span>' : '<span class="fail">✗ FAIL (Detected as bot)</span>';
        echo '</p>';
        
        // Run full security scan
        echo '<h3>Full Security Scan</h3>';
        $scan_result = GF_JS_Embed_Security::perform_security_scan($form_id, $test_data);
        ?>
        <pre><?php print_r($scan_result); ?></pre>
        
        <p>Overall Result: <?php echo $scan_result['passed'] ? '<span class="pass">✓ PASSED</span>' : '<span class="fail">✗ FAILED</span>'; ?></p>
    </div>
    
    <div class="section">
        <h2>Recommendations</h2>
        <?php if (!$domain_allowed): ?>
            <p class="warning">⚠️ Add <code>localhost:10018</code> to the allowed domains in the form settings.</p>
        <?php endif; ?>
        
        <?php if (!$rate_limit_ok): ?>
            <p class="warning">⚠️ Rate limit exceeded. Wait a few minutes or adjust rate limit settings.</p>
        <?php endif; ?>
        
        <?php if (!$scan_result['passed']): ?>
            <p class="warning">⚠️ Security scan failed with flags: <?php echo implode(', ', $scan_result['flags']); ?></p>
            <p>Risk score: <?php echo $scan_result['risk_score']; ?>/10 (threshold: 10)</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Test Different Form</h2>
        <form method="get">
            <label>Form ID: <input type="number" name="form_id" value="<?php echo $form_id; ?>" min="1"></label>
            <button type="submit">Test Form</button>
        </form>
    </div>
</body>
</html>