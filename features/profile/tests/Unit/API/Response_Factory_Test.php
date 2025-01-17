<?php
/**
 * Response Factory Test class.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\API
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\API;

use AthleteDashboard\Features\Profile\API\Response_Factory;
use AthleteDashboard\Features\Profile\API\Endpoints\User\User_Get;
use AthleteDashboard\Features\Profile\Profile_Service;
use WP_Error;
use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Response_Factory_Test
 */
class Response_Factory_Test extends WP_UnitTestCase {
	/**
	 * Response factory instance.
	 *
	 * @var Response_Factory
	 */
	protected $factory;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Response_Factory();
	}

	/**
	 * Test success response creation.
	 */
	public function test_success_response(): void {
		$data     = array( 'test' => 'data' );
		$response = $this->factory->success( $data );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'success' => true,
				'data'    => $data,
			),
			$response->get_data()
		);
	}

	/**
	 * Test success response with custom status.
	 */
	public function test_success_response_with_custom_status(): void {
		$data     = array( 'test' => 'data' );
		$response = $this->factory->success( $data, 201 );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals(
			array(
				'success' => true,
				'data'    => $data,
			),
			$response->get_data()
		);
	}

	/**
	 * Test error response creation.
	 */
	public function test_error_response(): void {
		$message  = 'Test error';
		$response = $this->factory->error( $message );

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals(
			array(
				'success' => false,
				'error'   => array(
					'message' => $message,
					'code'    => 500,
				),
			),
			$response->get_data()
		);
	}

	/**
	 * Test error response with custom code and data.
	 */
	public function test_error_response_with_custom_code_and_data(): void {
		$message         = 'Test error';
		$code            = 400;
		$additional_data = array( 'field' => 'error' );
		$response        = $this->factory->error( $message, $code, $additional_data );

		$this->assertEquals( $code, $response->get_status() );
		$this->assertEquals(
			array(
				'success' => false,
				'error'   => array(
					'message' => $message,
					'code'    => $code,
					'data'    => $additional_data,
				),
			),
			$response->get_data()
		);
	}

	/**
	 * Test validation error response.
	 */
	public function test_validation_error_response(): void {
		$wp_error = new WP_Error( 'test_error', 'Test validation error', array( 'field' => 'invalid' ) );
		$response = $this->factory->validation_error( $wp_error );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals(
			array(
				'success' => false,
				'error'   => array(
					'message' => 'Test validation error',
					'code'    => 400,
					'data'    => array( 'field' => 'invalid' ),
				),
			),
			$response->get_data()
		);
	}

	/**
	 * Test not found error response.
	 */
	public function test_not_found_response(): void {
		$message  = 'Resource not found';
		$response = $this->factory->not_found( $message );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals(
			array(
				'success' => false,
				'error'   => array(
					'message' => $message,
					'code'    => 404,
				),
			),
			$response->get_data()
		);
	}

	/**
	 * Test unauthorized error response.
	 */
	public function test_unauthorized_response(): void {
		$message  = 'Unauthorized access';
		$response = $this->factory->unauthorized( $message );

		$this->assertEquals( 401, $response->get_status() );
		$this->assertEquals(
			array(
				'success' => false,
				'error'   => array(
					'message' => $message,
					'code'    => 401,
				),
			),
			$response->get_data()
		);
	}

	/**
	 * Test forbidden error response.
	 */
	public function test_forbidden_response(): void {
		$message  = 'Access forbidden';
		$response = $this->factory->forbidden( $message );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals(
			array(
				'success' => false,
				'error'   => array(
					'message' => $message,
					'code'    => 403,
				),
			),
			$response->get_data()
		);
	}

	/**
	 * Test display name handling in response
	 */
	public function test_display_name_handling(): void {
		// Create test data
		$data = array(
			'display_name' => 'Test Display Name',
			'user_id'      => 1,
		);

		// Get response using factory
		$response = $this->factory->success( $data );

		// Assert response structure
		$this->assertTrue( $response instanceof WP_REST_Response );
		$response_data = $response->get_data();

		// Check response format
		$this->assertTrue( $response_data['success'] );
		$this->assertEquals( 'Test Display Name', $response_data['data']['display_name'] );
	}
}
