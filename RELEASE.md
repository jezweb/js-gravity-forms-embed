# Release Process Guide

This document outlines the release process for the Gravity Forms JavaScript Embed plugin.

## Quick Start

For most releases, simply run:
```bash
./release.sh patch    # For bug fixes (0.3.1 -> 0.3.2)
./release.sh minor    # For new features (0.3.1 -> 0.4.0)
./release.sh major    # For breaking changes (0.3.1 -> 1.0.0)
```

## Pre-Release Checklist

Before creating a release, ensure:

- [ ] All changes are committed and pushed
- [ ] You're on the `main` branch
- [ ] All tests pass (when available)
- [ ] Documentation is updated if needed
- [ ] CHANGELOG.md is ready to be updated

## Version Numbering

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (X.0.0): Breaking changes
- **MINOR** (0.X.0): New features, backwards compatible
- **PATCH** (0.0.X): Bug fixes, backwards compatible

### Examples:
- Bug fix: `0.3.1` → `0.3.2`
- New feature: `0.3.2` → `0.4.0`
- Breaking change: `0.4.0` → `1.0.0`

## Release Types

When running `./release.sh`, you can specify the release type:

```bash
./release.sh patch bugfix     # Default for patches
./release.sh minor feature    # For new features
./release.sh patch security   # For security fixes
./release.sh patch hotfix     # For critical fixes
```

## Manual Release Process

If you need to do a manual release:

1. **Update Version Numbers**
   ```bash
   # In gravity-forms-js-embed.php
   # Update both "Version:" header and GF_JS_EMBED_VERSION constant
   ```

2. **Update CHANGELOG.md**
   - Add new version section with date
   - List changes under appropriate headings
   - Add version comparison link at bottom

3. **Commit Changes**
   ```bash
   git add -A
   git commit -m "Release v0.3.2"
   git push origin main
   ```

4. **Create Tag**
   ```bash
   git tag v0.3.2
   git push origin v0.3.2
   ```

5. **Build Plugin**
   ```bash
   ./build.sh
   ```

6. **Create GitHub Release**
   ```bash
   gh release create v0.3.2 dist/gravity-forms-js-embed-v0.3.2.zip \
     --title "v0.3.2" \
     --notes "Release notes here"
   ```

## Changelog Format

Follow this format in CHANGELOG.md:

```markdown
## [0.3.2] - 2025-06-23

### Added
- New features or capabilities

### Changed
- Changes to existing functionality

### Fixed
- Bug fixes

### Security
- Security updates

### Deprecated
- Features to be removed in future

### Removed
- Features removed in this release
```

## Post-Release Verification

After releasing:

1. **Check GitHub Release**
   - Verify ZIP file is attached
   - Ensure release notes are correct
   - Check version tag is created

2. **Test Installation**
   - Download ZIP from release
   - Install on clean WordPress site
   - Verify version number shows correctly
   - Test basic functionality

3. **Update Documentation**
   - Update any version references
   - Check README compatibility info

## Troubleshooting

### Release Script Issues

**Script won't run:**
```bash
chmod +x release.sh
```

**Version already exists:**
- Delete the tag: `git tag -d v0.3.2`
- Delete remote tag: `git push origin :refs/tags/v0.3.2`

**Changelog conflicts:**
- Manually edit CHANGELOG.md
- Ensure proper format
- Commit and continue

### Build Issues

**ZIP file too large:**
- Check for unnecessary files
- Update .distignore if needed
- Clean build directory

**Missing files in ZIP:**
- Check build.sh copy commands
- Ensure files aren't in .distignore

## Release Schedule

We aim to follow this release cadence:

- **Patches**: As needed for bug fixes
- **Minor**: Monthly for new features
- **Major**: Annually or as needed

## Emergency Hotfix Process

For critical security issues:

1. Create fix on `hotfix` branch
2. Test thoroughly
3. Run: `./release.sh patch hotfix`
4. Notify users immediately

## WordPress.org Submission

When ready for WordPress.org:

1. Ensure all guidelines are met
2. Update readme.txt
3. Test with Plugin Check plugin
4. Submit via WordPress.org SVN

## Questions?

For questions about the release process, please open an issue on GitHub.