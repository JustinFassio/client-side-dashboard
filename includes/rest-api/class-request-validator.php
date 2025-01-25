<?php
/**
 * Request validator for REST API endpoints.
 *
 * @package AthleteDashboard
 * @subpackage REST_API
 */

namespace AthleteDashboard\REST_API;

/**
 * Class Request_Validator
 *
 * Validates and sanitizes REST API request parameters.
 */
class Request_Validator {

	/**
	 * Validates a REST API request against specified rules.
	 *
	 * @param \WP_REST_Request $request The request to validate.
	 * @param array            $rules   Validation rules to apply.
	 * @return bool True if validation passes, false otherwise.
	 */
	public function validate( \WP_REST_Request $request, array $rules ): bool {
		if ( WP_DEBUG ) {
			error_log( 'Validating request parameters.' );
		}

		$params = $request->get_params();
		return $this->validate_params( $params, $rules );
	}

	/**
	 * Validates request parameters against specified rules.
	 *
	 * @param array $params Parameters to validate.
	 * @param array $rules  Validation rules to apply.
	 * @return bool True if validation passes, false otherwise.
	 */
	private function validate_params( array $params, array $rules ): bool {
		if ( WP_DEBUG ) {
			error_log( 'Processing parameter validation.' );
		}

		foreach ( $rules as $param => $rule ) {
			if ( ! isset( $params[ $param ] ) || ! $this->validate_param( $params[ $param ], $rule ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validates a single parameter against a rule.
	 *
	 * @param mixed $value The value to validate.
	 * @param array $rule  The validation rule to apply.
	 * @return bool True if validation passes, false otherwise.
	 */
	private function validate_param( $value, array $rule ): bool {
		if ( WP_DEBUG ) {
			error_log( 'Validating parameter: ' . print_r( $value, true ) );
		}

		if ( $this->is_empty( $value ) && ! empty( $rule['required'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if a value is empty.
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if the value is empty, false otherwise.
	 */
	private function is_empty( $value ): bool {
		if ( WP_DEBUG ) {
			error_log( 'Checking if value is empty: ' . print_r( $value, true ) );
		}

		return empty( $value ) && ! is_numeric( $value );
	}
}
