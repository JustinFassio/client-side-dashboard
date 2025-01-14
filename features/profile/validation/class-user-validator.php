<?php
/**
 * User validator class.
 *
 * @package AthleteDashboard\Features\Profile\Validation
 */

namespace AthleteDashboard\Features\Profile\Validation;

use WP_Error;
use AthleteDashboard\Core\Config\Debug;

/**
 * Class User_Validator
 *
 * Handles validation of user data according to business rules.
 */
class User_Validator extends Base_Validator {
	/**
	 * User-specific validation constants
	 */
	private const NAME_MIN_LENGTH      = 2;
	private const NAME_MAX_LENGTH      = 50;
	private const USERNAME_MIN_LENGTH  = 3;
	private const USERNAME_MAX_LENGTH  = 30;
	private const USERNAME_PATTERN     = '/^[a-zA-Z0-9_\-\.]+$/';
	private const PHONE_PATTERN        = '/^\+?[\d\s-]{10,}$/';
	private const AVATAR_MAX_SIZE      = 2097152; // 2MB in bytes
	private const AVATAR_ALLOWED_TYPES = array( 'jpg', 'jpeg', 'png' );

	/**
	 * Get the validator-specific debug tag
	 *
	 * @return string The debug tag for this validator
	 */
	protected function get_debug_tag(): string {
		return 'validator.user';
	}

	/**
	 * Validate user data
	 *
	 * @param array $data User data to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_data( array $data ): bool|WP_Error {
		Debug::log( 'Starting user data validation', $this->get_debug_tag() );

		$array_check = $this->validate_array_input( $data );
		if ( $array_check instanceof WP_Error ) {
			return $array_check;
		}

		// Sanitize input data
		$data = $this->sanitize_user_data( $data );

		$validation_results = array(
			$this->validate_email( $data ),
			$this->validate_names( $data ),
			$this->validate_username( $data ),
		);

		foreach ( $validation_results as $result ) {
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		Debug::log( 'User data validation successful', $this->get_debug_tag() );
		return true;
	}

	/**
	 * Validate email address
	 *
	 * @param array $data User data containing email.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_email( array $data ): bool|WP_Error {
		if ( ! isset( $data['email'] ) ) {
			return $this->create_error(
				self::ERROR_REQUIRED_FIELD,
				'Email is required'
			);
		}

		if ( ! preg_match( self::EMAIL_PATTERN, $data['email'] ) ) {
			return $this->create_error(
				self::ERROR_INVALID_FORMAT,
				'Invalid email format'
			);
		}

		return true;
	}

	/**
	 * Validate user names
	 *
	 * @param array $data User data containing names.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_names( array $data ): bool|WP_Error {
		if ( isset( $data['firstName'] ) ) {
			$result = $this->validate_string(
				$data['firstName'],
				'First name',
				self::NAME_MIN_LENGTH,
				self::NAME_MAX_LENGTH,
				true
			);
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		if ( isset( $data['lastName'] ) ) {
			$result = $this->validate_string(
				$data['lastName'],
				'Last name',
				self::NAME_MIN_LENGTH,
				self::NAME_MAX_LENGTH,
				true
			);
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Validate username
	 *
	 * @param array $data User data containing username.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_username( array $data ): bool|WP_Error {
		if ( ! isset( $data['username'] ) ) {
			return true; // Username is optional
		}

		$result = $this->validate_string(
			$data['username'],
			'Username',
			self::USERNAME_MIN_LENGTH,
			self::USERNAME_MAX_LENGTH,
			false
		);
		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( ! preg_match( self::USERNAME_PATTERN, $data['username'] ) ) {
			return $this->create_error(
				self::ERROR_INVALID_CHARS,
				'Username can only contain letters, numbers, underscores, hyphens, and dots'
			);
		}

		return true;
	}

	/**
	 * Validate user meta data
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_meta( string $key, mixed $value ): bool|WP_Error {
		Debug::log( "Validating user meta: {$key}", $this->get_debug_tag() );

		switch ( $key ) {
			case 'phone':
				if ( ! preg_match( self::PHONE_PATTERN, $value ) ) {
					return $this->create_error(
						self::ERROR_INVALID_FORMAT,
						'Invalid phone number format',
						array( 'pattern' => self::PHONE_PATTERN )
					);
				}
				break;

			case 'avatar':
				if ( ! is_array( $value ) ) {
					return $this->create_error(
						self::ERROR_INVALID_TYPE,
						'Avatar must be a file upload'
					);
				}
				return $this->validate_file(
					$value,
					'Avatar',
					self::AVATAR_ALLOWED_TYPES,
					self::AVATAR_MAX_SIZE
				);

			default:
				return $this->create_error(
					self::ERROR_INVALID_FORMAT,
					"Unsupported meta key: {$key}"
				);
		}

		return true;
	}

	/**
	 * Sanitize user data
	 *
	 * @param array $data The user data to sanitize.
	 * @return array The sanitized user data.
	 */
	private function sanitize_user_data( array $data ): array {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_string( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}
}
