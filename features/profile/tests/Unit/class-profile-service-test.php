<?php
/**
 * Profile service test class.
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit;

use AthleteDashboard\Features\Profile\Profile_Service;
use WP_UnitTestCase;
use WP_Error;

/**
 * Test profile service functionality.
 */
class Profile_Service_Test extends WP_UnitTestCase {
	/**
	 * Profile service instance.
	 *
	 * @var Profile_Service
	 */
	private $service;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test user
		$this->user_id = $this->factory->user->create();
		$this->service = new Profile_Service();
	}

	/**
	 * Clean up test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up test user
		wp_delete_user( $this->user_id );
	}

	/**
	 * Test getting profile data.
	 */
	public function test_get_profile_data(): void {
		// Test with no data
		$data = $this->service->get_profile_data( $this->user_id );
		$this->assertNotWPError( $data );
		$this->assertArrayHasKey( 'gender', $data );
		$this->assertArrayHasKey( 'dominantSide', $data );

		// Add test data
		$test_data = array(
			'gender'            => 'male',
			'dominant_side'     => 'right',
			'medical_clearance' => true,
			'medical_notes'     => 'Test notes',
		);

		update_user_meta( $this->user_id, 'profile_data', $test_data );

		// Test with data
		$data = $this->service->get_profile_data( $this->user_id );
		$this->assertNotWPError( $data );
		$this->assertEquals( 'male', $data['gender'] );
		$this->assertEquals( 'right', $data['dominantSide'] );
		$this->assertTrue( $data['medicalClearance'] );
		$this->assertEquals( 'Test notes', $data['medicalNotes'] );
	}

	/**
	 * Test updating profile data.
	 */
	public function test_update_profile_data(): void {
		$profile_data = array(
			'gender'                  => 'female',
			'dominant_side'           => 'left',
			'medical_clearance'       => true,
			'medical_notes'           => 'Updated notes',
			'emergency_contact_name'  => 'John Doe',
			'emergency_contact_phone' => '123-456-7890',
		);

		$result = $this->service->update_profile_data( $this->user_id, $profile_data );
		$this->assertNotWPError( $result );
		$this->assertEquals( 'female', $result['gender'] );
		$this->assertEquals( 'left', $result['dominantSide'] );
		$this->assertTrue( $result['medicalClearance'] );
		$this->assertEquals( 'Updated notes', $result['medicalNotes'] );
		$this->assertEquals( 'John Doe', $result['emergencyContactName'] );
		$this->assertEquals( '123-456-7890', $result['emergencyContactPhone'] );
	}

	/**
	 * Test validation failures.
	 */
	public function test_validation_failures(): void {
		// Test invalid gender
		$invalid_gender = array(
			'gender' => 'invalid',
		);
		$result         = $this->service->update_profile_data( $this->user_id, $invalid_gender );
		$this->assertWPError( $result );
		$this->assertEquals( 'validation_error', $result->get_error_code() );

		// Test invalid dominant side
		$invalid_side = array(
			'dominant_side' => 'invalid',
		);
		$result       = $this->service->update_profile_data( $this->user_id, $invalid_side );
		$this->assertWPError( $result );
		$this->assertEquals( 'validation_error', $result->get_error_code() );

		// Test invalid phone number
		$invalid_phone = array(
			'emergency_contact_phone' => 'invalid',
		);
		$result        = $this->service->update_profile_data( $this->user_id, $invalid_phone );
		$this->assertWPError( $result );
		$this->assertEquals( 'validation_error', $result->get_error_code() );
	}
}
