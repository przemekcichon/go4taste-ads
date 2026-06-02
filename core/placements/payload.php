<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_ads_normalize_product_item' ) ) {
	/**
	 * Normalize a raw product_data object into a flat render-ready array.
	 *
	 * This is the canonical product shape used across all placements. Any new
	 * integration (theme adapter, ACF form, external plugin) MUST produce this
	 * shape. Adding new keys is allowed; removing or renaming existing keys
	 * requires a compatibility fallback.
	 *
	 * @param object $raw Raw product_data from API fetcher / filter.
	 * @return array{
	 *     id: string,
	 *     name: string,
	 *     price: string,
	 *     price_tax_exc: string,
	 *     price_without_reduction: string,
	 *     picture: string,
	 *     link: string,
	 *     category: string,
	 *     image_width: int,
	 *     image_height: int,
	 * }
	 */
	function g4t_ads_normalize_product_item( $raw ) {
		if ( ! is_object( $raw ) ) {
			return array();
		}

		$img_width  = isset( $raw->image_width )  ? absint( $raw->image_width )  : 0;
		$img_height = isset( $raw->image_height ) ? absint( $raw->image_height ) : 0;

		if ( $img_width  < 1 ) {
			$img_width  = 320;
		}
		if ( $img_height < 1 ) {
			$img_height = 180;
		}

		return array(
			'id'                      => isset( $raw->id )                      ? (string) $raw->id                      : '',
			'name'                    => isset( $raw->name )                    ? (string) $raw->name                    : '',
			'price'                   => isset( $raw->price )                   ? (string) $raw->price                   : '',
			'price_tax_exc'           => isset( $raw->price_tax_exc )           ? (string) $raw->price_tax_exc           : '',
			'price_without_reduction' => isset( $raw->price_without_reduction ) ? (string) $raw->price_without_reduction : '',
			'picture'                 => isset( $raw->picture )                 ? (string) $raw->picture                 : '',
			'link'                    => isset( $raw->link )                    ? (string) $raw->link                    : '',
			'category'                => isset( $raw->category )               ? (string) $raw->category               : '',
			'image_width'             => $img_width,
			'image_height'            => $img_height,
		);
	}
}

if ( ! function_exists( 'g4t_ads_build_placement_payload' ) ) {
	/**
	 * Build a normalized render payload for a registered placement.
	 *
	 * The payload is the single data contract passed from the ads plugin to any
	 * renderer: default block render, sidebar widget, theme template, or an
	 * external plugin adapter. No renderer should call the API fetcher directly;
	 * all should consume this payload.
	 *
	 * @param string              $placement_key  Placement key from registry.
	 * @param array<int,string>   $product_ids    Ordered list of product IDs to resolve.
	 * @param array<string,mixed> $context        Optional caller context (e.g. post_id, term_id).
	 * @return array{
	 *     placement_key: string,
	 *     source: string,
	 *     products: list<array<string,mixed>>,
	 *     context: array<string,mixed>,
	 * }
	 */
	function g4t_ads_build_placement_payload( $placement_key, $product_ids, $context = array() ) {
		$placement_key = sanitize_key( (string) $placement_key );
		$options       = g4t_core_get_ads_options();

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array();
		}

		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$placements = g4t_ads_get_registered_placements();
		$placement  = isset( $placements[ $placement_key ] ) ? $placements[ $placement_key ] : array();
		$source     = isset( $placement['source'] ) ? (string) $placement['source'] : 'custom';

		$products = array();
		foreach ( $product_ids as $product_id ) {
			$product_id = trim( (string) $product_id );
			if ( '' === $product_id ) {
				continue;
			}

			$raw = g4t_core_fetch_product_data( $product_id, $options );
			if ( ! is_object( $raw ) ) {
				continue;
			}

			$normalized = g4t_ads_normalize_product_item( $raw );

			/**
			 * Filter a single normalized product item before it enters the payload.
			 *
			 * @param array<string,mixed> $normalized   Normalized product array.
			 * @param object              $raw          Raw product_data from fetcher.
			 * @param string              $placement_key Placement key.
			 */
			$normalized = apply_filters( 'go4taste/ads/placement_product_item', $normalized, $raw, $placement_key );

			if ( is_array( $normalized ) && ! empty( $normalized ) ) {
				$products[] = $normalized;
			}
		}

		$payload = array(
			'placement_key' => $placement_key,
			'source'        => $source,
			'products'      => $products,
			'context'       => $context,
		);

		/**
		 * Filter the complete placement payload before it is handed to any renderer.
		 *
		 * External plugins and the theme can modify the payload without owning
		 * the data resolution logic.
		 *
		 * @param array<string,mixed>  $payload       Normalized placement payload.
		 * @param array<string,mixed>  $placement     Placement definition from registry.
		 * @param array<int,string>    $product_ids   Original product ID list.
		 */
		return apply_filters( 'go4taste/ads/resolve_placement_payload', $payload, $placement, $product_ids );
	}
}

if ( ! function_exists( 'g4t_ads_build_current_page_payload' ) ) {
	/**
	 * Build normalized render payloads for all registered placements on the current page.
	 *
	 * Returns a map keyed by placement_key. Placements that yield no products are
	 * omitted from the result.
	 *
	 * @param array<string,mixed> $context Optional caller context.
	 * @return array<string,array<string,mixed>>
	 */
	function g4t_ads_build_current_page_payload( $context = array() ) {
		$grouped_ids = g4t_ads_get_current_page_product_ids_by_placement();
		$payloads    = array();

		foreach ( $grouped_ids as $placement_key => $product_ids ) {
			if ( empty( $product_ids ) ) {
				continue;
			}

			$payload = g4t_ads_build_placement_payload( $placement_key, $product_ids, $context );

			if ( empty( $payload['products'] ) ) {
				continue;
			}

			$payloads[ $placement_key ] = $payload;
		}

		return $payloads;
	}
}
