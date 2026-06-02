<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_ads_get_placement_template_path' ) ) {
	/**
	 * Resolve template path for a placement with theme override support.
	 *
	 * Theme lookup order:
	 *   1. go4taste-ads/{placement_key}.php
	 *   2. go4taste-ads/placement.php (generic fallback)
	 *
	 * Plugin default fallback:
	 *   core/placements/templates/{placement_key}.php
	 *
	 * @param string              $placement_key Placement key.
	 * @param array<string,mixed> $payload       Normalized placement payload.
	 * @return string Absolute path to the resolved template file, or empty string if none.
	 */
	function g4t_ads_get_placement_template_path( $placement_key, $payload ) {
		$placement_key = sanitize_key( (string) $placement_key );

		$theme_template = locate_template(
			array(
				'go4taste-ads/' . $placement_key . '.php',
				'go4taste-ads/placement.php',
			),
			false,
			false
		);

		if ( ! empty( $theme_template ) ) {
			$template_path = $theme_template;
		} else {
			$plugin_template = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'core/placements/templates/' . $placement_key . '.php';
			$template_path   = file_exists( $plugin_template ) ? $plugin_template : '';
		}

		/**
		 * Filter the resolved template path for a placement.
		 *
		 * @param string              $template_path Resolved absolute template path.
		 * @param string              $placement_key Placement key.
		 * @param array<string,mixed> $payload       Normalized placement payload.
		 */
		return (string) apply_filters( 'go4taste/ads/placement_template_path', $template_path, $placement_key, $payload );
	}
}

if ( ! function_exists( 'g4t_ads_render_placement' ) ) {
	/**
	 * Render a placement by key, resolving product IDs on the current page.
	 *
	 * Resolution order:
	 * 1. If a render_callback is registered for the placement, call it with the payload.
	 * 2. Otherwise look for a template (theme override or plugin default).
	 * 3. If neither exists, emit nothing.
	 *
	 * External code (theme, companion plugin) may also fire this manually by
	 * calling:
	 *   echo g4t_ads_render_placement( 'my_placement_key' );
	 *
	 * @param string              $placement_key Placement key.
	 * @param array<int,string>   $product_ids   Optional explicit list; defaults to current-page resolver.
	 * @param array<string,mixed> $context       Optional caller context.
	 * @return string HTML output.
	 */
	function g4t_ads_render_placement( $placement_key, $product_ids = array(), $context = array() ) {
		$placement_key = sanitize_key( (string) $placement_key );

		if ( empty( $product_ids ) ) {
			$grouped = g4t_ads_get_current_page_product_ids_by_placement();
			$product_ids = isset( $grouped[ $placement_key ] ) ? $grouped[ $placement_key ] : array();
		}

		if ( empty( $product_ids ) ) {
			return '';
		}

		$payload = g4t_ads_build_placement_payload( $placement_key, $product_ids, $context );

		if ( empty( $payload['products'] ) ) {
			return '';
		}

		/**
		 * Action fired just before a placement is rendered.
		 *
		 * @param string              $placement_key Placement key.
		 * @param array<string,mixed> $payload       Normalized placement payload.
		 */
		do_action( 'go4taste/ads/before_render_placement', $placement_key, $payload );

		$placements = g4t_ads_get_registered_placements();
		$placement  = isset( $placements[ $placement_key ] ) ? $placements[ $placement_key ] : array();

		// 1. Custom render callback registered by the slice or adapter.
		$render_callback = isset( $placement['render_callback'] ) ? $placement['render_callback'] : null;
		if ( is_callable( $render_callback ) ) {
			$html = (string) call_user_func( $render_callback, $payload );

			/**
			 * Filter HTML output of a placement render.
			 *
			 * @param string              $html          Rendered HTML.
			 * @param string              $placement_key Placement key.
			 * @param array<string,mixed> $payload       Normalized placement payload.
			 */
			return (string) apply_filters( 'go4taste/ads/render_placement', $html, $placement_key, $payload );
		}

		// 2. Template-based render (theme override or plugin default).
		$template_path = g4t_ads_get_placement_template_path( $placement_key, $payload );

		if ( '' === $template_path || ! file_exists( $template_path ) ) {
			return '';
		}

		ob_start();
		require $template_path;
		$html = (string) ob_get_clean();

		return (string) apply_filters( 'go4taste/ads/render_placement', $html, $placement_key, $payload );
	}
}
