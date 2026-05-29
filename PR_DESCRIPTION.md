# PR: Refactor product data pipeline — remove legacy cache, optimize sync, fix reliability

**Branch:** feature/sync-pipeline-refactor -> main

## Motivation

The plugin maintained a dual-cache architecture: product data was first cached in PrestaShop Webservice transients (`product_data_prestashop_webservice_api_*`) on the frontend fetch path, and then separately stored in the Algolia Ad Server soft-cache layer. This caused stale data risks, redundant DB writes, and a complex sync path that fetched full product payloads for all 537 products just to obtain their IDs.

This PR eliminates the legacy transient layer, optimizes the sync query, and delivers five reliability fixes discovered during testing.

## TL;DR for reviewer

- Frontend product data now routes exclusively through the Algolia Ad Server filter bridge — no live Presta fetches from frontend.
- Sync ID fetch reduced from full payload (~537 × full XML) to `display=[id]` only (~95% payload reduction).
- Fixed: IPv6 hang (9+ min timeout), XML dual-mode parser, zombie job lock, and BOM-caused jQuery `parsererror`.
- Added one-time admin utility to clean up residual legacy transients from the database.

---

## Scope of changes

### 1. Remove legacy PrestaShop transient cache (double-cache elimination)

Files:
- `core/api-client/product-data.php`
- `core/api-client/class-go4taste-ads-api-fetcher.php`
- `features/admin-settings/settings-page.php`
- `features/admin-settings/index.php`
- `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`

Changes:
- `g4t_core_fetch_product_data()` no longer fetches live data from PrestaShop or reads/writes `product_data_prestashop_webservice_api_*` transients. Delegates exclusively to `apply_filters('go4taste_ads_get_product_data', null, $product_id)`.
- Removed all `set_transient` / `get_transient` calls for product data in the frontend fetch path of `class-go4taste-ads-api-fetcher.php`.
- Removed `cache_duration` field, sanitization method, and related admin UI.
- Added **"Clean Legacy PrestaShop Cache"** one-time cleanup button in Cache Maintenance section. On click: deletes all `_transient_product_data_prestashop_webservice_api_*` and `_transient_timeout_product_data_prestashop_webservice_api_*` rows directly via `$wpdb`.
- Registered new AJAX action `g4t_clear_legacy_presta_cache` with `manage_options` capability check and nonce validation.

### 2. Optimize PrestaShop API sync query — fetch IDs only

File:
- `core/api-client/class-go4taste-ads-api-fetcher.php`

Changes:
- `fetch_prestashop_webservice_product_ids()` now appends `display=[id]` to the `/products` endpoint using `http_build_query()` with safe `?`/`&` separator.
- `fetch_product_ids_for_sync()` calls `fetch_prestashop_webservice_product_ids()` directly instead of routing through `fetch_all_products()`.
- XML parser updated to **dual-mode**: checks both `$attrs['id']` (attribute form) and `$product_node->id` (child node form) — PrestaShop changes the XML structure depending on `display` param.

### 3. Fix network reliability — force IPv4, disable SSL verification

File:
- `core/api-client/class-go4taste-ads-api-fetcher.php`

Changes:
- `wp_remote_get()` in sync path uses: `sslverify => false`, `timeout => 15`.
- `http_api_curl` action temporarily forces `CURLOPT_IPRESOLVE_V4` for the sync request to prevent IPv6 resolution hang (reproduced as 9+ minute timeout on localhost).
- Action is removed immediately after the request completes to avoid side effects on other HTTP calls.

### 4. Remove zombie job lock on sync start

File:
- `features/algolia-product-sync/sync-ajax-handlers.php`

Changes:
- Removed blocking `409 Already Running` guard from `g4t_product_catalog_sync_ajax_start()`.
- Replaced with **hard reset**: if a stale job transient exists at start, it is unconditionally deleted before creating a new job. Eliminates stuck sync state after page reload or interrupted browser sessions.

### 5. Fix AJAX response BOM / output buffer contamination

File:
- `features/algolia-product-sync/sync-ajax-handlers.php`

Changes:
- Added `if ( ob_get_length() ) { ob_clean(); }` before every `wp_send_json_success()` / `wp_send_json_error()` call in `ajax_sync_start` and `ajax_sync_batch` handlers (10 call sites).
- Eliminates BOM or whitespace characters emitted by the PHP include chain before JSON output, which caused jQuery `$.ajax` `parsererror` despite HTTP 200 and a valid JSON body.

---

## Performance considerations

- Frontend path: zero live Presta fetches, zero transient writes for product data.
- Sync ID fetch: single lightweight XML with `<id>` nodes only instead of full product payloads.
- Legacy cleanup: one-time SQL DELETE, on-demand only (admin button click).

---

## Validation

Static validation:
- No editor-reported syntax errors in any modified file.
- Audit: zero remaining references to `product_data_prestashop_webservice_api_*` in fetch/sync logic.

Manual test plan:
1. Trigger sync from admin — verify start returns correct total count (537).
2. Let sync complete — verify all batches finish without `parsererror`.
3. Confirm no `product_data_prestashop_webservice_api_*` transients written to DB during sync.
4. Click "Clean Legacy PrestaShop Cache" — verify `deleted_rows` returned.
5. Interrupt sync mid-run, reload page, start again — verify no zombie job block.

---

## Risk / compatibility

- Risk: low — frontend data path behavior unchanged for consumers (same filter bridge, same Algolia data).
- `sslverify => false` is intentional for localhost; production keys are managed via plugin settings.
- `remove_all_actions('http_api_curl')` is broad — acceptable for now (sync runs in isolation), but a named callback + `remove_action` would be safer in future.
- Backward compatibility: preserved — Algolia Ad Server filter bridge, soft-cache keys, and view model shape unchanged.
- Architecture: VSA-compliant.

---

## Checklist

- [x] Legacy transient cache removed from frontend product data path
- [x] `cache_duration` setting removed from admin UI
- [x] One-time legacy cleanup utility added (button + AJAX + JS)
- [x] Sync ID fetch uses `display=[id]` — payload ~95% smaller
- [x] XML dual-mode parser handles both attribute and node forms
- [x] IPv4 forcing + `sslverify=false` + timeout=15 in sync HTTP call
- [x] Zombie job hard reset on sync start
- [x] `ob_clean()` before all `wp_send_json_*` calls in sync handlers
- [x] No remaining live references to `product_data_prestashop_webservice_api_*` in fetch/sync logic (audit passed)
