<?php

namespace AthleteDashboard\Tests\Admin;

use AthleteDashboard\Admin;
use AthleteDashboard\Tests\TestCase;

/**
 * Test the user profile functionality
 */
class UserProfileTest extends TestCase {
	private $user_id;
	private $original_post;

	public function setUp(): void {
		parent::setUp();

		// Store original POST data
		$this->original_post = $_POST;

		// Reset POST data
		$_POST = array();

		// Create a test user with admin capabilities
		$this->user_id = 1;
		$this->setUserCapabilities(
			array(
				'edit_user' => true,
			)
		);

		// Reset user meta data
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}usermeta WHERE user_id = {$this->user_id}" );
	}

	public function tearDown(): void {
		// Restore original POST data
		$_POST = $this->original_post;

		// Clean up user meta data
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}usermeta WHERE user_id = {$this->user_id}" );

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

	/**
	 * Test saving with missing nonce
	 */
	public function test_missing_nonce() {
		unset( $_POST['athlete_profile_nonce'] );
		$_POST['athlete_profile'] = array(
			'phone' => '123-456-7890',
		);

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertFalse( $result );
	}

	/**
	 * Test saving with missing profile data
	 */
	public function test_missing_profile_data() {
		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		unset( $_POST['athlete_profile'] );

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertTrue( $result ); // Should succeed with empty data

		$saved_data = get_user_meta( $this->user_id, '_athlete_profile_data', true );
		$this->assertIsArray( $saved_data );
		$this->assertArrayHasKey( 'phone', $saved_data );
		$this->assertArrayHasKey( 'age', $saved_data );
		$this->assertEquals( '', $saved_data['phone'] );
		$this->assertEquals( '', $saved_data['age'] );
	}

	/**
	 * Test saving with invalid injury data
	 */
	public function test_invalid_injury_data() {
		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		$_POST['athlete_profile']       = array(
			'injuries' => 'not_an_array', // Invalid injuries data
		);

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertTrue( $result ); // Should succeed but ignore invalid injuries

		$saved_data = get_user_meta( $this->user_id, '_athlete_profile_data', true );
		$this->assertIsArray( $saved_data );
		$this->assertIsArray( $saved_data['injuries'] );
		$this->assertCount( 0, $saved_data['injuries'] );
	}

	/**
	 * Test data sanitization
	 */
	public function test_data_sanitization() {
		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		$_POST['athlete_profile']       = array(
			'phone'                  => '<script>alert("xss")</script>123-456-7890',
			'medical_notes'          => '<p>Some <script>alert("xss")</script>notes</p>',
			'emergency_contact_name' => "O'Connor <b>Name</b>",
		);

		$result = Admin\save_athlete_profile_fields( $this->user_id );
		$this->assertTrue( $result );

		$saved_data = get_user_meta( $this->user_id, '_athlete_profile_data', true );
		$this->assertIsArray( $saved_data );
		$this->assertEquals( '123-456-7890', $saved_data['phone'] );
		$this->assertEquals( 'Some notes', $saved_data['medical_notes'] );
		$this->assertEquals( "O'Connor Name", $saved_data['emergency_contact_name'] );
	}

	/**
	 * Test validation logging
	 */
	public function test_validation_logging() {
		global $test_log_messages;
		$test_log_messages = array();

		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		$_POST['athlete_profile']       = array(
			'age'    => '150', // Invalid age
			'height' => '350', // Invalid height
		);

		ob_start();
		$result   = \AthleteDashboard\Admin\save_athlete_profile_fields( $this->user_id );
		$messages = ob_get_clean();

		$this->assertFalse( $result );

		// Get logged messages using the proper method
		$log_messages = $this->getErrorLogMessages();
		$this->assertNotEmpty( $log_messages, 'Error messages should be logged' );

		// Check if validation errors were logged
		$found_error = false;
		foreach ( $log_messages as $message ) {
			if ( strpos( $message, 'Invalid age (150) for user' ) !== false ) {
				$found_error = true;
				break;
			}
		}
		$this->assertTrue( $found_error, 'Age validation error should be logged' );
	}

	/**
	 * Test sanitization monitoring
	 */
	public function test_sanitization_monitoring() {
		global $test_log_messages;
		$test_log_messages = array();

		$_POST['athlete_profile_nonce'] = wp_create_nonce( 'athlete_profile_update' );
		$_POST['athlete_profile']       = array(
			'phone'         => '(123) 456-7890', // Contains characters that should be stripped
			'medical_notes' => '<script>alert("xss")</script>Test notes', // Contains script that should be removed
		);

		ob_start();
		$result   = \AthleteDashboard\Admin\save_athlete_profile_fields( $this->user_id );
		$messages = ob_get_clean();

		$this->assertTrue( $result );

		// Get logged messages using the proper method
		$log_messages = $this->getErrorLogMessages();
		$this->assertNotEmpty( $log_messages, 'Sanitization messages should be logged' );

		// Check if sanitization changes were logged
		$found_sanitization = false;
		foreach ( $log_messages as $message ) {
			if ( strpos( $message, 'Athlete Profile Sanitization Changes for user' ) !== false ) {
				$found_sanitization = true;
				$this->assertStringContainsString( '123456-7890', $message, 'Sanitized phone number should be logged' );
				$this->assertStringContainsString( 'Test notes', $message, 'Sanitized medical notes should be logged' );
				break;
			}
		}
		$this->assertTrue( $found_sanitization, 'Sanitization changes should be logged' );
	}
}
