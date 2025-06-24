<?php
/**
 * Lazy Loading Manager for Gravity Forms JS Embed
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Lazy_Loading {
    
    private static $instance = null;
    private $lazy_forms = [];
    private $observer_script_added = false;
    
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
        add_action('wp_footer', [$this, 'output_lazy_loading_script']);
        add_filter('gf_js_embed_shortcode_output', [$this, 'maybe_apply_lazy_loading'], 10, 3);
    }
    
    /**
     * Check if lazy loading should be applied
     */
    public function should_apply_lazy_loading($atts, $settings) {
        // Check if lazy loading is globally disabled
        $global_settings = get_option('gf_js_embed_settings', []);
        if (isset($global_settings['disable_lazy_loading']) && $global_settings['disable_lazy_loading']) {
            return false;
        }
        
        // Check shortcode attribute
        if (isset($atts['lazy']) && $atts['lazy'] === 'false') {
            return false;
        }
        
        // Check form-specific settings
        if (isset($settings['disable_lazy_loading']) && $settings['disable_lazy_loading']) {
            return false;
        }
        
        // Check if this is above the fold (disabled by default for performance)
        if (isset($atts['above_fold']) && $atts['above_fold'] === 'true') {
            return false;
        }
        
        // Apply lazy loading by default
        return true;
    }
    
    /**
     * Apply lazy loading to shortcode output
     */
    public function maybe_apply_lazy_loading($output, $atts, $settings) {
        if (!$this->should_apply_lazy_loading($atts, $settings)) {
            return $output;
        }
        
        $form_id = $atts['id'];
        $container_id = $atts['container_id'];
        
        // Store lazy form configuration
        $this->lazy_forms[$container_id] = [
            'form_id' => $form_id,
            'config' => $this->build_lazy_config($atts, $settings),
            'threshold' => $this->get_lazy_threshold($atts, $settings)
        ];
        
        // Create placeholder with intersection observer
        $placeholder_html = $this->create_lazy_placeholder($container_id, $atts, $settings);
        
        return $placeholder_html;
    }
    
    /**
     * Build configuration for lazy-loaded form
     */
    private function build_lazy_config($atts, $settings) {
        // Get API settings
        $api_settings = get_option('gf_js_embed_settings', []);
        $api_key = !empty($atts['api_key']) ? $atts['api_key'] : ($api_settings['api_keys'][0]['key'] ?? '');
        
        $config = [
            'formId' => intval($atts['id']),
            'targetId' => $atts['container_id'],
            'apiKey' => $api_key,
            'apiUrl' => rest_url('gf-js-embed/v1/'),
            'ajax' => $atts['ajax'] === 'true',
            'title' => $atts['title'] === 'true',
            'description' => $atts['description'] === 'true',
            'tabindex' => intval($atts['tabindex']),
            'useCurrentPageAsRedirect' => $atts['use_current_page_as_redirect'] === 'true'
        ];
        
        // Add field values if provided
        if (!empty($atts['field_values'])) {
            parse_str($atts['field_values'], $field_values);
            $config['fieldValues'] = $field_values;
        }
        
        // Add theme if specified
        if (!empty($atts['theme'])) {
            $config['theme'] = $atts['theme'];
        }
        
        return $config;
    }
    
    /**
     * Get lazy loading threshold
     */
    private function get_lazy_threshold($atts, $settings) {
        // Check shortcode attribute
        if (isset($atts['lazy_threshold'])) {
            return floatval($atts['lazy_threshold']);
        }
        
        // Check form settings
        if (isset($settings['lazy_threshold'])) {
            return floatval($settings['lazy_threshold']);
        }
        
        // Check global settings
        $global_settings = get_option('gf_js_embed_settings', []);
        if (isset($global_settings['lazy_threshold'])) {
            return floatval($global_settings['lazy_threshold']);
        }
        
        // Default threshold: load when 50% visible
        return 0.5;
    }
    
    /**
     * Create lazy loading placeholder
     */
    private function create_lazy_placeholder($container_id, $atts, $settings) {
        $form_id = $atts['id'];
        
        // Get placeholder content
        $placeholder_content = $this->get_placeholder_content($form_id, $atts, $settings);
        
        // Build placeholder HTML
        $placeholder_classes = [
            'gf-js-embed-container',
            'gf-js-embed-lazy',
            'loading'
        ];
        
        if (!empty($atts['class'])) {
            $placeholder_classes[] = $atts['class'];
        }
        
        $html = sprintf(
            '<div id="%s" class="%s" data-gf-lazy-form="%s" data-gf-lazy-threshold="%s">%s</div>',
            esc_attr($container_id),
            esc_attr(implode(' ', $placeholder_classes)),
            esc_attr($form_id),
            esc_attr($this->get_lazy_threshold($atts, $settings)),
            $placeholder_content
        );
        
        return $html;
    }
    
    /**
     * Get placeholder content based on settings
     */
    private function get_placeholder_content($form_id, $atts, $settings) {
        $placeholder_type = $this->get_placeholder_type($atts, $settings);
        
        switch ($placeholder_type) {
            case 'skeleton':
                return $this->create_skeleton_placeholder($form_id, $atts, $settings);
                
            case 'spinner':
                return $this->create_spinner_placeholder($form_id, $atts, $settings);
                
            case 'button':
                return $this->create_button_placeholder($form_id, $atts, $settings);
                
            case 'custom':
                return $this->create_custom_placeholder($form_id, $atts, $settings);
                
            case 'minimal':
            default:
                return $this->create_minimal_placeholder($form_id, $atts, $settings);
        }
    }
    
    /**
     * Get placeholder type from settings
     */
    private function get_placeholder_type($atts, $settings) {
        // Check shortcode attribute
        if (isset($atts['lazy_placeholder'])) {
            return sanitize_text_field($atts['lazy_placeholder']);
        }
        
        // Check form settings
        if (isset($settings['lazy_placeholder_type'])) {
            return sanitize_text_field($settings['lazy_placeholder_type']);
        }
        
        // Check global settings
        $global_settings = get_option('gf_js_embed_settings', []);
        if (isset($global_settings['lazy_placeholder_type'])) {
            return sanitize_text_field($global_settings['lazy_placeholder_type']);
        }
        
        return 'minimal';
    }
    
    /**
     * Create minimal placeholder
     */
    private function create_minimal_placeholder($form_id, $atts, $settings) {
        return sprintf(
            '<div class="gf-lazy-placeholder gf-lazy-minimal">
                <div class="gf-lazy-message">%s</div>
                <div class="gf-lazy-spinner"></div>
            </div>',
            esc_html__('Loading form...', 'gf-js-embed')
        );
    }
    
    /**
     * Create skeleton placeholder
     */
    private function create_skeleton_placeholder($form_id, $atts, $settings) {
        // Try to get form structure for skeleton
        if (class_exists('GFAPI') && GFAPI::form_exists($form_id)) {
            $form = GFAPI::get_form($form_id);
            $skeleton_fields = '';
            
            $field_count = min(count($form['fields']), 5); // Limit skeleton fields
            for ($i = 0; $i < $field_count; $i++) {
                $skeleton_fields .= '
                    <div class="gf-skeleton-field">
                        <div class="gf-skeleton-label"></div>
                        <div class="gf-skeleton-input"></div>
                    </div>';
            }
            
            return sprintf(
                '<div class="gf-lazy-placeholder gf-lazy-skeleton">
                    <div class="gf-skeleton-title"></div>
                    <div class="gf-skeleton-description"></div>
                    %s
                    <div class="gf-skeleton-button"></div>
                </div>',
                $skeleton_fields
            );
        }
        
        // Fallback to generic skeleton
        return '
            <div class="gf-lazy-placeholder gf-lazy-skeleton">
                <div class="gf-skeleton-title"></div>
                <div class="gf-skeleton-description"></div>
                <div class="gf-skeleton-field">
                    <div class="gf-skeleton-label"></div>
                    <div class="gf-skeleton-input"></div>
                </div>
                <div class="gf-skeleton-field">
                    <div class="gf-skeleton-label"></div>
                    <div class="gf-skeleton-input"></div>
                </div>
                <div class="gf-skeleton-button"></div>
            </div>';
    }
    
    /**
     * Create spinner placeholder
     */
    private function create_spinner_placeholder($form_id, $atts, $settings) {
        return sprintf(
            '<div class="gf-lazy-placeholder gf-lazy-spinner-container">
                <div class="gf-lazy-spinner-large"></div>
                <div class="gf-lazy-message">%s</div>
            </div>',
            esc_html__('Loading form...', 'gf-js-embed')
        );
    }
    
    /**
     * Create button placeholder
     */
    private function create_button_placeholder($form_id, $atts, $settings) {
        $button_text = $this->get_button_text($form_id, $atts, $settings);
        
        return sprintf(
            '<div class="gf-lazy-placeholder gf-lazy-button-container">
                <button type="button" class="gf-lazy-load-button" data-form-id="%s">
                    %s
                </button>
            </div>',
            esc_attr($form_id),
            esc_html($button_text)
        );
    }
    
    /**
     * Create custom placeholder
     */
    private function create_custom_placeholder($form_id, $atts, $settings) {
        // Check for custom placeholder content
        $custom_content = '';
        
        // Check shortcode attribute
        if (isset($atts['lazy_placeholder_content'])) {
            $custom_content = wp_kses_post($atts['lazy_placeholder_content']);
        }
        
        // Check form settings
        if (empty($custom_content) && isset($settings['lazy_placeholder_content'])) {
            $custom_content = wp_kses_post($settings['lazy_placeholder_content']);
        }
        
        // Check global settings
        if (empty($custom_content)) {
            $global_settings = get_option('gf_js_embed_settings', []);
            if (isset($global_settings['lazy_placeholder_content'])) {
                $custom_content = wp_kses_post($global_settings['lazy_placeholder_content']);
            }
        }
        
        // Fallback to minimal if no custom content
        if (empty($custom_content)) {
            return $this->create_minimal_placeholder($form_id, $atts, $settings);
        }
        
        return sprintf(
            '<div class="gf-lazy-placeholder gf-lazy-custom">%s</div>',
            $custom_content
        );
    }
    
    /**
     * Get button text for button placeholder
     */
    private function get_button_text($form_id, $atts, $settings) {
        // Try to get form title
        if (class_exists('GFAPI') && GFAPI::form_exists($form_id)) {
            $form = GFAPI::get_form($form_id);
            if (!empty($form['title'])) {
                return sprintf(__('Load "%s" Form', 'gf-js-embed'), $form['title']);
            }
        }
        
        return __('Load Form', 'gf-js-embed');
    }
    
    /**
     * Output lazy loading JavaScript
     */
    public function output_lazy_loading_script() {
        if (empty($this->lazy_forms)) {
            return;
        }
        
        // Output CSS for placeholders
        $this->output_placeholder_css();
        
        // Output JavaScript for intersection observer
        $this->output_intersection_observer_script();
        
        // Output form configurations
        $this->output_form_configs();
    }
    
    /**
     * Output CSS for lazy loading placeholders
     */
    private function output_placeholder_css() {
        echo '<style id="gf-lazy-loading-css">
            .gf-js-embed-lazy {
                min-height: 200px;
                position: relative;
            }
            
            .gf-lazy-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 200px;
                padding: 20px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            
            .gf-lazy-message {
                margin-bottom: 10px;
                color: #666;
                font-size: 14px;
            }
            
            .gf-lazy-spinner, .gf-lazy-spinner-large {
                border: 2px solid #f3f3f3;
                border-top: 2px solid #0073aa;
                border-radius: 50%;
                animation: gf-spin 1s linear infinite;
            }
            
            .gf-lazy-spinner {
                width: 20px;
                height: 20px;
            }
            
            .gf-lazy-spinner-large {
                width: 40px;
                height: 40px;
                margin-bottom: 15px;
            }
            
            @keyframes gf-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /* Skeleton styles */
            .gf-lazy-skeleton {
                align-items: stretch;
                text-align: left;
            }
            
            .gf-skeleton-title, .gf-skeleton-description, .gf-skeleton-label, .gf-skeleton-input, .gf-skeleton-button {
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: gf-skeleton-loading 1.5s ease-in-out infinite;
                border-radius: 4px;
                margin-bottom: 10px;
            }
            
            .gf-skeleton-title {
                height: 24px;
                width: 60%;
            }
            
            .gf-skeleton-description {
                height: 16px;
                width: 80%;
                margin-bottom: 20px;
            }
            
            .gf-skeleton-field {
                margin-bottom: 15px;
            }
            
            .gf-skeleton-label {
                height: 16px;
                width: 30%;
                margin-bottom: 5px;
            }
            
            .gf-skeleton-input {
                height: 40px;
                width: 100%;
            }
            
            .gf-skeleton-button {
                height: 40px;
                width: 120px;
                margin-top: 10px;
            }
            
            @keyframes gf-skeleton-loading {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }
            
            /* Button placeholder styles */
            .gf-lazy-load-button {
                background: #0073aa;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.2s ease;
            }
            
            .gf-lazy-load-button:hover {
                background: #005a87;
            }
            
            .gf-lazy-load-button:focus {
                outline: 2px solid #005a87;
                outline-offset: 2px;
            }
            
            /* Loading state */
            .gf-js-embed-lazy.gf-loading .gf-lazy-placeholder {
                opacity: 0.7;
            }
        </style>';
    }
    
    /**
     * Output intersection observer script
     */
    private function output_intersection_observer_script() {
        ?>
        <script id="gf-lazy-loading-js">
        (function() {
            'use strict';
            
            // Check for Intersection Observer support
            if (!('IntersectionObserver' in window)) {
                // Fallback: load all forms immediately
                document.querySelectorAll('.gf-js-embed-lazy').forEach(function(container) {
                    loadLazyForm(container);
                });
                return;
            }
            
            // Intersection Observer configuration
            var observerOptions = {
                root: null,
                rootMargin: '50px',
                threshold: [0, 0.25, 0.5, 0.75, 1.0]
            };
            
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var container = entry.target;
                        var threshold = parseFloat(container.dataset.gfLazyThreshold) || 0.5;
                        
                        if (entry.intersectionRatio >= threshold) {
                            observer.unobserve(container);
                            loadLazyForm(container);
                        }
                    }
                });
            }, observerOptions);
            
            // Observe all lazy form containers
            document.querySelectorAll('.gf-js-embed-lazy').forEach(function(container) {
                observer.observe(container);
            });
            
            // Handle manual load buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('gf-lazy-load-button')) {
                    e.preventDefault();
                    var container = e.target.closest('.gf-js-embed-lazy');
                    if (container) {
                        observer.unobserve(container);
                        loadLazyForm(container);
                    }
                }
            });
            
            function loadLazyForm(container) {
                if (container.classList.contains('gf-loading') || container.classList.contains('gf-loaded')) {
                    return;
                }
                
                container.classList.add('gf-loading');
                var formId = container.dataset.gfLazyForm;
                var config = window.gfLazyConfigs && window.gfLazyConfigs[container.id];
                
                if (!config) {
                    console.error('GF Lazy Loading: No configuration found for container', container.id);
                    return;
                }
                
                // Load the SDK if not already loaded
                if (typeof GravityFormsEmbed === 'undefined') {
                    var script = document.createElement('script');
                    script.src = config.sdkUrl || '<?php echo esc_url(home_url('/gf-js-embed/v1/embed.js')); ?>';
                    script.async = true;
                    script.onload = function() {
                        initializeForm(container, config);
                    };
                    script.onerror = function() {
                        showError(container, 'Failed to load form script');
                    };
                    document.head.appendChild(script);
                } else {
                    initializeForm(container, config);
                }
            }
            
            function initializeForm(container, config) {
                try {
                    // Clear placeholder content
                    container.innerHTML = '';
                    container.classList.remove('gf-loading');
                    container.classList.add('gf-loaded');
                    
                    // Initialize the form
                    var embed = new GravityFormsEmbed(config);
                    embed.render();
                    
                    // Fire custom event
                    var event = new CustomEvent('gfLazyFormLoaded', {
                        detail: { container: container, config: config }
                    });
                    container.dispatchEvent(event);
                    
                } catch (error) {
                    console.error('GF Lazy Loading: Form initialization failed', error);
                    showError(container, 'Failed to initialize form');
                }
            }
            
            function showError(container, message) {
                container.classList.remove('gf-loading');
                container.classList.add('gf-error');
                container.innerHTML = '<div class="gf-lazy-error">' + 
                    '<p>Error: ' + message + '</p>' +
                    '<button type="button" onclick="location.reload()">Retry</button>' +
                    '</div>';
            }
            
            // Performance: Preload SDK on user interaction
            var preloadTriggered = false;
            function preloadSDK() {
                if (preloadTriggered || typeof GravityFormsEmbed !== 'undefined') {
                    return;
                }
                preloadTriggered = true;
                
                var link = document.createElement('link');
                link.rel = 'preload';
                link.as = 'script';
                link.href = '<?php echo esc_url(home_url('/gf-js-embed/v1/embed.js')); ?>';
                document.head.appendChild(link);
            }
            
            // Preload on first user interaction
            ['mouseenter', 'touchstart', 'scroll'].forEach(function(event) {
                document.addEventListener(event, preloadSDK, { once: true, passive: true });
            });
            
        })();
        </script>
        <?php
    }
    
    /**
     * Output form configurations for JavaScript
     */
    private function output_form_configs() {
        echo '<script>';
        echo 'window.gfLazyConfigs = ' . wp_json_encode($this->lazy_forms) . ';';
        echo '</script>';
    }
    
    /**
     * Get performance metrics for lazy loading
     */
    public function get_performance_metrics() {
        return [
            'lazy_forms_count' => count($this->lazy_forms),
            'observer_script_size' => $this->observer_script_added ? 2048 : 0, // Approximate size
            'css_size' => 1536, // Approximate CSS size
            'estimated_savings' => count($this->lazy_forms) * 15000 // Estimated bytes saved per form
        ];
    }
}