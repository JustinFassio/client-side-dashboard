<?php
/**
 * Profile validator class.
 *
 * Handles validation of profile data including physical metrics,
 * preferences, demographics, and cross-field validations. Ensures
 * data integrity and business rule compliance.
 *
 * @package AthleteDashboard\Features\Profile\Validation
 * @since 1.0.0
 */

namespace AthleteDashboard\Features\Profile\Validation;

use WP_Error;
use AthleteDashboard\Core\Config\Debug;

/**
 * Class Profile_Validator
 *
 * Handles validation of profile data according to business rules.
 * Includes validation for physical metrics, preferences, demographics,
 * and cross-field validations such as BMI calculations and age-based
 * restrictions.
 *
 * @since 1.0.0
 */
class Profile_Validator extends Base_Validator {
	/**
	 * Profile-specific validation constants.
	 */
	private const MIN_HEIGHT_CM = 100;
	private const MAX_HEIGHT_CM = 250;
	private const MIN_WEIGHT_KG = 30;
	private const MAX_WEIGHT_KG = 300;
	private const MIN_AGE       = 13;
	private const MAX_AGE       = 120;

	/**
	 * BMI validation thresholds.
	 */
	private const MIN_BMI           = 13.0;  // Severe underweight threshold.
	private const MAX_BMI           = 50.0;  // Severe obesity threshold.
	private const ERROR_INVALID_BMI = 'invalid_bmi';

	/**
	 * Age-based fitness level restrictions.
	 */
	private const MIN_AGE_ADVANCED      = 16;    // Minimum age for advanced/expert levels.
	private const MAX_AGE_SENIOR        = 65;    // Age threshold for senior activity restrictions.
	private const ERROR_AGE_RESTRICTION = 'age_restriction';

	/**
	 * Allowed values for various fields.
	 */
	private const ALLOWED_UNITS           = array( 'imperial', 'metric' );
	private const ALLOWED_FITNESS_LEVELS  = array( 'beginner', 'intermediate', 'advanced', 'expert' );
	private const ALLOWED_ACTIVITY_LEVELS = array( 'sedentary', 'light', 'moderate', 'very_active', 'extra_active' );
	private const ALLOWED_GENDERS         = array( 'male', 'female', 'other', 'prefer_not_to_say', '' );

	/**
	 * Experience level options
	 */
	private const EXPERIENCE_LEVELS = array( 'beginner', 'intermediate', 'advanced' );

	/**
	 * Get the validator-specific debug tag.
	 *
	 * @since 1.0.0
	 * @return string The debug tag for this validator.
	 */
	protected function get_debug_tag(): string {
		return 'validator.profile';
	}

