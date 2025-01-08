<?php
namespace AthleteDashboard\Services;

use AthleteDashboard\Config;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles cache warming operations for the Athlete Dashboard.
 */
class Cache_Warmer {
    /**
     * @var array Cache configuration
     */
    private $config;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->config = require_once dirname(__DIR__) . '/config/cache-config.php';
    }

    /**
     * Initialize the cache warmer.
     */
    public function init() {
        // Register cron schedules
        add_action('init', [$this, 'register_cron_schedules']);

        // Hook into user login for cache warming
        if ($this->config['warm_cache']['on_login']) {
            add_action('wp_login', [$this, 'warm_user_cache'], 10, 2);
        }

        // Hook into cron for periodic cache warming
        if ($this->config['warm_cache']['on_cron']) {
            add_action('athlete_dashboard_warm_cache', [$this, 'warm_priority_users_cache']);
        }
    }

    /**
     * Register cron schedules.
     */
    public function register_cron_schedules() {
        // Add custom cron schedules
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedules']);

        if (!wp_next_scheduled('athlete_dashboard_warm_cache')) {
            wp_schedule_event(time(), $this->config['cron']['warm_cache'], 'athlete_dashboard_warm_cache');
        }
    }

    /**
     * Add custom cron schedules.
     *
     * @param array $schedules Existing cron schedules
     * @return array Modified cron schedules
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['fifteen_minutes'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 minutes')
        ];
        return $schedules;
    }

    /**
     * Warm cache for a specific user.
     *
     * @param string  $user_login Username
     * @param WP_User $user       User object
     */
    public function warm_user_cache($user_login, $user = null) {
        if (!$user) {
            $user = get_user_by('login', $user_login);
        }

        if (!$user) {
            return;
        }

        $start_time = microtime(true);
        $metrics = [
            'users_processed' => 1,
            'items_warmed' => 0,
            'errors' => 0,
            'user_id' => $user->ID
        ];

        try {
            $items_before = $this->get_cached_items_count();
            
            $this->warm_profile_cache($user->ID);
            $this->warm_overview_cache($user->ID);
            
            $items_after = $this->get_cached_items_count();
            $metrics['items_warmed'] = $items_after - $items_before;
            
        } catch (\Exception $e) {
            $metrics['errors']++;
            error_log(sprintf(
                'Error warming cache for user %d: %s',
                $user->ID,
                $e->getMessage()
            ));
        }

        $metrics['duration'] = round(microtime(true) - $start_time, 2);
        $this->track_performance('user_login', $metrics);
    }

    /**
     * Track performance metrics for cache warming jobs.
     *
     * @param string $job_type Type of cache warming job (e.g., 'priority_users', 'user_login')
     * @param array  $metrics Performance metrics to log
     */
    private function track_performance($job_type, $metrics) {
        if (!$this->config['monitoring']['enabled']) {
            return;
        }

        $metrics['timestamp'] = time();
        $metrics['job_type'] = $job_type;

        // Store metrics in WordPress options with auto-cleanup
        $log_key = 'athlete_dashboard_cache_warming_log';
        $logs = get_option($log_key, []);
        
        // Add new log entry
        array_unshift($logs, $metrics);
        
        // Keep only last X days of logs
        $retention_days = $this->config['monitoring']['stats_retention'];
        $cutoff = time() - ($retention_days * DAY_IN_SECONDS);
        $logs = array_filter($logs, function($log) use ($cutoff) {
            return $log['timestamp'] >= $cutoff;
        });

        update_option($log_key, array_slice($logs, 0, 1000), false);

        // Log to error log if enabled
        if ($this->config['monitoring']['log_stats']) {
            error_log(sprintf(
                'Cache warming job completed - Type: %s, Duration: %ds, Users: %d, Items: %d',
                $job_type,
                $metrics['duration'],
                $metrics['users_processed'],
                $metrics['items_warmed']
            ));
        }
    }

    /**
     * Warm cache for priority users (users with recent activity).
     */
    public function warm_priority_users_cache() {
        if (!$this->config['warm_cache']['priority_users']) {
            return;
        }

        $start_time = microtime(true);
        $metrics = [
            'users_processed' => 0,
            'items_warmed' => 0,
            'errors' => 0
        ];

        try {
            $users = $this->get_priority_users();
            
            foreach ($users as $user_id) {
                try {
                    $user = get_user_by('id', $user_id);
                    if ($user) {
                        $items_before = $this->get_cached_items_count();
                        $this->warm_user_cache('', $user);
                        $items_after = $this->get_cached_items_count();
                        
                        $metrics['items_warmed'] += ($items_after - $items_before);
                        $metrics['users_processed']++;
                    }
                } catch (\Exception $e) {
                    $metrics['errors']++;
                    error_log(sprintf(
                        'Error warming cache for user %d: %s',
                        $user_id,
                        $e->getMessage()
                    ));
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf(
                'Error in cache warming job: %s',
                $e->getMessage()
            ));
        }

        $metrics['duration'] = round(microtime(true) - $start_time, 2);
        $this->track_performance('priority_users', $metrics);
    }

    /**
     * Warm profile cache for a user.
     *
     * @param int $user_id User ID
     */
    private function warm_profile_cache($user_id) {
        foreach ($this->config['warm_groups']['profile'] as $type) {
            $key = Cache_Service::generate_user_key($user_id, $type);
            Cache_Service::remember(
                $key,
                function() use ($user_id, $type) {
                    return $this->get_profile_data($user_id, $type);
                },
                $this->config['ttl']['profile']
            );
        }
    }

    /**
     * Warm overview cache for a user.
     *
     * @param int $user_id User ID
     */
    private function warm_overview_cache($user_id) {
        foreach ($this->config['warm_groups']['overview'] as $type) {
            $key = Cache_Service::generate_user_key($user_id, $type);
            Cache_Service::remember(
                $key,
                function() use ($user_id, $type) {
                    return $this->get_overview_data($user_id, $type);
                },
                $this->config['ttl']['overview']
            );
        }
    }

    /**
     * Get profile data for cache warming.
     *
     * @param int    $user_id User ID
     * @param string $type    Data type
     * @return array Profile data
     */
    private function get_profile_data($user_id, $type) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return [];
        }

        switch ($type) {
            case 'full':
                return [
                    'id' => $user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'firstName' => get_user_meta($user->ID, 'first_name', true),
                    'lastName' => get_user_meta($user->ID, 'last_name', true),
                    'meta' => $this->get_profile_data($user_id, 'meta')
                ];
            
            case 'meta':
                return [
                    'age' => (int)get_user_meta($user_id, 'age', true),
                    'height' => (float)get_user_meta($user_id, 'height', true),
                    'weight' => (float)get_user_meta($user_id, 'weight', true),
                    'medicalNotes' => get_user_meta($user_id, 'medical_notes', true)
                ];

            case 'preferences':
                return [
                    'notifications' => get_user_meta($user_id, 'notification_preferences', true),
                    'timezone' => get_user_meta($user_id, 'timezone', true),
                    'units' => get_user_meta($user_id, 'unit_preferences', true)
                ];

            default:
                return [];
        }
    }

    /**
     * Get overview data for cache warming.
     *
     * @param int    $user_id User ID
     * @param string $type    Data type
     * @return array Overview data
     */
    private function get_overview_data($user_id, $type) {
        switch ($type) {
            case 'stats':
                return [
                    'workouts_completed' => (int)get_user_meta($user_id, 'workouts_completed', true),
                    'active_programs' => count(get_user_meta($user_id, 'active_programs', true) ?: []),
                    'nutrition_score' => (int)get_user_meta($user_id, 'nutrition_score', true)
                ];

            case 'activity':
                return get_user_meta($user_id, 'recent_activity', true) ?: [];

            case 'goals':
                return get_user_meta($user_id, 'goals', true) ?: [];

            default:
                return [];
        }
    }

    /**
     * Get priority users for cache warming.
     *
     * @return array Array of user IDs
     */
    private function get_priority_users() {
        global $wpdb;

        $threshold = time() - $this->config['warm_cache']['activity_threshold'];
        $max_users = $this->config['warm_cache']['max_users_per_job'];

        // Get users with recent activity based on multiple factors
        $query = $wpdb->prepare(
            "SELECT DISTINCT u.ID, 
                GREATEST(
                    COALESCE(CAST(um_last_login.meta_value AS UNSIGNED), 0),
                    COALESCE(CAST(um_last_activity.meta_value AS UNSIGNED), 0)
                ) as last_active,
                COUNT(DISTINCT p.ID) as program_count
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um_last_login 
                ON u.ID = um_last_login.user_id 
                AND um_last_login.meta_key = 'last_login'
            LEFT JOIN {$wpdb->usermeta} um_last_activity 
                ON u.ID = um_last_activity.user_id 
                AND um_last_activity.meta_key = 'last_activity'
            LEFT JOIN {$wpdb->posts} p 
                ON u.ID = p.post_author 
                AND p.post_type = 'workout_program'
                AND p.post_status = 'publish'
            WHERE (
                (um_last_login.meta_value > %d) OR
                (um_last_activity.meta_value > %d)
            )
            GROUP BY u.ID
            ORDER BY last_active DESC, program_count DESC
            LIMIT %d",
            $threshold,
            $threshold,
            $max_users
        );

        $results = $wpdb->get_col($query);
        return array_map('intval', $results ?: []);
    }

    /**
     * Get the current count of cached items for the athlete dashboard.
     *
     * @return int Number of cached items
     */
    private function get_cached_items_count() {
        global $wpdb;
        
        // Count transients for our cache
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_' . Cache_Service::TRANSIENT_PREFIX) . '%'
        ));

        return (int)$count;
    }
} 