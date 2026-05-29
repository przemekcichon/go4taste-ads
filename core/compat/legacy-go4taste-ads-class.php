<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Go4Taste_Ads' ) ) {
	/**
	 * Legacy compatibility proxy for removed Go4Taste_Ads singleton.
	 *
	 * Kept to support old theme overrides that still call:
	 * Go4Taste_Ads::get_instance()->fetch_product_data(...)
	 */
	class Go4Taste_Ads {
		/**
		 * @var self|null
		 */
		private static $instance = null;

		/**
		 * Legacy singleton accessor.
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Legacy product fetch signature retained for backward compatibility.
		 *
		 * @param string $product_id Product ID.
		 * @param string $p_value Legacy parameter (ignored by new runtime).
		 * @param string $lang PrestaShop language code.
		 * @param string $api_type Legacy API type.
		 * @param string $api_url API URL.
		 * @return object|false
		 */
		public function fetch_product_data( $product_id, $p_value = '', $lang = 'pl', $api_type = 'prestashop_webservice_api', $api_url = '' ) {
			$product_id = trim( (string) $product_id );
			if ( '' === $product_id ) {
				return false;
			}

			$from_filter = apply_filters( 'go4taste_ads_get_product_data', null, $product_id );
			if ( $from_filter ) {
				return $from_filter;
			}

			if ( ! function_exists( 'g4t_core_fetch_product_data' ) ) {
				return false;
			}

			$options = function_exists( 'g4t_core_get_ads_options' ) ? g4t_core_get_ads_options() : array();

			if ( '' !== trim( (string) $lang ) ) {
				$options['prestashop_lang'] = (string) $lang;
			}

			if ( '' !== trim( (string) $api_url ) ) {
				$options['api_url'] = (string) $api_url;
			}

			// Runtime is Webservice-only, keep this forced for legacy callers.
			$options['api_type'] = 'prestashop_webservice_api';

			if ( '' !== trim( (string) $p_value ) ) {
				$options['p_parameter_value'] = (string) $p_value;
			}

			return g4t_core_fetch_product_data( $product_id, $options );
		}
	}
}
