<?php
namespace AthleteDashboard\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles caching operations for the Athlete Dashboard.
 * Provides a unified interface for working with WordPress object cache and transients.
 */
class Cache_Service {
	/**
	 * Default cache group for object cache
	 */
	const CACHE_GROUP = 'athlete_dashboard';

	/**
	 * Default expiration time for cached items (1 hour)
	 */
	const DEFAULT_EXPIRATION = 3600;

	/**
	 * Cache key prefix for transients
	 */
	const TRANSIENT_PREFIX = 'ad_cache_';

	/**
	 * Get an item from cache.
	 *
	 * @param string $key Cache key
	 * @param string $group Optional. Cache group
	 * @return mixed|false The cached data or false if not found
	 */
	public static function get( $key, $group = self::CACHE_GROUP ) {
		// Try object cache first
		$data = wp_cache_get( $key, $group );
		if ( false !== $data ) {
			do_action( 'athlete_dashboard_cache_hit', $key, $group );
			return $data;
		}

		do_action( 'athlete_dashboard_cache_miss', $key, $group );

		// Fall back to transient
		return get_transient( self::TRANSIENT_PREFIX . $key );
	}

	/**
	 * Set an item in cache.
	 *
	 * @param string $key Cache key
	 * @param mixed  $data Data to cache
	 * @param int    $expiration Optional. Time until expiration in seconds
	 * @param string $group Optional. Cache group
	 * @return bool True on success, false on failure
	 */
	public static function set( $key, $data, $expiration = self::DEFAULT_EXPIRATION, $group = self::CACHE_GROUP ) {
		// Set in object cache
		$object_cache_set = wp_cache_set( $key, $data, $group, $expiration );

		// Also set in transients for persistence
		$transient_set = set_transient( self::TRANSIENT_PREFIX . $key, $data, $expiration );

		if ( $object_cache_set && $transient_set ) {
			do_action( 'athlete_dashboard_cache_set', $key, $data, $expiration, $group );
		}

		return $object_cache_set && $transient_set;
	}

	/**
	 * Delete an item from cache.
	 *
	 * @param string $key Cache key
	 * @param string $group Optional. Cache group
	 * @return bool True on success, false on failure
	 */
	public static function delete( $key, $group = self::CACHE_GROUP ) {
		// Delete from object cache
		$object_cache_deleted = wp_cache_delete( $key, $group );

		// Delete from transients
		$transient_deleted = delete_transient( self::TRANSIENT_PREFIX . $key );

		if ( $object_cache_deleted && $transient_deleted ) {
			do_action( 'athlete_dashboard_cache_delete', $key, $group );
		}

		return $object_cache_deleted && $transient_deleted;
	}

	/**
	 * Get or set cache value with callback.
	 *
	 * @param string   $key Cache key
	 * @param callable $callback Callback to generate value if not cached
	 * @param int      $expiration Optional. Time until expiration in seconds
	 * @param string   $group Optional. Cache group
	 * @return mixed The cached or generated value
	 */
	public static function remember( $key, $callback, $expiration = self::DEFAULT_EXPIRATION, $group = self::CACHE_GROUP ) {
		$data = self::get( $key, $group );
		if ( false !== $data ) {
			return $data;
		}

		$data = call_user_func( $callback );
		self::set( $key, $data, $expiration, $group );
		return $data;
	}

	/**
	 * Clear all cached items for a group.
	 *
	 * @param string $group Optional. Cache group
	 */
	public static function clear_group( $group = self::CACHE_GROUP ) {
		wp_cache_delete_group( $group );

		// Also clear related transients
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%'
			)
		);
	}

	/**
	 * Generate a cache key for a user.
	 *
	 * @param int    $user_id User ID
	 * @param string $type Data type
	 * @return string Cache key
	 */
	public static function generate_user_key( $user_id, $type ) {
		return "user_{$user_id}_{$type}";
	}

	/**
	 * Generate a cache key for a profile.
	 *
	 * @param int    $profile_id Profile ID
	 * @param string $type Data type
	 * @return string Cache key
	 */
	public static function generate_profile_key( $profile_id, $type ) {
		return "profile_{$profile_id}_{$type}";
	}

	/**
	 * Invalidate all cached data for a user.
	 *
	 * @param int $user_id User ID
	 */
	public static function invalidate_user_cache( $user_id ) {
		$types = array( 'profile', 'preferences', 'settings', 'meta' );
		foreach ( $types as $type ) {
			self::delete( self::generate_user_key( $user_id, $type ) );
		}
	}

	/**
	 * Invalidate specific cached data for a user.
	 *
	 * @param int    $user_id User ID
	 * @param string $type Data type
	 */
	public static function invalidate_user_data( $user_id, $type ) {
		self::delete( self::generate_user_key( $user_id, $type ) );
	}

	/**
	 * Check if object caching is available.
	 *
	 * @return bool True if object caching is available
	 */
	public static function is_object_cache_available() {
		return wp_using_ext_object_cache();
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array Cache statistics
	 */
	public static function get_stats() {
		global $wp_object_cache;

		$stats = array(
			'object_cache_available' => self::is_object_cache_available(),
			'hits'                   => 0,
			'misses'                 => 0,
			'data'                   => array(),
		);

		if ( isset( $wp_object_cache->cache_hits ) ) {
			$stats['hits'] = $wp_object_cache->cache_hits;
		}

		if ( isset( $wp_object_cache->cache_misses ) ) {
			$stats['misses'] = $wp_object_cache->cache_misses;
		}

		return $stats;
	}
}
