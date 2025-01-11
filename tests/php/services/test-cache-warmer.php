<?php
namespace AthleteDashboard\Tests\Services;

use AthleteDashboard\Services\Cache_Warmer;
use AthleteDashboard\Services\Cache_Service;
use WP_UnitTestCase;

class Cache_Warmer_Test extends WP_UnitTestCase {
	private $cache_warmer;
	private $test_user;

	public function setUp(): void {
		parent::setUp();

		// Clear cache before each test
		wp_cache_flush();
		$this->clear_all_transients();

		// Create test user
		$this->test_user = $this->factory->user->create_and_get(
			array(
				'user_login' => 'testuser',
				'user_email' => 'test@example.com',
			)
		);

		// Initialize cache warmer
		$this->cache_warmer = new Cache_Warmer();
	}

	public function tearDown(): void {
		// Clean up
		wp_delete_user( $this->test_user->ID );
		wp_cache_flush();
		$this->clear_all_transients();
		parent::tearDown();
	}

	private function clear_all_transients() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
	}

	public function test_warm_user_cache() {
		// Set up test data
		update_user_meta( $this->test_user->ID, 'first_name', 'Test' );
		update_user_meta( $this->test_user->ID, 'last_name', 'User' );
		update_user_meta( $this->test_user->ID, 'age', 25 );
		update_user_meta( $this->test_user->ID, 'height', 180 );
		update_user_meta( $this->test_user->ID, 'weight', 75 );

		// Warm the cache
		$this->cache_warmer->warm_user_cache( $this->test_user->user_login, $this->test_user );

		// Verify profile cache
		$profile_key    = Cache_Service::generate_user_key( $this->test_user->ID, 'full' );
		$cached_profile = Cache_Service::get( $profile_key );

		$this->assertNotFalse( $cached_profile );
		$this->assertEquals( $this->test_user->ID, $cached_profile['id'] );
		$this->assertEquals( 'Test', $cached_profile['firstName'] );
		$this->assertEquals( 'User', $cached_profile['lastName'] );

		// Verify overview cache
		$overview_key    = Cache_Service::generate_user_key( $this->test_user->ID, 'stats' );
		$cached_overview = Cache_Service::get( $overview_key );

		$this->assertNotFalse( $cached_overview );
		$this->assertArrayHasKey( 'workouts_completed', $cached_overview );
	}

	public function test_warm_priority_users_cache() {
		// Set up test data
		update_user_meta( $this->test_user->ID, 'last_activity', time() );

		// Warm cache for priority users
		$this->cache_warmer->warm_priority_users_cache();

		// Verify cache was warmed
		$profile_key    = Cache_Service::generate_user_key( $this->test_user->ID, 'full' );
		$cached_profile = Cache_Service::get( $profile_key );

		$this->assertNotFalse( $cached_profile );
		$this->assertEquals( $this->test_user->ID, $cached_profile['id'] );
	}

	public function test_cache_expiration() {
		// Set up test data
		update_user_meta( $this->test_user->ID, 'first_name', 'Test' );

		// Warm the cache
		$this->cache_warmer->warm_user_cache( $this->test_user->user_login, $this->test_user );

		// Verify initial cache
		$profile_key    = Cache_Service::generate_user_key( $this->test_user->ID, 'full' );
		$cached_profile = Cache_Service::get( $profile_key );
		$this->assertNotFalse( $cached_profile );

		// Fast forward time
		$this->mock_time_pass( 3700 ); // 1 hour + 100 seconds

		// Cache should be expired
		$cached_profile = Cache_Service::get( $profile_key );
		$this->assertFalse( $cached_profile );
	}

	public function test_cache_update_on_user_update() {
		// Initial cache warm
		$this->cache_warmer->warm_user_cache( $this->test_user->user_login, $this->test_user );

		// Update user data
		update_user_meta( $this->test_user->ID, 'first_name', 'Updated' );

		// Re-warm cache
		$this->cache_warmer->warm_user_cache( $this->test_user->user_login, $this->test_user );

		// Verify cache was updated
		$profile_key    = Cache_Service::generate_user_key( $this->test_user->ID, 'full' );
		$cached_profile = Cache_Service::get( $profile_key );

		$this->assertEquals( 'Updated', $cached_profile['firstName'] );
	}

	public function test_invalid_user() {
		// Try to warm cache for non-existent user
		$this->cache_warmer->warm_user_cache( 'nonexistentuser' );

		// Verify no cache was created
		$profile_key    = Cache_Service::generate_user_key( 999999, 'full' );
		$cached_profile = Cache_Service::get( $profile_key );

		$this->assertFalse( $cached_profile );
	}

	private function mock_time_pass( $seconds ) {
		// Mock time passage for testing cache expiration
		add_filter(
			'pre_option__transient_timeout_',
			function ( $pre, $option ) use ( $seconds ) {
				return time() - $seconds;
			},
			10,
			2
		);
	}
}
