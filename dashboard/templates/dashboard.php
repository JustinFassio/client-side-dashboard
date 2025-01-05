<?php
/**
 * Template Name: Dashboard
 * Template Post Type: page
 * 
 * Main dashboard template that provides the layout structure and coordinates
 * feature integration through React components.
 */

if (!defined('ABSPATH')) {
    exit;
}

use AthleteDashboard\Core\DashboardBridge;

// Initialize dashboard bridge
DashboardBridge::init();

// Get current feature
$current_feature = DashboardBridge::get_current_feature();
$feature_data = DashboardBridge::get_feature_data($current_feature);

// Pass feature data to JavaScript
wp_localize_script('athlete-dashboard', 'athleteDashboardFeature', $feature_data);

// Get header with minimal wrapper
get_header('minimal');
?>

<div id="athlete-dashboard" class="athlete-dashboard-container">
    <?php if (WP_DEBUG): ?>
        <div id="debug-info" style="background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
            <h3>Debug Information</h3>
            <pre>
Template File: <?php echo get_page_template(); ?>
Is Dashboard Template: <?php echo is_page_template('dashboard/templates/dashboard.php') ? 'Yes' : 'No'; ?>
WP_DEBUG: <?php echo WP_DEBUG ? 'Enabled' : 'Disabled'; ?>
Current Template: <?php echo get_page_template(); ?>
Theme Directory: <?php echo get_stylesheet_directory(); ?>
Script Path: <?php echo get_stylesheet_directory_uri() . '/assets/build/app.js'; ?>
Script Exists: <?php echo file_exists(get_stylesheet_directory() . '/assets/build/app.js') ? 'Yes' : 'No'; ?>
Current Feature: <?php echo $current_feature; ?>
Feature Data: <?php echo wp_json_encode($feature_data); ?>
athleteDashboardData: <?php echo wp_json_encode(array(
    'nonce' => wp_create_nonce('wp_rest'),
    'siteUrl' => get_site_url(),
    'apiUrl' => rest_url(),
    'userId' => get_current_user_id(),
    'debug' => WP_DEBUG
)); ?>
            </pre>
        </div>
    <?php endif; ?>
</div>

<?php get_footer('minimal'); ?>