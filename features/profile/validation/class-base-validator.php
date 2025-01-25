<?php
/**
 * Base validator class.
 *
 * @package AthleteDashboard\Features\Profile\Validation
 */

namespace AthleteDashboard\Features\Profile\Validation;

use WP_Error;
use AthleteDashboard\Core\Config\Debug;

/**
 * Abstract Base_Validator class
 *
 * Provides common validation functionality for all validators.
 * All validation methods follow a consistent pattern:
 * - Accept strongly typed parameters
 * - Return bool|WP_Error (true for valid, WP_Error for invalid)
 * - Use consistent error codes and messages
 */
abstract class Base_Validator {
	/**
	 * Common error codes
	 */
	protected const ERROR_INVALID_FORMAT = 'invalid_data_format';
	protected const ERROR_INVALID_TYPE   = 'invalid_data_type';
	protected const ERROR_INVALID_LENGTH = 'invalid_length';
	protected const ERROR_INVALID_RANGE  = 'invalid_range';
	protected const ERROR_INVALID_CHARS  = 'invalid_characters';
	protected const ERROR_REQUIRED_FIELD = 'required_field_missing';
	protected const ERROR_INVALID_FILE   = 'invalid_file';

	/**
	 * Common validation constants
	 */
	protected const MAX_STRING_LENGTH   = 255;
	protected const MIN_STRING_LENGTH   = 1;
	protected const ALLOWED_FILE_TYPES  = array( 'jpg', 'jpeg', 'png', 'gif' );
	protected const MAX_FILE_SIZE       = 5242880; // 5MB in bytes
	protected const SAFE_STRING_PATTERN = '/^[a-zA-Z0-9\s\-_\.]+$/';
	protected const EMAIL_PATTERN       = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

	/**
	 * Get the validator-specific debug tag
	 *
	 * @return string The debug tag for this validator
	 */
	abstract protected function get_debug_tag(): string;

