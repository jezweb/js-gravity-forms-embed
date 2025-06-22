# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[0.1.0]: https://github.com/jezweb/js-gravity-forms-embed/releases/tag/v0.1.0