<?php
/**
 * Internationalization handler class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_i18n {
    
    /**
     * Get translations for JavaScript
     */
    public static function get_translations($locale = null) {
        if (!$locale) {
            $locale = get_locale();
        }
        
        $translations = [
            'loading' => __('Loading form...', 'gf-js-embed'),
            'error' => __('Error loading form', 'gf-js-embed'),
            'submit' => __('Submit', 'gf-js-embed'),
            'submitting' => __('Submitting...', 'gf-js-embed'),
            'next' => __('Next', 'gf-js-embed'),
            'previous' => __('Previous', 'gf-js-embed'),
            'required' => __('This field is required', 'gf-js-embed'),
            'invalid_email' => __('Please enter a valid email', 'gf-js-embed'),
            'invalid_url' => __('Please enter a valid URL', 'gf-js-embed'),
            'invalid_number' => __('Please enter a valid number', 'gf-js-embed'),
            'invalid_phone' => __('Please enter a valid phone number', 'gf-js-embed'),
            'file_too_large' => __('File size exceeds limit', 'gf-js-embed'),
            'invalid_file_type' => __('File type not allowed', 'gf-js-embed'),
            'upload_failed' => __('File upload failed', 'gf-js-embed'),
            'form_error' => __('Please correct the errors below', 'gf-js-embed'),
            'network_error' => __('Network error. Please try again.', 'gf-js-embed'),
            'confirmation_default' => __('Thank you for your submission.', 'gf-js-embed'),
            'date_format' => __('mm/dd/yyyy', 'gf-js-embed'),
            'time_format' => __('12:00 AM', 'gf-js-embed'),
            'select_placeholder' => __('Please select...', 'gf-js-embed'),
            'multi_select_placeholder' => __('Select options...', 'gf-js-embed'),
            'choose_file' => __('Choose file', 'gf-js-embed'),
            'no_file_chosen' => __('No file chosen', 'gf-js-embed'),
            'remove_file' => __('Remove', 'gf-js-embed'),
            'page_x_of_y' => __('Page %1$s of %2$s', 'gf-js-embed')
        ];
        
        // Allow filtering of translations
        $translations = apply_filters('gf_js_embed_translations', $translations, $locale);
        
        return $translations;
    }
    
    /**
     * Get date format for JavaScript
     */
    public static function get_js_date_format($php_format = null) {
        if (!$php_format) {
            $php_format = get_option('date_format');
        }
        
        $replacements = [
            'd' => 'dd',
            'D' => 'D',
            'j' => 'd',
            'l' => 'DD',
            'N' => '',
            'S' => '',
            'w' => '',
            'z' => '',
            'W' => '',
            'F' => 'MM',
            'm' => 'mm',
            'M' => 'M',
            'n' => 'm',
            't' => '',
            'L' => '',
            'o' => 'yy',
            'Y' => 'yy',
            'y' => 'y',
            'a' => 'tt',
            'A' => 'TT',
            'B' => '',
            'g' => 'h',
            'G' => 'H',
            'h' => 'hh',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
            'u' => ''
        ];
        
        $js_format = strtr($php_format, $replacements);
        return $js_format;
    }
    
    /**
     * Get localized date picker settings
     */
    public static function get_datepicker_settings($locale = null) {
        if (!$locale) {
            $locale = get_locale();
        }
        
        // Get WordPress settings
        $start_of_week = get_option('start_of_week', 0);
        
        $settings = [
            'dateFormat' => self::get_js_date_format(),
            'firstDay' => $start_of_week,
            'dayNames' => [
                __('Sunday', 'gf-js-embed'),
                __('Monday', 'gf-js-embed'),
                __('Tuesday', 'gf-js-embed'),
                __('Wednesday', 'gf-js-embed'),
                __('Thursday', 'gf-js-embed'),
                __('Friday', 'gf-js-embed'),
                __('Saturday', 'gf-js-embed')
            ],
            'dayNamesShort' => [
                __('Sun', 'gf-js-embed'),
                __('Mon', 'gf-js-embed'),
                __('Tue', 'gf-js-embed'),
                __('Wed', 'gf-js-embed'),
                __('Thu', 'gf-js-embed'),
                __('Fri', 'gf-js-embed'),
                __('Sat', 'gf-js-embed')
            ],
            'dayNamesMin' => [
                __('Su', 'gf-js-embed'),
                __('Mo', 'gf-js-embed'),
                __('Tu', 'gf-js-embed'),
                __('We', 'gf-js-embed'),
                __('Th', 'gf-js-embed'),
                __('Fr', 'gf-js-embed'),
                __('Sa', 'gf-js-embed')
            ],
            'monthNames' => [
                __('January', 'gf-js-embed'),
                __('February', 'gf-js-embed'),
                __('March', 'gf-js-embed'),
                __('April', 'gf-js-embed'),
                __('May', 'gf-js-embed'),
                __('June', 'gf-js-embed'),
                __('July', 'gf-js-embed'),
                __('August', 'gf-js-embed'),
                __('September', 'gf-js-embed'),
                __('October', 'gf-js-embed'),
                __('November', 'gf-js-embed'),
                __('December', 'gf-js-embed')
            ],
            'monthNamesShort' => [
                __('Jan', 'gf-js-embed'),
                __('Feb', 'gf-js-embed'),
                __('Mar', 'gf-js-embed'),
                __('Apr', 'gf-js-embed'),
                __('May', 'gf-js-embed'),
                __('Jun', 'gf-js-embed'),
                __('Jul', 'gf-js-embed'),
                __('Aug', 'gf-js-embed'),
                __('Sep', 'gf-js-embed'),
                __('Oct', 'gf-js-embed'),
                __('Nov', 'gf-js-embed'),
                __('Dec', 'gf-js-embed')
            ],
            'prevText' => __('Previous', 'gf-js-embed'),
            'nextText' => __('Next', 'gf-js-embed'),
            'currentText' => __('Today', 'gf-js-embed'),
            'closeText' => __('Done', 'gf-js-embed'),
            'weekHeader' => __('Wk', 'gf-js-embed')
        ];
        
        return apply_filters('gf_js_embed_datepicker_settings', $settings, $locale);
    }
    
    /**
     * Get number format settings
     */
    public static function get_number_format($locale = null) {
        if (!$locale) {
            $locale = get_locale();
        }
        
        // Default to US format
        $decimal_separator = '.';
        $thousands_separator = ',';
        
        // Adjust based on locale
        if (strpos($locale, 'de_') === 0 || strpos($locale, 'fr_') === 0) {
            $decimal_separator = ',';
            $thousands_separator = '.';
        }
        
        return apply_filters('gf_js_embed_number_format', [
            'decimal_separator' => $decimal_separator,
            'thousands_separator' => $thousands_separator
        ], $locale);
    }
}