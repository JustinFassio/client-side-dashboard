<?php
/**
 * Workout Generator Bootstrap Tests
 */

class Workout_Generator_Bootstrap_Test extends WP_UnitTestCase {
    private $bootstrap;
    private $test_settings;

    public function setUp(): void {
        parent::setUp();
        $this->bootstrap = new Workout_Generator_Bootstrap();
        
        $this->test_settings = [
            'endpoint' => 'https://test-api.example.com',
            'rate_limit' => 100,
            'rate_window' => 3600,
            'debug_mode' => false
        ];
    }

    public function tearDown(): void {
        delete_option('workout_generator_settings');
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
        update_option('workout_generator_settings', $this->test_settings);
        
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
        $routes = rest_get_server()->get_routes();
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
        set_query_var('pagename', 'dashboard');

        $this->bootstrap->init();

        // Test script registration on dashboard page
        do_action('wp_enqueue_scripts');
        
        $this->assertTrue(
            wp_script_is('workout-generator', 'enqueued'),
            'Workout generator script should be enqueued on dashboard page'
        );

        // Test script data
        $script_data = wp_scripts()->get_data('workout-generator', 'data');
        $this->assertNotEmpty($script_data, 'Script data should be set');
        $this->assertStringContainsString('workoutGeneratorConfig', $script_data);
        $this->assertStringContainsString('apiEndpoint', $script_data);
        $this->assertStringContainsString('nonce', $script_data);
    }

    public function test_register_settings() {
        // Set up admin environment
        set_current_screen('options');
        
        $this->bootstrap->init();

        // Test settings registration
        $this->assertTrue(
            get_registered_settings()['workout_generator_settings'] !== null,
            'Settings should be registered'
        );

        // Test settings fields
        global $wp_settings_fields;
        $fields = $wp_settings_fields['workout_generator']['workout_generator_main'];

        $this->assertArrayHasKey(
            'endpoint',
            $fields,
            'Endpoint field should be registered'
        );
        $this->assertArrayHasKey(
            'rate_limit',
            $fields,
            'Rate limit field should be registered'
        );
        $this->assertArrayHasKey(
            'debug_mode',
            $fields,
            'Debug mode field should be registered'
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
            Workout_Generator_Bootstrap::DEFAULTS['rate_limit'],
            $clean_settings['rate_limit'],
            'Invalid rate limit should be reset to default'
        );
        $this->assertEquals(
            Workout_Generator_Bootstrap::DEFAULTS['rate_window'],
            $clean_settings['rate_window'],
            'Invalid rate window should be reset to default'
        );
        $this->assertTrue(
            $clean_settings['debug_mode'],
            'Debug mode should be converted to boolean'
        );
    }

    public function test_missing_api_key_notice() {
        // Ensure API key constant is not defined
        if (defined('AI_SERVICE_API_KEY')) {
            runkit_constant_remove('AI_SERVICE_API_KEY');
        }

        $this->bootstrap->init();

        // Capture admin notices
        ob_start();
        do_action('admin_notices');
        $notices = ob_get_clean();

        $this->assertStringContainsString(
            'AI service API key not configured',
            $notices,
            'Missing API key notice should be displayed'
        );
    }

    public function test_settings_page_render() {
        // Set up admin user
        $admin_user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_user_id);

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
        wp_set_current_user($user_id);

        ob_start();
        $this->bootstrap->render_settings_page();
        $output = ob_get_clean();

