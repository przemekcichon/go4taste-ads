<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_ads_add_placement_definition' ) ) {
	/**
	 * Add or replace a placement definition in registry payload.
	 *
	 * @param array<string,array<string,mixed>> $placements Existing placement list.
	 * @param string                            $key Placement key.
	 * @param array<string,mixed>               $definition Placement definition.
	 * @return array<string,array<string,mixed>>
	 */
	function g4t_ads_add_placement_definition( $placements, $key, $definition ) {
		if ( ! is_array( $placements ) ) {
			$placements = array();
		}

		$key = sanitize_key( (string) $key );
		if ( '' === $key ) {
			return $placements;
		}

		if ( ! is_array( $definition ) ) {
			$definition = array();
		}

		$placement = array(
			'key'                                => $key,
			'label'                              => isset( $definition['label'] ) ? sanitize_text_field( (string) $definition['label'] ) : $key,
			'source'                             => isset( $definition['source'] ) ? sanitize_key( (string) $definition['source'] ) : 'custom',
			'storage_scope'                      => isset( $definition['storage_scope'] ) ? sanitize_key( (string) $definition['storage_scope'] ) : 'custom',
			'storage_key'                        => isset( $definition['storage_key'] ) ? sanitize_text_field( (string) $definition['storage_key'] ) : '',
			'supported_contexts'                 => array_values( array_filter( array_map( 'sanitize_key', isset( $definition['supported_contexts'] ) && is_array( $definition['supported_contexts'] ) ? $definition['supported_contexts'] : array() ) ) ),
			'current_page_product_ids_callback'  => isset( $definition['current_page_product_ids_callback'] ) && is_callable( $definition['current_page_product_ids_callback'] )
				? $definition['current_page_product_ids_callback']
				: null,
			'render_callback'                    => isset( $definition['render_callback'] ) && is_callable( $definition['render_callback'] )
				? $definition['render_callback']
				: null,
			'template_filter'                    => isset( $definition['template_filter'] ) ? sanitize_key( (string) $definition['template_filter'] ) : '',
		);

		$placements[ $key ] = $placement;

		return $placements;
	}
}

if ( ! function_exists( 'g4t_ads_get_registered_placements' ) ) {
	/**
	 * Return normalized placements registered in Go4Taste Ads.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function g4t_ads_get_registered_placements() {
		$placements = apply_filters( 'go4taste/ads/register_placement', array() );

		if ( ! is_array( $placements ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $placements as $placement_key => $placement_definition ) {
			$normalized = g4t_ads_add_placement_definition( $normalized, (string) $placement_key, is_array( $placement_definition ) ? $placement_definition : array() );
		}

		return $normalized;
	}
}

if ( ! function_exists( 'g4t_ads_get_current_page_product_ids_by_placement' ) ) {
	/**
	 * Resolve current-page banner product IDs grouped by placement key.
	 *
	 * @return array<string,array<int,string>>
	 */
	function g4t_ads_get_current_page_product_ids_by_placement() {
		$grouped_ids = array();

		foreach ( g4t_ads_get_registered_placements() as $placement_key => $placement ) {
			$callback = isset( $placement['current_page_product_ids_callback'] ) ? $placement['current_page_product_ids_callback'] : null;
			if ( ! is_callable( $callback ) ) {
				continue;
			}

			$raw_ids = call_user_func( $callback, $placement );
			if ( ! is_array( $raw_ids ) ) {
				$raw_ids = array();
			}

			$normalized_ids = array();
			foreach ( $raw_ids as $product_id ) {
				$product_id = trim( (string) $product_id );
				if ( '' !== $product_id ) {
					$normalized_ids[ $product_id ] = $product_id;
				}
			}

			$grouped_ids[ $placement_key ] = array_values( $normalized_ids );
		}

		/**
		 * Filter grouped current-page product IDs per placement.
		 *
		 * @param array<string,array<int,string>>  $grouped_ids Grouped IDs per placement key.
		 * @param array<string,array<string,mixed>> $placements Registered placements.
		 */
		return apply_filters( 'go4taste/ads/current_page_product_ids_by_placement', $grouped_ids, g4t_ads_get_registered_placements() );
	}
}

if ( ! function_exists( 'g4t_ads_get_current_page_product_ids' ) ) {
	/**
	 * Resolve all current-page banner product IDs across registered placements.
	 *
	 * @return array<int,string>
	 */
	function g4t_ads_get_current_page_product_ids() {
		$product_ids = array();

		foreach ( g4t_ads_get_current_page_product_ids_by_placement() as $placement_product_ids ) {
			if ( ! is_array( $placement_product_ids ) ) {
				continue;
			}

			foreach ( $placement_product_ids as $product_id ) {
				$product_id = trim( (string) $product_id );
				if ( '' !== $product_id ) {
					$product_ids[ $product_id ] = $product_id;
				}
			}
		}

		return array_values( $product_ids );
	}
}

if ( ! function_exists( 'g4t_ads_collect_registered_placements_current_page_product_ids' ) ) {
	/**
	 * Compatibility bridge: append IDs resolved through placement registry.
	 *
	 * @param array<int,string> $product_ids IDs collected through legacy hooks.
	 * @return array<int,string>
	 */
	function g4t_ads_collect_registered_placements_current_page_product_ids( $product_ids ) {
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array();
		}

		foreach ( g4t_ads_get_current_page_product_ids() as $product_id ) {
			$product_id = trim( (string) $product_id );
			if ( '' !== $product_id ) {
				$product_ids[ $product_id ] = $product_id;
			}
		}

		return array_values( $product_ids );
	}
}

add_filter( 'g4t_ads_current_page_product_ids', 'g4t_ads_collect_registered_placements_current_page_product_ids', 5 );