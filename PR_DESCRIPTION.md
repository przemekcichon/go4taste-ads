# PR: Algolia Data Provider — Shared JS Layer for All Picker Surfaces

**Branch:** feature/use-ads-data-provider -> main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: JS data provider | ✅ Done | `G4TAdsProvider` module — shared search/fetch/normalize over algoliasearch SDK |
| Stage 2: Refactor picker-ui.js | ✅ Done | Replaced InstantSearch.js + jQuery with provider as data layer |
| Stage 3: Surface adapters | ✅ Done | Metabox, term edit, global settings — all use provider, no own credentials |
| Stage 4: Docs + QA matrix | ✅ Done | All surfaces QA-passed; two bugs found and fixed during QA |

---

## Context: what is already in place

PR #15 (`feature/banner-extensibility`) merged the PHP foundation:

- Placement registry (`core/placements/registry.php`) — `go4taste/ads/register_placement` filter
- Shared payload resolver (`core/placements/payload.php`) — normalized product shape for PHP renderers
- Render dispatcher (`core/placements/render.php`) — `g4t_ads_render_placement()` callable from theme/plugin
- Recipe bottom strip slice (`features/recipe-bottom-strip/`) — `g4t_recipe_bottom_strip_get_products_for_post()` integration point

The recipes plugin (`feature/picker-ui-extensibility` on that side) added:
- Step 6 picker UI in the recipe creator (native frontend controls, not the ads widget)
- Save path via flat `g4t_recipe_ads_product_ids_json` POST field
- Read path via `g4t_recipe_bottom_strip_get_products_for_post()`
- Credentials consumed from `go4taste_ads_options` passed as `G4T_RECIPE_CREATOR_DATA.adsPicker`

The creator queries Algolia directly using `algoliasearch` SDK. `picker-ui.js` did the same via InstantSearch.js. Both initialized their own client, both normalized hits independently. This PR closes that duplication — both now delegate to `G4TAdsProvider`.

---

## Problem

Two separate JS Algolia clients with duplicated logic:

```
creator.js (recipes plugin)            picker-ui.js (ads plugin)
──────────────────────────────         ──────────────────────────────
window.algoliasearch(appId, key)       window.algoliasearch(appId, key)
searchClient.initIndex(name)           instantsearch({ searchClient })
algoliaIndex.search(query, opts)       instantsearch.widgets.hits(...)
normalizeProductRecord(hit)            inline hit template string
algoliaIndex.getObject(id)             index.getObject(id)
```

`picker-ui.js` uses InstantSearch.js — Algolia's UI widget framework — which bundles data and DOM together. This makes extracting a reusable data layer impossible without refactoring. The creator went around it by using the low-level SDK directly, but duplicated the normalization and client init.

---

## Motivation

One JS module should own: client init, search, per-ID fetch, normalization. Every surface — admin picker, creator, future adapters — consumes this module instead of reinventing it.

Main goals:
- single Algolia client instance per page, shared across surfaces,
- one canonical normalization of Algolia hits to a product shape,
- picker-ui.js and creator.js both use the provider; neither owns data logic,
- new surfaces (new admin screen, external plugin) get data layer for free.

---

## Desired outcome

After this implementation:
1. `window.G4TAdsProvider` is the single entry point for Algolia product data on any surface.
2. `picker-ui.js` is a UI layer only — no data logic of its own.
3. `creator.js` (recipes plugin) replaces direct `algoliaIndex.search()` calls with provider calls.
4. Normalization lives in one place; both surfaces use the same product shape.
5. A new surface needs only `G4TAdsProvider.getInstance().search(query)` to get results.

---

## Architecture

### Stage 1: `G4TAdsProvider` module

New file: `core/provider/algolia-provider.js`

```javascript
// Singleton, initialized once per page from PHP-localized credentials.
window.G4TAdsProvider = (function () {
  let instance = null;

  function create(config) {
    const client = window.algoliasearch(config.appId, config.apiKey);
    const index  = client.initIndex(config.indexName);

    function normalize(hit) {
      return {
        id:      String(hit.objectID || hit.id || ''),
        name:    hit.name    || hit.title || '',
        picture: hit.picture || hit.image || '',
        link:    hit.link    || hit.url   || '',
        price:   hit.price   || null,
        category: hit.category || '',
      };
    }

    return {
      search(query, opts) {
        return index
          .search(String(query || ''), { hitsPerPage: opts?.hitsPerPage ?? 12 })
          .then(r => r.hits.map(normalize));
      },
      getById(id) {
        return index.getObject(String(id)).then(normalize);
      },
      getByIds(ids) {
        return Promise.all(ids.map(id => this.getById(id)));
      },
    };
  }

  return {
    init(config) { if (!instance) instance = create(config); },
    getInstance() { return instance; },
  };
})();
```

PHP side — ads plugin enqueues `algolia-provider.js` and localizes:
```php
wp_localize_script('g4t-ads-provider', 'G4T_ADS_PROVIDER_DATA', [
    'appId'     => $options['algolia_app_id'],
    'apiKey'    => $options['algolia_search_api_key'],
    'indexName' => $options['algolia_index_name'] ?? 'go4taste_product_list',
]);
```

---

### Stage 2: Refactor `picker-ui.js`

