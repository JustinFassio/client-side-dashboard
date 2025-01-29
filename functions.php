<?php
/**
 * Athlete Dashboard Theme Functions
 *
 * Core initialization file for the Athlete Dashboard theme. Handles feature bootstrapping,
 * asset management, template configuration, and REST API setup. This file serves as the main
 * entry point for theme functionality and coordinates the integration of various components.
 *
 * Key responsibilities:
 * - Core configuration loading
 * - Cache service initialization
 * - REST API endpoint registration
 * - Asset management and enqueuing
 * - Template handling
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

// Load core contracts and dependencies first
require_once get_stylesheet_directory() . '/features/core/contracts/interface-feature-contract.php';
require_once get_stylesheet_directory() . '/features/core/contracts/class-abstract-feature.php';
require_once get_stylesheet_directory() . '/features/core/container/class-container.php';
require_once get_stylesheet_directory() . '/features/core/events/class-events.php';

// Load core configurations
require_once get_stylesheet_directory() . '/dashboard/core/config/debug.php';
require_once get_stylesheet_directory() . '/dashboard/core/config/environment.php';
require_once get_stylesheet_directory() . '/dashboard/core/dashboardbridge.php';

// Load Redis configuration
require_once get_stylesheet_directory() . '/includes/config/redis-config.php';

// Load cache services
require_once get_stylesheet_directory() . '/includes/services/class-cache-service.php';
require_once get_stylesheet_directory() . '/includes/services/class-cache-warmer.php';
require_once get_stylesheet_directory() . '/includes/services/class-cache-monitor.php';

// Load admin widgets
require_once get_stylesheet_directory() . '/includes/admin/class-cache-stats-widget.php';

// Load profile feature dependencies
require_once get_stylesheet_directory() . '/features/profile/validation/class-base-validator.php';
require_once get_stylesheet_directory() . '/features/profile/services/interface-profile-service.php';
require_once get_stylesheet_directory() . '/features/profile/validation/class-profile-validator.php';
require_once get_stylesheet_directory() . '/features/profile/repository/class-profile-repository.php';
require_once get_stylesheet_directory() . '/features/profile/services/class-profile-service.php';
require_once get_stylesheet_directory() . '/features/profile/api/class-response-factory.php';
require_once get_stylesheet_directory() . '/features/profile/api/registry/class-endpoint-registry.php';
require_once get_stylesheet_directory() . '/features/profile/api/class-profile-routes.php';
require_once get_stylesheet_directory() . '/features/profile/api/migration/class-endpoint-verifier.php';
require_once get_stylesheet_directory() . '/features/profile/api/cli/class-migration-commands.php';
require_once get_stylesheet_directory() . '/features/profile/class-profile-feature.php';

// Initialize Profile feature through the Bootstrap class
$profile_bootstrap = new AthleteDashboard\Features\Profile\Profile_Bootstrap();
$container         = new AthleteDashboard\Core\Container();
$profile_bootstrap->bootstrap( $container );

// Load feature configurations.
require_once get_stylesheet_directory() . '/features/profile/Config/Config.php';
// Remove legacy Profile_Endpoints initialization
// use AthleteDashboard\Features\Profile\api\Profile_Endpoints;

// Load workout generator feature
require_once get_stylesheet_directory() . '/features/workout-generator/src/class-workout-generator-bootstrap.php';

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
	$dashboard_data = array_merge(
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
	);

	if ( WP_DEBUG ) {
		error_log( 'Dashboard data being localized: ' . wp_json_encode( $dashboard_data ) );
	}

	wp_localize_script(
		'athlete-dashboard',
		'athleteDashboardData',
		$dashboard_data
	);

	// Initialize feature-specific data.
	$current_feature = DashboardBridge::get_current_feature();
	$feature_data    = DashboardBridge::get_feature_data( $current_feature );

	if ( WP_DEBUG ) {
		error_log( 'Feature data being localized: ' . wp_json_encode( $feature_data ) );
	}

	wp_localize_script( 'athlete-dashboard', 'athleteDashboardFeature', $feature_data );
}

/**
 * Enqueue athlete dashboard scripts and styles.
 */
