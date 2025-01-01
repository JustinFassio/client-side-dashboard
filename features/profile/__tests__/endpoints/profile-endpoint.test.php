<?php
/**
 * Profile Endpoint Tests
 * 
 * Essential tests for profile data storage and retrieval.
 */

class Profile_Endpoint_Test {
    private $user_id;

    public function setUp() {
        $this->user_id = wp_create_user('testuser', 'password', 'test@example.com');
        wp_set_current_user($this->user_id);
    }

    public function tearDown() {
        wp_delete_user($this->user_id);
    }

    /**
     * Test basic profile operations (create/update/read).
     */
    public function test_profile_crud() {
        // Create/Update profile
        $data = array(
            'firstName' => 'John',
            'lastName' => 'Doe'
        );

        $response = $this->make_request('POST', '/athlete-dashboard/v1/profile', $data);
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('John', $response['data']['firstName']);

        // Read profile
        $response = $this->make_request('GET', '/athlete-dashboard/v1/profile/' . $this->user_id);
        $this->assertEquals(200, $response['code']);
        $this->assertEquals('John', $response['data']['firstName']);
    }

    /**
     * Test basic validation.
     */
    public function test_basic_validation() {
        $data = array(
            'firstName' => '', // Empty required field
        );

        $response = $this->make_request('POST', '/athlete-dashboard/v1/profile', $data);
        $this->assertEquals(400, $response['code']);
    }

    /**
     * Test authentication.
     */
    public function test_auth() {
        wp_set_current_user(0);
        $response = $this->make_request('GET', '/athlete-dashboard/v1/profile/1');
        $this->assertEquals(401, $response['code']);
    }

    /**
     * Helper function to make REST API requests.
     */
    private function make_request($method, $endpoint, $data = null) {
        $request = new WP_REST_Request($method, $endpoint);
        if ($data) {
            $request->set_body_params($data);
        }
        
        $response = rest_do_request($request);
        return array(
            'code' => $response->get_status(),
            'data' => $response->get_data(),
            'message' => $response->get_data()['message'] ?? ''
        );
    }

    /**
     * Simple assertion helpers.
     */
    private function assertEquals($expected, $actual) {
        if ($expected !== $actual) {
            throw new Exception("Expected $expected but got $actual");
        }
    }
} 