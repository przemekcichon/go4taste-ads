# PR: Ads Extensibility — Placement Registry + Shared Payload Resolver + Recipe Strip

**Branch:** feature/banner-extensibility -> main

## Implementation status

| Phase | Status | Description |
|---|---|---|
| Phase 1: Core registry | ✅ Done | Placement registry, resolver API, compatibility bridge |
| Phase 2: Existing placements → registry | ✅ Done | Inline block + sidebar widget wired through registry |
| Phase 2b: Shared payload resolver | ✅ Done | Normalized product payload, render dispatcher, theme template override |
| Phase 2c: Recipe bottom strip slice | ✅ Done | New `recipe_bottom_strip` placement + helper for recipes plugin |
| Phase 3: Recipes plugin integration | ✅ Done | Replace `g4t_fetch_ads_products()` with shared resolver + backward compat fallback |
| Phase 4: ACF form adapter for recipe bottom strip | ✅ Done | Implemented in `go4taste-recipes-plugin`: picker UI + save to `_g4t_a_recipe_bottom_product_ids` |
| Phase 5: Cache invalidation | 🔲 Next | Per-placement cache keys + invalidation hooks |

---

## Motivation

The current ads plugin already supports two concrete banner entry paths: an inline Gutenberg block and a sidebar widget. That works for the present use case, but it does not yet provide a clean extension model for future ways of adding banners or rendering them in other contexts such as a theme-level ACF form flow.

This plan defines how to turn the current implementation into a reusable banner platform where new placements can be added without duplicating data access, product resolution, or frontend rendering logic.

Main goals:
- keep existing block and widget behavior intact,
- introduce a stable placement registry for new banner entry points,
- separate banner data storage from banner rendering,
- make theme-level or external-plugin extensions possible without touching core placement logic,
- keep all runtime dependencies aligned with the VSA rules already used in the repository.

---

## Desired outcome

After the next implementation phase, the plugin should support these ideas:

1. A placement is a declared capability, not a hardcoded branch in a single renderer.
2. Different UI surfaces can register or edit banner product IDs, but all of them persist the same JSON array contract.
3. Rendering uses a shared resolver and normalized payload, regardless of whether the source is Gutenberg, widget settings, term meta, theme settings, or an ACF front-end form.
4. A theme or companion plugin can add a new placement by registering metadata, a save surface, and a render adapter, without forking existing logic.

---

## Proposed architecture

### 1) Core banner registry

Create a lightweight registry in `go4taste-ads` that stores banner placements as structured definitions.

Each placement definition should include:
- placement key,
- label and description for admin UI,
- storage scope (`postmeta`, `termmeta`, `option`, or custom adapter),
- data key or option key,
- supported contexts where the placement may render,
- resolver callback for reading raw IDs,
- renderer callback or template hook for emitting markup.

This registry becomes the single source of truth for what placements exist.

### 2) Shared resolver layer

Move all banner product retrieval behind one resolver that:
- reads the JSON array contract first,
- falls back to legacy single-string keys only where required,
- normalizes all IDs into one format,
- returns a render-ready payload for the frontend.

This resolver should not know whether the data came from a metabox, a widget, a theme setting, or an ACF form. It only resolves a placement key into products.

### 3) Shared render payload

Standardize the render payload so every placement returns the same shape.

The payload should at minimum contain:
- placement key,
- source type,
- normalized product list,
- display metadata,
- optional CTA/label values,
- optional template variant.

The existing frontend, including the theme, should consume this payload and stay ignorant of how the data was entered.

### 4) Adapter-based entry points

New ways of adding banners should be implemented as adapters, not as separate parallel systems.

Examples:
- Gutenberg block adapter,
- sidebar widget adapter,
- term edit screen adapter,
- main settings page adapter,
- theme ACF front-end form adapter,
- external plugin adapter.

Each adapter should only handle its own UI and save flow, then write into the same placement contract.

### 5) Theme integration boundary

If banner entry lives in the theme, the theme should only provide:
- form/template markup,
- optional styling,
- placement registration or filter configuration.

The theme should not own product resolution or banner payload construction. Those remain in `go4taste-ads`.

---

## Implementation phases

### ✅ Phase 1: Core abstraction — DONE

**Files added:**
- `core/placements/registry.php`

**What was delivered:**
- `g4t_ads_add_placement_definition()` — builder for a placement definition array with sanitized fields.
- `g4t_ads_get_registered_placements()` — returns all placements collected through `go4taste/ads/register_placement` filter.
- `g4t_ads_get_current_page_product_ids_by_placement()` — calls each placement's `current_page_product_ids_callback` and groups results by key.
- `g4t_ads_get_current_page_product_ids()` — flat merged list across all placements for compatibility.
- `g4t_ads_collect_registered_placements_current_page_product_ids()` — compatibility bridge wired into existing `g4t_ads_current_page_product_ids` filter at priority 5.
- `core/bootstrap.php` updated to load `registry.php` before feature slices.

**Public hook introduced:**
- `go4taste/ads/register_placement` — filter where any slice, theme, or external plugin registers its placement definition.

