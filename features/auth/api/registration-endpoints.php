<?php
/**
 * Registration API endpoints
 */

namespace AthleteAuth\API;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class RegistrationEndpoints {
    const ROUTE = 'athlete-dashboard/v1/auth/register';

    /**
     * Register endpoints
     */
    public static function register_routes() {
        error_log('Registering registration endpoints');

        register_rest_route(
            'athlete-dashboard/v1',
            '/auth/register',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [self::class, 'register_user'],
                'permission_callback' => '__return_true',
                'args' => [
                    'username' => [
                        'required' => true,
                        'type' => 'string',
                        'validate_callback' => [self::class, 'validate_username']
                    ],
                    'email' => [
                        'required' => true,
                        'type' => 'string',
                        'validate_callback' => [self::class, 'validate_email']
                    ],
                    'password' => [
                        'required' => true,
                        'type' => 'string',
                        'validate_callback' => [self::class, 'validate_password']
                    ],
                    'firstName' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'lastName' => [
                        'required' => true,
                        'type' => 'string'
                    ],
                    'agreeToTerms' => [
                        'required' => true,
                        'type' => 'boolean'
                    ]
                ]
            ]
        );

        error_log('Registration endpoints registered successfully');
    }

    /**
     * Register a new user
     */
    public static function register_user(WP_REST_Request $request) {
        $data = $request->get_json_params();
        error_log('Processing registration request: ' . json_encode($data));

        // Validate terms agreement
        if (!$data['agreeToTerms']) {
            return new WP_Error(
                'terms_not_accepted',
                'You must agree to the Terms and Conditions',
                ['status' => 400]
            );
        }

        // Check if username exists
        if (username_exists($data['username'])) {
            return new WP_Error(
                'username_exists',
                'This username is already taken',
                ['status' => 400]
            );
        }

        // Check if email exists
        if (email_exists($data['email'])) {
            return new WP_Error(
                'email_exists',
                'This email is already registered',
                ['status' => 400]
            );
        }

        try {
            // Create user
            $user_id = wp_create_user(
                $data['username'],
                $data['password'],
                $data['email']
            );

            if (is_wp_error($user_id)) {
                error_log('User creation failed: ' . $user_id->get_error_message());
                return new WP_Error(
                    'registration_failed',
                    'Failed to create user account',
                    ['status' => 500]
                );
            }

            // Update user meta
            wp_update_user([
                'ID' => $user_id,
                'first_name' => sanitize_text_field($data['firstName']),
                'last_name' => sanitize_text_field($data['lastName']),
                'role' => 'athlete'
            ]);

            // Add registration timestamp
            update_user_meta($user_id, 'registration_date', current_time('mysql'));

            // Send welcome email
            self::send_welcome_email($user_id);

            error_log("User registered successfully: $user_id");
            return rest_ensure_response([
                'success' => true,
                'message' => 'Registration successful',
                'userId' => $user_id
            ]);
        } catch (\Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            return new WP_Error(
                'registration_failed',
                'An error occurred during registration',
                ['status' => 500]
            );
        }
    }

    /**
     * Validate username
     */
    public static function validate_username($username) {
        if (strlen($username) < 3 || strlen($username) > 20) {
            return false;
        }
        return preg_match('/^[a-zA-Z0-9_-]+$/', $username);
    }

    /**
     * Validate email
     */
    public static function validate_email($email) {
        return is_email($email);
    }

    /**
     * Validate password
     */
    public static function validate_password($password) {
        if (strlen($password) < 8) {
            return false;
        }
        // Require at least one uppercase letter, one lowercase letter, one number, and one special character
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }

    /**
     * Send welcome email
     */
    private static function send_welcome_email($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;

        $to = $user->user_email;
        $subject = 'Welcome to Athlete Dashboard';
        $message = sprintf(
            "Hi %s,\n\nWelcome to Athlete Dashboard! Your account has been created successfully.\n\nBest regards,\nThe Athlete Dashboard Team",
            $user->first_name
        );
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($to, $subject, $message, $headers);
    }
}

// Register endpoints
add_action('rest_api_init', ['AthleteAuth\API\RegistrationEndpoints', 'register_routes']); 