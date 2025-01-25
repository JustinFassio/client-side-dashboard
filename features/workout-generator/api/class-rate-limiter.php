<?php
/**
 * Rate Limiter for API requests
 */

namespace AthleteDashboard\Features\WorkoutGenerator\API;

class Rate_Limiter {
	private $key_prefix;
	private $user_id;
	private $limits = array(
		'foundation'     => array(
			'requests' => 60,
			'window'   => 3600,
		),
		'performance'    => array(
			'requests' => 120,
			'window'   => 3600,
		),
		'transformation' => array(
			'requests' => 180,
			'window'   => 3600,
		),
	);
	private $current_headers;

	/**
	 * Constructor
	 *
	 * @param string   $key_prefix Prefix for cache keys
	 * @param int|null $user_id    Optional user ID for per-user limits
	 */
	public function __construct( $key_prefix = 'rate_limit', $user_id = null ) {
		$this->key_prefix = $key_prefix;
		$this->user_id    = $user_id;
	}

	/**
	 * Check if current request is within rate limit
	 */
	public function check_limit(): bool {
		$key   = $this->get_cache_key();
		$count = $this->get_request_count( $key );
		$limit = $this->get_user_limit();

		error_log(
			sprintf(
				'Rate Limiter: Checking limit - Count: %d, Limit: %d, Key: %s',
				$count,
				$limit['requests'],
				$key
			)
		);

		// Store the current rate limit state
		$this->current_headers = array(
			'X-RateLimit-Limit'     => $limit['requests'],
			'X-RateLimit-Remaining' => max( 0, $limit['requests'] - $count ),
			'X-RateLimit-Reset'     => $this->get_window_reset_time(),
		);

		if ( $count >= $limit['requests'] ) {
			error_log( 'Rate Limiter: Limit exceeded' );
			return false;
		}

		$this->increment_count( $key, $limit['window'] );
		error_log( 'Rate Limiter: Request allowed, new count: ' . ( $count + 1 ) );
		return true;
	}

	/**
	 * Get remaining requests in current window
	 */
	public function get_remaining(): int {
		$key       = $this->get_cache_key();
		$count     = $this->get_request_count( $key );
		$limit     = $this->get_user_limit();
		$remaining = max( 0, $limit['requests'] - $count );

		error_log(
			sprintf(
				'Rate Limiter: Remaining requests - Count: %d, Limit: %d, Remaining: %d',
				$count,
				$limit['requests'],
				$remaining
			)
		);

		return $remaining;
	}

	/**
	 * Get rate limit for current user
	 */
	public function get_user_limit(): array {
		$tier = $this->get_user_tier();
		error_log( 'Rate Limiter: User tier: ' . $tier );
		return $this->limits[ $tier ];
	}

	/**
	 * Get time until rate limit window resets
	 */
	public function get_window_reset_time(): int {
		$window               = $this->get_user_limit()['window'];
		$current_window_start = floor( time() / $window ) * $window;
		$reset_time           = $current_window_start + $window;

		error_log(
			sprintf(
				'Rate Limiter: Window reset - Current: %d, Window: %d, Reset: %d',
				time(),
				$window,
				$reset_time
			)
		);

		return $reset_time;
	}

	/**
	 * Get rate limit headers
	 */
	public function get_rate_limit_headers(): array {
		if ( isset( $this->current_headers ) ) {
			return $this->current_headers;
		}

		$limit   = $this->get_user_limit();
		$headers = array(
			'X-RateLimit-Limit'     => $limit['requests'],
			'X-RateLimit-Remaining' => $this->get_remaining(),
			'X-RateLimit-Reset'     => $this->get_window_reset_time(),
		);

		error_log( 'Rate Limiter: Generated headers - ' . print_r( $headers, true ) );
		return $headers;
	}

	/**
	 * Get user's tier
	 */
	private function get_user_tier(): string {
		if ( $this->user_id ) {
			$stored_tier = get_user_meta( $this->user_id, 'athlete_dashboard_tier', true );
			if ( $stored_tier && isset( $this->limits[ $stored_tier ] ) ) {
				error_log( 'Rate Limiter: Using stored tier: ' . $stored_tier );
				return $stored_tier;
			}
		}

		$tier       = apply_filters( 'athlete_dashboard_get_user_tier', 'foundation', $this->user_id );
		$final_tier = isset( $this->limits[ $tier ] ) ? $tier : 'foundation';
		error_log( 'Rate Limiter: Using tier: ' . $final_tier );
		return $final_tier;
	}

	/**
	 * Get identifier for rate limiting (user ID or hashed IP)
	 */
	private function get_identifier(): string {
		if ( $this->user_id ) {
			return "user_{$this->user_id}";
		}
		$ip = $this->get_client_ip();
		return 'ip_' . md5( $ip );
	}

	/**
	 * Get cache key for current user/IP
	 */
	private function get_cache_key(): string {
		$identifier   = $this->get_identifier();
		$window       = $this->get_user_limit()['window'];
		$window_start = floor( time() / $window ) * $window;

		$key = sprintf(
			'%s:%s:%d',
			$this->key_prefix,
			$identifier,
			$window_start
		);

		error_log( 'Rate Limiter: Generated cache key: ' . $key );
		return $key;
	}

	/**
	 * Get current request count
	 */
	private function get_request_count( string $key ): int {
		$count = get_transient( $key );
		$count = $count !== false ? (int) $count : 0;
		error_log( 'Rate Limiter: Current count for key ' . $key . ': ' . $count );
		return $count;
	}

	/**
	 * Increment the request count
	 */
	private function increment_count( string $key, int $window ): void {
		$count     = $this->get_request_count( $key );
		$new_count = $count + 1;
		set_transient( $key, $new_count, $window );
		error_log( 'Rate Limiter: Incremented count for key ' . $key . ' to ' . $new_count );
	}

	/**
	 * Get client IP address
	 */
	private function get_client_ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

		// Check for proxy headers
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded_ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip            = trim( $forwarded_ips[0] );
		}

		return $ip;
	}

	/**
	 * Update user's tier and reset their rate limit counters
	 */
	public function update_user_tier( string $new_tier ): bool {
		error_log( 'Rate Limiter: Attempting to update tier to: ' . $new_tier );

		if ( ! isset( $this->limits[ $new_tier ] ) ) {
			error_log( 'Rate Limiter: Invalid tier: ' . $new_tier );
			return false;
		}

		if ( $this->user_id ) {
			error_log( 'Rate Limiter: Updating tier for user ' . $this->user_id );
			update_user_meta( $this->user_id, 'athlete_dashboard_tier', $new_tier );

			// Reset counters for this user
			$key = $this->get_cache_key();
			delete_transient( $key );
			error_log( 'Rate Limiter: Reset counters for key: ' . $key );
		}

		return true;
	}
}
