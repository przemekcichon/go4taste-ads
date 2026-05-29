<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_bootstrap_features' ) ) {
	/**
	 * Load shared core boundaries and VSA feature slices.
	 *
	 * This keeps feature bootstrapping out of legacy horizontal layers.
	 *
	 * @return void
	 */
	function g4t_bootstrap_features() {
		// Core boundaries (shared by features)
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/api-client/product-data.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/compat/legacy-go4taste-ads-class.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/i18n/index.php';

		// Feature slices
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/banner-sidebar-widget/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/metabox-banner-sidebar-widget/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/algolia-ad-server/algolia-ad-server.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/algolia-product-sync/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/admin-settings/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/banner-inline-block/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/ga4-tracking/index.php';
	}
}
