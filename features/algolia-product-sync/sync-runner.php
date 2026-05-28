<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_product_catalog_sync_get_job_transient_key' ) ) {
	/**
	 * Build transient key for async Algolia sync job state.
	 *
	 * @return string
	 */
	function g4t_product_catalog_sync_get_job_transient_key() {
		return 'g4t_sync_algolia_job_' . get_current_user_id();
	}
}

if ( ! function_exists( 'g4t_product_catalog_sync_get_job_stale_timeout' ) ) {
	/**
	 * Return stale timeout for running sync job lock.
	 *
	 * @return int
	 */
	function g4t_product_catalog_sync_get_job_stale_timeout() {
		return 10 * MINUTE_IN_SECONDS;
	}
}

if ( ! function_exists( 'g4t_product_catalog_sync_is_job_stale' ) ) {
	/**
	 * Check if running sync job looks stale.
	 *
	 * @param array<string,mixed> $job_state Job state.
	 * @return bool
	 */
	function g4t_product_catalog_sync_is_job_stale( $job_state ) {
		if ( ! is_array( $job_state ) || empty( $job_state['status'] ) || 'running' !== $job_state['status'] ) {
			return false;
		}

		$updated_at = isset( $job_state['updated_at'] ) ? absint( $job_state['updated_at'] ) : 0;
		if ( 0 === $updated_at ) {
			return true;
		}

		return ( time() - $updated_at ) > g4t_product_catalog_sync_get_job_stale_timeout();
	}
}
