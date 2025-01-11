<?php
/**
 * Profile API endpoints for the Athlete Dashboard.
 *
 * This file contains the REST API endpoints for managing athlete profiles,
 * including profile data retrieval, updates, and validation.
 *
 * @package AthleteDashboardChild
 * @subpackage API
 */

namespace AthleteProfile\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class ProfileEndpoints
 *
 * Handles all REST API endpoints related to athlete profiles.
 * Provides functionality for managing profile data, user data,
 * and combined profile information.
 *
 * @package AthleteDashboardChild
 * @subpackage API
 */
class ProfileEndpoints {
	/**
	 * API namespace for all endpoints.
	 *
	 * @var string
	 */
	const NAMESPACE = 'athlete-dashboard/v1';

	/**
	 * Base route for profile endpoints.
	 *
	 * @var string
	 */
	const ROUTE = 'profile';

	/**
	 * Meta key for storing profile data.
	 *
	 * @var string
	 */
	const META_KEY = '_athlete_profile_data';

	/**
	 * Initialize the endpoints.
	 *
	 * Registers all necessary hooks and actions for the profile endpoints.
	 *
	 * @return void
	 */
	public static function init() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Initializing ProfileEndpoints.' );
		}

		// Register endpoints when WordPress initializes the REST API.
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		add_action(
			'init',
			function () {
					error_log( 'WordPress init: ProfileEndpoints loaded.' );
				}
			);
		}
	}

	/**
	 * Register profile endpoints.
	 *
	 * Registers all REST API endpoints for profile management:
	 * - Test endpoint for debugging (when WP_DEBUG is enabled)
	 * - Main profile endpoints for CRUD operations
	 * - User data endpoints
	 * - Combined data endpoint
	 * - Basic data endpoint
	 *
	 * @return void
	 */
	public static function register_routes() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Registering profile endpoints.' );
		error_log( 'Namespace: ' . self::NAMESPACE );
		error_log( 'Route: ' . self::ROUTE );
		}

		// Test endpoint for debugging (only when WP_DEBUG is enabled)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		register_rest_route(
			self::NAMESPACE,
			'/' . self::ROUTE . '/test',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => function () {
					$user_id      = get_current_user_id();
					$raw_meta     = get_user_meta( $user_id );
					$profile_data = get_user_meta( $user_id, self::META_KEY, true );

					return rest_ensure_response(
						array(
							'status'    => 'ok',
							'message'   => 'Profile API is working',
							'timestamp' => current_time( 'mysql' ),
							'debug'     => array(
								'user_id'      => $user_id,
								'meta_key'     => self::META_KEY,
								'profile_data' => $profile_data,
								'all_meta'     => $raw_meta,
							),
						)
					);
				},
				'permission_callback' => array( self::class, 'check_auth' ),
			)
		);
		}

		// Main profile endpoints
		register_rest_route(
			self::NAMESPACE,
			'/' . self::ROUTE,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_profile' ),
					'permission_callback' => array( self::class, 'check_auth' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'update_profile' ),
					'permission_callback' => array( self::class, 'check_auth' ),
				),
			)
		);

		// User data endpoints
		register_rest_route(
			self::NAMESPACE,
			'/' . self::ROUTE . '/user',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get_user_data' ),
					'permission_callback' => array( self::class, 'check_auth' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'update_user_data' ),
					'permission_callback' => array( self::class, 'check_auth' ),
				),
			)
		);

		// Combined data endpoint
		register_rest_route(
			self::NAMESPACE,
			'/' . self::ROUTE . '/full',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_combined_data' ),
				'permission_callback' => array( self::class, 'check_auth' ),
			)
		);

		// Basic data endpoint
		register_rest_route(
			self::NAMESPACE,
			'/' . self::ROUTE . '/basic',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'get_basic_data' ),
				'permission_callback' => array( self::class, 'check_auth' ),
			)
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Profile endpoints registered successfully.' );
		}
	}

	/**
	 * Check if user is authenticated.
	 *
	 * Verifies that the current user is logged in and has permission
	 * to access the profile endpoints.
	 *
	 * @return bool True if user is authenticated, false otherwise.
	 */
	public static function check_auth() {
		if ( ! is_user_logged_in() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Unauthorized profile API access attempt.' );
			}
			return false;
		}
		return true;
	}

	/**
	 * Get profile data for the current user.
	 *
	 * Retrieves the complete profile data for the currently logged-in user,
	 * including any custom fields and preferences.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or error object on failure.
	 */
	public static function get_profile() {
		$user_id = get_current_user_id();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "Fetching profile for user: $user_id" );
		}

		try {
			$profile_data = self::get_profile_data( $user_id );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Profile data retrieved successfully for user: $user_id" );
			error_log( 'Profile data: ' . wp_json_encode( $profile_data ) );
			}

			// Ensure age is properly cast to integer
			if ( isset( $profile_data['age'] ) ) {
				$profile_data['age'] = absint( $profile_data['age'] );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'profile' => $profile_data,
					),
				)
			);
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Error fetching profile: ' . $e->getMessage() );
			}
			return new WP_Error(
				'profile_fetch_error',
				'Failed to fetch profile data',
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Update profile data for the current user.
	 *
	 * Updates the profile data for the currently logged-in user with the provided data.
	 * Validates the input data before saving and merges it with existing data.
	 *
	 * @param WP_REST_Request $request The request object containing the profile data to update.
	 * @return WP_REST_Response|WP_Error Response object on success, or error object on failure.
	 */
	public static function update_profile( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$data    = $request->get_json_params();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '=== Profile Update Request ===' );
		error_log( "User ID: $user_id" );
			error_log( 'Raw request data: ' . wp_json_encode( $request->get_body() ) );
			error_log( 'Parsed JSON data: ' . wp_json_encode( $data ) );
		}

		if ( empty( $data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'No profile data provided in request.' );
			}
			return new WP_Error(
				'invalid_params',
				'No profile data provided',
				array( 'status' => 400 )
			);
		}

		try {
			// Convert age to integer if provided
			if ( isset( $data['age'] ) ) {
				$original_age = $data['age'];
				$data['age']  = absint( $data['age'] );

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Age conversion: $original_age -> {$data['age']}" );
				}
			}

			$validation = self::validate_profile_data( $data );
			if ( is_wp_error( $validation ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Profile validation failed: ' . $validation->get_error_message() );
					error_log( 'Validation errors: ' . wp_json_encode( $validation->get_error_data() ) );
				}
				return $validation;
			}

			$current_data = self::get_profile_data( $user_id );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Current profile data: ' . wp_json_encode( $current_data ) );
			}

			$updated_data = array_merge( $current_data, $data );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Merged profile data: ' . wp_json_encode( $updated_data ) );
			error_log( 'Saving profile data to meta key: ' . self::META_KEY );
			}

			$update_success = update_user_meta( $user_id, self::META_KEY, $updated_data );

			if ( $update_success === false ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Failed to update profile data in user meta.' );
				}
				return new WP_Error(
					'update_failed',
					'Failed to update profile',
					array( 'status' => 500 )
				);
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Profile updated successfully for user: $user_id" );
			error_log( '=== End Profile Update ===' );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'profile' => $updated_data,
					),
				)
			);
		} catch ( \Exception $e ) {
			error_log( 'Error updating profile: ' . $e->getMessage() );
			error_log( 'Error trace: ' . $e->getTraceAsString() );
			return new WP_Error(
				'profile_update_error',
				'Failed to update profile',
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get profile data for a specific user.
	 *
	 * Retrieves the stored profile data for the specified user ID.
	 * If no data exists, returns an empty array as the default.
	 *
	 * @param int $user_id The ID of the user to get profile data for.
	 * @return array The user's profile data or an empty array if none exists.
	 * @throws \Exception If there's an error retrieving the profile data.
	 */
	private static function get_profile_data( $user_id ) {
		if ( empty( $user_id ) ) {
			throw new \Exception( 'Invalid user ID provided.' );
		}

		try {
		$profile_data = get_user_meta( $user_id, self::META_KEY, true );

			// If no data exists, return empty array as default
		if ( empty( $profile_data ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "No profile data found for user: $user_id" );
				}
				return array();
			}

			// Ensure we have an array
			if ( ! is_array( $profile_data ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "Invalid profile data format for user: $user_id" );
				}
				return array();
			}

		return $profile_data;
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error retrieving profile data: ' . $e->getMessage() );
			}
			throw $e;
		}
	}

	/**
	 * Validate profile data before saving.
	 *
	 * Performs validation checks on the profile data to ensure it meets
	 * the required format and constraints.
	 *
	 * @param array $data The profile data to validate.
	 * @return true|WP_Error True if validation passes, WP_Error if validation fails.
	 */
	private static function validate_profile_data( $data ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'invalid_data_format',
				'Profile data must be an array',
				array( 'status' => 400 )
			);
		}

		$allowed_fields = array(
			'phoneNumber',
			'age',
			'gender',
			'height',
			'weight',
			'preferredUnits',
			'fitnessLevel',
			'activityLevel',
			'medicalConditions',
			'exerciseLimitations',
			'medications',
			'injuries',
			'physicalMetrics',
		);

		$allowed_units           = array( 'imperial', 'metric' );
		$allowed_fitness_levels  = array( 'beginner', 'intermediate', 'advanced', 'expert' );
		$allowed_activity_levels = array( 'sedentary', 'light', 'moderate', 'very_active', 'extra_active' );
		$allowed_genders         = array( 'male', 'female', 'other', 'prefer_not_to_say' );

		foreach ( $data as $field => $value ) {
			if ( ! in_array( $field, $allowed_fields, true ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "Invalid field in profile data: $field" );
				}
				continue;
			}

			switch ( $field ) {
				case 'age':
					if ( ! is_numeric( $value ) || $value < 0 || $value > 120 ) {
						return new WP_Error(
							'invalid_age',
							'Age must be between 0 and 120',
							array( 'status' => 400 )
						);
					}
					break;

				case 'gender':
					if ( ! empty( $value ) && ! in_array( $value, $allowed_genders, true ) ) {
						return new WP_Error(
							'invalid_gender',
							'Invalid gender value provided',
							array(
								'status'         => 400,
								'allowed_values' => $allowed_genders,
							)
						);
					}
					break;

				case 'preferredUnits':
					if ( ! empty( $value ) && ! in_array( $value, $allowed_units, true ) ) {
						return new WP_Error(
							'invalid_units',
							'Invalid units preference',
							array(
								'status'         => 400,
								'allowed_values' => $allowed_units,
							)
						);
					}
					break;

				case 'fitnessLevel':
					if ( ! empty( $value ) && ! in_array( $value, $allowed_fitness_levels, true ) ) {
						return new WP_Error(
							'invalid_fitness_level',
							'Invalid fitness level',
							array(
								'status'         => 400,
								'allowed_values' => $allowed_fitness_levels,
							)
						);
					}
					break;

				case 'activityLevel':
					if ( ! empty( $value ) && ! in_array( $value, $allowed_activity_levels, true ) ) {
						return new WP_Error(
							'invalid_activity_level',
							'Invalid activity level',
							array(
								'status'         => 400,
								'allowed_values' => $allowed_activity_levels,
							)
						);
					}
					break;

				case 'height':
				case 'weight':
					if ( ! empty( $value ) && ( ! is_numeric( $value ) || $value < 0 ) ) {
						return new WP_Error(
							"invalid_$field",
							ucfirst( $field ) . ' must be a positive number',
							array( 'status' => 400 )
						);
					}
					break;

				case 'medicalConditions':
				case 'exerciseLimitations':
				case 'injuries':
					if ( ! empty( $value ) && ! is_array( $value ) ) {
						return new WP_Error(
							"invalid_$field",
							ucfirst( $field ) . ' must be an array',
							array( 'status' => 400 )
						);
					}
					break;

				case 'physicalMetrics':
					if ( ! empty( $value ) ) {
						if ( ! is_array( $value ) ) {
							return new WP_Error(
								'invalid_physical_metrics',
								'Physical metrics must be an array',
								array( 'status' => 400 )
							);
						}

						foreach ( $value as $metric ) {
							if ( ! isset( $metric['type'], $metric['value'], $metric['unit'], $metric['date'] ) ) {
								return new WP_Error(
									'invalid_metric_format',
									'Each metric must have type, value, unit, and date',
									array( 'status' => 400 )
								);
							}

							if ( ! is_numeric( $metric['value'] ) || $metric['value'] < 0 ) {
								return new WP_Error(
									'invalid_metric_value',
									'Metric value must be a positive number',
									array( 'status' => 400 )
								);
							}
						}
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Get user data for the current user.
	 *
	 * Retrieves basic user information from WordPress core for the currently
	 * logged-in user.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or error object on failure.
	 */
	public static function get_user_data() {
		$user_id = get_current_user_id();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Fetching user data for ID: $user_id" );
		}

		try {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "User not found for ID: $user_id" );
				}
				return new WP_Error(
					'user_not_found',
					'User not found',
					array( 'status' => 404 )
				);
			}

			$user_data = array(
				'id'          => $user_id,
				'username'    => $user->user_login,
				'email'       => $user->user_email,
				'displayName' => $user->display_name,
				'firstName'   => $user->first_name,
				'lastName'    => $user->last_name,
				'roles'       => $user->roles,
			);

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => $user_data,
				)
			);
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Error fetching user data: ' . $e->getMessage() );
			}
			return new WP_Error(
				'user_fetch_error',
				'Failed to fetch user data',
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Update user data for the current user.
	 *
	 * Updates basic user information in WordPress core for the currently
	 * logged-in user.
	 *
	 * @param WP_REST_Request $request The request object containing the user data to update.
	 * @return WP_REST_Response|WP_Error Response object on success, or error object on failure.
	 */
	public static function update_user_data( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$data    = $request->get_json_params();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '=== User Data Update Request ===' );
			error_log( "User ID: $user_id" );
		error_log( 'Update data: ' . wp_json_encode( $data ) );
		}

		if ( empty( $data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'No user data provided in request' );
			}
				return new WP_Error(
				'invalid_params',
				'No user data provided',
				array( 'status' => 400 )
			);
		}

		try {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "User not found for ID: $user_id" );
				}
				return new WP_Error(
					'user_not_found',
					'User not found',
					array( 'status' => 404 )
				);
			}

			$updateable_fields = array(
				'first_name'   => 'firstName',
				'last_name'    => 'lastName',
				'display_name' => 'displayName',
				'user_email'   => 'email',
			);

			$user_data = array( 'ID' => $user_id );
			foreach ( $updateable_fields as $wp_field => $request_field ) {
				if ( isset( $data[ $request_field ] ) ) {
					$user_data[ $wp_field ] = sanitize_text_field( $data[ $request_field ] );
				}
			}

			if ( count( $user_data ) === 1 ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'No valid fields to update' );
				}
				return new WP_Error(
					'invalid_params',
					'No valid fields to update',
					array( 'status' => 400 )
				);
			}

			$result = wp_update_user( $user_data );
			if ( is_wp_error( $result ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Failed to update user: ' . $result->get_error_message() );
				}
				return $result;
			}

			// Get updated user data
			$updated_user  = get_userdata( $user_id );
			$response_data = array(
				'id'          => $user_id,
				'username'    => $updated_user->user_login,
				'email'       => $updated_user->user_email,
				'displayName' => $updated_user->display_name,
				'firstName'   => $updated_user->first_name,
				'lastName'    => $updated_user->last_name,
				'roles'       => $updated_user->roles,
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "User data updated successfully for ID: $user_id" );
				error_log( '=== End User Data Update ===' );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => $response_data,
				)
			);
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Error updating user data: ' . $e->getMessage() );
			}
			return new WP_Error(
				'update_error',
				'Failed to update user data',
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get combined user and profile data.
	 *
	 * Retrieves both WordPress user data and profile data for the current user
	 * and combines them into a single response.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or error object on failure.
	 */
	public static function get_combined_data() {
		$user_id = get_current_user_id();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Fetching combined data for user: $user_id" );
		}

		try {
			$user = get_userdata( $user_id );
		if ( ! $user ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "User not found for ID: $user_id" );
				}
			return new WP_Error(
				'user_not_found',
				'User not found',
				array( 'status' => 404 )
			);
		}

			$profile_data = self::get_profile_data( $user_id );
			$user_data    = array(
				'id'          => $user_id,
				'username'    => $user->user_login,
				'email'       => $user->user_email,
				'displayName' => $user->display_name,
				'firstName'   => $user->first_name,
				'lastName'    => $user->last_name,
				'roles'       => $user->roles,
			);

			$combined_data = array_merge( $user_data, $profile_data );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Combined data retrieved successfully for user: $user_id" );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => $combined_data,
				)
			);
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Error fetching combined data: ' . $e->getMessage() );
			}
			return new WP_Error(
				'data_fetch_error',
				'Failed to fetch combined data',
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get basic user profile data.
	 *
	 * Retrieves a minimal set of user and profile data, suitable for
	 * display in lists or summaries.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or error object on failure.
	 */
	public static function get_basic_data() {
		$user_id = get_current_user_id();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Fetching basic data for user: $user_id" );
		}

		try {
			$user = get_userdata( $user_id );
		if ( ! $user ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "User not found for ID: $user_id" );
				}
			return new WP_Error(
				'user_not_found',
				'User not found',
				array( 'status' => 404 )
			);
		}

			$profile_data = self::get_profile_data( $user_id );
			$basic_data   = array(
				'id'          => $user_id,
				'displayName' => $user->display_name,
				'firstName'   => $user->first_name,
				'lastName'    => $user->last_name,
			);

			// Add selected profile fields if they exist
			$profile_fields = array( 'age', 'gender', 'fitnessLevel', 'activityLevel' );
			foreach ( $profile_fields as $field ) {
				if ( isset( $profile_data[ $field ] ) ) {
					$basic_data[ $field ] = $profile_data[ $field ];
				}
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Basic data retrieved successfully for user: $user_id" );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => $basic_data,
				)
			);
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Error fetching basic data: ' . $e->getMessage() );
			}
			return new WP_Error(
				'data_fetch_error',
				'Failed to fetch basic data',
				array( 'status' => 500 )
			);
		}
	}
}

// Initialize endpoints
ProfileEndpoints::init();

// Debug log when this file is loaded
error_log( 'Profile endpoints file loaded' );
