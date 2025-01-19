<?php
/**
 * Physical Measurements Table Migration
 *
 * @package AthleteDashboard\Features\Profile\Database\Migrations
 */

namespace AthleteDashboard\Features\Profile\Database\Migrations;

use WP_Error;

/**
 * Class Physical_Measurements_Table
 */
class Physical_Measurements_Table {

	/**
	 * Run the migration.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function up(): bool|WP_Error {
		global $wpdb;
		$table_name = $wpdb->prefix . 'athlete_physical_measurements';
		$charset_collate = $wpdb->get_charset_collate();

		error_log('Physical_Measurements_Table: Starting table creation/update');

		// Check if table already exists
		if ($this->exists()) {
			error_log('Physical_Measurements_Table: Table already exists');
			return true;
		}

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			height float DEFAULT 0,
			weight float DEFAULT 0,
			chest float DEFAULT NULL,
			waist float DEFAULT NULL,
			hips float DEFAULT NULL,
			units_height varchar(10) NOT NULL DEFAULT 'cm',
			units_weight varchar(10) NOT NULL DEFAULT 'kg',
			units_measurements varchar(10) NOT NULL DEFAULT 'cm',
			date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY date (date)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);

		error_log('Physical_Measurements_Table: dbDelta result: ' . wp_json_encode($result));

		// Check if table was created successfully
		if (!$this->exists()) {
			error_log('Physical_Measurements_Table: Table creation failed');
			return new WP_Error(
				'table_creation_failed',
				__('Failed to create physical measurements table.', 'athlete-dashboard'),
				array('status' => 500)
			);
		}

		error_log('Physical_Measurements_Table: Table creation/update completed successfully');
		return true;
	}

	/**
	 * Check if the table exists.
	 *
	 * @return bool True if table exists, false otherwise.
	 */
	public function exists(): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . 'athlete_physical_measurements';
		return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
	}

	/**
	 * Drop the table.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function down(): bool|WP_Error {
		global $wpdb;
		$table_name = $wpdb->prefix . 'athlete_physical_measurements';
		
		if (!$this->exists()) {
			error_log('Physical_Measurements_Table: Table does not exist, nothing to drop');
			return true;
		}

		$result = $wpdb->query("DROP TABLE IF EXISTS $table_name");

		if (false === $result) {
			error_log('Physical_Measurements_Table: Failed to drop table');
			return new WP_Error(
				'table_drop_failed',
				__('Failed to drop physical measurements table.', 'athlete-dashboard'),
				array('status' => 500)
			);
		}

		error_log('Physical_Measurements_Table: Table dropped successfully');
		return true;
	}
}
