<?php
namespace AthleteDashboard\Core;

/**
 * Bridge class for handling dashboard functionality.
 *
 * @package AthleteDashboard
 * @subpackage Core
 */

// Global scope checks
global $wp_debug;
$wp_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

if ( $wp_debug ) {
	error_log( 'Loading DashboardBridge file: ' . __FILE__ );
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $wp_debug ) {
	error_log( 'Defining DashboardBridge class in namespace AthleteDashboard\\Core' );
}

/**
 * DashboardBridge class.
 *
 * Handles routing and feature management for the dashboard.
 */
class DashboardBridge {
	/** @var self|null Singleton instance. */
	private static $instance = null;

	/** @var string|null Current active feature. */
	private static $current_feature = null;

	/** @var array Debug log messages. */
	private $debug_log = array();

	/**
	 * Test if the class is loaded correctly.
	 *
	 * @return bool Always returns true.
	 */
	public static function test_loaded() {
		global $wp_debug;
		if ( $wp_debug ) {
			error_log( 'DashboardBridge::test_loaded() called' );
		}
		return true;
	}

	/**
	 * Initialize the bridge.
	 *
	 * @return self Instance of the bridge.
	 */
	public static function init() {
		global $wp_debug;
		if ( $wp_debug ) {
			error_log( 'DashboardBridge::init() called' );
		}

		if ( self::$instance === null ) {
			if ( $wp_debug ) {
				error_log( 'Creating new DashboardBridge instance' );
			}
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_feature_routing' ) );
		add_action( 'wp_footer', array( $this, 'output_debug_log' ) );
	}

	/**
	 * Register query variables for feature routing.
	 */
	public function register_query_vars() {
		global $wp;
		$wp->add_query_var( 'dashboard_feature' );
		$this->log_debug( 'Registered dashboard_feature query var.' );
	}

	/**
	 * Handle feature routing based on query variables.
	 */
	public function handle_feature_routing() {
		if ( ! is_page_template( 'dashboard/templates/dashboard.php' ) ) {
			return;
		}

		$feature = get_query_var( 'dashboard_feature' );
		$this->log_debug( 'Handling feature routing. Raw feature: ' . ( $feature ?: 'none' ) . '.' );

		if ( ! $feature ) {
			$feature = 'overview'; // Default feature.
			$this->log_debug( "No feature specified, defaulting to: {$feature}." );
		}

		$feature            = sanitize_key( $feature );
		$available_features = self::get_available_features();

		if ( ! array_key_exists( $feature, $available_features ) ) {
			$this->log_debug( "Invalid feature requested: {$feature}." );
			wp_die(
				esc_html__( 'Invalid dashboard feature requested.', 'athlete-dashboard' ),
				esc_html__( 'Error', 'athlete-dashboard' ),
				array( 'response' => 404 )
			);
		}

		self::$current_feature = $feature;
		$this->log_debug( "Set current feature to: {$feature}." );

		// Add feature-specific body class
		add_filter(
			'body_class',
			function ( $classes ) use ( $feature ) {
				$classes[] = "feature-{$feature}";
				return $classes;
			}
		);
	}

	/**
	 * Get the current active feature.
	 *
	 * @return string|null Current feature slug.
	 */
	public static function get_current_feature() {
		return self::$current_feature;
	}

	/**
	 * Get available dashboard features.
	 *
	 * @return array Array of available features and their data.
	 */
	public static function get_available_features() {
		return apply_filters(
			'athlete_dashboard_features',
			array(
				'overview'  => array(
					'title' => __( 'Overview', 'athlete-dashboard' ),
					'icon'  => 'dashboard',
				),
				'profile'   => array(
					'title' => __( 'Profile', 'athlete-dashboard' ),
					'icon'  => 'person',
				),
				'equipment' => array(
					'title' => __( 'Equipment', 'athlete-dashboard' ),
					'icon'  => 'fitness_center',
				),
			)
		);
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Debug message to log.
	 */
	private function log_debug( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->debug_log[] = $message;
		}
	}

	/**
	 * Output debug log messages in the footer.
	 */
	public function output_debug_log() {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || empty( $this->debug_log ) ) {
			return;
		}

		echo '<!-- Dashboard Debug Log:' . PHP_EOL;
		foreach ( $this->debug_log as $message ) {
			echo esc_html( $message ) . PHP_EOL;
		}
		echo '-->' . PHP_EOL;
	}

	/**
	 * Get data for a specific feature.
	 *
	 * @param string|null $feature Feature slug. If null, uses current feature.
	 * @return array|false Feature data or false if not found.
	 */
	public static function get_feature_data( $feature = null ) {
		if ( $feature === null ) {
			$feature = self::get_current_feature();
		}

		$features = self::get_available_features();
		return isset( $features[ $feature ] ) ? $features[ $feature ] : false;
	}

	/**
	 * Check API permission for requests.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool Whether the request has valid permissions.
	 */
	public static function check_api_permission( $request ) {
		return is_user_logged_in() && wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' );
	}
}
