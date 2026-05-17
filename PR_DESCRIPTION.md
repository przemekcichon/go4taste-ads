# PR: Configurable max products, theme template override, render callback, default cache

**Branch:** `feature/theme-banner-templates` → `main`

## Motywacja

Blok `go4taste-ads/inline-banner` miał hardcodowany limit 3 produktów oraz brak mechanizmu pozwalającego motywowi podmienić szablon renderowania. Każde wczytanie strony z blokiem generowało synchroniczne żądania HTTP do PrestaShop API bez cachowania, co powodowało znaczny spadek wydajności.

## Zakres zmian

### `includes/class-go4taste-ads.php`
- Dodano `render_inline_banner_block()` jako server-side render callback rejestrowany przez `register_block_type_from_metadata()`.
- Callback szuka szablonu w kolejności:
  1. `{theme}/go4taste-ads/inline-banner.php`
  2. `{theme}/go4taste-ads-inline-banner.php`
  3. Fallback do `build/blocks/inline-banner/render.php`
  4. Filterowalny path przez `g4t_ads_inline_banner_template_path`
- `maxProducts` lokalizowane przez `wp_localize_script()` z filtrem `g4t_ads_inline_banner_max_products` (domyślnie `null` = bez limitu).

### `includes/class-go4taste-ads-api-fetcher.php`
- Domyślna wartość `cache_duration` zmieniona z `0` (wyłączony) na `24` godziny.
- Eliminuje N synchronicznych HTTP requestów do PrestaShop przy każdym renderze strony.

### `src/blocks/inline-banner/edit.js`
- Usunięto hardcodowany limit 3 produktów.
- `getMaxProducts()` odczytuje wartość z `window.g4tAdsBlockData.maxProducts`.
- Gdy `maxProducts` jest `null` lub `0` — brak limitu.

### `src/blocks/inline-banner/block.json`
- Zaktualizowano opis bloku (usunięto wzmianekę o „1–3 produktach").

### `src/blocks/inline-banner/render.php` + `build/blocks/inline-banner/*`
- Zaktualizowany render fallback zgodny z nowym API renderowania.
- Zsynchronizowane artefakty buildowe.

## Weryfikacja

- Dodanie >3 produktów w edytorze działa poprawnie.
- Po ustawieniu `cache_duration > 0` strona z 4 produktami ładuje się bez opóźnienia przy kolejnych wejściach.
- Theme override (`go4taste-ads/inline-banner.php` w motywie) jest wykrywany i używany zamiast fallbacku.

## Brak breaking changes

- Bloki bez wybranych produktów renderują pusty string (bez zmian).
- Istniejące posty z ≤3 produktami działają bez modyfikacji.
- `cache_duration` ustawione ręcznie w opcjach pluginu ma pierwszeństwo przed domyślnym `24h`.
