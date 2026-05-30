<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/metabox-registration.php';
require_once __DIR__ . '/archive-settings-page.php';

/**
 * Register sidebar banner widget from feature-local slice.
 */
if ( ! function_exists( 'g4t_banner_sidebar_widget_register' ) ) {
	function g4t_banner_sidebar_widget_register() {
		require_once __DIR__ . '/widget-class.php';
		register_widget( 'Go4Taste_Ads_Sidebar_Regular_Widget' );
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_signal_loaded' ) ) {
	function g4t_banner_sidebar_widget_signal_loaded() {
		do_action( 'g4taste_product_ads_sidebar_regular_widget_product_id_function_loaded' );
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_enqueue_assets' ) ) {
	/**
	 * Enqueue frontend assets owned by the sidebar widget slice.
	 *
	 * @return void
	 */
	function g4t_banner_sidebar_widget_enqueue_assets() {
		$plugin_root    = dirname( __DIR__, 2 );
		$plugin_entry   = $plugin_root . '/go4taste-ads.php';
		$style_relative = 'features/banner-sidebar-widget/assets/css/go4taste-ads-sidebar-widget.css';
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

if ( ! function_exists( 'g4t_banner_sidebar_widget_collect_current_page_product_ids' ) ) {
	/**
	 * Append sidebar widget product ID used by the current singular page.
	 *
	 * @param array<int,string> $product_ids Product IDs collected so far.
	 * @return array<int,string>
	 */
	function g4t_banner_sidebar_widget_collect_current_page_product_ids( $product_ids ) {
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array();
		}

		if ( ! is_singular() ) {
			return array_values( array_unique( $product_ids ) );
		}

		$product_id = g4t_core_get_sidebar_widget_product_id();
		$product_id = trim( (string) $product_id );
		if ( '' !== $product_id ) {
			$product_ids[] = $product_id;
		}

		return array_values( array_unique( array_filter( array_map( 'strval', $product_ids ) ) ) );
	}
}
add_action( 'widgets_init', 'g4t_banner_sidebar_widget_register' );
add_action( 'plugins_loaded', 'g4t_banner_sidebar_widget_signal_loaded' );
add_action( 'wp_enqueue_scripts', 'g4t_banner_sidebar_widget_enqueue_assets' );
add_action( 'add_meta_boxes', 'g4t_promoted_product_metabox_add_meta_box' );
add_action( 'save_post', 'g4t_promoted_product_metabox_save_meta_box' );
add_action( 'wp_ajax_g4t_fetch_product_preview', 'g4t_promoted_product_metabox_fetch_product_preview' );
add_action( 'admin_enqueue_scripts', 'g4t_promoted_product_metabox_enqueue_preview_styles' );
add_action( 'admin_menu', 'g4t_banner_sidebar_widget_register_archive_settings_page' );
add_action( 'admin_enqueue_scripts', 'g4t_banner_sidebar_widget_enqueue_archive_settings_assets' );
add_action( 'admin_post_g4t_archive_sidebar_settings_save', 'g4t_banner_sidebar_widget_handle_archive_settings_save' );
add_filter( 'g4t_ads_current_page_product_ids', 'g4t_banner_sidebar_widget_collect_current_page_product_ids' );
