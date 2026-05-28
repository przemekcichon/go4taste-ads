# PR: Rename feature slices to banner-inline-block and banner-sidebar-widget

**Branch:** chore/rename-banner-slices -> main

## Motywacja

Ujednolicamy nazewnictwo feature slices i odchodzimy od nazwy `legacy-sidebar-widget`.
Zakres jest celowo kosmetyczny: rename sciezek i referencji bez zmiany publicznego kontraktu bloku oraz bez zmian zachowania runtime.

## TL;DR dla reviewera

- To jest rename-only PR dla nazw slice'ow (`inline-banner` i `sidebar widget`) + aktualizacja referencji.
- Nie ma zmian logiki biznesowej ani kontraktu bloku `go4taste-ads/inline-banner`.
- Build i lint sa zielone; stare katalogi po rename nie odtworzyly sie.

## Zakres zmian

### 1. Rename folderow feature slices (zachowanie historii przez `git mv`)

- `features/inline-banner-block` -> `features/banner-inline-block`
- `features/legacy-sidebar-widget` -> `features/banner-sidebar-widget`

### 2. Aktualizacja runtime referencji po rename

- `core/bootstrap.php` laduje teraz:
  - `features/banner-inline-block/index.php`
  - `features/banner-sidebar-widget/index.php`
- `features/banner-sidebar-widget/index.php`:
  - nowe callbacki `g4t_banner_sidebar_widget_*`
  - poprawiona sciezka assetu do `features/banner-sidebar-widget/assets/css/go4taste-ads-sidebar-widget.css`

### 3. Aktualizacja build pipeline

- `package.json`:
  - `npm run build` i `npm run start` wskazuja `features/banner-inline-block/src` oraz `features/banner-inline-block/build`

### 4. Usuniecie nazewnictwa legacy w widget feature

- `features/banner-sidebar-widget/widget-class.php`:
  - komentarz klasy bez "Legacy"
  - label widgetu zmieniony na `G4T Ads Sidebar Banner Widget`

### 5. Aktualizacja dokumentacji i komentarzy technicznych

- `README.md`
- `CURRENT_STATE.md`
- `VSA_FULL_MIGRATION_PLAN_NEXT.md`
- `features/banner-inline-block/{index.php,render.php,helpers.php}` (`@subpackage`)
- `features/ga4-tracking/index.php` (komentarz cross-feature)

## Weryfikacja (bramki ryzyka)

- Build: `npm run build` -> **OK**
- PHP lint (caly plugin) -> **OK**
- Safety grep: brak wystapien `inline-banner-block` i `legacy-sidebar-widget` w runtime/docs operacyjnych (pozostaly tylko kontrolowane wzmianki historyczne w tym PR opisie) -> **OK**
- Artefakty: brak odtworzonych katalogow:
  - `features/inline-banner-block`
  - `features/legacy-sidebar-widget`
  - root `build/`
  - root `src/`

## Wplyw i kompatybilnosc

- Brak zmiany publicznego kontraktu bloku (`go4taste-ads/inline-banner` pozostaje bez zmian)
- Brak zmian danych i API
- Zmiana dotyczy nazewnictwa feature slices, sciezek i dokumentacji

## Release readiness

- READY FOR MERGE
