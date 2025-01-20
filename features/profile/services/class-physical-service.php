<?php
/**
 * Physical Service.
 *
 * @package AthleteDashboard\Features\Profile\Services
 */

namespace AthleteDashboard\Features\Profile\Services;

use WP_Error;

/**
 * Class Physical_Service
 *
 * Handles physical measurement data operations.
 */
class Physical_Service {
	/**
	 * Cache group for physical data.
	 */
	private const CACHE_GROUP = 'athlete_physical_data';

	/**
	 * Cache expiration in seconds (24 hours).
	 */
	private const CACHE_EXPIRATION = 86400;

	/**
	 * Minimum allowed BMI value.
	 */
	private const MIN_BMI = 15.0;

	/**
	 * Maximum allowed BMI value.
	 */
	private const MAX_BMI = 40.0;

	/**
	 * Get physical data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Physical data or error.
	 */
	public function get_physical_data( int $user_id ): array|WP_Error {
		error_log( 'Physical_Service: Getting physical data for user ' . $user_id );

		// Try to get from cache first
		$cache_key   = "physical_data_{$user_id}";
		$cached_data = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached_data ) {
			error_log( 'Physical_Service: Returning cached data' );
			return $cached_data;
		}

		// Get data from user meta
		$height      = get_user_meta( $user_id, 'physical_height', true );
		$weight      = get_user_meta( $user_id, 'physical_weight', true );
		$chest       = get_user_meta( $user_id, 'physical_chest', true );
		$waist       = get_user_meta( $user_id, 'physical_waist', true );
		$hips        = get_user_meta( $user_id, 'physical_hips', true );
		$units       = get_user_meta( $user_id, 'physical_units', true );
		$preferences = get_user_meta( $user_id, 'physical_preferences', true );

		// Set default values if not found
		$data = array(
			'height'      => $height ? floatval( $height ) : 0,
			'weight'      => $weight ? floatval( $weight ) : 0,
			'chest'       => $chest ? floatval( $chest ) : null,
			'waist'       => $waist ? floatval( $waist ) : null,
			'hips'        => $hips ? floatval( $hips ) : null,
			'units'       => $units ?: array(
				'height'       => 'cm',
				'weight'       => 'kg',
				'measurements' => 'cm',
			),
			'preferences' => $preferences ?: array(
				'showMetric' => true,
			),
		);

		// Cache the data
		wp_cache_set( $cache_key, $data, self::CACHE_GROUP, self::CACHE_EXPIRATION );
		error_log( 'Physical_Service: Data cached for user ' . $user_id );

