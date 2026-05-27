# CURRENT_STATE

Surowy audyt aktualnej architektury pluginu go4taste-ads. Bez propozycji zmian VSA i bez refaktoru - tylko stan obecny.

## 1. Detailed ASCII Directory Tree

Uwaga: drzewo jest kompletne dla kodu pluginu. Bardzo glebokie struktury zaleznosci zewnetrznych (`vendor`, `.git`) sa pokazane do poziomu wystarczajacego dla audytu architektury.

```text
go4taste-ads/
|-- .gitignore
|-- go4taste-ads.php
|-- index.php
|-- LICENSE.txt
|-- package.json
|-- package-lock.json
|-- PR_DESCRIPTION.md
|-- README.md
|-- README.txt
|-- uninstall.php
|
|-- .github/
|   |-- copilot-instructions.md
|   |-- agents/
|   |   |-- go4taste-api-security-reviewer.agent.md
|   |   |-- go4taste-plugin-auditor.agent.md
|   |   |-- go4taste-plugin-cleanup.agent.md
|   |   |-- go4taste-plugin-developer.agent.md
|   |   |-- go4taste-plugin-reviewer.agent.md
|   |   |-- go4taste-prestashop-native-api.agent.md
|   |   `-- go4taste-security-hardening.agent.md
|   |-- instructions/
|   |   |-- prestashop-api-price-url-mapping.instructions.md
|   |   `-- review-first-findings.instructions.md
|   |-- plugin/
|   |   `-- plugin.json
|   `-- skills/
|       |-- wordpress-router/
|       |   |-- SKILL.md
|       |   `-- references/
|       |-- wp-abilities-api/
|       |   |-- SKILL.md
|       |   `-- references/
|       |-- wp-block-development/
|       |   |-- SKILL.md
|       |   |-- references/
|       |   `-- scripts/
|       |-- wp-block-themes/
|       |   |-- SKILL.md
|       |   |-- references/
|       |   `-- scripts/
|       |-- wp-interactivity-api/
|       |   |-- SKILL.md
|       |   `-- references/
|       |-- wp-performance/
|       |   |-- SKILL.md
|       |   |-- references/
|       |   `-- scripts/
|       |-- wp-phpstan/
|       |   |-- SKILL.md
|       |   |-- references/
|       |   `-- scripts/
|       |-- wp-playground/
|       |   |-- SKILL.md
|       |   `-- references/
|       |-- wp-plugin-development/
|       |   |-- SKILL.md
|       |   |-- references/
|       |   `-- scripts/
|       |-- wp-project-triage/
|       |   |-- SKILL.md
|       |   |-- references/
|       |   `-- scripts/
|       |-- wp-rest-api/
|       |   |-- SKILL.md
|       |   `-- references/
|       |-- wp-wpcli-and-ops/
|       |   |-- SKILL.md
|       |   |-- references/
|       |   `-- scripts/
|       `-- wpds/
|           `-- SKILL.md
|
|-- admin/
|   |-- class-go4taste-ads-admin.php
|   |-- class-go4taste-ads-sidebar-regular-widget.php
|   |-- index.php
|   |-- css/
|   |   `-- go4taste-ads-admin.css
|   `-- js/
|       `-- go4taste-ads-admin.js
|
|-- build/
|   `-- blocks/
|       `-- inline-banner/
|           |-- block.json
|           |-- index.asset.php
|           |-- index.css
|           |-- index.js
|           |-- render.php
|           |-- style-index.css
|           |-- view.asset.php
|           `-- view.js
|
|-- includes/
|   |-- class-go4taste-ads-activator.php
|   |-- class-go4taste-ads-api-fetcher.php
|   |-- class-go4taste-ads-deactivator.php
|   |-- class-go4taste-ads-i18n.php
|   |-- class-go4taste-ads-loader.php
|   |-- class-go4taste-ads-wp-cli-command.php
|   |-- class-go4taste-ads.php
|   `-- index.php
|
|-- languages/
|   `-- go4taste-ads.pot
|
|-- public/
|   |-- class-go4taste-ads-public.php
|   |-- index.php
|   |-- css/
|   |   `-- go4taste-ads-public.css
|   `-- partials/
|       `-- go4taste-ads-sidebar-regular-widget-public-display.php
|
|-- src/
|   `-- blocks/
|       `-- inline-banner/
|           |-- block.json
|           |-- edit.js
|           |-- editor.css
|           |-- index.js
|           |-- render.php
|           |-- style.css
|           |-- view.js
|           `-- components/
|               |-- AlgoliaProductSearch.js
|               `-- ProductPreview.js
|
|-- vendor/
|   |-- autoload.php
|   |-- bin/
|   |   |-- algolia-doctor
|   |   `-- algolia-doctor.bat
|   |-- composer/
|   |   |-- ClassLoader.php
|   |   |-- InstalledVersions.php
|   |   |-- autoload_classmap.php
|   |   |-- autoload_files.php
|   |   |-- autoload_namespaces.php
|   |   |-- autoload_psr4.php
|   |   |-- autoload_real.php
|   |   |-- autoload_static.php
|   |   |-- installed.json
|   |   |-- installed.php
|   |   |-- LICENSE
|   |   `-- platform_check.php
|   |-- algolia/
|   |   `-- algoliasearch-client-php/
|   |       |-- autoload.php
|   |       |-- src/
|   |       |-- bin/
|   |       |-- LICENSE
|   |       `-- SECURITY.md
|   `-- psr/
|       |-- http-message/
|       |   |-- src/
|       |   |-- docs/
|       |   |-- README.md
|       |   `-- LICENSE
|       |-- log/
|       |   |-- Psr/
|       |   `-- README.md
|       `-- simple-cache/
|           |-- src/
|           `-- README.md
|
`-- .git/
		|-- HEAD
		|-- config
		|-- index
		|-- packed-refs
		|-- refs/
		|-- logs/
		|-- objects/
		`-- hooks/
```

