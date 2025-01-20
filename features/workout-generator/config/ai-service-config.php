<?php
/**
 * AI Service Configuration
 *
 * This file defines the configuration constants for the AI workout generation service.
 * For security, the actual values should be set in the wp-config.php file or through environment variables.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define AI service configuration constants if not already defined
if ( ! defined( 'AI_SERVICE_API_KEY' ) ) {
	define( 'AI_SERVICE_API_KEY', getenv( 'ATHLETE_DASHBOARD_AI_API_KEY' ) ?: '' );
}

if ( ! defined( 'AI_SERVICE_ENDPOINT' ) ) {
	define( 'AI_SERVICE_ENDPOINT', getenv( 'ATHLETE_DASHBOARD_AI_ENDPOINT' ) ?: 'https://api.athlete-dashboard.ai/v1' );
}

// Optional: Define rate limiting constants
if ( ! defined( 'AI_SERVICE_RATE_LIMIT' ) ) {
	define( 'AI_SERVICE_RATE_LIMIT', getenv( 'ATHLETE_DASHBOARD_AI_RATE_LIMIT' ) ?: 100 );
}

if ( ! defined( 'AI_SERVICE_RATE_WINDOW' ) ) {
	define( 'AI_SERVICE_RATE_WINDOW', getenv( 'ATHLETE_DASHBOARD_AI_RATE_WINDOW' ) ?: 3600 );
}
