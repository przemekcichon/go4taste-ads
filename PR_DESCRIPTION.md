# PR: Algolia ad server + soft cache + daily sync cron + Admin API key

**Branch:** main -> main

## Motywacja

Obecny przeplyw pobierania danych produktowych byl oparty glownie o bezposredni fetch,
co utrudnialo centralne sterowanie zrodlem danych i fallbackiem cache.

Jednoczesnie synchronizacja do Algolii (zapis) wymagala klucza z uprawnieniami write,
a konfiguracja admina miala tylko pole Search-Only API Key.

Cel tej zmiany:
1. Wprowadzic centralny punkt pobierania danych produktowych przez filtr.
2. Dodac soft-cache dla danych z Algolii (stale fallback przy problemach z fetch).
3. Dodac dzienny cron sync Algolia z batch processingiem.
4. Rozdzielic klucze Algolia (search vs admin/write) na poziomie ustawien i zapisu.

## TL;DR dla reviewera

- Dodany nowy slice: `features/algolia-ad-server/` (filter provider + soft cache + fetch z Algolii).
- Konsumenci runtime (`banner-inline-block`, `metabox preview`) przechodza na wspolny filtr `go4taste_ads_get_product_data`.
- Sync do Algolii korzysta z nowego `algolia_admin_api_key` (zamiast search-only key).
- Dodany dzienny cron sync w `features/algolia-product-sync/cron.php` i zaladowanie go w `index.php`.

## Zakres zmian

### 1) Nowy feature slice: Algolia Ad Server

Pliki:
- `features/algolia-ad-server/algolia-ad-server.php`
- `features/algolia-ad-server/product-data.php`
- `features/algolia-ad-server/soft-cache.php`

Zmiany:
- Rejestracja filtra `go4taste_ads_get_product_data`.
- Pobieranie produktu z Algolii po `product_id`.
- Soft TTL cache:
  - soft expiry: 24h (`soft_expires_at`)
  - hard expiry transientu: 7 dni
- Fallback do stale cache, gdy fetch z Algolii sie nie powiedzie.

### 2) Przelaczenie konsumentow runtime na filtr

Pliki:
- `features/banner-inline-block/build/render.php`
- `features/metabox-banner-sidebar-widget/metabox-registration.php`

Zmiany:
- Zastapienie bezposredniego wywolania `g4t_core_fetch_product_data(...)` przez:
  - `apply_filters( 'go4taste_ads_get_product_data', null, $product_id )`
- Ujednolicenie punktu wejscia do pobierania danych produktowych.

### 3) Algolia sync: rozdzielenie klucza search i admin

Pliki:
- `core/api-client/class-go4taste-ads-api-fetcher.php`
- `features/admin-settings/settings-page.php`

Zmiany:
- `store_products_to_algolia()` uzywa teraz `algolia_admin_api_key`.
- Dodane pole ustawien: `Algolia Admin API Key`.
- Dodana sanitizacja `algolia_admin_api_key`.
- Pole renderowane jako `password` i celowo nie jest prefillowane.

### 4) Daily cron sync dla Algolia Product Sync

Pliki:
- `features/algolia-product-sync/index.php`
- `features/algolia-product-sync/cron.php` (nowy)

Zmiany:
- Zaladowanie `cron.php` w indeksie feature.
- Rejestracja custom schedule `g4t_daily`.
- Event bazowy: `g4t_algolia_daily_sync_event`.
- Batch worker: `g4t_algolia_process_cron_batch`.
- Stan joba przechowywany w transiencie (`g4t_sync_algolia_cron_job`).

### 5) Uporzadkowanie nazw callbackow settings slice

Plik:
- `features/admin-settings/index.php`

Zmiany:
- Zmiana prefiksu funkcji wrapperow hookow z `g4t_admin_settings_*` na `g4ta_admin_settings_*`.

### 6) Dokumentacja przeplywu

Plik:
- `CURRENT_STATE_FLOW.md` (nowy)

Zmiany:
- Audyt aktualnego przeplywu danych produktu, cache transient i integracji Algolia.

## Weryfikacja

- `git diff` potwierdza zmiany w runtime, admin settings i nowych slice'ach Algolia.
- Brak uruchomionych bramek automatycznych w tym kroku (build/lint/test do odpalenia przed merge).

## Ryzyko / kompatybilnosc

- Ryzyko srednie:
  - nowa sciezka pobierania danych przez filtr i nowy provider Algolia,
  - nowy background flow (cron + chaining batchy).
- Kompatybilnosc:
  - kontrakty AJAX sync (`g4t_sync_*`) pozostaja bez zmian,
  - zmieniony jest wymog konfiguracyjny dla zapisu do Algolii (potrzebny `algolia_admin_api_key`).

## Checklist

- [x] Dodany `go4taste_ads_get_product_data` provider oparty o Algolia + soft-cache
- [x] Banner inline + metabox preview korzystaja z filtra
- [x] Zapis do Algolii korzysta z Admin API key
- [x] Dodane pole `Algolia Admin API Key` w ustawieniach
- [x] Dodany daily cron sync z batch processingiem
- [x] Dodany dokument `CURRENT_STATE_FLOW.md`
