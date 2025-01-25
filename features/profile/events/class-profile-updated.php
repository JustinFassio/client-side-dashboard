<?php
/**
 * Profile Updated Event.
 *
 * @package AthleteDashboard\Features\Profile\Events
 */

namespace AthleteDashboard\Features\Profile\Events;

/**
 * Event dispatched when a profile is updated.
 */
class Profile_Updated {
	/**
	 * Constructor.
	 *
	 * @param int   $user_id      The user ID.
	 * @param array $profile_data The updated profile data.
	 */
	public function __construct(
		public readonly int $user_id,
		public readonly array $profile_data
	) {}
}
