<?php
/**
 * Asset Loader class for the Athlete Dashboard.
 *
 * @package AthleteDashboard
 */

namespace AthleteDashboard;

use AthleteDashboard\Core\Config\Debug;
use AthleteDashboard\Core\DashboardBridge;

/**
 * Class Asset_Loader
 *
 * Handles loading and versioning of assets for the Athlete Dashboard.
 */
class Asset_Loader {
	/**
	 * The manifest data.
	 *
	 * @var array
	 */
	private static $manifest = null;

	/**
	 * Get the manifest data.
	 *
	 * @return array The manifest data.
	 */
	public static function get_manifest() {
		if ( self::$manifest === null ) {
			$manifest_path = get_stylesheet_directory() . '/assets/build/manifest.json';
			if ( file_exists( $manifest_path ) ) {
				Debug::log( 'Loading asset manifest from: ' . $manifest_path );
				self::$manifest = json_decode( file_get_contents( $manifest_path ), true );
				if ( self::$manifest === null ) {
					Debug::log( 'Failed to parse manifest.json' );
					self::$manifest = array();
				} else {
					Debug::log( 'Loaded manifest with ' . count( self::$manifest ) . ' entries' );
				}
			} else {
				Debug::log( 'Manifest file not found at: ' . $manifest_path );
				self::$manifest = array();
			}
		}
		return self::$manifest;
	}

	/**
	 * Get an asset path from the manifest.
	 *
	 * @param string $key The asset key.
	 * @return string The asset path.
	 */
	public static function get_asset( $key ) {
		$manifest = self::get_manifest();
		$asset    = isset( $manifest[ $key ] ) ? $manifest[ $key ] : $key;
		Debug::log( "Asset lookup for '{$key}' returned: {$asset}" );
		return $asset;
	}

	/**
	 * Get the asset version.
	 *
	 * @param string $path The asset path.
	 * @return string The asset version.
	 */
	public static function get_version( $path ) {
		$full_path = get_stylesheet_directory() . '/assets/build/' . $path;
		if ( file_exists( $full_path ) ) {
			$version = filemtime( $full_path );
			Debug::log( "Version for '{$path}' is {$version} (from file)" );
			return $version;
		}

		$theme_version = wp_get_theme()->get( 'Version' );
		Debug::log( "Version for '{$path}' is {$theme_version} (from theme)" );
		return $theme_version;
	}

	/**
	 * Get the dashboard data for script localization.
	 *
	 * @return array The dashboard data.
	 */
	private static function get_dashboard_data() {
		$data = apply_filters( 'athlete_dashboard_feature_data', array() );
		Debug::log( 'Dashboard data prepared: ' . wp_json_encode( $data ) );
		return $data;
	}

	/**
	 * Enqueue the dashboard assets.
	 *
	 * @return void
	 */
	public static function enqueue_dashboard_assets() {
		if ( ! is_page_template( 'dashboard/templates/dashboard.php' ) ) {
			return;
		}

		Debug::log( 'Enqueuing dashboard assets' );

		// Get asset paths from manifest
		$app_js  = self::get_asset( 'app.js' );
		$app_css = self::get_asset( 'app.css' );

		Debug::log( "Resolved app.js to: {$app_js}" );
		Debug::log( "Resolved app.css to: {$app_css}" );

		// Enqueue WordPress scripts we depend on
		wp_enqueue_script( 'wp-element' );
		wp_enqueue_script( 'wp-data' );
		wp_enqueue_script( 'wp-api-fetch' );
		wp_enqueue_script( 'wp-i18n' );
		wp_enqueue_script( 'wp-hooks' );

		// Enqueue main script
		wp_enqueue_script(
			'athlete-dashboard',
			get_stylesheet_directory_uri() . "/assets/build/{$app_js}",
			array( 'wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n', 'wp-hooks' ),
			self::get_version( $app_js ),
			true
		);

		// Localize the script with our data
		wp_localize_script(
			'athlete-dashboard',
			'athleteDashboardData',
			self::get_dashboard_data()
		);

		// Enqueue main styles
		wp_enqueue_style(
			'athlete-dashboard',
			get_stylesheet_directory_uri() . "/assets/build/{$app_css}",
			array(),
			self::get_version( $app_css )
		);

		// Enqueue core styles
		wp_enqueue_style(
			'athlete-dashboard-core',
			get_stylesheet_directory_uri() . '/dashboard/styles/main.css',
			array(),
			self::get_version( 'main.css' )
		);

		Debug::log( 'Dashboard assets enqueued' );
	}
}
