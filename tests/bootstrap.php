<?php
/**
 * PHPUnit bootstrap file
 */

// First, let's try to load the composer autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    die('Please run `composer install` before running tests.');
}
require_once $autoloader;

// Load WP_Mock
WP_Mock::bootstrap();

// Define WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

// Load our test case base class
require_once __DIR__ . '/php/TestCase.php';

// Load any additional test helpers
require_once __DIR__ . '/php/helpers.php'; 