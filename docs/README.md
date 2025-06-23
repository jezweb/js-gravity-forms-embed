# Gravity Forms JavaScript Embed - Documentation

Complete documentation for the Gravity Forms JavaScript Embed plugin.

## Quick Navigation

### 🚀 Getting Started
- **[User Guide](user-guide/README.md)** - Complete guide for end users
- **[Installation & Setup](user-guide/README.md#installation)** - Get up and running quickly
- **[Quick Start](user-guide/README.md#quick-start)** - Embed your first form in 5 minutes

### 📚 For Users
- **[Configuration](user-guide/README.md#configuration)** - Plugin and form settings
- **[Embedding Forms](user-guide/README.md#embedding-forms)** - How to embed forms on external sites
- **[Security Settings](user-guide/README.md#security-settings)** - Protect your forms
- **[Styling Options](user-guide/README.md#styling-options)** - Customize form appearance
- **[Analytics & Monitoring](user-guide/README.md#analytics--monitoring)** - Track form performance

### 🔧 For Developers
- **[API Documentation](api/README.md)** - REST API and JavaScript SDK reference
- **[Hooks Reference](developer/hooks-reference.md)** - WordPress and plugin-specific hooks
- **[Integration Examples](developer/hooks-reference.md#usage-examples)** - Code examples for customization

### 🛠️ Support & Troubleshooting
- **[Troubleshooting Guide](troubleshooting/README.md)** - Solve common issues
- **[FAQ](user-guide/README.md#frequently-asked-questions)** - Frequently asked questions
- **[Testing Tools](troubleshooting/README.md#testing-tools)** - Built-in diagnostic tools

## What is this plugin?

The Gravity Forms JavaScript Embed plugin allows you to embed Gravity Forms on external websites using modern JavaScript instead of outdated iframes. This provides:

✅ **Better User Experience** - Forms integrate seamlessly with your website design  
✅ **Mobile Responsive** - Perfect display on all devices  
✅ **SEO Friendly** - Content is crawlable by search engines  
✅ **Faster Loading** - No iframe overhead  
✅ **Full Customization** - Complete control over styling  
✅ **Advanced Security** - Domain whitelisting, rate limiting, spam protection  

## Key Features

### Core Functionality
- **iframe-free embedding** on any website
- **All Gravity Forms field types** supported
- **Multi-page forms** with progress indicators
- **Conditional logic** preserved from Gravity Forms
- **File uploads** with progress tracking
- **Payment integration** for e-commerce forms

### Security & Performance
- **Domain whitelisting** for access control
- **API key authentication** for sensitive forms
- **Rate limiting** to prevent abuse
- **Advanced spam protection** with honeypot fields
- **CORS configuration** for cross-domain security
- **Performance monitoring** and optimization

### Analytics & Insights
- **Detailed analytics** dashboard
- **Conversion tracking** and reporting
- **Real-time monitoring** of form performance
- **Export capabilities** for further analysis
- **Custom event tracking** integration

## Architecture Overview

```
External Website          WordPress Site
┌─────────────────┐      ┌──────────────────┐
│   Your Site     │      │  Gravity Forms   │
│                 │      │                  │
│ ┌─────────────┐ │ HTTP │ ┌──────────────┐ │
│ │ Embed Code  │◄├─────►│ │ REST API     │ │
│ └─────────────┘ │      │ └──────────────┘ │
│                 │      │                  │
│ ┌─────────────┐ │      │ ┌──────────────┐ │
│ │ Form HTML   │ │      │ │ JS Embed     │ │
│ │ + CSS       │ │      │ │ Plugin       │ │
│ └─────────────┘ │      │ └──────────────┘ │
└─────────────────┘      └──────────────────┘
```

### How It Works

1. **Embed Script** - Include the JavaScript SDK on your external website
2. **API Request** - SDK fetches form configuration via REST API
3. **Security Check** - Domain validation and rate limiting applied
4. **Form Rendering** - JavaScript creates form HTML dynamically
5. **Submission** - Form data sent securely to WordPress
6. **Processing** - Gravity Forms handles validation, notifications, and storage

## Quick Start Example

```html
<!-- Add this to any HTML page -->
<div id="my-contact-form"></div>
<script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
<script>
GFEmbed.render({
    formId: 1,
    container: '#my-contact-form',
    domain: 'yoursite.com'
});
</script>
```

## Documentation Structure

### For Different User Types

#### **Website Owners & Marketers**
Start with: [User Guide](user-guide/README.md) → [Quick Start](user-guide/README.md#quick-start)

Key sections:
- Form configuration and security settings
- Embedding forms on your website
- Analytics and conversion tracking
- Styling and customization options

#### **Web Developers & Designers**
Start with: [API Documentation](api/README.md) → [JavaScript SDK](api/README.md#javascript-sdk)

Key sections:
- REST API endpoints and responses
- JavaScript SDK methods and options
- Custom styling and theming
- Integration examples and code samples

#### **WordPress Developers & Plugin Authors**
Start with: [Hooks Reference](developer/hooks-reference.md) → [Plugin-Specific Filters](developer/hooks-reference.md#plugin-specific-filters)

Key sections:
- WordPress and Gravity Forms hooks
- Plugin extension points
- Security and validation hooks
- Custom functionality examples

#### **System Administrators**
Start with: [Troubleshooting Guide](troubleshooting/README.md) → [Quick Diagnostic Steps](troubleshooting/README.md#quick-diagnostic-steps)

Key sections:
- System requirements and compatibility
- Performance optimization
- Security monitoring and logging
- Backup and maintenance procedures

## Support & Community

### Getting Help

1. **Built-in Testing** - Use the plugin's testing dashboard first
2. **Documentation Search** - Check relevant documentation sections
3. **GitHub Issues** - [Report bugs or request features](https://github.com/jezweb/js-gravity-forms-embed/issues)
4. **WordPress Forums** - Community support and discussions

### Contributing

We welcome contributions! Ways to help:

- **Report Issues** - Found a bug? Let us know!
- **Suggest Features** - Ideas for improvements
- **Submit Code** - Pull requests for fixes and enhancements
- **Improve Documentation** - Help make these docs even better
- **Share Examples** - Show how you're using the plugin

### Community Guidelines

- **Be Respectful** - Help create a welcoming environment
- **Search First** - Check existing issues and documentation
- **Provide Details** - Include version numbers, error messages, and steps to reproduce
- **Stay On Topic** - Keep discussions focused and relevant

## Version Compatibility

| Plugin Version | WordPress | Gravity Forms | PHP   |
|---------------|-----------|---------------|-------|
| 0.2.x         | 5.8+      | 2.5+          | 7.4+  |
| 0.3.x (planned) | 6.0+    | 2.6+          | 8.0+  |

## License & Legal

- **License**: GPL v2 or later
- **Privacy**: No personal data transmitted to third parties
- **GDPR**: Compatible with privacy regulations
- **Security**: Regular security audits and updates

---

## Quick Links

**Essential Reading:**
- [User Guide](user-guide/README.md) - Complete usage instructions
- [API Documentation](api/README.md) - Technical reference  
- [Troubleshooting](troubleshooting/README.md) - Problem solving

**Advanced Topics:**
- [Developer Hooks](developer/hooks-reference.md) - Customization reference
- [Security Best Practices](user-guide/README.md#security-settings) - Protect your forms
- [Performance Optimization](troubleshooting/README.md#performance-issues) - Speed improvements

**Support Resources:**
- [GitHub Repository](https://github.com/jezweb/js-gravity-forms-embed)
- [Issue Tracker](https://github.com/jezweb/js-gravity-forms-embed/issues)
- [WordPress Plugin Page](https://wordpress.org/plugins/gravity-forms-js-embed/)

---

*Last updated: June 2025 | Plugin version: 0.2.2*