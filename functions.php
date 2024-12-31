<?php
if (!defined('ABSPATH')) exit;

// Debug logging function
function athlete_dashboard_debug_log($message) {
    if (WP_DEBUG) {
        error_log('Athlete Dashboard: ' . $message);
    }
}

// Enqueue scripts and styles
function enqueue_athlete_dashboard_scripts() {
    if (!is_page_template('templates/dashboard.php')) {
        return;
    }

    $asset_file = include(get_stylesheet_directory() . '/assets/build/main.asset.php');
    
    // Enqueue WordPress scripts we depend on
    wp_enqueue_script('wp-data');
    wp_enqueue_script('wp-api-fetch');
    wp_enqueue_script('wp-i18n');
    
    wp_enqueue_script(
        'athlete-dashboard',
        get_stylesheet_directory_uri() . '/assets/build/main.js',
        array_merge(['wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n'], $asset_file['dependencies']),
        $asset_file['version']
    );

    wp_localize_script('athlete-dashboard', 'athleteDashboardData', array(
        'nonce' => wp_create_nonce('wp_rest'),
        'siteUrl' => get_site_url(),
        'apiUrl' => rest_url('wp/v2'),
        'userId' => get_current_user_id()
    ));

    wp_enqueue_style(
        'athlete-dashboard',
        get_stylesheet_directory_uri() . '/assets/build/main.css',
        array(),
        $asset_file['version']
    );
}
add_action('wp_enqueue_scripts', 'enqueue_athlete_dashboard_scripts');

// Add support for editor styles
function athlete_dashboard_setup() {
    add_theme_support('editor-styles');
    add_editor_style('assets/build/main.css');
}
add_action('after_setup_theme', 'athlete_dashboard_setup');

// Register dashboard template
function athlete_dashboard_add_template($templates) {
    athlete_dashboard_debug_log('Registering dashboard template');
    $templates['dashboard/templates/dashboard.php'] = 'Dashboard';
    return $templates;
}
add_filter('theme_page_templates', 'athlete_dashboard_add_template');

// Load dashboard template
function athlete_dashboard_load_template($template) {
    if(is_page_template('dashboard/templates/dashboard.php')) {
        athlete_dashboard_debug_log('Loading dashboard template');
        $template = get_stylesheet_directory() . '/dashboard/templates/dashboard.php';
    }
    return $template;
}
add_filter('template_include', 'athlete_dashboard_load_template');