	/**
	 * Validate complete profile data.
	 *
	 * Performs comprehensive validation of all profile data fields including
	 * sanitization, field-specific validation, and cross-field validation rules.
	 *
	 * @since 1.0.0
	 * @param array $data The profile data to validate.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_data( array $data ): bool|WP_Error {
		Debug::log( 'Starting profile data validation.', $this->get_debug_tag() );

		$array_check = $this->validate_array_input( $data );
		if ( $array_check instanceof WP_Error ) {
			return $array_check;
		}

		// Sanitize input data.
		$data = $this->sanitize_profile_data( $data );

		$validation_results = array(
			$this->validate_email( $data ),
			$this->validate_preferences( $data ),
			$this->validate_demographics( $data ),
			$this->validate_physical_metrics( $data ),
			$this->validate_cross_field_rules( $data ),  // Add cross-field validation.
		);

		foreach ( $validation_results as $result ) {
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		Debug::log( 'Profile data validation successful.', $this->get_debug_tag() );
		return true;
	}

	/**
	 * Validate email address.
	 *
	 * Checks if the provided email is valid when present. Email is optional
	 * in the profile but must meet length and format requirements if provided.
	 *
	 * @since 1.0.0
	 * @param array $data Profile data containing email.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_email( array $data ): bool|WP_Error {
		error_log(
			sprintf(
				'Athlete Dashboard [profile]: Validating email [exists=%s, value=%s]',
				isset( $data['email'] ) ? 'true' : 'false',
				isset( $data['email'] ) ? $data['email'] : 'not_set'
			)
		);

		if ( ! isset( $data['email'] ) ) {
			error_log( 'Athlete Dashboard [profile]: Email validation skipped - email not set' );
			return true; // Email is optional in profile.
		}

		// First validate string length
		$string_validation = $this->validate_string( $data['email'], 'Email', 5, 255, false );
		if ( $string_validation instanceof WP_Error ) {
			error_log(
				sprintf(
					'Athlete Dashboard [profile]: Email string validation failed [error=%s]',
					$string_validation->get_error_message()
				)
			);
			return $string_validation;
		}

		// Then validate email format using our standard pattern
		if ( ! preg_match( self::EMAIL_PATTERN, $data['email'] ) ) {
			error_log(
				sprintf(
					'Athlete Dashboard [profile]: Email format validation failed [email=%s]',
					$data['email']
				)
			);
			return new WP_Error(
				'invalid_email_format',
				'Invalid email address format',
				array( 'status' => 400 )
			);
		}

		error_log(
			sprintf(
				'Athlete Dashboard [profile]: Email validation passed [email=%s]',
				$data['email']
			)
		);
		return true;
	}

	/**
	 * Validate user preferences.
	 *
	 * Validates unit preferences, fitness level, and activity level against
	 * predefined allowed values.
	 *
	 * @since 1.0.0
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
	 * Validate physical metrics.
	 *
	 * Validates height and weight measurements, converting imperial units
	 * to metric for standardized validation.
	 *
	 * @since 1.0.0
	 * @param array $data Profile data containing physical metrics.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_physical_metrics( array $data ): bool|WP_Error {
		$units = isset( $data['units'] ) ? $data['units'] : 'metric';

		if ( isset( $data['height'] ) ) {
			$height = $data['height'];
			if ( 'imperial' === $units ) {
				// Convert height from inches to cm for validation.
				$height = $height * 2.54;
			}

			$result = $this->validate_number( $height, 'Height', self::MIN_HEIGHT_CM, self::MAX_HEIGHT_CM, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		if ( isset( $data['weight'] ) ) {
			$weight = $data['weight'];
			if ( 'imperial' === $units ) {
				// Convert weight from lbs to kg for validation.
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
	 * Cross-field validation rules.
	 *
	 * Performs validations that depend on multiple fields, such as BMI calculation
	 * and age-based restrictions for fitness and activity levels.
	 *
	 * @since 1.0.0
	 * @param array $data Profile data containing multiple fields.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	private function validate_cross_field_rules( array $data ): bool|WP_Error {
		// Only validate if we have enough data.
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
	 * Validate BMI is within healthy range.
	 *
	 * Calculates and validates BMI using height and weight, converting units
	 * as necessary. BMI must fall within defined thresholds.
	 *
	 * @since 1.0.0
	 * @param array $data Profile data containing height and weight.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	private function validate_bmi( array $data ): bool|WP_Error {
		$units = isset( $data['units'] ) ? $data['units'] : 'metric';

		// Convert to metric for BMI calculation.
		$height_m = 'imperial' === $units
			? $data['height'] * 0.0254  // Convert inches to meters.
			: $data['height'] / 100;    // Convert cm to meters.

		$weight_kg = 'imperial' === $units
			? $data['weight'] * 0.453592  // Convert lbs to kg.
			: $data['weight'];            // Already in kg.

		// Calculate BMI.
		$bmi = $weight_kg / ( $height_m * $height_m );

		if ( $bmi < self::MIN_BMI || $bmi > self::MAX_BMI ) {
			return new WP_Error(
				self::ERROR_INVALID_BMI,
				sprintf(
					'BMI value %.1f is outside the acceptable range (%.1f - %.1f).',
					$bmi,
					self::MIN_BMI,
					self::MAX_BMI
				),
				array(
					'bmi'     => $bmi,
					'min_bmi' => self::MIN_BMI,
					'max_bmi' => self::MAX_BMI,
					'status'  => 400,
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

	/**
	 * Validate user data.
	 *
	 * @param array $data User data to validate.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_user_data( array $data ): bool|WP_Error {
		Debug::log( 'Starting user data validation.', $this->get_debug_tag() );

		$array_check = $this->validate_array_input( $data );
		if ( $array_check instanceof WP_Error ) {
			return $array_check;
		}

		$validation_results = array(
			$this->validate_email( $data ),
			$this->validate_display_name( $data ),
			$this->validate_first_name( $data ),
			$this->validate_last_name( $data ),
			$this->validate_nickname( $data ),
		);

		foreach ( $validation_results as $result ) {
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		Debug::log( 'User data validation successful.', $this->get_debug_tag() );
		return true;
	}

	/**
	 * Validate nickname.
	 *
	 * @param array $data User data containing nickname.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_nickname( array $data ): bool|WP_Error {
		if ( ! isset( $data['nickname'] ) ) {
			return true; // Nickname is optional.
		}

		return $this->validate_string( $data['nickname'], 'Nickname', 0, 50, false );
	}

	/**
	 * Validate demographics data.
	 *
	 * Validates age, gender, and other demographic information.
	 *
	 * @since 1.0.0
	 * @param array $data Profile data containing demographics.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_demographics( array $data ): bool|WP_Error {
		if ( isset( $data['age'] ) ) {
			$result = $this->validate_number( $data['age'], 'Age', self::MIN_AGE, self::MAX_AGE, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		if ( isset( $data['gender'] ) ) {
			$result = $this->validate_enum( $data['gender'], 'Gender', self::ALLOWED_GENDERS, false );
			if ( $result instanceof WP_Error ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Validate medical conditions data.
	 *
	 * Validates medical conditions and related information.
	 *
	 * @since 1.0.0
	 * @param array $data Profile data containing medical conditions.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails.
	 */
	public function validate_medical_conditions( array $data ): bool|WP_Error {
		if ( ! isset( $data['medical_conditions'] ) ) {
			return true;
		}

		if ( ! is_array( $data['medical_conditions'] ) ) {
			return new WP_Error(
				'invalid_medical_conditions',
				'Medical conditions must be an array',
				array( 'status' => 400 )
			);
		}

		foreach ( $data['medical_conditions'] as $condition ) {
			if ( ! is_array( $condition ) ) {
				return new WP_Error(
					'invalid_medical_condition_format',
					'Each medical condition must be an array',
					array( 'status' => 400 )
				);
			}

			if ( ! isset( $condition['type'] ) || ! is_string( $condition['type'] ) ) {
				return new WP_Error(
					'invalid_medical_condition_type',
					'Medical condition type must be a string',
					array( 'status' => 400 )
				);
			}

			if ( isset( $condition['description'] ) ) {
				$result = $this->validate_string( $condition['description'], 'Medical condition description', 0, 1000, false );
				if ( $result instanceof WP_Error ) {
					return $result;
				}
			}
		}

		return true;
	}