        $this->assertEmpty(
            $output,
            'Settings page should not render for non-admin users'
        );
    }

    public function test_endpoint_security() {
        $this->bootstrap->init();
        $routes = rest_get_server()->get_routes();

        // Test authentication requirement
        foreach (['/generate', '/modify', '/history'] as $endpoint) {
            $full_route = '/athlete-dashboard/v1' . $endpoint;
            $route_obj = $routes[$full_route][0];
            
            $this->assertTrue(
                !empty($route_obj['permission_callback']),
                "Endpoint {$endpoint} should have permission callback"
            );

            // Test permission callback
            $request = new WP_REST_Request('GET', $full_route);
            $this->assertFalse(
                $route_obj['permission_callback']($request),
                "Unauthenticated request to {$endpoint} should be denied"
            );
        }
    }

    public function test_nonce_verification() {
        // Set up admin user
        $admin_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        $this->bootstrap->init();
        do_action('wp_enqueue_scripts');

        // Get the localized script data
        $script_data = wp_scripts()->get_data('workout-generator', 'data');
        
        // Verify nonce is present and valid
        $this->assertStringContainsString('nonce', $script_data);
        $this->assertNotEmpty(wp_verify_nonce(
            json_decode(str_replace('var workoutGeneratorConfig = ', '', trim($script_data, ';')), true)['nonce'],
            'wp_rest'
        ));
    }

    public function test_api_key_security() {
        // Test API key masking in debug output
        if (defined('AI_SERVICE_API_KEY')) {
            $reflection = new ReflectionClass($this->bootstrap);
            $method = $reflection->getMethod('setup_configuration');
            $method->setAccessible(true);
            
            ob_start();
            $method->invoke($this->bootstrap);
            $debug_output = ob_get_clean();

            $this->assertStringNotContainsString(
                AI_SERVICE_API_KEY,
                $debug_output,
                'API key should not appear in debug output'
            );
        }
    }

    public function test_settings_field_sanitization() {
        // Test XSS prevention in settings
        $dirty_settings = [
            'endpoint' => 'https://example.com/<script>alert(1)</script>',
            'rate_limit' => '100; DROP TABLE wp_posts;',
            'rate_window' => '<img src=x onerror=alert(1)>',
            'debug_mode' => '1" onclick="alert(1)"'
        ];

        $reflection = new ReflectionClass($this->bootstrap);
        $method = $reflection->getMethod('sanitize_settings');
        $method->setAccessible(true);

        $clean_settings = $method->invoke($this->bootstrap, $dirty_settings);

        // Verify sanitization
        $this->assertEquals(
            'https://example.com/',
            $clean_settings['endpoint'],
            'Endpoint should be sanitized URL'
        );
        $this->assertEquals(
            100,
            $clean_settings['rate_limit'],
            'Rate limit should be clean integer'
        );
        $this->assertEquals(
            3600,
            $clean_settings['rate_window'],
            'Invalid rate window should use default'
        );
        $this->assertTrue(
            is_bool($clean_settings['debug_mode']),
            'Debug mode should be boolean'
        );
    }

    public function test_role_based_access() {
        $roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
        $results = [];

        foreach ($roles as $role) {
            // Create user with role
            $user_id = $this->factory->user->create(['role' => $role]);
            wp_set_current_user($user_id);

            // Test settings page access
            ob_start();
            $this->bootstrap->render_settings_page();
            $output = ob_get_clean();

            $results[$role] = !empty($output);
        }

        // Only admin should access settings
        $this->assertTrue($results['administrator'], 'Admin should access settings');
        $this->assertFalse($results['editor'], 'Editor should not access settings');
        $this->assertFalse($results['author'], 'Author should not access settings');
        $this->assertFalse($results['contributor'], 'Contributor should not access settings');
        $this->assertFalse($results['subscriber'], 'Subscriber should not access settings');
    }

    public function test_data_shape_consistency() {
        // Test workout data shape
        $workout_data = [
            'id' => 1,
            'exercises' => [
                [
                    'id' => 1,
                    'name' => 'Push-ups',
                    'sets' => 3,
                    'reps' => 10,
                    'intensity' => 'moderate',
                    'type' => 'strength',
                    'equipment' => ['bodyweight'],
                    'rest_period' => 60,
                    'notes' => 'Keep core tight'
                ]
            ],
            'warmup' => [
                'id' => 2,
                'name' => 'Arm circles',
                'duration' => 300,
                'intensity' => 'light'
            ],
            'cooldown' => [
                'id' => 3,
                'name' => 'Static stretches',
                'duration' => 300,
                'intensity' => 'light'
            ]
        ];

        // Mock the endpoint response
        add_filter('rest_pre_serve_request', function($served, $result, $request, $server) use ($workout_data) {
            if (strpos($request->get_route(), '/athlete-dashboard/v1/generate') !== false) {
                $this->assertEquals(
                    $workout_data,
                    $result->get_data(),
                    'Workout data shape should match between PHP and TypeScript'
                );
            }
            return $served;
        }, 10, 4);

        // Simulate a request
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/generate');
        $request->set_body(json_encode(['preferences' => ['difficulty' => 'intermediate']]));
        
        $response = rest_get_server()->dispatch($request);
        $this->assertEquals(200, $response->get_status());
    }

    public function test_error_response_consistency() {
        // Test error response shape
        $error_data = [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Invalid workout data',
            'data' => [
                'status' => 400,
                'details' => [
                    'field' => 'exercises',
                    'error' => 'Missing required field'
                ]
            ]
        ];

        // Mock the endpoint response for an error
        add_filter('rest_pre_serve_request', function($served, $result, $request, $server) use ($error_data) {
            if (strpos($request->get_route(), '/athlete-dashboard/v1/generate') !== false) {
                $this->assertEquals(
                    $error_data,
                    $result->get_error_data(),
                    'Error response shape should match between PHP and TypeScript'
                );
            }
            return $served;
        }, 10, 4);

        // Simulate an invalid request
        $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/generate');
        $request->set_body(json_encode(['invalid' => 'data']));
        
        $response = rest_get_server()->dispatch($request);
        $this->assertEquals(400, $response->get_status());
    }
} 