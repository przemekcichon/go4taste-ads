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

### 5) Pivot: global option + term meta + cascade resolver

Files:
- features/banner-sidebar-widget/global-settings-integration.php (new)
- features/banner-sidebar-widget/term-meta-integration.php (new)
- features/banner-sidebar-widget/index.php
- features/banner-sidebar-widget/archive-settings-page.php (deleted)
- core/api-client/product-data.php

Changes:
- Removed standalone archive settings subpage (Settings -> Go4Taste Archiwa).
- Added global-settings-integration.php: registers g4t_a_archive_sidebar_product_ids as a field
  wired into existing main plugin settings page (go4taste_ads_setting_section).
  Own sanitize callback validates JSON array contract.
  Picker assets load only when $hook_suffix === 'toplevel_page_go4taste-ads'.
- Added term-meta-integration.php: Algolia picker on category/post_tag edit forms.
  Rendered via {taxonomy}_edit_form_fields hooks, registered in admin_init loop.
  Saved via edited_term hook (10, 3) with dedicated nonce + dynamic capability check.
  delete_term_meta called when picker is cleared (clean empty state).
  Assets load only on edit-tags.php / term.php for supported taxonomies.
  Supported taxonomies extensible via g4t_banner_sidebar_widget_supported_taxonomies filter.
- Refactored g4t_core_get_sidebar_widget_product_id() to strict cascade:
  1. is_singular(): postmeta JSON key -> legacy single-string fallback -> continue if both empty
  2. is_category()/is_tag()/is_tax(): term_meta JSON key -> fall through if empty
  3. is_home()/is_search()/category/tag/tax: global option JSON key
  4. return '' if nothing matched at any stage
  Switched from global $post to get_queried_object_id() for singular path.

---

## Data contract

- Singular postmeta key: _g4t_a_sidebar_product_ids
- Term meta key: _g4t_a_sidebar_product_ids (wp_termmeta)
- Archive option key: g4t_a_archive_sidebar_product_ids
- Storage format: JSON encoded array of strings, example: ["154"]

Cascade resolution order (runtime):
1. singular: postmeta JSON -> postmeta legacy string
2. category/tag/tax: term_meta JSON -> fall through if empty
3. home/search/category/tag/tax: wp_options JSON

Backward compatibility:
- Singular resolver falls back to legacy _g4t_a_sidebar_regular_widget_ps_product_id.

---

## Validation

Static validation:
- No editor errors in modified/new files:
	- features/banner-sidebar-widget/assets/js/picker-ui.js
	- features/banner-sidebar-widget/assets/css/picker-ui.css
	- features/banner-sidebar-widget/metabox-registration.php
	- features/banner-sidebar-widget/global-settings-integration.php
	- features/banner-sidebar-widget/term-meta-integration.php
	- features/banner-sidebar-widget/index.php
	- core/api-client/product-data.php

Manual test plan:
1. Edit single post metabox, save, verify _g4t_a_sidebar_product_ids contains JSON array.
2. Open Settings -> Go4Taste Ads, verify new "Archive Sidebar Product" picker field appears in section.
   Select product, save, verify g4t_a_archive_sidebar_product_ids contains JSON array in wp_options.
3. Open Categories or Tags admin, edit a term, verify "Sidebar Product" picker appears.
   Select product, save, verify _g4t_a_sidebar_product_ids in wp_termmeta.
4. Verify widget on singular uses postmeta value.
5. Verify widget on category/tag archive uses term meta value (if set).
6. Verify widget on category/tag archive falls through to global option when term meta is empty.
7. Verify widget on home/search uses global option value.
8. Verify legacy singular posts still render through legacy fallback key.

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
- [x] Pivot: standalone archive settings subpage replaced by field in main plugin settings
- [x] Term meta integration: Algolia picker on category/post_tag edit screens
- [x] Cascade resolver: singular -> term meta -> global option -> empty string
- [x] Assets load scoped to correct admin screens (metabox, main settings, term edit)
