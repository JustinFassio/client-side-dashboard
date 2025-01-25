<?php
/**
 * Events management class.
 *
 * @package AthleteDashboard\Core
 */

namespace AthleteDashboard\Core;

/**
 * Class Events
 *
 * Handles event registration and dispatching.
 */
class Events {
	/**
	 * Event listeners.
	 *
	 * @var array
	 */
	private static array $listeners = array();

	/**
	 * Register an event listener.
	 *
	 * @param string   $event    Event class name.
	 * @param callable $listener Listener callback.
	 * @return void
	 */
	public static function listen( string $event, callable $listener ): void {
		if ( ! isset( self::$listeners[ $event ] ) ) {
			self::$listeners[ $event ] = array();
		}

		self::$listeners[ $event ][] = $listener;
	}

	/**
	 * Dispatch an event.
	 *
	 * @param object $event Event instance.
	 * @return void
	 */
	public static function dispatch( object $event ): void {
		$event_class = get_class( $event );

		if ( ! isset( self::$listeners[ $event_class ] ) ) {
			return;
		}

		foreach ( self::$listeners[ $event_class ] as $listener ) {
			$listener( $event );
		}
	}

	/**
	 * Remove all listeners for an event.
	 *
	 * @param string|null $event Event class name or null to remove all listeners.
	 * @return void
	 */
	public static function forget( ?string $event = null ): void {
		if ( $event === null ) {
			self::$listeners = array();
			return;
		}

		unset( self::$listeners[ $event ] );
	}

	/**
	 * Check if an event has listeners.
	 *
	 * @param string $event Event class name.
	 * @return bool True if the event has listeners.
	 */
	public static function hasListeners( string $event ): bool {
		return isset( self::$listeners[ $event ] ) && ! empty( self::$listeners[ $event ] );
	}

	/**
	 * Get all registered listeners for an event.
	 *
	 * @param string|null $event Event class name or null to get all listeners.
	 * @return array Array of listeners.
	 */
	public static function getListeners( ?string $event = null ): array {
		if ( $event === null ) {
			return self::$listeners;
		}

		return self::$listeners[ $event ] ?? array();
	}
}
