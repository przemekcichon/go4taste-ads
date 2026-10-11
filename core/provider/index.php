<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_ads_provider_enqueue' ) ) {
	/**
	 * Register G4TAdsProvider JS and localize Algolia credentials.
	 *
	 * Called on both admin_enqueue_scripts and wp_enqueue_scripts. In admin the
	 * provider is enqueued for every picker surface (metabox, term edit, global
	 * settings). On the front end it is only registered; a surface that needs
	 * it (the recipe creator) calls wp_enqueue_script( 'g4t-ads-provider' ), so
	 * no other page loads the Algolia client or the search key.
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

		// Full build, not lite: the provider uses getObject().
		wp_register_script(
			'algolia-search-client',
			'https://cdn.jsdelivr.net/npm/algoliasearch@4.27.0/dist/algoliasearch.umd.js',
			array(),
			null,
			true
		);

		wp_register_script(
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

		if ( is_admin() ) {
			wp_enqueue_script( 'g4t-ads-provider' );
		}
	}
}

add_action( 'admin_enqueue_scripts', 'g4t_ads_provider_enqueue' );
add_action( 'wp_enqueue_scripts',    'g4t_ads_provider_enqueue' );
