# Go4Taste Ads: Full Next Migration Plan (VSA)

## Scope
This plan covers only remaining, not-yet-migrated areas in go4taste-ads:
1. inline-banner-block (Gutenberg block for inline products)
2. core i18n loading
3. ga4-tracking (view_promotion dataLayer flow)

## Current State Snapshot
1. Block registration and render callback are still owned by includes/class-go4taste-ads.php.
2. i18n is still owned by includes/class-go4taste-ads.php + includes/class-go4taste-ads-i18n.php.
3. GA4 logic is embedded directly in src/blocks/inline-banner/render.php (and mirrored build/blocks/inline-banner/render.php).
4. core/bootstrap.php already loads migrated feature slices, but not inline-banner-block and not core/i18n module.

## Target Ownership
1. features/inline-banner-block owns:
- block registration hooks
- render callback and rendering helpers
- block source/build co-location and build output path

2. core/i18n owns:
- plugins_loaded textdomain registration
- translation loader class/functionality

3. features/ga4-tracking owns:
- GA4 payload building
- view_promotion script output contract
- tracking toggle read helper integration with admin-settings option

## Phase Plan

### Phase 1: Prepare Boundaries (No behavior change)
1. Add core/i18n module:
- core/i18n/index.php
- core/i18n/loader.php (or class-i18n-loader.php)
2. Add features/inline-banner-block scaffold:
- features/inline-banner-block/index.php
- features/inline-banner-block/render.php
- features/inline-banner-block/helpers.php
3. Add features/ga4-tracking scaffold:
- features/ga4-tracking/index.php
- features/ga4-tracking/tracking-events.php
- features/ga4-tracking/payload-builder.php
4. Wire new modules in core/bootstrap.php (loaded but passive where needed).

DoD:
1. Plugin boots with no runtime changes.
2. No hook collisions.
3. No PHP errors in new files.

### Phase 2: Migrate i18n to core/i18n
1. Move textdomain loading from includes/class-go4taste-ads.php to core/i18n.
2. Replace Go4Taste_Ads_i18n usage with featureless core loader function/class.
3. Remove includes/class-go4taste-ads-i18n.php from active dependency chain.

DoD:
1. plugins_loaded still loads textdomain go4taste-ads.
2. Existing translations work exactly as before.
3. includes/class-go4taste-ads.php no longer owns i18n hook.

### Phase 3: Migrate inline-banner-block ownership
1. Move block registration logic from includes/class-go4taste-ads.php into features/inline-banner-block/index.php.
2. Move render callback to features/inline-banner-block/render.php.
3. Move/centralize block editor localization payload (Algolia app/key/index, maxProducts) into inline-banner-block slice.
4. Keep template override behavior and filter contract unchanged:
- g4t_ads_inline_banner_template_path
5. Keep border theme-json adjustment with block scope and migrate ownership if still needed by block slice.

DoD:
1. includes/class-go4taste-ads.php no longer contains register_blocks/render_inline_banner_block/enable_border_settings.
2. Block renders identically on frontend.
3. Editor product selection still works.

### Phase 4: Extract ga4-tracking from block template
1. Remove direct dataLayer payload composition from inline banner render template.
2. Move payload building to features/ga4-tracking/payload-builder.php.
3. Expose one integration point for inline-banner-block, for example:
- apply_filters('g4t_ads_inline_banner_ga4_items', ...)
or
- direct function call in tracking slice API.
4. Keep setting key backward compatibility:
- go4taste_ads_options[ga4_view_promotion_datalayer]
5. Keep output event contract unchanged:
- event = view_promotion
- ecommerce.items schema unchanged

DoD:
1. Inline-banner template has no GA4 business logic.
2. GA4 on/off toggle still controls script output.
3. dataLayer event payload remains backward-compatible.

### Phase 5: Co-locate block source/build artifacts
1. Move src/blocks/inline-banner into features/inline-banner-block/src.
2. Move build/blocks/inline-banner into features/inline-banner-block/build.
3. Update package.json build paths/scripts to output inside feature folder.
4. Update registration path references to feature-local build path.

DoD:
1. npm build produces block artifacts in feature folder.
2. register_block_type_from_metadata points to feature-local build directory.
3. No stale references to old src/build block paths.

### Phase 6: Final Orchestrator Reduction
1. Remove now-unused methods/dependencies from includes/class-go4taste-ads.php.
2. Keep only minimal core responsibilities that are still not migrated.
3. If empty enough, replace with thinner compatibility wrapper and migrate remaining hooks to feature/core modules.

DoD:
1. No inline-banner/i18n/ga4 responsibilities in includes/class-go4taste-ads.php.
2. Loader/orchestrator usage reduced to minimum transitional footprint.

## Validation Checklist Per Phase
1. WP admin loads plugin page without errors.
2. Block editor: inline banner block inserts, edits, and saves.
3. Frontend: inline banners render, prices and links unchanged.
4. GA4 toggle OFF: no view_promotion push.
5. GA4 toggle ON: one valid view_promotion push with expected items.
6. Translation domain loads and strings remain translatable.

## Risk Controls
1. Keep event and option contracts stable to avoid analytics regressions.
2. Do not change meta keys or data formats in this migration.
3. Keep template override path/filter backward-compatible.
4. Migrate in small commits per phase with smoke tests after each phase.

## Recommended Execution Order
1. Phase 1
2. Phase 2
3. Phase 3
4. Phase 4
5. Phase 5
6. Phase 6
