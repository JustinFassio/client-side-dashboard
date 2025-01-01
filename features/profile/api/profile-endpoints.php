<?php
/**
 * Profile API endpoints
 */

namespace AthleteProfile\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class ProfileEndpoints {
    const NAMESPACE = 'athlete-dashboard/v1';
    const ROUTE = 'profile';
    const META_KEY = '_athlete_profile_data';

    /**
     * Initialize the endpoints
     */
    public static function init() {
        // Log initialization
        error_log('Initializing ProfileEndpoints...');
        
        // Register our endpoints when WordPress initializes the REST API
        add_action('rest_api_init', [self::class, 'register_routes']);
        
        // Debug hook to verify WordPress is loading our class
        add_action('init', function() {
            error_log('WordPress init: ProfileEndpoints loaded');
        });
    }

    /**
     * Register profile endpoints
     */
    public static function register_routes() {
        error_log('Registering profile endpoints...');
        error_log('Namespace: ' . self::NAMESPACE);
        error_log('Route: ' . self::ROUTE);

        // Test endpoint for debugging
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/test',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => function() {
                    error_log('Test endpoint called');
                    $user_id = get_current_user_id();
                    $raw_meta = get_user_meta($user_id);
                    $profile_data = get_user_meta($user_id, self::META_KEY, true);
                    
                    return rest_ensure_response([
                        'status' => 'ok',
                        'message' => 'Profile API is working',
                        'timestamp' => current_time('mysql'),
                        'debug' => [
                            'user_id' => $user_id,
                            'meta_key' => self::META_KEY,
                            'profile_data' => $profile_data,
                            'all_meta' => $raw_meta
                        ]
                    ]);
                },
                'permission_callback' => [self::class, 'check_auth']
            ]
        );

        // Main endpoints
        error_log('Registering main profile endpoints...');
        
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE,
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [self::class, 'get_profile'],
                    'permission_callback' => [self::class, 'check_auth'],
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [self::class, 'update_profile'],
                    'permission_callback' => [self::class, 'check_auth'],
                ]
            ]
        );

        error_log('Profile endpoints registered successfully');
    }

    /**
     * Check if user is authenticated
     */
    public static function check_auth() {
        if (!is_user_logged_in()) {
            error_log('Unauthorized profile API access attempt');
            return false;
        }
        return true;
    }

    /**
     * Get profile data
     */
    public static function get_profile() {
        $user_id = get_current_user_id();
        error_log("Fetching profile for user: $user_id");

        try {
            $profile_data = self::get_profile_data($user_id);
            error_log("Profile data retrieved successfully for user: $user_id");
            error_log("Profile data: " . json_encode($profile_data));
            
            // Ensure age is properly cast to integer
            if (isset($profile_data['age'])) {
                $profile_data['age'] = absint($profile_data['age']);
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'profile' => $profile_data
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching profile: " . $e->getMessage());
            return new WP_Error(
                'profile_fetch_error',
                'Failed to fetch profile data',
                ['status' => 500]
            );
        }
    }

    /**
     * Update profile data
     */
    public static function update_profile(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $data = $request->get_json_params();
        
        error_log("=== Profile Update Request ===");
        error_log("User ID: $user_id");
        error_log("Raw request data: " . print_r($request->get_body(), true));
        error_log("Parsed JSON data: " . print_r($data, true));

        if (empty($data)) {
            error_log("No profile data provided in request");
            return new WP_Error(
                'invalid_params',
                'No profile data provided',
                ['status' => 400]
            );
        }

        try {
            // Convert age to integer if provided
            if (isset($data['age'])) {
                $original_age = $data['age'];
                $data['age'] = absint($data['age']);
                error_log("Age conversion: $original_age -> {$data['age']}");
            }

            $validation = self::validate_profile_data($data);
            if (is_wp_error($validation)) {
                error_log("Profile validation failed: " . $validation->get_error_message());
                error_log("Validation errors: " . print_r($validation->get_error_data(), true));
                return $validation;
            }

            $current_data = self::get_profile_data($user_id);
            error_log("Current profile data: " . print_r($current_data, true));
            
            $updated_data = array_merge($current_data, $data);
            error_log("Merged profile data: " . print_r($updated_data, true));
            
            error_log("Saving profile data to meta key: " . self::META_KEY);
            $update_success = update_user_meta($user_id, self::META_KEY, $updated_data);
            
            if ($update_success === false) {
                error_log("Failed to update profile data in user meta");
                return new WP_Error(
                    'update_failed',
                    'Failed to update profile',
                    ['status' => 500]
                );
            }

            error_log("Profile updated successfully for user: $user_id");
            error_log("=== End Profile Update ===");
            
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'profile' => $updated_data
                ],
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            error_log("Error updating profile: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return new WP_Error(
                'profile_update_error',
                'Failed to update profile',
                ['status' => 500]
            );
        }
    }

    /**
     * Get profile data from user meta
     */
    private static function get_profile_data($user_id) {
        $profile_data = get_user_meta($user_id, self::META_KEY, true);
        
        // If no profile data exists, return default structure
        if (empty($profile_data)) {
            return [
                'user_id' => $user_id,
                'phone' => '',
                'age' => '',
                'date_of_birth' => '',
                'height' => '',
                'weight' => '',
                'gender' => '',
                'dominant_side' => '',
                'medical_clearance' => false,
                'medical_notes' => '',
                'emergency_contact_name' => '',
                'emergency_contact_phone' => '',
                'injuries' => []
            ];
        }

        // Ensure user_id is included and age is an integer
        $profile_data['user_id'] = $user_id;
        if (isset($profile_data['age']) && $profile_data['age'] !== '') {
            $profile_data['age'] = absint($profile_data['age']);
        }
        
        return $profile_data;
    }

    /**
     * Validate profile data
     */
    private static function validate_profile_data($data) {
        $errors = [];

        // Age validation (if provided)
        if (isset($data['age'])) {
            $age = intval($data['age']);
            if ($age < 13 || $age > 120) {
                $errors['age'] = 'Age must be between 13 and 120';
            }
        }

        // Phone validation (if provided)
        if (isset($data['phone']) && !empty($data['phone'])) {
            if (!preg_match('/^[0-9+\-\(\)\s]*$/', $data['phone'])) {
                $errors['phone'] = 'Invalid phone number format';
            }
        }

        // Date of birth validation (if provided)
        if (isset($data['date_of_birth']) && !empty($data['date_of_birth'])) {
            $date = date_create($data['date_of_birth']);
            if (!$date) {
                $errors['date_of_birth'] = 'Invalid date format';
            }
        }

        // Height validation (if provided)
        if (isset($data['height'])) {
            $height = floatval($data['height']);
            if ($height < 0 || $height > 300) {
                $errors['height'] = 'Height must be between 0 and 300 cm';
            }
        }

        // Weight validation (if provided)
        if (isset($data['weight'])) {
            $weight = floatval($data['weight']);
            if ($weight < 0 || $weight > 500) {
                $errors['weight'] = 'Weight must be between 0 and 500 kg';
            }
        }

        // Gender validation (if provided)
        if (isset($data['gender']) && !empty($data['gender'])) {
            if (!in_array($data['gender'], ['male', 'female', 'other'])) {
                $errors['gender'] = 'Invalid gender value';
            }
        }

        // Dominant side validation (if provided)
        if (isset($data['dominant_side']) && !empty($data['dominant_side'])) {
            if (!in_array($data['dominant_side'], ['left', 'right'])) {
                $errors['dominant_side'] = 'Invalid dominant side value';
            }
        }

        if (!empty($errors)) {
            return new WP_Error(
                'validation_error',
                'Profile validation failed',
                [
                    'status' => 400,
                    'errors' => $errors
                ]
            );
        }

        return true;
    }
}

// Initialize endpoints
ProfileEndpoints::init();

// Debug log when this file is loaded
error_log('Profile endpoints file loaded'); 