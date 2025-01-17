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
		$units       = get_user_meta( $user_id, 'physical_units', true );
		$preferences = get_user_meta( $user_id, 'physical_preferences', true );

		// Set default values if not found
		$data = array(
			'height'      => $height ? floatval( $height ) : 175,
			'weight'      => $weight ? floatval( $weight ) : 70,
			'units'       => $units ?: array(
				'height' => 'cm',
				'weight' => 'kg',
			),
			'preferences' => $preferences ?: array(
				'showMetric'   => true,
				'trackHistory' => true,
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

		// Validate BMI
		$height_m  = $data['height'] / 100; // Convert to meters
		$weight_kg = $data['weight'];

		if ( $data['units']['weight'] === 'lbs' ) {
			$weight_kg = $data['weight'] * 0.453592; // Convert lbs to kg
		}

		$bmi = $weight_kg / ( $height_m * $height_m );

		if ( $bmi < self::MIN_BMI || $bmi > self::MAX_BMI ) {
			return new WP_Error(
				'invalid_bmi',
				sprintf(
					__( 'BMI must be between %1$f and %2$f. Current BMI: %3$f', 'athlete-dashboard' ),
					self::MIN_BMI,
					self::MAX_BMI,
					$bmi
				),
				array(
					'status'  => 400,
					'bmi'     => $bmi,
					'min_bmi' => self::MIN_BMI,
					'max_bmi' => self::MAX_BMI,
				)
			);
		}

		// Update user meta
		update_user_meta( $user_id, 'physical_height', $data['height'] );
		update_user_meta( $user_id, 'physical_weight', $data['weight'] );
		update_user_meta( $user_id, 'physical_units', $data['units'] );

		if ( isset( $data['preferences'] ) ) {
			update_user_meta( $user_id, 'physical_preferences', $data['preferences'] );
		}

		// Clear cache
		$cache_key = "physical_data_{$user_id}";
		wp_cache_delete( $cache_key, self::CACHE_GROUP );
		error_log( 'Physical_Service: Cache cleared for user ' . $user_id );

		// If history tracking is enabled, save to history
		$preferences = $data['preferences'] ?? get_user_meta( $user_id, 'physical_preferences', true );
		if ( $preferences && ! empty( $preferences['trackHistory'] ) ) {
			$this->save_to_history( $user_id, $data );
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

		$items = $wpdb->get_results( $query, ARRAY_A );

		if ( $wpdb->last_error ) {
			error_log( 'Physical_Service: Database error: ' . $wpdb->last_error );
			return new WP_Error(
				'db_error',
				__( 'Failed to retrieve physical history.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'items'  => $items ?: array(),
			'total'  => (int) $total,
			'limit'  => $limit,
			'offset' => $offset,
		);
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

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'date'    => current_time( 'mysql' ),
				'height'  => $data['height'],
				'weight'  => $data['weight'],
				'units'   => wp_json_encode( $data['units'] ),
			),
			array( '%d', '%s', '%f', '%f', '%s' )
		);

		if ( false === $result ) {
			error_log( 'Physical_Service: Failed to save history: ' . $wpdb->last_error );
			return new WP_Error(
				'history_save_failed',
				__( 'Failed to save physical measurement history.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}
}
