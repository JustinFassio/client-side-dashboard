<?php
/**
 * Abstract Feature Class
 *
 * @package AthleteDashboard\Features\Core\Contracts
 */

namespace AthleteDashboard\Features\Core\Contracts;

/**
 * Class Abstract_Feature
 *
 * Provides base implementation for features.
 */
abstract class Abstract_Feature implements Feature_Contract {
	/**
	 * Get the public API exposed by this feature.
	 *
	 * @return array{
	 *     services?: array<class-string>,
	 *     events?: array<class-string>,
	 *     endpoints?: array<class-string>
	 * }
	 */
	public function get_public_api(): array {
		return [];
	}

	/**
	 * Get feature dependencies.
	 *
	 * @return array<string, array{
	 *     events?: array<class-string>,
	 *     version?: string
	 * }>
	 */
	public function get_dependencies(): array {
		return [];
	}

	/**
	 * Get event subscriptions.
	 *
	 * @return array<class-string, array{
	 *     handler: string,
	 *     priority?: int
	 * }>
	 */
	public function get_event_subscriptions(): array {
		return [];
	}

	/**
	 * Initialize the feature.
	 *
	 * @return void
	 */
	abstract public function init(): void;
} 