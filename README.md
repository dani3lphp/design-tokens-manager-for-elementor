# 🎨 Design Tokens Manager for Elementor

**Manage your Elementor design system like a pro!** Bulk edit colors and fonts, use fluid typography with `clamp()`, and keep your design tokens in perfect sync.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/design-tokens-manager-for-elementor.svg)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/design-tokens-manager-for-elementor.svg)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/design-tokens-manager-for-elementor.svg)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](LICENSE)

---

## 🚀 What Does This Plugin Do?

If you use Elementor and want to manage your colors and fonts more efficiently, this plugin is for you! Instead of clicking through dozens of menus, you can:

- **Paste multiple colors at once** - Add 10 colors in seconds, not minutes
- **Bulk edit fonts** - Update all your typography in one go
- **Use responsive font sizes** - Support for modern CSS `clamp()` function
- **Sync with Elementor** - Two-way sync between your tokens and Elementor's Site Settings
- **Export/Import** - Backup your design system or move it between sites
- **Automate with WP-CLI** - Perfect for developers and agencies

---

## ✨ Key Features

### 🎯 Bulk Editing
Stop clicking one color at a time! Paste multiple tokens in this simple format:
```
Primary: #FF5733
Secondary: #00A8E8
Accent: #FFC300
```

### 📱 Fluid Typography
Use modern responsive font sizes with `clamp()`:
```
Heading: Inter, clamp(3rem, 2.5rem + 2vw, 4rem), 700, 1.2em
Body: Roboto, 1rem, 400, 1.5em
```

### 🔄 Two-Way Sync
- **Pull**: Import existing colors/fonts from Elementor Site Settings
- **Push**: Send your tokens to Elementor Site Settings
- Changes sync automatically!

### 💾 Import/Export
- Export your design tokens as JSON
- Import tokens with or without ID preservation
- Perfect for backing up or migrating sites

### ⌨️ WP-CLI Support
Automate your workflow:
```bash
wp edtm export --file=tokens.json
wp edtm import tokens.json --mode=merge
wp edtm sync
```

---

## 📦 Installation

### From WordPress.org (Recommended)

1. Go to **Plugins → Add New** in your WordPress admin
2. Search for **"Design Tokens Manager for Elementor"**
3. Click **Install Now**, then **Activate**
4. Done! 🎉

### Manual Installation

