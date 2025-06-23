#!/bin/bash

# Release Script for Gravity Forms JavaScript Embed
# Usage: ./release.sh [version] [type]
# Examples:
#   ./release.sh patch         # Increments patch version (0.3.1 -> 0.3.2)
#   ./release.sh minor         # Increments minor version (0.3.1 -> 0.4.0)
#   ./release.sh major         # Increments major version (0.3.1 -> 1.0.0)
#   ./release.sh 0.3.2         # Sets specific version
#   ./release.sh 0.3.2 hotfix  # Sets version with hotfix type

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to display usage
usage() {
    echo "Usage: $0 [version|patch|minor|major] [type]"
    echo ""
    echo "Version options:"
    echo "  patch       - Increment patch version (x.y.Z)"
    echo "  minor       - Increment minor version (x.Y.z)"
    echo "  major       - Increment major version (X.y.z)"
    echo "  x.y.z       - Set specific version number"
    echo ""
    echo "Type options (optional):"
    echo "  feature     - New features added"
    echo "  bugfix      - Bug fixes (default)"
    echo "  hotfix      - Critical fixes"
    echo "  security    - Security updates"
    echo ""
    echo "Examples:"
    echo "  $0 patch"
    echo "  $0 minor feature"
    echo "  $0 1.0.0"
    exit 1
}

# Check if we're in the right directory
if [ ! -f "gravity-forms-js-embed.php" ]; then
    echo -e "${RED}Error: Must be run from the plugin root directory${NC}"
    exit 1
fi

# Check if git is clean
if [[ -n $(git status -s) ]]; then
    echo -e "${RED}Error: Working directory is not clean. Commit or stash changes first.${NC}"
    git status -s
    exit 1
fi

# Get current version from plugin file
CURRENT_VERSION=$(grep -o 'Version: [0-9.]*' gravity-forms-js-embed.php | cut -d' ' -f2)
echo -e "${GREEN}Current version: ${CURRENT_VERSION}${NC}"

# Parse arguments
VERSION_ARG=${1:-patch}
RELEASE_TYPE=${2:-bugfix}

# Function to increment version
increment_version() {
    local version=$1
    local position=$2
    
    IFS='.' read -ra PARTS <<< "$version"
    
    case $position in
        major)
            ((PARTS[0]++))
            PARTS[1]=0
            PARTS[2]=0
            ;;
        minor)
            ((PARTS[1]++))
            PARTS[2]=0
            ;;
        patch)
            ((PARTS[2]++))
            ;;
    esac
    
    echo "${PARTS[0]}.${PARTS[1]}.${PARTS[2]}"
}

# Determine new version
if [[ $VERSION_ARG =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    NEW_VERSION=$VERSION_ARG
elif [[ $VERSION_ARG == "major" ]] || [[ $VERSION_ARG == "minor" ]] || [[ $VERSION_ARG == "patch" ]]; then
    NEW_VERSION=$(increment_version $CURRENT_VERSION $VERSION_ARG)
else
    echo -e "${RED}Error: Invalid version argument${NC}"
    usage
fi

echo -e "${GREEN}New version: ${NEW_VERSION}${NC}"
echo -e "${GREEN}Release type: ${RELEASE_TYPE}${NC}"

# Confirm with user
echo ""
read -p "Continue with release v${NEW_VERSION}? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Release cancelled"
    exit 1
fi

# Pull latest changes
echo -e "\n${YELLOW}Pulling latest changes...${NC}"
git pull origin main

# Update version in plugin header
echo -e "\n${YELLOW}Updating plugin version...${NC}"
sed -i "s/Version: ${CURRENT_VERSION}/Version: ${NEW_VERSION}/" gravity-forms-js-embed.php
sed -i "s/define('GF_JS_EMBED_VERSION', '${CURRENT_VERSION}');/define('GF_JS_EMBED_VERSION', '${NEW_VERSION}');/" gravity-forms-js-embed.php

# Create changelog entry
echo -e "\n${YELLOW}Creating changelog entry...${NC}"
TODAY=$(date +%Y-%m-%d)

# Prepare changelog section based on type
case $RELEASE_TYPE in
    feature)
        SECTION="### Added\n- \n\n### Changed\n- \n\n### Fixed\n- "
        ;;
    security)
        SECTION="### Security\n- \n\n### Fixed\n- "
        ;;
    hotfix)
        SECTION="### Fixed\n- "
        ;;
    *)
        SECTION="### Fixed\n- \n\n### Changed\n- "
        ;;
