<?php
namespace AthleteDashboard\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Profile_Endpoint extends WP_REST_Controller {
    public function register_routes() {
        register_rest_route('dashboard/v1', '/profile/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_user_profile'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_user_profile'],
                'permission_callback' => [$this, 'check_permission'],
            ]
        ]);
    }

    public function check_permission($request) {
        $user_id = $request['id'];
        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be logged in to access this endpoint.', 'athlete-dashboard'),
                ['status' => 401]
            );
        }

        if ($current_user_id !== (int) $user_id) {
            return new WP_Error(
                'rest_forbidden',
                __('You can only access your own profile.', 'athlete-dashboard'),
                ['status' => 403]
            );
        }

        return true;
    }

    public function get_user_profile($request) {
        $user_id = $request['id'];
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found', 'athlete-dashboard'),
                ['status' => 404]
            );
        }

        return new WP_REST_Response([
            'id' => $user->ID,
            'displayName' => $user->display_name,
            'email' => $user->user_email,
            'age' => get_user_meta($user_id, 'age', true),
            'gender' => get_user_meta($user_id, 'gender', true),
            'height' => get_user_meta($user_id, 'height', true),
            'weight' => get_user_meta($user_id, 'weight', true),
            'fitnessLevel' => get_user_meta($user_id, 'fitness_level', true),
            'activityLevel' => get_user_meta($user_id, 'activity_level', true),
            'medicalConditions' => get_user_meta($user_id, 'medical_conditions', true) ?: [],
            'exerciseLimitations' => get_user_meta($user_id, 'exercise_limitations', true) ?: [],
            'medications' => get_user_meta($user_id, 'medications', true),
            'injuries' => get_user_meta($user_id, 'injuries', true) ?: [],
        ], 200);
    }

    public function update_user_profile($request) {
        $user_id = $request['id'];
        $params = $request->get_json_params();

        $updatable_fields = [
            'age', 'gender', 'height', 'weight', 'fitnessLevel',
            'activityLevel', 'medicalConditions', 'exerciseLimitations',
            'medications', 'injuries'
        ];

        foreach ($updatable_fields as $field) {
            if (isset($params[$field])) {
                update_user_meta($user_id, $field, $params[$field]);
            }
        }

        return $this->get_user_profile($request);
    }
} 