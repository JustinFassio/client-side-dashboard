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
        if (!wp_next_scheduled('athlete_dashboard_warm_cache')) {
            wp_schedule_event(time(), $this->config['cron']['warm_cache'], 'athlete_dashboard_warm_cache');
        }
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

        $this->warm_profile_cache($user->ID);
        $this->warm_overview_cache($user->ID);
    }

    /**
     * Warm cache for priority users (users with recent activity).
     */
    public function warm_priority_users_cache() {
        if (!$this->config['warm_cache']['priority_users']) {
            return;
        }

        $users = $this->get_priority_users();
        
        foreach ($users as $user_id) {
            $this->warm_user_cache('', get_user_by('id', $user_id));
        }
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

        // Get users with recent activity (last 24 hours)
        $active_users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'last_activity'
            AND meta_value > %d
            ORDER BY meta_value DESC
            LIMIT %d",
            time() - DAY_IN_SECONDS,
            $this->config['warm_cache']['max_users_per_job']
        ));

        return array_map('intval', $active_users);
    }
} 