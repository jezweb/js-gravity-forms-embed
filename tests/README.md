# Gravity Forms JS Embed - Test Suite

This directory contains comprehensive testing tools that can validate the plugin functionality without requiring a WordPress installation.

## 🧪 Available Tests

### 1. **Standalone Test Suite** (`standalone-test.html`)
A comprehensive browser-based test suite that validates all aspects of the SDK.

**Features:**
- SDK loading and initialization tests
- API response simulation
- Field type rendering validation
- Security feature testing
- Event system verification
- Performance benchmarks
- Form validation testing

**Usage:**
```bash
# Open in browser
open tests/standalone-test.html
# or
xdg-open tests/standalone-test.html  # Linux
```

### 2. **Mock API Server** (`mock-api-server.html`)
A simulated API server that mimics WordPress REST API responses.

**Features:**
- Live request logging
- Statistics tracking
- Error simulation
- Latency simulation
- Multiple endpoint support
- Real form rendering

**Usage:**
```bash
# Open in browser to start the mock server
open tests/mock-api-server.html
```

### 3. **Unit Tests** (`sdk-unit-tests.js`)
JavaScript unit tests that can be run in browser or Node.js.

**Features:**
- SDK structure validation
- Field rendering tests
- Form building tests
- Validation tests
- Utility function tests
- Security feature tests

**Usage:**
```javascript
// In browser console
GFEmbedTests.runAll();

// Or run individual tests
GFEmbedTests.testFieldRendering();
GFEmbedTests.testValidation();
```

### 4. **PHP Syntax Validator** (`validate-php.php`)
Validates all PHP files without requiring WordPress.

**Features:**
- Syntax checking
- Security audit
- WordPress coding standards
- File structure validation

**Usage:**
```bash
php tests/validate-php.php
```

## 🚀 Quick Start Testing

1. **Basic Functionality Test:**
   ```bash
   # Open the standalone test suite
   open tests/standalone-test.html
   # Click "Run SDK Tests" button
   ```

2. **API Integration Test:**
   ```bash
   # Open the mock API server
   open tests/mock-api-server.html
   # The embedded form should load automatically
   ```

3. **PHP Validation:**
   ```bash
   php tests/validate-php.php
   # Should show all files passing validation
   ```

## ✅ Test Coverage

### JavaScript SDK
- ✅ Object structure and methods
- ✅ Field rendering (all types)
- ✅ Form building and HTML generation
- ✅ Event system
- ✅ Validation logic
- ✅ API communication
- ✅ Error handling
- ✅ Security features

### PHP Code
- ✅ Syntax validation
- ✅ Security checks
- ✅ WordPress compatibility
- ✅ File structure
- ✅ Coding standards

### Integration
- ✅ API endpoint simulation
- ✅ Form submission flow
- ✅ Asset loading
- ✅ Cross-domain requests
- ✅ Security token validation

## 📊 Expected Results

### Standalone Tests
All tests should show green checkmarks (✓) with "PASS" status.

### Mock API Server
- Forms should load within 1-2 seconds
- Submissions should show success confirmation
- Request log should show 200 OK responses

### PHP Validator
```
✅ All PHP files passed validation!
Files checked: X
Errors: 0
Warnings: 0
```

## 🐛 Troubleshooting

### Test Suite Not Loading
1. Ensure you're opening the HTML files in a modern browser
2. Check browser console for errors
3. Verify the SDK file path is correct

### Mock API Not Working
1. Check that JavaScript is enabled
2. Look for CORS errors in console
3. Ensure fetch API is supported by your browser

### PHP Validation Errors
1. Ensure PHP CLI is installed: `php --version`
2. Run from the correct directory
3. Check file permissions

## 🔧 Advanced Testing

### Custom Test Forms
Edit `mockForms` object in `mock-api-server.html` to test different form configurations:

```javascript
const mockForms = {
    2: {
        id: 2,
        title: "Custom Test Form",
        fields: [
            // Add your custom fields here
        ]
    }
};
```

### Performance Testing
Use the performance test in standalone-test.html to benchmark:
- Field rendering speed
- Event handling efficiency
- Validation performance
- Memory usage

### Security Testing
The security tests validate:
- Honeypot field implementation
- Rate limiting logic
- Spam pattern detection
- Bot behavior analysis
- CSRF token generation

## 📝 Notes

- These tests are designed to work without WordPress
- They simulate the WordPress REST API
- All tests run in the browser (except PHP validation)
- No external dependencies required
- Tests cover both happy paths and error conditions

## 🚦 CI/CD Integration

These tests can be integrated into your CI/CD pipeline:

```yaml
# Example GitHub Actions
- name: Validate PHP Syntax
  run: php tests/validate-php.php

- name: Run JavaScript Tests
  run: |
    npm install -g puppeteer
    node tests/run-browser-tests.js
```