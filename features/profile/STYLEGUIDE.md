# Profile Feature Style Guide

## Table of Contents
1. [Design System Integration](#design-system-integration)
2. [Color Palette](#color-palette)
3. [Typography](#typography)
4. [Layout & Spacing](#layout--spacing)
5. [Components](#components)
6. [CSS Architecture](#css-architecture)
7. [Workflow](#workflow)
8. [Areas for Improvement](#areas-for-improvement)

## Design System Integration

The profile feature uses the dashboard's global design system, located in `dashboard/styles/variables.css`. All components should use these CSS variables for consistency.

### Key Variables
```css
/* Colors */
--background-darker: #1a1a1a;
--input-background: #2a2a2a;
--text-light: #ffffff;
--text-dim: #a0a0a0;
--border-color: #333333;
--primary-color: #4caf50;

/* Spacing */
--spacing-xs: 0.25rem;
--spacing-sm: 0.5rem;
--spacing-md: 1rem;
--spacing-lg: 1.5rem;
--spacing-xl: 2rem;

/* Border Radius */
--border-radius-sm: 4px;
--border-radius-md: 8px;
--border-radius-lg: 12px;
```

## Color Palette

### Section Backgrounds
- Main background: `var(--background-darker)`
- Input fields: `var(--input-background)`
- Active/Hover states: `var(--background-hover)`

### Text Colors
- Primary text: `var(--text-light)`
- Secondary text: `var(--text-dim)`
- Links/Actions: `var(--primary-color)`

### Borders & Dividers
- Default border: `var(--border-color)`
- Focus state: `var(--primary-color)`
- Error state: `var(--error-border)`

## Typography

### Font Sizes
```css
--font-size-xs: 0.75rem;
--font-size-sm: 0.875rem;
--font-size-base: 1rem;
--font-size-lg: 1.125rem;
--font-size-xl: 1.25rem;
```

### Font Weights
```css
--font-weight-normal: 400;
--font-weight-medium: 500;
--font-weight-bold: 700;
```

## Layout & Spacing

### Section Layout
- Max width: 1200px
- Grid-based layout for sections
- Responsive breakpoints at 768px

### Form Groups
```css
.form-group {
  margin-bottom: var(--spacing-md);
  display: grid;
  grid-template-columns: 1fr auto;
  gap: var(--spacing-sm);
}
```

### Responsive Design
```css
@media (max-width: 768px) {
  .profile-sections {
    grid-template-columns: 1fr;
  }
}
```

## Components

### Section Component
All sections should use the `Section` component for consistency:
```tsx
<Section title="Section Title">
  <div className="form-section__grid">
    {/* Form fields */}
  </div>
</Section>
```

### Form Fields
Standard form field structure:
```tsx
<div className={styles['form-group']}>
  <label htmlFor="fieldId">Field Label</label>
  <div className={styles['input-wrapper']}>
    <input
      id="fieldId"
      type="text"
      value={value}
      onChange={handleChange}
    />
  </div>
</div>
```

## CSS Architecture

### File Structure
```
features/profile/
├── assets/
│   └── styles/
│       ├── base/
│       │   ├── forms.css
│       │   └── layout.css
│       └── components/
│           ├── ProfileForm.css
│           └── CoreSection.css
├── components/
│   └── [component]/
│       └── [Component].module.css
```

### CSS Modules
- Use CSS Modules for component-specific styles
- Follow BEM-like naming within modules
- Keep selectors flat and specific

## Workflow

### Adding New Styles
1. Check if the style exists in the design system
2. Use existing CSS variables
3. Add component-specific styles in a CSS module
4. Update responsive breakpoints as needed

### Updating Existing Styles
1. Review the component's CSS module
2. Check for design system variable usage
3. Test changes across all breakpoints
4. Verify consistency with other sections

## Areas for Improvement

### Code Cleanup
1. **Redundant Files**
   - `features/profile/types.ts` duplicates types from `/types` directory
   - Multiple `physical.ts` files need consolidation

2. **Legacy Styles**
   - `ProfileForm.css` contains outdated variables
   - Some inline styles remain in components

### Style Consolidation
1. **Variable Standardization**
   - Consolidate color variables
   - Standardize spacing values
   - Create consistent shadow system

2. **Component Refinement**
   - Extract common form styles to shared components
   - Create reusable input wrappers
   - Standardize button styles

### Future Improvements
1. **Design System**
   - Create a comprehensive color system
   - Implement dark/light theme toggle
   - Add animation/transition system

2. **Component Library**
   - Build reusable form components
   - Create shared layout components
   - Document component usage

3. **Documentation**
   - Add visual examples
   - Create interactive component playground
   - Document responsive behavior

## Best Practices

### CSS
1. Use CSS Modules for component styles
2. Follow BEM naming convention
3. Keep selectors specific and flat
4. Use design system variables
5. Test responsive behavior

### Components
1. Use the `Section` component wrapper
2. Follow consistent form structure
3. Implement proper error handling
4. Add loading states
5. Include aria labels

### Performance
1. Minimize CSS bundle size
2. Use CSS Grid/Flexbox for layouts
3. Implement lazy loading where appropriate
4. Optimize images and assets

## Contributing

### Adding New Sections
1. Create a new directory in `components/`
2. Use CSS Modules for styling
3. Follow existing section patterns
4. Update documentation

### Style Updates
1. Create a feature branch
2. Update relevant CSS modules
3. Test across breakpoints
4. Update documentation
5. Submit pull request

## Resources
- [Design System Variables](dashboard/styles/variables.css)
- [Component Library](features/profile/components)
- [Layout Examples](features/profile/components/layout) 

### Toggle Button Pattern
```css
/* Toggle Button Container */
.toggle-buttons {
    display: flex;
    gap: 1px;
    background: var(--border-color);
    padding: 1px;
    border-radius: var(--border-radius-sm);
    width: fit-content;
}

/* Individual Toggle Button */
.toggle-button {
    padding: var(--spacing-sm) var(--spacing-lg);
    border: none;
    background: var(--input-background);
    color: var(--text-dim);
    cursor: pointer;
    transition: background-color var(--transition-fast);
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-medium);
}

/* Toggle Button States */
.toggle-button.active {
    background: var(--primary-color);
    color: var(--background-darker);
    font-weight: var(--font-weight-bold);
}

.toggle-button:hover:not(.active) {
    background: var(--primary-hover);
    color: var(--background-darker);
    transform: translateY(-1px);
}

.toggle-button:focus {
    outline: none;
    box-shadow: 0 0 0 2px var(--primary-color);
}

.toggle-button:disabled {
    background-color: var(--text-dim);
    cursor: not-allowed;
    opacity: 0.7;
}

/* Responsive Design */
@media (max-width: 768px) {
    .toggle-buttons {
        width: 100%;
    }
    
    .toggle-button {
        flex: 1;
        text-align: center;
    }
}
```

Usage example:
```tsx
<div className="toggle-buttons" role="radiogroup" aria-label="Options">
    <button
        type="button"
        role="radio"
        aria-checked={isActive}
        className={`toggle-button ${isActive ? 'active' : ''}`}
        onClick={handleClick}
        disabled={isDisabled}
    >
        Option 1
    </button>
    <button
        type="button"
        role="radio"
        aria-checked={!isActive}
        className={`toggle-button ${!isActive ? 'active' : ''}`}
        onClick={handleClick}
        disabled={isDisabled}
    >
        Option 2
    </button>
</div>
```

Key features:
1. Uses design system variables for consistent styling
2. Includes all interactive states (hover, focus, active, disabled)
3. Maintains accessibility with proper ARIA roles
4. Responsive design with mobile optimization
5. Smooth transitions for better UX

### CSS Architecture

The Profile feature follows a structured CSS organization:

```
features/profile/
├── assets/
│   └── styles/
│       ├── index.css          # Main entry point for all styles
│       ├── base/
│       │   ├── forms.css      # Base form styles
│       │   └── layout.css     # Base layout styles
│       └── components/
│           ├── ProfileForm.css
│           ├── CoreSection.css
│           └── physical.css    # Physical section styles
```

#### Style Import Pattern
```css
/* index.css */
@import '../../../../dashboard/styles/variables.css';

/* Base Styles */
@import './base/forms.css';
@import './base/layout.css';

/* Components */
@import './components/ProfileForm.css';
@import './components/CoreSection.css';
@import './components/physical.css';
```

#### Component Style Pattern
```css
/* component.css */
@import '../../../../../dashboard/styles/variables.css';

/* Component-specific styles */
.component-name {
    /* Use design system variables */
    background: var(--background-darker);
    padding: var(--spacing-lg);
}
```

#### Best Practices
1. Always import design system variables at the top of each CSS file
2. Use component-specific CSS files for better organization
3. Follow consistent naming patterns for components and their styles
4. Maintain responsive design patterns
5. Use design system variables for colors, spacing, and typography

### Button Patterns

The Profile feature defines several button patterns for consistent UI:

#### 1. Toggle Buttons
Used for binary or multiple-choice selections where options are mutually exclusive.

#### 2. Save Changes Button
Primary action button used for form submissions and saving data.

```css
.save-button {
    padding: var(--spacing-md) var(--spacing-xl);
    background: var(--primary-color);
    color: var(--text-light);
    border: none;
    border-radius: var(--border-radius-md);
    font-weight: var(--font-weight-bold);
    font-size: var(--font-size-base);
    cursor: pointer;
    transition: all var(--transition-fast);
    width: fit-content;
    min-width: 120px;
    text-align: center;
}

.save-button:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.save-button:focus {
    outline: none;
    box-shadow: 0 0 0 2px var(--primary-color), 0 0 0 4px rgba(var(--primary-rgb), 0.3);
}

.save-button:disabled {
    background-color: var(--text-dim);
    cursor: not-allowed;
    opacity: 0.7;
    transform: none;
    box-shadow: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .save-button {
        width: 100%;
        padding: var(--spacing-lg) var(--spacing-xl);
    }
}
```

Usage example:
```tsx
<button
    type="submit"
    className="save-button"
    disabled={isDisabled || isLoading}
    onClick={handleSave}
>
    {isLoading ? 'Saving...' : 'Save Changes'}
</button>
```

Key features:
1. Clear visual hierarchy with bold text and prominent styling
2. Consistent padding and sizing across devices
3. Interactive states for hover, focus, and disabled
4. Mobile-optimized with full width and larger touch target
5. Loading state support

### Implementation Guidelines

1. **Button Selection**
   - Use toggle buttons for binary choices or mutually exclusive options
   - Use action buttons for primary actions (save, submit)
   - Use secondary buttons for optional actions (cancel, back)

2. **Accessibility**
   - Include proper ARIA roles and states
   - Ensure keyboard navigation works
   - Maintain sufficient color contrast

3. **Responsive Design**
   - Buttons should be touch-friendly on mobile (min 44px height)
   - Toggle groups should stack vertically on mobile
   - Maintain consistent spacing across breakpoints

4. **State Management**
   - Show clear active/selected states
   - Include hover and focus states
   - Handle disabled states appropriately

5. **Performance**
   - Use CSS transitions for smooth interactions
   - Avoid complex animations that might impact performance
   - Optimize for minimal CSS specificity 