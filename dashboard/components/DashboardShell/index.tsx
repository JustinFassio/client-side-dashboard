import { createElement, useState, useEffect } from '@wordpress/element';
import { Feature, FeatureContext } from '../../contracts/Feature';
import { FeatureRegistry } from '../../core/FeatureRegistry';
import { Events } from '../../core/events';
import './DashboardShell.css';

interface DashboardShellProps {}

interface NavItem {
  id: string;
  label: string;
  icon?: string;
}

export const DashboardShell: React.FC<DashboardShellProps> = () => {
  const [activeFeature, setActiveFeature] = useState<Feature | null>(null);
  const [registry, setRegistry] = useState<FeatureRegistry | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Initialize the feature registry
  useEffect(() => {
    const initializeRegistry = () => {
      try {
        // Check if required globals are available
        if (!window.wp?.data?.dispatch) {
          setError('WordPress data system is not initialized');
          setIsLoading(false);
          return;
        }

        if (!window.athleteDashboardData) {
          setError('Dashboard configuration is not available');
          setIsLoading(false);
          return;
        }

        const context: FeatureContext = {
          dispatch: window.wp.data.dispatch,
          userId: window.athleteDashboardData.userId,
          nonce: window.athleteDashboardData.nonce,
          apiUrl: window.athleteDashboardData.apiUrl
        };

        const featureRegistry = new FeatureRegistry(context);

        // Listen for feature registration
        Events.on('feature.registered', ({ identifier }) => {
          const feature = featureRegistry.getFeature(identifier);
          if (feature && !activeFeature) {
            setActiveFeature(feature);
          }
        });

        // Listen for feature errors
        Events.on('feature.error', ({ identifier, error }) => {
          console.error(`Feature ${identifier} error:`, error);
        });

        setRegistry(featureRegistry);
        setIsLoading(false);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to initialize dashboard');
        setIsLoading(false);
      }
    };

    // Initialize after a short delay to ensure WordPress is ready
    const timer = setTimeout(initializeRegistry, 100);
    return () => clearTimeout(timer);
  }, []);

  // Handle navigation
  const handleNavigation = async (featureId: string) => {
    if (!registry) return;

    const feature = registry.getFeature(featureId);
    if (!feature) return;

    try {
      // Cleanup current feature if exists
      if (activeFeature) {
        await activeFeature.cleanup();
      }

      // Initialize new feature if needed
      if (!feature.isEnabled()) {
        await registry.register(feature);
      }

      setActiveFeature(feature);
      feature.onNavigate?.();

      Events.emit('feature.navigated', {
        from: activeFeature?.identifier,
        to: feature.identifier
      });
    } catch (error) {
      console.error('Navigation error:', error);
    }
  };

  // Get navigation items from enabled features
  const getNavItems = (): NavItem[] => {
    if (!registry) return [];
    
    return registry.getEnabledFeatures()
      .map(feature => ({
        id: feature.identifier,
        label: feature.metadata.name,
        icon: feature.metadata.icon
      }))
      .sort((a, b) => {
        const featureA = registry.getFeature(a.id);
        const featureB = registry.getFeature(b.id);
        return (featureA?.metadata.order || 0) - (featureB?.metadata.order || 0);
      });
  };

  if (isLoading) {
    return <div className="dashboard-shell-loading">Loading dashboard...</div>;
  }

  if (error) {
    return <div className="dashboard-shell-error">{error}</div>;
  }

  return (
    <div className="dashboard-shell">
      <nav className="dashboard-nav">
        <ul className="nav-items">
          {getNavItems().map(item => (
            <li key={item.id} className="nav-item">
              <button 
                className={`nav-button ${activeFeature?.identifier === item.id ? 'active' : ''}`}
                onClick={() => handleNavigation(item.id)}
              >
                {item.icon && <span className="nav-icon">{item.icon}</span>}
                <span className="nav-label">{item.label}</span>
              </button>
            </li>
          ))}
        </ul>
      </nav>
      <main className="dashboard-main">
        <header className="dashboard-header">
          <h1>{activeFeature?.metadata.name || 'Athlete Dashboard'}</h1>
        </header>
        <div className="dashboard-content">
          {activeFeature ? (
            activeFeature.render()
          ) : (
            <div className="no-feature-selected">
              Select a feature from the navigation
            </div>
          )}
        </div>
      </main>
    </div>
  );
}; 