	/**
	 * Validate that input is an array
	 *
	 * @param mixed $data The data to validate.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	protected function validate_array_input( mixed $data ): bool|WP_Error {
		if ( ! is_array( $data ) ) {
			return $this->create_error(
				self::ERROR_INVALID_TYPE,
				'Data must be an array'
			);
		}
		return true;
	}

	/**
	 * Validate string data type and length
	 *
	 * @param mixed  $value The value to validate.
	 * @param string $field_name The name of the field being validated.
	 * @param int    $min_length Minimum allowed length.
	 * @param int    $max_length Maximum allowed length.
	 * @param bool   $required Whether the field is required.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	protected function validate_string(
		mixed $value,
		string $field_name,
		int $min_length = self::MIN_STRING_LENGTH,
		int $max_length = self::MAX_STRING_LENGTH,
		bool $required = true
	): bool|WP_Error {
		if ( ! isset( $value ) || $value === '' ) {
			return $required ? $this->create_error(
				self::ERROR_REQUIRED_FIELD,
				sprintf( '%s is required', $field_name )
			) : true;
		}

		if ( ! is_string( $value ) ) {
			return $this->create_error(
				self::ERROR_INVALID_TYPE,
				sprintf( '%s must be a string', $field_name )
			);
		}

		$length = strlen( $value );
		if ( $length < $min_length || $length > $max_length ) {
			return $this->create_error(
				self::ERROR_INVALID_LENGTH,
				sprintf( '%s must be between %d and %d characters', $field_name, $min_length, $max_length ),
				array(
					'min' => $min_length,
					'max' => $max_length,
				)
			);
		}

		return true;
	}

	/**
	 * Validate numeric value and range
	 *
	 * @param mixed  $value The value to validate.
	 * @param string $field_name The name of the field being validated.
	 * @param float  $min Minimum allowed value.
	 * @param float  $max Maximum allowed value.
	 * @param bool   $required Whether the field is required.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	protected function validate_number(
		mixed $value,
		string $field_name,
		float $min = PHP_FLOAT_MIN,
		float $max = PHP_FLOAT_MAX,
		bool $required = true
	): bool|WP_Error {
		if ( ! isset( $value ) ) {
			return $required ? $this->create_error(
				self::ERROR_REQUIRED_FIELD,
				sprintf( '%s is required', $field_name )
			) : true;
		}

		if ( ! is_numeric( $value ) ) {
			return $this->create_error(
				self::ERROR_INVALID_TYPE,
				sprintf( '%s must be a number', $field_name )
			);
		}

		$num_value = (float) $value;
		if ( $num_value < $min || $num_value > $max ) {
			return $this->create_error(
				self::ERROR_INVALID_RANGE,
				sprintf( '%s must be between %f and %f', $field_name, $min, $max ),
				array(
					'min' => $min,
					'max' => $max,
				)
			);
		}

		return true;
	}

	/**
	 * Validate that a value is one of a set of allowed values
	 *
	 * @param mixed  $value The value to validate.
	 * @param string $field_name The name of the field being validated.
	 * @param array  $allowed_values Array of allowed values.
	 * @param bool   $required Whether the field is required.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	protected function validate_enum(
		mixed $value,
		string $field_name,
		array $allowed_values,
		bool $required = true
	): bool|WP_Error {
		if ( ! isset( $value ) || $value === '' ) {
			return $required ? $this->create_error(
				self::ERROR_REQUIRED_FIELD,
				sprintf( '%s is required', $field_name )
			) : true;
		}

		if ( ! in_array( $value, $allowed_values, true ) ) {
			return $this->create_error(
				self::ERROR_INVALID_FORMAT,
				sprintf( '%s must be one of: %s', $field_name, implode( ', ', $allowed_values ) ),
				array( 'allowed_values' => $allowed_values )
			);
		}

		return true;
	}

	/**
	 * Validate file upload
	 *
	 * @param array  $file The uploaded file data.
	 * @param string $field_name The name of the field being validated.
	 * @param array  $allowed_types Array of allowed file types.
	 * @param int    $max_size Maximum file size in bytes.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	protected function validate_file(
		array $file,
		string $field_name,
		array $allowed_types = self::ALLOWED_FILE_TYPES,
		int $max_size = self::MAX_FILE_SIZE
	): bool|WP_Error {
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return $this->create_error(
				self::ERROR_INVALID_FILE,
				sprintf( 'No valid file uploaded for %s', $field_name )
			);
		}

		$file_info = pathinfo( $file['name'] );
		$extension = strtolower( $file_info['extension'] ?? '' );

		if ( ! in_array( $extension, $allowed_types, true ) ) {
			return $this->create_error(
				self::ERROR_INVALID_FILE,
				sprintf( 'Invalid file type for %s. Allowed types: %s', $field_name, implode( ', ', $allowed_types ) ),
				array( 'allowed_types' => $allowed_types )
			);
		}

		if ( $file['size'] > $max_size ) {
			return $this->create_error(
				self::ERROR_INVALID_FILE,
				sprintf( 'File size for %s exceeds maximum of %d bytes', $field_name, $max_size ),
				array( 'max_size' => $max_size )
			);
		}

		return true;
	}

	/**
	 * Sanitize a string value
	 *
	 * @param string $value The value to sanitize.
	 * @return string The sanitized value.
	 */
	protected function sanitize_string( string $value ): string {
		return htmlspecialchars( trim( $value ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Create a standardized WP_Error object
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param array  $data Additional error data.
	 * @return WP_Error
	 */
	protected function create_error( string $code, string $message, array $data = array() ): WP_Error {
		$error_data = array_merge(
			array( 'status' => 400 ),
			$data
		);

		Debug::log(
			"Validation error: {$code} - {$message}",
			$this->get_debug_tag()
		);

		return new WP_Error( $code, $message, $error_data );
	}
}
