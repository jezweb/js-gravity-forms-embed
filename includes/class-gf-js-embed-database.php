<?php
/**
 * Database management class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Database {
    
    private static $instance = null;
    private static $db_version = '1.0';
    
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
        // Hook into activation
        register_activation_hook(GF_JS_EMBED_PLUGIN_FILE, [$this, 'create_tables']);
        
        // Check for updates
        add_action('init', [$this, 'check_db_version']);
    }
    
    /**
     * Check database version and update if needed
     */
    public function check_db_version() {
        $current_version = get_option('gf_js_embed_db_version', '0');
        
        if (version_compare($current_version, self::$db_version, '<')) {
            $this->create_tables();
            update_option('gf_js_embed_db_version', self::$db_version);
        }
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Views table
        $table_views = $wpdb->prefix . 'gf_js_embed_views';
        $sql_views = "CREATE TABLE $table_views (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id int(11) NOT NULL,
            session_id varchar(32) NOT NULL,
            domain varchar(255) NOT NULL,
            referer text,
            user_agent text,
            ip_address varchar(45),
            country varchar(2) DEFAULT NULL,
            device_type varchar(20) DEFAULT NULL,
            browser varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY domain (domain),
            KEY created_at (created_at),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        // Submissions table
        $table_submissions = $wpdb->prefix . 'gf_js_embed_submissions';
        $sql_submissions = "CREATE TABLE $table_submissions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id int(11) NOT NULL,
            entry_id int(11) DEFAULT NULL,
            session_id varchar(32) NOT NULL,
            domain varchar(255) NOT NULL,
            completion_time int(11) DEFAULT NULL,
            is_spam tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY domain (domain),
            KEY created_at (created_at),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        // Field interactions table
        $table_interactions = $wpdb->prefix . 'gf_js_embed_interactions';
        $sql_interactions = "CREATE TABLE $table_interactions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id int(11) NOT NULL,
            field_id varchar(50) NOT NULL,
            session_id varchar(32) NOT NULL,
            interaction_type varchar(20) NOT NULL,
            interaction_count int(11) DEFAULT 1,
            time_spent int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_field (form_id, field_id),
            KEY session_id (session_id),
            KEY interaction_type (interaction_type)
        ) $charset_collate;";
        
        // Field errors table
        $table_errors = $wpdb->prefix . 'gf_js_embed_errors';
        $sql_errors = "CREATE TABLE $table_errors (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id int(11) NOT NULL,
            field_id varchar(50) NOT NULL,
            session_id varchar(32) NOT NULL,
            error_type varchar(50) NOT NULL,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_field (form_id, field_id),
            KEY session_id (session_id),
            KEY error_type (error_type)
        ) $charset_collate;";
        
        // Page progression table (for multi-page forms)
        $table_progression = $wpdb->prefix . 'gf_js_embed_progression';
        $sql_progression = "CREATE TABLE $table_progression (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id int(11) NOT NULL,
            session_id varchar(32) NOT NULL,
            page_number int(11) NOT NULL,
            time_spent int(11) DEFAULT 0,
            completed tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_session (form_id, session_id),
            KEY page_number (page_number)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_views);
        dbDelta($sql_submissions);
        dbDelta($sql_interactions);
        dbDelta($sql_errors);
        dbDelta($sql_progression);
    }
    
    /**
     * Drop all tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'gf_js_embed_views',
            $wpdb->prefix . 'gf_js_embed_submissions',
            $wpdb->prefix . 'gf_js_embed_interactions',
            $wpdb->prefix . 'gf_js_embed_errors',
            $wpdb->prefix . 'gf_js_embed_progression'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('gf_js_embed_db_version');
    }
    
    /**
     * Insert view record
     */
    public static function insert_view($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'gf_js_embed_views',
            [
                'form_id' => $data['form_id'],
                'session_id' => $data['session_id'],
                'domain' => $data['domain'],
                'referer' => $data['referer'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'country' => $data['country'] ?? null,
                'device_type' => $data['device_type'] ?? null,
                'browser' => $data['browser'] ?? null
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Insert submission record
     */
    public static function insert_submission($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'gf_js_embed_submissions',
            [
                'form_id' => $data['form_id'],
                'entry_id' => $data['entry_id'] ?? null,
                'session_id' => $data['session_id'],
                'domain' => $data['domain'],
                'completion_time' => $data['completion_time'] ?? null,
                'is_spam' => $data['is_spam'] ?? 0
            ],
            ['%d', '%d', '%s', '%s', '%d', '%d']
        );
    }
    
    /**
     * Insert or update interaction record
     */
    public static function upsert_interaction($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'gf_js_embed_interactions';
        
        // Check if interaction exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE form_id = %d AND field_id = %s AND session_id = %s AND interaction_type = %s",
            $data['form_id'],
            $data['field_id'],
            $data['session_id'],
            $data['interaction_type']
        ));
        
        if ($existing) {
            // Update existing record
            return $wpdb->update(
                $table,
                [
                    'interaction_count' => $existing->interaction_count + 1,
                    'time_spent' => $existing->time_spent + ($data['time_spent'] ?? 0)
                ],
                ['id' => $existing->id],
                ['%d', '%d'],
                ['%d']
            );
        } else {
            // Insert new record
            return $wpdb->insert(
                $table,
                [
                    'form_id' => $data['form_id'],
                    'field_id' => $data['field_id'],
                    'session_id' => $data['session_id'],
                    'interaction_type' => $data['interaction_type'],
                    'interaction_count' => 1,
                    'time_spent' => $data['time_spent'] ?? 0
                ],
                ['%d', '%s', '%s', '%s', '%d', '%d']
            );
        }
    }
    
    /**
     * Insert error record
     */
    public static function insert_error($data) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'gf_js_embed_errors',
            [
                'form_id' => $data['form_id'],
                'field_id' => $data['field_id'],
                'session_id' => $data['session_id'],
                'error_type' => $data['error_type'],
                'error_message' => $data['error_message'] ?? null
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Insert or update page progression
     */
    public static function upsert_progression($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'gf_js_embed_progression';
        
        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE form_id = %d AND session_id = %s AND page_number = %d",
            $data['form_id'],
            $data['session_id'],
            $data['page_number']
        ));
        
        if ($existing) {
            // Update existing record
            return $wpdb->update(
                $table,
                [
                    'time_spent' => $existing->time_spent + ($data['time_spent'] ?? 0),
                    'completed' => $data['completed'] ?? $existing->completed
                ],
                ['id' => $existing->id],
                ['%d', '%d'],
                ['%d']
            );
        } else {
            // Insert new record
            return $wpdb->insert(
                $table,
                [
                    'form_id' => $data['form_id'],
                    'session_id' => $data['session_id'],
                    'page_number' => $data['page_number'],
                    'time_spent' => $data['time_spent'] ?? 0,
                    'completed' => $data['completed'] ?? 0
                ],
                ['%d', '%s', '%d', '%d', '%d']
            );
        }
    }
    
    /**
     * Get analytics data
     */
    public static function get_analytics($form_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        // Default to last 30 days
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        $views_table = $wpdb->prefix . 'gf_js_embed_views';
        $submissions_table = $wpdb->prefix . 'gf_js_embed_submissions';
        
        // Get views count
        $views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $views_table 
             WHERE form_id = %d AND DATE(created_at) BETWEEN %s AND %s",
            $form_id, $date_from, $date_to
        ));
        
        // Get unique visitors
        $unique_visitors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $views_table 
             WHERE form_id = %d AND DATE(created_at) BETWEEN %s AND %s",
            $form_id, $date_from, $date_to
        ));
        
        // Get submissions count
        $submissions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $submissions_table 
             WHERE form_id = %d AND DATE(created_at) BETWEEN %s AND %s",
            $form_id, $date_from, $date_to
        ));
        
        // Get average completion time
        $avg_completion_time = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(completion_time) FROM $submissions_table 
             WHERE form_id = %d AND completion_time > 0 AND DATE(created_at) BETWEEN %s AND %s",
            $form_id, $date_from, $date_to
        ));
        
        // Get views by domain
        $domains = $wpdb->get_results($wpdb->prepare(
            "SELECT domain, COUNT(*) as count FROM $views_table 
             WHERE form_id = %d AND DATE(created_at) BETWEEN %s AND %s 
             GROUP BY domain ORDER BY count DESC",
            $form_id, $date_from, $date_to
        ));
        
        // Get device types
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as count FROM $views_table 
             WHERE form_id = %d AND device_type IS NOT NULL AND DATE(created_at) BETWEEN %s AND %s 
             GROUP BY device_type",
            $form_id, $date_from, $date_to
        ));
        
        // Get browsers
        $browsers = $wpdb->get_results($wpdb->prepare(
            "SELECT browser, COUNT(*) as count FROM $views_table 
             WHERE form_id = %d AND browser IS NOT NULL AND DATE(created_at) BETWEEN %s AND %s 
             GROUP BY browser ORDER BY count DESC LIMIT 10",
            $form_id, $date_from, $date_to
        ));
        
        return [
            'views' => $views,
            'unique_visitors' => $unique_visitors,
            'submissions' => $submissions,
            'conversion_rate' => $views > 0 ? round(($submissions / $views) * 100, 2) : 0,
            'avg_completion_time' => round($avg_completion_time ?? 0),
            'domains' => $domains,
            'devices' => $devices,
            'browsers' => $browsers
        ];
    }
    
    /**
     * Get field interaction data
     */
    public static function get_field_interactions($form_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        $table = $wpdb->prefix . 'gf_js_embed_interactions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT field_id, interaction_type, 
                    SUM(interaction_count) as total_interactions,
                    AVG(time_spent) as avg_time_spent,
                    COUNT(DISTINCT session_id) as unique_users
             FROM $table 
             WHERE form_id = %d AND DATE(created_at) BETWEEN %s AND %s 
             GROUP BY field_id, interaction_type",
            $form_id, $date_from, $date_to
        ));
    }
    
    /**
     * Get field error data
     */
    public static function get_field_errors($form_id, $date_from = null, $date_to = null) {
        global $wpdb;
        
        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }
        
        $table = $wpdb->prefix . 'gf_js_embed_errors';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT field_id, error_type, COUNT(*) as error_count,
                    COUNT(DISTINCT session_id) as affected_users
             FROM $table 
             WHERE form_id = %d AND DATE(created_at) BETWEEN %s AND %s 
             GROUP BY field_id, error_type
             ORDER BY error_count DESC",
            $form_id, $date_from, $date_to
        ));
    }
    
    /**
     * Get time series data
     */
    public static function get_time_series($form_id, $metric = 'views', $days = 30) {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-$days days"));
        $date_to = date('Y-m-d');
        
        if ($metric === 'views') {
            $table = $wpdb->prefix . 'gf_js_embed_views';
        } else {
            $table = $wpdb->prefix . 'gf_js_embed_submissions';
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM $table 
             WHERE form_id = %d AND DATE(created_at) BETWEEN %s AND %s 
             GROUP BY DATE(created_at) 
             ORDER BY date ASC",
            $form_id, $date_from, $date_to
        ));
        
        // Fill in missing dates with zeros
        $data = [];
        $current_date = strtotime($date_from);
        $end_date = strtotime($date_to);
        
        while ($current_date <= $end_date) {
            $date_str = date('Y-m-d', $current_date);
            $data[$date_str] = 0;
            $current_date = strtotime('+1 day', $current_date);
        }
        
        // Populate with actual data
        foreach ($results as $result) {
            $data[$result->date] = (int) $result->count;
        }
        
        return $data;
    }
    
    /**
     * Clean old data
     */
    public static function clean_old_data($days_to_keep = 90) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d', strtotime("-$days_to_keep days"));
        
        $tables = [
            $wpdb->prefix . 'gf_js_embed_views',
            $wpdb->prefix . 'gf_js_embed_submissions',
            $wpdb->prefix . 'gf_js_embed_interactions',
            $wpdb->prefix . 'gf_js_embed_errors',
            $wpdb->prefix . 'gf_js_embed_progression'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table WHERE created_at < %s",
                $cutoff_date
            ));
        }
    }
}

// Initialize the database class
GF_JS_Embed_Database::get_instance();