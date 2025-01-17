<?php
/**
 * Athlete Dashboard Theme Functions
 *
 * Core initialization file for the Athlete Dashboard child theme. Handles feature bootstrapping,
 * asset management, template configuration, and REST API setup. This file serves as the main
 * entry point for theme functionality and coordinates the integration of various components.
 *
 * Key responsibilities:
 * - Core configuration loading
 * - Cache service initialization
 * - REST API endpoint registration
 * - Asset management and enqueuing
 * - Template handling and Divi integration
 * - Debug logging configuration
 *
 * @package AthleteDashboard
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader
require_once get_stylesheet_directory() . '/vendor/autoload.php';

// Load core contracts
require_once get_stylesheet_directory() . '/features/core/contracts/interface-feature-contract.php';
require_once get_stylesheet_directory() . '/features/core/contracts/class-abstract-feature.php';

// Load core configurations.
require_once get_stylesheet_directory() . '/dashboard/core/config/debug.php';
require_once get_stylesheet_directory() . '/dashboard/core/config/environment.php';
require_once get_stylesheet_directory() . '/dashboard/core/dashboardbridge.php';

// Load Redis configuration
require_once get_stylesheet_directory() . '/includes/config/redis-config.php';

// Load cache services.
require_once get_stylesheet_directory() . '/includes/services/class-cache-service.php';
require_once get_stylesheet_directory() . '/includes/services/class-cache-warmer.php';
require_once get_stylesheet_directory() . '/includes/services/class-cache-monitor.php';

// Load admin widgets.
require_once get_stylesheet_directory() . '/includes/admin/class-cache-stats-widget.php';

/**
 * Initialize the Cache Statistics Widget in the WordPress admin dashboard.
 *
 * Creates and initializes an instance of the Cache_Stats_Widget class when in the admin area.
 * This widget provides real-time monitoring of cache performance metrics and statistics
 * to help administrators track and optimize the caching system's effectiveness.
 *
 * @since 1.0.0
 * @see \AthleteDashboard\Admin\Cache_Stats_Widget
 */
function init_cache_stats_widget() {
	if ( is_admin() ) {
		$widget = new AthleteDashboard\Admin\Cache_Stats_Widget();
		$widget->init();
	}
}
add_action( 'init', 'init_cache_stats_widget' );

/**
 * Initialize cache service.
 */
function init_cache_service() {
	AthleteDashboard\Services\Cache_Service::init();
}
add_action( 'init', 'init_cache_service', 5 ); // Run before cache stats widget initialization

// Load feature configurations.
require_once get_stylesheet_directory() . '/features/profile/Config/Config.php';
require_once get_stylesheet_directory() . '/features/profile/api/class-profile-endpoints.php';

// Load REST API dependencies.
require_once get_stylesheet_directory() . '/includes/rest-api/class-rate-limiter.php';
require_once get_stylesheet_directory() . '/includes/rest-api/class-request-validator.php';
require_once get_stylesheet_directory() . '/includes/rest-api/class-rest-controller-base.php';

// Load REST API controllers.
require_once get_stylesheet_directory() . '/includes/rest-api/class-overview-controller.php';
require_once get_stylesheet_directory() . '/includes/rest-api/class-profile-controller.php';

// Load feature endpoints.
require_once get_stylesheet_directory() . '/features/equipment/api/class-equipment-endpoints.php';

// Load REST API file.
require_once get_stylesheet_directory() . '/includes/rest-api.php';

// Initialize REST API.
require_once get_stylesheet_directory() . '/includes/class-rest-api.php';

use AthleteDashboard\Core\Config\Debug;
use AthleteDashboard\Core\Config\Environment;
use AthleteDashboard\Core\DashboardBridge;
use AthleteDashboard\Features\Profile\Config\Config as ProfileConfig;
use AthleteDashboard\Features\Profile\api\Profile_Endpoints;

/**
 * Add custom query variables for dashboard feature routing.
 *
 * Registers the 'dashboard_feature' query variable to WordPress, enabling
 * dynamic routing and feature-specific content loading in the dashboard.
 *
 * @since 1.0.0
 * @param array $vars Existing query variables.
 * @return array Modified array of query variables.
 */
function athlete_dashboard_add_query_vars( $vars ) {
	$vars[] = 'dashboard_feature';
	return $vars;
}
add_filter( 'query_vars', 'athlete_dashboard_add_query_vars' );

/**
 * Log debug messages using the Debug class.
 *
 * Wrapper function for the Debug::log method, providing a consistent
 * interface for debug logging throughout the theme.
 *
 * @since 1.0.0
 * @param mixed $message The message to log. Can be any data type that can be converted to string.
 * @see \AthleteDashboard\Core\Config\Debug::log()
 */
