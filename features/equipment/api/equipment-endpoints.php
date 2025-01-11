/**
 * Equipment management REST API endpoints.
 *
 * This file contains the REST API endpoints for managing equipment items,
 * including adding, updating, deleting, and retrieving equipment data.
 *
 * @package AthleteDashboard
 * @subpackage Equipment
 */

namespace AthleteDashboard\Features\Equipment\Api;

use AthleteDashboard\Core\DashboardBridge;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class Equipment_Endpoints
 *
 * Handles REST API endpoints for equipment management functionality.
 *
 * @package AthleteDashboard
 * @subpackage Equipment
 */
class Equipment_Endpoints {
	/**
	 * Initialize the endpoints.
	 *
	 * @return void
	 */
	public function init(): void {
		if (WP_DEBUG) {
			error_log('Initializing Equipment Endpoints.');
		}
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	/**
	 * Register the REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		if (WP_DEBUG) {
			error_log('Registering Equipment API routes.');
		}
		// ... existing code ...
	}

	/**
	 * Get equipment items for the current user.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 */
	public function get_equipment(WP_REST_Request $request) {
		if (WP_DEBUG) {
			error_log('Processing get_equipment request.');
		}
		// ... existing code ...
	}

	/**
	 * Add a new equipment item.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 */
	public function add_equipment(WP_REST_Request $request) {
		if (WP_DEBUG) {
			error_log('Processing add_equipment request.');
		}
		// ... existing code ...
	}

	/**
	 * Update an existing equipment item.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 */
	public function update_equipment(WP_REST_Request $request) {
		if (WP_DEBUG) {
			error_log('Processing update_equipment request.');
		}
		// ... existing code ...
	}

	/**
	 * Delete an equipment item.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 */
	public function delete_equipment(WP_REST_Request $request) {
		if (WP_DEBUG) {
			error_log('Processing delete_equipment request.');
		}
		// ... existing code ...
	}

	/**
	 * Check if the current user has permission for the requested operation.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if the request has permission, WP_Error object otherwise.
	 */
	private function check_permission(WP_REST_Request $request): bool|WP_Error {
		if (WP_DEBUG) {
			error_log('Checking equipment API permissions.');
		}
		// ... existing code ...
	}
}
