# Testing Documentation

## Overview

Our testing infrastructure combines PHPUnit with mocked WordPress functionality, enabling isolated testing while maintaining WordPress compatibility. This approach ensures reliable test execution without database dependencies.

### Testing Framework

#### Primary Framework: PHPUnit
- Using PHPUnit version specified in `composer.json`
- Tests run via `composer test` command
- Custom bootstrap file at `features/workout-generator/tests/bootstrap.php`

#### WordPress Integration
- Custom `WP_UnitTestCase` extending `PHPUnit\Framework\TestCase`
- Mocked WordPress functions in bootstrap.php
- No direct WordPress database dependency

### Test Architecture

#### Directory Structure
```
features/workout-generator/
├── tests/
│   ├── bootstrap.php          # Test initialization
│   ├── AIServiceTest.php      # AI Service tests
│   └── class-rate-limiter-test.php  # Rate limiter tests
├── api/
│   └── class-rate-limiter.php # Implementation
└── phpunit.xml               # PHPUnit configuration
```

### Successful Test Cases

#### Rate Limiter Tests
```php
class Rate_Limiter_Test extends TestCase {
    // Key test methods with descriptions
    
    public function test_tier_based_rate_limits() {
        // Validates tier-specific request limits:
        // - Foundation: 60/hour
        // - Performance: 120/hour
        // - Transformation: 180/hour
    }

    public function test_invalid_tier_fallback() {
        // Verifies Foundation tier fallback
    }

    public function test_tier_upgrade_handling() {
        // Validates tier upgrade scenarios
    }

    public function test_tier_rate_limit_headers() {
        // Verifies rate limit headers
    }
}
```

### Test Configuration

#### PHPUnit Configuration
```xml
<testsuites>
    <testsuite name="Workout Generator Tests">
        <file>features/workout-generator/tests/AIServiceTest.php</file>
        <file>features/workout-generator/tests/class-rate-limiter-test.php</file>
    </testsuite>
</testsuites>
```

### Running Tests

#### Available Commands
1. **Full Test Suite**
```bash
composer test
```

2. **Single Test Class**
```bash
composer test:single Rate_Limiter_Test
```

3. **Specific Test Method**
```bash
composer test:single Rate_Limiter_Test::test_tier_based_rate_limits
```

### Mock Implementation

#### WordPress Function Mocks
```php
// Key mocked functions in bootstrap.php
function get_transient($key) { return false; }
function set_transient($key, $value, $expiration) { return true; }
function wp_set_current_user($user_id) {
    global $current_user_id;
    $current_user_id = $user_id;
}
```

### Dependencies

#### Required Packages
```json
{
    "require-dev": {
        "mockery/mockery": "^1.6",
        "wp-phpunit/wp-phpunit": "^6.3",
        "yoast/phpunit-polyfills": "^1.1"
    }
}
```

### Key Success Factors

1. **Isolated Testing Environment**
   - No WordPress database dependency
   - Mocked WordPress functions
   - Custom test case class

2. **Comprehensive Rate Limiter Tests**
   - Tier-based limits verified
   - Upgrade scenarios covered
   - Header validation
   - Fallback behavior tested

3. **Clean Test Organization**
   - Namespaced test classes
   - Proper setUp and tearDown methods
   - Clear test method names

### Debugging Tests

#### Common Issues
1. **Missing Mocks**
   - Symptom: Undefined function errors
   - Solution: Add missing function to bootstrap.php

2. **Rate Limit Testing**
   - Symptom: Inconsistent results
   - Solution: Clear transients in tearDown

#### PHPUnit Configuration Issues
- Verify `phpunit.xml` location
- Check bootstrap file path
- Confirm test suite configuration

### Next Steps

1. **Coverage Expansion**
   - Add tests for `AIService` class
   - Implement integration tests
   - Add performance benchmarks

2. **Documentation**
   - Add inline documentation
   - Document test data generation

3. **CI Integration**
   - Add GitHub Actions workflow
   - Implement test reporting

### Contributing

#### Adding New Tests
1. Create test class in appropriate directory
2. Extend `TestCase` or `WP_UnitTestCase`
3. Add to `phpunit.xml` test suite
4. Document test purpose and coverage

#### Test Naming Conventions
- Classes: `*Test.php` or `class-*-test.php`
- Methods: `test_*` with descriptive names
- Follow WordPress coding standards

### Maintenance

#### Regular Tasks
1. Update mock implementations
2. Review test coverage
3. Verify CI integration
4. Update documentation

#### Version Control
- Tests committed with feature code
- Mock data in version control
- Configuration in version control 