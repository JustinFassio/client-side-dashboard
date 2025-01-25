<?php
namespace AthleteDashboard\RestApi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rate_Limiter {
	/**
	 * Default rate limit settings
	 */
	const DEFAULT_LIMIT  = 100; // requests per window
	const DEFAULT_WINDOW = 3600; // 1 hour in seconds
	const GLOBAL_LIMIT   = 1000; // global requests per window

	/**
	 * Check if the request should be rate limited
	 *
	 * @param int    $user_id      The user ID to check
	 * @param string $endpoint     The endpoint being accessed
	 * @param array  $custom_rules Optional custom rules for this endpoint
	 * @return bool|WP_Error True if allowed, WP_Error if limited
	 */
	public static function check_rate_limit( $user_id, $endpoint, $custom_rules = array() ) {
		// First check global rate limit
		$global_result = self::check_global_limit( $user_id );
		if ( is_wp_error( $global_result ) ) {
			return $global_result;
		}

		// Get rate limit settings
		$limit  = $custom_rules['limit'] ?? self::DEFAULT_LIMIT;
		$window = $custom_rules['window'] ?? self::DEFAULT_WINDOW;

		// Generate unique key for this user and endpoint
		$transient_key = "rate_limit_{$user_id}_{$endpoint}";

		// Get current count
		$count = get_transient( $transient_key );

		if ( false === $count ) {
			// First request in window
			set_transient( $transient_key, 1, $window );
			return true;
		}

		if ( $count >= $limit ) {
			$retry_after = self::get_retry_after( $transient_key );
			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					__( 'Rate limit exceeded for %s. Please try again later.', 'athlete-dashboard' ),
					$endpoint
				),
				array(
					'status'      => 429,
					'retry_after' => $retry_after,
					'endpoint'    => $endpoint,
				)
			);
		}

		// Increment counter atomically using a lock
		self::increment_counter( $transient_key, $count, $window );
		return true;
	}

	/**
	 * Check global rate limit across all endpoints
	 *
	 * @param int $user_id The user ID to check
	 * @return bool|WP_Error True if allowed, WP_Error if limited
	 */
	private static function check_global_limit( $user_id ) {
		$global_key = "rate_limit_{$user_id}_global";
		$count      = get_transient( $global_key );

		if ( false === $count ) {
			set_transient( $global_key, 1, self::DEFAULT_WINDOW );
			return true;
		}

		if ( $count >= self::GLOBAL_LIMIT ) {
			$retry_after = self::get_retry_after( $global_key );
			return new \WP_Error(
				'global_rate_limit_exceeded',
				__( 'Global rate limit exceeded. Please try again later.', 'athlete-dashboard' ),
				array(
					'status'      => 429,
					'retry_after' => $retry_after,
				)
			);
		}

		self::increment_counter( $global_key, $count, self::DEFAULT_WINDOW );
		return true;
	}

	/**
	 * Increment counter with atomic locking
	 *
	 * @param string $key    The transient key
	 * @param int    $count  Current count
	 * @param int    $window Time window in seconds
	 */
	private static function increment_counter( $key, $count, $window ) {
		$lock_key = "lock_{$key}";
		$acquired = false;

		// Try to acquire lock
		for ( $i = 0; $i < 3; $i++ ) {
			if ( get_transient( $lock_key ) === false ) {
				set_transient( $lock_key, 1, 10 ); // Lock for 10 seconds max
				$acquired = true;
				break;
			}
			usleep( 10000 ); // Wait 10ms before retry
		}

		if ( $acquired ) {
			set_transient( $key, $count + 1, $window );
			delete_transient( $lock_key );
		}
	}

	/**
	 * Get the number of seconds until the rate limit resets
	 *
	 * @param string $transient_key The transient key to check
	 * @return int Number of seconds until reset
	 */
	private static function get_retry_after( $transient_key ) {
		$timeout = get_option( "_transient_timeout_$transient_key" );
		return max( 0, $timeout - time() );
	}

	/**
	 * Get current rate limit status for a user
	 *
	 * @param int    $user_id  The user ID to check
	 * @param string $endpoint The endpoint being accessed
	 * @return array Rate limit status including global limits
	 */
	public static function get_rate_limit_status( $user_id, $endpoint ) {
		$endpoint_key = "rate_limit_{$user_id}_{$endpoint}";
		$global_key   = "rate_limit_{$user_id}_global";

		$endpoint_count = get_transient( $endpoint_key ) ?: 0;
		$global_count   = get_transient( $global_key ) ?: 0;

		return array(
			'endpoint' => array(
				'limit'     => self::DEFAULT_LIMIT,
				'remaining' => max( 0, self::DEFAULT_LIMIT - $endpoint_count ),
				'reset'     => self::get_retry_after( $endpoint_key ),
			),
			'global'   => array(
				'limit'     => self::GLOBAL_LIMIT,
				'remaining' => max( 0, self::GLOBAL_LIMIT - $global_count ),
				'reset'     => self::get_retry_after( $global_key ),
			),
		);
	}

	/**
	 * Clear rate limits for a user
	 *
	 * @param int $user_id The user ID to clear
	 */
	public static function clear_limits( $user_id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( "_transient_rate_limit_{$user_id}_" ) . '%'
			)
		);
	}
}
