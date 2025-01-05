<?php
if (!defined('ABSPATH')) exit;

// Load core configurations
require_once get_stylesheet_directory() . '/dashboard/core/config/debug.php';
require_once get_stylesheet_directory() . '/dashboard/core/config/environment.php';
require_once get_stylesheet_directory() . '/dashboard/core/dashboardbridge.php';

// Load feature configurations
require_once get_stylesheet_directory() . '/features/profile/config.php';

// Load feature endpoints
require_once get_stylesheet_directory() . '/features/profile/api/profile-endpoints.php';

// Load REST API file
require_once get_stylesheet_directory() . '/includes/rest-api.php';

// Initialize REST API
require_once get_stylesheet_directory() . '/includes/class-rest-api.php';
require_once get_stylesheet_directory() . '/includes/rest-api/class-overview-controller.php';

// Load feature endpoints
require_once get_stylesheet_directory() . '/dashboard/api/profile-endpoint.php';

use AthleteDashboard\Core\Config\Debug;
use AthleteDashboard\Core\Config\Environment;
use AthleteDashboard\Core\DashboardBridge;
use AthleteDashboard\Features\Profile\Config as ProfileConfig;

// Add dashboard feature query var
function athlete_dashboard_add_query_vars($vars) {
    $vars[] = 'dashboard_feature';
    return $vars;
}
add_filter('query_vars', 'athlete_dashboard_add_query_vars');

// Debug logging function
function athlete_dashboard_debug_log($message) {
    Debug::log($message);
}

// Debug REST API registration
add_action('rest_api_init', function() {
    Debug::log('REST API initialized', 'core');
}, 1);

// Get asset filename from manifest
function get_asset_filename($entry_name, $extension = 'css') {
    $manifest_path = get_stylesheet_directory() . '/assets/build/manifest.json';
    if (file_exists($manifest_path)) {
        $manifest = json_decode(file_get_contents($manifest_path), true);
        return $manifest["{$entry_name}.{$extension}"] ?? "{$entry_name}.{$extension}";
    }
    return "{$entry_name}.{$extension}";
}

// Get asset version with fallback
function get_asset_version($file_path) {
    if (file_exists($file_path)) {
        return filemtime($file_path);
    }
    return wp_get_theme()->get('Version');
}

// Enqueue scripts and styles
function enqueue_athlete_dashboard_scripts() {
    if (!is_page_template('dashboard/templates/dashboard.php')) {
        return;
    }

    Debug::log('Enqueuing dashboard scripts');

    // Enqueue WordPress scripts we depend on
    wp_enqueue_script('wp-element');
    wp_enqueue_script('wp-data');
    wp_enqueue_script('wp-api-fetch');
    wp_enqueue_script('wp-i18n');
    wp_enqueue_script('wp-hooks');
    
    // Main application script
    $app_js = get_asset_filename('app', 'js');
    wp_enqueue_script(
        'athlete-dashboard',
        get_stylesheet_directory_uri() . "/assets/build/{$app_js}",
        ['wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n', 'wp-hooks'],
        get_asset_version(get_stylesheet_directory() . "/assets/build/{$app_js}"),
        true
    );

    // Pass configuration to JavaScript
    wp_localize_script('athlete-dashboard', 'athleteDashboardData', array_merge(
        [
            'nonce' => wp_create_nonce('wp_rest'),
            'siteUrl' => get_site_url(),
            'apiUrl' => rest_url(),
            'userId' => get_current_user_id()
        ],
        ['environment' => Environment::get_settings()],
        ['debug' => Debug::get_settings()],
        ['features' => [
            'profile' => ProfileConfig::get_settings()
        ]]
    ));

    // Initialize feature data
    $current_feature = DashboardBridge::get_current_feature();
    $feature_data = DashboardBridge::get_feature_data($current_feature);
    wp_localize_script('athlete-dashboard', 'athleteDashboardFeature', $feature_data);

    // Enqueue main styles
    $app_css = get_asset_filename('app', 'css');
    wp_enqueue_style(
        'athlete-dashboard',
        get_stylesheet_directory_uri() . "/assets/build/{$app_css}",
        array(),
        get_asset_version(get_stylesheet_directory() . "/assets/build/{$app_css}")
    );

    // Enqueue dashboard core styles
    wp_enqueue_style(
        'athlete-dashboard-core',
        get_stylesheet_directory_uri() . '/dashboard/styles/main.css',
        array(),
        get_asset_version(get_stylesheet_directory() . '/dashboard/styles/main.css')
    );

    Debug::log('Dashboard scripts enqueued');
}
add_action('wp_enqueue_scripts', 'enqueue_athlete_dashboard_scripts');

// Add support for editor styles
function athlete_dashboard_setup() {
    add_theme_support('editor-styles');
    $dashboard_css = get_asset_filename('dashboard', 'css');
    add_editor_style("assets/build/{$dashboard_css}");
}
add_action('after_setup_theme', 'athlete_dashboard_setup');

// Remove Divi template parts for dashboard page
function athlete_dashboard_remove_divi_template_parts() {
    if (is_page_template('dashboard/templates/dashboard.php')) {
        // Remove Divi's default layout
        remove_action('et_header_top', 'et_add_mobile_navigation');
        remove_action('et_after_main_content', 'et_divi_output_footer_items');
        
        // Remove sidebar
        add_filter('et_divi_sidebar', '__return_false');
        
        // Remove default container classes
        add_filter('body_class', function($classes) {
            return array_diff($classes, ['et_right_sidebar', 'et_left_sidebar', 'et_includes_sidebar']);
        });
        
        // Set full width layout
        add_filter('et_pb_is_pagebuilder_used', '__return_false');
    }
}
add_action('template_redirect', 'athlete_dashboard_remove_divi_template_parts');

// Include admin user profile integration
require_once get_stylesheet_directory() . '/includes/admin/user-profile.php';

// Register custom template path
function athlete_dashboard_register_page_templates($templates) {
    $templates['dashboard/templates/dashboard.php'] = 'Dashboard';
    return $templates;
}
add_filter('theme_page_templates', 'athlete_dashboard_register_page_templates');

function athlete_dashboard_load_template($template) {
    if (get_page_template_slug() === 'dashboard/templates/dashboard.php') {
        $template = get_stylesheet_directory() . '/dashboard/templates/dashboard.php';
    }
    return $template;
}
add_filter('template_include', 'athlete_dashboard_load_template');

// Add debug logging for template loading
add_action('template_redirect', function() {
    Debug::log('Current template: ' . get_page_template_slug());
    Debug::log('Template file: ' . get_page_template());
});

add_action('init', ['AthleteDashboard\\Rest_Api', 'init']);

// Initialize REST API
add_action('rest_api_init', function() {
    (new AthleteDashboard\Api\Profile_Endpoint())->register_routes();
    Debug::log('Profile endpoint registered', 'api');
});