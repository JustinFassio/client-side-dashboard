<?php
/**
 * Physical Measurements Table Migration.
 *
 * @package AthleteDashboard\Features\Profile\Database\Migrations
 */

namespace AthleteDashboard\Features\Profile\Database\Migrations;

use AthleteDashboard\Core\Database\Migration;

/**
 * Class Physical_Measurements_Table
 *
 * Creates and manages the physical measurements table.
 */
class Physical_Measurements_Table extends Migration {
	/**
	 * Get the table name.
	 *
	 * @return string
	 */
	protected function get_table_name(): string {
		return $this->wpdb->prefix . 'athlete_physical_measurements';
	}

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public function up(): void {
		$table_name      = $this->get_table_name();
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            height decimal(5,2) DEFAULT NULL,
            weight decimal(5,2) DEFAULT NULL,
            chest decimal(5,2) DEFAULT NULL,
            waist decimal(5,2) DEFAULT NULL,
            hips decimal(5,2) DEFAULT NULL,
            units_height varchar(2) NOT NULL DEFAULT 'cm',
            units_weight varchar(3) NOT NULL DEFAULT 'kg',
            units_measurements varchar(2) NOT NULL DEFAULT 'cm',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY date (date)
        ) {$charset_collate};";

		$this->run_sql( $sql );
	}

	/**
	 * Reverse the migration.
	 *
	 * @return void
	 */
	public function down(): void {
		$table_name = $this->get_table_name();
		$sql        = "DROP TABLE IF EXISTS {$table_name};";
		$this->run_sql( $sql );
	}

	/**
	 * Migrate legacy data.
	 *
	 * @return void
	 */
	public function migrate_legacy_data(): void {
		global $wpdb;
		$table_name = $this->get_table_name();

		// Get all users with physical data
		$users = get_users(
			array(
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => 'user_height',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'user_weight',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		foreach ( $users as $user ) {
			$height = get_user_meta( $user->ID, 'user_height', true );
			$weight = get_user_meta( $user->ID, 'user_weight', true );
			$units  = get_user_meta( $user->ID, 'measurement_units', true ) ?: 'metric';

			if ( $height || $weight ) {
				$wpdb->insert(
					$table_name,
					array(
						'user_id'            => $user->ID,
						'height'             => $height ?: null,
						'weight'             => $weight ?: null,
						'units_height'       => $units === 'imperial' ? 'ft' : 'cm',
						'units_weight'       => $units === 'imperial' ? 'lbs' : 'kg',
						'units_measurements' => $units === 'imperial' ? 'in' : 'cm',
					),
					array(
						'%d',  // user_id
						'%f',  // height
						'%f',  // weight
						'%s',  // units_height
						'%s',  // units_weight
						'%s',  // units_measurements
					)
				);
			}
		}
	}
}
