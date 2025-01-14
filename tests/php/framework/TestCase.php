<?php
/**
 * Base test case class for Athlete Dashboard tests.
 *
 * @package AthleteDashboard\Tests\Framework
 */

namespace AthleteDashboard\Tests\Framework;

use WP_UnitTestCase;
use WP_REST_Server;
use WP_Error;

/**
 * Abstract TestCase class that provides common test functionality.
 */
abstract class TestCase extends WP_UnitTestCase {
	/** @var Logger Logger instance. */
	protected $logger;

	/** @var array Test data. */
	protected $test_data;

	/** @var WP_REST_Server REST server instance. */
	protected $server;

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();

		// Initialize logger.
		$this->logger = Logger::getInstance();
		$this->logger->clear();

		// Set up test data.
		$this->test_data = array();

		// Initialize REST server.
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Clean up after the test.
	 */
	public function tearDown(): void {
		// Clean up test data.
		$this->test_data = array();

		// Reset logger state.
		$this->logger->clear();

		// Clean up REST server.
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Create a test user with specified capabilities.
	 *
	 * @param array $capabilities User capabilities to set.
	 * @return int User ID.
	 */
	protected function create_test_user( $capabilities = array() ) {
		// Create user with specified capabilities.
		$user_id = self::factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		if ( ! empty( $capabilities ) ) {
			$user = get_user_by( 'id', $user_id );
			foreach ( $capabilities as $capability => $value ) {
				$user->add_cap( $capability, $value );
			}
		}

		return $user_id;
	}

	/**
	 * Set user meta for testing.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool|int Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	protected function set_user_meta( $user_id, $meta_key, $meta_value ) {
		return update_user_meta( $user_id, $meta_key, $meta_value );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Error message to log.
	 */
	protected function log_error( $message ) {
		$this->logger->log( "[ERROR] {$message}" );
	}

	/**
	 * Get error log messages.
	 *
	 * @return array Array of error log messages.
	 */
	protected function get_error_log_messages() {
		return $this->logger->getMessages();
	}

	/**
	 * Assert that a WP_Error has a specific error code.
	 *
	 * @param string   $error_code Expected error code.
	 * @param WP_Error $wp_error   WP_Error object.
	 * @param string   $message    Optional. Message to display when the assertion fails.
	 */
	protected function assertWPErrorCodeEquals( $error_code, WP_Error $wp_error, $message = '' ) {
		$this->assertEquals( $error_code, $wp_error->get_error_code(), $message );
	}
}
