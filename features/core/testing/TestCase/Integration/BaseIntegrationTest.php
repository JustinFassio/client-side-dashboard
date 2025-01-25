<?php
/**
 * Base Integration Test Case
 *
 * Provides shared test functionality for feature integration testing.
 * Use this class for tests that require WordPress functionality.
 *
 * @package AthleteDashboard\Features\Core\Testing\TestCase\Integration
 */

namespace AthleteDashboard\Features\Core\Testing\TestCase\Integration;

use WP_UnitTestCase;
use Mockery;

/**
 * Class BaseIntegrationTest
 */
abstract class BaseIntegrationTest extends WP_UnitTestCase {
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

	/**
	 * Create a test user with given role.
	 *
	 * @param string $role The WordPress role to assign.
	 * @return int The user ID.
	 */
	protected function createTestUser( string $role = 'subscriber' ): int {
		return self::factory()->user->create( array( 'role' => $role ) );
	}

	/**
	 * Create a test post.
	 *
	 * @param array $args Post creation arguments.
	 * @return int The post ID.
	 */
	protected function createTestPost( array $args = array() ): int {
		return self::factory()->post->create( $args );
	}
} 