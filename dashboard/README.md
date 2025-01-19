# Dashboard Module

The Dashboard module serves as a minimal shared shell for the athlete dashboard, providing only essential scaffolding while keeping features independent.

## Core Responsibilities

1. **Feature Management**
   - Feature registration and lifecycle management via `FeatureRegistry`
   - Minimal routing between features
   - Basic event system for cross-feature communication

2. **Shell Components**
   - Basic layout structure (`DashboardShell`)
   - Loading and error states
   - Navigation between features
   - Error boundaries

3. **Shared Infrastructure**
   - TypeScript definitions and contracts
   - Basic API services
   - Common utilities and hooks
   - WordPress integration points

## Directory Structure

```
dashboard/
├── components/       # Minimal shell components
│   ├── DashboardShell/    # Main application container
│   ├── FeatureRouter/     # Feature routing
│   ├── Navigation/        # Feature navigation
│   └── [shared]/         # Basic shared components
├── core/            # Feature management
│   ├── FeatureRegistry.ts # Feature registration
│   ├── events.ts         # Event system
│   └── services/        # Base services
├── contracts/       # Feature interfaces
├── hooks/           # Essential shared hooks
├── types/           # TypeScript definitions
└── utils/           # Common utilities
```

## Feature Integration

Features remain independent and self-contained, interacting with the dashboard only through:

1. **Registration**
   ```typescript
   class MyFeature extends Feature {
     identifier = 'my-feature';
     async register() {
       // Feature-specific registration
     }
   }
   ```

2. **Event System**
   ```typescript
   // Publishing events
   Events.publish('feature:action', payload);
   
   // Subscribing to events
   Events.subscribe('feature:action', handler);
   ```

3. **Routing**
   - Features define their own routes
   - Dashboard provides navigation between features

## Best Practices

1. **Keep the Shell Minimal**
   - Avoid adding feature-specific logic to the dashboard
   - Move shared functionality to appropriate features
   - Maintain clear boundaries between shell and features

2. **Feature Independence**
   - Features should be self-contained
   - Minimize cross-feature dependencies
   - Use events for cross-feature communication

3. **Type Safety**
   - Use provided TypeScript interfaces
   - Extend base types appropriately
   - Maintain strict type checking

## Development Guidelines

1. **Adding Shell Components**
   - Only add components needed by multiple features
   - Keep components simple and unopinionated
   - Document clear usage patterns

2. **Feature Communication**
   - Use the event system for loose coupling
   - Avoid direct feature-to-feature dependencies
   - Document event contracts

3. **WordPress Integration**
   - Maintain minimal WordPress coupling
   - Use provided integration points
   - Document WordPress dependencies

## Shared Components

### Button Component

The Button component provides consistent styling and behavior across features. It supports feature-specific styling while maintaining a cohesive look.

```typescript
import { Button } from '@dashboard/components/Button';

// Basic usage
<Button
    variant="primary"
    feature="profile"
    onClick={handleClick}
    disabled={isLoading}
    aria-busy={isLoading}
>
    Save Changes
</Button>
```

#### Props
- `variant`: Visual style ('primary' | 'secondary')
- `feature`: Feature-specific styling ('profile' | 'physical')
- Standard button props (onClick, disabled, etc.)

#### Best Practices
1. Always specify both `variant` and `feature` props
2. Use `aria-busy` for loading states
3. Include descriptive button text
4. Follow feature-specific styling:
   - Use `feature="profile"` for profile-related sections
   - Use `feature="physical"` for physical data sections

## Example: Feature Registration

```typescript
import { Feature } from '../core/Feature';
import { FeatureRegistry } from '../core/FeatureRegistry';

// Register a new feature
const registry = new FeatureRegistry(context);
await registry.register(new MyFeature());

// Feature implementation
class MyFeature extends Feature {
  identifier = 'my-feature';
  
  async register() {
    // Minimal registration logic
    this.registerRoutes();
    this.registerEventHandlers();
  }
}
``` 