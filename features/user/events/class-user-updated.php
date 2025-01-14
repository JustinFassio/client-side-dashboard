<?php
/**
 * User Updated Event.
 *
 * @package AthleteDashboard\Features\User\Events
 */

namespace AthleteDashboard\Features\User\Events;

/**
 * Event dispatched when a user is updated.
 */
class User_Updated {
	/**
	 * Constructor.
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_data The updated user data.
	 */
	public function __construct(
		public readonly int $user_id,
		public readonly array $user_data
	) {}
}
