<?php

/**
 * Recipe Bottom Strip — View model helper.
 *
 * Provides g4t_recipe_bottom_strip_get_products_for_post() which is the
 * recommended integration point for the go4taste-recipes-plugin view-model
 * builder and any other consumer that knows the post_id but is not running
 * inside a WordPress template request (e.g. REST API, import pipeline).
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/features/recipe-bottom-strip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_recipe_bottom_strip_get_products_for_post' ) ) {
	/**
	 * Return a normalized render-ready product list for a given post.
	 *
	 * Reads _g4t_a_recipe_bottom_product_ids (JSON array contract) and resolves
	 * each product through the shared payload builder. Returns the products array
	 * from the payload, ready to be consumed by Timber templates.
	 *
	 * This is the integration point that replaces direct `fetch_product_data` calls
	 * in the recipes plugin view-model builder.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int,array<string,mixed>> Normalized product items.
	 */
	function g4t_recipe_bottom_strip_get_products_for_post( int $post_id ) {
		if ( $post_id < 1 ) {
			return array();
		}

		// Reuse the placement resolver, passing the post_id context explicitly so
		// the resolver does not depend on get_queried_object_id() being correct.
		$raw_json = get_post_meta( $post_id, '_g4t_a_recipe_bottom_product_ids', true );
		$ids      = array();

		if ( is_string( $raw_json ) && '' !== trim( $raw_json ) ) {
			$decoded = json_decode( $raw_json, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $id ) {
					$id = trim( (string) $id );
					if ( '' !== $id ) {
						$ids[ $id ] = $id;
					}
				}
			}
		}

		// Legacy fallback.
		if ( empty( $ids ) ) {
			$legacy = get_post_meta( $post_id, '_g4t_a_recipe_bottom_ps_product_ids', true );
			if ( is_string( $legacy ) && '' !== trim( $legacy ) ) {
				$decoded_legacy = json_decode( $legacy, true );
				if ( is_array( $decoded_legacy ) ) {
					foreach ( $decoded_legacy as $id ) {
						$id = trim( (string) $id );
						if ( '' !== $id ) {
							$ids[ $id ] = $id;
						}
					}
				}
			}
		}

		if ( empty( $ids ) ) {
			return array();
		}

		$payload = g4t_ads_build_placement_payload(
			'recipe_bottom_strip',
			array_values( $ids ),
			array( 'post_id' => $post_id )
		);

		return isset( $payload['products'] ) && is_array( $payload['products'] ) ? $payload['products'] : array();
	}
}
