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
	 */
	public function get_profile( int $user_id ): array|WP_Error {
		try {
			if ( ! $this->profile_exists( $user_id ) ) {
				throw new Profile_Service_Exception(
					sprintf( 'Profile not found for user %d', $user_id ),
					Profile_Service_Exception::ERROR_NOT_FOUND
				);
			}

			return $this->repository->get_profile( $user_id );
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
	 * @return true|WP_Error True if valid, error on failure.
	 */
	public function validate_profile( array $data ): true|WP_Error {
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

			return $this->repository->get_profile_meta( $user_id, $key, $single );
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
}
