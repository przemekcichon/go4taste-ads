# PR: VSA extraction of legacy-sidebar-widget + promoted-product-metabox + product-catalog-sync

**Branch:** chore/vertical-slices-architecture → main

## Motywacja

Kontynuujemy migrację pluginu do True Vertical Slices Architecture zgodnie z nowym README i guardrails.
W tym PR wydzielono trzy obszary: legacy sidebar widget, promoted product metabox (rejestracja, zapis i preview AJAX) oraz product catalog sync (asynchroniczny sync do Algolia).

## Zakres zmian

### 1. Wydzielenie feature slice: legacy-sidebar-widget

Dodano nowy, autonomiczny slice:

- features/legacy-sidebar-widget/index.php
- features/legacy-sidebar-widget/widget-class.php
- features/legacy-sidebar-widget/widget-template.php

Feature sam rejestruje własne hooki:

- widgets_init -> rejestracja widgetu
- plugins_loaded -> sygnał kompatybilności g4taste_product_ads_sidebar_regular_widget_product_id_function_loaded

### 2. Migracja rejestracji hooka ze starego orchestratora

Usunięto widget wiring ze starej klasy centralnej:

- includes/class-go4taste-ads.php

Usunięte podpięcia:

- widgets_init -> register_widgets
- plugins_loaded -> load_product_id_function

### 3. Odcięcie widgetu od Go4Taste_Ads::get_instance() w samym slice

Widget w nowej lokalizacji nie korzysta już z singletona orchestratora.
Wprowadzono core boundary dla pobierania danych:

- core/api-client/product-data.php

Boundary udostępnia helpery:

- g4t_core_get_ads_options()
- g4t_core_get_sidebar_widget_product_id()
- g4t_core_fetch_product_data()

### 4. Bootstrap feature’ów przez core

Dodano nowy bootstrap:

- core/bootstrap.php

Entry point pluginu ładuje teraz feature slices przez:

- go4taste-ads.php -> g4t_bootstrap_features()

### 5. Cleanup w legacy admin class

Usunięto widget-only metody ze starej klasy admin:

- get_prestashop_product_id()
- load_product_id_function()
- register_widgets()

Plik:

- admin/class-go4taste-ads-admin.php

### 6. Usunięcie shimów kompatybilności

Po potwierdzeniu smoke testów usunięto shimy kompatybilności:

- admin/class-go4taste-ads-sidebar-regular-widget.php
- public/partials/go4taste-ads-sidebar-regular-widget-public-display.php

### 7. Wydzielenie feature slice: promoted-product-metabox

Dodano nowy, autonomiczny slice:

- features/promoted-product-metabox/index.php
- features/promoted-product-metabox/metabox-registration.php
- features/promoted-product-metabox/preview-template.php

Feature sam rejestruje własne hooki:

- add_meta_boxes -> g4t_promoted_product_metabox_add_meta_box
- save_post -> g4t_promoted_product_metabox_save_meta_box
- wp_ajax_g4t_fetch_product_preview -> g4t_promoted_product_metabox_fetch_product_preview
- admin_enqueue_scripts -> g4t_promoted_product_metabox_enqueue_preview_styles

Dodatkowo:

- usunięto metabox wiring z includes/class-go4taste-ads.php
- usunięto metabox/preview metody z admin/class-go4taste-ads-admin.php
- core/bootstrap.php ładuje teraz także features/promoted-product-metabox/index.php

### 8. Wydzielenie feature slice: product-catalog-sync

Dodano nowy, autonomiczny slice:

- features/product-catalog-sync/index.php
- features/product-catalog-sync/sync-runner.php
- features/product-catalog-sync/sync-ajax-handlers.php

Feature sam rejestruje własne hooki:

- wp_ajax_g4t_sync_start -> g4t_product_catalog_sync_ajax_start
- wp_ajax_g4t_sync_batch -> g4t_product_catalog_sync_ajax_batch
- wp_ajax_g4t_sync_status -> g4t_product_catalog_sync_ajax_status
- wp_ajax_g4t_sync_unlock -> g4t_product_catalog_sync_ajax_unlock

Dodatkowo:

- usunięto sync AJAX wiring z includes/class-go4taste-ads.php
- usunięto sync helpers i sync AJAX metody z admin/class-go4taste-ads-admin.php
- core/bootstrap.php ładuje teraz także features/product-catalog-sync/index.php

### 9. Usunięcie jednorazowych sekcji migracyjnych z panelu admin

Po zakończeniu migracji danych usunięto z UI sekcje jednorazowe:

- Update Product IDs from Shortcode
- Migrate Legacy Shortcode Banners to Inline Banners

Usunięto powiązaną logikę runtime:

