# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2025-06-22

### Added
- Comprehensive testing dashboard for plugin validation (#13)
- System health checks including WordPress, PHP, and Gravity Forms compatibility
- Form configuration tests to validate embed settings
- API endpoint tests with response time measurement
- JavaScript SDK validation tests
- Security feature testing including rate limiting and domain restrictions
- Performance tests for database, memory, and API response times
- One-click test execution with real-time progress updates
- Export test results as JSON for support requests
- Detailed test results with pass/fail/warning indicators
- Actionable fix suggestions for failed tests
- Responsive design for mobile compatibility

### Improved
- Admin menu organization with new Testing submenu
- Plugin load time tracking for performance monitoring
- Enhanced error handling throughout the plugin

## [0.1.5] - 2025-06-22

### Fixed
- Critical permission error when accessing form settings pages (#12)
- Added proper capability declaration to Gravity Forms menu item
- Added capability checks for both gravityforms_edit_forms and manage_options
- Added validation to ensure Gravity Forms is loaded before accessing its functions
- Improved error handling with user-friendly messages

### Security
- Properly enforced WordPress capability system for form settings access

## [0.1.4] - 2024-06-22

### Added
- Comprehensive embed code section on form detail page
- React and Vue component examples for each form
- Current security settings display on detail page
- "Back to Overview" navigation button
- Warning message when embedding is not enabled

### Changed
- Simplified menu text from "JS Embed Analytics" to "JS Embed"
- Plugin action link text from "Analytics" to "JS Embed"
- All page titles simplified to "JavaScript Embed"

### Removed
- Non-functional Settings link from overview page

### Improved
- Better organized form detail page with embed instructions
- Enhanced styling for code examples
- More intuitive navigation flow

## [0.1.3] - 2024-06-22

### Fixed
- Critical error when clicking Settings link from analytics page
- Corrected form settings URL structure (gf_form_settings instead of gf_edit_forms)
- Added error handling for missing Gravity Forms plugin
- Added form validation to prevent critical errors

## [0.1.2] - 2024-06-22

### Added
- Plugin action links in the plugins page (Analytics, Documentation)
- Plugin row meta links (View Documentation, Support, GitHub)
- Settings links in the analytics overview page
- Informational notice on the analytics page explaining the plugin

### Fixed
- Version number consistency in build process

## [0.1.1] - 2024-06-22

### Added
- Security index.php files in all directories to prevent directory browsing

### Security
- Enhanced security compliance for WordPress.org submission

## [0.1.0] - 2024-06-21

### Added
- Initial release
- JavaScript-based form embedding without iframes
- REST API endpoints for form data and submission
- Domain whitelisting for security
- API key authentication (optional)
- Form analytics tracking
- Multiple embedding methods
- Theme support (Default, Minimal, Rounded, Material)
- Custom CSS support
- All standard Gravity Forms field types
- Conditional logic support
- Multi-page form support
- File upload handling
- Form validation
- CORS configuration
- Rate limiting
- Internationalization support
- Admin interface for configuration
- Analytics dashboard

### Security
- Input sanitization for all form fields
- Domain-based access control
- API key generation and validation
- Rate limiting to prevent abuse
- Secure file upload handling

### Known Issues
- Some advanced field types still in development
- Payment fields not yet fully supported
- Limited support for third-party Gravity Forms add-ons

[0.2.0]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.5...v0.2.0
[0.1.5]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.4...v0.1.5
[0.1.4]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/jezweb/js-gravity-forms-embed/releases/tag/v0.1.0