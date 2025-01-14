<?php
/**
 * Tests for Profile_Validator class.
 *
 * @package AthleteDashboard\Tests\Features\Profile
 */

namespace AthleteDashboard\Tests\Features\Profile;

use AthleteDashboard\Features\Profile\Validation\Profile_Validator;
use WP_Error;
use WP_UnitTestCase;

/**
 * Class Test_Profile_Validator
 */
class Test_Profile_Validator extends WP_UnitTestCase {

	/**
	 * The validator instance.
	 *
	 * @var Profile_Validator
	 */
	private Profile_Validator $validator;

	/**
	 * Set up each test.
	 */
	public function set_up() {
		parent::set_up();
		$this->validator = new Profile_Validator();
	}

	/**
	 * Test that non-array data is rejected.
	 */
	public function test_non_array_data_is_rejected() {
		$result = $this->validator->validate_profile_data( 'not an array' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_data_format', $result->get_error_code() );
		$this->assertEquals( 400, $result->get_error_data()['status'] );
	}

	/**
	 * Test that invalid email is rejected.
	 */
	public function test_invalid_email_is_rejected() {
		$data = array(
			'email' => 'not-an-email',
		);

		$result = $this->validator->validate_profile_data( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'validation_failed', $result->get_error_code() );
		$this->assertArrayHasKey( 'email', $result->get_error_data()['errors'] );
	}

	/**
	 * Test that valid data passes validation.
	 */
	public function test_valid_data_passes_validation() {
		$data = array(
			'email' => 'test@example.com',
		);

		$result = $this->validator->validate_profile_data( $data );

		$this->assertTrue( $result );
	}
}