- admin_init -> handle_update_product_ids (odpięte)
- wp_ajax_g4t_migration_* (odpięte)
- metody update/migration w admin/class-go4taste-ads-admin.php (usunięte)
- migracyjny flow w admin/js/go4taste-ads-admin.js (usunięty)

### 10. Wydzielenie feature slice: admin-settings

Dodano nowy, autonomiczny slice:

- features/admin-settings/index.php
- features/admin-settings/settings-page.php

Feature sam rejestruje własne hooki:

- admin_menu -> rejestracja strony ustawień
- admin_init -> rejestracja settings/fields
- wp_ajax_g4t_test_prestashop_permissions -> test uprawnień API

Dodatkowo:

- usunięto admin settings i permissions hook wiring z includes/class-go4taste-ads.php
- usunięto metody settings/permissions z admin/class-go4taste-ads-admin.php
- core/bootstrap.php ładuje teraz także features/admin-settings/index.php

### 11. Wydzielenie feature slice: admin-assets

Dodano nowy, autonomiczny slice:

- features/admin-assets/index.php

Feature sam rejestruje własny hook:

- admin_enqueue_scripts -> g4t_admin_assets_enqueue

W slice osadzono:

- enqueue admin CSS/JS
- enqueue bibliotek Algolia dla panelu
- wp_localize_script payload dla sync i testu uprawnień API

Dodatkowo:

- usunięto zależność od Go4Taste_Ads_Admin z includes/class-go4taste-ads.php
- admin hooks w orchestratorze są teraz puste (feature-local ownership)
- core/bootstrap.php ładuje teraz także features/admin-assets/index.php

### 12. Finalny cleanup: usunięcie legacy admin class

Po wydzieleniu wszystkich odpowiedzialności usunięto plik:

- admin/class-go4taste-ads-admin.php

To domyka dekompozycję dawnego "god object" i usuwa martwy kod z repozytorium.

### 13. Cleanup orchestratora po migracji admin

Uproszczono includes/class-go4taste-ads.php:

- usunięto property admin_instance
- usunięto metody get_public_instance() i get_admin_instance()
- usunięto pustą metodę define_admin_hooks() i jej wywołanie z konstruktora

Orchestrator zachowuje tylko odpowiedzialności core (locale/public/block), a hooki admin są w feature slices.

## Weryfikacja

- Brak błędów statycznych/kompilacyjnych w zmienionych plikach.
- widgets_init jest rejestrowany tylko w feature slice.
- W kodzie slice legacy-sidebar-widget nie występuje Go4Taste_Ads::get_instance().
- Hook kompatybilności g4taste_product_ads_sidebar_regular_widget_product_id_function_loaded jest emitowany z feature slice.
- add_meta_boxes/save_post/wp_ajax_g4t_fetch_product_preview są rejestrowane w feature slice promoted-product-metabox.
- wp_ajax_g4t_sync_* są rejestrowane w feature slice product-catalog-sync.
- Brak referencji runtime do go4taste_update_product_ids i g4t_migration_*.
- admin_menu/admin_init/wp_ajax_g4t_test_prestashop_permissions są rejestrowane w feature slice admin-settings.
- admin_enqueue_scripts jest rejestrowany w feature slice admin-assets.
- Brak runtime referencji do Go4Taste_Ads_Admin.
- Brak runtime referencji do admin_instance/get_public_instance/get_admin_instance.

## Wpływ i kompatybilność

- Brak zmian kontraktu danych dla legacy widgetu (meta key pozostaje bez zmian):
  - _g4t_a_sidebar_regular_widget_ps_product_id
- Legacy include paths shimów zostały usunięte po potwierdzeniu braku regresji.
- Funkcjonalność widgetu, metaboxu, syncu, ustawień i admin assets pozostaje zachowana, ale logika i hooki zostały przeniesione do vertical slices.

### 14. VSA Phase 1: scaffolding granic dla i18n, inline-banner-block, ga4-tracking

Dodano trzy nowe moduły bez zmiany zachowania (passive scaffolds):

