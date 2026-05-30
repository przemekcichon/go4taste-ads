<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_register_archive_settings_page' ) ) {
	/**
	 * Register archive sidebar settings subpage under Settings.
	 *
	 * @return void
	 */
	function g4t_banner_sidebar_widget_register_archive_settings_page() {
		add_options_page(
			__( 'Go4Taste Archiwa', 'go4taste-ads' ),
			__( 'Go4Taste Archiwa', 'go4taste-ads' ),
			'manage_options',
			'g4t-ads-archive-sidebar-settings',
			'g4t_banner_sidebar_widget_render_archive_settings_page'
		);
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_enqueue_archive_settings_assets' ) ) {
	/**
	 * Enqueue picker assets on archive sidebar settings screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	function g4t_banner_sidebar_widget_enqueue_archive_settings_assets( $hook_suffix ) {
		if ( 'settings_page_g4t-ads-archive-sidebar-settings' !== $hook_suffix ) {
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
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_handle_archive_settings_save' ) ) {
	/**
	 * Save archive sidebar product IDs option.
	 *
	 * @return void
	 */
	function g4t_banner_sidebar_widget_handle_archive_settings_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'go4taste-ads' ) );
		}

		check_admin_referer( 'g4t_archive_sidebar_settings_save', 'g4t_archive_sidebar_settings_nonce' );

		$product_id = isset( $_POST['g4t_archive_sidebar_product_id'] )
			? sanitize_text_field( wp_unslash( $_POST['g4t_archive_sidebar_product_id'] ) )
			: '';
		$product_id = trim( (string) $product_id );

		$product_ids_json = '';
		if ( '' !== $product_id ) {
			$product_ids_json = wp_json_encode( array( $product_id ) );
			if ( ! is_string( $product_ids_json ) ) {
				$product_ids_json = '';
			}
		}

		update_option( 'g4t_a_archive_sidebar_product_ids', $product_ids_json, false );

		$redirect_url = add_query_arg(
			array(
				'page'    => 'g4t-ads-archive-sidebar-settings',
				'updated' => '1',
			),
			admin_url( 'options-general.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}

if ( ! function_exists( 'g4t_banner_sidebar_widget_render_archive_settings_page' ) ) {
	/**
	 * Render archive sidebar settings page.
	 *
	 * @return void
	 */
	function g4t_banner_sidebar_widget_render_archive_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$raw_product_ids = get_option( 'g4t_a_archive_sidebar_product_ids', '' );
		$product_id      = '';

		if ( is_string( $raw_product_ids ) && '' !== trim( $raw_product_ids ) ) {
			$decoded_product_ids = json_decode( $raw_product_ids, true );
			if ( is_array( $decoded_product_ids ) && ! empty( $decoded_product_ids ) ) {
				$product_id = trim( (string) $decoded_product_ids[0] );
			}
		}

		$options         = g4t_core_get_ads_options();
		$algolia_app_id  = isset( $options['algolia_app_id'] ) ? sanitize_text_field( (string) $options['algolia_app_id'] ) : '';
		$algolia_api_key = isset( $options['algolia_api_key'] ) ? sanitize_text_field( (string) $options['algolia_api_key'] ) : '';
		$index_name      = 'go4taste_product_list';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Go4Taste Archiwa', 'go4taste-ads' ); ?></h1>
			<p><?php esc_html_e( 'Wybierz produkt dla widżetu sidebaru na stronach archiwów (archive/home/search).', 'go4taste-ads' ); ?></p>

			<?php if ( isset( $_GET['updated'] ) && '1' === (string) $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Ustawienia zapisane.', 'go4taste-ads' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'g4t_archive_sidebar_settings_save', 'g4t_archive_sidebar_settings_nonce' ); ?>
				<input type="hidden" name="action" value="g4t_archive_sidebar_settings_save" />
				<input type="hidden" id="g4t_archive_sidebar_product_id" name="g4t_archive_sidebar_product_id" value="<?php echo esc_attr( $product_id ); ?>" />

				<div id="algolia-searchbox"></div>
				<div id="selected-product-container"></div>
				<div id="g4t-ad-preview" class="g4t-ad-preview"></div>

				<?php submit_button( __( 'Zapisz ustawienia archiwów', 'go4taste-ads' ) ); ?>
			</form>
		</div>
		<script type="text/javascript">
		jQuery(function() {
			if (typeof window.G4TAdsPicker !== 'function') {
				return;
			}

			window.G4TAdsPicker({
				hiddenFieldSelector: '#g4t_archive_sidebar_product_id',
				searchBoxSelector: '#algolia-searchbox',
				selectedContainerSelector: '#selected-product-container',
				previewContainerSelector: '#g4t-ad-preview',
				algoliaAppId: '<?php echo esc_js( $algolia_app_id ); ?>',
				algoliaApiKey: '<?php echo esc_js( $algolia_api_key ); ?>',
				indexName: '<?php echo esc_js( $index_name ); ?>',
				ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
				nonce: '<?php echo esc_js( wp_create_nonce( 'g4t_ad_preview_nonce' ) ); ?>',
				postId: 0
			});
		});
		</script>
		<?php
	}
}
