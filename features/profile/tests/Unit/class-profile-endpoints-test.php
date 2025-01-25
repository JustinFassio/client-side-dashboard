/**
 * Unit tests for Profile_Endpoints class.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit
 * @deprecated 1.0.0 Use modular endpoint tests in features/profile/tests/Unit/Endpoints/* instead.
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit;

use AthleteDashboard\Features\Profile\API\Profile_Endpoints;
use WP_REST_Request;
use WP_UnitTestCase;
use WP_Error;

/**
 * @deprecated 1.0.0 This test class is deprecated. Use the new modular endpoint tests instead.
 */
class Profile_Endpoints_Test extends \WP_UnitTestCase {
    /**
     * Test user ID.
     *
     * @var int
     */
    private $test_user_id;

    /**
     * Set up test environment.
     */
    public function set_up() {
        parent::set_up();

        // Create a test user
        $this->test_user_id = $this->factory->user->create(array(
            'role' => 'subscriber',
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'display_name' => 'Test User',
        ));

        // Set current user
        wp_set_current_user($this->test_user_id);
    }

    /**
     * Test successful profile retrieval.
     */
    public function test_get_profile_success() {
        // Set up test profile data
        $profile_data = array(
            'age' => 25,
            'gender' => 'male',
            'activityLevel' => 'moderate',
        );
        update_user_meta($this->test_user_id, Profile_Endpoints::META_KEY, $profile_data);

        // Make the request
        $response = Profile_Endpoints::get_profile();
        $data = $response->get_data();

        // Assert response structure
        $this->assertTrue($data['success']);
        $this->assertNull($data['error']);
        $this->assertArrayHasKey('profile', $data['data']);

        // Assert profile data
        $profile = $data['data']['profile'];
        $this->assertEquals($this->test_user_id, $profile['id']);
        $this->assertEquals('Test User', $profile['name']);
        $this->assertEquals('testuser', $profile['username']);
        $this->assertEquals('test@example.com', $profile['email']);
        $this->assertEquals(25, $profile['age']);
        $this->assertEquals('male', $profile['gender']);
        $this->assertEquals('moderate', $profile['activityLevel']);
    }

    /**
     * Test profile retrieval with no data.
     */
    public function test_get_profile_no_data() {
        // Make the request without setting any profile data
        $response = Profile_Endpoints::get_profile();

        // Assert it's a WP_Error
        $this->assertInstanceOf(WP_Error::class, $response);
        
        // Check error structure
        $data = $response->get_error_data();
        $this->assertEquals(404, $data['status']);
        $this->assertFalse($data['success']);
        $this->assertEquals('not_found', $data['error']['code']);
        $this->assertEquals('No profile data found', $data['error']['message']);
    }

    /**
     * Test unauthorized profile access.
     */
    public function test_get_profile_unauthorized() {
        // Set current user to 0 (not logged in)
        wp_set_current_user(0);

        // Make the request
        $response = Profile_Endpoints::get_profile();

        // Assert it's a WP_Error
        $this->assertInstanceOf(WP_Error::class, $response);
        
        // Check error structure
        $data = $response->get_error_data();
        $this->assertEquals(401, $data['status']);
        $this->assertFalse($data['success']);
        $this->assertEquals('unauthorized', $data['error']['code']);
        $this->assertEquals('User not logged in', $data['error']['message']);
    }

    /**
     * Test successful profile update.
     */
    public function test_update_profile_success() {
        // Create a mock request
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $profile_data = array(
            'age' => 30,
            'gender' => 'female',
            'activityLevel' => 'very_active',
        );
        $request->set_body_params($profile_data);

        // Make the request
        $response = Profile_Endpoints::update_profile($request);
        $data = $response->get_data();

        // Assert response structure
        $this->assertTrue($data['success']);
        $this->assertNull($data['error']);
        $this->assertArrayHasKey('profile', $data['data']);

        // Assert updated profile data
        $profile = $data['data']['profile'];
        $this->assertEquals(30, $profile['age']);
        $this->assertEquals('female', $profile['gender']);
        $this->assertEquals('very_active', $profile['activityLevel']);

        // Verify data was actually saved
        $saved_data = get_user_meta($this->test_user_id, Profile_Endpoints::META_KEY, true);
        $this->assertEquals(30, $saved_data['age']);
        $this->assertEquals('female', $saved_data['gender']);
        $this->assertEquals('very_active', $saved_data['activityLevel']);
    }

