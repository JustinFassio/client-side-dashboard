# Testing Guide

## Table of Contents
- [Overview](#overview)
- [Test Environment Setup](#test-environment-setup)
- [Running Tests](#running-tests)
- [Test Structure](#test-structure)
- [Writing Tests](#writing-tests)
- [CI/CD Integration](#cicd-integration)
- [Code Coverage](#code-coverage)

## Overview

The Athlete Dashboard uses a comprehensive testing strategy that includes:
- PHP Unit Tests for backend functionality
- Jest Tests for frontend components and features
- Integration Tests for API endpoints
- End-to-End Tests for critical user flows

## Test Environment Setup

### PHP Testing Environment

1. Install dependencies:
```bash
composer install
```

2. Configure WordPress test environment:
```bash
bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

3. Verify PHPUnit installation:
```bash
vendor/bin/phpunit --version
```

### JavaScript Testing Environment

1. Install dependencies:
```bash
npm install
```

2. Verify Jest installation:
```bash
npm test -- --version
```

## Running Tests

### PHP Tests

Run all PHP tests:
```bash
vendor/bin/phpunit
```

Run specific test suite:
```bash
vendor/bin/phpunit --testsuite unit
```

Run tests with coverage:
```bash
vendor/bin/phpunit --coverage-html tests/reports/coverage
```

### JavaScript Tests

Run all JavaScript tests:
```bash
npm test
```

Run tests in watch mode:
```bash
npm run test:watch
```

Run with coverage:
```bash
npm run test:coverage
```

## Test Structure

### PHP Tests Directory Structure
```
tests/
├── php/
│   ├── rest-api/           # REST API tests
│   ├── services/           # Service tests
│   ├── features/           # Feature tests
│   └── helpers.php         # Test helpers
├── bootstrap.php           # Test bootstrap
└── TestCase.php           # Base test case
```

### JavaScript Tests Directory Structure
```
__tests__/
├── components/            # Component tests
├── features/             # Feature tests
├── services/             # Service tests
└── utils/                # Utility tests
```

## Writing Tests

### PHP Test Guidelines

1. Extend the base test case:
```php
use AthleteDashboard\Tests\TestCase;

class MyTest extends TestCase {
    public function setUp(): void {
        parent::setUp();
        // Your setup code
    }
}
```

2. Use provided helper methods:
```php
$this->createMockUser();
$this->mockWordPressFunctions();
$this->mockWordPressCache();
```

3. Follow naming conventions:
```php
public function test_should_do_something() {
    // Test code
}
```

### JavaScript Test Guidelines

1. Use React Testing Library:
```typescript
import { render, screen } from '@testing-library/react';
import { MyComponent } from './MyComponent';

describe('MyComponent', () => {
    it('should render correctly', () => {
        render(<MyComponent />);
        expect(screen.getByRole('button')).toBeInTheDocument();
    });
});
```

2. Mock API calls:
```typescript
jest.mock('@/services/api', () => ({
    fetchData: jest.fn().mockResolvedValue({ data: 'test' })
}));
```

3. Test user interactions:
```typescript
import userEvent from '@testing-library/user-event';

test('should handle click', async () => {
    const user = userEvent.setup();
    render(<MyComponent />);
    await user.click(screen.getByRole('button'));
    expect(screen.getByText('Clicked')).toBeInTheDocument();
});
```

## CI/CD Integration

Tests are automatically run in the CI/CD pipeline:

1. On Pull Requests:
   - All unit tests
   - Linting checks
   - Type checking
   - Coverage reports

2. On Merge to Main:
   - All tests
   - E2E tests
   - Performance tests
   - Security scans

## Code Coverage

### Coverage Requirements

- PHP Code: Minimum 80% coverage
- JavaScript Code: Minimum 75% coverage
- Critical paths: 100% coverage

### Generating Coverage Reports

PHP Coverage:
```bash
vendor/bin/phpunit --coverage-html tests/reports/coverage-php
```

JavaScript Coverage:
```bash
npm run test:coverage
```

### Coverage Exclusions

The following are excluded from coverage requirements:
- Test files
- Configuration files
- Build scripts
- External libraries

## Best Practices

1. **Test Independence**
   - Each test should be independent
   - Clean up after each test
   - Don't rely on test execution order

2. **Test Data**
   - Use factories for test data
   - Avoid hard-coded IDs
   - Clean up test data after use

3. **Mocking**
   - Mock external services
   - Use dependency injection
   - Keep mocks simple

4. **Assertions**
   - Test one thing per test
   - Use descriptive assertion messages
   - Check both positive and negative cases

## Common Patterns

### Testing REST API Endpoints

```php
public function test_endpoint() {
    $request = new WP_REST_Request('GET', '/athlete-dashboard/v1/endpoint');
    $response = $this->controller->get_items($request);
    $this->assertEquals(200, $response->get_status());
}
```

### Testing React Components

```typescript
describe('Component', () => {
    it('should handle loading state', () => {
        render(<Component isLoading={true} />);
        expect(screen.getByTestId('loading')).toBeInTheDocument();
    });

    it('should handle error state', () => {
        render(<Component error="Error message" />);
        expect(screen.getByText('Error message')).toBeInTheDocument();
    });
});
```

## Need Help?

- Check the test output for detailed error messages
- Review test logs in CI/CD pipeline
- Contact the development team for assistance
``` 