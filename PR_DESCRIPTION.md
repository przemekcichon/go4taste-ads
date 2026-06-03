# PR: Picker Data Extensibility — Provider Contract for Creator-Native UI

**Branch:** feature/picker-ui-extensibility -> main

## Implementation status

| Stage | Status | Description |
|---|---|---|
| Stage 1: Picker context contract | 🔲 Next | Standardize context/variant inputs passed to picker runtime |
| Stage 2: Preview payload filters | 🔲 Next | Add hooks for preview payload and image URL overrides |
| Stage 3: Template override boundary | 🔲 Next | Add template path override for picker preview rendering |
| Stage 4: Surface adapters | 🔲 Next | Wire metabox, term, global settings, and external adapters to context contract |
| Stage 5: Docs + QA matrix | 🔲 Next | Publish hook contract and validate backward compatibility |

---

## Context update: creator persistence resolved in recipes plugin

The product persistence issue that was blocking QA of this integration has been diagnosed and fixed on the recipes plugin side. The root cause was ACF's security field filtering — see [go4taste-recipes-plugin PR_DESCRIPTION.md](../go4taste-recipes-plugin/PR_DESCRIPTION.md) for full details.

In short: ACF strips any `$_POST['acf']` keys that don't belong to a field group declared in `acf_form()`. Because `group_recipe_ads` was not in the creator's `field_groups` config, ACF removed `field_recipe_ads_products` from the POST data before the save hook fired. The fix submits product IDs as a flat top-level POST field (`g4t_recipe_ads_product_ids_json`) outside `acf[...]`, which ACF does not touch.

As a result of that fix, the creator now:
- saves selected Algolia product IDs reliably to `_g4t_a_recipe_bottom_product_ids`,
- loads them back correctly on edit,
- queries Algolia directly via credentials passed from PHP (`G4T_RECIPE_CREATOR_DATA.adsPicker`).

The ads plugin is not involved in the save path. Its current role in the integration is:
- providing Algolia credentials via `go4taste_ads_options`,
- providing `picker-ui.js` and picker CSS assets (enqueued but not actively driving creator UI).

The provider contract work described below remains relevant for future surfaces (metabox, term edit, global settings) and for making the data layer formally extensible — but it is no longer a blocker for the creator persistence fix.

---

## Motivation

Recipe creator needs to keep its own frontend layout (`product-list`, `.search-box`, `selected-strip`) while reusing Ads logic as data/provider layer.
This PR scope in ads plugin should therefore focus on stable provider contract and hooks, not enforcing the standard picker UI on every surface.

Main goals:
- keep one shared provider/runtime contract,
- allow external surfaces to consume data with their own UI,
- preserve backward compatibility for existing sidebar/metabox/term settings flows,
- avoid duplicating product resolution and preview payload mapping across plugins.

---

## Desired outcome

After this implementation:
1. Surfaces can declare context/variant/metadata to the Ads provider contract.
2. External adapters (like recipe creator) can consume data without mounting default picker UI widgets.
3. Preview payload and template path remain overrideable via stable hooks.
4. Existing admin/widget surfaces keep current behavior by default.

---

## Proposed architecture

### 1) Context and variant contract

Every picker init call should pass:
- context key (for example: metabox, term_edit, global_settings, recipe_creator),
- style variant (for example: default, creator, compact),
- optional metadata (post_id, term_id, screen_id).

### 2) Shared preview payload extension

Preview generation should expose extension hooks for:
- full preview payload,
- preview image URL,
- optional labels/metadata.

### 3) Template override boundary

Preview renderer should support template path override via filter.
Default template remains current one.

### 4) Surface adapters

Each surface keeps ownership of UI rendering and user interactions.
Ads contract provides:
- context/variant declaration support,
- product/preview data boundary,
- no requirement to reuse default `.ais-*` UI controls on custom surfaces.

### 5) Backward compatibility

If no new filters are used:
- runtime behavior and visuals remain identical to current implementation.

---

## Suggested public hook surface

- `go4taste/ads/picker_preview_payload`
- `go4taste/ads/picker_preview_image_url`
- `go4taste/ads/picker_preview_template_path`
- `go4taste/ads/picker_style_variant`
- `go4taste/ads/picker_context`

---

## Validation plan

### Must pass

1. Existing sidebar widget picker flow still works without configuration changes.
2. Existing term edit picker flow still works without configuration changes.
3. Existing global settings picker flow still works without configuration changes.
4. External adapter can consume provider contract without using default picker DOM controls.

### Follow-up verification

5. Cross-surface QA confirms creator can keep native UI while consuming Ads data/provider contract.
6. Documentation examples for hook usage in companion plugins/themes.

---

## Risks and mitigations

- Risk: visual regressions in existing admin flows.
  Mitigation: preserve default template and variant as fallback.

- Risk: fragmented extension points.
  Mitigation: publish one small, stable hook contract.

- Risk: duplicated logic in adapters.
  Mitigation: keep payload/template decisions in shared picker boundary.

---

## Decision points

- Final naming for context and variant keys.
- Whether to include optional preview HTML cache in this PR or defer.

---

## Merge readiness

- This branch is planning scope only at this moment.
- Creator persistence is unblocked (fixed in recipes plugin).
- Merge readiness will be reassessed after Stage 1–4 implementation and QA.
