# PR: VSA extraction of legacy sidebar widget (feature slice)

**Branch:** chore/vertical-slices-architecture → main

## Motywacja

Rozpoczynamy migrację pluginu do True Vertical Slices Architecture zgodnie z nowym README i guardrails.
Pierwszym wydzielonym obszarem jest legacy sidebar widget, który wcześniej był rozproszony po warstwach admin/public/includes i zależał od centralnego orchestratora.

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

### 6. Zachowanie kompatybilności ścieżek include

Zostawiono shimy kompatybilności dla starej struktury:

- admin/class-go4taste-ads-sidebar-regular-widget.php -> require feature class
- public/partials/go4taste-ads-sidebar-regular-widget-public-display.php -> require feature template

## Weryfikacja

- Brak błędów statycznych/kompilacyjnych w zmienionych plikach.
- widgets_init jest rejestrowany tylko w feature slice.
- W kodzie slice legacy-sidebar-widget nie występuje Go4Taste_Ads::get_instance().
- Hook kompatybilności g4taste_product_ads_sidebar_regular_widget_product_id_function_loaded jest emitowany z feature slice.

## Wpływ i kompatybilność

- Brak zmian kontraktu danych dla legacy widgetu (meta key pozostaje bez zmian):
  - _g4t_a_sidebar_regular_widget_ps_product_id
- Zachowano legacy include paths przez shimy.
- Funkcjonalność widgetu pozostaje zachowana, ale logika i hooki zostały przeniesione do vertical slice.

## Następne kroki (po tym PR)

- Ręczny smoke test na środowisku zdalnym (widgets admin + frontend render).
- Po potwierdzeniu braku regresji: usunięcie shimów kompatybilności i dalsza migracja kolejnych feature slices.
