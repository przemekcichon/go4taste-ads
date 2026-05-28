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
		$preview_relative = 'features/metabox-banner-sidebar-widget/assets/css/go4taste-ads-metabox-preview.css';
		$preview_url      = plugins_url( $preview_relative, $plugin_entry );
		$version          = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;

		wp_enqueue_style(
			'go4taste-ads-metabox-preview',
			$preview_url,
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

		$product_id = get_post_meta( $post->ID, '_g4t_a_sidebar_regular_widget_ps_product_id', true );
		$product_id = is_string( $product_id ) ? $product_id : (string) $product_id;

		$options        = g4t_core_get_ads_options();
		$algolia_app_id = isset( $options['algolia_app_id'] ) ? sanitize_text_field( (string) $options['algolia_app_id'] ) : '';
		$algolia_api_key = isset( $options['algolia_api_key'] ) ? sanitize_text_field( (string) $options['algolia_api_key'] ) : '';

		echo '<input type="hidden" id="g4t_a_sidebar_regular_widget_ps_product_id" name="g4t_a_sidebar_regular_widget_ps_product_id" value="' . esc_attr( $product_id ) . '" size="25" />';
		echo '<div id="algolia-searchbox"></div>';
		echo '<div id="selected-product-container"></div>';
		echo '<div id="g4t-ad-preview" class="g4t-ad-preview"></div>';
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			const searchClient = algoliasearch('<?php echo esc_js( $algolia_app_id ); ?>', '<?php echo esc_js( $algolia_api_key ); ?>');

			const search = instantsearch({
				indexName: 'go4taste_product_list',
				searchClient,
				searchFunction(helper) {
					if (helper.state.query === '') {
						return;
					}
					helper.search();
				}
			});

			const index = searchClient.initIndex('go4taste_product_list');

			search.addWidget(
				instantsearch.widgets.searchBox({
					container: '#algolia-searchbox',
					placeholder: 'Szukaj po nazwie lub identyfikatorze produktu ...',
					showReset: false,
					showSubmit: false,
				})
			);

			search.addWidget(
				instantsearch.widgets.hits({
					container: '#selected-product-container',
					templates: {
						item(hit) {
							return `
								<div class="algolia-suggestion" data-id="${hit.objectID}">
									<strong>${hit.name}</strong><br>
									<em>Kategoria: ${hit.category}</em><br>
									<em>Identyfikator: ${hit.objectID}</em>
								</div>
							`;
						},
					}
				})
			);

			$(document).on('click', '.algolia-suggestion', function() {
				const productId = $(this).data('id');
				$('#g4t_a_sidebar_regular_widget_ps_product_id').val(productId);
				displaySelectedProduct(productId);
				$('#algolia-searchbox input').val('');
				fetchProductPreview(productId);
			});

			$(document).on('click', '.remove-selected-product', function() {
				$('#g4t_a_sidebar_regular_widget_ps_product_id').val('');
				$('#selected-product-container').html('');
				$('#g4t-ad-preview').html('');
			});

			function displaySelectedProduct(productId) {
				index.getObject(productId)
					.then(function(product) {
						$('#selected-product-container').html(`
							<div class="selected-product">
								<span>${product.name}</span><br>
								<span>ID: ${productId}</span>
								<button type="button" class="remove-selected-product">&times;</button>
							</div>
						`);
					})
					.catch(function(err) {
						console.error('Error retrieving product:', err);
						$('#selected-product-container').html('<p>Product not found.</p>');
					});
			}

			function fetchProductPreview(productID) {
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'g4t_fetch_product_preview',
						product_id: productID,
						post_id: $('#post_ID').val() || '<?php echo esc_js( (string) $post->ID ); ?>',
						nonce: '<?php echo esc_js( wp_create_nonce( 'g4t_ad_preview_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response && response.success) {
							$('#g4t-ad-preview').html(response.data);
							return;
						}
						$('#g4t-ad-preview').html('<p>Error fetching product data.</p>');
					},
					error: function() {
						$('#g4t-ad-preview').html('<p>Error fetching product data.</p>');
					}
				});
			}

			var savedProductID = $('#g4t_a_sidebar_regular_widget_ps_product_id').val();
			if (savedProductID) {
				fetchProductPreview(savedProductID);
				displaySelectedProduct(savedProductID);
			}

			search.start();
		});
		</script>
		<style>
			.algolia-suggestion {
				padding: 5px;
				border-bottom: 1px solid #ddd;
				cursor: pointer;
			}
			.selected-product {
				display: inline-block;
				background-color: #f3f3f3;
				padding: 5px 10px;
				margin-top: 10px;
				border-radius: 4px;
				width: 282px;
			}
			.selected-product button {
				background: none;
				border: none;
				font-size: 16px;
				cursor: pointer;
				color: red;
			}
			.ais-SearchBox-input {
				padding: 0px 8px !important;
				min-height: 34px !important;
				font-size: 1em !important;
				box-shadow: rgba(0, 0, 0, 0.07) 0px 1px 2px inset !important;
				border: 1px solid rgb(221, 221, 221) !important;
				border-radius: 0px !important;
				width: 300px;
			}
		</style>
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
		$product_data = g4t_core_fetch_product_data( $product_id, $options );

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
	 * Persist selected product ID in legacy post meta key.
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
		update_post_meta( $post_id, '_g4t_a_sidebar_regular_widget_ps_product_id', $product_id );

		return $post_id;
	}
}
