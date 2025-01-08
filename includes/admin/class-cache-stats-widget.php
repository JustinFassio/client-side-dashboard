<?php
namespace AthleteDashboard\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard widget for displaying cache performance metrics.
 */
class Cache_Stats_Widget {
    /**
     * Initialize the dashboard widget.
     */
    public function init() {
        error_log('Cache Stats Widget: Initializing');
        if (is_admin()) {
            error_log('Cache Stats Widget: Admin area confirmed');
            add_action('load-index.php', [$this, 'setup_dashboard_widget']);
            error_log('Cache Stats Widget: Added load-index.php action');
        } else {
            error_log('Cache Stats Widget: Not in admin area');
        }
    }

    /**
     * Setup the dashboard widget.
     */
    public function setup_dashboard_widget() {
        error_log('Cache Stats Widget: Setting up dashboard widget');
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        error_log('Cache Stats Widget: Added wp_dashboard_setup and admin_enqueue_scripts actions');
    }

    /**
     * Add the dashboard widget.
     */
    public function add_dashboard_widget() {
        error_log('Cache Stats Widget: Adding dashboard widget');
        wp_add_dashboard_widget(
            'athlete_dashboard_cache_stats',
            __('Cache Performance Metrics', 'athlete-dashboard'),
            [$this, 'render_widget']
        );
        error_log('Cache Stats Widget: Dashboard widget added successfully');
    }

