<?php
/**
 * Base Unit Test Case
 *
 * Provides minimal shared test functionality for feature unit testing.
 * Use this class for tests that don't require WordPress functionality.
 *
 * @package AthleteDashboard\Features\Core\Testing\TestCase\Unit
 */

namespace AthleteDashboard\Features\Core\Testing\TestCase\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Class BaseUnitTest
 */
abstract class BaseUnitTest extends TestCase {
	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Assert that a class implements a contract.
	 *
	 * @param string $contract       The contract class name.
	 * @param object $implementation The implementation to test.
	 */
	protected function assertImplementsContract( string $contract, object $implementation ): void {
		$this->assertInstanceOf(
			$contract,
			$implementation,
			sprintf(
				'Class %s must implement %s',
				get_class( $implementation ),
				$contract
			)
		);
	}

	/**
	 * Create a mock object with expectations using Mockery.
	 *
	 * @template T
	 * @param class-string<T> $class The class to mock.
	 * @return T The mock object.
	 */
	protected function createMockery( string $class ) {
		return Mockery::mock( $class );
	}
} 