---

### ✅ Phase 2a: Existing placements onto registry — DONE

**Files changed:**
- `features/banner-inline-block/index.php`
- `features/banner-inline-block/helpers.php`
- `features/banner-sidebar-widget/index.php`

**What was delivered:**
- Extracted `g4t_inline_banner_get_current_page_product_ids_for_placement()` — pure product ID resolver for inline block, no filter coupling.
- `g4t_inline_banner_collect_current_page_product_ids()` kept as backward-compatible wrapper.
- `g4t_inline_banner_register_placement()` registered via `go4taste/ads/register_placement`.
- Old direct `add_filter('g4t_ads_current_page_product_ids', ...)` replaced in inline block.
- Extracted `g4t_banner_sidebar_widget_get_current_page_product_ids_for_placement()` — delegates to cascade resolver.
- `g4t_banner_sidebar_widget_collect_current_page_product_ids()` kept as backward-compatible wrapper.
- `g4t_banner_sidebar_widget_register_placement()` registered via `go4taste/ads/register_placement`.
- Old direct `add_filter('g4t_ads_current_page_product_ids', ...)` replaced in sidebar widget.

---

### ✅ Phase 2b: Shared payload resolver — DONE

**Files added:**
- `core/placements/payload.php`
- `core/placements/render.php`

**What was delivered:**

`payload.php`:
- `g4t_ads_normalize_product_item()` — canonical flat array for one product, consumed by all renderers; shape: `id, name, price, price_tax_exc, price_without_reduction, picture, link, category, image_width, image_height`.
- `g4t_ads_build_placement_payload()` — builds normalized payload for a placement key + product ID list. Calls API fetcher per ID, normalizes each item, returns `{ placement_key, source, products[], context }`.
- `g4t_ads_build_current_page_payload()` — builds payloads for all current-page placements in one call.

`render.php`:
- `g4t_ads_get_placement_template_path()` — resolves template with theme override support. Theme lookup order: `go4taste-ads/{key}.php`, then `go4taste-ads/placement.php`. Plugin fallback: `core/placements/templates/{key}.php`.
- `g4t_ads_render_placement()` — dispatcher: prefers `render_callback` from registry, falls back to template. Returns HTML string. Can be called from theme or any external code.

**Public hooks introduced:**
- `go4taste/ads/placement_product_item` — filter per product item before it enters payload.
- `go4taste/ads/resolve_placement_payload` — filter on the complete payload after resolution.
- `go4taste/ads/before_render_placement` — action before render starts.
- `go4taste/ads/render_placement` — filter on final HTML output.
- `go4taste/ads/placement_template_path` — filter on resolved template path.

---

### ✅ Phase 2c: Recipe bottom strip placement — DONE

**Files added:**
- `features/recipe-bottom-strip/index.php`
- `features/recipe-bottom-strip/helpers.php`

**What was delivered:**

`index.php`:
- Registers placement `recipe_bottom_strip` through `go4taste/ads/register_placement`.
- Storage key: `_g4t_a_recipe_bottom_product_ids` (JSON array contract).
- Legacy fallback: `_g4t_a_recipe_bottom_ps_product_ids`.
- Resolver: `g4t_recipe_bottom_strip_get_current_page_product_ids()` — reads post meta on `is_singular()`.

`helpers.php`:
- `g4t_recipe_bottom_strip_get_products_for_post( int $post_id )` — integration point for `go4taste-recipes-plugin` view-model builder. Reads post meta directly (independent of `get_queried_object_id()`), calls shared `g4t_ads_build_placement_payload()`, returns normalized products array ready for Timber templates.

`core/bootstrap.php` updated to load both `helpers.php` and `index.php` for this slice.

---

### ✅ Phase 3: Recipes plugin integration — DONE

**File changed:**
- `go4taste-recipes-plugin/features/recipe-display/view-model-builder.php`

**What was delivered:**
- `g4t_build_single_recipe_view_model()` now calls `g4t_recipe_bottom_strip_get_products_for_post($post_id)` when the function is available (guarded with `function_exists()`).
- Double fallback preserved for backward compatibility:
  1. If go4taste-ads is inactive — falls back to local `g4t_fetch_ads_products()`.
  2. If new meta key `_g4t_a_recipe_bottom_product_ids` is empty but old ACF repeater data exists — also falls back to `g4t_fetch_ads_products()` so existing recipes render correctly without migration.
- `ads.enabled` now reflects whether products were actually resolved, not just whether IDs exist in ACF meta.
- `g4t_fetch_ads_products()` is NOT removed — retained as legacy fallback function.

---

### ✅ Phase 4: ACF form adapter for recipe bottom strip — DONE (recipes plugin)

**Scope:**
- Add a picker UI to the `go4taste-recipes-plugin` ACF front-end form (last step of recipe wizard).
- Save selected product IDs as `_g4t_a_recipe_bottom_product_ids` (JSON array) in post meta.
- Picker should reuse the existing `G4TAdsPicker` JS module from `banner-sidebar-widget/assets/js/picker-ui.js`.
- The save path writes only to the meta key; no logic duplication with the resolver.
- This lives in `go4taste-recipes-plugin` or in the theme — `go4taste-ads` core is not modified.

