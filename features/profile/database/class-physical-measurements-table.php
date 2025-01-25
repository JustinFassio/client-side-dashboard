<?php
/**
 * Physical Measurements Table Manager
 *
 * @package AthleteDashboard\Features\Profile\Database
 */

namespace AthleteDashboard\Features\Profile\Database;

/**
 * Class Physical_Measurements_Table
 *
 * Manages the physical measurements table structure and operations.
 */
class Physical_Measurements_Table {

	/**
	 * Get the table name with prefix.
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'athlete_physical_measurements';
	}

	/**
	 * Check if the table exists.
	 *
	 * @return bool
	 */
	public function table_exists(): bool {
		global $wpdb;
		$table_name = $this->get_table_name();
		return $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
	}

	/**
	 * Create the physical measurements table.
	 *
	 * @return true|string True on success, error message on failure.
	 */
	public function create_table() {
		global $wpdb;
		$table_name      = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            height float DEFAULT NULL,
            weight float DEFAULT NULL,
            unit_system varchar(10) NOT NULL DEFAULT 'metric',
            measurement_date datetime NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY measurement_date (measurement_date)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$result = dbDelta( $sql );

		if ( ! empty( $wpdb->last_error ) ) {
			return $wpdb->last_error;
		}

		return true;
	}

	/**
	 * Drop the physical measurements table.
	 *
	 * @return bool|string True on success, error message on failure.
	 */
	public function drop_table() {
		global $wpdb;
		$table_name = $this->get_table_name();

		$result = $wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		if ( $result === false ) {
			return $wpdb->last_error;
		}

		return true;
	}

	/**
	 * Get the latest measurement for a user.
	 *
	 * @param int $user_id User ID.
	 * @return object|null Measurement data or null if not found.
	 */
	public function get_latest_measurement( int $user_id ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE user_id = %d ORDER BY measurement_date DESC LIMIT 1",
				$user_id
			)
		);
	}

	/**
	 * Insert a new measurement record.
	 *
	 * @param array $data Measurement data.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert_measurement( array $data ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		return $wpdb->insert(
			$table_name,
			$data,
			array(
				'%d', // user_id
				'%f', // height
				'%f', // weight
				'%s', // unit_system
				'%s', // measurement_date
				'%s', // created_at
			)
		);
	}

	/**
	 * Get measurement history for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Array of measurement records.
	 */
	public function get_measurement_history( int $user_id, array $args = array() ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : 10;
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE user_id = %d ORDER BY measurement_date DESC LIMIT %d OFFSET %d",
				$user_id,
				$limit,
				$offset
			)
		);
	}

	/**
	 * Delete a measurement record.
	 *
	 * @param int $record_id Record ID.
	 * @param int $user_id   User ID for verification.
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public function delete_measurement( int $record_id, int $user_id ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		return $wpdb->delete(
			$table_name,
			array(
				'id'      => $record_id,
				'user_id' => $user_id,
			),
			array(
				'%d',
				'%d',
			)
		);
	}
}
