<?php
/**
 * CSS Variables Management class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_CSS_Variables {
    
    private static $instance = null;
    
    /**
     * CSS Variable definitions with validation rules
     */
    private $variable_definitions = [
        // Color variables
        '--gf-primary-color' => [
            'type' => 'color',
            'default' => '#0073aa',
            'description' => 'Primary color for buttons and focus states',
            'category' => 'colors'
        ],
        '--gf-primary-hover' => [
            'type' => 'color',
            'default' => '#005a87',
            'description' => 'Primary color hover state',
            'category' => 'colors'
        ],
        '--gf-primary-focus' => [
            'type' => 'color',
            'default' => '#004c75',
            'description' => 'Primary color focus state',
            'category' => 'colors'
        ],
        '--gf-text-color' => [
            'type' => 'color',
            'default' => '#333',
            'description' => 'Main text color',
            'category' => 'colors'
        ],
        '--gf-text-muted' => [
            'type' => 'color',
            'default' => '#666',
            'description' => 'Muted text color for descriptions',
            'category' => 'colors'
        ],
        '--gf-text-light' => [
            'type' => 'color',
            'default' => '#999',
            'description' => 'Light text color for placeholders',
            'category' => 'colors'
        ],
        '--gf-bg-color' => [
            'type' => 'color',
            'default' => '#fff',
            'description' => 'Main background color',
            'category' => 'colors'
        ],
        '--gf-bg-alt' => [
            'type' => 'color',
            'default' => '#f9f9f9',
            'description' => 'Alternative background color',
            'category' => 'colors'
        ],
        '--gf-bg-dark' => [
            'type' => 'color',
            'default' => '#2d2d2d',
            'description' => 'Dark background color',
            'category' => 'colors'
        ],
        '--gf-border-color' => [
            'type' => 'color',
            'default' => '#ddd',
            'description' => 'Default border color',
            'category' => 'colors'
        ],
        '--gf-border-focus' => [
            'type' => 'color',
            'default' => '#0073aa',
            'description' => 'Border color for focus states',
            'category' => 'colors'
        ],
        '--gf-border-error' => [
            'type' => 'color',
            'default' => '#dc3232',
            'description' => 'Border color for error states',
            'category' => 'colors'
        ],
        '--gf-success-color' => [
            'type' => 'color',
            'default' => '#34a853',
            'description' => 'Success message color',
            'category' => 'colors'
        ],
        '--gf-success-bg' => [
            'type' => 'color',
            'default' => '#e6f4ea',
            'description' => 'Success message background',
            'category' => 'colors'
        ],
        '--gf-error-color' => [
            'type' => 'color',
            'default' => '#dc3232',
            'description' => 'Error message color',
            'category' => 'colors'
        ],
        '--gf-error-bg' => [
            'type' => 'color',
            'default' => '#ffeaea',
            'description' => 'Error message background',
            'category' => 'colors'
        ],
        '--gf-warning-color' => [
            'type' => 'color',
            'default' => '#f9a825',
            'description' => 'Warning message color',
            'category' => 'colors'
        ],
        '--gf-warning-bg' => [
            'type' => 'color',
            'default' => '#fff3cd',
            'description' => 'Warning message background',
            'category' => 'colors'
        ],
        
        // Typography variables
        '--gf-font-family' => [
            'type' => 'font-family',
            'default' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
            'description' => 'Font family for all text',
            'category' => 'typography'
        ],
        '--gf-font-size-base' => [
            'type' => 'size',
            'default' => '16px',
            'description' => 'Base font size',
            'category' => 'typography',
            'min' => '12px',
            'max' => '24px'
        ],
        '--gf-font-size-small' => [
            'type' => 'size',
            'default' => '14px',
            'description' => 'Small font size for descriptions',
            'category' => 'typography',
            'min' => '10px',
            'max' => '18px'
        ],
        '--gf-font-size-large' => [
            'type' => 'size',
            'default' => '18px',
            'description' => 'Large font size',
            'category' => 'typography',
            'min' => '16px',
            'max' => '28px'
        ],
        '--gf-font-size-title' => [
            'type' => 'size',
            'default' => '24px',
            'description' => 'Title font size',
            'category' => 'typography',
            'min' => '18px',
            'max' => '36px'
        ],
        '--gf-font-weight-normal' => [
            'type' => 'font-weight',
            'default' => '400',
            'description' => 'Normal font weight',
            'category' => 'typography'
        ],
        '--gf-font-weight-medium' => [
            'type' => 'font-weight',
            'default' => '500',
            'description' => 'Medium font weight',
            'category' => 'typography'
        ],
        '--gf-font-weight-bold' => [
            'type' => 'font-weight',
            'default' => '600',
            'description' => 'Bold font weight',
            'category' => 'typography'
        ],
        '--gf-line-height-base' => [
            'type' => 'number',
            'default' => '1.5',
            'description' => 'Base line height',
            'category' => 'typography',
            'min' => '1.2',
            'max' => '2.0'
        ],
        
        // Spacing variables
        '--gf-spacing-xs' => [
            'type' => 'size',
            'default' => '5px',
            'description' => 'Extra small spacing',
            'category' => 'spacing',
            'min' => '2px',
            'max' => '10px'
        ],
        '--gf-spacing-sm' => [
            'type' => 'size',
            'default' => '10px',
            'description' => 'Small spacing',
            'category' => 'spacing',
            'min' => '5px',
            'max' => '20px'
        ],
        '--gf-spacing-md' => [
            'type' => 'size',
            'default' => '15px',
            'description' => 'Medium spacing',
            'category' => 'spacing',
            'min' => '10px',
            'max' => '30px'
        ],
        '--gf-spacing-lg' => [
            'type' => 'size',
            'default' => '20px',
            'description' => 'Large spacing',
            'category' => 'spacing',
            'min' => '15px',
            'max' => '40px'
        ],
        '--gf-spacing-xl' => [
            'type' => 'size',
            'default' => '30px',
            'description' => 'Extra large spacing',
            'category' => 'spacing',
            'min' => '20px',
            'max' => '60px'
        ],
        '--gf-input-padding' => [
            'type' => 'padding',
            'default' => '10px 12px',
            'description' => 'Input field padding',
            'category' => 'spacing'
        ],
        '--gf-button-padding' => [
            'type' => 'padding',
            'default' => '12px 24px',
            'description' => 'Button padding',
            'category' => 'spacing'
        ],
        '--gf-field-margin' => [
            'type' => 'size',
            'default' => '20px',
            'description' => 'Margin between form fields',
            'category' => 'spacing',
            'min' => '10px',
            'max' => '40px'
        ],
        
        // Border radius variables
        '--gf-border-radius-sm' => [
            'type' => 'size',
            'default' => '4px',
            'description' => 'Small border radius',
            'category' => 'design',
            'min' => '0px',
            'max' => '20px'
        ],
        '--gf-border-radius-md' => [
            'type' => 'size',
            'default' => '6px',
            'description' => 'Medium border radius',
            'category' => 'design',
            'min' => '0px',
            'max' => '30px'
        ],
        '--gf-border-radius-lg' => [
            'type' => 'size',
            'default' => '8px',
            'description' => 'Large border radius',
            'category' => 'design',
            'min' => '0px',
            'max' => '40px'
        ],
        '--gf-border-radius-xl' => [
            'type' => 'size',
            'default' => '12px',
            'description' => 'Extra large border radius',
            'category' => 'design',
            'min' => '0px',
            'max' => '50px'
        ],
        '--gf-border-radius-pill' => [
            'type' => 'size',
            'default' => '25px',
            'description' => 'Pill-shaped border radius',
            'category' => 'design',
            'min' => '15px',
            'max' => '50px'
        ],
        
        // Shadow variables
        '--gf-shadow-sm' => [
            'type' => 'shadow',
            'default' => '0 1px 3px rgba(0,0,0,0.1)',
            'description' => 'Small shadow',
            'category' => 'design'
        ],
        '--gf-shadow-md' => [
            'type' => 'shadow',
            'default' => '0 2px 6px rgba(0,0,0,0.1)',
            'description' => 'Medium shadow',
            'category' => 'design'
        ],
        '--gf-shadow-lg' => [
            'type' => 'shadow',
            'default' => '0 4px 12px rgba(0,0,0,0.15)',
            'description' => 'Large shadow',
            'category' => 'design'
        ],
        '--gf-shadow-focus' => [
            'type' => 'shadow',
            'default' => '0 0 0 2px rgba(0, 115, 170, 0.2)',
            'description' => 'Focus shadow for inputs',
            'category' => 'design'
        ],
        
        // Transition variables
        '--gf-transition-fast' => [
            'type' => 'transition',
            'default' => '0.15s ease-in-out',
            'description' => 'Fast transition timing',
            'category' => 'design'
        ],
        '--gf-transition-normal' => [
            'type' => 'transition',
            'default' => '0.2s ease-in-out',
            'description' => 'Normal transition timing',
            'category' => 'design'
        ],
        '--gf-transition-slow' => [
            'type' => 'transition',
            'default' => '0.3s ease-in-out',
            'description' => 'Slow transition timing',
            'category' => 'design'
        ],
        
        // Form specific variables
        '--gf-input-border-width' => [
            'type' => 'size',
            'default' => '1px',
            'description' => 'Input border width',
            'category' => 'form',
            'min' => '0px',
            'max' => '5px'
        ]
    ];
    
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
        // No initialization needed for now
    }
    
    /**
     * Get all variable definitions
     */
    public function get_variable_definitions() {
        return $this->variable_definitions;
    }
    
    /**
     * Get variables by category
     */
    public function get_variables_by_category($category) {
        return array_filter($this->variable_definitions, function($var) use ($category) {
            return $var['category'] === $category;
        });
    }
    
    /**
     * Get variable definition
     */
    public function get_variable_definition($variable_name) {
        return isset($this->variable_definitions[$variable_name]) 
            ? $this->variable_definitions[$variable_name] 
            : null;
    }
    
    /**
     * Validate variable value
     */
    public function validate_variable_value($variable_name, $value) {
        $definition = $this->get_variable_definition($variable_name);
        
        if (!$definition) {
            return new WP_Error('invalid_variable', 'Unknown CSS variable');
        }
        
        switch ($definition['type']) {
            case 'color':
                return $this->validate_color($value);
                
            case 'size':
                return $this->validate_size($value, $definition);
                
            case 'font-family':
                return $this->validate_font_family($value);
                
            case 'font-weight':
                return $this->validate_font_weight($value);
                
            case 'number':
                return $this->validate_number($value, $definition);
                
            case 'padding':
                return $this->validate_padding($value);
                
            case 'shadow':
                return $this->validate_shadow($value);
                
            case 'transition':
                return $this->validate_transition($value);
                
            default:
                return $this->validate_generic($value);
        }
    }
    
    /**
     * Validate color value
     */
    private function validate_color($value) {
        // Accept hex colors, rgb(), rgba(), hsl(), hsla(), and named colors
        $patterns = [
            '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', // Hex
            '/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/', // RGB
            '/^rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*[\d.]+\s*\)$/', // RGBA
            '/^hsl\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*\)$/', // HSL
            '/^hsla\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*,\s*[\d.]+\s*\)$/', // HSLA
            '/^(transparent|inherit|currentColor)$/', // Special values
            '/^[a-zA-Z]+$/' // Named colors (basic validation)
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return new WP_Error('invalid_color', 'Invalid color format');
    }
    
    /**
     * Validate size value
     */
    private function validate_size($value, $definition) {
        // Accept px, em, rem, %, vh, vw units
        if (!preg_match('/^[\d.]+(px|em|rem|%|vh|vw)$/', $value)) {
            return new WP_Error('invalid_size', 'Invalid size format');
        }
        
        // Check min/max if defined
        if (isset($definition['min']) || isset($definition['max'])) {
            $numeric_value = floatval($value);
            $unit = preg_replace('/[\d.]/', '', $value);
            
            if (isset($definition['min'])) {
                $min_value = floatval($definition['min']);
                if ($numeric_value < $min_value) {
                    return new WP_Error('size_too_small', "Value must be at least {$definition['min']}");
                }
            }
            
            if (isset($definition['max'])) {
                $max_value = floatval($definition['max']);
                if ($numeric_value > $max_value) {
                    return new WP_Error('size_too_large', "Value must be at most {$definition['max']}");
                }
            }
        }
        
        return true;
    }
    
    /**
     * Validate font family value
     */
    private function validate_font_family($value) {
        // Basic validation - should contain valid font family syntax
        if (empty($value) || strlen($value) > 500) {
            return new WP_Error('invalid_font_family', 'Invalid font family');
        }
        
        return true;
    }
    
    /**
     * Validate font weight value
     */
    private function validate_font_weight($value) {
        $valid_weights = ['100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold', 'lighter', 'bolder'];
        
        if (!in_array($value, $valid_weights)) {
            return new WP_Error('invalid_font_weight', 'Invalid font weight');
        }
        
        return true;
    }
    
    /**
     * Validate number value
     */
    private function validate_number($value, $definition) {
        if (!is_numeric($value)) {
            return new WP_Error('invalid_number', 'Value must be numeric');
        }
        
        $numeric_value = floatval($value);
        
        if (isset($definition['min']) && $numeric_value < $definition['min']) {
            return new WP_Error('number_too_small', "Value must be at least {$definition['min']}");
        }
        
        if (isset($definition['max']) && $numeric_value > $definition['max']) {
            return new WP_Error('number_too_large', "Value must be at most {$definition['max']}");
        }
        
        return true;
    }
    
    /**
     * Validate padding value
     */
    private function validate_padding($value) {
        // Accept various padding formats: "10px", "10px 15px", "10px 15px 10px 15px"
        if (!preg_match('/^[\d.]+(px|em|rem|%)?(\s+[\d.]+(px|em|rem|%)?){0,3}$/', $value)) {
            return new WP_Error('invalid_padding', 'Invalid padding format');
        }
        
        return true;
    }
    
    /**
     * Validate shadow value
     */
    private function validate_shadow($value) {
        // Basic shadow validation - accept "none" or shadow syntax
        if ($value === 'none' || $value === 'inherit') {
            return true;
        }
        
        // Basic validation for box-shadow syntax
        if (preg_match('/^[\d.-]+\s*[\w\s(),.-]+$/', $value)) {
            return true;
        }
        
        return new WP_Error('invalid_shadow', 'Invalid shadow format');
    }
    
    /**
     * Validate transition value
     */
    private function validate_transition($value) {
        // Basic transition validation
        if (preg_match('/^[\d.]+s?\s+(ease|ease-in|ease-out|ease-in-out|linear)$/i', $value)) {
            return true;
        }
        
        return new WP_Error('invalid_transition', 'Invalid transition format');
    }
    
    /**
     * Generic validation (just check length and basic safety)
     */
    private function validate_generic($value) {
        if (empty($value) || strlen($value) > 200) {
            return new WP_Error('invalid_value', 'Invalid value');
        }
        
        // Check for potentially dangerous content
        if (preg_match('/(javascript:|data:|expression\()/i', $value)) {
            return new WP_Error('unsafe_value', 'Value contains unsafe content');
        }
        
        return true;
    }
    
    /**
     * Sanitize variable value
     */
    public function sanitize_variable_value($variable_name, $value) {
        $validation = $this->validate_variable_value($variable_name, $value);
        
        if (is_wp_error($validation)) {
            // Return default value if validation fails
            $definition = $this->get_variable_definition($variable_name);
            return $definition ? $definition['default'] : '';
        }
        
        return sanitize_text_field($value);
    }
    
    /**
     * Get default CSS variables as array
     */
    public function get_default_variables() {
        $defaults = [];
        
        foreach ($this->variable_definitions as $name => $definition) {
            $defaults[$name] = $definition['default'];
        }
        
        return $defaults;
    }
    
    /**
     * Generate CSS variables string
     */
    public function generate_css_variables($variables = null, $minify = false) {
        if ($variables === null) {
            $variables = $this->get_default_variables();
        }
        
        /**
         * Filters CSS variables before they are applied
         * 
         * @since 2.0.0
         * 
         * @param array $variables Array of CSS variable name => value pairs
         * @param string $theme_name Name of the current theme (if known)
         */
        $theme_name = $this->current_theme ?? 'default';
        $variables = apply_filters('gf_js_embed_theme_variables', $variables, $theme_name);
        
        if ($minify) {
            // Minified version
            $css = ':root{';
            foreach ($variables as $name => $value) {
                if (isset($this->variable_definitions[$name])) {
                    $sanitized_value = $this->sanitize_variable_value($name, $value);
                    $css .= "{$name}:{$sanitized_value};";
                }
            }
            $css .= '}';
        } else {
            // Formatted version
            $css = ":root {\n";
            foreach ($variables as $name => $value) {
                if (isset($this->variable_definitions[$name])) {
                    $sanitized_value = $this->sanitize_variable_value($name, $value);
                    $css .= "    {$name}: {$sanitized_value};\n";
                }
            }
            $css .= "}\n";
        }
        
        return $css;
    }
    
    /**
     * Validate theme data for security and conflicts
     */
    public function validate_theme_data($theme_data) {
        $errors = [];
        $warnings = [];
        
        // Check for required fields
        if (empty($theme_data['name'])) {
            $errors[] = __('Theme name is required', 'gf-js-embed');
        }
        
        if (empty($theme_data['variables']) || !is_array($theme_data['variables'])) {
            $errors[] = __('Theme variables are required and must be an array', 'gf-js-embed');
        }
        
        // Validate theme name
        if (!empty($theme_data['name'])) {
            if (strlen($theme_data['name']) > 100) {
                $errors[] = __('Theme name is too long (max 100 characters)', 'gf-js-embed');
            }
            
            if (!preg_match('/^[a-zA-Z0-9\s_-]+$/', $theme_data['name'])) {
                $errors[] = __('Theme name contains invalid characters', 'gf-js-embed');
            }
        }
        
        // Validate description
        if (!empty($theme_data['description']) && strlen($theme_data['description']) > 500) {
            $errors[] = __('Theme description is too long (max 500 characters)', 'gf-js-embed');
        }
        
        // Validate variables
        if (!empty($theme_data['variables'])) {
            foreach ($theme_data['variables'] as $var_name => $value) {
                // Check if variable is defined
                if (!isset($this->variable_definitions[$var_name])) {
                    $warnings[] = sprintf(__('Unknown variable: %s', 'gf-js-embed'), $var_name);
                    continue;
                }
                
                // Validate variable value
                $validation = $this->validate_variable_value($var_name, $value);
                if (is_wp_error($validation)) {
                    $errors[] = sprintf(__('Invalid value for %s: %s', 'gf-js-embed'), $var_name, $validation->get_error_message());
                }
            }
        }
        
        // Check for theme conflicts
        $conflicts = $this->detect_theme_conflicts($theme_data['variables'] ?? []);
        if (!empty($conflicts)) {
            $warnings = array_merge($warnings, $conflicts);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Detect potential theme conflicts
     */
    private function detect_theme_conflicts($variables) {
        $warnings = [];
        
        // Check for extremely contrasting values that might cause readability issues
        if (isset($variables['--gf-text-color']) && isset($variables['--gf-bg-color'])) {
            $text_color = $this->parse_color($variables['--gf-text-color']);
            $bg_color = $this->parse_color($variables['--gf-bg-color']);
            
            if ($text_color && $bg_color) {
                $contrast = $this->calculate_contrast_ratio($text_color, $bg_color);
                if ($contrast < 4.5) {
                    $warnings[] = __('Low contrast between text and background colors may affect readability', 'gf-js-embed');
                }
            }
        }
        
        // Check for extremely large spacing values
        $spacing_vars = ['--gf-spacing-xs', '--gf-spacing-sm', '--gf-spacing-md', '--gf-spacing-lg', '--gf-spacing-xl'];
        foreach ($spacing_vars as $var) {
            if (isset($variables[$var])) {
                $value = floatval($variables[$var]);
                if ($value > 100) {
                    $warnings[] = sprintf(__('Large spacing value for %s may cause layout issues', 'gf-js-embed'), $var);
                }
            }
        }
        
        // Check for extremely small or large font sizes
        $font_size_vars = ['--gf-font-size-base', '--gf-font-size-small', '--gf-font-size-large', '--gf-font-size-title'];
        foreach ($font_size_vars as $var) {
            if (isset($variables[$var])) {
                $value = floatval($variables[$var]);
                if ($value < 10) {
                    $warnings[] = sprintf(__('Small font size for %s may affect readability', 'gf-js-embed'), $var);
                } elseif ($value > 72) {
                    $warnings[] = sprintf(__('Large font size for %s may cause layout issues', 'gf-js-embed'), $var);
                }
            }
        }
        
        return $warnings;
    }
    
    /**
     * Parse color value to RGB array
     */
    private function parse_color($color) {
        // Simple hex color parsing
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $color, $matches)) {
            return [
                'r' => hexdec(substr($matches[1], 0, 2)),
                'g' => hexdec(substr($matches[1], 2, 2)),
                'b' => hexdec(substr($matches[1], 4, 2))
            ];
        }
        
        // Simple RGB parsing
        if (preg_match('/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/', $color, $matches)) {
            return [
                'r' => intval($matches[1]),
                'g' => intval($matches[2]),
                'b' => intval($matches[3])
            ];
        }
        
        return null;
    }
    
    /**
     * Calculate contrast ratio between two colors
     */
    private function calculate_contrast_ratio($color1, $color2) {
        $l1 = $this->get_relative_luminance($color1);
        $l2 = $this->get_relative_luminance($color2);
        
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);
        
        return ($lighter + 0.05) / ($darker + 0.05);
    }
    
    /**
     * Get relative luminance of a color
     */
    private function get_relative_luminance($color) {
        $r = $color['r'] / 255;
        $g = $color['g'] / 255;
        $b = $color['b'] / 255;
        
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
    
    /**
     * Generate safe CSS from variables with additional security checks
     */
    public function generate_safe_css($variables) {
        $validation = $this->validate_theme_data(['variables' => $variables]);
        
        if (!$validation['valid']) {
            return new WP_Error('invalid_theme', implode(', ', $validation['errors']));
        }
        
        // Generate CSS with CSP-safe inline styles
        $css = $this->generate_css_variables($variables);
        
        // Remove any potential XSS vectors
        $css = preg_replace('/javascript:/i', '', $css);
        $css = preg_replace('/data:/i', '', $css);
        $css = preg_replace('/expression\(/i', '', $css);
        
        return $css;
    }
    
    /**
     * Get theme performance metrics
     */
    public function get_theme_performance_metrics($variables) {
        $metrics = [
            'css_size' => 0,
            'variable_count' => count($variables),
            'complexity_score' => 0,
            'warnings' => []
        ];
        
        $css = $this->generate_css_variables($variables);
        $metrics['css_size'] = strlen($css);
        
        // Calculate complexity score based on number of variables and their types
        $complex_types = ['shadow', 'gradient', 'transition'];
        foreach ($variables as $name => $value) {
            $definition = $this->get_variable_definition($name);
            if ($definition && in_array($definition['type'], $complex_types)) {
                $metrics['complexity_score'] += 2;
            } else {
                $metrics['complexity_score'] += 1;
            }
        }
        
        // Performance warnings
        if ($metrics['css_size'] > 5000) {
            $metrics['warnings'][] = __('Large CSS size may impact performance', 'gf-js-embed');
        }
        
        if ($metrics['variable_count'] > 50) {
            $metrics['warnings'][] = __('Many variables may impact browser performance', 'gf-js-embed');
        }
        
        return $metrics;
    }
}