1. Download the plugin from [WordPress.org](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
2. Upload the `design-tokens-manager-for-elementor` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Requirements

- WordPress 5.6 or higher
- Elementor (Free or Pro) - must be installed and activated
- PHP 7.0 or higher

---

## 🎓 How to Use

### Step 1: Access the Plugin

After activating the plugin, go to:

**WordPress Admin → Elementor → Templates → Design Tokens**

(Look in the left sidebar under "Elementor", then click "Templates")

### Step 2: Add Your Design Tokens

#### Adding Colors (Bulk Method)
1. Click the **Colors** tab
2. Paste your colors in this format:
   ```
   Primary: #FF5733
   Secondary: #00A8E8
   Accent: #FFC300
   Background: #F5F5F5
   ```
3. Click **Save Tokens**

#### Adding Fonts (Bulk Method)
1. Click the **Fonts** tab
2. Paste your fonts in this format:
   ```
   Heading: Inter, 3rem, 700, 1.2em
   Body: Roboto, 1rem, 400, 1.5em
   Small: Arial, 0.875rem, 400, 1.4em
   ```
3. Click **Save Tokens**

#### Using Responsive Font Sizes
You can use `clamp()` for fluid typography:
```
Hero: Inter, clamp(2rem, 1.5rem + 2vw, 4rem), 700, 1.1em
```
This creates fonts that scale smoothly between screen sizes!

### Step 3: Sync with Elementor

After saving your tokens:
- **Pull from Elementor**: Import existing Site Settings
- **Push to Elementor**: Send your tokens to Site Settings
- Your tokens are now available throughout Elementor!

---

## 📖 Common Use Cases

### 🎨 For Designers
Quickly create and manage your design system without clicking through multiple menus. Update all your brand colors in seconds.

### 🏢 For Agencies
Export design tokens from one site and import to another. Maintain consistent branding across multiple client sites.

### 👨‍💻 For Developers
Use WP-CLI commands to automate token management. Perfect for deployment scripts and version control.

### 🎓 For Beginners
Simple interface - just paste your colors and fonts! No technical knowledge required.

---

## 🔧 Advanced Features

### Import/Export Options

**Export your tokens:**
1. Click **Import/Export** tab
2. Choose source: Plugin or Elementor Kit
3. Enable "Preserve IDs" if moving between sites
4. Click **Export** and download JSON

**Import tokens:**
1. Click **Import/Export** tab
2. Choose your JSON file
3. Select mode:
   - **Merge**: Add new tokens, keep existing
   - **Replace**: Replace all tokens
4. Click **Import**

### WP-CLI Commands

Perfect for developers and automation:

```bash
# Export tokens
wp edtm export --file=tokens.json --source=kit --preserve-ids

# Import tokens
wp edtm import tokens.json --mode=merge

# Sync with Elementor
wp edtm sync
```

---

## 💡 Tips & Tricks

### Best Practices

1. **Use meaningful names**: Instead of "Color1", use "Primary" or "Brand"
2. **Start with Pull**: Import existing Elementor colors before adding new ones
3. **Export regularly**: Keep backups of your design system
4. **Use clamp() wisely**: Great for headings, but fixed sizes work better for body text

### Bulk Paste Format

Colors can be in any of these formats:
- `Primary: #FF5733` (hex with #)
- `Secondary: FF5733` (hex without #)
- `Accent: rgb(255, 87, 51)` (RGB)
- `Background: rgba(255, 87, 51, 0.5)` (RGBA)

Fonts format:
```
Name: FontFamily, Size, Weight, LineHeight
```
- **Font Family**: Any font name (e.g., Inter, Roboto)
- **Size**: Any CSS unit (px, rem, em) or clamp()
- **Weight**: 100-900 or bold/normal
- **Line Height**: Number or CSS unit

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

- **Report bugs**: [GitHub Issues](https://github.com/dani3lphp/design-tokens-manager-for-elementor/issues)
- **Suggest features**: [GitHub Discussions](https://github.com/dani3lphp/design-tokens-manager-for-elementor/discussions)
- **Submit pull requests**: Fork the repo and submit PRs
- **Improve documentation**: Help us make this README even better!

### Development Setup

```bash
# Clone the repository
git clone https://github.com/dani3lphp/design-tokens-manager-for-elementor.git

# Install in WordPress plugins directory
cd wp-content/plugins/
ln -s /path/to/cloned/repo design-tokens-manager-for-elementor

# Make your changes and test!
```

---

## 📝 Changelog

### Version 1.5.1 (Current)
- **Fixed**: Tab persistence after save/pull/push operations
- **Fixed**: Section preference save reliability
- **Fixed**: WordPress coding standards compliance
- **Enhanced**: Variable naming conventions
- **Improved**: Security hardening for form handlers
- **Improved**: Code documentation

### Version 1.5.0
- Initial public release
- Bulk color/font paste with clamp() support
- Two-way sync (Pull/Push)
- Import/Export with ID preservation
- Smart tab persistence after operations
- Direct Kit meta fallback
- Queue-based sync processing
- WP-CLI commands

[View full changelog →](https://wordpress.org/plugins/design-tokens-manager-for-elementor/#developers)

---

## 🐛 Bug Reports & Support

### Found a Bug?
Please report it on [GitHub Issues](https://github.com/dani3lphp/design-tokens-manager-for-elementor/issues)

### Need Help?
- **WordPress.org Support**: [Support Forum](https://wordpress.org/support/plugin/design-tokens-manager-for-elementor/)
- **Documentation**: This README and plugin page
- **GitHub Discussions**: [Ask questions](https://github.com/dani3lphp/design-tokens-manager-for-elementor/discussions)

---

## 📄 License

This plugin is licensed under the [GPL v2 or later](LICENSE).

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

---

## 🌟 Show Your Support

If you find this plugin helpful, please:

- ⭐ Star this repository on GitHub
- ⭐ Rate it on [WordPress.org](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
- 📢 Share it with other Elementor users
- 💬 Leave a review

---

## 👨‍💻 Author

**Lupu Daniel Gabriel**

- WordPress.org: [@nes07](https://profiles.wordpress.org/nes07/)
- GitHub: [@dani3lphp](https://github.com/dani3lphp)

---

## 🔗 Links

- **Plugin Homepage**: [WordPress.org](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
- **GitHub Repository**: [Source Code](https://github.com/dani3lphp/design-tokens-manager-for-elementor)
- **Report Issues**: [GitHub Issues](https://github.com/dani3lphp/design-tokens-manager-for-elementor/issues)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/design-tokens-manager-for-elementor/)

---

## 🎉 Thank You!

Thank you for using Design Tokens Manager for Elementor! We hope it makes your design workflow faster and more efficient.

**Happy designing!** 🎨

---

*Made with ❤️ for the Elementor community*
