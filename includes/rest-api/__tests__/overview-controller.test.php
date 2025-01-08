<?php
namespace AthleteDashboard\Tests\RestApi;

use WP_UnitTestCase;
use WP_REST_Request;
use AthleteDashboard\RestApi\Overview_Controller;
use AthleteDashboard\Services\Cache_Service;

class Overview_Controller_Test extends WP_UnitTestCase {
    private $controller;
    private $user_id;
    private $admin_id;

    public function setUp(): void {
        parent::setUp();
        
        // Create test users
        $this->user_id = $this->factory->user->create([
            'role' => 'subscriber'
        ]);
        $this->admin_id = $this->factory->user->create([
            'role' => 'administrator'
        ]);
        
        // Create controller
        $this->controller = new Overview_Controller();
        
        // Clear cache before each test
        wp_cache_flush();
    }

    public function tearDown(): void {
        parent::tearDown();
        wp_delete_user($this->user_id);
        wp_delete_user($this->admin_id);
        wp_cache_flush();
    }

    public function test_register_routes() {
        global $wp_rest_server;
        $routes = $wp_rest_server->get_routes();
        
        $this->assertArrayHasKey('/athlete-dashboard/v1/overview/(?P<user_id>\d+)', $routes);
        $this->assertArrayHasKey('/athlete-dashboard/v1/overview/goals/(?P<goal_id>\d+)', $routes);
        $this->assertArrayHasKey('/athlete-dashboard/v1/overview/activity/(?P<activity_id>\d+)', $routes);
    }

    public function test_get_overview_data() {
        // Set current user
        wp_set_current_user($this->user_id);

        // Set up test data
        update_user_meta($this->user_id, 'workouts_completed', 5);
        update_user_meta($this->user_id, 'nutrition_score', 80);

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
        update_user_meta($this->user_id, 'workouts_completed', 10);
        $cached_response = $this->controller->get_overview_data($request);
        $cached_data = $cached_response->get_data();
        
        // Should return cached value
        $this->assertEquals(5, $cached_data['stats']['workouts_completed']);

        // Clear cache and verify new value
        Cache_Service::invalidate_user_data($this->user_id, 'overview');
        $fresh_response = $this->controller->get_overview_data($request);
        $fresh_data = $fresh_response->get_data();
        
        $this->assertEquals(10, $fresh_data['stats']['workouts_completed']);
    }

    public function test_update_goal() {
        wp_set_current_user($this->user_id);

        // Create test goal
        $goal_id = $this->factory->post->create([
            'post_type' => 'goal',
            'post_author' => $this->user_id
        ]);

        // Create test request
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/overview/goals/' . $goal_id);
        $request->set_param('goal_id', $goal_id);
        $request->set_param('progress', 50);

        // Update goal
        $response = $this->controller->update_goal($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals(50, get_post_meta($goal_id, 'goal_progress', true));

        // Verify cache invalidation
        $overview_request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview/' . $this->user_id);
        $overview_request->set_param('user_id', $this->user_id);
        
        $overview_response = $this->controller->get_overview_data($overview_request);
        $overview_data = $overview_response->get_data();
        
        $found_goal = false;
        foreach ($overview_data['goals'] as $goal) {
            if ($goal['id'] === $goal_id) {
                $this->assertEquals(50, $goal['progress']);
                $found_goal = true;
                break;
            }
        }
        $this->assertTrue($found_goal, 'Updated goal not found in overview data');
    }

    public function test_dismiss_activity() {
        wp_set_current_user($this->user_id);

        // Create test activity
        $activity_id = $this->factory->post->create([
            'post_type' => 'activity',
            'post_author' => $this->user_id
        ]);

        // Create test request
        $request = new WP_REST_Request('DELETE', '/athlete-dashboard/v1/overview/activity/' . $activity_id);
        $request->set_param('activity_id', $activity_id);

        // Dismiss activity
        $response = $this->controller->dismiss_activity($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue((bool) get_post_meta($activity_id, 'activity_dismissed', true));

        // Verify cache invalidation
        $overview_request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview/' . $this->user_id);
        $overview_request->set_param('user_id', $this->user_id);
        
        $overview_response = $this->controller->get_overview_data($overview_request);
        $overview_data = $overview_response->get_data();
        
        $found_activity = false;
        foreach ($overview_data['recent_activity'] as $activity) {
            if ($activity['id'] === $activity_id) {
                $found_activity = true;
                break;
            }
        }
        $this->assertFalse($found_activity, 'Dismissed activity still present in overview data');
    }

    public function test_permission_check() {
        // Test unauthenticated access
        wp_set_current_user(0);
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview/' . $this->user_id);
        $request->set_param('user_id', $this->user_id);
        $check = $this->controller->check_permission($request);
        
        $this->assertInstanceOf('WP_Error', $check);
        $this->assertEquals(401, $check->get_error_data()['status']);

        // Test accessing other user's data
        wp_set_current_user($this->user_id);
        $other_user_id = $this->factory->user->create();
        
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/overview/' . $other_user_id);
        $request->set_param('user_id', $other_user_id);
        
        $response = $this->controller->get_overview_data($request);
        
        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals(403, $response->get_error_data()['status']);

        // Test admin access to other user's data
        wp_set_current_user($this->admin_id);
        $response = $this->controller->get_overview_data($request);
        
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
    }

    public function test_rate_limiting() {
        wp_set_current_user($this->user_id);
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