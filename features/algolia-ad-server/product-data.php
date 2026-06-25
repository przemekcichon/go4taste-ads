<?php
// features/algolia-ad-server/product-data.php

require_once __DIR__ . '/soft-cache.php';

/**
 * Normalize product record to object shape used by templates.
 *
 * @param mixed $record
 * @return stdClass|null
 */
function g4t_algolia_ad_server_normalize_product_record($record) {
    if (is_object($record)) {
        $record = (array) $record;
    }

    if (!is_array($record)) {
        return null;
    }

    $normalized = new stdClass();
    $normalized->id = isset($record['id']) ? (string) $record['id'] : '';
    if ('' === $normalized->id && isset($record['objectID'])) {
        $normalized->id = (string) $record['objectID'];
    }
    $normalized->name = isset($record['name']) ? (string) $record['name'] : '';
    $normalized->price = isset($record['price']) ? (string) $record['price'] : '';
    $normalized->price_without_reduction = isset($record['price_without_reduction']) ? trim((string) $record['price_without_reduction']) : '';
    if ('' === $normalized->price_without_reduction) {
        $normalized->price_without_reduction = $normalized->price;
    }
    $normalized->link = isset($record['link']) ? (string) $record['link'] : '';
    $normalized->picture = isset($record['picture']) ? (string) $record['picture'] : '';
    $normalized->category = isset($record['category']) ? (string) $record['category'] : '';
    $normalized->reference = isset($record['reference']) ? (string) $record['reference'] : '';
    $normalized->price_tax_exc = isset($record['price_tax_exc']) ? (string) $record['price_tax_exc'] : $normalized->price;
    $normalized->available_for_order = isset($record['available_for_order']) ? (int) $record['available_for_order'] : 0;
    $normalized->low_stock_alert = isset($record['low_stock_alert']) ? (int) $record['low_stock_alert'] : 0;
    if (isset($record['image_width'])) {
        $normalized->image_width = (int) $record['image_width'];
    }
    if (isset($record['image_height'])) {
        $normalized->image_height = (int) $record['image_height'];
    }

    if ('' === $normalized->id || '' === $normalized->name || '' === $normalized->link || '' === $normalized->picture) {
        return null;
    }

    return $normalized;
}

/**
 * Fetch product data from Algolia with Soft TTL cache fallback.
 *
 * @param int $product_id
 * @return stdClass|null
 */
function g4t_algolia_ad_server_fetch_product_data($product_id) {
    $cache_key = 'g4t_ads_product_' . intval($product_id);
    $force_refresh = is_user_logged_in()
        && current_user_can('manage_options')
        && isset($_GET['g4t_ads_nocache']);

    $cached = $force_refresh ? null : g4t_algolia_ad_server_soft_cache_get($cache_key);

    // If cache exists and not expired, return it
    if ($cached && $cached['soft_expires_at'] > time()) {
        return g4t_algolia_ad_server_normalize_product_record($cached['data']);
    }

    // Try to fetch fresh from Algolia
    try {
        if (!class_exists('Algolia\\AlgoliaSearch\\SearchClient')) {
            throw new RuntimeException('Algolia SearchClient class not available.');
        }

        $app_id = g4t_ads_get_algolia_app_id();
        $search_key = g4t_ads_get_algolia_search_key();
        if ('' === $app_id || '' === $search_key) {
            throw new RuntimeException('Missing Algolia read credentials.');
        }

        $client = \Algolia\AlgoliaSearch\SearchClient::create($app_id, $search_key);
        $index = $client->initIndex('go4taste_product_list');
        $record = $index->getObject((string) $product_id);

        $normalized_record = g4t_algolia_ad_server_normalize_product_record($record);
        if ($normalized_record) {
            g4t_algolia_ad_server_soft_cache_set($cache_key, (array) $normalized_record);
            return $normalized_record;
        }
        // If Algolia returns nothing, fall through to stale
    } catch (Throwable $e) {
        // Algolia fetch failed, will return stale if available
    }

    if ($force_refresh) {
        $cached = g4t_algolia_ad_server_soft_cache_get($cache_key);
    }

    // If we have stale cache, return it
    if ($cached) {
        return g4t_algolia_ad_server_normalize_product_record($cached['data']);
    }

    // No data available
    return null;
}
