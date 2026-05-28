<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/settings-page.php';

if ( ! function_exists( 'g4t_admin_settings_feature_instance' ) ) {
	/**
	 * Return singleton instance for admin settings feature.
	 *
	 * @return Go4Taste_Ads_Admin_Settings
	 */
	function g4t_admin_settings_feature_instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Go4Taste_Ads_Admin_Settings();
		}

		return $instance;
	}
}

if ( ! function_exists( 'g4t_admin_settings_register_menu' ) ) {
	/**
	 * Register plugin admin menu.
	 *
	 * @return void
	 */
	function g4t_admin_settings_register_menu() {
		g4t_admin_settings_feature_instance()->add_admin_menu();
	}
}

if ( ! function_exists( 'g4t_admin_settings_register_fields' ) ) {
	/**
	 * Register settings sections and fields.
	 *
	 * @return void
	 */
	function g4t_admin_settings_register_fields() {
		g4t_admin_settings_feature_instance()->page_init();
	}
}

if ( ! function_exists( 'g4t_admin_settings_ajax_test_permissions' ) ) {
	/**
	 * AJAX handler for PrestaShop permissions test.
	 *
	 * @return void
	 */
	function g4t_admin_settings_ajax_test_permissions() {
		g4t_admin_settings_feature_instance()->ajax_test_prestashop_permissions();
	}
}

if ( ! function_exists( 'g4t_admin_settings_enqueue_assets' ) ) {
	/**
	 * Enqueue settings page scripts/styles owned by this feature slice.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	function g4t_admin_settings_enqueue_assets( $hook_suffix ) {
		if ( 'toplevel_page_go4taste-ads' !== $hook_suffix ) {
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

		wp_enqueue_style(
			'go4taste-ads-admin-settings',
			$style_url,
			array(),
			$plugin_version,
			'all'
		);

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
			)
		);
	}
}

add_action( 'admin_menu', 'g4t_admin_settings_register_menu' );
add_action( 'admin_init', 'g4t_admin_settings_register_fields' );
add_action( 'wp_ajax_g4t_test_prestashop_permissions', 'g4t_admin_settings_ajax_test_permissions' );
add_action( 'admin_enqueue_scripts', 'g4t_admin_settings_enqueue_assets' );
