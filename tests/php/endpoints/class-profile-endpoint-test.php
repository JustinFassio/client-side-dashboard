<?php
/**
 * Class Profile_Endpoint_Test
 *
 * @package Athlete_Dashboard
 */

class Profile_Endpoint_Test extends WP_Test_REST_Controller_Testcase {
    /**
     * Test user ID.
     *
     * @var int
     */
    protected static $test_user_id;

    /**
     * Admin user ID.
     *
     * @var int
     */
    protected static $admin_id;

    /**
     * Setup before class.
     */
    public static function wpSetUpBeforeClass($factory) {
        parent::wpSetUpBeforeClass($factory);

        // Create test users
        self::$test_user_id = $factory->user->create(array(
            'role' => 'subscriber',
            'user_login' => 'testathlete',
            'user_email' => 'test@athlete.com',
            'display_name' => 'Test Athlete'
        ));
        
        self::$admin_id = $factory->user->create(array(
            'role' => 'administrator',
            'user_login' => 'testadmin',
            'user_email' => 'admin@athlete.com'
        ));

        // Initialize test profile data
        update_user_meta(self::$test_user_id, '_profile_first_name', 'John');
        update_user_meta(self::$test_user_id, '_profile_last_name', 'Doe');
        update_user_meta(self::$test_user_id, '_profile_age', 25);
        update_user_meta(self::$test_user_id, '_profile_height', 180);
        update_user_meta(self::$test_user_id, '_profile_weight', 75);
    }

    /**
     * Setup test environment.
     */
    public function set_up() {
        parent::set_up();
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server;
        do_action('rest_api_init');
    }

    /**
     * Test if our namespace is registered.
     */
    public function test_namespace_registration() {
        $namespaces = $this->server->get_namespaces();
        $this->assertContains('athlete-dashboard/v1', $namespaces, 'API namespace not registered');
    }

    /**
     * Test if profile routes exist.
     */
    public function test_route_existence() {
        $routes = $this->server->get_routes();
        $this->assertArrayHasKey('/athlete-dashboard/v1/profile', $routes, 'Profile route not registered');
        $this->assertArrayHasKey('/athlete-dashboard/v1/profile/(?P<id>[\d]+)', $routes, 'Profile ID route not registered');
    }

    /**
     * Test getting profile data.
     */
    public function test_get_profile() {
        wp_set_current_user(self::$test_user_id);
        
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/profile/' . self::$test_user_id);
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('John', $data['firstName']);
        $this->assertEquals('Doe', $data['lastName']);
        $this->assertEquals(25, $data['age']);
        $this->assertEquals(180, $data['height']);
        $this->assertEquals(75, $data['weight']);
    }

    /**
     * Test unauthorized profile access.
     */
    public function test_get_profile_unauthorized() {
        wp_set_current_user(0); // Set to logged out user
        
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/profile/' . self::$test_user_id);
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(401, $response->get_status());
    }

    /**
     * Test accessing another user's profile.
     */
    public function test_get_other_user_profile() {
        wp_set_current_user(self::$test_user_id);
        
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/profile/' . self::$admin_id);
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(403, $response->get_status());
    }

