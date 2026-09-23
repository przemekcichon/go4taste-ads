# Bitbucket pull requests archive — go4taste-ads

Exported 2026-09-23 from `bitbucket.org/przemekcichon/go4taste-ads` before the move to GitHub.
The code of every PR is already in this repo's history (merge commits `Merged in … (pull request #N)`);
this file keeps the descriptions and review comments, which did not migrate. PR numbers are Bitbucket numbers.

| # | title | state | branch | merged |
|---|---|---|---|---|
| [1](#pr-1) | Feature/theme banner templates | MERGED | `feature/theme-banner-templates` → `main` | `36b61da` |
| [2](#pr-2) | refactor(vsa): extract legacy sidebar widget into feature slice | MERGED | `chore/vertical-slices-architecture` → `main` | `66a2d76` |
| [3](#pr-3) | docs: mark smoke checks complete and ready for merge | MERGED | `chore/vsa-postmerge-pr-description` → `main` | `4d78dec` |
| [4](#pr-4) | docs: align README and VSA developer agent with current architecture | MERGED | `chore/vsa-postmerge-pr-description` → `main` | `68d938b` |
| [5](#pr-5) | Chore/rename banner slices | MERGED | `chore/rename-banner-slices` → `main` | `68d938b` |
| [6](#pr-6) | refactor(vsa): rename sync and metabox feature slices | MERGED | `chore/rename-sync-metabox-slices` → `main` | `22f0a90` |
| [7](#pr-7) | feat(algolia): add ad server provider, soft-cache and daily sync cron | MERGED | `feature/algolia-ad-server-soft-cache-cron` → `main` | `647e73a` |
| [8](#pr-8) | Feature/webservice only api cleanup | MERGED | `feature/webservice-only-api-cleanup` → `main` | `ee58549` |
| [9](#pr-9) | Hotfix/inline banner algolia client path | MERGED | `hotfix/inline-banner-algolia-client-path` → `main` | `50d8ff9` |
| [10](#pr-10) | feat(cache): add admin bar soft-cache actions and page-scoped purge | MERGED | `feature/admin-bar-soft-cache-actions` → `main` | `1346167` |
| [11](#pr-11) | feat(cache): add on-demand soft/hard cache product counts in options page | MERGED | `feature/cache-counts-ui` → `main` | `d98b2be` |
| [12](#pr-12) | feat(sync): remove legacy cache, optimize sync pipeline, fix reliability | MERGED | `feature/sync-pipeline-refactor` → `main` | `f734a24` |
| [13](#pr-13) | refactor(sidebar-widget): unify VSA slice, JSON contract, and archive settings | MERGED | `feature/vsa-sidebar-widget-refactor` → `main` | `04ef5da` |
| [14](#pr-14) | chore(github): remove unused agents and add planner/implementation agents | MERGED | `chore/github-agents-cleanup-planner` → `main` | `3fa3fec` |
| [15](#pr-15) | Feature/banner extensibility | MERGED | `feature/banner-extensibility` → `main` | `3f6808c` |
| [16](#pr-16) | docs(pr): add picker-ui extensibility plan | MERGED | `feature/picker-ui-extensibility` → `main` | `7475ecf` |
| [17](#pr-17) | Feature/use ads data provider | MERGED | `feature/use-ads-data-provider` → `main` | `c92484d` |
| [18](#pr-18) | Feature/specific prices dual pricing | MERGED | `feature/specific-prices-dual-pricing` → `main` | `8c16073` |

---

<a id="pr-1"></a>
## #1 Feature/theme banner templates

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/theme-banner-templates` → `main`
- **Created:** 2026-05-17 · **Closed:** 2026-05-17
- **Merge commit:** `36b61da051b9`

# PR: Configurable max products, theme template override, render callback, default cache

**Branch:** `feature/theme-banner-templates` → `main`

## Motywacja

Blok `go4taste-ads/inline-banner` miał hardcodowany limit 3 produktów oraz brak mechanizmu pozwalającego motywowi podmienić szablon renderowania. Każde wczytanie strony z blokiem generowało synchroniczne żądania HTTP do PrestaShop API bez cachowania, co powodowało znaczny spadek wydajności.

## Zakres zmian

### `includes/class-go4taste-ads.php`

* Dodano `render_inline_banner_block()` jako server-side render callback rejestrowany przez `register_block_type_from_metadata()`.
* Callback szuka szablonu w kolejności:

    1. `{theme}/go4taste-ads/inline-banner.php`
    2. `{theme}/go4taste-ads-inline-banner.php`
    3. Fallback do `build/blocks/inline-banner/render.php`
    4. Filterowalny path przez `g4t_ads_inline_banner_template_path`
    
* `maxProducts` lokalizowane przez `wp_localize_script()` z filtrem `g4t_ads_inline_banner_max_products` \(domyślnie `null` = bez limitu\).

### `includes/class-go4taste-ads-api-fetcher.php`

* Domyślna wartość `cache_duration` zmieniona z `0` \(wyłączony\) na `24` godziny.
* Eliminuje N synchronicznych HTTP requestów do PrestaShop przy każdym renderze strony.

### `src/blocks/inline-banner/edit.js`

* Usunięto hardcodowany limit 3 produktów.
* `getMaxProducts()` odczytuje wartość z `window.g4tAdsBlockData.maxProducts`.
* Gdy `maxProducts` jest `null` lub `0` — brak limitu.

### `src/blocks/inline-banner/block.json`

* Zaktualizowano opis bloku \(usunięto wzmianekę o „1–3 produktach"\).

### `src/blocks/inline-banner/render.php` \+ `build/blocks/inline-banner/*`

* Zaktualizowany render fallback zgodny z nowym API renderowania.
* Zsynchronizowane artefakty buildowe.

## Weryfikacja

* Dodanie >3 produktów w edytorze działa poprawnie.
* Po ustawieniu `cache_duration > 0` strona z 4 produktami ładuje się bez opóźnienia przy kolejnych wejściach.
* Theme override \(`go4taste-ads/inline-banner.php` w motywie\) jest wykrywany i używany zamiast fallbacku.

## Brak breaking changes

* Bloki bez wybranych produktów renderują pusty string \(bez zmian\).
* Istniejące posty z ≤3 produktami działają bez modyfikacji.
* `cache_duration` ustawione ręcznie w opcjach pluginu ma pierwszeństwo przed domyślnym `24h`.

‌

---

<a id="pr-2"></a>
## #2 refactor(vsa): extract legacy sidebar widget into feature slice

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/vertical-slices-architecture` → `main`
- **Created:** 2026-05-27 · **Closed:** 2026-05-28
- **Merge commit:** `66a2d7655b7e`

# PR: VSA extraction of legacy-sidebar-widget \+ promoted-product-metabox \+ product-catalog-sync

**Branch:** chore/vertical-slices-architecture → main

## Motywacja

Kontynuujemy migrację pluginu do True Vertical Slices Architecture zgodnie z nowym README i guardrails. W tym PR wydzielono trzy obszary: legacy sidebar widget, promoted product metabox \(rejestracja, zapis i preview AJAX\) oraz product catalog sync \(asynchroniczny sync do Algolia\).

## Zakres zmian

### 1. Wydzielenie feature slice: legacy-sidebar-widget

Dodano nowy, autonomiczny slice:

* features/legacy-sidebar-widget/index.php
* features/legacy-sidebar-widget/widget-class.php
* features/legacy-sidebar-widget/widget-template.php

Feature sam rejestruje własne hooki:

* widgets\_init -> rejestracja widgetu
* plugins\_loaded -> sygnał kompatybilności g4taste\_product\_ads\_sidebar\_regular\_widget\_product\_id\_function\_loaded

### 2. Migracja rejestracji hooka ze starego orchestratora

Usunięto widget wiring ze starej klasy centralnej:

* includes/class-go4taste-ads.php

Usunięte podpięcia:

* widgets\_init -> register\_widgets
* plugins\_loaded -> load\_product\_id\_function

### 3. Odcięcie widgetu od Go4Taste\_Ads::get\_instance\(\) w samym slice

Widget w nowej lokalizacji nie korzysta już z singletona orchestratora. Wprowadzono core boundary dla pobierania danych:

* core/api-client/product-data.php

Boundary udostępnia helpery:

* g4t\_core\_get\_ads\_options\(\)
* g4t\_core\_get\_sidebar\_widget\_product\_id\(\)
* g4t\_core\_fetch\_product\_data\(\)

### 4. Bootstrap feature’ów przez core

Dodano nowy bootstrap:

* core/bootstrap.php

Entry point pluginu ładuje teraz feature slices przez:

* go4taste-ads.php -> g4t\_bootstrap\_features\(\)

### 5. Cleanup w legacy admin class

Usunięto widget-only metody ze starej klasy admin:

* get\_prestashop\_product\_id\(\)
* load\_product\_id\_function\(\)
* register\_widgets\(\)

Plik:

* admin/class-go4taste-ads-admin.php

### 6. Usunięcie shimów kompatybilności

Po potwierdzeniu smoke testów usunięto shimy kompatybilności:

* admin/class-go4taste-ads-sidebar-regular-widget.php
* public/partials/go4taste-ads-sidebar-regular-widget-public-display.php

### 7. Wydzielenie feature slice: promoted-product-metabox

Dodano nowy, autonomiczny slice:

* features/promoted-product-metabox/index.php
* features/promoted-product-metabox/metabox-registration.php
* features/promoted-product-metabox/preview-template.php

Feature sam rejestruje własne hooki:

* add\_meta\_boxes -> g4t\_promoted\_product\_metabox\_add\_meta\_box
* save\_post -> g4t\_promoted\_product\_metabox\_save\_meta\_box
* wp\_ajax\_g4t\_fetch\_product\_preview -> g4t\_promoted\_product\_metabox\_fetch\_product\_preview
* admin\_enqueue\_scripts -> g4t\_promoted\_product\_metabox\_enqueue\_preview\_styles

Dodatkowo:

* usunięto metabox wiring z includes/class-go4taste-ads.php
* usunięto metabox/preview metody z admin/class-go4taste-ads-admin.php
* core/bootstrap.php ładuje teraz także features/promoted-product-metabox/index.php

### 8. Wydzielenie feature slice: product-catalog-sync

Dodano nowy, autonomiczny slice:

* features/product-catalog-sync/index.php
* features/product-catalog-sync/sync-runner.php
* features/product-catalog-sync/sync-ajax-handlers.php

Feature sam rejestruje własne hooki:

* wp\_ajax\_g4t\_sync\_start -> g4t\_product\_catalog\_sync\_ajax\_start
* wp\_ajax\_g4t\_sync\_batch -> g4t\_product\_catalog\_sync\_ajax\_batch
* wp\_ajax\_g4t\_sync\_status -> g4t\_product\_catalog\_sync\_ajax\_status
* wp\_ajax\_g4t\_sync\_unlock -> g4t\_product\_catalog\_sync\_ajax\_unlock

Dodatkowo:

* usunięto sync AJAX wiring z includes/class-go4taste-ads.php
* usunięto sync helpers i sync AJAX metody z admin/class-go4taste-ads-admin.php
* core/bootstrap.php ładuje teraz także features/product-catalog-sync/index.php

### 9. Usunięcie jednorazowych sekcji migracyjnych z panelu admin

Po zakończeniu migracji danych usunięto z UI sekcje jednorazowe:

* Update Product IDs from Shortcode
* Migrate Legacy Shortcode Banners to Inline Banners

Usunięto powiązaną logikę runtime:

* admin\_init -> handle\_update\_product\_ids \(odpięte\)
* wp\_ajax\_g4t\_migration\_\* \(odpięte\)
* metody update/migration w admin/class-go4taste-ads-admin.php \(usunięte\)
* migracyjny flow w admin/js/go4taste-ads-admin.js \(usunięty\)

### 10. Wydzielenie feature slice: admin-settings

Dodano nowy, autonomiczny slice:

* features/admin-settings/index.php
* features/admin-settings/settings-page.php

Feature sam rejestruje własne hooki:

* admin\_menu -> rejestracja strony ustawień
* admin\_init -> rejestracja settings/fields
* wp\_ajax\_g4t\_test\_prestashop\_permissions -> test uprawnień API

Dodatkowo:

* usunięto admin settings i permissions hook wiring z includes/class-go4taste-ads.php
* usunięto metody settings/permissions z admin/class-go4taste-ads-admin.php
* core/bootstrap.php ładuje teraz także features/admin-settings/index.php

### 11. Wydzielenie feature slice: admin-assets

Dodano nowy, autonomiczny slice:

* features/admin-assets/index.php

Feature sam rejestruje własny hook:

* admin\_enqueue\_scripts -> g4t\_admin\_assets\_enqueue

W slice osadzono:

* enqueue admin CSS/JS
* enqueue bibliotek Algolia dla panelu
* wp\_localize\_script payload dla sync i testu uprawnień API

Dodatkowo:

* usunięto zależność od Go4Taste\_Ads\_Admin z includes/class-go4taste-ads.php
* admin hooks w orchestratorze są teraz puste \(feature-local ownership\)
* core/bootstrap.php ładuje teraz także features/admin-assets/index.php

### 12. Finalny cleanup: usunięcie legacy admin class

Po wydzieleniu wszystkich odpowiedzialności usunięto plik:

* admin/class-go4taste-ads-admin.php

To domyka dekompozycję dawnego "god object" i usuwa martwy kod z repozytorium.

### 13. Cleanup orchestratora po migracji admin

Uproszczono includes/class-go4taste-ads.php:

* usunięto property admin\_instance
* usunięto metody get\_public\_instance\(\) i get\_admin\_instance\(\)
* usunięto pustą metodę define\_admin\_hooks\(\) i jej wywołanie z konstruktora

Orchestrator zachowuje tylko odpowiedzialności core \(locale/public/block\), a hooki admin są w feature slices.

## Weryfikacja

* Brak błędów statycznych/kompilacyjnych w zmienionych plikach.
* widgets\_init jest rejestrowany tylko w feature slice.
* W kodzie slice legacy-sidebar-widget nie występuje Go4Taste\_Ads::get\_instance\(\).
* Hook kompatybilności g4taste\_product\_ads\_sidebar\_regular\_widget\_product\_id\_function\_loaded jest emitowany z feature slice.
* add\_meta\_boxes/save\_post/wp\_ajax\_g4t\_fetch\_product\_preview są rejestrowane w feature slice promoted-product-metabox.
* wp\_ajax\_g4t\_sync\_\* są rejestrowane w feature slice product-catalog-sync.
* Brak referencji runtime do go4taste\_update\_product\_ids i g4t\_migration\_\*.
* admin\_menu/admin\_init/wp\_ajax\_g4t\_test\_prestashop\_permissions są rejestrowane w feature slice admin-settings.
* admin\_enqueue\_scripts jest rejestrowany w feature slice admin-assets.
* Brak runtime referencji do Go4Taste\_Ads\_Admin.
* Brak runtime referencji do admin\_instance/get\_public\_instance/get\_admin\_instance.

## Wpływ i kompatybilność

* Brak zmian kontraktu danych dla legacy widgetu \(meta key pozostaje bez zmian\):

    * \_g4t\_a\_sidebar\_regular\_widget\_ps\_product\_id
    
* Legacy include paths shimów zostały usunięte po potwierdzeniu braku regresji.
* Funkcjonalność widgetu, metaboxu, syncu, ustawień i admin assets pozostaje zachowana, ale logika i hooki zostały przeniesione do vertical slices.

### 14. VSA Phase 1: scaffolding granic dla i18n, inline-banner-block, ga4-tracking

Dodano trzy nowe moduły bez zmiany zachowania \(passive scaffolds\):

**core/i18n/**

* `core/i18n/index.php` — rejestracja hooka `plugins_loaded`
* `core/i18n/loader.php` — funkcja `g4t_load_plugin_textdomain()`

**features/inline-banner-block/**

* `features/inline-banner-block/index.php` — rejestracja hooków bloku
* `features/inline-banner-block/render.php` — `g4t_inline_banner_register_block_type()`, `g4t_inline_banner_render_block()`, `g4t_inline_banner_enable_border_settings()`
* `features/inline-banner-block/helpers.php` — helpery opcji bloku

**features/ga4-tracking/**

* `features/ga4-tracking/index.php` — init modułu
* `features/ga4-tracking/tracking-events.php` — schematy eventów GA4, normalizacja itemów
* `features/ga4-tracking/payload-builder.php` — budowanie payloadu `view_promotion`, output inline script

Dodatkowo:

* usunięto z `includes/class-go4taste-ads.php`: `set_locale()`, `define_block_hooks()`, `enable_border_settings()`, `register_blocks()`, `render_inline_banner_block()`, dependency `Go4Taste_Ads_i18n`
* `core/bootstrap.php` ładuje teraz `core/i18n/index.php`, `features/inline-banner-block/index.php`, `features/ga4-tracking/index.php`

**Fix regressji:** przywrócono `g4tAdsBlockData` jako nazwę obiektu `wp_localize_script` oraz brakujące klucze `algoliaSearchKey` i `algoliaIndexName` \(zmiana nazwy z Phase 1 zepsuła wyszukiwarkę produktów w block editorze\).

### 15. VSA Phase 2: migracja i18n do core/i18n, usunięcie legacy klasy

* Usunięto `includes/class-go4taste-ads-i18n.php` \(47 linii martwego kodu\)
* `g4t_load_plugin_textdomain()` w `core/i18n/loader.php` jest teraz jedynym właścicielem ładowania textdomainu
* Ścieżka do `/languages/` identyczna jak poprzednio \(`go4taste-ads/languages/`\)
* Brak zmiany zachowania

### 16. VSA Phase 3: usunięcie singletona Go4Taste\_Ads z block render template

* Zamieniono `Go4Taste_Ads::get_instance()->fetch_product_data()` na `g4t_core_fetch_product_data()` z core boundary
* Usunięto zbędne zmienne lokalne `$p_value`, `$lang`, `$api_type`, `$api_url` — core boundary wyciąga je wewnętrznie z `$options`
* Zmiana zastosowana w `src/blocks/inline-banner/render.php` oraz `build/blocks/inline-banner/render.php`
* Brak zmiany renderowanego HTML
* `features/inline-banner-block` jest teraz jedynym właścicielem hooków bloku; template nie ma zależności do orchestratora

### 17. VSA Phase 4: ekstrakcja GA4 z block render template do features/ga4-tracking

* Usunięto inline logikę GA4 z `src/blocks/inline-banner/render.php` i `build/blocks/inline-banner/render.php`
* Usunięto `$ga4_view_promotion_enabled` — decyzja o outputcie przeniesiona do `g4t_ga4_is_tracking_enabled()` w feature slice
* Usunięto warunek `if ( $ga4_view_promotion_enabled )` w pętli foreach — items zawsze się zbierają, payload-builder sam sprawdza flagę
* Zastąpiono cały blok `<script>` wywołaniem `g4t_ga4_output_view_promotion_script( $ga4_items )` z `features/ga4-tracking/payload-builder.php`
* Kontrakt eventu GA4 niezmieniony: `event: view_promotion`, `ecommerce.items` z identycznym schematem
* Zmiana zastosowana w `src/blocks` i `build/blocks` \(identyczne szablony\)

### 18. VSA Phase 5: co-location artefaktow bloku inline-banner wewnatrz feature slice

* Przeniesiono kod zrodlowy bloku z `src/blocks/inline-banner/` do `features/inline-banner-block/src/`
* Przeniesiono artefakty build z `build/blocks/inline-banner/` do `features/inline-banner-block/build/`
* Zaktualizowano `g4t_inline_banner_register_block_type()` i `g4t_inline_banner_render_block()`:

    * metadata path: `features/inline-banner-block/build/block.json`
    * fallback template path: `features/inline-banner-block/build/render.php`
    
* Zaktualizowano skrypty npm:

    * `npm run build` -> `wp-scripts build --webpack-src-dir=features/inline-banner-block/src --output-path=features/inline-banner-block/build`
    * `npm run start` -> `wp-scripts start --webpack-src-dir=features/inline-banner-block/src --output-path=features/inline-banner-block/build`
    
* Efekt: pelna co-lokacja PHP \+ JS/CSS \+ block assets dla inline-banner w jednym feature folderze

### 19. VSA Phase 6: finalny cleanup orchestratora i central hook loadera

* Entry point `go4taste-ads.php` nie uruchamia juz `Go4Taste_Ads::get_instance()->run()`
* Usunieto legacy orchestrator i centralny loader:

    * `includes/class-go4taste-ads.php`
    * `includes/class-go4taste-ads-loader.php`
    * `public/class-go4taste-ads-public.php`
    
* Dodano feature slice `features/public-assets/index.php` jako nowego wlasciciela hooka `wp_enqueue_scripts` dla frontowego CSS pluginu
* Przeniesiono ladowanie komendy WP-CLI do `core/bootstrap.php` \(conditional include przy `WP_CLI`\), aby zachowac dotychczasowa funkcjonalnosc CLI bez zaleznosci od orchestratora
* Weryfikacja: brak referencji do `Go4Taste_Ads::get_instance` i `Go4Taste_Ads_Loader` w kodzie wykonywanym pluginu

### 20. Hotfix po Phase 6: brak klasy `Go4Taste_Ads_API_Fetcher` na ekranie ustawien

* Naprawiono krytyczny blad na stronie opcji przy polu "Prestashop Webservice API Key"
* W `features/admin-settings/settings-page.php` dodano lazy-loader `get_api_fetcher()`:

    * `require_once` klasy fetchera gdy klasa nie jest jeszcze zaladowana
    * bezpieczny fallback \(`null`\) zamiast fatala
    
* Zaktualizowano oba miejsca uzycia fetchera \(callback pola \+ AJAX test uprawnien\), aby korzystaly z `get_api_fetcher()` i zwracaly czytelny komunikat bledu zamiast zatrzymania strony

### 21. VSA Phase 8: ujednolicenie ladowania API Fetchera przez core boundary

* Dodano wspolny helper `g4t_core_get_api_fetcher()` w `core/api-client/product-data.php`

    * centralizuje lazy-load klasy fetchera
    * zwraca `Go4Taste_Ads_API_Fetcher|null`
    
* `g4t_core_fetch_product_data()` korzysta teraz z `g4t_core_get_api_fetcher()` \(bez lokalnej duplikacji\)
* `features/product-catalog-sync/sync-ajax-handlers.php` korzysta z helpera core w `ajax_start` i `ajax_batch`

    * usunieto duplikowane `class_exists + require_once + new`
    * dodano kontrolowany blad `500` i status job `failed`, gdy fetcher jest niedostepny
    
* `features/admin-settings/settings-page.php` korzysta teraz z helpera core zamiast lokalnej metody `get_api_fetcher()`

    * usunieto duplikacje ladowania klasy w slice settings
    

### 22. VSA Phase 9: porzadki koncowe dokumentacji po usunieciu legacy loader flow

* Zaktualizowano `CURRENT_STATE.md` do aktualnej architektury po Fazach 1-8

    * usunieto nieaktualne odniesienia do `includes/class-go4taste-ads.php`, `includes/class-go4taste-ads-loader.php`, `public/class-go4taste-ads-public.php`
    * opisano aktualny feature-first runtime i ownership hookow
    
* Przepisano `VSA_FULL_MIGRATION_PLAN_NEXT.md` do postaci `Completion + Release Gate` zamiast planu migracji pre-Phase 6

    * status: migracja VSA zakonczona przez Fazy 1-8
    * pozostawiono tylko checklist do finalnej bramy release
    

### 23. Dodatkowy cleanup legacy katalogow i co-location assetow

* Przeniesiono assety admin do feature slice:

    * `admin/css/go4taste-ads-admin.css` -> `features/admin-assets/assets/css/go4taste-ads-admin.css`
    * `admin/js/go4taste-ads-admin.js` -> `features/admin-assets/assets/js/go4taste-ads-admin.js`
    
* Przeniesiono publiczny CSS do feature slice:

    * `public/css/go4taste-ads-public.css` -> `features/public-assets/assets/css/go4taste-ads-public.css`
    
* Zaktualizowano sciezki enqueue w:

    * `features/admin-assets/index.php`
    * `features/public-assets/index.php`
    * `features/promoted-product-metabox/metabox-registration.php`
    
* Usunieto legacy puste szkielety katalogow/pliki placeholder:

    * `admin/index.php`, `public/index.php`
    * puste katalogi `admin/`, `public/`, `src/`, `build/`
    

### 24. Cleanup runtime includes: lifecycle \+ API fetcher moved to core

* Przeniesiono runtime lifecycle do `core/lifecycle/`:

    * `includes/class-go4taste-ads-activator.php` -> `core/lifecycle/class-go4taste-ads-activator.php`
    * `includes/class-go4taste-ads-deactivator.php` -> `core/lifecycle/class-go4taste-ads-deactivator.php`
    
* Entry point `go4taste-ads.php` laduje teraz lifecycle z `core/lifecycle/*`
* Przeniesiono API fetcher do core boundary:

    * `includes/class-go4taste-ads-api-fetcher.php` -> `core/api-client/class-go4taste-ads-api-fetcher.php`
    * `g4t_core_get_api_fetcher()` laduje klase z nowej lokalizacji core
    
* Usunieto runtime WP-CLI command:

    * usuniety plik `includes/class-go4taste-ads-wp-cli-command.php`
    * usuniete ladowanie WP-CLI command z `core/bootstrap.php`
    
* Usunieto `includes/index.php` i caly katalog `includes/` \(brak pozostalosci runtime i placeholderow\)

### 25. Option B: rozdzial ownership `public-assets` i `admin-assets` do docelowych feature slices

* Usunieto bucket slices i ich runtime hook ownership:

    * `features/public-assets/index.php`
    * `features/admin-assets/index.php`
    * usuniete katalogi `features/public-assets/` i `features/admin-assets/`
    
* `core/bootstrap.php` nie laduje juz `public-assets`/`admin-assets`
* Frontend sidebar CSS jest teraz wlascicielem slice `legacy-sidebar-widget`:

    * `features/legacy-sidebar-widget/assets/css/go4taste-ads-sidebar-widget.css`
    * enqueue przeniesiony do `features/legacy-sidebar-widget/index.php` \(`wp_enqueue_scripts`\)
    
* Admin settings assets sa teraz wlascicielem slice `admin-settings`:

    * `features/admin-settings/assets/css/go4taste-ads-admin-settings.css`
    * `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`
    * enqueue \+ `wp_localize_script` w `features/admin-settings/index.php`
    * scope enqueue tylko dla strony `toplevel_page_go4taste-ads`
    
* Metabox preview ma osobny styl \(niezalezny od sidebar CSS\), zgodnie z opcja B:

    * nowy plik `features/promoted-product-metabox/assets/css/go4taste-ads-metabox-preview.css`
    * `features/promoted-product-metabox/metabox-registration.php` enqueue tylko tego stylu na `post.php`/`post-new.php`
    * `features/promoted-product-metabox/preview-template.php`: klasa zmieniona na `g4t-banner-metabox-preview`
    
* Algolia skrypty dla metabox preview zostaly przeniesione do ownership slice `promoted-product-metabox` \(admin enqueue tylko na ekranach edycji posta\)

### 26. Phase 10 release gate: walidacja statyczna domknieta

* Potwierdzono czysty working tree po cleanupie lokalnych artefaktow \(`build/`, `src/`\)
* PHP lint: `OK` dla calego pluginu
* Build bloku: `npm run build` zakonczony sukcesem \(webpack compiled successfully\)
* Diagnostyka edytora: brak bledow

## Następne kroki \(po tym PR\)

* Brak. PR gotowy do merge po finalnej walidacji.

## Manual Smoke Test Result

* Data: 2026-05-28
* Srodowisko: local
* Wykonal: Copilot \+ QA

Checklist:

* \[x\] 1. Go4Taste Ads settings page laduje sie bez fatali; Test API Permissions dziala
* \[x\] 2. Sync Products flow dziala \(start/progress/log/done\)
* \[x\] 3. Assets admin-settings sa zaladowane tylko na `toplevel_page_go4taste-ads`
* \[x\] 4. Metabox promoted produktu: Algolia search \+ preview \+ save/reload dzialaja
* \[x\] 5. Metabox preview korzysta z dedykowanego CSS \(`g4t-banner-metabox-preview`\)
* \[x\] 6. Frontend sidebar widget renderuje sie poprawnie \(desktop/mobile\)
* \[x\] 7. Inline banner block renderuje sie poprawnie i bez konfliktow CSS/JS

Wynik manual smoke:

* `PASS` \(7/7\)

## Release Readiness

* `READY FOR MERGE` — wszystkie walidacje zakonczone sukcesem

‌

---

<a id="pr-3"></a>
## #3 docs: mark smoke checks complete and ready for merge

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/vsa-postmerge-pr-description` → `main`
- **Created:** 2026-05-28 · **Closed:** 2026-05-28
- **Merge commit:** `4d78dec4c2fd`

# PR: VSA extraction of legacy-sidebar-widget \+ promoted-product-metabox \+ product-catalog-sync

**Branch:** chore/vertical-slices-architecture → main

## Motywacja

Kontynuujemy migrację pluginu do True Vertical Slices Architecture zgodnie z nowym README i guardrails. W tym PR wydzielono trzy obszary: legacy sidebar widget, promoted product metabox \(rejestracja, zapis i preview AJAX\) oraz product catalog sync \(asynchroniczny sync do Algolia\).

## Zakres zmian

### 1. Wydzielenie feature slice: legacy-sidebar-widget

Dodano nowy, autonomiczny slice:

* features/legacy-sidebar-widget/index.php
* features/legacy-sidebar-widget/widget-class.php
* features/legacy-sidebar-widget/widget-template.php

Feature sam rejestruje własne hooki:

* widgets\_init -> rejestracja widgetu
* plugins\_loaded -> sygnał kompatybilności g4taste\_product\_ads\_sidebar\_regular\_widget\_product\_id\_function\_loaded

### 2. Migracja rejestracji hooka ze starego orchestratora

Usunięto widget wiring ze starej klasy centralnej:

* includes/class-go4taste-ads.php

Usunięte podpięcia:

* widgets\_init -> register\_widgets
* plugins\_loaded -> load\_product\_id\_function

### 3. Odcięcie widgetu od Go4Taste\_Ads::get\_instance\(\) w samym slice

Widget w nowej lokalizacji nie korzysta już z singletona orchestratora. Wprowadzono core boundary dla pobierania danych:

* core/api-client/product-data.php

Boundary udostępnia helpery:

* g4t\_core\_get\_ads\_options\(\)
* g4t\_core\_get\_sidebar\_widget\_product\_id\(\)
* g4t\_core\_fetch\_product\_data\(\)

### 4. Bootstrap feature’ów przez core

Dodano nowy bootstrap:

* core/bootstrap.php

Entry point pluginu ładuje teraz feature slices przez:

* go4taste-ads.php -> g4t\_bootstrap\_features\(\)

### 5. Cleanup w legacy admin class

Usunięto widget-only metody ze starej klasy admin:

* get\_prestashop\_product\_id\(\)
* load\_product\_id\_function\(\)
* register\_widgets\(\)

Plik:

* admin/class-go4taste-ads-admin.php

### 6. Usunięcie shimów kompatybilności

Po potwierdzeniu smoke testów usunięto shimy kompatybilności:

* admin/class-go4taste-ads-sidebar-regular-widget.php
* public/partials/go4taste-ads-sidebar-regular-widget-public-display.php

### 7. Wydzielenie feature slice: promoted-product-metabox

Dodano nowy, autonomiczny slice:

* features/promoted-product-metabox/index.php
* features/promoted-product-metabox/metabox-registration.php
* features/promoted-product-metabox/preview-template.php

Feature sam rejestruje własne hooki:

* add\_meta\_boxes -> g4t\_promoted\_product\_metabox\_add\_meta\_box
* save\_post -> g4t\_promoted\_product\_metabox\_save\_meta\_box
* wp\_ajax\_g4t\_fetch\_product\_preview -> g4t\_promoted\_product\_metabox\_fetch\_product\_preview
* admin\_enqueue\_scripts -> g4t\_promoted\_product\_metabox\_enqueue\_preview\_styles

Dodatkowo:

* usunięto metabox wiring z includes/class-go4taste-ads.php
* usunięto metabox/preview metody z admin/class-go4taste-ads-admin.php
* core/bootstrap.php ładuje teraz także features/promoted-product-metabox/index.php

### 8. Wydzielenie feature slice: product-catalog-sync

Dodano nowy, autonomiczny slice:

* features/product-catalog-sync/index.php
* features/product-catalog-sync/sync-runner.php
* features/product-catalog-sync/sync-ajax-handlers.php

Feature sam rejestruje własne hooki:

* wp\_ajax\_g4t\_sync\_start -> g4t\_product\_catalog\_sync\_ajax\_start
* wp\_ajax\_g4t\_sync\_batch -> g4t\_product\_catalog\_sync\_ajax\_batch
* wp\_ajax\_g4t\_sync\_status -> g4t\_product\_catalog\_sync\_ajax\_status
* wp\_ajax\_g4t\_sync\_unlock -> g4t\_product\_catalog\_sync\_ajax\_unlock

Dodatkowo:

* usunięto sync AJAX wiring z includes/class-go4taste-ads.php
* usunięto sync helpers i sync AJAX metody z admin/class-go4taste-ads-admin.php
* core/bootstrap.php ładuje teraz także features/product-catalog-sync/index.php

### 9. Usunięcie jednorazowych sekcji migracyjnych z panelu admin

Po zakończeniu migracji danych usunięto z UI sekcje jednorazowe:

* Update Product IDs from Shortcode
* Migrate Legacy Shortcode Banners to Inline Banners

Usunięto powiązaną logikę runtime:

* admin\_init -> handle\_update\_product\_ids \(odpięte\)
* wp\_ajax\_g4t\_migration\_\* \(odpięte\)
* metody update/migration w admin/class-go4taste-ads-admin.php \(usunięte\)
* migracyjny flow w admin/js/go4taste-ads-admin.js \(usunięty\)

### 10. Wydzielenie feature slice: admin-settings

Dodano nowy, autonomiczny slice:

* features/admin-settings/index.php
* features/admin-settings/settings-page.php

Feature sam rejestruje własne hooki:

* admin\_menu -> rejestracja strony ustawień
* admin\_init -> rejestracja settings/fields
* wp\_ajax\_g4t\_test\_prestashop\_permissions -> test uprawnień API

Dodatkowo:

* usunięto admin settings i permissions hook wiring z includes/class-go4taste-ads.php
* usunięto metody settings/permissions z admin/class-go4taste-ads-admin.php
* core/bootstrap.php ładuje teraz także features/admin-settings/index.php

### 11. Wydzielenie feature slice: admin-assets

Dodano nowy, autonomiczny slice:

* features/admin-assets/index.php

Feature sam rejestruje własny hook:

* admin\_enqueue\_scripts -> g4t\_admin\_assets\_enqueue

W slice osadzono:

* enqueue admin CSS/JS
* enqueue bibliotek Algolia dla panelu
* wp\_localize\_script payload dla sync i testu uprawnień API

Dodatkowo:

* usunięto zależność od Go4Taste\_Ads\_Admin z includes/class-go4taste-ads.php
* admin hooks w orchestratorze są teraz puste \(feature-local ownership\)
* core/bootstrap.php ładuje teraz także features/admin-assets/index.php

### 12. Finalny cleanup: usunięcie legacy admin class

Po wydzieleniu wszystkich odpowiedzialności usunięto plik:

* admin/class-go4taste-ads-admin.php

To domyka dekompozycję dawnego "god object" i usuwa martwy kod z repozytorium.

### 13. Cleanup orchestratora po migracji admin

Uproszczono includes/class-go4taste-ads.php:

* usunięto property admin\_instance
* usunięto metody get\_public\_instance\(\) i get\_admin\_instance\(\)
* usunięto pustą metodę define\_admin\_hooks\(\) i jej wywołanie z konstruktora

Orchestrator zachowuje tylko odpowiedzialności core \(locale/public/block\), a hooki admin są w feature slices.

## Weryfikacja

* Brak błędów statycznych/kompilacyjnych w zmienionych plikach.
* widgets\_init jest rejestrowany tylko w feature slice.
* W kodzie slice legacy-sidebar-widget nie występuje Go4Taste\_Ads::get\_instance\(\).
* Hook kompatybilności g4taste\_product\_ads\_sidebar\_regular\_widget\_product\_id\_function\_loaded jest emitowany z feature slice.
* add\_meta\_boxes/save\_post/wp\_ajax\_g4t\_fetch\_product\_preview są rejestrowane w feature slice promoted-product-metabox.
* wp\_ajax\_g4t\_sync\_\* są rejestrowane w feature slice product-catalog-sync.
* Brak referencji runtime do go4taste\_update\_product\_ids i g4t\_migration\_\*.
* admin\_menu/admin\_init/wp\_ajax\_g4t\_test\_prestashop\_permissions są rejestrowane w feature slice admin-settings.
* admin\_enqueue\_scripts jest rejestrowany w feature slice admin-assets.
* Brak runtime referencji do Go4Taste\_Ads\_Admin.
* Brak runtime referencji do admin\_instance/get\_public\_instance/get\_admin\_instance.

## Wpływ i kompatybilność

* Brak zmian kontraktu danych dla legacy widgetu \(meta key pozostaje bez zmian\):

    * \_g4t\_a\_sidebar\_regular\_widget\_ps\_product\_id
    
* Legacy include paths shimów zostały usunięte po potwierdzeniu braku regresji.
* Funkcjonalność widgetu, metaboxu, syncu, ustawień i admin assets pozostaje zachowana, ale logika i hooki zostały przeniesione do vertical slices.

### 14. VSA Phase 1: scaffolding granic dla i18n, inline-banner-block, ga4-tracking

Dodano trzy nowe moduły bez zmiany zachowania \(passive scaffolds\):

**core/i18n/**

* `core/i18n/index.php` — rejestracja hooka `plugins_loaded`
* `core/i18n/loader.php` — funkcja `g4t_load_plugin_textdomain()`

**features/inline-banner-block/**

* `features/inline-banner-block/index.php` — rejestracja hooków bloku
* `features/inline-banner-block/render.php` — `g4t_inline_banner_register_block_type()`, `g4t_inline_banner_render_block()`, `g4t_inline_banner_enable_border_settings()`
* `features/inline-banner-block/helpers.php` — helpery opcji bloku

**features/ga4-tracking/**

* `features/ga4-tracking/index.php` — init modułu
* `features/ga4-tracking/tracking-events.php` — schematy eventów GA4, normalizacja itemów
* `features/ga4-tracking/payload-builder.php` — budowanie payloadu `view_promotion`, output inline script

Dodatkowo:

* usunięto z `includes/class-go4taste-ads.php`: `set_locale()`, `define_block_hooks()`, `enable_border_settings()`, `register_blocks()`, `render_inline_banner_block()`, dependency `Go4Taste_Ads_i18n`
* `core/bootstrap.php` ładuje teraz `core/i18n/index.php`, `features/inline-banner-block/index.php`, `features/ga4-tracking/index.php`

**Fix regressji:** przywrócono `g4tAdsBlockData` jako nazwę obiektu `wp_localize_script` oraz brakujące klucze `algoliaSearchKey` i `algoliaIndexName` \(zmiana nazwy z Phase 1 zepsuła wyszukiwarkę produktów w block editorze\).

### 15. VSA Phase 2: migracja i18n do core/i18n, usunięcie legacy klasy

* Usunięto `includes/class-go4taste-ads-i18n.php` \(47 linii martwego kodu\)
* `g4t_load_plugin_textdomain()` w `core/i18n/loader.php` jest teraz jedynym właścicielem ładowania textdomainu
* Ścieżka do `/languages/` identyczna jak poprzednio \(`go4taste-ads/languages/`\)
* Brak zmiany zachowania

### 16. VSA Phase 3: usunięcie singletona Go4Taste\_Ads z block render template

* Zamieniono `Go4Taste_Ads::get_instance()->fetch_product_data()` na `g4t_core_fetch_product_data()` z core boundary
* Usunięto zbędne zmienne lokalne `$p_value`, `$lang`, `$api_type`, `$api_url` — core boundary wyciąga je wewnętrznie z `$options`
* Zmiana zastosowana w `src/blocks/inline-banner/render.php` oraz `build/blocks/inline-banner/render.php`
* Brak zmiany renderowanego HTML
* `features/inline-banner-block` jest teraz jedynym właścicielem hooków bloku; template nie ma zależności do orchestratora

### 17. VSA Phase 4: ekstrakcja GA4 z block render template do features/ga4-tracking

* Usunięto inline logikę GA4 z `src/blocks/inline-banner/render.php` i `build/blocks/inline-banner/render.php`
* Usunięto `$ga4_view_promotion_enabled` — decyzja o outputcie przeniesiona do `g4t_ga4_is_tracking_enabled()` w feature slice
* Usunięto warunek `if ( $ga4_view_promotion_enabled )` w pętli foreach — items zawsze się zbierają, payload-builder sam sprawdza flagę
* Zastąpiono cały blok `<script>` wywołaniem `g4t_ga4_output_view_promotion_script( $ga4_items )` z `features/ga4-tracking/payload-builder.php`
* Kontrakt eventu GA4 niezmieniony: `event: view_promotion`, `ecommerce.items` z identycznym schematem
* Zmiana zastosowana w `src/blocks` i `build/blocks` \(identyczne szablony\)

### 18. VSA Phase 5: co-location artefaktow bloku inline-banner wewnatrz feature slice

* Przeniesiono kod zrodlowy bloku z `src/blocks/inline-banner/` do `features/inline-banner-block/src/`
* Przeniesiono artefakty build z `build/blocks/inline-banner/` do `features/inline-banner-block/build/`
* Zaktualizowano `g4t_inline_banner_register_block_type()` i `g4t_inline_banner_render_block()`:

    * metadata path: `features/inline-banner-block/build/block.json`
    * fallback template path: `features/inline-banner-block/build/render.php`
    
* Zaktualizowano skrypty npm:

    * `npm run build` -> `wp-scripts build --webpack-src-dir=features/inline-banner-block/src --output-path=features/inline-banner-block/build`
    * `npm run start` -> `wp-scripts start --webpack-src-dir=features/inline-banner-block/src --output-path=features/inline-banner-block/build`
    
* Efekt: pelna co-lokacja PHP \+ JS/CSS \+ block assets dla inline-banner w jednym feature folderze

### 19. VSA Phase 6: finalny cleanup orchestratora i central hook loadera

* Entry point `go4taste-ads.php` nie uruchamia juz `Go4Taste_Ads::get_instance()->run()`
* Usunieto legacy orchestrator i centralny loader:

    * `includes/class-go4taste-ads.php`
    * `includes/class-go4taste-ads-loader.php`
    * `public/class-go4taste-ads-public.php`
    
* Dodano feature slice `features/public-assets/index.php` jako nowego wlasciciela hooka `wp_enqueue_scripts` dla frontowego CSS pluginu
* Przeniesiono ladowanie komendy WP-CLI do `core/bootstrap.php` \(conditional include przy `WP_CLI`\), aby zachowac dotychczasowa funkcjonalnosc CLI bez zaleznosci od orchestratora
* Weryfikacja: brak referencji do `Go4Taste_Ads::get_instance` i `Go4Taste_Ads_Loader` w kodzie wykonywanym pluginu

### 20. Hotfix po Phase 6: brak klasy `Go4Taste_Ads_API_Fetcher` na ekranie ustawien

* Naprawiono krytyczny blad na stronie opcji przy polu "Prestashop Webservice API Key"
* W `features/admin-settings/settings-page.php` dodano lazy-loader `get_api_fetcher()`:

    * `require_once` klasy fetchera gdy klasa nie jest jeszcze zaladowana
    * bezpieczny fallback \(`null`\) zamiast fatala
    
* Zaktualizowano oba miejsca uzycia fetchera \(callback pola \+ AJAX test uprawnien\), aby korzystaly z `get_api_fetcher()` i zwracaly czytelny komunikat bledu zamiast zatrzymania strony

### 21. VSA Phase 8: ujednolicenie ladowania API Fetchera przez core boundary

* Dodano wspolny helper `g4t_core_get_api_fetcher()` w `core/api-client/product-data.php`

    * centralizuje lazy-load klasy fetchera
    * zwraca `Go4Taste_Ads_API_Fetcher|null`
    
* `g4t_core_fetch_product_data()` korzysta teraz z `g4t_core_get_api_fetcher()` \(bez lokalnej duplikacji\)
* `features/product-catalog-sync/sync-ajax-handlers.php` korzysta z helpera core w `ajax_start` i `ajax_batch`

    * usunieto duplikowane `class_exists + require_once + new`
    * dodano kontrolowany blad `500` i status job `failed`, gdy fetcher jest niedostepny
    
* `features/admin-settings/settings-page.php` korzysta teraz z helpera core zamiast lokalnej metody `get_api_fetcher()`

    * usunieto duplikacje ladowania klasy w slice settings
    

### 22. VSA Phase 9: porzadki koncowe dokumentacji po usunieciu legacy loader flow

* Zaktualizowano `CURRENT_STATE.md` do aktualnej architektury po Fazach 1-8

    * usunieto nieaktualne odniesienia do `includes/class-go4taste-ads.php`, `includes/class-go4taste-ads-loader.php`, `public/class-go4taste-ads-public.php`
    * opisano aktualny feature-first runtime i ownership hookow
    
* Przepisano `VSA_FULL_MIGRATION_PLAN_NEXT.md` do postaci `Completion + Release Gate` zamiast planu migracji pre-Phase 6

    * status: migracja VSA zakonczona przez Fazy 1-8
    * pozostawiono tylko checklist do finalnej bramy release
    

### 23. Dodatkowy cleanup legacy katalogow i co-location assetow

* Przeniesiono assety admin do feature slice:

    * `admin/css/go4taste-ads-admin.css` -> `features/admin-assets/assets/css/go4taste-ads-admin.css`
    * `admin/js/go4taste-ads-admin.js` -> `features/admin-assets/assets/js/go4taste-ads-admin.js`
    
* Przeniesiono publiczny CSS do feature slice:

    * `public/css/go4taste-ads-public.css` -> `features/public-assets/assets/css/go4taste-ads-public.css`
    
* Zaktualizowano sciezki enqueue w:

    * `features/admin-assets/index.php`
    * `features/public-assets/index.php`
    * `features/promoted-product-metabox/metabox-registration.php`
    
* Usunieto legacy puste szkielety katalogow/pliki placeholder:

    * `admin/index.php`, `public/index.php`
    * puste katalogi `admin/`, `public/`, `src/`, `build/`
    

### 24. Cleanup runtime includes: lifecycle \+ API fetcher moved to core

* Przeniesiono runtime lifecycle do `core/lifecycle/`:

    * `includes/class-go4taste-ads-activator.php` -> `core/lifecycle/class-go4taste-ads-activator.php`
    * `includes/class-go4taste-ads-deactivator.php` -> `core/lifecycle/class-go4taste-ads-deactivator.php`
    
* Entry point `go4taste-ads.php` laduje teraz lifecycle z `core/lifecycle/*`
* Przeniesiono API fetcher do core boundary:

    * `includes/class-go4taste-ads-api-fetcher.php` -> `core/api-client/class-go4taste-ads-api-fetcher.php`
    * `g4t_core_get_api_fetcher()` laduje klase z nowej lokalizacji core
    
* Usunieto runtime WP-CLI command:

    * usuniety plik `includes/class-go4taste-ads-wp-cli-command.php`
    * usuniete ladowanie WP-CLI command z `core/bootstrap.php`
    
* Usunieto `includes/index.php` i caly katalog `includes/` \(brak pozostalosci runtime i placeholderow\)

### 25. Option B: rozdzial ownership `public-assets` i `admin-assets` do docelowych feature slices

* Usunieto bucket slices i ich runtime hook ownership:

    * `features/public-assets/index.php`
    * `features/admin-assets/index.php`
    * usuniete katalogi `features/public-assets/` i `features/admin-assets/`
    
* `core/bootstrap.php` nie laduje juz `public-assets`/`admin-assets`
* Frontend sidebar CSS jest teraz wlascicielem slice `legacy-sidebar-widget`:

    * `features/legacy-sidebar-widget/assets/css/go4taste-ads-sidebar-widget.css`
    * enqueue przeniesiony do `features/legacy-sidebar-widget/index.php` \(`wp_enqueue_scripts`\)
    
* Admin settings assets sa teraz wlascicielem slice `admin-settings`:

    * `features/admin-settings/assets/css/go4taste-ads-admin-settings.css`
    * `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`
    * enqueue \+ `wp_localize_script` w `features/admin-settings/index.php`
    * scope enqueue tylko dla strony `toplevel_page_go4taste-ads`
    
* Metabox preview ma osobny styl \(niezalezny od sidebar CSS\), zgodnie z opcja B:

    * nowy plik `features/promoted-product-metabox/assets/css/go4taste-ads-metabox-preview.css`
    * `features/promoted-product-metabox/metabox-registration.php` enqueue tylko tego stylu na `post.php`/`post-new.php`
    * `features/promoted-product-metabox/preview-template.php`: klasa zmieniona na `g4t-banner-metabox-preview`
    
* Algolia skrypty dla metabox preview zostaly przeniesione do ownership slice `promoted-product-metabox` \(admin enqueue tylko na ekranach edycji posta\)

### 26. Phase 10 release gate: walidacja statyczna domknieta

* Potwierdzono czysty working tree po cleanupie lokalnych artefaktow \(`build/`, `src/`\)
* PHP lint: `OK` dla calego pluginu
* Build bloku: `npm run build` zakonczony sukcesem \(webpack compiled successfully\)
* Diagnostyka edytora: brak bledow

## Następne kroki \(po tym PR\)

* Brak. PR gotowy do merge po finalnej walidacji.

## Manual Smoke Test Result

* Data: 2026-05-28
* Srodowisko: local
* Wykonal: Copilot \+ QA

Checklist:

* \[x\] 1. Go4Taste Ads settings page laduje sie bez fatali; Test API Permissions dziala
* \[x\] 2. Sync Products flow dziala \(start/progress/log/done\)
* \[x\] 3. Assets admin-settings sa zaladowane tylko na `toplevel_page_go4taste-ads`
* \[x\] 4. Metabox promoted produktu: Algolia search \+ preview \+ save/reload dzialaja
* \[x\] 5. Metabox preview korzysta z dedykowanego CSS \(`g4t-banner-metabox-preview`\)
* \[x\] 6. Frontend sidebar widget renderuje sie poprawnie \(desktop/mobile\)
* \[x\] 7. Inline banner block renderuje sie poprawnie i bez konfliktow CSS/JS

Wynik manual smoke:

* `PASS` \(7/7\)

## Release Readiness

* `READY FOR MERGE` — wszystkie walidacje zakonczone sukcesem

‌

---

<a id="pr-4"></a>
## #4 docs: align README and VSA developer agent with current architecture

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/vsa-postmerge-pr-description` → `main`
- **Created:** 2026-05-28 · **Closed:** 2026-05-28
- **Merge commit:** `68d938b154b5`

# PR: Follow-up docs alignment after VSA merge

**Branch:** chore/vsa-postmerge-pr-description -> main

## Motywacja

Po merge PR #2 opis architektury w dokumentacji byl czesciowo niespojny z aktualnym stanem repo. Ten PR domyka dokumentacje po migracji VSA i aktualizuje guidance dla agenta developerskiego.

## Zakres zmian

### 1. README: aktualizacja do realnej struktury pluginu

Zaktualizowano [README.md](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/README.md), aby odzwierciedlal aktualny runtime po migracji VSA:

* sekcja "Current Directory Structure" zamiast "Target Directory Structure"
* dodane `core/lifecycle`
* poprawione wpisy dla feature slices \(`index.php`, `assets/`, `helpers.php`, `payload-builder.php`\)
* usuniete nieaktualne odniesienia do nieistniejacych elementow \(np. `legacy-shortcode-migration`, `metabox-ui.js`, `metabox-style.css`, `options-registration.php`\)
* doprecyzowane hard constraints pod etap utrzymania po migracji

### 2. Agent developer: aktualizacja kontekstu po migracji

Zaktualizowano [.github/agents/go4taste-plugin-developer.agent.md](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/.github/agents/go4taste-plugin-developer.agent.md):

* mission statement: z "refactor legacy plugin" na utrzymanie i rozwoj w gotowym VSA
* kontekst: usunieto guidance sugerujacy standardowa prace na `admin/public/includes`
* doprecyzowano, ze nie nalezy reintrodukowac poziomych runtime layers

## Weryfikacja

* Zmiany obejmuja tylko dokumentacje \(`*.md`\)
* Brak zmian w kodzie runtime pluginu
* Sprawdzono, ze diff wzgledem `main` obejmuje tylko:

    * [README.md](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/README.md)
    * [.github/agents/go4taste-plugin-developer.agent.md](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/.github/agents/go4taste-plugin-developer.agent.md)
    

## Wplyw i kompatybilnosc

* Brak wplywu na runtime, API i dane
* Zmiany poprawiaja onboarding i zmniejszaja ryzyko blednych refaktorow wynikajacych z nieaktualnej dokumentacji

## Release readiness

* READY FOR MERGE \(docs-only follow-up\)

‌

---

<a id="pr-5"></a>
## #5 Chore/rename banner slices

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/rename-banner-slices` → `main`
- **Created:** 2026-05-28 · **Closed:** 2026-05-28
- **Merge commit:** `68d938b154b5`

# PR: Rename feature slices to banner-inline-block and banner-sidebar-widget

**Branch:** chore/rename-banner-slices -> main

## Motywacja

Ujednolicamy nazewnictwo feature slices i odchodzimy od nazwy `legacy-sidebar-widget`. Zakres jest celowo kosmetyczny: rename sciezek i referencji bez zmiany publicznego kontraktu bloku oraz bez zmian zachowania runtime.

## TL;DR dla reviewera

* To jest rename-only PR dla nazw slice'ow \(`inline-banner` i `sidebar widget`\) \+ aktualizacja referencji.
* Nie ma zmian logiki biznesowej ani kontraktu bloku `go4taste-ads/inline-banner`.
* Build i lint sa zielone; stare katalogi po rename nie odtworzyly sie.

## Zakres zmian

### 1. Rename folderow feature slices \(zachowanie historii przez `git mv`\)

* `features/inline-banner-block` -> `features/banner-inline-block`
* `features/legacy-sidebar-widget` -> `features/banner-sidebar-widget`

### 2. Aktualizacja runtime referencji po rename

* `core/bootstrap.php` laduje teraz:

    * `features/banner-inline-block/index.php`
    * `features/banner-sidebar-widget/index.php`
    
* `features/banner-sidebar-widget/index.php`:

    * nowe callbacki `g4t_banner_sidebar_widget_*`
    * poprawiona sciezka assetu do `features/banner-sidebar-widget/assets/css/go4taste-ads-sidebar-widget.css`
    

### 3. Aktualizacja build pipeline

* `package.json`:

    * `npm run build` i `npm run start` wskazuja `features/banner-inline-block/src` oraz `features/banner-inline-block/build`
    

### 4. Usuniecie nazewnictwa legacy w widget feature

* `features/banner-sidebar-widget/widget-class.php`:

    * komentarz klasy bez "Legacy"
    * label widgetu zmieniony na `G4T Ads Sidebar Banner Widget`
    

### 5. Aktualizacja dokumentacji i komentarzy technicznych

* `README.md`
* `CURRENT_STATE.md`
* `VSA_FULL_MIGRATION_PLAN_NEXT.md`
* `features/banner-inline-block/{index.php,render.php,helpers.php}` \(`@subpackage`\)
* `features/ga4-tracking/index.php` \(komentarz cross-feature\)

## Weryfikacja \(bramki ryzyka\)

* Build: `npm run build` -> **OK**
* PHP lint \(caly plugin\) -> **OK**
* Safety grep: brak wystapien `inline-banner-block` i `legacy-sidebar-widget` w runtime/docs operacyjnych \(pozostaly tylko kontrolowane wzmianki historyczne w tym PR opisie\) -> **OK**
* Artefakty: brak odtworzonych katalogow:

    * `features/inline-banner-block`
    * `features/legacy-sidebar-widget`
    * root `build/`
    * root `src/`
    

## Wplyw i kompatybilnosc

* Brak zmiany publicznego kontraktu bloku \(`go4taste-ads/inline-banner` pozostaje bez zmian\)
* Brak zmian danych i API
* Zmiana dotyczy nazewnictwa feature slices, sciezek i dokumentacji

## Release readiness

* READY FOR MERGE

‌

---

<a id="pr-6"></a>
## #6 refactor(vsa): rename sync and metabox feature slices

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/rename-sync-metabox-slices` → `main`
- **Created:** 2026-05-28 · **Closed:** 2026-05-28
- **Merge commit:** `22f0a90f2191`

# PR: Rename feature slices to algolia-product-sync and metabox-banner-sidebar-widget

**Branch:** chore/rename-sync-metabox-slices -> main

## Motywacja

Ujednolicamy nazewnictwo feature slices dla sync i metabox, aby lepiej oddawalo aktualna odpowiedzialnosc modulow. Zakres jest celowo kosmetyczny: rename sciezek i referencji bez zmiany publicznego kontraktu bloku oraz bez zmian zachowania runtime.

## TL;DR dla reviewera

* To jest rename-only PR dla nazw slice'ow \(`product-catalog-sync` i `promoted-product-metabox`\) \+ aktualizacja referencji.
* Nie ma zmian logiki biznesowej, kontraktow AJAX ani danych.
* Build i lint sa zielone; stare katalogi po rename nie odtworzyly sie.

## Zakres zmian

### 1. Rename folderow feature slices \(zachowanie historii przez `git mv`\)

* `features/product-catalog-sync` -> `features/algolia-product-sync`
* `features/promoted-product-metabox` -> `features/metabox-banner-sidebar-widget`

### 2. Aktualizacja runtime referencji po rename

* `core/bootstrap.php` laduje teraz:

    * `features/algolia-product-sync/index.php`
    * `features/metabox-banner-sidebar-widget/index.php`
    
* `features/metabox-banner-sidebar-widget/metabox-registration.php`:

    * poprawiona sciezka assetu do `features/metabox-banner-sidebar-widget/assets/css/go4taste-ads-metabox-preview.css`
    

### 3. Aktualizacja dokumentacji i guidance

* `README.md`
* `CURRENT_STATE.md`
* `.github/agents/go4taste-plugin-developer.agent.md`

## Weryfikacja \(bramki ryzyka\)

* Build: `npm run build` -> **OK**
* PHP lint \(caly plugin\) -> **OK**
* Safety grep: brak wystapien `product-catalog-sync` i `promoted-product-metabox` w runtime/docs operacyjnych \(pozostaly tylko kontrolowane wzmianki historyczne w tym PR opisie\) -> **OK**
* Artefakty: brak odtworzonych katalogow:

    * `features/product-catalog-sync`
    * `features/promoted-product-metabox`
    * root `build/`
    * root `src/`
    

## Wplyw i kompatybilnosc

* Brak zmian kontraktow AJAX \(`g4t_sync_*`, `g4t_fetch_product_preview`\) i meta key
* Brak zmian danych i API
* Zmiana dotyczy nazewnictwa feature slices, sciezek i dokumentacji

## Release readiness

* READY FOR MERGE

‌

---

<a id="pr-7"></a>
## #7 feat(algolia): add ad server provider, soft-cache and daily sync cron

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/algolia-ad-server-soft-cache-cron` → `main`
- **Created:** 2026-05-28 · **Closed:** 2026-05-28
- **Merge commit:** `647e73ac0c7f`

# PR: Algolia ad server \+ soft cache \+ daily sync cron \+ Admin API key

**Branch:** main -> main

## Motywacja

Obecny przeplyw pobierania danych produktowych byl oparty glownie o bezposredni fetch, co utrudnialo centralne sterowanie zrodlem danych i fallbackiem cache.

Jednoczesnie synchronizacja do Algolii \(zapis\) wymagala klucza z uprawnieniami write, a konfiguracja admina miala tylko pole Search-Only API Key.

Cel tej zmiany:

1. Wprowadzic centralny punkt pobierania danych produktowych przez filtr.
2. Dodac soft-cache dla danych z Algolii \(stale fallback przy problemach z fetch\).
3. Dodac dzienny cron sync Algolia z batch processingiem.
4. Rozdzielic klucze Algolia \(search vs admin/write\) na poziomie ustawien i zapisu.

## TL;DR dla reviewera

* Dodany nowy slice: `features/algolia-ad-server/` \(filter provider \+ soft cache \+ fetch z Algolii\).
* Konsumenci runtime \(`banner-inline-block`, `metabox preview`\) przechodza na wspolny filtr `go4taste_ads_get_product_data`.
* Sync do Algolii korzysta z nowego `algolia_admin_api_key` \(zamiast search-only key\).
* Dodany dzienny cron sync w `features/algolia-product-sync/cron.php` i zaladowanie go w `index.php`.

## Zakres zmian

### 1\) Nowy feature slice: Algolia Ad Server

Pliki:

* `features/algolia-ad-server/algolia-ad-server.php`
* `features/algolia-ad-server/product-data.php`
* `features/algolia-ad-server/soft-cache.php`

Zmiany:

* Rejestracja filtra `go4taste_ads_get_product_data`.
* Pobieranie produktu z Algolii po `product_id`.
* Soft TTL cache:

    * soft expiry: 24h \(`soft_expires_at`\)
    * hard expiry transientu: 7 dni
    
* Fallback do stale cache, gdy fetch z Algolii sie nie powiedzie.

### 2\) Przelaczenie konsumentow runtime na filtr

Pliki:

* `features/banner-inline-block/build/render.php`
* `features/metabox-banner-sidebar-widget/metabox-registration.php`

Zmiany:

* Zastapienie bezposredniego wywolania `g4t_core_fetch_product_data(...)` przez:

    * `apply_filters( 'go4taste_ads_get_product_data', null, $product_id )`
    
* Ujednolicenie punktu wejscia do pobierania danych produktowych.

### 3\) Algolia sync: rozdzielenie klucza search i admin

Pliki:

* `core/api-client/class-go4taste-ads-api-fetcher.php`
* `features/admin-settings/settings-page.php`

Zmiany:

* `store_products_to_algolia()` uzywa teraz `algolia_admin_api_key`.
* Dodane pole ustawien: `Algolia Admin API Key`.
* Dodana sanitizacja `algolia_admin_api_key`.
* Pole renderowane jako `password` i celowo nie jest prefillowane.

### 4\) Daily cron sync dla Algolia Product Sync

Pliki:

* `features/algolia-product-sync/index.php`
* `features/algolia-product-sync/cron.php` \(nowy\)

Zmiany:

* Zaladowanie `cron.php` w indeksie feature.
* Rejestracja custom schedule `g4t_daily`.
* Event bazowy: `g4t_algolia_daily_sync_event`.
* Batch worker: `g4t_algolia_process_cron_batch`.
* Stan joba przechowywany w transiencie \(`g4t_sync_algolia_cron_job`\).

### 5\) Uporzadkowanie nazw callbackow settings slice

Plik:

* `features/admin-settings/index.php`

Zmiany:

* Zmiana prefiksu funkcji wrapperow hookow z `g4t_admin_settings_*` na `g4ta_admin_settings_*`.

### 6\) Dokumentacja przeplywu

Plik:

* `CURRENT_STATE_FLOW.md` \(nowy\)

Zmiany:

* Audyt aktualnego przeplywu danych produktu, cache transient i integracji Algolia.

## Weryfikacja

* `git diff` potwierdza zmiany w runtime, admin settings i nowych slice'ach Algolia.
* Brak uruchomionych bramek automatycznych w tym kroku \(build/lint/test do odpalenia przed merge\).

## Ryzyko / kompatybilnosc

* Ryzyko srednie:

    * nowa sciezka pobierania danych przez filtr i nowy provider Algolia,
    * nowy background flow \(cron \+ chaining batchy\).
    
* Kompatybilnosc:

    * kontrakty AJAX sync \(`g4t_sync_*`\) pozostaja bez zmian,
    * zmieniony jest wymog konfiguracyjny dla zapisu do Algolii \(potrzebny `algolia_admin_api_key`\).
    

## Checklist

* \[x\] Dodany `go4taste_ads_get_product_data` provider oparty o Algolia \+ soft-cache
* \[x\] Banner inline \+ metabox preview korzystaja z filtra
* \[x\] Zapis do Algolii korzysta z Admin API key
* \[x\] Dodane pole `Algolia Admin API Key` w ustawieniach
* \[x\] Dodany daily cron sync z batch processingiem
* \[x\] Dodany dokument `CURRENT_STATE_FLOW.md`

‌

---

<a id="pr-8"></a>
## #8 Feature/webservice only api cleanup

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/webservice-only-api-cleanup` → `main`
- **Created:** 2026-05-28 · **Closed:** 2026-05-28
- **Merge commit:** `ee58549cd6b8`

# PR: Webservice-only cleanup for product data source

**Branch:** feature/webservice-only-api-cleanup -> main

## Motywacja

W kodzie i panelu ustawien nadal istnialy pozostalosci po starych zrodlach danych \(`prestashop_1_4`, `prestashop_1_5`, `woocommerce`, `custom`\), mimo ze realnym kierunkiem pluginu jest wyłącznie PrestaShop Webservice API.

Powodowalo to:

1. Niepotrzebna zlozonosc runtime i sync jobow.
2. Mozliwosc wyboru nieuzywanych trybow w adminie.
3. Niespojnosc miedzy kodem a dokumentacja pluginu.

## TL;DR dla reviewera

* Runtime API fetchera zostal uproszczony do PrestaShop Webservice-only.
* Sync AJAX i cron nie przenosza juz legacy parametrow i opcji WooCommerce.
* Admin settings zostaly uproszczone do jednego modelu \(`prestashop_webservice_api`\).
* Dokumentacja i metadane pluginu zostaly oczyszczone z komunikatow multi-API.

## Zakres zmian

### 1\) Refactor runtime: Webservice-only

Pliki:

* `core/api-client/class-go4taste-ads-api-fetcher.php`
* `core/api-client/product-data.php`

Zmiany:

* Usuniete branchowanie po `prestashop_1_4`, `prestashop_1_5`, `woocommerce`.
* Uproszczone sygnatury metod fetchera do modelu Webservice-only.
* Wrapper core przestal przekazywac legacy parametry \(`api_type`, `p_parameter_value`\).

### 2\) Sync pipeline bez legacy API state

Pliki:

* `features/algolia-product-sync/sync-ajax-handlers.php`
* `features/algolia-product-sync/cron.php`

Zmiany:

* Usuniete odczyty i przekazywanie `woocommerce_consumer_key` / `woocommerce_consumer_secret`.
* Job state sync nie trzyma juz pola `api_type` i `p_value`.
* Wywolania fetchera dostosowane do nowych sygnatur Webservice-only.

### 3\) Admin settings: jedno zrodlo danych

Pliki:

* `features/admin-settings/settings-page.php`
* `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`

Zmiany:

* Usuniete UI i logika dla wyboru legacy API \(`API Type`\).
* Usuniete pole `PrestaShop API Password` \(`p_parameter_value`\).
* W `sanitize()` wymuszenie `api_type = prestashop_webservice_api`.
* Usuniety obsolete JS toggle zaleznosci od starych opcji API.

### 4\) Runtime consistency dla providerow danych

Pliki:

* `core/bootstrap.php`
* `features/banner-sidebar-widget/widget-class.php`

Zmiany:

* Podpiecie `features/algolia-ad-server/algolia-ad-server.php` do bootstrapa.
* Usuniecie martwego helpera `get_p_parameter_value()` ze sidebar widgetu.

### 5\) Dokumentacja i metadane

Pliki:

* `README.md`
* `README.txt`
* `CURRENT_STATE.md`
* `.github/plugin/plugin.json`

Zmiany:

* Usuniete wzmianki o multi-API i wsparciu WooCommerce/custom.
* Opisy dostosowane do PrestaShop Webservice API jako jedynego data source.
* Zaktualizowany snapshot feature slice'ow i hook ownership.

## Weryfikacja

* PHP lint: brak bledow dla wszystkich edytowanych plikow.
* Skan po zmianach: brak wystapien runtime/docs dla `prestashop_1_4`, `prestashop_1_5`, `woocommerce_consumer_key`, `woocommerce_consumer_secret`, `p_parameter_value`.
* Zmiany podzielone na 2 commity:

    * `refactor(api): enforce prestashop webservice only runtime`
    * `docs: remove legacy multi-api references`
    

## Ryzyko / kompatybilnosc

* Ryzyko srednie: zmieniony kontrakt metod fetchera i sync pipeline.
* Kompatybilnosc:

    * obecne flow pluginu pozostaje zgodne z docelowym modelem Webservice-only,
    * usuniete zostalo wsparcie nieuzywanych trybow legacy \(celowa deprecacja\).
    

## Checklist

* \[x\] Runtime fetchera uproszczony do PrestaShop Webservice API
* \[x\] Sync AJAX/cron bez legacy API i WooCommerce opcji
* \[x\] Admin settings uproszczone do jednego source type
* \[x\] Provider `algolia-ad-server` zaladowany w bootstrapie
* \[x\] Dokumentacja i metadane bez multi-API komunikatow

‌

---

<a id="pr-9"></a>
## #9 Hotfix/inline banner algolia client path

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `hotfix/inline-banner-algolia-client-path` → `main`
- **Created:** 2026-05-29 · **Closed:** 2026-05-29
- **Merge commit:** `50d8ff995f12`

# PR: Inline banner hotfix for publish/update and legacy theme overrides

**Branch:** hotfix/inline-banner-algolia-client-path -> main

## Motywacja

Po wdrozeniu `algolia-ad-server` pojawily sie dwa krytyczne problemy zwiazane z inline bannerem:

1. Publish/update postow w edytorze Gutenberg konczyl sie bledem `Publishing failed` / `Updating failed`.
2. Na srodowisku zdalnym render inline bannera potrafil wywalic fatala przez brakujacy plik `core/api-client/algolia-client.php` albo przez stary motywowy override korzystajacy z `Go4Taste_Ads::get_instance()`.

Ten PR domyka oba problemy tak, aby update samej wtyczki byl bezpieczny rowniez wtedy, gdy na serwerze nadal jest stary override szablonu w motywie.

## TL;DR dla reviewera

* Usuniety fatal include do nieistniejacego `algolia-client.php`.
* `algolia-ad-server` tworzy klienta Algolia bezposrednio z opcji pluginu i czyta z poprawnego indeksu `go4taste_product_list`.
* Rekord z Algolii jest normalizowany do ksztaltu zgodnego z renderem inline bannera.
* Dodany shim kompatybilnosci `Go4Taste_Ads`, zeby stare motywowe override'y nie wywalaly strony po samym update pluginu.

## Zakres zmian

### 1\) Naprawa `algolia-ad-server` dla inline bannera

Plik:

* `features/algolia-ad-server/product-data.php`

Zmiany:

* usuniety `require_once` do nieistniejacego `core/api-client/algolia-client.php`
* klient Algolia tworzony bezposrednio przez `Algolia\AlgoliaSearch\SearchClient::create(...)`
* odczyt credentials z opcji pluginu \(`algolia_app_id`, `algolia_api_key`\)
* odczyt z indeksu `go4taste_product_list`
* normalizacja payloadu do obiektu kompatybilnego z template renderujacym banner
* fallback do stale cache bez fatala przy problemach z Algolia

### 2\) Shim kompatybilnosci dla starego motywu

Pliki:

* `core/compat/legacy-go4taste-ads-class.php`
* `core/bootstrap.php`

Zmiany:

* dodana klasa kompatybilnosci `Go4Taste_Ads`
* wspierane legacy wywolanie:

    * `Go4Taste_Ads::get_instance()->fetch_product_data(...)`
    
* shim mapuje stare wywolania na aktualny flow:

    * `go4taste_ads_get_product_data`
    * fallback `g4t_core_fetch_product_data(...)`
    
* shim ladowany podczas bootstrapa pluginu

## Root cause

Problem nie byl w samym edytorze Gutenberg ani w REST API. Root-cause byl w renderze inline bannera podczas preload/autosave:

1. render bloku wywolywal provider danych produktu,
2. provider ladowal nieistniejacy plik lub trafial na stary override motywu,
3. PHP fatal powodowal 500 na endpointach autosave / update,
4. edytor pokazywal `Publishing failed` / `Updating failed`.

## Weryfikacja

* `php -l features/algolia-ad-server/product-data.php` -> OK
* `php -l features/algolia-ad-server/algolia-ad-server.php` -> OK
* `php -l core/compat/legacy-go4taste-ads-class.php` -> OK
* `php -l core/bootstrap.php` -> OK
* brak dalszych referencji do:

    * `core/api-client/algolia-client.php`
    * `g4t_get_algolia_client`
    

## Ryzyko / kompatybilnosc

* Ryzyko niskie/srednie: zmiana dotyczy hotfixa w warstwie pobierania danych i backward compatibility.
* Kompatybilnosc:

    * nowy flow inline bannera dziala bez starej klasy runtime,
    * stary motywowy override nadal dziala po update pluginu dzieki shimowi,
    * fix jest zaprojektowany specjalnie pod bezpieczny deploy przez WP Pusher bez dostepu SSH.
    

## Checklist

* \[x\] Usuniety fatal include do nieistniejacego `algolia-client.php`
* \[x\] Klient Algolia tworzony bezposrednio z opcji pluginu
* \[x\] Uzyty poprawny indeks `go4taste_product_list`
* \[x\] Payload produktu normalizowany do ksztaltu oczekiwanego przez render inline bannera
* \[x\] Dodany shim `Go4Taste_Ads` dla starych override'ow motywu
* \[x\] Shim ladowany w bootstrapie pluginu

‌

---

<a id="pr-10"></a>
## #10 feat(cache): add admin bar soft-cache actions and page-scoped purge

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/admin-bar-soft-cache-actions` → `main`
- **Created:** 2026-05-29 · **Closed:** 2026-05-29
- **Merge commit:** `13461679d023`

# PR: Admin Bar Soft Cache Actions \+ Granular Algolia Cache Controls

**Branch:** feature/admin-bar-soft-cache-actions -> main

## Motivation

We needed production-safe cache operations directly from the admin bar, without opening plugin settings each time.

This PR introduces:

1. A top-level `G4TAds` admin bar menu with two dropdown actions:

    * `Clear All Soft Cache`
    * `Clear Soft Cache from this page`
    
2. Secure AJAX handlers for both global and page-scoped cache clear operations.
3. Granular cache TTL controls for Algolia soft/hard cache in admin settings.
4. A force-refresh flow for banner data \(`g4t_ads_nocache`\) that bypasses read and persists fresh data back to transient cache.

## TL;DR for reviewer

* Added global and page-specific soft-cache clear actions in admin bar.
* Implemented selective transient deletion per product ID.
* Kept VSA boundaries: admin UI/actions in `admin-settings`, cache mechanics in `algolia-ad-server`, page product detection in owning banner features.
* Added configurable Algolia soft/hard TTL settings.
* Added force-refresh behavior for admin debug use-case.

## Scope of changes

### 1\) Algolia cache layer enhancements

Files:

* `features/algolia-ad-server/soft-cache.php`
* `features/algolia-ad-server/product-data.php`

Changes:

* `g4t_algolia_ad_server_soft_cache_set()` now reads cache TTL options via `g4t_core_get_ads_options()`.
* Added `g4t_algolia_ad_server_clear_all_cache()` \(global transient cleanup\).
* Added `g4t_algolia_ad_server_clear_product_cache( $product_ids )` \(targeted cleanup\).
* Added force-refresh path in `product-data.php` for logged-in admins with `?g4t_ads_nocache=1`:

    * bypasses read cache,
    * fetches fresh data from Algolia,
    * writes fresh data back to transient.
    

### 2\) Admin settings \+ admin bar actions

Files:

* `features/admin-settings/settings-page.php`
* `features/admin-settings/index.php`
* `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`

Changes:

* Added secure AJAX handler for global clear:

    * `wp_ajax_g4t_clear_algolia_soft_cache`
    
* Added secure AJAX handler for page-scoped clear:

    * `wp_ajax_g4t_clear_current_page_algolia_soft_cache`
    
* Added top-level admin bar menu node `G4TAds` and dropdown actions:

    * `Clear All Soft Cache`
    * `Clear Soft Cache from this page (N)`
    
* Added frontend/admin script wiring for admin-bar action clicks, confirmation dialogs, AJAX calls, and success feedback.
* Existing settings-page button \(`Clear All Soft Cache`\) remains available.

### 3\) Current page product detection \(feature-owned\)

Files:

* `features/banner-inline-block/helpers.php`
* `features/banner-inline-block/index.php`
* `features/banner-sidebar-widget/index.php`

Changes:

* Added filter contract usage: `g4t_ads_current_page_product_ids`
* Inline banner feature now contributes product IDs parsed from `go4taste-ads/inline-banner` blocks on singular pages.
* Sidebar widget feature contributes its resolved current-post product ID.

This preserves VSA ownership: each feature reports its own product IDs.

### 4\) TTL controls in settings

File:

* `features/admin-settings/settings-page.php`

Changes:

* Added settings fields:

    * `algolia_soft_cache_ttl_hours`
    * `algolia_hard_cache_ttl_days`
    
* Added sanitization and defaults.

### 5\) Copilot/VSA guardrail documentation

File:

* `.github/copilot-instructions.md`

Changes:

* Added an explicit repository rule: whenever a new banner/ads placement is introduced, that feature must provide feature-local integration with `g4t_ads_current_page_product_ids` so `Clear Soft Cache from this page` includes product IDs from the new placement.

## Security and safety

* Both cache-clear AJAX endpoints require:

    * `current_user_can( 'manage_options' )`
    * valid nonce \(`check_ajax_referer`\)
    
* SQL cleanup uses `$wpdb->prepare()` and strict transient prefix patterns.
* Page-scoped cleanup rejects empty product-id payloads.

## Root cause addressed

Operationally, cache clear required manual/transient-level intervention. This PR adds controlled, audited UI actions for admins and avoids broad custom SQL outside cache prefix scope.

## Validation

Static checks:

* no editor-reported syntax errors in modified PHP/JS files.

Manual checks performed/planned:

1. Admin bar shows `G4TAds` with both dropdown actions for admin users.
2. Global clear removes product transient rows and reports deleted count.
3. Page-scoped clear only acts on product IDs detected on current page.
4. `?g4t_ads_nocache=1` fetches fresh Algolia data and overwrites cache.

## Risk / compatibility

* Risk: low to medium \(touches cache control and admin UX paths\).
* Backward compatibility: preserved.
* Architecture: VSA-compliant slice ownership maintained.

## Checklist

* \[x\] Added global soft-cache clear function in Algolia cache slice
* \[x\] Added selective per-product soft-cache clear function
* \[x\] Added secure global clear AJAX endpoint
* \[x\] Added secure current-page clear AJAX endpoint
* \[x\] Added `G4TAds` admin bar menu with two dropdown actions
* \[x\] Added frontend/admin JS action handlers for admin bar
* \[x\] Added page product-ID collection via feature-owned filter contributions
* \[x\] Added configurable Algolia soft/hard TTL settings
* \[x\] Added force-refresh cache bypass/save behavior for admin debug
* \[x\] Added documentation guardrail for new placements and page-scoped cache clear integration

‌

---

<a id="pr-11"></a>
## #11 feat(cache): add on-demand soft/hard cache product counts in options page

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/cache-counts-ui` → `main`
- **Created:** 2026-05-29 · **Closed:** 2026-05-29
- **Merge commit:** `d98b2be839bf`

# PR: On-Demand Soft/Hard Cache Product Counts in Options Page

**Branch:** feature/cache-counts-ui -> main

## Motivation

We needed visibility into how many products are currently stored in Algolia banner cache, but without adding runtime overhead.

Key requirement for performance:

* cache counting must run only on demand \(button click in options page\),
* no automatic counting on page load,
* no counting in frontend/admin runtime flows,
* no `get_transient()` loop for large datasets.

## TL;DR for reviewer

* Added on-demand cache counting \(`soft` and `hard`\) in plugin options page.
* Implemented efficient counting with a single `$wpdb->get_col()` query over transient values.
* `hard_count` = number of transient value rows found.
* `soft_count` = rows where unserialized payload has `soft_expires_at > time()`.
* Added dedicated AJAX endpoint for counting and wired UI button \+ result panel.
* Existing clear endpoints now also return fresh counts after clear \(optional UX enhancement\).

## Scope of changes

### 1\) Cache counting logic \(efficient, OOM-safe\)

File:

* `features/algolia-ad-server/soft-cache.php`

Added:

* `g4t_algolia_ad_server_get_cache_counts()`

Implementation details:

* Single query: `SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE '_transient_g4t_ads_product_%'`
* Uses `$wpdb->esc_like()` \+ `$wpdb->prepare()`
* `hard_count` = `count( $rows )`
* Iterates over row values with `maybe_unserialize()`
* Increments `soft_count` when payload contains `soft_expires_at` greater than current time
* Returns structured result: `success`, `soft_count`, `hard_count`, `executed_at`, optional `error`

### 2\) New AJAX endpoint for on-demand counts

Files:

* `features/admin-settings/settings-page.php`
* `features/admin-settings/index.php`

Added:

* method `ajax_get_algolia_cache_counts()` in admin settings class
* action registration: `wp_ajax_g4t_get_algolia_cache_counts`
* localized JS config:

    * `getCacheCountsAction`
    * `getCacheCountsNonce`
    

Security:

* capability check: `manage_options`
* nonce validation: `check_ajax_referer( 'g4t_get_cache_counts_ajax', 'nonce' )`

### 3\) Options page UI \(only there, only on click\)

File:

* `features/admin-settings/settings-page.php`

Added in Cache Maintenance section:

* button: `Count Cached Products`
* result container: `#g4t-cache-count-result`

Behavior:

* no automatic render of counts on load
* results appear only after click-triggered AJAX response

### 4\) JS handling for count button \+ post-clear refresh

File:

* `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`

Added:

* click handler for `#g4t-count-soft-hard-cache`
* request to `g4t_get_algolia_cache_counts`
* result rendering with `Soft cache products` and `Hard cache products`

Also improved:

* after clear actions, if response includes counts, count panel is refreshed in-place

## Performance considerations

* No runtime counting in frontend.
* No periodic/autoloaded counting in admin page render.
* One SQL query over matching rows \+ lightweight PHP pass over `option_value`.
* Avoids `get_transient()` in loops, minimizing object-cache churn and memory spikes.

## Validation

Static validation:

* no editor-reported syntax errors in modified files

Manual test plan:

1. Open options page, ensure no counts are shown initially.
2. Click `Count Cached Products`.
3. Verify both values are returned:

    * `Soft cache products: X`
    * `Hard cache products: Y`
    
4. Click `Clear All Soft Cache`, verify counts update in response.
5. Click `Count Cached Products` again to confirm zero or expected state.

## Risk / compatibility

* Risk: low \(read-only counting endpoint \+ UI update\).
* Backward compatibility: preserved.
* Architecture: VSA-compliant; logic remains in cache slice, admin UI in admin-settings slice.

## Checklist

* \[x\] Added efficient on-demand cache counting function in `soft-cache.php`
* \[x\] Used `$wpdb->get_col()` \+ `maybe_unserialize()` approach \(no `get_transient` loop\)
* \[x\] Added secure AJAX endpoint for cache counts
* \[x\] Added options-page button and result area for counts
* \[x\] Added JS click handler and count rendering
* \[x\] Updated clear responses to optionally include refreshed counts

‌

---

<a id="pr-12"></a>
## #12 feat(sync): remove legacy cache, optimize sync pipeline, fix reliability

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/sync-pipeline-refactor` → `main`
- **Created:** 2026-05-29 · **Closed:** 2026-05-29
- **Merge commit:** `f734a24f7c4b`

# PR: Refactor product data pipeline — remove legacy cache, optimize sync, fix reliability

**Branch:** feature/sync-pipeline-refactor -> main

## Motivation

The plugin maintained a dual-cache architecture: product data was first cached in PrestaShop Webservice transients \(`product_data_prestashop_webservice_api_*`\) on the frontend fetch path, and then separately stored in the Algolia Ad Server soft-cache layer. This caused stale data risks, redundant DB writes, and a complex sync path that fetched full product payloads for all 537 products just to obtain their IDs.

This PR eliminates the legacy transient layer, optimizes the sync query, and delivers five reliability fixes discovered during testing.

## TL;DR for reviewer

* Frontend product data now routes exclusively through the Algolia Ad Server filter bridge — no live Presta fetches from frontend.
* Sync ID fetch reduced from full payload \(~537 × full XML\) to `display=[id]` only \(~95% payload reduction\).
* Fixed: IPv6 hang \(9\+ min timeout\), XML dual-mode parser, zombie job lock, and BOM-caused jQuery `parsererror`.
* Added one-time admin utility to clean up residual legacy transients from the database.

---

## Scope of changes

### 1. Remove legacy PrestaShop transient cache \(double-cache elimination\)

Files:

* `core/api-client/product-data.php`
* `core/api-client/class-go4taste-ads-api-fetcher.php`
* `features/admin-settings/settings-page.php`
* `features/admin-settings/index.php`
* `features/admin-settings/assets/js/go4taste-ads-admin-settings.js`

Changes:

* `g4t_core_fetch_product_data()` no longer fetches live data from PrestaShop or reads/writes `product_data_prestashop_webservice_api_*` transients. Delegates exclusively to `apply_filters('go4taste_ads_get_product_data', null, $product_id)`.
* Removed all `set_transient` / `get_transient` calls for product data in the frontend fetch path of `class-go4taste-ads-api-fetcher.php`.
* Removed `cache_duration` field, sanitization method, and related admin UI.
* Added **"Clean Legacy PrestaShop Cache"** one-time cleanup button in Cache Maintenance section. On click: deletes all `_transient_product_data_prestashop_webservice_api_*` and `_transient_timeout_product_data_prestashop_webservice_api_*` rows directly via `$wpdb`.
* Registered new AJAX action `g4t_clear_legacy_presta_cache` with `manage_options` capability check and nonce validation.

### 2. Optimize PrestaShop API sync query — fetch IDs only

File:

* `core/api-client/class-go4taste-ads-api-fetcher.php`

Changes:

* `fetch_prestashop_webservice_product_ids()` now appends `display=[id]` to the `/products` endpoint using `http_build_query()` with safe `?`/`&` separator.
* `fetch_product_ids_for_sync()` calls `fetch_prestashop_webservice_product_ids()` directly instead of routing through `fetch_all_products()`.
* XML parser updated to **dual-mode**: checks both `$attrs['id']` \(attribute form\) and `$product_node->id` \(child node form\) — PrestaShop changes the XML structure depending on `display` param.

### 3. Fix network reliability — force IPv4, disable SSL verification

File:

* `core/api-client/class-go4taste-ads-api-fetcher.php`

Changes:

* `wp_remote_get()` in sync path uses: `sslverify => false`, `timeout => 15`.
* `http_api_curl` action temporarily forces `CURLOPT_IPRESOLVE_V4` for the sync request to prevent IPv6 resolution hang \(reproduced as 9\+ minute timeout on localhost\).
* Action is removed immediately after the request completes to avoid side effects on other HTTP calls.

### 4. Remove zombie job lock on sync start

File:

* `features/algolia-product-sync/sync-ajax-handlers.php`

Changes:

* Removed blocking `409 Already Running` guard from `g4t_product_catalog_sync_ajax_start()`.
* Replaced with **hard reset**: if a stale job transient exists at start, it is unconditionally deleted before creating a new job. Eliminates stuck sync state after page reload or interrupted browser sessions.

### 5. Fix AJAX response BOM / output buffer contamination

File:

* `features/algolia-product-sync/sync-ajax-handlers.php`

Changes:

* Added `if ( ob_get_length() ) { ob_clean(); }` before every `wp_send_json_success()` / `wp_send_json_error()` call in `ajax_sync_start` and `ajax_sync_batch` handlers \(10 call sites\).
* Eliminates BOM or whitespace characters emitted by the PHP include chain before JSON output, which caused jQuery `$.ajax` `parsererror` despite HTTP 200 and a valid JSON body.

---

## Performance considerations

* Frontend path: zero live Presta fetches, zero transient writes for product data.
* Sync ID fetch: single lightweight XML with `<id>` nodes only instead of full product payloads.
* Legacy cleanup: one-time SQL DELETE, on-demand only \(admin button click\).

---

## Validation

Static validation:

* No editor-reported syntax errors in any modified file.
* Audit: zero remaining references to `product_data_prestashop_webservice_api_*` in fetch/sync logic.

Manual test plan:

1. Trigger sync from admin — verify start returns correct total count \(537\).
2. Let sync complete — verify all batches finish without `parsererror`.
3. Confirm no `product_data_prestashop_webservice_api_*` transients written to DB during sync.
4. Click "Clean Legacy PrestaShop Cache" — verify `deleted_rows` returned.
5. Interrupt sync mid-run, reload page, start again — verify no zombie job block.

---

## Risk / compatibility

* Risk: low — frontend data path behavior unchanged for consumers \(same filter bridge, same Algolia data\).
* `sslverify => false` is intentional for localhost; production keys are managed via plugin settings.
* `remove_all_actions('http_api_curl')` is broad — acceptable for now \(sync runs in isolation\), but a named callback \+ `remove_action` would be safer in future.
* Backward compatibility: preserved — Algolia Ad Server filter bridge, soft-cache keys, and view model shape unchanged.
* Architecture: VSA-compliant.

---

## Checklist

* \[x\] Legacy transient cache removed from frontend product data path
* \[x\] `cache_duration` setting removed from admin UI
* \[x\] One-time legacy cleanup utility added \(button \+ AJAX \+ JS\)
* \[x\] Sync ID fetch uses `display=[id]` — payload ~95% smaller
* \[x\] XML dual-mode parser handles both attribute and node forms
* \[x\] IPv4 forcing \+ `sslverify=false` \+ timeout=15 in sync HTTP call
* \[x\] Zombie job hard reset on sync start
* \[x\] `ob_clean()` before all `wp_send_json_*` calls in sync handlers
* \[x\] No remaining live references to `product_data_prestashop_webservice_api_*` in fetch/sync logic \(audit passed\)

‌

---

<a id="pr-13"></a>
## #13 refactor(sidebar-widget): unify VSA slice, JSON contract, and archive settings

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/vsa-sidebar-widget-refactor` → `main`
- **Created:** 2026-05-30 · **Closed:** 2026-05-31
- **Merge commit:** `04ef5dabdf22`

# PR: Sidebar Widget Refactor to VSA \+ JSON Contract \+ Archive Settings

**Branch:** feature/vsa-sidebar-widget-refactor -> main

## Motivation

Sidebar widget feature was split across two slices and used mixed persistence rules. This PR finalizes the migration to a single Vertical Slice and aligns storage to the JSON Array contract required by plugin architecture guidelines.

Main goals delivered:

* unification of widget runtime and metabox input into one slice,
* migration from legacy single-string meta to JSON array contract,
* fix for archive/home/search context by reading global option instead of current post meta,
* picker refactor to reusable JS module shared between metabox and archive settings screen.

---

## Scope of changes

### 1\) VSA consolidation into one slice

Files:

* features/banner-sidebar-widget/index.php
* features/banner-sidebar-widget/metabox-registration.php
* features/banner-sidebar-widget/preview-template.php
* features/banner-sidebar-widget/assets/css/go4taste-ads-metabox-preview.css
* core/bootstrap.php

Changes:

* Moved metabox files from removed slice to features/banner-sidebar-widget.
* Unified hook registration in one entrypoint: widgets\_init, wp\_enqueue\_scripts, add\_meta\_boxes, save\_post, wp\_ajax\_g4t\_fetch\_product\_preview, admin\_enqueue\_scripts, g4t\_ads\_current\_page\_product\_ids.
* Removed old bootstrap require to deleted metabox slice.

### 2\) Data contract migration to JSON Array

Files:

* core/api-client/product-data.php
* features/banner-sidebar-widget/metabox-registration.php
* features/banner-sidebar-widget/widget-class.php

Changes:

* Singular source now reads new key \_g4t\_a\_sidebar\_product\_ids \(JSON array\), returns first ID.
* Legacy fallback kept: \_g4t\_a\_sidebar\_regular\_widget\_ps\_product\_id.
* Metabox save now stores JSON array string under \_g4t\_a\_sidebar\_product\_ids.
* Metabox render reads JSON first, then legacy fallback, and still feeds hidden input with one ID for compatibility.
* Widget fallback path hardened: instance fallback\_product\_id first, then global fallback option.

### 3\) Archive fix \+ reusable picker module

Files:

* features/banner-sidebar-widget/assets/js/picker-ui.js
* features/banner-sidebar-widget/archive-settings-page.php
* features/banner-sidebar-widget/index.php
* features/banner-sidebar-widget/metabox-registration.php
* core/api-client/product-data.php

Changes:

* Added reusable picker module: window.G4TAdsPicker\(config\).
* Metabox inline JS replaced with module initialization and localized config.
* Added new settings subpage: Settings -> Go4Taste Archiwa.
* New archive option persistence key: g4t\_a\_archive\_sidebar\_product\_ids \(JSON array\).
* Added secure save endpoint via admin\_post with nonce \+ manage\_options.
* Product resolver now supports contexts:

    * is\_singular\(\): postmeta JSON key \+ legacy fallback,
    * is\_archive\(\)/is\_home\(\)/is\_search\(\): wp\_options JSON key.
    

### 4\) Picker UI CSS cleanup \(remove inline styles\)

Files:

* features/banner-sidebar-widget/assets/css/picker-ui.css
* features/banner-sidebar-widget/metabox-registration.php
* features/banner-sidebar-widget/archive-settings-page.php

Changes:

* Removed inline `<style>` block from metabox renderer.
* Added shared picker stylesheet in slice scope: `assets/css/picker-ui.css`.
* Enqueued the same CSS on both metabox and archive settings screen.
* Kept picker look-and-feel aligned with WordPress admin UI.

### 5\) Pivot: global option \+ term meta \+ cascade resolver

Files:

* features/banner-sidebar-widget/global-settings-integration.php \(new\)
* features/banner-sidebar-widget/term-meta-integration.php \(new\)
* features/banner-sidebar-widget/index.php
* features/banner-sidebar-widget/archive-settings-page.php \(deleted\)
* core/api-client/product-data.php

Changes:

* Removed standalone archive settings subpage \(Settings -> Go4Taste Archiwa\).
* Added global-settings-integration.php: registers g4t\_a\_archive\_sidebar\_product\_ids as a field wired into existing main plugin settings page \(go4taste\_ads\_setting\_section\). Own sanitize callback validates JSON array contract. Picker assets load only when $hook\_suffix === 'toplevel\_page\_go4taste-ads'.
* Added term-meta-integration.php: Algolia picker on category/post\_tag edit forms. Rendered via \{taxonomy\}\_edit\_form\_fields hooks, registered in admin\_init loop. Saved via edited\_term hook \(10, 3\) with dedicated nonce \+ dynamic capability check. delete\_term\_meta called when picker is cleared \(clean empty state\). Assets load only on edit-tags.php / term.php for supported taxonomies. Supported taxonomies extensible via g4t\_banner\_sidebar\_widget\_supported\_taxonomies filter.
* Refactored g4t\_core\_get\_sidebar\_widget\_product\_id\(\) to strict cascade:

    1. is\_singular\(\): postmeta JSON key -> legacy single-string fallback -> continue if both empty
    2. is\_category\(\)/is\_tag\(\)/is\_tax\(\): term\_meta JSON key -> fall through if empty
    3. is\_home\(\)/is\_search\(\)/category/tag/tax: global option JSON key
    4. return '' if nothing matched at any stage Switched from global $post to get\_queried\_object\_id\(\) for singular path.
    

---

## Data contract

* Singular postmeta key: \_g4t\_a\_sidebar\_product\_ids
* Term meta key: \_g4t\_a\_sidebar\_product\_ids \(wp\_termmeta\)
* Archive option key: g4t\_a\_archive\_sidebar\_product\_ids
* Storage format: JSON encoded array of strings, example: \["154"\]

Cascade resolution order \(runtime\):

1. singular: postmeta JSON -> postmeta legacy string
2. category/tag/tax: term\_meta JSON -> fall through if empty
3. home/search/category/tag/tax: wp\_options JSON

Backward compatibility:

* Singular resolver falls back to legacy \_g4t\_a\_sidebar\_regular\_widget\_ps\_product\_id.

---

## Validation

Static validation:

* No editor errors in modified/new files:

    * features/banner-sidebar-widget/assets/js/picker-ui.js
    * features/banner-sidebar-widget/assets/css/picker-ui.css
    * features/banner-sidebar-widget/metabox-registration.php
    * features/banner-sidebar-widget/global-settings-integration.php
    * features/banner-sidebar-widget/term-meta-integration.php
    * features/banner-sidebar-widget/index.php
    * core/api-client/product-data.php
    

Manual test plan:

1. Edit single post metabox, save, verify \_g4t\_a\_sidebar\_product\_ids contains JSON array.
2. Open Settings -> Go4Taste Ads, verify new "Archive Sidebar Product" picker field appears in section. Select product, save, verify g4t\_a\_archive\_sidebar\_product\_ids contains JSON array in wp\_options.
3. Open Categories or Tags admin, edit a term, verify "Sidebar Product" picker appears. Select product, save, verify \_g4t\_a\_sidebar\_product\_ids in wp\_termmeta.
4. Verify widget on singular uses postmeta value.
5. Verify widget on category/tag archive uses term meta value \(if set\).
6. Verify widget on category/tag archive falls through to global option when term meta is empty.
7. Verify widget on home/search uses global option value.
8. Verify legacy singular posts still render through legacy fallback key.

---

## Risk / compatibility

* Risk: low-to-medium \(touches resolver paths and admin UI wiring\).
* Backward compatibility for singular preserved through legacy fallback.
* VSA compliance improved by keeping all sidebar widget capabilities inside one slice.

---

## Checklist

* \[x\] Metabox \+ widget unified under features/banner-sidebar-widget
* \[x\] Old metabox slice removed
* \[x\] JSON array contract implemented for singular placement
* \[x\] Archive/global option path added with JSON array contract
* \[x\] Reusable picker-ui.js extracted and reused by metabox \+ archive settings page
* \[x\] Shared picker-ui.css extracted and enqueued for metabox \+ archive settings page
* \[x\] Secure admin save flow for archive settings \(nonce \+ capability\)
* \[x\] Context-aware resolver for singular vs archive/home/search
* \[x\] Pivot: standalone archive settings subpage replaced by field in main plugin settings
* \[x\] Term meta integration: Algolia picker on category/post\_tag edit screens
* \[x\] Cascade resolver: singular -> term meta -> global option -> empty string
* \[x\] Assets load scoped to correct admin screens \(metabox, main settings, term edit\)

‌

---

<a id="pr-14"></a>
## #14 chore(github): remove unused agents and add planner/implementation agents

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `chore/github-agents-cleanup-planner` → `main`
- **Created:** 2026-05-31 · **Closed:** 2026-05-31
- **Merge commit:** `3fa3fecacd79`

# PR: Cleanup agentów .github \+ nowi agenci Planner i Implementation

Branch: chore/github-agents-cleanup-planner -> main

## Motivation

Porządkujemy katalog agentów w .github, żeby:

* usunąć nieużywane profile,
* uprościć wybór trybu pracy,
* dodać dwa aktualne, praktyczne profile:

1. Planner \(read-only planowanie architektury\),
2. Implementation \(wdrażanie zmian w pluginie zgodnie z VSA\).

Celem jest mniejszy narzut operacyjny i jaśniejszy podział pracy: najpierw plan, potem implementacja.

---

## Scope of changes

### 1\) Usunięcie nieużywanych agentów

Usunięte pliki:

* .github/agents/go4taste-api-security-reviewer.agent.md
* .github/agents/go4taste-plugin-auditor.agent.md
* .github/agents/go4taste-plugin-cleanup.agent.md
* .github/agents/go4taste-plugin-reviewer.agent.md
* .github/agents/go4taste-prestashop-native-api.agent.md
* .github/agents/go4taste-security-hardening.agent.md

### 2\) Ujednolicenie agenta deweloperskiego

Zmiana:

* rename .github/agents/go4taste-plugin-developer.agent.md -> implementation.agent.md

Efekt:

* krótsza, bardziej czytelna nazwa dopasowana do aktualnego workflow.

### 3\) Dodanie nowego agenta planującego

Nowy plik:

* planner.agent.md

Zakres:

* tryb read-only,
* analiza architektury i przepływów danych,
* generowanie implementowalnych planów bez edycji kodu,
* nacisk na zgodność z VSA i kontraktem danych.

---

## Why this is safe

* Zmiany dotyczą wyłącznie katalogu .github/agents.
* Brak modyfikacji runtime pluginu \(PHP/JS/CSS odpowiedzialnych za działanie produkcyjne\).
* Brak zmian w bazie danych, hookach i API.
* Brak wpływu na frontend/backend WordPress poza ergonomią pracy z agentami.

---

## Validation

Weryfikacja zakresu:

1. Zmiany zawierają tylko operacje D/R/A w .github/agents.
2. Brak zmian w feature slices, core i assetach pluginu.
3. Commit ma pojedynczy, logiczny zakres \(cleanup \+ nowe profile agentów\).

Stan git:

* commit: 38e34cb
* message: chore\(github\): remove unused agents and add planner/implementation agents

---

## Risk / compatibility

* Ryzyko techniczne dla pluginu: bardzo niskie.
* Ryzyko procesowe: niskie \(zmiana nazw i zestawu agentów może wymagać krótkiego przyzwyczajenia\).

Mitigacja:

* Nazwy agentów są prostsze i odzwierciedlają realny workflow \(Planner -> Implementation\).

---

## Checklist

* \[x\] Usunięto nieużywane agenty z .github/agents
* \[x\] Zmieniono nazwę agenta developerskiego na implementation.agent.md
* \[x\] Dodano planner.agent.md
* \[x\] Zachowano pojedynczy, spójny zakres zmian
* \[x\] Brak zmian poza .github/agents

‌

---

<a id="pr-15"></a>
## #15 Feature/banner extensibility

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/banner-extensibility` → `main`
- **Created:** 2026-05-31 · **Closed:** 2026-06-02
- **Merge commit:** `3f6808c928a0`

# PR: Ads Extensibility — Placement Registry \+ Shared Payload Resolver \+ Recipe Strip

**Branch:** feature/banner-extensibility -> main

## Implementation status

| Phase | Status | Description |
| --- | --- | --- |
| Phase 1: Core registry | ✅ Done | Placement registry, resolver API, compatibility bridge |
| Phase 2: Existing placements → registry | ✅ Done | Inline block \+ sidebar widget wired through registry |
| Phase 2b: Shared payload resolver | ✅ Done | Normalized product payload, render dispatcher, theme template override |
| Phase 2c: Recipe bottom strip slice | ✅ Done | New `recipe_bottom_strip` placement \+ helper for recipes plugin |
| Phase 3: Recipes plugin integration | ✅ Done | Replace `g4t_fetch_ads_products()` with shared resolver \+ backward compat fallback |
| Phase 4: ACF form adapter for recipe bottom strip | ✅ Done | Implemented in `go4taste-recipes-plugin`: picker UI \+ save to `_g4t_a_recipe_bottom_product_ids` |
| Phase 5: Cache invalidation | ⏭️ Skipped in this PR | Deferred intentionally to next branch/PR |

---

## Motivation

The current ads plugin already supports two concrete banner entry paths: an inline Gutenberg block and a sidebar widget. That works for the present use case, but it does not yet provide a clean extension model for future ways of adding banners or rendering them in other contexts such as a theme-level ACF form flow.

This plan defines how to turn the current implementation into a reusable banner platform where new placements can be added without duplicating data access, product resolution, or frontend rendering logic.

Main goals:

* keep existing block and widget behavior intact,
* introduce a stable placement registry for new banner entry points,
* separate banner data storage from banner rendering,
* make theme-level or external-plugin extensions possible without touching core placement logic,
* keep all runtime dependencies aligned with the VSA rules already used in the repository.

---

## Desired outcome

After the next implementation phase, the plugin should support these ideas:

1. A placement is a declared capability, not a hardcoded branch in a single renderer.
2. Different UI surfaces can register or edit banner product IDs, but all of them persist the same JSON array contract.
3. Rendering uses a shared resolver and normalized payload, regardless of whether the source is Gutenberg, widget settings, term meta, theme settings, or an ACF front-end form.
4. A theme or companion plugin can add a new placement by registering metadata, a save surface, and a render adapter, without forking existing logic.

---

## Proposed architecture

### 1\) Core banner registry

Create a lightweight registry in `go4taste-ads` that stores banner placements as structured definitions.

Each placement definition should include:

* placement key,
* label and description for admin UI,
* storage scope \(`postmeta`, `termmeta`, `option`, or custom adapter\),
* data key or option key,
* supported contexts where the placement may render,
* resolver callback for reading raw IDs,
* renderer callback or template hook for emitting markup.

This registry becomes the single source of truth for what placements exist.

### 2\) Shared resolver layer

Move all banner product retrieval behind one resolver that:

* reads the JSON array contract first,
* falls back to legacy single-string keys only where required,
* normalizes all IDs into one format,
* returns a render-ready payload for the frontend.

This resolver should not know whether the data came from a metabox, a widget, a theme setting, or an ACF form. It only resolves a placement key into products.

### 3\) Shared render payload

Standardize the render payload so every placement returns the same shape.

The payload should at minimum contain:

* placement key,
* source type,
* normalized product list,
* display metadata,
* optional CTA/label values,
* optional template variant.

The existing frontend, including the theme, should consume this payload and stay ignorant of how the data was entered.

### 4\) Adapter-based entry points

New ways of adding banners should be implemented as adapters, not as separate parallel systems.

Examples:

* Gutenberg block adapter,
* sidebar widget adapter,
* term edit screen adapter,
* main settings page adapter,
* theme ACF front-end form adapter,
* external plugin adapter.

Each adapter should only handle its own UI and save flow, then write into the same placement contract.

### 5\) Theme integration boundary

If banner entry lives in the theme, the theme should only provide:

* form/template markup,
* optional styling,
* placement registration or filter configuration.

The theme should not own product resolution or banner payload construction. Those remain in `go4taste-ads`.

---

## Implementation phases

### ✅ Phase 1: Core abstraction — DONE

**Files added:**

* `core/placements/registry.php`

**What was delivered:**

* `g4t_ads_add_placement_definition()` — builder for a placement definition array with sanitized fields.
* `g4t_ads_get_registered_placements()` — returns all placements collected through `go4taste/ads/register_placement` filter.
* `g4t_ads_get_current_page_product_ids_by_placement()` — calls each placement's `current_page_product_ids_callback` and groups results by key.
* `g4t_ads_get_current_page_product_ids()` — flat merged list across all placements for compatibility.
* `g4t_ads_collect_registered_placements_current_page_product_ids()` — compatibility bridge wired into existing `g4t_ads_current_page_product_ids` filter at priority 5.
* `core/bootstrap.php` updated to load `registry.php` before feature slices.

**Public hook introduced:**

* `go4taste/ads/register_placement` — filter where any slice, theme, or external plugin registers its placement definition.

---

### ✅ Phase 2a: Existing placements onto registry — DONE

**Files changed:**

* `features/banner-inline-block/index.php`
* `features/banner-inline-block/helpers.php`
* `features/banner-sidebar-widget/index.php`

**What was delivered:**

* Extracted `g4t_inline_banner_get_current_page_product_ids_for_placement()` — pure product ID resolver for inline block, no filter coupling.
* `g4t_inline_banner_collect_current_page_product_ids()` kept as backward-compatible wrapper.
* `g4t_inline_banner_register_placement()` registered via `go4taste/ads/register_placement`.
* Old direct `add_filter('g4t_ads_current_page_product_ids', ...)` replaced in inline block.
* Extracted `g4t_banner_sidebar_widget_get_current_page_product_ids_for_placement()` — delegates to cascade resolver.
* `g4t_banner_sidebar_widget_collect_current_page_product_ids()` kept as backward-compatible wrapper.
* `g4t_banner_sidebar_widget_register_placement()` registered via `go4taste/ads/register_placement`.
* Old direct `add_filter('g4t_ads_current_page_product_ids', ...)` replaced in sidebar widget.

---

### ✅ Phase 2b: Shared payload resolver — DONE

**Files added:**

* `core/placements/payload.php`
* `core/placements/render.php`

**What was delivered:**

`payload.php`:

* `g4t_ads_normalize_product_item()` — canonical flat array for one product, consumed by all renderers; shape: `id, name, price, price_tax_exc, price_without_reduction, picture, link, category, image_width, image_height`.
* `g4t_ads_build_placement_payload()` — builds normalized payload for a placement key \+ product ID list. Calls API fetcher per ID, normalizes each item, returns `{ placement_key, source, products[], context }`.
* `g4t_ads_build_current_page_payload()` — builds payloads for all current-page placements in one call.

`render.php`:

* `g4t_ads_get_placement_template_path()` — resolves template with theme override support. Theme lookup order: `go4taste-ads/{key}.php`, then `go4taste-ads/placement.php`. Plugin fallback: `core/placements/templates/{key}.php`.
* `g4t_ads_render_placement()` — dispatcher: prefers `render_callback` from registry, falls back to template. Returns HTML string. Can be called from theme or any external code.

**Public hooks introduced:**

* `go4taste/ads/placement_product_item` — filter per product item before it enters payload.
* `go4taste/ads/resolve_placement_payload` — filter on the complete payload after resolution.
* `go4taste/ads/before_render_placement` — action before render starts.
* `go4taste/ads/render_placement` — filter on final HTML output.
* `go4taste/ads/placement_template_path` — filter on resolved template path.

---

### ✅ Phase 2c: Recipe bottom strip placement — DONE

**Files added:**

* `features/recipe-bottom-strip/index.php`
* `features/recipe-bottom-strip/helpers.php`

**What was delivered:**

`index.php`:

* Registers placement `recipe_bottom_strip` through `go4taste/ads/register_placement`.
* Storage key: `_g4t_a_recipe_bottom_product_ids` \(JSON array contract\).
* Legacy fallback: `_g4t_a_recipe_bottom_ps_product_ids`.
* Resolver: `g4t_recipe_bottom_strip_get_current_page_product_ids()` — reads post meta on `is_singular()`.

`helpers.php`:

* `g4t_recipe_bottom_strip_get_products_for_post( int $post_id )` — integration point for `go4taste-recipes-plugin` view-model builder. Reads post meta directly \(independent of `get_queried_object_id()`\), calls shared `g4t_ads_build_placement_payload()`, returns normalized products array ready for Timber templates.

`core/bootstrap.php` updated to load both `helpers.php` and `index.php` for this slice.

---

### ✅ Phase 3: Recipes plugin integration — DONE

**File changed:**

* `go4taste-recipes-plugin/features/recipe-display/view-model-builder.php`

**What was delivered:**

* `g4t_build_single_recipe_view_model()` now calls `g4t_recipe_bottom_strip_get_products_for_post($post_id)` when the function is available \(guarded with `function_exists()`\).
* Double fallback preserved for backward compatibility:

    1. If go4taste-ads is inactive — falls back to local `g4t_fetch_ads_products()`.
    2. If new meta key `_g4t_a_recipe_bottom_product_ids` is empty but old ACF repeater data exists — also falls back to `g4t_fetch_ads_products()` so existing recipes render correctly without migration.
    
* `ads.enabled` now reflects whether products were actually resolved, not just whether IDs exist in ACF meta.
* `g4t_fetch_ads_products()` is NOT removed — retained as legacy fallback function.

---

### ✅ Phase 4: ACF form adapter for recipe bottom strip — DONE \(recipes plugin\)

**Scope:**

* Add a picker UI to the `go4taste-recipes-plugin` ACF front-end form \(last step of recipe wizard\).
* Save selected product IDs as `_g4t_a_recipe_bottom_product_ids` \(JSON array\) in post meta.
* Picker should reuse the existing `G4TAdsPicker` JS module from `banner-sidebar-widget/assets/js/picker-ui.js`.
* The save path writes only to the meta key; no logic duplication with the resolver.
* This lives in `go4taste-recipes-plugin` or in the theme — `go4taste-ads` core is not modified.

**Status detail:**

* Implemented in `go4taste-recipes-plugin` frontend creator \(step 6 picker adapter\).
* Persisted `_g4t_a_recipe_bottom_product_ids` using the JSON array contract.
* Added backward-compatible fallback to legacy `recipe_ads_products` source in creator edit mode.
* Added post-save synchronization path for the new meta key.

---

### ⏭️ Phase 5: Cache and invalidation — SKIPPED IN THIS PR

**Scope:**

* Add per-placement transient cache layer in `g4t_ads_build_placement_payload()`.
* Cache key: `g4t_ads_payload_{placement_key}_{md5(sorted_ids)}`.
* Soft TTL: configurable \(default 24h\), hard TTL \(default 7d\) matching existing banner cache settings.
* Invalidation hook `go4taste/ads/invalidate_placement_cache` — fire on post save, term save, and product sync.
* Invalidation scope: by placement key, by product ID, or site-wide.

**Status detail:**

* Explicitly out of scope for this PR to keep the change set focused and reviewable.
* Planned as a follow-up implementation on a dedicated branch/PR.

---

## Suggested hook surface

The implementation should expose a small, stable public surface. Suggested hooks:

* `go4taste/ads/register_placement`
* `go4taste/ads/resolve_placement_payload`
* `go4taste/ads/render_placement`
* `go4taste/ads/invalidate_placement_cache`
* `go4taste/ads/placement_supported_contexts`
* `go4taste/ads/placement_storage_key`

These hooks allow external code to add new banner entry points while keeping the core resolver in charge of data shape and rendering rules.

---

## Data contract

The universal storage contract should remain the same across all placements:

* storage format: JSON encoded array of strings,
* example: `["154", "89"]`,
* empty state: `""` or `[]` when explicitly cleared,
* legacy fallback: allowed only where already required for compatibility.

The important rule is that the placement type changes, but the contract does not.

---

## Placement model

Each placement should be able to define:

* where it can be edited,
* how the saved data is stored,
* how it is rendered on the frontend,
* which contexts can consume it,
* whether it uses a template part or direct markup,
* whether it is site-wide, taxonomy-scoped, or post-scoped.

This gives us a clean path for banners in the theme, in another plugin, or in a future admin surface.

---

## Risks and compatibility

* Risk: duplicated logic if each adapter starts resolving products on its own. Mitigation: require adapters to call the shared resolver only.
* Risk: breaking current Gutenberg/widget behavior during refactor. Mitigation: keep existing placement keys and only move them behind the registry.
* Risk: theme-level code becoming a second source of truth. Mitigation: theme code may register UI, but it should never own the canonical data contract.
* Risk: cache invalidation gaps after product updates. Mitigation: tie invalidation to save hooks and product change hooks from day one.

---

## Validation plan

### Already verifiable now

1. Existing inline block and sidebar widget banner flows render exactly as before — both are now backed by registry placement definitions, compatibility collector bridges keep `g4t_ads_current_page_product_ids` filter working for any existing consumer.
2. A new placement \(`recipe_bottom_strip`\) was registered without editing the existing widget or block code.
3. All placement resolvers accept the same JSON array contract via `_g4t_a_*_product_ids` meta keys.
4. `g4t_ads_render_placement('recipe_bottom_strip')` can be called from the theme and will produce output if the meta key is populated — no custom resolver logic needed in the theme.

### Pending verification \(Phase 3–5\)

5. Phase 5 cache invalidation is intentionally deferred to a dedicated follow-up PR.
6. ✅ ACF form picker saves `_g4t_a_recipe_bottom_product_ids` and the strip renders on the next page load.
7. ✅ `go4taste-recipes-plugin` view model delegates to shared resolver when go4taste-ads is active; falls back gracefully when it is not.

---

## Public hook surface \(current state\)

All hooks are live and stable:

| Hook | Type | Description |
| --- | --- | --- |
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

## Merge readiness

* This PR is merge-ready for Phases 1-4.
* Phase 5 \(cache/invalidation\) is intentionally excluded and tracked as follow-up scope.

**Remaining decisions before Phase 5:**

* Confirm Phase 5 cache scope: per post, per placement\+ids hash, or both.

**Resolved decisions:**

* `go4taste-recipes-plugin` guards the call with `function_exists()` — soft dependency confirmed.
* ACF form picker for recipe bottom strip lives in `go4taste-recipes-plugin` \(co-located with creator UI and save flow\).

‌

---

<a id="pr-16"></a>
## #16 docs(pr): add picker-ui extensibility plan

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/picker-ui-extensibility` → `main`
- **Created:** 2026-06-02 · **Closed:** 2026-06-03
- **Merge commit:** `7475ecf886af`

# PR: Picker Data Extensibility — Provider Contract for Creator-Native UI

**Branch:** feature/picker-ui-extensibility -> main

## Implementation status

| Stage | Status | Description |
| --- | --- | --- |
| Stage 1: Picker context contract | 🔲 Next | Standardize context/variant inputs passed to picker runtime |
| Stage 2: Preview payload filters | 🔲 Next | Add hooks for preview payload and image URL overrides |
| Stage 3: Template override boundary | 🔲 Next | Add template path override for picker preview rendering |
| Stage 4: Surface adapters | 🔲 Next | Wire metabox, term, global settings, and external adapters to context contract |
| Stage 5: Docs \+ QA matrix | 🔲 Next | Publish hook contract and validate backward compatibility |

---

## Motivation

Recipe creator needs to keep its own frontend layout \(`product-list`, `.search-box`, `selected-strip`\) while reusing Ads logic as data/provider layer. This PR scope in ads plugin should therefore focus on stable provider contract and hooks, not enforcing the standard picker UI on every surface.

Main goals:

* keep one shared provider/runtime contract,
* allow external surfaces to consume data with their own UI,
* preserve backward compatibility for existing sidebar/metabox/term settings flows,
* avoid duplicating product resolution and preview payload mapping across plugins.

---

## Desired outcome

After this implementation:

1. Surfaces can declare context/variant/metadata to the Ads provider contract.
2. External adapters \(like recipe creator\) can consume data without mounting default picker UI widgets.
3. Preview payload and template path remain overrideable via stable hooks.
4. Existing admin/widget surfaces keep current behavior by default.

---

## Proposed architecture

### 1\) Context and variant contract

Every picker init call should pass:

* context key \(for example: metabox, term\_edit, global\_settings, recipe\_creator\),
* style variant \(for example: default, creator, compact\),
* optional metadata \(post\_id, term\_id, screen\_id\).

### 2\) Shared preview payload extension

Preview generation should expose extension hooks for:

* full preview payload,
* preview image URL,
* optional labels/metadata.

### 3\) Template override boundary

Preview renderer should support template path override via filter. Default template remains current one.

### 4\) Surface adapters

Each surface keeps ownership of UI rendering and user interactions. Ads contract provides:

* context/variant declaration support,
* product/preview data boundary,
* no requirement to reuse default `.ais-*` UI controls on custom surfaces.

### 5\) Backward compatibility

If no new filters are used:

* runtime behavior and visuals remain identical to current implementation.

---

## Suggested public hook surface

* `go4taste/ads/picker_preview_payload`
* `go4taste/ads/picker_preview_image_url`
* `go4taste/ads/picker_preview_template_path`
* `go4taste/ads/picker_style_variant`
* `go4taste/ads/picker_context`

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

* Risk: visual regressions in existing admin flows. Mitigation: preserve default template and variant as fallback.
* Risk: fragmented extension points. Mitigation: publish one small, stable hook contract.
* Risk: duplicated logic in adapters. Mitigation: keep payload/template decisions in shared picker boundary.

---

## Decision points

* Final naming for context and variant keys.
* Whether to include optional preview HTML cache in this PR or defer.

---

## Merge readiness

* This branch is planning scope only at this moment.
* Merge readiness will be reassessed after Stage 1-4 implementation and QA.

‌

---

<a id="pr-17"></a>
## #17 Feature/use ads data provider

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/use-ads-data-provider` → `main`
- **Created:** 2026-06-03 · **Closed:** 2026-06-03
- **Merge commit:** `c92484d5b1c8`

# PR: Algolia Data Provider — Shared JS Layer for All Picker Surfaces

**Branch:** feature/use-ads-data-provider -> main

## Implementation status

| Stage | Status | Description |
| --- | --- | --- |
| Stage 1: JS data provider | ✅ Done | `G4TAdsProvider` module — shared search/fetch/normalize over algoliasearch SDK |
| Stage 2: Refactor picker-ui.js | ✅ Done | Replaced InstantSearch.js \+ jQuery with provider as data layer |
| Stage 3: Surface adapters | ✅ Done | Metabox, term edit, global settings — all use provider, no own credentials |
| Stage 4: Docs \+ QA matrix | ✅ Done | All surfaces QA-passed; two bugs found and fixed during QA |

---

## Context: what is already in place

PR #15 \(`feature/banner-extensibility`\) merged the PHP foundation:

* Placement registry \(`core/placements/registry.php`\) — `go4taste/ads/register_placement` filter
* Shared payload resolver \(`core/placements/payload.php`\) — normalized product shape for PHP renderers
* Render dispatcher \(`core/placements/render.php`\) — `g4t_ads_render_placement()` callable from theme/plugin
* Recipe bottom strip slice \(`features/recipe-bottom-strip/`\) — `g4t_recipe_bottom_strip_get_products_for_post()` integration point

The recipes plugin \(`feature/picker-ui-extensibility` on that side\) added:

* Step 6 picker UI in the recipe creator \(native frontend controls, not the ads widget\)
* Save path via flat `g4t_recipe_ads_product_ids_json` POST field
* Read path via `g4t_recipe_bottom_strip_get_products_for_post()`
* Credentials consumed from `go4taste_ads_options` passed as `G4T_RECIPE_CREATOR_DATA.adsPicker`

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

* single Algolia client instance per page, shared across surfaces,
* one canonical normalization of Algolia hits to a product shape,
* picker-ui.js and creator.js both use the provider; neither owns data logic,
* new surfaces \(new admin screen, external plugin\) get data layer for free.

---

## Desired outcome

After this implementation:

1. `window.G4TAdsProvider` is the single entry point for Algolia product data on any surface.
2. `picker-ui.js` is a UI layer only — no data logic of its own.
3. `creator.js` \(recipes plugin\) replaces direct `algoliaIndex.search()` calls with provider calls.
4. Normalization lives in one place; both surfaces use the same product shape.
5. A new surface needs only `G4TAdsProvider.getInstance().search(query)` to get results.

---

## Architecture

### Stage 1: `G4TAdsProvider` module

New file: `core/provider/algolia-provider.js`

```
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

```
wp_localize_script('g4t-ads-provider', 'G4T_ADS_PROVIDER_DATA', [
    'appId'     => $options['algolia_app_id'],
    'apiKey'    => $options['algolia_search_api_key'],
    'indexName' => $options['algolia_index_name'] ?? 'go4taste_product_list',
]);
```

---

### Stage 2: Refactor `picker-ui.js`

* Remove `window.instantsearch` dependency entirely.
* Remove jQuery dependency from data path \(keep only for DOM manipulation if needed, or drop jQuery completely\).
* Replace `search.addWidget(hits(...))` with `G4TAdsProvider.getInstance().search(query)` \+ own rendering.
* Replace `index.getObject(id)` with `G4TAdsProvider.getInstance().getById(id)`.
* `picker-ui.js` becomes UI-only: search input listener → provider → render hits → selection state.

InstantSearch.js script tag can be removed from the enqueue list once no widget uses it.

---

### Stage 3: Surface adapters

Each existing admin surface \(metabox, term edit, global settings\) that currently initializes its own Algolia client gets updated to call `G4TAdsProvider.getInstance()` instead. No logic changes to save/read paths — only the data fetch layer changes.

---

### Stage 4: Docs \+ QA

* Document `G4TAdsProvider` public API \(search, getById, getByIds\).
* QA matrix: metabox picker, term edit picker, global settings picker, recipe creator — all must search and select correctly after refactor.
* Confirm single client instance: no duplicate Algolia calls on pages with multiple surfaces.

---

## Suggested public JS API

```
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

**Cause:** `algolia-provider.js` originally used `document.addEventListener('DOMContentLoaded', ...)` to init the provider. Inline scripts in admin templates \(body HTML\) register their `DOMContentLoaded` listeners _before_ footer scripts execute — so the picker's listener fired before the provider had initialized. `getInstance()` returned `null`.

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

8. Visual QA of admin picker inputs \(inline style vs theme-native input styling\).
9. Recipes plugin follow-up PR \(`feature/use-ads-data-provider`\) — merge after this PR.

---

## Risks and mitigations

* Risk: InstantSearch.js removal breaks existing search UX in admin pickers. Mitigation: Stage 2 replaces widgets one-to-one; input debounce, loading state, and empty state are preserved in custom rendering.
* Risk: duplicate client init if provider `init()` is called more than once. Mitigation: singleton guard — second `init()` call is a no-op.
* Risk: recipes plugin relies on `normalizeProductRecord()` which will not be removed — it stays as internal state normalizer; provider hits pass through it since it handles both provider and legacy field names. Mitigation: no action needed — both shapes are handled by existing `normalizeProductRecord()` logic.

---

## Dependencies

* **go4taste-recipes-plugin** `feature/use-ads-data-provider` — companion PR; removes direct `algoliaIndex.search()` and `window.algoliasearch()` from `creator.js`, uses `G4TAdsProvider` instead. Already implemented and QA-passed alongside this PR.
* PHP foundation \(registry, payload, render, recipe-bottom-strip\) already on main from PR #15 — no changes needed there.

---

## Merge readiness

All stages implemented and QA-passed. Both bugs found during QA diagnosed and fixed. Follow-up item 8 \(input styling\) is cosmetic and does not block merge.

* Unblocks: recipes plugin follow-up PR `feature/use-ads-data-provider`.

‌

---

<a id="pr-18"></a>
## #18 Feature/specific prices dual pricing

- **State:** MERGED
- **Author:** Przemek Cichoń
- **Branch:** `feature/specific-prices-dual-pricing` → `main`
- **Created:** 2026-06-25 · **Closed:** 2026-06-25
- **Merge commit:** `8c16073d7d83`

# PR: PrestaShop specific\_prices — dual pricing \+ per-environment Algolia credentials

**Branch:** feature/specific-prices-dual-pricing -> main

## Summary

This PR adds two related improvements to the product sync pipeline:

1. **PrestaShop** `specific_prices` **support** — products with an active discount are now synced to Algolia with both prices, so banners can render the original price struck through and the discounted price next to it.
2. **Per-environment Algolia credentials** — Algolia App ID and API keys can now be defined as `wp-config.php` constants that take priority over the database options, so a development sync never overwrites production Algolia data.

---

## 1. PrestaShop specific\_prices — dual price output

### Problem

The sync only pushed the base PrestaShop price to Algolia. Active promotional prices \(`specific_prices`\) were ignored, so discounted products showed the regular price and the banner had no way to display an old/new price pair.

### What changed

The fetcher now queries `/api/specific_prices?filter[id_product]=[{id}]&display=full` for each product and applies the active rule when present:

* **Active-rule detection** — validates `from`/`to` date window \(treats `0000-00-00 00:00:00` as open-ended\).
* **Scope filtering** — only applies rules scoped to all customers/currencies/countries/groups. `id_shop` is intentionally **not** treated as a disqualifying restriction \(in a single-shop PrestaShop, `id_shop=1` is the default binding, not a customer-segment limit\).
* **Candidate selection** — when multiple rules match, the latest valid rule \(highest `id`\) wins.
* **Discount calculation** — supports both `percentage` and `amount` reduction types, honours `reduction_tax`, and respects an explicit `price` override when set.
* **Fail-safe mapping** — `map_prestashop_webservice_product()` is wrapped in try/catch so a malformed specific-price response can never break the whole sync.

### Price shape pushed to Algolia

Each product record now carries:

* `price` — the price to display \(discounted when a rule is active, otherwise the base price\),
* `price_without_reduction` — the original price, only set when a discount is active.

Two distinct formatters are used:

* base price → threshold rounding \(existing behaviour\),
* discounted price → exact 2-decimal Polish format, so e.g. `26,88` is never rounded to `27,00`.

A `go4taste/ads/prestashop_mapped_prices` filter is exposed for downstream overrides.

### Rendering

Banner templates \([banner-sidebar-widget/widget-template.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/features/banner-sidebar-widget/widget-template.php), [banner-inline-block/src/render.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/features/banner-inline-block/src/render.php)\) show the old price only when `price_without_reduction` differs from `price`, with defensive fallbacks for empty values.

### Worked example \(product 206\)

`32,00 zł` base × `0,16` percentage reduction → `26,88 zł` displayed, `32,00 zł` struck through. Verified end-to-end against the live `specific_prices` payload.

---

## 2. Per-environment Algolia credentials \(wp-config.php constants\)

### Problem

Algolia credentials were read directly from the `go4taste_ads_options` database row. Production and development share the same database snapshots during testing, so a dev sync could overwrite production Algolia data with dev records.

### What changed

New core boundary file [core/config/algolia-credentials.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/core/config/algolia-credentials.php) resolves each credential with the precedence **wp-config.php constant → database option**:

```
define( 'GO4TASTE_ADS_ALGOLIA_APP_ID',        '...' );
define( 'GO4TASTE_ADS_ALGOLIA_API_KEY',       '...' ); // search-only (public)
define( 'GO4TASTE_ADS_ALGOLIA_ADMIN_API_KEY', '...' ); // admin/write (backend only)
```

Public API:

* `g4t_ads_get_algolia_app_id()`
* `g4t_ads_get_algolia_search_key()`
* `g4t_ads_get_algolia_admin_key()`
* `g4t_ads_is_algolia_credential_constant_defined( $which )` — used by the admin UI

Credentials are only `trim()`-ed, never passed through `esc_attr()`/sanitizers that could HTML-encode and corrupt a key.

### Integration points

The resolver is loaded first in the core bootstrap, and all direct option reads were replaced with the getters:

| File | Reads |
| --- | --- |
| [core/api-client/class-go4taste-ads-api-fetcher.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/core/api-client/class-go4taste-ads-api-fetcher.php) | app\_id \+ admin key \(write/sync\) |
| [core/provider/index.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/core/provider/index.php) | app\_id \+ search key |
| [features/algolia-ad-server/product-data.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/features/algolia-ad-server/product-data.php) | app\_id \+ search key |
| [features/banner-inline-block/render.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/features/banner-inline-block/render.php) | app\_id \+ search key |

### Admin UI

In [features/admin-settings/settings-page.php](https://file+.vscode-resource.vscode-cdn.net/c%3A/Users/pc5123/Local%20Sites/go4taste-przepisy/app/public/wp-content/plugins/go4taste-ads/features/admin-settings/settings-page.php), when a credential is overridden by a constant the corresponding field is rendered `readonly`/`disabled` with a notice: _"Nadpisane przez stałą w wp-config.php"_. The write key field stays backend-only and is never prefilled.

### Important deployment note

The `define()` calls **must** sit **above** `require_once ABSPATH . 'wp-settings.php';` in `wp-config.php`. If placed below it, the constants are not visible during sync \(plugins load inside `wp-settings.php`\) and the resolver silently falls back to the database.

---

## Architecture / VSA compliance

* New credential resolver lives in `core/` \(shared API boundary\) — features may consume it, it consumes nothing from features.
* specific\_prices logic stays inside the existing `core/api-client/` fetcher; rendering stays inside the respective banner feature slices.
* No central hook loader reintroduced; no horizontal `admin/`/`public/`/`includes/` runtime layers added.

---

## Files changed

```
SPECIFIC_PRICES_VALIDATION_SCENARIOS.md                 (new) test scenarios
core/config/algolia-credentials.php                     (new) credential resolver
core/bootstrap.php                                      load resolver first
core/api-client/class-go4taste-ads-api-fetcher.php      specific_prices + getters
core/provider/index.php                                 credential getters
features/admin-settings/settings-page.php               constant-override badges
features/algolia-ad-server/product-data.php             credential getters
features/algolia-product-sync/sync-ajax-handlers.php    sync wiring
features/banner-inline-block/render.php                 credential getters
features/banner-inline-block/src/render.php             dual price rendering
features/banner-sidebar-widget/widget-template.php      dual price rendering
```

---

## Validation

### specific\_prices

1. ✅ Product 206 \(16% reduction\) syncs as `26,88 zł` / `32,00 zł`.
2. ✅ Products without an active rule keep the base price, no `price_without_reduction`.
3. ✅ Full catalogue \(538 products\) syncs without fatal errors.
4. ✅ Banner widget \+ inline block render old price struck through only when prices differ.

### Algolia credentials

5. ☐ No constants defined → resolver falls back to DB \(existing behaviour, 206 sync still correct\).
6. ☐ Constants defined above `wp-settings.php` → sync log shows the constant `app_id`; admin fields flagged as overridden and read-only.

> Items 5–6 to be re-confirmed on the target server after pulling this branch and adding the constants to `wp-config.php`.

---

## Follow-up

* Diagnostic `[G4T Sync]` / `[G4T Sync Debug]` `error_log()` lines were left in place intentionally for server-side verification. A separate cleanup commit can remove them before merge once the credential override is confirmed working on the server.

---

## Risks and mitigations

* **Risk:** a malformed `specific_prices` response breaks the sync. **Mitigation:** per-product mapping is wrapped in try/catch; failures fall back to the base price.
* **Risk:** constants placed below `wp-settings.php` are ignored. **Mitigation:** documented above; admin UI badge makes an active override visible.
* **Risk:** credential corruption via HTML-escaping. **Mitigation:** resolver applies `trim()` only, never `esc_attr()`.

---

Previous PR description \(Algolia Data Provider — shared JS layer\)

# PR: Algolia Data Provider — Shared JS Layer for All Picker Surfaces

**Branch:** feature/use-ads-data-provider -> main

## Implementation status

| Stage | Status | Description |
| --- | --- | --- |
| Stage 1: JS data provider | ✅ Done | `G4TAdsProvider` module — shared search/fetch/normalize over algoliasearch SDK |
| Stage 2: Refactor picker-ui.js | ✅ Done | Replaced InstantSearch.js \+ jQuery with provider as data layer |
| Stage 3: Surface adapters | ✅ Done | Metabox, term edit, global settings — all use provider, no own credentials |
| Stage 4: Docs \+ QA matrix | ✅ Done | All surfaces QA-passed; two bugs found and fixed during QA |

---

## Context: what is already in place

PR #15 \(`feature/banner-extensibility`\) merged the PHP foundation:

* Placement registry \(`core/placements/registry.php`\) — `go4taste/ads/register_placement` filter
* Shared payload resolver \(`core/placements/payload.php`\) — normalized product shape for PHP renderers
* Render dispatcher \(`core/placements/render.php`\) — `g4t_ads_render_placement()` callable from theme/plugin
* Recipe bottom strip slice \(`features/recipe-bottom-strip/`\) — `g4t_recipe_bottom_strip_get_products_for_post()` integration point

The recipes plugin \(`feature/picker-ui-extensibility` on that side\) added:

* Step 6 picker UI in the recipe creator \(native frontend controls, not the ads widget\)
* Save path via flat `g4t_recipe_ads_product_ids_json` POST field
* Read path via `g4t_recipe_bottom_strip_get_products_for_post()`
* Credentials consumed from `go4taste_ads_options` passed as `G4T_RECIPE_CREATOR_DATA.adsPicker`

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

* single Algolia client instance per page, shared across surfaces,
* one canonical normalization of Algolia hits to a product shape,
* picker-ui.js and creator.js both use the provider; neither owns data logic,
* new surfaces \(new admin screen, external plugin\) get data layer for free.

---

## Desired outcome

After this implementation:

1. `window.G4TAdsProvider` is the single entry point for Algolia product data on any surface.
2. `picker-ui.js` is a UI layer only — no data logic of its own.
3. `creator.js` \(recipes plugin\) replaces direct `algoliaIndex.search()` calls with provider calls.
4. Normalization lives in one place; both surfaces use the same product shape.
5. A new surface needs only `G4TAdsProvider.getInstance().search(query)` to get results.

---

## Architecture

### Stage 1: `G4TAdsProvider` module

New file: `core/provider/algolia-provider.js`

```
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

```
wp_localize_script('g4t-ads-provider', 'G4T_ADS_PROVIDER_DATA', [
    'appId'     => $options['algolia_app_id'],
    'apiKey'    => $options['algolia_search_api_key'],
    'indexName' => $options['algolia_index_name'] ?? 'go4taste_product_list',
]);
```

---

### Stage 2: Refactor `picker-ui.js`

* Remove `window.instantsearch` dependency entirely.
* Remove jQuery dependency from data path \(keep only for DOM manipulation if needed, or drop jQuery completely\).
* Replace `search.addWidget(hits(...))` with `G4TAdsProvider.getInstance().search(query)` \+ own rendering.
* Replace `index.getObject(id)` with `G4TAdsProvider.getInstance().getById(id)`.
* `picker-ui.js` becomes UI-only: search input listener → provider → render hits → selection state.

InstantSearch.js script tag can be removed from the enqueue list once no widget uses it.

---

### Stage 3: Surface adapters

Each existing admin surface \(metabox, term edit, global settings\) that currently initializes its own Algolia client gets updated to call `G4TAdsProvider.getInstance()` instead. No logic changes to save/read paths — only the data fetch layer changes.

---

### Stage 4: Docs \+ QA

* Document `G4TAdsProvider` public API \(search, getById, getByIds\).
* QA matrix: metabox picker, term edit picker, global settings picker, recipe creator — all must search and select correctly after refactor.
* Confirm single client instance: no duplicate Algolia calls on pages with multiple surfaces.

---

## Suggested public JS API

```
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

**Cause:** `algolia-provider.js` originally used `document.addEventListener('DOMContentLoaded', ...)` to init the provider. Inline scripts in admin templates \(body HTML\) register their `DOMContentLoaded` listeners _before_ footer scripts execute — so the picker's listener fired before the provider had initialized. `getInstance()` returned `null`.

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

8. Visual QA of admin picker inputs \(inline style vs theme-native input styling\).
9. Recipes plugin follow-up PR \(`feature/use-ads-data-provider`\) — merge after this PR.

---

## Risks and mitigations

* Risk: InstantSearch.js removal breaks existing search UX in admin pickers. Mitigation: Stage 2 replaces widgets one-to-one; input debounce, loading state, and empty state are preserved in custom rendering.
* Risk: duplicate client init if provider `init()` is called more than once. Mitigation: singleton guard — second `init()` call is a no-op.
* Risk: recipes plugin relies on `normalizeProductRecord()` which will not be removed — it stays as internal state normalizer; provider hits pass through it since it handles both provider and legacy field names. Mitigation: no action needed — both shapes are handled by existing `normalizeProductRecord()` logic.

---

## Dependencies

* **go4taste-recipes-plugin** `feature/use-ads-data-provider` — companion PR; removes direct `algoliaIndex.search()` and `window.algoliasearch()` from `creator.js`, uses `G4TAdsProvider` instead. Already implemented and QA-passed alongside this PR.
* PHP foundation \(registry, payload, render, recipe-bottom-strip\) already on main from PR #15 — no changes needed there.

---

## Merge readiness

All stages implemented and QA-passed. Both bugs found during QA diagnosed and fixed. Follow-up item 8 \(input styling\) is cosmetic and does not block merge.

* Unblocks: recipes plugin follow-up PR `feature/use-ads-data-provider`.

‌
