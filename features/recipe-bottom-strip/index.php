<?php

/**
 * Recipe Bottom Strip — Placement slice for product ads strip below recipe content.
 *
 * This slice declares the recipe_bottom_strip placement, which reads product IDs
 * from post meta key _g4t_a_recipe_bottom_product_ids (JSON array contract).
 *
 * The meta key can be written by:
 *  - the go4taste-recipes-plugin ACF front-end form (via dedicated field),
 *  - a Metabox on the recipe CPT,
 *  - a programmatic import pipeline.
 *
 * The placement resolves the full normalized payload via core/placements/payload.php.
 * Rendering is delegated to the registered render_callback or a theme template
 * override at: go4taste-ads/recipe_bottom_strip.php
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/features/recipe-bottom-strip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_recipe_bottom_strip_get_current_page_product_ids' ) ) {
	/**
	 * Resolve product IDs for recipe_bottom_strip placement on the current page.
	 *
	 * Reads _g4t_a_recipe_bottom_product_ids (JSON array) from post meta.
	 * Falls back to legacy key _g4t_a_recipe_bottom_ps_product_ids if new key empty.
	 *
	 * @return array<int,string>
	 */
	function g4t_recipe_bottom_strip_get_current_page_product_ids() {
		if ( ! is_singular() ) {
			return array();
		}

		$post_id = get_queried_object_id();
		if ( $post_id < 1 ) {
			return array();
		}

		// Primary: JSON array contract.
		$raw_json = get_post_meta( $post_id, '_g4t_a_recipe_bottom_product_ids', true );

		if ( is_string( $raw_json ) && '' !== trim( $raw_json ) ) {
			$decoded = json_decode( $raw_json, true );
			if ( is_array( $decoded ) && ! empty( $decoded ) ) {
				$ids = array();
				foreach ( $decoded as $id ) {
					$id = trim( (string) $id );
					if ( '' !== $id ) {
						$ids[ $id ] = $id;
					}
				}
				if ( ! empty( $ids ) ) {
					return array_values( $ids );
				}
			}
		}

		// Legacy fallback: single-string meta key used before JSON contract.
		$legacy_raw = get_post_meta( $post_id, '_g4t_a_recipe_bottom_ps_product_ids', true );
		if ( is_string( $legacy_raw ) && '' !== trim( $legacy_raw ) ) {
			$legacy_decoded = json_decode( $legacy_raw, true );
			if ( is_array( $legacy_decoded ) && ! empty( $legacy_decoded ) ) {
				$ids = array();
				foreach ( $legacy_decoded as $id ) {
					$id = trim( (string) $id );
					if ( '' !== $id ) {
						$ids[ $id ] = $id;
					}
				}
				if ( ! empty( $ids ) ) {
					return array_values( $ids );
				}
			}
		}

		return array();
	}
}

if ( ! function_exists( 'g4t_recipe_bottom_strip_register_placement' ) ) {
	/**
	 * Register recipe bottom strip placement in shared placement registry.
	 *
	 * @param array<string,array<string,mixed>> $placements Existing placement list.
	 * @return array<string,array<string,mixed>>
	 */
	function g4t_recipe_bottom_strip_register_placement( $placements ) {
		return g4t_ads_add_placement_definition(
			$placements,
			'recipe_bottom_strip',
			array(
				'label'                             => 'Recipe Bottom Product Strip',
				'source'                            => 'recipe_acf_form',
				'storage_scope'                     => 'postmeta',
				'storage_key'                       => '_g4t_a_recipe_bottom_product_ids',
				'supported_contexts'                => array( 'singular' ),
				'current_page_product_ids_callback' => 'g4t_recipe_bottom_strip_get_current_page_product_ids',
				'template_filter'                   => 'go4taste/ads/recipe_bottom_strip_template_path',
			)
		);
	}
}

add_filter( 'go4taste/ads/register_placement', 'g4t_recipe_bottom_strip_register_placement' );