	/**
	 * Convert units between metric and imperial.
	 *
	 * @since 1.0.0
	 * @param array  $data Physical data to convert.
	 * @param string $to_units Target unit system ('metric' or 'imperial').
	 * @return array Converted data.
	 */
	public function convert_units( array $data, string $to_units ): array {
		$from_units = $data['units'] ?? 'metric';

		if ( $from_units === $to_units ) {
			return $data;
		}

		$converted          = $data;
		$converted['units'] = $to_units;

		if ( isset( $data['height'] ) ) {
			$converted['height'] = $to_units === 'imperial'
				? $data['height'] / 2.54  // cm to inches
				: $data['height'] * 2.54; // inches to cm
		}

		if ( isset( $data['weight'] ) ) {
			$converted['weight'] = $to_units === 'imperial'
				? $data['weight'] / 0.453592  // kg to lbs
				: $data['weight'] * 0.453592; // lbs to kg
		}

		return $converted;
	}

	/**
	 * Validate profile data for workout generation requirements
	 *
	 * @param array $data Profile data to validate
	 * @return bool|WP_Error True if valid, WP_Error otherwise
	 */
	public function validate_workout_requirements( array $data ): bool|WP_Error {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Validating workout requirements: ' . print_r( $data, true ) );
		}

		// Check required fields
		$required_fields = array(
			'heightCm'        => 'Height',
			'weightKg'        => 'Weight',
			'experienceLevel' => 'Experience Level',
		);

