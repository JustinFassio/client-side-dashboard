<?php
/**
 * Feature router template
 * Handles loading feature-specific content based on the current feature
 */

if (!defined('ABSPATH')) {
    exit;
}

use AthleteDashboard\Core\DashboardBridge;

// Initialize the bridge if not already done
DashboardBridge::init();

$feature = $args['feature'] ?? DashboardBridge::get_current_feature();
$feature_data = DashboardBridge::get_feature_data($feature);

// Output feature data for React
wp_localize_script('athlete-dashboard', 'athleteDashboardFeature', $feature_data);

// Load feature-specific template if it exists
$feature_template = get_stylesheet_directory() . "/features/{$feature}/templates/{$feature}-view.php";
if (file_exists($feature_template)) {
    include $feature_template;
} else {
    // Fallback to default content
    echo '<div class="feature-content" data-feature="' . esc_attr($feature) . '"></div>';
} 