<?php
if (!defined('ABSPATH')) exit;

// Define WP_ENV if not already defined
if (!defined('WP_ENV')) {
    define('WP_ENV', 'development');
}

// Debug logging in development
if (WP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/**
 * Athlete Dashboard Child Theme functions and definitions
 */

// Register dashboard template
function register_dashboard_template($templates) {
    $templates['dashboard/templates/dashboard.php'] = 'Dashboard';
    return $templates;
}
add_filter('theme_page_templates', 'register_dashboard_template');

function load_dashboard_template($template) {
    if (is_page_template('dashboard/templates/dashboard.php')) {
        $template_path = get_stylesheet_directory() . '/dashboard/templates/dashboard.php';
        if (file_exists($template_path)) {
            return $template_path;
        }
        error_log('Dashboard template file not found at: ' . $template_path);
    }
    return $template;
}
add_filter('template_include', 'load_dashboard_template');

// Asset enqueuing
function athlete_dashboard_child_enqueue_assets() {
    // Only load dashboard assets on dashboard template
    if (!is_page_template('dashboard/templates/dashboard.php')) {
        return;
    }

    if (WP_ENV === 'development') {
        // Development mode - load from Vite dev server
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.development.js', [], null, true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.development.js', ['react'], null, true);
        
        // Check if Vite server is running
        $vite_server = @file_get_contents('http://localhost:5173/@vite/client');
        if ($vite_server !== false) {
            // Vite server is running
            error_log('Vite development server is running');
            
            // Vite HMR
            wp_enqueue_script('vite-client', 'http://localhost:5173/@vite/client', [], null, true);
            
            // Main application
            wp_register_script('dashboard-js', 'http://localhost:5173/assets/src/main.tsx', ['react', 'react-dom', 'vite-client'], null, true);
            wp_enqueue_script('dashboard-js');
        } else {
            // Fallback to production assets if Vite server is not running
            error_log('Vite server not running, falling back to production assets');
            load_production_assets();
        }
    } else {
        load_production_assets();
    }

    // Add nonce for REST API and environment info
    wp_localize_script('dashboard-js', 'wpApiSettings', [
        'nonce' => wp_create_nonce('wp_rest'),
        'rootapiurl' => esc_url_raw(rest_url()),
        'env' => WP_ENV,
        'isDevMode' => WP_ENV === 'development',
        'templateUrl' => get_stylesheet_directory_uri(),
        'viteUrl' => WP_ENV === 'development' ? 'http://localhost:5173' : ''
    ]);
}

function load_production_assets() {
    // Production mode - load built assets
    $asset_manifest = get_stylesheet_directory() . '/assets/dist/manifest.json';
    
    if (file_exists($asset_manifest)) {
        $manifest = json_decode(file_get_contents($asset_manifest), true);
        
        if ($manifest) {
            $entry_point = array_key_exists('assets/src/main.tsx', $manifest) ? 
                          $manifest['assets/src/main.tsx'] : 
                          $manifest['main.tsx'];
            
            if (isset($entry_point['file'])) {
                wp_enqueue_script(
                    'athlete-dashboard-main',
                    get_stylesheet_directory_uri() . '/assets/dist/' . $entry_point['file'],
                    ['wp-element'],
                    null,
                    true
                );
            }
            
            if (isset($entry_point['css'])) {
                foreach ($entry_point['css'] as $css_file) {
                    wp_enqueue_style(
                        'athlete-dashboard-' . basename($css_file, '.css'),
                        get_stylesheet_directory_uri() . '/assets/dist/' . $css_file
                    );
                }
            }
        } else {
            error_log('Invalid manifest.json format');
        }
    } else {
        error_log('Production manifest file not found at: ' . $asset_manifest);
    }
}
add_action('wp_enqueue_scripts', 'athlete_dashboard_child_enqueue_assets');

// Add CORS headers for development
if (WP_ENV === 'development') {
    add_action('init', function() {
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, X-WP-Nonce");
    });
}

// Profile REST API endpoints
function athlete_dashboard_register_profile_routes() {
    register_rest_route('athlete-dashboard/v1', '/profile', [
        [
            'methods' => 'GET',
            'callback' => 'athlete_dashboard_get_profile',
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ],
        [
            'methods' => 'POST',
            'callback' => 'athlete_dashboard_update_profile',
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ]
    ]);
}
add_action('rest_api_init', 'athlete_dashboard_register_profile_routes');

function athlete_dashboard_get_profile($request) {
    $user_id = get_current_user_id();
    
    return new WP_REST_Response([
        'id' => $user_id,
        'name' => get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true),
        'email' => get_userdata($user_id)->user_email,
        'age' => (int) get_user_meta($user_id, '_profile_age', true),
        'gender' => get_user_meta($user_id, '_profile_gender', true),
        'height' => (float) get_user_meta($user_id, '_profile_height', true),
        'weight' => (float) get_user_meta($user_id, '_profile_weight', true),
        'injuries' => get_user_meta($user_id, '_profile_injuries', true) ?: [],
        'medicalClearance' => (bool) get_user_meta($user_id, '_profile_medical_clearance', true)
    ], 200);
}

function athlete_dashboard_update_profile($request) {
    $user_id = get_current_user_id();
    $params = $request->get_json_params();
    
    $fields = [
        'age' => '_profile_age',
        'gender' => '_profile_gender',
        'height' => '_profile_height',
        'weight' => '_profile_weight',
        'injuries' => '_profile_injuries',
        'medicalClearance' => '_profile_medical_clearance'
    ];
    
    foreach ($fields as $param_key => $meta_key) {
        if (isset($params[$param_key])) {
            update_user_meta($user_id, $meta_key, $params[$param_key]);
        }
    }
    
    return athlete_dashboard_get_profile($request);
} 