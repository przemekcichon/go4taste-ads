<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_supported_taxonomies' ) ) {
	/**
	 * Return taxonomies that support sidebar widget term-level config.
	 *
	 * @return array<int,string>
	 */
	function g4t_banner_sidebar_widget_supported_taxonomies() {
		$taxonomies = array( 'category', 'post_tag' );
		$taxonomies = apply_filters( 'g4t_banner_sidebar_widget_supported_taxonomies', $taxonomies );

		if ( ! is_array( $taxonomies ) ) {
			return array( 'category', 'post_tag' );
		}

		$normalized = array();
		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			if ( '' !== $taxonomy ) {
				$normalized[ $taxonomy ] = $taxonomy;
			}
		}

		return array_values( $normalized );
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_normalize_product_ids_json' ) ) {
	/**
	 * Normalize product IDs into JSON array string or empty string.
	 *
	 * @param mixed $raw_value Raw value from request/meta.
	 * @return string
	 */
	function g4t_banner_sidebar_widget_normalize_product_ids_json( $raw_value ) {
		$normalized_ids = array();

		if ( is_array( $raw_value ) ) {
			foreach ( $raw_value as $value ) {
				$product_id = trim( sanitize_text_field( (string) $value ) );
				if ( '' !== $product_id ) {
					$normalized_ids[ $product_id ] = $product_id;
				}
			}
		} elseif ( is_string( $raw_value ) ) {
			$raw_value = trim( $raw_value );
			if ( '' === $raw_value ) {
				return '';
			}

			$decoded = json_decode( $raw_value, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $value ) {
					$product_id = trim( sanitize_text_field( (string) $value ) );
					if ( '' !== $product_id ) {
						$normalized_ids[ $product_id ] = $product_id;
					}
				}
			} else {
				$product_id = trim( sanitize_text_field( $raw_value ) );
				if ( '' !== $product_id ) {
					$normalized_ids[ $product_id ] = $product_id;
				}
			}
		}

		if ( empty( $normalized_ids ) ) {
			return '';
		}

		$encoded = wp_json_encode( array_values( $normalized_ids ) );
		return is_string( $encoded ) ? $encoded : '';
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_register_term_meta_hooks' ) ) {
	/**
	 * Register term-form render and save hooks for supported taxonomies.
	 *
	 * @return void
	 */
	function g4t_banner_sidebar_widget_register_term_meta_hooks() {
		$taxonomies = g4t_banner_sidebar_widget_supported_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			add_action( $taxonomy . '_edit_form_fields', 'g4t_banner_sidebar_widget_render_term_picker_field' );
		}
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_render_term_picker_field' ) ) {
	/**
	 * Render picker field on term edit form.
	 *
	 * @param WP_Term $term Current term.
	 * @return void
	 */
	function g4t_banner_sidebar_widget_render_term_picker_field( $term ) {
		if ( ! ( $term instanceof WP_Term ) ) {
			return;
		}

		$taxonomy = isset( $term->taxonomy ) ? sanitize_key( (string) $term->taxonomy ) : '';
		if ( '' === $taxonomy || ! in_array( $taxonomy, g4t_banner_sidebar_widget_supported_taxonomies(), true ) ) {
			return;
		}

		$raw_product_ids = get_term_meta( $term->term_id, '_g4t_a_sidebar_product_ids', true );
		$product_id      = '';
		if ( is_string( $raw_product_ids ) && '' !== trim( $raw_product_ids ) ) {
			$decoded = json_decode( $raw_product_ids, true );
			if ( is_array( $decoded ) && ! empty( $decoded ) ) {
				$product_id = trim( (string) $decoded[0] );
			}
		}

		if ( '' === $product_id && is_string( $raw_product_ids ) ) {
			$product_id = trim( $raw_product_ids );
		}

		$field_id_suffix = (string) $term->term_id;
		?>
		<tr class="form-field g4t-sidebar-product-picker-wrap">
			<th scope="row"><label for="g4t_a_sidebar_product_ids_<?php echo esc_attr( $field_id_suffix ); ?>"><?php esc_html_e( 'Sidebar Product', 'go4taste-ads' ); ?></label></th>
			<td>
				<?php wp_nonce_field( 'g4t_sidebar_term_meta_save', 'g4t_sidebar_term_meta_nonce' ); ?>
				<input
					type="hidden"
					id="g4t_a_sidebar_product_ids_<?php echo esc_attr( $field_id_suffix ); ?>"
					name="g4t_a_sidebar_product_ids"
					value="<?php echo esc_attr( $product_id ); ?>"
				/>
				<div id="g4t-term-sidebar-algolia-searchbox-<?php echo esc_attr( $field_id_suffix ); ?>"></div>
				<div id="g4t-term-sidebar-selected-product-container-<?php echo esc_attr( $field_id_suffix ); ?>"></div>
				<div id="g4t-term-sidebar-ad-preview-<?php echo esc_attr( $field_id_suffix ); ?>" class="g4t-ad-preview"></div>
				<p class="description"><?php esc_html_e( 'Produkt widzetu sidebar dla tego archiwum termu.', 'go4taste-ads' ); ?></p>
				<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', function() {
					if (typeof window.G4TAdsPicker !== 'function') {
						return;
					}

					window.G4TAdsPicker({
						hiddenFieldSelector: '#g4t_a_sidebar_product_ids_<?php echo esc_js( $field_id_suffix ); ?>',
						searchBoxSelector: '#g4t-term-sidebar-algolia-searchbox-<?php echo esc_js( $field_id_suffix ); ?>',
						selectedContainerSelector: '#g4t-term-sidebar-selected-product-container-<?php echo esc_js( $field_id_suffix ); ?>',
						previewContainerSelector: '#g4t-term-sidebar-ad-preview-<?php echo esc_js( $field_id_suffix ); ?>',
						ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
						nonce: '<?php echo esc_js( wp_create_nonce( 'g4t_ad_preview_nonce' ) ); ?>',
						postId: 0
					});
				});
				</script>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_save_term_meta' ) ) {
	/**
	 * Save sidebar product JSON meta for edited terms.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	function g4t_banner_sidebar_widget_save_term_meta( $term_id, $tt_id = 0, $taxonomy = '' ) {
		unset( $tt_id );

		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( '' === $taxonomy || ! in_array( $taxonomy, g4t_banner_sidebar_widget_supported_taxonomies(), true ) ) {
			return;
		}

		if ( ! isset( $_POST['g4t_sidebar_term_meta_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['g4t_sidebar_term_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'g4t_sidebar_term_meta_save' ) ) {
			return;
		}

		$taxonomy_object = get_taxonomy( $taxonomy );
		$required_cap    = 'manage_categories';
		if ( is_object( $taxonomy_object ) && isset( $taxonomy_object->cap ) && is_object( $taxonomy_object->cap ) && isset( $taxonomy_object->cap->edit_terms ) ) {
			$required_cap = (string) $taxonomy_object->cap->edit_terms;
		}
		if ( ! current_user_can( $required_cap ) ) {
			return;
		}

		$raw_value      = isset( $_POST['g4t_a_sidebar_product_ids'] ) ? wp_unslash( $_POST['g4t_a_sidebar_product_ids'] ) : '';
		$normalized_json = g4t_banner_sidebar_widget_normalize_product_ids_json( $raw_value );

		if ( '' === $normalized_json ) {
			delete_term_meta( $term_id, '_g4t_a_sidebar_product_ids' );
			return;
		}

		update_term_meta( $term_id, '_g4t_a_sidebar_product_ids', $normalized_json );
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_enqueue_term_meta_assets' ) ) {
	/**
	 * Enqueue picker assets only on term edit screens for supported taxonomies.
	 *
	 * @param string $hook_suffix Current admin hook suffix.
	 * @return void
	 */
	function g4t_banner_sidebar_widget_enqueue_term_meta_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}

		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		if ( '' === $taxonomy || ! in_array( $taxonomy, g4t_banner_sidebar_widget_supported_taxonomies(), true ) ) {
			return;
		}

		$plugin_root  = dirname( __DIR__, 2 );
		$plugin_entry = $plugin_root . '/go4taste-ads.php';
		$version      = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;

		wp_enqueue_style(
			'go4taste-ads-metabox-preview',
			plugins_url( 'features/banner-sidebar-widget/assets/css/go4taste-ads-metabox-preview.css', $plugin_entry ),
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
			'g4t-ads-picker-ui',
			plugins_url( 'features/banner-sidebar-widget/assets/js/picker-ui.js', $plugin_entry ),
			array( 'g4t-ads-provider' ),
			$version,
			true
		);
	}
}
