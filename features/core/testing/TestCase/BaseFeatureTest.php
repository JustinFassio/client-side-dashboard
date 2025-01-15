<?php
/**
 * Base Feature Test Case
 *
 * Provides minimal shared test functionality for feature testing.
 * Features should extend this class only if they need WordPress test infrastructure.
 *
 * @package AthleteDashboard\Features\Core\Testing\TestCase
 */

namespace AthleteDashboard\Features\Core\Testing\TestCase;

use WP_UnitTestCase;
use Mockery;

/**
 * Class BaseFeatureTest
 */
abstract class BaseFeatureTest extends WP_UnitTestCase {
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
	 * @param string $contract     The contract class name.
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
} 