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
		$preferences = $request->get_param( 'preferences' );
		$settings    = $request->get_param( 'settings' );

		// TODO: Implement AI workout generation logic
		$workout = array(
			'id'          => uniqid( 'workout_' ),
			'name'        => 'Generated Workout',
			'description' => 'AI-generated workout based on your preferences',
			'difficulty'  => $preferences['fitnessLevel'],
			'duration'    => $preferences['preferredDuration'],
			'exercises'   => array(),
			'targetGoals' => $preferences['targetMuscleGroups'],
			'equipment'   => $preferences['availableEquipment'],
			'createdAt'   => current_time( 'mysql' ),
			'updatedAt'   => current_time( 'mysql' ),
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $workout,
			),
			200
		);
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
