<?php
namespace AthleteDashboard\Features\WorkoutGenerator;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class WorkoutEndpoints {
	private const NAMESPACE = 'athlete-dashboard/v1';
	private const BASE      = '/workout-generator';

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/generate',
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

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/save',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_workout' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'userId'  => array(
						'required' => true,
						'type'     => 'integer',
					),
					'workout' => array(
						'required' => true,
						'type'     => 'object',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/history/(?P<userId>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_workout_history' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'userId' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);
	}

	public function check_permission( WP_REST_Request $request ): bool {
		return is_user_logged_in() && current_user_can( 'read' );
	}

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

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $workout,
				),
				200
			);

		} catch ( AI_Service_Exception $e ) {
			return new WP_Error(
				$e->getCode() ?: 'ai_service_error',
				$e->getMessage(),
				array(
					'status' => 500,
					'data'   => $e->getData(),
				)
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'generation_failed',
				'Failed to generate workout: ' . $e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	public function save_workout( WP_REST_Request $request ): WP_REST_Response {
		$user_id = $request->get_param( 'userId' );
		$workout = $request->get_param( 'workout' );

		// Save workout to user meta
		$saved = update_user_meta( $user_id, 'workout_' . $workout['id'], $workout );

		if ( ! $saved ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => array(
						'code'    => 'save_error',
						'message' => 'Failed to save workout',
					),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $workout,
			),
			200
		);
	}

	public function get_workout_history( WP_REST_Request $request ): WP_REST_Response {
		$user_id = $request->get_param( 'userId' );

		// Get all user meta keys that start with 'workout_'
		global $wpdb;
		$workout_keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_key FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE %s",
				$user_id,
				'workout_%'
			)
		);

		$workouts = array();
		foreach ( $workout_keys as $key ) {
			$workout = get_user_meta( $user_id, $key, true );
			if ( $workout ) {
				$workouts[] = $workout;
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $workouts,
			),
			200
		);
	}
}
