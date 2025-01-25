<?php
/**
 * Template Name: Dashboard
 * Template Post Type: page
 *
 * Main dashboard template that provides the layout structure and coordinates
 * feature integration through React components.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load asset helper functions
require_once get_stylesheet_directory() . '/functions.php';

// Debug information
if ( WP_DEBUG ) {
	error_log( 'Loading dashboard template.' );
	error_log( 'Current template file: ' . __FILE__ );
	error_log( 'Theme directory: ' . get_stylesheet_directory() );
}

// Ensure the DashboardBridge class is loaded
$dashboardbridge_path = get_stylesheet_directory() . '/dashboard/core/dashboardbridge.php';
if ( ! class_exists( '\\AthleteDashboard\\Core\\DashboardBridge' ) ) {
	if ( file_exists( $dashboardbridge_path ) ) {
		if ( WP_DEBUG ) {
			error_log( 'Loading DashboardBridge from: ' . $dashboardbridge_path );
		}
		require_once $dashboardbridge_path;
		if ( ! class_exists( '\\AthleteDashboard\\Core\\DashboardBridge' ) ) {
			if ( WP_DEBUG ) {
				error_log( 'DashboardBridge class still not found after including file.' );
			}
			wp_die( 'Critical Error: DashboardBridge class could not be loaded.' );
		}
	} else {
		if ( WP_DEBUG ) {
			error_log( 'DashboardBridge file not found at: ' . $dashboardbridge_path );
		}
		wp_die( 'Critical Error: DashboardBridge file not found.' );
	}
}

use AthleteDashboard\Core\DashboardBridge;
use AthleteDashboard\Core\Config\Environment;

// Initialize dashboard bridge if not already initialized
if ( ! DashboardBridge::get_current_feature() ) {
	if ( WP_DEBUG ) {
		error_log( 'Initializing DashboardBridge.' );
	}
	DashboardBridge::init();
}

// Get current feature
$current_feature = DashboardBridge::get_current_feature();
$feature_data    = DashboardBridge::get_feature_data( $current_feature );

if ( WP_DEBUG ) {
	error_log( 'Current feature: ' . ( $current_feature ?: 'none' ) );
	error_log( 'Feature data: ' . wp_json_encode( $feature_data ) );
}

// Pass feature data to JavaScript
wp_localize_script( 'athlete-dashboard', 'athleteDashboardFeature', $feature_data );

// Get header with minimal wrapper
get_header( 'minimal' );
?>

<div id="athlete-dashboard" class="athlete-dashboard-container">
	<?php if ( WP_DEBUG ) : ?>
		<div id="debug-info" style="background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
			<h3>Debug Information</h3>
			<pre>
		<?php
		// Only log debug information in debug mode
		$debug_info = array(
			'template_info'  => array(
				'file'            => get_page_template(),
				'is_dashboard'    => is_page_template( 'dashboard/templates/dashboard.php' ),
				'theme_directory' => get_stylesheet_directory(),
			),
			'script_info'    => array(
				'path'   => get_stylesheet_directory_uri() . '/assets/build/' . get_asset_filename( 'app', 'js' ),
				'exists' => file_exists( get_stylesheet_directory() . '/assets/build/' . get_asset_filename( 'app', 'js' ) ),
			),
			'feature_info'   => array(
				'current' => $current_feature,
				'data'    => $feature_data,
			),
			'bootstrap_info' => array(
				'dashboard_bridge_exists'      => class_exists( '\\AthleteDashboard\\Core\\DashboardBridge' ),
				'dashboard_bridge_file_exists' => file_exists( $dashboardbridge_path ),
			),
			'profile_info'   => array(
				'routes'           => array_filter(
					rest_get_server()->get_routes(),
					function ( $route ) {
						return strpos( $route, 'athlete-dashboard/v1/profile' ) === 0;
					},
					ARRAY_FILTER_USE_KEY
				),
				'bootstrap_status' => array(
					'container_exists'         => class_exists( '\\AthleteDashboard\\Core\\Container' ),
					'profile_bootstrap_exists' => class_exists( '\\AthleteDashboard\\Features\\Profile\\Profile_Bootstrap' ),
				),
			),
		);

		echo "Debug Information:\n";
		echo wp_json_encode( $debug_info, JSON_PRETTY_PRINT );
		?>
			</pre>
		</div>

		<script>
			// Add runtime debug info only in debug mode
			window.addEventListener('load', function() {
				console.log('=== Dashboard Debug Info ===');
				console.log('athleteDashboardData:', window.athleteDashboardData);
				console.log('athleteDashboardFeature:', window.athleteDashboardFeature);
				console.log('React Mount Target:', document.getElementById('athlete-dashboard'));
				console.log('=========================');
			});
		</script>
	<?php endif; ?>

	<div id="athlete-dashboard"></div>
</div>

<?php get_footer( 'minimal' ); ?>