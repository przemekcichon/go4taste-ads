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

if ( ! function_exists( 'g4t_inline_banner_extract_product_ids_from_blocks' ) ) {
	/**
	 * Extract inline banner product IDs from parsed block tree.
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed block tree.
	 * @return array<int,string>
	 */
	function g4t_inline_banner_extract_product_ids_from_blocks( $blocks ) {
		$product_ids = array();

		if ( ! is_array( $blocks ) ) {
			return $product_ids;
		}

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			if ( 'go4taste-ads/inline-banner' === $block_name ) {
				$products = isset( $block['attrs']['products'] ) && is_array( $block['attrs']['products'] ) ? $block['attrs']['products'] : array();
				foreach ( $products as $product_entry ) {
					$product_id = isset( $product_entry['id'] ) ? trim( (string) $product_entry['id'] ) : '';
					if ( '' !== $product_id ) {
						$product_ids[ $product_id ] = $product_id;
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				foreach ( g4t_inline_banner_extract_product_ids_from_blocks( $block['innerBlocks'] ) as $nested_product_id ) {
					$product_ids[ $nested_product_id ] = $nested_product_id;
				}
			}
		}

		return array_values( $product_ids );
	}
}

if ( ! function_exists( 'g4t_inline_banner_get_current_page_product_ids_for_placement' ) ) {
	/**
	 * Resolve current-page product IDs for inline banner placement.
	 *
	 * @return array<int,string>
	 */
	function g4t_inline_banner_get_current_page_product_ids_for_placement() {
		if ( ! is_singular() ) {
			return array();
		}

		$queried_object = get_queried_object();
		if ( ! $queried_object instanceof WP_Post ) {
			return array();
		}

		$post_content = isset( $queried_object->post_content ) ? (string) $queried_object->post_content : '';
		if ( '' === $post_content || ! function_exists( 'parse_blocks' ) ) {
			return array();
		}

		return g4t_inline_banner_extract_product_ids_from_blocks( parse_blocks( $post_content ) );
	}

}

if ( ! function_exists( 'g4t_inline_banner_collect_current_page_product_ids' ) ) {
	/**
	 * Backward-compatible collector for legacy filter consumers.
	 *
	 * @param array<int,string> $product_ids Product IDs collected so far.
	 * @return array<int,string>
	 */
	function g4t_inline_banner_collect_current_page_product_ids( $product_ids ) {
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array();
		}

		foreach ( g4t_inline_banner_get_current_page_product_ids_for_placement() as $product_id ) {
			$product_ids[] = $product_id;
		}

		return array_values( array_unique( array_filter( array_map( 'strval', $product_ids ) ) ) );
	}
}
