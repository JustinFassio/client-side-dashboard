<?php
namespace AthleteDashboard\Tests;

use PHPUnit\Framework\TestCase as PHPUnit_TestCase;

/**
 * Base test case class for the Athlete Dashboard theme.
 *
 * @package Athlete_Dashboard
 */

/**
 * Logger class for test cases.
 */
class Logger {
	/** @var array Messages logged during test execution. */
	private static $messages = array();

	/** @var Logger|null Singleton instance. */
	private static $instance = null;

	/** @var bool Debug mode flag. */
	private $debug = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialize the logger instance.
		self::$messages = array();
	}

	/**
	 * Get singleton instance of the logger.
	 *
	 * @return Logger Logger instance.
	 */
	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Log a message.
	 *
	 * @param string $message Message to log.
	 */
	public function log( $message ) {
		global $test_log_messages;

		// Add message to both static array and global array.
		self::$messages[]    = $message;
		$test_log_messages[] = $message;

		// Print debug information if enabled.
		if ( $this->debug ) {
			print_r( 'Logged message: ' . $message );
		}
	}

	/**
	 * Get all logged messages.
	 *
	 * @return array Array of logged messages.
	 */
	public function getMessages() {
		global $test_log_messages;

		if ( $this->debug ) {
			print_r( 'Current messages: ' );
			print_r( self::$messages );
		}

		// Return both static and global messages.
		return array_merge( self::$messages, $test_log_messages );
	}

	/**
	 * Reset the logger state.
	 */
	public function reset() {
		global $test_log_messages;

		if ( $this->debug ) {
			print_r( 'Resetting logger state.' );
		}

		// Clear both static and global messages.
		self::$messages    = array();
		$test_log_messages = array();
	}

	/**
	 * Set debug mode.
	 *
	 * @param bool $enabled Whether to enable debug mode.
	 */
	public function setDebugMode( $enabled ) {
		$this->debug = $enabled;
	}
}

/**
 * Base test case class for all tests.
 */
class TestCase extends PHPUnit_TestCase {
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
