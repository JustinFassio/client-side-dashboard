<?php
/**
 * Feature Contract Interface
 *
 * @package AthleteDashboard\Features\Core\Contracts
 */

namespace AthleteDashboard\Features\Core\Contracts;

/**
 * Interface Feature_Contract
 *
 * Defines the contract that all features must implement.
 */
interface Feature_Contract {
	/**
	 * Initialize the feature.
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Get the public API exposed by this feature.
	 *
	 * @return array{
	 *     services?: array<class-string>,
	 *     events?: array<class-string>,
	 *     endpoints?: array<class-string>
	 * }
	 */
	public function get_public_api(): array;

	/**
	 * Get feature dependencies.
	 *
	 * @return array<string, array{
	 *     events?: array<class-string>,
	 *     version?: string
	 * }>
	 */
	public function get_dependencies(): array;

	/**
	 * Get event subscriptions.
	 *
	 * @return array<class-string, array{
	 *     handler: string,
	 *     priority?: int
	 * }>
	 */
	public function get_event_subscriptions(): array;
} 