		return $data;
	}

	/**
	 * Update physical data for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Physical data to update.
	 * @return array|WP_Error Updated data or error.
	 */
	public function update_physical_data( int $user_id, array $data ): array|WP_Error {
		error_log( 'Physical_Service: Updating physical data for user ' . $user_id );
		error_log( 'Data: ' . wp_json_encode( $data ) );

		// Validate required fields
		if ( ! isset( $data['height'] ) || ! isset( $data['weight'] ) || ! isset( $data['units'] ) ) {
			error_log( 'Physical_Service: Missing required fields' );
			return new WP_Error(
				'missing_data',
				__( 'Height, weight, and units are required.', 'athlete-dashboard' ),
				array( 'status' => 400 )
			);
		}

		// Convert height to centimeters for storage and BMI calculation
		$height_cm = $data['height'];
		error_log( 'Physical_Service: Original height: ' . $height_cm . ' ' . $data['units']['height'] );

		if ( $data['units']['height'] === 'ft' ) {
			// If we have separate feet and inches, use those
			if ( isset( $data['heightFeet'] ) && isset( $data['heightInches'] ) ) {
				$feet         = (float) $data['heightFeet'];
				$inches       = (float) $data['heightInches'];
				$total_inches = ( $feet * 12 ) + $inches;
				$height_cm    = round( $total_inches * 2.54, 2 );
				error_log( 'Physical_Service: Converted from ft/in: ' . $feet . 'ft ' . $inches . 'in = ' . $height_cm . 'cm' );
			} else {
				// Convert from decimal feet to cm
				$height_cm = round( $data['height'] * 30.48, 2 );
				error_log( 'Physical_Service: Converted from decimal feet: ' . $data['height'] . 'ft = ' . $height_cm . 'cm' );
			}
		} elseif ( $data['units']['height'] !== 'cm' ) {
			return new WP_Error(
				'invalid_unit',
				__( 'Height must be in feet/inches or centimeters.', 'athlete-dashboard' ),
				array( 'status' => 400 )
			);
		}

		// Validate height range (in cm)
		if ( $height_cm < 50 || $height_cm > 300 ) {
			return new WP_Error(
				'invalid_height',
				sprintf(
					__( 'Height must be between %1$d and %2$d cm. Current height: %3$.2f cm', 'athlete-dashboard' ),
					50,
					300,
					$height_cm
				),
				array(
					'status'    => 400,
					'height_cm' => $height_cm,
				)
			);
		}

		// Convert to meters for BMI calculation
		$height_m = $height_cm / 100;
		error_log( 'Physical_Service: Final height in meters: ' . $height_m );

		// Store the height in centimeters in the database
		$data['height']       = $height_cm;
		$data['units_height'] = 'cm'; // Always store in cm

		// Convert weight to kg for BMI calculation
		$weight_kg = $data['weight'];
		error_log( 'Physical_Service: Original weight: ' . $weight_kg . ' ' . $data['units']['weight'] );
		if ( $data['units']['weight'] === 'lbs' ) {
			$weight_kg = $data['weight'] * 0.453592; // Convert lbs to kg
			error_log( 'Physical_Service: Converted weight from lbs to kg: ' . $weight_kg );
		} elseif ( $data['units']['weight'] !== 'kg' ) {
			return new WP_Error(
				'invalid_unit',
				__( 'Weight must be in pounds or kilograms.', 'athlete-dashboard' ),
				array( 'status' => 400 )
			);
		}
		error_log( 'Physical_Service: Final weight in kg: ' . $weight_kg );

		// Calculate and validate BMI
		$bmi = $weight_kg / ( $height_m * $height_m );
		error_log( 'Physical_Service: Calculated BMI: ' . $bmi );

		if ( $bmi < self::MIN_BMI || $bmi > self::MAX_BMI ) {
			return new WP_Error(
				'invalid_bmi',
				sprintf(
					__( 'The provided height and weight result in an invalid BMI of %1$.1f. BMI must be between %2$.1f and %3$.1f.', 'athlete-dashboard' ),
					$bmi,
					self::MIN_BMI,
					self::MAX_BMI
				),
				array(
					'status'    => 400,
					'bmi'       => $bmi,
					'min_bmi'   => self::MIN_BMI,
					'max_bmi'   => self::MAX_BMI,
					'height_m'  => $height_m,
					'weight_kg' => $weight_kg,
				)
			);
		}

		// Update user meta with original values (not converted)
		update_user_meta( $user_id, 'physical_height', $data['height'] );
		update_user_meta( $user_id, 'physical_weight', $data['weight'] );
		update_user_meta( $user_id, 'physical_units', $data['units'] );

		// Update optional measurements
		if ( isset( $data['chest'] ) ) {
			update_user_meta( $user_id, 'physical_chest', $data['chest'] );
		}
		if ( isset( $data['waist'] ) ) {
			update_user_meta( $user_id, 'physical_waist', $data['waist'] );
		}
		if ( isset( $data['hips'] ) ) {
			update_user_meta( $user_id, 'physical_hips', $data['hips'] );
		}

		if ( isset( $data['preferences'] ) ) {
			update_user_meta( $user_id, 'physical_preferences', $data['preferences'] );
		}

		// Clear cache
		$cache_key = "physical_data_{$user_id}";
		wp_cache_delete( $cache_key, self::CACHE_GROUP );
		error_log( 'Physical_Service: Cache cleared for user ' . $user_id );

		error_log( 'Physical_Service: CHECKPOINT - Before history save attempt' );
		// Always save to history
		error_log( 'Physical_Service: About to save to history...' );
		error_log( 'Physical_Service: History data = ' . wp_json_encode( $data ) );
		$history_result = $this->save_to_history( $user_id, $data );
		error_log( 'Physical_Service: History save result = ' . ( $history_result === true ? 'success' : 'error' ) );
		if ( is_wp_error( $history_result ) ) {
			error_log( 'Physical_Service: History save error: ' . $history_result->get_error_message() );
		}

		return $this->get_physical_data( $user_id );
	}

	/**
	 * Get physical measurement history for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array|WP_Error History data or error.
	 */
	public function get_physical_history( int $user_id, array $args = array() ): array|WP_Error {
		error_log( 'Physical_Service: Getting physical history for user ' . $user_id );
		error_log( 'Args: ' . wp_json_encode( $args ) );

		global $wpdb;
		$table_name = $wpdb->prefix . 'athlete_physical_measurements';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			error_log( 'Physical_Service: History table not found' );
			return new WP_Error(
				'no_table',
				__( 'History table not found. Please contact support.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		// Get total count
		$total_query = "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d";
		$total       = $wpdb->get_var( $wpdb->prepare( $total_query, $user_id ) );

		// Get paginated results
		$limit  = isset( $args['limit'] ) ? min( abs( intval( $args['limit'] ) ), 50 ) : 10;
		$offset = isset( $args['offset'] ) ? abs( intval( $args['offset'] ) ) : 0;

		$query = $wpdb->prepare(
			"SELECT * FROM {$table_name} 
            WHERE user_id = %d 
            ORDER BY date DESC 
            LIMIT %d OFFSET %d",
			$user_id,
			$limit,
			$offset
		);

		error_log( 'Physical_Service: History query = ' . $query );
		$items = $wpdb->get_results( $query, ARRAY_A );
		error_log( 'Physical_Service: History items = ' . wp_json_encode( $items ) );

		if ( $wpdb->last_error ) {
			error_log( 'Physical_Service: Database error: ' . $wpdb->last_error );
			return new WP_Error(
				'db_error',
				__( 'Failed to retrieve physical history.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		$response = array(
			'items'  => $items ?: array(),
			'total'  => (int) $total,
			'limit'  => $limit,
			'offset' => $offset,
		);
		error_log( 'Physical_Service: History response = ' . wp_json_encode( $response ) );

		return $response;
	}

	/**
	 * Save physical data to history.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Physical data to save.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function save_to_history( int $user_id, array $data ): bool|WP_Error {
		global $wpdb;
		$table_name = $wpdb->prefix . 'athlete_physical_measurements';

		error_log( 'Physical_Service: save_to_history called' );
		error_log( 'Physical_Service: Table name = ' . $table_name );
		error_log( 'Physical_Service: wpdb prefix = ' . $wpdb->prefix );

		// Log the data we're about to insert
		$insert_data = array(
			'user_id'            => $user_id,
			'date'               => current_time( 'mysql' ),
			'height'             => $data['height'],  // Already in cm from update_physical_data
			'weight'             => $data['weight'],  // Keep original value for history tracking
			'units_height'       => 'cm',            // Always store height in cm
			'units_weight'       => $data['units']['weight'] ?? 'kg',  // Original unit for weight
			'units_measurements' => $data['units']['measurements'] ?? 'cm',
		);

		// Additional validation for history data
		if ( ! is_numeric( $insert_data['height'] ) || $insert_data['height'] < 50 || $insert_data['height'] > 300 ) {
			error_log( 'Physical_Service: Invalid height value for history: ' . $insert_data['height'] );
			return new WP_Error(
				'invalid_height',
				__( 'Invalid height value for history record.', 'athlete-dashboard' ),
				array( 'status' => 400 )
			);
		}

		error_log( 'Physical_Service: Attempting to insert data: ' . wp_json_encode( $insert_data ) );

		$result = $wpdb->insert(
			$table_name,
			$insert_data,
			array( '%d', '%s', '%f', '%f', '%s', '%s', '%s' )
		);

		error_log( 'Physical_Service: last_query = ' . $wpdb->last_query );
		error_log( 'Physical_Service: last_error = ' . $wpdb->last_error );
		error_log( 'Physical_Service: Insert result = ' . ( $result === false ? 'false' : 'true' ) );

		if ( false === $result ) {
			error_log( 'Physical_Service: Failed to save history: ' . $wpdb->last_error );
			return new WP_Error(
				'history_save_failed',
				__( 'Failed to save physical measurement history.', 'athlete-dashboard' ),
				array(
					'status' => 500,
					'error'  => $wpdb->last_error,
					'query'  => $wpdb->last_query,
				)
			);
		}

		error_log( 'Physical_Service: Successfully saved to history' );
		return true;
	}

	/**
	 * Check if table exists
	 *
	 * @param string $table_name Table name to check.
	 * @return bool Whether table exists.
	 */
	private function table_exists( $table_name ) {
		global $wpdb;
		$table_name = esc_sql( $table_name );
		$query      = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		$result     = $wpdb->get_var( $query );
		return ! empty( $result );
	}

	/**
	 * Get total records count
	 *
	 * @return int Total number of records
	 */
	private function get_total_records() {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name}"
		);
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get records with pagination
	 *
	 * @param array $args Query arguments
	 * @return array Records found
	 */
	private function get_records( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'orderby' => 'id',
			'order'   => 'DESC',
			'offset'  => 0,
			'limit'   => 10,
		);

		$args    = wp_parse_args( $args, $defaults );
		$orderby = esc_sql( $args['orderby'] );
		$order   = esc_sql( $args['order'] );
		$offset  = absint( $args['offset'] );
		$limit   = absint( $args['limit'] );

		$query = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
			ORDER BY %s %s
			LIMIT %d OFFSET %d",
			$orderby,
			$order,
			$limit,
			$offset
		);

		$cache_key = 'physical_records_' . md5( $query );
		$results   = wp_cache_get( $cache_key );

		if ( false === $results ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $results, '', 3600 );
		}

		return $results;
	}
}
