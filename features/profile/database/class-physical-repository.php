<?php
/**
 * Physical Repository Class.
 *
 * @package AthleteDashboard\Features\Profile\Database
 */

namespace AthleteDashboard\Features\Profile\Database;

use WP_Error;

/**
 * Class Physical_Repository
 *
 * Handles database operations for physical measurements.
 */
class Physical_Repository {
	/**
	 * The table name.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'athlete_physical_measurements';
	}

	/**
	 * Get the latest physical data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array|WP_Error Physical data or error.
	 */
	public function get_latest_data( int $user_id ): array|WP_Error {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY date DESC 
            LIMIT 1",
			$user_id
		);

		$result = $wpdb->get_row( $query, ARRAY_A );

		if ( null === $result ) {
			// Return empty data with default units if no record exists
			return array(
				'height' => 0,
				'weight' => 0,
				'chest'  => null,
				'waist'  => null,
				'hips'   => null,
				'units'  => array(
					'height'       => 'cm',
					'weight'       => 'kg',
					'measurements' => 'cm',
				),
			);
		}

		return array(
			'height' => (float) $result['height'],
			'weight' => (float) $result['weight'],
			'chest'  => $result['chest'] ? (float) $result['chest'] : null,
			'waist'  => $result['waist'] ? (float) $result['waist'] : null,
			'hips'   => $result['hips'] ? (float) $result['hips'] : null,
			'units'  => array(
				'height'       => $result['units_height'] ?? 'cm',
				'weight'       => $result['units_weight'] ?? 'kg',
				'measurements' => $result['units_measurements'] ?? 'cm',
			),
		);
	}

	/**
	 * Save physical data for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $data    Physical data to save.
	 * @return array|WP_Error Saved data or error.
	 */
	public function save_data( int $user_id, array $data ): array|WP_Error {
		global $wpdb;

		$insert_data = array(
			'user_id'            => $user_id,
			'height'             => $data['height'],
			'weight'             => $data['weight'],
			'chest'              => $data['chest'] ?? null,
			'waist'              => $data['waist'] ?? null,
			'hips'               => $data['hips'] ?? null,
			'units_height'       => $data['units']['height'],
			'units_weight'       => $data['units']['weight'],
			'units_measurements' => $data['units']['measurements'] ?? 'cm',
		);

		$format = array(
			'%d',  // user_id
			'%f',  // height
			'%f',  // weight
			'%f',  // chest
			'%f',  // waist
			'%f',  // hips
			'%s',  // units_height
			'%s',  // units_weight
			'%s',  // units_measurements
		);

		$result = $wpdb->insert( $this->table_name, $insert_data, $format );

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to save physical data.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		return $this->get_latest_data( $user_id );
	}

	/**
	 * Get physical measurement history for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array|WP_Error History data or error.
	 */
	public function get_history( int $user_id, array $args = array() ): array|WP_Error {
		global $wpdb;

		$defaults = array(
			'limit'  => 10,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Get total count
		$total_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
			$user_id
		);
		$total       = (int) $wpdb->get_var( $total_query );

		// Get paginated results
		$query = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY date DESC 
            LIMIT %d OFFSET %d",
			$user_id,
			$args['limit'],
			$args['offset']
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( null === $results ) {
			$results = array();
		}

		// Format results
		$items = array_map(
			function ( $row ) {
				return array(
					'id'     => (int) $row['id'],
					'date'   => $row['date'],
					'height' => (float) $row['height'],
					'weight' => (float) $row['weight'],
					'chest'  => $row['chest'] ? (float) $row['chest'] : null,
					'waist'  => $row['waist'] ? (float) $row['waist'] : null,
					'hips'   => $row['hips'] ? (float) $row['hips'] : null,
					'units'  => array(
						'height'       => $row['units_height'],
						'weight'       => $row['units_weight'],
						'measurements' => $row['units_measurements'],
					),
				);
			},
			$results
		);

		return array(
			'items'  => $items,
			'total'  => $total,
			'limit'  => $args['limit'],
			'offset' => $args['offset'],
		);
	}
}
