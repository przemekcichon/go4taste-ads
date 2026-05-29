# PR: On-Demand Soft/Hard Cache Product Counts in Options Page

**Branch:** feature/cache-counts-ui -> main

## Motivation

We needed visibility into how many products are currently stored in Algolia banner cache, but without adding runtime overhead.

Key requirement for performance:
- cache counting must run only on demand (button click in options page),
- no automatic counting on page load,
- no counting in frontend/admin runtime flows,
- no `get_transient()` loop for large datasets.

## TL;DR for reviewer

- Added on-demand cache counting (`soft` and `hard`) in plugin options page.
- Implemented efficient counting with a single `$wpdb->get_col()` query over transient values.
- `hard_count` = number of transient value rows found.
- `soft_count` = rows where unserialized payload has `soft_expires_at > time()`.
- Added dedicated AJAX endpoint for counting and wired UI button + result panel.
- Existing clear endpoints now also return fresh counts after clear (optional UX enhancement).

## Scope of changes

### 1) Cache counting logic (efficient, OOM-safe)

File:
- `features/algolia-ad-server/soft-cache.php`

Added:
- `g4t_algolia_ad_server_get_cache_counts()`

Implementation details:
- Single query: `SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE '_transient_g4t_ads_product_%'`
- Uses `$wpdb->esc_like()` + `$wpdb->prepare()`
- `hard_count` = `count( $rows )`
- Iterates over row values with `maybe_unserialize()`
- Increments `soft_count` when payload contains `soft_expires_at` greater than current time
- Returns structured result: `success`, `soft_count`, `hard_count`, `executed_at`, optional `error`

### 2) New AJAX endpoint for on-demand counts

Files:
- `features/admin-settings/settings-page.php`
- `features/admin-settings/index.php`

Added:
- method `ajax_get_algolia_cache_counts()` in admin settings class
- action registration: `wp_ajax_g4t_get_algolia_cache_counts`
- localized JS config:
  - `getCacheCountsAction`
  - `getCacheCountsNonce`

Security:
- capability check: `manage_options`
- nonce validation: `check_ajax_referer( 'g4t_get_cache_counts_ajax', 'nonce' )`

### 3) Options page UI (only there, only on click)

File:
- `features/admin-settings/settings-page.php`

Added in Cache Maintenance section:
- button: `Count Cached Products`
- result container: `#g4t-cache-count-result`

Behavior:
- no automatic render of counts on load
- results appear only after click-triggered AJAX response

### 4) JS handling for count button + post-clear refresh

File:
- `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`

Added:
- click handler for `#g4t-count-soft-hard-cache`
- request to `g4t_get_algolia_cache_counts`
- result rendering with `Soft cache products` and `Hard cache products`

Also improved:
- after clear actions, if response includes counts, count panel is refreshed in-place

## Performance considerations

- No runtime counting in frontend.
- No periodic/autoloaded counting in admin page render.
- One SQL query over matching rows + lightweight PHP pass over `option_value`.
- Avoids `get_transient()` in loops, minimizing object-cache churn and memory spikes.

## Validation

Static validation:
- no editor-reported syntax errors in modified files

Manual test plan:
1. Open options page, ensure no counts are shown initially.
2. Click `Count Cached Products`.
3. Verify both values are returned:
   - `Soft cache products: X`
   - `Hard cache products: Y`
4. Click `Clear All Soft Cache`, verify counts update in response.
5. Click `Count Cached Products` again to confirm zero or expected state.

## Risk / compatibility

- Risk: low (read-only counting endpoint + UI update).
- Backward compatibility: preserved.
- Architecture: VSA-compliant; logic remains in cache slice, admin UI in admin-settings slice.

## Checklist

- [x] Added efficient on-demand cache counting function in `soft-cache.php`
- [x] Used `$wpdb->get_col()` + `maybe_unserialize()` approach (no `get_transient` loop)
- [x] Added secure AJAX endpoint for cache counts
- [x] Added options-page button and result area for counts
- [x] Added JS click handler and count rendering
- [x] Updated clear responses to optionally include refreshed counts
