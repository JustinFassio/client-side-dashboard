<?php
/**
 * Profile Updated Event Listener.
 *
 * @package AthleteDashboard\Features\User\Events\Listeners
 */

namespace AthleteDashboard\Features\User\Events\Listeners;

use AthleteDashboard\Features\User\Services\User_Service;
use AthleteDashboard\Features\Profile\Events\Profile_Updated;

/**
 * Listener for Profile Updated events.
 */
class Profile_Updated_Listener {
	/**
	 * Constructor.
	 *
	 * @param User_Service $user_service User service instance.
	 */
	public function __construct(
		private User_Service $user_service
	) {}

	/**
	 * Handle the event.
	 *
	 * @param Profile_Updated $event The event instance.
	 */
	public function handle( Profile_Updated $event ): void {
		// Update relevant user data when profile changes
		$user_data = array(
			'display_name' => $event->profile_data['display_name'] ?? null,
			'user_email'   => $event->profile_data['email'] ?? null,
		);

		// Only update fields that are present in the profile data
		$user_data = array_filter( $user_data, fn( $value ) => ! is_null( $value ) );

		if ( ! empty( $user_data ) ) {
			$this->user_service->update_user( $event->user_id, $user_data );
		}
	}
}
