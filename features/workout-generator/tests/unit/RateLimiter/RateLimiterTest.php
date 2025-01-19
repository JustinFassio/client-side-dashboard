<?php
/**
 * Rate Limiter Tests
 */

namespace AthleteDashboard\Features\WorkoutGenerator\Tests\Unit\RateLimiter;

use PHPUnit\Framework\TestCase;
use AthleteDashboard\Features\WorkoutGenerator\API\Rate_Limiter;

class RateLimiterTest extends TestCase {
    private $rate_limiter;
    private $test_prefix = 'test_limiter';
    private $test_user_id = 1;
    private $test_limit = 60;  // Default foundation tier limit
    private $test_window = 3600;  // Default window of 1 hour
    protected $factory;

    public function setUp(): void {
        parent::setUp();
        
        // Initialize factory for user creation
        $this->factory = new class {
            private $user_count = 0;
            
            public function create($args = array()) {
                $this->user_count++;
                return $this->user_count;
            }
        };

        $this->rate_limiter = new Rate_Limiter($this->test_prefix, $this->test_user_id);
    }

    public function tearDown(): void {
        global $wp_filter;
        $wp_filter = [];
        parent::tearDown();
    }

    public function test_initial_limit_check() {
        $this->assertTrue(
            $this->rate_limiter->check_limit(),
            'First request should be within limit'
        );
    }

