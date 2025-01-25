<?php
/**
 * Cache Statistics Dashboard Widget.
 *
 * This file contains the Cache_Stats_Widget class which provides a dashboard
 * widget displaying cache statistics and performance metrics.
 *
 * @package AthleteDashboard
 * @subpackage Admin
 */

namespace AthleteDashboard\Admin;

use WP_Debug_Data;

/**
 * Class Cache_Stats_Widget
 *
 * Implements a WordPress dashboard widget that displays cache statistics
 * and performance metrics for the Athlete Dashboard.
 *
 * @package AthleteDashboard
 * @subpackage Admin
 */
class Cache_Stats_Widget {
	/**
	 * Initialize the widget.
	 *
	 * @return void
	 */
	public function init() {
		if ( WP_DEBUG ) {
			error_log( 'Initializing cache stats widget.' );
		}
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		add_action( 'wp_ajax_clear_cache', array( $this, 'handle_clear_cache' ) );
		add_action( 'wp_ajax_refresh_cache_stats', array( $this, 'handle_refresh_stats' ) );
	}

	/**
	 * Add the dashboard widget.
	 *
	 * @return void
	 */
	public function add_dashboard_widget() {
		if ( WP_DEBUG ) {
			error_log( 'Adding cache stats dashboard widget.' );
		}
		wp_add_dashboard_widget(
			'athlete_dashboard_cache_stats',
			__( 'Cache Statistics', 'athlete-dashboard' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Handle AJAX request to clear cache.
	 *
	 * @return void
	 */
	public function handle_clear_cache() {
		check_ajax_referer( 'cache_stats_widget_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		// Clear the cache
		wp_cache_flush();
		wp_cache_set( 'last_cleared', time(), 'athlete_dashboard_stats' );

		// Return updated stats
		wp_send_json_success( $this->get_cache_stats() );
	}

	/**
	 * Handle AJAX request to refresh stats.
	 *
	 * @return void
	 */
	public function handle_refresh_stats() {
		check_ajax_referer( 'cache_stats_widget_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		wp_send_json_success( $this->get_cache_stats() );
	}

	/**
	 * Render the widget content.
	 *
	 * @return void
	 */
	public function render_widget() {
		if ( WP_DEBUG ) {
			error_log( 'Rendering cache stats widget.' );
		}

		$stats = $this->get_cache_stats();

		echo '<div class="cache-stats-widget">';
		echo '<h3>' . esc_html__( 'Cache Performance', 'athlete-dashboard' ) . '</h3>';

		echo '<div class="stats-grid">';
		foreach ( $stats as $key => $value ) {
			$is_status   = strpos( $key, 'Cache Status' ) !== false;
			$status_attr = $is_status ? ' data-status="' . strtolower( $value ) . '"' : '';

			echo '<div class="stat-item">';
			echo '<span class="stat-label">' . esc_html( $key ) . '</span>';
			echo '<span class="stat-value"' . $status_attr . '>' . esc_html( $value ) . '</span>';
			echo '</div>';
		}
		echo '</div>';

		echo '<div class="cache-actions">';
		echo '<button class="button" id="clear-cache">' .
			esc_html__( 'Clear Cache', 'athlete-dashboard' ) . '</button>';
		echo '<button class="button" id="refresh-stats">' .
			esc_html__( 'Refresh Stats', 'athlete-dashboard' ) . '</button>';
		echo '</div>';
		echo '</div>';

		$this->enqueue_widget_assets();
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array Array of cache statistics.
	 */
	private function get_cache_stats() {
		if ( WP_DEBUG ) {
			error_log( 'Retrieving cache statistics.' );
		}

		return array(
			__( 'Cache Size', 'athlete-dashboard' )   => $this->get_cache_size(),
			__( 'Cache Hits', 'athlete-dashboard' )   => $this->get_cache_hits(),
			__( 'Cache Misses', 'athlete-dashboard' ) => $this->get_cache_misses(),
			__( 'Cache Ratio', 'athlete-dashboard' )  => $this->get_cache_ratio(),
			__( 'Object Count', 'athlete-dashboard' ) => $this->get_object_count(),
			__( 'Memory Usage', 'athlete-dashboard' ) => $this->get_memory_usage(),
			__( 'Last Cleared', 'athlete-dashboard' ) => $this->get_last_cleared(),
			__( 'Cache Status', 'athlete-dashboard' ) => $this->get_cache_status(),
		);
	}

	/**
	 * Enqueue widget assets.
	 *
	 * @return void
	 */
	private function enqueue_widget_assets() {
		if ( WP_DEBUG ) {
			error_log( 'Enqueuing widget assets.' );
		}

		wp_enqueue_style(
			'cache-stats-widget-style',
			get_stylesheet_directory_uri() . '/includes/admin/css/cache-stats-widget.css',
			array(),
			wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_script(
			'cache-stats-widget-script',
			get_stylesheet_directory_uri() . '/includes/admin/js/cache-stats-widget.js',
			array( 'jquery' ),
			wp_get_theme()->get( 'Version' ),
			true
		);

		wp_localize_script(
			'cache-stats-widget-script',
			'cacheStatsWidgetSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cache_stats_widget_nonce' ),
			)
		);
	}

	/**
	 * Get the cache size.
	 *
	 * @return string Formatted cache size.
	 */
	private function get_cache_size() {
		if ( WP_DEBUG ) {
			error_log( 'Calculating cache size.' );
		}
		$size = wp_cache_get( 'cache_size', 'athlete_dashboard_stats' );
		return $size ? size_format( $size ) : '0 B';
	}

	/**
	 * Get the number of cache hits.
	 *
	 * @return int Number of cache hits.
	 */
	private function get_cache_hits() {
		if ( WP_DEBUG ) {
			error_log( 'Retrieving cache hits.' );
		}
		return (int) wp_cache_get( 'cache_hits', 'athlete_dashboard_stats' );
	}

	/**
	 * Get the number of cache misses.
	 *
	 * @return int Number of cache misses.
	 */
	private function get_cache_misses() {
		if ( WP_DEBUG ) {
			error_log( 'Retrieving cache misses.' );
		}
		return (int) wp_cache_get( 'cache_misses', 'athlete_dashboard_stats' );
	}

	/**
	 * Get the cache hit ratio.
	 *
	 * @return string Formatted cache ratio.
	 */
	private function get_cache_ratio() {
		if ( WP_DEBUG ) {
			error_log( 'Calculating cache ratio.' );
		}
		$hits  = $this->get_cache_hits();
		$total = $hits + $this->get_cache_misses();
		return $total > 0 ? round( ( $hits / $total ) * 100, 2 ) . '%' : '0%';
	}

	/**
	 * Get the number of cached objects.
	 *
	 * @return int Number of cached objects.
	 */
	private function get_object_count() {
		if ( WP_DEBUG ) {
			error_log( 'Counting cached objects.' );
		}
		return (int) wp_cache_get( 'object_count', 'athlete_dashboard_stats' );
	}

	/**
	 * Get the memory usage.
	 *
	 * @return string Formatted memory usage.
	 */
	private function get_memory_usage() {
		if ( WP_DEBUG ) {
			error_log( 'Calculating memory usage.' );
		}
		$usage = wp_cache_get( 'memory_usage', 'athlete_dashboard_stats' );
		return $usage ? size_format( $usage ) : '0 B';
	}

	/**
	 * Get the last time the cache was cleared.
	 *
	 * @return string Formatted date and time.
	 */
	private function get_last_cleared() {
		if ( WP_DEBUG ) {
			error_log( 'Retrieving last cache clear time.' );
		}
		$timestamp = wp_cache_get( 'last_cleared', 'athlete_dashboard_stats' );
		return $timestamp ? date_i18n(
			get_option( 'date_format' ) . ' ' .
			get_option( 'time_format' ),
			$timestamp
		) :
							__( 'Never', 'athlete-dashboard' );
	}

	/**
	 * Get the cache status.
	 *
	 * @return string Cache status message.
	 */
	private function get_cache_status() {
		if ( WP_DEBUG ) {
			error_log( 'Checking cache status.' );
		}
		return wp_cache_get( 'cache_enabled', 'athlete_dashboard_stats' ) ?
				__( 'Enabled', 'athlete-dashboard' ) :
				__( 'Disabled', 'athlete-dashboard' );
	}
}
