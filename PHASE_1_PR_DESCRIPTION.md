# Phase 1: VSA Scaffolding for Remaining Areas

## Commit
`7ccec03` — chore(vsa): Phase 1 scaffolding — prepare boundaries for i18n, inline-banner-block, ga4-tracking

## What Changed

### New Modules Created (Scaffolds)

#### 1. **core/i18n/** — Core i18n Module
- `core/i18n/index.php` — Hook registration for textdomain loading
- `core/i18n/loader.php` — `g4t_load_plugin_textdomain()` function

**Purpose:** Feature-agnostic internationalization. All features can rely on textdomain being loaded early.

#### 2. **features/inline-banner-block/** — Block Slice
- `features/inline-banner-block/index.php` — Hook registration
- `features/inline-banner-block/render.php` — Block type registration + render callback + border settings
- `features/inline-banner-block/helpers.php` — Option getter helpers

**Purpose:** Owns Gutenberg block registration, server-side rendering, and theme integration.

#### 3. **features/ga4-tracking/** — GA4 Tracking Slice
- `features/ga4-tracking/index.php` — Module init
- `features/ga4-tracking/tracking-events.php` — Event schemas and item normalization
- `features/ga4-tracking/payload-builder.php` — Payload building and script output

**Purpose:** Centralized GA4 view_promotion event building (payload still incomplete in templates — Phase 4 extracts this).

### Orchestrator Cleanup

**includes/class-go4taste-ads.php** — Removed 150+ lines:
- ❌ `set_locale()` method
- ❌ `define_block_hooks()` method
- ❌ `enable_border_settings()` method
- ❌ `register_blocks()` method
- ❌ `render_inline_banner_block()` method
- ❌ Go4Taste_Ads_i18n dependency from `load_dependencies()`

### Bootstrap Updated

**core/bootstrap.php** — Now loads:
```php
core/i18n/index.php                        // New
features/inline-banner-block/index.php     // New
features/ga4-tracking/index.php            // New
```

## Behavior Impact
✅ **NONE** — All new modules are scaffolds. Functions are defined but not yet integrated into rendering flow. Plugin boots with no visible changes.

## Next Steps (Phase 2-6)
See [VSA_FULL_MIGRATION_PLAN_NEXT.md](./VSA_FULL_MIGRATION_PLAN_NEXT.md) for full 6-phase roadmap:

1. **Phase 1** ✅ DONE — Prepare boundaries
2. **Phase 2** — Migrate i18n (move textdomain loading to core module)
3. **Phase 3** — Migrate inline-banner-block ownership (activate block registration)
4. **Phase 4** — Extract ga4-tracking (move GA4 logic from templates)
5. **Phase 5** — Co-locate block artifacts (move src/build paths to feature)
6. **Phase 6** — Final orchestrator reduction

## How to Validate

1. **Plugin boots:**
   ```bash
   wp plugin verify-plugin go4taste-ads
   ```

2. **No PHP errors:**
   - Check error logs
   - Verify syntax: `php -l core/i18n/index.php`, etc.

3. **Block still renders:**
   - Create post with inline-banner block
   - Verify frontend output unchanged

4. **Admin settings still load:**
   - Open Go4Taste Ads → Settings
   - Verify GA4 toggle checkbox visible

5. **Translations available:**
   - Check language files still load
   - Verify any translatable strings

## Risk Assessment
- **LOW**: New files are scaffolds only. No behavior changes except removing unused orchestrator methods.
- **Backward compatibility**: All template paths, option keys, filter hooks remain unchanged.
- **Migration safety**: Each new module is self-contained (no cross-dependencies).

## Notes for Reviewers
- This commit prepares the codebase for Phases 2-6
- No functional changes occur — purely organizational restructuring
- All new functions are helpers/definitions without invocation in rendering flow yet
- Recommend merge after smoke tests pass
