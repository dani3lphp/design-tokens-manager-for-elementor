# Design Tokens Manager for Elementor

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/design-tokens-manager-for-elementor)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![WordPress Plugin Required PHP Version](https://img.shields.io/wordpress/plugin/required-php/design-tokens-manager-for-elementor)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/design-tokens-manager-for-elementor)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/design-tokens-manager-for-elementor)](https://wordpress.org/plugins/design-tokens-manager-for-elementor/)

Manage Elementor Global Colors and Fonts with advanced features like fluid typography support, bulk editing, and seamless Site Settings synchronization.

## 🌟 Key Features

- **Bulk Token Management**
  - Paste multiple colors or fonts at once
  - Smart parsing of token definitions
  - Instant validation and feedback

- **Fluid Typography Support**
  - Full `clamp()` function support
  - Perfect for responsive designs
  - Example: `clamp(1rem, 2vw + 1rem, 3rem)`

- **Smart Sync System**
  - Two-way sync with Elementor Site Settings
  - Token ID preservation across operations
  - Queue-based processing for reliability

- **User Experience**
  - Tab persistence across operations
  - Intuitive bulk actions
  - Real-time color previews

- **Developer Friendly**
  - WP-CLI commands
  - Direct Kit meta access
  - Clean, documented code

## 🚀 Installation

1. Upload to `/wp-content/plugins/` or install via WordPress dashboard
2. Activate the plugin through the 'Plugins' menu
3. Navigate to **Elementor → Design Tokens**
4. Start managing your design tokens!

## 📝 Usage Examples

### Bulk Color Import
```text
Primary: #FF5733
Secondary: #00A8E8
Accent: #4CAF50
```

### Bulk Font Import
```text
Heading: Inter, clamp(3rem, 2.5rem + 2vw, 4rem), 700, 1.2em
Body: Roboto, 1rem, 400, 1.5em
Small: system-ui, 0.875rem, 400, 1.4em
```

## 🛠️ Requirements

- WordPress 5.6+
- PHP 7.0+
- Elementor (Free or Pro)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## 📜 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Built for the Elementor community
- Inspired by design token management systems
- Thanks to all contributors and testers

## 📚 Documentation

For detailed documentation, examples, and troubleshooting, please visit our [Wiki](https://github.com/your-username/design-tokens-manager-for-elementor/wiki).

## ⚡ CLI Commands

```bash
# Export all tokens
wp elementor-tokens export --format=json

# Import tokens
wp elementor-tokens import tokens.json

# Sync with Site Settings
wp elementor-tokens sync --direction=push
```

## 🆘 Support

- For bug reports and feature requests, please use the [GitHub Issues](https://github.com/your-username/design-tokens-manager-for-elementor/issues)
- For general questions, use the [WordPress.org support forum](https://wordpress.org/support/plugin/design-tokens-manager-for-elementor/)

---

Made with ❤️ by [Daniel Gabriel Lupu](https://github.com/lupudanielgabriel)
