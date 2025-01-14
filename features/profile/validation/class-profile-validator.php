<?php
/**
 * Profile validator class.
 *
 * @package AthleteDashboard\Features\Profile\Validation
 */

namespace AthleteDashboard\Features\Profile\Validation;

use WP_Error;
use AthleteDashboard\Core\Config\Debug;

/**
 * Class Profile_Validator
 *
 * Handles validation of profile data according to business rules.
 */
class Profile_Validator extends Base_Validator {
	/**
	 * Profile-specific validation constants
	 */
	private const MIN_HEIGHT_CM = 100;
	private const MAX_HEIGHT_CM = 250;
	private const MIN_WEIGHT_KG = 30;
	private const MAX_WEIGHT_KG = 300;
	private const MIN_AGE       = 13;
	private const MAX_AGE       = 120;

	/**
	 * BMI validation thresholds
	 */
	private const MIN_BMI           = 13.0;  // Severe underweight threshold
	private const MAX_BMI           = 50.0;  // Severe obesity threshold
	private const ERROR_INVALID_BMI = 'invalid_bmi';

	/**
	 * Age-based fitness level restrictions
	 */
	private const MIN_AGE_ADVANCED      = 16;  // Minimum age for advanced/expert levels
	private const MAX_AGE_SENIOR        = 65;    // Age threshold for senior activity restrictions
	private const ERROR_AGE_RESTRICTION = 'age_restriction';

	/**
	 * Allowed values for various fields
	 */
	private const ALLOWED_UNITS           = array( 'imperial', 'metric' );
	private const ALLOWED_FITNESS_LEVELS  = array( 'beginner', 'intermediate', 'advanced', 'expert' );
	private const ALLOWED_ACTIVITY_LEVELS = array( 'sedentary', 'light', 'moderate', 'very_active', 'extra_active' );
	private const ALLOWED_GENDERS         = array( 'male', 'female', 'other', 'prefer_not_to_say', '' );

	/**
	 * Get the validator-specific debug tag
	 *
	 * @return string The debug tag for this validator
	 */
	protected function get_debug_tag(): string {
		return 'validator.profile';
	}

