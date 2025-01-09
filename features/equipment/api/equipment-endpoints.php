<?php
namespace AthleteDashboard\Features\Equipment;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use AthleteDashboard\Core\DashboardBridge;

class EquipmentEndpoints {
    private const NAMESPACE = 'athlete-dashboard/v1';
    private const BASE = '/equipment';
    private static $instance = null;
    private static $routes_registered = false;

    private function __construct() {
        // Prevent direct instantiation
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_routes() {
        if (self::$routes_registered) {
            return;
        }

        add_action('rest_api_init', function() {
            if (self::$routes_registered) {
                return;
            }

            // Equipment endpoints
            register_rest_route(
                self::NAMESPACE,
                self::BASE . '/items',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_equipment'],
                    'permission_callback' => [$this, 'check_permission'],
                    'schema' => [$this, 'get_item_schema']
                ]
            );

            register_rest_route(
                self::NAMESPACE,
                self::BASE . '/items',
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'add_equipment'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args' => [
                        'name' => [
                            'required' => true,
                            'type' => 'string'
                        ],
                        'type' => [
                            'required' => true,
                            'type' => 'string',
                            'enum' => ['machine', 'free weights', 'bands', 'other']
                        ]
                    ]
                ]
            );

            register_rest_route(
                self::NAMESPACE,
                self::BASE . '/items/(?P<id>[a-zA-Z0-9-_]+)',
                [
                    'methods' => 'PUT',
                    'callback' => [$this, 'update_equipment'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args' => [
                        'id' => [
                            'required' => true,
                            'type' => 'string'
                        ]
                    ]
                ]
            );

            register_rest_route(
                self::NAMESPACE,
                self::BASE . '/items/(?P<id>[a-zA-Z0-9-_]+)',
                [
                    'methods' => 'DELETE',
                    'callback' => [$this, 'delete_equipment'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args' => [
                        'id' => [
                            'required' => true,
                            'type' => 'string'
                        ]
                    ]
                ]
            );

            // Equipment Sets endpoints
            register_rest_route(
                self::NAMESPACE,
                self::BASE . '/sets',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_equipment_sets'],
                    'permission_callback' => [$this, 'check_permission']
                ]
            );

            // Workout Zones endpoints
            register_rest_route(
                self::NAMESPACE,
                self::BASE . '/zones',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_workout_zones'],
                    'permission_callback' => [$this, 'check_permission']
                ]
            );
        });

        add_action('rest_pre_dispatch', [$this, 'handle_pre_dispatch'], 10, 3);
        self::$routes_registered = true;
    }

    public function handle_pre_dispatch($result, $server, $request) {
        if (strpos($request->get_route(), self::BASE) === false) {
            return $result;
        }

        // Add CORS headers for equipment endpoints
        header('Access-Control-Allow-Headers: X-WP-Nonce');
        return $result;
    }

    public function check_permission(WP_REST_Request $request): bool {
        // Add request validation
        if (!$request->get_header('X-WP-Nonce')) {
            return false;
        }
        
        // Check if request is already being processed
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $current_route = $request->get_route();
            if (strpos($current_route, self::BASE) === false) {
                return true; // Not our endpoint, allow through
            }
        }

        // Use a try-catch to prevent authentication errors from breaking other features
        try {
            return DashboardBridge::check_api_permission($request);
        } catch (Exception $e) {
            error_log('Equipment endpoint permission check failed: ' . $e->getMessage());
            return false;
        }
    }

    public function get_equipment(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $equipment = get_user_meta($user_id, 'equipment', true);

        if (!$equipment) {
            $equipment = [];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $equipment
        ], 200);
    }

    public function add_equipment(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $equipment = get_user_meta($user_id, 'equipment', true) ?: [];

        $new_equipment = [
            'id' => uniqid('equipment_'),
            'name' => $request->get_param('name'),
            'type' => $request->get_param('type'),
            'weightRange' => $request->get_param('weightRange'),
            'quantity' => $request->get_param('quantity'),
            'description' => $request->get_param('description')
        ];

        $equipment[] = $new_equipment;
        $updated = update_user_meta($user_id, 'equipment', $equipment);

        if (!$updated) {
            return new WP_REST_Response([
                'success' => false,
                'error' => [
                    'code' => 'equipment_add_error',
                    'message' => 'Failed to add equipment'
                ]
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $new_equipment
        ], 201);
    }

    public function update_equipment(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $equipment = get_user_meta($user_id, 'equipment', true) ?: [];
        $equipment_id = $request->get_param('id');

        $updated_equipment = array_map(function($item) use ($request, $equipment_id) {
            if ($item['id'] === $equipment_id) {
                return array_merge($item, [
                    'name' => $request->get_param('name') ?? $item['name'],
                    'type' => $request->get_param('type') ?? $item['type'],
                    'weightRange' => $request->get_param('weightRange') ?? $item['weightRange'],
                    'quantity' => $request->get_param('quantity') ?? $item['quantity'],
                    'description' => $request->get_param('description') ?? $item['description']
                ]);
            }
            return $item;
        }, $equipment);

        $updated = update_user_meta($user_id, 'equipment', $updated_equipment);

        if (!$updated) {
            return new WP_REST_Response([
                'success' => false,
                'error' => [
                    'code' => 'equipment_update_error',
                    'message' => 'Failed to update equipment'
                ]
            ], 500);
        }

        $updated_item = array_filter($updated_equipment, function($item) use ($equipment_id) {
            return $item['id'] === $equipment_id;
        });

        return new WP_REST_Response([
            'success' => true,
            'data' => reset($updated_item)
        ], 200);
    }

    public function delete_equipment(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $equipment = get_user_meta($user_id, 'equipment', true) ?: [];
        $equipment_id = $request->get_param('id');

        $filtered_equipment = array_filter($equipment, function($item) use ($equipment_id) {
            return $item['id'] !== $equipment_id;
        });

        $updated = update_user_meta($user_id, 'equipment', $filtered_equipment);

        if (!$updated) {
            return new WP_REST_Response([
                'success' => false,
                'error' => [
                    'code' => 'equipment_delete_error',
                    'message' => 'Failed to delete equipment'
                ]
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true
        ], 200);
    }

    public function get_equipment_sets(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $equipment_sets = get_user_meta($user_id, 'equipment_sets', true);

        if (!$equipment_sets) {
            $equipment_sets = [];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $equipment_sets
        ], 200);
    }

    public function get_workout_zones(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $workout_zones = get_user_meta($user_id, 'workout_zones', true);

        if (!$workout_zones) {
            $workout_zones = [];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $workout_zones
        ], 200);
    }
} 