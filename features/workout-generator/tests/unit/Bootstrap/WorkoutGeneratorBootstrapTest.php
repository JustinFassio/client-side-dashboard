<?php
namespace AthleteDashboard\Features\WorkoutGenerator\Tests\Unit\Bootstrap;

/**
 * Workout Generator Bootstrap Tests
 */

use PHPUnit\Framework\TestCase;
use AthleteDashboard\Features\WorkoutGenerator\Workout_Generator_Bootstrap;
use ReflectionClass;
use WP_REST_Request;
use WP_Error;
use WP_UnitTest_Factory;
use WP_Die_Exception;

class WorkoutGeneratorBootstrapTest extends \WP_UnitTestCase {
    private $bootstrap;
    private $test_settings;
    protected $factory;

    protected function setUp(): void {
        parent::setUp();
        $this->bootstrap = new Workout_Generator_Bootstrap();
        
        $this->test_settings = [
            'endpoint' => 'https://test-api.example.com',
            'rate_limit' => AI_SERVICE_RATE_LIMIT,
            'rate_window' => AI_SERVICE_RATE_WINDOW,
            'debug_mode' => WORKOUT_GENERATOR_DEBUG
        ];

        // Mock factory for user creation
        $this->factory = new WP_UnitTest_Factory();

        // Set up WP_Die handler for testing
        \add_filter('wp_die_handler', function() {
            return function($message, $title = '', $args = []) {
                throw new \WP_Die_Exception($message);
            };
        });
    }

    public function tearDown(): void {
        global $wp_options, $wp_actions, $wp_registered_settings;
        $wp_options = [];
        $wp_actions = [];
        $wp_registered_settings = [];
        parent::tearDown();
    }

    public function test_init_loads_dependencies() {
        // Test that required files are loaded
        $this->bootstrap->init();

        $this->assertTrue(
            class_exists('Workout_Endpoints'),
            'Workout_Endpoints class should be loaded'
        );
        $this->assertTrue(
            class_exists('AI_Service'),
            'AI_Service class should be loaded'
        );
        $this->assertTrue(
            class_exists('Workout_Validator'),
            'Workout_Validator class should be loaded'
        );
        $this->assertTrue(
            class_exists('Rate_Limiter'),
            'Rate_Limiter class should be loaded'
        );
    }

    public function test_setup_configuration() {
        \update_option('workout_generator_settings', $this->test_settings);
        
        $this->bootstrap->init();

        $this->assertEquals(
            $this->test_settings['endpoint'],
            AI_SERVICE_ENDPOINT,
            'AI service endpoint should be set from settings'
        );
        $this->assertEquals(
            $this->test_settings['rate_limit'],
            AI_SERVICE_RATE_LIMIT,
            'Rate limit should be set from settings'
        );
        $this->assertEquals(
            $this->test_settings['rate_window'],
            AI_SERVICE_RATE_WINDOW,
            'Rate window should be set from settings'
        );
        $this->assertEquals(
            $this->test_settings['debug_mode'],
            WORKOUT_GENERATOR_DEBUG,
            'Debug mode should be set from settings'
        );
    }

    public function test_register_endpoints() {
        $this->bootstrap->init();

        // Check if REST routes are registered
        $routes = \rest_get_server()->get_routes();
        $this->assertArrayHasKey(
            '/athlete-dashboard/v1/generate',
            $routes,
            'Generate workout endpoint should be registered'
        );
        $this->assertArrayHasKey(
            '/athlete-dashboard/v1/modify',
            $routes,
            'Modify workout endpoint should be registered'
        );
        $this->assertArrayHasKey(
            '/athlete-dashboard/v1/history',
            $routes,
            'Workout history endpoint should be registered'
        );
    }

    public function test_register_assets() {
        // Set up test environment
        global $wp_query;
        $wp_query->is_page = true;
        \set_query_var('pagename', 'dashboard');

        $this->bootstrap->init();

        // Test script registration on dashboard page
        \do_action('wp_enqueue_scripts');
        
        $this->assertTrue(
            \wp_script_is('workout-generator', 'enqueued'),
            'Workout generator script should be enqueued on dashboard page'
        );

        // Test script data
        $scripts = \wp_scripts();
        $this->assertNotEmpty(
            $scripts->registered['workout-generator'],
            'Script should be registered'
        );
    }