function athlete_dashboard_debug_log( $message ) {
	Debug::log( $message );
}

// Add debug mode filter.
add_filter(
	'athlete_dashboard_debug_mode',
	function ( $debug ) {
		// Enable debug only for users with manage_options capability.
		if ( current_user_can( 'manage_options' ) ) {
			return $debug; // Keep as is for administrators.
		}
		return false; // Disable for non-administrators.
	}
);

// Debug REST API registration.
add_action(
	'rest_api_init',
	function () {
		Debug::log( 'REST API initialized.', 'core' );
	},
	1
);

/**
 * Get asset filename from the build manifest.
 *
 * Resolves the actual filename for an asset from the build manifest JSON file.
 * Uses static caching to store the manifest data after initial load, improving
 * performance for subsequent calls. If the manifest doesn't exist or the entry
 * isn't found, falls back to an unhashed filename.
 *
 * @since 1.0.0
 * @param string $entry_name The entry point name in the manifest (e.g., 'app', 'dashboard').
 * @param string $extension The file extension to look for (default: 'css').
 * @return string The resolved filename from the manifest or the fallback unhashed filename.
 */
function get_asset_filename( $entry_name, $extension = 'css' ) {
	static $manifest = null;

	if ( null === $manifest ) {
		$manifest_path = get_stylesheet_directory() . '/assets/build/manifest.json';
		if ( file_exists( $manifest_path ) ) {
			$manifest_content = file_get_contents( $manifest_path );
			$manifest         = json_decode( $manifest_content, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$manifest = array();
			}
		} else {
			$manifest = array();
		}
	}

	$asset_key = "{$entry_name}.{$extension}";
	return isset( $manifest[ $asset_key ] ) ? $manifest[ $asset_key ] : $asset_key;
}

/**
 * Get asset version for cache busting.
 *
 * Generates a version string for asset cache busting based on the file's
 * last modification time. If the file doesn't exist, falls back to the
 * current theme version. This ensures proper cache invalidation when
 * assets are updated.
 *
 * @since 1.0.0
 * @param string $file_path The absolute path to the asset file.
 * @return string|int The file modification time or theme version for cache busting.
 */
function get_asset_version( $file_path ) {
	if ( file_exists( $file_path ) ) {
		return filemtime( $file_path );
	}
	return wp_get_theme()->get( 'Version' );
}

/**
 * Enqueue WordPress core dependencies required by the dashboard.
 *
 * Loads essential WordPress scripts needed for the React-based dashboard,
 * including element handling, data management, and API functionality.
 *
 * @since 1.0.0
 */
function enqueue_core_dependencies() {
	wp_enqueue_script( 'wp-element' );
	wp_enqueue_script( 'wp-data' );
	wp_enqueue_script( 'wp-api-fetch' );
	wp_enqueue_script( 'wp-i18n' );
	wp_enqueue_script( 'wp-hooks' );
}

/**
 * Configure dashboard runtime data.
 *
 * Sets up JavaScript runtime configuration including API endpoints,
 * user data, environment settings, and feature-specific information.
 *
 * @since 1.0.0
 */
function localize_dashboard_data() {
	// Configure core dashboard data.
	wp_localize_script(
		'athlete-dashboard',
		'athleteDashboardData',
		array_merge(
			array(
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'siteUrl' => get_site_url(),
				'apiUrl'  => rest_url(),
				'userId'  => get_current_user_id(),
			),
			array( 'environment' => Environment::get_settings() ),
			array( 'debug' => Debug::get_settings() ),
			array(
				'features' => array(
					'profile' => ProfileConfig::get_settings(),
				),
			)
		)
	);

	// Initialize feature-specific data.
	$current_feature = DashboardBridge::get_current_feature();
	$feature_data    = DashboardBridge::get_feature_data( $current_feature );
	wp_localize_script( 'athlete-dashboard', 'athleteDashboardFeature', $feature_data );
}

/**
 * Enqueue athlete dashboard scripts and styles.
 *
 * Coordinates the loading of all dashboard-related assets by calling
 * specialized helper functions for each aspect of asset management.
 * Only loads assets when viewing the dashboard template to optimize
 * performance.
 *
 * @since 1.0.0
 * @see enqueue_core_dependencies() For WordPress core script loading
 * @see enqueue_app_scripts() For main application script loading
 * @see enqueue_app_styles() For style loading
 * @see localize_dashboard_data() For runtime data configuration
 */
