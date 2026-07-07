=== Salient Custom Elements ===
Contributors: riccardodicurti
Tags: wpbakery, salient, page-builder, ai, custom-elements
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://liberapay.com/riccardodicurti/donate

Generate native WPBakery elements integrated with the Salient theme. Create with AI or manually, preview instantly, and ship a production plugin.

== Description ==

**Salient Custom Elements** is a dev/staging tool for teams using the commercial [Salient](https://themeforest.net/item/salient-responsive-multipurpose-theme/4363266) theme and [WPBakery Page Builder](https://wpbakery.com/). It generates standalone PHP files that register custom WPBakery shortcodes, ready for production.

= Features =

* **AI generation** via the official WordPress AI plugin (multi-turn chat for edits)
* **Instant editor integration** — generated elements appear in WPBakery immediately
* **Live preview** — private preview page per element
* **Production rules** — SEO, security, accessibility (WCAG 2.2 AA), and responsive CSS baked in
* **Code review** — built-in auditor flags common issues before export
* **Ship to production** — package all elements into a standalone mini-plugin (`salient-shipped-elements`) and remove the generator
* **Italian translation included** — UI in English by default; loads `it_IT` automatically when WordPress is set to Italian

= Requirements =

* WordPress 6.9+ (WordPress 7+ recommended for AI features)
* PHP 8.0+
* Salient theme active
* WPBakery Page Builder active
* [WordPress AI plugin](https://wordpress.org/plugins/ai/) for AI generation (optional — manual creation still works)

This plugin does **not** include or redistribute Salient or WPBakery. It detects their presence and degrades gracefully when they are missing.

= Author =

Developed by [Riccardo Di Curti](https://riccardodicurti.it).

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/salient-custom-elements/` or install from GitHub.
2. Activate the plugin through the **Plugins** screen.
3. Ensure Salient and WPBakery are active.
4. Open **Custom Elements** in the WordPress admin menu.
5. Generate an element with AI or create one manually, then review and export.

== Frequently Asked Questions ==

= Does this work without the WordPress AI plugin? =

Yes. You can create and edit elements manually. AI generation requires the WordPress AI plugin with a configured model.

= Can I use this on a production site? =

The generator is intended for dev/staging. Use **Package and remove generator** (or **Download zip**) to ship a production-only mini-plugin, then remove this generator before going live.

= Is Italian supported? =

Yes. Set WordPress to Italian (`it_IT`) and the admin UI will load the bundled translation automatically.

== Screenshots ==

1. AI generation chat and element list
2. Edit screen with preview and multi-turn AI modification
3. Wiki and production rules reference

== Changelog ==

= 0.2.0 =
* First public release
* AI multi-turn element editing via chat
* Original generation prompt saved and shown on edit screen
* Large preview button in edit toolbar
* Export/ship all elements (not only reviewed)
* English source strings with bundled Italian translation
* Admin footer with author credits and Liberapay donation link
* GPLv2 license, readme.txt, uninstall cleanup

== Upgrade Notice ==

= 0.2.0 =
First public release. Review generated PHP before shipping to production.
