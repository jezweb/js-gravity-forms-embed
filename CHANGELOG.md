# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [0.5.0] - 2024-12-24

### Added

#### Visual Theme Customizer System
- **Complete Theme Management Interface** - New admin page for visual theme customization
- **50+ CSS Variables** - Comprehensive theming system with colors, typography, spacing, and design controls
- **Live Preview System** - Real-time preview of theme changes in the admin interface
- **Predefined Themes** - 10 built-in themes (minimal, rounded, material, dark, bootstrap, tailwind, glass, flat, corporate)
- **Custom Theme Creation** - Build and save unlimited custom themes
- **Theme Library Management** - Organize themes with categories, search, and filtering
- **Theme Import/Export** - Batch export/import themes as JSON or ZIP files
- **Theme Sharing** - Generate temporary sharing URLs for themes
- **Theme Validation** - Comprehensive validation with security checks and accessibility warnings
- **Theme Versioning** - Track theme changes and duplicate themes for variations

#### Performance Optimization
- **Lazy Loading System** - Forms only load when visible in viewport using Intersection Observer API
- **Multiple Placeholder Types** - Choose from minimal, skeleton, spinner, button, or custom HTML placeholders
- **Configurable Loading Thresholds** - Control when forms load (0.0 = immediate, 1.0 = fully visible)
- **Critical CSS** - Prevent layout shift with inline critical styles
- **Performance Caching** - Object caching for themes and form data
- **CSS Minification** - Automatic CSS compression for production

#### Enhanced Security & Features
- **Rate Limiting System** - Configurable request throttling with database tracking and exponential backoff
- **JavaScript Event System** - Global event bus for form lifecycle management and extensibility  
- **CSRF Protection** - Session-based token validation for form submissions
- **Multi-Page Forms Support** - Progress tracking, auto-save, and navigation between form pages
- **Conditional Logic Engine** - Dynamic field visibility and requirements based on user input
- **Comprehensive Hooks System** - 15+ new action and filter hooks for extensibility
- **Enhanced Domain Validation** - Improved CORS and domain allowlist management
- **Security Event Logging** - Detailed logging of security violations and events
- **Multi-level Security** - Low, medium, and high security modes
- **Bot Detection** - Advanced automated bot detection and blocking

#### Developer Experience
- **Hooks Documentation** - Comprehensive documentation of all available hooks and filters
- **API Extensions** - Enhanced REST API with proper filtering and extensibility
- **Theme Development Tools** - Tools for creating and debugging custom themes
- **Performance Metrics** - Built-in performance monitoring and reporting
- **Security Scanning** - Automated security scans for form submissions

#### Admin Interface Improvements
- **Enhanced Form Settings** - Expanded settings panel with organized sections including performance settings
- **Visual Controls** - Color pickers, sliders, and intuitive form controls
- **Help System** - Built-in help and documentation within the admin interface
- **Real-time Feedback** - Immediate validation and feedback for settings
- **Improved UX** - Better organization, tooltips, and user guidance

### Changed
- **Converted to CSS Variables** - Migrated from hardcoded CSS to flexible CSS custom properties
- **Improved API Structure** - More consistent and extensible API endpoints
- **Enhanced Error Handling** - Better error messages and recovery mechanisms
- **Optimized Database Queries** - Improved performance for analytics and form data
- **Updated Admin Styling** - Modern, responsive admin interface design
- Updated README.md to accurately reflect implemented vs promised features
- Enhanced main plugin class to load new feature modules
- Improved JavaScript SDK with event system integration

### Fixed
- **Hook Implementation** - Fixed missing hooks that were documented but not implemented
- **Form Validation** - Improved client-side and server-side validation consistency
- **Performance Issues** - Resolved bottlenecks in form loading and rendering
- **Security Vulnerabilities** - Enhanced protection against common attack vectors
- **Browser Compatibility** - Improved compatibility with older browsers and mobile devices
- Removed overpromised features from documentation (honeypot fields, bot detection, etc.)
- Corrected API endpoint documentation with proper WordPress REST API paths
- Fixed JavaScript SDK examples to show actual implemented functionality

