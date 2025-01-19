<?php
/**
 * AI Service for workout generation
 */

class AI_Service_Exception extends Exception {
    private $error_code;
    private $error_details;

    public function __construct($message, $code = 0, $details = null) {
        parent::__construct($message, $code);
        $this->error_code = $code;
        $this->error_details = $details;
    }

    public function get_error_code() {
        return $this->error_code;
    }

    public function get_error_details() {
        return $this->error_details;
    }
}

class AI_Service {
    private $api_key;
    private $node_endpoint;
    private $rate_limiter;

    public function __construct() {
        $this->validate_configuration();
        $this->rate_limiter = new Rate_Limiter('ai_service', 100, 3600); // 100 requests per hour
    }

    /**
     * Validate service configuration
     */
    private function validate_configuration() {
        // Check for API key
        if (!defined('AI_SERVICE_API_KEY') || empty(AI_SERVICE_API_KEY)) {
            throw new AI_Service_Exception(
                'AI service API key not configured',
                'CONFIG_ERROR'
            );
        }
        $this->api_key = AI_SERVICE_API_KEY;

        // Check for endpoint
        if (!defined('AI_SERVICE_ENDPOINT') || empty(AI_SERVICE_ENDPOINT)) {
            throw new AI_Service_Exception(
                'AI service endpoint not configured',
                'CONFIG_ERROR'
            );
        }
        $this->node_endpoint = AI_SERVICE_ENDPOINT;

        // Validate endpoint URL
        if (!filter_var($this->node_endpoint, FILTER_VALIDATE_URL)) {
            throw new AI_Service_Exception(
                'Invalid AI service endpoint URL',
                'CONFIG_ERROR'
            );
        }
    }

    /**
     * Generate a workout plan using AI
     */
    public function generate_workout_plan($prompt) {
        $this->check_rate_limit();
        return $this->make_request('POST', '/generate', $prompt);
    }

    /**
     * Modify an existing workout plan
     */
    public function modify_workout_plan($workout, $modifications) {
        $this->check_rate_limit();
        return $this->make_request('POST', '/modify', [
            'workout' => $workout,
            'modifications' => $modifications
        ]);
    }

    /**
     * Get workout by ID
     */
    public function get_workout_by_id($workout_id) {
        if (empty($workout_id)) {
            throw new AI_Service_Exception(
                'Workout ID is required',
                'INVALID_INPUT'
            );
        }
        return $this->make_request('GET', "/workout/{$workout_id}");
    }

    /**
     * Get exercise by ID
     */
    public function get_exercise_by_id($exercise_id) {
        if (empty($exercise_id)) {
            throw new AI_Service_Exception(
                'Exercise ID is required',
                'INVALID_INPUT'
            );
        }
        return $this->make_request('GET', "/exercise/{$exercise_id}");
    }

    /**
     * Get workout history
     */
    public function get_workout_history($user_id, $filters = null) {
        if (empty($user_id)) {
            throw new AI_Service_Exception(
                'User ID is required',
                'INVALID_INPUT'
            );
        }
        $query = $filters ? '?' . http_build_query($filters) : '';
        return $this->make_request('GET', "/history/{$user_id}{$query}");
    }

    /**
     * Suggest alternative exercises
     */
    public function suggest_alternatives($exercise, $constraints) {
        $this->check_rate_limit();
        return $this->make_request('POST', '/alternatives', [
            'exercise' => $exercise,
            'constraints' => $constraints
        ]);
    }

    /**
     * Make HTTP request to Node/TypeScript service
     */
    private function make_request($method, $endpoint, $data = null) {
        $url = $this->node_endpoint . $endpoint;
        
        $args = [
            'method'  => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key'    => $this->api_key,
                'User-Agent'   => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            ],
            'timeout' => 30,
            'sslverify' => true
        ];

        if ($data !== null) {
            $args['body'] = wp_json_encode($data);
        }

        // Log request (if debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'AI Service Request: %s %s',
                $method,
                $url
            ));
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new AI_Service_Exception(
                'Failed to connect to AI service: ' . $response->get_error_message(),
                'CONNECTION_ERROR',
                $response->get_error_data()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json_body = json_decode($body, true);

        // Log response (if debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'AI Service Response: %d %s',
                $status_code,
                substr($body, 0, 1000) // First 1000 chars only
            ));
        }

        if ($status_code !== 200) {
            throw new AI_Service_Exception(
                $json_body['message'] ?? 'Unknown error occurred',
                $json_body['code'] ?? 'API_ERROR',
                $json_body['details'] ?? ['status' => $status_code]
            );
        }

        if ($json_body === null && !empty($body)) {
            throw new AI_Service_Exception(
                'Invalid JSON response from AI service',
                'INVALID_RESPONSE',
                ['response' => substr($body, 0, 1000)]
            );
        }

        return $json_body;
    }

    /**
     * Check rate limit
     */
    private function check_rate_limit() {
        if (!$this->rate_limiter->check_limit()) {
            throw new AI_Service_Exception(
                'Rate limit exceeded',
                'RATE_LIMIT_EXCEEDED',
                [
                    'limit' => $this->rate_limiter->get_limit(),
                    'window' => $this->rate_limiter->get_window(),
                    'remaining' => $this->rate_limiter->get_remaining()
                ]
            );
        }
    }
} 