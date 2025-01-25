<?php
namespace AthleteDashboard\RestApi;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AthleteDashboard\Services\Cache_Service;

class Overview_Controller extends Rest_Controller_Base {
	/**
	 * Cache expiration time for overview data (5 minutes)
	 */
	const CACHE_EXPIRATION = 300;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace        = 'athlete-dashboard/v1';
		$this->rest_base        = 'overview';
		$this->rate_limit_rules = array(
			'limit'  => 100,  // 100 requests per hour
			'window' => 3600, // 1 hour window
		);
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<user_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_overview_data' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/goals/(?P<goal_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_goal' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'progress' => array(
							'required'          => true,
							'type'              => 'integer',
							'minimum'           => 0,
							'maximum'           => 100,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activity/(?P<activity_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'dismiss_activity' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Check if the current user has permission.
	 */
	public function check_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to access this endpoint.', 'athlete-dashboard' ),
				array( 'status' => 401 )
			);
		}

		$user_id = $request->get_param( 'user_id' );
		if ( $user_id && get_current_user_id() !== (int) $user_id ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You can only access your own data.', 'athlete-dashboard' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get overview data for a user.
	 */
	public function get_overview_data( $request ) {
		try {
			$user_id = (int) $request->get_param( 'user_id' );

			// Check if user can access this overview
			if ( ! $this->can_access_overview( $user_id ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'You do not have permission to access this overview.', 'athlete-dashboard' ),
					array( 'status' => 403 )
				);
			}

			// Try to get overview data from cache
			$cache_key = Cache_Service::generate_user_key( $user_id, 'overview' );
			$data      = Cache_Service::remember(
				$cache_key,
				function () use ( $user_id ) {
					// Get workouts completed
					$workouts_completed = (int) get_user_meta( $user_id, 'workouts_completed', true );

					// Get active programs
					$active_programs = $this->get_active_programs( $user_id );

					// Get nutrition score
					$nutrition_score = $this->calculate_nutrition_score( $user_id );

					// Get recent activity
					$recent_activity = $this->get_recent_activity( $user_id );

					// Get goals
					$goals = $this->get_user_goals( $user_id );

					return array(
						'stats'           => array(
							'workouts_completed' => $workouts_completed,
							'active_programs'    => count( $active_programs ),
							'nutrition_score'    => $nutrition_score,
						),
						'recent_activity' => $recent_activity,
						'goals'           => $goals,
					);
				},
				self::CACHE_EXPIRATION
			);

			return $this->prepare_response( $data );
		} catch ( \Exception $e ) {
			return $this->handle_error(
				new \WP_Error(
					'overview_error',
					$e->getMessage(),
					array( 'status' => 500 )
				)
			);
		}
	}

	/**
	 * Update a goal's progress.
	 */
	public function update_goal( $request ) {
		try {
			$goal_id  = (int) $request->get_param( 'goal_id' );
			$progress = (int) $request->get_param( 'progress' );

			// Update goal progress
			$updated = update_post_meta( $goal_id, 'goal_progress', $progress );

			if ( $updated ) {
				// Invalidate user's overview cache
				$user_id = get_post_field( 'post_author', $goal_id );
				Cache_Service::invalidate_user_data( $user_id, 'overview' );
				return $this->prepare_response( array( 'success' => true ) );
			}

			return $this->handle_error(
				new \WP_Error(
					'update_failed',
					__( 'Failed to update goal progress.', 'athlete-dashboard' ),
					array( 'status' => 500 )
				)
			);
		} catch ( \Exception $e ) {
			return $this->handle_error(
				new \WP_Error(
					'goal_update_error',
					$e->getMessage(),
					array( 'status' => 500 )
				)
			);
		}
	}

