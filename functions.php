<?php
if (!defined('ABSPATH')) exit;

function athlete_dashboard_enqueue_scripts() {
    wp_enqueue_style('divi-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'athlete_dashboard_enqueue_scripts');

// Register dashboard template
function athlete_dashboard_add_template($templates) {
    $templates['dashboard/templates/dashboard.php'] = 'Dashboard';
    return $templates;
}
add_filter('theme_page_templates', 'athlete_dashboard_add_template');

// Load dashboard template
function athlete_dashboard_load_template($template) {
    if(is_page_template('dashboard/templates/dashboard.php')) {
        $template = get_stylesheet_directory() . '/dashboard/templates/dashboard.php';
    }
    return $template;
}
add_filter('template_include', 'athlete_dashboard_load_template');