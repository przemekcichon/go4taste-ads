<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/sync-runner.php';

if ( ! function_exists( 'g4t_product_catalog_sync_ajax_start' ) ) {
	/**
	 * Start async Algolia sync job (AJAX).
	 *
	 * @return void
	 */
	function g4t_product_catalog_sync_ajax_start() {
		if ( ! current_user_can( 'manage_options' ) ) {
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		check_ajax_referer( 'g4t_sync_products_ajax', 'nonce' );

		$job_key      = g4t_product_catalog_sync_get_job_transient_key();
		$existing_job = get_transient( $job_key );
		if ( is_array( $existing_job ) ) {
			// Admin-triggered start is a hard reset: always overwrite any previous sync state.
			delete_transient( $job_key );
		}

		$batch_size = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 20;
		$batch_size = max( 1, min( 100, $batch_size ) );

		$options         = g4t_core_get_ads_options();
		$lang            = isset( $options['prestashop_lang'] ) ? sanitize_text_field( (string) $options['prestashop_lang'] ) : 'pl';
		$api_url         = isset( $options['api_url'] ) ? esc_url_raw( (string) $options['api_url'] ) : '';

		if ( '' === trim( $api_url ) ) {
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => 'Missing API settings.' ), 400 );
		}

		$api_fetcher = g4t_core_get_api_fetcher();
		if ( ! $api_fetcher ) {
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => 'API fetcher is unavailable.' ), 500 );
		}

		$product_ids = $api_fetcher->fetch_product_ids_for_sync( $lang, $api_url );

		if ( empty( $product_ids ) ) {
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => 'No products found for sync.' ), 404 );
		}

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
		if ( ob_get_length() ) {
			ob_clean();
		}
		wp_send_json_success( array( 'job' => $job_state ) );
	}
}

if ( ! function_exists( 'g4t_product_catalog_sync_ajax_batch' ) ) {
	/**
	 * Process one async Algolia sync batch (AJAX).
	 *
	 * @return void
	 */
	function g4t_product_catalog_sync_ajax_batch() {
		if ( ! current_user_can( 'manage_options' ) ) {
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		check_ajax_referer( 'g4t_sync_products_ajax', 'nonce' );

		$job_key   = g4t_product_catalog_sync_get_job_transient_key();
		$job_state = get_transient( $job_key );
		if ( ! is_array( $job_state ) || empty( $job_state['status'] ) ) {
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => 'Sync job not found.' ), 404 );
		}

		if ( 'done' === $job_state['status'] ) {
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
		}

		if ( g4t_product_catalog_sync_is_job_stale( $job_state ) ) {
			$job_state['status']        = 'failed';
			$job_state['error_message'] = 'Sync job is stale. Please start again.';
			$job_state['updated_at']    = time();
			set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => 'Sync job is stale.', 'job' => $job_state ), 409 );
		}

		$total = isset( $job_state['total'] ) ? (int) $job_state['total'] : 0;
		$offset = isset( $job_state['offset'] ) ? (int) $job_state['offset'] : 0;
		$remaining = max( 0, $total - $offset );

		if ( 0 === $remaining ) {
			$job_state['status'] = 'done';
			$job_state['updated_at'] = time();
			set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
		}

		$batch_size = isset( $job_state['batch_size'] ) ? (int) $job_state['batch_size'] : 20;
		$batch_size = max( 1, min( 100, $batch_size ) );
		$ids_batch = array_slice( $job_state['product_ids'], $offset, $batch_size );

		if ( empty( $ids_batch ) ) {
			$job_state['status'] = 'done';
			$job_state['updated_at'] = time();
			set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
		}

		$api_fetcher = g4t_core_get_api_fetcher();
		if ( ! $api_fetcher ) {
			$job_state['status']        = 'failed';
			$job_state['error_message'] = 'API fetcher is unavailable.';
			$job_state['updated_at']    = time();
			set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
			if ( ob_get_length() ) {
				ob_clean();
			}
			wp_send_json_error( array( 'message' => 'API fetcher is unavailable.', 'job' => $job_state ), 500 );
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
				error_log('[G4T Sync] fetch_product_data returned false/null for product ' . $product_id);
				$job_state['errors'] = isset( $job_state['errors'] ) ? (int) $job_state['errors'] + 1 : 1;
			}
		}

		error_log('[G4T Sync] Batch ready: ' . count( $products ) . ' products fetched, ' . count( $ids_batch ) . ' total in batch.');

		if ( ! empty( $products ) ) {
			try {
				$api_fetcher->store_products_to_algolia( $products );
				$job_state['synced'] = isset( $job_state['synced'] ) ? (int) $job_state['synced'] + count( $products ) : count( $products );
				error_log('[G4T Sync] store_products_to_algolia succeeded for ' . count( $products ) . ' products.');
			} catch ( Throwable $e ) {
				error_log('[G4T Sync] store_products_to_algolia Exception: ' . $e->getMessage());
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
		if ( ob_get_length() ) {
			ob_clean();
		}
		wp_send_json_success( array( 'job' => $job_state, 'done' => 'done' === $job_state['status'] ) );
	}
}

if ( ! function_exists( 'g4t_product_catalog_sync_ajax_status' ) ) {
	/**
	 * Read async sync status (AJAX).
	 *
	 * @return void
	 */
	function g4t_product_catalog_sync_ajax_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		check_ajax_referer( 'g4t_sync_products_ajax', 'nonce' );

		$job_key   = g4t_product_catalog_sync_get_job_transient_key();
		$job_state = get_transient( $job_key );
		if ( ! is_array( $job_state ) ) {
			wp_send_json_error( array( 'message' => 'Sync job not found.' ), 404 );
		}

		if ( g4t_product_catalog_sync_is_job_stale( $job_state ) ) {
			$job_state['status']        = 'failed';
			$job_state['error_message'] = 'Sync job is stale. Lock was released.';
			$job_state['updated_at']    = time();
			delete_transient( $job_key );
			wp_send_json_success( array( 'job' => $job_state ) );
		}

		wp_send_json_success( array( 'job' => $job_state ) );
	}
}

if ( ! function_exists( 'g4t_product_catalog_sync_ajax_unlock' ) ) {
	/**
	 * Force unlock async sync job (AJAX).
	 *
	 * @return void
	 */
	function g4t_product_catalog_sync_ajax_unlock() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		check_ajax_referer( 'g4t_sync_products_ajax', 'nonce' );

		$job_key   = g4t_product_catalog_sync_get_job_transient_key();
		$job_state = get_transient( $job_key );

		if ( ! is_array( $job_state ) ) {
			wp_send_json_success( array( 'message' => 'No active sync lock found.' ) );
		}

		if ( isset( $job_state['status'] ) && 'running' === $job_state['status'] && ! g4t_product_catalog_sync_is_job_stale( $job_state ) ) {
			wp_send_json_error(
				array(
					'message' => 'Sync is still running. Unlock is allowed only for stale jobs.',
					'job'     => $job_state,
				),
				409
			);
		}

		delete_transient( $job_key );
		wp_send_json_success( array( 'message' => 'Sync lock has been cleared.' ) );
	}
}
