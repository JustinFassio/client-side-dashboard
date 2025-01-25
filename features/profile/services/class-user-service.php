<?php
/**
 * User service class.
 *
 * @package AthleteDashboard\Features\Profile\Services
 */

namespace AthleteDashboard\Features\Profile\Services;

use AthleteDashboard\Features\Profile\Data\User_Repository;
use AthleteDashboard\Features\Profile\Validation\User_Validator;
use WP_Error;

/**
 * Class for handling user operations.
 */
class User_Service {
	/**
	 * User repository instance.
	 *
	 * @var User_Repository
	 */
	private $repository;

	/**
	 * User validator instance.
	 *
	 * @var User_Validator
	 */
	private $validator;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new User_Repository();
		$this->validator  = new User_Validator();
	}

	/**
	 * Get basic user data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error User data or error.
	 */
	public function get_basic_data( int $user_id ) {
		$data = $this->repository->get_basic_data( $user_id );
		if ( ! $data ) {
			return new WP_Error( 'user_not_found', 'User not found.' );
		}

		return $data;
	}

	/**
	 * Update user meta.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return true|WP_Error True if successful, error otherwise.
	 */
	public function update_meta( int $user_id, string $key, $value ) {
		// Validate meta value.
		$validation = $this->validator->validate_meta( $key, $value );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$result = $this->repository->update_meta( $user_id, $key, $value );
		if ( ! $result ) {
			return new WP_Error( 'meta_update_failed', 'Failed to update user meta.' );
		}

		return true;
	}
}
