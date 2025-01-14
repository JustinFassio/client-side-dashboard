<?php
/**
 * Profile routes class.
 *
 * @package AthleteDashboard\Features\Profile\API
 */

namespace AthleteDashboard\Features\Profile\API;

/**
 * Class for registering profile REST API routes.
 */
class Profile_Routes {
	/**
	 * API Namespace.
	 *
	 * @var string
	 */
	private const API_NAMESPACE = 'athlete-dashboard/v1';

	/**
	 * Base route.
	 *
	 * @var string
	 */
	private const BASE_ROUTE = 'profile';

	/**
	 * Profile controller instance.
	 *
	 * @var Profile_Controller
	 */
	private $controller;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->controller = new Profile_Controller();
	}

	/**
	 * Initialize routes.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// Get profile endpoint.
		register_rest_route(
			self::API_NAMESPACE,
			'/' . self::BASE_ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->controller, 'get_profile' ),
				'permission_callback' => '__return_true',
			)
		);

		// Update profile endpoint.
		register_rest_route(
			self::API_NAMESPACE,
			'/' . self::BASE_ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this->controller, 'update_profile' ),
				'permission_callback' => '__return_true',
			)
		);

		// Get combined data endpoint.
		register_rest_route(
			self::API_NAMESPACE,
			'/' . self::BASE_ROUTE . '/combined',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->controller, 'get_combined_data' ),
				'permission_callback' => '__return_true',
			)
		);
	}
}
