<?php
/**
 * Physical Service Test Case
 *
 * @package AthleteDashboard\Tests
 */

namespace AthleteDashboard\Tests;

use AthleteDashboard\Features\Profile\Services\Physical_Service;
use WP_Error;

class Test_Physical_Service extends AD_UnitTestCase {
	private $service;
	private $user_id = 1;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new Physical_Service();
	}

	public function test_get_physical_data_returns_cached_data() {
		// Setup
		$cache_key   = "physical_data_{$this->user_id}";
		$cached_data = array(
			'height' => 180,
			'weight' => 75,
			'units'  => array(
				'height' => 'cm',
				'weight' => 'kg',
			),
		);

		WP_Mock::userFunction(
			'wp_cache_get',
			array(
				'args'   => array( $cache_key, Physical_Service::CACHE_GROUP ),
				'return' => $cached_data,
				'times'  => 1,
			)
		);

		// Execute
		$result = $this->service->get_physical_data( $this->user_id );

		// Verify
		$this->assertEquals( $cached_data, $result );
	}

	public function test_get_physical_data_fetches_from_meta() {
		// Setup
		$cache_key = "physical_data_{$this->user_id}";

		WP_Mock::userFunction(
			'wp_cache_get',
			array(
				'args'   => array( $cache_key, Physical_Service::CACHE_GROUP ),
				'return' => false,
			)
		);

		WP_Mock::userFunction(
			'get_user_meta',
			array(
				'return_map' => array(
					array( 'physical_height', true, 180 ),
					array( 'physical_weight', true, 75 ),
					array(
						'physical_units',
						true,
						array(
							'height' => 'cm',
							'weight' => 'kg',
						),
					),
				),
			)
		);

		WP_Mock::userFunction(
			'wp_cache_set',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		// Execute
		$result = $this->service->get_physical_data( $this->user_id );

		// Verify
		$this->assertEquals( 180, $result['height'] );
		$this->assertEquals( 75, $result['weight'] );
		$this->assertEquals( 'cm', $result['units']['height'] );
		$this->assertEquals( 'kg', $result['units']['weight'] );
	}

	public function test_update_physical_data_validates_required_fields() {
		// Setup
		$data = array( 'units' => array( 'height' => 'cm' ) ); // Missing height and weight

		// Execute
		$result = $this->service->update_physical_data( $this->user_id, $data );

		// Verify
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'missing_data', $result->get_error_code() );
	}

	public function test_update_physical_data_validates_height_range() {
		// Setup
		$data = array(
			'height' => 400, // Too tall
			'weight' => 75,
			'units'  => array(
				'height' => 'cm',
				'weight' => 'kg',
			),
		);

		// Execute
		$result = $this->service->update_physical_data( $this->user_id, $data );

		// Verify
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_height', $result->get_error_code() );
	}

	public function test_update_physical_data_validates_bmi_range() {
		// Setup
		$data = array(
			'height' => 180,
			'weight' => 30, // Too light for height (BMI < 15)
			'units'  => array(
				'height' => 'cm',
				'weight' => 'kg',
			),
		);

		// Execute
		$result = $this->service->update_physical_data( $this->user_id, $data );

		// Verify
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_bmi', $result->get_error_code() );
	}

	public function test_update_physical_data_converts_imperial_to_metric() {
		// Setup
		$data = array(
			'heightFeet'   => 6,
			'heightInches' => 0,
			'weight'       => 165,
			'units'        => array(
				'height' => 'ft',
				'weight' => 'lbs',
			),
		);

		WP_Mock::userFunction(
			'update_user_meta',
			array(
				'return' => true,
				'times'  => '4+',
			)
		);

		WP_Mock::userFunction(
			'wp_cache_delete',
			array(
				'times' => 1,
			)
		);

		// Execute
		$result = $this->service->update_physical_data( $this->user_id, $data );

		// Verify
		$this->assertIsArray( $result );
		$this->assertEqualsWithDelta( 183, $result['height'], 1 ); // 6ft ≈ 183cm
		$this->assertEqualsWithDelta( 75, $result['weight'], 1 ); // 165lbs ≈ 75kg
	}

	public function test_save_to_history_creates_record() {
		// Setup
		global $wpdb;
		$table_name = $wpdb->prefix . 'athlete_physical_measurements';

		$wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( $table_name ),
				$this->callback(
					function ( $data ) {
						return isset( $data['user_id'] ) &&
							isset( $data['height'] ) &&
							isset( $data['weight'] ) &&
							isset( $data['date'] );
					}
				)
			)
			->willReturn( 1 );

		$data = array(
			'height' => 180,
			'weight' => 75,
			'units'  => array(
				'height' => 'cm',
				'weight' => 'kg',
			),
		);

		// Execute
		$result = $this->service->save_to_history( $this->user_id, $data );

		// Verify
		$this->assertTrue( $result );
	}
}
