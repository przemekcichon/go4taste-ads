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

add_action( 'admin_menu', 'g4t_admin_settings_register_menu' );
add_action( 'admin_init', 'g4t_admin_settings_register_fields' );
add_action( 'wp_ajax_g4t_test_prestashop_permissions', 'g4t_admin_settings_ajax_test_permissions' );
