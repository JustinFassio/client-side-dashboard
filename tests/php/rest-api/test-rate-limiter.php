<?php
namespace AthleteDashboard\Tests\RestApi;

use AthleteDashboard\RestApi\Rate_Limiter;
use WP_UnitTestCase;

class Rate_Limiter_Test extends WP_UnitTestCase {
    private $user_id;
    private $admin_id;

    public function setUp(): void {
        parent::setUp();
        $this->user_id = $this->factory->user->create(['role' => 'subscriber']);
        $this->admin_id = $this->factory->user->create(['role' => 'administrator']);
        Rate_Limiter::clear_limits($this->user_id);
        Rate_Limiter::clear_limits($this->admin_id);
    }

    public function tearDown(): void {
        Rate_Limiter::clear_limits($this->user_id);
        Rate_Limiter::clear_limits($this->admin_id);
        parent::tearDown();
    }

    public function test_endpoint_specific_rate_limit() {
        $custom_rules = [
            'limit' => 5,
            'window' => 3600
        ];

        // Test up to limit
        for ($i = 0; $i < 5; $i++) {
            $result = Rate_Limiter::check_rate_limit($this->user_id, 'profile', $custom_rules);
            $this->assertTrue($result);
        }

        // Test exceeding limit
        $result = Rate_Limiter::check_rate_limit($this->user_id, 'profile', $custom_rules);
        $this->assertWPError($result);
        $this->assertEquals('rate_limit_exceeded', $result->get_error_code());
    }

    public function test_global_rate_limit() {
        // Test multiple endpoints approaching global limit
        for ($i = 0; $i < 998; $i++) {
            $endpoint = "endpoint_" . ($i % 10); // Rotate between 10 endpoints
            $result = Rate_Limiter::check_rate_limit($this->user_id, $endpoint);
            $this->assertTrue($result);
        }

        // Test exceeding global limit
        $result = Rate_Limiter::check_rate_limit($this->user_id, 'new_endpoint');
        $this->assertWPError($result);
        $this->assertEquals('global_rate_limit_exceeded', $result->get_error_code());
    }

    public function test_concurrent_requests() {
        $endpoint = 'profile';
        $processes = [];
        
        // Simulate 10 concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $processes[] = new WP_Background_Process(function() use ($endpoint) {
                return Rate_Limiter::check_rate_limit($this->user_id, $endpoint);
            });
        }

        // Wait for all processes to complete
        foreach ($processes as $process) {
            $process->complete();
        }

        // Verify rate limit count is accurate
        $status = Rate_Limiter::get_rate_limit_status($this->user_id, $endpoint);
        $this->assertEquals(10, Rate_Limiter::DEFAULT_LIMIT - $status['endpoint']['remaining']);
    }

    public function test_rate_limit_reset() {
        $endpoint = 'profile';
        
        // Make some requests
        for ($i = 0; $i < 5; $i++) {
            Rate_Limiter::check_rate_limit($this->user_id, $endpoint);
        }

        // Force expiration of the rate limit
        $transient_key = "rate_limit_{$this->user_id}_{$endpoint}";
        delete_transient($transient_key);

        // Verify counter is reset
        $status = Rate_Limiter::get_rate_limit_status($this->user_id, $endpoint);
        $this->assertEquals(Rate_Limiter::DEFAULT_LIMIT, $status['endpoint']['remaining']);
    }

    public function test_rate_limit_status() {
        $endpoint = 'profile';
        
        // Make a few requests
        for ($i = 0; $i < 3; $i++) {
            Rate_Limiter::check_rate_limit($this->user_id, $endpoint);
        }

        // Check status
        $status = Rate_Limiter::get_rate_limit_status($this->user_id, $endpoint);
        
        $this->assertArrayHasKey('endpoint', $status);
        $this->assertArrayHasKey('global', $status);
        $this->assertEquals(Rate_Limiter::DEFAULT_LIMIT - 3, $status['endpoint']['remaining']);
        $this->assertEquals(Rate_Limiter::GLOBAL_LIMIT - 3, $status['global']['remaining']);
    }

    public function test_clear_limits() {
        $endpoint = 'profile';
        
        // Make some requests
        for ($i = 0; $i < 5; $i++) {
            Rate_Limiter::check_rate_limit($this->user_id, $endpoint);
        }

        // Clear limits
        Rate_Limiter::clear_limits($this->user_id);

        // Verify all limits are cleared
        $status = Rate_Limiter::get_rate_limit_status($this->user_id, $endpoint);
        $this->assertEquals(Rate_Limiter::DEFAULT_LIMIT, $status['endpoint']['remaining']);
        $this->assertEquals(Rate_Limiter::GLOBAL_LIMIT, $status['global']['remaining']);
    }
} 