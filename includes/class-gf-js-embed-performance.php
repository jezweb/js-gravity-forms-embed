<?php
/**
 * Performance Optimization class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Performance {
    
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
        // Hook into WordPress optimization features
        add_filter('gf_js_embed_theme_css', [$this, 'optimize_theme_css'], 10, 2);
        add_filter('gf_js_embed_theme_variables', [$this, 'optimize_theme_variables'], 10, 1);
        
        // Add resource hints for faster loading
        add_action('wp_head', [$this, 'add_resource_hints'], 1);
        
        // Register theme preloading
        add_action('wp_enqueue_scripts', [$this, 'preload_theme_assets']);
    }
    
    /**
     * Optimize theme CSS
     */
    public function optimize_theme_css($css, $theme_name) {
        // Check if we should minify
        $should_minify = !defined('SCRIPT_DEBUG') || !SCRIPT_DEBUG;
        
        if ($should_minify) {
            // Basic CSS minification
            $css = $this->minify_css($css);
        }
        
        return $css;
    }
    
    /**
     * Minify CSS
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove spaces around specific characters
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        
        // Remove trailing semicolon before closing brace
        $css = str_replace(';}', '}', $css);
        
        // Remove leading/trailing whitespace
        $css = trim($css);
        
        return $css;
    }
    
    /**
     * Optimize theme variables by removing unused ones
     */
    public function optimize_theme_variables($variables) {
        // Get list of actually used variables in the CSS
        $used_variables = $this->get_used_variables();
        
        // If we can't determine used variables, return all
        if (empty($used_variables)) {
            return $variables;
        }
        
        // Filter to only include used variables
        $optimized = [];
        foreach ($variables as $name => $value) {
            if (in_array($name, $used_variables)) {
                $optimized[$name] = $value;
            }
        }
        
        return $optimized;
    }
    
    /**
     * Get list of CSS variables actually used in stylesheets
     */
    private function get_used_variables() {
        // Cache the result
        $cache_key = 'used_css_variables';
        $cached = wp_cache_get($cache_key, 'gf_js_embed_performance');
        
        if ($cached !== false) {
            return $cached;
        }
        
        // In a real implementation, this would scan the CSS files
        // For now, return empty to include all variables
        $used = [];
        
        wp_cache_set($cache_key, $used, 'gf_js_embed_performance', HOUR_IN_SECONDS);
        
        return $used;
    }
    
    /**
     * Add resource hints for faster loading
     */
    public function add_resource_hints() {
        // Only on pages that might have forms
        if (!is_singular() && !is_page()) {
            return;
        }
        
        // Preconnect to the site's own domain for API calls
        $api_url = rest_url('gf-js-embed/v1/');
        $parsed = parse_url($api_url);
        
        if ($parsed && isset($parsed['host'])) {
            echo sprintf(
                '<link rel="preconnect" href="%s://%s" crossorigin>',
                $parsed['scheme'],
                $parsed['host']
            ) . "\n";
        }
        
        // DNS prefetch for common resources
        echo '<link rel="dns-prefetch" href="' . esc_url(home_url()) . '">' . "\n";
    }
    
    /**
     * Preload theme assets for better performance
     */
    public function preload_theme_assets() {
        // Check if we're on a page that might have embedded forms
        if (!is_singular() && !is_page()) {
            return;
        }
        
        global $post;
        if (!$post) {
            return;
        }
        
        // Check if content has our shortcode or embed code
        $has_shortcode = has_shortcode($post->post_content, 'gf_js_embed');
        $has_embed = strpos($post->post_content, 'data-gf-form') !== false;
        
        if (!$has_shortcode && !$has_embed) {
            return;
        }
        
        // Preload the embed script
        echo sprintf(
            '<link rel="preload" href="%s" as="script">',
            esc_url(home_url('/gf-js-embed/v1/embed.js'))
        ) . "\n";
    }
    
    /**
     * Get optimized theme CSS for inline embedding
     */
    public function get_inline_theme_css($theme_name) {
        $theme_manager = GF_JS_Embed_Theme_Manager::get_instance();
        $css_variables = GF_JS_Embed_CSS_Variables::get_instance();
        
        // Get theme variables
        $variables = [];
        
        // Check custom themes
        $custom_themes = $theme_manager->get_custom_themes();
        if (isset($custom_themes[$theme_name])) {
            $variables = $custom_themes[$theme_name]['variables'];
        } else {
            // Check predefined themes
            $predefined_themes = $theme_manager->get_predefined_themes();
            foreach ($predefined_themes as $category => $category_data) {
                if (isset($category_data['themes'][$theme_name])) {
                    $variables = array_merge(
                        $css_variables->get_default_variables(),
                        $category_data['themes'][$theme_name]['variables']
                    );
                    break;
                }
            }
        }
        
        if (empty($variables)) {
            return '';
        }
        
        // Generate minified CSS
        $css = $css_variables->generate_css_variables($variables, true);
        
        // Apply additional optimization
        return $this->optimize_theme_css($css, $theme_name);
    }
    
    /**
     * Generate critical CSS for above-the-fold rendering
     */
    public function get_critical_css() {
        // This would contain only the most essential styles
        // needed for initial render to prevent layout shift
        return '
.gf-js-embed-container {
    min-height: 200px;
    position: relative;
}
.gf-js-embed-container.loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 40px;
    height: 40px;
    margin: -20px 0 0 -20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #0073aa;
    border-radius: 50%;
    animation: gf-spin 1s linear infinite;
}
@keyframes gf-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}';
    }
    
    /**
     * Enable lazy loading for form assets
     */
    public function enable_lazy_loading($config) {
        $config['lazyLoad'] = true;
        $config['loadDelay'] = 100; // ms delay before loading
        $config['viewport'] = [
            'rootMargin' => '50px' // Start loading 50px before in viewport
        ];
        
        return $config;
    }
}