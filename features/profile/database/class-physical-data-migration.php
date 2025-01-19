	/**
	 * Run the migration.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function up(): bool|WP_Error {
		error_log( 'Physical_Data_Migration: Running migration' );
		global $wpdb;

		$table_name = $wpdb->prefix . 'athlete_physical_measurements';
		$charset_collate = $wpdb->get_charset_collate();

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			error_log( 'Physical_Data_Migration: Table already exists' );
			return true;
		}

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			height decimal(5,2) DEFAULT NULL,
			weight decimal(5,2) DEFAULT NULL,
			chest decimal(5,2) DEFAULT NULL,
			waist decimal(5,2) DEFAULT NULL,
			hips decimal(5,2) DEFAULT NULL,
			units longtext DEFAULT NULL,
			date datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY date (date)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$result = dbDelta( $sql );

		if ( empty( $result ) ) {
			error_log( 'Physical_Data_Migration: Failed to create table' );
			return new WP_Error(
				'migration_failed',
				__( 'Failed to create physical measurements table.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		error_log( 'Physical_Data_Migration: Table created successfully' );
		error_log( 'Migration result: ' . wp_json_encode( $result ) );

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
		return $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
	}

	/**
	 * Reverse the migration.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function down(): bool|WP_Error {
		error_log( 'Physical_Data_Migration: Reversing migration' );
		global $wpdb;

		$table_name = $wpdb->prefix . 'athlete_physical_measurements';
		
		// Check if table exists before trying to drop it
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			error_log( 'Physical_Data_Migration: Table does not exist' );
			return true;
		}

		$result = $wpdb->query( "DROP TABLE IF EXISTS $table_name" );

		if ( false === $result ) {
			error_log( 'Physical_Data_Migration: Failed to drop table' );
			return new WP_Error(
				'migration_failed',
				__( 'Failed to drop physical measurements table.', 'athlete-dashboard' ),
				array( 'status' => 500 )
			);
		}

		error_log( 'Physical_Data_Migration: Table dropped successfully' );
		return true;
	} 