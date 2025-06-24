<?php
/**
 * Analytics tracking class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Analytics {
    
    /**
     * Track form view
     */
    public static function track_view($form_id, $domain = '') {
        // Generate session ID if not exists
        $session_id = self::get_session_id();
        
        // Get browser info
        $browser_info = self::get_browser_info();
        
        // Insert into database
        GF_JS_Embed_Database::insert_view([
            'form_id' => $form_id,
            'session_id' => $session_id,
            'domain' => $domain ?: parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST),
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => self::get_client_ip(),
            'device_type' => $browser_info['device_type'],
            'browser' => $browser_info['browser']
        ]);
        
        // Keep legacy option-based tracking for backward compatibility (temporary)
        $stats_key = 'gf_js_embed_stats_' . $form_id;
        $stats = get_option($stats_key, self::get_default_stats());
        
        // Daily views
        $date = date('Y-m-d');
        $stats['views_daily'][$date] = ($stats['views_daily'][$date] ?? 0) + 1;
        
        // Total views
        $stats['total_views'] = ($stats['total_views'] ?? 0) + 1;
        
        // Domain tracking
        if ($domain) {
            $stats['domains'][$domain]['views'] = ($stats['domains'][$domain]['views'] ?? 0) + 1;
            $stats['domains'][$domain]['last_view'] = current_time('timestamp');
        }
        
        // Keep only last 90 days of daily data
        $stats['views_daily'] = array_slice($stats['views_daily'], -90, 90, true);
        
        update_option($stats_key, $stats);
    }
    
    /**
     * Track form submission
     */
    public static function track_submission($form_id, $domain = '', $entry_id = null, $completion_time = null) {
        // Generate session ID if not exists
        $session_id = self::get_session_id();
        
        // Insert into database
        GF_JS_Embed_Database::insert_submission([
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'session_id' => $session_id,
            'domain' => $domain ?: parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST),
            'completion_time' => $completion_time
        ]);
        
        // Keep legacy option-based tracking for backward compatibility (temporary)
        $stats_key = 'gf_js_embed_stats_' . $form_id;
        $stats = get_option($stats_key, self::get_default_stats());
        
        // Daily submissions
        $date = date('Y-m-d');
        $stats['submissions_daily'][$date] = ($stats['submissions_daily'][$date] ?? 0) + 1;
        
        // Total submissions
        $stats['total_submissions'] = ($stats['total_submissions'] ?? 0) + 1;
        
        // Domain tracking
        if ($domain) {
            $stats['domains'][$domain]['submissions'] = ($stats['domains'][$domain]['submissions'] ?? 0) + 1;
        }
        
        // Keep only last 90 days of daily data
        $stats['submissions_daily'] = array_slice($stats['submissions_daily'], -90, 90, true);
        
        update_option($stats_key, $stats);
    }
    
    /**
     * Get form analytics
     */
    public static function get_form_analytics($form_id) {
        $stats = get_option('gf_js_embed_stats_' . $form_id, self::get_default_stats());
        
        // Calculate conversion rate
        $conversion_rate = 0;
        if ($stats['total_views'] > 0) {
            $conversion_rate = round(($stats['total_submissions'] / $stats['total_views']) * 100, 2);
        }
        
        return [
            'total_views' => $stats['total_views'],
            'total_submissions' => $stats['total_submissions'],
            'conversion_rate' => $conversion_rate,
            'domains' => $stats['domains'],
            'views_daily' => $stats['views_daily'],
            'submissions_daily' => $stats['submissions_daily'],
            'last_7_days' => self::get_last_n_days_stats($stats, 7),
            'last_30_days' => self::get_last_n_days_stats($stats, 30)
        ];
    }
    
    /**
     * Get last N days statistics
     */
    private static function get_last_n_days_stats($stats, $days) {
        $result = [
            'views' => 0,
            'submissions' => 0,
            'dates' => []
        ];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $views = $stats['views_daily'][$date] ?? 0;
            $submissions = $stats['submissions_daily'][$date] ?? 0;
            
            $result['views'] += $views;
            $result['submissions'] += $submissions;
            $result['dates'][$date] = [
                'views' => $views,
                'submissions' => $submissions
            ];
        }
        
        return $result;
    }
    
    /**
     * Get all forms analytics summary
     */
    public static function get_all_forms_analytics() {
        $forms = GFAPI::get_forms();
        $summary = [];
        
        foreach ($forms as $form) {
            $analytics = self::get_form_analytics($form['id']);
            $summary[] = [
                'form_id' => $form['id'],
                'form_title' => $form['title'],
                'analytics' => $analytics
            ];
        }
        
        return $summary;
    }
    
    /**
     * Get default stats structure
     */
    private static function get_default_stats() {
        return [
            'total_views' => 0,
            'total_submissions' => 0,
            'views_daily' => [],
            'submissions_daily' => [],
            'domains' => []
        ];
    }
    
    /**
     * Clear analytics for a form
     */
    public static function clear_form_analytics($form_id) {
        delete_option('gf_js_embed_stats_' . $form_id);
    }
    
    /**
     * Export analytics data
     */
    public static function export_analytics($form_id, $format = 'csv') {
        $analytics = self::get_form_analytics($form_id);
        
        if ($format === 'csv') {
            $csv_data = "Date,Views,Submissions\n";
            
            foreach ($analytics['last_30_days']['dates'] as $date => $data) {
                $csv_data .= sprintf("%s,%d,%d\n", $date, $data['views'], $data['submissions']);
            }
            
            return $csv_data;
        } elseif ($format === 'json') {
            return json_encode($analytics, JSON_PRETTY_PRINT);
        }
        
        return $analytics;
    }
    
    /**
     * Track field interaction
     */
    public static function track_field_interaction($form_id, $field_id, $interaction_type, $time_spent = 0) {
        $session_id = self::get_session_id();
        
        GF_JS_Embed_Database::upsert_interaction([
            'form_id' => $form_id,
            'field_id' => $field_id,
            'session_id' => $session_id,
            'interaction_type' => $interaction_type,
            'time_spent' => $time_spent
        ]);
    }
    
    /**
     * Track field error
     */
    public static function track_field_error($form_id, $field_id, $error_type, $error_message = '') {
        $session_id = self::get_session_id();
        
        GF_JS_Embed_Database::insert_error([
            'form_id' => $form_id,
            'field_id' => $field_id,
            'session_id' => $session_id,
            'error_type' => $error_type,
            'error_message' => $error_message
        ]);
    }
    
    /**
     * Track page progression
     */
    public static function track_page_progression($form_id, $page_number, $time_spent = 0, $completed = false) {
        $session_id = self::get_session_id();
        
        GF_JS_Embed_Database::upsert_progression([
            'form_id' => $form_id,
            'session_id' => $session_id,
            'page_number' => $page_number,
            'time_spent' => $time_spent,
            'completed' => $completed
        ]);
    }
    
    /**
     * Get or generate session ID
     */
    private static function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['gf_js_embed_session_id'])) {
            $_SESSION['gf_js_embed_session_id'] = wp_generate_password(32, false);
        }
        
        return $_SESSION['gf_js_embed_session_id'];
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get browser and device info
     */
    private static function get_browser_info() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Device type detection
        $device_type = 'desktop';
        if (preg_match('/mobile/i', $user_agent)) {
            $device_type = 'mobile';
        } elseif (preg_match('/tablet/i', $user_agent)) {
            $device_type = 'tablet';
        }
        
        // Browser detection
        $browser = 'Other';
        if (preg_match('/firefox/i', $user_agent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/chrome/i', $user_agent) && !preg_match('/edge/i', $user_agent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari/i', $user_agent) && !preg_match('/chrome/i', $user_agent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edge/i', $user_agent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera|opr/i', $user_agent)) {
            $browser = 'Opera';
        } elseif (preg_match('/trident/i', $user_agent)) {
            $browser = 'Internet Explorer';
        }
        
        return [
            'device_type' => $device_type,
            'browser' => $browser
        ];
    }
    
    /**
     * Get enhanced analytics using database
     */
    public static function get_enhanced_analytics($form_id, $date_from = null, $date_to = null) {
        return GF_JS_Embed_Database::get_analytics($form_id, $date_from, $date_to);
    }
    
    /**
     * Get field interaction heatmap data
     */
    public static function get_field_heatmap($form_id, $date_from = null, $date_to = null) {
        $interactions = GF_JS_Embed_Database::get_field_interactions($form_id, $date_from, $date_to);
        $errors = GF_JS_Embed_Database::get_field_errors($form_id, $date_from, $date_to);
        
        // Combine interaction and error data
        $heatmap = [];
        
        foreach ($interactions as $interaction) {
            $field_id = $interaction->field_id;
            if (!isset($heatmap[$field_id])) {
                $heatmap[$field_id] = [
                    'interactions' => 0,
                    'avg_time' => 0,
                    'errors' => 0,
                    'error_rate' => 0
                ];
            }
            
            $heatmap[$field_id]['interactions'] += $interaction->total_interactions;
            $heatmap[$field_id]['avg_time'] = $interaction->avg_time_spent;
        }
        
        foreach ($errors as $error) {
            $field_id = $error->field_id;
            if (!isset($heatmap[$field_id])) {
                $heatmap[$field_id] = [
                    'interactions' => 0,
                    'avg_time' => 0,
                    'errors' => 0,
                    'error_rate' => 0
                ];
            }
            
            $heatmap[$field_id]['errors'] += $error->error_count;
        }
        
        // Calculate error rates
        foreach ($heatmap as $field_id => &$data) {
            if ($data['interactions'] > 0) {
                $data['error_rate'] = round(($data['errors'] / $data['interactions']) * 100, 2);
            }
        }
        
        return $heatmap;
    }
}