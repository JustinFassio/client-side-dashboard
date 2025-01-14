<?php
/**
 * Profile repository class.
 *
 * @package AthleteDashboard\Features\Profile\Data
 */

namespace AthleteDashboard\Features\Profile\Data;

use AthleteDashboard\Features\Profile\Types\Profile;

/**
 * Class for handling profile data storage and retrieval.
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
	 * @return array|false Profile data or false if not found.
	 */
	public function get( int $user_id ) {
		return get_user_meta( $user_id, self::PROFILE_META_KEY, true );
	}

	/**
	 * Save profile data for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data Profile data to save.
	 * @return bool
	 */
	public function save( int $user_id, array $data ): bool {
		return update_user_meta( $user_id, self::PROFILE_META_KEY, $data );
	}
}
