<?php
/**
 * Rate Limiter Tests
 */

class Rate_Limiter_Test extends WP_UnitTestCase {
    private $rate_limiter;
    private $test_prefix = 'test_limiter';
    private $test_limit = 5;
    private $test_window = 60;

    public function setUp(): void {
        parent::setUp();
        $this->rate_limiter = new Rate_Limiter(
            $this->test_prefix,
            $this->test_limit,
            $this->test_window
        );
    }

    public function tearDown(): void {
        // Clean up any transients
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->test_prefix . '%'
            )
        );
        parent::tearDown();
    }

    public function test_initial_limit_check() {
        $this->assertTrue(
            $this->rate_limiter->check_limit(),
            'First request should be within limit'
        );
    }

    public function test_multiple_requests_within_limit() {
        for ($i = 0; $i < $this->test_limit - 1; $i++) {
            $this->assertTrue(
                $this->rate_limiter->check_limit(),
                "Request {$i} should be within limit"
            );
        }

        $this->assertEquals(
            1,
            $this->rate_limiter->get_remaining(),
            'Should have one request remaining'
        );
    }

    public function test_exceeding_limit() {
        // Use up all allowed requests
        for ($i = 0; $i < $this->test_limit; $i++) {
            $this->rate_limiter->check_limit();
        }

        $this->assertFalse(
            $this->rate_limiter->check_limit(),
            'Request exceeding limit should be rejected'
        );

        $this->assertEquals(
            0,
            $this->rate_limiter->get_remaining(),
            'Should have no requests remaining'
        );
    }

    public function test_limit_reset_after_window() {
        // Use up all requests
        for ($i = 0; $i < $this->test_limit; $i++) {
            $this->rate_limiter->check_limit();
        }

        // Simulate time passing
        $this->simulate_time_passing($this->test_window + 1);

        $this->assertTrue(
            $this->rate_limiter->check_limit(),
            'Should allow requests after window expires'
        );

        $this->assertEquals(
            $this->test_limit - 1,
            $this->rate_limiter->get_remaining(),
            'Should reset remaining requests after window expires'
        );
    }

    public function test_different_users_separate_limits() {
        // Create two test users
        $user1 = $this->factory->user->create();
        $user2 = $this->factory->user->create();

        // Test as user 1
        wp_set_current_user($user1);
        for ($i = 0; $i < $this->test_limit; $i++) {
            $this->rate_limiter->check_limit();
        }
        $this->assertFalse($this->rate_limiter->check_limit(), 'User 1 should be rate limited');

        // Test as user 2
        wp_set_current_user($user2);
        $this->assertTrue(
            $this->rate_limiter->check_limit(),
            'User 2 should have separate rate limit'
        );
    }

    public function test_ip_based_limiting() {
        // Simulate no logged-in user
        wp_set_current_user(0);

        // Test with first IP
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
        for ($i = 0; $i < $this->test_limit; $i++) {
            $this->rate_limiter->check_limit();
        }
        $this->assertFalse($this->rate_limiter->check_limit(), 'IP 1 should be rate limited');

        // Test with second IP
        $_SERVER['REMOTE_ADDR'] = '192.0.2.2';
        $this->assertTrue(
            $this->rate_limiter->check_limit(),
            'IP 2 should have separate rate limit'
        );
    }

    public function test_proxy_ip_detection() {
        wp_set_current_user(0);
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.0.2.1';

        $this->rate_limiter->check_limit();

        // Get the cache key via reflection to check the IP used
        $reflection = new ReflectionClass($this->rate_limiter);
        $method = $reflection->getMethod('get_cache_key');
        $method->setAccessible(true);
        $cache_key = $method->invoke($this->rate_limiter);

        $this->assertStringContainsString(
            'ip_203.0.113.1',
            $cache_key,
            'Should use first IP from X-Forwarded-For'
        );
    }

    public function test_invalid_ip_handling() {
        wp_set_current_user(0);
        $_SERVER['REMOTE_ADDR'] = 'invalid-ip';

        $reflection = new ReflectionClass($this->rate_limiter);
        $method = $reflection->getMethod('get_client_ip');
        $method->setAccessible(true);

        $this->assertEquals(
            '0.0.0.0',
            $method->invoke($this->rate_limiter),
            'Should return fallback IP for invalid addresses'
        );
    }

    /**
     * Helper to simulate time passing
     */
    private function simulate_time_passing($seconds) {
        // Clear transients cache
        wp_cache_flush();
        
        // Add time offset to transient expiration
        add_filter('pre_get_transient', function($pre, $transient) use ($seconds) {
            if (strpos($transient, $this->test_prefix) === 0) {
                return false; // Simulate expired transient
            }
            return $pre;
        }, 10, 2);
    }
} 