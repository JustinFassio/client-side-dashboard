<?php
/**
 * Profile service exception class.
 *
 * @package AthleteDashboard\Features\Profile\Services
 */

namespace AthleteDashboard\Features\Profile\Services;

use Exception;

/**
 * Exception class for profile service errors.
 */
class Profile_Service_Exception extends Exception {
	/**
	 * Error codes.
	 */
	public const ERROR_VALIDATION   = 'validation_error';
	public const ERROR_NOT_FOUND    = 'profile_not_found';
	public const ERROR_UNAUTHORIZED = 'unauthorized';
	public const ERROR_INVALID_DATA = 'invalid_data';
	public const ERROR_DATABASE     = 'database_error';
	public const ERROR_META         = 'meta_operation_failed';

	/**
	 * Additional error data.
	 *
	 * @var array
	 */
	private array $error_data;

	/**
	 * Constructor.
	 *
	 * @param string         $message    Error message.
	 * @param string         $code       Error code.
	 * @param array          $error_data Additional error data.
	 * @param Exception|null $previous   Previous exception.
	 */
	public function __construct(
		string $message,
		string $code = self::ERROR_INVALID_DATA,
		array $error_data = array(),
		?Exception $previous = null
	) {
		parent::__construct( $message, 0, $previous );
		$this->code       = $code;
		$this->error_data = $error_data;
	}

	/**
	 * Get additional error data.
	 *
	 * @return array Error data.
	 */
	public function get_error_data(): array {
		return $this->error_data;
	}

	/**
	 * Convert exception to WP_Error.
	 *
	 * @return \WP_Error
	 */
	public function to_wp_error(): \WP_Error {
		return new \WP_Error(
			$this->code,
			$this->getMessage(),
			$this->error_data
		);
	}
}
