# go4taste-ads

WordPress plugin for promoted product integration via PrestaShop/WooCommerce APIs, featuring an inline Gutenberg block, product sync, and GA4 promotion tracking.

## 🏗️ Architecture Philosophy: True Vertical Slices (VSA)

This project has been refactored from a legacy horizontal architecture into a **True Vertical Slices Architecture (VSA)**. 
We prioritize *business features* over *technical layers*. The old God Objects (`class-go4taste-ads-admin.php`) and central hook loaders have been dismantled.

- **No Horizontal Layers:** We do not group files by their technical purpose (no global `admin/`, `public/`, or `includes/` folders).
- **Co-location:** PHP, JS, CSS, and block source files that belong to the same business feature live side-by-side in the exact same folder.
- **Autonomy:** Each feature slice is 100% self-contained. It registers its own `add_action` and `add_filter` hooks directly. 

## 📂 Current Directory Structure

```text
go4taste-ads/
├── core/                                    # Shared foundations (Read-Only for Features)
│   ├── bootstrap.php                        # Initializes the plugin, loads features
│   ├── api-client/                          # Shared logic for fetching data from Presta/Woo
│   ├── i18n/                                # Translation loaders
│   └── lifecycle/                           # Activation/deactivation classes
│
├── features/                                # TRUE VERTICAL SLICES (Business Functions)
│   ├── inline-banner-block/                 # Feature: Gutenberg block for inline products
│   │   ├── index.php                        # Hook registration for the block feature
│   │   ├── render.php                       # SSR registration + fallback render wiring
│   │   ├── helpers.php                      # Feature-local block helpers
│   │   ├── src/                             # Block source (JS/CSS/render template)
│   │   └── build/                           # Compiled block assets
│   │
│   ├── promoted-product-metabox/            # Feature: Post-level metabox for Algolia product search
│   │   ├── index.php                        # Hook registration for metabox feature
│   │   ├── metabox-registration.php         # add_meta_boxes, save_post, AJAX preview
│   │   ├── preview-template.php             # Preview markup
│   │   └── assets/css/                      # Feature-local metabox preview styles
│   │
│   ├── product-catalog-sync/                # Feature: PrestaShop/Woo to Algolia batch sync
│   │   ├── index.php                        # Hook registration for sync feature
│   │   ├── sync-runner.php                  # Logic for batching and Algolia index updates
│   │   └── sync-ajax-handlers.php           # AJAX endpoints for sync progress
│   │
│   ├── legacy-sidebar-widget/               # Feature: The old WP_Widget (isolated for easy removal)
│   │   ├── index.php                        # Hook registration for widget feature
│   │   ├── widget-class.php
│   │   ├── widget-template.php
│   │   └── assets/css/                      # Feature-local sidebar widget styles
│   │
│   ├── ga4-tracking/                        # Feature: dataLayer pushes for promotion views
│   │   ├── index.php
│   │   ├── tracking-events.php
│   │   └── payload-builder.php
│   │
│   └── admin-settings/                      # Feature: Plugin configuration UI
│       ├── index.php                        # Hook registration for settings feature
│       ├── settings-page.php                # Renders page + register_setting/fields
│       └── assets/                          # Feature-local settings scripts/styles
│
├── .github/                                 
│   └── copilot-instructions.md              # AI architecture guardrails
├── package.json                             # Root build scripts (configured to output to features/)
└── go4taste-ads.php                         # Plugin entrypoint
```
## **🤖 Hard Constraints for AI Refactoring**

1. **Kill the Orchestrator:** Do not use `class-go4taste-ads-loader.php` or `Go4Taste_Ads::get_instance()`. Every feature must call `add_action()` or `add_filter()` directly within its own directory.  
2. **Do Not Reintroduce God Objects:** Do not reintroduce `class-go4taste-ads-admin.php` or centralize feature logic in one class/file. Keep ownership inside dedicated slices.  
3. **No Cross-Feature Dependencies:** Features cannot depend on other features. Shared logic must live in `core/` (for example `core/api-client/`).  
4. **Block Co-location:** Inline banner block source and compiled assets must stay inside `features/inline-banner-block/src` and `features/inline-banner-block/build`.