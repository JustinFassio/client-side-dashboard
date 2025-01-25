<?php
/**
 * Profile repository class.
 *
 * @package AthleteDashboard\Features\Profile\Repository
 */

namespace AthleteDashboard\Features\Profile\Repository;

use WP_Error;

/**
 * Class Profile_Repository
 *
 * Handles data persistence for profiles.
 */
class Profile_Repository {
	/**
	 * Meta key for storing profile data.
	 *
	 * @var string
	 */
	private const PROFILE_META_KEY = '_athlete_profile_data';

	/**
	 * Get profile data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Profile data or error if not found.
	 */
	public function get_profile( int $user_id ): array|WP_Error {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Repository: Fetching profile for user %d', $user_id ) );
		}

		// Check if user exists first
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found.', 'athlete-dashboard' ),
				array( 'status' => 404 )
			);
		}

		// Get stored profile data
		$profile_data = get_user_meta( $user_id, self::PROFILE_META_KEY, true );

		// If no profile data exists, create initial structure from user data
		if ( empty( $profile_data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Profile Repository: Creating initial profile structure for user %d', $user_id ) );
			}

			$profile_data = array(
				'id'                    => $user_id,
				'email'                 => $user->user_email,
				'username'              => $user->user_login,
				'firstName'             => $user->first_name,
				'lastName'              => $user->last_name,
				'displayName'           => $user->display_name,
				'roles'                 => $user->roles,
				// Initialize empty sections that can be filled out
				'fitnessGoals'          => array(),
				'preferredWorkoutTypes' => array(),
				'equipment'             => array(),
				'injuries'              => array(),
				'medicalNotes'          => '',
				'emergencyContactName'  => '',
				'emergencyContactPhone' => '',
				'height'                => null,
				'weight'                => null,
				'age'                   => null,
				'gender'                => '',
			);

			// Store this initial profile data
			update_user_meta( $user_id, self::PROFILE_META_KEY, $profile_data );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Profile Repository: Initial profile structure created: %s', print_r( $profile_data, true ) ) );
			}
		}

		return $profile_data;
	}

	/**
	 * Update profile data for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Profile data to update.
	 * @return array|WP_Error Updated profile data or error.
	 */
	public function update_profile( int $user_id, array $data ): array|WP_Error {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Repository: Updating profile for user %d', $user_id ) );
			error_log( sprintf( 'Profile Repository: Update data: %s', print_r( $data, true ) ) );
		}

		// Check if user exists
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found.', 'athlete-dashboard' ),
				array( 'status' => 404 )
			);
		}

		// Get existing profile data
		$existing_data = get_user_meta( $user_id, self::PROFILE_META_KEY, true );
		if ( ! is_array( $existing_data ) ) {
			$existing_data = array();
		}

		// Merge new data with existing data
		$updated_data = $this->merge_profile_data( $existing_data, $data );

		// Update the profile data
		$result = update_user_meta( $user_id, self::PROFILE_META_KEY, $updated_data );

		if ( false === $result ) {
			return new WP_Error(
				'profile_update_failed',
				__( 'Failed to update profile data.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Repository: Profile updated successfully for user %d', $user_id ) );
		}

		return $updated_data;
	}

	/**
	 * Merge profile data, handling nested arrays correctly.
	 *
	 * @param array $existing_data Existing profile data.
	 * @param array $new_data New profile data.
	 * @return array Merged profile data.
	 */
	private function merge_profile_data( array $existing_data, array $new_data ): array {
		$merged_data = $existing_data;

		foreach ( $new_data as $key => $value ) {
			// If the value is an array and the existing data has the same key as an array
			if ( is_array( $value ) && isset( $existing_data[ $key ] ) && is_array( $existing_data[ $key ] ) ) {
				// For arrays that represent lists (equipment, fitnessGoals), replace entirely
				if ( isset( $value[0] ) || isset( $existing_data[ $key ][0] ) ) {
					$merged_data[ $key ] = $value;
				} else {
					// For associative arrays, merge recursively
					$merged_data[ $key ] = $this->merge_profile_data( $existing_data[ $key ], $value );
				}
			} else {
				// For non-array values or when the key doesn't exist in existing data
				$merged_data[ $key ] = $value;
			}
		}

		return $merged_data;
	}

	/**
	 * Delete profile data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete_profile( int $user_id ): bool|WP_Error {
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found.', 'athlete-dashboard' ),
				array( 'status' => 404 )
			);
		}

		$result = delete_user_meta( $user_id, self::PROFILE_META_KEY );

		if ( ! $result ) {
			return new WP_Error(
				'profile_delete_failed',
				__( 'Failed to delete profile data.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Check if a profile exists for a user.
	 *
	 * @param int $user_id User ID.
	 * @return bool Whether the profile exists.
	 */
	public function profile_exists( int $user_id ): bool {
		$profile_data = get_user_meta( $user_id, self::PROFILE_META_KEY, true );
		return ! empty( $profile_data );
	}
}
