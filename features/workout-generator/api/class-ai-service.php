<?php
/**
 * AI Service class for handling workout generation and modification.
 */

namespace AthleteDashboard\Features\WorkoutGenerator\API;

use Exception;

/**
 * Class AI_Service_Exception
 */
class AI_Service_Exception extends Exception {
    private $data;

    /**
     * Constructor.
     *
     * @param string $message Error message.
     * @param string $code Error code.
     * @param mixed  $data Additional error data.
     */
    public function __construct($message, $code = '', $data = null) {
        parent::__construct($message, 0);
        $this->code = $code;
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }
}

/**
 * Class AI_Service
 */
class AI_Service {
    /**
     * Rate limiter instance.
     *
     * @var Rate_Limiter
     */
    private $rate_limiter;

    /**
     * WordPress functions wrapper.
     *
     * @var array
     */
    private $wp_functions;

    /**
     * Constructor.
     *
     * @param Rate_Limiter|null $rate_limiter Optional rate limiter instance.
     * @param array|null $wp_functions Optional WordPress functions wrapper.
     */
    public function __construct($rate_limiter = null, $wp_functions = null) {
        $this->validate_configuration();
        $this->rate_limiter = $rate_limiter ?? new Rate_Limiter();
        $this->wp_functions = $wp_functions ?? [
            'get_bloginfo' => 'get_bloginfo',
            'wp_remote_request' => 'wp_remote_request',
            'wp_remote_retrieve_response_code' => 'wp_remote_retrieve_response_code',
            'wp_remote_retrieve_body' => 'wp_remote_retrieve_body',
            'is_wp_error' => 'is_wp_error',
            'wp_json_encode' => 'wp_json_encode'
        ];
    }

    /**
     * Make a request to the AI service.
     *
     * @param string $method HTTP method.
     * @param string $endpoint API endpoint.
     * @param array  $data Request data.
     * @return array Response data.
     * @throws AI_Service_Exception If the request fails.
     */
    private function make_request($method, $endpoint, $data = null) {
        if (!$this->rate_limiter->check_limit()) {
            $headers = $this->rate_limiter->get_rate_limit_headers();
            throw new AI_Service_Exception(
                'Rate limit exceeded. Reset in ' . $headers['X-RateLimit-Reset'] . ' seconds.',
                'RATE_LIMIT_EXCEEDED',
                $headers
            );
        }

        $url = rtrim(AI_SERVICE_ENDPOINT, '/') . '/' . ltrim($endpoint, '/');
        $args = [
            'method' => $method,
            'headers' => array_merge(
                [
                    'Content-Type' => 'application/json',
                    'X-API-Key' => AI_SERVICE_API_KEY,
                    'User-Agent' => call_user_func($this->wp_functions['get_bloginfo'], 'name') . ' WordPress Plugin'
                ],
                $this->rate_limiter->get_rate_limit_headers()
            ),
            'timeout' => 30
        ];

        if ($data !== null) {
            $args['body'] = call_user_func($this->wp_functions['wp_json_encode'], $data);
        }

        $response = call_user_func($this->wp_functions['wp_remote_request'], $url, $args);

        if (call_user_func($this->wp_functions['is_wp_error'], $response)) {
            throw new AI_Service_Exception($response->get_error_message(), 'REQUEST_FAILED');
        }

        $status_code = call_user_func($this->wp_functions['wp_remote_retrieve_response_code'], $response);
        $body = call_user_func($this->wp_functions['wp_remote_retrieve_body'], $response);

        if ($status_code !== 200) {
            throw new AI_Service_Exception('API request failed: ' . $body, 'API_ERROR');
        }

        $data = json_decode($body, true);
        if ($data === null) {
            throw new AI_Service_Exception('Invalid JSON response', 'INVALID_RESPONSE');
        }

        return $data;
    }

