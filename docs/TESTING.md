# Testing Guide

## Table of Contents
- [Overview](#overview)
- [Test Environment Setup](#test-environment-setup)
- [Running Tests](#running-tests)
- [Test Structure](#test-structure)
- [Writing Tests](#writing-tests)
- [CI/CD Integration](#cicd-integration)
- [Code Coverage](#code-coverage)
- [Feature-Specific Testing](#feature-specific-testing)

## Overview

The Athlete Dashboard uses a comprehensive testing strategy that includes:
- PHP Unit Tests for backend functionality
- Jest Tests for frontend components and features
- Integration Tests for API endpoints
- End-to-End Tests for critical user flows

Each feature maintains its own test documentation in `features/[feature-name]/tests/README.md`, providing detailed information about:
- Feature-specific test cases
- Mock implementations
- Test data requirements
- Common debugging scenarios

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

Run tests for a specific feature:
```bash
vendor/bin/phpunit --testsuite features/[feature-name]
```

### Feature-Specific Testing

Each feature in the Athlete Dashboard maintains its own test suite and documentation. For example, the Workout Generator feature's tests are documented in `features/workout-generator/tests/README.md`.

Key aspects of feature-specific testing:

1. **Isolated Testing Environment**
   - Mock WordPress functions
   - Custom test case classes
   - Feature-specific test data

2. **Test Organization**
   - Unit tests for individual components
   - Integration tests for feature interactions
   - End-to-end tests for user flows

3. **Mock Implementations**
   - WordPress function mocks
   - Service mocks
   - API response mocks

4. **Common Debugging Scenarios**
   - Missing mock functions
   - Rate limit testing issues
   - Transient data handling

For detailed information about testing a specific feature, refer to its test documentation in the feature's tests directory.

## Test Structure

Tests are organized following the Feature-First architecture:

```
features/
├── [feature-name]/
│   ├── tests/
│   │   ├── README.md           # Feature test documentation
│   │   ├── bootstrap.php       # Test initialization
│   │   ├── *Test.php          # Test classes
│   │   └── test-data/         # Test fixtures
│   └── ...
```

## Writing Tests

### Best Practices

1. **Isolation**
   - Tests should be independent
   - Use fresh test data for each test
   - Clean up after tests

2. **Naming**
   - Clear, descriptive test names
   - Follow `test_[what]_[expected]` pattern
   - Group related tests in classes

3. **Assertions**
   - Use specific assertions
   - Test both success and failure cases
   - Verify side effects

### Example Test

```php
class Feature_Test extends TestCase {
    public function setUp(): void {
        parent::setUp();
        // Feature-specific setup
    }

    public function test_feature_behavior_expected_result() {
        // Arrange
        $input = 'test data';

        // Act
        $result = do_something($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

## CI/CD Integration

Tests are automatically run in CI/CD pipelines:

1. **Pull Requests**
   - All tests must pass
   - Coverage requirements met
   - No new warnings

2. **Deployment**
   - Full test suite runs
   - Performance benchmarks
   - Security checks

## Code Coverage

Coverage reports are generated for:
- PHP tests using PHPUnit
- JavaScript tests using Jest
- End-to-end tests using Cypress

Minimum coverage requirements:
- Lines: 80%
- Functions: 90%
- Branches: 75%

## Contributing

When adding or modifying tests:

1. Follow the test organization structure
2. Update relevant documentation
3. Maintain coverage requirements
4. Add debug information for common issues
``` 