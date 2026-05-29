# PR: Admin Bar Soft Cache Actions + Granular Algolia Cache Controls

**Branch:** feature/admin-bar-soft-cache-actions -> main

## Motivation

We needed production-safe cache operations directly from the admin bar, without opening plugin settings each time.

This PR introduces:

1. A top-level `G4TAds` admin bar menu with two dropdown actions:
   - `Clear All Soft Cache`
   - `Clear Soft Cache from this page`
2. Secure AJAX handlers for both global and page-scoped cache clear operations.
3. Granular cache TTL controls for Algolia soft/hard cache in admin settings.
4. A force-refresh flow for banner data (`g4t_ads_nocache`) that bypasses read and persists fresh data back to transient cache.

## TL;DR for reviewer

- Added global and page-specific soft-cache clear actions in admin bar.
- Implemented selective transient deletion per product ID.
- Kept VSA boundaries: admin UI/actions in `admin-settings`, cache mechanics in `algolia-ad-server`, page product detection in owning banner features.
- Added configurable Algolia soft/hard TTL settings.
- Added force-refresh behavior for admin debug use-case.

## Scope of changes

### 1) Algolia cache layer enhancements

Files:
- `features/algolia-ad-server/soft-cache.php`
- `features/algolia-ad-server/product-data.php`

Changes:
- `g4t_algolia_ad_server_soft_cache_set()` now reads cache TTL options via `g4t_core_get_ads_options()`.
- Added `g4t_algolia_ad_server_clear_all_cache()` (global transient cleanup).
- Added `g4t_algolia_ad_server_clear_product_cache( $product_ids )` (targeted cleanup).
- Added force-refresh path in `product-data.php` for logged-in admins with `?g4t_ads_nocache=1`:
  - bypasses read cache,
  - fetches fresh data from Algolia,
  - writes fresh data back to transient.

### 2) Admin settings + admin bar actions

Files:
- `features/admin-settings/settings-page.php`
- `features/admin-settings/index.php`
- `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`

Changes:
- Added secure AJAX handler for global clear:
  - `wp_ajax_g4t_clear_algolia_soft_cache`
- Added secure AJAX handler for page-scoped clear:
  - `wp_ajax_g4t_clear_current_page_algolia_soft_cache`
- Added top-level admin bar menu node `G4TAds` and dropdown actions:
  - `Clear All Soft Cache`
  - `Clear Soft Cache from this page (N)`
- Added frontend/admin script wiring for admin-bar action clicks, confirmation dialogs, AJAX calls, and success feedback.
- Existing settings-page button (`Clear All Soft Cache`) remains available.

### 3) Current page product detection (feature-owned)

Files:
- `features/banner-inline-block/helpers.php`
- `features/banner-inline-block/index.php`
- `features/banner-sidebar-widget/index.php`

Changes:
- Added filter contract usage: `g4t_ads_current_page_product_ids`
- Inline banner feature now contributes product IDs parsed from `go4taste-ads/inline-banner` blocks on singular pages.
- Sidebar widget feature contributes its resolved current-post product ID.

This preserves VSA ownership: each feature reports its own product IDs.

### 4) TTL controls in settings

File:
- `features/admin-settings/settings-page.php`

Changes:
- Added settings fields:
  - `algolia_soft_cache_ttl_hours`
  - `algolia_hard_cache_ttl_days`
- Added sanitization and defaults.

### 5) Copilot/VSA guardrail documentation

File:
- `.github/copilot-instructions.md`

Changes:
- Added an explicit repository rule: whenever a new banner/ads placement is introduced, that feature must provide feature-local integration with `g4t_ads_current_page_product_ids` so `Clear Soft Cache from this page` includes product IDs from the new placement.

## Security and safety

- Both cache-clear AJAX endpoints require:
  - `current_user_can( 'manage_options' )`
  - valid nonce (`check_ajax_referer`)
- SQL cleanup uses `$wpdb->prepare()` and strict transient prefix patterns.
- Page-scoped cleanup rejects empty product-id payloads.

## Root cause addressed

Operationally, cache clear required manual/transient-level intervention.
This PR adds controlled, audited UI actions for admins and avoids broad custom SQL outside cache prefix scope.

## Validation

Static checks:
- no editor-reported syntax errors in modified PHP/JS files.

Manual checks performed/planned:
1. Admin bar shows `G4TAds` with both dropdown actions for admin users.
2. Global clear removes product transient rows and reports deleted count.
3. Page-scoped clear only acts on product IDs detected on current page.
4. `?g4t_ads_nocache=1` fetches fresh Algolia data and overwrites cache.

## Risk / compatibility

- Risk: low to medium (touches cache control and admin UX paths).
- Backward compatibility: preserved.
- Architecture: VSA-compliant slice ownership maintained.

## Checklist

- [x] Added global soft-cache clear function in Algolia cache slice
- [x] Added selective per-product soft-cache clear function
- [x] Added secure global clear AJAX endpoint
- [x] Added secure current-page clear AJAX endpoint
- [x] Added `G4TAds` admin bar menu with two dropdown actions
- [x] Added frontend/admin JS action handlers for admin bar
- [x] Added page product-ID collection via feature-owned filter contributions
- [x] Added configurable Algolia soft/hard TTL settings
- [x] Added force-refresh cache bypass/save behavior for admin debug
- [x] Added documentation guardrail for new placements and page-scoped cache clear integration
