# Athlete Dashboard Testing Guide

## Overview
This document provides comprehensive guidance for running and maintaining tests for the Athlete Dashboard WordPress theme. Our testing infrastructure covers both backend (PHP/WordPress) and frontend (React/TypeScript) components.

## Test Infrastructure

### Backend Testing
- PHPUnit with WP_Mock for unit tests
- wp-phpunit for WordPress integration tests
- Custom performance testing framework

### Frontend Testing
- Jest for unit and integration tests
- React Testing Library for component testing
- Custom performance monitoring

## Prerequisites
- PHP 7.4 or higher
- Node.js 16 or higher
- MySQL 5.7 or higher
- WordPress development environment
- Composer
- npm or yarn

## Installation

### Backend Setup

#### 1. Install PHP Dependencies
```bash
# Install PHP dependencies
composer require --dev phpunit/phpunit
composer require --dev yoast/phpunit-polyfills
composer require --dev 10up/wp_mock
```

#### 2. Set Up Local Test Database

The test suite requires a dedicated MySQL database. You can create one using:

```bash
# Log into MySQL
mysql -u root -p

# Create test database
CREATE DATABASE wordpress_test;

# Create test user (if needed)
CREATE USER 'wp_test'@'localhost' IDENTIFIED BY 'wp_test_pass';
GRANT ALL PRIVILEGES ON wordpress_test.* TO 'wp_test'@'localhost';
FLUSH PRIVILEGES;
```

Configure your test environment:

1. Create `.env.testing`:
```bash
cp .env.example .env.testing
```

2. Update database credentials in `.env.testing`:
```ini
DB_NAME=wordpress_test
DB_USER=wp_test
DB_PASSWORD=wp_test_pass
DB_HOST=localhost
```

3. Set up WordPress test environment:
```bash
bin/install-wp-tests.sh wordpress_test wp_test wp_test_pass localhost latest
```

This script will:
- Download WordPress testing suite
- Configure the test environment
- Create necessary database tables

#### 3. Verify Setup

```bash
# Run a quick test to verify setup
composer test -- --filter=test_sample

# You should see output indicating successful test execution
```

### Frontend Setup
```bash
# Install Node dependencies
cd features/profile/assets
npm install
```

## Directory Structure
```
tests/
├── php/
│   ├── endpoints/           # REST API endpoint tests
│   ├── framework/          # Test framework and utilities
│   ├── performance/        # Performance test suites
│   └── bootstrap.php      # PHPUnit bootstrap file
├── reports/               # Test reports and coverage
└── README.md             # This documentation

features/
└── profile/
    ├── assets/
    │   └── tests/
    │       ├── components/  # React component tests
    │       ├── hooks/      # Custom hook tests
    │       └── performance/ # Frontend performance tests
    └── tests/
        └── performance/    # Backend performance tests
```

## Running Tests

### Backend Tests
```bash
# Run all PHP tests
composer test

# Run specific test suite
composer test -- --testsuite profile

# Run performance tests
composer test -- --testsuite performance

# Generate coverage report
composer test -- --coverage-html=reports/coverage
```

### Frontend Tests
```bash
cd features/profile/assets

# Run all frontend tests
npm test

# Run with coverage
npm run test:coverage

# Run performance tests
npm run test:perf
```

## Performance Testing

### Thresholds
```typescript
// Frontend thresholds (in milliseconds)
const THRESHOLDS = {
    API_RESPONSE: 300,
    CACHE_RESPONSE: 50,
    DB_QUERY: 100,
    RENDER_TIME: 100
};
```

### Running Performance Tests
```bash
# Backend performance suite
composer test -- --testsuite performance

# Frontend performance suite
npm run test:perf
```

### Performance Reports and Artifacts

#### Location
Performance test results are stored in:
```
tests/
└── reports/
    └── performance/
        ├── backend/
        │   ├── results.json       # Latest test results
        │   └── history/          # Historical results
        └── frontend/
            ├── results.json      # Latest test results
            └── history/         # Historical results
```

#### Understanding Results

1. **Backend Metrics**
```json
{
    "timestamp": "2025-01-13T12:00:00Z",
    "metrics": {
        "database": {
            "query_time_avg": 45,
            "query_count_avg": 3,
            "cache_hit_ratio": 0.95
        },
        "api": {
            "response_time_p95": 280,
            "response_time_avg": 150
        }
    },
    "thresholds": {
        "passed": true,
        "violations": []
    }
}
```

2. **Frontend Metrics**
```json
{
    "timestamp": "2025-01-13T12:00:00Z",
    "metrics": {
        "render": {
            "initial_load": 95,
            "form_update": 45,
            "unit_conversion": 8
        },
        "api": {
            "profile_fetch": 280,
            "profile_update": 180
        }
    }
}
```

#### Interpreting Results

1. **Response Times**
   - Green: Below threshold
   - Yellow: Within 20% of threshold
   - Red: Exceeds threshold

2. **Cache Performance**
   - Optimal cache hit ratio: > 0.90
   - Warning level: 0.70 - 0.90
   - Critical level: < 0.70

3. **Query Counts**
   - Optimal: ≤ 5 queries per operation
   - Warning: 6-10 queries
   - Critical: > 10 queries

### Historical Trends

Performance history is tracked in CI/CD:
1. Access the GitHub Actions artifacts
2. Download performance reports
3. View trend graphs in the CI dashboard

Example trend analysis:
```bash
# Generate performance trend report
composer test:perf-report

# Output shows 30-day trend
Last 30 Days:
- API Response Time: -15% (improved)
- Cache Hit Ratio: +5% (improved)
- Query Count: No change
```

### Debugging Performance Issues

1. **High Response Times**
   - Check database query execution plans
   - Verify cache effectiveness
   - Monitor external service calls

2. **Cache Misses**
   - Review cache key generation
   - Check cache expiration settings
   - Verify cache storage configuration

3. **Excessive Queries**
   - Use query monitoring
   - Check for N+1 query patterns
   - Review eager loading implementation

## Continuous Integration

### GitHub Actions Workflow
- Separate jobs for PHP and frontend tests
- Real MySQL database for integration tests
- Performance metrics collection
- Coverage reporting to Codecov

### Environment Variables
```yaml
# CI environment variables
MYSQL_ROOT_PASSWORD: root
MYSQL_DATABASE: wordpress_test
WP_VERSION: latest
NODE_VERSION: '16'
```

## Best Practices

### Writing Tests
1. **Isolation**: Each test should be independent
2. **Mocking**: Use WP_Mock for WordPress functions in unit tests
3. **Performance**: Include performance assertions in integration tests
4. **Coverage**: Maintain minimum 80% coverage for new code

### Common Patterns

#### Testing WordPress Functions
```php
public function test_wordpress_function() {
    \WP_Mock::userFunction('get_option', [
        'args' => ['my_option'],
        'times' => 1,
        'return' => 'value'
    ]);
}
```

#### Testing React Components
```typescript
it('renders profile form', () => {
    render(<ProfileForm />);
    expect(screen.getByRole('form')).toBeInTheDocument();
});
```

## Troubleshooting

### Common Issues
1. **Database Connection**: Check MySQL credentials and permissions
2. **WordPress Test Environment**: Verify wp-tests-config.php settings
3. **Node Modules**: Clear npm cache and node_modules if tests fail
4. **Performance Thresholds**: Adjust based on environment

### Debug Mode
```bash
# PHP debug mode
composer test -- --debug

# Jest debug mode
npm test -- --debug
```

## Legacy Support
The old `./bin/run-tests.sh` script is maintained for backward compatibility but will be deprecated in future versions. Please migrate to the new test commands. 