<?php
/**
 * Base test case class for Athlete Dashboard tests.
 *
 * @package AthleteDashboard\Tests\Framework
 */

namespace AthleteDashboard\Tests\Framework;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use WP_REST_Server;

/**
 * Abstract TestCase class that provides common test functionality.
 */
abstract class TestCase extends PHPUnitTestCase {
	/** @var Logger Logger instance. */
	protected $logger;

	/** @var array Test data. */
	protected $test_data;

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();

		// Initialize logger.
		$this->logger = Logger::getInstance();
		$this->logger->reset();

		// Set up test data.
		$this->test_data = array();
	}

	/**
	 * Clean up after the test.
	 */
	public function tearDown(): void {
		// Clean up test data.
		$this->test_data = array();

		// Reset logger state.
		$this->logger->reset();

		parent::tearDown();
	}

	/**
	 * Create a test user with specified capabilities.
	 *
	 * @param array $capabilities User capabilities to set.
	 * @return int User ID.
	 */
	protected function create_test_user( $capabilities ) {
		// Create user with specified capabilities.
		$user_id = wp_insert_user(
			array(
				'user_login' => 'testuser_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'subscriber',
			)
		);

		// Set user capabilities.
		foreach ( $capabilities as $capability => $value ) {
			update_user_meta( $user_id, 'wp_capabilities', array( $capability => $value ) );
		}

		return $user_id;
	}

	/**
	 * Set user meta for testing.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 */
	protected function set_user_meta( $user_id, $meta_key, $meta_value ) {
		update_user_meta( $user_id, $meta_key, $meta_value );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Error message to log.
	 */
	protected function log_error( $message ) {
		// Log error message for debugging.
		error_log( $message );
	}

	/**
	 * Set user capabilities for testing.
	 *
	 * @param array $capabilities User capabilities to set.
	 */
	protected function setUserCapabilities( $capabilities ) {
		global $current_user_capabilities;
		$current_user_capabilities = $capabilities;
	}

	/**
	 * Get error log messages.
	 *
	 * @return array Array of error log messages.
	 */
	protected function getErrorLogMessages() {
		return $this->logger->getMessages();
	}
}
