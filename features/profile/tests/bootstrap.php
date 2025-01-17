<?php
/**
 * Bootstrap file for Profile Feature Tests
 *
 * @package AthleteDashboard\Features\Profile\Tests
 */

// Load Composer's autoloader.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/vendor/autoload.php';

// Load core contracts.
require_once dirname( dirname( __DIR__ ) ) . '/core/contracts/interface-feature-contract.php';
require_once dirname( dirname( __DIR__ ) ) . '/core/contracts/class-abstract-feature.php';

// Load Profile feature files.
require_once dirname( __DIR__ ) . '/class-profile-feature.php';
require_once dirname( __DIR__ ) . '/api/class-profile-routes.php';
require_once dirname( __DIR__ ) . '/services/class-profile-service.php';
require_once dirname( __DIR__ ) . '/services/class-user-service.php';
require_once dirname( __DIR__ ) . '/events/class-profile-updated.php';
require_once dirname( __DIR__ ) . '/services/class-physical-service.php';

// Load WordPress test suite
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';
require_once $_tests_dir . '/includes/bootstrap.php';
