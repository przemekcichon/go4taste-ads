<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_ads_provider_enqueue' ) ) {
	/**
	 * Enqueue G4TAdsProvider JS and localize Algolia credentials.
	 *
	 * Called on both admin_enqueue_scripts and wp_enqueue_scripts so every
	 * surface — metabox, term edit, global settings, frontend creator — gets
	 * the shared provider without owning credentials.
	 *
	 * @return void
	 */
	function g4t_ads_provider_enqueue() {
		$app_id  = g4t_ads_get_algolia_app_id();
		$api_key = g4t_ads_get_algolia_search_key();

		if ( '' === $app_id || '' === $api_key ) {
			return;
		}

		$plugin_entry = dirname( __DIR__, 2 ) . '/go4taste-ads.php';
		$version      = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;

		wp_enqueue_script(
			'algolia-search-client',
			'https://cdn.jsdelivr.net/npm/algoliasearch@4',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'g4t-ads-provider',
			plugins_url( 'core/provider/algolia-provider.js', $plugin_entry ),
			array( 'algolia-search-client' ),
			$version,
			true
		);

		wp_localize_script(
			'g4t-ads-provider',
			'G4T_ADS_PROVIDER_DATA',
			array(
				'appId'     => $app_id,
				'apiKey'    => $api_key,
				'indexName' => 'go4taste_product_list',
			)
		);
	}
}

add_action( 'admin_enqueue_scripts', 'g4t_ads_provider_enqueue' );
add_action( 'wp_enqueue_scripts',    'g4t_ads_provider_enqueue' );
