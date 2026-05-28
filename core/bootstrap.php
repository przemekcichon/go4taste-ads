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
		if ( defined( 'WP_CLI' ) && true === constant( 'WP_CLI' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-go4taste-ads-wp-cli-command.php';
		}

		// Core boundaries (shared by features)
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/api-client/product-data.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/i18n/index.php';

		// Feature slices
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/public-assets/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/legacy-sidebar-widget/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/promoted-product-metabox/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/product-catalog-sync/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/admin-settings/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/admin-assets/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/inline-banner-block/index.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/ga4-tracking/index.php';
	}
}