	/**
	 * Dismiss an activity.
	 */
	public function dismiss_activity( $request ) {
		try {
			$activity_id = (int) $request->get_param( 'activity_id' );

			// Mark activity as dismissed
			$updated = update_post_meta( $activity_id, 'activity_dismissed', true );

			if ( $updated ) {
				// Invalidate user's overview cache
				$user_id = get_post_field( 'post_author', $activity_id );
				Cache_Service::invalidate_user_data( $user_id, 'overview' );
				return $this->prepare_response( array( 'success' => true ) );
			}

			return $this->handle_error(
				new \WP_Error(
					'dismiss_failed',
					__( 'Failed to dismiss activity.', 'athlete-dashboard' ),
					array( 'status' => 500 )
				)
			);
		} catch ( \Exception $e ) {
			return $this->handle_error(
				new \WP_Error(
					'activity_dismiss_error',
					$e->getMessage(),
					array( 'status' => 500 )
				)
			);
		}
	}

	/**
	 * Check if current user can access an overview.
	 *
	 * @param int $user_id The user ID to check.
	 * @return bool Whether the current user can access the overview.
	 */
	private function can_access_overview( $user_id ) {
		$current_user_id = get_current_user_id();
		return $current_user_id === (int) $user_id || current_user_can( 'administrator' );
	}

	/**
	 * Get active programs for a user.
	 */
	private function get_active_programs( int $user_id ): array {
		// Try to get active programs from cache
		$cache_key = Cache_Service::generate_user_key( $user_id, 'active_programs' );
		return Cache_Service::remember(
			$cache_key,
			function () use ( $user_id ) {
				$args = array(
					'post_type'   => 'program',
					'post_status' => 'publish',
					'meta_query'  => array(
						array(
							'key'   => 'program_user',
							'value' => $user_id,
						),
						array(
							'key'   => 'program_status',
							'value' => 'active',
						),
					),
				);

				$query = new \WP_Query( $args );
				return $query->posts;
			},
			self::CACHE_EXPIRATION
		);
	}

	/**
	 * Calculate nutrition score for a user.
	 */
	private function calculate_nutrition_score( int $user_id ): int {
		// Try to get nutrition score from cache
		$cache_key = Cache_Service::generate_user_key( $user_id, 'nutrition_score' );
		return Cache_Service::remember(
			$cache_key,
			function () use ( $user_id ) {
				$score = (int) get_user_meta( $user_id, 'nutrition_score', true );
				return $score ?: 0;
			},
			self::CACHE_EXPIRATION
		);
	}

	/**
	 * Get recent activity for a user.
	 */
	private function get_recent_activity( int $user_id ): array {
		// Try to get recent activity from cache
		$cache_key = Cache_Service::generate_user_key( $user_id, 'recent_activity' );
		return Cache_Service::remember(
			$cache_key,
			function () use ( $user_id ) {
				$args = array(
					'post_type'      => 'activity',
					'posts_per_page' => 10,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'meta_query'     => array(
						array(
							'key'   => 'activity_user',
							'value' => $user_id,
						),
						array(
							'key'     => 'activity_dismissed',
							'compare' => 'NOT EXISTS',
						),
					),
				);

				$query      = new \WP_Query( $args );
				$activities = array();

				foreach ( $query->posts as $post ) {
					$activities[] = array(
						'id'    => $post->ID,
						'type'  => get_post_meta( $post->ID, 'activity_type', true ),
						'title' => $post->post_title,
						'date'  => get_the_date( 'Y-m-d', $post ),
					);
				}

				return $activities;
			},
			self::CACHE_EXPIRATION
		);
	}

	/**
	 * Get goals for a user.
	 */
	private function get_user_goals( int $user_id ): array {
		// Try to get user goals from cache
		$cache_key = Cache_Service::generate_user_key( $user_id, 'goals' );
		return Cache_Service::remember(
			$cache_key,
			function () use ( $user_id ) {
				$args = array(
					'post_type'      => 'goal',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'   => 'goal_user',
							'value' => $user_id,
						),
					),
				);

				$query = new \WP_Query( $args );
				$goals = array();

				foreach ( $query->posts as $post ) {
					$goals[] = array(
						'id'          => $post->ID,
						'title'       => $post->post_title,
						'progress'    => (int) get_post_meta( $post->ID, 'goal_progress', true ),
						'target_date' => get_post_meta( $post->ID, 'goal_target_date', true ),
					);
				}

				return $goals;
			},
			self::CACHE_EXPIRATION
		);
	}

	/**
	 * Get collection parameters.
	 */
	public function get_collection_params(): array {
		return array(
			'user_id' => array(
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
