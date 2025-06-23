# API Documentation

Complete reference for the Gravity Forms JavaScript Embed REST API endpoints and JavaScript SDK.

## Table of Contents

- [REST API Endpoints](#rest-api-endpoints)
- [JavaScript SDK](#javascript-sdk)
- [Authentication](#authentication)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Examples](#examples)

## REST API Endpoints

Base URL: `https://yoursite.com/wp-json/gf-embed/v1/`

All endpoints support CORS when properly configured with allowed domains.

### Get Form Data

Retrieve form configuration and fields.

**Endpoint:** `GET /form/{id}`

**Parameters:**
- `id` (integer, required): Form ID

**Headers:**
- `Origin`: Required for CORS validation
- `X-API-Key`: Optional, required if form has API key enabled

**Response:**
```json
{
  "success": true,
  "form": {
    "id": 1,
    "title": "Contact Form",
    "description": "Get in touch with us",
    "displayTitle": true,
    "displayDescription": true,
    "button": {
      "text": "Submit",
      "type": "text"
    },
    "fields": [
      {
        "id": "1",
        "type": "text",
        "label": "Name",
        "description": "",
        "isRequired": true,
        "placeholder": "Enter your name",
        "cssClass": "",
        "size": "medium",
        "defaultValue": "",
        "errorMessage": "This field is required"
      }
    ],
    "cssClass": "",
    "enableAnimation": false,
    "validationSummary": false
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Form not found",
  "code": "form_not_found"
}
```

### Submit Form

Submit form data and create entry.

**Endpoint:** `POST /submit/{id}`

**Parameters:**
- `id` (integer, required): Form ID

**Headers:**
- `Content-Type`: `application/json`
- `Origin`: Required for CORS validation
- `X-API-Key`: Optional, required if form has API key enabled
- `X-CSRF-Token`: Optional, for additional security

**Request Body:**
```json
{
  "input_1": "John Doe",
  "input_2": "john@example.com",
  "input_3": "Hello, this is a test message.",
  "gf_csrf_token": "optional-csrf-token"
}
```

**Success Response:**
```json
{
  "success": true,
  "entry_id": 123,
  "confirmation": {
    "type": "message",
    "message": "Thank you for your submission.",
    "url": "",
    "pageId": ""
  }
}
```

**Validation Error Response:**
```json
{
  "success": false,
  "errors": {
    "1": "This field is required",
    "2": "Please enter a valid email address"
  },
  "message": "Please correct the errors below."
}
```

### Get Form Assets

Retrieve CSS and configuration data for form rendering.

**Endpoint:** `GET /assets/{id}`

**Parameters:**
- `id` (integer, required): Form ID

**Response:**
```json
{
  "css": "/* Form-specific CSS styles */",
  "translations": {
    "required_field": "This field is required",
    "invalid_email": "Please enter a valid email address"
  },
  "config": {
    "dateFormat": "m/d/Y",
    "timeFormat": "g:i A",
    "startOfWeek": 0,
    "currency": "USD"
  }
}
```

## JavaScript SDK

The JavaScript SDK provides a simple interface for embedding forms.

### Loading the SDK

```html
<script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
```

### Basic Usage

```javascript
GFEmbed.render({
    formId: 1,
    container: '#form-container',
    domain: 'yoursite.com'
});
```

### Configuration Options

```javascript
GFEmbed.render({
    // Required options
    formId: 1,                    // Form ID (integer)
    container: '#form-container', // CSS selector or DOM element
    domain: 'yoursite.com',       // Your WordPress domain
    
    // Authentication
    apiKey: 'your-api-key',       // Optional API key
    
    // Display options
    theme: 'default',             // default|minimal|rounded|material|custom
    showTitle: true,              // Show form title
    showDescription: true,        // Show form description
    customCSS: '',                // Custom CSS string
    
    // Behavior options
    autoResize: true,             // Auto-resize container
    enableAnimation: true,        // Enable form animations
    preloadAssets: false,         // Preload CSS and translations
    
    // Validation options
    validateOnBlur: true,         // Validate fields on blur
    highlightErrors: true,        // Highlight invalid fields
    
    // Event callbacks
    onLoad: function(form) { },           // Form loaded successfully
    onError: function(error) { },         // Loading error occurred
    onSubmit: function(data) { },         // Form submission started
    onSuccess: function(response) { },    // Submission successful
    onValidationError: function(errors) { }, // Validation failed
    onFieldChange: function(field, value) { }, // Field value changed
    onPageChange: function(page, total) { }    // Multi-page form navigation
});
```

### SDK Methods

#### `GFEmbed.render(options)`
Render a form in the specified container.

#### `GFEmbed.preload(options)`
Preload form data without rendering.

```javascript
GFEmbed.preload({
    formId: 1,
    domain: 'yoursite.com'
}).then(formData => {
    console.log('Form preloaded:', formData);
});
```

#### `GFEmbed.validate(formId, data)`
Validate form data without submitting.

```javascript
GFEmbed.validate(1, {
    input_1: 'John Doe',
    input_2: 'invalid-email'
}).then(result => {
    if (result.valid) {
        console.log('Validation passed');
    } else {
        console.log('Validation errors:', result.errors);
    }
});
```

#### `GFEmbed.submit(formId, data, options)`
Submit form data programmatically.

```javascript
GFEmbed.submit(1, {
    input_1: 'John Doe',
    input_2: 'john@example.com'
}, {
    domain: 'yoursite.com',
    apiKey: 'optional-api-key'
}).then(response => {
    console.log('Submission result:', response);
});
```

#### `GFEmbed.destroy(container)`
Remove a rendered form and clean up event listeners.

```javascript
GFEmbed.destroy('#form-container');
```

### Field Types Support

The SDK supports all standard Gravity Forms field types:

- **Text fields**: `text`, `textarea`, `email`, `url`, `phone`
- **Choice fields**: `select`, `multiselect`, `radio`, `checkbox`
- **Advanced fields**: `date`, `time`, `number`, `fileupload`
- **Pricing fields**: `product`, `option`, `quantity`, `total`
- **Layout fields**: `page`, `section`, `html`

## Authentication

### Domain-Based Authentication

Add allowed domains in form settings:

```javascript
// Domain must be in allowed list
GFEmbed.render({
    formId: 1,
    container: '#form',
    domain: 'yoursite.com'  // Must match allowed domain
});
```

### API Key Authentication

For enhanced security, enable API keys:

```javascript
GFEmbed.render({
    formId: 1,
    container: '#form',
    domain: 'yoursite.com',
    apiKey: 'gf_embed_abc123def456'  // Generated in form settings
});
```

### CSRF Protection

Enable CSRF tokens for additional security:

```javascript
// CSRF tokens are handled automatically by the SDK
// Manual implementation:
fetch('/wp-json/gf-embed/v1/csrf-token/1')
.then(response => response.json())
.then(data => {
    // Include token in submission
    const formData = {
        input_1: 'value',
        gf_csrf_token: data.token
    };
});
```

## Error Handling

### HTTP Status Codes

- `200`: Success
- `400`: Bad Request (validation errors)
- `401`: Unauthorized (invalid API key)
- `403`: Forbidden (domain not allowed, rate limited)
- `404`: Not Found (form doesn't exist)
- `429`: Too Many Requests (rate limit exceeded)
- `500`: Internal Server Error

### Error Response Format

```json
{
  "success": false,
  "message": "Human-readable error message",
  "code": "machine_readable_error_code",
  "data": {
    "additional": "error details"
  }
}
```

### Common Error Codes

- `form_not_found`: Form ID doesn't exist
- `form_disabled`: Form embedding is disabled
- `domain_not_allowed`: Origin domain not in whitelist
- `rate_limit_exceeded`: Too many requests
- `invalid_api_key`: API key is invalid or expired
- `validation_failed`: Form data validation errors
- `security_blocked`: Blocked by security filters

### JavaScript Error Handling

```javascript
GFEmbed.render({
    formId: 1,
    container: '#form',
    domain: 'yoursite.com',
    
    onError: function(error) {
        console.error('Form error:', error);
        
        switch(error.code) {
            case 'form_not_found':
                alert('Form not found. Please check the form ID.');
                break;
            case 'domain_not_allowed':
                alert('This domain is not authorized to embed this form.');
                break;
            case 'rate_limit_exceeded':
                alert('Too many requests. Please try again later.');
                break;
            default:
                alert('An error occurred loading the form.');
        }
    },
    
    onValidationError: function(errors) {
        console.log('Validation errors:', errors);
        // Handle field-specific errors
        Object.keys(errors).forEach(fieldId => {
            const fieldElement = document.getElementById('input_' + fieldId);
            if (fieldElement) {
                fieldElement.classList.add('error');
                // Show error message
            }
        });
    }
});
```

## Rate Limiting

### Default Limits

- **60 requests per minute** per IP address
- **20 form submissions per hour** per form
- **10 burst requests** allowed

### Rate Limit Headers

Responses include rate limiting information:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995200
X-RateLimit-Retry-After: 30
```

### Handling Rate Limits

```javascript
GFEmbed.render({
    formId: 1,
    container: '#form',
    domain: 'yoursite.com',
    
    onError: function(error) {
        if (error.code === 'rate_limit_exceeded') {
            const retryAfter = error.data.retry_after || 60;
            setTimeout(() => {
                // Retry form loading
                GFEmbed.render(/* same options */);
            }, retryAfter * 1000);
        }
    }
});
```

## Examples

### Basic Contact Form

```html
<!DOCTYPE html>
<html>
<head>
    <title>Contact Us</title>
</head>
<body>
    <h1>Get in Touch</h1>
    <div id="contact-form"></div>
    
    <script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
    <script>
    GFEmbed.render({
        formId: 1,
        container: '#contact-form',
        domain: 'yoursite.com',
        theme: 'material',
        
        onSuccess: function(response) {
            alert('Thank you for your message!');
        }
    });
    </script>
</body>
</html>
```

### Newsletter Signup with Analytics

```html
<div id="newsletter-form"></div>

<script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
<script>
GFEmbed.render({
    formId: 2,
    container: '#newsletter-form',
    domain: 'yoursite.com',
    theme: 'minimal',
    showTitle: false,
    
    customCSS: `
        .gf-embed-form {
            max-width: 400px;
            margin: 0 auto;
        }
        .gf-submit-button {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
    `,
    
    onSuccess: function(response) {
        // Track conversion in Google Analytics
        gtag('event', 'sign_up', {
            method: 'newsletter'
        });
        
        // Show success message
        document.getElementById('newsletter-form').innerHTML = 
            '<p>✅ Thanks for subscribing!</p>';
    }
});
</script>
```

### Multi-Step Form with Progress

```html
<div id="multi-step-form"></div>

<script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
<script>
GFEmbed.render({
    formId: 3,
    container: '#multi-step-form',
    domain: 'yoursite.com',
    theme: 'rounded',
    
    onLoad: function(form) {
        console.log('Multi-step form loaded with', form.pages, 'pages');
    },
    
    onPageChange: function(currentPage, totalPages) {
        // Update progress indicator
        const progress = (currentPage / totalPages) * 100;
        document.getElementById('progress-bar').style.width = progress + '%';
        
        // Track page views
        gtag('event', 'form_step', {
            form_id: 3,
            step: currentPage,
            total_steps: totalPages
        });
    },
    
    onSubmit: function(data) {
        // Show loading state
        document.getElementById('submit-button').disabled = true;
        document.getElementById('submit-button').textContent = 'Submitting...';
    },
    
    onSuccess: function(response) {
        // Track completion
        gtag('event', 'form_complete', {
            form_id: 3,
            entry_id: response.entry_id
        });
    }
});
</script>
```

### Form with File Upload

```html
<div id="upload-form"></div>

<script src="https://yoursite.com/gf-js-embed/v1/embed.js"></script>
<script>
GFEmbed.render({
    formId: 4,
    container: '#upload-form',
    domain: 'yoursite.com',
    apiKey: 'gf_embed_secure_key_123',
    
    onUploadProgress: function(fieldId, progress) {
        console.log(`Upload progress for field ${fieldId}: ${progress}%`);
        
        // Update progress bar
        const progressBar = document.getElementById(`progress-${fieldId}`);
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
    },
    
    onUploadComplete: function(fieldId, response) {
        console.log(`Upload complete for field ${fieldId}:`, response);
    },
    
    onError: function(error) {
        if (error.code === 'upload_failed') {
            alert('File upload failed. Please try again.');
        }
    }
});
</script>
```

### Conditional Form Loading

```javascript
// Load different forms based on user type
function loadUserForm() {
    // Get user type from your system
    const userType = getCurrentUserType();
    
    const formConfig = {
        'customer': { formId: 1, theme: 'default' },
        'partner': { formId: 2, theme: 'material' },
        'employee': { formId: 3, theme: 'minimal' }
    };
    
    const config = formConfig[userType] || formConfig['customer'];
    
    GFEmbed.render({
        ...config,
        container: '#dynamic-form',
        domain: 'yoursite.com',
        
        onLoad: function(form) {
            console.log(`Loaded ${userType} form:`, form.title);
        }
    });
}

// Load form when page is ready
document.addEventListener('DOMContentLoaded', loadUserForm);
```

For more examples and implementation details, see the [User Guide](../user-guide/README.md) and [Developer Reference](../developer/hooks-reference.md).