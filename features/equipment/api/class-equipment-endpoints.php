<?php
/**
 * Equipment API endpoints for the Athlete Dashboard.
 *
 * This file contains the REST API endpoints for managing equipment,
 * including equipment items, sets, and workout zones.
 *
 * @package AthleteDashboard\Features\Equipment\API
 */

namespace AthleteDashboard\Features\Equipment\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AthleteDashboard\Core\DashboardBridge;

/**
 * Class EquipmentEndpoints
 *
 * Handles all REST API endpoints related to equipment management.
 * Provides functionality for managing equipment items, sets, and workout zones.
 *
 * @package AthleteDashboard\Features\Equipment\API
 */
class EquipmentEndpoints {
	/**
	 * API namespace for all endpoints.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'athlete-dashboard/v1';

	/**
	 * Base route for equipment endpoints.
	 *
	 * @var string
	 */
	private const BASE = '/equipment';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Flag to track if routes have been registered.
	 *
	 * @var bool
	 */
	private static $routes_registered = false;

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// Prevent direct instantiation.
	}

	/**
	 * Get singleton instance.
	 *
	 * @return self The singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all equipment-related REST API endpoints.
	 *
	 * Registers endpoints for:
	 * - Equipment items (GET, POST, PUT, DELETE)
	 * - Equipment sets (GET)
	 * - Workout zones (GET)
	 *
	 * @return void
	 */
	public function register_routes() {
		if ( self::$routes_registered ) {
			return;
		}

		add_action(
			'rest_api_init',
			function () {
				if ( self::$routes_registered ) {
					return;
				}

				// Register equipment item endpoints.
				register_rest_route(
					self::NAMESPACE,
					self::BASE . '/items',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_equipment' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'schema'              => array( $this, 'get_item_schema' ),
					)
				);

				register_rest_route(
					self::NAMESPACE,
					self::BASE . '/items',
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'add_equipment' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'name' => array(
								'required' => true,
								'type'     => 'string',
							),
							'type' => array(
								'required' => true,
								'type'     => 'string',
								'enum'     => array( 'machine', 'free weights', 'bands', 'other' ),
							),
						),
					)
				);

				register_rest_route(
					self::NAMESPACE,
					self::BASE . '/items/(?P<id>[a-zA-Z0-9-_]+)',
					array(
						'methods'             => 'PUT',
						'callback'            => array( $this, 'update_equipment' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'id' => array(
								'required' => true,
								'type'     => 'string',
							),
						),
					)
				);

				register_rest_route(
					self::NAMESPACE,
					self::BASE . '/items/(?P<id>[a-zA-Z0-9-_]+)',
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'delete_equipment' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'id' => array(
								'required' => true,
								'type'     => 'string',
							),
						),
					)
				);

				// Register equipment sets endpoint.
				register_rest_route(
					self::NAMESPACE,
					self::BASE . '/sets',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_equipment_sets' ),
						'permission_callback' => array( $this, 'check_permission' ),
					)
				);

				// Register workout zones endpoint.
				register_rest_route(
					self::NAMESPACE,
					self::BASE . '/zones',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_workout_zones' ),
						'permission_callback' => array( $this, 'check_permission' ),
					)
				);
			}
		);

		add_action( 'rest_pre_dispatch', array( $this, 'handle_pre_dispatch' ), 10, 3 );
		self::$routes_registered = true;
	}

	/**
	 * Handle pre-dispatch actions for equipment endpoints.
	 *
	 * Adds CORS headers for equipment-related requests.
	 *
	 * @param mixed           $result  Response to replace the requested version with.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed The original or modified response.
	 */
	public function handle_pre_dispatch( $result, $server, $request ) {
		if ( false === strpos( $request->get_route(), self::BASE ) ) {
			return $result;
		}

		// Add CORS headers for equipment endpoints.
		header( 'Access-Control-Allow-Headers: X-WP-Nonce' );
		return $result;
	}

	/**
	 * Check if the current user has permission to access equipment endpoints.
	 *
	 * Validates the request nonce and user permissions.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool True if user has permission, false otherwise.
	 */
	public function check_permission( WP_REST_Request $request ): bool {
		// Add request validation.
		if ( ! $request->get_header( 'X-WP-Nonce' ) ) {
			return false;
		}

		// Check if request is already being processed.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$current_route = $request->get_route();
			if ( false === strpos( $current_route, self::BASE ) ) {
				return true; // Not our endpoint, allow through.
			}
		}

		// Use a try-catch to prevent authentication errors from breaking other features.
		try {
			return DashboardBridge::check_api_permission( $request );
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Equipment endpoint permission check failed: ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Get all equipment items for the current user.
	 *
	 * @return WP_REST_Response Response containing the user's equipment items.
	 */
	public function get_equipment(): WP_REST_Response {
		$user_id   = get_current_user_id();
		$equipment = get_user_meta( $user_id, 'equipment', true );

		if ( false === $equipment ) {
			$equipment = array();
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $equipment,
			),
			200
		);
	}

	/**
	 * Add a new equipment item for the current user.
	 *
	 * @param WP_REST_Request $request The request object containing equipment details.
	 * @return WP_REST_Response Response containing the newly created equipment item.
	 */
	public function add_equipment( WP_REST_Request $request ): WP_REST_Response {
		$user_id   = get_current_user_id();
		$equipment = get_user_meta( $user_id, 'equipment', true );
		if ( false === $equipment ) {
			$equipment = array();
		}

		$new_equipment = array(
			'id'          => uniqid( 'equipment_' ),
			'name'        => $request->get_param( 'name' ),
			'type'        => $request->get_param( 'type' ),
			'weightRange' => $request->get_param( 'weightRange' ),
			'quantity'    => $request->get_param( 'quantity' ),
			'description' => $request->get_param( 'description' ),
		);

		$equipment[] = $new_equipment;
		$updated     = update_user_meta( $user_id, 'equipment', $equipment );

		if ( ! $updated ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => array(
						'code'    => 'equipment_add_error',
						'message' => 'Failed to add equipment',
					),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $new_equipment,
			),
			201
		);
	}

	/**
	 * Update an existing equipment item.
	 *
	 * @param WP_REST_Request $request The request object containing updated equipment details.
	 * @return WP_REST_Response Response containing the updated equipment item.
	 */
	public function update_equipment( WP_REST_Request $request ): WP_REST_Response {
		$user_id   = get_current_user_id();
		$equipment = get_user_meta( $user_id, 'equipment', true );
		if ( false === $equipment ) {
			$equipment = array();
		}
		$equipment_id = $request->get_param( 'id' );

		$updated_equipment = array_map(
			function ( $item ) use ( $request, $equipment_id ) {
				if ( $item['id'] === $equipment_id ) {
					$name         = $request->get_param( 'name' );
					$type         = $request->get_param( 'type' );
					$weight_range = $request->get_param( 'weightRange' );
					$quantity     = $request->get_param( 'quantity' );
					$description  = $request->get_param( 'description' );

					return array_merge(
						$item,
						array(
							'name'        => null !== $name ? $name : $item['name'],
							'type'        => null !== $type ? $type : $item['type'],
							'weightRange' => null !== $weight_range ? $weight_range : $item['weightRange'],
							'quantity'    => null !== $quantity ? $quantity : $item['quantity'],
							'description' => null !== $description ? $description : $item['description'],
						)
					);
				}
				return $item;
			},
			$equipment
		);

		$updated = update_user_meta( $user_id, 'equipment', $updated_equipment );

		if ( ! $updated ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => array(
						'code'    => 'equipment_update_error',
						'message' => 'Failed to update equipment',
					),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $updated_equipment,
			),
			200
		);
	}

	/**
	 * Delete an equipment item.
	 *
	 * @param WP_REST_Request $request The request object containing the equipment ID to delete.
	 * @return WP_REST_Response Response indicating success or failure.
	 */
	public function delete_equipment( WP_REST_Request $request ): WP_REST_Response {
		$user_id   = get_current_user_id();
		$equipment = get_user_meta( $user_id, 'equipment', true );
		if ( false === $equipment ) {
			$equipment = array();
		}
		$equipment_id = $request->get_param( 'id' );

		$updated_equipment = array_filter(
			$equipment,
			function ( $item ) use ( $equipment_id ) {
				return $item['id'] !== $equipment_id;
			}
		);

		$updated = update_user_meta( $user_id, 'equipment', array_values( $updated_equipment ) );

		if ( ! $updated ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => array(
						'code'    => 'equipment_delete_error',
						'message' => 'Failed to delete equipment',
					),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id' => $equipment_id,
				),
			),
			200
		);
	}

	/**
	 * Get all equipment sets for the current user.
	 *
	 * @return WP_REST_Response Response containing the user's equipment sets.
	 */
	public function get_equipment_sets(): WP_REST_Response {
		$user_id = get_current_user_id();
		$sets    = get_user_meta( $user_id, 'equipment_sets', true );

		if ( false === $sets ) {
			$sets = array();
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $sets,
			),
			200
		);
	}

	/**
	 * Get all workout zones for the current user.
	 *
	 * @return WP_REST_Response Response containing the user's workout zones.
	 */
	public function get_workout_zones(): WP_REST_Response {
		$user_id = get_current_user_id();
		$zones   = get_user_meta( $user_id, 'workout_zones', true );

		if ( false === $zones ) {
			$zones = array();
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $zones,
			),
			200
		);
	}
}
