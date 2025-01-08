<?php
namespace AthleteDashboard\Tests\RestApi;

use AthleteDashboard\RestApi\Overview_Controller;
use AthleteDashboard\Tests\TestCase;
use WP_REST_Request;

class Overview_Controller_Test extends TestCase {
    private $controller;
    private $user_id;
    private $admin_id;

    public function setUp(): void {
        parent::setUp();
        
        // Create test users
        $this->user_id = 1;
        $this->admin_id = 2;
        
        // Create controller
        $this->controller = new Overview_Controller();
        
        // Mock WordPress functions
        $this->mockWordPressFunctions();
        $this->mockWordPressCache();
        $this->mockWordPressTransients();
    }

    public function test_register_routes() {
        $this->mockWordPressRest();
        $this->controller->register_routes();
        $this->assertTrue(true); // If we got here, no exceptions were thrown
    }

    public function test_get_overview_data() {
        // Set current user
        \WP_Mock::userFunction('get_current_user_id')->andReturn($this->user_id);

        // Mock user meta
        \WP_Mock::userFunction('get_user_meta')
            ->with($this->user_id, 'workouts_completed', true)
            ->andReturn(5);

        \WP_Mock::userFunction('get_user_meta')
            ->with($this->user_id, 'nutrition_score', true)
            ->andReturn(80);

        // Create test request
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview/' . $this->user_id);
        $request->set_param('user_id', $this->user_id);
        $response = $this->controller->get_overview_data($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('recent_activity', $data);
        $this->assertArrayHasKey('goals', $data);
        
        $this->assertEquals(5, $data['stats']['workouts_completed']);
        $this->assertEquals(80, $data['stats']['nutrition_score']);

        // Test caching
        \WP_Mock::userFunction('get_user_meta')
            ->with($this->user_id, 'workouts_completed', true)
            ->andReturn(10);

        $cached_response = $this->controller->get_overview_data($request);
        $cached_data = $cached_response->get_data();
        
        // Should return cached value
        $this->assertEquals(5, $cached_data['stats']['workouts_completed']);
    }

    public function test_update_goal() {
        // Set current user
        \WP_Mock::userFunction('get_current_user_id')->andReturn($this->user_id);

        // Mock post meta
        \WP_Mock::userFunction('update_post_meta')
            ->with(1, 'goal_progress', 50)
            ->andReturn(true);

        \WP_Mock::userFunction('get_post_field')
            ->with('post_author', 1)
            ->andReturn($this->user_id);

        // Create test request
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/overview/goals/1');
        $request->set_param('goal_id', 1);
        $request->set_param('progress', 50);

        // Update goal
        $response = $this->controller->update_goal($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    public function test_dismiss_activity() {
        // Set current user
        \WP_Mock::userFunction('get_current_user_id')->andReturn($this->user_id);

        // Mock post meta
        \WP_Mock::userFunction('update_post_meta')
            ->with(1, 'activity_dismissed', true)
            ->andReturn(true);

        \WP_Mock::userFunction('get_post_field')
            ->with('post_author', 1)
            ->andReturn($this->user_id);

        // Create test request
        $request = new WP_REST_Request('DELETE', '/athlete-dashboard/v1/overview/activity/1');
        $request->set_param('activity_id', 1);

        // Dismiss activity
        $response = $this->controller->dismiss_activity($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    public function test_permission_check() {
        // Test unauthenticated access
        \WP_Mock::userFunction('is_user_logged_in')->andReturn(false);
        
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview/' . $this->user_id);
        $request->set_param('user_id', $this->user_id);
        $check = $this->controller->check_permission($request);
        
        $this->assertInstanceOf('WP_Error', $check);
        $this->assertEquals(401, $check->get_error_data()['status']);

        // Test accessing other user's data
        \WP_Mock::userFunction('is_user_logged_in')->andReturn(true);
        \WP_Mock::userFunction('get_current_user_id')->andReturn($this->user_id);
        \WP_Mock::userFunction('current_user_can')->andReturn(false);
        
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview/999');
        $request->set_param('user_id', 999);
        
        $response = $this->controller->get_overview_data($request);
        
        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals(403, $response->get_error_data()['status']);

        // Test admin access to other user's data
        \WP_Mock::userFunction('get_current_user_id')->andReturn($this->admin_id);
        \WP_Mock::userFunction('current_user_can')
            ->with('administrator')
            ->andReturn(true);
        
        $response = $this->controller->get_overview_data($request);
        
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
    }

    public function test_rate_limiting() {
        // Set current user
        \WP_Mock::userFunction('get_current_user_id')->andReturn($this->user_id);

        // Mock rate limiter to allow first 100 requests
        \WP_Mock::userFunction('get_transient')
            ->andReturnUsing(function($key) {
                static $count = 0;
                if (strpos($key, 'rate_limit_') === 0) {
                    return $count++;
                }
                return false;
            });

        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview/' . $this->user_id);
        $request->set_param('user_id', $this->user_id);

        // Make requests up to the limit
        for ($i = 0; $i < 100; $i++) {
            $response = $this->controller->get_overview_data($request);
            $this->assertEquals(200, $response->get_status());
        }

        // Next request should fail due to rate limiting
        $response = $this->controller->get_overview_data($request);
        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals(429, $response->get_error_data()['status']);
    }
} 