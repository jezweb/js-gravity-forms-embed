# User Guide

Complete guide to using the Gravity Forms JavaScript Embed plugin to embed forms on external websites without iframes.

## Table of Contents

- [Quick Start](#quick-start)
- [Installation](#installation)
- [Configuration](#configuration)
- [Embedding Forms](#embedding-forms)
- [Security Settings](#security-settings)
- [Styling Options](#styling-options)
- [Analytics & Monitoring](#analytics--monitoring)
- [Advanced Features](#advanced-features)
- [Frequently Asked Questions](#frequently-asked-questions)

## Quick Start

Get your first form embedded in 5 minutes:

### 1. Enable Embedding

1. Go to **Gravity Forms** → Select your form
2. Click **Settings** → **JavaScript Embed**
3. Check ✅ **Enable embedding for this form**
4. Add your domain to **Allowed Domains** (e.g., `example.com`)
5. Click **Save Settings**

### 2. Get Embed Code

Copy the JavaScript embed code provided:

```html
<div id="gf-embed-1"></div>
<script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
<script>
GFEmbed.render({
    formId: 1,
    container: '#gf-embed-1',
    domain: 'yoursite.com'
});
</script>
```

### 3. Add to Your Website

Paste the code into any HTML page where you want the form to appear.

**That's it!** Your form will load without an iframe, matching your site's design.

## Installation

### Automatic Installation

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins** → **Add New**
3. Search for "Gravity Forms JavaScript Embed"
4. Click **Install Now** → **Activate**

### Manual Installation

1. Download the plugin ZIP file
2. Go to **Plugins** → **Add New** → **Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Requirements

- WordPress 5.8 or higher
- Gravity Forms 2.5 or higher  
- PHP 7.4 or higher

## Configuration

### Plugin Settings

Access plugin settings at **Gravity Forms** → **JS Embed**:

#### Analytics Dashboard
- View form embedding statistics
- Monitor submission rates
- Track popular forms
- Export usage data

#### Testing Dashboard
- Run system compatibility checks
- Test form configurations
- Verify API endpoints
- Monitor performance

### Form-Level Settings

Configure each form individually at **Form Settings** → **JavaScript Embed**:

#### Basic Settings

**Enable embedding for this form**
- ✅ Check to allow external embedding
- ❌ Uncheck to disable (forms won't load externally)

**Display Options**
- **Show form title**: Display form name above fields
- **Show form description**: Display form description text

#### Security Settings

**Allowed Domains**
Enter domains that can embed this form (one per line):
```
example.com
subdomain.example.com
another-site.org
```

**Domain Restrictions**
- Leave empty to allow all domains (not recommended)
- Use `*` for wildcard (development only)
- Include subdomains explicitly if needed

**API Key Authentication** (Optional)
- Enable for additional security
- Required for sensitive forms
- Auto-generated when enabled

#### Rate Limiting

Configure request limits to prevent abuse:

- **Requests per minute**: Default 60
- **Submissions per hour**: Default 20
- **Burst allowance**: Default 10

## Embedding Forms

### Basic Embedding

The simplest way to embed a form:

```html
<div id="my-form"></div>
<script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
<script>
GFEmbed.render({
    formId: 1,
    container: '#my-form',
    domain: 'yoursite.com'
});
</script>
```

### Advanced Configuration

Customize form appearance and behavior:

```javascript
GFEmbed.render({
    formId: 1,
    container: '#my-form',
    domain: 'yoursite.com',
    
    // Styling options
    theme: 'material',          // default, minimal, rounded, material, custom
    customCSS: 'form { max-width: 600px; }',
    
    // Display options
    showTitle: true,
    showDescription: false,
    
    // Behavior options
    autoResize: true,
    enableAnimation: true,
    
    // Callbacks
    onLoad: function(form) {
        console.log('Form loaded', form);
    },
    onSubmit: function(data) {
        console.log('Form submitted', data);
    },
    onSuccess: function(response) {
        console.log('Submission successful', response);
    },
    onError: function(error) {
        console.log('Submission failed', error);
    }
});
```

### Multiple Forms

Embed multiple forms on the same page:

```html
<!-- Form 1 -->
<div id="contact-form"></div>

<!-- Form 2 -->
<div id="newsletter-form"></div>

<script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
<script>
// Contact form
GFEmbed.render({
    formId: 1,
    container: '#contact-form',
    domain: 'yoursite.com',
    theme: 'material'
});

// Newsletter form
GFEmbed.render({
    formId: 2,
    container: '#newsletter-form',
    domain: 'yoursite.com',
    theme: 'minimal'
});
</script>
```

### Conditional Loading

Load forms based on user interaction:

```javascript
// Load form when button is clicked
document.getElementById('show-form-btn').onclick = function() {
    GFEmbed.render({
        formId: 1,
        container: '#dynamic-form',
        domain: 'yoursite.com'
    });
};

// Load form after page scroll
window.addEventListener('scroll', function() {
    if (window.scrollY > 500) {
        // Load form only once
        if (!window.formLoaded) {
            GFEmbed.render({
                formId: 3,
                container: '#scroll-form',
                domain: 'yoursite.com'
            });
            window.formLoaded = true;
        }
    }
});
```

## Security Settings

### Domain Whitelisting

Control which websites can embed your forms:

#### Specific Domains
```
example.com           ← Allow only this domain
www.example.com       ← Include www if needed
blog.example.com      ← Include subdomains explicitly
```

#### Development Setup
```
localhost:3000        ← Local development
staging.example.com   ← Staging environment  
example.com           ← Production site
```

#### Security Best Practices

✅ **DO:**
- Use specific domain names
- Include all necessary subdomains
- Regularly audit allowed domains
- Remove unused domains promptly

❌ **DON'T:**
- Use wildcard (*) in production
- Allow overly broad domains
- Leave domain list empty
- Include untrusted domains

### API Key Authentication

For sensitive forms, enable API key authentication:

1. **Enable API Keys**
   - Form Settings → JavaScript Embed
   - Check "Require API key"
   - Copy the generated key

2. **Use in Embed Code**
   ```javascript
   GFEmbed.render({
       formId: 1,
       container: '#secure-form',
       domain: 'yoursite.com',
       apiKey: 'your-api-key-here'
   });
   ```

3. **Key Management**
   - Regenerate keys regularly
   - Use different keys per environment
   - Store keys securely (environment variables)

### Rate Limiting

Prevent abuse with configurable limits:

#### Default Limits
- **60 requests/minute** per IP address
- **20 submissions/hour** per form
- **10 burst requests** allowed

#### Custom Limits
Adjust based on your needs:

```
High-traffic forms: 120 requests/minute
Contact forms: 30 requests/minute  
Newsletter signups: 240 requests/minute
```

### Security Monitoring

Monitor security events in **JS Embed** → **Testing**:

- **Rate limit violations**: IPs hitting limits
- **Domain violations**: Unauthorized domains
- **API key failures**: Invalid authentication
- **Suspicious patterns**: Potential attacks

## Styling Options

### Built-in Themes

Choose from pre-designed themes:

#### Default Theme
Clean, professional styling that works with most websites:
```javascript
theme: 'default'
```

#### Minimal Theme  
Stripped-down styling for maximum customization:
```javascript
theme: 'minimal'
```

#### Rounded Theme
Modern design with rounded corners and soft shadows:
```javascript
theme: 'rounded'
```

#### Material Theme
Google Material Design-inspired styling:
```javascript
theme: 'material'
```

### Custom Styling

#### Inline CSS
Add custom styles directly:
```javascript
GFEmbed.render({
    formId: 1,
    container: '#my-form',
    domain: 'yoursite.com',
    customCSS: `
        .gf-embed-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .gf-field input,
        .gf-field textarea {
            border: 2px solid #007cba;
            border-radius: 8px;
            padding: 12px;
        }
        .gf-submit-button {
            background: #007cba;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
        }
    `
});
```

#### External Stylesheet
Link to your own CSS file:
```html
<link rel="stylesheet" href="your-custom-form-styles.css">
```

#### CSS Classes Reference
Target specific form elements:

```css
/* Form container */
.gf-embed-form { }

/* Individual fields */
.gf-field { }
.gf-field-required { }

/* Input elements */
.gf-field input { }
.gf-field textarea { }  
.gf-field select { }

/* Labels and descriptions */
.gf-field-label { }
.gf-field-description { }

/* Buttons */
.gf-submit-button { }
.gf-next-button { }
.gf-previous-button { }

/* Validation */
.gf-field-error { }
.gf-error-message { }

/* Multi-page forms */
.gf-page-break { }
.gf-progress-bar { }
```

### Responsive Design

Ensure forms look great on all devices:

```css
/* Mobile-first approach */
.gf-embed-form {
    width: 100%;
    max-width: 100%;
    padding: 10px;
}

/* Tablet styles */
@media (min-width: 768px) {
    .gf-embed-form {
        max-width: 600px;
        padding: 20px;
    }
}

/* Desktop styles */
@media (min-width: 1024px) {
    .gf-embed-form {
        max-width: 800px;
        padding: 30px;
    }
}
```

## Analytics & Monitoring

### View Analytics

Access detailed analytics at **Gravity Forms** → **JS Embed**:

#### Overview Dashboard
- **Total forms embedded**: Count of active embedded forms
- **Total views**: Number of times forms were loaded
- **Total submissions**: Successful form submissions  
- **Conversion rate**: Submissions/views percentage

#### Form-Specific Stats
For each form, view:
- **Embed views**: How often form was loaded
- **Submission count**: Number of submissions
- **Top domains**: Which sites generate most traffic
- **Popular fields**: Most/least used form fields
- **Conversion trends**: Performance over time

#### Performance Metrics
Monitor technical performance:
- **Average load time**: Form rendering speed
- **API response time**: Backend performance
- **Error rates**: Failed requests percentage
- **Bounce rate**: Forms loaded but not submitted

### Export Data

Export analytics for reporting:

1. **CSV Export**
   - Go to JS Embed → Analytics
   - Select date range
   - Click "Export CSV"

2. **JSON API**
   ```javascript
   fetch('/wp-json/gf-embed/v1/analytics/1', {
       headers: { 'X-API-Key': 'your-key' }
   })
   .then(response => response.json())
   .then(data => console.log(data));
   ```

### Real-time Monitoring

Set up alerts for important events:

```php
// Add to functions.php
add_action('gf_js_embed_submission', function($form_id, $entry_id) {
    // Send notification for VIP form submissions
    if ($form_id == 1) {
        wp_mail('admin@example.com', 'VIP Form Submission', 'New submission received');
    }
});
```

## Advanced Features

### Multi-page Forms

Embed complex multi-page forms:

```javascript
GFEmbed.render({
    formId: 5,
    container: '#multi-page-form',
    domain: 'yoursite.com',
    
    // Multi-page options
    showProgress: true,
    enableBackButton: true,
    saveProgress: true,        // Save partially completed forms
    
    // Page transition callbacks
    onPageChange: function(currentPage, totalPages) {
        console.log(`Page ${currentPage} of ${totalPages}`);
    }
});
```

### Conditional Logic

Forms with conditional fields work automatically:

```javascript
// No additional configuration needed
// Conditional logic from Gravity Forms is preserved
GFEmbed.render({
    formId: 3,
    container: '#conditional-form',
    domain: 'yoursite.com'
});
```

### File Uploads

Handle file uploads securely:

```javascript
GFEmbed.render({
    formId: 4,
    container: '#upload-form',
    domain: 'yoursite.com',
    
    // File upload options
    maxFileSize: '10MB',
    allowedTypes: ['jpg', 'png', 'pdf', 'doc'],
    
    // Upload progress callback
    onUploadProgress: function(progress) {
        console.log(`Upload ${progress}% complete`);
    }
});
```

### Payment Integration

Embed forms with payment fields:

```javascript
GFEmbed.render({
    formId: 6,
    container: '#payment-form',
    domain: 'yoursite.com',
    
    // Payment options
    currency: 'USD',
    testMode: false,           // Set to true for testing
    
    // Payment callbacks
    onPaymentSuccess: function(response) {
        console.log('Payment successful', response);
    },
    onPaymentError: function(error) {
        console.log('Payment failed', error);
    }
});
```

### Custom Validation

Add client-side validation:

```javascript
GFEmbed.render({
    formId: 2,
    container: '#validated-form',
    domain: 'yoursite.com',
    
    // Custom validation
    onValidate: function(formData) {
        // Custom validation logic
        if (formData.input_1.length < 5) {
            return {
                valid: false,
                errors: {
                    input_1: 'Name must be at least 5 characters'
                }
            };
        }
        return { valid: true };
    }
});
```

### Dynamic Form Loading

Load different forms based on conditions:

```javascript
// Load form based on user type
const userType = getUserType(); // Your function
const formId = userType === 'premium' ? 1 : 2;

GFEmbed.render({
    formId: formId,
    container: '#dynamic-form',
    domain: 'yoursite.com'
});

// Load form based on URL parameters
const urlParams = new URLSearchParams(window.location.search);
const campaign = urlParams.get('campaign');
const formMap = {
    'newsletter': 1,
    'contact': 2,
    'demo': 3
};

if (formMap[campaign]) {
    GFEmbed.render({
        formId: formMap[campaign],
        container: '#campaign-form',
        domain: 'yoursite.com'
    });
}
```

## Frequently Asked Questions

### General Questions

**Q: Do I need to modify my existing Gravity Forms?**
A: No, existing forms work without modification. Just enable embedding in form settings.

**Q: Will this work with my theme?**
A: Yes, forms adapt to your website's styling. Use themes or custom CSS for perfect integration.

**Q: Can I embed the same form on multiple sites?**
A: Yes, add all domains to the "Allowed Domains" list in form settings.

**Q: Is this better than iframe embedding?**
A: Yes, JavaScript embedding provides better user experience, SEO benefits, and mobile compatibility.

### Technical Questions

**Q: What happens if JavaScript is disabled?**
A: Forms won't load. Consider providing a fallback link to a hosted form page.

**Q: How do I handle GDPR compliance?**
A: Forms inherit GDPR settings from Gravity Forms. Add privacy checkboxes as needed.

**Q: Can I customize the submit button text?**
A: Yes, edit the form in Gravity Forms admin to change button text and styling.

**Q: How do I track conversions in Google Analytics?**
A: Use the `onSuccess` callback to trigger Google Analytics events:

```javascript
GFEmbed.render({
    formId: 1,
    container: '#form',
    domain: 'yoursite.com',
    onSuccess: function(response) {
        // Google Analytics 4
        gtag('event', 'form_submit', {
            'form_id': 1,
            'form_name': 'Contact Form'
        });
        
        // Universal Analytics
        ga('send', 'event', 'Form', 'Submit', 'Contact Form');
    }
});
```

### Troubleshooting Questions

**Q: Form doesn't appear on my website**
A: Check these common issues:
1. Domain added to allowed domains list
2. Form embedding is enabled
3. No JavaScript errors in browser console
4. Correct form ID in embed code

**Q: Getting CORS errors**  
A: Ensure your domain is in the allowed domains list without protocol (use `example.com`, not `https://example.com`).

**Q: Form loads but won't submit**
A: Check:
1. All required fields are filled
2. No rate limiting blocking submissions  
3. API endpoint is accessible
4. Form validation rules are met

**Q: Styling doesn't match my website**
A: Use custom CSS or select a different theme. The 'minimal' theme provides the cleanest base for customization.

For more detailed troubleshooting, see the [Troubleshooting Guide](../troubleshooting/README.md).

### Performance Questions  

**Q: Will this slow down my website?**
A: No, forms load asynchronously and are cached. The plugin is optimized for performance.

**Q: How many forms can I embed on one page?**
A: No technical limit, but consider user experience. Multiple forms load independently.

**Q: Can I preload forms for faster display?**
A: Yes, use the `preload` option:

```javascript
GFEmbed.preload({
    formId: 1,
    domain: 'yoursite.com'
});
```

### Security Questions

**Q: Is JavaScript embedding secure?**
A: Yes, the plugin includes multiple security layers:
- Domain whitelisting
- Rate limiting  
- Input sanitization
- CSRF protection
- Honeypot spam protection

**Q: Should I use API keys?**
A: Use API keys for sensitive forms like contact forms with personal data or payment forms.

**Q: How do I prevent spam?**
A: The plugin includes automatic spam protection. Additional measures:
- Enable rate limiting
- Use strict domain whitelisting
- Add CAPTCHA fields in Gravity Forms
- Monitor security logs

## Need More Help?

- **Documentation**: Browse other guides in the `/docs` folder
- **Testing Tools**: Use the built-in testing dashboard
- **Support**: Create an issue on [GitHub](https://github.com/jezweb/js-gravity-forms-embed/issues)
- **Community**: Join the WordPress.org plugin support forum

Remember to check the [Troubleshooting Guide](../troubleshooting/README.md) for solutions to common problems.