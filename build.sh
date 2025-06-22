#!/bin/bash
#
# Build script for Gravity Forms JS Embed
# Creates a release-ready ZIP file
#

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Plugin details
PLUGIN_SLUG="gravity-forms-js-embed"
VERSION=$(grep "Version:" gravity-forms-js-embed.php | sed 's/.*Version: *//')

echo -e "${GREEN}Building Gravity Forms JS Embed v${VERSION}${NC}"
echo "======================================"

# Create build directory
BUILD_DIR="build/${PLUGIN_SLUG}"
DIST_DIR="dist"

# Clean previous builds
echo "Cleaning previous builds..."
rm -rf build/
rm -rf dist/
mkdir -p ${BUILD_DIR}
mkdir -p ${DIST_DIR}

# Copy files
echo "Copying plugin files..."
# PHP files
cp -r includes/ ${BUILD_DIR}/
cp gravity-forms-js-embed.php ${BUILD_DIR}/
cp uninstall.php ${BUILD_DIR}/

# Assets
cp -r assets/ ${BUILD_DIR}/

# Documentation
cp README.md ${BUILD_DIR}/
cp CHANGELOG.md ${BUILD_DIR}/

# Create languages directory
mkdir -p ${BUILD_DIR}/languages

# Create .distignore
cat > ${BUILD_DIR}/.distignore << 'EOF'
# Directories
.git
.github
node_modules
tests
build
dist

# Files
.gitignore
.distignore
.editorconfig
.DS_Store
Thumbs.db
composer.json
composer.lock
package.json
package-lock.json
phpunit.xml
*.log
*.sql
*.tar.gz
*.zip
build.sh
CONTRIBUTING.md

# Development files
*.map
*.scss
src/

# Editor files
*.swp
*.swo
*~
.idea
.vscode
EOF

# Create readme.txt for WordPress.org
echo "Creating readme.txt..."
cat > ${BUILD_DIR}/readme.txt << 'EOF'
=== Gravity Forms JavaScript Embed ===
Contributors: jezweb
Tags: gravity forms, embed, javascript, forms, remote
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed Gravity Forms on any website using JavaScript. No iframes required!

== Description ==

Gravity Forms JavaScript Embed allows you to embed your Gravity Forms on any website, including static sites, SPAs, and different domains. It provides a secure, performant way to collect form submissions without iframes.

= Features =

* **JavaScript SDK** - Simple embed code for any website
* **Cross-Domain Support** - Embed forms on different domains
* **Security Features** - API keys, rate limiting, domain whitelisting
* **Advanced Field Support** - All Gravity Forms field types
* **Touch Support** - Mobile-friendly signature fields
* **Real-Time Validation** - Client-side and server-side validation
* **Analytics Tracking** - Track views and submissions by domain
* **Customizable Styling** - Multiple themes and custom CSS support
* **REST API** - Modern API architecture
* **Performance Optimized** - Minimal load on your server

= Use Cases =