    public function test_multiple_requests_within_limit() {
        // Reset transients
        global $wp_transients;
        $wp_transients = [];

        // Set foundation tier
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'foundation';
        });

        $this->rate_limiter = new Rate_Limiter('test_limiter', 1);
        $limit = $this->rate_limiter->get_user_limit()['requests'];

        // Make requests up to limit - 1
        for ($i = 0; $i < $limit - 1; $i++) {
            $this->assertTrue(
                $this->rate_limiter->check_limit(),
                "Request {$i} should be within limit"
            );
        }

        // Verify remaining count
        $this->assertEquals(
            1,
            $this->rate_limiter->get_remaining(),
            'Should have one request remaining'
        );

        // Use last request
        $this->assertTrue(
            $this->rate_limiter->check_limit(),
            'Last request should be allowed'
        );

        // Verify no requests remaining
        $this->assertEquals(
            0,
            $this->rate_limiter->get_remaining(),
            'Should have no requests remaining'
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
        // Reset transients
        global $wp_transients;
        $wp_transients = [];

        // Set foundation tier
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'foundation';
        });

        $this->rate_limiter = new Rate_Limiter('test_limiter', 1);
        $limit = $this->rate_limiter->get_user_limit()['requests'];

        // Use up all requests
        for ($i = 0; $i < $limit; $i++) {
            $this->assertTrue($this->rate_limiter->check_limit());
        }

        // Verify limit is reached
        $this->assertFalse($this->rate_limiter->check_limit());

        // Simulate time passing beyond window
        global $wp_transients;
        $wp_transients = []; // Clear transients to simulate expiration

        // Verify new requests are allowed
        $this->assertTrue(
            $this->rate_limiter->check_limit(),
            'Should allow requests after window expires'
        );
    }

    public function test_different_users_separate_limits() {
        // Reset transients
        global $wp_transients;
        $wp_transients = [];

        // Set foundation tier
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'foundation';
        });

        // Create rate limiter for user 1
        $rate_limiter_1 = new Rate_Limiter('test_limiter', 1);
        $limit = $rate_limiter_1->get_user_limit()['requests'];

        // Use up all requests for user 1
        for ($i = 0; $i < $limit; $i++) {
            $this->assertTrue($rate_limiter_1->check_limit());
        }

        // Verify user 1 is at limit
        $this->assertFalse($rate_limiter_1->check_limit());

        // Create rate limiter for user 2
        $rate_limiter_2 = new Rate_Limiter('test_limiter', 2);

        // Verify user 2 has fresh limit
        $this->assertTrue(
            $rate_limiter_2->check_limit(),
            'User 2 should have separate rate limit'
        );

        // Verify user 2 has full limit
        $this->assertEquals(
            $limit - 1,
            $rate_limiter_2->get_remaining(),
            'User 2 should have full limit minus one request'
        );
    }

    public function test_ip_based_limiting() {
        // Reset transients
        global $wp_transients;
        $wp_transients = [];

        // Set foundation tier
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'foundation';
        });

        // Create rate limiter for IP 1
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $rate_limiter_1 = new Rate_Limiter('test_limiter');
        $limit = $rate_limiter_1->get_user_limit()['requests'];

        // Use up all requests for IP 1
        for ($i = 0; $i < $limit; $i++) {
            $this->assertTrue($rate_limiter_1->check_limit());
        }

        // Verify IP 1 is at limit
        $this->assertFalse($rate_limiter_1->check_limit());

        // Create rate limiter for IP 2
        $_SERVER['REMOTE_ADDR'] = '192.168.1.2';
        $rate_limiter_2 = new Rate_Limiter('test_limiter');

        // Verify IP 2 has fresh limit
        $this->assertTrue(
            $rate_limiter_2->check_limit(),
            'IP 2 should have separate rate limit'
        );

        // Verify IP 2 has full limit
        $this->assertEquals(
            $limit - 1,
            $rate_limiter_2->get_remaining(),
            'IP 2 should have full limit minus one request'
        );
    }

    public function test_proxy_ip_detection() {
        // Reset transients
        global $wp_transients;
        $wp_transients = [];

        // Set foundation tier
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'foundation';
        });

        $this->rate_limiter = new Rate_Limiter('test_limiter', 1);

        // Test with direct IP
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $reflection = new \ReflectionClass($this->rate_limiter);
        $method = $reflection->getMethod('get_client_ip');
        $method->setAccessible(true);

        $this->assertEquals(
            '192.168.1.1',
            $method->invoke($this->rate_limiter),
            'Should use REMOTE_ADDR when available'
        );
    }

    public function test_invalid_ip_handling() {
        // Reset transients
        global $wp_transients;
        $wp_transients = [];

        // Set foundation tier
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'foundation';
        });

        $this->rate_limiter = new Rate_Limiter('test_limiter', 1);

        // Test with invalid IP
        $_SERVER['REMOTE_ADDR'] = 'invalid-ip';

        // Should use default IP for invalid addresses
        $reflection = new \ReflectionClass($this->rate_limiter);
        $method = $reflection->getMethod('get_client_ip');
        $method->setAccessible(true);

        $this->assertEquals(
            '0.0.0.0',
            $method->invoke($this->rate_limiter),
            'Should use default IP for invalid addresses'
        );

        // Test with missing IP
        unset($_SERVER['REMOTE_ADDR']);

        $this->assertEquals(
            '0.0.0.0',
            $method->invoke($this->rate_limiter),
            'Should use default IP when REMOTE_ADDR is not set'
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

    public function test_tier_based_rate_limits() {
        $tiers = [
            'foundation' => 60,
            'performance' => 120,
            'transformation' => 180
        ];

        foreach ($tiers as $tier => $limit) {
            // Reset filters and transients
            global $wp_filter, $wp_transients;
            $wp_filter = [];
            $wp_transients = [];

            // Set up tier filter
            add_filter('athlete_dashboard_get_user_tier', function() use ($tier) {
                return $tier;
            });

            $this->rate_limiter = new Rate_Limiter('test_limiter', 1);

            // Make requests up to limit
            for ($i = 0; $i < $limit; $i++) {
                $this->assertTrue(
                    $this->rate_limiter->check_limit(),
                    "Request {$i} should be allowed for {$tier} tier"
                );
            }

            // Next request should be denied
            $this->assertFalse(
                $this->rate_limiter->check_limit(),
                "{$tier} tier should be limited after {$limit} requests"
            );
        }
    }

    public function test_invalid_tier_fallback() {
        // Create a user
        $user_id = $this->factory->create();
        wp_set_current_user($user_id);

        // Mock filter to return invalid tier
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'invalid_tier';
        });

        $rate_limiter = new Rate_Limiter('test_invalid');
        
        // Should fallback to foundation tier (60 requests/hour)
        for ($i = 0; $i < 60; $i++) {
            $this->assertTrue($rate_limiter->check_limit(), "Request $i should be allowed with invalid tier");
        }
        $this->assertFalse($rate_limiter->check_limit(), 'Should be limited after 60 requests with invalid tier');
    }

    public function test_tier_upgrade_handling() {
        // Reset transients
        global $wp_transients;
        $wp_transients = [];

        // Start with foundation tier
        $current_tier = 'foundation';
        add_filter('athlete_dashboard_get_user_tier', function() use (&$current_tier) {
            return $current_tier;
        });

        $this->rate_limiter = new Rate_Limiter('test_limiter', 1);
        $foundation_limit = $this->rate_limiter->get_user_limit()['requests'];

        // Use up some requests as foundation tier
        for ($i = 0; $i < $foundation_limit - 1; $i++) {
            $this->assertTrue(
                $this->rate_limiter->check_limit(),
                "Foundation tier request {$i} should be allowed"
            );
        }

        // Upgrade to transformation tier
        $current_tier = 'transformation';
        $transformation_limit = $this->rate_limiter->get_user_limit()['requests'];

        // Should be able to make more requests up to transformation tier limit
        for ($i = $foundation_limit - 1; $i < $transformation_limit - 1; $i++) {
            $this->assertTrue(
                $this->rate_limiter->check_limit(),
                "Transformation tier request {$i} should be allowed after upgrade"
            );
        }

        // Verify remaining requests for transformation tier
        $this->assertEquals(
            1,
            $this->rate_limiter->get_remaining(),
            'Should have one request remaining after upgrade'
        );

        // Use final request
        $this->assertTrue(
            $this->rate_limiter->check_limit(),
            'Final request should be allowed'
        );

        // Verify no requests remaining
        $this->assertEquals(
            0,
            $this->rate_limiter->get_remaining(),
            'Should have no requests remaining'
        );
    }

    public function test_tier_rate_limit_headers() {
        // Create a user
        $user_id = $this->factory->create();
        wp_set_current_user($user_id);
        
        $rate_limiter = new Rate_Limiter('test_headers');
        
        // Test foundation tier headers
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'foundation';
        });
        
        $headers = $rate_limiter->get_rate_limit_headers();
        $this->assertEquals(60, $headers['X-RateLimit-Limit'], 'Foundation tier should have 60 requests limit');
        
        // Test performance tier headers
        remove_all_filters('athlete_dashboard_get_user_tier');
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'performance';
        });
        
        $headers = $rate_limiter->get_rate_limit_headers();
        $this->assertEquals(120, $headers['X-RateLimit-Limit'], 'Performance tier should have 120 requests limit');
        
        // Test transformation tier headers
        remove_all_filters('athlete_dashboard_get_user_tier');
        add_filter('athlete_dashboard_get_user_tier', function() {
            return 'transformation';
        });
        
        $headers = $rate_limiter->get_rate_limit_headers();
        $this->assertEquals(180, $headers['X-RateLimit-Limit'], 'Transformation tier should have 180 requests limit');
    }
} 