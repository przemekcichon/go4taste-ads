<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_promoted_product_metabox_enqueue_preview_styles' ) ) {
	/**
	 * Enqueue promoted product metabox preview assets on post editor screens.
	 *
	 * @return void
	 */
	function g4t_promoted_product_metabox_enqueue_preview_styles() {
		global $pagenow;

		if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
			return;
		}

		$plugin_root      = dirname( __DIR__, 2 );
		$plugin_entry     = $plugin_root . '/go4taste-ads.php';
		$preview_relative = 'features/banner-sidebar-widget/assets/css/go4taste-ads-metabox-preview.css';
		$preview_url      = plugins_url( $preview_relative, $plugin_entry );
		$version          = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;

		wp_enqueue_style(
			'go4taste-ads-metabox-preview',
			$preview_url,
			array(),
			$version,
			'all'
		);

		wp_enqueue_style(
			'go4taste-ads-picker-ui',
			plugins_url( 'features/banner-sidebar-widget/assets/css/picker-ui.css', $plugin_entry ),
			array(),
			$version,
			'all'
		);

		wp_enqueue_script(
			'algolia-instantsearch',
			'https://cdn.jsdelivr.net/npm/instantsearch.js@4',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'algolia-search-client',
			'https://cdn.jsdelivr.net/npm/algoliasearch@4',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'g4t-ads-picker-ui',
			plugins_url( 'features/banner-sidebar-widget/assets/js/picker-ui.js', $plugin_entry ),
			array( 'jquery', 'algolia-instantsearch', 'algolia-search-client' ),
			$version,
			true
		);

		$options         = g4t_core_get_ads_options();
		$algolia_app_id  = isset( $options['algolia_app_id'] ) ? sanitize_text_field( (string) $options['algolia_app_id'] ) : '';
		$algolia_api_key = isset( $options['algolia_api_key'] ) ? sanitize_text_field( (string) $options['algolia_api_key'] ) : '';

		wp_localize_script(
			'g4t-ads-picker-ui',
			'g4tAdsSidebarMetaboxPickerConfig',
			array(
				'algoliaAppId'  => $algolia_app_id,
				'algoliaApiKey' => $algolia_api_key,
				'indexName'     => 'go4taste_product_list',
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'g4t_ad_preview_nonce' ),
			)
		);
	}
}

if ( ! function_exists( 'g4t_promoted_product_metabox_add_meta_box' ) ) {
	/**
	 * Register the promoted product selector metabox on posts.
	 *
	 * @return void
	 */
	function g4t_promoted_product_metabox_add_meta_box() {
		add_meta_box(
			'go4taste_ads_sidebar_regular_widget_meta_box',
			'Go4Taste Ads Widget',
			'g4t_promoted_product_metabox_render_meta_box',
			'post',
			'normal',
			'high'
		);
	}
}

if ( ! function_exists( 'g4t_promoted_product_metabox_render_meta_box' ) ) {
	/**
	 * Render metabox UI with Algolia product picker and preview.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	function g4t_promoted_product_metabox_render_meta_box( $post ) {
		wp_nonce_field( 'go4taste_ads_sidebar_regular_widget_nonce_action', 'go4taste_ads_sidebar_regular_widget_nonce' );

		$product_id      = '';
		$raw_product_ids = get_post_meta( $post->ID, '_g4t_a_sidebar_product_ids', true );

		if ( is_string( $raw_product_ids ) && '' !== trim( $raw_product_ids ) ) {
			$decoded_product_ids = json_decode( $raw_product_ids, true );
			if ( is_array( $decoded_product_ids ) && ! empty( $decoded_product_ids ) ) {
				$product_id = trim( (string) $decoded_product_ids[0] );
			}
		}

		if ( '' === $product_id ) {
			$legacy_product_id = get_post_meta( $post->ID, '_g4t_a_sidebar_regular_widget_ps_product_id', true );
			$product_id        = trim( is_string( $legacy_product_id ) ? $legacy_product_id : (string) $legacy_product_id );
		}

		echo '<input type="hidden" id="g4t_a_sidebar_regular_widget_ps_product_id" name="g4t_a_sidebar_regular_widget_ps_product_id" value="' . esc_attr( $product_id ) . '" size="25" />';
		echo '<div id="algolia-searchbox"></div>';
		echo '<div id="selected-product-container"></div>';
		echo '<div id="g4t-ad-preview" class="g4t-ad-preview"></div>';
		?>
		<script type="text/javascript">
		jQuery(function() {
			if (typeof window.G4TAdsPicker !== 'function') {
				return;
			}

			var config = window.g4tAdsSidebarMetaboxPickerConfig || {};
			window.G4TAdsPicker({
				hiddenFieldSelector: '#g4t_a_sidebar_regular_widget_ps_product_id',
				searchBoxSelector: '#algolia-searchbox',
				selectedContainerSelector: '#selected-product-container',
				previewContainerSelector: '#g4t-ad-preview',
				algoliaAppId: config.algoliaAppId || '',
				algoliaApiKey: config.algoliaApiKey || '',
				indexName: config.indexName || 'go4taste_product_list',
				ajaxUrl: config.ajaxUrl || '',
				nonce: config.nonce || '',
				postId: jQuery('#post_ID').val() || '<?php echo esc_js( (string) $post->ID ); ?>'
			});
		});
		</script>
		<?php
	}
}

if ( ! function_exists( 'g4t_promoted_product_metabox_fetch_product_preview' ) ) {
	/**
	 * AJAX handler for promoted product preview in metabox.
	 *
	 * @return void
	 */
	function g4t_promoted_product_metabox_fetch_product_preview() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'g4t_ad_preview_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}

		$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : '';

		if ( '' === $product_id ) {
			wp_send_json_error( 'No product ID provided.' );
		}

		$options = g4t_core_get_ads_options();
		$product_data = apply_filters( 'go4taste_ads_get_product_data', null, $product_id );

		if ( ! $product_data ) {
			wp_send_json_error( 'Failed to fetch product data.' );
		}

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

		ob_start();
		require __DIR__ . '/preview-template.php';
		$output = ob_get_clean();

		wp_send_json_success( $output );
	}
}

if ( ! function_exists( 'g4t_promoted_product_metabox_save_meta_box' ) ) {
	/**
	 * Persist selected product ID as JSON array in post meta.
	 *
	 * @param int $post_id Current post ID.
	 * @return int|void
	 */
	function g4t_promoted_product_metabox_save_meta_box( $post_id ) {
		if ( ! isset( $_POST['go4taste_ads_sidebar_regular_widget_nonce'] ) ) {
			return $post_id;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['go4taste_ads_sidebar_regular_widget_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'go4taste_ads_sidebar_regular_widget_nonce_action' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$product_id = isset( $_POST['g4t_a_sidebar_regular_widget_ps_product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['g4t_a_sidebar_regular_widget_ps_product_id'] ) ) : '';
		$product_id = trim( (string) $product_id );

		$product_ids_json = '';
		if ( '' !== $product_id ) {
			$product_ids_json = wp_json_encode( array( $product_id ) );
			if ( ! is_string( $product_ids_json ) ) {
				$product_ids_json = '';
			}
		}

		update_post_meta( $post_id, '_g4t_a_sidebar_product_ids', $product_ids_json );

		return $post_id;
	}
}