esac

# Check if changelog entry already exists
if grep -q "## \[${NEW_VERSION}\]" CHANGELOG.md; then
    echo -e "${YELLOW}Changelog entry for v${NEW_VERSION} already exists${NC}"
else
    # Create temporary file with new changelog entry
    cat > /tmp/changelog_new.md << EOF
## [${NEW_VERSION}] - ${TODAY}

${SECTION}

EOF

    # Insert after the first occurrence of "## ["
    awk '/^## \[/ && !found {print ""; system("cat /tmp/changelog_new.md"); found=1} 1' CHANGELOG.md > /tmp/changelog_temp.md
    
    # If no existing version entries, add after the header
    if ! grep -q "^## \[" /tmp/changelog_temp.md; then
        awk '/^and this project adheres to/ {print; print ""; system("cat /tmp/changelog_new.md"); next} 1' CHANGELOG.md > /tmp/changelog_temp.md
    fi
    
    mv /tmp/changelog_temp.md CHANGELOG.md
    
    # Add version link at the bottom
    if [ "$CURRENT_VERSION" != "0.0.0" ]; then
        sed -i "/^\[${CURRENT_VERSION}\]:/i [${NEW_VERSION}]: https://github.com/jezweb/js-gravity-forms-embed/compare/v${CURRENT_VERSION}...v${NEW_VERSION}" CHANGELOG.md
    fi
    
    echo -e "${GREEN}Created changelog entry for v${NEW_VERSION}${NC}"
    echo -e "${YELLOW}Please edit CHANGELOG.md to add your changes${NC}"
fi

# Open editor for changelog
echo ""
read -p "Edit CHANGELOG.md now? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    ${EDITOR:-nano} CHANGELOG.md
fi

# Show changes
echo -e "\n${YELLOW}Changes to be committed:${NC}"
git diff --stat

# Commit changes
echo -e "\n${YELLOW}Committing changes...${NC}"
git add -A
git commit -m "Release v${NEW_VERSION}

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"

# Create tag
echo -e "\n${YELLOW}Creating git tag...${NC}"
git tag -a "v${NEW_VERSION}" -m "Release v${NEW_VERSION}"

# Push changes and tag
echo -e "\n${YELLOW}Pushing to GitHub...${NC}"
git push origin main
git push origin "v${NEW_VERSION}"

# Build the plugin
echo -e "\n${YELLOW}Building plugin...${NC}"
./build.sh

# Extract changelog for this version
echo -e "\n${YELLOW}Preparing release notes...${NC}"
RELEASE_NOTES=$(awk "/## \[${NEW_VERSION}\]/{flag=1; next} /## \[/{flag=0} flag" CHANGELOG.md | sed '/^$/d')

# Create GitHub release
echo -e "\n${YELLOW}Creating GitHub release...${NC}"
gh release create "v${NEW_VERSION}" \
    "dist/gravity-forms-js-embed-v${NEW_VERSION}.zip" \
    --title "v${NEW_VERSION}" \
    --notes "${RELEASE_NOTES}

### Installation
Download the ZIP file and upload it to your WordPress site via Plugins > Add New > Upload Plugin.

### Full Changelog
See [CHANGELOG.md](https://github.com/jezweb/js-gravity-forms-embed/blob/main/CHANGELOG.md#${NEW_VERSION//\./-}---${TODAY}) for complete details."

echo -e "\n${GREEN}✅ Release v${NEW_VERSION} completed successfully!${NC}"
echo -e "${GREEN}GitHub Release: https://github.com/jezweb/js-gravity-forms-embed/releases/tag/v${NEW_VERSION}${NC}"

# Cleanup
rm -f /tmp/changelog_new.md

# Optional: Open release page
echo ""
read -p "Open release page in browser? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    xdg-open "https://github.com/jezweb/js-gravity-forms-embed/releases/tag/v${NEW_VERSION}" 2>/dev/null || \
    open "https://github.com/jezweb/js-gravity-forms-embed/releases/tag/v${NEW_VERSION}" 2>/dev/null || \
    echo "Please open: https://github.com/jezweb/js-gravity-forms-embed/releases/tag/v${NEW_VERSION}"
fi