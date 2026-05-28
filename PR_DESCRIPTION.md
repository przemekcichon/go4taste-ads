# PR: Follow-up docs alignment after VSA merge

**Branch:** chore/vsa-postmerge-pr-description -> main

## Motywacja

Po merge PR #2 opis architektury w dokumentacji byl czesciowo niespojny z aktualnym stanem repo.
Ten PR domyka dokumentacje po migracji VSA i aktualizuje guidance dla agenta developerskiego.

## Zakres zmian

### 1. README: aktualizacja do realnej struktury pluginu

Zaktualizowano [README.md](README.md), aby odzwierciedlal aktualny runtime po migracji VSA:

- sekcja "Current Directory Structure" zamiast "Target Directory Structure"
- dodane `core/lifecycle`
- poprawione wpisy dla feature slices (`index.php`, `assets/`, `helpers.php`, `payload-builder.php`)
- usuniete nieaktualne odniesienia do nieistniejacych elementow (np. `legacy-shortcode-migration`, `metabox-ui.js`, `metabox-style.css`, `options-registration.php`)
- doprecyzowane hard constraints pod etap utrzymania po migracji

### 2. Agent developer: aktualizacja kontekstu po migracji

Zaktualizowano [.github/agents/go4taste-plugin-developer.agent.md](.github/agents/go4taste-plugin-developer.agent.md):

- mission statement: z "refactor legacy plugin" na utrzymanie i rozwoj w gotowym VSA
- kontekst: usunieto guidance sugerujacy standardowa prace na `admin/public/includes`
- doprecyzowano, ze nie nalezy reintrodukowac poziomych runtime layers

## Weryfikacja

- Zmiany obejmuja tylko dokumentacje (`*.md`)
- Brak zmian w kodzie runtime pluginu
- Sprawdzono, ze diff wzgledem `main` obejmuje tylko:
  - [README.md](README.md)
  - [.github/agents/go4taste-plugin-developer.agent.md](.github/agents/go4taste-plugin-developer.agent.md)

## Wplyw i kompatybilnosc

- Brak wplywu na runtime, API i dane
- Zmiany poprawiaja onboarding i zmniejszaja ryzyko blednych refaktorow wynikajacych z nieaktualnej dokumentacji

## Release readiness

- READY FOR MERGE (docs-only follow-up)
