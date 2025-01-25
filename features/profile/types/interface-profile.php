<?php
/**
 * Profile data interface.
 *
 * @package AthleteDashboard\Features\Profile\Types
 */

namespace AthleteDashboard\Features\Profile\Types;

/**
 * Interface for profile data structure.
 */
interface Profile {
	/**
	 * Get the user ID associated with this profile.
	 *
	 * @return int
	 */
	public function get_user_id(): int;

	/**
	 * Get all profile data.
	 *
	 * @return array
	 */
	public function get_data(): array;

	/**
	 * Set profile data.
	 *
	 * @param array $data Profile data to set.
	 * @return bool
	 */
	public function set_data( array $data ): bool;
}
