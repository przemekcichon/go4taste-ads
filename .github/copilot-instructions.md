---
description: Core architectural guidelines, coding standards, and data contracts for the Go4Taste Ads WordPress plugin.
project: Go4Taste Ads
frameworks: [WordPress, React, PHP]
architecture: Vertical Slices Architecture (VSA)
data_format: JSON
---

# Go4Taste Ads - Copilot Instructions

You are assisting with the development of the "Go4Taste Ads" WordPress plugin. You MUST strictly follow the architectural boundaries and data persistence rules outlined below when writing, reviewing, or refactoring code.

## 1. Architectural Pattern: Vertical Slices Architecture (VSA)
- **Concept:** The plugin follows VSA. Features are grouped by business capability (slices), not by technical horizontal layers (MVC).
- **Rule:** Do not create shared `common`, `helpers`, or `utils` directories unless absolutely necessary for the core API boundary (`core/`).
- **Rule:** A slice (e.g., `features/banner-sidebar-widget`) MUST contain all its dependencies: UI registration (Metabox), data processing (`save_post`), frontend rendering (Widget/Block), and assets.
- **Rule:** NEVER use `require` or `include` statements across different feature slices.
- **Naming Convention:** Use `kebab-case` for all folder and file names (e.g., `banner-sidebar-widget`, `widget-class.php`).

## 2. Universal Data Contract
To ensure compatibility across different input methods (Gutenberg, ACF Frontend Forms, Metaboxes, GraphQL), all placements MUST persist selected products using the same standardized format.

### Storage Format: JSON Array of Strings
- **Type:** JSON Encoded Array
- **Value:** Array of Product IDs (Strings, representing Algolia/PrestaShop Object IDs).
- **Example:** `["154", "89"]` or `["12"]`.
- **Empty State:** Empty string `""` or `[]` (if explicitly deleted/empty).

### Data Locations
1. **Posts/Pages/CPTs (e.g., Recipes, Articles):**
   - Stored in `wp_postmeta`.
   - **Key Naming Convention:** `_g4t_a_[placement_name]_product_ids` (e.g., `_g4t_a_sidebar_product_ids`).
2. **Gutenberg Blocks:**
   - Saved as Block Attributes (serialized in `wp_posts.post_content`).
   - **Attribute Name:** `product_ids` (Array of strings). *Note: The legacy `products` attribute containing full objects is deprecated and pending migration.*
3. **Global/Archives (e.g., Global Sidebar, Term Archives):**
   - Stored in `wp_options`.
   - **Option Name Convention:** `g4t_a_[placement_name]_product_ids` (e.g., `g4t_a_archive_sidebar_product_ids`).

## 3. Backward Compatibility & Fallbacks
- When fetching data (e.g., via `get_post_meta`), ALWAYS read the new JSON array key first.
- If the new key is empty or invalid, seamlessly fallback to reading the legacy single-string key (e.g., `_g4t_a_sidebar_regular_widget_ps_product_id`).
- Always validate the decoded JSON (ensure it's an array) before returning the value.
- Prevent PHP warnings by explicitly casting and checking variable types (e.g., `is_string()`, `is_array()`).

## 4. Development Environment Constraints
- The project runs on LocalWP.
- When making HTTP requests via `wp_remote_get` or similar WordPress HTTP API functions, you MUST use `'sslverify' => false` and force IPv4 via hooks (`CURLOPT_IPRESOLVE_V4`) to prevent local network timeouts.