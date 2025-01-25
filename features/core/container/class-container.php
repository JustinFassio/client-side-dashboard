<?php
/**
 * Container class for dependency injection.
 *
 * @package AthleteDashboard\Core
 */

namespace AthleteDashboard\Core;

/**
 * Simple dependency injection container.
 */
class Container {
	/**
	 * The container bindings.
	 *
	 * @var array
	 */
	private array $bindings = array();

	/**
	 * The resolved instances.
	 *
	 * @var array
	 */
	private array $instances = array();

	/**
	 * Register a binding with the container.
	 *
	 * @param string   $abstract The abstract type to register.
	 * @param callable $concrete The concrete instance or factory function.
	 * @return void
	 */
	public function singleton( string $abstract, callable $concrete ): void {
		$this->bindings[ $abstract ] = $concrete;
	}

	/**
	 * Get an instance from the container.
	 *
	 * @param string $abstract The abstract type to resolve.
	 * @return mixed The resolved instance.
	 * @throws \Exception If the binding cannot be resolved.
	 */
	public function get( string $abstract ): mixed {
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		if ( ! isset( $this->bindings[ $abstract ] ) ) {
			throw new \Exception( "No binding found for {$abstract}" );
		}

		$concrete = $this->bindings[ $abstract ];
		$instance = $concrete( $this );

		$this->instances[ $abstract ] = $instance;

		return $instance;
	}

	/**
	 * Check if a binding exists in the container.
	 *
	 * @param string $abstract The abstract type to check.
	 * @return bool True if the binding exists.
	 */
	public function has( string $abstract ): bool {
		return isset( $this->bindings[ $abstract ] );
	}

	/**
	 * Remove a binding from the container.
	 *
	 * @param string $abstract The abstract type to remove.
	 * @return void
	 */
	public function remove( string $abstract ): void {
		unset( $this->bindings[ $abstract ] );
		unset( $this->instances[ $abstract ] );
	}
}
