# PR: Inline banner hotfix for publish/update and legacy theme overrides

**Branch:** hotfix/inline-banner-algolia-client-path -> main

## Motywacja

Po wdrozeniu `algolia-ad-server` pojawily sie dwa krytyczne problemy zwiazane z inline bannerem:

1. Publish/update postow w edytorze Gutenberg konczyl sie bledem `Publishing failed` / `Updating failed`.
2. Na srodowisku zdalnym render inline bannera potrafil wywalic fatala przez brakujacy plik
   `core/api-client/algolia-client.php` albo przez stary motywowy override korzystajacy z
   `Go4Taste_Ads::get_instance()`.

Ten PR domyka oba problemy tak, aby update samej wtyczki byl bezpieczny rowniez wtedy,
gdy na serwerze nadal jest stary override szablonu w motywie.

## TL;DR dla reviewera

- Usuniety fatal include do nieistniejacego `algolia-client.php`.
- `algolia-ad-server` tworzy klienta Algolia bezposrednio z opcji pluginu i czyta z poprawnego indeksu `go4taste_product_list`.
- Rekord z Algolii jest normalizowany do ksztaltu zgodnego z renderem inline bannera.
- Dodany shim kompatybilnosci `Go4Taste_Ads`, zeby stare motywowe override'y nie wywalaly strony po samym update pluginu.

## Zakres zmian

### 1) Naprawa `algolia-ad-server` dla inline bannera

Plik:
- `features/algolia-ad-server/product-data.php`

Zmiany:
- usuniety `require_once` do nieistniejacego `core/api-client/algolia-client.php`
- klient Algolia tworzony bezposrednio przez `Algolia\AlgoliaSearch\SearchClient::create(...)`
- odczyt credentials z opcji pluginu (`algolia_app_id`, `algolia_api_key`)
- odczyt z indeksu `go4taste_product_list`
- normalizacja payloadu do obiektu kompatybilnego z template renderujacym banner
- fallback do stale cache bez fatala przy problemach z Algolia

### 2) Shim kompatybilnosci dla starego motywu

Pliki:
- `core/compat/legacy-go4taste-ads-class.php`
- `core/bootstrap.php`

Zmiany:
- dodana klasa kompatybilnosci `Go4Taste_Ads`
- wspierane legacy wywolanie:
  - `Go4Taste_Ads::get_instance()->fetch_product_data(...)`
- shim mapuje stare wywolania na aktualny flow:
  - `go4taste_ads_get_product_data`
  - fallback `g4t_core_fetch_product_data(...)`
- shim ladowany podczas bootstrapa pluginu

## Root cause

Problem nie byl w samym edytorze Gutenberg ani w REST API.
Root-cause byl w renderze inline bannera podczas preload/autosave:

1. render bloku wywolywal provider danych produktu,
2. provider ladowal nieistniejacy plik lub trafial na stary override motywu,
3. PHP fatal powodowal 500 na endpointach autosave / update,
4. edytor pokazywal `Publishing failed` / `Updating failed`.

## Weryfikacja

- `php -l features/algolia-ad-server/product-data.php` -> OK
- `php -l features/algolia-ad-server/algolia-ad-server.php` -> OK
- `php -l core/compat/legacy-go4taste-ads-class.php` -> OK
- `php -l core/bootstrap.php` -> OK
- brak dalszych referencji do:
  - `core/api-client/algolia-client.php`
  - `g4t_get_algolia_client`

## Ryzyko / kompatybilnosc

- Ryzyko niskie/srednie: zmiana dotyczy hotfixa w warstwie pobierania danych i backward compatibility.
- Kompatybilnosc:
  - nowy flow inline bannera dziala bez starej klasy runtime,
  - stary motywowy override nadal dziala po update pluginu dzieki shimowi,
  - fix jest zaprojektowany specjalnie pod bezpieczny deploy przez WP Pusher bez dostepu SSH.

## Checklist

- [x] Usuniety fatal include do nieistniejacego `algolia-client.php`
- [x] Klient Algolia tworzony bezposrednio z opcji pluginu
- [x] Uzyty poprawny indeks `go4taste_product_list`
- [x] Payload produktu normalizowany do ksztaltu oczekiwanego przez render inline bannera
- [x] Dodany shim `Go4Taste_Ads` dla starych override'ow motywu
- [x] Shim ladowany w bootstrapie pluginu
