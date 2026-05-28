<?php
// features/algolia-ad-server/product-data.php

require_once __DIR__ . '/soft-cache.php';

/**
 * Fetch product data from Algolia with Soft TTL cache fallback.
 *
 * @param int $product_id
 * @return array|null
 */
function g4t_algolia_ad_server_fetch_product_data($product_id) {
    $cache_key = 'g4t_ads_product_' . intval($product_id);
    $cached = g4t_algolia_ad_server_soft_cache_get($cache_key);

    // If cache exists and not expired, return it
    if ($cached && $cached['soft_expires_at'] > time()) {
        return $cached['data'];
    }

    // Try to fetch fresh from Algolia
    try {
        require_once dirname(__DIR__, 2) . '/core/api-client/algolia-client.php';
        $client = g4t_get_algolia_client();
        $index = $client->initIndex('products');
        $record = $index->getObject((string) $product_id);

        if (is_array($record)) {
            g4t_algolia_ad_server_soft_cache_set($cache_key, $record);
            return $record;
        }
        // If Algolia returns nothing, fall through to stale
    } catch (Exception $e) {
        // Algolia fetch failed, will return stale if available
    }

    // If we have stale cache, return it
    if ($cached) {
        return $cached['data'];
    }

    // No data available
    return null;
}
