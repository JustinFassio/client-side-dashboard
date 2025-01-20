<?php
/**
 * Profile Validator Test Case
 *
 * @package AthleteDashboard\Tests
 */

namespace AthleteDashboard\Tests;

use AthleteDashboard\Features\Profile\Validation\Profile_Validator;
use WP_Error;

class Test_Profile_Validator extends AD_UnitTestCase {
	private $validator;

	protected function setUp(): void {
		parent::setUp();
		$this->validator = new Profile_Validator();
	}

	public function test_validate_physical_measurements_with_valid_data() {
		// Setup
		$data = array(
			'heightCm' => 180,
			'weightKg' => 75,
		);

		// Execute
		$result = $this->validator->validate_physical_measurements( $data );

		// Verify
		$this->assertTrue( $result['is_valid'] );
		$this->assertEmpty( $result['errors'] );
		$this->assertEquals( $data['heightCm'], $result['sanitized_data']['heightCm'] );
		$this->assertEquals( $data['weightKg'], $result['sanitized_data']['weightKg'] );
	}

	public function test_validate_physical_measurements_with_invalid_height() {
		// Setup
		$data = array(
			'heightCm' => 400, // Too tall
			'weightKg' => 75,
		);

		// Execute
		$result = $this->validator->validate_physical_measurements( $data );

		// Verify
		$this->assertFalse( $result['is_valid'] );
		$this->assertArrayHasKey( 'heightCm', $result['errors'] );
	}

	public function test_validate_physical_measurements_with_invalid_weight() {
		// Setup
		$data = array(
			'heightCm' => 180,
			'weightKg' => 300, // Too heavy
		);

		// Execute
		$result = $this->validator->validate_physical_measurements( $data );

		// Verify
		$this->assertFalse( $result['is_valid'] );
		$this->assertArrayHasKey( 'weightKg', $result['errors'] );
	}

	public function test_validate_physical_measurements_with_missing_data() {
		// Setup
		$data = array(
			'heightCm' => 180,
			// Missing weight
		);

		// Execute
		$result = $this->validator->validate_physical_measurements( $data );

		// Verify
		$this->assertFalse( $result['is_valid'] );
		$this->assertArrayHasKey( 'weightKg', $result['errors'] );
	}

	public function test_validate_physical_measurements_with_non_numeric_values() {
		// Setup
		$data = array(
			'heightCm' => 'tall',
			'weightKg' => 'heavy',
		);

		// Execute
		$result = $this->validator->validate_physical_measurements( $data );

		// Verify
		$this->assertFalse( $result['is_valid'] );
		$this->assertArrayHasKey( 'heightCm', $result['errors'] );
		$this->assertArrayHasKey( 'weightKg', $result['errors'] );
	}

	public function test_validate_experience_level() {
		// Setup
		$valid_levels = array( 'beginner', 'intermediate', 'advanced' );

		foreach ( $valid_levels as $level ) {
			$data = array( 'experienceLevel' => $level );

			// Execute
			$result = $this->validator->validate_profile( $data );

			// Verify
			$this->assertTrue( $result );
		}

		// Test invalid level
		$data   = array( 'experienceLevel' => 'expert' );
		$result = $this->validator->validate_profile( $data );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_experience_level', $result->get_error_code() );
	}

	public function test_validate_height_range() {
		// Setup
		$test_cases = array(
			array(
				'height'   => 50,
				'expected' => false,
			),  // Too short
			array(
				'height'   => 150,
				'expected' => true,
			),  // Valid
			array(
				'height'   => 200,
				'expected' => true,
			),  // Valid
			array(
				'height'   => 300,
				'expected' => false,
			), // Too tall
		);

		foreach ( $test_cases as $case ) {
			// Execute
			$result = $this->validator->is_valid_height( $case['height'] );

			// Verify
			$this->assertEquals(
				$case['expected'],
				$result,
				"Height {$case['height']} validation failed"
			);
		}
	}

	public function test_validate_weight_range() {
		// Setup
		$test_cases = array(
			array(
				'weight'   => 20,
				'expected' => false,
			),  // Too light
			array(
				'weight'   => 50,
				'expected' => true,
			),   // Valid
			array(
				'weight'   => 100,
				'expected' => true,
			),  // Valid
			array(
				'weight'   => 200,
				'expected' => false,
			), // Too heavy
		);

		foreach ( $test_cases as $case ) {
			// Execute
			$result = $this->validator->is_valid_weight( $case['weight'] );

			// Verify
			$this->assertEquals(
				$case['expected'],
				$result,
				"Weight {$case['weight']} validation failed"
			);
		}
	}
}
