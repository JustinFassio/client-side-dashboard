<?php
namespace AthleteDashboard\RestApi;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Rest_Controller_Base {
    /**
     * The namespace for this controller's routes.
     *
     * @var string
     */
    protected $namespace = 'athlete-dashboard/v1';

    /**
     * The base for this controller's routes.
     *
     * @var string
     */
    protected $rest_base;

    /**
     * Custom rate limit rules for this controller.
     *
     * @var array
     */
    protected $rate_limit_rules = array();

    /**
     * Register routes for this controller.
     */
    abstract public function register_routes();

    /**
     * Check permissions for the request.
     *
     * @param \WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if permission is granted, WP_Error otherwise.
     */
    protected function check_permission($request) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_forbidden',
                __('You must be logged in to access this endpoint.', 'athlete-dashboard'),
                array('status' => 401)
            );
        }

        // Check rate limiting
        $rate_limit = Rate_Limiter::check_rate_limit(
            get_current_user_id(),
            $this->rest_base,
            $this->rate_limit_rules
        );

        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }

        return true;
    }

    /**
     * Validate request data.
     *
     * @param array  $data    The request data to validate.
     * @param array  $rules   The validation rules.
     * @param string $context The validation context (create/update).
     * @return array|\WP_Error Validated and sanitized data or WP_Error if validation fails.
     */
    protected function validate_request($data, $rules, $context = 'create') {
        return Request_Validator::validate($data, $rules, $context);
    }

    /**
     * Add rate limit headers to response.
     *
     * @param \WP_REST_Response $response The response object.
     * @return \WP_REST_Response The modified response object.
     */
    protected function add_rate_limit_headers($response) {
        $status = Rate_Limiter::get_rate_limit_status(
            get_current_user_id(),
            $this->rest_base
        );

        $response->header('X-RateLimit-Limit', $status['endpoint']['limit']);
        $response->header('X-RateLimit-Remaining', $status['endpoint']['remaining']);
        $response->header('X-RateLimit-Reset', $status['endpoint']['reset']);

        return $response;
    }

    /**
     * Prepare response for output.
     *
     * @param mixed $data The data to prepare.
     * @return \WP_REST_Response The prepared response.
     */
    protected function prepare_response($data) {
        $response = rest_ensure_response($data);
        $response = $this->add_rate_limit_headers($response);

        return $response;
    }

    /**
     * Handle error response.
     *
     * @param \WP_Error $error The error object.
     * @return \WP_REST_Response The error response.
     */
    protected function handle_error($error) {
        $data = $error->get_error_data();
        $status = isset($data['status']) ? $data['status'] : 500;

        $response = new \WP_REST_Response(
            array(
                'code' => $error->get_error_code(),
                'message' => $error->get_error_message(),
                'data' => $data
            ),
            $status
        );

        return $this->add_rate_limit_headers($response);
    }

    /**
     * Get the item schema.
     *
     * @return array The item schema.
     */
    public function get_item_schema() {
        return array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => $this->rest_base,
            'type'       => 'object'
        );
    }
} 