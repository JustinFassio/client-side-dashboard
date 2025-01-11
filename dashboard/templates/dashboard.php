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
wp_localize_script( 'athlete-dashboard-script', 'athleteDashboardFeature', $feature_data );

// Get header with minimal wrapper
get_header( 'minimal' );
?>

<div id="athlete-dashboard" class="athlete-dashboard-container">
	<?php if ( WP_DEBUG ) : ?>
		<div id="debug-info" style="background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
			<h3>Debug Information</h3>
			<pre>
Template File: <?php echo get_page_template(); ?>
Is Dashboard Template: <?php echo is_page_template( 'dashboard/templates/dashboard.php' ) ? 'Yes' : 'No'; ?>
WP_DEBUG: <?php echo WP_DEBUG ? 'Enabled' : 'Disabled'; ?>
Current Template: <?php echo get_page_template(); ?>
Theme Directory: <?php echo get_stylesheet_directory(); ?>
Script Path: <?php echo get_stylesheet_directory_uri() . '/assets/build/app.26e094f6e87125cfab1b.js'; ?>
Script Exists: <?php echo file_exists( get_stylesheet_directory() . '/assets/build/app.26e094f6e87125cfab1b.js' ) ? 'Yes' : 'No'; ?>
Current Feature: <?php echo $current_feature; ?>
Feature Data: <?php echo wp_json_encode( $feature_data ); ?>
DashboardBridge Class Exists: <?php echo class_exists( '\\AthleteDashboard\\Core\\DashboardBridge' ) ? 'Yes' : 'No'; ?>
DashboardBridge File Exists: <?php echo file_exists( $dashboardbridge_path ) ? 'Yes' : 'No'; ?>
athleteDashboardData: 
		<?php
		echo wp_json_encode(
			array(
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'siteUrl' => get_site_url(),
				'apiUrl'  => rest_url(),
				'userId'  => get_current_user_id(),
				'debug'   => WP_DEBUG,
			)
		);
		?>
			</pre>
		</div>
	<?php endif; ?>
</div>

<?php get_footer( 'minimal' ); ?>