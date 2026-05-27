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

add_action( 'widgets_init', 'g4t_legacy_sidebar_widget_register' );
add_action( 'plugins_loaded', 'g4t_legacy_sidebar_widget_signal_loaded' );
