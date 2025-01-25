<?php

namespace AthleteDashboard\Tests\Admin;

use AthleteDashboard\Admin;
use AthleteDashboard\Tests\TestCase;

/**
 * Test the user profile functionality
 */
class Test_User_Profile extends TestCase {
	private $user_id;

	public function setUp(): void {
		parent::setUp();

		// Create a test user with admin capabilities
		$this->user_id = 1;
		$this->setUserCapabilities(
			array(
				'edit_user' => true,
			)
		);
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test saving valid profile data
	 */
	public function test_save_valid_profile_data() {
		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		$_POST['athlete_profile']       = array(
			'phone'                   => '123-456-7890',
			'age'                     => '25',
			'date_of_birth'           => '1998-01-01',
			'height'                  => '180',
			'weight'                  => '75.5',
			'gender'                  => 'male',
			'dominant_side'           => 'right',
			'medical_clearance'       => '1',
			'medical_notes'           => 'No issues',
			'emergency_contact_name'  => 'John Doe',
			'emergency_contact_phone' => '098-765-4321',
			'injuries'                => array(
				array(
					'id'      => '1',
					'name'    => 'Sprained Ankle',
					'details' => 'Recovered',
				),
			),
		);

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertTrue( $result );

		$saved_data = get_user_meta( $this->user_id, '_athlete_profile_data', true );
		$this->assertIsArray( $saved_data );
		$this->assertEquals( '123-456-7890', $saved_data['phone'] );
		$this->assertEquals( 25, $saved_data['age'] );
		$this->assertEquals( '180', $saved_data['height'] );
		$this->assertEquals( '75.5', $saved_data['weight'] );
	}

	/**
	 * Test saving invalid age
	 */
	public function test_save_invalid_age() {
		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		$_POST['athlete_profile']       = array(
			'age' => '150', // Invalid age
		);

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertFalse( $result );
	}

	/**
	 * Test saving invalid height
	 */
	public function test_save_invalid_height() {
		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		$_POST['athlete_profile']       = array(
			'height' => '350', // Invalid height
		);

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertFalse( $result );
	}

	/**
	 * Test CSRF protection
	 */
	public function test_csrf_protection() {
		$_POST['athlete_profile_nonce'] = 'invalid_nonce';
		$_POST['athlete_profile']       = array(
			'phone' => '123-456-7890',
		);

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertFalse( $result );
	}

	/**
	 * Test unauthorized user
	 */
	public function test_unauthorized_user() {
		$this->setUserCapabilities(
			array(
				'edit_user' => false,
			)
		);

		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		$_POST['athlete_profile']       = array(
			'phone' => '123-456-7890',
		);

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertFalse( $result );
	}
}
