<?php
/**
 * AI Service Tests
 */

namespace AthleteDashboard\Features\WorkoutGenerator\Tests\Unit\AIService;

use PHPUnit\Framework\TestCase;
use AthleteDashboard\Features\WorkoutGenerator\API\AI_Service;
use AthleteDashboard\Features\WorkoutGenerator\API\AI_Service_Exception;
use AthleteDashboard\Features\WorkoutGenerator\API\Rate_Limiter;
use WP_Error;

/**
 * Test case for the AI Service class.
 */
class AIServiceTest extends TestCase {
	/**
	 * The AI service instance.
	 *
	 * @var \AI_Service
	 */
	private $ai_service;

	/**
	 * Mock rate limiter instance.
	 *
	 * @var \Rate_Limiter|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $rate_limiter;

	/**
	 * Mock WordPress functions.
	 *
	 * @var array
	 */
	private $wp_functions;

	/**
	 * Test endpoint URL.
	 *
	 * @var string
	 */
	private $test_endpoint = 'https://test-api.example.com';

	/**
	 * Test API key.
	 *
	 * @var string
	 */
	private $test_api_key = 'test-api-key';

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Define required constants
		if ( ! defined( 'AI_SERVICE_API_KEY' ) ) {
			define( 'AI_SERVICE_API_KEY', $this->test_api_key );
		}
		if ( ! defined( 'AI_SERVICE_ENDPOINT' ) ) {
			define( 'AI_SERVICE_ENDPOINT', $this->test_endpoint );
		}

		// Create mock rate limiter
		$this->rate_limiter = $this->createMock( Rate_Limiter::class );
		$this->rate_limiter->method( 'check_limit' )
			->willReturn( true );

