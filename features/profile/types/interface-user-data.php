<?php
/**
 * User data interface.
 *
 * @package AthleteDashboard\Features\Profile\Types
 */

namespace AthleteDashboard\Features\Profile\Types;

/**
 * Interface for user data structure.
 */
interface UserData {
	/**
	 * Get the user ID.
	 *
	 * @return int
	 */
	public function get_id(): int;

	/**
	 * Get basic user data.
	 *
	 * @return array
	 */
	public function get_basic_data(): array;

	/**
	 * Get user meta data.
	 *
	 * @param string $key Meta key.
	 * @return mixed
	 */
	public function get_meta( string $key );

	/**
	 * Update user meta data.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool
	 */
	public function update_meta( string $key, $value ): bool;
}