function enqueue_athlete_dashboard_scripts() {
	if ( ! is_page_template( 'dashboard/templates/dashboard.php' ) ) {
		if ( WP_DEBUG ) {
			error_log( 'Not loading dashboard scripts - not on dashboard template' );
		}
		return;
	}

	if ( WP_DEBUG ) {
		error_log( 'Starting dashboard script enqueuing.' );
	}

	// Load core dependencies first
	enqueue_core_dependencies();

	// Get the script filename
	$script_filename = get_asset_filename( 'app', 'js' );
	$script_path     = get_stylesheet_directory() . '/assets/build/' . $script_filename;
	$script_url      = get_stylesheet_directory_uri() . '/assets/build/' . $script_filename;

	if ( WP_DEBUG ) {
		error_log( 'Script path: ' . $script_path );
		error_log( 'Script URL: ' . $script_url );
		error_log( 'Script exists: ' . ( file_exists( $script_path ) ? 'yes' : 'no' ) );
	}

	// Load application scripts
	wp_enqueue_script(
		'athlete-dashboard',
		$script_url,
		array( 'wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n', 'wp-hooks' ),
		get_asset_version( $script_path ),
		true
	);

	// Configure runtime data BEFORE the app loads
	$dashboard_data = array(
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'apiUrl'      => rest_url( 'athlete-dashboard/v1' ),
		'siteUrl'     => get_site_url(),
		'debug'       => defined( 'WP_DEBUG' ) && WP_DEBUG,
		'userId'      => get_current_user_id(),
		'isLoggedIn'  => is_user_logged_in(),
		'environment' => Environment::get_settings(),
		'features'    => array(
			'profile' => ProfileConfig::get_settings(),
		),
	);

	if ( WP_DEBUG ) {
		error_log( 'Localizing dashboard data: ' . wp_json_encode( $dashboard_data ) );
	}

	wp_localize_script(
		'athlete-dashboard',
		'athleteDashboardData',
		$dashboard_data
	);

	// Initialize feature-specific data
	$current_feature = DashboardBridge::get_current_feature();
	$feature_data    = DashboardBridge::get_feature_data( $current_feature );

	if ( WP_DEBUG ) {
		error_log( 'Localizing feature data: ' . wp_json_encode( $feature_data ) );
	}

	wp_localize_script(
		'athlete-dashboard',
		'athleteDashboardFeature',
		$feature_data
	);

	// Load application styles
	enqueue_app_styles();

	if ( WP_DEBUG ) {
		error_log( 'Dashboard scripts enqueued successfully' );
	}
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
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );

	// Register the dashboard template
	add_theme_support( 'custom-page-templates' );
}
add_action( 'after_setup_theme', 'athlete_dashboard_setup' );

/**
 * Register page templates.
 *
 * @param array $templates Array of page templates.
 * @return array Modified array of page templates.
 */
