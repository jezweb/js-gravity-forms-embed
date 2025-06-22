# Gravity Forms JavaScript Embed

Embed Gravity Forms on any website using JavaScript instead of iframes. This plugin provides a modern, performant alternative to iframe embedding with full support for all Gravity Forms features.

## Features

- 🚀 **JavaScript-based embedding** - No iframes required
- 🔒 **Secure** - API key authentication and domain whitelisting
- 📊 **Analytics** - Track form views and submissions
- 🎨 **Customizable** - Multiple themes and custom CSS support
- 📱 **Responsive** - Works perfectly on all devices
- ⚡ **Fast** - Optimized for performance
- 🌐 **CORS Support** - Embed forms on any domain
- 📝 **All Field Types** - Support for all Gravity Forms field types

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Gravity Forms 2.5 or higher

## Installation

1. Download the plugin from the [releases page](https://github.com/jezweb/js-gravity-forms-embed/releases)
2. Upload to your WordPress plugins directory
3. Activate the plugin
4. Configure your forms for embedding

## Quick Start

### 1. Enable JavaScript Embedding

1. Go to **Forms** → Select your form → **Settings** → **JavaScript Embed**
2. Check **Enable JavaScript Embedding**
3. Configure allowed domains (leave empty to allow all)
4. Save settings

### 2. Embed Your Form

Copy and paste this code on any website:

```html
<!-- Gravity Forms JavaScript Embed -->
<div id="gf-form-1"></div>
<script src="https://your-site.com/gf-js-embed/v1/embed.js?form=1"></script>
```

Replace `1` with your form ID and `your-site.com` with your WordPress domain.

## Embedding Methods

### Method 1: Simple Embed
```html
<div id="gf-form-1"></div>
<script src="https://your-site.com/gf-js-embed/v1/embed.js?form=1"></script>
```

### Method 2: Data Attribute
```html
<div data-gf-form="1"></div>
<script src="https://your-site.com/gf-js-embed/v1/embed.js"></script>
```

### Method 3: Multiple Forms
```html
<div data-gf-form="1"></div>
<div data-gf-form="2"></div>
<div data-gf-form="3"></div>
<script src="https://your-site.com/gf-js-embed/v1/embed.js"></script>
```

### Method 4: With API Key
```html
<div data-gf-form="1" data-gf-api-key="gfjs_xxxxxxxxxx"></div>
<script src="https://your-site.com/gf-js-embed/v1/embed.js"></script>
```

## Configuration

### Domain Whitelisting

Control which domains can embed your forms:

1. Go to form settings → JavaScript Embed
2. Add allowed domains (one per line):
   - `https://example.com` - Exact domain
   - `*.example.com` - Wildcard subdomains
   - Leave empty to allow all domains

### Themes

Choose from built-in themes or create your own:

- **Default** - Standard Gravity Forms styling
- **Minimal** - Clean, minimalist design
- **Rounded** - Soft corners and modern look
- **Material** - Google Material Design inspired

### Custom CSS

Add custom CSS in the form settings to match your brand:

```css
/* Example: Change button color */
.gf-button {
    background: #ff6b6b;
}

/* Example: Adjust field spacing */
.gf-field {
    margin-bottom: 30px;
}
```

## JavaScript API

### Events

Listen for form events:

```javascript
document.addEventListener('gfEmbedFormReady', function(e) {
    console.log('Form ready:', e.detail.formId);
});

document.addEventListener('gfEmbedSubmitSuccess', function(e) {
    console.log('Form submitted:', e.detail.entryId);
});

document.addEventListener('gfEmbedSubmitError', function(e) {
    console.log('Submit error:', e.detail.errors);
});
```

### Methods

```javascript
// Load a form programmatically
GravityFormsEmbed.loadForm(formId, containerElement);

// Access form data
const formData = GravityFormsEmbed.forms[formId];
```

## Analytics

View detailed analytics for your embedded forms:

1. Go to **Forms** → **JS Embed Analytics**
2. See metrics including:
   - Total views
   - Submissions
   - Conversion rates
   - Traffic by domain

## Security

The plugin includes multiple security layers:

- **API Key Authentication** - Optional API keys for added security
- **Domain Whitelisting** - Restrict embedding to specific domains
- **Rate Limiting** - Prevent abuse with configurable limits
- **Input Sanitization** - All inputs are properly sanitized
- **CORS Headers** - Proper cross-origin resource sharing

## Troubleshooting

### Form not loading

1. Check browser console for errors
2. Verify domain is whitelisted
3. Ensure form embedding is enabled
4. Check API key if using authentication

### Styling issues

1. Check for CSS conflicts with host page
2. Use theme options or custom CSS
3. Inspect elements in browser developer tools

### Submission errors

1. Verify all required fields are filled
2. Check browser console for detailed errors
3. Ensure CORS is properly configured

## Support

- 📖 [Documentation](https://github.com/jezweb/js-gravity-forms-embed/wiki)
- 🐛 [Report Issues](https://github.com/jezweb/js-gravity-forms-embed/issues)
- 💬 [Discussions](https://github.com/jezweb/js-gravity-forms-embed/discussions)

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

This plugin is licensed under the GPL v2 or later.

---

Made with ❤️ by [Jezweb](https://www.jezweb.com.au)