<?php
/**
 * User service class.
 *
 * @package AthleteDashboard\Features\User\Services
 */

namespace AthleteDashboard\Features\User\Services;

use AthleteDashboard\Core\Events;
use AthleteDashboard\Features\User\Events\User_Updated;
use AthleteDashboard\Features\User\Repository\User_Repository;
use AthleteDashboard\Features\User\Validation\User_Validator;
use WP_Error;

/**
 * Class for handling user operations.
 */
class User_Service implements User_Service_Interface {
	/**
	 * Constructor.
	 *
	 * @param User_Repository $repository User repository instance.
	 * @param User_Validator  $validator   User validator instance.
	 */
	public function __construct(
		private User_Repository $repository,
		private User_Validator $validator
	) {}

	/**
	 * Get basic user data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error User data or error on failure.
	 */
	public function get_basic_data( int $user_id ): array|WP_Error {
		try {
			$data = $this->repository->get_basic_data( $user_id );
			if ( ! $data ) {
				throw new User_Service_Exception(
					sprintf( 'User not found: %d', $user_id ),
					User_Service_Exception::ERROR_NOT_FOUND
				);
			}
			return $data;
		} catch ( User_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error( 'user_error', $e->getMessage() );
		}
	}

	/**
	 * Update user data.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    User data to update.
	 * @return array|WP_Error Updated user data or error on failure.
	 */
	public function update_user( int $user_id, array $data ): array|WP_Error {
		try {
			// Validate user data
			$validation = $this->validator->validate_data( $data );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			// Update user
			$result = $this->repository->update_user( $user_id, $data );
			if ( is_wp_error( $result ) ) {
				throw new User_Service_Exception(
					'Failed to update user',
					User_Service_Exception::ERROR_UPDATE_FAILED,
					array( 'user_id' => $user_id )
				);
			}

			// Dispatch event
			Events::dispatch( new User_Updated( $user_id, $result ) );

			return $result;
		} catch ( User_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error( 'user_error', $e->getMessage() );
		}
	}

	/**
	 * Update user meta.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function update_meta( int $user_id, string $key, mixed $value ): bool|WP_Error {
		try {
			// Validate meta value
			$validation = $this->validator->validate_meta( $key, $value );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			$result = $this->repository->update_meta( $user_id, $key, $value );
			if ( ! $result ) {
				throw new User_Service_Exception(
					'Failed to update user meta',
					User_Service_Exception::ERROR_META,
					array(
						'user_id' => $user_id,
						'key'     => $key,
					)
				);
			}

			return true;
		} catch ( User_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error( 'user_error', $e->getMessage() );
		}
	}

	/**
	 * Get user meta.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Whether to return a single value.
	 * @return mixed Meta value(s).
	 */
	public function get_meta( int $user_id, string $key, bool $single = true ): mixed {
		try {
			$value = $this->repository->get_meta( $user_id, $key, $single );
			if ( $value === false ) {
				throw new User_Service_Exception(
					'Failed to get user meta',
					User_Service_Exception::ERROR_META,
					array(
						'user_id' => $user_id,
						'key'     => $key,
					)
				);
			}
			return $value;
		} catch ( User_Service_Exception $e ) {
			return $e->to_wp_error();
		} catch ( \Exception $e ) {
			return new WP_Error( 'user_error', $e->getMessage() );
		}
	}
}
