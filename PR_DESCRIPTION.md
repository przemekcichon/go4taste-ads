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

## Wpływ i kompatybilność

- Brak zmian kontraktu danych dla legacy widgetu (meta key pozostaje bez zmian):
  - _g4t_a_sidebar_regular_widget_ps_product_id
- Legacy include paths shimów zostały usunięte po potwierdzeniu braku regresji.
- Funkcjonalność widgetu, metaboxu, syncu, ustawień i admin assets pozostaje zachowana, ale logika i hooki zostały przeniesione do vertical slices.

## Następne kroki (po tym PR)

- Dalsza migracja kolejnych feature slices (bez legacy-shortcode-migration, bo flow został wygaszony po jednorazowym użyciu).
- Stopniowe wygaszanie legacy orchestratora po wydzieleniu pozostałych obszarów.
