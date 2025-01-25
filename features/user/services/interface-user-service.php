<?php
/**
 * User service interface.
 *
 * @package AthleteDashboard\Features\User\Services
 */

namespace AthleteDashboard\Features\User\Services;

use WP_Error;

/**
 * Interface for user service operations.
 */
interface User_Service_Interface {
	/**
	 * Get basic user data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error User data or error on failure.
	 */
	public function get_basic_data( int $user_id ): array|WP_Error;

	/**
	 * Update user data.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    User data to update.
	 * @return array|WP_Error Updated user data or error on failure.
	 */
	public function update_user( int $user_id, array $data ): array|WP_Error;

	/**
	 * Update user meta.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function update_meta( int $user_id, string $key, mixed $value ): bool|WP_Error;

	/**
	 * Get user meta.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Whether to return a single value.
	 * @return mixed Meta value(s).
	 */
	public function get_meta( int $user_id, string $key, bool $single = true ): mixed;
}
