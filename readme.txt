=== Design Tokens Manager for Elementor ===
Contributors: lupudanielgabriel
Tags: elementor, design, tokens, colors, typography
Requires at least: 5.6
Tested up to: 6.8
Stable tag: 1.5.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage Elementor Global Colors and Fonts with clamp() support, ID preservation, bulk editing, and seamless Site Settings sync.

== Description ==

**Design Tokens Manager for Elementor** gives you complete control over your Elementor design system.

= Key Features =

* **Bulk Editing** - Paste multiple colors/fonts at once
* **Fluid Typography** - Full clamp() support for responsive sizing
* **ID Preservation** - Maintains token references across saves
* **Two-Way Sync** - Pull from or Push to Elementor Site Settings
* **Import/Export** - Backup or migrate your design tokens
* **Smart Tab Persistence** - Stays on your current section after saving
* **WP-CLI Support** - Automate token management

= Perfect For =

* Design system managers
* Agencies managing multiple sites
* Developers wanting programmatic control
* Anyone tired of clicking 50+ times to update colors

= Bulk Format Examples =

**Colors:**
`Primary: #FF5733
Secondary: #00A8E8`

**Fonts:**
`Heading: Inter, clamp(3rem, 2.5rem + 2vw, 4rem), 700, 1.2em
Body: Roboto, 1 rem, 400, 1.5em`

= Requirements =

* WordPress 5.6 or higher
* Elementor (free or Pro)
* PHP 7.0 or higher

== Installation ==

1. Upload to `/wp-content/plugins/` or install via dashboard
2. Activate the plugin
3. Go to **Elementor → Design Tokens**
4. Add tokens and click **Save Tokens**

== Frequently Asked Questions ==

= Does this work with Elementor Free? =

Yes! Works with both Free and Pro versions.

= Can I use clamp() for font sizes? =

Absolutely! Enter `clamp(1rem, 2vw, 3rem)` directly in the size field.

= Will deletions sync properly? =

Yes. Deleted tokens are removed from Site Settings permanently.

= Does it preserve my current tab after saving? =

Yes! The plugin now remembers which section (Colors/Fonts) you were working on and returns you there after save, pull, or push operations.

== Screenshots ==

1. Main interface with bulk paste and row editing
2. Two-way sync toolbar (Pull/Push to Site Settings)
3. Import/Export with ID preservation options
4. Bulk actions (select and delete multiple tokens)

== Changelog ==

= 1.5.1 =
* Fixed: Tab persistence after save/pull/push operations
* Fixed: Section preference save reliability
* Fixed: WordPress coding standards compliance
* Enhanced: Variable naming conventions
* Improved: Security hardening for form handlers
* Improved: Code documentation

= 1.5.0 =
* Initial public release
* Bulk color/font paste with clamp() support
* Two-way sync (Pull/Push)
* Import/Export with ID preservation
* Smart tab persistence after operations
* Direct Kit meta fallback
* Queue-based sync processing
* WP-CLI commands

== Upgrade Notice ==

= 1.5.1 =
Important update fixing tab persistence and improving security. Recommended for all users.

= 1.5.0 =
First stable release. Safe to use in production.

== Additional Info ==

**WP-CLI Commands:**

`wp edtm export --file=tokens.json --source=kit --preserve-ids
wp edtm import tokens.json --mode=merge
wp edtm sync`

**GitHub:** https://github.com/yourusername/design-tokens-manager-for-elementor

== Support ==

For bug reports and feature requests, please use the WordPress.org support forum.

== Credits ==

Developed by Lupu Daniel Gabriel