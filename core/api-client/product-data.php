<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_core_get_ads_options' ) ) {
	/**
	 * Return normalized plugin options array.
	 *
	 * @return array<string,mixed>
	 */
	function g4t_core_get_ads_options() {
		$options = get_option( 'go4taste_ads_options', array() );
		return is_array( $options ) ? $options : array();
	}
}

if ( ! function_exists( 'g4t_core_get_sidebar_widget_product_id' ) ) {
	/**
	 * Get current post widget product ID from legacy meta key.
	 *
	 * @return string
	 */
	function g4t_core_get_sidebar_widget_product_id() {
		global $post;

		if ( ! is_object( $post ) || empty( $post->ID ) ) {
			return '';
		}

		$product_id = get_post_meta( (int) $post->ID, '_g4t_a_sidebar_regular_widget_ps_product_id', true );
		return is_string( $product_id ) ? $product_id : (string) $product_id;
	}
}

if ( ! function_exists( 'g4t_core_get_api_fetcher' ) ) {
	/**
	 * Return API fetcher instance with lazy class loading.
	 *
	 * @return Go4Taste_Ads_API_Fetcher|null
	 */
	function g4t_core_get_api_fetcher() {
		if ( ! class_exists( 'Go4Taste_Ads_API_Fetcher' ) ) {
			require_once dirname( __FILE__ ) . '/class-go4taste-ads-api-fetcher.php';
		}

		if ( ! class_exists( 'Go4Taste_Ads_API_Fetcher' ) ) {
			return null;
		}

		return new Go4Taste_Ads_API_Fetcher();
	}
}

if ( ! function_exists( 'g4t_core_fetch_product_data' ) ) {
	/**
	 * Fetch product data using shared API fetcher.
	 *
	 * @param string               $product_id Product ID.
	 * @param array<string,mixed>  $options Plugin options.
	 * @return object|false
	 */
	function g4t_core_fetch_product_data( $product_id, $options ) {
		$product_id = trim( (string) $product_id );
		if ( '' === $product_id ) {
			return false;
		}

		$api_fetcher = g4t_core_get_api_fetcher();
		if ( ! $api_fetcher ) {
			return false;
		}
		$lang       = isset( $options['prestashop_lang'] ) ? (string) $options['prestashop_lang'] : 'pl';
		$api_url    = isset( $options['api_url'] ) ? (string) $options['api_url'] : '';

		if ( '' === trim( $api_url ) ) {
			return false;
		}

		return $api_fetcher->fetch_product_data( $product_id, $lang, $api_url );
	}
}