	/**
	 * Validate complete profile data
	 *
	 * @param array $data The profile data to validate.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_data( array $data ): bool|WP_Error {
		Debug::log( 'Starting profile data validation', $this->get_debug_tag() );

		$array_check = $this->validate_array_input( $data );
		if ( $array_check instanceof WP_Error ) {
			return $array_check;
		}

		// Sanitize input data
		$data = $this->sanitize_profile_data( $data );

		$validation_results = array(
			$this->validate_email( $data ),
			$this->validate_preferences( $data ),
			$this->validate_demographics( $data ),
			$this->validate_physical_metrics( $data ),
			$this->validate_cross_field_rules( $data ),  // Add cross-field validation
		);

		foreach ( $validation_results as $result ) {
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		Debug::log( 'Profile data validation successful', $this->get_debug_tag() );
		return true;
	}

	/**
	 * Validate email address
	 *
	 * @param array $data Profile data containing email.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_email( array $data ): bool|WP_Error {
		if ( ! isset( $data['email'] ) ) {
			return true; // Email is optional in profile
		}

		return $this->validate_string( $data['email'], 'Email', 5, 255, false );
	}

	/**
	 * Validate user preferences
	 *
	 * @param array $data Profile data containing preferences.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_preferences( array $data ): bool|WP_Error {
		if ( isset( $data['units'] ) ) {
			$result = $this->validate_enum( $data['units'], 'Units', self::ALLOWED_UNITS, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		if ( isset( $data['fitness_level'] ) ) {
			$result = $this->validate_enum( $data['fitness_level'], 'Fitness level', self::ALLOWED_FITNESS_LEVELS, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		if ( isset( $data['activity_level'] ) ) {
			$result = $this->validate_enum( $data['activity_level'], 'Activity level', self::ALLOWED_ACTIVITY_LEVELS, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Validate demographic information
	 *
	 * @param array $data Profile data containing demographics.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_demographics( array $data ): bool|WP_Error {
		if ( isset( $data['gender'] ) ) {
			$result = $this->validate_enum( $data['gender'], 'Gender', self::ALLOWED_GENDERS, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		if ( isset( $data['age'] ) ) {
			$result = $this->validate_number( $data['age'], 'Age', self::MIN_AGE, self::MAX_AGE, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Validate physical metrics
	 *
	 * @param array $data Profile data containing physical metrics.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_physical_metrics( array $data ): bool|WP_Error {
		$units = $data['units'] ?? 'metric';

		if ( isset( $data['height'] ) ) {
			$height = $data['height'];
			if ( $units === 'imperial' ) {
				// Convert height from inches to cm for validation
				$height = $height * 2.54;
			}

			$result = $this->validate_number( $height, 'Height', self::MIN_HEIGHT_CM, self::MAX_HEIGHT_CM, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		if ( isset( $data['weight'] ) ) {
			$weight = $data['weight'];
			if ( $units === 'imperial' ) {
				// Convert weight from lbs to kg for validation
				$weight = $weight * 0.453592;
			}

			$result = $this->validate_number( $weight, 'Weight', self::MIN_WEIGHT_KG, self::MAX_WEIGHT_KG, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Cross-field validation rules
	 *
	 * @param array $data Profile data containing multiple fields.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	private function validate_cross_field_rules( array $data ): bool|WP_Error {
		// Only validate if we have enough data
		if ( isset( $data['height'], $data['weight'] ) ) {
			$result = $this->validate_bmi( $data );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		if ( isset( $data['age'] ) ) {
			if ( isset( $data['fitness_level'] ) ) {
				$result = $this->validate_age_fitness_level( $data );
				if ( $result instanceof WP_Error ) {
					return $result;
				}
			}

			if ( isset( $data['activity_level'] ) ) {
				$result = $this->validate_age_activity_level( $data );
				if ( $result instanceof WP_Error ) {
					return $result;
				}
			}
		}

		return true;
	}

	/**
	 * Validate BMI is within healthy range
	 *
	 * @param array $data Profile data containing height and weight.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	private function validate_bmi( array $data ): bool|WP_Error {
		$units = $data['units'] ?? 'metric';

		// Convert to metric for BMI calculation
		$height_m = $units === 'imperial'
			? $data['height'] * 0.0254  // Convert inches to meters
			: $data['height'] / 100;    // Convert cm to meters

		$weight_kg = $units === 'imperial'
			? $data['weight'] * 0.453592  // Convert lbs to kg
			: $data['weight'];

		$bmi = $weight_kg / ( $height_m * $height_m );

		if ( $bmi < self::MIN_BMI || $bmi > self::MAX_BMI ) {
			return $this->create_error(
				self::ERROR_INVALID_BMI,
				sprintf( 'BMI value %.1f is outside acceptable range (%.1f - %.1f)', $bmi, self::MIN_BMI, self::MAX_BMI ),
				array(
					'bmi'     => $bmi,
					'min_bmi' => self::MIN_BMI,
					'max_bmi' => self::MAX_BMI,
				)
			);
		}

		return true;
	}

	/**
	 * Validate age-appropriate fitness level
	 *
	 * @param array $data Profile data containing age and fitness level.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	private function validate_age_fitness_level( array $data ): bool|WP_Error {
		if ( $data['age'] < self::MIN_AGE_ADVANCED &&
			in_array( $data['fitness_level'], array( 'advanced', 'expert' ), true ) ) {
			return $this->create_error(
				self::ERROR_AGE_RESTRICTION,
				sprintf( 'Advanced fitness levels require minimum age of %d', self::MIN_AGE_ADVANCED ),
				array( 'min_age' => self::MIN_AGE_ADVANCED )
			);
		}

		return true;
	}

	/**
	 * Validate age-appropriate activity level
	 *
	 * @param array $data Profile data containing age and activity level.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	private function validate_age_activity_level( array $data ): bool|WP_Error {
		if ( $data['age'] >= self::MAX_AGE_SENIOR ) {
			$allowed_levels = array( 'sedentary', 'light', 'moderate' );
			if ( ! in_array( $data['activity_level'], $allowed_levels, true ) ) {
				return $this->create_error(
					self::ERROR_AGE_RESTRICTION,
					'Senior users are limited to moderate or lower activity levels',
					array( 'allowed_levels' => $allowed_levels )
				);
			}
		}

		return true;
	}

	/**
	 * Sanitize profile data
	 *
	 * @param array $data The profile data to sanitize.
	 * @return array The sanitized profile data.
	 */
	private function sanitize_profile_data( array $data ): array {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_string( $value );
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $key ] = filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}
}
