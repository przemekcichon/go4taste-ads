<?php
// features/algolia-ad-server/soft-cache.php

/**
 * Get a value from the soft TTL cache.
 *
 * @param string $cache_key
 * @return array|null ['data' => ..., 'soft_expires_at' => ...] or null if not found.
 */
function g4t_algolia_ad_server_soft_cache_get($cache_key) {
    $payload = get_transient($cache_key);
    if (!is_array($payload) || !isset($payload['data'], $payload['soft_expires_at'])) {
        return null;
    }
    return $payload;
}

/**
 * Set a value in the soft TTL cache.
 *
 * @param string $cache_key
 * @param mixed  $data
 * @return void
 */
function g4t_algolia_ad_server_soft_cache_set($cache_key, $data) {
    $payload = [
        'data' => $data,
        'soft_expires_at' => time() + DAY_IN_SECONDS, // 24h soft TTL
    ];
    // Hard TTL: 7 days
    set_transient($cache_key, $payload, 7 * DAY_IN_SECONDS);
}
