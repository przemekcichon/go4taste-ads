<?php

/**
 * GA4 Tracking — Event definitions and schemas
 *
 * Defines event types, payload schemas, and validation for GA4 ecommerce
 * tracking in WordPress dataLayer.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/features/ga4-tracking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_ga4_get_ecommerce_item_schema' ) ) {
	/**
	 * Get the schema template for a single ecommerce item in GA4 event.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string,mixed> Item schema template.
	 */
	function g4t_ga4_get_ecommerce_item_schema() {

		return array(
			'item_id'         => '',
			'item_name'       => '',
			'item_category'   => '',
			'price'           => 0,
			'creative_slot'   => '',
			'creative_name'   => '',
		);

	}
}

if ( ! function_exists( 'g4t_ga4_normalize_item' ) ) {
	/**
	 * Normalize a product item for GA4 ecommerce event payload.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string,mixed> $item Product item data.
	 * @return array<string,mixed> Normalized item matching GA4 schema.
	 */
	function g4t_ga4_normalize_item( $item ) {

		$schema = g4t_ga4_get_ecommerce_item_schema();

		return array_intersect_key(
			wp_parse_args( (array) $item, $schema ),
			$schema
		);

	}
}