    /**
     * Test updating profile data.
     */
    public function test_update_profile() {
        wp_set_current_user(self::$test_user_id);
        
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'userId' => self::$test_user_id,
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'age' => 30,
            'height' => 170,
            'weight' => 65
        ));
        
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('Jane', $data['firstName']);
        $this->assertEquals('Smith', $data['lastName']);
        $this->assertEquals(30, $data['age']);
        
        // Verify data was actually saved
        $this->assertEquals('Jane', get_user_meta(self::$test_user_id, '_profile_first_name', true));
        $this->assertEquals(30, get_user_meta(self::$test_user_id, '_profile_age', true));
    }

    /**
     * Test invalid profile data.
     */
    public function test_update_profile_invalid_data() {
        wp_set_current_user(self::$test_user_id);
        
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'userId' => self::$test_user_id,
            'age' => -5, // Invalid age
            'weight' => 0 // Invalid weight
        ));
        
        $response = $this->server->dispatch($request);
        
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('message', $data);
    }

    /**
     * Test profile data sanitization.
     */
    public function test_profile_data_sanitization() {
        wp_set_current_user(self::$test_user_id);
        
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'userId' => self::$test_user_id,
            'firstName' => ' John  ', // Extra spaces
            'email' => ' TEST@example.com  ', // Mixed case and spaces
            'phone' => '+1 (555) 123-4567' // Format with special characters
        ));
        
        $response = $this->server->dispatch($request);
        $data = $response->get_data();
        
        $this->assertEquals('John', $data['firstName']);
        $this->assertEquals('test@example.com', $data['email']);
        $this->assertEquals('+15551234567', $data['phone']);
    }

    /**
     * Test medical clearance flag.
     */
    public function test_medical_clearance_update() {
        wp_set_current_user(self::$test_user_id);
        
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'userId' => self::$test_user_id,
            'medicalClearance' => true
        ));
        
        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
        
        $this->assertEquals('1', get_user_meta(self::$test_user_id, '_profile_medical_clearance', true));
    }

    /**
     * Clean up test environment.
     */
    public function tear_down() {
        parent::tear_down();
        global $wp_rest_server;
        $wp_rest_server = null;
    }

    /**
     * Clean up after class.
     */
    public static function wpTearDownAfterClass() {
        self::delete_user(self::$test_user_id);
        self::delete_user(self::$admin_id);
        parent::wpTearDownAfterClass();
    }

    /**
     * Test boundary conditions for numeric fields.
     *
     * @dataProvider provide_boundary_test_data
     */
    public function test_numeric_field_boundaries($field, $value, $should_pass) {
        wp_set_current_user(self::$test_user_id);
        
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'userId' => self::$test_user_id,
            $field => $value
        ));
        
        $response = $this->server->dispatch($request);
        
        if ($should_pass) {
            $this->assertEquals(200, $response->get_status());
            $data = $response->get_data();
            $this->assertEquals($value, $data[$field]);
        } else {
            $this->assertEquals(400, $response->get_status());
        }
    }

    /**
     * Data provider for boundary tests.
     */
    public function provide_boundary_test_data() {
        return array(
            'valid age' => array('age', 25, true),
            'minimum age' => array('age', 13, true),
            'maximum age' => array('age', 100, true),
            'below minimum age' => array('age', 12, false),
            'above maximum age' => array('age', 101, false),
            'valid weight' => array('weight', 70, true),
            'minimum weight' => array('weight', 30, true),
            'maximum weight' => array('weight', 300, true),
            'below minimum weight' => array('weight', 29, false),
            'above maximum weight' => array('weight', 301, false),
            'valid height' => array('height', 170, true),
            'minimum height' => array('height', 100, true),
            'maximum height' => array('height', 250, true),
            'below minimum height' => array('height', 99, false),
            'above maximum height' => array('height', 251, false)
        );
    }

    /**
     * Test special character handling in text fields.
     */
    public function test_special_character_handling() {
        wp_set_current_user(self::$test_user_id);
        
        $special_chars = array(
            'firstName' => "O'Connor-Smith",
            'lastName' => "d'Artagnan-Müller",
            'displayName' => "João São Paulo III",
            'notes' => "Special chars: !@#$%^&*()_+ áéíóú"
        );
        
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array_merge(
            array('userId' => self::$test_user_id),
            $special_chars
        ));
        
        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        foreach ($special_chars as $field => $value) {
            $this->assertEquals($value, $data[$field]);
        }
    }

    /**
     * Test malformed data validation.
     */
    public function test_malformed_data_validation() {
        wp_set_current_user(self::$test_user_id);
        
        $test_cases = array(
            array(
                'data' => array('age' => 'not_a_number'),
                'expected_status' => 400,
                'message' => 'Age must be a number'
            ),
            array(
                'data' => array('email' => 'not_an_email'),
                'expected_status' => 400,
                'message' => 'Invalid email format'
            ),
            array(
                'data' => array('height' => array()),
                'expected_status' => 400,
                'message' => 'Height must be a number'
            )
        );
        
        foreach ($test_cases as $test) {
            $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
            $request->set_body_params(array_merge(
                array('userId' => self::$test_user_id),
                $test['data']
            ));
            
            $response = $this->server->dispatch($request);
            $this->assertEquals($test['expected_status'], $response->get_status());
            
            $data = $response->get_data();
            $this->assertStringContainsString($test['message'], $data['message']);
        }
    }

    /**
     * Test concurrent update handling.
     */
    public function test_concurrent_updates() {
        wp_set_current_user(self::$test_user_id);
        
        // First update
        $request1 = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request1->set_body_params(array(
            'userId' => self::$test_user_id,
            'firstName' => 'Update1',
            'version' => 1
        ));
        
        // Second update (concurrent)
        $request2 = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request2->set_body_params(array(
            'userId' => self::$test_user_id,
            'firstName' => 'Update2',
            'version' => 1
        ));
        
        $response1 = $this->server->dispatch($request1);
        $this->assertEquals(200, $response1->get_status());
        
        $response2 = $this->server->dispatch($request2);
        $this->assertEquals(409, $response2->get_status()); // Conflict
        
        // Verify the first update succeeded
        $this->assertEquals(
            'Update1',
            get_user_meta(self::$test_user_id, '_profile_first_name', true)
        );
    }

    /**
     * Test partial update validation.
     */
    public function test_partial_update() {
        wp_set_current_user(self::$test_user_id);
        
        // Initial state
        $initial_data = array(
            'firstName' => 'John',
            'lastName' => 'Doe',
            'age' => 25
        );
        
        foreach ($initial_data as $key => $value) {
            update_user_meta(self::$test_user_id, "_profile_" . strtolower($key), $value);
        }
        
        // Partial update
        $request = new WP_REST_Request('PATCH', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'userId' => self::$test_user_id,
            'firstName' => 'Jane'
        ));
        
        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
        
        // Verify only specified field was updated
        $this->assertEquals('Jane', get_user_meta(self::$test_user_id, '_profile_first_name', true));
        $this->assertEquals('Doe', get_user_meta(self::$test_user_id, '_profile_last_name', true));
        $this->assertEquals(25, get_user_meta(self::$test_user_id, '_profile_age', true));
    }

    /**
     * Test profile version tracking.
     */
    public function test_profile_version_tracking() {
        wp_set_current_user(self::$test_user_id);
        
        // Initial update
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'userId' => self::$test_user_id,
            'firstName' => 'Version1'
        ));
        
        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('version', $data);
        $initial_version = $data['version'];
        
        // Second update
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'userId' => self::$test_user_id,
            'firstName' => 'Version2',
            'version' => $initial_version
        ));
        
        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertGreaterThan($initial_version, $data['version']);
    }
} 