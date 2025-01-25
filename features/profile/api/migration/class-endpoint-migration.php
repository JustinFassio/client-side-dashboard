<?php
/**
 * Endpoint Migration Helper.
 *
 * @package AthleteDashboard\Features\Profile\API\Migration
 */

namespace AthleteDashboard\Features\Profile\API\Migration;

use AthleteDashboard\Features\Profile\API\Profile_Routes;
use WP_Error;

/**
 * Class Endpoint_Migration
 *
 * Manages the migration process from legacy to new endpoints.
 */
class Endpoint_Migration {
	/**
	 * Profile routes instance.
	 *
	 * @var Profile_Routes
	 */
	private Profile_Routes $routes;

	/**
	 * Migration status log option name.
	 *
	 * @var string
	 */
	private const MIGRATION_LOG_OPTION = 'athlete_dashboard_endpoint_migration_log';

	/**
	 * Constructor.
	 *
	 * @param Profile_Routes $routes Profile routes instance.
	 */
	public function __construct( Profile_Routes $routes ) {
		$this->routes = $routes;
	}

	/**
	 * Start migration for a specific endpoint.
	 *
	 * @param string $endpoint_key Endpoint key to migrate.
	 * @return true|WP_Error True if successful, WP_Error if failed.
	 */
	public function start_migration( string $endpoint_key ): true|WP_Error {
		try {
			// Log migration start
			$this->log_migration_event( $endpoint_key, 'start' );

			// Enable new endpoint
			$this->routes->enable_new_endpoint( $endpoint_key );

			// Log migration completion
			$this->log_migration_event( $endpoint_key, 'complete' );

			return true;
		} catch ( \Exception $e ) {
			$error = new WP_Error(
				'migration_failed',
				sprintf(
					__( 'Failed to migrate endpoint %1$s: %2$s', 'athlete-dashboard' ),
					$endpoint_key,
					$e->getMessage()
				)
			);

			// Log migration failure
			$this->log_migration_event( $endpoint_key, 'failed', $e->getMessage() );

			return $error;
		}
	}

	/**
	 * Rollback migration for a specific endpoint.
	 *
	 * @param string $endpoint_key Endpoint key to rollback.
	 * @return true|WP_Error True if successful, WP_Error if failed.
	 */
	public function rollback_migration( string $endpoint_key ): true|WP_Error {
		try {
			// Log rollback start
			$this->log_migration_event( $endpoint_key, 'rollback_start' );

			// Disable new endpoint
			$this->routes->disable_new_endpoint( $endpoint_key );

			// Log rollback completion
			$this->log_migration_event( $endpoint_key, 'rollback_complete' );

			return true;
		} catch ( \Exception $e ) {
			$error = new WP_Error(
				'rollback_failed',
				sprintf(
					__( 'Failed to rollback endpoint %1$s: %2$s', 'athlete-dashboard' ),
					$endpoint_key,
					$e->getMessage()
				)
			);

			// Log rollback failure
			$this->log_migration_event( $endpoint_key, 'rollback_failed', $e->getMessage() );

			return $error;
		}
	}

	/**
	 * Get migration status for all endpoints.
	 *
	 * @return array Migration status for all endpoints.
	 */
	public function get_migration_status(): array {
		$log    = $this->get_migration_log();
		$status = array();

		foreach ( $this->get_endpoint_keys() as $key ) {
			$status[ $key ] = array(
				'is_migrated' => $this->is_endpoint_migrated( $key ),
				'last_event'  => $this->get_last_event( $key, $log ),
				'history'     => $this->get_endpoint_history( $key, $log ),
			);
		}

		return $status;
	}

	/**
	 * Check if an endpoint is fully migrated.
	 *
	 * @param string $endpoint_key Endpoint key to check.
	 * @return bool True if migrated.
	 */
	public function is_endpoint_migrated( string $endpoint_key ): bool {
		$log    = $this->get_migration_log();
		$events = $this->get_endpoint_history( $endpoint_key, $log );

		if ( empty( $events ) ) {
			return false;
		}

		$last_event = end( $events );
		return $last_event['status'] === 'complete';
	}

	/**
	 * Get all available endpoint keys.
	 *
	 * @return array Array of endpoint keys.
	 */
	public function get_endpoint_keys(): array {
		return array(
			'use_new_profile_get',
			'use_new_profile_update',
			'use_new_profile_delete',
			'use_new_user_get',
			'use_new_user_update',
		);
	}

	/**
	 * Log a migration event.
	 *
	 * @param string $endpoint_key Endpoint key.
	 * @param string $status      Event status.
	 * @param string $message     Optional message.
	 * @return void
	 */
	private function log_migration_event( string $endpoint_key, string $status, string $message = '' ): void {
		$log = $this->get_migration_log();

		if ( ! isset( $log[ $endpoint_key ] ) ) {
			$log[ $endpoint_key ] = array();
		}

		$log[ $endpoint_key ][] = array(
			'timestamp' => current_time( 'mysql' ),
			'status'    => $status,
			'message'   => $message,
		);

		update_option( self::MIGRATION_LOG_OPTION, $log );
	}

	/**
	 * Get the migration log.
	 *
	 * @return array Migration log.
	 */
	private function get_migration_log(): array {
		return get_option( self::MIGRATION_LOG_OPTION, array() );
	}

	/**
	 * Get the last event for an endpoint.
	 *
	 * @param string $endpoint_key Endpoint key.
	 * @param array  $log         Migration log.
	 * @return array|null Last event or null if none.
	 */
	private function get_last_event( string $endpoint_key, array $log ): ?array {
		if ( ! isset( $log[ $endpoint_key ] ) || empty( $log[ $endpoint_key ] ) ) {
			return null;
		}

		return end( $log[ $endpoint_key ] );
	}

	/**
	 * Get history for an endpoint.
	 *
	 * @param string $endpoint_key Endpoint key.
	 * @param array  $log         Migration log.
	 * @return array Event history.
	 */
	private function get_endpoint_history( string $endpoint_key, array $log ): array {
		return $log[ $endpoint_key ] ?? array();
	}
}