    /**
     * Test profile update with validation errors.
     */
    public function test_update_profile_validation_error() {
        // Create a mock request with invalid data
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $invalid_data = array(
            'age' => 5, // Too young
            'gender' => 'invalid', // Invalid gender
            'activityLevel' => 'super_active', // Invalid activity level
        );
        $request->set_body_params($invalid_data);

        // Make the request
        $response = Profile_Endpoints::update_profile($request);

        // Assert it's a WP_Error
        $this->assertInstanceOf(WP_Error::class, $response);
        
        // Check error structure
        $data = $response->get_error_data();
        $this->assertEquals(400, $data['status']);
        $this->assertFalse($data['success']);
        $this->assertEquals('validation_failed', $data['error']['code']);
        $this->assertEquals('Profile data validation failed', $data['error']['message']);
        
        // Check validation details
        $this->assertArrayHasKey('details', $data['error']);
        $this->assertArrayHasKey('age', $data['error']['details']);
        $this->assertArrayHasKey('gender', $data['error']['details']);
        $this->assertArrayHasKey('activityLevel', $data['error']['details']);
    }

    /**
     * Test profile update with empty data.
     */
    public function test_update_profile_empty_data() {
        // Create a mock request with no data
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array());

        // Make the request
        $response = Profile_Endpoints::update_profile($request);

        // Assert it's a WP_Error
        $this->assertInstanceOf(WP_Error::class, $response);
        