* Embed forms on static sites (GitHub Pages, Netlify, etc.)
* Add forms to Single Page Applications (React, Vue, Angular)
* Share forms across multiple WordPress sites
* Collect submissions from marketing landing pages
* Integrate forms into mobile apps

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/gravity-forms-js-embed`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Forms > Settings > JavaScript Embed
4. Configure your allowed domains and security settings
5. Copy the embed code for your forms

= Requirements =

* WordPress 5.0 or higher
* Gravity Forms 2.5 or higher
* PHP 7.2 or higher
* SSL certificate (recommended for security)

== Frequently Asked Questions ==

= How do I embed a form? =

1. Go to Forms > Settings > JavaScript Embed
2. Find your form and click "Get Embed Code"
3. Copy the provided JavaScript snippet
4. Paste it into your external website

= Is it secure? =

Yes! The plugin includes multiple security features:
* API key authentication
* Domain whitelisting
* Rate limiting
* CSRF protection
* Honeypot fields
* Bot detection

= Can I customize the styling? =

Yes, you can:
* Choose from pre-built themes
* Add custom CSS
* Override styles in your site's CSS
* Use CSS variables for theming

= Does it work with conditional logic? =

Yes, all Gravity Forms features are supported including:
* Conditional logic
* Calculations
* Multi-page forms
* File uploads
* Payment fields

= What about GDPR compliance? =

The plugin respects all Gravity Forms privacy settings and includes:
* No data stored on external sites
* All submissions go directly to your WordPress site
* Full control over data retention
* Compatible with privacy plugins

== Screenshots ==

1. Admin settings page with domain configuration
2. Embed code generator
3. Example of embedded form on external site
4. Analytics dashboard showing form performance
5. Security settings and API key management

== Changelog ==

= 1.0.0 =
* Initial release
* JavaScript SDK with full field support
* Security features (API keys, rate limiting)
* Analytics tracking
* Multiple theme support
* Touch-enabled signature fields
* REST API implementation

== Upgrade Notice ==

= 1.0.0 =
Initial release - no upgrade notices yet.

== Developer Documentation ==

= Available Hooks =

**Filters:**
* `gf_js_embed_allowed_domains` - Modify allowed domains
* `gf_js_embed_rate_limit` - Adjust rate limiting
* `gf_js_embed_form_data` - Filter form data before sending
* `gf_js_embed_submission_data` - Filter submission data

**Actions:**
* `gf_js_embed_form_loaded` - Fired when form is loaded via API
* `gf_js_embed_submission_success` - Fired on successful submission
* `gf_js_embed_submission_failed` - Fired on failed submission

= REST API Endpoints =

* `GET /wp-json/gf-embed/v1/form/{id}` - Retrieve form
* `POST /wp-json/gf-embed/v1/submit/{id}` - Submit form
* `GET /wp-json/gf-embed/v1/assets/{id}` - Get form assets

= JavaScript Events =

* `gfEmbedFormReady` - Form loaded and ready
* `gfEmbedSubmitSuccess` - Successful submission
* `gfEmbedSubmitError` - Submission error
* `gfEmbedValidationError` - Validation failed

== Support ==

For support, please visit [github.com/jezweb/js-gravity-forms-embed](https://github.com/jezweb/js-gravity-forms-embed)
EOF

# Minify JavaScript (if uglify-js is available)
if command -v uglifyjs &> /dev/null; then
    echo "Minifying JavaScript..."
    uglifyjs ${BUILD_DIR}/assets/js/gf-embed-sdk.js -o ${BUILD_DIR}/assets/js/gf-embed-sdk.min.js -c -m
else
    echo -e "${YELLOW}Warning: uglifyjs not found. Skipping minification.${NC}"
    cp ${BUILD_DIR}/assets/js/gf-embed-sdk.js ${BUILD_DIR}/assets/js/gf-embed-sdk.min.js
fi

# Create ZIP file
ZIP_NAME="${PLUGIN_SLUG}-v${VERSION}.zip"
echo "Creating ZIP archive: ${ZIP_NAME}"
cd build/
zip -r "../${DIST_DIR}/${ZIP_NAME}" ${PLUGIN_SLUG}/ -x "*.DS_Store" "*__MACOSX*"
cd ..

# Create version info file
echo "Creating version info..."
cat > ${DIST_DIR}/version-info.json << EOF
{
    "name": "Gravity Forms JavaScript Embed",
    "slug": "${PLUGIN_SLUG}",
    "version": "${VERSION}",
    "download_url": "https://github.com/jezweb/js-gravity-forms-embed/releases/download/v${VERSION}/${ZIP_NAME}",
    "build_date": "$(date -u +"%Y-%m-%d %H:%M:%S UTC")",
    "file_size": "$(du -h ${DIST_DIR}/${ZIP_NAME} | cut -f1)",
    "php_required": "7.2",
    "wp_required": "5.0",
    "tested_up_to": "6.4"
}
EOF

# Generate checksums
echo "Generating checksums..."
cd ${DIST_DIR}
sha256sum ${ZIP_NAME} > ${ZIP_NAME}.sha256
md5sum ${ZIP_NAME} > ${ZIP_NAME}.md5
cd ..

# Final summary
echo ""
echo -e "${GREEN}Build completed successfully!${NC}"
echo "======================================"
echo "Version: ${VERSION}"
echo "ZIP File: ${DIST_DIR}/${ZIP_NAME}"
echo "Size: $(du -h ${DIST_DIR}/${ZIP_NAME} | cut -f1)"
echo ""
echo "Files included:"
find ${BUILD_DIR} -type f | wc -l
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Test the ZIP file by installing it on a fresh WordPress site"
echo "2. Create a GitHub release and upload ${DIST_DIR}/${ZIP_NAME}"
echo "3. Submit to WordPress.org repository (if desired)"

# Cleanup build directory (keep dist)
# rm -rf build/