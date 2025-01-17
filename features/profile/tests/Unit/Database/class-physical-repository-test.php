<?php
/**
 * Physical Repository Test.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Database
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\Database;

use WP_UnitTestCase;
use WP_Error;
use AthleteDashboard\Features\Profile\Database\Physical_Repository;
use AthleteDashboard\Features\Profile\Database\Migrations\Physical_Measurements_Table;

/**
 * Class Physical_Repository_Test
 */
class Physical_Repository_Test extends WP_UnitTestCase {
	private Physical_Repository $repository;
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

		// Initialize repository
		$this->repository = new Physical_Repository();
	}

	public function tearDown(): void {
		// Clean up test data
		$this->migration->down();
		wp_delete_user( $this->test_user_id );
		parent::tearDown();
	}

	public function test_get_latest_data_returns_default_values_for_new_user(): void {
		$result = $this->repository->get_latest_data( $this->test_user_id );

		$this->assertIsArray( $result );
		$this->assertEquals( 0, $result['height'] );
		$this->assertEquals( 0, $result['weight'] );
		$this->assertNull( $result['chest'] );
		$this->assertNull( $result['waist'] );
		$this->assertNull( $result['hips'] );
		$this->assertEquals( 'cm', $result['units']['height'] );
		$this->assertEquals( 'kg', $result['units']['weight'] );
		$this->assertEquals( 'cm', $result['units']['measurements'] );
	}

	public function test_save_data_stores_and_retrieves_correctly(): void {
		$test_data = array(
			'height' => 180,
			'weight' => 75,
			'chest'  => 95,
			'waist'  => 80,
			'hips'   => 90,
			'units'  => array(
				'height'       => 'cm',
				'weight'       => 'kg',
				'measurements' => 'cm',
			),
		);

		$result = $this->repository->save_data( $this->test_user_id, $test_data );

		$this->assertIsArray( $result );
		$this->assertEquals( $test_data['height'], $result['height'] );
		$this->assertEquals( $test_data['weight'], $result['weight'] );
		$this->assertEquals( $test_data['chest'], $result['chest'] );
		$this->assertEquals( $test_data['waist'], $result['waist'] );
		$this->assertEquals( $test_data['hips'], $result['hips'] );
		$this->assertEquals( $test_data['units'], $result['units'] );
	}

	public function test_save_data_handles_imperial_units(): void {
		$test_data = array(
			'height' => 71,  // inches
			'weight' => 165, // lbs
			'chest'  => 38,   // inches
			'waist'  => 32,   // inches
			'hips'   => 36,    // inches
			'units'  => array(
				'height'       => 'ft',
				'weight'       => 'lbs',
				'measurements' => 'in',
			),
		);

		$result = $this->repository->save_data( $this->test_user_id, $test_data );

		$this->assertIsArray( $result );
		$this->assertEquals( $test_data['height'], $result['height'] );
		$this->assertEquals( $test_data['weight'], $result['weight'] );
		$this->assertEquals( $test_data['chest'], $result['chest'] );
		$this->assertEquals( $test_data['waist'], $result['waist'] );
		$this->assertEquals( $test_data['hips'], $result['hips'] );
		$this->assertEquals( $test_data['units'], $result['units'] );
	}

	public function test_get_history_returns_paginated_results(): void {
		// Insert multiple records
		$test_data = array(
			'height' => 180,
			'weight' => 75,
			'units'  => array(
				'height'       => 'cm',
				'weight'       => 'kg',
				'measurements' => 'cm',
			),
		);

		for ( $i = 0; $i < 15; $i++ ) {
			$test_data['height'] += 0.5;
			$test_data['weight'] += 0.5;
			$this->repository->save_data( $this->test_user_id, $test_data );
		}

		// Test first page
		$result = $this->repository->get_history(
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

		// Test second page
		$result = $this->repository->get_history(
			$this->test_user_id,
			array(
				'limit'  => 5,
				'offset' => 5,
			)
		);

		$this->assertIsArray( $result );
		$this->assertCount( 5, $result['items'] );
		$this->assertEquals( 15, $result['total'] );
		$this->assertEquals( 5, $result['limit'] );
		$this->assertEquals( 5, $result['offset'] );
	}

	public function test_legacy_data_migration(): void {
		// Set up legacy data
		update_user_meta( $this->test_user_id, 'user_height', '180' );
		update_user_meta( $this->test_user_id, 'user_weight', '75' );
		update_user_meta( $this->test_user_id, 'measurement_units', 'metric' );

		// Run migration
		$this->migration->migrate_legacy_data();

		// Verify migrated data
		$result = $this->repository->get_latest_data( $this->test_user_id );

		$this->assertIsArray( $result );
		$this->assertEquals( 180, $result['height'] );
		$this->assertEquals( 75, $result['weight'] );
		$this->assertEquals( 'cm', $result['units']['height'] );
		$this->assertEquals( 'kg', $result['units']['weight'] );
	}
}
