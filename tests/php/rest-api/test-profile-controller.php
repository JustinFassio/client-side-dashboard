<?php
namespace AthleteDashboard\Tests\RestApi;

use AthleteDashboard\RestApi\Profile_Controller;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Server;

class Profile_Controller_Test extends WP_UnitTestCase {
    private $server;
    private $user_id;
    private $admin_id;
    private $controller;

    public function setUp(): void {
        parent::setUp();
        
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server;
        do_action('rest_api_init');
        
        $this->user_id = $this->factory->user->create(['role' => 'subscriber']);
        $this->admin_id = $this->factory->user->create(['role' => 'administrator']);
        $this->controller = new Profile_Controller();
    }

    public function test_register_routes() {
        $routes = $this->server->get_routes();
        $this->assertArrayHasKey('/athlete-dashboard/v1/profile/(?P<id>\d+)', $routes);
        $this->assertArrayHasKey('/athlete-dashboard/v1/profile/bulk', $routes);
    }

    public function test_get_profile() {
        wp_set_current_user($this->user_id);
        
        // Test getting own profile
        $request = new WP_REST_Request('GET', "/athlete-dashboard/v1/profile/{$this->user_id}");
        $response = $this->controller->get_profile($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($this->user_id, $data['id']);
        
        // Test getting another user's profile (should fail)
        $other_user_id = $this->factory->user->create();
        $request = new WP_REST_Request('GET', "/athlete-dashboard/v1/profile/{$other_user_id}");
        $response = $this->controller->get_profile($request);
        
        $this->assertWPError($response);
        $this->assertEquals('rest_forbidden', $response->get_error_code());
    }

    public function test_update_profile() {
        wp_set_current_user($this->user_id);
        
        $request = new WP_REST_Request('POST', "/athlete-dashboard/v1/profile/{$this->user_id}");
        $request->set_body_params([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'age' => 25,
            'height' => 180,
            'weight' => 75,
            'injuries' => [
                [
                    'name' => 'Sprained Ankle',
                    'description' => 'Left ankle sprain',
                    'date' => '2024-01-15',
                    'status' => 'active'
                ]
            ]
        ]);
        
        $response = $this->controller->update_profile($request);
        $this->assertEquals(200, $response->get_status());
        
        // Verify data was saved correctly
        $data = $response->get_data();
        $this->assertEquals('John', $data['firstName']);
        $this->assertEquals('Doe', $data['lastName']);
        $this->assertCount(1, $data['injuries']);
    }

    public function test_bulk_update_profiles() {
        wp_set_current_user($this->admin_id);
        
        $user1 = $this->factory->user->create();
        $user2 = $this->factory->user->create();
        
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile/bulk');
        $request->set_body_params([
            'profiles' => [
                [
                    'id' => $user1,
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ],
                [
                    'id' => $user2,
                    'firstName' => 'Jane',
                    'lastName' => 'Smith'
                ]
            ]
        ]);
        
        $response = $this->controller->bulk_update_profiles($request);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertCount(2, $data['success']);
        $this->assertEmpty($data['errors']);
    }

    public function test_permission_checks() {
        // Test non-admin accessing bulk endpoint
        wp_set_current_user($this->user_id);
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile/bulk');
        $response = $this->controller->bulk_update_profiles($request);
        $this->assertWPError($response);
        $this->assertEquals('rest_forbidden', $response->get_error_code());
        
        // Test admin accessing bulk endpoint
        wp_set_current_user($this->admin_id);
        $response = $this->controller->bulk_update_profiles($request);
        $this->assertNotWPError($response);
    }

    public function test_transaction_rollback() {
        wp_set_current_user($this->user_id);
        
        // Create invalid request that should trigger rollback
        $request = new WP_REST_Request('POST', "/athlete-dashboard/v1/profile/{$this->user_id}");
        $request->set_body_params([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'invalid-email' // This should trigger validation error
        ]);
        
        $response = $this->controller->update_profile($request);
        $this->assertWPError($response);
        
        // Verify no partial updates were saved
        $saved_first_name = get_user_meta($this->user_id, 'first_name', true);
        $this->assertEmpty($saved_first_name);
    }

    public function test_rate_limiting() {
        wp_set_current_user($this->user_id);
        
        // Make multiple requests to trigger rate limit
        for ($i = 0; $i < 201; $i++) {
            $request = new WP_REST_Request('GET', "/athlete-dashboard/v1/profile/{$this->user_id}");
            $response = $this->controller->get_profile($request);
            
            if ($i >= 200) {
                $this->assertWPError($response);
                $this->assertEquals('rate_limit_exceeded', $response->get_error_code());
                break;
            }
        }
    }

    public function test_error_handling() {
        wp_set_current_user($this->user_id);
        
        // Test non-existent profile
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/profile/999999');
        $response = $this->controller->get_profile($request);
        $this->assertWPError($response);
        $this->assertEquals('profile_not_found', $response->get_error_code());
        
        // Test invalid data
        $request = new WP_REST_Request('POST', "/athlete-dashboard/v1/profile/{$this->user_id}");
        $request->set_body_params([
            'firstName' => '', // Empty required field
            'age' => 'invalid' // Invalid type
        ]);
        
        $response = $this->controller->update_profile($request);
        $this->assertWPError($response);
        $error_data = $response->get_error_data();
        $this->assertArrayHasKey('firstName', $error_data['errors']);
        $this->assertArrayHasKey('age', $error_data['errors']);
    }
} 