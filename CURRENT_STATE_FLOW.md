# Audyt: Przepływ danych produktów, cache transient, integracja Algolia (stan na 28.05.2026)

---

## Przepływ danych dla frontendu (PrestaShop)

### Pobieranie danych produktu do wyświetlenia

#### Inline Banner Block (Gutenberg)

* Plik renderujący blok:  
  `render.php`  
* Proces:  
  1. Odczyt ID produktu (z atrybutów bloku lub kontekstu).  
  2. Wywołanie funkcji pobierającej dane produktu:  
     `g4t_core_fetch_product_data( $product_id )` (zdefiniowana w product-data.php).  
  3. Funkcja ta korzysta z cache (transient) lub pobiera dane z API PrestaShop.  
  4. Wynik przekazywany do szablonu renderującego HTML bloku.

#### Sidebar Banner Widget

* Plik widgetu:  
  `index.php` 
* Proces:  
  1. Odczyt ID produktu (z ustawień widgetu/metaboxu).  
  2. Wywołanie `g4t_core_fetch_product_data( $product_id )`.  
  3. Analogiczny mechanizm cache/API jak w bloku.  
  4. Render HTML widgetu z danymi produktu.

#### Metabox Preview (Admin)

* Plik AJAX preview:  
  `features/metabox-banner-sidebar-widget/ajax-preview.php`  
  (lub analogiczny handler w `index.php`)  
* Proces:  
  1. AJAX żądanie z ID produktu.  
  2. Wywołanie `g4t_core_fetch_product_data( $product_id )`.  
  3. Zwrócenie HTML z podglądem produktu.

---

## Mechanizm Transient Cache

### Lokalizacja kodu obsługującego transienty

* Plik:  
  `product-data.php`  
* Funkcje:  
  * `g4t_core_fetch_product_data( $product_id )`  
    * Odczyt:  
      `get_transient( "g4t_product_{$product_id}" )`  
    * Zapis:  
      `set_transient( "g4t_product_{$product_id}", $data, $ttl )` 
    * Usuwanie:  
      `delete_transient( "g4t_product_{$product_id}" )` (np. przy synchronizacji)

### Klucze i TTL

* Klucz:  
  `g4t_product_{$product_id}`
* Czas życia (TTL):  
  * Domyślnie 1 godzina (`$ttl = HOUR_IN_SECONDS`), może być nadpisywane w kodzie.  
* Zakres cache:  
  * Każdy produkt cache’owany osobno.  
  * Cache dotyczy zarówno frontend (blok/widget), jak i AJAX preview w adminie.

---

## Obecna integracja z Algolią

### Inicjalizacja klienta Algolii

* Plik:  
  `features/algolia-product-sync/algolia-client.php`  
  (lub bezpośrednio w `sync-ajax-handlers.php`)  
* Funkcja:  
  * `g4t_algolia_get_client()`  
    * Tworzy instancję klienta Algolia z kluczem API i AppID z opcji pluginu.

### Synchronizacja (push) danych do Algolii

* Plik:  
  `sync-ajax-handlers.php`  
* Proces:  
  1. AJAX: `wp_ajax_g4t_sync_start`, `wp_ajax_g4t_sync_batch`, `wp_ajax_g4t_sync_status`, `wp_ajax_g4t_sync_unlock`.  
  2. Funkcje pobierają dane produktów (z PrestaShop lub cache).  
  3. Dane są formatowane i wysyłane do Algolii przez klienta (`$client->initIndex()->saveObjects()`).

### Wyszukiwanie (admin)

* Plik:  
  `features/algolia-product-sync/admin-search.php`  
  (lub fragmenty w `settings-page.php`)  
* Proces:  
  1. AJAX/REST do wyszukiwania produktów w Algolii (np. do podpowiedzi w polu wyboru produktu).  
  2. Użycie klienta Algolia: `$client->initIndex()->search($query)`.

---

## Zależności API (PrestaShop)

### Główny plik połączenia z API

* Plik:  
  `class-go4taste-ads-api-fetcher.php` 
* Klasa:  
  `Go4Taste_Ads_Api_Fetcher`  
  * Odpowiada za autoryzację, budowanie zapytań, obsługę endpointów PrestaShop.

### Konsumenci fetchera

* Funkcja proxy:  
  `g4t_core_get_api_fetcher()` (w `product-data.php`)  
* Funkcje korzystające:  
  * `g4t_core_fetch_product_data()` (główny entrypoint dla pobierania produktu)  
  * `sync-ajax-handlers.php` (masowy fetch do synchronizacji)  
  * `settings-page.php` (test połączenia API)  
  * Potencjalnie inne feature slices korzystające z danych produktów

---

## Podsumowanie (stan na 28.05.2026)

* Przepływ danych produktu: Zcentralizowany w `g4t_core_fetch_product_data()`, wywoływany przez blok, widget, AJAX preview.  
* Cache transient: Klucz per produkt, TTL 1h, obsługa w `product-data.php`.  
* Algolia: Klient inicjalizowany w feature slice, push i search rozdzielone na osobne pliki, całość w algolia-product-sync.  
* API PrestaShop: Fetcher w `class-go4taste-ads-api-fetcher.php`, konsumowany przez proxy i funkcje feature slices.

Stan: Przepływ danych jest czytelny, cache działa per produkt, integracja Algolia rozdzielona na sync/search, a fetcher API PrestaShop jest jednopunktowy i wykorzystywany przez wszystkie kluczowe funkcje.

