<?php
/**
 * User service exception class.
 *
 * @package AthleteDashboard\Features\User\Services
 */

namespace AthleteDashboard\Features\User\Services;

use Exception;

/**
 * Exception class for user service errors.
 */
class User_Service_Exception extends Exception {
	/**
	 * Error codes.
	 */
	public const ERROR_NOT_FOUND     = 'user_not_found';
	public const ERROR_INVALID_DATA  = 'invalid_user_data';
	public const ERROR_UPDATE_FAILED = 'user_update_failed';
	public const ERROR_META          = 'meta_operation_failed';
	public const ERROR_UNAUTHORIZED  = 'unauthorized';

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
