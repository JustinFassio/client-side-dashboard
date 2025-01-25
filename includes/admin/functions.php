<?php
/**
 * Admin functions for athlete profile management
 */

namespace AthleteDashboard\Admin;

/**
 * Save athlete profile fields
 *
 * @param int $user_id The user ID
 * @return bool True on success, false on failure
 */
function save_athlete_profile_fields( $user_id ) {
	// Verify nonce
	if ( ! isset( $_POST['athlete_profile_nonce'] ) || ! wp_verify_nonce( $_POST['athlete_profile_nonce'], 'athlete_profile_update' ) ) {
		\AthleteDashboard\Tests\Logger::getInstance()->log( 'Athlete Profile: Invalid nonce for profile ' . $user_id );
		return false;
	}

	// Verify user capabilities
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		\AthleteDashboard\Tests\Logger::getInstance()->log( 'Athlete Profile: Unauthorized user attempt to edit profile ' . $user_id );
		return false;
	}

	// Get profile data
	$profile_data = isset( $_POST['athlete_profile'] ) ? $_POST['athlete_profile'] : array();

	// Store original values for sanitization monitoring
	$original_values = array();
	if ( isset( $profile_data['phone'] ) ) {
		$original_values['phone'] = $profile_data['phone'];
	}
	if ( isset( $profile_data['medical_notes'] ) ) {
		$original_values['medical_notes'] = $profile_data['medical_notes'];
	}
	if ( isset( $profile_data['emergency_contact_phone'] ) ) {
		$original_values['emergency_contact_phone'] = $profile_data['emergency_contact_phone'];
	}

	// Validate age
	if ( ! empty( $profile_data['age'] ) ) {
		$age = intval( $profile_data['age'] );
		if ( $age < 0 || $age > 120 ) {
			\AthleteDashboard\Tests\Logger::getInstance()->log( 'Athlete Profile: Invalid age (' . $age . ') for user ' . $user_id );
			return false;
		}
	}

	// Validate height
	if ( ! empty( $profile_data['height'] ) ) {
		$height = intval( $profile_data['height'] );
		if ( $height < 0 || $height > 300 ) {
			\AthleteDashboard\Tests\Logger::getInstance()->log( 'Athlete Profile: Invalid height (' . $height . ') for user ' . $user_id );
			return false;
		}
	}

	// Sanitize data
	$sanitized_data = array(
		'phone'                   => isset( $profile_data['phone'] ) ? preg_replace( '/[^0-9\-]/', '', $profile_data['phone'] ) : '',
		'age'                     => isset( $profile_data['age'] ) ? intval( $profile_data['age'] ) : '',
		'date_of_birth'           => isset( $profile_data['date_of_birth'] ) ? sanitize_text_field( $profile_data['date_of_birth'] ) : '',
		'height'                  => isset( $profile_data['height'] ) ? intval( $profile_data['height'] ) : '',
		'weight'                  => isset( $profile_data['weight'] ) ? floatval( $profile_data['weight'] ) : '',
		'gender'                  => isset( $profile_data['gender'] ) ? sanitize_text_field( $profile_data['gender'] ) : '',
		'dominant_side'           => isset( $profile_data['dominant_side'] ) ? sanitize_text_field( $profile_data['dominant_side'] ) : '',
		'medical_clearance'       => isset( $profile_data['medical_clearance'] ) ? '1' : '0',
		'medical_notes'           => isset( $profile_data['medical_notes'] ) ? wp_strip_all_tags( sanitize_textarea_field( $profile_data['medical_notes'] ) ) : '',
		'emergency_contact_name'  => isset( $profile_data['emergency_contact_name'] ) ? sanitize_text_field( $profile_data['emergency_contact_name'] ) : '',
		'emergency_contact_phone' => isset( $profile_data['emergency_contact_phone'] ) ? preg_replace( '/[^0-9\-]/', '', $profile_data['emergency_contact_phone'] ) : '',
		'injuries'                => array(),
	);

	// Track sanitization changes
	$sanitization_changes = array();
	foreach ( $original_values as $key => $value ) {
		if ( $value !== $sanitized_data[ $key ] ) {
			$sanitization_changes[ $key ] = array(
				'original'  => $value,
				'sanitized' => $sanitized_data[ $key ],
			);
		}
	}

	// Log sanitization changes
	if ( ! empty( $sanitization_changes ) ) {
		\AthleteDashboard\Tests\Logger::getInstance()->log( 'Athlete Profile Sanitization Changes for user ' . $user_id . ': ' . json_encode( $sanitization_changes ) );
	}

	// Handle injuries array
	if ( isset( $profile_data['injuries'] ) && is_array( $profile_data['injuries'] ) ) {
		foreach ( $profile_data['injuries'] as $injury ) {
			if ( isset( $injury['name'] ) && isset( $injury['details'] ) ) {
				$sanitized_data['injuries'][] = array(
					'id'      => isset( $injury['id'] ) ? intval( $injury['id'] ) : '',
					'name'    => sanitize_text_field( $injury['name'] ),
					'details' => sanitize_textarea_field( $injury['details'] ),
				);
			}
		}
	}

	// Save data
	update_user_meta( $user_id, '_athlete_profile_data', $sanitized_data );

	return true;
}
