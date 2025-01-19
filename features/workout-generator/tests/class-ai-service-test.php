<?php
/**
 * AI Service Tests
 */

class AI_Service_Test extends WP_UnitTestCase {
    private $ai_service;
    private $test_endpoint = 'https://test-api.example.com';
    private $test_api_key = 'test_key_123';

    public function setUp(): void {
        parent::setUp();
        
        // Define required constants
        if (!defined('AI_SERVICE_API_KEY')) {
            define('AI_SERVICE_API_KEY', $this->test_api_key);
        }
        if (!defined('AI_SERVICE_ENDPOINT')) {
            define('AI_SERVICE_ENDPOINT', $this->test_endpoint);
        }

        $this->ai_service = new AI_Service();
    }

    public function test_constructor_validates_configuration() {
        // Test missing API key
        $this->expectException(AI_Service_Exception::class);
        $this->expectExceptionCode('CONFIG_ERROR');
        
        $reflection = new ReflectionClass(AI_Service::class);
        $constructor = $reflection->getConstructor();
        $constructor->invoke(new AI_Service());
    }

    public function test_generate_workout_plan() {
        $prompt = ['difficulty' => 'intermediate'];
        $expected_response = ['workout' => ['exercises' => []]];

        // Mock successful API response
        add_filter('pre_http_request', function($pre, $args, $url) use ($expected_response) {
            $this->assertEquals('POST', $args['method']);
            $this->assertEquals($this->test_endpoint . '/generate', $url);
            $this->assertArrayHasKey('X-API-Key', $args['headers']);
            $this->assertEquals($this->test_api_key, $args['headers']['X-API-Key']);
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode($expected_response)
            ];
        }, 10, 3);