function athlete_dashboard_add_page_templates( $templates ) {
	$templates['dashboard/templates/dashboard.php'] = 'Dashboard';
	return $templates;
}
add_filter( 'theme_page_templates', 'athlete_dashboard_add_page_templates' );

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
	if ( WP_DEBUG ) {
		error_log( 'Template loading - Current template: ' . $template );
		error_log( 'Page template slug: ' . get_page_template_slug() );
	}

	if ( is_page() && get_page_template_slug() === 'dashboard/templates/dashboard.php' ) {
		$new_template = get_stylesheet_directory() . '/dashboard/templates/dashboard.php';

		if ( WP_DEBUG ) {
			error_log( 'Loading dashboard template from: ' . $new_template );
			error_log( 'Template exists: ' . ( file_exists( $new_template ) ? 'yes' : 'no' ) );
		}

		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}

	return $template;
}
add_filter( 'template_include', 'athlete_dashboard_load_template', 99 );

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
		// Initialize workout generator endpoints
		$workout_generator = new AthleteDashboard\Features\WorkoutGenerator\Workout_Generator_Bootstrap();
		$workout_generator->init();

		// Log registration and API details.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
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
	// Load main application styles
	wp_enqueue_style(
		'athlete-dashboard-app',
		get_template_directory_uri() . '/assets/build/app.css',
		array(),
		get_asset_version( get_template_directory() . '/assets/build/app.css' )
	);

	// Load dashboard core styles
	wp_enqueue_style(
		'athlete-dashboard-core',
		get_template_directory_uri() . '/dashboard/styles/main.css',
		array(),
		get_asset_version( get_template_directory() . '/dashboard/styles/main.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'enqueue_app_styles' );

/**
 * Validate AI Service Configuration
 */
add_action(
	'admin_init',
	function () {
		if ( defined( 'AI_SERVICE_API_KEY' ) ) {
			error_log( 'AI Service API Key is configured: ' . substr( AI_SERVICE_API_KEY, 0, 10 ) . '...' );
		} else {
			error_log( 'Warning: AI Service API Key is not configured' );
		}

		if ( defined( 'AI_SERVICE_ENDPOINT' ) ) {
			error_log( 'AI Service Endpoint is configured: ' . AI_SERVICE_ENDPOINT );
		} else {
			error_log( 'Warning: AI Service Endpoint is not configured' );
		}
	}
);

/**
 * Initialize the Profile feature.
 */
function athlete_dashboard_init_profile_feature() {
	// Create container
	$container = new \AthleteDashboard\Core\Container();

	// Initialize Profile feature
	$profile_bootstrap = new \AthleteDashboard\Features\Profile\Profile_Bootstrap();

	// Bootstrap the feature
	$profile_bootstrap->bootstrap( $container );

	// Debug logging
	if ( WP_DEBUG ) {
		error_log( 'Profile feature initialized via athlete_dashboard_init_profile_feature' );

		// Log registered routes after rest_api_init
		add_action(
			'rest_api_init',
			function () {
				$routes = rest_get_server()->get_routes();
				error_log( 'All registered routes after profile initialization:' );
				foreach ( $routes as $route => $handlers ) {
					if ( strpos( $route, 'athlete-dashboard/v1/profile' ) === 0 ) {
						error_log( "Route: $route" );
						error_log( 'Handlers: ' . print_r( $handlers, true ) );
					}
				}
			},
			999
		);
	}
}

/**
 * Clean up legacy profile functionality.
 */
function athlete_dashboard_cleanup_legacy_profile() {
	// Remove legacy actions
	remove_all_actions( 'athlete_dashboard_register_rest_routes' );
	remove_all_actions( 'athlete_dashboard_init_profile' );
	remove_all_actions( 'athlete_dashboard_profile_endpoints' );

	// Unregister legacy REST routes if they exist
	add_action(
		'rest_api_init',
		function () {
			if ( WP_DEBUG ) {
				error_log( '🧹 Starting legacy route cleanup at priority 5' );
				error_log( '📋 Current routes before cleanup:' );
				$routes = rest_get_server()->get_routes();
				foreach ( $routes as $route => $handlers ) {
					if ( strpos( $route, 'athlete-dashboard/v1/profile' ) === 0 ) {
						error_log( "  - Found route: $route" );
					}
				}
			}

			global $wp_rest_server;
			if ( $wp_rest_server ) {
				$routes  = $wp_rest_server->get_routes();
				$cleaned = 0;
				foreach ( $routes as $route => $handlers ) {
					// Only clean up routes that don't match our new pattern
					if ( strpos( $route, 'athlete-dashboard/v1/profile' ) === 0 &&
					! preg_match( '/athlete-dashboard\/v1\/profile\/\(\?P<user_id>\\\d\+\)/', $route ) ) {
						unregister_rest_route( 'athlete-dashboard/v1', ltrim( $route, 'athlete-dashboard/v1' ) );
						$cleaned++;
						if ( WP_DEBUG ) {
							error_log( "🗑️ Cleaned up legacy route: $route" );
						}
					}
				}
				if ( WP_DEBUG ) {
					error_log( "✨ Legacy cleanup complete. Removed $cleaned routes" );
					error_log( '📋 Remaining routes after cleanup:' );
					$routes = rest_get_server()->get_routes();
					foreach ( $routes as $route => $handlers ) {
						if ( strpos( $route, 'athlete-dashboard/v1/profile' ) === 0 ) {
							error_log( "  - Route still present: $route" );
						}
					}
				}
			}
		},
		5
	);  // Run at priority 5, before new registration
}

// Initialize on plugins_loaded to ensure all dependencies are available
add_action( 'plugins_loaded', 'athlete_dashboard_cleanup_legacy_profile' );
add_action( 'plugins_loaded', 'athlete_dashboard_init_profile_feature' );

// Add debug logging for script localization
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( WP_DEBUG ) {
			error_log( '🚀 Localizing athlete-dashboard script' );
		}

		// Generate nonce for REST API
		$nonce = wp_create_nonce( 'wp_rest' );
		if ( WP_DEBUG ) {
			error_log( '✨ Generated REST API nonce' );
		}

		// Get API URL
		$api_url = rest_url( 'athlete-dashboard/v1' );
		if ( WP_DEBUG ) {
			error_log( '📍 API URL: ' . $api_url );
		}

		// Localize script with debug data
		wp_localize_script(
			'athlete-dashboard',
			'athleteDashboardData',
			array(
				'nonce'      => $nonce,
				'apiUrl'     => $api_url,
				'siteUrl'    => get_site_url(),
				'debug'      => WP_DEBUG,
				'userId'     => get_current_user_id(),
				'isLoggedIn' => is_user_logged_in(),
			)
		);

		if ( WP_DEBUG ) {
			error_log( '✅ Script localization complete' );
			error_log(
				'📋 Localized data: ' . wp_json_encode(
					array(
						'nonce_set'    => ! empty( $nonce ),
						'api_url_set'  => ! empty( $api_url ),
						'user_id'      => get_current_user_id(),
						'is_logged_in' => is_user_logged_in(),
					)
				)
			);
		}
	},
	20
);

