<?php
/**
 * Bootstrap file for Profile Feature Tests
 *
 * @package AthleteDashboard\Features\Profile\Tests
 */

// Load Composer's autoloader.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/vendor/autoload.php';

// Load WordPress test suite
$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Try multiple common locations if WP_TESTS_DIR is not set
if ( ! $_tests_dir ) {
	$_possible_dirs = array(
		rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib',
		dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/tests/phpunit',
		'/tmp/wordpress-tests-lib',
		__DIR__ . '/../../wordpress-tests-lib',
	);

	foreach ( $_possible_dirs as $dir ) {
		if ( file_exists( $dir . '/includes/functions.php' ) ) {
			$_tests_dir = $dir;
			break;
		}
	}
}

// If we still can't find the tests directory, provide helpful error
if ( ! $_tests_dir || ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "\nError: Could not find WordPress tests directory.\n";
	echo "Please set WP_TESTS_DIR environment variable or install WordPress test suite using bin/install-wp-tests.sh\n";
	echo "Example: WP_TESTS_DIR=/path/to/wordpress/tests vendor/bin/phpunit\n\n";
	exit( 1 );
}

// Load test environment
require_once $_tests_dir . '/includes/functions.php';

// Load WordPress test bootstrap
require_once $_tests_dir . '/includes/bootstrap.php';

// Load WordPress REST API functions
require_once ABSPATH . WPINC . '/rest-api.php';
require_once ABSPATH . WPINC . '/rest-api/endpoints/class-wp-rest-controller.php';
require_once ABSPATH . WPINC . '/rest-api/class-wp-rest-response.php';

// Load core contracts.
require_once dirname( dirname( __DIR__ ) ) . '/core/contracts/interface-feature-contract.php';
require_once dirname( dirname( __DIR__ ) ) . '/core/contracts/class-abstract-feature.php';

// Load core services
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/services/class-cache-service.php';

// Load Profile feature files.
require_once dirname( __DIR__ ) . '/class-profile-feature.php';
require_once dirname( __DIR__ ) . '/api/class-profile-routes.php';
require_once dirname( __DIR__ ) . '/services/class-profile-service.php';
require_once dirname( __DIR__ ) . '/services/class-user-service.php';
require_once dirname( __DIR__ ) . '/events/class-profile-updated.php';
require_once dirname( __DIR__ ) . '/services/class-physical-service.php';

// Load endpoint base classes
require_once dirname( __DIR__ ) . '/api/endpoints/base/class-base-endpoint.php';
require_once dirname( __DIR__ ) . '/api/endpoints/base/trait-auth-checks.php';

// Load Profile endpoints
require_once dirname( __DIR__ ) . '/api/endpoints/profile/class-profile-update.php';
require_once dirname( __DIR__ ) . '/api/endpoints/profile/class-profile-get.php';
require_once dirname( __DIR__ ) . '/api/endpoints/profile/class-profile-delete.php';

// Load User endpoints
require_once dirname( __DIR__ ) . '/api/endpoints/user/class-user-get.php';
require_once dirname( __DIR__ ) . '/api/endpoints/user/class-user-update.php';

// Load Physical endpoints
require_once dirname( __DIR__ ) . '/api/endpoints/physical/class-physical-get.php';
require_once dirname( __DIR__ ) . '/api/endpoints/physical/class-physical-update.php';
require_once dirname( __DIR__ ) . '/api/endpoints/physical/class-physical-history.php';
