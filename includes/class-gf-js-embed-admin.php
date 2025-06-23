<?php
/**
 * Admin interface class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Admin {
    
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
        // Add form settings
        add_action('gform_form_settings_menu', [$this, 'add_form_settings_menu'], 10, 2);
        add_action('gform_form_settings_page_gf_js_embed', [$this, 'form_settings_page']);
        
        // Hook into multiple save points for compatibility
        add_filter('gform_form_settings_save_gf_js_embed', [$this, 'save_form_settings'], 10, 2); // Dynamic hook
        add_filter('gform_pre_form_settings_save', [$this, 'save_form_settings_fallback']); // Documented hook
        
        // Add scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu'], 25);
    }
    
    /**
     * Add form settings menu item
     */
    public function add_form_settings_menu($menu_items, $form_id) {
        $menu_items[] = [
            'name' => 'gf_js_embed',
            'label' => __('JavaScript Embed', 'gf-js-embed'),
            'icon' => 'dashicons-embed-generic',
            'capability' => 'gravityforms_edit_forms'
        ];
        return $menu_items;
    }
    
    /**
     * Form settings page
     */
    public function form_settings_page() {
        // Get the id of the page view(form ID) and the $form object
        $form_id = rgget('id');

        $form = GFAPI::get_form( $form_id );

        // Check if GFFormSettings class exists
        if (!class_exists('GFFormSettings')) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Gravity Forms settings class not available.', 'gf-js-embed'); ?></p>
            </div>
            <?php
            return;
        }
        
        // Handle form submission if save hook doesn't fire
        if (isset($_POST['gf_js_embed_nonce']) && wp_verify_nonce($_POST['gf_js_embed_nonce'], 'gf_js_embed_save_settings')) {
            // Check if the dynamic save hook was already called by checking a flag
            if (!defined('GF_JS_EMBED_SETTINGS_SAVED')) {
                $this->save_form_settings([], $form);
                // Commented this out since it gives off duplicate constant on update/save
                // define('GF_JS_EMBED_SETTINGS_SAVED', true);
            }
        }
        
        // Output page header
        GFFormSettings::page_header();
        
        // Ensure Gravity Forms is available
        if (!class_exists('GFCommon')) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Gravity Forms is not available.', 'gf-js-embed'); ?></p>
            </div>
            <?php
            GFFormSettings::page_footer();
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('gravityforms_edit_forms') && !current_user_can('manage_options')) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('You do not have permission to access this page.', 'gf-js-embed'); ?></p>
            </div>
            <?php
            GFFormSettings::page_footer();
            return;
        }
        
        // Ensure we have a valid form
        if (!$form || !isset($form['id'])) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Invalid form specified.', 'gf-js-embed'); ?></p>
            </div>
            <?php
            GFFormSettings::page_footer();
            return;
        }
        
        $settings = self::get_form_settings($form['id']);
        ?>
        
        <form method="post">
            <?php wp_nonce_field('gf_js_embed_save_settings', 'gf_js_embed_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="js_embed_enabled"><?php _e('Enable JavaScript Embedding', 'gf-js-embed'); ?></label></th>
                    <td>
                        <input type="checkbox" name="js_embed_enabled" id="js_embed_enabled" 
                               value="1" <?php checked($settings['enabled'], true); ?>>
                        <label for="js_embed_enabled"><?php _e('Allow this form to be embedded on external sites', 'gf-js-embed'); ?></label>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="js_embed_title"><?php _e('Display Options', 'gf-js-embed'); ?></label></th>
                    <td>
                        <input type="checkbox" name="js_embed_title" id="js_embed_title" 
                               value="1" <?php checked($settings['display_title'], true); ?>>
                        <label for="js_embed_title"><?php _e('Display form title', 'gf-js-embed'); ?></label>
                        <br><br>
                        
                        <input type="checkbox" name="js_embed_description" id="js_embed_description" 
                               value="1" <?php checked($settings['display_description'], true); ?>>
                        <label for="js_embed_description"><?php _e('Display form description', 'gf-js-embed'); ?></label>
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php _e('Allowed Domains', 'gf-js-embed'); ?></label></th>
                    <td>
                        <textarea name="js_embed_domains" rows="5" cols="50" class="large-text"><?php 
                            echo esc_textarea(implode("\n", $settings['allowed_domains'])); 
                        ?></textarea>
                        <p class="description"><?php _e('Enter one domain per line. Use * to allow all domains. Examples: https://example.com, *.example.com', 'gf-js-embed'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="js_embed_theme"><?php _e('Theme', 'gf-js-embed'); ?></label></th>
                    <td>
                        <select name="js_embed_theme" id="js_embed_theme">
                            <option value=""><?php _e('Default', 'gf-js-embed'); ?></option>
                            <option value="minimal" <?php selected($settings['theme'], 'minimal'); ?>><?php _e('Minimal', 'gf-js-embed'); ?></option>
                            <option value="rounded" <?php selected($settings['theme'], 'rounded'); ?>><?php _e('Rounded', 'gf-js-embed'); ?></option>
                            <option value="material" <?php selected($settings['theme'], 'material'); ?>><?php _e('Material', 'gf-js-embed'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="js_embed_custom_css"><?php _e('Custom CSS', 'gf-js-embed'); ?></label></th>
                    <td>
                        <textarea name="js_embed_custom_css" id="js_embed_custom_css" rows="10" cols="50" class="large-text code"><?php 
                            echo esc_textarea($settings['custom_css']); 
                        ?></textarea>
                        <p class="description"><?php _e('Add custom CSS to style the embedded form. Will be scoped to the form container.', 'gf-js-embed'); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Security Settings', 'gf-js-embed'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="js_embed_rate_limit"><?php _e('Rate Limiting', 'gf-js-embed'); ?></label></th>
                    <td>
                        <input type="number" name="js_embed_rate_limit" id="js_embed_rate_limit" 
                               value="<?php echo esc_attr($settings['rate_limit']); ?>" min="1" max="1000" />
                        <label for="js_embed_rate_limit"><?php _e('requests per hour', 'gf-js-embed'); ?></label>
                        <p class="description"><?php _e('Maximum number of requests allowed per IP address per hour.', 'gf-js-embed'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php _e('Security Features', 'gf-js-embed'); ?></label></th>
                    <td>
                        <input type="checkbox" name="js_embed_honeypot" id="js_embed_honeypot" 
                               value="1" <?php checked($settings['honeypot_enabled'], true); ?>>
                        <label for="js_embed_honeypot"><?php _e('Enable honeypot protection (bot detection)', 'gf-js-embed'); ?></label>
                        <br><br>
                        
                        <input type="checkbox" name="js_embed_csrf" id="js_embed_csrf" 
                               value="1" <?php checked($settings['csrf_enabled'], true); ?>>
                        <label for="js_embed_csrf"><?php _e('Enable CSRF token protection', 'gf-js-embed'); ?></label>
                        <br><br>
                        
                        <input type="checkbox" name="js_embed_spam_detection" id="js_embed_spam_detection" 
                               value="1" <?php checked($settings['spam_detection'], true); ?>>
                        <label for="js_embed_spam_detection"><?php _e('Enable spam detection patterns', 'gf-js-embed'); ?></label>
                        <br><br>
                        
                        <input type="checkbox" name="js_embed_bot_detection" id="js_embed_bot_detection" 
                               value="1" <?php checked($settings['bot_detection'], true); ?>>
                        <label for="js_embed_bot_detection"><?php _e('Enable automated bot detection', 'gf-js-embed'); ?></label>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="js_embed_security_level"><?php _e('Security Level', 'gf-js-embed'); ?></label></th>
                    <td>
                        <select name="js_embed_security_level" id="js_embed_security_level">
                            <option value="low" <?php selected($settings['security_level'], 'low'); ?>><?php _e('Low - Basic protection', 'gf-js-embed'); ?></option>
                            <option value="medium" <?php selected($settings['security_level'], 'medium'); ?>><?php _e('Medium - Recommended', 'gf-js-embed'); ?></option>
                            <option value="high" <?php selected($settings['security_level'], 'high'); ?>><?php _e('High - Maximum security', 'gf-js-embed'); ?></option>
                        </select>
                        <p class="description"><?php _e('Higher security levels may block more legitimate submissions but provide better protection.', 'gf-js-embed'); ?></p>
                    </td>
                </tr>
                
                <?php if ($settings['api_key']) : ?>
                <tr>
                    <th><label><?php _e('API Key', 'gf-js-embed'); ?></label></th>
                    <td>
                        <code><?php echo esc_html($settings['api_key']); ?></code>
                        <button type="submit" name="regenerate_api_key" class="button" onclick="return confirm('<?php esc_attr_e('Are you sure you want to regenerate the API key?', 'gf-js-embed'); ?>');">
                            <?php _e('Regenerate', 'gf-js-embed'); ?>
                        </button>
                        <p class="description"><?php _e('Use this API key for secure access to the form.', 'gf-js-embed'); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            
            <?php submit_button(__('Save Settings', 'gf-js-embed')); ?>
        </form>
        
        <?php if ($settings['enabled']) : ?>
        <hr>
        
        <h3><?php _e('Embed Code', 'gf-js-embed'); ?></h3>
        <p><?php _e('Copy and paste this code on any website where you want to display this form:', 'gf-js-embed'); ?></p>
        
        <div class="gf-embed-code-section">
            <h4><?php _e('Method 1: Simple Embed', 'gf-js-embed'); ?></h4>
            <textarea readonly class="large-text code" rows="3" onclick="this.select();"><!-- Gravity Forms JavaScript Embed -->
<div id="gf-form-<?php echo $form['id']; ?>"></div>
<script src="<?php echo home_url('/gf-js-embed/v1/embed.js?form=' . $form['id']); ?>"></script></textarea>
        </div>
        
        <div class="gf-embed-code-section">
            <h4><?php _e('Method 2: Data Attribute', 'gf-js-embed'); ?></h4>
            <textarea readonly class="large-text code" rows="3" onclick="this.select();"><!-- Gravity Forms JavaScript Embed -->
<div data-gf-form="<?php echo $form['id']; ?>"></div>
<script src="<?php echo home_url('/gf-js-embed/v1/embed.js'); ?>"></script></textarea>
        </div>
        
        <?php if ($settings['api_key']) : ?>
        <div class="gf-embed-code-section">
            <h4><?php _e('Method 3: With API Key', 'gf-js-embed'); ?></h4>
            <textarea readonly class="large-text code" rows="3" onclick="this.select();"><!-- Gravity Forms JavaScript Embed with API Key -->
<div data-gf-form="<?php echo $form['id']; ?>" data-gf-api-key="<?php echo esc_attr($settings['api_key']); ?>"></div>
<script src="<?php echo home_url('/gf-js-embed/v1/embed.js'); ?>"></script></textarea>
        </div>
        <?php endif; ?>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=gf_js_embed_analytics&form_id=' . $form['id']); ?>" class="button">
                <?php _e('View Analytics', 'gf-js-embed'); ?>
            </a>
        </p>
        <?php endif; ?>
        
        <style>
            .gf-embed-code-section {
                margin: 20px 0;
            }
            .gf-embed-code-section h4 {
                margin-bottom: 10px;
            }
            .gf-embed-code-section textarea {
                font-family: Consolas, Monaco, monospace;
                font-size: 13px;
                background: #f5f5f5;
            }
        </style>
        <?php
        
        // Output page footer
        GFFormSettings::page_footer();
    }
    
    /**
     * Save form settings
     */
    public function save_form_settings($settings, $form) {
        // Set flag to prevent duplicate saves
        if (!defined('GF_JS_EMBED_SETTINGS_SAVED')) {
            define('GF_JS_EMBED_SETTINGS_SAVED', true);
        }
        
        if (!isset($_POST['gf_js_embed_nonce']) || !wp_verify_nonce($_POST['gf_js_embed_nonce'], 'gf_js_embed_save_settings')) {
            return $settings;
        }
        
        $embed_settings = [
            'enabled' => !empty($_POST['js_embed_enabled']),
            'display_title' => !empty($_POST['js_embed_title']),
            'display_description' => !empty($_POST['js_embed_description']),
            'allowed_domains' => array_filter(array_map('trim', 
                explode("\n", $_POST['js_embed_domains'] ?? '')
            )),
            'theme' => sanitize_text_field($_POST['js_embed_theme'] ?? ''),
            'custom_css' => sanitize_textarea_field($_POST['js_embed_custom_css'] ?? ''),
            'rate_limit' => max(1, intval($_POST['js_embed_rate_limit'] ?? 60)),
            'honeypot_enabled' => !empty($_POST['js_embed_honeypot']),
            'csrf_enabled' => !empty($_POST['js_embed_csrf']),
            'spam_detection' => !empty($_POST['js_embed_spam_detection']),
            'bot_detection' => !empty($_POST['js_embed_bot_detection']),
            'security_level' => sanitize_text_field($_POST['js_embed_security_level'] ?? 'medium')
        ];
        
        // Get existing settings to preserve API key
        $existing = self::get_form_settings($form['id']);
        $embed_settings['api_key'] = $existing['api_key'];
        
        // Handle API key regeneration
        if (isset($_POST['regenerate_api_key'])) {
            $embed_settings['api_key'] = GF_JS_Embed_Security::generate_api_key();
        }
        
        // Generate API key if enabled and no key exists
        if ($embed_settings['enabled'] && empty($embed_settings['api_key'])) {
            $embed_settings['api_key'] = GF_JS_Embed_Security::generate_api_key();
        }
        
        // Save settings
        update_option('gf_js_embed_form_' . $form['id'], $embed_settings);
        
        // Show success message
        GFCommon::add_message(__('Settings saved successfully.', 'gf-js-embed'));
        
        return $settings;
    }
    
    /**
     * Fallback save method using documented hook
     */
    public function save_form_settings_fallback($form) {
        // Only process if this is our settings page
        if (!isset($_POST['gf_js_embed_nonce']) || !wp_verify_nonce($_POST['gf_js_embed_nonce'], 'gf_js_embed_save_settings')) {
            return $form;
        }
        
        // Check if already saved to prevent duplicate processing
        if (!defined('GF_JS_EMBED_SETTINGS_SAVED')) {
            $this->save_form_settings([], $form);
        }
        
        return $form;
    }
    
    /**
     * Get form embed settings
     */
    public static function get_form_settings($form_id) {
        $defaults = [
            'enabled' => false,
            'display_title' => true,
            'display_description' => true,
            'allowed_domains' => [],
            'theme' => '',
            'custom_css' => '',
            'api_key' => '',
            'rate_limit' => 60,
            'honeypot_enabled' => true,
            'csrf_enabled' => true,
            'spam_detection' => true,
            'bot_detection' => true,
            'security_level' => 'medium'
        ];
        
        $settings = get_option('gf_js_embed_form_' . $form_id, []);
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'gf_edit_forms',
            __('JavaScript Embed', 'gf-js-embed'),
            __('JS Embed', 'gf-js-embed'),
            'manage_options',
            'gf_js_embed_analytics',
            [$this, 'analytics_page']
        );
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        if (!$form_id) {
            $this->analytics_overview_page();
            return;
        }
        
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            wp_die(__('Form not found.', 'gf-js-embed'));
        }
        
        $analytics = GF_JS_Embed_Analytics::get_form_analytics($form_id);
        
        ?>
        <div class="wrap">
            <h1>
                <?php echo sprintf(__('JavaScript Embed: %s', 'gf-js-embed'), esc_html($form['title'])); ?>
                <a href="<?php echo admin_url('admin.php?page=gf_js_embed_analytics'); ?>" class="page-title-action">
                    <?php _e('Back to Overview', 'gf-js-embed'); ?>
                </a>
            </h1>
            
            <?php 
            $settings = self::get_form_settings($form_id);
            if (!$settings['enabled']) : 
            ?>
                <div class="notice notice-warning">
                    <p>
                        <?php _e('JavaScript embedding is not enabled for this form.', 'gf-js-embed'); ?>
                        <a href="<?php echo admin_url('admin.php?page=gf_edit_forms&subview=gf_js_embed&id=' . $form_id); ?>&view=settings">
                            <?php _e('Enable it now', 'gf-js-embed'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="gf-embed-analytics-grid">
                <div class="gf-embed-stat-box">
                    <h3><?php _e('Total Views', 'gf-js-embed'); ?></h3>
                    <p class="gf-embed-stat-number"><?php echo number_format($analytics['total_views']); ?></p>
                </div>
                
                <div class="gf-embed-stat-box">
                    <h3><?php _e('Total Submissions', 'gf-js-embed'); ?></h3>
                    <p class="gf-embed-stat-number"><?php echo number_format($analytics['total_submissions']); ?></p>
                </div>
                
                <div class="gf-embed-stat-box">
                    <h3><?php _e('Conversion Rate', 'gf-js-embed'); ?></h3>
                    <p class="gf-embed-stat-number"><?php echo $analytics['conversion_rate']; ?>%</p>
                </div>
                
                <div class="gf-embed-stat-box">
                    <h3><?php _e('Active Domains', 'gf-js-embed'); ?></h3>
                    <p class="gf-embed-stat-number"><?php echo count($analytics['domains']); ?></p>
                </div>
            </div>
            
            <h2><?php _e('Views by Domain', 'gf-js-embed'); ?></h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Domain', 'gf-js-embed'); ?></th>
                        <th><?php _e('Views', 'gf-js-embed'); ?></th>
                        <th><?php _e('Last View', 'gf-js-embed'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['domains'] as $domain => $data) : ?>
                    <tr>
                        <td><?php echo esc_html($domain); ?></td>
                        <td><?php echo number_format($data['views']); ?></td>
                        <td><?php echo human_time_diff($data['last_view'], current_time('timestamp')) . ' ' . __('ago', 'gf-js-embed'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($settings['enabled']) : ?>
            <div style="margin-top: 40px;">
                <h2><?php _e('Embed This Form', 'gf-js-embed'); ?></h2>
                <p><?php _e('Copy and paste one of these code snippets to embed this form on any website:', 'gf-js-embed'); ?></p>
                
                <div class="gf-embed-code-section">
                    <h3><?php _e('Method 1: Simple Embed', 'gf-js-embed'); ?></h3>
                    <p class="description"><?php _e('The easiest way to embed your form. Just copy and paste this code:', 'gf-js-embed'); ?></p>
                    <textarea readonly class="large-text code" rows="3" onclick="this.select();"><!-- <?php echo esc_html($form['title']); ?> -->
<div id="gf-form-<?php echo $form_id; ?>"></div>
<script src="<?php echo esc_url(home_url('/gf-js-embed/v1/embed.js?form=' . $form_id)); ?>"></script></textarea>
                </div>
                
                <div class="gf-embed-code-section">
                    <h3><?php _e('Method 2: Multiple Forms on Same Page', 'gf-js-embed'); ?></h3>
                    <p class="description"><?php _e('Use this method if you want to embed multiple forms on the same page:', 'gf-js-embed'); ?></p>
                    <textarea readonly class="large-text code" rows="7" onclick="this.select();"><!-- Load the SDK once -->
<script src="<?php echo esc_url(home_url('/gf-js-embed/v1/embed.js')); ?>"></script>

<!-- <?php echo esc_html($form['title']); ?> -->
<div data-gf-form="<?php echo $form_id; ?>"></div>

<!-- Add more forms as needed -->
<div data-gf-form="another-form-id"></div></textarea>
                </div>
                
                <?php if ($settings['api_key']) : ?>
                <div class="gf-embed-code-section">
                    <h3><?php _e('Method 3: Secure Embed with API Key', 'gf-js-embed'); ?></h3>
                    <p class="description"><?php _e('For additional security, use your API key:', 'gf-js-embed'); ?></p>
                    <textarea readonly class="large-text code" rows="3" onclick="this.select();"><!-- Secure embed for <?php echo esc_html($form['title']); ?> -->
<div data-gf-form="<?php echo $form_id; ?>" data-gf-api-key="<?php echo esc_attr($settings['api_key']); ?>"></div>
<script src="<?php echo esc_url(home_url('/gf-js-embed/v1/embed.js')); ?>"></script></textarea>
                </div>
                <?php endif; ?>
                
                <div class="gf-embed-code-section">
                    <h3><?php _e('Integration Examples', 'gf-js-embed'); ?></h3>
                    <div class="gf-integration-examples">
                        <div class="gf-example-card">
                            <h4><?php _e('React Component', 'gf-js-embed'); ?></h4>
                            <pre class="code"><code>import React, { useEffect } from 'react';

function <?php echo str_replace([' ', '-'], '', ucwords($form['title'])); ?>Form() {
  useEffect(() => {
    const script = document.createElement('script');
    script.src = '<?php echo esc_url(home_url('/gf-js-embed/v1/embed.js?form=' . $form_id)); ?>';
    script.async = true;
    document.body.appendChild(script);
    
    return () => {
      document.body.removeChild(script);
    };
  }, []);
  
  return &lt;div id="gf-form-<?php echo $form_id; ?>"&gt;&lt;/div&gt;;
}</code></pre>
                        </div>
                        
                        <div class="gf-example-card">
                            <h4><?php _e('Vue Component', 'gf-js-embed'); ?></h4>
                            <pre class="code"><code>&lt;template&gt;
  &lt;div id="gf-form-<?php echo $form_id; ?>"&gt;&lt;/div&gt;
&lt;/template&gt;

&lt;script&gt;
export default {
  mounted() {
    const script = document.createElement('script');
    script.src = '<?php echo esc_url(home_url('/gf-js-embed/v1/embed.js?form=' . $form_id)); ?>';
    script.async = true;
    document.body.appendChild(script);
  }
}
&lt;/script&gt;</code></pre>
                        </div>
                    </div>
                </div>
                
                <div class="gf-embed-code-section">
                    <h3><?php _e('Current Settings', 'gf-js-embed'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Allowed Domains', 'gf-js-embed'); ?></th>
                            <td>
                                <?php if (empty($settings['allowed_domains']) || in_array('*', $settings['allowed_domains'])) : ?>
                                    <span class="dashicons dashicons-warning" style="color: #f39c12;"></span> 
                                    <?php _e('All domains allowed', 'gf-js-embed'); ?>
                                <?php else : ?>
                                    <ul style="margin: 0;">
                                        <?php foreach ($settings['allowed_domains'] as $domain) : ?>
                                            <li><?php echo esc_html($domain); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Security Level', 'gf-js-embed'); ?></th>
                            <td><?php echo ucfirst($settings['security_level']); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Rate Limit', 'gf-js-embed'); ?></th>
                            <td><?php echo $settings['rate_limit']; ?> <?php _e('requests per hour', 'gf-js-embed'); ?></td>
                        </tr>
                    </table>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=gf_edit_forms&subview=gf_js_embed&id=' . $form_id); ?>&view=settings" class="button">
                            <?php _e('Configure Settings', 'gf-js-embed'); ?>
                        </a>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
            .gf-embed-analytics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .gf-embed-stat-box {
                background: #fff;
                border: 1px solid #ddd;
                padding: 20px;
                text-align: center;
            }
            .gf-embed-stat-box h3 {
                margin: 0 0 10px 0;
                color: #666;
            }
            .gf-embed-stat-number {
                font-size: 36px;
                font-weight: bold;
                color: #333;
                margin: 0;
            }
            .gf-embed-code-section {
                margin: 30px 0;
                background: #f8f8f8;
                padding: 20px;
                border-radius: 5px;
                border: 1px solid #e0e0e0;
            }
            .gf-embed-code-section h3 {
                margin-top: 0;
                color: #333;
            }
            .gf-embed-code-section textarea {
                font-family: Consolas, Monaco, monospace;
                font-size: 13px;
                background: #fff;
                border: 1px solid #ddd;
            }
            .gf-embed-code-section .description {
                color: #666;
                margin-bottom: 10px;
            }
            .gf-integration-examples {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }
            .gf-example-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 15px;
            }
            .gf-example-card h4 {
                margin-top: 0;
                color: #555;
            }
            .gf-example-card pre {
                background: #f5f5f5;
                border: 1px solid #e0e0e0;
                border-radius: 3px;
                padding: 10px;
                overflow-x: auto;
                margin: 0;
            }
            .gf-example-card code {
                font-size: 12px;
                line-height: 1.4;
            }
        </style>
        <?php
    }
    
    /**
     * Analytics overview page
     */
    private function analytics_overview_page() {
        // Check if Gravity Forms is active
        if (!class_exists('GFAPI')) {
            ?>
            <div class="wrap">
                <h1><?php _e('JavaScript Embed', 'gf-js-embed'); ?></h1>
                <div class="notice notice-error">
                    <p><?php _e('Gravity Forms must be installed and activated to use this plugin.', 'gf-js-embed'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        
        $forms = GFAPI::get_forms();
        
        ?>
        <div class="wrap">
            <h1><?php _e('JavaScript Embed', 'gf-js-embed'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <?php _e('The JavaScript Embed plugin allows you to embed Gravity Forms on any website using JavaScript. Enable embedding for individual forms and track their performance across different domains.', 'gf-js-embed'); ?>
                    <a href="https://github.com/jezweb/js-gravity-forms-embed#readme" target="_blank"><?php _e('View Documentation', 'gf-js-embed'); ?></a>
                </p>
            </div>
            
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Form', 'gf-js-embed'); ?></th>
                        <th><?php _e('Status', 'gf-js-embed'); ?></th>
                        <th><?php _e('Views', 'gf-js-embed'); ?></th>
                        <th><?php _e('Submissions', 'gf-js-embed'); ?></th>
                        <th><?php _e('Conversion Rate', 'gf-js-embed'); ?></th>
                        <th><?php _e('Actions', 'gf-js-embed'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form) : 
                        $settings = self::get_form_settings($form['id']);
                        $analytics = GF_JS_Embed_Analytics::get_form_analytics($form['id']);
                    ?>
                    <tr>
                        <td><?php echo esc_html($form['title']); ?></td>
                        <td>
                            <?php if ($settings['enabled']) : ?>
                                <span style="color: green;">● <?php _e('Enabled', 'gf-js-embed'); ?></span>
                            <?php else : ?>
                                <span style="color: gray;">● <?php _e('Disabled', 'gf-js-embed'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($analytics['total_views']); ?></td>
                        <td><?php echo number_format($analytics['total_submissions']); ?></td>
                        <td><?php echo $analytics['conversion_rate']; ?>%</td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=gf_js_embed_analytics&form_id=' . $form['id']); ?>">
                                <?php _e('View Details', 'gf-js-embed'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, ['toplevel_page_gf_edit_forms', 'forms_page_gf_js_embed_analytics'])) {
            return;
        }
        
        wp_enqueue_style(
            'gf-js-embed-admin',
            GF_JS_EMBED_PLUGIN_URL . 'assets/css/admin.css',
            [],
            GF_JS_EMBED_VERSION
        );
        
        wp_enqueue_script(
            'gf-js-embed-admin',
            GF_JS_EMBED_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            GF_JS_EMBED_VERSION,
            true
        );
    }
}