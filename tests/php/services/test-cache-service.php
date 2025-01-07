<?php
namespace AthleteDashboard\Tests\Services;

use AthleteDashboard\Services\Cache_Service;
use WP_UnitTestCase;

class Cache_Service_Test extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        // Clear cache before each test
        wp_cache_flush();
        $this->clear_all_transients();
    }

    public function tearDown(): void {
        // Clean up after each test
        wp_cache_flush();
        $this->clear_all_transients();
        parent::tearDown();
    }

    private function clear_all_transients() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
    }

    public function test_set_and_get() {
        $key = 'test_key';
        $data = ['test' => 'data'];

        // Test setting data
        $result = Cache_Service::set($key, $data);
        $this->assertTrue($result);

        // Test getting data from object cache
        $cached_data = Cache_Service::get($key);
        $this->assertEquals($data, $cached_data);

        // Clear object cache to test transient fallback
        wp_cache_flush();

        // Test getting data from transient
        $cached_data = Cache_Service::get($key);
        $this->assertEquals($data, $cached_data);
    }

    public function test_delete() {
        $key = 'test_key';
        $data = ['test' => 'data'];

        Cache_Service::set($key, $data);
        $this->assertEquals($data, Cache_Service::get($key));

        // Test deletion
        $result = Cache_Service::delete($key);
        $this->assertTrue($result);
        $this->assertFalse(Cache_Service::get($key));

        // Verify transient is also deleted
        $this->assertFalse(get_transient(Cache_Service::TRANSIENT_PREFIX . $key));
    }

    public function test_remember() {
        $key = 'test_key';
        $calls = 0;
        $callback = function() use (&$calls) {
            $calls++;
            return ['generated' => 'data'];
        };

        // First call should execute callback
        $result = Cache_Service::remember($key, $callback);
        $this->assertEquals(['generated' => 'data'], $result);
        $this->assertEquals(1, $calls);

        // Second call should use cached data
        $result = Cache_Service::remember($key, $callback);
        $this->assertEquals(['generated' => 'data'], $result);
        $this->assertEquals(1, $calls); // Callback should not be called again
    }

    public function test_clear_group() {
        $keys = ['key1', 'key2', 'key3'];
        $data = ['test' => 'data'];

        // Set multiple items in cache
        foreach ($keys as $key) {
            Cache_Service::set($key, $data);
        }

        // Verify data is cached
        foreach ($keys as $key) {
            $this->assertEquals($data, Cache_Service::get($key));
        }

        // Clear group
        Cache_Service::clear_group();

        // Verify data is cleared
        foreach ($keys as $key) {
            $this->assertFalse(Cache_Service::get($key));
        }
    }

    public function test_user_cache_invalidation() {
        $user_id = 1;
        $types = ['profile', 'preferences', 'settings', 'meta'];
        $data = ['test' => 'data'];

        // Set cache for all types
        foreach ($types as $type) {
            $key = Cache_Service::generate_user_key($user_id, $type);
            Cache_Service::set($key, $data);
        }

        // Verify data is cached
        foreach ($types as $type) {
            $key = Cache_Service::generate_user_key($user_id, $type);
            $this->assertEquals($data, Cache_Service::get($key));
        }

        // Invalidate all user cache
        Cache_Service::invalidate_user_cache($user_id);

        // Verify all data is cleared
        foreach ($types as $type) {
            $key = Cache_Service::generate_user_key($user_id, $type);
            $this->assertFalse(Cache_Service::get($key));
        }
    }

    public function test_cache_expiration() {
        $key = 'test_key';
        $data = ['test' => 'data'];
        $expiration = 1; // 1 second

        Cache_Service::set($key, $data, $expiration);
        $this->assertEquals($data, Cache_Service::get($key));

        // Wait for cache to expire
        sleep(2);

        $this->assertFalse(Cache_Service::get($key));
    }

    public function test_object_cache_fallback() {
        $key = 'test_key';
        $data = ['test' => 'data'];

        Cache_Service::set($key, $data);

        // Clear object cache to test transient fallback
        wp_cache_flush();

        // Data should still be available from transient
        $this->assertEquals($data, Cache_Service::get($key));
    }

    public function test_cache_key_generation() {
        $user_id = 123;
        $type = 'profile';

        $user_key = Cache_Service::generate_user_key($user_id, $type);
        $this->assertEquals("user_{$user_id}_{$type}", $user_key);

        $profile_key = Cache_Service::generate_profile_key($user_id, $type);
        $this->assertEquals("profile_{$user_id}_{$type}", $profile_key);
    }

    public function test_cache_stats() {
        $stats = Cache_Service::get_stats();
        
        $this->assertArrayHasKey('object_cache_available', $stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('data', $stats);
    }

    public function test_concurrent_access() {
        $key = 'test_key';
        $data = ['test' => 'data'];

        // Simulate concurrent writes
        $processes = [];
        for ($i = 0; $i < 5; $i++) {
            $processes[] = function() use ($key, $data) {
                return Cache_Service::set($key, $data);
            };
        }

        // Run processes
        $results = array_map(function($process) {
            return $process();
        }, $processes);

        // All processes should succeed
        foreach ($results as $result) {
            $this->assertTrue($result);
        }

        // Final data should be correct
        $this->assertEquals($data, Cache_Service::get($key));
    }
} 