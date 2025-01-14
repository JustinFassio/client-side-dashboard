<?php
/**
 * Tests for Base_Validator class
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Validation
 */

namespace AthleteDashboard\Features\Profile\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use WP_Error;
use AthleteDashboard\Features\Profile\Validation\Base_Validator;

/**
 * Test cases for Base_Validator class
 */
class BaseValidatorTest extends TestCase {
	/**
	 * @var Base_Validator
	 */
	private $validator;

	protected function setUp(): void {
		// Create a concrete implementation of Base_Validator for testing
		$this->validator = new class() extends Base_Validator {
			protected function get_debug_tag(): string {
				return 'validator.test';
			}

			// Expose protected methods for testing
			public function test_validate_array_input( array $data ): bool|WP_Error {
				return $this->validate_array_input( $data );
			}

			public function test_create_error( string $code, string $message, array $data = array() ): WP_Error {
				return $this->create_error( $code, $message, $data );
			}
		};
	}

	public function test_validate_array_input_with_valid_array(): void {
		$result = $this->validator->test_validate_array_input( array( 'test' => 'data' ) );
		$this->assertTrue( $result );
	}

	public function test_validate_array_input_with_empty_array(): void {
		$result = $this->validator->test_validate_array_input( array() );
		$this->assertTrue( $result );
	}

	public function test_create_error_returns_wp_error(): void {
		$result = $this->validator->test_create_error( 'test_code', 'Test message' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'test_code', $result->get_error_code() );
		$this->assertEquals( 'Test message', $result->get_error_message() );
	}

	public function test_create_error_includes_status_in_data(): void {
		$result = $this->validator->test_create_error( 'test_code', 'Test message' );
		$data   = $result->get_error_data();

		$this->assertArrayHasKey( 'status', $data );
		$this->assertEquals( 400, $data['status'] );
	}

	public function test_create_error_merges_additional_data(): void {
		$additional_data = array( 'key' => 'value' );
		$result          = $this->validator->test_create_error( 'test_code', 'Test message', $additional_data );
		$data            = $result->get_error_data();

		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'key', $data );
		$this->assertEquals( 'value', $data['key'] );
	}

	public function test_get_debug_tag_returns_expected_value(): void {
		$reflection = new \ReflectionClass( $this->validator );
		$method     = $reflection->getMethod( 'get_debug_tag' );
		$method->setAccessible( true );

		$this->assertEquals( 'validator.test', $method->invoke( $this->validator ) );
	}
}