### Tests and QA assets (actual state)

- Brak plugin-owned test suite (`tests`, `__tests__`, `phpunit.xml`, `jest.config`, `playwright.config` - nie znaleziono).
- Jedyne pliki testowe pochodza z zaleznosci (`vendor/psr/log/.../Test/*`) i nie testuja pluginu.

## 2. Core Business Features Actually Delivered

To, co plugin realnie daje dzisiaj:

1. **Inline Banner Gutenberg block** (`go4taste-ads/inline-banner`)
- Wybieranie i wyswietlanie produktow inline w tresci.
- Atrybuty layoutu mobilnego (`column`, `carousel`, `single`) i stylowania kart/przyciskow.
- SSR render callback z mozliwoscia override szablonu przez motyw.

2. **Sidebar product widget (legacy)**
- Klasyczny `WP_Widget` dla promowania produktu w sidebarze.
- Widget jest oznaczony jako przestarzaly i sugeruje uzycie bloku.

3. **Post-level promoted product meta box**
- Metabox na `post`, wybor produktu przez Algolia search.
- Zapis product ID do post meta.

4. **Admin settings page**
- Konfiguracja zrodla API (PrestaShop/WooCommerce), URL-i, kluczy, jezyka, fallback product ID.
- Konfiguracja Algolia (`app_id`, `api_key`) oraz cache/GA4 toggle.

5. **PrestaShop/WooCommerce API integration + Algolia sync**
- Pobieranie danych produktow z API.
- Batch sync produktow do indeksu Algolia.

6. **Legacy migration tools**
- Migracja starych shortcode bannerow (`prestashop_*`) do bloku inline-banner.
- Tryb dry-run, batch processing, status/progress przez AJAX.

7. **WP-CLI utility**
- Komenda do aktualizacji meta product ID z shortcode (`wp go4taste update_product_ids`).

8. **GA4 dataLayer event for promotion views**
- Opcjonalne `view_promotion` push podczas renderu inline-banner.

## 3. Architectural Breakdown (PHP, JS/CSS, Tests)

### PHP logic location

