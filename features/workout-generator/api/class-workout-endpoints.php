<?php
/**
 * Workout Generator REST API endpoints
 */

namespace AthleteDashboard\Features\WorkoutGenerator\API;

use Exception;
use WP_Error;
use WP_REST_Request;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\Repository\Profile_Repository;
use AthleteDashboard\Features\Profile\Validation\Profile_Validator;
use AthleteDashboard\Features\WorkoutGenerator\API\AI_Service;
use AthleteDashboard\Features\WorkoutGenerator\API\Workout_Validator;
use AthleteDashboard\Features\WorkoutGenerator\API\Rate_Limiter;

class Workout_Endpoints {
	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		error_log( 'Registering workout generator endpoints...' );

		\register_rest_route(
			'athlete-dashboard/v1',
			'/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_workout' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'preferences' => array(
						'required' => true,
						'type'     => 'object',
					),
					'settings'    => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);
		error_log( 'Registered /generate endpoint with POST method' );

		\register_rest_route(
			'athlete-dashboard/v1',
			'/modify',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'modify_workout' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'modifications' => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);

		\register_rest_route(
			'athlete-dashboard/v1',
			'/history',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_workout_history' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'filters' => array(
						'required' => false,
						'type'     => 'object',
					),
				),
			)
		);

		\register_rest_route(
			'athlete-dashboard/v1',
			'/workout/alternative/(?P<exercise_id>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_exercise_alternative' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'constraints' => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);
	}

	/**
	 * Generate a workout plan.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function generate_workout( WP_REST_Request $request ): WP_REST_Response {
		try {
			$preferences = $request->get_param( 'preferences' );
			$settings    = $request->get_param( 'settings' );
			$user_id     = get_current_user_id();

			// Get user profile data
			$profile_service = new \AthleteDashboard\Features\Profile\API\Profile_Service();
			$profile         = $profile_service->get_profile( $user_id );
			$training_prefs  = $profile_service->get_training_preferences( $user_id );
			$equipment       = $profile_service->get_equipment_availability( $user_id );

			// Generate AI prompt
			$prompt = array(
				'profile'             => $profile,
				'preferences'         => $preferences,
				'trainingPreferences' => $training_prefs,
				'equipment'           => $equipment,
				'settings'            => $settings,
			);

			// Call AI service
			$ai_service = new AI_Service();
			$workout    = $ai_service->generate_workout_plan_with_profile( $prompt );

			// Validate workout
			$validator         = new Workout_Validator();
			$validation_result = $validator->validate(
				$workout,
				array(
					'maxExercises'   => $preferences['maxExercises'] ?? 10,
					'minRestPeriod'  => $preferences['minRestPeriod'] ?? 60,
					'requiredWarmup' => true,
				)
			);

			if ( ! $validation_result['isValid'] ) {
				return new WP_Error(
					'validation_failed',
					'Generated workout failed validation',
					array(
						'status' => 400,
						'errors' => $validation_result['errors'],
					)
				);
			}

			// Add metadata
			$workout['createdAt'] = current_time( 'mysql' );
			$workout['updatedAt'] = current_time( 'mysql' );
			$workout['userId']    = $user_id;

			$response = new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $workout,
				),
				200
			);

			// Add rate limit headers to response
			$rate_limiter = new Rate_Limiter();
			$headers      = $rate_limiter->get_rate_limit_headers();
			foreach ( $headers as $header => $value ) {
				$response->header( $header, $value );
			}

			return $response;

		} catch ( AI_Service_Exception $e ) {
			$error = new WP_Error(
				$e->getCode() ?: 'ai_service_error',
				$e->getMessage(),
				array(
					'status' => 500,
					'data'   => $e->getData(),
				)
			);

			// Add rate limit headers from exception data
			if ( is_array( $e->getData() ) && isset( $e->getData()['X-RateLimit-Limit'] ) ) {
				$error->add_data(
					array(
						'headers' => array(
							'X-RateLimit-Limit'     => $e->getData()['X-RateLimit-Limit'],
							'X-RateLimit-Remaining' => $e->getData()['X-RateLimit-Remaining'],
							'X-RateLimit-Reset'     => $e->getData()['X-RateLimit-Reset'],
						),
					)
				);
			}

			return $error;
		} catch ( \Exception $e ) {
			return new WP_Error(
				'generation_failed',
				'Failed to generate workout: ' . $e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Modify an existing workout
	 */
	public function modify_workout( WP_REST_Request $request ) {
		try {
			$workout_id    = $request->get_param( 'id' );
			$modifications = $request->get_json_params();

			// Get current workout
			$ai_service      = new AI_Service();
			$current_workout = $ai_service->get_workout_by_id( $workout_id );

			if ( ! $current_workout ) {
				return new WP_Error(
					'not_found',
					'Workout not found',
					array( 'status' => 404 )
				);
			}

			// Apply modifications
			$modified_workout = $ai_service->modify_workout_plan( $current_workout, $modifications );

			// Validate modified workout
			$validator         = new Workout_Validator();
			$validation_result = $validator->validate(
				$modified_workout,
				array(
					'maxExercises'   => $current_workout['preferences']['maxExercises'],
					'minRestPeriod'  => $current_workout['preferences']['minRestPeriod'],
					'requiredWarmup' => true,
				)
			);

			if ( ! $validation_result['isValid'] ) {
				return new WP_Error(
					'validation_failed',
					'Modified workout failed validation',
					array(
						'status' => 400,
						'errors' => $validation_result['errors'],
					)
				);
			}

			return \rest_ensure_response( $modified_workout );

		} catch ( Exception $e ) {
			return new WP_Error(
				'modification_failed',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get workout history
	 */
	public function get_workout_history( WP_REST_Request $request ) {
		try {
			$user_id = \get_current_user_id();
			$filters = $request->get_param( 'filters' );

			$ai_service = new AI_Service();
			$history    = $ai_service->get_workout_history( $user_id, $filters );

			return \rest_ensure_response( $history );

		} catch ( Exception $e ) {
			return new WP_Error(
				'history_fetch_failed',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get alternative exercise
	 */
	public function get_exercise_alternative( WP_REST_Request $request ) {
		try {
			$exercise_id = $request->get_param( 'exercise_id' );
			$constraints = $request->get_json_params();

			$ai_service = new AI_Service();

			// Get original exercise
			$exercise = $ai_service->get_exercise_by_id( $exercise_id );
			if ( ! $exercise ) {
				return new WP_Error(
					'not_found',
					'Exercise not found',
					array( 'status' => 404 )
				);
			}

			// Get alternatives
			$alternatives = $ai_service->suggest_alternatives( $exercise, $constraints );

			if ( empty( $alternatives ) ) {
				return new WP_Error(
					'no_alternatives',
					'No suitable alternatives found',
					array( 'status' => 404 )
				);
			}

			return \rest_ensure_response( $alternatives[0] );

		} catch ( Exception $e ) {
			return new WP_Error(
				'alternative_failed',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Check if user has permission to access endpoints
	 */
	public function check_permission() {
		$is_logged_in = \is_user_logged_in();
		error_log( 'Checking workout endpoint permission. User logged in: ' . ( $is_logged_in ? 'yes' : 'no' ) );
		return $is_logged_in;
	}

	/**
	 * Validate workout preferences
	 */
	private function validate_preferences( $preferences ) {
		if ( ! is_array( $preferences ) ) {
			throw new Exception( 'Invalid preferences format' );
		}

		// Validate maxExercises
		if ( isset( $preferences['maxExercises'] ) ) {
			$max = intval( $preferences['maxExercises'] );
			if ( $max <= 0 || $max > 50 ) {
				throw new Exception( 'maxExercises must be between 1 and 50' );
			}
		}

		// Validate minRestPeriod
		if ( isset( $preferences['minRestPeriod'] ) ) {
			$rest = intval( $preferences['minRestPeriod'] );
			if ( $rest < 0 || $rest > 600 ) {
				throw new Exception( 'minRestPeriod must be between 0 and 600 seconds' );
			}
		}

		// Validate intensity
		if ( isset( $preferences['intensity'] ) ) {
			$intensity = intval( $preferences['intensity'] );
			if ( $intensity < 1 || $intensity > 10 ) {
				throw new Exception( 'intensity must be between 1 and 10' );
			}
		}

		// Validate equipment
		if ( isset( $preferences['equipment'] ) ) {
			if ( ! is_array( $preferences['equipment'] ) ) {
				throw new Exception( 'equipment must be an array' );
			}
			foreach ( $preferences['equipment'] as $item ) {
				if ( ! is_string( $item ) || empty( $item ) ) {
					throw new Exception( 'equipment items must be non-empty strings' );
				}
			}
		}
	}
}
