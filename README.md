# Gravity Forms JavaScript Embed

Transform your Gravity Forms into powerful, embeddable JavaScript widgets that work seamlessly on any website - no iframes required! This enterprise-grade plugin provides a modern, secure, and performant solution for cross-domain form embedding.

## 🌟 Why JavaScript Embed?

Traditional iframe embedding comes with significant limitations: styling restrictions, responsive design challenges, cross-domain issues, and poor SEO. Our JavaScript embedding solution eliminates these problems while providing a superior user experience.

### Key Benefits

- **No iframe limitations** - Full control over styling and behavior
- **Seamless integration** - Forms appear native to the host website
- **Better performance** - Faster loading and smaller footprint
- **Enhanced security** - API keys, domain restrictions, and rate limiting
- **Complete analytics** - Track performance across all embedded locations
- **Developer-friendly** - Extensive API and customization options

## 🚀 Core Features

### Form Embedding Capabilities

- **Universal Compatibility** - Embed on any website, platform, or CMS
- **Multiple Embedding Methods** - Simple script tags, data attributes, or programmatic loading
- **Unlimited Forms** - Embed as many forms as needed on a single page
- **Cross-Domain Support** - Full CORS implementation for seamless cross-origin requests
- **Responsive Design** - Forms automatically adapt to any screen size
- **No Dependencies** - Works without jQuery or other libraries

### Field Support

Comprehensive support for all Gravity Forms field types:

- ✅ Standard Fields (text, email, phone, number, etc.)
- ✅ Advanced Fields (name, address, file upload, list)
- ✅ Post Fields (title, content, category, tags)
- ✅ Pricing Fields (product, quantity, total)
- ✅ Signature Fields with touch support
- ✅ Multi-page Forms with progress indicators
- ✅ Conditional Logic (show/hide fields dynamically)
- ✅ Calculations and dynamic field population
- ✅ File Uploads with drag-and-drop
- ✅ Date/Time pickers with localization

### 🎨 Themes & Styling

**10 Pre-built Professional Themes:**

1. **Default** - Classic Gravity Forms appearance
2. **Dark Mode** - Elegant dark theme with high contrast
3. **Bootstrap-style** - Mimics Bootstrap 5 (no Bootstrap required)
4. **Tailwind-style** - Matches Tailwind CSS (no Tailwind required)
5. **Glass/Glassmorphism** - Modern frosted glass effect
6. **Flat Design** - Bold, minimalist aesthetic
7. **Minimal** - Clean with underline inputs
8. **Rounded** - Soft corners and modern spacing
9. **Material Design** - Google's Material UI inspired
10. **Corporate** - Professional, conservative styling

**Styling Features:**
- Theme selection per form or per embed
- Custom CSS support with live preview
- CSS variable system for easy customization
- Scoped styles prevent conflicts
- Mobile-optimized responsive design

### 🔒 Security Features

**Enterprise-Grade Security:**

- **API Key Authentication** - Generate unique keys per form
- **Domain Whitelisting** - Restrict embedding to authorized domains
  - Exact domain matching
  - Wildcard subdomain support
  - IP address restrictions
- **Rate Limiting** - Configurable limits per IP/domain
- **CSRF Protection** - Token-based form security
- **Input Sanitization** - XSS and injection prevention


### 📊 Analytics & Insights

**Comprehensive Analytics Dashboard:**

- **Form Performance Metrics**
  - Total views and unique visitors
  - Submission counts and success rates
  - Conversion rates and abandonment tracking
  - Average completion time
  
- **Traffic Analysis**
  - Views by domain/website
  - Device and browser statistics
  - Peak usage times
  
- **Engagement Tracking**
  - Drop-off points identification
  - Error frequency by field
  - Multi-page progression analysis

### ⚡ Performance Optimization

- **Asset Optimization** - Minified CSS and JavaScript
- **Caching Support** - Intelligent caching strategies
- **Async Loading** - Non-blocking script execution

### 🛠️ Developer Features

**JavaScript SDK:**
```javascript
// Get the global event system instance
const gfEvents = window.GFEvents;

// Listen for form events
gfEvents.on('form.ready', (data) => {
    console.log('Form ready:', data.formId);
});

gfEvents.on('field.changed', (data) => {
    console.log('Field changed:', data.fieldId, data.value);
});

gfEvents.on('form.submitted', (data) => {
    console.log('Form submitted:', data.formId);
});

// Emit custom events
gfEvents.emit('custom.event', { custom: 'data' });
```

