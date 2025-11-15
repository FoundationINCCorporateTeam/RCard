# Contributing to RCard

Thank you for your interest in contributing to the RCard platform! This document provides guidelines and instructions for contributing.

## Code of Conduct

- Be respectful and professional
- Follow security best practices
- Write clean, documented code
- Test your changes thoroughly

## Development Setup

1. **Fork and Clone**
   ```bash
   git clone https://github.com/YourUsername/RCard.git
   cd RCard
   ```

2. **Run Setup**
   ```bash
   ./setup.sh
   ```

3. **Start Development Server**
   ```bash
   php -S localhost:8000
   ```

## Coding Standards

### PHP Standards

- **PHP Version**: 8.0+
- **Style**: PSR-12 compliant
- **Documentation**: PHPDoc for all functions
- **Type Hints**: Use strict types where possible

#### Function Documentation Example
```php
/**
 * Create a new loan with interest calculation
 * 
 * @param int $user_id User identifier
 * @param int $card_id Card identifier
 * @param float $principal Loan amount
 * @param int $days Loan duration in days
 * @return string|false Loan ID on success, false on failure
 */
function r_loan_create(int $user_id, int $card_id, float $principal, int $days) {
    // Implementation
}
```

### Security Guidelines

1. **Input Validation**
   - ALWAYS sanitize user input
   - Use type-safe functions (r_sanitize_string, r_sanitize_int, etc.)
   - Validate file paths to prevent traversal attacks

2. **Authentication**
   - Check authentication for protected endpoints
   - Use r_require_auth() for protected pages
   - Regenerate session IDs after authentication

3. **Data Encryption**
   - Use r_encrypt() for sensitive data
   - Never store plain-text passwords
   - Use r_hash_password() for password storage

4. **Rate Limiting**
   - Implement rate limiting for sensitive operations
   - Use r_rate_limit_check() for API endpoints

### JavaScript Standards

- **Style**: Modern ES6+
- **Naming**: camelCase for variables and functions
- **Comments**: JSDoc for complex functions
- **Async**: Use async/await for API calls

#### JavaScript Example
```javascript
/**
 * Create a new loan via API
 * @param {number} cardId - Card identifier
 * @param {number} principal - Loan amount
 * @param {number} days - Loan duration
 * @returns {Promise<Object>} API response
 */
async function createLoan(cardId, principal, days) {
    return await apiPost('loans_create', {
        card_id: cardId,
        principal: principal,
        days: days
    });
}
```

## File Organization

### Directory Structure

```
/includes       - Backend modules (auth, cards, loans, etc.)
/dashboard      - User-facing pages
/org           - Sponsor/organization pages
/public        - Static assets (CSS, JS, images)
/jsondata      - Data storage (excluded from git)
```

### Naming Conventions

- **PHP files**: lowercase with underscores (e.g., `auth.php`)
- **Functions**: `r_` prefix for RCard functions (e.g., `r_user_create()`)
- **Classes**: PascalCase (if adding classes)
- **Variables**: snake_case for PHP, camelCase for JavaScript

## Adding New Features

### 1. API Endpoints

Add to `api.php`:

```php
case 'your_new_action':
    handle_your_new_action();
    break;

function handle_your_new_action(): void {
    $user_id = r_require_auth(); // If authentication required
    
    // Your logic here
    
    r_success(['data' => $result]);
}
```

### 2. Data Models

Add functions to appropriate `/includes` file:

```php
/**
 * Create new entity
 */
function r_entity_create(array $data) {
    // Implementation
}

/**
 * Load entity
 */
function r_entity_load(int $id) {
    // Implementation
}

/**
 * Update entity
 */
function r_entity_update(int $id, array $data): bool {
    // Implementation
}
```

### 3. Frontend Pages

Create in `/dashboard` or `/org`:

```php
<?php
require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../includes/security.php';

r_session_start();

if (!r_is_authenticated()) {
    header('Location: /index.php');
    exit;
}

// Your page logic
?>
<!DOCTYPE html>
<html>
<!-- Your HTML -->
</html>
```

## Testing

### Manual Testing Checklist

- [ ] Test with different user roles
- [ ] Test error cases (invalid input, missing data)
- [ ] Test security (authentication, authorization)
- [ ] Test in different browsers
- [ ] Test with seed data

### Security Testing

- [ ] Input validation works correctly
- [ ] Authentication required for protected endpoints
- [ ] Rate limiting prevents abuse
- [ ] Encryption/decryption works properly
- [ ] No SQL injection vulnerabilities (when migrating to SQL)
- [ ] No XSS vulnerabilities

## Pull Request Process

1. **Create a Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make Changes**
   - Write clean, documented code
   - Follow coding standards
   - Test thoroughly

3. **Commit**
   ```bash
   git add .
   git commit -m "feat: add your feature description"
   ```

   Commit message format:
   - `feat:` New feature
   - `fix:` Bug fix
   - `docs:` Documentation changes
   - `refactor:` Code refactoring
   - `security:` Security improvements
   - `test:` Test additions

4. **Push and Create PR**
   ```bash
   git push origin feature/your-feature-name
   ```

5. **PR Description**
   - Describe what changes you made
   - Explain why the changes are necessary
   - List any breaking changes
   - Include screenshots for UI changes

## Common Tasks

### Adding a New Card Type

1. Add to card catalog in `/includes/cards.php`:
   ```php
   'your_type' => [
       [
           'id' => 'your-card',
           'name' => 'Your Card Name',
           // ... other properties
       ]
   ]
   ```

2. Update UI in `/dashboard/cards.php` to display new type

### Adding a New API Endpoint

1. Add case to `api.php` switch statement
2. Create handler function
3. Add validation and sanitization
4. Return appropriate response

### Modifying Interest Calculation

1. Update `r_loan_preview_interest()` in `/includes/loans.php`
2. Update `r_loan_calculate_accrued_interest()`
3. Test with various scenarios
4. Update documentation

## Database Migration Guide

When migrating from JSON to SQL:

1. **Create Schema**
   ```sql
   CREATE TABLE users (
       id BIGINT PRIMARY KEY,
       username VARCHAR(255) UNIQUE,
       password_hash VARCHAR(255),
       -- etc
   );
   ```

2. **Update Data Access Functions**
   - Keep function signatures identical
   - Replace file operations with SQL queries
   - Maintain backward compatibility during transition

3. **Migration Script**
   - Read JSON files
   - Insert into SQL database
   - Verify data integrity

## Getting Help

- Check existing code for examples
- Read the README.md for platform overview
- Review inline documentation
- Ask questions in pull request comments

## License

By contributing, you agree that your contributions will be licensed under the same terms as the project.
