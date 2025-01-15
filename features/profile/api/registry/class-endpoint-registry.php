<?php
/**
 * Endpoint Registry.
 *
 * @package AthleteDashboard\Features\Profile\API\Registry
 */

namespace AthleteDashboard\Features\Profile\API\Registry;

use AthleteDashboard\Features\Profile\API\Endpoints\Base\Base_Endpoint;

/**
 * Class Endpoint_Registry
 *
 * Manages registration of REST API endpoints.
 */
class Endpoint_Registry {
	/**
	 * Registered endpoints.
	 *
	 * @var Base_Endpoint[]
	 */
	private array $endpoints = array();

	/**
	 * Register an endpoint.
	 *
	 * @param Base_Endpoint $endpoint Endpoint instance.
	 * @return void
	 */
	public function register_endpoint( Base_Endpoint $endpoint ): void {
		$this->endpoints[] = $endpoint;
		$endpoint->register_routes();
	}

	/**
	 * Get all registered endpoints.
	 *
	 * @return Base_Endpoint[]
	 */
	public function get_endpoints(): array {
		return $this->endpoints;
	}
}
