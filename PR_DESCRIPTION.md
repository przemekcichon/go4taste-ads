# PR: Sidebar Widget Refactor to VSA + JSON Contract + Archive Settings

**Branch:** feature/vsa-sidebar-widget-refactor -> main

## Motivation

Sidebar widget feature was split across two slices and used mixed persistence rules. This PR finalizes the migration to a single Vertical Slice and aligns storage to the JSON Array contract required by plugin architecture guidelines.

Main goals delivered:
- unification of widget runtime and metabox input into one slice,
- migration from legacy single-string meta to JSON array contract,
- fix for archive/home/search context by reading global option instead of current post meta,
- picker refactor to reusable JS module shared between metabox and archive settings screen.

---

## Scope of changes

### 1) VSA consolidation into one slice

Files:
- features/banner-sidebar-widget/index.php
- features/banner-sidebar-widget/metabox-registration.php
- features/banner-sidebar-widget/preview-template.php
- features/banner-sidebar-widget/assets/css/go4taste-ads-metabox-preview.css
- core/bootstrap.php

Changes:
- Moved metabox files from removed slice to features/banner-sidebar-widget.
- Unified hook registration in one entrypoint: widgets_init, wp_enqueue_scripts, add_meta_boxes, save_post, wp_ajax_g4t_fetch_product_preview, admin_enqueue_scripts, g4t_ads_current_page_product_ids.
- Removed old bootstrap require to deleted metabox slice.

### 2) Data contract migration to JSON Array

Files:
- core/api-client/product-data.php
- features/banner-sidebar-widget/metabox-registration.php
- features/banner-sidebar-widget/widget-class.php

Changes:
- Singular source now reads new key _g4t_a_sidebar_product_ids (JSON array), returns first ID.
- Legacy fallback kept: _g4t_a_sidebar_regular_widget_ps_product_id.
- Metabox save now stores JSON array string under _g4t_a_sidebar_product_ids.
- Metabox render reads JSON first, then legacy fallback, and still feeds hidden input with one ID for compatibility.
- Widget fallback path hardened: instance fallback_product_id first, then global fallback option.

### 3) Archive fix + reusable picker module

Files:
- features/banner-sidebar-widget/assets/js/picker-ui.js
- features/banner-sidebar-widget/archive-settings-page.php
- features/banner-sidebar-widget/index.php
- features/banner-sidebar-widget/metabox-registration.php
- core/api-client/product-data.php

Changes:
- Added reusable picker module: window.G4TAdsPicker(config).
- Metabox inline JS replaced with module initialization and localized config.
- Added new settings subpage: Settings -> Go4Taste Archiwa.
- New archive option persistence key: g4t_a_archive_sidebar_product_ids (JSON array).
- Added secure save endpoint via admin_post with nonce + manage_options.
- Product resolver now supports contexts:
	- is_singular(): postmeta JSON key + legacy fallback,
	- is_archive()/is_home()/is_search(): wp_options JSON key.

### 4) Picker UI CSS cleanup (remove inline styles)

Files:
- features/banner-sidebar-widget/assets/css/picker-ui.css
- features/banner-sidebar-widget/metabox-registration.php
- features/banner-sidebar-widget/archive-settings-page.php

Changes:
- Removed inline `<style>` block from metabox renderer.
- Added shared picker stylesheet in slice scope: `assets/css/picker-ui.css`.
- Enqueued the same CSS on both metabox and archive settings screen.
- Kept picker look-and-feel aligned with WordPress admin UI.

---

## Data contract

- Singular postmeta key: _g4t_a_sidebar_product_ids
- Archive option key: g4t_a_archive_sidebar_product_ids
- Storage format: JSON encoded array of strings, example: ["154"]

Backward compatibility:
- Singular resolver falls back to legacy _g4t_a_sidebar_regular_widget_ps_product_id.

---

## Validation

Static validation:
- No editor errors in modified/new files:
	- features/banner-sidebar-widget/assets/js/picker-ui.js
	- features/banner-sidebar-widget/assets/css/picker-ui.css
	- features/banner-sidebar-widget/metabox-registration.php
	- features/banner-sidebar-widget/archive-settings-page.php
	- features/banner-sidebar-widget/index.php
	- core/api-client/product-data.php

Manual test plan:
1. Edit single post metabox, save, verify _g4t_a_sidebar_product_ids contains JSON array.
2. Open Settings -> Go4Taste Archiwa, select product, save, verify g4t_a_archive_sidebar_product_ids contains JSON array.
3. Verify widget on singular uses postmeta value.
4. Verify widget on archive/home/search uses option value.
5. Verify legacy singular posts still render through legacy fallback key.

---

## Risk / compatibility

- Risk: low-to-medium (touches resolver paths and admin UI wiring).
- Backward compatibility for singular preserved through legacy fallback.
- VSA compliance improved by keeping all sidebar widget capabilities inside one slice.

---

## Checklist

- [x] Metabox + widget unified under features/banner-sidebar-widget
- [x] Old metabox slice removed
- [x] JSON array contract implemented for singular placement
- [x] Archive/global option path added with JSON array contract
- [x] Reusable picker-ui.js extracted and reused by metabox + archive settings page
- [x] Shared picker-ui.css extracted and enqueued for metabox + archive settings page
- [x] Secure admin save flow for archive settings (nonce + capability)
- [x] Context-aware resolver for singular vs archive/home/search
