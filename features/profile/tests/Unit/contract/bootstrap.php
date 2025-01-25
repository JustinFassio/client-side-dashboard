<?php
/**
 * PHPUnit bootstrap file for Feature Contract tests
 *
 * @package AthleteDashboard\Features\Profile\Tests\Unit\Contract
 */

// First, load the composer autoloader
require_once dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/vendor/autoload.php';

// Load core contracts early
require_once dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/features/core/contracts/interface-feature-contract.php';
require_once dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/features/core/contracts/class-abstract-feature.php';

// Load the WordPress test suite
$_tests_dir = dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) . '/vendor/wp-phpunit/wp-phpunit';

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// Start up the WP testing environment.
require_once $_tests_dir . '/includes/bootstrap.php'; 