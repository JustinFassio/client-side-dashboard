<?php
/**
 * Test script for profile endpoints
 * 
 * Usage: Add ?test_profile_api=1 to any WordPress page to run this test
 */

add_action('init', function() {
    if (!isset($_GET['test_profile_api'])) {
        return;
    }

    error_log('Running profile API test...');

    // Test 1: Check if our namespace is registered
    $namespaces = rest_get_server()->get_namespaces();
    error_log('Registered namespaces: ' . print_r($namespaces, true));

    // Test 2: Get all routes
    $routes = rest_get_server()->get_routes();
    error_log('All registered routes: ' . print_r($routes, true));

    // Test 3: Try to get our specific route
    $route = rest_get_route_data('/athlete-dashboard/v1/profile');
    error_log('Profile route data: ' . print_r($route, true));

    // Test 4: Check permissions
    $request = new WP_REST_Request('POST', '/athlete-dashboard/v1/profile');
    $response = rest_do_request($request);
    error_log('Test request response: ' . print_r($response, true));

    // Output results
    header('Content-Type: application/json');
    echo json_encode([
        'namespaces' => $namespaces,
        'has_profile_namespace' => in_array('athlete-dashboard/v1', $namespaces),
        'route_exists' => !empty($route),
        'test_response' => $response->get_data()
    ]);
    exit;
}); 