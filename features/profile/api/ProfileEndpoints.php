<?php
/**
 * Profile endpoints class.
 *
 * @package AthleteDashboard\Features\Profile\API
 */

namespace AthleteDashboard\Features\Profile\API;

use AthleteDashboard\Core\Config\Debug;
use AthleteDashboard\Features\Profile\Config\Config;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_REST_Server;

/**
 * Class ProfileEndpoints
 *
 * Handles REST API endpoints for managing athlete profiles.
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
	 * Track if endpoints have been initialized
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Initialize the endpoints.
	 *
	 * Registers all necessary hooks and actions for the profile endpoints.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			self::log_debug( 'ProfileEndpoints already initialized, skipping.' );
			return;
		}

		self::log_debug( 'Initializing ProfileEndpoints.' );

		// Register endpoints when WordPress initializes the REST API.
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );

		self::log_debug_action( 'init', 'WordPress init: ProfileEndpoints loaded.' );

		self::$initialized = true;
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
		self::log_debug( 'Registering profile endpoints.' );
		self::log_debug( 'Namespace: ' . self::NAMESPACE );
		self::log_debug( 'Route: ' . self::ROUTE );

		// Public test endpoint for debugging
		register_rest_route(
			self::NAMESPACE,
			'/' . self::ROUTE . '/public-test',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => function () {
					return rest_ensure_response(
						array(
							'success'   => true,
							'message'   => 'Profile API public test endpoint is working',
							'timestamp' => current_time( 'mysql' ),
						)
					);
				},
				'permission_callback' => '__return_true',  // Allow public access
			)
		);

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

		self::log_debug( 'Profile endpoints registered successfully.' );
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
			self::log_debug( 'Unauthorized profile API access attempt.' );
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

		if ( ! $user_id ) {
			return new WP_Error(
				'no_user',
				'User not found',
				array( 'status' => 404 )
			);
		}

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

			// Get user data to ensure consistent response format
			$user = get_userdata( $user_id );
			$meta = get_user_meta( $user_id );

			// Merge with basic user data
			$response = array_merge(
				$profile_data,
				array(
					'id'          => $user_id,
					'name'        => $user->display_name,
					'username'    => $user->user_login,
					'email'       => $user->user_email,
					'roles'       => $user->roles,
					'firstName'   => $meta['first_name'][0] ?? '',
					'lastName'    => $meta['last_name'][0] ?? '',
					'displayName' => $user->display_name,
				)
			);

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'profile' => $response,
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

			// Get user data to ensure consistent response format
			$user = get_userdata( $user_id );
			$meta = get_user_meta( $user_id );

			// Merge with basic user data
			$response = array_merge(
				$updated_data,
				array(
					'id'          => $user_id,
					'name'        => $user->display_name,
					'username'    => $user->user_login,
					'email'       => $user->user_email,
					'roles'       => $user->roles,
					'firstName'   => $meta['first_name'][0] ?? '',
					'lastName'    => $meta['last_name'][0] ?? '',
					'displayName' => $user->display_name,
				)
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Profile updated successfully for user: $user_id" );
				error_log( '=== End Profile Update ===' );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'profile' => $response,
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

		$errors = array();

		// Define allowed values for various fields
		$allowed_units           = array( 'imperial', 'metric' );
		$allowed_fitness_levels  = array( 'beginner', 'intermediate', 'advanced', 'expert' );
		$allowed_activity_levels = array( 'sedentary', 'light', 'moderate', 'very_active', 'extra_active' );
		$allowed_genders         = array( 'male', 'female', 'other', 'prefer_not_to_say', '' );

		// Validate core user fields
		if ( isset( $data['email'] ) && ! is_email( $data['email'] ) ) {
			$errors['email'] = 'Invalid email address';
		}

		// Validate name fields
		if ( isset( $data['firstName'] ) && empty( trim( $data['firstName'] ) ) ) {
			$errors['firstName'] = 'First name cannot be empty';
		}
		if ( isset( $data['lastName'] ) && empty( trim( $data['lastName'] ) ) ) {
			$errors['lastName'] = 'Last name cannot be empty';
		}

		// Validate numeric fields
		if ( isset( $data['age'] ) ) {
			$age = absint( $data['age'] );
			if ( $age < 13 || $age > 120 ) {
				$errors['age'] = 'Age must be between 13 and 120';
			}
		}

		if ( isset( $data['height'] ) ) {
			$height = absint( $data['height'] );
			if ( $height < 50 || $height > 300 ) {
				$errors['height'] = 'Height must be between 50cm and 300cm';
			}
		}

		if ( isset( $data['weight'] ) ) {
			$weight = absint( $data['weight'] );
			if ( $weight < 20 || $weight > 500 ) {
				$errors['weight'] = 'Weight must be between 20kg and 500kg';
			}
		}

		// Validate gender field
		if ( isset( $data['gender'] ) && ! in_array( $data['gender'], $allowed_genders, true ) ) {
			$errors['gender'] = sprintf(
				'Invalid gender value. Allowed values: %s',
				implode( ', ', $allowed_genders )
			);
		}

		// Validate phone number if present
		if ( isset( $data['phoneNumber'] ) && ! empty( $data['phoneNumber'] ) ) {
			if ( ! preg_match( '/^[+]?[0-9\s-()]{10,20}$/', $data['phoneNumber'] ) ) {
				$errors['phoneNumber'] = 'Invalid phone number format';
			}
		}

		// Validate emergency contact fields
		if ( isset( $data['emergencyContactName'] ) && empty( trim( $data['emergencyContactName'] ) ) ) {
			$errors['emergencyContactName'] = 'Emergency contact name cannot be empty';
		}
		if ( isset( $data['emergencyContactPhone'] ) ) {
			if ( ! preg_match( '/^[+]?[0-9\s-()]{10,20}$/', $data['emergencyContactPhone'] ) ) {
				$errors['emergencyContactPhone'] = 'Invalid emergency contact phone number';
			}
		}

		// Validate preference fields
		if ( isset( $data['preferredUnits'] ) && ! in_array( $data['preferredUnits'], $allowed_units, true ) ) {
			$errors['preferredUnits'] = sprintf(
				'Invalid units preference. Allowed values: %s',
				implode( ', ', $allowed_units )
			);
		}

		if ( isset( $data['fitnessLevel'] ) && ! in_array( $data['fitnessLevel'], $allowed_fitness_levels, true ) ) {
			$errors['fitnessLevel'] = sprintf(
				'Invalid fitness level. Allowed values: %s',
				implode( ', ', $allowed_fitness_levels )
			);
		}

		if ( isset( $data['activityLevel'] ) && ! in_array( $data['activityLevel'], $allowed_activity_levels, true ) ) {
			$errors['activityLevel'] = sprintf(
				'Invalid activity level. Allowed values: %s',
				implode( ', ', $allowed_activity_levels )
			);
		}

		// Validate array fields
		$array_fields = array( 'medicalConditions', 'exerciseLimitations', 'injuries', 'medications' );
		foreach ( $array_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				if ( ! is_array( $data[ $field ] ) ) {
					$errors[ $field ] = ucfirst( $field ) . ' must be an array';
				} else {
					// Validate each item in the array
					foreach ( $data[ $field ] as $index => $item ) {
						if ( $field === 'injuries' ) {
							if ( ! isset( $item['name'], $item['description'], $item['date'], $item['status'] ) ) {
								$errors[ $field . '_' . $index ] = 'Each injury must have name, description, date, and status';
							}
						} elseif ( ! is_string( $item ) || empty( trim( $item ) ) ) {
							$errors[ $field . '_' . $index ] = 'Each ' . rtrim( $field, 's' ) . ' must be a non-empty string';
						}
					}
				}
			}
		}

		// Validate physical metrics if present
		if ( isset( $data['physicalMetrics'] ) ) {
			if ( ! is_array( $data['physicalMetrics'] ) ) {
				$errors['physicalMetrics'] = 'Physical metrics must be an array';
			} else {
				foreach ( $data['physicalMetrics'] as $index => $metric ) {
					if ( ! isset( $metric['type'], $metric['value'], $metric['unit'], $metric['date'] ) ) {
						$errors[ 'physicalMetrics_' . $index ] = 'Each metric must have type, value, unit, and date';
					} elseif ( ! is_numeric( $metric['value'] ) || $metric['value'] < 0 ) {
						$errors[ 'physicalMetrics_' . $index . '_value' ] = 'Metric value must be a positive number';
					}
				}
			}
		}

		// Return validation errors if any found
		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'validation_failed',
				'Profile data validation failed',
				array(
					'status' => 400,
					'errors' => $errors,
				)
			);
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

		if ( ! $user_id ) {
			return new WP_Error(
				'no_user',
				'User not found',
				array( 'status' => 404 )
			);
		}

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

			$meta      = get_user_meta( $user_id );
			$user_data = array(
				'id'          => $user_id,
				'name'        => $user->display_name,
				'username'    => $user->user_login,
				'email'       => $user->user_email,
				'roles'       => $user->roles,
				'firstName'   => $meta['first_name'][0] ?? '',
				'lastName'    => $meta['last_name'][0] ?? '',
				'displayName' => $user->display_name,
			);

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'profile' => $user_data,
					),
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
			$updated_user = get_userdata( $user_id );
			$meta         = get_user_meta( $user_id );
			$response     = array(
				'id'          => $user_id,
				'name'        => $updated_user->display_name,
				'username'    => $updated_user->user_login,
				'email'       => $updated_user->user_email,
				'roles'       => $updated_user->roles,
				'firstName'   => $meta['first_name'][0] ?? '',
				'lastName'    => $meta['last_name'][0] ?? '',
				'displayName' => $updated_user->display_name,
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "User data updated successfully for ID: $user_id" );
				error_log( '=== End User Data Update ===' );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'profile' => $response,
					),
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
	 * Get basic profile data.
	 *
	 * Returns the basic profile data that matches the legacy endpoint format
	 * for backward compatibility.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or error object on failure.
	 */
	public static function get_basic_data() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new WP_Error(
				'no_user',
				'User not found',
				array( 'status' => 404 )
			);
		}

		$user = get_userdata( $user_id );
		$meta = get_user_meta( $user_id );

		// Match the legacy endpoint response format exactly
		$response = array(
			'id'          => $user_id,
			'name'        => $user->display_name,
			'username'    => $user->user_login,
			'email'       => $user->user_email,
			'roles'       => $user->roles,
			'firstName'   => $meta['first_name'][0] ?? '',
			'lastName'    => $meta['last_name'][0] ?? '',
			'displayName' => $user->display_name,
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'profile' => $response,
				),
			)
		);
	}

	/**
	 * Get combined profile and user data.
	 *
	 * Returns both the basic user data and any additional profile data
	 * stored in user meta.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or error object on failure.
	 */
	public static function get_combined_data() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new WP_Error(
				'no_user',
				'User not found',
				array( 'status' => 404 )
			);
		}

		try {
			// Get basic user data
			$user = get_userdata( $user_id );
			$meta = get_user_meta( $user_id );

			// Get additional profile data
			$profile_data = self::get_profile_data( $user_id );

			// Combine the data, ensuring basic user data takes precedence
			$response = array_merge(
				$profile_data,
				array(
					'id'          => $user_id,
					'name'        => $user->display_name,
					'username'    => $user->user_login,
					'email'       => $user->user_email,
					'roles'       => $user->roles,
					'firstName'   => $meta['first_name'][0] ?? '',
					'lastName'    => $meta['last_name'][0] ?? '',
					'displayName' => $user->display_name,
				)
			);

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'profile' => $response,
					),
				)
			);
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error getting combined profile data: ' . $e->getMessage() );
			}
			return new WP_Error(
				'profile_fetch_error',
				'Failed to fetch profile data',
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Log debug message if WP_DEBUG is enabled.
	 *
	 * @param string $message Debug message to log.
	 * @return void
	 */
	private static function log_debug( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $message );
		}
	}

	/**
	 * Add debug logging action if WP_DEBUG is enabled.
	 *
	 * @param string $hook Action hook name.
	 * @param string $message Debug message to log.
	 * @return void
	 */
	private static function log_debug_action( $hook, $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_action(
				$hook,
				function () use ( $message ) {
					error_log( $message );
				}
			);
		}
	}
}

// Initialize endpoints
ProfileEndpoints::init();

// Debug log when this file is loaded
error_log( 'Profile endpoints file loaded' );
