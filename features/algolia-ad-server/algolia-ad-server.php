<?php
// features/algolia-ad-server/algolia-ad-server.php

// Register the global filter for product data retrieval via Algolia + Soft Cache
add_filter('go4taste_ads_get_product_data', 'g4t_algolia_ad_server_get_product_data', 10, 2);

/**
 * Filter handler: fetch product data from Algolia with Soft TTL cache.
 *
 * @param mixed $null Always null (for filter signature).
 * @param int   $product_id Product ID to fetch.
 * @return stdClass|null Product data object or null if not found.
 */
function g4t_algolia_ad_server_get_product_data($null, $product_id) {
    require_once __DIR__ . '/product-data.php';
    return g4t_algolia_ad_server_fetch_product_data($product_id);
}
