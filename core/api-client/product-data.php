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
	 * Get current post widget product ID.
	 *
	 * Reads the new JSON array contract first and falls back to the legacy
	 * single-string meta key for backward compatibility.
	 *
	 * @return string
	 */
	function g4t_core_get_sidebar_widget_product_id() {
		global $post;

		$resolve_first_product_id_from_json = static function( $raw_json ) {
			if ( ! is_string( $raw_json ) || '' === trim( $raw_json ) ) {
				return '';
			}

			$decoded_product_ids = json_decode( $raw_json, true );
			if ( ! is_array( $decoded_product_ids ) || ! isset( $decoded_product_ids[0] ) ) {
				return '';
			}

			return trim( (string) $decoded_product_ids[0] );
		};

		if ( is_archive() || is_home() || is_search() ) {
			$archive_product_id = $resolve_first_product_id_from_json( get_option( 'g4t_a_archive_sidebar_product_ids', '' ) );
			return '' !== $archive_product_id ? $archive_product_id : '';
		}

		if ( ! is_singular() ) {
			return '';
		}

		if ( ! is_object( $post ) || empty( $post->ID ) ) {
			return '';
		}

		$post_id = (int) $post->ID;

		$product_id = $resolve_first_product_id_from_json( get_post_meta( $post_id, '_g4t_a_sidebar_product_ids', true ) );
		if ( '' !== $product_id ) {
			return $product_id;
		}

		$legacy_product_id = get_post_meta( $post_id, '_g4t_a_sidebar_regular_widget_ps_product_id', true );
		$legacy_product_id = is_string( $legacy_product_id ) ? $legacy_product_id : (string) $legacy_product_id;

		return trim( $legacy_product_id );
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
	 * Fetch product data through the Algolia product bridge.
	 *
	 * @param string               $product_id Product ID.
	 * @param array<string,mixed>  $options Plugin options.
	 * @return object|null
	 */
	function g4t_core_fetch_product_data( $product_id, $options ) {
		$product_id = trim( (string) $product_id );
		if ( '' === $product_id ) {
			return null;
		}

		return apply_filters( 'go4taste_ads_get_product_data', null, $product_id );
	}
}
