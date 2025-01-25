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
	 * Cache categories
	 */
	const CATEGORY_CRITICAL = 'critical';
	const CATEGORY_FREQUENT = 'frequent';
	const CATEGORY_COMPUTED = 'computed';
	const CATEGORY_STATIC = 'static';

	/**
	 * Initialize the cache service.
	 */
	public static function init() {
		// Ensure stats group exists and is initialized
		wp_cache_add( 'cache_enabled', true, 'athlete_dashboard_stats' );
		wp_cache_add( 'cache_hits', 0, 'athlete_dashboard_stats' );
		wp_cache_add( 'cache_misses', 0, 'athlete_dashboard_stats' );
		wp_cache_add( 'cache_size', 0, 'athlete_dashboard_stats' );
		wp_cache_add( 'object_count', 0, 'athlete_dashboard_stats' );
		wp_cache_add( 'memory_usage', memory_get_usage(true), 'athlete_dashboard_stats' );
		wp_cache_add( 'last_access', time(), 'athlete_dashboard_stats' );
		wp_cache_add( 'last_write', time(), 'athlete_dashboard_stats' );
		wp_cache_add( 'last_delete', time(), 'athlete_dashboard_stats' );

		// Initialize cache monitoring
		add_action( 'athlete_dashboard_cache_hit', array( __CLASS__, 'track_cache_hit' ), 10, 2 );
		add_action( 'athlete_dashboard_cache_miss', array( __CLASS__, 'track_cache_miss' ), 10, 2 );
		add_action( 'athlete_dashboard_cache_set', array( __CLASS__, 'track_cache_set' ), 10, 4 );
		add_action( 'athlete_dashboard_cache_delete', array( __CLASS__, 'track_cache_delete' ), 10, 2 );
	}

	/**
	 * Track cache hit.
	 *
	 * @param string $key Cache key.
	 * @param string $group Cache group.
	 */
	public static function track_cache_hit( $key, $group ) {
		$hits = (int) wp_cache_get( 'cache_hits', 'athlete_dashboard_stats' ) ?: 0;
		wp_cache_set( 'cache_hits', $hits + 1, 'athlete_dashboard_stats' );
		
		// Track last access time
		wp_cache_set( 'last_access', time(), 'athlete_dashboard_stats' );
	}

	/**
	 * Track cache miss.
	 *
	 * @param string $key Cache key.
	 * @param string $group Cache group.
	 */
	public static function track_cache_miss( $key, $group ) {
		$misses = (int) wp_cache_get( 'cache_misses', 'athlete_dashboard_stats' ) ?: 0;
		wp_cache_set( 'cache_misses', $misses + 1, 'athlete_dashboard_stats' );
		
		// Track last access time
		wp_cache_set( 'last_access', time(), 'athlete_dashboard_stats' );
	}

	/**
	 * Track cache set.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $data Data being cached.
	 * @param int    $expiration Expiration time.
	 * @param string $group Cache group.
	 */
	public static function track_cache_set( $key, $data, $expiration, $group ) {
		// Track object count
		$count = (int) wp_cache_get( 'object_count', 'athlete_dashboard_stats' ) ?: 0;
		wp_cache_set( 'object_count', $count + 1, 'athlete_dashboard_stats' );

		// Track cache size
		$size = (int) wp_cache_get( 'cache_size', 'athlete_dashboard_stats' ) ?: 0;
		$new_size = $size + strlen( serialize( $data ) );
		wp_cache_set( 'cache_size', $new_size, 'athlete_dashboard_stats' );

		// Track memory usage
		$memory = memory_get_usage(true);
		wp_cache_set( 'memory_usage', $memory, 'athlete_dashboard_stats' );
		
		// Track last write time
		wp_cache_set( 'last_write', time(), 'athlete_dashboard_stats' );
	}

	/**
	 * Track cache delete.
	 *
	 * @param string $key Cache key.
	 * @param string $group Cache group.
	 */
	public static function track_cache_delete( $key, $group ) {
		$count = (int) wp_cache_get( 'object_count', 'athlete_dashboard_stats' ) ?: 0;
		if ( $count > 0 ) {
			wp_cache_set( 'object_count', $count - 1, 'athlete_dashboard_stats' );
		}
		
		// Track last delete time
		wp_cache_set( 'last_delete', time(), 'athlete_dashboard_stats' );
	}

	/**
	 * Get cache strategy for category.
	 *
	 * @param string $category Cache category.
	 * @return array Cache strategy configuration.
	 */
	private static function get_cache_strategy(string $category): array {
		global $redis_cache_groups;

		$group_key = 'athlete_dashboard_' . $category;
		$config = $redis_cache_groups[$group_key] ?? null;

		if ($config) {
			return [
				'ttl' => $config['ttl'],
				'distributed_lock' => $config['persistent'],
				'warm_on_write' => $config['persistent'],
				'retry_attempts' => isset($config['stampede_ttl']) ? 3 : 1,
				'backoff_ms' => isset($config['stampede_ttl']) ? 100 : 0,
				'stampede_ttl' => $config['stampede_ttl'] ?? null,
			];
		}

		// Fallback to default strategy
		switch ($category) {
			case self::CATEGORY_CRITICAL:
				return [
					'ttl' => 3600,
					'distributed_lock' => true,
					'warm_on_write' => true,
					'retry_attempts' => 3,
					'backoff_ms' => 100,
				];
			case self::CATEGORY_COMPUTED:
				return [
					'ttl' => 1800,
					'distributed_lock' => true,
					'warm_on_write' => false,
					'retry_attempts' => 2,
					'backoff_ms' => 50,
				];
			case self::CATEGORY_STATIC:
				return [
					'ttl' => 86400,
					'distributed_lock' => false,
					'warm_on_write' => true,
					'retry_attempts' => 1,
					'backoff_ms' => 0,
				];
			case self::CATEGORY_FREQUENT:
			default:
				return [
					'ttl' => 900,
					'distributed_lock' => false,
					'warm_on_write' => false,
					'retry_attempts' => 1,
					'backoff_ms' => 0,
				];
		}
	}

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
			// Explicitly trigger the action for tracking
			self::track_cache_hit( $key, $group );
			return $data;
		}

		// Explicitly trigger the action for tracking
		self::track_cache_miss( $key, $group );

		// Fall back to transient
		$data = get_transient( self::TRANSIENT_PREFIX . $key );
		
		if ( false !== $data ) {
			// If we got data from transient, still count it as a hit
			self::track_cache_hit( $key, $group );
			return $data;
		}
		
		return false;
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

		if ( $object_cache_set || $transient_set ) {
			// Explicitly trigger the action for tracking
			self::track_cache_set( $key, $data, $expiration, $group );
			return true;
		}

		return false;
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

		if ( $object_cache_deleted || $transient_deleted ) {
			// Explicitly trigger the action for tracking
			self::track_cache_delete( $key, $group );
			return true;
		}

		return false;
	}

	/**
	 * Get or set cache value with callback.
	 *
	 * @param string   $key Cache key
	 * @param callable $callback Callback to generate value if not cached
	 * @param array    $options Optional. Cache options
	 * @return mixed The cached or generated value
	 */
	public static function remember(string $key, callable $callback, array $options = []): mixed {
		$category = $options['category'] ?? self::CATEGORY_FREQUENT;
		$strategy = self::get_cache_strategy($category);
		
		if ($strategy['distributed_lock']) {
			return self::remember_with_lock($key, $callback, $strategy);
		}
		
		return self::remember_simple($key, $callback, $strategy);
	}

	private static function remember_with_lock(string $key, callable $callback, array $strategy): mixed {
		$lock_key = "lock_{$key}";
		$attempt = 0;
		
		// Check for stampede protection
		if (isset($strategy['stampede_ttl'])) {
			$stale_data = self::get($key);
			if ($stale_data !== false) {
				// Extend TTL for stale data during recomputation
				self::set($key, $stale_data, $strategy['stampede_ttl']);
			}
		}
		
		while ($attempt < $strategy['retry_attempts']) {
			if (self::acquire_lock($lock_key)) {
				try {
					return self::get_or_set($key, $callback, $strategy);
				} finally {
					self::release_lock($lock_key);
				}
			}
			
			$attempt++;
			if ($attempt < $strategy['retry_attempts']) {
				usleep($strategy['backoff_ms'] * 1000);
			}
		}
		
		// Fallback to direct read if lock acquisition fails
		$data = self::get($key);
		return $data !== false ? $data : $callback();
	}

	private static function remember_simple(string $key, callable $callback, array $strategy): mixed {
		return self::get_or_set($key, $callback, $strategy);
	}

	private static function get_or_set(string $key, callable $callback, array $strategy): mixed {
		$data = self::get($key);
		if ($data !== false) {
			return $data;
		}
		
		$data = $callback();
		self::set($key, $data, $strategy['ttl']);
		
		if ($strategy['warm_on_write']) {
			do_action('athlete_dashboard_cache_warmed', $key, $data);
		}
		
		return $data;
	}

	private static function acquire_lock(string $key): bool {
		return wp_cache_add($key, '1', self::CACHE_GROUP, 30);
	}

	private static function release_lock(string $key): bool {
		return wp_cache_delete($key, self::CACHE_GROUP);
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
		return array(
			'object_cache_available' => self::is_object_cache_available(),
			'hits'                   => (int) wp_cache_get( 'cache_hits', 'athlete_dashboard_stats' ) ?: 0,
			'misses'                 => (int) wp_cache_get( 'cache_misses', 'athlete_dashboard_stats' ) ?: 0,
			'ratio'                  => self::calculate_hit_ratio(),
			'size'                   => (int) wp_cache_get( 'cache_size', 'athlete_dashboard_stats' ) ?: 0,
			'memory_usage'           => (int) wp_cache_get( 'memory_usage', 'athlete_dashboard_stats' ) ?: 0,
			'object_count'           => (int) wp_cache_get( 'object_count', 'athlete_dashboard_stats' ) ?: 0,
			'last_access'            => wp_cache_get( 'last_access', 'athlete_dashboard_stats' ),
			'last_write'             => wp_cache_get( 'last_write', 'athlete_dashboard_stats' ),
			'last_delete'            => wp_cache_get( 'last_delete', 'athlete_dashboard_stats' ),
			'enabled'                => (bool) wp_cache_get( 'cache_enabled', 'athlete_dashboard_stats' )
		);
	}

	/**
	 * Calculate cache hit ratio
	 *
	 * @return float Hit ratio as a percentage
	 */
	private static function calculate_hit_ratio() {
		$hits = (int) wp_cache_get( 'cache_hits', 'athlete_dashboard_stats' ) ?: 0;
		$misses = (int) wp_cache_get( 'cache_misses', 'athlete_dashboard_stats' ) ?: 0;
		$total = $hits + $misses;
		
		return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
	}
}
