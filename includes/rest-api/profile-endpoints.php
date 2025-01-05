<?php

if (!defined('ABSPATH')) {
    exit;
}

class Athlete_Dashboard_Profile_Endpoints {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('athlete-dashboard/v1', '/profile/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_profile'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    )
                )
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_profile'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    )
                )
            )
        ));
    }

    public function check_permission($request) {
        $user_id = $request['id'];
        $current_user_id = get_current_user_id();

        // Allow if user is requesting their own profile or is an admin
        return $current_user_id === (int)$user_id || current_user_can('administrator');
    }

    public function get_profile($request) {
        $user_id = $request['id'];
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error(
                'profile_not_found',
                'Profile not found',
                array('status' => 404)
            );
        }

        // Get user meta data
        $profile_data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'displayName' => $user->display_name,
            'firstName' => get_user_meta($user_id, 'first_name', true),
            'lastName' => get_user_meta($user_id, 'last_name', true),
            'phone' => get_user_meta($user_id, 'phone', true),
            'age' => (int)get_user_meta($user_id, 'age', true),
            'dateOfBirth' => get_user_meta($user_id, 'date_of_birth', true),
            'height' => (float)get_user_meta($user_id, 'height', true),
            'weight' => (float)get_user_meta($user_id, 'weight', true),
            'gender' => get_user_meta($user_id, 'gender', true),
            'dominantSide' => get_user_meta($user_id, 'dominant_side', true),
            'medicalClearance' => (bool)get_user_meta($user_id, 'medical_clearance', true),
            'medicalNotes' => get_user_meta($user_id, 'medical_notes', true),
            'emergencyContactName' => get_user_meta($user_id, 'emergency_contact_name', true),
            'emergencyContactPhone' => get_user_meta($user_id, 'emergency_contact_phone', true),
            'injuries' => $this->get_user_injuries($user_id)
        );

        return rest_ensure_response($profile_data);
    }

    public function update_profile($request) {
        $user_id = $request['id'];
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error(
                'profile_not_found',
                'Profile not found',
                array('status' => 404)
            );
        }

        $params = $request->get_json_params();
        
        // Update user meta data
        $meta_fields = array(
            'phone', 'age', 'date_of_birth', 'height', 'weight',
            'gender', 'dominant_side', 'medical_clearance', 'medical_notes',
            'emergency_contact_name', 'emergency_contact_phone'
        );

        foreach ($meta_fields as $field) {
            if (isset($params[$field])) {
                update_user_meta($user_id, $field, $params[$field]);
            }
        }

        // Handle injuries separately if provided
        if (isset($params['injuries'])) {
            $this->update_user_injuries($user_id, $params['injuries']);
        }

        // Return updated profile
        return $this->get_profile($request);
    }

    private function get_user_injuries($user_id) {
        $injuries = get_user_meta($user_id, 'injuries', true);
        return is_array($injuries) ? $injuries : array();
    }

    private function update_user_injuries($user_id, $injuries) {
        if (!is_array($injuries)) {
            return;
        }

        update_user_meta($user_id, 'injuries', $injuries);
    }
}

// Initialize the endpoints
new Athlete_Dashboard_Profile_Endpoints(); 