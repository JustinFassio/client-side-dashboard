<?php
/**
 * Tests for Profile_Validator class
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Validation
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use WP_Error;
use AthleteDashboard\Features\Profile\Validation\Profile_Validator;

/**
 * Test class for Profile_Validator
 *
 * Tests validation and sanitization of profile data including:
 * - Basic field validation (email, age, gender, etc.)
 * - Cross-field validation (BMI, age-based restrictions)
 * - Data sanitization
 * - Medical conditions and health data
 * - Unit conversions
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Validation
 */
class ProfileValidatorTest extends TestCase {
	/**
	 * @var Profile_Validator The validator instance being tested
	 */
	private $validator;

	/**
	 * Set up the test environment
	 * Creates a new instance of Profile_Validator before each test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->validator = new Profile_Validator();
	}

	/**
	 * Test validation with complete valid data
	 * Verifies that a complete profile with all fields within acceptable ranges passes validation
	 */
	public function test_validate_data_with_valid_data(): void {
		$data = array(
			'email'          => 'test@example.com',
			'units'          => 'metric',
			'fitness_level'  => 'intermediate',
			'activity_level' => 'moderate',
			'gender'         => 'prefer_not_to_say',
		);

		$result = $this->validator->validate_data( $data );
		$this->assertTrue( $result );
	}

	/**
	 * Test validation with valid cross-field data
	 * Verifies that data passing all cross-field validations (BMI, age restrictions) is accepted
	 */
	public function test_validate_data_with_valid_cross_field_data(): void {
		$data = array(
			'height'         => 170,    // 170 cm
			'weight'         => 70,     // 70 kg
			'age'            => 25,
			'fitness_level'  => 'advanced',
			'activity_level' => 'very_active',
			'units'          => 'metric',
		);

		$result = $this->validator->validate_data( $data );
		$this->assertTrue( $result );
	}

