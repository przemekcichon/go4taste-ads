# PR: Rename feature slices to algolia-product-sync and metabox-banner-sidebar-widget

**Branch:** chore/rename-sync-metabox-slices -> main

## Motywacja

Ujednolicamy nazewnictwo feature slices dla sync i metabox, aby lepiej oddawalo aktualna odpowiedzialnosc modulow.
Zakres jest celowo kosmetyczny: rename sciezek i referencji bez zmiany publicznego kontraktu bloku oraz bez zmian zachowania runtime.

## TL;DR dla reviewera

- To jest rename-only PR dla nazw slice'ow (`product-catalog-sync` i `promoted-product-metabox`) + aktualizacja referencji.
- Nie ma zmian logiki biznesowej, kontraktow AJAX ani danych.
- Build i lint sa zielone; stare katalogi po rename nie odtworzyly sie.

## Zakres zmian

### 1. Rename folderow feature slices (zachowanie historii przez `git mv`)

- `features/product-catalog-sync` -> `features/algolia-product-sync`
- `features/promoted-product-metabox` -> `features/metabox-banner-sidebar-widget`

### 2. Aktualizacja runtime referencji po rename

- `core/bootstrap.php` laduje teraz:
  - `features/algolia-product-sync/index.php`
  - `features/metabox-banner-sidebar-widget/index.php`
- `features/metabox-banner-sidebar-widget/metabox-registration.php`:
  - poprawiona sciezka assetu do `features/metabox-banner-sidebar-widget/assets/css/go4taste-ads-metabox-preview.css`

### 3. Aktualizacja dokumentacji i guidance

- `README.md`
- `CURRENT_STATE.md`
- `.github/agents/go4taste-plugin-developer.agent.md`

## Weryfikacja (bramki ryzyka)

- Build: `npm run build` -> **OK**
- PHP lint (caly plugin) -> **OK**
- Safety grep: brak wystapien `product-catalog-sync` i `promoted-product-metabox` w runtime/docs operacyjnych (pozostaly tylko kontrolowane wzmianki historyczne w tym PR opisie) -> **OK**
- Artefakty: brak odtworzonych katalogow:
  - `features/product-catalog-sync`
  - `features/promoted-product-metabox`
  - root `build/`
  - root `src/`

## Wplyw i kompatybilnosc

- Brak zmian kontraktow AJAX (`g4t_sync_*`, `g4t_fetch_product_preview`) i meta key
- Brak zmian danych i API
- Zmiana dotyczy nazewnictwa feature slices, sciezek i dokumentacji

## Release readiness

- READY FOR MERGE
