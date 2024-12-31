<?php
if (!defined('ABSPATH')) exit;

// Debug logging function
function athlete_dashboard_debug_log($message) {
    if (WP_DEBUG) {
        error_log('Athlete Dashboard: ' . $message);
    }
}

// Enqueue scripts and styles
function athlete_dashboard_enqueue_scripts() {
    $asset_file_path = get_stylesheet_directory() . '/assets/build/main.asset.php';
    
    // Default dependencies and version
    $asset_file = [
        'dependencies' => ['wp-element'],
        'version' => filemtime(get_stylesheet_directory() . '/assets/build/main.js')
    ];

    // Try to load the asset file if it exists
    if (file_exists($asset_file_path)) {
        $asset_file = require $asset_file_path;
    }

    // Enqueue main script if it exists
    $script_path = get_stylesheet_directory() . '/assets/build/main.js';
    if (file_exists($script_path)) {
        wp_enqueue_script(
            'athlete-dashboard-scripts',
            get_stylesheet_directory_uri() . '/assets/build/main.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        // Localize script with WordPress data
        wp_localize_script(
            'athlete-dashboard-scripts',
            'athleteDashboardData',
            array(
                'nonce' => wp_create_nonce('wp_rest'),
                'siteUrl' => get_site_url(),
                'apiUrl' => get_rest_url(),
                'userId' => get_current_user_id(),
            )
        );
    } else {
        athlete_dashboard_debug_log('Main script file not found: ' . $script_path);
    }

    // Enqueue main stylesheet if it exists
    $style_path = get_stylesheet_directory() . '/assets/build/main.css';
    if (file_exists($style_path)) {
        wp_enqueue_style(
            'athlete-dashboard-styles',
            get_stylesheet_directory_uri() . '/assets/build/main.css',
            array(),
            $asset_file['version']
        );
    } else {
        athlete_dashboard_debug_log('Main stylesheet not found: ' . $style_path);
    }

    // Always enqueue parent theme style
    wp_enqueue_style('divi-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'athlete_dashboard_enqueue_scripts');

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