**core/i18n/**
- `core/i18n/index.php` — rejestracja hooka `plugins_loaded`
- `core/i18n/loader.php` — funkcja `g4t_load_plugin_textdomain()`

**features/inline-banner-block/**
- `features/inline-banner-block/index.php` — rejestracja hooków bloku
- `features/inline-banner-block/render.php` — `g4t_inline_banner_register_block_type()`, `g4t_inline_banner_render_block()`, `g4t_inline_banner_enable_border_settings()`
- `features/inline-banner-block/helpers.php` — helpery opcji bloku

**features/ga4-tracking/**
- `features/ga4-tracking/index.php` — init modułu
- `features/ga4-tracking/tracking-events.php` — schematy eventów GA4, normalizacja itemów
- `features/ga4-tracking/payload-builder.php` — budowanie payloadu `view_promotion`, output inline script

Dodatkowo:

- usunięto z `includes/class-go4taste-ads.php`: `set_locale()`, `define_block_hooks()`, `enable_border_settings()`, `register_blocks()`, `render_inline_banner_block()`, dependency `Go4Taste_Ads_i18n`
- `core/bootstrap.php` ładuje teraz `core/i18n/index.php`, `features/inline-banner-block/index.php`, `features/ga4-tracking/index.php`

**Fix regressji:** przywrócono `g4tAdsBlockData` jako nazwę obiektu `wp_localize_script` oraz brakujące klucze `algoliaSearchKey` i `algoliaIndexName` (zmiana nazwy z Phase 1 zepsuła wyszukiwarkę produktów w block editorze).

### 15. VSA Phase 2: migracja i18n do core/i18n, usunięcie legacy klasy

- Usunięto `includes/class-go4taste-ads-i18n.php` (47 linii martwego kodu)
- `g4t_load_plugin_textdomain()` w `core/i18n/loader.php` jest teraz jedynym właścicielem ładowania textdomainu
- Ścieżka do `/languages/` identyczna jak poprzednio (`go4taste-ads/languages/`)
- Brak zmiany zachowania

### 16. VSA Phase 3: usunięcie singletona Go4Taste_Ads z block render template

- Zamieniono `Go4Taste_Ads::get_instance()->fetch_product_data()` na `g4t_core_fetch_product_data()` z core boundary
- Usunięto zbędne zmienne lokalne `$p_value`, `$lang`, `$api_type`, `$api_url` — core boundary wyciąga je wewnętrznie z `$options`
- Zmiana zastosowana w `src/blocks/inline-banner/render.php` oraz `build/blocks/inline-banner/render.php`
- Brak zmiany renderowanego HTML
- `features/inline-banner-block` jest teraz jedynym właścicielem hooków bloku; template nie ma zależności do orchestratora

### 17. VSA Phase 4: ekstrakcja GA4 z block render template do features/ga4-tracking

- Usunięto inline logikę GA4 z `src/blocks/inline-banner/render.php` i `build/blocks/inline-banner/render.php`
- Usunięto `$ga4_view_promotion_enabled` — decyzja o outputcie przeniesiona do `g4t_ga4_is_tracking_enabled()` w feature slice
- Usunięto warunek `if ( $ga4_view_promotion_enabled )` w pętli foreach — items zawsze się zbierają, payload-builder sam sprawdza flagę
- Zastąpiono cały blok `<script>` wywołaniem `g4t_ga4_output_view_promotion_script( $ga4_items )` z `features/ga4-tracking/payload-builder.php`
- Kontrakt eventu GA4 niezmieniony: `event: view_promotion`, `ecommerce.items` z identycznym schematem
- Zmiana zastosowana w `src/blocks` i `build/blocks` (identyczne szablony)

### 18. VSA Phase 5: co-location artefaktow bloku inline-banner wewnatrz feature slice

- Przeniesiono kod zrodlowy bloku z `src/blocks/inline-banner/` do `features/inline-banner-block/src/`
- Przeniesiono artefakty build z `build/blocks/inline-banner/` do `features/inline-banner-block/build/`
- Zaktualizowano `g4t_inline_banner_register_block_type()` i `g4t_inline_banner_render_block()`:
  - metadata path: `features/inline-banner-block/build/block.json`
  - fallback template path: `features/inline-banner-block/build/render.php`
- Zaktualizowano skrypty npm:
  - `npm run build` -> `wp-scripts build --webpack-src-dir=features/inline-banner-block/src --output-path=features/inline-banner-block/build`
  - `npm run start` -> `wp-scripts start --webpack-src-dir=features/inline-banner-block/src --output-path=features/inline-banner-block/build`
- Efekt: pelna co-lokacja PHP + JS/CSS + block assets dla inline-banner w jednym feature folderze

### 19. VSA Phase 6: finalny cleanup orchestratora i central hook loadera

- Entry point `go4taste-ads.php` nie uruchamia juz `Go4Taste_Ads::get_instance()->run()`
- Usunieto legacy orchestrator i centralny loader:
  - `includes/class-go4taste-ads.php`
  - `includes/class-go4taste-ads-loader.php`
  - `public/class-go4taste-ads-public.php`
- Dodano feature slice `features/public-assets/index.php` jako nowego wlasciciela hooka `wp_enqueue_scripts` dla frontowego CSS pluginu
- Przeniesiono ladowanie komendy WP-CLI do `core/bootstrap.php` (conditional include przy `WP_CLI`), aby zachowac dotychczasowa funkcjonalnosc CLI bez zaleznosci od orchestratora
- Weryfikacja: brak referencji do `Go4Taste_Ads::get_instance` i `Go4Taste_Ads_Loader` w kodzie wykonywanym pluginu

### 20. Hotfix po Phase 6: brak klasy `Go4Taste_Ads_API_Fetcher` na ekranie ustawien

- Naprawiono krytyczny blad na stronie opcji przy polu "Prestashop Webservice API Key"
- W `features/admin-settings/settings-page.php` dodano lazy-loader `get_api_fetcher()`:
  - `require_once` klasy fetchera gdy klasa nie jest jeszcze zaladowana
  - bezpieczny fallback (`null`) zamiast fatala
- Zaktualizowano oba miejsca uzycia fetchera (callback pola + AJAX test uprawnien), aby korzystaly z `get_api_fetcher()` i zwracaly czytelny komunikat bledu zamiast zatrzymania strony

### 21. VSA Phase 8: ujednolicenie ladowania API Fetchera przez core boundary

- Dodano wspolny helper `g4t_core_get_api_fetcher()` w `core/api-client/product-data.php`
  - centralizuje lazy-load klasy fetchera
  - zwraca `Go4Taste_Ads_API_Fetcher|null`
- `g4t_core_fetch_product_data()` korzysta teraz z `g4t_core_get_api_fetcher()` (bez lokalnej duplikacji)
- `features/product-catalog-sync/sync-ajax-handlers.php` korzysta z helpera core w `ajax_start` i `ajax_batch`
  - usunieto duplikowane `class_exists + require_once + new`
  - dodano kontrolowany blad `500` i status job `failed`, gdy fetcher jest niedostepny
- `features/admin-settings/settings-page.php` korzysta teraz z helpera core zamiast lokalnej metody `get_api_fetcher()`
  - usunieto duplikacje ladowania klasy w slice settings

### 22. VSA Phase 9: porzadki koncowe dokumentacji po usunieciu legacy loader flow

- Zaktualizowano `CURRENT_STATE.md` do aktualnej architektury po Fazach 1-8
  - usunieto nieaktualne odniesienia do `includes/class-go4taste-ads.php`, `includes/class-go4taste-ads-loader.php`, `public/class-go4taste-ads-public.php`
  - opisano aktualny feature-first runtime i ownership hookow
- Przepisano `VSA_FULL_MIGRATION_PLAN_NEXT.md` do postaci `Completion + Release Gate` zamiast planu migracji pre-Phase 6
  - status: migracja VSA zakonczona przez Fazy 1-8
  - pozostawiono tylko checklist do finalnej bramy release

### 23. Dodatkowy cleanup legacy katalogow i co-location assetow

- Przeniesiono assety admin do feature slice:
  - `admin/css/go4taste-ads-admin.css` -> `features/admin-assets/assets/css/go4taste-ads-admin.css`
  - `admin/js/go4taste-ads-admin.js` -> `features/admin-assets/assets/js/go4taste-ads-admin.js`
- Przeniesiono publiczny CSS do feature slice:
  - `public/css/go4taste-ads-public.css` -> `features/public-assets/assets/css/go4taste-ads-public.css`
- Zaktualizowano sciezki enqueue w:
  - `features/admin-assets/index.php`
  - `features/public-assets/index.php`
  - `features/promoted-product-metabox/metabox-registration.php`
- Usunieto legacy puste szkielety katalogow/pliki placeholder:
  - `admin/index.php`, `public/index.php`
  - puste katalogi `admin/`, `public/`, `src/`, `build/`

### 24. Cleanup runtime includes: lifecycle + API fetcher moved to core

- Przeniesiono runtime lifecycle do `core/lifecycle/`:
  - `includes/class-go4taste-ads-activator.php` -> `core/lifecycle/class-go4taste-ads-activator.php`
  - `includes/class-go4taste-ads-deactivator.php` -> `core/lifecycle/class-go4taste-ads-deactivator.php`
- Entry point `go4taste-ads.php` laduje teraz lifecycle z `core/lifecycle/*`
- Przeniesiono API fetcher do core boundary:
  - `includes/class-go4taste-ads-api-fetcher.php` -> `core/api-client/class-go4taste-ads-api-fetcher.php`
  - `g4t_core_get_api_fetcher()` laduje klase z nowej lokalizacji core
- Usunieto runtime WP-CLI command:
  - usuniety plik `includes/class-go4taste-ads-wp-cli-command.php`
  - usuniete ladowanie WP-CLI command z `core/bootstrap.php`
- Usunieto `includes/index.php` i caly katalog `includes/` (brak pozostalosci runtime i placeholderow)

## Następne kroki (po tym PR)

- Phase 10: finalna brama release (lint/build + smoke test + merge readiness)
