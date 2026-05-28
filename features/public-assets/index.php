<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_public_assets_enqueue' ) ) {
	/**
	 * Enqueue shared public stylesheet for front-end placements.
	 *
	 * @return void
	 */
	function g4t_public_assets_enqueue() {
		$plugin_root       = dirname( __DIR__, 2 );
		$plugin_entry_file = $plugin_root . '/go4taste-ads.php';
		$style_relative    = 'features/public-assets/assets/css/go4taste-ads-public.css';
		$style_url         = plugins_url( $style_relative, $plugin_entry_file );
		$plugin_version    = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;

		wp_enqueue_style(
			'go4taste-ads',
			$style_url,
			array(),
			$plugin_version,
			'all'
		);
	}
}

add_action( 'wp_enqueue_scripts', 'g4t_public_assets_enqueue' );
