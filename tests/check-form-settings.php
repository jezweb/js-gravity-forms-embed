<?php
/**
 * Check form settings for JavaScript Embed
 * 
 * Usage: Access this file directly in your browser to see the current form settings
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../../../../wp-load.php';

// Check if user is logged in and has permissions
if (!current_user_can('manage_options')) {
    wp_die('You need to be logged in as an administrator to view this page.');
}

// Get form ID from query parameter or default to 1
$form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 1;

// Get form settings
$settings = get_option('gf_js_embed_form_' . $form_id, []);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Settings - Gravity Forms JS Embed</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .api-key { font-family: monospace; background: #f0f0f0; padding: 4px; }
        .action-buttons { margin-top: 20px; }
        .button { padding: 10px 15px; margin-right: 10px; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Form Settings for Form ID: <?php echo $form_id; ?></h1>
        
        <?php if (empty($settings)): ?>
            <p class="error">No settings found for this form. The form may not have JavaScript embedding configured.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Embedding Enabled</td>
                    <td><?php echo $settings['enabled'] ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></td>
                </tr>
                <tr>
                    <td>API Key</td>
                    <td>
                        <?php if (!empty($settings['api_key'])): ?>
                            <span class="api-key"><?php echo esc_html($settings['api_key']); ?></span>
                            <br><small>This API key is required for all embed requests to this form.</small>
                        <?php else: ?>
                            <em>No API key set</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Allowed Domains</td>
                    <td>
                        <?php 
                        if (!empty($settings['allowed_domains']) && is_array($settings['allowed_domains'])) {
                            echo implode('<br>', array_map('esc_html', $settings['allowed_domains']));
                        } else {
                            echo '<em>No domain restrictions</em>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
        
        <div class="action-buttons">
            <h2>Actions</h2>
            
            <?php if (!empty($settings['api_key'])): ?>
                <h3>Option 1: Use the API Key</h3>
                <p>Include this API key in your embed code:</p>
                <pre>&lt;div data-gf-form="<?php echo $form_id; ?>" data-gf-api-key="<?php echo esc_html($settings['api_key']); ?>"&gt;&lt;/div&gt;
&lt;script src="<?php echo home_url('/gf-js-embed/v1/embed.js'); ?>"&gt;&lt;/script&gt;</pre>
                
                <h3>Option 2: Remove API Key Requirement (Development Only)</h3>
                <form method="post">
                    <?php wp_nonce_field('remove_api_key', 'remove_api_key_nonce'); ?>
                    <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                    <button type="submit" name="remove_api_key" class="button" onclick="return confirm('This will remove the API key requirement. Are you sure?');">
                        Remove API Key Requirement
                    </button>
                </form>
            <?php endif; ?>
            
            <h3>Option 3: Check Different Form</h3>
            <form method="get">
                <label>Form ID: <input type="number" name="form_id" value="<?php echo $form_id; ?>" min="1"></label>
                <button type="submit" class="button">Check Form</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
// Handle form submission to remove API key
if (isset($_POST['remove_api_key']) && wp_verify_nonce($_POST['remove_api_key_nonce'], 'remove_api_key')) {
    $form_id = intval($_POST['form_id']);
    $settings = get_option('gf_js_embed_form_' . $form_id, []);
    $settings['api_key'] = '';
    update_option('gf_js_embed_form_' . $form_id, $settings);
    
    // Redirect to refresh the page
    wp_redirect(add_query_arg('form_id', $form_id, $_SERVER['REQUEST_URI']));
    exit;
}
?>