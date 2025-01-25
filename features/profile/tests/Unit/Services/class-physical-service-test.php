<?php
/**
 * Physical Service Test.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Services
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\Services;

use WP_UnitTestCase;
use WP_Error;
use AthleteDashboard\Features\Profile\Services\Physical_Service;
use AthleteDashboard\Features\Profile\Database\Migrations\Physical_Measurements_Table;

/**
 * Class Physical_Service_Test
 */
class Physical_Service_Test extends WP_UnitTestCase {
	private Physical_Service $service;
	private int $test_user_id;
	private Physical_Measurements_Table $migration;

	public function setUp(): void {
		parent::setUp();

		// Create test user
		$this->test_user_id = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		// Run migration
		$this->migration = new Physical_Measurements_Table();
		$this->migration->up();

		// Initialize service
		$this->service = new Physical_Service();
	}

	public function tearDown(): void {
		// Clean up test data
		$this->migration->down();
		wp_delete_user( $this->test_user_id );
		parent::tearDown();
	}

	public function test_get_physical_data_returns_default_values_for_new_user(): void {
		$result = $this->service->get_physical_data( $this->test_user_id );

		$this->assertIsArray( $result );
		$this->assertEquals( 0, $result['height'] );
		$this->assertEquals( 0, $result['weight'] );
		$this->assertNull( $result['chest'] );
		$this->assertNull( $result['waist'] );
		$this->assertNull( $result['hips'] );
		$this->assertEquals( 'cm', $result['units']['height'] );
		$this->assertEquals( 'kg', $result['units']['weight'] );
		$this->assertArrayHasKey( 'preferences', $result );
	}

	public function test_update_physical_data_validates_bmi(): void {
		$test_data = array(
			'height' => 180,
			'weight' => 30, // Too low for height
			'units'  => array(
				'height' => 'cm',
				'weight' => 'kg',
			),
		);

		$result = $this->service->update_physical_data( $this->test_user_id, $test_data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_bmi', $result->get_error_code() );
		$this->assertArrayHasKey( 'bmi', $result->get_error_data() );
	}

	public function test_update_physical_data_validates_measurement_ranges(): void {
		$test_data = array(
			'height' => 180,
			'weight' => 75,
			'chest'  => 250, // Too high
			'units'  => array(
				'height'       => 'cm',
				'weight'       => 'kg',
				'measurements' => 'cm',
			),
		);

		$result = $this->service->update_physical_data( $this->test_user_id, $test_data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_measurement', $result->get_error_code() );
	}

	public function test_update_physical_data_stores_and_retrieves_correctly(): void {
		$test_data = array(
			'height'      => 180,
			'weight'      => 75,
			'chest'       => 95,
			'waist'       => 80,
			'hips'        => 90,
			'units'       => array(
				'height'       => 'cm',
				'weight'       => 'kg',
				'measurements' => 'cm',
			),
			'preferences' => array(
				'showMetric' => true
			),
		);

		$result = $this->service->update_physical_data( $this->test_user_id, $test_data );

		$this->assertIsArray( $result );
		$this->assertEquals( $test_data['height'], $result['height'] );
		$this->assertEquals( $test_data['weight'], $result['weight'] );
		$this->assertEquals( $test_data['chest'], $result['chest'] );
		$this->assertEquals( $test_data['waist'], $result['waist'] );
		$this->assertEquals( $test_data['hips'], $result['hips'] );
		$this->assertEquals( $test_data['units'], $result['units'] );
		$this->assertEquals( $test_data['preferences'], $result['preferences'] );
	}

	public function test_get_physical_history_returns_paginated_results(): void {
		// Insert multiple records
		$test_data = array(
			'height' => 180,
			'weight' => 75,
			'units'  => array(
				'height'       => 'cm',
				'weight'       => 'kg',
				'measurements' => 'cm',
			),
			'preferences' => array(
				'showMetric' => true
			),
		);

		for ( $i = 0; $i < 15; $i++ ) {
			$test_data['height'] += 0.5;
			$test_data['weight'] += 0.5;
			$this->service->update_physical_data( $this->test_user_id, $test_data );
		}

		// Test first page
		$result = $this->service->get_physical_history(
			$this->test_user_id,
			array(
				'limit'  => 5,
				'offset' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertCount( 5, $result['items'] );
		$this->assertEquals( 15, $result['total'] );
		$this->assertEquals( 5, $result['limit'] );
		$this->assertEquals( 0, $result['offset'] );
	}

	public function test_caching_behavior(): void {
		// Initial data fetch
		$initial_result = $this->service->get_physical_data( $this->test_user_id );

		// Update data
		$test_data = array(
			'height' => 180,
			'weight' => 75,
			'units'  => array(
				'height' => 'cm',
				'weight' => 'kg',
			),
		);
		$this->service->update_physical_data( $this->test_user_id, $test_data );

		// Fetch again - should get new data, not cached
		$updated_result = $this->service->get_physical_data( $this->test_user_id );

		$this->assertNotEquals( $initial_result, $updated_result );
		$this->assertEquals( $test_data['height'], $updated_result['height'] );
		$this->assertEquals( $test_data['weight'], $updated_result['weight'] );
	}
}