**Status detail:**
- Implemented in `go4taste-recipes-plugin` frontend creator (step 6 picker adapter).
- Persisted `_g4t_a_recipe_bottom_product_ids` using the JSON array contract.
- Added backward-compatible fallback to legacy `recipe_ads_products` source in creator edit mode.
- Added post-save synchronization path for the new meta key.

---

### 🔲 Phase 5: Cache and invalidation — TODO

**Scope:**
- Add per-placement transient cache layer in `g4t_ads_build_placement_payload()`.
- Cache key: `g4t_ads_payload_{placement_key}_{md5(sorted_ids)}`.
- Soft TTL: configurable (default 24h), hard TTL (default 7d) matching existing banner cache settings.
- Invalidation hook `go4taste/ads/invalidate_placement_cache` — fire on post save, term save, and product sync.
- Invalidation scope: by placement key, by product ID, or site-wide.

---

## Suggested hook surface

The implementation should expose a small, stable public surface. Suggested hooks:

- `go4taste/ads/register_placement`
- `go4taste/ads/resolve_placement_payload`
- `go4taste/ads/render_placement`
- `go4taste/ads/invalidate_placement_cache`
- `go4taste/ads/placement_supported_contexts`
- `go4taste/ads/placement_storage_key`

These hooks allow external code to add new banner entry points while keeping the core resolver in charge of data shape and rendering rules.

---

## Data contract

The universal storage contract should remain the same across all placements:

- storage format: JSON encoded array of strings,
- example: `["154", "89"]`,
- empty state: `""` or `[]` when explicitly cleared,
- legacy fallback: allowed only where already required for compatibility.

The important rule is that the placement type changes, but the contract does not.

---

## Placement model

Each placement should be able to define:

- where it can be edited,
- how the saved data is stored,
- how it is rendered on the frontend,
- which contexts can consume it,
- whether it uses a template part or direct markup,
- whether it is site-wide, taxonomy-scoped, or post-scoped.

This gives us a clean path for banners in the theme, in another plugin, or in a future admin surface.

---

## Risks and compatibility

- Risk: duplicated logic if each adapter starts resolving products on its own. Mitigation: require adapters to call the shared resolver only.
- Risk: breaking current Gutenberg/widget behavior during refactor. Mitigation: keep existing placement keys and only move them behind the registry.
- Risk: theme-level code becoming a second source of truth. Mitigation: theme code may register UI, but it should never own the canonical data contract.
- Risk: cache invalidation gaps after product updates. Mitigation: tie invalidation to save hooks and product change hooks from day one.

---

## Validation plan

### Already verifiable now

1. Existing inline block and sidebar widget banner flows render exactly as before — both are now backed by registry placement definitions, compatibility collector bridges keep `g4t_ads_current_page_product_ids` filter working for any existing consumer.
2. A new placement (`recipe_bottom_strip`) was registered without editing the existing widget or block code.
3. All placement resolvers accept the same JSON array contract via `_g4t_a_*_product_ids` meta keys.
4. `g4t_ads_render_placement('recipe_bottom_strip')` can be called from the theme and will produce output if the meta key is populated — no custom resolver logic needed in the theme.

### Pending verification (Phase 3–5)

5. Cache invalidation after editing placement data and after product sync — not yet implemented.
6. ✅ ACF form picker saves `_g4t_a_recipe_bottom_product_ids` and the strip renders on the next page load.
7. ✅ `go4taste-recipes-plugin` view model delegates to shared resolver when go4taste-ads is active; falls back gracefully when it is not.

---

## Public hook surface (current state)

All hooks are live and stable:

| Hook | Type | Description |
|---|---|---|
| `go4taste/ads/register_placement` | filter | Register a placement definition |
| `go4taste/ads/placement_product_item` | filter | Modify one product item before it enters payload |
| `go4taste/ads/resolve_placement_payload` | filter | Modify complete placement payload after resolution |
| `go4taste/ads/before_render_placement` | action | Fires before render starts |
| `go4taste/ads/render_placement` | filter | Modify or replace final HTML output |
| `go4taste/ads/placement_template_path` | filter | Override resolved template path |
| `go4taste/ads/current_page_product_ids_by_placement` | filter | Modify grouped product ID map |

Legacy filter `g4t_ads_current_page_product_ids` remains functional via compatibility bridge.

---

## Decision point

~~This document intentionally proposes the platform approach only.~~

The platform foundation is implemented. Phases 1 and 2 are complete.

**Remaining decisions before Phase 5:**
- Confirm Phase 5 cache scope: per post, per placement+ids hash, or both.

**Resolved decisions:**
- `go4taste-recipes-plugin` guards the call with `function_exists()` — soft dependency confirmed.
- ACF form picker for recipe bottom strip lives in `go4taste-recipes-plugin` (co-located with creator UI and save flow).