- **Bootstrap + lifecycle**: `go4taste-ads.php`
- **Core orchestrator + hook wiring**: `includes/class-go4taste-ads.php`
- **Hook loader abstraction**: `includes/class-go4taste-ads-loader.php`
- **Admin runtime (UI + AJAX + migration + sync + settings + metabox)**: `admin/class-go4taste-ads-admin.php`
- **Public runtime**: `public/class-go4taste-ads-public.php`
- **Data/API integration**: `includes/class-go4taste-ads-api-fetcher.php`
- **WP-CLI command**: `includes/class-go4taste-ads-wp-cli-command.php`
- **Widget**: `admin/class-go4taste-ads-sidebar-regular-widget.php`

### JS/CSS assets location

- **Admin CSS/JS**: `admin/css/go4taste-ads-admin.css`, `admin/js/go4taste-ads-admin.js`
- **Public CSS**: `public/css/go4taste-ads-public.css`
- **Block source assets**: `src/blocks/inline-banner/*`
- **Compiled block assets**: `build/blocks/inline-banner/*`

### Tests location

- **Brak testow pluginu** (PHP unit/integration i JS unit/e2e).

### Co-location vs horizontal layers

**Wniosek: architektura jest horyzontalna (technical-layer based), nie feature-sliced.**

- Rozklad katalogow: `admin`, `public`, `includes`, `src`, `build`.
- Feature'y biznesowe (migracja, sync, metabox, settings, preview) sa upchniete glownie w jednej klasie admin.
- Block ma osobny pipeline (`src` -> `build`), ale reszta pluginu nie jest organizowana per feature slice.

## 4. Hook Strategy

### Jak hooki sa rejestrowane

Hook strategy jest **scentralizowana**.

- Entry point: `go4taste-ads.php` wywoluje `Go4Taste_Ads::get_instance()->run()`.
- `Go4Taste_Ads` buduje zestawy hookow w metodach:
	- `define_admin_hooks()`
	- `define_public_hooks()`
	- `define_block_hooks()`
- Rejestracje trafiaja do `Go4Taste_Ads_Loader` (`add_action`, `add_filter`).
- Finalne `add_action`/`add_filter` odpala dopiero `Go4Taste_Ads_Loader::run()`.

### Czy jest central orchestrator czy scattering

- **Tak, jest central orchestrator**: `includes/class-go4taste-ads.php`.
- **Nie ma istotnego scattering hookow po wielu plikach** - wiring siedzi centralnie.
- Logika callbackow jest jednak ciezka i skondensowana glownie w klasie admin/API.

### Najwazniejsze zarejestrowane hook groups

- `plugins_loaded` (i18n, helper init)
- `admin_enqueue_scripts`, `wp_enqueue_scripts`
- `add_meta_boxes`, `save_post`
- `widgets_init`
- `admin_menu`, `admin_init`
- wiele `wp_ajax_*` (preview, migration, sync, permissions)
- `init` (register blocks)
- `wp_theme_json_data_theme` (border settings)

## 5. Data Strategy (CPT, Taxonomies, Custom Fields)

### CPT

- **Brak** `register_post_type()` w plugin-owned kodzie.

### Taxonomies

- **Brak** `register_taxonomy()` w plugin-owned kodzie.

### Custom Fields / ACF

- **Brak ACF registrations** (`acf_add_local_field_group()` nie wystepuje).
- Plugin używa klasycznego **meta box + post meta**.

### Co plugin rejestruje i jak przechowuje dane

1. **Meta box** na `post` + zapis product ID do post meta:
- `_g4t_a_sidebar_regular_widget_ps_product_id`

2. **Settings API option bucket**:
- `go4taste_ads_options` (serialized array)

3. **Transient-based async job state**:
- migration i sync status/progress/locks w transientach

4. **External data sources**:
- PrestaShop API
- WooCommerce API
- Algolia index jako storage/search layer dla product lookup

5. **Brak plugin-owned DB schema**:
- Brak custom tables, brak migration layer dla schematu danych.

## Brutally Honest Snapshot

- Architektura jest uporzadkowana pod katem bootstrapa i hook wiringu, ale nadal klasycznie horyzontalna.
- Runtime biznesowy jest skoncentrowany glownie w 2 duzych klasach (`Admin`, `API_Fetcher`).
- Block jest nowoczesny (block.json + src/build), ale reszta pluginu ma starszy, proceduralno-adminowy charakter.
- Brak testow pluginu to najwieksza luka techniczna w obecnym stanie.