	/**
	 * Test validation with invalid BMI
	 * Verifies that the validator correctly identifies and reports invalid BMI values
	 * Assumes WordPress WP_Error class behavior for error reporting
	 */
	public function test_validate_data_with_invalid_bmi(): void {
		$data = array(
			'height' => 170,    // 170 cm
			'weight' => 30,     // 30 kg (too low for height)
			'units'  => 'metric',
		);

		$result = $this->validator->validate_data( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_bmi', $result->get_error_code() );
	}

	/**
	 * Test BMI calculation with imperial units
	 * Verifies correct unit conversion and BMI calculation for imperial measurements
	 * Tests the integration between unit conversion and BMI validation
	 */
	public function test_validate_data_with_imperial_units_bmi(): void {
		$data = array(
			'height' => 67,     // 67 inches (170 cm)
			'weight' => 154,    // 154 lbs (70 kg)
			'units'  => 'imperial',
		);

		$result = $this->validator->validate_data( $data );
		$this->assertTrue( $result );
	}

	/**
	 * Test age restrictions for advanced fitness level
	 * Verifies that underage users cannot select advanced fitness levels
	 * Assumes business rule that advanced fitness requires minimum age
	 */
	public function test_validate_data_with_underage_advanced_fitness(): void {
		$data = array(
			'age'           => 15,
			'fitness_level' => 'advanced',
		);

		$result = $this->validator->validate_data( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'age_restriction', $result->get_error_code() );
	}

	/**
	 * Test activity level restrictions for senior users
	 * Verifies that senior users cannot select high intensity activity levels
	 * Assumes business rule that seniors should avoid high intensity activities
	 */
	public function test_validate_data_with_senior_high_activity(): void {
		$data = array(
			'age'            => 65,
			'activity_level' => 'very_active',
		);

		$result = $this->validator->validate_data( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'age_restriction', $result->get_error_code() );
	}

	/**
	 * Test validation with valid senior activity level
	 * Verifies that appropriate activity levels are allowed for senior users
	 */
	public function test_validate_data_with_valid_senior_activity(): void {
		$data = array(
			'age'            => 65,
			'activity_level' => 'moderate',
		);

		$result = $this->validator->validate_data( $data );
		$this->assertTrue( $result );
	}

	/**
	 * Test validation with missing optional fields
	 * Verifies that the validator accepts data with missing optional fields
	 * Assumes certain fields are optional based on business requirements
	 */
	public function test_validate_data_with_missing_optional_fields(): void {
		$data = array(
			'email' => 'test@example.com',
			// Missing height, weight, age, etc.
		);

		$result = $this->validator->validate_data( $data );
		$this->assertTrue( $result );
	}

	/**
	 * Data provider for BMI validation tests
	 * Provides test cases for various BMI scenarios including:
	 * - Valid metric measurements
	 * - Valid imperial measurements
	 * - Invalid low BMI
	 * - Invalid high BMI
	 *
	 * @return array Test cases for BMI validation
	 */
	public function bmi_data_provider(): array {
		return array(
			'valid_metric'     => array(
				array(
					'height' => 170,
					'weight' => 70,
					'units'  => 'metric',
				),
				true,
			),
			'valid_imperial'   => array(
				array(
					'height' => 67,
					'weight' => 154,
					'units'  => 'imperial',
				),
				true,
			),
			'invalid_low_bmi'  => array(
				array(
					'height' => 170,
					'weight' => 30,
					'units'  => 'metric',
				),
				false,
			),
			'invalid_high_bmi' => array(
				array(
					'height' => 170,
					'weight' => 150,
					'units'  => 'metric',
				),
				false,
			),
		);
	}

	/**
	 * @dataProvider bmi_data_provider
	 */
	public function test_validate_bmi_with_data_provider( array $data, bool $expected ): void {
		$result = $this->validator->validate_data( $data );

		if ( $expected ) {
			$this->assertTrue( $result );
		} else {
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertEquals( 'invalid_bmi', $result->get_error_code() );
		}
	}

	public function test_validate_email_with_valid_email(): void {
		$data   = array( 'email' => 'test@example.com' );
		$result = $this->validator->validate_email( $data );
		$this->assertTrue( $result );
	}

	public function test_validate_email_with_missing_email(): void {
		$data   = array();
		$result = $this->validator->validate_email( $data );
		$this->assertTrue( $result ); // Email is optional in profile
	}

	public function test_validate_preferences_with_valid_data(): void {
		$data   = array(
			'units'          => 'metric',
			'fitness_level'  => 'intermediate',
			'activity_level' => 'moderate',
		);
		$result = $this->validator->validate_preferences( $data );
		$this->assertTrue( $result );
	}

	public function test_validate_preferences_with_invalid_units(): void {
		$data   = array( 'units' => 'invalid_unit' );
		$result = $this->validator->validate_preferences( $data );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_demographics_with_valid_data(): void {
		$data   = array(
			'gender' => 'prefer_not_to_say',
			'age'    => 25,
		);
		$result = $this->validator->validate_demographics( $data );
		$this->assertTrue( $result );
	}

	public function test_validate_demographics_with_invalid_age(): void {
		$data   = array( 'age' => 200 ); // Age too high
		$result = $this->validator->validate_demographics( $data );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_physical_metrics_with_valid_data(): void {
		$data   = array(
			'height' => 170,
			'weight' => 70,
			'units'  => 'metric',
		);
		$result = $this->validator->validate_physical_metrics( $data );
		$this->assertTrue( $result );
	}

	public function test_validate_physical_metrics_with_invalid_height(): void {
		$data   = array(
			'height' => 50, // Height too low
			'units'  => 'metric',
		);
		$result = $this->validator->validate_physical_metrics( $data );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Make sanitize_profile_data accessible for testing
	 */
	private function sanitize_test_data( array $data ): array {
		$reflection = new \ReflectionClass( $this->validator );
		$method     = $reflection->getMethod( 'sanitize_profile_data' );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->validator, array( $data ) );
	}

	public function test_sanitize_profile_data_with_basic_strings(): void {
		$input = array(
			'email'     => ' test@example.com ',  // Extra whitespace
			'firstName' => "John\n",          // Newline
			'lastName'  => "\tDoe",            // Tab
		);

		$sanitized = $this->sanitize_test_data( $input );

		$this->assertEquals( 'test@example.com', $sanitized['email'] );
		$this->assertEquals( 'John', $sanitized['firstName'] );
		$this->assertEquals( 'Doe', $sanitized['lastName'] );
	}

	public function test_sanitize_profile_data_with_numeric_values(): void {
		$data = array(
			'height' => ' 180.5 ',
			'weight' => ' 75.5 ',
			'age'    => ' 25 ',
		);

		$sanitized = $this->sanitize_test_data( $data );

		$this->assertEquals( 180.5, $sanitized['height'] );
		$this->assertEquals( 75.5, $sanitized['weight'] );
		$this->assertEquals( 25, $sanitized['age'] );
	}

	public function test_sanitize_profile_data_with_mixed_types(): void {
		$input = array(
			'string_value' => 'test',
			'int_value'    => 42,
			'float_value'  => 3.14,
			'array_value'  => array( 'nested' => 'value' ),
			'bool_value'   => true,
		);

		$sanitized = $this->sanitize_test_data( $input );

		$this->assertEquals( 'test', $sanitized['string_value'] );
		$this->assertEquals( 42, $sanitized['int_value'] );
		$this->assertEquals( 3.14, $sanitized['float_value'] );
		$this->assertEquals( array( 'nested' => 'value' ), $sanitized['array_value'] );
		$this->assertTrue( $sanitized['bool_value'] );
	}

	public function test_sanitize_profile_data_with_special_chars(): void {
		$input = array(
			'html_string'   => '<p>Test</p>',
			'special_chars' => '& " \' < >',
			'script_tag'    => '<script>alert("xss")</script>',
			'multi_byte'    => '🏋️‍♂️ Workout!',
		);

		$sanitized = $this->sanitize_test_data( $input );

		$this->assertEquals( '&lt;p&gt;Test&lt;/p&gt;', $sanitized['html_string'] );
		$this->assertEquals( '&amp; &quot; &#039; &lt; &gt;', $sanitized['special_chars'] );
		$this->assertEquals( '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $sanitized['script_tag'] );
		$this->assertEquals( '🏋️‍♂️ Workout!', $sanitized['multi_byte'] ); // Multi-byte chars should be preserved
	}

	public function test_sanitize_profile_data_edge_cases(): void {
		$input = array(
			'empty_string'    => '',
			'null_value'      => null,
			'zero'            => 0,
			'false_value'     => false,
			'long_string'     => str_repeat( 'a', 1000 ),
			'whitespace_only' => "  \t\n\r  ",
		);

		$sanitized = $this->sanitize_test_data( $input );

		$this->assertEquals( '', $sanitized['empty_string'] );
		$this->assertNull( $sanitized['null_value'] );
		$this->assertEquals( 0, $sanitized['zero'] );
		$this->assertFalse( $sanitized['false_value'] );
		$this->assertEquals( str_repeat( 'a', 1000 ), $sanitized['long_string'] );
		$this->assertEquals( '', $sanitized['whitespace_only'] );
	}

	/**
	 * Data provider for sanitization tests
	 */
	public function sanitization_data_provider(): array {
		return array(
			'basic_strings'  => array(
				array(
					'input'    => array( 'name' => ' Test ' ),
					'expected' => array( 'name' => 'Test' ),
				),
			),
			'numeric_values' => array(
				array(
					'input'    => array( 'value' => ' 123.45 ' ),
					'expected' => array( 'value' => 123.45 ),
				),
			),
			'special_chars'  => array(
				array(
					'input'    => array( 'text' => '<p>Test & Demo</p>' ),
					'expected' => array( 'text' => '&lt;p&gt;Test &amp; Demo&lt;/p&gt;' ),
				),
			),
		);
	}

	/**
	 * @dataProvider sanitization_data_provider
	 */
	public function test_sanitize_profile_data_with_provider( array $testCase ): void {
		$sanitized = $this->sanitize_test_data( $testCase['input'] );
		$this->assertEquals( $testCase['expected'], $sanitized );
	}

	public function test_validate_data_applies_sanitization(): void {
		$data = array(
			'height'         => ' 180.5 ',
			'weight'         => ' 75.5 ',
			'age'            => ' 25 ',
			'email'          => ' test@example.com ',
			'gender'         => 'male',
			'fitness_level'  => 'beginner',
			'activity_level' => 'moderate',
			'units'          => 'metric',
		);

		$result = $this->validator->validate_data( $data );
		$this->assertTrue( $result === true );
	}

	/**
	 * Test validation of medical conditions with valid data
	 */
	public function test_validate_medical_conditions_with_valid_data(): void {
		$data = array(
			'medical_conditions' => array(
				array(
					'type'        => 'asthma',
					'description' => 'Mild asthma, controlled with inhaler',
				),
				array(
					'type'        => 'allergy',
					'description' => 'Peanut allergy',
				),
			),
		);

		$result = $this->validator->validate_medical_conditions( $data );
		$this->assertTrue( $result );
	}

	/**
	 * Test validation of medical conditions with invalid format
	 */
	public function test_validate_medical_conditions_with_invalid_format(): void {
		$data = array(
			'medical_conditions' => 'not an array',
		);

		$result = $this->validator->validate_medical_conditions( $data );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_medical_conditions', $result->get_error_code() );
	}

	/**
	 * Test validation of medical conditions with invalid types
	 */
	public function test_validate_medical_conditions_with_invalid_types(): void {
		$data = array(
			'medical_conditions' => array(
				array(
					'description' => 'Missing type field',
				),
			),
		);

		$result = $this->validator->validate_medical_conditions( $data );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_medical_condition_type', $result->get_error_code() );
	}

	/**
	 * Test validation of medical conditions with long description
	 */
	public function test_validate_medical_conditions_with_long_description(): void {
		$data = array(
			'medical_conditions' => array(
				array(
					'type'        => 'condition',
					'description' => str_repeat( 'a', 1001 ), // Create a string longer than 1000 chars
				),
			),
		);

		$result = $this->validator->validate_medical_conditions( $data );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_length', $result->get_error_code() );
	}

	/**
	 * Data provider for unit conversion tests
	 * Tests both metric to imperial and imperial to metric conversions
	 *
	 * @return array Test cases for unit conversions
	 */
	public function unit_conversion_data_provider(): array {
		return array(
			'metric_to_imperial_height' => array(
				array(
					'height' => 180,
					'weight' => 80,
					'units'  => 'metric',
				),
				array(
					'height' => 70.87, // 180cm in inches
					'weight' => 176.37, // 80kg in lbs
					'units'  => 'imperial',
				),
			),
			'imperial_to_metric_height' => array(
				array(
					'height' => 72,
					'weight' => 180,
					'units'  => 'imperial',
				),
				array(
					'height' => 182.88, // 72 inches in cm
					'weight' => 81.65, // 180lbs in kg
					'units'  => 'metric',
				),
			),
		);
	}

	/**
	 * @dataProvider unit_conversion_data_provider
	 */
	public function test_unit_conversions( array $input, array $expected ): void {
		$target_units = $expected['units'];
		$result       = $this->validator->convert_units( $input, $target_units );

		$this->assertEquals( $expected['height'], round( $result['height'], 2 ) );
		$this->assertEquals( $expected['weight'], round( $result['weight'], 2 ) );
		$this->assertEquals( $expected['units'], $result['units'] );
	}

	/**
	 * Test BMI validation edge cases with detailed error messages
	 */
	public function test_validate_bmi_edge_cases_with_error_messages(): void {
		$data = array(
			'height' => 170,    // 170 cm
			'weight' => 30,     // 30 kg (too low for height)
			'units'  => 'metric',
		);

		$result = $this->validator->validate_data( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'invalid_bmi', $result->get_error_code() );

		$error_data = $result->get_error_data();
		$this->assertArrayHasKey( 'bmi', $error_data );
		$this->assertArrayHasKey( 'min_bmi', $error_data );
		$this->assertArrayHasKey( 'max_bmi', $error_data );
	}
}
