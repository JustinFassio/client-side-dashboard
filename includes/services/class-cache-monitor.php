<?php
/**
 * Cache monitoring functionality for the Athlete Dashboard.
 *
 * @package Athlete_Dashboard
 */

namespace AthleteDashboard\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cache_Monitor
 * Monitors and manages caching operations for improved performance.
 */
class Cache_Monitor {
	/**
	 * The cache key prefix.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * The cache expiration time in seconds.
	 *
	 * @var int
	 */
	private $expiration;

	/**
	 * The cache group name.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Initialize the cache monitor.
	 *
	 * @param string $prefix     The cache key prefix.
	 * @param int    $expiration The cache expiration time in seconds.
	 * @param string $group      The cache group name.
	 */
	public function __construct( $prefix = '', $expiration = 3600, $group = '' ) {
		$this->prefix     = $prefix;
		$this->expiration = $expiration;
		$this->group      = $group;
	}

	/**
	 * Get a value from cache.
	 *
	 * @param string $key The cache key.
	 * @return mixed The cached value or false if not found.
	 */
	public function get( $key ) {
		// Generate the full cache key.
		$full_key = $this->generate_key( $key );

		// Attempt to get the value from cache.
		$value = wp_cache_get( $full_key, $this->group );

		return $value;
	}

	/**
	 * Set a value in cache.
	 *
	 * @param string $key   The cache key.
	 * @param mixed  $value The value to cache.
	 * @return bool True on success, false on failure.
	 */
	public function set( $key, $value ) {
		// Generate the full cache key.
		$full_key = $this->generate_key( $key );

		// Set the value in cache.
		return wp_cache_set( $full_key, $value, $this->group, $this->expiration );
	}

	/**
	 * Delete a value from cache.
	 *
	 * @param string $key The cache key.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $key ) {
		// Generate the full cache key.
		$full_key = $this->generate_key( $key );

		// Delete the value from cache.
		return wp_cache_delete( $full_key, $this->group );
	}

	/**
	 * Generate a unique cache key.
	 *
	 * @param string $key The base key.
	 * @return string The generated cache key.
	 */
	private function generate_key( $key ) {
		// Add prefix to the key.
		return $this->prefix . $key;
	}
}
