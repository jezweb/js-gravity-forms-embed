# Developer Hooks Reference

This document provides a comprehensive reference for all hooks and filters available in the Gravity Forms JavaScript Embed plugin.

## Table of Contents

- [WordPress Core Hooks](#wordpress-core-hooks)
- [Gravity Forms Integration Hooks](#gravity-forms-integration-hooks)
- [Plugin-Specific Filters](#plugin-specific-filters)
- [Security Hooks](#security-hooks)
- [API Extension Points](#api-extension-points)
- [Usage Examples](#usage-examples)

## WordPress Core Hooks

### Actions

#### `plugins_loaded`
Called when all plugins have been loaded. Used to check requirements.

**Location:** `gravity-forms-js-embed.php:29`

```php
add_action('plugins_loaded', 'gf_js_embed_check_requirements');
```

#### `admin_notices`
Used to display admin notices when dependencies are missing.

**Location:** `gravity-forms-js-embed.php:33`

```php
add_action('admin_notices', 'gf_js_embed_missing_gf_notice');
```

#### `init`
Initialize plugin components and register rewrite rules.

**Location:** `includes/class-gf-js-embed.php:37`

```php
add_action('init', [$this, 'init']);
```

#### `template_redirect`
Serve the JavaScript embed script.

**Location:** `includes/class-gf-js-embed.php:68`

```php
add_action('template_redirect', [$this, 'serve_embed_script']);
```

#### `admin_menu`
Add plugin admin menu pages.

**Location:** `includes/class-gf-js-embed-admin.php:42`

```php
add_action('admin_menu', [$this, 'add_admin_menu'], 25);
```

#### `admin_enqueue_scripts`
Enqueue admin scripts and styles.

**Location:** `includes/class-gf-js-embed-admin.php:39`

```php
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
```

#### `rest_api_init`
Register REST API endpoints.

**Location:** `includes/class-gf-js-embed-api.php:30`

```php
add_action('rest_api_init', [$this, 'register_rest_routes']);
```

### Filters

#### `plugin_action_links_*`
Add action links to the plugin on the plugins page.

**Location:** `includes/class-gf-js-embed.php:40`

```php
add_filter('plugin_action_links_' . plugin_basename(GF_JS_EMBED_PLUGIN_FILE), [$this, 'add_plugin_action_links']);
```

#### `plugin_row_meta`
Add meta links to the plugin row.

**Location:** `includes/class-gf-js-embed.php:41`

```php
add_filter('plugin_row_meta', [$this, 'add_plugin_row_meta'], 10, 2);
```

#### `query_vars`
Add custom query variables for the embed script endpoint.

**Location:** `includes/class-gf-js-embed.php:63`

```php
add_filter('query_vars', function($vars) {
    $vars[] = 'gf_js_embed';
    return $vars;
});
```

## Gravity Forms Integration Hooks

### Actions

#### `gform_form_settings_page_gf_js_embed`
Display the form settings page for JavaScript embedding.

**Location:** `includes/class-gf-js-embed-admin.php:32`

```php
add_action('gform_form_settings_page_gf_js_embed', [$this, 'form_settings_page']);
```

### Filters

#### `gform_form_settings_menu`
Add JavaScript Embed to the form settings menu.

**Location:** `includes/class-gf-js-embed-admin.php:31`

```php
add_action('gform_form_settings_menu', [$this, 'add_form_settings_menu'], 10, 2);
```

#### `gform_form_settings_save_gf_js_embed`
Save form settings when the form is saved (dynamic hook).

**Location:** `includes/class-gf-js-embed-admin.php:35`

```php
add_filter('gform_form_settings_save_gf_js_embed', [$this, 'save_form_settings'], 10, 2);
```

#### `gform_pre_form_settings_save`
Fallback save method for form settings.

**Location:** `includes/class-gf-js-embed-admin.php:36`

```php
add_filter('gform_pre_form_settings_save', [$this, 'save_form_settings_fallback']);
```

## Plugin-Specific Filters

The plugin provides several filters for customization:

### `gf_js_embed_form_data`
Filter form data before sending to API.

**Parameters:**
- `$form_data` (array): The prepared form data
- `$form` (array): The original Gravity Forms form object
- `$settings` (array): Form embed settings

**Example:**
```php
add_filter('gf_js_embed_form_data', function($form_data, $form, $settings) {
    // Modify form data before API response
    $form_data['custom_field'] = 'custom_value';
    return $form_data;
}, 10, 3);
```

### `gf_js_embed_security_settings`
Filter security settings for forms.

**Parameters:**
- `$security_settings` (array): Current security settings
- `$form_id` (int): The form ID

**Example:**
```php
add_filter('gf_js_embed_security_settings', function($security_settings, $form_id) {
    // Add custom security rules
    $security_settings['custom_check'] = true;
    return $security_settings;
}, 10, 2);
```

### `gf_js_embed_allowed_domains`
Filter allowed domains for CORS.

**Parameters:**
- `$domains` (array): Array of allowed domains
- `$form_id` (int): The form ID

**Example:**
```php
add_filter('gf_js_embed_allowed_domains', function($domains, $form_id) {
    // Add additional allowed domains
    $domains[] = 'example.com';
    return $domains;
}, 10, 2);
```

## Security Hooks

### Actions

#### `gf_js_embed_security_violation`
Triggered when a security violation is detected.

**Parameters:**
- `$violation_type` (string): Type of violation
- `$details` (array): Violation details
- `$form_id` (int): Form ID if applicable

**Example:**
```php
add_action('gf_js_embed_security_violation', function($violation_type, $details, $form_id) {
    // Log security violations
    error_log("JS Embed Security Violation: {$violation_type} for form {$form_id}");
}, 10, 3);
```

### Filters

#### `gf_js_embed_rate_limit`
Filter rate limiting settings.

**Parameters:**
- `$limits` (array): Rate limit configuration
- `$identifier` (string): Client identifier (IP address)
- `$form_id` (int): Form ID

**Example:**
```php
add_filter('gf_js_embed_rate_limit', function($limits, $identifier, $form_id) {
    // Custom rate limiting logic
    if ($identifier === '192.168.1.100') {
        $limits['requests_per_minute'] = 100; // Higher limit for trusted IP
    }
    return $limits;
}, 10, 3);
```

## API Extension Points

### REST API Hooks

The plugin provides hooks for extending the REST API:

#### `gf_js_embed_api_response`
Filter API responses before sending.

**Parameters:**
- `$response` (array): The API response data
- `$endpoint` (string): The endpoint being called
- `$request` (WP_REST_Request): The request object

**Example:**
```php
add_filter('gf_js_embed_api_response', function($response, $endpoint, $request) {
    if ($endpoint === 'form') {
        $response['custom_data'] = 'additional_info';
    }
    return $response;
}, 10, 3);
```

#### `gf_js_embed_submission_data`
Filter form submission data before processing.

**Parameters:**
- `$submission_data` (array): The form submission data
- `$form` (array): The form object
- `$request` (WP_REST_Request): The request object

**Example:**
```php
add_filter('gf_js_embed_submission_data', function($submission_data, $form, $request) {
    // Add timestamp to submissions
    $submission_data['submitted_at'] = current_time('mysql');
    return $submission_data;
}, 10, 3);
```

## Usage Examples

### Example 1: Custom Form Validation

```php
// Add custom validation to embedded forms
add_filter('gf_js_embed_submission_data', function($submission_data, $form, $request) {
    // Custom validation logic
    if (empty($submission_data['input_1']) || strlen($submission_data['input_1']) < 5) {
        wp_die('Custom validation failed', 'Validation Error', ['response' => 400]);
    }
    return $submission_data;
}, 10, 3);
```

### Example 2: Enhanced Security Logging

```php
// Log all security events to custom table
add_action('gf_js_embed_security_violation', function($violation_type, $details, $form_id) {
    global $wpdb;
    
    $wpdb->insert(
        $wpdb->prefix . 'gf_embed_security_log',
        [
            'violation_type' => $violation_type,
            'form_id' => $form_id,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'timestamp' => current_time('mysql')
        ]
    );
}, 10, 3);
```

### Example 3: Dynamic Domain Allowlist

```php
// Dynamically allow domains based on user role
add_filter('gf_js_embed_allowed_domains', function($domains, $form_id) {
    $current_user = wp_get_current_user();
    
    if (in_array('administrator', $current_user->roles)) {
        // Administrators can embed on any domain
        $domains[] = '*';
    }
    
    return $domains;
}, 10, 2);
```

### Example 4: Custom Form Data Enhancement

```php
// Add custom fields to form data
add_filter('gf_js_embed_form_data', function($form_data, $form, $settings) {
    // Add server timestamp
    $form_data['server_time'] = time();
    
    // Add custom branding
    $form_data['branding'] = [
        'logo' => get_option('site_logo'),
        'colors' => get_option('brand_colors')
    ];
    
    return $form_data;
}, 10, 3);
```

## Hook Priority Guidelines

- Use priority 10 (default) for most customizations
- Use priority 5 for hooks that should run early
- Use priority 15 or higher for hooks that depend on other plugins
- Use priority 999 for hooks that should run last

## Best Practices

1. **Always check if functions exist** before using them:
   ```php
   if (function_exists('gf_js_embed_get_settings')) {
       // Your code here
   }
   ```

2. **Use appropriate hook priorities** to ensure proper execution order

3. **Validate and sanitize data** in your hook callbacks

4. **Handle errors gracefully** and provide meaningful error messages

5. **Document your customizations** for future maintenance

## Security Considerations

- Always validate and sanitize user input in hook callbacks
- Check user capabilities before performing privileged operations
- Use nonces for form submissions and AJAX requests
- Be cautious when allowing unrestricted domain access
- Log security events for monitoring and debugging

For more information on extending the plugin, see the [API Documentation](../api/README.md) and [Security Guidelines](../security.md).