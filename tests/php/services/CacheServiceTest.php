<?php
/**
 * Tests for the Cache Service class.
 *
 * @package AthleteDashboard\Tests\Services
 */

namespace AthleteDashboard\Tests\Services;

use AthleteDashboard\Services\Cache_Service;
use AthleteDashboard\Tests\Framework\TestCase;

/**
 * Class CacheServiceTest
 */
class CacheServiceTest extends TestCase {
	/**
	 * The user ID for testing.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up the test environment.
	 */
	public function setUp() {
		// Create a test user.
		$this->user_id = wp_create_user( 'testuser', 'testpass', 'test@example.com' );

		// Set up user meta.
		update_user_meta( $this->user_id, 'test_meta_key', 'test_value' );

		// Initialize cache service.
		$this->cache_service = new Cache_Service();
	}

	/**
	 * Clean up after the test.
	 */
	public function tearDown() {
		global $wpdb;

		// Clean up user meta.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}usermeta WHERE user_id = %d",
				$this->user_id
			)
		);

		// Clean up user.
		wp_delete_user( $this->user_id );

		// Clean up cache service.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}usermeta WHERE user_id = %d",
				$this->user_id
			)
		);
	}

	/**
	 * Test that cache keys are generated correctly.
	 */
	public function test_cache_key_generation() {
		// Test key generation.
		$key = $this->cache_service->generate_cache_key( 'test_key', array( 'param1' => 'value1' ) );
		$this->assertStringContainsString( 'test_key', $key );
		$this->assertStringContainsString( 'param1', $key );
		$this->assertStringContainsString( 'value1', $key );
	}

	/**
	 * Test that cache statistics are tracked correctly.
	 */
	public function test_cache_stats() {
		// Test stats tracking.
		$this->cache_service->track_cache_hit( 'test_key' );
		$stats = $this->cache_service->get_cache_stats();
		$this->assertArrayHasKey( 'hits', $stats );
	}

	/**
	 * Test concurrent access to the cache service.
	 */
	public function test_concurrent_access() {
		// Set up test data.
		$key   = 'test_concurrent_key';
		$value = 'test_value';

		// Test concurrent writes.
		$this->cache_service->set( $key, $value, 3600 );
		$this->cache_service->set( $key, 'new_value', 3600 );

		// Verify final value.
		$result = $this->cache_service->get( $key );
		$this->assertEquals( 'new_value', $result );
	}
}
