<?php
/**
 * Tests for the Equipment Endpoints class.
 *
 * @package AthleteDashboard\Tests\RestApi
 */

namespace AthleteDashboard\Tests\RestApi;

use AthleteDashboard\Features\Equipment\API\Equipment_Endpoints;
use AthleteDashboard\Tests\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class EquipmentEndpointsTest
 */
class EquipmentEndpointsTest extends TestCase {
	/**
	 * The equipment endpoints instance.
	 *
	 * @var Equipment_Endpoints
	 */
	private $endpoints;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoints = Equipment_Endpoints::get_instance();
		$this->endpoints->register_routes();
	}

	/**
	 * Test getting equipment items.
	 */
	public function test_get_equipment() {
		$user_id = $this->create_test_user( array( 'manage_equipment' => true ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/equipment/items' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = $this->endpoints->get_equipment( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsArray( $data['data'] );
	}

	/**
	 * Test adding equipment items.
	 */
	public function test_add_equipment() {
		$user_id = $this->create_test_user( array( 'manage_equipment' => true ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/equipment/items' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'name', 'Test Equipment' );
		$request->set_param( 'type', 'machine' );
		$request->set_param( 'weightRange', '10-100' );
		$request->set_param( 'quantity', 1 );
		$request->set_param( 'description', 'Test description' );

		$response = $this->endpoints->add_equipment( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'id', $data['data'] );
		$this->assertEquals( 'Test Equipment', $data['data']['name'] );
	}

	/**
	 * Test updating equipment items.
	 */
	public function test_update_equipment() {
		$user_id = $this->create_test_user( array( 'manage_equipment' => true ) );
		wp_set_current_user( $user_id );

		// First, add an equipment item.
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/equipment/items' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'name', 'Original Equipment' );
		$request->set_param( 'type', 'machine' );
		$response = $this->endpoints->add_equipment( $request );
		$item_id  = $response->get_data()['data']['id'];

		// Then, update it.
		$request = new WP_REST_Request( 'PUT', '/athlete-dashboard/v1/equipment/items/' . $item_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'name', 'Updated Equipment' );
		$response = $this->endpoints->update_equipment( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertContains(
			array(
				'id'   => $item_id,
				'name' => 'Updated Equipment',
				'type' => 'machine',
			),
			$data['data']
		);
	}

	/**
	 * Test deleting equipment items.
	 */
	public function test_delete_equipment() {
		$user_id = $this->create_test_user( array( 'manage_equipment' => true ) );
		wp_set_current_user( $user_id );

		// First, add an equipment item.
		$request = new WP_REST_Request( 'POST', '/athlete-dashboard/v1/equipment/items' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_param( 'name', 'Equipment to Delete' );
		$request->set_param( 'type', 'machine' );
		$response = $this->endpoints->add_equipment( $request );
		$item_id  = $response->get_data()['data']['id'];

		// Then, delete it.
		$request = new WP_REST_Request( 'DELETE', '/athlete-dashboard/v1/equipment/items/' . $item_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = $this->endpoints->delete_equipment( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( $item_id, $data['data']['id'] );

		// Verify it's gone.
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/equipment/items' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = $this->endpoints->get_equipment( $request );
		$data     = $response->get_data();
		$this->assertEmpty( $data['data'] );
	}

	/**
	 * Test getting equipment sets.
	 */
	public function test_get_equipment_sets() {
		$user_id = $this->create_test_user( array( 'manage_equipment' => true ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/equipment/sets' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = $this->endpoints->get_equipment_sets( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsArray( $data['data'] );
	}

	/**
	 * Test getting workout zones.
	 */
	public function test_get_workout_zones() {
		$user_id = $this->create_test_user( array( 'manage_equipment' => true ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/equipment/zones' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = $this->endpoints->get_workout_zones( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertIsArray( $data['data'] );
	}

	/**
	 * Test permission checks.
	 */
	public function test_permission_check() {
		// Test without user.
		$request = new WP_REST_Request( 'GET', '/athlete-dashboard/v1/equipment/items' );
		$this->assertFalse( $this->endpoints->check_permission( $request ) );

		// Test with user but no nonce.
		$user_id = $this->create_test_user( array( 'manage_equipment' => true ) );
		wp_set_current_user( $user_id );
		$this->assertFalse( $this->endpoints->check_permission( $request ) );

		// Test with user and valid nonce.
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$this->assertTrue( $this->endpoints->check_permission( $request ) );
	}
}
