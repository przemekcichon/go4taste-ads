<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_admin_assets_enqueue' ) ) {
	/**
	 * Enqueue shared admin styles/scripts for plugin screens.
	 *
	 * @return void
	 */
	function g4t_admin_assets_enqueue() {
		$plugin_root         = dirname( __DIR__, 2 );
		$plugin_entry_file   = $plugin_root . '/go4taste-ads.php';
		$script_relative     = 'features/admin-assets/assets/js/go4taste-ads-admin.js';
		$style_relative      = 'features/admin-assets/assets/css/go4taste-ads-admin.css';
		$admin_script_path   = $plugin_root . '/' . $script_relative;
		$admin_script_url    = plugins_url( $script_relative, $plugin_entry_file );
		$admin_style_url     = plugins_url( $style_relative, $plugin_entry_file );
		$plugin_version      = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;
		$admin_script_version = file_exists( $admin_script_path ) ? (string) filemtime( $admin_script_path ) : $plugin_version;

		wp_enqueue_style(
			'go4taste-ads',
			$admin_style_url,
			array(),
			$plugin_version,
			'all'
		);

		wp_enqueue_script(
			'algolia-instantsearch',
			'https://cdn.jsdelivr.net/npm/instantsearch.js@4',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'algolia-search-client',
			'https://cdn.jsdelivr.net/npm/algoliasearch@4',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'go4taste-ads',
			$admin_script_url,
			array( 'jquery' ),
			$admin_script_version,
			true
		);

		wp_localize_script(
			'go4taste-ads',
			'g4tAdsAdmin',
			array(
				'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
				'syncNonce'             => wp_create_nonce( 'g4t_sync_products_ajax' ),
				'syncStartAction'       => 'g4t_sync_start',
				'syncBatchAction'       => 'g4t_sync_batch',
				'syncStatusAction'      => 'g4t_sync_status',
				'syncUnlockAction'      => 'g4t_sync_unlock',
				'permissionsNonce'      => wp_create_nonce( 'g4t_test_permissions_ajax' ),
				'permissionsTestAction' => 'g4t_test_prestashop_permissions',
			)
		);
	}
}

add_action( 'admin_enqueue_scripts', 'g4t_admin_assets_enqueue' );
