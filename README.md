# go4taste-ads

WordPress plugin for promoted product integration via PrestaShop/WooCommerce APIs, featuring an inline Gutenberg block, product sync, and legacy migration tools.

## 🏗️ Architecture Philosophy: True Vertical Slices (VSA)

This project has been refactored from a legacy horizontal architecture into a **True Vertical Slices Architecture (VSA)**. 
We prioritize *business features* over *technical layers*. The old God Objects (`class-go4taste-ads-admin.php`) and central hook loaders have been dismantled.

- **No Horizontal Layers:** We do not group files by their technical purpose (no global `admin/`, `public/`, or `includes/` folders).
- **Co-location:** PHP, JS, CSS, and block source files that belong to the same business feature live side-by-side in the exact same folder.
- **Autonomy:** Each feature slice is 100% self-contained. It registers its own `add_action` and `add_filter` hooks directly. 

## 📂 Target Directory Structure

```text
go4taste-ads/
├── core/                                    # Shared foundations (Read-Only for Features)
│   ├── bootstrap.php                        # Initializes the plugin, loads features
│   ├── api-client/                          # Shared logic for fetching data from Presta/Woo
│   └── i18n/                                # Translation loaders
│
├── features/                                # TRUE VERTICAL SLICES (Business Functions)
│   ├── inline-banner-block/                 # Feature: Gutenberg block for inline products
│   │   ├── block.json                       
│   │   ├── render.php                       # SSR render callback
│   │   ├── src/                             # React components (Algolia search, Preview)
│   │   └── build/                           # Compiled block assets
│   │
│   ├── promoted-product-metabox/            # Feature: Post-level metabox for Algolia product search
│   │   ├── metabox-registration.php         # add_meta_boxes and save_post logic
│   │   ├── metabox-ui.js                    # Algolia search logic for the metabox
│   │   └── metabox-style.css
│   │
│   ├── product-catalog-sync/                # Feature: PrestaShop/Woo to Algolia batch sync
│   │   ├── sync-runner.php                  # Logic for batching and Algolia index updates
│   │   └── sync-ajax-handlers.php           # AJAX endpoints for sync progress
│   │
│   ├── legacy-shortcode-migration/          # Feature: Migrating old shortcodes to blocks
│   │   ├── migration-runner.php             # AJAX batch processing
│   │   ├── migration-wp-cli.php             # WP-CLI commands related to migration
│   │   └── migration-ui.js                  
│   │
│   ├── legacy-sidebar-widget/               # Feature: The old WP_Widget (isolated for easy removal)
│   │   └── widget-class.php                 
│   │
│   ├── ga4-tracking/                        # Feature: dataLayer pushes for promotion views
│   │   └── tracking-events.php              
│   │
│   └── admin-settings/                      # Feature: Plugin configuration UI
│       ├── settings-page.php                # Renders the options page
│       └── options-registration.php         # register_setting logic
│
├── .github/                                 
│   └── copilot-instructions.md              # AI architecture guardrails
├── package.json                             # Root build scripts (configured to output to features/)
└── go4taste-ads.php                         # Plugin entrypoint
```
## **🤖 Hard Constraints for AI Refactoring**

1. **Kill the Orchestrator:** Do not use `class-go4taste-ads-loader.php` or `Go4Taste_Ads::get_instance()`. Every feature must call `add_action()` or `add_filter()` directly within its own directory.  
2. **Dismantle the Admin Class:** `class-go4taste-ads-admin.php` is deprecated. Extract its methods into the appropriate slices (Settings, Sync, Migration, Metabox).  
3. **No Cross-Feature Dependencies:** `product-catalog-sync` must not depend on `legacy-shortcode-migration`. If they share API logic, that logic belongs in `core/api-client/`.  
4. **Block Co-location:** The Gutenberg block source code (`src/blocks/inline-banner`) and compiled assets must be moved entirely inside `features/inline-banner-block/`.