function enqueue_athlete_dashboard_scripts() {
	if ( ! is_page_template( 'dashboard/templates/dashboard.php' ) ) {
		return;
	}

	Debug::log( 'Starting dashboard script enqueuing.', 'core' );

	// Load core dependencies.
	enqueue_core_dependencies();

	// Load application scripts.
	wp_enqueue_script(
		'athlete-dashboard',
		get_stylesheet_directory_uri() . '/assets/build/' . get_asset_filename( 'app', 'js' ),
		array( 'wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n', 'wp-hooks' ),
		get_asset_version( get_stylesheet_directory() . '/assets/build/' . get_asset_filename( 'app', 'js' ) ),
		true
	);

	// Load application styles.
	enqueue_app_styles();

	// Configure runtime data.
	localize_dashboard_data();

	Debug::log( 'Dashboard scripts enqueued.', 'core' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_athlete_dashboard_scripts' );

/**
 * Set up theme support for editor styles.
 *
 * Enables editor styles support and registers the dashboard CSS file
 * to ensure consistent styling in both frontend and editor views.
 *
 * @since 1.0.0
 */
function athlete_dashboard_setup() {
	add_theme_support( 'editor-styles' );
	$dashboard_css = get_asset_filename( 'dashboard', 'css' );
	add_editor_style( "assets/build/{$dashboard_css}" );
}
add_action( 'after_setup_theme', 'athlete_dashboard_setup' );

/**
 * Remove Divi template parts for the dashboard page.
 *
 * Disables various Divi theme elements when viewing the dashboard template
 * to provide a clean, custom interface for the dashboard experience.
 *
 * @since 1.0.0
 */
function athlete_dashboard_remove_divi_template_parts() {
	if ( is_page_template( 'dashboard/templates/dashboard.php' ) ) {
		// Remove Divi's default layout elements.
		remove_action( 'et_header_top', 'et_add_mobile_navigation' );
		remove_action( 'et_after_main_content', 'et_divi_output_footer_items' );

		// Disable sidebar functionality.
		add_filter( 'et_divi_sidebar', '__return_false' );

		// Remove default container classes.
		add_filter(
			'body_class',
			function ( $classes ) {
				return array_diff( $classes, array( 'et_right_sidebar', 'et_left_sidebar', 'et_includes_sidebar' ) );
			}
		);

		// Disable page builder for dashboard.
		add_filter( 'et_pb_is_pagebuilder_used', '__return_false' );
	}
}
add_action( 'template_redirect', 'athlete_dashboard_remove_divi_template_parts' );

// Include admin user profile integration.
require_once get_stylesheet_directory() . '/includes/admin/user-profile.php';

/**
 * Register custom page templates.
 *
 * Adds the dashboard template to the list of available page templates
 * in the WordPress admin area.
 *
 * @since 1.0.0
 * @param array $templates Array of page templates.
 * @return array Modified array of page templates.
 */
function athlete_dashboard_register_page_templates( $templates ) {
	$templates['dashboard/templates/dashboard.php'] = 'Dashboard';
	return $templates;
}
add_filter( 'theme_page_templates', 'athlete_dashboard_register_page_templates' );

/**
 * Load the dashboard template.
 *
 * Intercepts template loading to serve our custom dashboard template
 * when the appropriate page template is selected.
 *
 * @since 1.0.0
 * @param string $template Path to the template file.
 * @return string Modified path to the template file.
 */
function athlete_dashboard_load_template( $template ) {
	if ( get_page_template_slug() === 'dashboard/templates/dashboard.php' ) {
		$template = get_stylesheet_directory() . '/dashboard/templates/dashboard.php';
	}
	return $template;
}
add_filter( 'template_include', 'athlete_dashboard_load_template' );

// Add debug logging for template loading.
add_action(
	'template_redirect',
	function () {
		Debug::log( 'Current template: ' . get_page_template_slug() . '.' );
		Debug::log( 'Template file: ' . get_page_template() . '.' );
	}
);

// Initialize REST API endpoints.
add_action(
	'rest_api_init',
	function () {
		// Initialize profile endpoints.
		AthleteDashboard\Features\Profile\api\Profile_Endpoints::init();

		// Log registration and API details.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			Debug::log( 'Profile endpoints initialized.', 'api' );
			Debug::log( 'REST API URL: ' . rest_url( 'athlete-dashboard/v1/profile' ) . '.', 'api' );
			Debug::log( 'Current user: ' . get_current_user_id() . '.', 'api' );

			// Test the endpoint registration.
			$server         = rest_get_server();
			$routes         = $server->get_routes();
			$profile_routes = array_filter(
				array_keys( $routes ),
				function ( $route ) {
					return strpos( $route, 'athlete-dashboard/v1/profile' ) === 0;
				}
			);
			Debug::log( 'Registered profile routes: ' . implode( ', ', $profile_routes ) . '.', 'api' );
		}
	},
	5  // Higher priority to ensure it runs after core REST API initialization.
);

/**
 * Debug REST API requests and route registration.
 */
add_filter(
	'rest_url',
	function ( $url ) {
		Debug::log( 'REST URL requested: ' . $url, 'rest.debug' );
		return $url;
	}
);

add_action(
	'rest_api_init',
	function () {
		Debug::log( 'REST API Routes registered', 'rest.debug' );

		// Log all registered routes
		$routes = rest_get_server()->get_routes();
		foreach ( $routes as $route => $handlers ) {
			if ( strpos( $route, 'athlete-dashboard' ) !== false ) {
				Debug::log( 'Registered route: ' . $route, 'rest.debug' );
			}
		}
	}
);

/**
 * Enqueue dashboard styles.
 *
 * Loads both the main application styles and core dashboard styles
 * with proper cache busting.
 *
 * @since 1.0.0
 */
function enqueue_app_styles() {
	// Load main application styles.
	$app_css = get_asset_filename( 'app', 'css' );
	wp_enqueue_style(
		'athlete-dashboard',
		get_stylesheet_directory_uri() . "/assets/build/{$app_css}",
		array(),
		get_asset_version( get_stylesheet_directory() . "/assets/build/{$app_css}" )
	);

	// Load dashboard core styles.
	wp_enqueue_style(
		'athlete-dashboard-core',
		get_stylesheet_directory_uri() . '/dashboard/styles/main.css',
		array(),
		get_asset_version( get_stylesheet_directory() . '/dashboard/styles/main.css' )
	);
}

// Load base classes and interfaces first
require_once get_stylesheet_directory() . '/features/profile/validation/class-base-validator.php';
require_once get_stylesheet_directory() . '/features/profile/services/interface-profile-service.php';

// Load implementations
require_once get_stylesheet_directory() . '/features/profile/validation/class-profile-validator.php';
require_once get_stylesheet_directory() . '/features/profile/repository/class-profile-repository.php';
require_once get_stylesheet_directory() . '/features/profile/services/class-profile-service.php';

// Load API related classes
require_once get_stylesheet_directory() . '/features/profile/api/class-response-factory.php';
require_once get_stylesheet_directory() . '/features/profile/api/registry/class-endpoint-registry.php';
require_once get_stylesheet_directory() . '/features/profile/api/class-profile-routes.php';
require_once get_stylesheet_directory() . '/features/profile/api/migration/class-endpoint-verifier.php';
require_once get_stylesheet_directory() . '/features/profile/api/cli/class-migration-commands.php';

// Load feature class last
require_once get_stylesheet_directory() . '/features/profile/class-profile-feature.php';

// Initialize Profile feature
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Initialize dependencies
	$registry         = new AthleteDashboard\Features\Profile\API\Registry\Endpoint_Registry();
	$repository       = new AthleteDashboard\Features\Profile\Repository\Profile_Repository();
	$validator        = new AthleteDashboard\Features\Profile\Validation\Profile_Validator();
	$service          = new AthleteDashboard\Features\Profile\Services\Profile_Service( $repository, $validator );
	$response_factory = new AthleteDashboard\Features\Profile\API\Response_Factory();
	$routes           = new AthleteDashboard\Features\Profile\API\Profile_Routes( $service, $response_factory, $registry );

	// Initialize feature
	$profile_feature = new AthleteDashboard\Features\Profile\Profile_Feature( $routes );
	$profile_feature->init();
} else {
	// Initialize dependencies for normal requests
	$registry         = new AthleteDashboard\Features\Profile\API\Registry\Endpoint_Registry();
	$repository       = new AthleteDashboard\Features\Profile\Repository\Profile_Repository();
	$validator        = new AthleteDashboard\Features\Profile\Validation\Profile_Validator();
	$service          = new AthleteDashboard\Features\Profile\Services\Profile_Service( $repository, $validator );
	$response_factory = new AthleteDashboard\Features\Profile\API\Response_Factory();
	$routes           = new AthleteDashboard\Features\Profile\API\Profile_Routes( $service, $response_factory, $registry );

	// Initialize profile feature
	$profile_feature = new AthleteDashboard\Features\Profile\Profile_Feature( $routes );
	$profile_feature->init();
}