- Remove `window.instantsearch` dependency entirely.
- Remove jQuery dependency from data path (keep only for DOM manipulation if needed, or drop jQuery completely).
- Replace `search.addWidget(hits(...))` with `G4TAdsProvider.getInstance().search(query)` + own rendering.
- Replace `index.getObject(id)` with `G4TAdsProvider.getInstance().getById(id)`.
- `picker-ui.js` becomes UI-only: search input listener → provider → render hits → selection state.

InstantSearch.js script tag can be removed from the enqueue list once no widget uses it.

---

### Stage 3: Surface adapters

Each existing admin surface (metabox, term edit, global settings) that currently initializes its own Algolia client gets updated to call `G4TAdsProvider.getInstance()` instead. No logic changes to save/read paths — only the data fetch layer changes.

---

### Stage 4: Docs + QA

- Document `G4TAdsProvider` public API (search, getById, getByIds).
- QA matrix: metabox picker, term edit picker, global settings picker, recipe creator — all must search and select correctly after refactor.
- Confirm single client instance: no duplicate Algolia calls on pages with multiple surfaces.

---

## Suggested public JS API

```javascript
G4TAdsProvider.init({ appId, apiKey, indexName })   // called synchronously when algolia-provider.js executes (dependency order guarantees algoliasearch is ready)
G4TAdsProvider.getInstance()                         // returns provider or null if not initialized
  .search(query, { hitsPerPage })   // → Promise<NormalizedProduct[]>
  .getById(id)                      // → Promise<NormalizedProduct>
  .getByIds(ids[])                  // → Promise<NormalizedProduct[]>
```

`NormalizedProduct` shape: `{ id, name, picture, link, price, category }` — same as current `normalizeProductRecord()` in creator.js.

---

## Bugs found during QA

### Bug 1: "Algolia is not configured" on all admin picker surfaces

**Symptom:** After switching to `G4TAdsProvider`, metabox, term edit, and global settings pickers showed "Algolia is not configured" instead of the search field.

**Cause:** `algolia-provider.js` originally used `document.addEventListener('DOMContentLoaded', ...)` to init the provider. Inline scripts in admin templates (body HTML) register their `DOMContentLoaded` listeners *before* footer scripts execute — so the picker's listener fired before the provider had initialized. `getInstance()` returned `null`.

**Fix:** Removed `DOMContentLoaded` wrapper from provider auto-init. Since `algolia-search-client` is a declared dependency of `g4t-ads-provider`, WordPress guarantees `window.algoliasearch` is available when `algolia-provider.js` executes. Init is now synchronous — `getInstance()` returns an instance for any listener registered later.

### Bug 2: Search input missing in admin picker containers

**Symptom:** After the `DOMContentLoaded` fix, provider initialized correctly but the picker area remained non-interactive — no search field visible.

**Cause:** The old `picker-ui.js` used InstantSearch's `searchBox` widget which rendered an `<input>` inside the container div. The new `picker-ui.js` queried for an existing input inside the div — but none existed; the div was empty.

**Fix:** `picker-ui.js` now creates and appends an `<input type="search">` element inside the search container div before attaching event listeners.

---

## Validation plan

### Passed ✅

1. ✅ Metabox picker searches and selects a product — saves correctly.
2. ✅ Term edit picker searches and selects — saves correctly.
3. ✅ Global settings picker searches and selects — saves correctly.
4. ✅ Recipe creator step 6 searches Algolia and selects products — saves to `_g4t_a_recipe_bottom_product_ids`.
5. ✅ Single Algolia client instance per page — `algoliasearch@4` CDN loaded once, `instantsearch.js` no longer requested.
6. ✅ Recipe creator edit mode — previously saved product IDs load back into selected-strip.
7. ✅ New recipe publish — redirects to correct `post_id`.

### Follow-up

8. Visual QA of admin picker inputs (inline style vs theme-native input styling).
9. Recipes plugin follow-up PR (`feature/use-ads-data-provider`) — merge after this PR.

---

## Risks and mitigations

- Risk: InstantSearch.js removal breaks existing search UX in admin pickers.
  Mitigation: Stage 2 replaces widgets one-to-one; input debounce, loading state, and empty state are preserved in custom rendering.

- Risk: duplicate client init if provider `init()` is called more than once.
  Mitigation: singleton guard — second `init()` call is a no-op.

- Risk: recipes plugin relies on `normalizeProductRecord()` which will not be removed — it stays as internal state normalizer; provider hits pass through it since it handles both provider and legacy field names.
  Mitigation: no action needed — both shapes are handled by existing `normalizeProductRecord()` logic.

---

## Dependencies

- **go4taste-recipes-plugin** `feature/use-ads-data-provider` — companion PR; removes direct `algoliaIndex.search()` and `window.algoliasearch()` from `creator.js`, uses `G4TAdsProvider` instead. Already implemented and QA-passed alongside this PR.
- PHP foundation (registry, payload, render, recipe-bottom-strip) already on main from PR #15 — no changes needed there.

---

## Merge readiness

All stages implemented and QA-passed. Both bugs found during QA diagnosed and fixed. Follow-up item 8 (input styling) is cosmetic and does not block merge.
- Unblocks: recipes plugin follow-up PR `feature/use-ads-data-provider`.
