<?php

/**
 * GA4 Tracking Feature — Ecommerce event tracking
 *
 * Handles GA4 dataLayer event building and integration with product display
 * features (inline banner block, sidebar widget, metabox previews).
 *
 * GA4 tracking is controlled by the 'ga4_view_promotion_datalayer' setting
 * registered in admin-settings feature.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/features/ga4-tracking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'tracking-events.php';
require_once plugin_dir_path( __FILE__ ) . 'payload-builder.php';

/**
 * Initialize GA4 tracking module hooks.
 *
 * @since 1.2.0
 */
if ( ! function_exists( 'g4t_ga4_tracking_init_hooks' ) ) {
	function g4t_ga4_tracking_init_hooks() {

		// Hooks will be registered by consuming features (banner-inline-block, etc.)
		// This module provides payload building and event schema helpers.

	}
}

g4t_ga4_tracking_init_hooks();
