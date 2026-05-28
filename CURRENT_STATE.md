# CURRENT_STATE

Aktualny snapshot architektury pluginu go4taste-ads po zakonczeniu migracji VSA (Fazy 1-8).

## 1. Runtime ownership (aktualny)

1. Entry point: go4taste-ads.php
- laduje Composer autoload
- laduje core/bootstrap.php
- uruchamia g4t_bootstrap_features()
- nie korzysta z Go4Taste_Ads::get_instance()

2. Core boundaries: core/
- core/api-client/product-data.php
- core/api-client/class-go4taste-ads-api-fetcher.php
- core/i18n/index.php
- core/i18n/loader.php
- core/lifecycle/class-go4taste-ads-activator.php
- core/lifecycle/class-go4taste-ads-deactivator.php

3. Feature slices: features/
- features/admin-settings
- features/banner-sidebar-widget
- features/metabox-banner-sidebar-widget
- features/algolia-product-sync
- features/banner-inline-block
- features/ga4-tracking

4. Legacy classes usuniete z runtime orchestracji
- includes/class-go4taste-ads.php
- includes/class-go4taste-ads-loader.php
- public/class-go4taste-ads-public.php

## 2. Hook strategy (aktualny)

Hooki sa rejestrowane feature-local, bez centralnego loadera:

- admin_menu, admin_init, wp_ajax_g4t_test_prestashop_permissions, admin_enqueue_scripts: features/admin-settings/index.php
- widgets_init, plugins_loaded, wp_enqueue_scripts: features/banner-sidebar-widget/index.php
- add_meta_boxes, save_post, wp_ajax_g4t_fetch_product_preview, admin_enqueue_scripts: features/metabox-banner-sidebar-widget/index.php
- wp_ajax_g4t_sync_start, wp_ajax_g4t_sync_batch, wp_ajax_g4t_sync_status, wp_ajax_g4t_sync_unlock: features/algolia-product-sync/index.php
- init, wp_theme_json_data_theme: features/banner-inline-block/index.php
- plugins_loaded (textdomain): core/i18n/index.php

## 3. Block ownership (aktualny)

Inline banner block jest w pelni co-located w feature slice:

- Source: features/banner-inline-block/src/
- Build: features/banner-inline-block/build/
- Registration + render callback: features/banner-inline-block/render.php

npm scripts:
- npm run build -> wp-scripts build --webpack-src-dir=features/banner-inline-block/src --output-path=features/banner-inline-block/build
- npm run start -> wp-scripts start --webpack-src-dir=features/banner-inline-block/src --output-path=features/banner-inline-block/build

## 4. API fetcher loading strategy

Jedno miejsce ladowania klasy fetchera:

- g4t_core_get_api_fetcher() w core/api-client/product-data.php

Konsumenci:
- g4t_core_fetch_product_data()
- features/algolia-product-sync/sync-ajax-handlers.php
- features/admin-settings/settings-page.php

## 5. Delivered business features

1. Inline banner Gutenberg block (SSR + theme template override)
2. Sidebar banner widget (compat mode)
3. Promoted product metabox + preview AJAX
4. Admin settings page + API permissions test
5. Product catalog sync do Algolia (async AJAX)
6. PrestaShop/WooCommerce integrations
7. GA4 view_promotion payload for inline banner
8. Brak aktywnej komendy WP-CLI w runtime pluginu

## 6. Known gaps

1. Brak plugin-owned automated test suite (PHP/JS).
2. Duza odpowiedzialnosc pozostaje w core/api-client/class-go4taste-ads-api-fetcher.php (kandydat do dalszej dekompozycji po release).

## 7. Summary

Migracja do feature-first VSA dla runtime hook ownership jest zakonczona.
Asset ownership zostal rozdzielony per feature (sidebar/admin-settings/metabox preview), bez wspolnych bucket slices.
Statyczna czesc finalnej bramy release (Phase 10) jest zakonczona: lint/build/diagnostyka OK.
Pozostaje manual smoke test (WP Admin + frontend) dla final merge readiness.
