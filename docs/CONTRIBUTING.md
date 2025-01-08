# Contributing to Athlete Dashboard

## Table of Contents
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Code Standards](#code-standards)
- [Pull Request Process](#pull-request-process)
- [Local Development Setup](#local-development-setup)
- [Release Process](#release-process)

## Getting Started

1. **Fork the Repository**
   - Fork the repository to your GitHub account
   - Clone your fork locally

2. **Set Up Development Environment**
   ```bash
   # Install PHP dependencies
   composer install

   # Install JavaScript dependencies
   npm install

   # Set up WordPress test environment
   bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

3. **Configure Local WordPress**
   - Copy `.env.example` to `.env`
   - Update environment variables
   - Configure WordPress with debug mode:
     ```php
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     ```

## Development Workflow

1. **Create Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Development Process**
   - Write tests first (TDD approach)
   - Implement feature/fix
   - Ensure all tests pass
   - Update documentation

3. **Code Quality Checks**
   ```bash
   # PHP checks
   composer run lint
   composer run test

   # JavaScript checks
   npm run lint
   npm run test
   ```

## Code Standards

### PHP Standards
- Follow PSR-12 coding standards
- Use type hints and return types
- Document classes and methods with PHPDoc
- Keep methods focused and small

Example:
```php
/**
 * Updates user profile data.
 *
 * @param int   $user_id User ID
 * @param array $data    Profile data
 * @return bool|WP_Error True on success, WP_Error on failure
 */
public function update_profile(int $user_id, array $data): bool|WP_Error {
    // Implementation
}
```

### TypeScript Standards
- Use TypeScript for all new JavaScript code
- Define interfaces for data structures
- Use functional components for React
- Document complex functions

Example:
```typescript
interface ProfileData {
  firstName: string;
  lastName: string;
  email: string;
}

const updateProfile = async (userId: number, data: ProfileData): Promise<void> => {
  // Implementation
};
```

### CSS/SCSS Standards
- Use BEM naming convention
- Keep specificity low
- Use variables for common values
- Mobile-first approach

Example:
```scss
.profile {
  &__header {
    // Styles
  }

  &__content {
    // Styles
  }
}
```

## Pull Request Process

1. **Before Submitting**
   - Update documentation
   - Add/update tests
   - Run all checks locally
   - Rebase on main branch

2. **PR Requirements**
   - Clear description of changes
   - Link to related issues
   - Screenshots for UI changes
   - Test coverage report
   - Documentation updates

3. **Review Process**
   - Two approvals required
   - All checks must pass
   - No merge conflicts
   - Documentation reviewed

4. **After Merge**
   - Delete feature branch
   - Update changelog
   - Update documentation

## Local Development Setup

1. **Prerequisites**
   - PHP 8.0+
   - Node.js 16+
   - MySQL 5.7+
   - WordPress 6.0+

2. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE athlete_dashboard_dev;
   ```

3. **WordPress Configuration**
   ```bash
   # Copy configuration
   cp wp-config-sample.php wp-config.php
   
   # Update database credentials
   define('DB_NAME', 'athlete_dashboard_dev');
   define('DB_USER', 'your_user');
   define('DB_PASSWORD', 'your_password');
   ```

4. **Theme Setup**
   ```bash
   # Link theme
   cd wp-content/themes
   ln -s /path/to/your/repo athlete-dashboard-child
   ```

5. **Build Assets**
   ```bash
   # Development build with watch
   npm run dev

   # Production build
   npm run build
   ```

## Release Process

1. **Preparation**
   - Update version numbers
   - Update changelog
   - Run full test suite
   - Build production assets

2. **Testing**
   - Test on staging environment
   - Perform smoke tests
   - Check backward compatibility
   - Validate documentation

3. **Release**
   - Create release branch
   - Tag version
   - Generate release notes
   - Deploy to production

4. **Post-Release**
   - Monitor error logs
   - Update documentation
   - Notify users
   - Clean up branches

## Need Help?

- Check existing issues
- Join our Slack channel
- Review documentation
- Contact the core team

## Code of Conduct

Please note that this project is released with a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms. 