<?php
/**
 * User repository class.
 *
 * @package AthleteDashboard\Features\Profile\Data
 */

namespace AthleteDashboard\Features\Profile\Data;

use AthleteDashboard\Features\Profile\Types\UserData;

/**
 * Class for handling user data storage and retrieval.
 */
class User_Repository {
	/**
	 * Get basic user data.
	 *
	 * @param int $user_id User ID.
	 * @return array|false User data or false if not found.
	 */
	public function get_basic_data( int $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		return array(
			'id'        => $user->ID,
			'email'     => $user->user_email,
			'firstName' => $user->first_name,
			'lastName'  => $user->last_name,
		);
	}

	/**
	 * Get user meta value.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key Meta key.
	 * @return mixed Meta value or false if not found.
	 */
	public function get_meta( int $user_id, string $key ) {
		return get_user_meta( $user_id, $key, true );
	}

	/**
	 * Update user meta value.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	public function update_meta( int $user_id, string $key, $value ): bool {
		return update_user_meta( $user_id, $key, $value );
	}
}
