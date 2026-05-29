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
    $options = array();
    if (function_exists('g4t_core_get_ads_options')) {
        $resolved_options = g4t_core_get_ads_options();
        if (is_array($resolved_options)) {
            $options = $resolved_options;
        }
    }

    $soft_ttl_hours = isset($options['algolia_soft_cache_ttl_hours']) ? max(1, intval($options['algolia_soft_cache_ttl_hours'])) : 24;
    $hard_ttl_days = isset($options['algolia_hard_cache_ttl_days']) ? max(1, intval($options['algolia_hard_cache_ttl_days'])) : 7;

    $payload = [
        'data' => $data,
        'soft_expires_at' => time() + ($soft_ttl_hours * HOUR_IN_SECONDS),
    ];
    set_transient($cache_key, $payload, $hard_ttl_days * DAY_IN_SECONDS);
}

/**
 * Clear all Algolia ad-server product soft-cache transients.
 *
 * @return array<string,mixed>
 */
function g4t_algolia_ad_server_clear_all_cache() {
    global $wpdb;

    if (!isset($wpdb) || !is_object($wpdb) || !isset($wpdb->options)) {
        return [
            'success' => false,
            'deleted_rows' => 0,
            'executed_at' => current_time('mysql'),
            'error' => 'Database connection is not available.',
        ];
    }

    $value_prefix = $wpdb->esc_like('_transient_g4t_ads_product_') . '%';
    $timeout_prefix = $wpdb->esc_like('_transient_timeout_g4t_ads_product_') . '%';

    $query = $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $value_prefix,
        $timeout_prefix
    );

    if (!is_string($query) || '' === $query) {
        return [
            'success' => false,
            'deleted_rows' => 0,
            'executed_at' => current_time('mysql'),
            'error' => 'Failed to prepare cache cleanup query.',
        ];
    }

    $deleted_rows = $wpdb->query($query);
    if (false === $deleted_rows) {
        return [
            'success' => false,
            'deleted_rows' => 0,
            'executed_at' => current_time('mysql'),
            'error' => 'Failed to execute cache cleanup query.',
        ];
    }

    return [
        'success' => true,
        'deleted_rows' => (int) $deleted_rows,
        'executed_at' => current_time('mysql'),
    ];
}

/**
 * Clear Algolia ad-server soft-cache transients for specific product IDs.
 *
 * @param array<int|string,mixed> $product_ids Product IDs to purge.
 * @return array<string,mixed>
 */
function g4t_algolia_ad_server_clear_product_cache($product_ids) {
    global $wpdb;

    if (!isset($wpdb) || !is_object($wpdb) || !isset($wpdb->options)) {
        return [
            'success' => false,
            'deleted_rows' => 0,
            'executed_at' => current_time('mysql'),
            'cleared_product_ids' => [],
            'error' => 'Database connection is not available.',
        ];
    }

    if (!is_array($product_ids)) {
        $product_ids = [];
    }

    $normalized_product_ids = [];
    foreach ($product_ids as $product_id) {
        $normalized_product_id = trim((string) $product_id);
        if ('' === $normalized_product_id) {
            continue;
        }

        $normalized_product_ids[$normalized_product_id] = $normalized_product_id;
    }

    if (empty($normalized_product_ids)) {
        return [
            'success' => true,
            'deleted_rows' => 0,
            'executed_at' => current_time('mysql'),
            'cleared_product_ids' => [],
        ];
    }

    $like_clauses = [];
    $query_values = [];
    foreach ($normalized_product_ids as $normalized_product_id) {
        $value_prefix = $wpdb->esc_like('_transient_g4t_ads_product_' . $normalized_product_id) . '%';
        $timeout_prefix = $wpdb->esc_like('_transient_timeout_g4t_ads_product_' . $normalized_product_id) . '%';

        $like_clauses[] = 'option_name LIKE %s';
        $query_values[] = $value_prefix;
        $like_clauses[] = 'option_name LIKE %s';
        $query_values[] = $timeout_prefix;
    }

    $query = $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE " . implode(' OR ', $like_clauses),
        ...$query_values
    );

    if (!is_string($query) || '' === $query) {
        return [
            'success' => false,
            'deleted_rows' => 0,
            'executed_at' => current_time('mysql'),
            'cleared_product_ids' => array_values($normalized_product_ids),
            'error' => 'Failed to prepare selective cache cleanup query.',
        ];
    }

    $deleted_rows = $wpdb->query($query);
    if (false === $deleted_rows) {
        return [
            'success' => false,
            'deleted_rows' => 0,
            'executed_at' => current_time('mysql'),
            'cleared_product_ids' => array_values($normalized_product_ids),
            'error' => 'Failed to execute selective cache cleanup query.',
        ];
    }

    return [
        'success' => true,
        'deleted_rows' => (int) $deleted_rows,
        'executed_at' => current_time('mysql'),
        'cleared_product_ids' => array_values($normalized_product_ids),
    ];
}

/**
 * Count Algolia product cache entries (hard + soft) on demand.
 *
 * Hard count is based on transient value rows existing in wp_options.
 * Soft count is based on payload['soft_expires_at'] > current time.
 *
 * @return array<string,mixed>
 */
function g4t_algolia_ad_server_get_cache_counts() {
    global $wpdb;

    if (!isset($wpdb) || !is_object($wpdb) || !isset($wpdb->options)) {
        return [
            'success' => false,
            'soft_count' => 0,
            'hard_count' => 0,
            'executed_at' => current_time('mysql'),
            'error' => 'Database connection is not available.',
        ];
    }

    $value_prefix = $wpdb->esc_like('_transient_g4t_ads_product_') . '%';
    $query = $wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
        $value_prefix
    );

    if (!is_string($query) || '' === $query) {
        return [
            'success' => false,
            'soft_count' => 0,
            'hard_count' => 0,
            'executed_at' => current_time('mysql'),
            'error' => 'Failed to prepare cache count query.',
        ];
    }

    $rows = $wpdb->get_col($query);
    if (!is_array($rows)) {
        return [
            'success' => false,
            'soft_count' => 0,
            'hard_count' => 0,
            'executed_at' => current_time('mysql'),
            'error' => 'Failed to fetch cache count rows.',
        ];
    }

    $hard_count = count($rows);
    $soft_count = 0;
    $now = time();

    foreach ($rows as $row_value) {
        $payload = maybe_unserialize($row_value);
        if (!is_array($payload) || !isset($payload['soft_expires_at'])) {
            continue;
        }

        if ((int) $payload['soft_expires_at'] > $now) {
            $soft_count++;
        }
    }

    return [
        'success' => true,
        'soft_count' => (int) $soft_count,
        'hard_count' => (int) $hard_count,
        'executed_at' => current_time('mysql'),
    ];
}
