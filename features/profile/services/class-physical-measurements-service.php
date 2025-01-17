<?php
/**
 * Physical Measurements Service
 *
 * @package AthleteDashboard\Features\Profile\Services
 */

namespace AthleteDashboard\Features\Profile\Services;

use WP_Error;

class Physical_Measurements_Service {
	private $wpdb;
	private $table_name;

	public function __construct( $wpdb = null ) {
		global $wpdb;
		$global_wpdb      = $wpdb;
		$this->wpdb       = $wpdb ?? $global_wpdb;
		$this->table_name = $this->wpdb->prefix . 'physical_measurements';
	}

	/**
	 * Get measurements for a user
	 */
	public function get_measurements( int $user_id, array $args = array() ) {
		if ( ! $this->user_exists( $user_id ) ) {
			return new WP_Error( 'invalid_user', $this->__( 'Invalid user ID' ) );
		}

		$defaults = array(
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'limit'   => 10,
			'offset'  => 0,
		);

		$args    = $this->wp_parse_args( $args, $defaults );
		$order   = $this->esc_sql( $args['order'] );
		$orderby = $this->esc_sql( $args['orderby'] );

		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d",
			$user_id,
			$args['limit'],
			$args['offset']
		);

		$results = $this->wpdb->get_results( $query );
		if ( ! $results ) {
			return array();
		}

		return array_map( array( $this, 'format_measurement' ), $results );
	}

	/**
	 * Add a new measurement
	 */
	public function add_measurement( int $user_id, array $data ) {
		if ( ! $this->user_exists( $user_id ) ) {
			return new WP_Error( 'invalid_user', $this->__( 'Invalid user ID' ) );
		}

		$validation = $this->validate_measurement_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$measurement_data = array_merge(
			$data,
			array(
				'user_id'    => $user_id,
				'created_at' => $this->current_time( 'mysql' ),
			)
		);

		$formats = $this->get_column_formats();
		$result  = $this->wpdb->insert( $this->table_name, $measurement_data, $formats );

		if ( $result === false ) {
			return new WP_Error( 'db_error', $this->__( 'Failed to add measurement' ) );
		}

		return $this->wpdb->insert_id;
	}

	/**
	 * Update a measurement
	 */
	public function update_measurement( int $user_id, int $measurement_id, array $data ) {
		if ( ! $this->user_exists( $user_id ) ) {
			return new WP_Error( 'invalid_user', $this->__( 'Invalid user ID' ) );
		}

		$existing = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d AND user_id = %d",
				$measurement_id,
				$user_id
			)
		);

		if ( ! $existing ) {
			return new WP_Error( 'not_found', $this->__( 'Measurement not found' ) );
		}

		$validation = $this->validate_measurement_data( $data, true );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$formats      = $this->get_column_formats();
		$where        = array(
			'id'      => $measurement_id,
			'user_id' => $user_id,
		);
		$where_format = array( '%d', '%d' );

		$result = $this->wpdb->update( $this->table_name, $data, $where, $formats, $where_format );

		if ( $result === false ) {
			return new WP_Error( 'db_error', $this->__( 'Failed to update measurement' ) );
		}

		return true;
	}

	/**
	 * Delete a measurement
	 */
	public function delete_measurement( int $user_id, int $measurement_id ) {
		if ( ! $this->user_exists( $user_id ) ) {
			return new WP_Error( 'invalid_user', $this->__( 'Invalid user ID' ) );
		}

		$result = $this->wpdb->delete(
			$this->table_name,
			array(
				'id'      => $measurement_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);

		if ( $result === false ) {
			return new WP_Error( 'db_error', $this->__( 'Failed to delete measurement' ) );
		}

		return true;
	}

	/**
	 * Format a measurement for output
	 */
	public function format_measurement( $measurement ) {
		return array(
			'id'          => (int) $measurement->id,
			'user_id'     => (int) $measurement->user_id,
			'weight'      => isset( $measurement->weight ) ? (float) $measurement->weight : null,
			'height'      => isset( $measurement->height ) ? (float) $measurement->height : null,
			'unit_system' => $measurement->unit_system,
			'created_at'  => substr( $measurement->created_at, 0, 10 ),
		);
	}

	/**
	 * Validate measurement data
	 */
	protected function validate_measurement_data( array $data, bool $partial = false ) {
		if ( ! $partial && empty( $data ) ) {
			return new WP_Error( 'validation_error', $this->__( 'No measurement data provided' ) );
		}

		if ( isset( $data['unit_system'] ) && ! in_array( $data['unit_system'], array( 'metric', 'imperial' ) ) ) {
			return new WP_Error( 'validation_error', $this->__( 'Invalid unit system' ) );
		}

		$numeric_fields = array( 'weight', 'height' );
		foreach ( $numeric_fields as $field ) {
			if ( isset( $data[ $field ] ) && ! is_numeric( $data[ $field ] ) ) {
				return new WP_Error( 'validation_error', $this->__( 'Invalid numeric value' ) );
			}
		}

		return true;
	}

	/**
	 * Get column formats for database operations
	 */
	protected function get_column_formats() {
		return array(
			'user_id'     => '%d',
			'weight'      => '%f',
			'height'      => '%f',
			'unit_system' => '%s',
			'created_at'  => '%s',
		);
	}

	/**
	 * Check if a user exists
	 */
	protected function user_exists( int $user_id ): bool {
		return get_userdata( $user_id ) !== false;
	}

	/**
	 * Format a date
	 */
	protected function format_date( string $date ): string {
		return mysql2date( get_option( 'date_format' ), $date );
	}

	/**
	 * Get current time
	 */
	protected function current_time( string $type ): string {
		return current_time( $type );
	}

	/**
	 * Translate a string
	 */
	protected function __( string $text ): string {
		return __( $text, 'athlete-dashboard' );
	}

	/**
	 * Parse arguments
	 */
	protected function wp_parse_args( array $args, array $defaults ): array {
		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Escape SQL
	 */
	protected function esc_sql( string $sql ): string {
		return esc_sql( $sql );
	}
}
