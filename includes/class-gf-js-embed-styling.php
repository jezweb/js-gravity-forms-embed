<?php
/**
 * Styling handler class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Styling {
    
    /**
     * Get form CSS
     */
    public static function get_form_css($form_id, $settings) {
        $css = self::get_base_css();
        
        // Add theme CSS
        if (!empty($settings['theme'])) {
            $css .= self::get_theme_css($settings['theme']);
        }
        
        // Add custom theme variables if this is a custom theme
        if (!empty($settings['theme']) && self::is_custom_theme($settings['theme'])) {
            $css .= self::get_custom_theme_css($settings['theme']);
        }
        
        // Add custom CSS
        if (!empty($settings['custom_css'])) {
            // Scope custom CSS to the form container
            $custom_css = $settings['custom_css'];
            $custom_css = str_replace('{form_id}', $form_id, $custom_css);
            $css .= "\n/* Custom CSS */\n" . $custom_css;
        }
        
        // Minify CSS in production
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            $css = self::minify_css($css);
        }
        
        return $css;
    }
    
    /**
     * Check if theme is a valid theme (custom or predefined)
     */
    private static function is_custom_theme($theme_name) {
        $theme_manager = GF_JS_Embed_Theme_Manager::get_instance();
        
        // Check custom themes
        $custom_themes = $theme_manager->get_custom_themes();
        if (isset($custom_themes[$theme_name])) {
            return true;
        }
        
        // Check predefined themes
        $predefined_themes = $theme_manager->get_predefined_themes();
        foreach ($predefined_themes as $category => $category_data) {
            if (isset($category_data['themes'][$theme_name])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get custom theme CSS
     */
    private static function get_custom_theme_css($theme_name) {
        $theme_manager = GF_JS_Embed_Theme_Manager::get_instance();
        $css_variables = GF_JS_Embed_CSS_Variables::get_instance();
        
        // Check custom themes first
        $custom_themes = $theme_manager->get_custom_themes();
        if (isset($custom_themes[$theme_name])) {
            $theme_data = $custom_themes[$theme_name];
            return $css_variables->generate_css_variables($theme_data['variables']);
        }
        
        // Check predefined themes
        $predefined_themes = $theme_manager->get_predefined_themes();
        foreach ($predefined_themes as $category => $category_data) {
            if (isset($category_data['themes'][$theme_name])) {
                $theme_variables = array_merge(
                    $css_variables->get_default_variables(),
                    $category_data['themes'][$theme_name]['variables']
                );
                return $css_variables->generate_css_variables($theme_variables);
            }
        }
        
        return '';
    }
    
    /**
     * Get base CSS for all forms
     */
    private static function get_base_css() {
        return '
/* CSS Custom Properties (Variables) for Theme System */
:root {
    /* Colors - Primary */
    --gf-primary-color: #0073aa;
    --gf-primary-hover: #005a87;
    --gf-primary-focus: #004c75;
    
    /* Colors - Text */
    --gf-text-color: #333;
    --gf-text-muted: #666;
    --gf-text-light: #999;
    
    /* Colors - Background */
    --gf-bg-color: #fff;
    --gf-bg-alt: #f9f9f9;
    --gf-bg-dark: #2d2d2d;
    
    /* Colors - Border */
    --gf-border-color: #ddd;
    --gf-border-focus: #0073aa;
    --gf-border-error: #dc3232;
    
    /* Colors - State */
    --gf-success-color: #34a853;
    --gf-success-bg: #e6f4ea;
    --gf-error-color: #dc3232;
    --gf-error-bg: #ffeaea;
    --gf-warning-color: #f9a825;
    --gf-warning-bg: #fff3cd;
    
    /* Typography */
    --gf-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    --gf-font-size-base: 16px;
    --gf-font-size-small: 14px;
    --gf-font-size-large: 18px;
    --gf-font-size-title: 24px;
    --gf-font-weight-normal: 400;
    --gf-font-weight-medium: 500;
    --gf-font-weight-bold: 600;
    --gf-line-height-base: 1.5;
    
    /* Spacing */
    --gf-spacing-xs: 5px;
    --gf-spacing-sm: 10px;
    --gf-spacing-md: 15px;
    --gf-spacing-lg: 20px;
    --gf-spacing-xl: 30px;
    
    /* Border Radius */
    --gf-border-radius-sm: 4px;
    --gf-border-radius-md: 6px;
    --gf-border-radius-lg: 8px;
    --gf-border-radius-xl: 12px;
    --gf-border-radius-pill: 25px;
    
    /* Shadows */
    --gf-shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
    --gf-shadow-md: 0 2px 6px rgba(0,0,0,0.1);
    --gf-shadow-lg: 0 4px 12px rgba(0,0,0,0.15);
    --gf-shadow-focus: 0 0 0 2px rgba(0, 115, 170, 0.2);
    
    /* Transitions */
    --gf-transition-fast: 0.15s ease-in-out;
    --gf-transition-normal: 0.2s ease-in-out;
    --gf-transition-slow: 0.3s ease-in-out;
    
    /* Form specific */
    --gf-input-padding: 10px 12px;
    --gf-input-border-width: 1px;
    --gf-button-padding: 12px 24px;
    --gf-field-margin: 20px;
}

/* Base Styles for Gravity Forms JS Embed */
.gf-embedded-form {
    max-width: 100%;
    margin: 0 auto;
    font-family: var(--gf-font-family);
    color: var(--gf-text-color);
    background-color: var(--gf-bg-color);
}

.gf-embedded-form * {
    box-sizing: border-box;
}

.gf-form-title {
    font-size: var(--gf-font-size-title);
    font-weight: var(--gf-font-weight-bold);
    margin: 0 0 var(--gf-spacing-sm) 0;
    color: var(--gf-text-color);
}

.gf-form-description {
    font-size: var(--gf-font-size-base);
    color: var(--gf-text-muted);
    margin: 0 0 var(--gf-spacing-lg) 0;
}

.gf-field {
    /* margin-bottom handled by column layout */
}

.gf-field label {
    display: block;
    margin-bottom: var(--gf-spacing-xs);
    font-weight: var(--gf-font-weight-bold);
    color: var(--gf-text-color);
}

.gf-field input[type="text"],
.gf-field input[type="email"],
.gf-field input[type="tel"],
.gf-field input[type="number"],
.gf-field input[type="url"],
.gf-field input[type="date"],
.gf-field input[type="time"],
.gf-field textarea,
.gf-field select {
    width: 100%;
    padding: var(--gf-input-padding);
    font-size: var(--gf-font-size-base);
    border: var(--gf-input-border-width) solid var(--gf-border-color);
    border-radius: var(--gf-border-radius-sm);
    background-color: var(--gf-bg-color);
    transition: border-color var(--gf-transition-normal);
    color: var(--gf-text-color);
    font-family: var(--gf-font-family);
}

.gf-field input:focus,
.gf-field textarea:focus,
.gf-field select:focus {
    outline: none;
    border-color: var(--gf-border-focus);
    box-shadow: var(--gf-shadow-focus);
}

.gf-field textarea {
    min-height: 120px;
    resize: vertical;
}

.gf-field select {
    cursor: pointer;
}

/* Radio and Checkbox */
.gf-field-radio label,
.gf-field-checkbox label {
    display: flex;
    align-items: center;
    margin-bottom: var(--gf-spacing-sm);
    font-weight: var(--gf-font-weight-normal);
    cursor: pointer;
    color: var(--gf-text-color);
}

.gf-field-radio input[type="radio"],
.gf-field-checkbox input[type="checkbox"] {
    width: auto;
    margin-right: var(--gf-spacing-sm);
}

/* Required field indicator */
.gf-required {
    color: var(--gf-error-color);
    font-weight: var(--gf-font-weight-bold);
    margin-left: var(--gf-spacing-xs);
}

/* Field description */
.gf-field-description {
    font-size: var(--gf-font-size-small);
    color: var(--gf-text-muted);
    margin-top: var(--gf-spacing-xs);
}

/* Sub-labels */
.gf-sublabel {
    display: block;
    font-size: var(--gf-font-size-small);
    color: var(--gf-text-muted);
    font-weight: var(--gf-font-weight-normal);
    margin-top: var(--gf-spacing-xs);
}

/* Sub-label placement - above */
.gf-sublabel-above .gf-sublabel {
    margin-top: 0;
    margin-bottom: var(--gf-spacing-xs);
}

/* Complex field containers */
.ginput_complex {
    display: flex;
    flex-wrap: wrap;
    gap: var(--gf-spacing-sm);
}

.ginput_complex > span {
    flex: 1;
    min-width: 0;
}

/* Name field specific */
.gf-name-field .gf-name-part {
    display: flex;
    flex-direction: column;
}

.gf-name-field .name_prefix {
    flex: 0 0 20%;
}

/* Email confirmation field */
.gf-email-confirm-field {
    display: flex;
    flex-wrap: wrap;
    gap: var(--gf-spacing-sm);
}

.gf-email-confirm-field .ginput_left,
.gf-email-confirm-field .ginput_right {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

/* Ensure input containers do not have their own margins */
.ginput_container {
    margin: 0;
}

/* Field Column Layouts */
.gf-embedded-form .gf-fields {
    display: flex;
    flex-wrap: wrap;
    margin-left: calc(var(--gf-spacing-sm) * -1);
    margin-right: calc(var(--gf-spacing-sm) * -1);
}

.gf-embedded-form .gf-field {
    padding-left: var(--gf-spacing-sm);
    padding-right: var(--gf-spacing-sm);
    width: 100%;
    box-sizing: border-box;
}

/* Ensure proper spacing between rows */
.gf-embedded-form .gf-field:not(:last-child) {
    margin-bottom: var(--gf-field-margin);
}

/* Field size classes */
.gf-field.gfield_size_small,
.gf-field.gf-field-small {
    flex: 0 0 25%;
    max-width: 25%;
}

.gf-field.gfield_size_medium,
.gf-field.gf-field-medium {
    flex: 0 0 50%;
    max-width: 50%;
}

.gf-field.gfield_size_large,
.gf-field.gf-field-large {
    flex: 0 0 100%;
    max-width: 100%;
}

/* Multi-column layout classes */
.gf-field.gf_left_half {
    flex: 0 0 50%;
    max-width: 50%;
}

.gf-field.gf_right_half {
    flex: 0 0 50%;
    max-width: 50%;
}

.gf-field.gf_left_third {
    flex: 0 0 33.33333%;
    max-width: 33.33333%;
}

.gf-field.gf_middle_third {
    flex: 0 0 33.33333%;
    max-width: 33.33333%;
}

.gf-field.gf_right_third {
    flex: 0 0 33.33333%;
    max-width: 33.33333%;
}

.gf-field.gf_first_quarter {
    flex: 0 0 25%;
    max-width: 25%;
}

.gf-field.gf_second_quarter {
    flex: 0 0 25%;
    max-width: 25%;
}

.gf-field.gf_third_quarter {
    flex: 0 0 25%;
    max-width: 25%;
}

.gf-field.gf_fourth_quarter {
    flex: 0 0 25%;
    max-width: 25%;
}

/* List columns */
.gf-field.gf_list_2col > .ginput_container {
    width: 50%;
}

.gf-field.gf_list_3col > .ginput_container {
    width: 33.33333%;
}

.gf-field.gf_list_4col > .ginput_container {
    width: 25%;
}

/* Responsive breakpoints */
@media (max-width: 768px) {
    .gf-field.gfield_size_small,
    .gf-field.gf-field-small,
    .gf-field.gfield_size_medium,
    .gf-field.gf-field-medium,
    .gf-field.gf_left_half,
    .gf-field.gf_right_half,
    .gf-field.gf_left_third,
    .gf-field.gf_middle_third,
    .gf-field.gf_right_third,
    .gf-field.gf_first_quarter,
    .gf-field.gf_second_quarter,
    .gf-field.gf_third_quarter,
    .gf-field.gf_fourth_quarter {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .ginput_complex {
        flex-direction: column;
    }
    
    .ginput_complex > span {
        width: 100%;
    }
}

/* Error messages */
.gf-error-message {
    color: var(--gf-error-color);
    font-size: var(--gf-font-size-small);
    margin-top: var(--gf-spacing-xs);
}

.gf-field.gf-field-error input,
.gf-field.gf-field-error textarea,
.gf-field.gf-field-error select {
    border-color: var(--gf-border-error);
}

/* Submit button */
.gf-form-footer {
    margin-top: var(--gf-spacing-xl);
}

.gf-button {
    background: var(--gf-primary-color);
    color: white;
    padding: var(--gf-button-padding);
    font-size: var(--gf-font-size-base);
    font-weight: var(--gf-font-weight-bold);
    border: none;
    border-radius: var(--gf-border-radius-sm);
    cursor: pointer;
    transition: background-color var(--gf-transition-normal);
    font-family: var(--gf-font-family);
}

.gf-button:hover {
    background: var(--gf-primary-hover);
}

.gf-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Loading state */
.gf-loading {
    padding: calc(var(--gf-spacing-xl) * 1.5);
    text-align: center;
    color: var(--gf-text-muted);
}

.gf-loading:before {
    content: "";
    display: inline-block;
    width: var(--gf-spacing-lg);
    height: var(--gf-spacing-lg);
    margin-right: var(--gf-spacing-sm);
    border: 2px solid var(--gf-border-color);
    border-top-color: var(--gf-primary-color);
    border-radius: 50%;
    animation: gf-spin 0.8s linear infinite;
}

@keyframes gf-spin {
    to { transform: rotate(360deg); }
}

/* Confirmation message */
.gf-confirmation {
    padding: var(--gf-spacing-lg);
    background: var(--gf-success-bg);
    border: var(--gf-input-border-width) solid var(--gf-success-color);
    border-radius: var(--gf-border-radius-sm);
    color: var(--gf-success-color);
    text-align: center;
}

/* Multi-page forms */
.gf-page-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

.gf-page-step {
    display: flex;
    align-items: center;
    margin: 0 10px;
    color: #999;
}

.gf-page-step.active {
    color: #0073aa;
    font-weight: 600;
}

.gf-page-step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    margin-right: 8px;
    border: 2px solid #ddd;
    border-radius: 50%;
    font-size: 14px;
}

.gf-page-step.active .gf-page-step-number {
    background: #0073aa;
    border-color: #0073aa;
    color: white;
}

.gf-page-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

/* File upload */
.gf-field-fileupload {
    position: relative;
}

.gf-file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.gf-file-input-wrapper input[type="file"] {
    position: absolute;
    left: -9999px;
}

.gf-file-input-button {
    display: inline-block;
    padding: 8px 16px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.gf-file-input-button:hover {
    background: #e8e8e8;
}

/* List Fields */
.gf-list-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.gf-list-table th,
.gf-list-table td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: left;
}

.gf-list-table th {
    background: #f5f5f5;
    font-weight: 600;
}

.gf-list-input {
    width: 100%;
    border: none;
    padding: 4px;
    background: transparent;
}

.gf-list-input:focus {
    outline: 1px solid #0073aa;
}

.gf-list-actions {
    width: 40px;
    text-align: center;
}

.gf-list-delete-row {
    background: #dc3232;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
}

.gf-list-add-row {
    background: #0073aa;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.gf-list-add-row:hover {
    background: #005a87;
}

/* Signature Fields */
.gf-signature-container {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background: #fff;
}

.gf-signature-canvas {
    border: 1px dashed #ccc;
    cursor: crosshair;
    width: 100%;
    max-width: 400px;
    height: 200px;
    display: block;
}

.gf-signature-actions {
    margin-top: 10px;
    text-align: right;
}

.gf-signature-clear {
    background: #f0f0f0;
    border: 1px solid #ccc;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.gf-signature-clear:hover {
    background: #e8e8e8;
}

/* Calculation Fields */
.gf-calculation {
    background: #f9f9f9;
    font-weight: 600;
}

/* Enhanced Form Errors */
.gf-form-error {
    background: #ffeaea;
    border: 1px solid #dc3232;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
    color: #721c24;
}

.gf-form-error p {
    margin: 0;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 600px) {
    .gf-form-title {
        font-size: 20px;
    }
    
    .gf-button {
        width: 100%;
    }
    
    .gf-page-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .gf-page-buttons button {
        width: 100%;
    }
    
    .gf-list-table {
        font-size: 14px;
    }
    
    .gf-signature-canvas {
        height: 150px;
    }
}
        ';
    }
    
    /**
     * Get theme-specific CSS
     */
    private static function get_theme_css($theme) {
        $themes = [
            'minimal' => '
/* Minimal Theme */
.gf-embedded-form.theme-minimal {
    --gf-font-family: "Inter", -apple-system, sans-serif;
    --gf-border-color: #e0e0e0;
    --gf-border-focus: #333;
    --gf-primary-color: #333;
    --gf-primary-hover: #000;
    --gf-border-radius-sm: 0;
    --gf-shadow-focus: none;
}

.gf-embedded-form.theme-minimal .gf-field input,
.gf-embedded-form.theme-minimal .gf-field textarea,
.gf-embedded-form.theme-minimal .gf-field select {
    border: none;
    border-bottom: 2px solid var(--gf-border-color);
    border-radius: var(--gf-border-radius-sm);
    padding-left: 0;
    padding-right: 0;
    background: transparent;
}

.gf-embedded-form.theme-minimal .gf-field input:focus,
.gf-embedded-form.theme-minimal .gf-field textarea:focus,
.gf-embedded-form.theme-minimal .gf-field select:focus {
    border-bottom-color: var(--gf-border-focus);
    box-shadow: var(--gf-shadow-focus);
}

.gf-embedded-form.theme-minimal .gf-button {
    background: var(--gf-primary-color);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: var(--gf-font-size-small);
    border-radius: var(--gf-border-radius-sm);
}

.gf-embedded-form.theme-minimal .gf-button:hover {
    background: var(--gf-primary-hover);
}
            ',
            
            'rounded' => '
/* Rounded Theme */
.gf-embedded-form.theme-rounded {
    --gf-border-radius-sm: 25px;
    --gf-border-radius-md: 15px;
    --gf-input-padding: 12px 20px;
    --gf-button-padding: 14px 30px;
}

.gf-embedded-form.theme-rounded .gf-field input,
.gf-embedded-form.theme-rounded .gf-field textarea,
.gf-embedded-form.theme-rounded .gf-field select {
    border-radius: var(--gf-border-radius-sm);
    padding: var(--gf-input-padding);
}

.gf-embedded-form.theme-rounded .gf-button {
    border-radius: var(--gf-border-radius-sm);
    padding: var(--gf-button-padding);
}

.gf-embedded-form.theme-rounded .gf-confirmation {
    border-radius: var(--gf-border-radius-md);
}
            ',
            
            'material' => '
/* Material Theme */
.gf-field {
    position: relative;
    padding-top: 20px;
}

.gf-field label {
    position: absolute;
    top: 30px;
    left: 12px;
    transition: all 0.2s;
    pointer-events: none;
    color: #999;
    font-weight: normal;
}

.gf-field input:focus ~ label,
.gf-field input:not(:placeholder-shown) ~ label,
.gf-field textarea:focus ~ label,
.gf-field textarea:not(:placeholder-shown) ~ label {
    top: 0;
    left: 0;
    font-size: 12px;
    color: #0073aa;
}

.gf-field input,
.gf-field textarea {
    border: none;
    border-bottom: 1px solid #ddd;
    border-radius: 0;
    padding: 10px 0;
}

.gf-field input:focus,
.gf-field textarea:focus {
    border-bottom: 2px solid #0073aa;
    box-shadow: none;
}

.gf-button {
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.gf-button:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}
            ',
            
            'dark' => '
/* Dark Mode Theme */
.gf-embedded-form.theme-dark {
    background: #2d2d2d;
    color: #e0e0e0;
    padding: 30px;
    border-radius: 8px;
}

.gf-embedded-form.theme-dark .gf-form-title {
    color: #ffffff;
}

.gf-embedded-form.theme-dark .gf-form-description {
    color: #b0b0b0;
}

.gf-embedded-form.theme-dark .gf-field label {
    color: #e0e0e0;
}

.gf-embedded-form.theme-dark .gf-field input[type="text"],
.gf-embedded-form.theme-dark .gf-field input[type="email"],
.gf-embedded-form.theme-dark .gf-field input[type="tel"],
.gf-embedded-form.theme-dark .gf-field input[type="number"],
.gf-embedded-form.theme-dark .gf-field input[type="url"],
.gf-embedded-form.theme-dark .gf-field input[type="date"],
.gf-embedded-form.theme-dark .gf-field input[type="time"],
.gf-embedded-form.theme-dark .gf-field textarea,
.gf-embedded-form.theme-dark .gf-field select {
    background: #3a3a3a;
    border: 1px solid #4a4a4a;
    color: #e0e0e0;
}

.gf-embedded-form.theme-dark .gf-field input:focus,
.gf-embedded-form.theme-dark .gf-field textarea:focus,
.gf-embedded-form.theme-dark .gf-field select:focus {
    border-color: #4a9eff;
    box-shadow: 0 0 0 2px rgba(74, 158, 255, 0.2);
    background: #404040;
}

.gf-embedded-form.theme-dark .gf-field input::placeholder,
.gf-embedded-form.theme-dark .gf-field textarea::placeholder {
    color: #888;
}

.gf-embedded-form.theme-dark .gf-field-description {
    color: #a0a0a0;
}

.gf-embedded-form.theme-dark .gf-required {
    color: #ff6b6b;
}

.gf-embedded-form.theme-dark .gf-error-message {
    color: #ff6b6b;
}

.gf-embedded-form.theme-dark .gf-field.gf-field-error input,
.gf-embedded-form.theme-dark .gf-field.gf-field-error textarea,
.gf-embedded-form.theme-dark .gf-field.gf-field-error select {
    border-color: #ff6b6b;
    background: #3a2a2a;
}

.gf-embedded-form.theme-dark .gf-button {
    background: #4a9eff;
    color: white;
}

.gf-embedded-form.theme-dark .gf-button:hover {
    background: #3a8eef;
    box-shadow: 0 4px 12px rgba(74, 158, 255, 0.3);
}

.gf-embedded-form.theme-dark .gf-button:disabled {
    background: #4a4a4a;
    color: #888;
}

.gf-embedded-form.theme-dark .gf-confirmation {
    background: #2a3a2a;
    border-color: #4ade80;
    color: #4ade80;
}

/* Dark mode specific adjustments */
.gf-embedded-form.theme-dark .gf-field-radio label,
.gf-embedded-form.theme-dark .gf-field-checkbox label {
    color: #e0e0e0;
}

.gf-embedded-form.theme-dark .gf-field-radio input[type="radio"],
.gf-embedded-form.theme-dark .gf-field-checkbox input[type="checkbox"] {
    background: #3a3a3a;
    border-color: #4a4a4a;
}

.gf-embedded-form.theme-dark .gf-page-step {
    color: #888;
}

.gf-embedded-form.theme-dark .gf-page-step.active {
    color: #4a9eff;
}

.gf-embedded-form.theme-dark .gf-page-step-number {
    background: #3a3a3a;
    border-color: #4a4a4a;
    color: #888;
}

.gf-embedded-form.theme-dark .gf-page-step.active .gf-page-step-number {
    background: #4a9eff;
    border-color: #4a9eff;
    color: white;
}

.gf-embedded-form.theme-dark .gf-list-table {
    border-color: #4a4a4a;
}

.gf-embedded-form.theme-dark .gf-list-table th {
    background: #3a3a3a;
    border-color: #4a4a4a;
    color: #e0e0e0;
}

.gf-embedded-form.theme-dark .gf-list-table td {
    border-color: #4a4a4a;
}

.gf-embedded-form.theme-dark .gf-signature-canvas {
    background: #3a3a3a;
    border-color: #4a4a4a;
}

.gf-embedded-form.theme-dark .gf-loading {
    color: #b0b0b0;
}

.gf-embedded-form.theme-dark .gf-loading:before {
    border-color: #4a4a4a;
    border-top-color: #4a9eff;
}
            ',
            
            'bootstrap' => '
/* Bootstrap-style Theme */
.gf-embedded-form {
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.gf-form-title {
    font-size: 2rem;
    font-weight: 500;
    line-height: 1.2;
    margin-bottom: 0.5rem;
}

.gf-form-description {
    color: #6c757d;
    margin-bottom: 1.5rem;
}

.gf-field {
    margin-bottom: 1rem;
}

.gf-field label {
    display: inline-block;
    margin-bottom: 0.5rem;
    font-weight: 400;
}

.gf-field input[type="text"],
.gf-field input[type="email"],
.gf-field input[type="tel"],
.gf-field input[type="number"],
.gf-field input[type="url"],
.gf-field input[type="date"],
.gf-field input[type="time"],
.gf-field textarea,
.gf-field select {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    appearance: none;
    border-radius: 0.375rem;
    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.gf-field input:focus,
.gf-field textarea:focus,
.gf-field select:focus {
    color: #212529;
    background-color: #fff;
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
}

.gf-field-description {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #6c757d;
}

.gf-required {
    color: #dc3545;
}

.gf-error-message {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}

.gf-field.gf-field-error input,
.gf-field.gf-field-error textarea,
.gf-field.gf-field-error select {
    border-color: #dc3545;
}

.gf-field.gf-field-error input:focus,
.gf-field.gf-field-error textarea:focus,
.gf-field.gf-field-error select:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.25rem rgba(220,53,69,.25);
}

.gf-button {
    display: inline-block;
    font-weight: 400;
    line-height: 1.5;
    color: #fff;
    text-align: center;
    text-decoration: none;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
    background-color: #0d6efd;
    border: 1px solid #0d6efd;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    border-radius: 0.375rem;
    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.gf-button:hover {
    color: #fff;
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.gf-button:focus {
    color: #fff;
    background-color: #0b5ed7;
    border-color: #0a58ca;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(49,132,253,.5);
}

.gf-button:disabled {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
    opacity: 0.65;
}

.gf-confirmation {
    position: relative;
    padding: 1rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    color: #0f5132;
    background-color: #d1e7dd;
    border-color: #badbcc;
}

/* Bootstrap form check styles */
.gf-field-radio,
.gf-field-checkbox {
    padding-left: 0;
}

.gf-field-radio label,
.gf-field-checkbox label {
    display: block;
    margin-bottom: 0.5rem;
    padding-left: 1.5em;
    position: relative;
}

.gf-field-radio input[type="radio"],
.gf-field-checkbox input[type="checkbox"] {
    position: absolute;
    margin-top: 0.3rem;
    margin-left: -1.5em;
}
            ',
            
            'tailwind' => '
/* Tailwind-style Theme */
.gf-embedded-form {
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.gf-form-title {
    font-size: 1.875rem;
    line-height: 2.25rem;
    font-weight: 700;
    letter-spacing: -0.025em;
    color: #111827;
    margin-bottom: 0.5rem;
}

.gf-form-description {
    font-size: 1rem;
    line-height: 1.5rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.gf-field {
    margin-bottom: 1.5rem;
}

.gf-field label {
    display: block;
    font-size: 0.875rem;
    line-height: 1.25rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.gf-field input[type="text"],
.gf-field input[type="email"],
.gf-field input[type="tel"],
.gf-field input[type="number"],
.gf-field input[type="url"],
.gf-field input[type="date"],
.gf-field input[type="time"],
.gf-field textarea,
.gf-field select {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #111827;
    background-color: #fff;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-duration: 150ms;
}

.gf-field input:focus,
.gf-field textarea:focus,
.gf-field select:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.gf-field input::placeholder,
.gf-field textarea::placeholder {
    color: #9ca3af;
}

.gf-field-description {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #6b7280;
}

.gf-required {
    color: #ef4444;
}

.gf-error-message {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #ef4444;
}

.gf-field.gf-field-error input,
.gf-field.gf-field-error textarea,
.gf-field.gf-field-error select {
    border-color: #ef4444;
}

.gf-field.gf-field-error input:focus,
.gf-field.gf-field-error textarea:focus,
.gf-field.gf-field-error select:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.gf-button {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    font-weight: 500;
    color: #fff;
    background-color: #3b82f6;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-duration: 150ms;
    cursor: pointer;
}

.gf-button:hover {
    background-color: #2563eb;
}

.gf-button:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}

.gf-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.gf-confirmation {
    padding: 1rem;
    background-color: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 0.375rem;
    color: #166534;
}

/* Tailwind checkbox/radio styles */
.gf-field-radio label,
.gf-field-checkbox label {
    display: flex;
    align-items: center;
    font-weight: 400;
}

.gf-field-radio input[type="radio"],
.gf-field-checkbox input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    color: #3b82f6;
    border-color: #d1d5db;
    border-radius: 0.25rem;
    margin-right: 0.5rem;
    flex-shrink: 0;
}

.gf-field-radio input[type="radio"] {
    border-radius: 100%;
}

.gf-field-radio input[type="radio"]:focus,
.gf-field-checkbox input[type="checkbox"]:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
}
            ',
            
            'glass' => '
/* Glass/Glassmorphism Theme */
.gf-embedded-form {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

.gf-form-title {
    color: #333;
    text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
}

.gf-form-description {
    color: #555;
}

.gf-field label {
    color: #333;
    font-weight: 500;
}

.gf-field input[type="text"],
.gf-field input[type="email"],
.gf-field input[type="tel"],
.gf-field input[type="number"],
.gf-field input[type="url"],
.gf-field input[type="date"],
.gf-field input[type="time"],
.gf-field textarea,
.gf-field select {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #333;
    padding: 12px 16px;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.gf-field input:focus,
.gf-field textarea:focus,
.gf-field select:focus {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
    outline: none;
}

.gf-field input::placeholder,
.gf-field textarea::placeholder {
    color: rgba(0, 0, 0, 0.5);
}

.gf-button {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #333;
    font-weight: 600;
    padding: 12px 30px;
    border-radius: 25px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px 0 rgba(31, 38, 135, 0.2);
}

.gf-button:hover {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.2) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px 0 rgba(31, 38, 135, 0.3);
}

.gf-confirmation {
    background: rgba(52, 168, 83, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(52, 168, 83, 0.3);
    border-radius: 12px;
    color: #2d6a3d;
}

.gf-field-description {
    color: #666;
    font-size: 0.9em;
}

.gf-error-message {
    color: #d32f2f;
    background: rgba(211, 47, 47, 0.1);
    padding: 8px 12px;
    border-radius: 8px;
    margin-top: 8px;
}

/* Ensure glass effect works on various backgrounds */
@supports not (backdrop-filter: blur(10px)) {
    .gf-embedded-form {
        background: rgba(255, 255, 255, 0.9);
    }
    
    .gf-field input,
    .gf-field textarea,
    .gf-field select {
        background: rgba(255, 255, 255, 0.8);
    }
}
            ',
            
            'flat' => '
/* Flat Design Theme */
.gf-embedded-form {
    background: #f5f5f5;
    padding: 40px;
    border: 3px solid #333;
}

.gf-form-title {
    color: #333;
    font-size: 32px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: -1px;
    margin-bottom: 10px;
}

.gf-form-description {
    color: #666;
    font-size: 18px;
    margin-bottom: 30px;
}

.gf-field label {
    color: #333;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.gf-field input[type="text"],
.gf-field input[type="email"],
.gf-field input[type="tel"],
.gf-field input[type="number"],
.gf-field input[type="url"],
.gf-field input[type="date"],
.gf-field input[type="time"],
.gf-field textarea,
.gf-field select {
    background: #fff;
    border: 3px solid #333;
    border-radius: 0;
    padding: 15px;
    font-size: 16px;
    font-weight: 500;
    transition: border-color 0.2s;
}

.gf-field input:focus,
.gf-field textarea:focus,
.gf-field select:focus {
    outline: none;
    border-color: #e74c3c;
    background: #fff;
}

.gf-button {
    background: #333;
    color: #fff;
    border: none;
    padding: 15px 40px;
    font-size: 16px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 2px;
    border-radius: 0;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
}

.gf-button:hover {
    background: #e74c3c;
    transform: scale(1.05);
}

.gf-button:active {
    transform: scale(0.95);
}

.gf-required {
    color: #e74c3c;
    font-weight: 900;
}

.gf-error-message {
    background: #e74c3c;
    color: #fff;
    padding: 10px 15px;
    font-weight: 700;
    margin-top: 10px;
}

.gf-field.gf-field-error input,
.gf-field.gf-field-error textarea,
.gf-field.gf-field-error select {
    border-color: #e74c3c;
    border-width: 3px;
}

.gf-confirmation {
    background: #2ecc71;
    color: #fff;
    padding: 20px;
    font-weight: 700;
    font-size: 18px;
    text-align: center;
    border: none;
}

.gf-field-description {
    background: #ecf0f1;
    padding: 10px;
    margin-top: 5px;
    font-size: 14px;
    color: #7f8c8d;
}

/* Flat checkbox and radio */
.gf-field-radio input[type="radio"],
.gf-field-checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    border: 3px solid #333;
    background: #fff;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    position: relative;
    margin-right: 10px;
}

.gf-field-radio input[type="radio"] {
    border-radius: 50%;
}

.gf-field-radio input[type="radio"]:checked::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 10px;
    height: 10px;
    background: #333;
    border-radius: 50%;
}

.gf-field-checkbox input[type="checkbox"]:checked::after {
    content: "✓";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 16px;
    font-weight: 900;
    color: #333;
}
            ',
            
            'corporate' => '
/* Corporate Theme */
.gf-embedded-form {
    background: #ffffff;
    border: 1px solid #e0e0e0;
    padding: 35px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    font-family: Georgia, "Times New Roman", serif;
}

.gf-form-title {
    color: #1a237e;
    font-size: 28px;
    font-weight: 400;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #1a237e;
}

.gf-form-description {
    color: #616161;
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 25px;
}

.gf-field {
    margin-bottom: 25px;
}

.gf-field label {
    color: #1a237e;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    display: block;
}

.gf-field input[type="text"],
.gf-field input[type="email"],
.gf-field input[type="tel"],
.gf-field input[type="number"],
.gf-field input[type="url"],
.gf-field input[type="date"],
.gf-field input[type="time"],
.gf-field textarea,
.gf-field select {
    width: 100%;
    padding: 10px 15px;
    font-size: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 4px;
    background-color: #fafafa;
    transition: all 0.3s ease;
    font-family: inherit;
}

.gf-field input:focus,
.gf-field textarea:focus,
.gf-field select:focus {
    outline: none;
    border-color: #1a237e;
    background-color: #ffffff;
}

.gf-field-description {
    font-size: 13px;
    color: #757575;
    margin-top: 5px;
    font-style: italic;
}

.gf-required {
    color: #b71c1c;
}

.gf-error-message {
    color: #b71c1c;
    font-size: 14px;
    margin-top: 5px;
}

.gf-field.gf-field-error input,
.gf-field.gf-field-error textarea,
.gf-field.gf-field-error select {
    border-color: #b71c1c;
    background-color: #ffebee;
}

.gf-button {
    background: #1a237e;
    color: #ffffff;
    border: none;
    padding: 12px 35px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-family: inherit;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.gf-button:hover {
    background: #0d47a1;
}

.gf-button:disabled {
    background: #bdbdbd;
    cursor: not-allowed;
}

.gf-confirmation {
    background: #e8f5e9;
    border: 1px solid #4caf50;
    border-radius: 4px;
    padding: 20px;
    color: #1b5e20;
    text-align: center;
    font-size: 16px;
}

/* Corporate radio and checkbox styling */
.gf-field-radio label,
.gf-field-checkbox label {
    font-weight: 400;
    color: #424242;
}

.gf-field-radio input[type="radio"],
.gf-field-checkbox input[type="checkbox"] {
    margin-right: 8px;
}

/* Professional list styling */
.gf-list-table {
    border: 2px solid #e0e0e0;
}

.gf-list-table th {
    background: #f5f5f5;
    color: #1a237e;
    font-weight: 600;
    padding: 12px;
    border-bottom: 2px solid #e0e0e0;
}

.gf-list-table td {
    padding: 10px;
}

/* Page steps for multi-page forms */
.gf-page-steps {
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 20px;
    margin-bottom: 30px;
}

.gf-page-step {
    color: #9e9e9e;
}

.gf-page-step.active {
    color: #1a237e;
}

.gf-page-step-number {
    background: #f5f5f5;
    border: 2px solid #e0e0e0;
    color: #9e9e9e;
}

.gf-page-step.active .gf-page-step-number {
    background: #1a237e;
    border-color: #1a237e;
    color: #ffffff;
}
            '
        ];
        
        return $themes[$theme] ?? '';
    }
    
    /**
     * Minify CSS
     */
    private static function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = str_replace([' {', '{ ', ' }', '} ', ': ', ' :', '; ', ' ;'], ['{', '{', '}', '}', ':', ':', ';', ';'], $css);
        
        return trim($css);
    }
    
    /**
     * Get inline styles for specific form
     */
    public static function get_inline_styles($form_id, $settings) {
        $styles = [];
        
        // Primary color customization
        if (!empty($settings['primary_color'])) {
            $color = sanitize_hex_color($settings['primary_color']);
            $styles[] = "#gf-form-{$form_id} .gf-button { background-color: {$color}; }";
            $styles[] = "#gf-form-{$form_id} .gf-field input:focus { border-color: {$color}; }";
        }
        
        // Form width
        if (!empty($settings['form_width'])) {
            $width = intval($settings['form_width']);
            $styles[] = "#gf-form-{$form_id} { max-width: {$width}px; }";
        }
        
        return implode("\n", $styles);
    }
}