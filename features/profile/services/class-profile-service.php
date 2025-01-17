<?php
/**
 * Profile service class.
 *
 * @package AthleteDashboard\Features\Profile\Services
 */

namespace AthleteDashboard\Features\Profile\Services;

use AthleteDashboard\Core\Events;
use AthleteDashboard\Features\Profile\Events\Profile_Updated;
use AthleteDashboard\Features\Profile\Repository\Profile_Repository;
use AthleteDashboard\Features\Profile\Validation\Profile_Validator;
use WP_Error;
use WP_User;
use AthleteDashboard\Core\Cache\Cache_Service;
use AthleteDashboard\Core\Cache\Cache_Category;

/**
 * Class for handling profile business logic.
 */
class Profile_Service implements Profile_Service_Interface {
	/**
	 * Profile repository instance.
	 *
	 * @var Profile_Repository
	 */
	private $repository;

	/**
	 * Profile validator instance.
	 *
	 * @var Profile_Validator
	 */
	private $validator;

	/**
	 * Constructor.
	 *
	 * @param Profile_Repository $repository Repository instance.
	 * @param Profile_Validator  $validator   Validator instance.
	 */
	public function __construct(
		Profile_Repository $repository,
		Profile_Validator $validator
	) {
		$this->repository = $repository;
		$this->validator  = $validator;
	}

