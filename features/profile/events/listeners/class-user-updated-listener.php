<?php
/**
 * User Updated Event Listener.
 *
 * @package AthleteDashboard\Features\Profile\Events\Listeners
 */

namespace AthleteDashboard\Features\Profile\Events\Listeners;

use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\User\Events\User_Updated;

/**
 * Listener for User Updated events.
 */
class User_Updated_Listener {
	/**
	 * Constructor.
	 *
	 * @param Profile_Service $profile_service Profile service instance.
	 */
	public function __construct(
		private Profile_Service $profile_service
	) {}

	/**
	 * Handle the event.
	 *
	 * @param User_Updated $event The event instance.
	 */
	public function handle( User_Updated $event ): void {
		// Update relevant profile data when user data changes
		$profile_data = $this->profile_service->get_profile( $event->user_id );
		if ( ! is_wp_error( $profile_data ) ) {
			$updated_data = array_merge(
				$profile_data,
				array(
					'email'        => $event->user_data['email'] ?? $profile_data['email'],
					'display_name' => $event->user_data['display_name'] ?? $profile_data['display_name'],
				// Add other relevant field mappings
				)
			);

			$this->profile_service->update_profile( $event->user_id, $updated_data );
		}
	}
}
