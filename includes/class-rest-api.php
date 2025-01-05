<?php
namespace AthleteDashboard;

use AthleteDashboard\RestApi\Overview_Controller;

class Rest_Api {
    /**
     * Initialize the REST API.
     */
    public static function init() {
        add_action('rest_api_init', [self::class, 'register_controllers']);
    }

    /**
     * Register REST API controllers.
     */
    public static function register_controllers() {
        // Register Overview controller
        $overview_controller = new Overview_Controller();
        $overview_controller->register_routes();

        // Register other controllers here
    }
} 