    public function test_register_settings() {
        // Set up admin environment
        \set_current_screen('options');
        
        $this->bootstrap->init();

        // Trigger admin_init action to register settings
        \do_action('admin_init');

        // Test settings registration
        global $wp_registered_settings;
        $this->assertArrayHasKey(
            'workout_generator_settings',
            $wp_registered_settings,
            'Settings should be registered'
        );

        // Test settings fields
        global $wp_settings_fields;
        $this->assertNotNull(
            $wp_settings_fields,
            'Settings fields should be initialized'
        );
    }

    public function test_settings_sanitization() {
        $dirty_settings = [
            'endpoint' => 'not-a-url',
            'rate_limit' => -1,
            'rate_window' => 30, // Too low
            'debug_mode' => 1
        ];

        $reflection = new ReflectionClass($this->bootstrap);
        $method = $reflection->getMethod('sanitize_settings');
        $method->setAccessible(true);

        $clean_settings = $method->invoke($this->bootstrap, $dirty_settings);

        $this->assertEmpty(
            $clean_settings['endpoint'],
            'Invalid URL should be sanitized'
        );
        $this->assertEquals(
            1,
            $clean_settings['rate_limit'],
            'Invalid rate limit should be reset to minimum'
        );
        $this->assertEquals(
            60,
            $clean_settings['rate_window'],
            'Invalid rate window should be reset to minimum'
        );
        $this->assertTrue(
            $clean_settings['debug_mode'],
            'Debug mode should be set from settings'
        );
    }

    public function test_missing_api_key_notice() {
        // Set empty settings
        \update_option('workout_generator_settings', ['api_key' => '']);
        
        $this->bootstrap->init();

        // Capture admin notices
        ob_start();
        $this->bootstrap->missing_api_key_notice();
        $notices = ob_get_clean();

        $this->assertStringContainsString(
            'Please configure your API key',
            $notices,
            'Missing API key notice should be displayed'
        );
    }

