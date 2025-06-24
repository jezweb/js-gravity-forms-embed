<?php
/**
 * API Key Test Script
 * 
 * Run this script to test API key functionality
 * Usage: php test-api-key.php
 */

// Load WordPress
$wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    die("Error: Could not find wp-load.php. Please adjust the path.\n");
}
require_once($wp_load_path);

// Colors for terminal output
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[0;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

echo "\n{$blue}=== Gravity Forms JS Embed API Key Test ==={$reset}\n\n";

// Check if plugin is active
if (!class_exists('GF_JS_Embed')) {
    die("{$red}Error: Gravity Forms JS Embed plugin is not active.{$reset}\n");
}

// Get test form ID
$form_id = 1; // Change this to your test form ID
if (isset($argv[1])) {
    $form_id = intval($argv[1]);
}

echo "Testing with form ID: {$form_id}\n\n";

// Test 1: Check form settings
echo "{$yellow}Test 1: Checking form settings...{$reset}\n";
$settings = GF_JS_Embed_Admin::get_form_settings($form_id);

if ($settings['enabled']) {
    echo "{$green}✓ Form embedding is enabled{$reset}\n";
} else {
    echo "{$red}✗ Form embedding is disabled{$reset}\n";
}

if (!empty($settings['api_key'])) {
    echo "{$green}✓ API key exists: {$settings['api_key']}{$reset}\n";
} else {
    echo "{$yellow}! No API key generated yet{$reset}\n";
}

echo "\n";

// Test 2: Generate API key
echo "{$yellow}Test 2: Testing API key generation...{$reset}\n";
$test_key = GF_JS_Embed_Security::generate_api_key();
echo "Generated test key: {$test_key}\n";

if (preg_match('/^gfjs_[a-f0-9]{32}$/', $test_key)) {
    echo "{$green}✓ API key format is correct{$reset}\n";
} else {
    echo "{$red}✗ API key format is incorrect{$reset}\n";
}

echo "\n";

// Test 3: API key validation
echo "{$yellow}Test 3: Testing API key validation...{$reset}\n";

if (!empty($settings['api_key'])) {
    // Test with correct key
    if (GF_JS_Embed_Security::validate_api_key($settings['api_key'], $form_id)) {
        echo "{$green}✓ Valid API key accepted{$reset}\n";
    } else {
        echo "{$red}✗ Valid API key rejected{$reset}\n";
    }
    
    // Test with incorrect key
    if (!GF_JS_Embed_Security::validate_api_key('gfjs_incorrect_key', $form_id)) {
        echo "{$green}✓ Invalid API key rejected{$reset}\n";
    } else {
        echo "{$red}✗ Invalid API key accepted{$reset}\n";
    }
} else {
    echo "{$yellow}! Skipping validation test - no API key set{$reset}\n";
}

echo "\n";

// Test 4: REST API endpoint
echo "{$yellow}Test 4: Testing REST API endpoint...{$reset}\n";

$rest_url = rest_url('gf-embed/v1/form/' . $form_id);
echo "REST endpoint: {$rest_url}\n";

// Test without API key
$response = wp_remote_get($rest_url);
if (!is_wp_error($response)) {
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $status = wp_remote_retrieve_response_code($response);
    
    if (!empty($settings['api_key'])) {
        if ($status === 401) {
            echo "{$green}✓ Request without API key correctly rejected (401){$reset}\n";
        } else {
            echo "{$red}✗ Request without API key was not rejected (status: {$status}){$reset}\n";
        }
    } else {
        if ($status === 200) {
            echo "{$green}✓ Request without API key accepted (no key required){$reset}\n";
        } else {
            echo "{$red}✗ Request failed (status: {$status}){$reset}\n";
        }
    }
}

// Test with API key
if (!empty($settings['api_key'])) {
    $response = wp_remote_get($rest_url, [
        'headers' => [
            'X-API-Key' => $settings['api_key']
        ]
    ]);
    
    if (!is_wp_error($response)) {
        $status = wp_remote_retrieve_response_code($response);
        if ($status === 200) {
            echo "{$green}✓ Request with valid API key accepted (200){$reset}\n";
        } else {
            echo "{$red}✗ Request with valid API key failed (status: {$status}){$reset}\n";
        }
    }
}

echo "\n";

// Test 5: Security features
echo "{$yellow}Test 5: Checking security features...{$reset}\n";

// Check rate limiting
$identifier = '127.0.0.1';
$rate_limit_ok = GF_JS_Embed_Security::check_rate_limit($identifier, 5, 60);
if ($rate_limit_ok) {
    echo "{$green}✓ Rate limiting is working{$reset}\n";
} else {
    echo "{$yellow}! Rate limit reached for testing{$reset}\n";
}

// Check domain validation
$test_domain = 'https://example.com';
$domain_allowed = GF_JS_Embed_Security::is_domain_allowed($test_domain, $form_id);
$allowed_domains = empty($settings['allowed_domains']) ? ['*'] : $settings['allowed_domains'];
echo "Allowed domains: " . implode(', ', $allowed_domains) . "\n";

echo "\n{$blue}=== Test Summary ==={$reset}\n";
echo "Form ID: {$form_id}\n";
echo "Embedding enabled: " . ($settings['enabled'] ? 'Yes' : 'No') . "\n";
echo "API key required: " . (!empty($settings['api_key']) ? 'Yes' : 'No') . "\n";
if (!empty($settings['api_key'])) {
    echo "API key: {$settings['api_key']}\n";
}
echo "\n";

// Show example embed codes
echo "{$blue}=== Example Embed Codes ==={$reset}\n\n";

if (!empty($settings['api_key'])) {
    echo "With API key:\n";
    echo "{$yellow}<div data-gf-form=\"{$form_id}\" data-gf-api-key=\"{$settings['api_key']}\"></div>\n";
    echo "<script src=\"" . home_url('/gf-js-embed/v1/embed.js') . "\"></script>{$reset}\n\n";
} else {
    echo "Without API key:\n";
    echo "{$yellow}<div data-gf-form=\"{$form_id}\"></div>\n";
    echo "<script src=\"" . home_url('/gf-js-embed/v1/embed.js') . "\"></script>{$reset}\n\n";
}

echo "Done!\n\n";