<?php
/**
 * Profile service interface.
 *
 * @package AthleteDashboard\Features\Profile\Services
 */

namespace AthleteDashboard\Features\Profile\Services;

use WP_Error;

/**
 * Interface for profile service.
 */
interface Profile_Service_Interface {
	/**
	 * Get a user's profile data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Profile data or error on failure.
	 */
	public function get_profile( int $user_id ): array|WP_Error;

	/**
	 * Update a user's profile data.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Profile data to update.
	 * @return array|WP_Error Updated profile data or error on failure.
	 */
	public function update_profile( int $user_id, array $data ): array|WP_Error;

	/**
	 * Delete a user's profile data.
	 *
	 * @param int $user_id User ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete_profile( int $user_id ): bool|WP_Error;

	/**
	 * Validate profile data.
	 *
	 * @param array $data Profile data to validate.
	 * @return bool|WP_Error True if valid, error on failure.
	 */
	public function validate_profile( array $data ): bool|WP_Error;

	/**
	 * Check if a profile exists.
	 *
	 * @param int $user_id User ID.
	 * @return bool Whether the profile exists.
	 */
	public function profile_exists( int $user_id ): bool;

	/**
	 * Get profile metadata.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Metadata key.
	 * @param bool   $single  Whether to return a single value.
	 * @return mixed Metadata value(s).
	 */
	public function get_profile_meta( int $user_id, string $key, bool $single = true ): mixed;

	/**
	 * Update profile metadata.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Metadata key.
	 * @param mixed  $value   Metadata value.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function update_profile_meta( int $user_id, string $key, mixed $value ): bool|WP_Error;

	/**
	 * Get user data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error User data or error on failure.
	 */
	public function get_user_data( int $user_id ): array|WP_Error;

	/**
	 * Update user data.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    User data to update.
	 * @return array|WP_Error Updated user data or error on failure.
	 */
	public function update_user_data( int $user_id, array $data ): array|WP_Error;

	/**
	 * Get combined profile and user data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Combined data or error on failure.
	 */
	public function get_combined_data( int $user_id ): array|WP_Error;

	/**
	 * Get basic profile data.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Basic profile data or error on failure.
	 */
	public function get_basic_data( int $user_id ): array|WP_Error;
}