// Add comprehensive route debugging
add_action(
	'rest_api_init',
	function () {
		error_log( '=== FULL ROUTE DEBUG ===' );
		error_log( 'Debug timestamp: ' . date( 'Y-m-d H:i:s' ) );
		$routes = rest_get_server()->get_routes();
		foreach ( $routes as $route => $handlers ) {
			if ( strpos( $route, 'athlete-dashboard' ) !== false ) {
				error_log( "\nRoute: $route" );
				error_log( 'Number of handlers: ' . count( $handlers ) );
				foreach ( $handlers as $index => $handler ) {
					error_log( "Handler #$index:" );
					error_log( '- Methods: ' . print_r( array_keys( $handler ), true ) );
					error_log( '- Callback: ' . ( is_array( $handler['callback'] ) ? get_class( $handler['callback'][0] ) : 'Closure' ) );
					error_log( '- Permission Callback: ' . ( isset( $handler['permission_callback'] ) ? 'Present' : 'Missing' ) );
					if ( isset( $handler['args'] ) ) {
						error_log( '- Arguments: ' . print_r( $handler['args'], true ) );
					}
				}
			}
		}
	},
	999
);

// Add request debugging
add_action(
	'rest_pre_dispatch',
	function ( $result, $server, $request ) {
		if ( strpos( $request->get_route(), 'athlete-dashboard' ) !== false ) {
			error_log( "\n=== REST REQUEST DEBUG ===" );
			error_log( 'Debug timestamp: ' . date( 'Y-m-d H:i:s' ) );
			error_log( 'Request URI: ' . $_SERVER['REQUEST_URI'] );
			error_log( 'Request Path: ' . $request->get_route() );
			error_log( 'Request Method: ' . $request->get_method() );
			error_log( 'Request Params: ' . print_r( $request->get_params(), true ) );
			error_log( 'Request Headers: ' . print_r( $request->get_headers(), true ) );
			error_log( 'Current User ID: ' . get_current_user_id() );
			error_log( 'Is User Logged In: ' . ( is_user_logged_in() ? 'Yes' : 'No' ) );

			// Check if route exists by checking registered routes
			$routes       = rest_get_server()->get_routes();
			$route_exists = false;
			$request_path = $request->get_route();

			foreach ( $routes as $route => $handlers ) {
				// Temporarily comment out problematic regex code
				/*
				// Convert route parameters to regex pattern
				$pattern = preg_replace('/\(\?P<[\w-]+>([^)]+)\)/', '($1)', $route);
				$pattern = str_replace('/', '\\/', $pattern);
				$pattern = '/^' . $pattern . '$/';

				if (preg_match($pattern, $request_path)) {
				$route_exists = true;
				error_log('Matched Route: ' . $route);
				error_log('Handler Details: ' . print_r($handlers, true));
				break;
				}
				*/
				// Simple string comparison for now
				if ( $route === $request_path ) {
					$route_exists = true;
					error_log( 'Matched Route: ' . $route );
					error_log( 'Handler Details: ' . print_r( $handlers, true ) );
					break;
				}
			}

			if ( ! $route_exists ) {
				error_log( 'WARNING: No matching route found for ' . $request_path );
				error_log( 'Available routes:' );
				foreach ( $routes as $route => $handlers ) {
					if ( strpos( $route, 'athlete-dashboard' ) !== false ) {
						error_log( '  - ' . $route );
						error_log( '    Methods: ' . implode( ', ', array_keys( $handlers[0] ) ) );
					}
				}
			}
		}
		return $result;
	},
	10,
	3
);

