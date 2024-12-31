import { createHooks } from '@wordpress/hooks';

export type EventCallback<T = any> = (data: T) => void;

class DashboardEvents {
  private hooks = createHooks();
  private namespace = 'athlete-dashboard';

  /**
   * Add an event listener
   */
  on<T = any>(event: string, callback: EventCallback<T>, priority = 10): void {
    this.hooks.addAction(
      `${this.namespace}.${event}`,
      this.namespace,
      callback,
      priority
    );
  }

  /**
   * Remove an event listener
   */
  off<T = any>(event: string, callback: EventCallback<T>): void {
    this.hooks.removeAction(
      `${this.namespace}.${event}`,
      this.namespace,
      callback
    );
  }

  /**
   * Emit an event
   */
  emit<T = any>(event: string, data: T): void {
    this.hooks.doAction(`${this.namespace}.${event}`, data);
  }

  /**
   * Add a filter
   */
  addFilter<T = any>(
    name: string,
    callback: (value: T) => T,
    priority = 10
  ): void {
    this.hooks.addFilter(
      `${this.namespace}.${name}`,
      this.namespace,
      callback,
      priority
    );
  }

  /**
   * Apply filters to a value
   */
  applyFilters<T = any>(name: string, value: T): T {
    return this.hooks.applyFilters(
      `${this.namespace}.${name}`,
      value
    ) as T;
  }
}

export const Events = new DashboardEvents(); 