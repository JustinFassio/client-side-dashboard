<?php

use AthleteDashboard\Services\Cache_Warmer;
use AthleteDashboard\Services\Cache_Monitor;
use AthleteDashboard\Admin\Cache_Stats_Widget;

class Athlete_Dashboard {
	private $cache_warmer;
	private $cache_monitor;
	private $cache_stats_widget;

	public function __construct() {
		// Initialize cache warmer
		$this->cache_warmer = new Cache_Warmer();
		$this->cache_warmer->init();

		// Initialize cache monitor
		$this->cache_monitor = new Cache_Monitor();
		$this->cache_monitor->init();

		// Initialize cache stats widget
		$this->cache_stats_widget = new Cache_Stats_Widget();
		$this->cache_stats_widget->init();

		// Record response time on shutdown
		add_action( 'shutdown', array( $this->cache_monitor, 'record_response_time' ) );
	}
}
