<?php

/**
 * Inline Banner Block — Helper functions
 *
 * Utility functions for block rendering, data normalization, and block-specific
 * business logic. These are made available to block templates via require.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/features/banner-inline-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_inline_banner_get_options' ) ) {
	/**
	 * Get inline banner block options.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string,mixed> Block options from plugin settings.
	 */
	function g4t_inline_banner_get_options() {

		$options = get_option( 'go4taste_ads_options' );

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		return $options;

	}
}

if ( ! function_exists( 'g4t_inline_banner_get_option' ) ) {
	/**
	 * Get a single inline banner block option.
	 *
	 * @since 1.2.0
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value if option not found.
	 * @return mixed Option value or default.
	 */
	function g4t_inline_banner_get_option( $key, $default = null ) {

		$options = g4t_inline_banner_get_options();

		return $options[ $key ] ?? $default;

	}
}
