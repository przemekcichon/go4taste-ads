# Go4Taste Ads: VSA Migration Completion + Release Gate

## Status

Migracja VSA zaplanowana w fazach 1-6 zostala wykonana i rozszerzona do faz 7-9:

1. Phase 1: scaffolding core/i18n + inline-banner-block + ga4-tracking
2. Phase 2: i18n ownership moved to core/i18n
3. Phase 3: inline-banner render odciety od Go4Taste_Ads singleton
4. Phase 4: GA4 extracted from block template to feature slice
5. Phase 5: block source/build co-located in features/inline-banner-block
6. Phase 6: legacy orchestrator and central hook loader removed
7. Phase 7: remote smoke verification confirmed
8. Phase 8: API fetcher loading unified via core boundary helper
9. Phase 9: documentation cleanup after loader-flow removal

## Current architecture checkpoint

1. Brak runtime uzycia Go4Taste_Ads::get_instance() i Go4Taste_Ads_Loader.
2. Hook ownership jest feature-local lub core-local (bez centralnego orchestratora).
3. Inline-banner block assets sa co-located w feature slice.
4. API fetcher loading ma jeden wspolny boundary helper: g4t_core_get_api_fetcher().

## Remaining step (Phase 10)

Finalna brama release:

1. Lint/build gate
- php -l changed PHP files
- npm run build

2. Smoke gate
- admin settings render + permissions AJAX
- product sync start/batch/status/unlock
- promoted product metabox + preview AJAX
- inline banner block (editor + frontend render)
- public/admin assets enqueue

3. PR/release gate
- PR_DESCRIPTION contains phases 1-9 and validation notes
- no stale references to removed loader/orchestrator flow in active docs
- branch ready for merge to main

## Out of scope for this release

1. Deep dekompozycja core/api-client/class-go4taste-ads-api-fetcher.php.
2. Building automated test suite (PHP/JS) from scratch.
