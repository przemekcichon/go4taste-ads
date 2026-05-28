<?php

/**
 * Inline Banner Block — Server-side rendering and theme integration
 *
 * Handles block registration, rendering callback, and theme.json border support.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/features/inline-banner-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_inline_banner_register_block_type' ) ) {
	/**
	 * Register the inline banner block.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	function g4t_inline_banner_register_block_type() {

		if ( ! function_exists( 'register_block_type_from_metadata' ) || ! class_exists( 'WP_Block_Type' ) ) {
			return;
		}

		$block_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'build/blocks/inline-banner';

		if ( ! file_exists( $block_path . '/block.json' ) ) {
			return;
		}

		$block_type = register_block_type_from_metadata(
			$block_path,
			array(
				'render_callback' => 'g4t_inline_banner_render_block',
			)
		);

		if ( ! $block_type instanceof WP_Block_Type ) {
			return;
		}

		$editor_handle = $block_type->editor_script_handles[0] ?? '';
		if ( empty( $editor_handle ) ) {
			return;
		}

		$options     = get_option( 'go4taste_ads_options' );
		$max_products = intval( apply_filters( 'g4t_ads_inline_banner_max_products', 0 ) );
		if ( $max_products < 0 ) {
			$max_products = 0;
		}

		wp_localize_script(
			$editor_handle,
			'g4tAdsBlockData',
			array(
				'algoliaAppId'     => isset( $options['algolia_app_id'] ) ? esc_attr( $options['algolia_app_id'] ) : '',
				'algoliaSearchKey' => isset( $options['algolia_api_key'] ) ? esc_attr( $options['algolia_api_key'] ) : '',
				'algoliaIndexName' => 'go4taste_product_list',
				'maxProducts'      => $max_products,
			)
		);

	}
}

if ( ! function_exists( 'g4t_inline_banner_render_block' ) ) {
	/**
	 * Render callback for the inline banner block with theme template override support.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param string              $content    Block content.
	 * @param WP_Block            $block      Block instance.
	 * @return string HTML output or empty string if template not found.
	 */
	function g4t_inline_banner_render_block( $attributes, $content, $block ) {

		$default_template = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'build/blocks/inline-banner/render.php';
		$theme_template   = locate_template(
			array(
				'go4taste-ads/inline-banner.php',
				'go4taste-ads-inline-banner.php',
			),
			false,
			false
		);

		$template_path = ! empty( $theme_template ) ? $theme_template : $default_template;

		$template_path = apply_filters( 'g4t_ads_inline_banner_template_path', $template_path, $attributes, $content, $block );

		if ( ! is_string( $template_path ) || '' === $template_path || ! file_exists( $template_path ) ) {
			$template_path = $default_template;
		}

		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		ob_start();
		require $template_path;

		return (string) ob_get_clean();

	}
}

if ( ! function_exists( 'g4t_inline_banner_enable_border_settings' ) ) {
	/**
	 * Ensure border settings are enabled in theme.json for classic themes.
	 *
	 * Classic themes (without theme.json) do not opt-in to border settings
	 * by default, which hides the UI even when a block declares support.
	 *
	 * @since 1.2.0
	 *
	 * @param WP_Theme_JSON_Data $theme_json Current theme JSON data.
	 * @return WP_Theme_JSON_Data Modified theme JSON with border settings enabled.
	 */
	function g4t_inline_banner_enable_border_settings( $theme_json ) {

		if ( ! is_object( $theme_json ) || ! method_exists( $theme_json, 'update_with' ) ) {
			return $theme_json;
		}

		$new_data = array(
			'version'  => 3,
			'settings' => array(
				'border' => array(
					'color'  => true,
					'radius' => true,
					'style'  => true,
					'width'  => true,
				),
			),
		);

		return $theme_json->update_with( $new_data );

	}
}
