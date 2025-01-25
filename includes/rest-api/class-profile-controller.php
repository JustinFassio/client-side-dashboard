<?php
namespace AthleteDashboard\RestApi;

use AthleteDashboard\Services\Cache_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Profile_Controller extends Rest_Controller_Base {
	/**
	 * Cache expiration time for profile data (1 hour)
	 */
	const CACHE_EXPIRATION = 3600;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->rest_base        = 'profile';
		$this->rate_limit_rules = array(
			'limit'  => 200,  // Higher limit for profile endpoints
			'window' => 3600, // 1 hour window
		);
	}

	/**
	 * Register routes for the profile endpoints.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_profile' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_profile_args(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_profile' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_profile_args(),
				),
			)
		);

		// Add bulk operations endpoint with stricter rate limiting
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'bulk_update_profiles' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'profiles' => array(
						'required' => true,
						'type'     => 'array',
						'items'    => $this->get_profile_args(),
					),
				),
			)
		);
	}

	/**
	 * Check admin permissions for bulk operations.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool|\WP_Error True if permission is granted, WP_Error otherwise.
	 */
	protected function check_admin_permission( $request ) {
		$permission = $this->check_permission( $request );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		if ( ! current_user_can( 'administrator' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to perform bulk operations.', 'athlete-dashboard' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get profile data.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error The response object or error.
	 */
	public function get_profile( $request ) {
		try {
			$user_id = $request['id'];

			// Check if user can access this profile
			if ( ! $this->can_access_profile( $user_id ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'You do not have permission to access this profile.', 'athlete-dashboard' ),
					array( 'status' => 403 )
				);
			}

			// Try to get profile from cache
			$cache_key    = Cache_Service::generate_profile_key( $user_id, 'full' );
			$profile_data = Cache_Service::remember(
				$cache_key,
				function () use ( $user_id ) {
					$user = get_user_by( 'id', $user_id );
					if ( ! $user ) {
						throw new \Exception( __( 'Profile not found.', 'athlete-dashboard' ) );
					}
					return $this->get_profile_data( $user );
				},
				self::CACHE_EXPIRATION
			);

			return $this->prepare_response( $profile_data );
		} catch ( \Exception $e ) {
			return $this->handle_error(
				new \WP_Error(
					'profile_error',
					$e->getMessage(),
					array( 'status' => 500 )
				)
			);
		}
	}

	/**
	 * Update profile data.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error The response object or error.
	 */
	public function update_profile( $request ) {
		try {
			$user_id = $request['id'];

			// Check if user can modify this profile
			if ( ! $this->can_modify_profile( $user_id ) ) {
				return new \WP_Error(
					'rest_forbidden',
					__( 'You do not have permission to modify this profile.', 'athlete-dashboard' ),
					array( 'status' => 403 )
				);
			}

			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return new \WP_Error(
					'profile_not_found',
					__( 'Profile not found.', 'athlete-dashboard' ),
					array( 'status' => 404 )
				);
			}

			// Validate request data
			$data = $this->validate_request(
				$request->get_json_params(),
				Request_Validator::get_profile_rules(),
				'update'
			);

			if ( is_wp_error( $data ) ) {
				return $this->handle_error( $data );
			}

			// Update user meta with transaction-like behavior
			$updated = $this->update_profile_data( $user_id, $data );
			if ( is_wp_error( $updated ) ) {
				return $this->handle_error( $updated );
			}

			// Invalidate cache
			Cache_Service::invalidate_user_cache( $user_id );

			// Return updated profile
			return $this->get_profile( $request );
		} catch ( \Exception $e ) {
			return $this->handle_error(
				new \WP_Error(
					'profile_update_error',
					$e->getMessage(),
					array( 'status' => 500 )
				)
			);
		}
	}

	/**
	 * Bulk update profiles (admin only).
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error The response object or error.
	 */
	public function bulk_update_profiles( $request ) {
		try {
			$profiles = $request->get_param( 'profiles' );
			$results  = array(
				'success' => array(),
				'errors'  => array(),
			);

			foreach ( $profiles as $profile ) {
				$update_request = new \WP_REST_Request( 'POST', "/wp/v2/users/{$profile['id']}" );
				$update_request->set_body_params( $profile );

				$response = $this->update_profile( $update_request );

				if ( is_wp_error( $response ) ) {
					$results['errors'][] = array(
						'id'    => $profile['id'],
						'error' => $response->get_error_message(),
					);
				} else {
					$results['success'][] = $profile['id'];
					// Invalidate cache for this profile
					Cache_Service::invalidate_user_cache( $profile['id'] );
				}
			}

			return $this->prepare_response( $results );
		} catch ( \Exception $e ) {
			return $this->handle_error(
				new \WP_Error(
					'bulk_update_error',
					$e->getMessage(),
					array( 'status' => 500 )
				)
			);
		}
	}

	/**
	 * Get profile data with proper type casting.
	 *
	 * @param \WP_User $user The user object.
	 * @return array The profile data.
	 */
	private function get_profile_data( $user ) {
		// Try to get from cache first
		$cache_key = Cache_Service::generate_user_key( $user->ID, 'meta' );
		return Cache_Service::remember(
			$cache_key,
			function () use ( $user ) {
				return array(
					'id'           => $user->ID,
					'username'     => $user->user_login,
					'email'        => $user->user_email,
					'firstName'    => get_user_meta( $user->ID, 'first_name', true ),
					'lastName'     => get_user_meta( $user->ID, 'last_name', true ),
					'age'          => (int) get_user_meta( $user->ID, 'age', true ),
					'height'       => (float) get_user_meta( $user->ID, 'height', true ),
					'weight'       => (float) get_user_meta( $user->ID, 'weight', true ),
					'medicalNotes' => get_user_meta( $user->ID, 'medical_notes', true ),
					'injuries'     => $this->get_injuries( $user->ID ),
				);
			},
			self::CACHE_EXPIRATION
		);
	}

	/**
	 * Get injuries with proper structure.
	 *
	 * @param int $user_id The user ID.
	 * @return array The injuries array.
	 */
	private function get_injuries( $user_id ) {
		// Try to get from cache first
		$cache_key = Cache_Service::generate_user_key( $user_id, 'injuries' );
		return Cache_Service::remember(
			$cache_key,
			function () use ( $user_id ) {
				$injuries = get_user_meta( $user_id, 'injuries', true );
				if ( ! is_array( $injuries ) ) {
					return array();
				}

				return array_map(
					function ( $injury ) {
						return array(
							'name'        => sanitize_text_field( $injury['name'] ?? '' ),
							'description' => sanitize_textarea_field( $injury['description'] ?? '' ),
							'date'        => sanitize_text_field( $injury['date'] ?? '' ),
							'status'      => sanitize_text_field( $injury['status'] ?? 'active' ),
						);
					},
					$injuries
				);
			},
			self::CACHE_EXPIRATION
		);
	}

	/**
	 * Update profile data with transaction-like behavior.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $data    The validated data.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	private function update_profile_data( $user_id, $data ) {
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $data as $key => $value ) {
				$meta_key = $this->get_meta_key( $key );
				if ( ! update_user_meta( $user_id, $meta_key, $value ) ) {
					throw new \Exception(
						sprintf(
							__( 'Failed to update %s.', 'athlete-dashboard' ),
							$key
						)
					);
				}
			}

			$wpdb->query( 'COMMIT' );

			// Invalidate user cache
			Cache_Service::invalidate_user_cache( $user_id );

			return true;
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error(
				'update_failed',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Check if current user can access a profile.
	 *
	 * @param int $user_id The user ID to check.
	 * @return bool Whether the current user can access the profile.
	 */
	private function can_access_profile( $user_id ) {
		$current_user_id = get_current_user_id();
		return $current_user_id === (int) $user_id || current_user_can( 'administrator' );
	}

	/**
	 * Check if current user can modify a profile.
	 *
	 * @param int $user_id The user ID to check.
	 * @return bool Whether the current user can modify the profile.
	 */
	private function can_modify_profile( $user_id ) {
		return $this->can_access_profile( $user_id );
	}

	/**
	 * Convert camelCase profile field to meta key.
	 *
	 * @param string $field The field name.
	 * @return string The meta key.
	 */
	private function get_meta_key( $field ) {
		$meta_map = array(
			'firstName'    => 'first_name',
			'lastName'     => 'last_name',
			'medicalNotes' => 'medical_notes',
		);

		return isset( $meta_map[ $field ] ) ? $meta_map[ $field ] : $field;
	}

	/**
	 * Get the item schema for profiles.
	 *
	 * @return array The item schema.
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'profile',
			'type'       => 'object',
			'properties' => array(
				'id'        => array(
					'description' => __( 'Unique identifier for the user.', 'athlete-dashboard' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'username'  => array(
					'description' => __( 'Username for the user.', 'athlete-dashboard' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'email'     => array(
					'description' => __( 'Email address for the user.', 'athlete-dashboard' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
				),
				'firstName' => array(
					'description' => __( 'First name for the user.', 'athlete-dashboard' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'lastName'  => array(
					'description' => __( 'Last name for the user.', 'athlete-dashboard' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
	}
}
