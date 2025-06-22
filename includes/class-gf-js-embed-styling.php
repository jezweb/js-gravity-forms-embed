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
     * Get base CSS for all forms
     */
    private static function get_base_css() {
        return '
/* Base Styles for Gravity Forms JS Embed */
.gf-embedded-form {
    max-width: 100%;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.gf-embedded-form * {
    box-sizing: border-box;
}

.gf-form-title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 10px 0;
    color: #333;
}

.gf-form-description {
    font-size: 16px;
    color: #666;
    margin: 0 0 20px 0;
}

.gf-field {
    margin-bottom: 20px;
}

.gf-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
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
    padding: 10px 12px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #fff;
    transition: border-color 0.2s;
}

.gf-field input:focus,
.gf-field textarea:focus,
.gf-field select:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
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
    margin-bottom: 10px;
    font-weight: normal;
    cursor: pointer;
}

.gf-field-radio input[type="radio"],
.gf-field-checkbox input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}

/* Required field indicator */
.gf-required {
    color: #d63638;
    font-weight: 700;
    margin-left: 4px;
}

/* Field description */
.gf-field-description {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

/* Error messages */
.gf-error-message {
    color: #d63638;
    font-size: 14px;
    margin-top: 5px;
}

.gf-field.gf-field-error input,
.gf-field.gf-field-error textarea,
.gf-field.gf-field-error select {
    border-color: #d63638;
}

/* Submit button */
.gf-form-footer {
    margin-top: 30px;
}

.gf-button {
    background: #0073aa;
    color: white;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.gf-button:hover {
    background: #005a87;
}

.gf-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Loading state */
.gf-loading {
    padding: 40px;
    text-align: center;
    color: #666;
}

.gf-loading:before {
    content: "";
    display: inline-block;
    width: 20px;
    height: 20px;
    margin-right: 10px;
    border: 2px solid #ddd;
    border-top-color: #0073aa;
    border-radius: 50%;
    animation: gf-spin 0.8s linear infinite;
}

@keyframes gf-spin {
    to { transform: rotate(360deg); }
}

/* Confirmation message */
.gf-confirmation {
    padding: 20px;
    background: #e6f4ea;
    border: 1px solid #34a853;
    border-radius: 4px;
    color: #1a5f3f;
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
.gf-embedded-form {
    font-family: "Inter", -apple-system, sans-serif;
}

.gf-field input,
.gf-field textarea,
.gf-field select {
    border: none;
    border-bottom: 2px solid #e0e0e0;
    border-radius: 0;
    padding-left: 0;
    padding-right: 0;
    background: transparent;
}

.gf-field input:focus,
.gf-field textarea:focus,
.gf-field select:focus {
    border-bottom-color: #333;
    box-shadow: none;
}

.gf-button {
    background: #333;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 14px;
    border-radius: 0;
}

.gf-button:hover {
    background: #000;
}
            ',
            
            'rounded' => '
/* Rounded Theme */
.gf-field input,
.gf-field textarea,
.gf-field select {
    border-radius: 25px;
    padding: 12px 20px;
}

.gf-button {
    border-radius: 25px;
    padding: 14px 30px;
}

.gf-confirmation {
    border-radius: 15px;
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