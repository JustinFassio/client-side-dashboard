<?php
/**
 * Profile migrator class.
 *
 * @package AthleteDashboard\Features\Profile
 */

namespace AthleteDashboard\Features\Profile;

use WP_Error;

/**
 * Class for handling profile data migration.
 */
class Profile_Migrator {
	/**
	 * Profile service instance.
	 *
	 * @var Profile_Service
	 */
	private $service;

	/**
	 * Constructor.
	 *
	 * @param Profile_Service $service Service instance.
	 */
	public function __construct( Profile_Service $service ) {
		$this->service = $service;
	}

	/**
	 * Migrate user data.
	 *
	 * @param int $user_id User ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function migrate_user_data( $user_id ) {
		// Get legacy data
		$legacy_data = array(
			'phone'                   => get_user_meta( $user_id, 'phone', true ),
			'age'                     => get_user_meta( $user_id, 'age', true ),
			'gender'                  => get_user_meta( $user_id, 'gender', true ),
			'dominant_side'           => get_user_meta( $user_id, 'dominant_side', true ),
			'medical_clearance'       => get_user_meta( $user_id, 'medical_clearance', true ),
			'medical_notes'           => get_user_meta( $user_id, 'medical_notes', true ),
			'emergency_contact_name'  => get_user_meta( $user_id, 'emergency_contact_name', true ),
			'emergency_contact_phone' => get_user_meta( $user_id, 'emergency_contact_phone', true ),
		);

		// Update data
		$result = $this->service->update_profile_data( $user_id, $legacy_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Clean up old data
		delete_user_meta( $user_id, 'phone' );
		delete_user_meta( $user_id, 'age' );
		delete_user_meta( $user_id, 'gender' );
		delete_user_meta( $user_id, 'dominant_side' );
		delete_user_meta( $user_id, 'medical_clearance' );
		delete_user_meta( $user_id, 'medical_notes' );
		delete_user_meta( $user_id, 'emergency_contact_name' );
		delete_user_meta( $user_id, 'emergency_contact_phone' );

		return true;
	}

	/**
	 * Migrate all users.
	 *
	 * @return array Migration results.
	 */
	public function migrate_all_users(): array {
		$results = array(
			'success' => 0,
			'failed'  => 0,
			'errors'  => array(),
		);

		$users = get_users( array( 'fields' => array( 'ID' ) ) );
		foreach ( $users as $user ) {
			$result = $this->migrate_user_data( $user->ID );
			if ( is_wp_error( $result ) ) {
				++$results['failed'];
				$results['errors'][] = array(
					'user_id' => $user->ID,
					'message' => $result->get_error_message(),
				);
			} else {
				++$results['success'];
			}
		}

		return $results;
	}

	/**
	 * Register WP-CLI commands.
	 */
	public static function register_cli_commands(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		\WP_CLI::add_command(
			'athlete-dashboard physical migrate',
			function ( $args, $assoc_args ) {
				$service  = new Profile_Service();
				$migrator = new self( $service );

				if ( isset( $assoc_args['user-id'] ) ) {
					$user_id = (int) $assoc_args['user-id'];
					\WP_CLI::log( sprintf( 'Migrating user %d...', $user_id ) );

					$result = $migrator->migrate_user_data( $user_id );
					if ( is_wp_error( $result ) ) {
						\WP_CLI::error( $result->get_error_message() );
					}

					\WP_CLI::success( sprintf( 'Successfully migrated user %d', $user_id ) );
					return;
				}

				\WP_CLI::log( 'Starting migration for all users...' );
				$progress = \WP_CLI\Utils\make_progress_bar( 'Migrating users', count( get_users() ) );

				$results = $migrator->migrate_all_users();
				$progress->finish();

				\WP_CLI::log(
					sprintf(
						'Migration completed. Success: %d, Failed: %d',
						$results['success'],
						$results['failed']
					)
				);

				if ( $results['failed'] > 0 ) {
					\WP_CLI::log( 'Failed migrations:' );
					foreach ( $results['errors'] as $error ) {
						\WP_CLI::log(
							sprintf(
								'User %d: %s',
								$error['user_id'],
								$error['message']
							)
						);
					}
				}

				\WP_CLI::success( 'Physical data migration completed.' );
			}
		);
	}
}

// Register CLI commands
Profile_Migrator::register_cli_commands();