        // Check error structure
        $data = $response->get_error_data();
        $this->assertEquals(400, $data['status']);
        $this->assertFalse($data['success']);
        $this->assertEquals('invalid_params', $data['error']['code']);
        $this->assertEquals('No profile data provided', $data['error']['message']);
    }

    /**
     * Test profile update with invalid age values.
     *
     * @dataProvider provide_invalid_age_data
     */
    public function test_update_profile_invalid_age($age, $expected_message) {
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array('age' => $age));

        $response = Profile_Endpoints::update_profile($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $data = $response->get_error_data();
        $this->assertEquals(400, $data['status']);
        $this->assertEquals('validation_failed', $data['error']['code']);
        $this->assertArrayHasKey('age', $data['error']['details']);
        $this->assertEquals($expected_message, $data['error']['details']['age']);
    }

    /**
     * Data provider for invalid age tests.
     */
    public function provide_invalid_age_data() {
        return array(
            'negative age' => array(-5, 'Age must be between 13 and 120'),
            'too young' => array(5, 'Age must be between 13 and 120'),
            'too old' => array(150, 'Age must be between 13 and 120'),
            'zero age' => array(0, 'Age must be between 13 and 120'),
        );
    }

    /**
     * Test profile update with invalid gender values.
     *
     * @dataProvider provide_invalid_gender_data
     */
    public function test_update_profile_invalid_gender($gender) {
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array('gender' => $gender));

        $response = Profile_Endpoints::update_profile($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $data = $response->get_error_data();
        $this->assertEquals(400, $data['status']);
        $this->assertEquals('validation_failed', $data['error']['code']);
        $this->assertArrayHasKey('gender', $data['error']['details']);
        $this->assertStringContainsString('Invalid gender value', $data['error']['details']['gender']);
    }

    /**
     * Data provider for invalid gender tests.
     */
    public function provide_invalid_gender_data() {
        return array(
            'invalid string' => array('invalid_gender'),
            'number' => array(123),
            'empty array' => array(array()),
            'boolean' => array(true),
        );
    }

    /**
     * Test profile update with invalid activity level values.
     *
     * @dataProvider provide_invalid_activity_level_data
     */
    public function test_update_profile_invalid_activity_level($level) {
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array('activityLevel' => $level));

        $response = Profile_Endpoints::update_profile($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $data = $response->get_error_data();
        $this->assertEquals(400, $data['status']);
        $this->assertEquals('validation_failed', $data['error']['code']);
        $this->assertArrayHasKey('activityLevel', $data['error']['details']);
        $this->assertStringContainsString('Invalid activity level', $data['error']['details']['activityLevel']);
    }

    /**
     * Data provider for invalid activity level tests.
     */
    public function provide_invalid_activity_level_data() {
        return array(
            'invalid string' => array('super_active'),
            'number' => array(123),
            'empty array' => array(array()),
            'boolean' => array(true),
        );
    }

    /**
     * Test profile update with invalid emergency contact data.
     */
    public function test_update_profile_invalid_emergency_contact() {
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'emergencyContactName' => '',  // Empty name
            'emergencyContactPhone' => 'not-a-phone-number'  // Invalid phone format
        ));

        $response = Profile_Endpoints::update_profile($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $data = $response->get_error_data();
        $this->assertEquals(400, $data['status']);
        $this->assertEquals('validation_failed', $data['error']['code']);
        
        $details = $data['error']['details'];
        $this->assertArrayHasKey('emergencyContactName', $details);
        $this->assertArrayHasKey('emergencyContactPhone', $details);
        $this->assertEquals('Emergency contact name cannot be empty', $details['emergencyContactName']);
        $this->assertEquals('Invalid emergency contact phone number', $details['emergencyContactPhone']);
    }

    /**
     * Test profile update with invalid array fields.
     */
    public function test_update_profile_invalid_array_fields() {
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'medicalConditions' => 'not-an-array',  // Should be array
            'exerciseLimitations' => array(''),  // Empty string in array
            'injuries' => array(
                array(
                    'name' => 'Knee Injury',
                    // Missing required fields
                )
            )
        ));

        $response = Profile_Endpoints::update_profile($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $data = $response->get_error_data();
        $this->assertEquals(400, $data['status']);
        $this->assertEquals('validation_failed', $data['error']['code']);
        
        $details = $data['error']['details'];
        $this->assertArrayHasKey('medicalConditions', $details);
        $this->assertArrayHasKey('exerciseLimitations_0', $details);
        $this->assertArrayHasKey('injuries_0', $details);
    }

    /**
     * Test profile update with partial valid data.
     */
    public function test_update_profile_partial_valid_data() {
        // First set some initial data
        $initial_data = array(
            'age' => 25,
            'gender' => 'male',
            'activityLevel' => 'moderate'
        );
        update_user_meta($this->test_user_id, Profile_Endpoints::META_KEY, $initial_data);

        // Update only age
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array('age' => 30));

        $response = Profile_Endpoints::update_profile($request);
        $data = $response->get_data();

        // Assert response structure
        $this->assertTrue($data['success']);
        $this->assertNull($data['error']);
        
        // Verify only age was updated, other fields remained
        $profile = $data['data']['profile'];
        $this->assertEquals(30, $profile['age']);
        $this->assertEquals('male', $profile['gender']);
        $this->assertEquals('moderate', $profile['activityLevel']);
    }

    /**
     * Test debug endpoint with authenticated user.
     */
    public function test_debug_endpoint_success() {
        // Set up test profile data
        $profile_data = array(
            'age' => 25,
            'gender' => 'male',
            'activityLevel' => 'moderate',
        );
        update_user_meta($this->test_user_id, Profile_Endpoints::META_KEY, $profile_data);

        // Create request
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/profile/test');
        $response = Profile_Endpoints::debug_endpoint($request);

        // Assert response structure
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();

        // Check debug data structure
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('Profile API is working', $data['message']);
        $this->assertArrayHasKey('timestamp', $data);
        
        // Check debug info
        $this->assertArrayHasKey('debug', $data);
        $debug = $data['debug'];
        $this->assertEquals($this->test_user_id, $debug['user_id']);
        $this->assertEquals(Profile_Endpoints::META_KEY, $debug['meta_key']);
        $this->assertEquals($profile_data, $debug['profile_data']);
        $this->assertArrayHasKey('all_meta', $debug);
    }

    /**
     * Test debug endpoint with unauthorized user.
     */
    public function test_debug_endpoint_unauthorized() {
        wp_set_current_user(0); // No user logged in

        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/profile/test');
        
        // The debug endpoint uses check_auth permission callback
        $this->assertFalse(Profile_Endpoints::check_auth());
    }

    /**
     * Test REST API integration for get profile.
     */
    public function test_rest_get_profile_integration() {
        wp_set_current_user($this->test_user_id);

        // Set up test profile data
        $profile_data = array(
            'age' => 25,
            'gender' => 'male',
            'activityLevel' => 'moderate',
        );
        update_user_meta($this->test_user_id, Profile_Endpoints::META_KEY, $profile_data);

        // Register routes (normally done by WordPress)
        Profile_Endpoints::register_routes();

        // Create and dispatch request
        $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/profile');
        $response = rest_get_server()->dispatch($request);

        // Assert response
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        // Check response structure
        $this->assertTrue($data['success']);
        $this->assertNull($data['error']);
        $this->assertArrayHasKey('profile', $data['data']);

        // Verify profile data
        $profile = $data['data']['profile'];
        $this->assertEquals(25, $profile['age']);
        $this->assertEquals('male', $profile['gender']);
        $this->assertEquals('moderate', $profile['activityLevel']);
    }

    /**
     * Test REST API integration for update profile.
     */
    public function test_rest_update_profile_integration() {
        wp_set_current_user($this->test_user_id);

        // Register routes
        Profile_Endpoints::register_routes();

        // Create and dispatch request
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $request->set_body_params(array(
            'age' => 30,
            'gender' => 'female',
            'activityLevel' => 'very_active',
        ));
        $response = rest_get_server()->dispatch($request);

        // Assert response
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        // Check response structure
        $this->assertTrue($data['success']);
        $this->assertNull($data['error']);
        $this->assertArrayHasKey('profile', $data['data']);

        // Verify updated profile data
        $profile = $data['data']['profile'];
        $this->assertEquals(30, $profile['age']);
        $this->assertEquals('female', $profile['gender']);
        $this->assertEquals('very_active', $profile['activityLevel']);

        // Verify data was actually saved
        $saved_data = get_user_meta($this->test_user_id, Profile_Endpoints::META_KEY, true);
        $this->assertEquals(30, $saved_data['age']);
        $this->assertEquals('female', $saved_data['gender']);
        $this->assertEquals('very_active', $saved_data['activityLevel']);
    }

    /**
     * Test REST API integration for unauthorized access.
     */
    public function test_rest_unauthorized_access() {
        wp_set_current_user(0); // No user logged in

        // Register routes
        Profile_Endpoints::register_routes();

        // Test GET request
        $get_request = new WP_REST_Request('GET', '/athlete-dashboard/v1/profile');
        $get_response = rest_get_server()->dispatch($get_request);
        $this->assertEquals(401, $get_response->get_status());

        // Test POST request
        $post_request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
        $post_request->set_body_params(array('age' => 30));
        $post_response = rest_get_server()->dispatch($post_request);
        $this->assertEquals(401, $post_response->get_status());
    }

    /**
     * Test REST API integration with invalid method.
     */
    public function test_rest_invalid_method() {
        wp_set_current_user($this->test_user_id);

        // Register routes
        Profile_Endpoints::register_routes();

        // Test PUT request (not supported)
        $request = new WP_REST_Request('PUT', '/athlete-dashboard/v1/profile');
        $response = rest_get_server()->dispatch($request);
        $this->assertEquals(404, $response->get_status());
    }

    /**
     * Clean up test environment.
     */
    public function tear_down() {
        // Clean up test user and associated meta
        if ($this->test_user_id) {
            delete_user_meta($this->test_user_id, Profile_Endpoints::META_KEY);
            wp_delete_user($this->test_user_id);
        }

        parent::tear_down();
    }

    public function test_deprecated_notice() {
        $this->markTestSkipped(
            'This test class is deprecated. Use the new modular endpoint tests in features/profile/tests/Unit/Endpoints/* instead.'
        );
    }
} 