add_action(
	'init',
	function () {
		error_log( '=== DEBUG: Inspecting rest_api_init hooks ===' );
		global $wp_filter;
		if ( isset( $wp_filter['rest_api_init'] ) ) {
			error_log( print_r( $wp_filter['rest_api_init'], true ) );
		}
		error_log( '=== END DEBUG ===' );
	}
);

/**
 * Register custom header and footer templates
 */
function athlete_dashboard_register_templates() {
	add_filter(
		'get_header',
		function ( $name ) {
			if ( $name === 'minimal' ) {
				return get_stylesheet_directory() . '/dashboard/templates/header-minimal.php';
			}
			return $name;
		}
	);

	add_filter(
		'get_footer',
		function ( $name ) {
			if ( $name === 'minimal' ) {
				return get_stylesheet_directory() . '/dashboard/templates/footer-minimal.php';
			}
			return $name;
		}
	);
}
add_action( 'after_setup_theme', 'athlete_dashboard_register_templates' );

/**
 * Enqueue application scripts
 */
function enqueue_app_scripts() {
	// Enqueue core dependencies first
	enqueue_core_dependencies();

	// Enqueue the main application script
	wp_enqueue_script(
		'athlete-dashboard',
		get_template_directory_uri() . '/assets/build/' . get_asset_filename( 'app', 'js' ),
		array( 'wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n', 'wp-hooks' ),
		get_asset_version( get_template_directory() . '/assets/build/' . get_asset_filename( 'app', 'js' ) ),
		true
	);

	if ( WP_DEBUG ) {
		error_log( 'Enqueued app.js with version: ' . get_asset_version( get_template_directory() . '/assets/build/' . get_asset_filename( 'app', 'js' ) ) );
	}
}
add_action( 'wp_enqueue_scripts', 'enqueue_app_scripts' );
