<?php

namespace AthleteDashboard\Features\Profile\Tests\Unit;

use AthleteDashboard\Features\Profile\API\Endpoints\User\User_Get;
use AthleteDashboard\Features\Profile\Services\Profile_Service;
use AthleteDashboard\Features\Profile\API\Response_Factory;
use WP_REST_Request;
use WP_Error;
use PHPUnit\Framework\TestCase;

class User_Get_Test extends TestCase {
	private $profile_service;
	private $response_factory;
	private $endpoint;

	protected function setUp(): void {
		parent::setUp();

		$this->profile_service  = $this->createMock( Profile_Service::class );
		$this->response_factory = $this->createMock( Response_Factory::class );
		$this->endpoint         = new User_Get( $this->profile_service, $this->response_factory );
	}

	public function test_get_route_returns_correct_pattern() {
		$this->assertEquals( 'user/(?P<user_id>\d+)', $this->endpoint->get_route() );
	}

	public function test_check_permission_returns_true_for_logged_in_user() {
		// Mock WordPress functions
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			function is_user_logged_in() {
				return true;
			}
		}

		if ( ! function_exists( 'current_user_can' ) ) {
			function current_user_can() {
				return true;
			}
		}

		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_param' )
			->with( 'user_id' )
			->willReturn( 1 );

		$this->assertTrue( $this->endpoint->check_permission( $request ) );
	}

	public function test_check_permission_returns_false_for_logged_out_user() {
		// Mock WordPress functions
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			function is_user_logged_in() {
				return false;
			}
		}

		$request = $this->createMock( WP_REST_Request::class );
		$this->assertFalse( $this->endpoint->check_permission( $request ) );
	}

	public function test_handle_request_returns_profile_data() {
		$profile_data = array(
			'user_id' => 1,
			'data'    => array(
				'basic' => array(
					'firstName' => 'Test',
					'lastName'  => 'User',
				),
			),
		);

		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_param' )
			->with( 'user_id' )
			->willReturn( 1 );

		$this->profile_service->method( 'get_user_profile' )
			->with( 1 )
			->willReturn( $profile_data );

		$this->response_factory->method( 'success' )
			->with( $profile_data )
			->willReturn( $profile_data );

		$response = $this->endpoint->handle_request( $request );
		$this->assertEquals( $profile_data, $response );
	}

	public function test_handle_request_returns_error_for_invalid_user() {
		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_param' )
			->with( 'user_id' )
			->willReturn( 999 );

		$error = new WP_Error( 'profile_not_found', 'Profile not found' );

		$this->profile_service->method( 'get_user_profile' )
			->with( 999 )
			->willReturn( $error );

		$this->response_factory->method( 'error' )
			->with( $error )
			->willReturn( $error );

		$response = $this->endpoint->handle_request( $request );
		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'profile_not_found', $response->get_error_code() );
	}

	public function test_get_schema_returns_valid_schema() {
		$schema = $this->endpoint->get_schema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );

		$properties = $schema['properties'];
		$this->assertArrayHasKey( 'user_id', $properties );
		$this->assertArrayHasKey( 'data', $properties );
	}
}
