<?php
/**
 * Profile Routes class.
 *
 * @package AthleteDashboard\Features\Profile\API
 */

namespace AthleteDashboard\Features\Profile\API;

use AthleteDashboard\Features\Profile\API\Registry\Endpoint_Registry;
use AthleteDashboard\Features\Profile\API\Endpoints\User\User_Get;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\API\Profile_Endpoints;
use AthleteDashboard\Features\Profile\API\Response_Factory;

/**
 * Class Profile_Routes
 *
 * Handles registration of profile-related REST API routes.
 */
class Profile_Routes {
	/**
	 * Profile service instance.
	 *
	 * @var Profile_Service
	 */
	private Profile_Service $service;

	/**
	 * Response factory instance.
	 *
	 * @var Response_Factory
	 */
	private Response_Factory $response_factory;

	/**
	 * Endpoint registry instance.
	 *
	 * @var Endpoint_Registry
	 */
	private Endpoint_Registry $registry;

	/**
	 * Constructor.
	 *
	 * @param Profile_Service   $service          Profile service instance.
	 * @param Response_Factory  $response_factory Response factory instance.
	 * @param Endpoint_Registry $registry         Endpoint registry instance.
	 */
	public function __construct(
		Profile_Service $service,
		Response_Factory $response_factory,
		Endpoint_Registry $registry
	) {
		$this->service          = $service;
		$this->response_factory = $response_factory;
		$this->registry         = $registry;
	}

	/**
	 * Initialize routes.
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// Register new User Get endpoint
		$this->registry->register_endpoint(
			new User_Get( $this->service, $this->response_factory )
		);

		// Register remaining legacy routes
		Profile_Endpoints::register_routes();
	}
}
