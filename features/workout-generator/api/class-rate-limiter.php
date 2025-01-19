<?php
/**
 * Rate Limiter for API requests
 */

class Rate_Limiter {
    private $key_prefix;
    private $limit;
    private $window;

    /**
     * Constructor
     *
     * @param string $key_prefix Prefix for cache keys
     * @param int    $limit      Maximum number of requests
     * @param int    $window     Time window in seconds
     */
    public function __construct($key_prefix, $limit, $window) {
        $this->key_prefix = $key_prefix;
        $this->limit = $limit;
        $this->window = $window;
    }

    /**
     * Check if current request is within rate limit
     */
    public function check_limit(): bool {
        $key = $this->get_cache_key();
        $count = $this->get_request_count($key);

        if ($count >= $this->limit) {
            return false;
        }

        $this->increment_count($key);
        return true;
    }

    /**
     * Get remaining requests in current window
     */
    public function get_remaining(): int {
        $count = $this->get_request_count($this->get_cache_key());
        return max(0, $this->limit - $count);
    }

    /**
     * Get rate limit
     */
    public function get_limit(): int {
        return $this->limit;
    }

    /**
     * Get time window in seconds
     */
    public function get_window(): int {
        return $this->window;
    }

    /**
     * Get cache key for current user/IP
     */
    private function get_cache_key(): string {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        
        return sprintf(
            '%s:%s:%s',
            $this->key_prefix,
            $user_id ? "user_{$user_id}" : "ip_{$ip}",
            floor(time() / $this->window)
        );
    }

    /**
     * Get current request count
     */
    private function get_request_count(string $key): int {
        return (int) get_transient($key) ?: 0;
    }

    /**
     * Increment request count
     */
    private function increment_count(string $key): void {
        $count = $this->get_request_count($key);
        set_transient($key, $count + 1, $this->window);
    }

    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip = '';
        
        // Check for proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',       // Nginx proxy
            'HTTP_CLIENT_IP',       // Client IP
            'HTTP_X_FORWARDED_FOR', // Forward headers
            'REMOTE_ADDR'           // Default
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                break;
            }
        }

        // If X-Forwarded-For contains multiple IPs, get the first one
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }

        // Validate IP format
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) ?: '0.0.0.0';
    }
} 