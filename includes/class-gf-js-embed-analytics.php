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
    public static function track_submission($form_id, $domain = '') {
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
}