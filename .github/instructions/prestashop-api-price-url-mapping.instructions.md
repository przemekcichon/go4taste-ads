---
description: "Global rule for PrestaShop API integration changes: enforce product price rounding and product URL mapping"
applyTo: "**/*.php, **/*.js, **/*.ts, **/*.tsx, **/*.jsx"
---
When implementing or modifying PrestaShop Webservice API integration logic in this repository, enforce these mapping rules.

1. Price mapping rule:
- Source: `product.price` from PrestaShop XML.
- Output: use threshold mapping with decimal-part cutoff at `0.45`.
- Rule: if decimal part is below `0.45`, cut down to one decimal place.
- Rule: if decimal part is `0.45` or above, map to `.50`.
- Examples: `18.42` -> `18.4`, `18.45` -> `18.50`, `18.50013` -> `18.50`.
- For numeric stability, compare using normalized grosz/cents integers (no raw float threshold comparison).

2. Product URL mapping rule:
- Source slug: `product.link_rewrite.language[id=1]`.
- Language fallback: if `id=1` is missing, use the first available `language` value.
- Base URL: use the plugin setting `api_url` (from Product API Settings), not a hardcoded domain.
- Output URL format: `<api_url>/<link_rewrite>.html`.

3. Consistency guardrails:
- Keep these rules consistent across fetchers, mappers, renderers, and admin-triggered migrations.
- If code paths are duplicated, refactor to a shared mapping helper instead of re-implementing logic with variations.
- Any change that touches these mappings must preserve backward compatibility for existing non-PrestaShop API types.
