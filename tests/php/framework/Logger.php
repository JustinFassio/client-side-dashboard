<?php
/**
 * Test framework logger class.
 *
 * @package AthleteDashboard\Tests\Framework
 */

namespace AthleteDashboard\Tests\Framework;

/**
 * Logger class for test framework.
 */
class Logger {
	/**
	 * Singleton instance
	 *
	 * @var Logger|null
	 */
	private static ?Logger $instance = null;

	/**
	 * Log messages array
	 *
	 * @var array
	 */
	private array $messages = array();

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Get singleton instance
	 *
	 * @return Logger
	 */
	public static function getInstance(): Logger {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Log a message
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	public function log( string $message ): void {
		$this->messages[] = $message;
		// Also output to console for immediate feedback during tests.
		echo "\n[LOG] " . $message;
	}

	/**
	 * Get all logged messages
	 *
	 * @return array
	 */
	public function getMessages(): array {
		return $this->messages;
	}

	/**
	 * Clear all logged messages
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->messages = array();
	}
}
