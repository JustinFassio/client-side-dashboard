<?php

namespace AthleteDashboard\Features\Profile\API\Endpoints\Physical;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/services/class-physical-service.php';
require_once dirname( __DIR__ ) . '/base/trait-auth-checks.php';

use AthleteDashboard\Features\Profile\API\Response_Factory;
use AthleteDashboard\Features\Profile\Services\Physical_Service;
use AthleteDashboard\Features\Profile\API\Endpoints\Base\Auth_Checks;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

abstract class Base_Endpoint {
	use Auth_Checks;

	protected $response_factory;
	protected $service;

	public function __construct() {
		$this->response_factory = new Response_Factory();
		$this->service          = new Physical_Service();
	}

	abstract public function get_route(): string;
	abstract public function get_method(): string;
	abstract protected function handle_request( WP_REST_Request $request ): WP_REST_Response|WP_Error;

	public function register(): void {
		error_log( 'Registering physical endpoint: ' . $this->get_route() );
		register_rest_route(
			'athlete-dashboard/v1',
			$this->get_route(),
			array(
				'methods'             => $this->get_method(),
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	protected function check_permission( WP_REST_Request $request ): bool|WP_Error {
		// First check if user is logged in
		$logged_in_check = $this->check_logged_in();
		if ( is_wp_error( $logged_in_check ) ) {
			return $logged_in_check;
		}

		// Then check if they own the resource
		$user_id = $request['user_id'] ?? 0;
		return $this->check_resource_owner( $user_id );
	}
}
