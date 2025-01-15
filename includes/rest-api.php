<?php
/**
 * Custom REST API endpoints for the Athlete Dashboard
 *
 * @package AthleteDashboard\Features\Profile\API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AthleteDashboard\Core\Config\Debug;
use AthleteDashboard\Features\Profile\API\Profile_Endpoints;

// Initialize the Profile_Endpoints class
add_action( 'init', array( Profile_Endpoints::class, 'init' ) );