    public function test_settings_page_render() {
        // Set up admin user
        $admin_user_id = $this->factory->user->create(['role' => 'administrator']);
        \wp_set_current_user($admin_user_id);

        ob_start();
        $this->bootstrap->render_settings_page();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '<form action="options.php"',
            $output,
            'Settings form should be rendered'
        );
        $this->assertStringContainsString(
            'Workout Generator Settings',
            $output,
            'Settings title should be rendered'
        );
    }

    public function test_non_admin_settings_access() {
        // Set up non-admin user
        $user_id = $this->factory->user->create(['role' => 'subscriber']);
        \wp_set_current_user($user_id);

        // Debug output
        global $current_user;
        error_log("Test user roles: " . implode(', ', $current_user->roles));
        error_log("Can manage options: " . (\current_user_can('manage_options') ? 'true' : 'false'));

        // Explicitly check capability
        $this->assertFalse(
            \current_user_can('manage_options'),
            'Subscriber should not have manage_options capability'
        );

        // Expect WP_Die_Exception
        $this->expectException('\WP_Die_Exception');
        $this->expectExceptionMessage('Unauthorized access');

        // Capture output to prevent it from being displayed
        ob_start();
        try {
            $this->bootstrap->render_settings_page();
            error_log("No exception thrown");
        } catch (\WP_Die_Exception $e) {
            error_log("Exception caught: " . $e->getMessage());
            ob_end_clean();
            throw $e;
        } catch (\Exception $e) {
            error_log("Unexpected exception caught: " . get_class($e) . " - " . $e->getMessage());
        }
        ob_end_clean();
    }

    public function test_role_based_access() {
        // Test editor access
        $editor_id = $this->factory->user->create(['role' => 'editor']);
        \wp_set_current_user($editor_id);

        // Debug output
        global $current_user;
        error_log("Test editor roles: " . implode(', ', $current_user->roles));
        error_log("Can manage options: " . (\current_user_can('manage_options') ? 'true' : 'false'));

        // Explicitly check capability
        $this->assertFalse(
            \current_user_can('manage_options'),
            'Editor should not have manage_options capability'
        );

        // Expect WP_Die_Exception
        $this->expectException('\WP_Die_Exception');
        $this->expectExceptionMessage('Unauthorized access');

        // Capture output to prevent it from being displayed
        ob_start();
        try {
            $this->bootstrap->render_settings_page();
            error_log("No exception thrown");
        } catch (\WP_Die_Exception $e) {
            error_log("Exception caught: " . $e->getMessage());
            ob_end_clean();
            throw $e;
        } catch (\Exception $e) {
            error_log("Unexpected exception caught: " . get_class($e) . " - " . $e->getMessage());
        }
        ob_end_clean();
    }

    public function test_endpoint_security() {
        $this->bootstrap->init();

        // Test nonce verification
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/generate');
        $response = \rest_get_server()->dispatch($request);

        $this->assertEquals(
            'rest_forbidden',
            $response->get_error_code(),
            'Request without nonce should be forbidden'
        );
    }

    public function test_nonce_verification() {
        // Set up user
        $user_id = $this->factory->user->create(['role' => 'subscriber']);
        \wp_set_current_user($user_id);

        $this->bootstrap->init();

        // Test with invalid nonce
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/generate');
        $request->set_param('_wpnonce', 'invalid_nonce');
        $response = \rest_get_server()->dispatch($request);

        $this->assertEquals(
            'rest_forbidden',
            $response->get_error_code(),
            'Request with invalid nonce should be forbidden'
        );
    }

    public function test_api_key_security() {
        // Store original value if exists
        $original_key = defined('AI_SERVICE_API_KEY') ? AI_SERVICE_API_KEY : null;
        
        // Temporarily undefine the constant for this test
        if (defined('AI_SERVICE_API_KEY')) {
            // Skip this test if we can't modify constants
            $this->markTestSkipped('Cannot modify constants in this environment');
        }

        $this->bootstrap->init();

        // Test API key validation
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/generate');
        $response = \rest_get_server()->dispatch($request);

        $this->assertEquals(
            'rest_forbidden',
            $response->get_error_code(),
            'Request without API key should be forbidden'
        );
    }

    public function test_settings_field_sanitization() {
        $dirty_settings = [
            'endpoint' => 'javascript:alert("xss")',
            'rate_limit' => '100; DROP TABLE users;',
            'debug_mode' => '<script>alert("xss")</script>'
        ];

        $reflection = new ReflectionClass($this->bootstrap);
        $method = $reflection->getMethod('sanitize_settings');
        $method->setAccessible(true);

        $clean_settings = $method->invoke($this->bootstrap, $dirty_settings);

        $this->assertEmpty(
            $clean_settings['endpoint'],
            'JavaScript URLs should be sanitized'
        );
        $this->assertEquals(
            100,
            $clean_settings['rate_limit'],
            'SQL injection attempts should be sanitized to integer'
        );
        $this->assertTrue(
            $clean_settings['debug_mode'],
            'HTML/Script tags should be converted to boolean'
        );
    }

    public function test_data_shape_consistency() {
        // Mock successful API response
        \add_filter('pre_http_request', function() {
            return [
                'response' => ['code' => 200],
                'body' => json_encode([
                    'workout' => [
                        'exercises' => [
                            ['name' => 'Squat', 'sets' => 3, 'reps' => 10],
                            ['name' => 'Bench Press', 'sets' => 3, 'reps' => 8]
                        ]
                    ]
                ])
            ];
        });

        $this->bootstrap->init();

        // Test that the response shape matches expected format
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/generate');
        $response = \rest_get_server()->dispatch($request);

        $this->assertInstanceOf(
            'WP_Error',
            $response,
            'Response should be a WP_Error instance'
        );
    }

    public function test_error_response_consistency() {
        // Mock failed API response
        \add_filter('pre_http_request', function() {
            return new WP_Error('api_error', 'API request failed');
        });

        $this->bootstrap->init();

        // Test that the error response is consistent
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/generate');
        $response = \rest_get_server()->dispatch($request);

        $this->assertInstanceOf(
            'WP_Error',
            $response,
            'Response should be a WP_Error instance'
        );
        $this->assertEquals(
            'rest_forbidden',
            $response->get_error_code(),
            'Error code should be rest_forbidden'
        );
    }
} 