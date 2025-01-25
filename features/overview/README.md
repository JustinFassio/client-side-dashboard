# Overview Feature

## Overview
The Overview feature provides a comprehensive dashboard view of the athlete's activities, progress, and key metrics. It aggregates data from various features to present a unified view of the athlete's training status and achievements.

## Configuration
```typescript
interface OverviewConfig {
    enabled: boolean;
    widgets: {
        recentActivity: boolean;
        progressMetrics: boolean;
        upcomingWorkouts: boolean;
        goals: boolean;
    };
    refresh: {
        interval: number; // milliseconds
        autoRefresh: boolean;
    };
    display: {
        defaultTimeRange: '7d' | '30d' | '90d';
        maxMetrics: number;
        chartType: 'line' | 'bar';
    };
}
```

## API Endpoints

### Base Path
```
/wp-json/athlete-dashboard/v1/overview
```

### Endpoints

#### GET /summary
- **Purpose**: Retrieve overview dashboard data
- **Authentication**: Required
- **Response**:
  ```typescript
  interface OverviewResponse {
      success: boolean;
      data: {
          summary: {
              recentActivity: Activity[];
              metrics: {
                  workoutsCompleted: number;
                  totalDuration: number;
                  averageIntensity: number;
              };
              goals: Goal[];
              upcomingWorkouts: Workout[];
          };
      };
  }
  ```

#### GET /metrics
- **Purpose**: Fetch specific metrics for the dashboard
- **Authentication**: Required
- **Parameters**:
  ```typescript
  interface MetricsRequest {
      timeRange: '7d' | '30d' | '90d';
      metrics: string[];
  }
  ```
- **Error Codes**:
  - `400`: Invalid request parameters
  - `401`: Unauthorized
  - `500`: Server error

## Events/Actions

### WordPress Actions
```php
// Fired when overview data is refreshed
do_action('athlete_dashboard_overview_refreshed', $user_id);

// Fired when a metric is updated
do_action('athlete_dashboard_metric_updated', $user_id, $metric_key, $value);
```

### TypeScript Events
```typescript
enum OverviewEvent {
    REFRESH_REQUEST = 'OVERVIEW_REFRESH_REQUEST',
    REFRESH_SUCCESS = 'OVERVIEW_REFRESH_SUCCESS',
    REFRESH_ERROR = 'OVERVIEW_REFRESH_ERROR',
    METRIC_UPDATED = 'OVERVIEW_METRIC_UPDATED',
    WIDGET_TOGGLE = 'OVERVIEW_WIDGET_TOGGLE'
}
```

## Components

### Main Components
- `OverviewDashboard`: Main dashboard layout
  ```typescript
  interface OverviewDashboardProps {
      timeRange: string;
      onTimeRangeChange: (range: string) => void;
      refreshInterval?: number;
  }
  ```
- `MetricsWidget`: Displays key performance metrics
  ```typescript
  interface MetricsWidgetProps {
      metrics: Metric[];
      chartType?: 'line' | 'bar';
      onMetricClick?: (metric: Metric) => void;
  }
  ```

### Hooks
- `useOverview`: Access overview data and methods
  ```typescript
  function useOverview(): {
      summary: OverviewSummary | null;
      loading: boolean;
      error: Error | null;
      refresh: () => Promise<void>;
      toggleWidget: (widgetId: string) => void;
  }
  ```

## Dependencies

### External
- @wordpress/api-fetch
- @wordpress/hooks
- chart.js
- date-fns

### Internal
- ProfileContext (from profile feature)
- WorkoutService (from workout feature)
- ChartComponents (from dashboard/components)

## Testing

### Unit Tests
```bash
# Run overview feature tests
npm run test features/overview
```

### Integration Tests
```bash
# Run overview integration tests
npm run test:integration features/overview
```

## Error Handling

### Error Types
```typescript
enum OverviewErrorCodes {
    REFRESH_FAILED = 'OVERVIEW_REFRESH_FAILED',
    INVALID_METRICS = 'OVERVIEW_INVALID_METRICS',
    DATA_FETCH_FAILED = 'OVERVIEW_DATA_FETCH_FAILED',
    WIDGET_ERROR = 'OVERVIEW_WIDGET_ERROR'
}
```

### Error Recovery
- Automatic retry on data fetch failures
- Graceful degradation of widgets
- Cached data fallback
- Individual widget error containment

## Performance Considerations
- Cached dashboard data
- Lazy loading of widgets
- Optimized chart rendering
- Debounced refresh calls
- Efficient data aggregation

## Security
- Data access validation
- Metric calculation verification
- Input sanitization
- Cross-feature data access control
- User data isolation

## Changelog
- 1.2.0: Added customizable widgets
- 1.1.0: Enhanced metrics visualization
- 1.0.1: Performance improvements
- 1.0.0: Initial release 

### Styling Guidelines

#### Button Patterns
All primary action buttons (e.g., "Refresh Overview", "Update Dashboard") should follow these styling rules:
```css
.action-button {
    background: var(--primary-color);
    color: var(--background-darker);  /* Critical for text contrast */
    border: none;
    padding: var(--spacing-sm) var(--spacing-lg);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
    cursor: pointer;
    transition: background-color var(--transition-fast);
}

.action-button:hover {
    background: var(--primary-hover);
    color: var(--background-darker);
    transform: translateY(-1px);
}

.action-button:disabled {
    background-color: var(--text-dim);
    cursor: not-allowed;
    opacity: 0.7;
}
```

Key styling principles:
1. Use `var(--background-darker)` for button text to ensure contrast against citron green
2. Maintain consistent padding using spacing variables
3. Include hover state with subtle transform effect
4. Use transition for smooth hover effects
5. Include disabled state styling

#### Theme Integration
- Import variables from dashboard: `@import '../../../../dashboard/styles/variables.css';`
- Use CSS variables for colors, spacing, and typography
- Follow dark theme color scheme for consistent UI

#### Responsive Design
- Use breakpoints at 768px and 480px
- Adjust grid layouts and padding for mobile
- Maintain button styling across all screen sizes 