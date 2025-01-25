<?php
/**
 * Redis Configuration for Athlete Dashboard
 *
 * Configures Redis caching with optimized settings for different cache categories.
 *
 * @package AthleteDashboard\Config
 */

namespace AthleteDashboard\Config;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enable Redis object caching
if ( ! defined( 'WP_CACHE' ) ) {
	define( 'WP_CACHE', true );
}

if ( ! defined( 'WP_REDIS_DISABLED' ) ) {
	define( 'WP_REDIS_DISABLED', false );
}

// Redis connection settings
if ( ! defined( 'WP_REDIS_HOST' ) ) {
	define( 'WP_REDIS_HOST', '127.0.0.1' );
}

if ( ! defined( 'WP_REDIS_PORT' ) ) {
	define( 'WP_REDIS_PORT', 6379 );
}

if ( ! defined( 'WP_REDIS_TIMEOUT' ) ) {
	define( 'WP_REDIS_TIMEOUT', 1 );
}

if ( ! defined( 'WP_REDIS_READ_TIMEOUT' ) ) {
	define( 'WP_REDIS_READ_TIMEOUT', 1 );
}

// Cache key settings
if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
	define( 'WP_CACHE_KEY_SALT', 'athlete_dashboard_' );
}

// Redis database selection (0-15)
if ( ! defined( 'WP_REDIS_DATABASE' ) ) {
	define( 'WP_REDIS_DATABASE', 0 );
}

// Persistent connections
if ( ! defined( 'WP_REDIS_PERSISTENT' ) ) {
	define( 'WP_REDIS_PERSISTENT', true );
}

// Cache groups configuration
$redis_cache_groups = array(
	// Critical data - persistent with shorter TTL
	'athlete_dashboard_critical' => array(
		'persistent' => true,
		'ttl'        => 3600, // 1 hour
	),
	
	// Frequent data - in-memory only
	'athlete_dashboard_frequent' => array(
		'persistent' => false,
		'ttl'        => 900, // 15 minutes
	),
	
	// Computed data - with stampede protection
	'athlete_dashboard_computed' => array(
		'persistent' => false,
		'ttl'        => 1800, // 30 minutes
		'stampede_ttl' => 30, // 30 seconds grace period
	),
	
	// Static data - persistent with longer TTL
	'athlete_dashboard_static' => array(
		'persistent' => true,
		'ttl'        => 86400, // 24 hours
	),
);

// Set cache groups configuration
if ( ! defined( 'WP_REDIS_SELECTIVE_FLUSH' ) ) {
	define( 'WP_REDIS_SELECTIVE_FLUSH', true );
}

// Configure Redis cache groups
global $redis_cache_groups;

// Configure ignored groups (never cache these)
$redis_ignored_groups = array(
	'users',
	'userlogins',
	'usermeta',
	'user_meta',
	'site-transient',
	'site-options',
	'site-lookup',
	'blog-lookup',
	'blog-details',
	'rss',
	'global-posts',
	'blog-id-cache',
);

// Set ignored groups
if ( ! defined( 'WP_REDIS_IGNORED_GROUPS' ) ) {
	define( 'WP_REDIS_IGNORED_GROUPS', serialize( $redis_ignored_groups ) );
}

// Maxttl for different cache categories
$redis_maxttl = array(
	'athlete_dashboard_critical' => 7200,    // 2 hours
	'athlete_dashboard_frequent' => 1800,    // 30 minutes
	'athlete_dashboard_computed' => 3600,    // 1 hour
	'athlete_dashboard_static'   => 172800,  // 48 hours
);

if ( ! defined( 'WP_REDIS_MAXTTL' ) ) {
	define( 'WP_REDIS_MAXTTL', serialize( $redis_maxttl ) );
} 