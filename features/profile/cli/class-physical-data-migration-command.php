<?php
/**
 * Physical Data Migration Command
 *
 * @package AthleteDashboard\Features\Profile\CLI
 */

namespace AthleteDashboard\Features\Profile\CLI;

use AthleteDashboard\Features\Profile\Database\Physical_Data_Migrator;
use AthleteDashboard\Features\Profile\Database\Physical_Measurements_Table;
use WP_CLI;

/**
 * Manages physical data migration.
 */
class Physical_Data_Migration_Command {

	/**
	 * Migrates physical data from user meta to the new measurements table.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Whether to run the migration in dry-run mode
	 *
	 * [--user-id=<user-id>]
	 * : Migrate a specific user's data
	 *
	 * [--force]
	 * : Force migration even if data exists
	 *
	 * ## EXAMPLES
	 *
	 *     # Migrate all users' physical data
	 *     $ wp athlete physical-data migrate
	 *
	 *     # Dry run migration for all users
	 *     $ wp athlete physical-data migrate --dry-run
	 *
	 *     # Migrate specific user's data
	 *     $ wp athlete physical-data migrate --user-id=123
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command options.
	 */
	public function migrate( $args, $assoc_args ) {
		// Create table if it doesn't exist
		$table_manager = new Physical_Measurements_Table();
		if ( ! $table_manager->table_exists() ) {
			WP_CLI::log( 'Creating physical measurements table...' );
			$result = $table_manager->create_table();
			if ( is_string( $result ) ) {
				WP_CLI::error( "Failed to create table: $result" );
			}
		}

		$migrator = new Physical_Data_Migrator();

		// Set dry run mode if specified
		if ( isset( $assoc_args['dry-run'] ) ) {
			$migrator->set_dry_run( true );
			WP_CLI::log( 'Running in dry-run mode...' );
		}

		// Get force flag
		$force = isset( $assoc_args['force'] );

		// Migrate specific user or all users
		if ( isset( $assoc_args['user-id'] ) ) {
			$user_id = (int) $assoc_args['user-id'];
			WP_CLI::log( "Migrating data for user $user_id..." );

			$result = $migrator->migrate_user( $user_id, $force );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}

			WP_CLI::success( "Successfully migrated data for user $user_id" );
		} else {
			WP_CLI::log( 'Migrating data for all users...' );

			$results = $migrator->migrate_all_users( $force );

			WP_CLI::log(
				sprintf(
					'Migration complete. Success: %d, Failed: %d',
					$results['success'],
					$results['failed']
				)
			);

			if ( ! empty( $results['errors'] ) ) {
				WP_CLI::log( 'Errors encountered:' );
				foreach ( $results['errors'] as $error ) {
					WP_CLI::log(
						sprintf(
							'User %d: %s',
							$error['user_id'],
							$error['error']
						)
					);
				}
			}

			if ( $results['failed'] === 0 ) {
				WP_CLI::success( 'All data migrated successfully' );
			} else {
				WP_CLI::warning( 'Some migrations failed. Check the logs for details.' );
			}
		}

