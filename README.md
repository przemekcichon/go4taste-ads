# Go4Taste Ads

WordPress plugin for displaying product ads in sidebars with Algolia search integration, multi-API support (PrestaShop, WooCommerce), and transient caching.

## Description

Go4Taste Ads provides a sidebar widget that displays product advertisements fetched from external e-commerce APIs. It supports PrestaShop (1.4 and 1.5), WooCommerce, and custom API endpoints. Products can be selected per-post via an Algolia-powered search interface in the post editor.

## Features

- **Sidebar widget** for displaying product ads (image, name, price, buy link)
- **Algolia search integration** for finding and selecting products in the post editor
- **Multi-API support**: PrestaShop 1.4, PrestaShop 1.5, WooCommerce, custom
- **Transient caching** with configurable TTL to reduce API calls
- **WP-CLI commands** for bulk operations (e.g. extracting product IDs from shortcodes)
- **Internationalization** ready with `.pot` template

## Requirements

- WordPress 3.0.1+
- PHP 5.6+
- Composer dependencies (Algolia PHP Search Client)

## Installation

1. Upload the `go4taste-ads` folder to `/wp-content/plugins/`
2. Run `composer install` in the plugin directory (if vendor is not bundled)
3. Activate the plugin through the **Plugins** menu in WordPress
4. Configure settings under **Go4Taste Ads** in the admin menu

## Configuration

Navigate to the plugin settings page to configure:

- **API Type** — PrestaShop 1.4, PrestaShop 1.5, WooCommerce, or Custom
- **API URL** — Base URL of the e-commerce API
- **API Password** — PrestaShop API key (p parameter)
- **Language** — PrestaShop language ID
- **Fallback Product ID** — Default product when none is assigned to a post
- **Algolia App ID** and **Search-Only API Key** — For product search in the editor
- **Cache Duration** — Transient cache lifetime in hours

## Usage

1. Edit a post and use the **Go4Taste Ads Widget** meta box to search and select a product
2. Add the **G4T Ads Sidebar Regular Widget** to a sidebar via **Appearance > Widgets**
3. The widget displays the selected product's image, name, price, and a link to the store

## License

GPL-2.0+