### Technical Details
- **New Classes Added:**
  - `GF_JS_Embed_Theme_Manager` - Core theme management
  - `GF_JS_Embed_CSS_Variables` - CSS variable definitions and validation
  - `GF_JS_Embed_Theme_Customizer_Admin` - Admin interface for theme customization
  - `GF_JS_Embed_Lazy_Loading` - Lazy loading implementation
  - `GF_JS_Embed_Rate_Limiter` - Request rate limiting
  - `GF_JS_Embed_Performance` - Performance optimization utilities

- **Hooks Added:**
  - `gf_js_embed_theme_variables` - Filter CSS variables before application
  - `gf_js_embed_predefined_themes` - Filter predefined theme list
  - `gf_js_embed_theme_applied` - Action when theme is applied to form
  - `gf_js_embed_security_violation` - Action for security violations
  - `gf_js_embed_api_response` - Filter API responses
  - And 10+ additional hooks for extensibility

## [0.4.0] - 2025-06-23

### Added
- 6 new professional themes: Dark Mode, Bootstrap-style, Tailwind-style, Glass/Glassmorphism, Flat Design, Corporate
- Theme selection support via `data-gf-theme` attribute in embed code
- Theme demo page at `/examples/theme-demo.html` for testing all themes
- Automated release process with `release.sh` script
- Comprehensive testing dashboard for plugin validation
- Developer hooks documentation
- Troubleshooting guide

### Changed
- Enhanced README with comprehensive feature documentation
- Updated admin interface to include new theme options
- Improved JavaScript SDK to support theme parameter
- Enhanced API to handle theme overrides

### Fixed
- Theme CSS properly scoped to prevent conflicts
- SDK now correctly passes theme parameter to API

## [0.3.1] - 2025-06-23

### Fixed
- Fixed form settings page parameter issue - method now correctly retrieves form data using `rgget('id')` (PR #24)
- Fixed duplicate constant definition error on settings save
- Fixed incorrect admin URLs in analytics page (changed from `gf_form_settings` to `gf_edit_forms` with proper `view=settings` parameter)

### Contributors
- @cenemil - Form settings page fixes (PR #24)

## [0.3.0] - 2025-06-23

### Added
- Comprehensive documentation suite (#21, #16, #6)
- Complete user guide with setup, configuration, and usage instructions
- Developer hooks reference with all WordPress and plugin-specific hooks
- Troubleshooting guide with diagnostic steps and common solutions
- API documentation covering REST endpoints and JavaScript SDK
- Main documentation index with navigation and quick start guide

### Documentation
- `/docs/user-guide/README.md` - Complete user guide for end users
- `/docs/developer/hooks-reference.md` - Developer reference for customization
- `/docs/troubleshooting/README.md` - Comprehensive troubleshooting guide
- `/docs/api/README.md` - REST API and JavaScript SDK documentation
- `/docs/README.md` - Main documentation index and navigation

### Improved
- Professional-grade documentation for users at all technical levels
- Clear navigation structure for different user types (users, developers, admins)
- Comprehensive examples and code samples
- Cross-references between documentation sections

## [0.2.2] - 2025-06-23

### Fixed
- Enhanced form settings save mechanism for better compatibility (#19)
- Added fallback save methods to ensure settings persist across all Gravity Forms versions
- Implemented duplicate save prevention using flags

### Improved
- Now hooks into both dynamic and documented save points for maximum compatibility
- Added support for `gform_pre_form_settings_save` as fallback
- Settings save in form display page if primary save hook fails
- Better future-proofing against Gravity Forms updates

### Technical
- Uses three-tier save approach: dynamic hook, documented hook, and page display fallback
- Prevents duplicate saves with `GF_JS_EMBED_SETTINGS_SAVED` constant

## [0.2.1] - 2025-06-23

### Fixed
- Added missing GFFormSettings::page_header() and page_footer() for proper Gravity Forms UI consistency (#18)
- Form settings page now properly integrates with Gravity Forms navigation and styling
- Removed custom heading that was redundant with GF page header

### Improved
- Better error handling with proper page wrapper even on error conditions
- Form settings page now matches the look and feel of native Gravity Forms pages

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

[0.4.0]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.3.1...v0.4.0
[0.3.1]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.2.2...v0.3.0
[0.2.2]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.5...v0.2.0
[0.1.5]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.4...v0.1.5
[0.1.4]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/jezweb/js-gravity-forms-embed/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/jezweb/js-gravity-forms-embed/releases/tag/v0.1.0
