# AI Equipment Manager

## Overview
The AI Equipment Manager is a comprehensive system for managing workout equipment, optimizing its usage, and integrating with the AI Workout Generator. This feature helps users organize their equipment inventory, create custom sets, manage workout zones, and receive intelligent recommendations for equipment utilization.

## Core Functionalities

### 1. Equipment Inventory Management
- **Equipment Types**
  - Machines
  - Free weights
  - Resistance bands
  - Other equipment
- **Equipment Properties**
  - Name and type
  - Weight range (for applicable items)
  - Quantity tracking
  - Custom descriptions and notes
- **Visual Management**
  - Grid/card-based interface
  - Categorized views
  - Search and filter capabilities

### 2. Goal-Based Equipment Recommendations
- **AI Analysis**
  - Equipment optimization for user goals
  - Gap analysis in current inventory
  - Suggestions for complementary equipment
- **Integration with Profile**
  - Fitness level consideration
  - Training preferences alignment
  - Health conditions and limitations

### 3. Equipment Sets & Layouts
- **Custom Sets**
  - Create and manage equipment groupings
  - Purpose-specific collections
  - Quick access to common combinations
- **Layout Management**
  - Visual space planning
  - Equipment arrangement suggestions
  - Saved configurations

### 4. Workout Zone Management
- **Zone Types**
  - Home gym
  - Commercial gym
  - Outdoor setup
- **Zone Properties**
  - Available equipment list
  - Space constraints
  - Environmental considerations

### 5. Multi-User Support
- **Profile Management**
  - Individual equipment preferences
  - Shared equipment access
  - Usage scheduling

## Enhanced Features

### 1. Goal Tracking & Optimization
- **Usage Analytics**
  - Equipment utilization patterns
  - Progress tracking
  - Optimization suggestions
- **AI Insights**
  - Equipment usage recommendations
  - Progressive overload guidance
  - Variety suggestions

### 2. Workout Generator Integration
- **Dynamic Adaptation**
  - Equipment-based workout customization
  - Alternative exercise suggestions
  - Real-time availability updates

### 3. Smart Equipment Suggestions
- **Purchase Recommendations**
  - Goal-aligned suggestions
  - Space optimization
  - Budget considerations
- **Prioritization**
  - Most impactful additions
  - Versatility ranking
  - Cost-benefit analysis

### 4. Maintenance Tracking
- **Equipment Health**
  - Usage monitoring
  - Maintenance schedules
  - Replacement notifications
- **Safety Checks**
  - Regular inspection reminders
  - Wear and tear tracking
  - Safety guidelines

## Technical Implementation

### Components
- \`EquipmentManager\`: Main orchestration component
- \`EquipmentListWidget\`: Equipment inventory display
- \`EquipmentSetWidget\`: Set management interface
- \`WorkoutZoneWidget\`: Zone configuration

### Services
- \`EquipmentService\`: Core equipment operations
  - CRUD operations for equipment
  - Set management
  - Zone configuration
  - Integration with workout generator

### Contexts
- \`EquipmentContext\`: Global state management
  - Equipment inventory
  - Sets and zones
  - Loading and error states

### Types
- \`Equipment\`: Core equipment properties
- \`EquipmentSet\`: Custom equipment groupings
- \`WorkoutZone\`: Zone configurations

## API Integration

### Endpoints
- \`/equipment/items\`: Equipment CRUD operations
- \`/equipment/sets\`: Set management
- \`/equipment/zones\`: Zone configuration
- \`/equipment/recommendations\`: AI suggestions

### Data Models
```typescript
interface Equipment {
    id: string;
    name: string;
    type: 'machine' | 'free weights' | 'bands' | 'other';
    weightRange?: string;
    quantity?: number;
    description?: string;
}

interface EquipmentSet {
    id: string;
    name: string;
    equipmentIds: string[];
    notes?: string;
}

interface WorkoutZone {
    id: string;
    name: string;
    equipmentIds: string[];
    environment: 'home' | 'gym' | 'outdoor';
}
```

## Development Workflow

### Phase 1: Core Implementation
1. Equipment inventory management
2. Basic CRUD operations
3. UI components and styling

### Phase 2: Enhanced Features
1. Equipment sets and zones
2. AI recommendations
3. Workout generator integration

### Phase 3: Advanced Features
1. Analytics and insights
2. Maintenance tracking
3. Multi-user support

## Best Practices

### State Management
- Use React Context for global state
- Implement proper error handling
- Maintain loading states

### API Integration
- RESTful endpoint design
- Proper error responses
- Rate limiting and caching

### UI/UX Guidelines
- Responsive design
- Accessibility compliance
- Performance optimization

## Getting Started

### Prerequisites
- Node.js and npm
- WordPress environment
- API credentials

### Installation
1. Install dependencies
2. Configure API endpoints
3. Set up development environment

### Configuration
1. API endpoint setup
2. WordPress integration
3. Environment variables

## Contributing
- Code style guidelines
- Testing requirements
- Documentation standards
- Pull request process

## License
[License details] 

### Styling Guidelines

#### Button Patterns
All primary action buttons (e.g., "Add Equipment", "Save Equipment") should follow these styling rules:
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