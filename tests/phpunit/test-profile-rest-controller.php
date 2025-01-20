<?php
/**
 * Profile REST Controller Test Case
 *
 * @package AthleteDashboard\Tests
 */

namespace AthleteDashboard\Tests;

use WP_REST_Request;
use WP_REST_Server;
use AthleteDashboard\Features\Profile\Controllers\Profile_REST_Controller;

/**
 * @group rest-api
 */
class Test_Profile_REST_Controller extends AD_UnitTestCase {
	private $server;
	private $namespace = 'athlete-dashboard/v1';
	private $route     = '/profile';
	private $user_id;

	protected function setUp(): void {
		parent::setUp();

		// Initialize REST server
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		// Create test user
		$this->user_id = wp_create_user( 'testuser', 'testpass', 'test@example.com' );
		wp_set_current_user( $this->user_id );
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( "/{$this->namespace}{$this->route}", $routes );
		$this->assertArrayHasKey( "/{$this->namespace}{$this->route}/physical", $routes );
	}

	public function test_get_profile() {
		$request  = new WP_REST_Request( 'GET', "/{$this->namespace}{$this->route}" );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'physical', $data );
		$this->assertArrayHasKey( 'experience', $data );
	}

	public function test_update_physical_measurements() {
		$request = new WP_REST_Request( 'POST', "/{$this->namespace}{$this->route}/physical" );
		$request->set_body_params(
			array(
				'heightCm' => 180,
				'weightKg' => 75,
				'units'    => array(
					'height' => 'cm',
					'weight' => 'kg',
				),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 180, $data['height'] );
		$this->assertEquals( 75, $data['weight'] );
	}

	public function test_update_physical_measurements_invalid_data() {
		$request = new WP_REST_Request( 'POST', "/{$this->namespace}{$this->route}/physical" );
		$request->set_body_params(
			array(
				'heightCm' => 400, // Invalid height
				'weightKg' => 75,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'invalid_height', $data['code'] );
	}

	public function test_get_physical_history() {
		$request  = new WP_REST_Request( 'GET', "/{$this->namespace}{$this->route}/physical/history" );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertArrayHasKey( 'pages', $data );
	}

	public function test_unauthorized_access() {
		// Clear current user
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', "/{$this->namespace}{$this->route}" );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_nonce_validation() {
		$request = new WP_REST_Request( 'POST', "/{$this->namespace}{$this->route}/physical" );
		$request->set_body_params(
			array(
				'heightCm' => 180,
				'weightKg' => 75,
			)
		);
		// Don't set nonce

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Cleanup test user
		if ( $this->user_id ) {
			wp_delete_user( $this->user_id );
		}
	}
}
