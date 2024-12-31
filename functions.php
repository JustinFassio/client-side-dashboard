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
    if (!is_page_template('dashboard/templates/dashboard.php')) {
        return;
    }

    athlete_dashboard_debug_log('Enqueuing dashboard scripts');

    // Enqueue WordPress scripts we depend on
    wp_enqueue_script('wp-element');
    wp_enqueue_script('wp-data');
    wp_enqueue_script('wp-api-fetch');
    wp_enqueue_script('wp-i18n');
    wp_enqueue_script('wp-hooks');
    
    // Main dashboard script
    wp_enqueue_script(
        'athlete-dashboard',
        get_stylesheet_directory_uri() . '/build/main.js',
        ['wp-element', 'wp-data', 'wp-api-fetch', 'wp-i18n', 'wp-hooks'],
        filemtime(get_stylesheet_directory() . '/build/main.js'),
        true
    );

    // Pass configuration to JavaScript
    wp_localize_script('athlete-dashboard', 'athleteDashboardData', array(
        'nonce' => wp_create_nonce('wp_rest'),
        'siteUrl' => get_site_url(),
        'apiUrl' => rest_url(),
        'userId' => get_current_user_id()
    ));

    // Styles
    wp_enqueue_style(
        'athlete-dashboard',
        get_stylesheet_directory_uri() . '/build/main.css',
        array(),
        filemtime(get_stylesheet_directory() . '/build/main.css')
    );

    athlete_dashboard_debug_log('Dashboard scripts enqueued');
}
add_action('wp_enqueue_scripts', 'enqueue_athlete_dashboard_scripts');

// Add support for editor styles
function athlete_dashboard_setup() {
    add_theme_support('editor-styles');
    add_editor_style('build/main.css');
}
add_action('after_setup_theme', 'athlete_dashboard_setup');

// Register REST API endpoints
function athlete_dashboard_register_rest_routes() {
    athlete_dashboard_debug_log('Registering REST routes');

    register_rest_route('athlete-dashboard/v1', '/profile', array(
        array(
            'methods' => 'GET',
            'callback' => 'athlete_dashboard_get_profile',
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ),
        array(
            'methods' => 'POST',
            'callback' => 'athlete_dashboard_update_profile',
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        )
    ));
}
add_action('rest_api_init', 'athlete_dashboard_register_rest_routes');

// Profile REST API handlers
function athlete_dashboard_get_profile($request) {
    $user_id = get_current_user_id();
    
    $profile_data = array(
        'firstName' => get_user_meta($user_id, 'first_name', true),
        'lastName' => get_user_meta($user_id, 'last_name', true),
        'email' => get_user_by('id', $user_id)->user_email,
        'age' => (int)get_user_meta($user_id, 'athlete_age', true) ?: 0,
        'gender' => get_user_meta($user_id, 'athlete_gender', true) ?: 'prefer_not_to_say',
        'height' => (float)get_user_meta($user_id, 'athlete_height', true) ?: 0,
        'weight' => (float)get_user_meta($user_id, 'athlete_weight', true) ?: 0,
        'medicalInfo' => array(
            'hasInjuries' => (bool)get_user_meta($user_id, 'athlete_has_injuries', true),
            'injuries' => get_user_meta($user_id, 'athlete_injuries', true),
            'hasMedicalClearance' => (bool)get_user_meta($user_id, 'athlete_has_medical_clearance', true),
            'medicalClearanceDate' => get_user_meta($user_id, 'athlete_medical_clearance_date', true),
            'medicalNotes' => get_user_meta($user_id, 'athlete_medical_notes', true)
        ),
        'bio' => get_user_meta($user_id, 'description', true),
        'fitnessGoals' => get_user_meta($user_id, 'athlete_fitness_goals', true),
        'preferredWorkoutTypes' => get_user_meta($user_id, 'athlete_preferred_workout_types', true) ?: array()
    );

    return rest_ensure_response($profile_data);
}

function athlete_dashboard_update_profile($request) {
    $user_id = get_current_user_id();
    $params = $request->get_json_params();

    // Update basic user data
    if (isset($params['firstName'])) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($params['firstName']));
    }
    if (isset($params['lastName'])) {
        update_user_meta($user_id, 'last_name', sanitize_text_field($params['lastName']));
    }
    if (isset($params['email'])) {
        $user = wp_update_user(array(
            'ID' => $user_id,
            'user_email' => sanitize_email($params['email'])
        ));
        if (is_wp_error($user)) {
            return new WP_Error('email_update_failed', $user->get_error_message());
        }
    }

    // Update physical attributes
    if (isset($params['age'])) {
        update_user_meta($user_id, 'athlete_age', (int)$params['age']);
    }
    if (isset($params['gender'])) {
        update_user_meta($user_id, 'athlete_gender', sanitize_text_field($params['gender']));
    }
    if (isset($params['height'])) {
        update_user_meta($user_id, 'athlete_height', (float)$params['height']);
    }
    if (isset($params['weight'])) {
        update_user_meta($user_id, 'athlete_weight', (float)$params['weight']);
    }

    // Update medical info
    if (isset($params['medicalInfo'])) {
        $medical_info = $params['medicalInfo'];
        update_user_meta($user_id, 'athlete_has_injuries', (bool)$medical_info['hasInjuries']);
        update_user_meta($user_id, 'athlete_injuries', sanitize_textarea_field($medical_info['injuries'] ?? ''));
        update_user_meta($user_id, 'athlete_has_medical_clearance', (bool)$medical_info['hasMedicalClearance']);
        update_user_meta($user_id, 'athlete_medical_clearance_date', sanitize_text_field($medical_info['medicalClearanceDate'] ?? ''));
        update_user_meta($user_id, 'athlete_medical_notes', sanitize_textarea_field($medical_info['medicalNotes'] ?? ''));
    }

    // Update additional info
    if (isset($params['bio'])) {
        update_user_meta($user_id, 'description', sanitize_textarea_field($params['bio']));
    }
    if (isset($params['fitnessGoals'])) {
        update_user_meta($user_id, 'athlete_fitness_goals', sanitize_textarea_field($params['fitnessGoals']));
    }
    if (isset($params['preferredWorkoutTypes'])) {
        update_user_meta($user_id, 'athlete_preferred_workout_types', array_map('sanitize_text_field', $params['preferredWorkoutTypes']));
    }

    // Return updated profile
    return athlete_dashboard_get_profile($request);
}

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
        $new_template = get_stylesheet_directory() . '/dashboard/templates/dashboard.php';
        if (file_exists($new_template)) {
            athlete_dashboard_debug_log('Dashboard template found at: ' . $new_template);
            return $new_template;
        }
        athlete_dashboard_debug_log('Dashboard template not found at: ' . $new_template);
    }
    return $template;
}
add_filter('template_include', 'athlete_dashboard_load_template');

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