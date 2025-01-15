<?php
/**
 * Bootstrap file for Profile Feature Tests
 *
 * @package AthleteDashboard\Features\Profile\Tests
 */

// Load Composer's autoloader.
require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/vendor/autoload.php';

// Load core contracts.
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/core/contracts/interface-feature-contract.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/core/contracts/class-abstract-feature.php';

// Load Profile feature files.
require_once dirname( dirname( __FILE__ ) ) . '/class-profile-feature.php';
require_once dirname( dirname( __FILE__ ) ) . '/api/class-profile-routes.php';
require_once dirname( dirname( __FILE__ ) ) . '/services/class-profile-service.php';
require_once dirname( dirname( __FILE__ ) ) . '/services/class-user-service.php';
require_once dirname( dirname( __FILE__ ) ) . '/events/class-profile-updated.php'; 