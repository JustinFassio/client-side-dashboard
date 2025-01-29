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

// Get header with minimal wrapper
require get_stylesheet_directory() . '/dashboard/templates/header-minimal.php';

// Pass feature data to JavaScript after script is enqueued
wp_localize_script( 'athlete-dashboard', 'athleteDashboardFeature', $feature_data );

// Debug script data
if ( WP_DEBUG ) {
	error_log( 'athleteDashboardData: ' . wp_json_encode( $GLOBALS['wp_scripts']->get_data( 'athlete-dashboard', 'data' ) ) );
	error_log( 'athleteDashboardFeature: ' . wp_json_encode( $feature_data ) );
}
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
				'template_path'   => __FILE__,
			),
			'script_info'    => array(
				'path'              => get_stylesheet_directory_uri() . '/assets/build/' . get_asset_filename( 'app', 'js' ),
				'exists'            => file_exists( get_stylesheet_directory() . '/assets/build/' . get_asset_filename( 'app', 'js' ) ),
				'localized_data'    => array(
					'athleteDashboardData'    => $GLOBALS['wp_scripts']->get_data( 'athlete-dashboard', 'data' ),
					'athleteDashboardFeature' => $feature_data,
				),
				'script_queue'      => $GLOBALS['wp_scripts']->queue,
				'script_registered' => isset( $GLOBALS['wp_scripts']->registered['athlete-dashboard'] ) ?
					array(
						'src'  => $GLOBALS['wp_scripts']->registered['athlete-dashboard']->src,
						'deps' => $GLOBALS['wp_scripts']->registered['athlete-dashboard']->deps,
						'ver'  => $GLOBALS['wp_scripts']->registered['athlete-dashboard']->ver,
					) : 'Not registered',
			),
			'feature_info'   => array(
				'current' => $current_feature,
				'data'    => $feature_data,
			),
			'bootstrap_info' => array(
				'dashboard_bridge_exists'      => class_exists( '\\AthleteDashboard\\Core\\DashboardBridge' ),
				'dashboard_bridge_file_exists' => file_exists( $dashboardbridge_path ),
			),
			'wp_query_info'  => array(
				'is_page'       => is_page(),
				'is_single'     => is_single(),
				'post_type'     => get_post_type(),
				'template_slug' => get_page_template_slug(),
			),
		);
		echo wp_kses_post( print_r( $debug_info, true ) );
		?>
			</pre>
		</div>
	<?php endif; ?>

	<div id="athlete-dashboard"></div>
</div>

<?php
// Get footer with minimal wrapper
require get_stylesheet_directory() . '/dashboard/templates/footer-minimal.php';
?>