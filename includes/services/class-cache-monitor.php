<?php
namespace AthleteDashboard\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles cache monitoring and alerts for the Athlete Dashboard.
 */
class Cache_Monitor {
    /**
     * @var array Cache configuration
     */
    private $config;

    /**
     * @var array Current monitoring stats
     */
    private $stats;

    /**
     * @var float Timestamp of monitoring start
     */
    private $start_time;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->config = require_once dirname(__DIR__) . '/config/cache-config.php';
        $this->start_time = microtime(true);
        $this->init_stats();
    }

    /**
     * Initialize monitoring.
     */
    public function init() {
        if (!$this->config['monitoring']['enabled']) {
            return;
        }

        // Monitor cache operations
        add_action('athlete_dashboard_cache_hit', [$this, 'record_hit']);
        add_action('athlete_dashboard_cache_miss', [$this, 'record_miss']);
        add_action('athlete_dashboard_cache_set', [$this, 'record_set']);
        add_action('athlete_dashboard_cache_delete', [$this, 'record_delete']);

        // Schedule stats logging
        if ($this->config['monitoring']['log_stats']) {
            add_action('init', [$this, 'schedule_stats_logging']);
            add_action('athlete_dashboard_log_cache_stats', [$this, 'log_stats']);
        }

        // Clean up old stats
        add_action('athlete_dashboard_cleanup_cache_stats', [$this, 'cleanup_old_stats']);
    }

    /**
     * Initialize stats array.
     */
    private function init_stats() {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'memory_usage' => 0,
            'response_times' => [],
        ];
    }

    /**
     * Schedule stats logging.
     */
    public function schedule_stats_logging() {
        if (!wp_next_scheduled('athlete_dashboard_log_cache_stats')) {
            wp_schedule_event(time(), 'hourly', 'athlete_dashboard_log_cache_stats');
        }

        if (!wp_next_scheduled('athlete_dashboard_cleanup_cache_stats')) {
            wp_schedule_event(time(), 'daily', 'athlete_dashboard_cleanup_cache_stats');
        }
    }

    /**
     * Record a cache hit.
     */
    public function record_hit() {
        if ($this->should_sample()) {
            $this->stats['hits']++;
            $this->check_thresholds();
        }
    }

    /**
     * Record a cache miss.
     */
    public function record_miss() {
        if ($this->should_sample()) {
            $this->stats['misses']++;
            $this->check_thresholds();
        }
    }

    /**
     * Record a cache set operation.
     *
     * @param string $key Cache key
     * @param mixed  $value Cached value
     */
    public function record_set($key, $value) {
        if ($this->should_sample()) {
            $this->stats['sets']++;
            $this->update_memory_usage();
        }
    }

    /**
     * Record a cache delete operation.
     */
    public function record_delete() {
        if ($this->should_sample()) {
            $this->stats['deletes']++;
            $this->update_memory_usage();
        }
    }

    /**
     * Record response time.
     */
    public function record_response_time() {
        if ($this->should_sample()) {
            $response_time = (microtime(true) - $this->start_time) * 1000;
            $this->stats['response_times'][] = $response_time;
            
            if (count($this->stats['response_times']) > 100) {
                array_shift($this->stats['response_times']);
            }

            $this->check_response_time($response_time);
        }
    }

    /**
     * Check if the current request should be sampled.
     *
     * @return bool Whether to sample this request
     */
    private function should_sample() {
        return mt_rand() / mt_getrandmax() < $this->config['monitoring']['sampling_rate'];
    }

    /**
     * Update memory usage stats.
     */
    private function update_memory_usage() {
        if (function_exists('memory_get_usage')) {
            $this->stats['memory_usage'] = memory_get_usage(true);
            $this->check_memory_usage();
        }
    }

    /**
     * Check cache performance thresholds and trigger alerts if needed.
     */
    private function check_thresholds() {
        $total_requests = $this->stats['hits'] + $this->stats['misses'];
        if ($total_requests < 100) {
            return; // Not enough data
        }

        $hit_rate = $this->stats['hits'] / $total_requests;
        $miss_rate = $this->stats['misses'] / $total_requests;

        if ($hit_rate < $this->config['monitoring']['alert_thresholds']['hit_rate']) {
            $this->trigger_alert('low_hit_rate', sprintf(
                'Cache hit rate is %.2f%%, below threshold of %.2f%%',
                $hit_rate * 100,
                $this->config['monitoring']['alert_thresholds']['hit_rate'] * 100
            ));
        }

        if ($miss_rate > $this->config['monitoring']['alert_thresholds']['miss_rate']) {
            $this->trigger_alert('high_miss_rate', sprintf(
                'Cache miss rate is %.2f%%, above threshold of %.2f%%',
                $miss_rate * 100,
                $this->config['monitoring']['alert_thresholds']['miss_rate'] * 100
            ));
        }
    }

    /**
     * Check memory usage and trigger alert if needed.
     */
    private function check_memory_usage() {
        $memory_limit = $this->get_memory_limit();
        if (!$memory_limit) {
            return;
        }

        $usage_ratio = $this->stats['memory_usage'] / $memory_limit;
        if ($usage_ratio > $this->config['monitoring']['alert_thresholds']['memory_usage']) {
            $this->trigger_alert('high_memory_usage', sprintf(
                'Cache memory usage is at %.2f%% of limit',
                $usage_ratio * 100
            ));
        }
    }

    /**
     * Check response time and trigger alert if needed.
     *
     * @param float $response_time Response time in milliseconds
     */
    private function check_response_time($response_time) {
        if ($response_time > $this->config['monitoring']['alert_thresholds']['response_time']) {
            $this->trigger_alert('high_response_time', sprintf(
                'Cache response time is %.2fms, above threshold of %.2fms',
                $response_time,
                $this->config['monitoring']['alert_thresholds']['response_time']
            ));
        }
    }

    /**
     * Trigger an alert through configured channels.
     *
     * @param string $type Alert type
     * @param string $message Alert message
     */
    private function trigger_alert($type, $message) {
        $alert_key = "cache_alert_{$type}";
        $last_alert = get_transient($alert_key);
        
        if ($last_alert) {
            return; // Alert cooldown still active
        }

        set_transient($alert_key, time(), $this->config['monitoring']['alert_cooldown']);

        if ($this->config['monitoring']['alert_channels']['email']) {
            $this->send_email_alert($type, $message);
        }

        if ($this->config['monitoring']['alert_channels']['slack']) {
            $this->send_slack_alert($type, $message);
        }

        if ($this->config['monitoring']['alert_channels']['admin_notice']) {
            $this->add_admin_notice($type, $message);
        }

        if ($this->config['monitoring']['alert_channels']['log']) {
            error_log(sprintf('[Athlete Dashboard Cache Alert] %s: %s', $type, $message));
        }
    }

    /**
     * Send an email alert.
     *
     * @param string $type Alert type
     * @param string $message Alert message
     */
    private function send_email_alert($type, $message) {
        $recipients = $this->config['monitoring']['alert_recipients']['email'];
        if (empty($recipients)) {
            return;
        }

        $subject = sprintf('[Athlete Dashboard] Cache Alert: %s', $type);
        $body = sprintf("Cache Performance Alert\n\nType: %s\nMessage: %s\n\nStats:\n%s",
            $type,
            $message,
            print_r($this->stats, true)
        );

        foreach ($recipients as $recipient) {
            wp_mail($recipient, $subject, $body);
        }
    }

    /**
     * Send a Slack alert.
     *
     * @param string $type Alert type
     * @param string $message Alert message
     */
    private function send_slack_alert($type, $message) {
        $webhook_url = $this->config['monitoring']['alert_recipients']['slack_webhook'];
        if (empty($webhook_url)) {
            return;
        }

        wp_remote_post($webhook_url, [
            'body' => wp_json_encode([
                'text' => sprintf("*Cache Performance Alert*\n\nType: %s\nMessage: %s\n\nStats:\n```%s```",
                    $type,
                    $message,
                    print_r($this->stats, true)
                )
            ])
        ]);
    }

    /**
     * Add an admin notice.
     *
     * @param string $type Alert type
     * @param string $message Alert message
     */
    private function add_admin_notice($type, $message) {
        add_action('admin_notices', function() use ($type, $message) {
            printf(
                '<div class="notice notice-error"><p><strong>Cache Alert:</strong> %s</p></div>',
                esc_html($message)
            );
        });
    }

    /**
     * Log current cache stats.
     */
    public function log_stats() {
        if (!$this->config['monitoring']['log_stats']) {
            return;
        }

        $stats = $this->get_current_stats();
        $this->save_stats($stats);
    }

    /**
     * Get current cache statistics.
     *
     * @return array Cache statistics
     */
    public function get_current_stats() {
        $total_requests = $this->stats['hits'] + $this->stats['misses'];
        $hit_rate = $total_requests > 0 ? $this->stats['hits'] / $total_requests : 0;
        
        $avg_response_time = !empty($this->stats['response_times']) 
            ? array_sum($this->stats['response_times']) / count($this->stats['response_times'])
            : 0;

        return [
            'timestamp' => time(),
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'hit_rate' => $hit_rate,
            'sets' => $this->stats['sets'],
            'deletes' => $this->stats['deletes'],
            'memory_usage' => $this->stats['memory_usage'],
            'avg_response_time' => $avg_response_time,
        ];
    }

    /**
     * Save stats to the database.
     *
     * @param array $stats Statistics to save
     */
    private function save_stats($stats) {
        $saved_stats = get_option('athlete_dashboard_cache_stats', []);
        $saved_stats[] = $stats;
        
        // Keep only recent stats based on retention period
        $retention_period = $this->config['monitoring']['stats_retention'] * DAY_IN_SECONDS;
        $saved_stats = array_filter($saved_stats, function($stat) use ($retention_period) {
            return $stat['timestamp'] > (time() - $retention_period);
        });

        update_option('athlete_dashboard_cache_stats', $saved_stats);
    }

    /**
     * Clean up old statistics.
     */
    public function cleanup_old_stats() {
        $saved_stats = get_option('athlete_dashboard_cache_stats', []);
        $retention_period = $this->config['monitoring']['stats_retention'] * DAY_IN_SECONDS;
        
        $saved_stats = array_filter($saved_stats, function($stat) use ($retention_period) {
            return $stat['timestamp'] > (time() - $retention_period);
        });

        update_option('athlete_dashboard_cache_stats', $saved_stats);
    }

    /**
     * Get PHP memory limit in bytes.
     *
     * @return int|null Memory limit in bytes or null if not determinable
     */
    private function get_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        if (!$memory_limit) {
            return null;
        }

        $unit = strtoupper(substr($memory_limit, -1));
        $value = (int)$memory_limit;

        switch ($unit) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }

        return $value;
    }
} 