**REST API Endpoints:**
- `GET /wp-json/gf-js-embed/v1/form/{id}` - Retrieve form structure
- `POST /wp-json/gf-js-embed/v1/form/{id}/submit` - Submit form data
- `GET /wp-json/gf-js-embed/v1/analytics/form/{id}` - Get form analytics
- `POST /wp-json/gf-js-embed/v1/analytics/track` - Track analytics events

**WordPress Hooks:**
- `gf_js_embed_before_render` - Modify form before rendering
- `gf_js_embed_allowed_domains` - Filter allowed domains
- `gf_js_embed_submission_data` - Process submission data
- `gf_js_embed_api_response` - Modify API responses

### 🧪 Testing & Validation

**Built-in Testing Dashboard:**
- System compatibility checks
- Form configuration validation
- API endpoint testing
- Security feature verification
- Performance benchmarking
- Cross-browser testing tools

### 🌐 Internationalization

- Basic translation support
- Plugin text domain ready for translations

## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Gravity Forms 2.5 or higher
- SSL certificate (recommended)

## 🚀 Getting Started

### Installation

1. Download the latest release from [GitHub](https://github.com/jezweb/js-gravity-forms-embed/releases)
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin in WordPress
4. Configure your first form for embedding

### Basic Setup

1. **Enable Embedding:**
   - Navigate to Forms → Your Form → Settings → JavaScript Embed
   - Check "Enable JavaScript Embedding"
   - Configure security settings as needed

2. **Embed Your Form:**
   ```html
   <div data-gf-form="1"></div>
   <script src="https://your-site.com/gf-js-embed/v1/embed.js"></script>
   ```

### Advanced Configuration

**With Theme and API Key:**
```html
<div data-gf-form="1" 
     data-gf-theme="dark" 
     data-gf-api-key="gfjs_xxxxxxxxxx">
</div>
<script src="https://your-site.com/gf-js-embed/v1/gf-embed-sdk.js"></script>
```

**Multiple Forms:**
```html
<div data-gf-form="1" data-gf-theme="bootstrap"></div>
<div data-gf-form="2" data-gf-theme="tailwind"></div>
<div data-gf-form="3" data-gf-theme="glass"></div>
<script src="https://your-site.com/gf-js-embed/v1/gf-embed-sdk.js"></script>
```

## 📊 Use Cases

- **Marketing Landing Pages** - Embed forms on campaign sites
- **Multi-Site Networks** - Share forms across WordPress sites
- **Static Site Generators** - Add forms to Jekyll, Hugo, etc.
- **Single Page Applications** - React, Vue, Angular integration
- **Third-Party Platforms** - Shopify, Squarespace, Wix
- **Mobile Applications** - WebView compatible
- **Email Campaigns** - Link to hosted form pages

## 🛟 Support & Resources

- 📖 [Full Documentation](https://github.com/jezweb/js-gravity-forms-embed/tree/main/docs)
- 🐛 [Report Issues](https://github.com/jezweb/js-gravity-forms-embed/issues)
- 💬 [Community Discussions](https://github.com/jezweb/js-gravity-forms-embed/discussions)
- 📧 [Email Support](mailto:support@jezweb.com.au)
- 🎥 [Video Tutorials](https://www.youtube.com/jezweb)

## 🤝 Contributing

We welcome contributions! Whether it's:
- 🐛 Bug reports
- 💡 Feature suggestions
- 🔧 Pull requests
- 📖 Documentation improvements
- 🌍 Translations

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## 📄 License

This plugin is licensed under the GPL v2 or later. See [LICENSE](LICENSE) for details.

## 🏆 Credits

Developed and maintained by [Jezweb](https://www.jezweb.com.au) - WordPress experts since 2005.

Special thanks to:
- The Gravity Forms team for their excellent plugin
- Our contributors and beta testers
- The WordPress community

---

**Version:** 0.4.0 | **Last Updated:** June 2025 | **Active Installs:** 1,000+

⭐ If you find this plugin useful, please [star it on GitHub](https://github.com/jezweb/js-gravity-forms-embed)!