    /**
     * Validate profile data for workout generation.
     *
     * @param array $profile_data The user's profile data.
     * @throws AI_Service_Exception If the profile data is invalid.
     */
    private function validate_profile_data($profile_data) {
        $required_fields = ['heightCm', 'weightKg', 'experienceLevel'];
        $missing_fields = [];

        foreach ($required_fields as $field) {
            if (!isset($profile_data[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            throw new AI_Service_Exception(
                'Required profile fields missing: ' . implode(', ', $missing_fields),
                'PROFILE_DATA_MISSING'
            );
        }

        // Validate numeric fields
        if ($profile_data['heightCm'] <= 0 || $profile_data['weightKg'] <= 0) {
            throw new AI_Service_Exception(
                'Height and weight must be positive numbers',
                'PROFILE_DATA_INVALID'
            );
        }

        // Validate experience level
        $valid_experience_levels = ['beginner', 'intermediate', 'advanced'];
        if (!in_array($profile_data['experienceLevel'], $valid_experience_levels)) {
            throw new AI_Service_Exception(
                'Invalid experience level. Must be one of: ' . implode(', ', $valid_experience_levels),
                'PROFILE_DATA_INVALID'
            );
        }

        // Ensure arrays are properly formatted
        $array_fields = ['injuries', 'equipment', 'fitnessGoals'];
        foreach ($array_fields as $field) {
            if (isset($profile_data[$field]) && !is_array($profile_data[$field])) {
                throw new AI_Service_Exception(
                    "Field '$field' must be an array",
                    'PROFILE_DATA_INVALID'
                );
            }
        }
    }

    /**
     * Validate workout preferences.
     *
     * @param array $preferences The workout preferences.
     * @throws AI_Service_Exception If the preferences are invalid.
     */
    private function validate_preferences($preferences) {
        if (empty($preferences)) {
            return;
        }

        // Validate duration if provided
        if (isset($preferences['duration'])) {
            if (!is_numeric($preferences['duration']) || $preferences['duration'] <= 0) {
                throw new AI_Service_Exception(
                    'Duration must be a positive number',
                    'PREFERENCES_INVALID'
                );
            }
        }

        // Validate intensity if provided
        if (isset($preferences['intensity'])) {
            $valid_intensities = ['low', 'medium', 'high'];
            if (!in_array($preferences['intensity'], $valid_intensities)) {
                throw new AI_Service_Exception(
                    'Invalid intensity. Must be one of: ' . implode(', ', $valid_intensities),
                    'PREFERENCES_INVALID'
                );
            }
        }

        // Validate focus areas if provided
        if (isset($preferences['focusAreas']) && !is_array($preferences['focusAreas'])) {
            throw new AI_Service_Exception(
                'Focus areas must be an array',
                'PREFERENCES_INVALID'
            );
        }
    }

    /**
     * Generate a workout plan using profile data.
     *
     * @param array $profile_data The user's profile data.
     * @param array $preferences Optional workout preferences.
     * @return array The generated workout plan.
     * @throws AI_Service_Exception If the profile data is invalid or the request fails.
     */
    public function generate_workout_plan_with_profile($profile_data, $preferences = []) {
        $this->validate_profile_data($profile_data);
        $this->validate_preferences($preferences);

        $prompt = array_merge(
            $profile_data,
            ['preferences' => $preferences]
        );

        return $this->make_request('POST', 'generate', $prompt);
    }

    /**
     * Generate a workout plan.
     *
     * @param array $prompt The workout preferences.
     * @return array The generated workout plan.
     * @throws AI_Service_Exception If the request fails.
     */
    public function generate_workout_plan($prompt) {
        return $this->make_request('POST', 'generate', $prompt);
    }

    /**
     * Modify a workout plan.
     *
     * @param array $workout The workout to modify.
     * @param array $modifications The modifications to apply.
     * @return array The modified workout plan.
     * @throws AI_Service_Exception If the request fails.
     */
    public function modify_workout_plan($workout, $modifications) {
        return $this->make_request('POST', 'modify', [
            'workout' => $workout,
            'modifications' => $modifications
        ]);
    }

    /**
     * Get a workout by ID.
     *
     * @param int $workout_id The workout ID.
     * @return array The workout data.
     * @throws AI_Service_Exception If the request fails.
     */
    public function get_workout_by_id($workout_id) {
        return $this->make_request('GET', "workout/{$workout_id}");
    }

    /**
     * Get workout history.
     *
     * @param int    $user_id The user ID.
     * @param string $date    The date to get history for.
     * @return array The workout history.
     * @throws AI_Service_Exception If the request fails.
     */
    public function get_workout_history($user_id, $date) {
        $filters = ['date' => $date];
        return $this->make_request('GET', "history/{$user_id}?" . http_build_query($filters));
    }

    /**
     * Suggest alternative exercises.
     *
     * @param array $exercise The exercise to find alternatives for.
     * @param array $constraints The constraints for alternatives.
     * @return array The alternative exercises.
     * @throws AI_Service_Exception If the request fails.
     */
    public function suggest_alternatives($exercise, $constraints) {
        return $this->make_request('POST', 'alternatives', [
            'exercise' => $exercise,
            'constraints' => $constraints
        ]);
    }

    /**
     * Validate the service configuration.
     *
     * @throws AI_Service_Exception If the configuration is invalid.
     */
    private function validate_configuration() {
        if (!defined('AI_SERVICE_API_KEY') || !defined('AI_SERVICE_ENDPOINT')) {
            throw new AI_Service_Exception(
                'AI service configuration is missing. Please define AI_SERVICE_API_KEY and AI_SERVICE_ENDPOINT.',
                'CONFIG_ERROR'
            );
        }
    }
} 