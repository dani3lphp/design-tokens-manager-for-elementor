# Design Tokens Manager for Elementor

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/design-tokens-manager-for-elementor)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![WordPress Plugin Required PHP Version](https://img.shields.io/wordpress/plugin/required-php/design-tokens-manager-for-elementor)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/design-tokens-manager-for-elementor)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/design-tokens-manager-for-elementor)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

> Take full control of your Elementor design system. Effortlessly manage Global Colors and Fonts with perfect ID consistency, smart `clamp()` sizing support, and real-time sync to Site Settings.

---

## 📖 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
- [Installation](#-installation)
- [Quick Start Guide](#-quick-start-guide)
- [Usage Examples](#-usage-examples)
- [Advanced Features](#-advanced-features)
- [WP-CLI Commands](#-wp-cli-commands)
- [Import/Export](#-importexport)
- [Requirements](#-requirements)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [Support](#-support)
- [License](#-license)

---

## 🎯 Overview

Design Tokens Manager for Elementor is a powerful WordPress plugin that streamlines the management of Elementor's Global Colors and Typography. Whether you're maintaining a design system, working with multiple brands, or simply want better control over your site's visual tokens, this plugin provides the tools you need.

### Why Use This Plugin?

- ✅ **Bulk Operations**: Add dozens of colors or fonts at once instead of one-by-one
- ✅ **Fluid Typography**: Full support for CSS `clamp()` functions for responsive sizing
- ✅ **ID Preservation**: Maintains token IDs across sync operations to prevent broken references
- ✅ **Two-Way Sync**: Pull tokens from Elementor or push your managed tokens back
- ✅ **Import/Export**: Backup, share, or migrate your design tokens as JSON
- ✅ **Developer Tools**: WP-CLI commands for automation and CI/CD workflows

---

## 🌟 Key Features

### 🎨 **Comprehensive Color Management**
- Visual color picker with hex code display
- Bulk paste: `TokenName: #HexCode` format
- Real-time color preview swatches
- Automatic hex validation and sanitization

### 🔤 **Advanced Typography Control**
- Font family with fallback stacks support
- Font size with `px`, `rem`, `em`, or `clamp()` values
- Font weight (100-900)
- Line height in `em` units
- Bulk paste: `Name: Family, Size, Weight, LineHeight`

### 🔄 **Smart Synchronization**
- **Pull from Elementor**: Import existing tokens from Site Settings
- **Push to Elementor**: Apply your managed tokens to Site Settings
- ID-aware merging prevents duplicates
- Queue-based processing for reliability
- Preserves unknown Elementor properties

### 📦 **Import/Export System**
- Export to JSON format (all tokens or current section)
- Import with merge or replace modes
- ID preservation option for consistent references
- 2MB file size limit for security
- Automatic validation of JSON structure

### 👨‍💻 **Developer Features**
- WP-CLI integration for automation
- Direct Kit meta access for edge cases
- Clean, documented, WordPress-standard code
- Hooks and filters for extensibility

### 💡 **User Experience**
- Tab persistence across page loads and form submissions
- Intuitive table-based interface
- Bulk actions (select all, delete selected)
- Inline editing with instant validation
- Section-based organization (Colors/Fonts)

---

## 🚀 Installation

### Method 1: WordPress Admin (Recommended)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Search for "Design Tokens Manager for Elementor"
4. Click **Install Now**, then **Activate**
5. Access via **Elementor → Design Tokens** in the admin menu

### Method 2: Manual Upload

1. Download the plugin ZIP file from [WordPress.org](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 3: FTP Upload

1. Download and extract the plugin ZIP file
2. Upload the `design-tokens-manager-for-elementor` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

---

## 🎓 Quick Start Guide

### Step 1: Access the Plugin
Navigate to **Elementor → Design Tokens** in your WordPress admin menu.

### Step 2: Choose Your Section
- Click **Colors** or **Fonts** tabs to manage different token types
- Your last viewed section is remembered automatically

### Step 3: Add Tokens

#### Option A: Add Individual Tokens
1. Click **Add Row** button
2. Fill in the token details:
   - **Colors**: Token name and hex color
   - **Fonts**: Token name, family, size, weight, line height
3. Click **Save Tokens**

#### Option B: Bulk Paste (Faster!)
1. Prepare your tokens in a text editor:
   ```
   Primary: #FF5733
   Secondary: #00A8E8
   Accent: #4CAF50
   ```
2. Paste into the **Bulk Paste** textarea
3. Click **Save Tokens**

### Step 4: Sync with Elementor
1. Click the **Sync** tab
2. Choose **Pull from Elementor** to import existing tokens
3. Or choose **Push to Elementor** to apply your managed tokens

---

## 📝 Usage Examples

### Example 1: Adding Brand Colors

**Individual Entry:**
1. Click **Add Row**
2. Token: `Brand Primary`, Color: `#1E88E5`
3. Click **Add Row** again
4. Token: `Brand Secondary`, Color: `#FFC107`
5. Click **Save Tokens**

**Bulk Entry:**
```
Brand Primary: #1E88E5
Brand Secondary: #FFC107
Brand Accent: #4CAF50
Brand Dark: #263238
Brand Light: #ECEFF1
```

### Example 2: Responsive Typography with Clamp

```
Heading XL: Inter, clamp(2.5rem, 2rem + 2vw, 4rem), 700, 1.2em
Heading L: Inter, clamp(2rem, 1.5rem + 1.5vw, 3rem), 700, 1.3em
Heading M: Inter, clamp(1.5rem, 1.25rem + 1vw, 2rem), 600, 1.3em
Body: Roboto, 1rem, 400, 1.6em
Small: Roboto, 0.875rem, 400, 1.5em
```

### Example 3: Font Stacks with Fallbacks

```
Heading Font: "Playfair Display", Georgia, serif, 2rem, 700, 1.2em
Body Font: "Open Sans", -apple-system, BlinkMacSystemFont, sans-serif, 1rem, 400, 1.6em
Mono Font: "Fira Code", Consolas, Monaco, monospace, 0.9rem, 400, 1.4em
```

### Example 4: System Font Stack

```
System UI: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif, 1rem, 400, 1.5em
```

---

## 🔧 Advanced Features

### Fluid Typography with clamp()

The plugin fully supports CSS `clamp()` function for fluid, responsive typography:

```css
clamp(minimum, preferred, maximum)
```

**Example:**
```
Hero Title: Inter, clamp(3rem, 2.5rem + 2vw, 5rem), 900, 1.1em
```

This creates a font size that:
- Never goes below `3rem`
- Scales fluidly based on viewport width (`2.5rem + 2vw`)
- Never exceeds `5rem`

### Token ID Preservation

The plugin intelligently manages token IDs to maintain consistency:

- **First Save**: Generates new unique IDs
- **Subsequent Saves**: Preserves existing IDs
- **Pull from Elementor**: Imports IDs from Site Settings
- **Push to Elementor**: Matches by name, preserves existing IDs

This prevents broken references in your Elementor designs.

### Merge vs. Replace Modes

**Merge Mode** (Default):
- Combines imported tokens with existing ones
- Updates matching tokens (by name)
- Adds new tokens
- Preserves IDs when possible

**Replace Mode**:
- Clears existing tokens in the section
- Imports only the new tokens
- Useful for complete design system overhauls

### Queue-Based Sync

If the plugin can't immediately sync with Elementor (due to loading order), it queues the operation:

1. Tokens are saved to WordPress options
2. Operation is queued for processing
3. Sync happens on next admin page load
4. You're notified when sync completes

---

## ⚡ WP-CLI Commands

Automate token management from the command line:

### Export Tokens

```bash
# Export all tokens (colors and fonts)
wp elementor-tokens export

# Export specific section
wp elementor-tokens export --source=colors
wp elementor-tokens export --source=fonts

# Export with custom filename
wp elementor-tokens export --output=my-tokens.json

# Export to stdout (pipe to other commands)
wp elementor-tokens export --stdout
```

### Import Tokens

```bash
# Import from file (merge mode)
wp elementor-tokens import tokens.json

# Import with replace mode
wp elementor-tokens import tokens.json --mode=replace

# Import preserving IDs
wp elementor-tokens import tokens.json --preserve-ids

# Import from URL
wp elementor-tokens import https://example.com/tokens.json
```

### Sync Operations

```bash
# Push local tokens to Elementor Site Settings
wp elementor-tokens sync --direction=push

# Pull tokens from Elementor Site Settings
wp elementor-tokens sync --direction=pull
```

### Automation Examples

**Daily Backup:**
```bash
#!/bin/bash
DATE=$(date +%Y-%m-%d)
wp elementor-tokens export --output="backups/tokens-$DATE.json"
```

**CI/CD Deployment:**
```bash
# After deployment, import design tokens
wp elementor-tokens import design-tokens.json --mode=replace
wp elementor-tokens sync --direction=push
```

---

## 📦 Import/Export

### Export Format

Exported JSON structure:
```json
{
  "colors": [
    {
      "id": "edtmcol_abc123",
      "title": "Primary",
      "color": "#1E88E5"
    }
  ],
  "fonts": [
    {
      "id": "edtmtyp_xyz789",
      "title": "Heading",
      "family": "Inter",
      "size": "2rem",
      "weight": 700,
      "line_height": "1.2em"
    }
  ]
}
```

### Import Options

**Source Selection:**
- **All**: Import both colors and fonts
- **Colors Only**: Import only color tokens
- **Fonts Only**: Import only typography tokens

**Mode Selection:**
- **Merge**: Combine with existing tokens
- **Replace**: Clear section before importing

**ID Preservation:**
- **On**: Use IDs from import file (recommended for backups/migrations)
- **Off**: Generate new IDs (useful for duplicating tokens)

### Import Limits

- Maximum file size: 2MB
- Accepted format: JSON only
- Automatic validation of structure

---

## 🛠️ Requirements

### Minimum Requirements
- **WordPress**: 5.6 or higher
- **PHP**: 7.0 or higher
- **Elementor**: Any version (Free or Pro)

### Recommended Environment
- **WordPress**: 6.0+
- **PHP**: 8.0+
- **Elementor**: Latest version
- **MySQL**: 5.7+ or MariaDB 10.3+

### Browser Support
- Chrome/Edge (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)

---

## 🔍 Troubleshooting

### Tokens Not Appearing in Elementor

**Solution:**
1. Go to **Sync** tab
2. Click **Push to Elementor**
3. Refresh the Elementor editor
4. Clear Elementor cache: **Elementor → Tools → Regenerate CSS**

### Import Fails

**Common Causes:**
- File too large (>2MB): Split into smaller files
- Invalid JSON format: Validate at [jsonlint.com](https://jsonlint.com)
- Wrong file type: Ensure `.json` extension

### Section Not Persisting

**Solution:**
- Clear browser cache
- Check if JavaScript errors exist (browser console)
- Try a different browser
- Disable conflicting plugins temporarily

### Clamp() Not Working

**Requirements:**
- Browser must support CSS `clamp()` (all modern browsers do)
- Elementor must be recent version
- Try clearing Elementor cache

### WP-CLI Not Found

**Installation:**
```bash
# Install WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Verify
wp --info
```

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### Reporting Bugs

1. Check [existing issues](https://github.com/dani3lphp/design-tokens-manager-for-elementor/issues)
2. Create a new issue with:
   - Clear description
   - Steps to reproduce
   - Expected vs actual behavior
   - WordPress/PHP/Elementor versions
   - Screenshots if applicable

### Submitting Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Follow WordPress coding standards
4. Test thoroughly
5. Commit changes: `git commit -m 'Add amazing feature'`
6. Push to branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use meaningful variable and function names
- Add inline comments for complex logic
- Write DocBlocks for functions
- Test with PHP 7.0+ and latest WordPress

### Development Setup

```bash
# Clone repository
git clone https://github.com/dani3lphp/design-tokens-manager-for-elementor.git

# Install development tools (optional)
composer install

# Run PHPCS (if configured)
phpcs --standard=WordPress .

# Run PHP syntax check
find . -name "*.php" -exec php -l {} \;
```

---

## 🆘 Support

### WordPress.org Support Forum
For general questions and community support:
- [Plugin Support Forum](https://wordpress.org/support/plugin/design-tokens-manager-for-elementor/)

### GitHub Issues
For bug reports and feature requests:
- [GitHub Issues](https://github.com/dani3lphp/design-tokens-manager-for-elementor/issues)

### Before Requesting Support

Please provide:
- WordPress version
- PHP version
- Elementor version (Free/Pro)
- Plugin version
- Steps to reproduce the issue
- Screenshots if applicable
- Browser console errors (if any)

### Response Time
- Community support: 1-3 business days
- Bug reports: 1-5 business days
- Feature requests: Varies based on priority

---

## 📜 License

This plugin is licensed under the **GNU General Public License v2 or later**.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE) file for full details.

---

## 🙏 Acknowledgments

- Built with ❤️ for the Elementor community
- Inspired by design token standards and modern design systems
- Thanks to all contributors, testers, and users
- Special thanks to the WordPress and Elementor teams

---

## 📊 Project Stats

- **Version**: 1.5.1
- **Active Installations**: Check [WordPress.org](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
- **Rating**: Check [WordPress.org](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
- **Last Updated**: 2024

---

## 🔗 Links

- [WordPress.org Plugin Page](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
- [GitHub Repository](https://github.com/dani3lphp/design-tokens-manager-for-elementor)
- [Report Issues](https://github.com/dani3lphp/design-tokens-manager-for-elementor/issues)
- [Author: Daniel Gabriel Lupu](https://github.com/dani3lphp)

---

## 📝 Changelog

### 1.5.1 - 2024
- Fixed undefined variable bug in admin interface
- Enhanced XSS security in font family sanitization
- Added 2MB file upload limit for security
- Improved internationalization with translators comments
- Created languages directory for translations
- Enhanced input sanitization with wp_unslash()
- Optimized performance (added phpcs:ignore for acceptable slow query)
- Better error messages with specific codes
- WordPress.org Plugin Check compliance verified

### 1.5.0 - 2024
- Initial public release
- Bulk color and font management
- Full clamp() support for fluid typography
- Import/Export functionality
- WP-CLI integration
- Two-way sync with Elementor Site Settings

---

**Made with ❤️ by [Daniel Gabriel Lupu](https://github.com/dani3lphp)**

*If you find this plugin helpful, please consider [leaving a review](https://wordpress.org/support/plugin/design-tokens-manager-for-elementor/reviews/) on WordPress.org!*
