<?php
/**
 * Profile service class.
 *
 * @package AthleteDashboard\Features\Profile
 */

namespace AthleteDashboard\Features\Profile;

use WP_Error;

/**
 * Class for handling profile operations.
 */
class Profile_Service {
	/**
	 * Get profile data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Profile data or error.
	 */
	public function get_profile_data( int $user_id ): array|WP_Error {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Service: Fetching profile data for user %d', $user_id ) );
		}

		// Check user exists
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found.', 'athlete-dashboard' ),
				array( 'user_id' => $user_id )
			);
		}

		// Get user data
		$user = get_userdata( $user_id );
		$meta = get_user_meta( $user_id );

		// Build profile data
		$profile_data = array(
			'id'                    => $user_id,
			'username'              => $user->user_login,
			'email'                 => $user->user_email,
			'displayName'           => $user->display_name,
			'firstName'             => $meta['first_name'][0] ?? '',
			'lastName'              => $meta['last_name'][0] ?? '',
			'nickname'              => $meta['nickname'][0] ?? '',
			'roles'                 => $user->roles,
			'age'                   => isset( $meta['age'] ) ? absint( $meta['age'][0] ) : null,
			'gender'                => $meta['gender'][0] ?? '',
			'phone'                 => $meta['phone'][0] ?? '',
			'emergencyContactName'  => $meta['emergency_contact_name'][0] ?? '',
			'emergencyContactPhone' => $meta['emergency_contact_phone'][0] ?? '',
			'medicalNotes'          => $meta['medical_notes'][0] ?? '',
			'medicalClearance'      => ! empty( $meta['medical_clearance'][0] ),
			'injuries'              => $this->get_injuries( $user_id ),
		);

		return $profile_data;
	}

	/**
	 * Update profile data for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Profile data to update.
	 * @return array|WP_Error Updated data or error.
	 */
	public function update_profile_data( int $user_id, array $data ): array|WP_Error {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Profile Service: Updating profile data for user %d', $user_id ) );
		}

		// Validate data
		$validation = $this->validate_profile_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Update WordPress user data
		$user_data = array(
			'ID' => $user_id,
		);

		if ( isset( $data['email'] ) ) {
			$user_data['user_email'] = $data['email'];
		}
		if ( isset( $data['firstName'] ) ) {
			$user_data['first_name'] = $data['firstName'];
		}
		if ( isset( $data['lastName'] ) ) {
			$user_data['last_name'] = $data['lastName'];
		}
		if ( isset( $data['displayName'] ) ) {
			$user_data['display_name'] = $data['displayName'];
		}

		if ( count( $user_data ) > 1 ) {
			$result = wp_update_user( $user_data );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Update meta fields
		$meta_fields = array(
			'age'                   => 'age',
			'gender'                => 'gender',
			'phone'                 => 'phone',
			'emergencyContactName'  => 'emergency_contact_name',
			'emergencyContactPhone' => 'emergency_contact_phone',
			'medicalNotes'          => 'medical_notes',
			'medicalClearance'      => 'medical_clearance',
		);

		foreach ( $meta_fields as $field => $meta_key ) {
			if ( isset( $data[ $field ] ) ) {
				update_user_meta( $user_id, $meta_key, $data[ $field ] );
			}
		}

		// Handle injuries separately
		if ( isset( $data['injuries'] ) ) {
			$this->update_injuries( $user_id, $data['injuries'] );
		}

		return $this->get_profile_data( $user_id );
	}

	/**
	 * Get injuries for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of injuries.
	 */
	private function get_injuries( int $user_id ): array {
		$injuries = get_user_meta( $user_id, 'injuries', true );
		return is_array( $injuries ) ? $injuries : array();
	}

	/**
	 * Update injuries for a user.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $injuries Array of injuries.
	 * @return bool Whether the update was successful.
	 */
	private function update_injuries( int $user_id, array $injuries ): bool {
		return update_user_meta( $user_id, 'injuries', $injuries );
	}

	/**
	 * Validate profile data.
	 *
	 * @param array $data Profile data to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_profile_data( array $data ): bool|WP_Error {
		$errors = new WP_Error();

		// Validate email
		if ( isset( $data['email'] ) && ! is_email( $data['email'] ) ) {
			$errors->add( 'invalid_email', __( 'Invalid email address.', 'athlete-dashboard' ) );
		}

		// Validate age
		if ( isset( $data['age'] ) ) {
			$age = absint( $data['age'] );
			if ( $age < 13 || $age > 120 ) {
				$errors->add( 'invalid_age', __( 'Age must be between 13 and 120.', 'athlete-dashboard' ) );
			}
		}

		// Validate gender
		$allowed_genders = array( 'male', 'female', 'other', 'prefer_not_to_say', '' );
		if ( isset( $data['gender'] ) && ! in_array( $data['gender'], $allowed_genders, true ) ) {
			$errors->add( 'invalid_gender', __( 'Invalid gender value.', 'athlete-dashboard' ) );
		}

		// Validate phone numbers
		$phone_pattern = '/^[+]?[0-9\s-()]{10,20}$/';
		if ( isset( $data['phone'] ) && ! empty( $data['phone'] ) && ! preg_match( $phone_pattern, $data['phone'] ) ) {
			$errors->add( 'invalid_phone', __( 'Invalid phone number format.', 'athlete-dashboard' ) );
		}
		if ( isset( $data['emergencyContactPhone'] ) && ! empty( $data['emergencyContactPhone'] ) && ! preg_match( $phone_pattern, $data['emergencyContactPhone'] ) ) {
			$errors->add( 'invalid_emergency_phone', __( 'Invalid emergency contact phone number format.', 'athlete-dashboard' ) );
		}

		// Validate injuries
		if ( isset( $data['injuries'] ) ) {
			if ( ! is_array( $data['injuries'] ) ) {
				$errors->add( 'invalid_injuries', __( 'Injuries must be an array.', 'athlete-dashboard' ) );
			} else {
				foreach ( $data['injuries'] as $index => $injury ) {
					if ( ! isset( $injury['name'], $injury['description'], $injury['date'], $injury['status'] ) ) {
						$errors->add(
							'invalid_injury_' . $index,
							__( 'Each injury must have name, description, date, and status.', 'athlete-dashboard' )
						);
					}
				}
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}
}
