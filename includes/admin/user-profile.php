<?php
/**
 * Athlete Dashboard - User Profile Admin Integration
 * Adds custom athlete profile fields to the WordPress Admin User Profile page.
 */

namespace AthleteDashboard\Admin;

/**
 * Add the athlete profile sections to the user profile
 */
function add_athlete_profile_fields( $user ) {
	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	// Get existing profile data
	$profile_data = get_user_meta( $user->ID, '_athlete_profile_data', true );
	$profile_data = is_array( $profile_data ) ? $profile_data : array();

	// Default values
	$defaults = array(
		'phone'                   => '',
		'age'                     => '',
		'date_of_birth'           => '',
		'height'                  => '',
		'weight'                  => '',
		'gender'                  => '',
		'dominant_side'           => '',
		'medical_clearance'       => false,
		'medical_notes'           => '',
		'emergency_contact_name'  => '',
		'emergency_contact_phone' => '',
		'injuries'                => array(),
	);

	$profile_data = wp_parse_args( $profile_data, $defaults );

	// Add nonce field for security
	wp_nonce_field( 'athlete_profile_update', 'athlete_profile_nonce' );
	?>
	
	<div class="athlete-profile-admin">
		<h2><?php _e( 'Athlete Profile', 'athlete-dashboard' ); ?></h2>
		
		<!-- Basic Information -->
		<div class="athlete-profile-section">
			<h3><?php _e( 'Basic Information', 'athlete-dashboard' ); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="phone"><?php _e( 'Phone', 'athlete-dashboard' ); ?></label></th>
					<td>
						<input type="tel" 
								name="athlete_profile[phone]" 
								id="phone" 
								value="<?php echo esc_attr( $profile_data['phone'] ); ?>" 
								class="regular-text" />
					</td>
				</tr>
				<tr>
					<th><label for="age"><?php _e( 'Age', 'athlete-dashboard' ); ?></label></th>
					<td>
						<input type="number" 
								name="athlete_profile[age]" 
								id="age" 
								value="<?php echo esc_attr( $profile_data['age'] ); ?>" 
								class="regular-text"
								min="13"
								max="120" />
						<p class="description"><?php _e( 'Age must be between 13 and 120', 'athlete-dashboard' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="date_of_birth"><?php _e( 'Date of Birth', 'athlete-dashboard' ); ?></label></th>
					<td>
						<input type="date" 
								name="athlete_profile[date_of_birth]" 
								id="date_of_birth" 
								value="<?php echo esc_attr( $profile_data['date_of_birth'] ); ?>" 
								class="regular-text" />
					</td>
				</tr>
			</table>
		</div>

		<!-- Physical Information -->
		<div class="athlete-profile-section">
			<h3><?php _e( 'Physical Information', 'athlete-dashboard' ); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="height"><?php _e( 'Height (cm)', 'athlete-dashboard' ); ?></label></th>
					<td>
						<input type="number" 
								name="athlete_profile[height]" 
								id="height" 
								value="<?php echo esc_attr( $profile_data['height'] ); ?>" 
								class="regular-text" 
								step="1" />
					</td>
				</tr>
				<tr>
					<th><label for="weight"><?php _e( 'Weight (kg)', 'athlete-dashboard' ); ?></label></th>
					<td>
						<input type="number" 
								name="athlete_profile[weight]" 
								id="weight" 
								value="<?php echo esc_attr( $profile_data['weight'] ); ?>" 
								class="regular-text" 
								step="0.1" />
					</td>
				</tr>
				<tr>
					<th><label for="gender"><?php _e( 'Gender', 'athlete-dashboard' ); ?></label></th>
					<td>
						<select name="athlete_profile[gender]" id="gender" class="regular-text">
							<option value=""><?php _e( 'Select Gender', 'athlete-dashboard' ); ?></option>
							<option value="male" <?php selected( $profile_data['gender'], 'male' ); ?>><?php _e( 'Male', 'athlete-dashboard' ); ?></option>
							<option value="female" <?php selected( $profile_data['gender'], 'female' ); ?>><?php _e( 'Female', 'athlete-dashboard' ); ?></option>
							<option value="other" <?php selected( $profile_data['gender'], 'other' ); ?>><?php _e( 'Other', 'athlete-dashboard' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="dominant_side"><?php _e( 'Dominant Side', 'athlete-dashboard' ); ?></label></th>
					<td>
						<select name="athlete_profile[dominant_side]" id="dominant_side" class="regular-text">
							<option value=""><?php _e( 'Select Dominant Side', 'athlete-dashboard' ); ?></option>
							<option value="left" <?php selected( $profile_data['dominant_side'], 'left' ); ?>><?php _e( 'Left', 'athlete-dashboard' ); ?></option>
							<option value="right" <?php selected( $profile_data['dominant_side'], 'right' ); ?>><?php _e( 'Right', 'athlete-dashboard' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<!-- Medical Information -->
		<div class="athlete-profile-section">
			<h3><?php _e( 'Medical Information', 'athlete-dashboard' ); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="medical_clearance"><?php _e( 'Medical Clearance', 'athlete-dashboard' ); ?></label></th>
					<td>
						<input type="checkbox" 
								name="athlete_profile[medical_clearance]" 
								id="medical_clearance" 
								value="1" 
								<?php checked( $profile_data['medical_clearance'], true ); ?> />
						<span class="description"><?php _e( 'Athlete has medical clearance to participate', 'athlete-dashboard' ); ?></span>
					</td>
				</tr>
				<tr>
					<th><label for="medical_notes"><?php _e( 'Medical Notes', 'athlete-dashboard' ); ?></label></th>
					<td>
						<textarea name="athlete_profile[medical_notes]" 
									id="medical_notes" 
									rows="4" 
									class="regular-text"><?php echo esc_textarea( $profile_data['medical_notes'] ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label for="emergency_contact_name"><?php _e( 'Emergency Contact Name', 'athlete-dashboard' ); ?></label></th>
					<td>
						<input type="text" 
								name="athlete_profile[emergency_contact_name]" 
								id="emergency_contact_name" 
								value="<?php echo esc_attr( $profile_data['emergency_contact_name'] ); ?>" 
								class="regular-text" />
					</td>
				</tr>
				<tr>
					<th><label for="emergency_contact_phone"><?php _e( 'Emergency Contact Phone', 'athlete-dashboard' ); ?></label></th>
					<td>
						<input type="tel" 
								name="athlete_profile[emergency_contact_phone]" 
								id="emergency_contact_phone" 
								value="<?php echo esc_attr( $profile_data['emergency_contact_phone'] ); ?>" 
								class="regular-text" />
					</td>
				</tr>
			</table>
		</div>

		<!-- Injuries & Limitations -->
		<div class="athlete-profile-section">
			<h3><?php _e( 'Injuries & Limitations', 'athlete-dashboard' ); ?></h3>
			<div class="injuries-list">
				<?php if ( ! empty( $profile_data['injuries'] ) ) : ?>
					<?php foreach ( $profile_data['injuries'] as $index => $injury ) : ?>
						<div class="injury-item">
							<input type="hidden" 
									name="athlete_profile[injuries][<?php echo $index; ?>][id]" 
									value="<?php echo esc_attr( $injury['id'] ); ?>" />
							<table class="form-table">
								<tr>
									<th><label><?php _e( 'Injury Name', 'athlete-dashboard' ); ?></label></th>
									<td>
										<input type="text" 
												name="athlete_profile[injuries][<?php echo $index; ?>][name]" 
												value="<?php echo esc_attr( $injury['name'] ); ?>" 
												class="regular-text" />
									</td>
								</tr>
								<tr>
									<th><label><?php _e( 'Details', 'athlete-dashboard' ); ?></label></th>
									<td>
										<textarea name="athlete_profile[injuries][<?php echo $index; ?>][details]" 
													rows="3" 
													class="regular-text"><?php echo esc_textarea( $injury['details'] ); ?></textarea>
									</td>
								</tr>
							</table>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="description"><?php _e( 'No injuries recorded.', 'athlete-dashboard' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<style>
		.athlete-profile-admin {
			background: #fff;
			padding: 20px;
			margin: 20px 0;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}

		.athlete-profile-section {
			margin-bottom: 30px;
			padding: 20px;
			background: #f9f9f9;
			border-radius: 6px;
		}

		.athlete-profile-section h3 {
			margin-top: 0;
			padding-bottom: 10px;
			border-bottom: 2px solid #ddff0e;
			color: #1a1a1a;
		}

		.athlete-profile-section .form-table {
			margin-top: 15px;
		}

		.athlete-profile-section input[type="text"],
		.athlete-profile-section input[type="tel"],
		.athlete-profile-section input[type="number"],
		.athlete-profile-section input[type="date"],
		.athlete-profile-section select,
		.athlete-profile-section textarea {
			width: 100%;
			max-width: 400px;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
			background-color: #fff;
		}

		.athlete-profile-section input:focus,
		.athlete-profile-section select:focus,
		.athlete-profile-section textarea:focus {
			border-color: #ddff0e;
			box-shadow: 0 0 0 1px #ddff0e;
			outline: none;
		}

		.injury-item {
			background: #fff;
			padding: 15px;
			margin-bottom: 15px;
			border-radius: 4px;
			border: 1px solid #ddd;
		}

		.description {
			color: #666;
			font-style: italic;
			margin-top: 5px;
		}
	</style>
	<?php
}

/**
 * Save the athlete profile data
 *
 * @param int $user_id The ID of the user being edited.
 * @return bool|void False on failure. Void on success.
 */
function save_athlete_profile_fields( $user_id ) {
	// Verify user capabilities
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	// Verify nonce
	if ( ! isset( $_POST['athlete_profile_nonce'] ) || ! wp_verify_nonce( $_POST['athlete_profile_nonce'], 'athlete_profile_update' ) ) {
		return false;
	}

	if ( ! isset( $_POST['athlete_profile'] ) ) {
		return false;
	}

	$profile_data = wp_unslash( $_POST['athlete_profile'] );

	// Input validation
	$age = isset( $profile_data['age'] ) ? absint( $profile_data['age'] ) : '';
	if ( $age && ( $age < 13 || $age > 120 ) ) {
		return false;
	}

	$height = isset( $profile_data['height'] ) ? absint( $profile_data['height'] ) : '';
	if ( $height && $height > 300 ) { // Max height validation (300cm = ~9.8ft)
		return false;
	}

	$weight = isset( $profile_data['weight'] ) ? floatval( $profile_data['weight'] ) : '';
	if ( $weight && ( $weight < 20 || $weight > 500 ) ) { // Reasonable weight range in kg
		return false;
	}

	// Validate gender values
	$valid_genders = array( '', 'male', 'female', 'other' );
	$gender        = isset( $profile_data['gender'] ) ? sanitize_text_field( $profile_data['gender'] ) : '';
	if ( ! in_array( $gender, $valid_genders, true ) ) {
		return false;
	}

	// Validate dominant side values
	$valid_sides   = array( '', 'left', 'right' );
	$dominant_side = isset( $profile_data['dominant_side'] ) ? sanitize_text_field( $profile_data['dominant_side'] ) : '';
	if ( ! in_array( $dominant_side, $valid_sides, true ) ) {
		return false;
	}

	// Sanitize the data
	$sanitized_data = array(
		'phone'                   => isset( $profile_data['phone'] ) ? sanitize_text_field( $profile_data['phone'] ) : '',
		'age'                     => $age,
		'date_of_birth'           => isset( $profile_data['date_of_birth'] ) ? sanitize_text_field( $profile_data['date_of_birth'] ) : '',
		'height'                  => $height,
		'weight'                  => $weight,
		'gender'                  => $gender,
		'dominant_side'           => $dominant_side,
		'medical_clearance'       => isset( $profile_data['medical_clearance'] ),
		'medical_notes'           => isset( $profile_data['medical_notes'] ) ? sanitize_textarea_field( $profile_data['medical_notes'] ) : '',
		'emergency_contact_name'  => isset( $profile_data['emergency_contact_name'] ) ? sanitize_text_field( $profile_data['emergency_contact_name'] ) : '',
		'emergency_contact_phone' => isset( $profile_data['emergency_contact_phone'] ) ? sanitize_text_field( $profile_data['emergency_contact_phone'] ) : '',
		'injuries'                => array(),
	);

	// Sanitize injuries
	if ( ! empty( $profile_data['injuries'] ) && is_array( $profile_data['injuries'] ) ) {
		foreach ( $profile_data['injuries'] as $injury ) {
			if ( ! is_array( $injury ) ) {
				continue;
			}
			$sanitized_data['injuries'][] = array(
				'id'      => isset( $injury['id'] ) ? sanitize_text_field( $injury['id'] ) : '',
				'name'    => isset( $injury['name'] ) ? sanitize_text_field( $injury['name'] ) : '',
				'details' => isset( $injury['details'] ) ? sanitize_textarea_field( $injury['details'] ) : '',
			);
		}
	}

	// Update user meta
	return update_user_meta( $user_id, '_athlete_profile_data', $sanitized_data );
}

// Add the hooks
add_action( 'show_user_profile', __NAMESPACE__ . '\\add_athlete_profile_fields' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\\add_athlete_profile_fields' );
add_action( 'personal_options_update', __NAMESPACE__ . '\\save_athlete_profile_fields' );
add_action( 'edit_user_profile_update', __NAMESPACE__ . '\\save_athlete_profile_fields' );
