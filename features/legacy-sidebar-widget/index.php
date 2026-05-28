<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register legacy sidebar widget from feature-local slice.
 */
if ( ! function_exists( 'g4t_legacy_sidebar_widget_register' ) ) {
	function g4t_legacy_sidebar_widget_register() {
		require_once __DIR__ . '/widget-class.php';
		register_widget( 'Go4Taste_Ads_Sidebar_Regular_Widget' );
	}
}

if ( ! function_exists( 'g4t_legacy_sidebar_widget_signal_loaded' ) ) {
	function g4t_legacy_sidebar_widget_signal_loaded() {
		do_action( 'g4taste_product_ads_sidebar_regular_widget_product_id_function_loaded' );
	}
}

if ( ! function_exists( 'g4t_legacy_sidebar_widget_enqueue_assets' ) ) {
	/**
	 * Enqueue frontend assets owned by the legacy sidebar widget slice.
	 *
	 * @return void
	 */
	function g4t_legacy_sidebar_widget_enqueue_assets() {
		$plugin_root    = dirname( __DIR__, 2 );
		$plugin_entry   = $plugin_root . '/go4taste-ads.php';
		$style_relative = 'features/legacy-sidebar-widget/assets/css/go4taste-ads-sidebar-widget.css';
		$style_url      = plugins_url( $style_relative, $plugin_entry );
		$version        = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;

		wp_enqueue_style(
			'go4taste-ads-sidebar-widget',
			$style_url,
			array(),
			$version,
			'all'
		);
	}
}

add_action( 'widgets_init', 'g4t_legacy_sidebar_widget_register' );
add_action( 'plugins_loaded', 'g4t_legacy_sidebar_widget_signal_loaded' );
add_action( 'wp_enqueue_scripts', 'g4t_legacy_sidebar_widget_enqueue_assets' );
