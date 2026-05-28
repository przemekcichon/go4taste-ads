<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom daily schedule if not present.
 */
add_filter( 'cron_schedules', function( $schedules ) {
	if ( ! isset( $schedules['g4t_daily'] ) ) {
		$schedules['g4t_daily'] = array(
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'Once Daily (G4T)', 'go4taste-ads' ),
		);
	}
	return $schedules;
} );

/**
 * Schedule the recurring event on plugin load.
 */
add_action( 'init', function() {
	if ( ! wp_next_scheduled( 'g4t_algolia_daily_sync_event' ) ) {
		wp_schedule_event( time() + 60, 'g4t_daily', 'g4t_algolia_daily_sync_event' );
	}
} );

/**
 * Get a cron-specific job transient key (not user-based).
 */
function g4t_product_catalog_sync_get_cron_job_transient_key() {
	return 'g4t_sync_algolia_cron_job';
}

/**
 * Cron event callback: initialize sync job state and schedule first batch.
 */
add_action( 'g4t_algolia_daily_sync_event', function() {
	require_once __DIR__ . '/sync-runner.php';

	$job_key = g4t_product_catalog_sync_get_cron_job_transient_key();
	$existing_job = get_transient( $job_key );
	if ( is_array( $existing_job ) && isset( $existing_job['status'] ) && 'running' === $existing_job['status'] ) {
		// Don't start if a job is already running.
		return;
	}

	$options         = g4t_core_get_ads_options();
	$lang            = isset( $options['prestashop_lang'] ) ? sanitize_text_field( (string) $options['prestashop_lang'] ) : 'pl';
	$api_url         = isset( $options['api_url'] ) ? esc_url_raw( (string) $options['api_url'] ) : '';

	if ( '' === trim( $api_url ) ) {
		return;
	}

	$api_fetcher = g4t_core_get_api_fetcher();
	if ( ! $api_fetcher ) {
		return;
	}

	$product_ids = $api_fetcher->fetch_product_ids_for_sync( $lang, $api_url );
	if ( empty( $product_ids ) ) {
		return;
	}

	$batch_size = 50; // Cron can use a larger batch size, adjust as needed.

	$job_state = array(
		'status'      => 'running',
		'lang'        => $lang,
		'api_url'     => $api_url,
		'product_ids' => $product_ids,
		'total'       => count( $product_ids ),
		'offset'      => 0,
		'batch_size'  => $batch_size,
		'synced'      => 0,
		'errors'      => 0,
		'created_at'  => time(),
		'updated_at'  => time(),
	);

	set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
	wp_schedule_single_event( time(), 'g4t_algolia_process_cron_batch' );
} );

/**
 * Cron batch processor: processes one batch and chains next if needed.
 */
add_action( 'g4t_algolia_process_cron_batch', function() {
	require_once __DIR__ . '/sync-runner.php';

	$job_key   = g4t_product_catalog_sync_get_cron_job_transient_key();
	$job_state = get_transient( $job_key );
	if ( ! is_array( $job_state ) || empty( $job_state['status'] ) || 'done' === $job_state['status'] ) {
		return;
	}

	$total     = isset( $job_state['total'] ) ? (int) $job_state['total'] : 0;
	$offset    = isset( $job_state['offset'] ) ? (int) $job_state['offset'] : 0;
	$batch_size = isset( $job_state['batch_size'] ) ? (int) $job_state['batch_size'] : 50;
	$ids_batch = array_slice( $job_state['product_ids'], $offset, $batch_size );

	if ( empty( $ids_batch ) ) {
		$job_state['status'] = 'done';
		$job_state['updated_at'] = time();
		set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
		return;
	}

	$api_fetcher = g4t_core_get_api_fetcher();
	if ( ! $api_fetcher ) {
		$job_state['status']        = 'failed';
		$job_state['error_message'] = 'API fetcher is unavailable.';
		$job_state['updated_at']    = time();
		set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
		return;
	}

	$products = array();

	foreach ( $ids_batch as $product_id ) {
		$product_data = $api_fetcher->fetch_product_data(
			$product_id,
			$job_state['lang'],
			$job_state['api_url']
		);

		if ( $product_data ) {
			$products[] = $product_data;
		} else {
			$job_state['errors'] = isset( $job_state['errors'] ) ? (int) $job_state['errors'] + 1 : 1;
		}
	}

	if ( ! empty( $products ) ) {
		try {
			$api_fetcher->store_products_to_algolia( $products );
			$job_state['synced'] = isset( $job_state['synced'] ) ? (int) $job_state['synced'] + count( $products ) : count( $products );
		} catch ( Throwable $e ) {
			$job_state['errors'] = isset( $job_state['errors'] ) ? (int) $job_state['errors'] + count( $products ) : count( $products );
			$job_state['last_error'] = $e->getMessage();
		}
	}

	$job_state['offset'] = $offset + count( $ids_batch );
	if ( (int) $job_state['offset'] >= $total ) {
		$job_state['status'] = 'done';
	}

	$job_state['updated_at'] = time();
	set_transient( $job_key, $job_state, HOUR_IN_SECONDS );

	// Chain next batch if not done
	if ( 'running' === $job_state['status'] ) {
		wp_schedule_single_event( time(), 'g4t_algolia_process_cron_batch' );
	}
} );
