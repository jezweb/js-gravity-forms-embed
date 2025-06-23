# Troubleshooting Guide

This comprehensive guide helps you diagnose and resolve common issues with the Gravity Forms JavaScript Embed plugin.

## Table of Contents

- [Quick Diagnostic Steps](#quick-diagnostic-steps)
- [Common Issues](#common-issues)
- [Error Messages](#error-messages)
- [Testing Tools](#testing-tools)
- [Performance Issues](#performance-issues)
- [Security Issues](#security-issues)
- [Integration Problems](#integration-problems)
- [Getting Help](#getting-help)

## Quick Diagnostic Steps

Before diving into specific issues, run through these quick checks:

### 1. Plugin Health Check

Navigate to **Gravity Forms → JS Embed → Testing** in your WordPress admin to run comprehensive tests:

- System compatibility checks
- Form configuration validation
- API endpoint testing
- Security feature verification
- Performance benchmarks

### 2. Basic Requirements

Ensure your environment meets these requirements:

- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher
- **Gravity Forms:** 2.5 or higher
- **Memory Limit:** 256MB recommended
- **Max Execution Time:** 30 seconds minimum

### 3. Plugin Status

Check these indicators in your WordPress admin:

- ✅ Gravity Forms is active
- ✅ JS Embed plugin is active
- ✅ No PHP errors in debug log
- ✅ Form embed settings are configured

## Common Issues

### Forms Not Loading

**Symptoms:**
- Forms don't appear on external websites
- JavaScript console shows network errors
- Embed script returns 404 error

**Diagnosis:**
```bash
# Test embed script accessibility
curl -I https://yoursite.com/gf-js-embed/v1/embed.js

# Check API endpoint
curl -I https://yoursite.com/wp-json/gf-embed/v1/form/1
```

**Solutions:**

1. **Check Permalink Structure**
   - Go to Settings → Permalinks
   - Click "Save Changes" to flush rewrite rules
   - Test embed script URL again

2. **Verify Form Settings**
   - Edit your form in Gravity Forms
   - Go to Settings → JavaScript Embed
   - Ensure "Enable embedding" is checked
   - Verify allowed domains are configured

3. **Check .htaccess File**
   ```apache
   # Add to .htaccess if needed
   RewriteRule ^gf-js-embed/v1/embed\.js$ /index.php?gf_js_embed=1 [QSA,L]
   ```

### CORS Errors

**Symptoms:**
- Console error: "Access to fetch at ... has been blocked by CORS policy"
- Forms load but submissions fail
- Network tab shows CORS preflight failures

**Diagnosis:**
```javascript
// Test CORS in browser console
fetch('https://yoursite.com/wp-json/gf-embed/v1/form/1', {
    method: 'GET',
    headers: {
        'Origin': 'https://external-site.com'
    }
}).then(response => console.log(response.status));
```

**Solutions:**

1. **Configure Allowed Domains**
   - Edit form → Settings → JavaScript Embed
   - Add domains to "Allowed Domains" field
   - Use format: `domain.com` (without protocol)
   - For testing, temporarily add `*` for all domains

2. **Check Server Configuration**
   ```apache
   # Add to .htaccess if server strips CORS headers
   Header always set Access-Control-Allow-Origin "*"
   Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
   Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
   ```

3. **Verify Plugin CORS Logic**
   ```php
   // Add to functions.php for debugging
   add_action('wp_head', function() {
       if (defined('WP_DEBUG') && WP_DEBUG) {
           echo "<!-- CORS Debug: Origin = " . ($_SERVER['HTTP_ORIGIN'] ?? 'none') . " -->\n";
       }
   });
   ```

### Form Submission Failures

**Symptoms:**
- Forms display correctly but don't submit
- Validation errors don't appear
- Submissions don't appear in Gravity Forms entries

**Diagnosis:**
```javascript
// Check submission endpoint in browser console
fetch('https://yoursite.com/wp-json/gf-embed/v1/submit/1', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        input_1: 'test value'
    })
}).then(response => response.json()).then(data => console.log(data));
```

**Solutions:**

1. **Check Form Field Mapping**
   - Verify field IDs match between form and submission data
   - Use browser developer tools to inspect form HTML
   - Check field naming convention: `input_1`, `input_2`, etc.

2. **Validate Required Fields**
   - Ensure all required fields are included in submission
   - Check field validation rules in Gravity Forms
   - Test with minimal data first

3. **Review Security Settings**
   - Check if rate limiting is blocking submissions
   - Verify API key requirements (if enabled)
   - Review honeypot field configuration

### Styling Issues

**Symptoms:**
- Forms don't match website design
- CSS conflicts with existing styles
- Mobile responsiveness problems

**Solutions:**

1. **Custom CSS Override**
   ```css
   /* Add to your theme's CSS */
   .gf-embed-form {
       font-family: inherit !important;
   }
   
   .gf-embed-form input,
   .gf-embed-form textarea {
       border: 1px solid #ddd !important;
       padding: 10px !important;
   }
   ```

2. **Theme Selection**
   - Edit form → Settings → JavaScript Embed
   - Try different themes: Default, Minimal, Rounded, Material
   - Use "Custom" theme for complete control

3. **CSS Specificity Issues**
   ```css
   /* Increase specificity if needed */
   body .gf-embed-form .gf-field input {
       /* Your styles here */
   }
   ```

## Error Messages

### "Sorry, you are not allowed to access this page"

**Cause:** User permission issues accessing form settings

**Solution:**
1. Ensure you have `gravityforms_edit_forms` capability
2. Check user role permissions
3. Verify Gravity Forms is properly activated

### "Form not found"

**Cause:** Form ID doesn't exist or is inactive

**Solution:**
1. Verify form ID in Gravity Forms admin
2. Check if form is active (not in trash)
3. Ensure form ID in embed code matches

### "Domain not allowed"

**Cause:** CORS restriction blocking external domain

**Solution:**
1. Add domain to allowed domains list
2. Check domain format (no protocol, no trailing slash)
3. Verify case sensitivity of domain names

### "Rate limit exceeded"

**Cause:** Too many requests from same IP/domain

**Solution:**
1. Review rate limiting settings
2. Implement proper caching on frontend
3. Contact administrator to adjust limits

### "Invalid API key"

**Cause:** API key mismatch or malformed

**Solution:**
1. Regenerate API key in form settings
2. Verify API key is passed correctly in headers
3. Check for extra whitespace or characters

## Testing Tools

### Built-in Testing Dashboard

Access **Gravity Forms → JS Embed → Testing** for:

- System health checks
- Form configuration validation  
- API endpoint testing
- Security verification
- Performance monitoring

### Browser Developer Tools

**Network Tab:**
- Monitor API requests and responses
- Check status codes and response times
- Identify CORS issues

**Console Tab:**
- View JavaScript errors
- Test API calls manually
- Debug form submission data

### Command Line Testing

```bash
# Test form endpoint
curl -H "Origin: https://external-site.com" \
     "https://yoursite.com/wp-json/gf-embed/v1/form/1"

# Test submission endpoint
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Origin: https://external-site.com" \
     -d '{"input_1":"test"}' \
     "https://yoursite.com/wp-json/gf-embed/v1/submit/1"

# Check embed script
curl -I "https://yoursite.com/gf-js-embed/v1/embed.js"
```

## Performance Issues

### Slow Form Loading

**Symptoms:**
- Forms take several seconds to appear
- Multiple network requests visible
- Poor user experience

**Diagnosis:**
```javascript
// Measure load time
console.time('form-load');
// ... after form loads
console.timeEnd('form-load');
```

**Solutions:**

1. **Enable Caching**
   ```javascript
   // Add cache headers to embed script
   // This is handled automatically by the plugin
   ```

2. **Optimize API Responses**
   - Remove unnecessary form fields
   - Simplify conditional logic
   - Reduce form description length

3. **Use CDN**
   - Serve embed script through CDN
   - Cache API responses when possible

### High Server Load

**Symptoms:**
- Slow WordPress admin
- API timeouts
- Database connection errors

**Solutions:**

1. **Database Optimization**
   ```sql
   -- Add indexes for analytics queries
   ALTER TABLE wp_gf_embed_analytics 
   ADD INDEX idx_form_date (form_id, created_at);
   ```

2. **Rate Limiting**
   - Reduce rate limits for non-essential requests
   - Implement progressive backoff
   - Use authentication for higher limits

3. **Monitoring**
   ```php
   // Add to wp-config.php for monitoring
   define('WP_DEBUG_LOG', true);
   define('SAVEQUERIES', true);
   ```

## Security Issues

### Spam Submissions

**Symptoms:**
- High volume of low-quality entries
- Suspicious submission patterns
- Performance degradation

**Solutions:**

1. **Enable Security Features**
   - Honeypot fields (automatic)
   - Rate limiting (configure per form)
   - Domain restrictions (whitelist)

2. **Custom Validation**
   ```php
   add_filter('gf_js_embed_submission_data', function($data, $form, $request) {
       // Custom spam detection logic
       if (strlen($data['input_1']) < 5) {
           wp_die('Submission blocked', 'Security', ['response' => 403]);
       }
       return $data;
   }, 10, 3);
   ```

### Unauthorized Access

**Symptoms:**
- Forms loading on restricted domains
- Submissions from unexpected sources
- Security violation logs

**Solutions:**

1. **Strict Domain Control**
   - Remove wildcard (*) from allowed domains
   - Use specific subdomains only
   - Regular audit of domain list

2. **API Key Authentication**
   - Enable API keys for sensitive forms
   - Rotate keys regularly
   - Monitor key usage

## Integration Problems

### WordPress Multisite

**Issues:**
- Plugin not working on subsites
- Settings not saved properly
- Network activation problems

**Solutions:**

1. **Network Activation**
   ```php
   // Add network-specific configuration
   if (is_multisite()) {
       // Handle multisite-specific logic
   }
   ```

2. **Database Tables**
   - Ensure tables created on each subsite
   - Check table prefixes are correct

### Theme Conflicts

**Issues:**
- JavaScript errors from theme
- CSS conflicts
- Form not rendering

**Solutions:**

1. **Theme Testing**
   - Switch to default theme temporarily
   - Test with theme's default settings
   - Check for jQuery conflicts

2. **Script Dependencies**
   ```php
   // Ensure proper script loading
   wp_enqueue_script('jquery');
   ```

### Plugin Conflicts

**Common Conflicts:**
- Security plugins blocking API
- Caching plugins interfering
- Form builders causing conflicts

**Debugging Steps:**

1. **Plugin Deactivation Test**
   - Deactivate all other plugins
   - Test form embedding
   - Reactivate plugins one by one

2. **Common Plugin Issues**
   ```php
   // Exclude from security plugins
   add_filter('wordfence_ls_exclude_urls', function($exclusions) {
       $exclusions[] = '/wp-json/gf-embed/';
       return $exclusions;
   });
   ```

## Getting Help

### Before Requesting Support

1. **Run the Testing Dashboard**
   - Go to JS Embed → Testing
   - Run all test categories
   - Screenshot any failed tests

2. **Gather Information**
   - WordPress version
   - Gravity Forms version
   - Plugin version
   - PHP version
   - Error messages (exact text)
   - Steps to reproduce

3. **Check Recent Changes**
   - Recent plugin updates
   - Theme changes
   - Server configuration changes
   - New plugin installations

### Support Channels

1. **GitHub Issues** (Preferred)
   - Create detailed issue report
   - Include test results and screenshots
   - Tag with appropriate labels

2. **WordPress Support Forums**
   - Search existing topics first
   - Provide complete system information
   - Follow up with additional details

### Creating Effective Bug Reports

Include these details:

```
**Environment:**
- WordPress: 6.x.x
- Gravity Forms: 2.x.x
- JS Embed Plugin: 0.x.x
- PHP: 8.x.x
- Server: Apache/Nginx

**Issue:**
Brief description of the problem

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Result:**
What should happen

**Actual Result:**
What actually happens

**Testing Results:**
[Paste results from Testing Dashboard]

**Additional Info:**
- Error messages
- Console logs
- Network requests
```

### Emergency Support

For critical production issues:

1. **Temporary Workaround**
   - Disable problematic forms
   - Switch to iframe embedding
   - Restore from backup if needed

2. **Quick Fixes**
   - Flush permalinks
   - Clear all caches
   - Check .htaccess file

3. **Rollback Options**
   - Downgrade to previous plugin version
   - Restore database backup
   - Switch to Gravity Forms standard embedding

Remember: Most issues can be resolved quickly with proper diagnosis. Use the built-in testing tools and follow the systematic troubleshooting approach outlined in this guide.