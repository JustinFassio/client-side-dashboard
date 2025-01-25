<?php
namespace AthleteDashboard\Config;

/**
 * API Endpoint Configuration
 *
 * Defines standardized endpoint paths and configurations for the Athlete Dashboard API.
 */
class Endpoints {
	const NAMESPACE = 'athlete-dashboard/v1';

	/**
	 * Authentication endpoints
	 */
	const AUTH = array(
		'login'         => array(
			'path'             => '/auth/login',
			'methods'          => array( 'POST' ),
			'deprecated_paths' => array( '/auth_login' ),
		),
		'register'      => array(
			'path'             => '/auth/register',
			'methods'          => array( 'POST' ),
			'deprecated_paths' => array(),
		),
		'logout'        => array(
			'path'             => '/auth/logout',
			'methods'          => array( 'POST' ),
			'deprecated_paths' => array(),
		),
		'refresh-token' => array(
			'path'             => '/auth/refresh-token',
			'methods'          => array( 'POST' ),
			'deprecated_paths' => array( '/auth/refresh_token' ),
		),
	);

	/**
	 * Profile endpoints
	 */
	const PROFILE = array(
		'get'         => array(
			'path'             => '/profile/{user_id}',
			'methods'          => array( 'GET' ),
			'deprecated_paths' => array( '/profile/(?P<id>\d+)' ),
		),
		'update'      => array(
			'path'             => '/profile/{user_id}',
			'methods'          => array( 'PUT' ),
			'deprecated_paths' => array(),
		),
		'bulk-update' => array(
			'path'             => '/profile/bulk-updates',
			'methods'          => array( 'POST' ),
			'deprecated_paths' => array( '/profile/bulk', '/profile_bulk' ),
		),
		'settings'    => array(
			'path'             => '/profile/{user_id}/settings',
			'methods'          => array( 'GET', 'PUT' ),
			'deprecated_paths' => array(),
		),
		'preferences' => array(
			'path'             => '/profile/{user_id}/preferences',
			'methods'          => array( 'GET', 'PUT' ),
			'deprecated_paths' => array(),
		),
	);

	/**
	 * Overview endpoints
	 */
	const OVERVIEW = array(
		'get'      => array(
			'path'             => '/overview/{user_id}',
			'methods'          => array( 'GET' ),
			'deprecated_paths' => array(),
		),
		'goals'    => array(
			'path'             => '/overview/goals/{goal_id}',
			'methods'          => array( 'GET', 'PUT', 'DELETE' ),
			'deprecated_paths' => array(),
		),
		'activity' => array(
			'path'             => '/overview/activity/{activity_id}',
			'methods'          => array( 'GET', 'DELETE' ),
			'deprecated_paths' => array(),
		),
		'stats'    => array(
			'path'             => '/overview/{user_id}/stats',
			'methods'          => array( 'GET' ),
			'deprecated_paths' => array( '/overview/(?P<user_id>\d+)/statistics' ),
		),
	);

	/**
	 * Get the full path for an endpoint
	 */
	public static function get_path( string $group, string $endpoint ): string {
		$endpoints = constant( 'self::' . strtoupper( $group ) );
		return isset( $endpoints[ $endpoint ] ) ? $endpoints[ $endpoint ]['path'] : '';
	}

	/**
	 * Get allowed methods for an endpoint
	 */
	public static function get_methods( string $group, string $endpoint ): array {
		$endpoints = constant( 'self::' . strtoupper( $group ) );
		return isset( $endpoints[ $endpoint ] ) ? $endpoints[ $endpoint ]['methods'] : array();
	}

	/**
	 * Get deprecated paths for an endpoint
	 */
	public static function get_deprecated_paths( string $group, string $endpoint ): array {
		$endpoints = constant( 'self::' . strtoupper( $group ) );
		return isset( $endpoints[ $endpoint ] ) ? $endpoints[ $endpoint ]['deprecated_paths'] : array();
	}

	/**
	 * Check if a path is deprecated
	 */
	public static function is_deprecated_path( string $path ): bool {
		foreach ( array( self::AUTH, self::PROFILE, self::OVERVIEW ) as $group ) {
			foreach ( $group as $endpoint ) {
				if ( in_array( $path, $endpoint['deprecated_paths'] ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get the new path for a deprecated path
	 */
	public static function get_new_path( string $deprecated_path ): ?string {
		foreach ( array( self::AUTH, self::PROFILE, self::OVERVIEW ) as $group ) {
			foreach ( $group as $endpoint ) {
				if ( in_array( $deprecated_path, $endpoint['deprecated_paths'] ) ) {
					return $endpoint['path'];
				}
			}
		}
		return null;
	}
}