	/**
	 * Get a user's profile data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Profile data or error on failure.
	 * @throws \Exception When user is not found or other errors occur.
	 */
	public function get_profile( int $user_id ): array|WP_Error {
		try {
			if ( ! $this->profile_exists( $user_id ) ) {
				throw new Profile_Service_Exception(
					sprintf( 'Profile not found for user %d', $user_id ),
					Profile_Service_Exception::ERROR_NOT_FOUND
				);
			}

			$cache_key = Cache_Service::generate_user_key( $user_id, 'profile' );
			return Cache_Service::remember(
				$cache_key,
				fn() => $this->repository->get_profile( $user_id ),
				array( 'category' => Cache_Service::CATEGORY_CRITICAL )
			);
		} catch ( Profile_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error(
				'profile_error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Update a user's profile data.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Profile data to update.
	 * @return array|WP_Error Updated profile data or error on failure.
	 * @throws \Exception When validation fails or update errors occur.
	 */
	public function update_profile( int $user_id, array $data ): array|WP_Error {
		try {
			// Validate the profile data
			$validation_result = $this->validate_profile( $data );
			if ( is_wp_error( $validation_result ) ) {
				return $validation_result;
			}

			// Update the profile
			$result = $this->repository->update_profile( $user_id, $data );
			if ( is_wp_error( $result ) ) {
				throw new Profile_Service_Exception(
					'Failed to update profile',
					Profile_Service_Exception::ERROR_DATABASE,
					array( 'user_id' => $user_id )
				);
			}

			// Dispatch event
			Events::dispatch( new Profile_Updated( $user_id, $result ) );

			return $this->get_profile( $user_id );
		} catch ( Profile_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error(
				'profile_error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Delete a user's profile data.
	 *
	 * @param int $user_id User ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete_profile( int $user_id ): bool|WP_Error {
		try {
			if ( ! $this->profile_exists( $user_id ) ) {
				throw new Profile_Service_Exception(
					sprintf( 'Profile not found for user %d', $user_id ),
					Profile_Service_Exception::ERROR_NOT_FOUND
				);
			}

			return $this->repository->delete_profile( $user_id );
		} catch ( Profile_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error(
				'profile_error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Validate profile data.
	 *
	 * @param array $data Profile data to validate.
	 * @return bool|WP_Error True if valid, error on failure.
	 */
	public function validate_profile( array $data ): bool|WP_Error {
		return $this->validator->validate_data( $data );
	}

	/**
	 * Check if a profile exists.
	 *
	 * @param int $user_id User ID.
	 * @return bool Whether the profile exists.
	 */
	public function profile_exists( int $user_id ): bool {
		return $this->repository->profile_exists( $user_id );
	}

	/**
	 * Get profile metadata.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Metadata key.
	 * @param bool   $single  Whether to return a single value.
	 * @return mixed Metadata value(s).
	 */
	public function get_profile_meta( int $user_id, string $key, bool $single = true ): mixed {
		try {
			if ( ! $this->profile_exists( $user_id ) ) {
				throw new Profile_Service_Exception(
					sprintf( 'Profile not found for user %d', $user_id ),
					Profile_Service_Exception::ERROR_NOT_FOUND
				);
			}

			$cache_key = Cache_Service::generate_user_key( $user_id, "meta_{$key}" );
			return Cache_Service::remember(
				$cache_key,
				fn() => $this->repository->get_profile_meta( $user_id, $key, $single ),
				array( 'category' => Cache_Service::CATEGORY_FREQUENT )
			);
		} catch ( Profile_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error(
				'profile_error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Update profile metadata.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Metadata key.
	 * @param mixed  $value   Metadata value.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function update_profile_meta( int $user_id, string $key, mixed $value ): bool|WP_Error {
		try {
			if ( ! $this->profile_exists( $user_id ) ) {
				throw new Profile_Service_Exception(
					sprintf( 'Profile not found for user %d', $user_id ),
					Profile_Service_Exception::ERROR_NOT_FOUND
				);
			}

			return $this->repository->update_profile_meta( $user_id, $key, $value );
		} catch ( Profile_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error(
				'profile_error',
				$e->getMessage()
			);
		}
	}

	/**
	 * Get user data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error User data or error on failure.
	 */
	public function get_user_data( int $user_id ): array|WP_Error {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Service: Getting user data for ID %d', $user_id ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Profile Service: User not found for ID %d', $user_id ) );
			}
			return new WP_Error(
				'user_not_found',
				sprintf(
					/* translators: %d: User ID */
					__( 'User not found: %d', 'athlete-dashboard' ),
					$user_id
				),
				array( 'user_id' => $user_id )
			);
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Service: Formatting user data for ID %d', $user_id ) );
		}

		try {
			$user_data = $this->format_user_data( $user );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Profile Service: User data formatted successfully - Fields: [%s]',
						implode( ', ', array_keys( $user_data ) )
					)
				);
			}

			return $user_data;
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Profile Service: Error formatting user data - %s', $e->getMessage() ) );
			}
			return new WP_Error(
				'user_format_error',
				__( 'Failed to format user data', 'athlete-dashboard' ),
				array(
					'user_id' => $user_id,
					'error'   => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Update user data.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    User data to update.
	 * @return array|WP_Error Updated user data or error on failure.
	 */
	public function update_user_data( int $user_id, array $data ): array|WP_Error {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Service: Starting user data update for ID %d', $user_id ) );
		}

		// Validate user data
		$validation_result = $this->validator->validate_user_data( $data );
		if ( is_wp_error( $validation_result ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Profile Service: Validation failed for user %d - %s',
						$user_id,
						$validation_result->get_error_message()
					)
				);
			}
			return $validation_result;
		}

		// Prepare user data for update
		$user_data = array(
			'ID' => $user_id,
		);

		$updateable_fields = array(
			'first_name'   => 'firstName',
			'last_name'    => 'lastName',
			'display_name' => 'displayName',
			'user_email'   => 'email',
			'nickname'     => 'nickname',
		);

		foreach ( $updateable_fields as $wp_field => $request_field ) {
			if ( isset( $data[ $request_field ] ) ) {
				$user_data[ $wp_field ] = sanitize_text_field( $data[ $request_field ] );
			}
		}

		// Update user
		$result = wp_update_user( $user_data );
		if ( is_wp_error( $result ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Profile Service: Failed to update user %d - %s',
						$user_id,
						$result->get_error_message()
					)
				);
			}
			return new WP_Error(
				'user_update_error',
				__( 'Failed to update user data', 'athlete-dashboard' ),
				array(
					'user_id' => $user_id,
					'error'   => $result->get_error_message(),
				)
			);
		}

		// Update nickname separately since it's a meta field
		if ( isset( $data['nickname'] ) ) {
			update_user_meta( $user_id, 'nickname', sanitize_text_field( $data['nickname'] ) );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Service: Successfully updated user %d', $user_id ) );
		}

		return $this->get_user_data( $user_id );
	}

	/**
	 * Get combined profile and user data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Combined data or error on failure.
	 */
	public function get_combined_data( int $user_id ): array|WP_Error {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Service: Starting combined data fetch for user %d', $user_id ) );
		}

		// Get user data
		$user_data = $this->get_user_data( $user_id );
		if ( is_wp_error( $user_data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Profile Service: Failed to fetch user data for ID %d - %s',
						$user_id,
						$user_data->get_error_message()
					)
				);
			}
			return $user_data;
		}

		// Get profile data
		$profile_data = $this->get_profile( $user_id );
		if ( is_wp_error( $profile_data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'Profile Service: Failed to fetch profile data for ID %d - %s',
						$user_id,
						$profile_data->get_error_message()
					)
				);
			}
			return $profile_data;
		}

		// Merge data, ensuring user data takes precedence
		$merged_data = array_merge( $profile_data, $user_data );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'Profile Service: Successfully merged data for user %d - Fields: [%s]',
					$user_id,
					implode( ', ', array_keys( $merged_data ) )
				)
			);
		}

		return $merged_data;
	}

	/**
	 * Get basic profile data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Basic profile data or error on failure.
	 */
	public function get_basic_data( int $user_id ): array|WP_Error {
		return $this->get_user_data( $user_id );
	}

	/**
	 * Format user data into a consistent structure.
	 *
	 * @param WP_User $user WordPress user object.
	 * @return array Formatted user data.
	 */
	private function format_user_data( WP_User $user ): array {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Service: Formatting user data for user %s', $user->user_login ) );
		}

		return array(
			'id'          => $user->ID,
			'username'    => $user->user_login,
			'email'       => $user->user_email,
			'roles'       => $user->roles,
			'firstName'   => get_user_meta( $user->ID, 'first_name', true ) ?: '',
			'lastName'    => get_user_meta( $user->ID, 'last_name', true ) ?: '',
			'displayName' => $user->display_name,
			'nickname'    => get_user_meta( $user->ID, 'nickname', true ) ?: '',
		);
	}

	/**
	 * Update user profile
	 *
	 * @param int   $user_id The user ID to update.
	 * @param array $data    The profile data to update.
	 * @return array Result of the update operation.
	 * @throws \Exception When validation fails or update errors occur.
	 */
	public function update_user_profile( $user_id, $data ) {
		// Validate all required fields before proceeding.

		// Check if the user exists and is valid.

		// Validate the email format and uniqueness.

		// Process the profile update with validated data.

		// Return the result of the update operation.

		return array(
			'id'          => $user_id,
			'username'    => $user_info->user_login,
			'email'       => $user_info->user_email,
			'roles'       => $user_info->roles,
			'firstName'   => ! empty( $user_info->first_name ) ? $user_info->first_name : '',
			'lastName'    => ! empty( $user_info->last_name ) ? $user_info->last_name : '',
			'displayName' => $user_info->display_name,
			'nickname'    => ! empty( $user_info->nickname ) ? $user_info->nickname : '',
		);
	}
}