		// Output migration log
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			WP_CLI::log( "\nMigration Log:" );
			foreach ( $migrator->get_migration_log() as $log ) {
				WP_CLI::log(
					sprintf(
						'[%s] User %d: %s',
						$log['timestamp'],
						$log['user_id'],
						$log['message']
					)
				);
			}
		}
	}

	/**
	 * Verifies the migration by comparing old and new data.
	 *
	 * ## OPTIONS
	 *
	 * [--user-id=<user-id>]
	 * : Verify a specific user's data
	 *
	 * ## EXAMPLES
	 *
	 *     # Verify all migrated data
	 *     $ wp athlete physical-data verify
	 *
	 *     # Verify specific user's data
	 *     $ wp athlete physical-data verify --user-id=123
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command options.
	 */
	public function verify( $args, $assoc_args ) {
		global $wpdb;
		$table_manager = new Physical_Measurements_Table();

		if ( ! $table_manager->table_exists() ) {
			WP_CLI::error( 'Physical measurements table does not exist' );
		}

		$table_name = $table_manager->get_table_name();

		if ( isset( $assoc_args['user-id'] ) ) {
			$user_id = (int) $assoc_args['user-id'];
			$this->verify_user_data( $user_id, $table_name );
		} else {
			$users    = get_users( array( 'fields' => array( 'ID' ) ) );
			$progress = \WP_CLI\Utils\make_progress_bar(
				'Verifying user data',
				count( $users )
			);

			$issues = array();
			foreach ( $users as $user ) {
				$result = $this->verify_user_data( $user->ID, $table_name, false );
				if ( is_wp_error( $result ) ) {
					$issues[] = array(
						'user_id' => $user->ID,
						'error'   => $result->get_error_message(),
					);
				}
				$progress->tick();
			}

			$progress->finish();

			if ( empty( $issues ) ) {
				WP_CLI::success( 'All data verified successfully' );
			} else {
				WP_CLI::warning( 'Found issues:' );
				foreach ( $issues as $issue ) {
					WP_CLI::log(
						sprintf(
							'User %d: %s',
							$issue['user_id'],
							$issue['error']
						)
					);
				}
			}
		}
	}

	/**
	 * Verify a single user's data
	 *
	 * @param int    $user_id    User ID.
	 * @param string $table_name Table name.
	 * @param bool   $output     Whether to output results.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function verify_user_data( $user_id, $table_name, $output = true ) {
		global $wpdb;

		// Get old data
		$old_height = get_user_meta( $user_id, 'user_height', true );
		$old_weight = get_user_meta( $user_id, 'user_weight', true );
		$old_units  = get_user_meta( $user_id, 'measurement_units', true ) ?: 'metric';

		// Get new data
		$new_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY measurement_date DESC LIMIT 1",
				$user_id
			)
		);

		$issues = array();

		// Convert old data to metric if needed
		if ( $old_units === 'imperial' && ( $old_height || $old_weight ) ) {
			if ( $old_height ) {
				$old_height = round( $old_height * 2.54, 2 ); // inches to cm
			}
			if ( $old_weight ) {
				$old_weight = round( $old_weight * 0.45359237, 2 ); // lbs to kg
			}
		}

		// Compare data with a small tolerance for floating point differences
		$tolerance = 0.01;

		if ( ! empty( $old_height ) && ( ! $new_data || abs( $new_data->height - $old_height ) > $tolerance ) ) {
			$issues[] = "Height mismatch: old={$old_height}, new=" . ( $new_data ? $new_data->height : 'null' );
		}

		if ( ! empty( $old_weight ) && ( ! $new_data || abs( $new_data->weight - $old_weight ) > $tolerance ) ) {
			$issues[] = "Weight mismatch: old={$old_weight}, new=" . ( $new_data ? $new_data->weight : 'null' );
		}

		// Only check units if there are actual measurements
		// Note: We always store in metric units now
		if ( ( $old_height || $old_weight ) && $new_data && $new_data->unit_system !== 'metric' ) {
			$issues[] = 'Units mismatch: expected=metric, actual=' . $new_data->unit_system;
		}

		if ( ! empty( $issues ) ) {
			$error = new \WP_Error(
				'verification_failed',
				sprintf( 'Data verification failed for user %d: %s', $user_id, implode( '; ', $issues ) )
			);

			if ( $output ) {
				WP_CLI::error( $error->get_error_message() );
			}

			return $error;
		}

		if ( $output ) {
			WP_CLI::success( sprintf( 'Data verified successfully for user %d', $user_id ) );
		}

		return true;
	}

	/**
	 * Create the physical measurements table.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create the physical measurements table
	 *     $ wp athlete physical-data create-table
	 */
	public function create_table() {
		$table_manager = new Physical_Measurements_Table();

		if ( $table_manager->table_exists() ) {
			WP_CLI::warning( 'Table already exists' );
			return;
		}

		WP_CLI::log( 'Creating physical measurements table...' );
		$result = $table_manager->create_table();

		if ( is_string( $result ) ) {
			WP_CLI::error( "Failed to create table: $result" );
		}

		WP_CLI::success( 'Table created successfully' );
	}

	/**
	 * Drop the physical measurements table.
	 *
	 * ## EXAMPLES
	 *
	 *     # Drop the physical measurements table
	 *     $ wp athlete physical-data drop-table
	 */
	public function drop_table() {
		$table_manager = new Physical_Measurements_Table();

		if ( ! $table_manager->table_exists() ) {
			WP_CLI::warning( 'Table does not exist' );
			return;
		}

		WP_CLI::log( 'Dropping physical measurements table...' );
		$result = $table_manager->drop_table();

		if ( is_string( $result ) ) {
			WP_CLI::error( "Failed to drop table: $result" );
		}

		WP_CLI::success( 'Table dropped successfully' );
	}
}

// Register the command
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'athlete physical-data', Physical_Data_Migration_Command::class );
}
