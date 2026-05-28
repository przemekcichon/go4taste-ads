<?php

/**
 * GA4 Tracking — Payload builders and output
 *
 * Functions to build GA4 dataLayer payload structures and output scripts
 * for ecommerce events.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/features/ga4-tracking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_ga4_is_tracking_enabled' ) ) {
	/**
	 * Check if GA4 view_promotion event tracking is enabled.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if GA4 tracking is enabled via admin settings.
	 */
	function g4t_ga4_is_tracking_enabled() {

		$options = get_option( 'go4taste_ads_options' );

		if ( ! is_array( $options ) ) {
			return false;
		}

		return ! empty( $options['ga4_view_promotion_datalayer'] );

	}
}

if ( ! function_exists( 'g4t_ga4_build_view_promotion_payload' ) ) {
	/**
	 * Build a GA4 view_promotion event payload.
	 *
	 * @since 1.2.0
	 *
	 * @param array<int,array<string,mixed>> $items Array of product items.
	 * @return array<string,mixed> Event payload ready for dataLayer.push().
	 */
	function g4t_ga4_build_view_promotion_payload( $items ) {

		$normalized_items = array_map( 'g4t_ga4_normalize_item', (array) $items );

		return array(
			'event'       => 'view_promotion',
			'ecommerce'   => array(
				'items' => array_filter( $normalized_items ),
			),
		);

	}
}

if ( ! function_exists( 'g4t_ga4_output_view_promotion_script' ) ) {
	/**
	 * Output inline script for GA4 view_promotion event.
	 *
	 * Safe-escapes and outputs dataLayer.push() call for ecommerce tracking.
	 *
	 * @since 1.2.0
	 *
	 * @param array<int,array<string,mixed>> $items Array of product items.
	 * @return void Outputs inline script tag and JavaScript.
	 */
	function g4t_ga4_output_view_promotion_script( $items ) {

		if ( ! g4t_ga4_is_tracking_enabled() || empty( $items ) ) {
			return;
		}

		$payload = g4t_ga4_build_view_promotion_payload( $items );

		// phpcs:disable
		?>
		<script>
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( <?php echo wp_json_encode( $payload ); ?> );
		</script>
		<?php
		// phpcs:enable

	}
}
