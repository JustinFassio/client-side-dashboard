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

        // Add user data endpoint
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/user',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [self::class, 'get_user_data'],
                    'permission_callback' => [self::class, 'check_auth'],
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [self::class, 'update_user_data'],
                    'permission_callback' => [self::class, 'check_auth'],
                ]
            ]
        );

        // Add combined data endpoint
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/full',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_combined_data'],
                'permission_callback' => [self::class, 'check_auth'],
            ]
        );

        // Add basic data endpoint
        register_rest_route(
            self::NAMESPACE,
            '/' . self::ROUTE . '/basic',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [self::class, 'get_basic_data'],
                'permission_callback' => [self::class, 'check_auth'],
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
        global $wpdb;
        
        error_log("=== Profile Data Fetch Debug ===");
        error_log("Fetching profile data for user ID: {$user_id}");
        
        // Fetch core user data
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("ERROR: Failed to fetch user data for ID: {$user_id}");
            return new WP_Error('user_not_found', 'User not found');
        }
        
        error_log("Core WordPress user data:");
        error_log(print_r([
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name
        ], true));
        
        // Get profile meta data
        $profile_data = get_user_meta($user_id, self::META_KEY, true);
        error_log("Raw profile meta data:");
        error_log(print_r($profile_data, true));
        
        // If no profile data exists, return default structure
        if (empty($profile_data)) {
            error_log("No existing profile data found, returning default structure");
            $profile_data = [
                'id' => $user_id,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'displayName' => $user->display_name,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'phoneNumber' => '',
                'age' => 0,
                'gender' => '',
                'height' => 0,
                'weight' => 0,
                'preferredUnits' => 'imperial',
                'fitnessLevel' => 'beginner',
                'activityLevel' => 'sedentary',
                'medicalConditions' => [],
                'exerciseLimitations' => [],
                'medications' => '',
                'injuries' => [],
                'physicalMetrics' => [
                    [
                        'type' => 'height',
                        'value' => 0,
                        'unit' => 'cm',
                        'date' => date('c')
                    ],
                    [
                        'type' => 'weight',
                        'value' => 0,
                        'unit' => 'kg',
                        'date' => date('c')
                    ]
                ]
            ];
        }

        // Ensure core user data is always included and up to date
        $profile_data['id'] = $user_id;
        $profile_data['username'] = $user->user_login;
        $profile_data['email'] = $user->user_email;
        $profile_data['displayName'] = $user->display_name;
        $profile_data['firstName'] = $user->first_name;
        $profile_data['lastName'] = $user->last_name;
        
        error_log("Final profile data structure:");
        error_log(print_r($profile_data, true));
        error_log("=== End Profile Data Fetch Debug ===");
        
        return $profile_data;
    }

    /**
     * Validate profile data
     */
    private static function validate_profile_data($data) {
        $errors = [];

        // Validate height (in cm)
        if (isset($data['height'])) {
            $height = floatval($data['height']);
            if ($height < 50 || $height > 250) {
                $errors['height'] = 'Height must be between 50cm and 250cm';
            }
        }

        // Validate weight (in kg)
        if (isset($data['weight'])) {
            $weight = floatval($data['weight']);
            if ($weight < 30 || $weight > 200) {
                $errors['weight'] = 'Weight must be between 30kg and 200kg';
            }
        }

        // Validate preferred units
        if (isset($data['preferredUnits']) && !in_array($data['preferredUnits'], ['imperial', 'metric'])) {
            $errors['preferredUnits'] = 'Invalid unit system';
        }

        // Validate age
        if (isset($data['age'])) {
            $age = intval($data['age']);
            if ($age < 13 || $age > 120) {
                $errors['age'] = 'Age must be between 13 and 120';
            }
        }

        // Validate gender
        if (isset($data['gender']) && !in_array($data['gender'], ['male', 'female', 'other', 'prefer_not_to_say'])) {
            $errors['gender'] = 'Invalid gender value';
        }

        // Validate fitness level
        if (isset($data['fitnessLevel']) && !in_array($data['fitnessLevel'], ['beginner', 'intermediate', 'advanced', 'expert'])) {
            $errors['fitnessLevel'] = 'Invalid fitness level';
        }

        // Validate activity level
        if (isset($data['activityLevel']) && !in_array($data['activityLevel'], ['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extremely_active'])) {
            $errors['activityLevel'] = 'Invalid activity level';
        }

        // Validate physicalMetrics
        if (isset($data['physicalMetrics'])) {
            if (!is_array($data['physicalMetrics'])) {
                $errors['physicalMetrics'] = 'Physical metrics must be an array';
            } else {
                foreach ($data['physicalMetrics'] as $index => $metric) {
                    if (!isset($metric['type']) || !in_array($metric['type'], ['height', 'weight', 'bodyFat', 'muscleMass'])) {
                        $errors["physicalMetrics.{$index}.type"] = 'Invalid metric type';
                    }
                    if (!isset($metric['value']) || !is_numeric($metric['value'])) {
                        $errors["physicalMetrics.{$index}.value"] = 'Invalid metric value';
                    }
                    if (!isset($metric['unit']) || empty($metric['unit'])) {
                        $errors["physicalMetrics.{$index}.unit"] = 'Invalid metric unit';
                    }
                    if (!isset($metric['date']) || !strtotime($metric['date'])) {
                        $errors["physicalMetrics.{$index}.date"] = 'Invalid metric date';
                    }
                }
            }
        }

        if (!empty($errors)) {
            return new WP_Error(
                'validation_error',
                'Profile validation failed',
                ['status' => 400, 'errors' => $errors]
            );
        }

        return true;
    }

    /**
     * Get WordPress user data
     */
    public static function get_user_data() {
        $user_id = get_current_user_id();
        error_log("Fetching WordPress user data for user: $user_id");

        try {
            $user = get_userdata($user_id);
            if (!$user) {
                return new WP_Error(
                    'user_not_found',
                    'User not found',
                    ['status' => 404]
                );
            }

            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching user data: " . $e->getMessage());
            return new WP_Error(
                'user_fetch_error',
                'Failed to fetch user data',
                ['status' => 500]
            );
        }
    }

    /**
     * Update WordPress user data
     */
    public static function update_user_data(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $data = $request->get_json_params();
        error_log("Updating WordPress user data for user: $user_id");
        error_log("Update data: " . json_encode($data));

        try {
            $update_data = [
                'ID' => $user_id
            ];

            // Map fields to WordPress user data
            if (isset($data['email'])) {
                $update_data['user_email'] = sanitize_email($data['email']);
            }
            if (isset($data['display_name'])) {
                $update_data['display_name'] = sanitize_text_field($data['display_name']);
            }
            if (isset($data['first_name'])) {
                $update_data['first_name'] = sanitize_text_field($data['first_name']);
            }
            if (isset($data['last_name'])) {
                $update_data['last_name'] = sanitize_text_field($data['last_name']);
            }

            // Update user data
            $result = wp_update_user($update_data);

            if (is_wp_error($result)) {
                error_log("Error updating user: " . $result->get_error_message());
                return new WP_Error(
                    'user_update_error',
                    $result->get_error_message(),
                    ['status' => 500]
                );
            }

            // Get updated user data
            $user = get_userdata($user_id);
            if (!$user) {
                return new WP_Error(
                    'user_not_found',
                    'User not found after update',
                    ['status' => 404]
                );
            }

            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name
                ],
                'message' => 'User data updated successfully'
            ]);
        } catch (\Exception $e) {
            error_log("Error updating user data: " . $e->getMessage());
            return new WP_Error(
                'user_update_error',
                'Failed to update user data',
                ['status' => 500]
            );
        }
    }

    /**
     * Get combined user and profile data
     */
    public static function get_combined_data() {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error(
                'user_not_found',
                'User not found',
                ['status' => 404]
            );
        }

        try {
            $profile_data = self::get_profile_data($user_id);
            
            $combined_data = [
                // WordPress core fields
                'username' => $user->user_login,
                'email' => $user->user_email,
                'displayName' => $user->display_name,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                
                // Profile data
                ...$profile_data
            ];

            return rest_ensure_response([
                'success' => true,
                'data' => $combined_data
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching combined data: " . $e->getMessage());
            return new WP_Error(
                'fetch_error',
                'Failed to fetch user data',
                ['status' => 500]
            );
        }
    }

    /**
     * Get basic profile data for initial render
     */
    public static function get_basic_data() {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error(
                'user_not_found',
                'User not found',
                ['status' => 404]
            );
        }

        try {
            // Get only essential profile fields
            $profile_data = self::get_profile_data($user_id);
            
            $basic_data = [
                // Essential user fields
                'username' => $user->user_login,
                'displayName' => $user->display_name,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                
                // Essential profile fields
                'userId' => $profile_data['user_id'],
                'email' => $user->user_email
            ];

            // Add cache headers
            header('Cache-Control: private, max-age=60'); // 1 minute cache
            header('ETag: "' . md5(json_encode($basic_data)) . '"');

            return rest_ensure_response([
                'success' => true,
                'data' => $basic_data
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching basic data: " . $e->getMessage());
            return new WP_Error(
                'fetch_error',
                'Failed to fetch basic user data',
                ['status' => 500]
            );
        }
    }
}

// Initialize endpoints
ProfileEndpoints::init();

// Debug log when this file is loaded
error_log('Profile endpoints file loaded'); 