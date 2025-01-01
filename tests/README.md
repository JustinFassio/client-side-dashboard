# Athlete Dashboard Testing Guide

## Overview
This document provides comprehensive guidance for running and maintaining tests for the Athlete Dashboard WordPress theme.

## Prerequisites
- PHP 7.4 or higher
- WordPress development environment
- PHPUnit 9.0 or higher
- WP-CLI installed globally

## Installation

1. Install testing dependencies:
```bash
composer require --dev phpunit/phpunit
composer require --dev yoast/phpunit-polyfills
```

2. Set up WordPress test environment:
```bash
bin/install-wp-tests.sh wordpress_test root root localhost latest
```

## Directory Structure
```
tests/
├── php/
│   ├── endpoints/           # REST API endpoint tests
│   ├── framework/          # Test framework and utilities
│   └── bootstrap.php      # PHPUnit bootstrap file
├── reports/               # Test reports and coverage
└── README.md             # This documentation
```

## Running Tests

### Basic Usage
```bash
# Run all tests
./bin/run-tests.sh

# Run specific test suite
./bin/run-tests.sh -f ProfileEndpointTest

# Run tests with database reset
./bin/run-tests.sh -r
```

### Command Line Options
- `-e, --environment`: Set test environment (development, staging, production)
- `-p, --parallel`: Enable parallel test execution
- `-d, --debug`: Enable debug mode
- `-r, --reset-db`: Reset test database before running tests
- `-f, --filter`: Filter tests by name pattern

### Environment Configuration
Create environment-specific configuration files:
```bash
cp .env.example .env.development
cp .env.example .env.staging
cp .env.example .env.production
```

## Writing Tests

### Endpoint Tests
```php
class My_Endpoint_Test extends WP_Test_REST_Controller_Testcase {
    public function test_endpoint() {
        $request = new WP_REST_Request('GET', '/my-endpoint');
        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
    }
}
```

### Best Practices
1. **Isolation**: Each test should be independent and clean up after itself
2. **Naming**: Use descriptive test names that explain the scenario
3. **Assertions**: Make specific assertions about expected outcomes
4. **Data**: Use data providers for testing multiple scenarios
5. **Setup**: Use `setUp()` and `tearDown()` methods appropriately

### Common Patterns

#### Testing Authentication
```php
public function test_endpoint_requires_authentication() {
    wp_set_current_user(0);
    $request = new WP_REST_Request('GET', '/protected-endpoint');
    $response = $this->server->dispatch($request);
    $this->assertEquals(401, $response->get_status());
}
```

#### Testing Data Validation
```php
public function test_invalid_data_returns_error() {
    $request = new WP_REST_Request('POST', '/my-endpoint');
    $request->set_body_params(['invalid' => 'data']);
    $response = $this->server->dispatch($request);
    $this->assertEquals(400, $response->get_status());
}
```

## Debugging

### Debug Mode
Enable debug mode to get detailed output:
```bash
./bin/run-tests.sh -d
```

### Common Issues
1. **Database Connection**: Ensure test database credentials are correct
2. **File Permissions**: Check permissions on test directories
3. **Memory Limits**: Increase PHP memory limit if needed

### Logging
Test logs are stored in `tests/reports/`:
- `coverage/`: HTML coverage reports
- `junit.xml`: JUnit format test results
- `testdox.html`: Human-readable test documentation

## Integration Testing

### Setting Up Test Data
```php
public static function wpSetUpBeforeClass($factory) {
    self::$test_user_id = $factory->user->create([
        'role' => 'subscriber'
    ]);
    update_user_meta(self::$test_user_id, 'test_key', 'test_value');
}
```

### Testing WordPress Hooks
```php
public function test_action_triggered() {
    $triggered = false;
    add_action('my_action', function() use (&$triggered) {
        $triggered = true;
    });
    
    do_action('my_action');
    $this->assertTrue($triggered);
}
```

## Performance Testing

### Load Testing
```php
public function test_endpoint_performance() {
    $start = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $request = new WP_REST_Request('GET', '/my-endpoint');
        $this->server->dispatch($request);
    }
    
    $time = microtime(true) - $start;
    $this->assertLessThan(5.0, $time);
}
```

## Continuous Integration
Tests are automatically run on:
- Pull request creation
- Push to main branch
- Nightly builds

### CI Configuration
See `.github/workflows/tests.yml` for CI setup details.

## Contributing
1. Write tests for new features
2. Update tests for modified functionality
3. Ensure all tests pass before submitting PR
4. Include test coverage reports with PRs 