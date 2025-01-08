<?php
namespace AthleteDashboard\Admin;

use AthleteDashboard\Services\Cache_Monitor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard widget for cache statistics visualization.
 */
class Cache_Stats_Widget {
    /**
     * @var Cache_Monitor
     */
    private $cache_monitor;

    /**
     * Initialize the widget.
     */
    public function init() {
        // Only load for admin users
        if (!is_admin()) {
            return;
        }

        $this->cache_monitor = new Cache_Monitor();
        
        // Register the dashboard widget
        add_action('wp_dashboard_setup', [$this, 'register_widget']);
        
        // Add required scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // Register AJAX handler
        add_action('wp_ajax_refresh_cache_stats', [$this, 'handle_refresh_stats']);
    }

    /**
     * Register the dashboard widget.
     */
    public function register_widget() {
        wp_add_dashboard_widget(
            'athlete_dashboard_cache_stats',
            'Cache Performance',
            [$this, 'render_widget'],
            null,
            null,
            'normal',
            'high'
        );
    }

    /**
     * Enqueue required assets for the widget.
     */
    public function enqueue_assets($hook) {
        if ('index.php' !== $hook) {
            return;
        }

        // Enqueue Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '3.7.0',
            true
        );

        // Enqueue our widget script
        wp_enqueue_script(
            'athlete-dashboard-cache-stats',
            get_stylesheet_directory_uri() . '/assets/js/cache-stats-widget.js',
            ['chartjs'],
            '1.0.0',
            true
        );

        // Pass data to JavaScript
        wp_localize_script(
            'athlete-dashboard-cache-stats',
            'athleteDashboardCacheStats',
            [
                'stats' => $this->get_stats_for_chart(),
                'nonce' => wp_create_nonce('athlete_dashboard_cache_stats'),
            ]
        );

        // Enqueue widget styles
        wp_enqueue_style(
            'athlete-dashboard-cache-stats',
            get_stylesheet_directory_uri() . '/assets/css/cache-stats-widget.css',
            [],
            '1.0.0'
        );
    }

    /**
     * Render the dashboard widget.
     */
    public function render_widget() {
        $current_stats = $this->cache_monitor->get_current_stats();
        $hit_rate = number_format($current_stats['hit_rate'] * 100, 1);
        $avg_response = number_format($current_stats['avg_response_time'], 1);
        $memory_usage = size_format($current_stats['memory_usage'], 2);
        
        ?>
        <div class="cache-stats-widget">
            <div class="cache-stats-summary">
                <div class="stat-box">
                    <h4>Cache Hit Rate</h4>
                    <div class="stat-value <?php echo $hit_rate >= 80 ? 'good' : ($hit_rate >= 60 ? 'warning' : 'poor'); ?>">
                        <?php echo esc_html($hit_rate); ?>%
                    </div>
                </div>
                <div class="stat-box">
                    <h4>Avg Response Time</h4>
                    <div class="stat-value <?php echo $avg_response <= 200 ? 'good' : ($avg_response <= 500 ? 'warning' : 'poor'); ?>">
                        <?php echo esc_html($avg_response); ?>ms
                    </div>
                </div>
                <div class="stat-box">
                    <h4>Memory Usage</h4>
                    <div class="stat-value">
                        <?php echo esc_html($memory_usage); ?>
                    </div>
                </div>
            </div>

            <div class="cache-stats-charts">
                <div class="chart-container">
                    <canvas id="cacheHitRateChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="responseTimeChart"></canvas>
                </div>
            </div>

            <div class="cache-stats-footer">
                <p class="description">
                    Last updated: <?php echo esc_html(human_time_diff(time(), $current_stats['timestamp'])); ?> ago
                </p>
                <button type="button" class="button" id="refreshCacheStats">
                    Refresh Stats
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Get formatted stats for the charts.
     *
     * @return array
     */
    private function get_stats_for_chart() {
        $saved_stats = get_option('athlete_dashboard_cache_stats', []);
        $stats = array_slice($saved_stats, -24); // Last 24 data points

        $labels = [];
        $hit_rates = [];
        $response_times = [];

        foreach ($stats as $stat) {
            $labels[] = date('H:i', $stat['timestamp']);
            $hit_rates[] = round($stat['hit_rate'] * 100, 1);
            $response_times[] = round($stat['avg_response_time'], 1);
        }

        return [
            'labels' => $labels,
            'hitRates' => $hit_rates,
            'responseTimes' => $response_times,
        ];
    }

    /**
     * Handle AJAX request to refresh stats.
     */
    public function handle_refresh_stats() {
        check_ajax_referer('athlete_dashboard_cache_stats', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $current_stats = $this->cache_monitor->get_current_stats();
        $chart_stats = $this->get_stats_for_chart();

        wp_send_json_success([
            'current' => [
                'hit_rate' => $current_stats['hit_rate'],
                'avg_response_time' => $current_stats['avg_response_time'],
                'memory_usage' => size_format($current_stats['memory_usage'], 2),
                'timestamp' => $current_stats['timestamp']
            ],
            'labels' => $chart_stats['labels'],
            'hitRates' => $chart_stats['hitRates'],
            'responseTimes' => $chart_stats['responseTimes']
        ]);
    }
} 