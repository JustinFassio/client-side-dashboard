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
	 * Get profile data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Profile data or error if not found.
	 */
	public function get_profile( int $user_id ) {
		$profile_data = get_user_meta( $user_id, 'athlete_profile', true );

		if ( empty( $profile_data ) ) {
			return new WP_Error(
				'profile_not_found',
				__( 'Profile not found for user.', 'athlete-dashboard' ),
				array( 'status' => 404 )
			);
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
		$result = update_user_meta( $user_id, 'athlete_profile', $data );

		if ( false === $result ) {
			return new WP_Error(
				'profile_update_failed',
				__( 'Failed to update profile.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		return $data;
	}

	/**
	 * Delete profile data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete_profile( int $user_id ): bool|WP_Error {
		$result = delete_user_meta( $user_id, 'athlete_profile' );

		if ( false === $result ) {
			return new WP_Error(
				'profile_delete_failed',
				__( 'Failed to delete profile.', 'athlete-dashboard' ),
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
