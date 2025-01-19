<?php
/**
 * Rate Limiter for API requests
 */

namespace AthleteDashboard\Features\WorkoutGenerator\API;

class Rate_Limiter {
    private $key_prefix;
    private $user_id;
    private $limits = [
        'foundation' => ['requests' => 60, 'window' => 3600],
        'performance' => ['requests' => 120, 'window' => 3600],
        'transformation' => ['requests' => 180, 'window' => 3600]
    ];

    /**
     * Constructor
     *
     * @param string   $key_prefix Prefix for cache keys
     * @param int|null $user_id    Optional user ID for per-user limits
     */
    public function __construct($key_prefix = 'rate_limit', $user_id = null) {
        $this->key_prefix = $key_prefix;
        $this->user_id = $user_id;
    }

    /**
     * Check if current request is within rate limit
     */
    public function check_limit(): bool {
        $key = $this->get_cache_key();
        $count = $this->get_request_count($key);
        $limit = $this->get_user_limit();

        if ($count >= $limit['requests']) {
            return false;
        }

        $this->increment_count($key);
        return true;
    }

    /**
     * Get remaining requests in current window
     */
    public function get_remaining(): int {
        $key = $this->get_cache_key();
        $count = $this->get_request_count($key);
        $limit = $this->get_user_limit();
        return max(0, $limit['requests'] - $count);
    }

    /**
     * Get rate limit for current user
     */
    public function get_user_limit(): array {
        // Get user's tier, defaulting to foundation
        $tier = apply_filters('athlete_dashboard_get_user_tier', 'foundation', $this->user_id);
        
        // Ensure we have a valid tier
        if (!isset($this->limits[$tier])) {
            $tier = 'foundation';
        }
        
        return $this->limits[$tier];
    }

    /**
     * Get time until rate limit window resets
     */
    public function get_window_reset_time(): int {
        $window = $this->get_user_limit()['window'];
        return $window - (time() % $window);
    }

    /**
     * Get rate limit headers
     */
    public function get_rate_limit_headers(): array {
        $limit = $this->get_user_limit();
        return [
            'X-RateLimit-Limit' => $limit['requests'],
            'X-RateLimit-Remaining' => $this->get_remaining(),
            'X-RateLimit-Reset' => $this->get_window_reset_time()
        ];
    }

    /**
     * Get cache key for current user/IP
     */
    private function get_cache_key(): string {
        $ip = $this->get_client_ip();
        
        // Use user ID if available, otherwise use IP
        $identifier = $this->user_id ? "user_{$this->user_id}" : "ip_{$ip}";
        
        $window = $this->get_user_limit()['window'];
        $window_start = floor(time() / $window) * $window;
        
        return sprintf(
            '%s:%s:%d',
            $this->key_prefix,
            $identifier,
            $window_start
        );
    }

    /**
     * Get current request count
     */
    private function get_request_count(string $key): int {
        $count = get_transient($key);
        return $count !== false ? (int) $count : 0;
    }

    /**
     * Increment request count
     */
    private function increment_count(string $key): void {
        $count = $this->get_request_count($key);
        $window = $this->get_user_limit()['window'];
        $window_start = floor(time() / $window) * $window;
        $expiration = $window_start + $window - time();
        
        // Delete old transient first to ensure clean state
        delete_transient($key);
        
        // Set new count
        set_transient($key, $count + 1, $expiration);
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

        // During testing, return default IP for invalid addresses
        if (defined('PHPUNIT_RUNNING') && !filter_var($ip, FILTER_VALIDATE_IP)) {
            return '0.0.0.0';
        }

        return $ip;
    }
} 