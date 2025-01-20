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
	private const PROFILE_META_KEY = 'athlete_profile';

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

		$profile_data = get_user_meta( $user_id, self::PROFILE_META_KEY, true );

		// Check if user exists
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found.', 'athlete-dashboard' ),
				array( 'status' => 404 )
			);
		}

		// If no profile data exists, return a structured error
		if ( empty( $profile_data ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'Profile Repository: No profile found for user %d', $user_id ) );
			}
			return new WP_Error(
				'profile_not_found',
				__( 'Profile not found for user.', 'athlete-dashboard' ),
				array(
					'status'   => 404,
					'user_id'  => $user_id,
					'meta_key' => self::PROFILE_META_KEY,
				)
			);
		}

		// Log profile data in debug mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Repository: Retrieved profile data: %s', print_r( $profile_data, true ) ) );
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
		$updated_data = array_merge( $existing_data, $data );

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
		$profile_data = get_user_meta( $user_id, 'athlete_profile', true );
		return ! empty( $profile_data );
	}
}