    /**
     * Enqueue required assets for the widget.
     *
     * @param string $hook The current admin page
     */
    public function enqueue_assets($hook) {
        error_log('Cache Stats Widget: Attempting to enqueue assets for hook: ' . $hook);
        
        if ('index.php' !== $hook) {
            error_log('Cache Stats Widget: Skipping asset enqueue - not on dashboard page');
            return;
        }

        $theme_version = wp_get_theme()->get('Version');
        $css_file = get_stylesheet_directory() . '/assets/css/admin.css';
        $js_file = get_stylesheet_directory() . '/assets/js/admin.js';

        error_log('Cache Stats Widget: Theme version: ' . $theme_version);
        error_log('Cache Stats Widget: CSS file path: ' . $css_file);
        error_log('Cache Stats Widget: JS file path: ' . $js_file);

        // Enqueue CSS if it exists
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'athlete-dashboard-admin',
                get_stylesheet_directory_uri() . '/assets/css/admin.css',
                [],
                $theme_version
            );
            error_log('Cache Stats Widget: CSS file enqueued successfully');
        } else {
            error_log('Cache Stats Widget: admin.css file not found at ' . $css_file);
        }

        // Enqueue Chart.js
        wp_enqueue_script(
            'athlete-dashboard-charts',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '4.4.0',
            true
        );
        error_log('Cache Stats Widget: Chart.js enqueued successfully');

        // Enqueue admin JS if it exists
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'athlete-dashboard-admin',
                get_stylesheet_directory_uri() . '/assets/js/admin.js',
                ['jquery', 'athlete-dashboard-charts'],
                $theme_version,
                true
            );
            error_log('Cache Stats Widget: Admin JS file enqueued successfully');
        } else {
            error_log('Cache Stats Widget: admin.js file not found at ' . $js_file);
        }
    }

    /**
     * Render the dashboard widget content.
     */
    public function render_widget() {
        $logs = $this->get_cache_logs();
        $stats = $this->calculate_stats($logs);
        
        ?>
        <div class="athlete-dashboard-cache-stats">
            <div class="cache-stats-summary">
                <div class="stat-box">
                    <h4><?php _e('Cache Hit Rate', 'athlete-dashboard'); ?></h4>
                    <div class="stat-value"><?php echo esc_html(number_format($stats['hit_rate'] * 100, 1)); ?>%</div>
                </div>
                <div class="stat-box">
                    <h4><?php _e('Avg Response Time', 'athlete-dashboard'); ?></h4>
                    <div class="stat-value"><?php echo esc_html(number_format($stats['avg_duration'], 2)); ?>s</div>
                </div>
                <div class="stat-box">
                    <h4><?php _e('Error Rate', 'athlete-dashboard'); ?></h4>
                    <div class="stat-value"><?php echo esc_html(number_format($stats['error_rate'] * 100, 1)); ?>%</div>
                </div>
            </div>

            <div class="cache-stats-chart">
                <canvas id="cachePerformanceChart"></canvas>
            </div>

            <div class="cache-stats-logs">
                <h4><?php _e('Recent Cache Jobs', 'athlete-dashboard'); ?></h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'athlete-dashboard'); ?></th>
                            <th><?php _e('Type', 'athlete-dashboard'); ?></th>
                            <th><?php _e('Duration', 'athlete-dashboard'); ?></th>
                            <th><?php _e('Users', 'athlete-dashboard'); ?></th>
                            <th><?php _e('Items', 'athlete-dashboard'); ?></th>
                            <th><?php _e('Errors', 'athlete-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($logs, 0, 5) as $log): ?>
                        <tr>
                            <td><?php echo esc_html(date('Y-m-d H:i:s', $log['timestamp'])); ?></td>
                            <td><?php echo esc_html($log['job_type']); ?></td>
                            <td><?php echo esc_html(number_format($log['duration'], 2)); ?>s</td>
                            <td><?php echo esc_html($log['users_processed']); ?></td>
                            <td><?php echo esc_html($log['items_warmed']); ?></td>
                            <td><?php echo esc_html($log['errors']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var ctx = document.getElementById('cachePerformanceChart').getContext('2d');
                var chartData = <?php echo wp_json_encode($this->prepare_chart_data($logs)); ?>;
                
                new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Get cache warming logs.
     *
     * @return array Cache logs
     */
    private function get_cache_logs() {
        return get_option('athlete_dashboard_cache_warming_log', []);
    }

    /**
     * Calculate statistics from cache logs.
     *
     * @param array $logs Cache logs
     * @return array Calculated statistics
     */
    private function calculate_stats($logs) {
        if (empty($logs)) {
            return [
                'hit_rate' => 0,
                'avg_duration' => 0,
                'error_rate' => 0
            ];
        }

        $total_duration = 0;
        $total_errors = 0;
        $total_items = 0;
        $total_attempts = 0;

        foreach ($logs as $log) {
            $total_duration += $log['duration'];
            $total_errors += $log['errors'];
            $total_items += $log['items_warmed'];
            $total_attempts++;
        }

        return [
            'hit_rate' => $total_items > 0 ? ($total_items - $total_errors) / $total_items : 0,
            'avg_duration' => $total_attempts > 0 ? $total_duration / $total_attempts : 0,
            'error_rate' => $total_attempts > 0 ? $total_errors / $total_attempts : 0
        ];
    }

    /**
     * Prepare data for the performance chart.
     *
     * @param array $logs Cache logs
     * @return array Chart data
     */
    private function prepare_chart_data($logs) {
        $logs = array_reverse(array_slice($logs, 0, 24)); // Last 24 entries
        
        $labels = [];
        $durations = [];
        $items = [];
        $errors = [];

        foreach ($logs as $log) {
            $labels[] = date('H:i', $log['timestamp']);
            $durations[] = $log['duration'];
            $items[] = $log['items_warmed'];
            $errors[] = $log['errors'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Duration (s)', 'athlete-dashboard'),
                    'data' => $durations,
                    'borderColor' => '#2271b1',
                    'fill' => false
                ],
                [
                    'label' => __('Items Warmed', 'athlete-dashboard'),
                    'data' => $items,
                    'borderColor' => '#46b450',
                    'fill' => false
                ],
                [
                    'label' => __('Errors', 'athlete-dashboard'),
                    'data' => $errors,
                    'borderColor' => '#dc3232',
                    'fill' => false
                ]
            ]
        ];
    }
} 