<?php

/**
 * Inline Banner Block Feature — Gutenberg block registration and rendering
 *
 * Registers the go4taste-ads/inline-banner block and provides server-side
 * rendering with theme-template override support.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/features/banner-inline-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'render.php';
require_once plugin_dir_path( __FILE__ ) . 'helpers.php';

if ( ! function_exists( 'g4t_inline_banner_register_placement' ) ) {
	/**
	 * Register inline banner placement in shared ads placement registry.
	 *
	 * @param array<string,array<string,mixed>> $placements Existing placements.
	 * @return array<string,array<string,mixed>>
	 */
	function g4t_inline_banner_register_placement( $placements ) {
		return g4t_ads_add_placement_definition(
			$placements,
			'inline_banner_block',
			array(
				'label'                             => 'Inline Banner Block',
				'source'                            => 'gutenberg_block',
				'storage_scope'                     => 'post_content',
				'storage_key'                       => 'go4taste-ads/inline-banner:attrs.products',
				'supported_contexts'                => array( 'singular' ),
				'current_page_product_ids_callback' => 'g4t_inline_banner_get_current_page_product_ids_for_placement',
				'render_callback'                   => 'g4t_inline_banner_render_block',
			)
		);
	}
}

/**
 * Register block hooks for inline-banner feature.
 *
 * @since 1.2.0
 */
if ( ! function_exists( 'g4t_inline_banner_register_block_hooks' ) ) {
	function g4t_inline_banner_register_block_hooks() {

		if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
			return;
		}

		// Register block when WordPress initializes.
		add_action( 'init', 'g4t_inline_banner_register_block_type' );

		// Enable border settings in classic themes.
		if ( class_exists( 'WP_Theme_JSON_Data' ) ) {
			add_filter( 'wp_theme_json_data_theme', 'g4t_inline_banner_enable_border_settings' );
		}

		add_filter( 'go4taste/ads/register_placement', 'g4t_inline_banner_register_placement' );

	}
}

g4t_inline_banner_register_block_hooks();
