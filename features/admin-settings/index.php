<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/settings-page.php';

if ( ! function_exists( 'g4ta_admin_settings_feature_instance' ) ) {
	/**
	 * Return singleton instance for admin settings feature.
	 *
	 * @return Go4Taste_Ads_Admin_Settings
	 */
	function g4ta_admin_settings_feature_instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Go4Taste_Ads_Admin_Settings();
		}

		return $instance;
	}
}

if ( ! function_exists( 'g4ta_admin_settings_register_menu' ) ) {
	/**
	 * Register plugin admin menu.
	 *
	 * @return void
	 */
	function g4ta_admin_settings_register_menu() {
		g4ta_admin_settings_feature_instance()->add_admin_menu();
	}
}

if ( ! function_exists( 'g4ta_admin_settings_register_fields' ) ) {
	/**
	 * Register settings sections and fields.
	 *
	 * @return void
	 */
	function g4ta_admin_settings_register_fields() {
		g4ta_admin_settings_feature_instance()->page_init();
	}
}

if ( ! function_exists( 'g4ta_admin_settings_ajax_test_permissions' ) ) {
	/**
	 * AJAX handler for PrestaShop permissions test.
	 *
	 * @return void
	 */
	function g4ta_admin_settings_ajax_test_permissions() {
		g4ta_admin_settings_feature_instance()->ajax_test_prestashop_permissions();
	}
}

if ( ! function_exists( 'g4ta_admin_settings_ajax_clear_soft_cache' ) ) {
	/**
	 * AJAX handler for global Algolia soft-cache clear.
	 *
	 * @return void
	 */
	function g4ta_admin_settings_ajax_clear_soft_cache() {
		g4ta_admin_settings_feature_instance()->ajax_clear_algolia_soft_cache();
	}
}

if ( ! function_exists( 'g4ta_admin_settings_ajax_clear_current_page_soft_cache' ) ) {
	/**
	 * AJAX handler for current-page Algolia soft-cache clear.
	 *
	 * @return void
	 */
	function g4ta_admin_settings_ajax_clear_current_page_soft_cache() {
		g4ta_admin_settings_feature_instance()->ajax_clear_current_page_algolia_soft_cache();
	}
}

if ( ! function_exists( 'g4ta_admin_settings_ajax_get_cache_counts' ) ) {
	/**
	 * AJAX handler for on-demand Algolia cache counts.
	 *
	 * @return void
	 */
	function g4ta_admin_settings_ajax_get_cache_counts() {
		g4ta_admin_settings_feature_instance()->ajax_get_algolia_cache_counts();
	}
}

if ( ! function_exists( 'g4ta_admin_settings_register_admin_bar' ) ) {
	/**
	 * Register admin bar menu items owned by the admin-settings slice.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar instance.
	 * @return void
	 */
	function g4ta_admin_settings_register_admin_bar( $admin_bar ) {
		g4ta_admin_settings_feature_instance()->add_admin_bar_menu( $admin_bar );
	}
}

if ( ! function_exists( 'g4ta_admin_settings_enqueue_assets' ) ) {
	/**
	 * Enqueue settings page scripts/styles owned by this feature slice.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	function g4ta_admin_settings_enqueue_assets( $hook_suffix ) {
		$should_enqueue_script = ( 'toplevel_page_go4taste-ads' === $hook_suffix ) || ( current_user_can( 'manage_options' ) && is_admin_bar_showing() );
		if ( ! $should_enqueue_script ) {
			return;
		}

		$plugin_root      = dirname( __DIR__, 2 );
		$plugin_entry     = $plugin_root . '/go4taste-ads.php';
		$script_relative  = 'features/admin-settings/assets/js/go4taste-ads-admin-settings.js';
		$style_relative   = 'features/admin-settings/assets/css/go4taste-ads-admin-settings.css';
		$script_path      = $plugin_root . '/' . $script_relative;
		$script_url       = plugins_url( $script_relative, $plugin_entry );
		$style_url        = plugins_url( $style_relative, $plugin_entry );
		$plugin_version   = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;
		$script_version   = file_exists( $script_path ) ? (string) filemtime( $script_path ) : $plugin_version;

		if ( 'toplevel_page_go4taste-ads' === $hook_suffix ) {
			wp_enqueue_style(
				'go4taste-ads-admin-settings',
				$style_url,
				array(),
				$plugin_version,
				'all'
			);
		}

		wp_enqueue_script(
			'go4taste-ads-admin-settings',
			$script_url,
			array( 'jquery' ),
			$script_version,
			true
		);

		wp_localize_script(
			'go4taste-ads-admin-settings',
			'g4tAdsAdmin',
			array(
				'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
				'syncNonce'             => wp_create_nonce( 'g4t_sync_products_ajax' ),
				'syncStartAction'       => 'g4t_sync_start',
				'syncBatchAction'       => 'g4t_sync_batch',
				'syncStatusAction'      => 'g4t_sync_status',
				'syncUnlockAction'      => 'g4t_sync_unlock',
				'permissionsNonce'      => wp_create_nonce( 'g4t_test_permissions_ajax' ),
				'permissionsTestAction' => 'g4t_test_prestashop_permissions',
				'clearCacheNonce'       => wp_create_nonce( 'g4t_clear_soft_cache_ajax' ),
				'clearCacheAction'      => 'g4t_clear_algolia_soft_cache',
				'getCacheCountsNonce'   => wp_create_nonce( 'g4t_get_cache_counts_ajax' ),
				'getCacheCountsAction'  => 'g4t_get_algolia_cache_counts',
				'clearCurrentPageCacheNonce'  => wp_create_nonce( 'g4t_clear_current_page_soft_cache_ajax' ),
				'clearCurrentPageCacheAction' => 'g4t_clear_current_page_algolia_soft_cache',
				'currentPageProductIds'       => g4ta_admin_settings_feature_instance()->get_current_page_product_ids(),
			)
		);
	}
}

if ( ! function_exists( 'g4ta_admin_settings_enqueue_frontend_assets' ) ) {
	/**
	 * Enqueue shared admin-bar assets on the frontend.
	 *
	 * @return void
	 */
	function g4ta_admin_settings_enqueue_frontend_assets() {
		if ( ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
			return;
		}

		g4ta_admin_settings_enqueue_assets( '' );
	}
}

add_action( 'admin_menu', 'g4ta_admin_settings_register_menu' );
add_action( 'admin_init', 'g4ta_admin_settings_register_fields' );
add_action( 'wp_ajax_g4t_test_prestashop_permissions', 'g4ta_admin_settings_ajax_test_permissions' );
add_action( 'wp_ajax_g4t_clear_algolia_soft_cache', 'g4ta_admin_settings_ajax_clear_soft_cache' );
add_action( 'wp_ajax_g4t_clear_current_page_algolia_soft_cache', 'g4ta_admin_settings_ajax_clear_current_page_soft_cache' );
add_action( 'wp_ajax_g4t_get_algolia_cache_counts', 'g4ta_admin_settings_ajax_get_cache_counts' );
add_action( 'admin_enqueue_scripts', 'g4ta_admin_settings_enqueue_assets' );
add_action( 'wp_enqueue_scripts', 'g4ta_admin_settings_enqueue_frontend_assets' );
add_action( 'admin_bar_menu', 'g4ta_admin_settings_register_admin_bar', 100 );
