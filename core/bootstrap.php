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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/api-client/product-data.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/legacy-sidebar-widget/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/promoted-product-metabox/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/product-catalog-sync/index.php';
	}
}