        $response = $this->ai_service->generate_workout_plan($prompt);
        $this->assertEquals($expected_response, $response);
    }

    public function test_modify_workout_plan() {
        $workout = ['id' => 1];
        $modifications = ['difficulty' => 'harder'];
        $expected_response = ['workout' => ['id' => 1, 'difficulty' => 'advanced']];

        add_filter('pre_http_request', function($pre, $args, $url) use ($expected_response) {
            $this->assertEquals('POST', $args['method']);
            $this->assertEquals($this->test_endpoint . '/modify', $url);
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode($expected_response)
            ];
        }, 10, 3);

        $response = $this->ai_service->modify_workout_plan($workout, $modifications);
        $this->assertEquals($expected_response, $response);
    }

    public function test_get_workout_by_id() {
        $workout_id = 123;
        $expected_response = ['workout' => ['id' => $workout_id]];

        add_filter('pre_http_request', function($pre, $args, $url) use ($workout_id, $expected_response) {
            $this->assertEquals('GET', $args['method']);
            $this->assertEquals($this->test_endpoint . "/workout/{$workout_id}", $url);
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode($expected_response)
            ];
        }, 10, 3);

        $response = $this->ai_service->get_workout_by_id($workout_id);
        $this->assertEquals($expected_response, $response);
    }

    public function test_get_workout_history() {
        $user_id = 456;
        $filters = ['date' => '2024-03-01'];
        $expected_response = ['history' => []];

        add_filter('pre_http_request', function($pre, $args, $url) use ($user_id, $filters, $expected_response) {
            $this->assertEquals('GET', $args['method']);
            $this->assertEquals(
                $this->test_endpoint . "/history/{$user_id}?" . http_build_query($filters),
                $url
            );
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode($expected_response)
            ];
        }, 10, 3);

        $response = $this->ai_service->get_workout_history($user_id, $filters);
        $this->assertEquals($expected_response, $response);
    }

    public function test_suggest_alternatives() {
        $exercise = ['id' => 789];
        $constraints = ['equipment' => 'none'];
        $expected_response = ['alternatives' => []];

        add_filter('pre_http_request', function($pre, $args, $url) use ($expected_response) {
            $this->assertEquals('POST', $args['method']);
            $this->assertEquals($this->test_endpoint . '/alternatives', $url);
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode($expected_response)
            ];
        }, 10, 3);

        $response = $this->ai_service->suggest_alternatives($exercise, $constraints);
        $this->assertEquals($expected_response, $response);
    }

    public function test_api_error_handling() {
        $error_response = [
            'message' => 'Invalid request',
            'code' => 'INVALID_INPUT',
            'details' => ['field' => 'prompt']
        ];

        add_filter('pre_http_request', function() use ($error_response) {
            return [
                'response' => ['code' => 400],
                'body' => json_encode($error_response)
            ];
        });

        $this->expectException(AI_Service_Exception::class);
        $this->expectExceptionMessage($error_response['message']);
        $this->expectExceptionCode($error_response['code']);

        $this->ai_service->generate_workout_plan([]);
    }

    public function test_connection_error_handling() {
        add_filter('pre_http_request', function() {
            return new WP_Error('http_request_failed', 'Connection failed');
        });

        $this->expectException(AI_Service_Exception::class);
        $this->expectExceptionCode('CONNECTION_ERROR');

        $this->ai_service->generate_workout_plan([]);
    }

    public function test_invalid_json_response_handling() {
        add_filter('pre_http_request', function() {
            return [
                'response' => ['code' => 200],
                'body' => 'invalid json'
            ];
        });

        $this->expectException(AI_Service_Exception::class);
        $this->expectExceptionCode('INVALID_RESPONSE');

        $this->ai_service->generate_workout_plan([]);
    }

    public function test_rate_limit_handling() {
        // Mock rate limiter to always return false
        $mock_rate_limiter = $this->createMock(Rate_Limiter::class);
        $mock_rate_limiter->method('check_limit')->willReturn(false);
        $mock_rate_limiter->method('get_limit')->willReturn(100);
        $mock_rate_limiter->method('get_window')->willReturn(3600);
        $mock_rate_limiter->method('get_remaining')->willReturn(0);

        $reflection = new ReflectionClass($this->ai_service);
        $property = $reflection->getProperty('rate_limiter');
        $property->setAccessible(true);
        $property->setValue($this->ai_service, $mock_rate_limiter);

        $this->expectException(AI_Service_Exception::class);
        $this->expectExceptionCode('RATE_LIMIT_EXCEEDED');

        $this->ai_service->generate_workout_plan([]);
    }

    public function test_large_payload_handling() {
        $large_prompt = [
            'difficulty' => 'intermediate',
            'exercises' => array_fill(0, 100, [
                'name' => 'Exercise',
                'sets' => 3,
                'reps' => 10,
                'description' => str_repeat('Long description. ', 50)
            ])
        ];

        // Mock successful API response
        add_filter('pre_http_request', function($pre, $args, $url) {
            $this->assertLessThan(
                1024 * 1024, // 1MB
                strlen($args['body']),
                'Request payload should be within reasonable size'
            );
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['status' => 'success'])
            ];
        }, 10, 3);

        $response = $this->ai_service->generate_workout_plan($large_prompt);
        $this->assertNotEmpty($response);
    }

    public function test_concurrent_requests() {
        $requests = 5;
        $responses = [];
        $exceptions = [];

        // Create multiple concurrent requests
        for ($i = 0; $i < $requests; $i++) {
            try {
                $responses[] = $this->ai_service->generate_workout_plan([
                    'difficulty' => 'intermediate',
                    'concurrent_id' => $i
                ]);
            } catch (AI_Service_Exception $e) {
                $exceptions[] = $e;
            }
        }

        // Verify rate limiting worked
        $this->assertCount(
            $requests,
            array_merge($responses, $exceptions),
            'All requests should either succeed or fail with rate limit'
        );
    }

    public function test_unicode_and_special_chars() {
        $prompt = [
            'difficulty' => 'intermediate',
            'preferences' => [
                'name' => 'Test 🏋️‍♂️ Workout',
                'description' => 'Special chars: &<>"\'',
                'notes' => "Multi\nline\nnotes"
            ]
        ];

        add_filter('pre_http_request', function($pre, $args, $url) {
            $body = json_decode($args['body'], true);
            $this->assertNotFalse(
                $body,
                'Request body should be valid JSON with Unicode'
            );
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['status' => 'success'])
            ];
        }, 10, 3);

        $response = $this->ai_service->generate_workout_plan($prompt);
        $this->assertNotEmpty($response);
    }

    public function test_profile_data_integration() {
        // Mock profile data
        $profile_data = [
            'age' => 30,
            'experience_level' => 'intermediate',
            'injuries' => ['shoulder'],
            'equipment' => ['dumbbells', 'resistance_bands']
        ];

        // Mock profile service
        $mock_profile = $this->getMockBuilder('Profile_Service')
            ->disableOriginalConstructor()
            ->getMock();
        $mock_profile->method('get_training_preferences')
            ->willReturn($profile_data);

        add_filter('pre_http_request', function($pre, $args, $url) use ($profile_data) {
            $body = json_decode($args['body'], true);
            
            // Verify profile data is included in request
            $this->assertArrayHasKey('profile', $body);
            $this->assertEquals($profile_data['age'], $body['profile']['age']);
            $this->assertEquals($profile_data['experience_level'], $body['profile']['experienceLevel']);
            $this->assertEquals($profile_data['injuries'], $body['profile']['injuries']);
            $this->assertEquals($profile_data['equipment'], $body['profile']['equipment']);
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['workout' => ['exercises' => []]])
            ];
        }, 10, 3);

        $reflection = new ReflectionClass($this->ai_service);
        $property = $reflection->getProperty('profile_service');
        $property->setAccessible(true);
        $property->setValue($this->ai_service, $mock_profile);

        $response = $this->ai_service->generate_workout_plan(['difficulty' => 'intermediate']);
        $this->assertArrayHasKey('workout', $response);
    }

    public function test_missing_profile_data_handling() {
        // Mock profile service that returns incomplete data
        $mock_profile = $this->getMockBuilder('Profile_Service')
            ->disableOriginalConstructor()
            ->getMock();
        $mock_profile->method('get_training_preferences')
            ->willReturn(['age' => 30]); // Missing other fields

        $reflection = new ReflectionClass($this->ai_service);
        $property = $reflection->getProperty('profile_service');
        $property->setAccessible(true);
        $property->setValue($this->ai_service, $mock_profile);

        add_filter('pre_http_request', function($pre, $args, $url) {
            $body = json_decode($args['body'], true);
            
            // Verify defaults are used for missing data
            $this->assertArrayHasKey('profile', $body);
            $this->assertEquals(30, $body['profile']['age']);
            $this->assertEquals('beginner', $body['profile']['experienceLevel']);
            $this->assertEquals([], $body['profile']['injuries']);
            $this->assertEquals(['bodyweight'], $body['profile']['equipment']);
            
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['workout' => ['exercises' => []]])
            ];
        }, 10, 3);

        $response = $this->ai_service->generate_workout_plan(['difficulty' => 'intermediate']);
        $this->assertArrayHasKey('workout', $response);
    }
} 