		foreach ( $required_fields as $field => $label ) {
			if ( ! isset( $data[ $field ] ) || empty( $data[ $field ] ) ) {
				return new WP_Error(
					'missing_required_field',
					sprintf( __( '%s is required for workout generation.', 'athlete-dashboard' ), $label ),
					array(
						'status' => 400,
						'field'  => $field,
					)
				);
			}
		}

		// Validate height
		$height = (float) $data['heightCm'];
		if ( $height < self::MIN_HEIGHT_CM || $height > self::MAX_HEIGHT_CM ) {
			return new WP_Error(
				'invalid_height',
				sprintf(
					__( 'Height must be between %1$d and %2$d cm.', 'athlete-dashboard' ),
					self::MIN_HEIGHT_CM,
					self::MAX_HEIGHT_CM
				),
				array( 'status' => 400 )
			);
		}

		// Validate weight
		$weight = (float) $data['weightKg'];
		if ( $weight < self::MIN_WEIGHT_KG || $weight > self::MAX_WEIGHT_KG ) {
			return new WP_Error(
				'invalid_weight',
				sprintf(
					__( 'Weight must be between %1$d and %2$d kg.', 'athlete-dashboard' ),
					self::MIN_WEIGHT_KG,
					self::MAX_WEIGHT_KG
				),
				array( 'status' => 400 )
			);
		}

		// Validate experience level
		if ( ! in_array( $data['experienceLevel'], self::EXPERIENCE_LEVELS, true ) ) {
			return new WP_Error(
				'invalid_experience_level',
				__( 'Invalid experience level. Must be beginner, intermediate, or advanced.', 'athlete-dashboard' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate physical measurements
	 *
	 * @param array $data Data to validate.
	 * @return array Validation result.
	 */
	public function validate_physical_measurements( $data ) {
		$errors = array();

		// Sanitize and validate height
		$height = isset( $data['heightCm'] ) ? absint( $data['heightCm'] ) : 0;
		if ( ! $this->is_valid_height( $height ) ) {
			$errors['heightCm'] = sprintf(
				/* translators: %1$d: minimum height, %2$d: maximum height */
				__( 'Height must be between %1$d and %2$d cm.', 'athlete-dashboard' ),
				self::MIN_HEIGHT_CM,
				self::MAX_HEIGHT_CM
			);
		}

		// Sanitize and validate weight
		$weight = isset( $data['weightKg'] ) ? absint( $data['weightKg'] ) : 0;
		if ( ! $this->is_valid_weight( $weight ) ) {
			$errors['weightKg'] = sprintf(
				/* translators: %1$d: minimum weight, %2$d: maximum weight */
				__( 'Weight must be between %1$d and %2$d kg.', 'athlete-dashboard' ),
				self::MIN_WEIGHT_KG,
				self::MAX_WEIGHT_KG
			);
		}

		return array(
			'is_valid'       => empty( $errors ),
			'errors'         => $errors,
			'sanitized_data' => array(
				'heightCm' => $height,
				'weightKg' => $weight,
			),
		);
	}

	/**
	 * Validate height value
	 *
	 * @param int $height Height in cm.
	 * @return bool Whether height is valid.
	 */
	private function is_valid_height( $height ) {
		return $height >= self::MIN_HEIGHT_CM && $height <= self::MAX_HEIGHT_CM;
	}

	/**
	 * Validate weight value
	 *
	 * @param int $weight Weight in kg.
	 * @return bool Whether weight is valid.
	 */
	private function is_valid_weight( $weight ) {
		return $weight >= self::MIN_WEIGHT_KG && $weight <= self::MAX_WEIGHT_KG;
	}
}
