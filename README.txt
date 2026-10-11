=== Go4Taste Ads ===
Contributors: go4tastetechlab
Donate link: https://go4taste.pl/techlab/
Tags: ads, product, sidebar, widget, e-commerce
Requires at least: 3.0.1
Tested up to: 6.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display product ads in WordPress sidebars with data fetched from PrestaShop Webservice API.

== Description ==

Go4Taste Ads provides a sidebar widget that displays product advertisements fetched from PrestaShop Webservice API. Products can be selected per-post using an Algolia-powered search interface in the WordPress editor.

Features:

* Sidebar widget displaying product image, name, price, and buy link
* Algolia search integration for product selection in the post editor
* Data source support: PrestaShop Webservice API
* Transient caching with configurable TTL
* WP-CLI commands for bulk product ID updates
* Internationalization ready

== Installation ==

1. Upload the `go4taste-ads` folder to the `/wp-content/plugins/` directory
2. Run `composer install` in the plugin directory if the `vendor/` folder is not included
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure settings under the Go4Taste Ads admin menu

== Frequently Asked Questions ==


= Which data source is supported? =

PrestaShop Webservice API.

= How does product selection work? =

When editing a post, use the Go4Taste Ads Widget meta box to search for products by name or ID via Algolia. The selected product ID is saved as post meta and used by the sidebar widget.

= How does caching work? =

Product data is cached using WordPress transients. The cache duration can be configured in the plugin settings (in hours).

== Changelog ==

= 1.1.2 =
* Algolia provider is enqueued on the front end only where a surface requests it (recipe creator)
* algoliasearch pinned to 4.27.0

= 1.1.1 =
* Banners: the whole product card is a single link, the product name is the anchor
* Inline banner: rebuilt block output, so the specific prices fallback is live
* Sidebar widget CSS is versioned by file time to bust browser caches

= 1.1.0 =
* Added Algolia search integration for product selection
* Added WP-CLI command for bulk product ID updates
* Added configurable cache duration
* Added product preview in post editor

= 1.0.0 =
* Initial release with sidebar widget and PrestaShop API support

== Upgrade Notice ==

= 1.1.2 =
Front-end pages no longer load the Algolia client unless needed. Update go4taste-recipes-plugin first.

= 1.1.1 =
Whole-card product links in banners and a price display fix in the inline banner.

= 1.1.0 =
Adds Algolia search, WP-CLI commands, and configurable caching.
