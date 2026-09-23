<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_sanitize_archive_product_ids_option' ) ) {
	/**
	 * Sanitize global archive sidebar product IDs option.
	 *
	 * @param mixed $raw_value Raw option value.
	 * @return string
	 */
	function g4t_banner_sidebar_widget_sanitize_archive_product_ids_option( $raw_value ) {
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

if ( ! function_exists( 'g4t_banner_sidebar_widget_register_global_setting' ) ) {
	/**
	 * Register global archive sidebar option inside existing settings group.
	 *
	 * @return void
	 */
	function g4t_banner_sidebar_widget_register_global_setting() {
		register_setting(
			'go4taste_ads_options_group',
			'g4t_a_archive_sidebar_product_ids',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'g4t_banner_sidebar_widget_sanitize_archive_product_ids_option',
				'default'           => '',
			)
		);
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_register_global_settings_field' ) ) {
	/**
	 * Add global archive sidebar picker field to main plugin settings section.
	 *
	 * @return void
	 */
	function g4t_banner_sidebar_widget_register_global_settings_field() {
		add_settings_field(
			'g4t_a_archive_sidebar_product_ids',
			__( 'Archive Sidebar Product', 'go4taste-ads' ),
			'g4t_banner_sidebar_widget_render_global_settings_field',
			'go4taste-ads',
			'go4taste_ads_setting_section'
		);
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_render_global_settings_field' ) ) {
	/**
	 * Render archive sidebar picker inside the main plugin settings page.
	 *
	 * @return void
	 */
	function g4t_banner_sidebar_widget_render_global_settings_field() {
		$raw_product_ids = get_option( 'g4t_a_archive_sidebar_product_ids', '' );
		$product_id      = '';

		if ( is_string( $raw_product_ids ) && '' !== trim( $raw_product_ids ) ) {
			$decoded_product_ids = json_decode( $raw_product_ids, true );
			if ( is_array( $decoded_product_ids ) && ! empty( $decoded_product_ids ) ) {
				$product_id = trim( (string) $decoded_product_ids[0] );
			}
		}

		if ( '' === $product_id && is_string( $raw_product_ids ) ) {
			$product_id = trim( $raw_product_ids );
		}

		?>
		<input
			type="hidden"
			id="g4t_a_archive_sidebar_product_ids"
			name="g4t_a_archive_sidebar_product_ids"
			value="<?php echo esc_attr( $product_id ); ?>"
		/>
		<div id="g4t-archive-sidebar-algolia-searchbox"></div>
		<div id="g4t-archive-sidebar-selected-product-container"></div>
		<div id="g4t-archive-sidebar-ad-preview" class="g4t-ad-preview"></div>
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			if (typeof window.G4TAdsPicker !== 'function') {
				return;
			}

			window.G4TAdsPicker({
				hiddenFieldSelector: '#g4t_a_archive_sidebar_product_ids',
				searchBoxSelector: '#g4t-archive-sidebar-algolia-searchbox',
				selectedContainerSelector: '#g4t-archive-sidebar-selected-product-container',
				previewContainerSelector: '#g4t-archive-sidebar-ad-preview',
				ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
				nonce: '<?php echo esc_js( wp_create_nonce( 'g4t_ad_preview_nonce' ) ); ?>',
				postId: 0
			});
		});
		</script>
		<?php
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_enqueue_global_settings_assets' ) ) {
	/**
	 * Enqueue picker assets only on the main plugin settings page.
	 *
	 * @param string $hook_suffix Current admin hook suffix.
	 * @return void
	 */
	function g4t_banner_sidebar_widget_enqueue_global_settings_assets( $hook_suffix ) {
		if ( 'toplevel_page_go4taste-ads' !== $hook_suffix ) {
			return;
		}

		$plugin_root  = dirname( __DIR__, 2 );
		$plugin_entry = $plugin_root . '/go4taste-ads.php';
		$version      = defined( 'GO4TASTE_ADS_VERSION' ) ? GO4TASTE_ADS_VERSION : null;
		$preview_path    = __DIR__ . '/assets/css/go4taste-ads-metabox-preview.css';
		$preview_version = file_exists( $preview_path ) ? (string) filemtime( $preview_path ) : $version;

		wp_enqueue_style(
			'go4taste-ads-metabox-preview',
			plugins_url( 'features/banner-sidebar-widget/assets/css/go4taste-ads-metabox-preview.css', $plugin_entry ),
			array(),
			$preview_version,
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
