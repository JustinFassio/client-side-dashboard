<?php
namespace AthleteDashboard\Tests\RestApi;

use WP_UnitTestCase;
use WP_REST_Request;
use AthleteDashboard\RestApi\Overview_Controller;

class Overview_Controller_Test extends WP_UnitTestCase {
    private $controller;
    private $user_id;

    public function setUp(): void {
        parent::setUp();
        
        // Create test user
        $this->user_id = $this->factory->user->create([
            'role' => 'subscriber'
        ]);
        
        // Create controller
        $this->controller = new Overview_Controller();
        
        // Set current user
        wp_set_current_user($this->user_id);
    }

    public function tearDown(): void {
        parent::tearDown();
        wp_delete_user($this->user_id);
    }

    public function test_register_routes() {
        global $wp_rest_server;
        $routes = $wp_rest_server->get_routes();
        
        $this->assertArrayHasKey('/custom/v1/overview/(?P<user_id>\d+)', $routes);
        $this->assertArrayHasKey('/custom/v1/overview/goals/(?P<goal_id>\d+)', $routes);
        $this->assertArrayHasKey('/custom/v1/overview/activity/(?P<activity_id>\d+)', $routes);
    }

    public function test_get_overview_data() {
        // Set up test data
        update_user_meta($this->user_id, 'workouts_completed', 5);
        update_user_meta($this->user_id, 'nutrition_score', 80);

        // Create test request
        $request = new WP_REST_Request('GET', '/custom/v1/overview/' . $this->user_id);
        $response = $this->controller->get_overview_data($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('recent_activity', $data);
        $this->assertArrayHasKey('goals', $data);
        
        $this->assertEquals(5, $data['stats']['workouts_completed']);
        $this->assertEquals(80, $data['stats']['nutrition_score']);
    }

    public function test_update_goal() {
        // Create test goal
        $goal_id = $this->factory->post->create([
            'post_type' => 'goal',
            'post_author' => $this->user_id
        ]);

        // Create test request
        $request = new WP_REST_Request('PUT', '/custom/v1/overview/goals/' . $goal_id);
        $request->set_param('progress', 75);
        
        $response = $this->controller->update_goal($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals(75, get_post_meta($goal_id, 'goal_progress', true));
    }

    public function test_dismiss_activity() {
        // Create test activity
        $activity_id = $this->factory->post->create([
            'post_type' => 'activity',
            'post_author' => $this->user_id
        ]);

        // Create test request
        $request = new WP_REST_Request('DELETE', '/custom/v1/overview/activity/' . $activity_id);
        
        $response = $this->controller->dismiss_activity($request);
        
        $this->assertEquals(200, $response->get_status());
        $this->assertTrue((bool) get_post_meta($activity_id, 'activity_dismissed', true));
    }

    public function test_permission_check() {
        // Test unauthenticated access
        wp_set_current_user(0);
        $request = new WP_REST_Request('GET', '/custom/v1/overview/' . $this->user_id);
        $check = $this->controller->check_permission($request);
        
        $this->assertInstanceOf('WP_Error', $check);
        $this->assertEquals(401, $check->get_error_data()['status']);

        // Test accessing other user's data
        $other_user_id = $this->factory->user->create();
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('GET', '/custom/v1/overview/' . $other_user_id);
        $request->set_param('user_id', $other_user_id);
        
        $check = $this->controller->check_permission($request);
        
        $this->assertInstanceOf('WP_Error', $check);
        $this->assertEquals(403, $check->get_error_data()['status']);
    }
} 