		// Create mock WordPress functions
		$this->wp_functions = array(
			'get_bloginfo'                     => function () {
				return 'Test Site'; },
			'wp_remote_request'                => function ( $url, $args ) {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => json_encode( array( 'workout' => array( 'exercises' => array() ) ) ),
				);
			},
			'wp_remote_retrieve_response_code' => function ( $response ) {
				return $response['response']['code'];
			},
			'wp_remote_retrieve_body'          => function ( $response ) {
				return $response['body'];
			},
			'is_wp_error'                      => function ( $thing ) {
				return $thing instanceof WP_Error;
			},
			'wp_json_encode'                   => function ( $data ) {
				return json_encode( $data );
			},
		);

		// Create AI service with mock rate limiter and WordPress functions
		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
	}

	private function setup_mock_rate_limiter( $check_limit_result = true ) {
		$mock_rate_limiter = $this->createMock( Rate_Limiter::class );
		$mock_rate_limiter->method( 'check_limit' )
			->willReturn( $check_limit_result );

		$mock_rate_limiter->method( 'get_rate_limit_headers' )
			->willReturn(
				array(
					'X-RateLimit-Limit'     => 100,
					'X-RateLimit-Remaining' => 99,
					'X-RateLimit-Reset'     => time() + 3600,
				)
			);

		return $mock_rate_limiter;
	}

	/**
	 * Test generate workout plan.
	 */
	public function test_generate_workout_plan() {
		$preferences       = array( 'difficulty' => 'intermediate' );
		$profile_data      = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);
		$expected_response = array( 'workout' => array( 'exercises' => array() ) );

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $expected_response ) {
			$this->assertEquals( 'POST', $args['method'] );
			$this->assertEquals( $this->test_endpoint . '/generate', $url );
			$this->assertArrayHasKey( 'X-API-Key', $args['headers'] );
			$this->assertEquals( $this->test_api_key, $args['headers']['X-API-Key'] );

			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $expected_response ),
			);
		};

		$response = $this->ai_service->generate_workout_plan( $preferences, $profile_data );
		$this->assertEquals( $expected_response, $response );
	}

	/**
	 * Test modify workout plan.
	 */
	public function test_modify_workout_plan() {
		$workout           = array( 'id' => 1 );
		$modifications     = array( 'difficulty' => 'harder' );
		$expected_response = array(
			'workout' => array(
				'id'         => 1,
				'difficulty' => 'advanced',
			),
		);

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $expected_response ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $expected_response ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->modify_workout_plan( $workout, $modifications );
		$this->assertEquals( $expected_response, $response );
	}

	/**
	 * Test get workout by ID.
	 */
	public function test_get_workout_by_id() {
		$workout_id        = 123;
		$expected_response = array( 'workout' => array( 'id' => $workout_id ) );

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $expected_response ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $expected_response ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->get_workout_by_id( $workout_id );
		$this->assertEquals( $expected_response, $response );
	}

	/**
	 * Test get workout history.
	 */
	public function test_get_workout_history() {
		$user_id           = 456;
		$date              = '2024-03-01';
		$expected_response = array( 'history' => array() );

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $expected_response ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $expected_response ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->get_workout_history( $user_id, $date );
		$this->assertEquals( $expected_response, $response );
	}

	/**
	 * Test suggest alternatives.
	 */
	public function test_suggest_alternatives() {
		$exercise          = array(
			'id'   => 1,
			'name' => 'Push-ups',
		);
		$constraints       = array( 'equipment' => 'none' );
		$expected_response = array( 'alternatives' => array() );

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $expected_response ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $expected_response ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->suggest_alternatives( $exercise, $constraints );
		$this->assertEquals( $expected_response, $response );
	}

	/**
	 * Test API error handling.
	 */
	public function test_api_error_handling() {
		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) {
			return array(
				'response' => array( 'code' => 400 ),
				'body'     => json_encode( array( 'message' => 'Bad request' ) ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'API_ERROR' );

		$preferences  = array( 'difficulty' => 'invalid' );
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);
		$this->ai_service->generate_workout_plan( $preferences, $profile_data );
	}

	/**
	 * Test connection error handling.
	 */
	public function test_connection_error_handling() {
		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) {
			return new WP_Error( 'http_request_failed', 'Connection failed' );
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'REQUEST_FAILED' );

		$preferences  = array( 'difficulty' => 'intermediate' );
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);
		$this->ai_service->generate_workout_plan( $preferences, $profile_data );
	}

	/**
	 * Test invalid JSON response handling.
	 */
	public function test_invalid_json_response_handling() {
		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => 'invalid json',
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'INVALID_RESPONSE' );

		$preferences  = array( 'difficulty' => 'intermediate' );
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);
		$this->ai_service->generate_workout_plan( $preferences, $profile_data );
	}

	/**
	 * Test rate limit handling.
	 */
	public function test_rate_limit_handling() {
		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) {
			return array(
				'response' => array( 'code' => 429 ),
				'body'     => json_encode( array( 'message' => 'Rate limit exceeded' ) ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'RATE_LIMIT_EXCEEDED' );

		$preferences  = array( 'difficulty' => 'intermediate' );
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);
		$this->ai_service->generate_workout_plan( $preferences, $profile_data );
	}

	/**
	 * Test unauthorized API key.
	 */
	public function test_unauthorized_api_key() {
		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) {
			return array(
				'response' => array( 'code' => 401 ),
				'body'     => json_encode( array( 'message' => 'Unauthorized' ) ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'UNAUTHORIZED' );

		$preferences  = array( 'difficulty' => 'intermediate' );
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);
		$this->ai_service->generate_workout_plan( $preferences, $profile_data );
	}

	/**
	 * Test workout generation with valid profile data.
	 */
	public function test_generate_workout_plan_with_valid_profile() {
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
			'injuries'        => array( 'lower_back' ),
			'equipment'       => array( 'dumbbells', 'resistance_bands' ),
			'fitnessGoals'    => array( 'strength', 'endurance' ),
		);

		$preferences = array(
			'duration'  => 45,
			'intensity' => 'medium',
		);

		$expected_response = array(
			'workout' => array(
				'exercises' => array(),
			),
		);

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $profile_data, $preferences, $expected_response ) {
			$body = json_decode( $args['body'], true );
			$this->assertEquals( $profile_data['heightCm'], $body['heightCm'] );
			$this->assertEquals( $profile_data['weightKg'], $body['weightKg'] );
			$this->assertEquals( $profile_data['experienceLevel'], $body['experienceLevel'] );
			$this->assertEquals( $preferences, $body['preferences'] );

			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $expected_response ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan_with_profile( $profile_data, $preferences );
		$this->assertEquals( $expected_response, $response );
	}

	/**
	 * Test workout generation with missing required profile data.
	 */
	public function test_generate_workout_plan_with_missing_profile_data() {
		$profile_data = array(
			'heightCm'        => 175,
			// weightKg missing
			'experienceLevel' => 'intermediate',
		);

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );

		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'PROFILE_DATA_MISSING' );

		$this->ai_service->generate_workout_plan_with_profile( $profile_data );
	}

	/**
	 * Test workout generation with invalid profile data.
	 */
	public function test_generate_workout_plan_with_invalid_profile_data() {
		$profile_data = array(
			'heightCm'        => -175,  // Invalid negative height
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
			'injuries'        => 'back pain',  // Should be an array
		);

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );

		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'PROFILE_DATA_INVALID' );

		$this->ai_service->generate_workout_plan_with_profile( $profile_data );
	}

	/**
	 * Test workout generation with invalid experience level.
	 */
	public function test_generate_workout_plan_with_invalid_experience_level() {
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'expert',  // Invalid level
			'injuries'        => array(),
		);

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );

		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'PROFILE_DATA_INVALID' );

		$this->ai_service->generate_workout_plan_with_profile( $profile_data );
	}

	/**
	 * Test workout generation with invalid preferences.
	 */
	public function test_generate_workout_plan_with_invalid_preferences() {
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
			'injuries'        => array(),
		);

		$invalid_preferences = array(
			'duration'   => -30,  // Invalid negative duration
			'intensity'  => 'super-high',  // Invalid intensity level
			'focusAreas' => 'arms',  // Should be an array
		);

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );

		$this->expectException( AI_Service_Exception::class );
		$this->expectExceptionCode( 'PREFERENCES_INVALID' );

		$this->ai_service->generate_workout_plan_with_profile( $profile_data, $invalid_preferences );
	}

	/**
	 * Test workout generation with empty preferences.
	 */
	public function test_generate_workout_plan_with_empty_preferences() {
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
			'injuries'        => array(),
		);

		$expected_response = array(
			'workout' => array(
				'exercises' => array(),
			),
		);

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $expected_response ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $expected_response ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan_with_profile( $profile_data );
		$this->assertEquals( $expected_response, $response );
	}

	/**
	 * Test rate limit headers are included in requests.
	 */
	public function test_rate_limit_headers_in_request() {
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);

		$expected_headers = array(
			'X-RateLimit-Limit'     => 60, // Foundation tier limit
			'X-RateLimit-Remaining' => 59,
			'X-RateLimit-Reset'     => 3600,
		);

		$this->rate_limiter = $this->createMock( Rate_Limiter::class );
		$this->rate_limiter->method( 'check_limit' )->willReturn( true );
		$this->rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $expected_headers );

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $expected_headers ) {
			foreach ( $expected_headers as $header => $value ) {
				$this->assertEquals( $value, $args['headers'][ $header ] );
			}

			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'workout' => array( 'exercises' => array() ) ) ),
			);
		};

		$this->ai_service = new AI_Service( $this->rate_limiter, $this->wp_functions );
		$this->ai_service->generate_workout_plan_with_profile( $profile_data );
	}

	/**
	 * Test rate limit exceeded with headers in exception.
	 */
	public function test_rate_limit_exceeded_with_headers() {
		$reset_time = time() + 3600;
		$headers    = array(
			'X-RateLimit-Limit'     => 100,
			'X-RateLimit-Remaining' => 0,
			'X-RateLimit-Reset'     => $reset_time,
		);

		$mock_rate_limiter = $this->createMock( Rate_Limiter::class );
		$mock_rate_limiter->expects( $this->once() )
			->method( 'check_limit' )
			->willReturn( false );
		$mock_rate_limiter->expects( $this->once() )
			->method( 'get_rate_limit_headers' )
			->willReturn( $headers );

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );

		try {
			$this->ai_service->generate_workout_plan(
				array(),
				array(
					'heightCm'        => 175,
					'weightKg'        => 70,
					'experienceLevel' => 'intermediate',
				)
			);
			$this->fail( 'Expected AI_Service_Exception was not thrown' );
		} catch ( AI_Service_Exception $e ) {
			$this->assertEquals( 'RATE_LIMIT_EXCEEDED', $e->getCode() );
			$data = $e->getData();
			$this->assertArrayHasKey( 'X-RateLimit-Limit', $data );
			$this->assertArrayHasKey( 'X-RateLimit-Remaining', $data );
			$this->assertArrayHasKey( 'X-RateLimit-Reset', $data );
			$this->assertEquals( $headers['X-RateLimit-Limit'], $data['X-RateLimit-Limit'] );
			$this->assertEquals( $headers['X-RateLimit-Remaining'], $data['X-RateLimit-Remaining'] );
			$this->assertEquals( $headers['X-RateLimit-Reset'], $data['X-RateLimit-Reset'] );
		}
	}

	/**
	 * Test tier rate limits
	 */
	public function test_tier_rate_limits() {
		$mock_rate_limiter = $this->createMock( Rate_Limiter::class );
		$profile_data      = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);

		// Test foundation tier
		$foundation_headers = array(
			'X-RateLimit-Limit'     => 60,
			'X-RateLimit-Remaining' => 59,
			'X-RateLimit-Reset'     => time() + 3600,
		);
		$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $foundation_headers );
		$mock_rate_limiter->method( 'check_limit' )->willReturn( true );

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan( array(), $profile_data );
		$this->assertNotNull( $response );

		// Test performance tier
		$performance_headers = array(
			'X-RateLimit-Limit'     => 120,
			'X-RateLimit-Remaining' => 119,
			'X-RateLimit-Reset'     => time() + 3600,
		);
		$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $performance_headers );

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan( array(), $profile_data );
		$this->assertNotNull( $response );

		// Test transformation tier
		$transformation_headers = array(
			'X-RateLimit-Limit'     => 180,
			'X-RateLimit-Remaining' => 179,
			'X-RateLimit-Reset'     => time() + 3600,
		);
		$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $transformation_headers );

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan( array(), $profile_data );
		$this->assertNotNull( $response );
	}

	/**
	 * Test tier-based rate limiting in AI Service
	 */
	public function test_tier_based_rate_limiting() {
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);

		// Test foundation tier
		$foundation_headers = array(
			'X-RateLimit-Limit'     => 60,
			'X-RateLimit-Remaining' => 59,
			'X-RateLimit-Reset'     => time() + 3600,
		);
		$mock_rate_limiter  = $this->createMock( Rate_Limiter::class );
		$mock_rate_limiter->method( 'check_limit' )->willReturn( true );
		$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $foundation_headers );

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $foundation_headers ) {
			foreach ( $foundation_headers as $header => $value ) {
				$this->assertEquals( $value, $args['headers'][ $header ] );
			}
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'workout' => array( 'exercises' => array() ) ) ),
			);
		};

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan( array(), $profile_data );
		$this->assertNotNull( $response );

		// Test performance tier
		$performance_headers = array(
			'X-RateLimit-Limit'     => 120,
			'X-RateLimit-Remaining' => 119,
			'X-RateLimit-Reset'     => time() + 3600,
		);
		$mock_rate_limiter   = $this->createMock( Rate_Limiter::class );
		$mock_rate_limiter->method( 'check_limit' )->willReturn( true );
		$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $performance_headers );

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $performance_headers ) {
			foreach ( $performance_headers as $header => $value ) {
				$this->assertEquals( $value, $args['headers'][ $header ] );
			}
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'workout' => array( 'exercises' => array() ) ) ),
			);
		};

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan( array(), $profile_data );
		$this->assertNotNull( $response );

		// Test transformation tier
		$transformation_headers = array(
			'X-RateLimit-Limit'     => 180,
			'X-RateLimit-Remaining' => 179,
			'X-RateLimit-Reset'     => time() + 3600,
		);
		$mock_rate_limiter      = $this->createMock( Rate_Limiter::class );
		$mock_rate_limiter->method( 'check_limit' )->willReturn( true );
		$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $transformation_headers );

		$this->wp_functions['wp_remote_request'] = function ( $url, $args ) use ( $transformation_headers ) {
			foreach ( $transformation_headers as $header => $value ) {
				$this->assertEquals( $value, $args['headers'][ $header ] );
			}
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'workout' => array( 'exercises' => array() ) ) ),
			);
		};

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan( array(), $profile_data );
		$this->assertNotNull( $response );
	}

	/**
	 * Test rate limit exceeded behavior for different tiers
	 */
	public function test_tier_rate_limit_exceeded() {
		$tiers = array(
			'foundation'     => 60,
			'performance'    => 120,
			'transformation' => 180,
		);

		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);

		foreach ( $tiers as $tier => $limit ) {
			$reset_time = time() + 3600;
			$headers    = array(
				'X-RateLimit-Limit'     => $limit,
				'X-RateLimit-Remaining' => 0,
				'X-RateLimit-Reset'     => $reset_time,
			);

			$mock_rate_limiter = $this->createMock( Rate_Limiter::class );
			$mock_rate_limiter->method( 'check_limit' )->willReturn( false );
			$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $headers );
			$mock_rate_limiter->method( 'get_user_limit' )->willReturn(
				array(
					'requests' => $limit,
					'window'   => 3600,
				)
			);

			$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );

			try {
				$this->ai_service->generate_workout_plan( array(), $profile_data );
				$this->fail( "Expected rate limit exception for $tier tier" );
			} catch ( AI_Service_Exception $e ) {
				$this->assertEquals( 'RATE_LIMIT_EXCEEDED', $e->getCode() );
				$data = $e->getData();
				$this->assertArrayHasKey( 'X-RateLimit-Limit', $data );
				$this->assertArrayHasKey( 'X-RateLimit-Remaining', $data );
				$this->assertArrayHasKey( 'X-RateLimit-Reset', $data );
				$this->assertEquals( $limit, $data['X-RateLimit-Limit'] );
				$this->assertEquals( 0, $data['X-RateLimit-Remaining'] );
				$this->assertEquals( $reset_time, $data['X-RateLimit-Reset'] );
			}
		}
	}

	/**
	 * Test tier upgrade during request
	 */
	public function test_tier_upgrade_during_request() {
		$profile_data = array(
			'heightCm'        => 175,
			'weightKg'        => 70,
			'experienceLevel' => 'intermediate',
		);

		// Start with foundation tier
		$foundation_headers = array(
			'X-RateLimit-Limit'     => 60,
			'X-RateLimit-Remaining' => 0,
			'X-RateLimit-Reset'     => time() + 3600,
		);

		$mock_rate_limiter = $this->createMock( Rate_Limiter::class );
		$mock_rate_limiter->method( 'check_limit' )->willReturn( false );
		$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $foundation_headers );

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );

		try {
			$this->ai_service->generate_workout_plan( array(), $profile_data );
			$this->fail( 'Expected rate limit exception for foundation tier' );
		} catch ( AI_Service_Exception $e ) {
			$this->assertEquals( 'RATE_LIMIT_EXCEEDED', $e->getCode() );
		}

		// Upgrade to transformation tier
		$transformation_headers = array(
			'X-RateLimit-Limit'     => 180,
			'X-RateLimit-Remaining' => 180,
			'X-RateLimit-Reset'     => time() + 3600,
		);

		$mock_rate_limiter = $this->createMock( Rate_Limiter::class );
		$mock_rate_limiter->method( 'check_limit' )->willReturn( true );
		$mock_rate_limiter->method( 'get_rate_limit_headers' )->willReturn( $transformation_headers );

		$this->ai_service = new AI_Service( $mock_rate_limiter, $this->wp_functions );
		$response         = $this->ai_service->generate_workout_plan( array(), $profile_data );
		$this->assertNotNull( $response );
	}
}
