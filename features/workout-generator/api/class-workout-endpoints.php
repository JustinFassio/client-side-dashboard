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
	public function generate_workout( WP_REST_Request $request ) {
		$user_id = get_current_user_id();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Workout Generator: Generating workout for user %d', $user_id ) );
		}

		// Get profile data
		$repository      = new Profile_Repository();
		$validator       = new Profile_Validator();
		$profile_service = new Profile_Service( $repository, $validator );

		$profile_data = $profile_service->get_profile( $user_id );

		if ( is_wp_error( $profile_data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Workout Generator: Profile error - %s', $profile_data->get_error_message() ) );
			}
			return new WP_Error(
				'profile_error',
				__( 'Unable to generate workout: Profile not found or incomplete. Please complete your profile first.', 'athlete-dashboard' ),
				array( 'status' => 400 )
			);
		}

		// Validate profile data for workout requirements
		$validation_result = $validator->validate_workout_requirements( $profile_data );
		if ( is_wp_error( $validation_result ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Workout Generator: Profile validation error - %s', $validation_result->get_error_message() ) );
			}
			return new WP_Error(
				'profile_validation_error',
				sprintf(
					__( 'Unable to generate workout: %s', 'athlete-dashboard' ),
					$validation_result->get_error_message()
				),
				array( 'status' => 400 )
			);
		}

		// Get workout preferences from request
		$preferences = $request->get_param( 'preferences' ) ?? array();

		// Merge profile data with preferences
		$workout_params = array_merge(
			array(
				'heightCm'        => $profile_data['heightCm'],
				'weightKg'        => $profile_data['weightKg'],
				'experienceLevel' => $profile_data['experienceLevel'],
				'age'             => $profile_data['age'] ?? null,
				'gender'          => $profile_data['gender'] ?? null,
				'injuries'        => $profile_data['injuries'] ?? array(),
				'equipment'       => $profile_data['equipment'] ?? array(),
			),
			$preferences
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Workout Generator: Using parameters - %s', print_r( $workout_params, true ) ) );
		}

		// Generate workout using AI Service
		$ai_service   = new AI_Service();
		$workout_data = $ai_service->generate_workout_plan( $workout_params );

		if ( is_wp_error( $workout_data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Workout Generator: AI Service error - %s', $workout_data->get_error_message() ) );
			}
			return $workout_data;
		}

		return rest_ensure_response( $workout_data );
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
