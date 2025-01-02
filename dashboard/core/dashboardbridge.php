<?php
namespace AthleteDashboard\Core;

if (!defined('ABSPATH')) {
    exit;
}

class DashboardBridge {
    private static $instance = null;
    private static $current_feature = null;
    private $debug_log = [];

    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'handle_feature_routing']);
        add_action('wp_footer', [$this, 'output_debug_log']);
    }

    public function register_query_vars() {
        global $wp;
        $wp->add_query_var('dashboard_feature');
        $this->log_debug('Registered dashboard_feature query var');
    }

    public function handle_feature_routing() {
        if (!is_page_template('dashboard/templates/dashboard.php')) {
            return;
        }

        $feature = get_query_var('dashboard_feature');
        $this->log_debug("Handling feature routing. Raw feature: " . ($feature ?: 'none'));

        if (!$feature) {
            $feature = 'overview'; // Default feature
            $this->log_debug("No feature specified, defaulting to: {$feature}");
        }

        $feature = sanitize_key($feature);
        $available_features = self::get_available_features();

        if (!array_key_exists($feature, $available_features)) {
            $this->log_debug("Invalid feature requested: {$feature}");
            wp_die(
                sprintf(
                    __('Invalid feature "%s". Available features: %s', 'athlete-dashboard'),
                    esc_html($feature),
                    implode(', ', array_keys($available_features))
                ),
                __('Invalid Feature', 'athlete-dashboard'),
                ['response' => 404]
            );
        }

        self::$current_feature = $feature;
        $this->log_debug("Feature set to: {$feature}");

        // Add feature-specific body class
        add_filter('body_class', function($classes) use ($feature) {
            $classes[] = "feature-{$feature}";
            return $classes;
        });
    }

    public static function get_current_feature() {
        if (self::$current_feature === null) {
            $feature = get_query_var('dashboard_feature', 'overview');
            self::$current_feature = sanitize_key($feature);
        }
        return self::$current_feature;
    }

    public static function get_available_features() {
        $default_features = [
            'overview' => __('Overview', 'athlete-dashboard'),
            'profile' => __('Profile', 'athlete-dashboard'),
            'workouts' => __('Workouts', 'athlete-dashboard'),
            'nutrition' => __('Nutrition', 'athlete-dashboard'),
        ];

        $features = apply_filters('athlete_dashboard_features', $default_features);
        
        // Ensure all features are properly sanitized
        return array_combine(
            array_map('sanitize_key', array_keys($features)),
            array_map('sanitize_text_field', $features)
        );
    }

    private function log_debug($message) {
        if (WP_DEBUG) {
            $this->debug_log[] = date('Y-m-d H:i:s') . ' - ' . $message;
            error_log('[Athlete Dashboard] ' . $message);
        }
    }

    public function output_debug_log() {
        if (WP_DEBUG && !empty($this->debug_log) && current_user_can('manage_options')) {
            echo '<!-- Athlete Dashboard Debug Log -->' . PHP_EOL;
            echo '<!--' . PHP_EOL;
            foreach ($this->debug_log as $log) {
                echo esc_html($log) . PHP_EOL;
            }
            echo '-->' . PHP_EOL;
        }
    }

    public static function get_feature_data($feature = null) {
        $feature = $feature ?: self::get_current_feature();
        if (!$feature) {
            return [];
        }

        $base_data = [
            'name' => $feature,
            'label' => self::get_available_features()[$feature] ?? $feature,
            'timestamp' => time(),
        ];

        return apply_filters("athlete_dashboard_{$feature}_data", $base_data);
    }
} 