<?php
/**
 * Physical Data Migrator
 *
 * @package AthleteDashboard\Features\Profile\Database
 */

namespace AthleteDashboard\Features\Profile\Database;

use WP_Error;

/**
 * Class Physical_Data_Migrator
 *
 * Handles migration of physical data from user meta to the measurements table.
 */
class Physical_Data_Migrator {

	/**
	 * Whether to run in dry-run mode.
	 *
	 * @var bool
	 */
	private bool $is_dry_run = false;

	/**
	 * Migration log.
	 *
	 * @var array
	 */
	private array $log = array();

	/**
	 * Table manager instance.
	 *
	 * @var Physical_Measurements_Table
	 */
	private Physical_Measurements_Table $table_manager;

	/**
	 * Valid measurement ranges.
	 *
	 * @var array
	 */
	private array $ranges = array(
		'metric'   => array(
			'height' => array(
				'min' => 100,
				'max' => 250,
			),  // cm
			'weight' => array(
				'min' => 30,
				'max' => 300,
			),   // kg
		),
		'imperial' => array(
			'height' => array(
				'min' => 39,
				'max' => 98,
			),    // inches
			'weight' => array(
				'min' => 66,
				'max' => 660,
			),   // lbs
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->table_manager = new Physical_Measurements_Table();
	}

	/**
	 * Set dry run mode.
	 *
	 * @param bool $enabled Whether to enable dry run mode.
	 * @return void
	 */
	public function set_dry_run( bool $enabled ): void {
		$this->is_dry_run = $enabled;
	}

	/**
	 * Get migration log.
	 *
	 * @return array
	 */
	public function get_migration_log(): array {
		return $this->log;
	}

	/**
	 * Log a migration message.
	 *
	 * @param int    $user_id User ID.
	 * @param string $message Message to log.
	 * @return void
	 */
	private function log( int $user_id, string $message ): void {
		$this->log[] = array(
			'user_id'   => $user_id,
			'message'   => $message,
			'timestamp' => current_time( 'mysql' ),
		);
	}

	/**
	 * Migrate physical data for a specific user.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $force   Whether to force migration even if data exists.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function migrate_user( int $user_id, bool $force = false ) {
		// Check if user exists
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'invalid_user',
				sprintf( 'User %d does not exist', $user_id )
			);
		}

		// Check if data already exists
		if ( ! $force && $this->table_manager->get_latest_measurement( $user_id ) ) {
			$this->log( $user_id, 'Data already exists, skipping (use --force to override)' );
			return true;
		}

		// Get legacy data
		$height = get_user_meta( $user_id, 'user_height', true );
		$weight = get_user_meta( $user_id, 'user_weight', true );
		$units  = get_user_meta( $user_id, 'measurement_units', true ) ?: 'metric';

		// Skip if no valid data
		if ( empty( $height ) && empty( $weight ) ) {
			$this->log( $user_id, 'No physical data found' );
			return true;
		}

		// Sanitize and validate data
		$height      = $this->sanitize_measurement( $height );
		$weight      = $this->sanitize_measurement( $weight );
		$unit_system = $this->sanitize_unit_system( $units );

		// Validate measurements
		$validation_result = $this->validate_measurements( $height, $weight, $unit_system );
		if ( is_wp_error( $validation_result ) ) {
			$this->log( $user_id, 'Validation failed: ' . $validation_result->get_error_message() );
			return $validation_result;
		}

		// Convert to metric if needed
		if ( $unit_system === 'imperial' ) {
			$converted = $this->convert_to_metric( $height, $weight );
			$height    = $converted['height'];
			$weight    = $converted['weight'];
			$this->log(
				$user_id,
				sprintf(
					'Converted from imperial: height=%s cm, weight=%s kg',
					$height,
					$weight
				)
			);
		}

		// Prepare data for insertion
		$data = array(
			'user_id'          => $user_id,
			'height'           => $height,
			'weight'           => $weight,
			'unit_system'      => 'metric', // Always store in metric
			'measurement_date' => current_time( 'mysql' ),
			'created_at'       => current_time( 'mysql' ),
		);

		// Log the migration
		$this->log(
			$user_id,
			sprintf(
				'Migrating data: height=%s, weight=%s, original_units=%s',
				$height,
				$weight,
				$unit_system
			)
		);

		// Skip actual insertion in dry-run mode
		if ( $this->is_dry_run ) {
			$this->log( $user_id, '[DRY RUN] Would insert data' );
			return true;
		}

		// Create backup before insertion
		$this->create_backup( $user_id );

		// Insert data
		$result = $this->table_manager->insert_measurement( $data );
		if ( $result === false ) {
			global $wpdb;
			return new WP_Error(
				'insert_failed',
				sprintf( 'Failed to insert data: %s', $wpdb->last_error )
			);
		}

		$this->log( $user_id, 'Data migrated successfully' );
		return true;
	}

	/**
	 * Migrate physical data for all users.
	 *
	 * @param bool $force Whether to force migration even if data exists.
	 * @return array Migration results.
	 */
	public function migrate_all_users( bool $force = false ): array {
		$users   = get_users( array( 'fields' => array( 'ID' ) ) );
		$results = array(
			'success' => 0,
			'failed'  => 0,
			'skipped' => 0,
			'errors'  => array(),
		);

		$total   = count( $users );
		$current = 0;

		foreach ( $users as $user ) {
			++$current;
			$this->log( $user->ID, sprintf( 'Processing user %d of %d', $current, $total ) );

			$result = $this->migrate_user( $user->ID, $force );
			if ( is_wp_error( $result ) ) {
				++$results['failed'];
				$results['errors'][] = array(
					'user_id' => $user->ID,
					'error'   => $result->get_error_message(),
				);
			} elseif ( $this->table_manager->get_latest_measurement( $user->ID ) ) {
					++$results['success'];
			} else {
				++$results['skipped'];
			}
		}

		return $results;
	}

	/**
	 * Create a backup of user's physical data.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function create_backup( int $user_id ): void {
		$backup = array(
			'height'      => get_user_meta( $user_id, 'user_height', true ),
			'weight'      => get_user_meta( $user_id, 'user_weight', true ),
			'units'       => get_user_meta( $user_id, 'measurement_units', true ),
			'backup_date' => current_time( 'mysql' ),
		);

		update_user_meta( $user_id, 'physical_data_backup', $backup );
		$this->log( $user_id, 'Created backup of original data' );
	}

	/**
	 * Validate measurements against allowed ranges.
	 *
	 * @param float|null $height      Height value.
	 * @param float|null $weight      Weight value.
	 * @param string     $unit_system Unit system.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_measurements( $height, $weight, string $unit_system ) {
		if ( $height !== null ) {
			$range = $this->ranges[ $unit_system ]['height'];
			if ( $height < $range['min'] || $height > $range['max'] ) {
				return new WP_Error(
					'invalid_height',
					sprintf(
						'Height must be between %d and %d %s',
						$range['min'],
						$range['max'],
						$unit_system === 'metric' ? 'cm' : 'inches'
					)
				);
			}
		}

		if ( $weight !== null ) {
			$range = $this->ranges[ $unit_system ]['weight'];
			if ( $weight < $range['min'] || $weight > $range['max'] ) {
				return new WP_Error(
					'invalid_weight',
					sprintf(
						'Weight must be between %d and %d %s',
						$range['min'],
						$range['max'],
						$unit_system === 'metric' ? 'kg' : 'lbs'
					)
				);
			}
		}

		if ( $height === null && $weight === null ) {
			return new WP_Error(
				'no_measurements',
				'At least one measurement (height or weight) must be provided'
			);
		}

		return true;
	}

	/**
	 * Convert measurements from imperial to metric.
	 *
	 * @param float|null $height Height in inches.
	 * @param float|null $weight Weight in pounds.
	 * @return array Converted measurements.
	 */
	private function convert_to_metric( $height, $weight ): array {
		return array(
			'height' => $height !== null ? round( $height * 2.54, 2 ) : null,      // inches to cm
			'weight' => $weight !== null ? round( $weight * 0.45359237, 2 ) : null, // lbs to kg
		);
	}

	/**
	 * Sanitize a measurement value.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return float|null
	 */
	private function sanitize_measurement( $value ) {
		if ( empty( $value ) ) {
			return null;
		}

		$value = (float) $value;
		return $value > 0 ? $value : null;
	}

	/**
	 * Sanitize unit system value.
	 *
	 * @param string $value Value to sanitize.
	 * @return string
	 */
	private function sanitize_unit_system( string $value ): string {
		$valid_systems = array( 'metric', 'imperial' );
		return in_array( $value, $valid_systems, true ) ? $value : 'metric';
	}
}
