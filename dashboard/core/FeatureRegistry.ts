import { Feature, FeatureContext } from '../contracts/Feature';
import { Events } from './events';

export class FeatureRegistry {
  private features: Map<string, Feature> = new Map();
  private initialized: Set<string> = new Set();
  private context: FeatureContext;

  constructor(context: FeatureContext) {
    this.context = context;
    this.setupGlobalEvents();
  }

  private setupGlobalEvents() {
    // Handle user changes
    Events.on('user:changed', (userId: number) => {
      this.features.forEach(feature => {
        if (feature.onUserChange) {
          feature.onUserChange(userId);
        }
      });
    });

    // Handle navigation
    Events.on('navigation:changed', (path: string) => {
      this.features.forEach(feature => {
        if (feature.onNavigate) {
          feature.onNavigate();
        }
      });
    });
  }

  /**
   * Register a new feature
   */
  async register(feature: Feature): Promise<void> {
    if (this.features.has(feature.identifier)) {
      throw new Error(`Feature ${feature.identifier} is already registered`);
    }

    try {
      // Register the feature
      this.features.set(feature.identifier, feature);
      
      // Initialize if enabled
      if (feature.isEnabled()) {
        await this.initFeature(feature);
      }

      // Emit registration event
      Events.emit('feature.registered', {
        identifier: feature.identifier,
        metadata: feature.metadata
      });

      // Log registration
      if (this.context.debug) {
        console.log(`Registered feature: ${feature.identifier}`, {
          metadata: feature.metadata,
          enabled: feature.isEnabled()
        });
      }
    } catch (error) {
      Events.emit('feature.error', {
        identifier: feature.identifier,
        error
      });
      throw error;
    }
  }

  /**
   * Initialize a feature
   */
  private async initFeature(feature: Feature): Promise<void> {
    if (this.initialized.has(feature.identifier)) {
      return;
    }

    try {
      // Call lifecycle methods
      await feature.register(this.context);
      await feature.init();
      
      this.initialized.add(feature.identifier);

      Events.emit('feature.initialized', {
        identifier: feature.identifier
      });

      // Log initialization
      if (this.context.debug) {
        console.log(`Initialized feature: ${feature.identifier}`);
      }
    } catch (error) {
      Events.emit('feature.error', {
        identifier: feature.identifier,
        error
      });
      throw error;
    }
  }

  /**
   * Get a registered feature
   */
  getFeature(identifier: string): Feature | undefined {
    return this.features.get(identifier);
  }

  /**
   * Get all registered features
   */
  getAllFeatures(): Feature[] {
    return Array.from(this.features.values());
  }

  /**
   * Get all enabled features
   */
  getEnabledFeatures(): Feature[] {
    return this.getAllFeatures().filter(feature => feature.isEnabled());
  }

  /**
   * Check if a feature is initialized
   */
  isFeatureInitialized(identifier: string): boolean {
    return this.initialized.has(identifier);
  }

  /**
   * Cleanup all features
   */
  async cleanup(): Promise<void> {
    for (const feature of this.features.values()) {
      if (this.initialized.has(feature.identifier)) {
        await feature.cleanup();
      }
    }
    
    this.features.clear();
    this.initialized.clear();

    // Remove global event listeners
    Events.removeAllListeners();
  }
} 