<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/metabox-registration.php';
require_once __DIR__ . '/global-settings-integration.php';
require_once __DIR__ . '/term-meta-integration.php';

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

if ( ! function_exists( 'g4t_banner_sidebar_widget_get_current_page_product_ids_for_placement' ) ) {
	/**
	 * Resolve current-page product IDs for sidebar widget placement.
	 *
	 * @return array<int,string>
	 */
	function g4t_banner_sidebar_widget_get_current_page_product_ids_for_placement() {
		$product_id = trim( (string) g4t_core_get_sidebar_widget_product_id() );

		if ( '' === $product_id ) {
			return array();
		}

		return array( $product_id );
	}
}


if ( ! function_exists( 'g4t_banner_sidebar_widget_collect_current_page_product_ids' ) ) {
	/**
	 * Backward-compatible collector for legacy filter consumers.
	 *
	 * @param array<int,string> $product_ids Product IDs collected so far.
	 * @return array<int,string>
	 */
	function g4t_banner_sidebar_widget_collect_current_page_product_ids( $product_ids ) {
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array();
		}

		foreach ( g4t_banner_sidebar_widget_get_current_page_product_ids_for_placement() as $product_id ) {
			$product_ids[] = $product_id;
		}

		return array_values( array_unique( array_filter( array_map( 'strval', $product_ids ) ) ) );
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_register_placement' ) ) {
	/**
	 * Register sidebar widget placement in shared ads placement registry.
	 *
	 * @param array<string,array<string,mixed>> $placements Existing placements.
	 * @return array<string,array<string,mixed>>
	 */
	function g4t_banner_sidebar_widget_register_placement( $placements ) {
		return g4t_ads_add_placement_definition(
			$placements,
			'sidebar_widget_banner',
			array(
				'label'                             => 'Sidebar Banner Widget',
				'source'                            => 'sidebar_widget',
				'storage_scope'                     => 'cascade',
				'storage_key'                       => '_g4t_a_sidebar_product_ids|g4t_a_archive_sidebar_product_ids',
				'supported_contexts'                => array( 'singular', 'archive', 'home', 'search' ),
				'current_page_product_ids_callback' => 'g4t_banner_sidebar_widget_get_current_page_product_ids_for_placement',
			)
		);
	}
}
add_action( 'widgets_init', 'g4t_banner_sidebar_widget_register' );
add_action( 'plugins_loaded', 'g4t_banner_sidebar_widget_signal_loaded' );
add_action( 'wp_enqueue_scripts', 'g4t_banner_sidebar_widget_enqueue_assets' );
add_action( 'add_meta_boxes', 'g4t_promoted_product_metabox_add_meta_box' );
add_action( 'save_post', 'g4t_promoted_product_metabox_save_meta_box' );
add_action( 'wp_ajax_g4t_fetch_product_preview', 'g4t_promoted_product_metabox_fetch_product_preview' );
add_action( 'admin_enqueue_scripts', 'g4t_promoted_product_metabox_enqueue_preview_styles' );
add_action( 'admin_init', 'g4t_banner_sidebar_widget_register_global_setting' );
add_action( 'admin_init', 'g4t_banner_sidebar_widget_register_global_settings_field' );
add_action( 'admin_init', 'g4t_banner_sidebar_widget_register_term_meta_hooks' );
add_action( 'admin_enqueue_scripts', 'g4t_banner_sidebar_widget_enqueue_global_settings_assets' );
add_action( 'admin_enqueue_scripts', 'g4t_banner_sidebar_widget_enqueue_term_meta_assets' );
add_action( 'edited_term', 'g4t_banner_sidebar_widget_save_term_meta', 10, 3 );
add_filter( 'go4taste/ads/register_placement', 'g4t_banner_sidebar_widget_register_placement' );
