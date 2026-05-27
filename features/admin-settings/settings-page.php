<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings page feature slice.
 */
class Go4Taste_Ads_Admin_Settings {
	/**
	 * Render permissions report HTML for admin settings panel.
	 *
	 * @since 1.2.0
	 * @param array<string,mixed>|false $permissions_report Report structure.
	 * @return string
	 */
	private function render_prestashop_permissions_report_html( $permissions_report ) {
		if ( ! is_array( $permissions_report ) ) {
			return '<p class="description">Brak zapisanego wyniku testu uprawnien. Kliknij "Test API Permissions".</p>';
		}

		ob_start();

		$summary_color = ! empty( $permissions_report['ok'] ) ? '#188038' : '#a00';
		$summary_text  = ! empty( $permissions_report['message'] ) ? (string) $permissions_report['message'] : 'Brak danych weryfikacji.';

		echo '<p class="description" style="color:' . esc_attr( $summary_color ) . ';"><strong>Status uprawnien:</strong> ' . esc_html( $summary_text ) . '</p>';

		if ( ! empty( $permissions_report['checked_at'] ) ) {
			echo '<p class="description">Ostatni test: ' . esc_html( (string) $permissions_report['checked_at'] ) . '</p>';
		}

		if ( ! empty( $permissions_report['resources'] ) && is_array( $permissions_report['resources'] ) ) {
			echo '<ul style="margin:6px 0 0 18px; list-style:disc;">';
			foreach ( $permissions_report['resources'] as $resource_name => $resource_status ) {
				$resource_ok    = ! empty( $resource_status['ok'] );
				$resource_color = $resource_ok ? '#188038' : '#a00';
				$code_suffix    = '';
				if ( isset( $resource_status['http_code'] ) && (int) $resource_status['http_code'] > 0 ) {
					$code_suffix = ' (HTTP ' . (int) $resource_status['http_code'] . ')';
				}

				$line = strtoupper( (string) $resource_name ) . ': ' . ( ( isset( $resource_status['message'] ) && '' !== (string) $resource_status['message'] ) ? (string) $resource_status['message'] : ( $resource_ok ? 'OK' : 'Brak uprawnienia' ) ) . $code_suffix;
				echo '<li style="color:' . esc_attr( $resource_color ) . ';">' . esc_html( $line ) . '</li>';
			}
			echo '</ul>';
		}

		return (string) ob_get_clean();
	}

	/**
	 * AJAX: test PrestaShop Webservice permissions on demand.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ajax_test_prestashop_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}

		check_ajax_referer( 'g4t_test_permissions_ajax', 'nonce' );

		$options         = get_option( 'go4taste_ads_options' );
		$api_type        = isset( $options['api_type'] ) ? sanitize_text_field( $options['api_type'] ) : '';
		$api_url         = isset( $options['api_url'] ) ? esc_url_raw( $options['api_url'] ) : '';
		$stored_key      = isset( $options['prestashop_webservice_api_key'] ) ? sanitize_text_field( $options['prestashop_webservice_api_key'] ) : '';
		$tax_country_iso = isset( $options['prestashop_tax_country_iso'] ) ? strtoupper( sanitize_text_field( $options['prestashop_tax_country_iso'] ) ) : 'PL';

		if ( 'prestashop_webservice_api' !== $api_type ) {
			wp_send_json_error( array( 'message' => 'Wybierz API Type: Prestashop Webservice API.' ), 400 );
		}

		if ( '' === trim( (string) $api_url ) || '' === trim( (string) $stored_key ) ) {
			wp_send_json_error( array( 'message' => 'Uzupelnij API URL i zapisz klucz Webservice.' ), 400 );
		}

		$api_fetcher         = new Go4Taste_Ads_API_Fetcher();
		$permissions_report  = $api_fetcher->verify_prestashop_webservice_permissions( $api_url, $stored_key, $tax_country_iso );

		wp_send_json_success(
			array(
				'report' => $permissions_report,
				'html'   => $this->render_prestashop_permissions_report_html( $permissions_report ),
			)
		);
	}

	/**
	 * Registers the admin menu for the plugin settings.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			'Go4Taste Product Ads Settings',
			'Go4Taste Ads',
			'manage_options',
			'go4taste-ads',
			array( $this, 'create_admin_page' ),
			'dashicons-admin-generic',
			100
		);
	}

	/**
	 * Display the plugin settings page.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h1>Go4Taste Ads Settings</h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'go4taste_ads_options_group' );
				do_settings_sections( 'go4taste-ads' );
				do_settings_sections( 'algolia_settings' );
				submit_button();
				?>
			</form>

			<hr>

			<h2><?php _e( 'Sync Products to Algolia', 'go4taste-ads' ); ?></h2>
			<p><?php _e( 'Click the button below to sync all products from the PrestaShop API to Algolia.', 'go4taste-ads' ); ?></p>
			<form method="post" id="g4t-sync-form" onsubmit="return false;">
				<p>
					<label for="g4t-sync-batch-size"><?php _e( 'Batch size:', 'go4taste-ads' ); ?></label>
					<input type="number" min="1" max="100" step="1" id="g4t-sync-batch-size" name="g4t_sync_batch_size" value="20" style="width:120px;">
				</p>
				<button type="button" class="button-primary" id="g4t-sync-start"><?php _e( 'Sync Products', 'go4taste-ads' ); ?></button>
			</form>

			<div id="g4t-sync-progress" class="g4t-migration-progress" style="display:none;">
				<div class="g4t-migration-progress-bar-wrap">
					<div id="g4t-sync-progress-bar" class="g4t-migration-progress-bar" style="width:0%;"></div>
				</div>
				<p id="g4t-sync-progress-text" class="g4t-migration-progress-text"></p>
				<ul id="g4t-sync-progress-stats" class="g4t-migration-progress-stats"></ul>
				<div id="g4t-sync-log" class="g4t-migration-log"></div>
			</div>

		</div>
		<?php
	}

	/**
	 * Registers the settings for the plugin.
	 *
	 * @since    1.0.0
	 * @since    1.1.0 Added transient caching field
	 * @return void
	 */
	public function page_init() {
		register_setting(
			'go4taste_ads_options_group',
			'go4taste_ads_options',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'go4taste_ads_setting_section',
			'Product API Settings',
			'__return_false',
			'go4taste-ads'
		);

		add_settings_section(
			'algolia_settings',
			'Algolia Settings',
			'__return_false',
			'algolia_settings'
		);

		add_settings_field( 'api_type', 'API Type', array( $this, 'api_type_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
		add_settings_field( 'api_url', 'API URL', array( $this, 'api_url_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
		add_settings_field( 'p_parameter_value', 'PrestaShop API Password', array( $this, 'p_parameter_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
		add_settings_field( 'prestashop_webservice_api_key', 'PrestaShop Webservice API Key', array( $this, 'prestashop_webservice_api_key_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
		add_settings_field( 'prestashop_lang', 'PrestaShop Language', array( $this, 'prestashop_lang_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
		add_settings_field( 'prestashop_tax_country_iso', 'PrestaShop Tax Country ISO', array( $this, 'prestashop_tax_country_iso_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
		add_settings_field( 'fallback_product_id', 'Fallback Product ID', array( $this, 'fallback_product_id_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
		add_settings_field( 'algolia_app_id', 'Algolia App ID', array( $this, 'algolia_app_id_callback' ), 'algolia_settings', 'algolia_settings' );
		add_settings_field( 'algolia_api_key', 'Algolia Search-Only API Key', array( $this, 'algolia_api_key_callback' ), 'algolia_settings', 'algolia_settings' );
		add_settings_field( 'cache_duration', 'Transient Cache Duration (hours)', array( $this, 'cache_duration_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
		add_settings_field( 'ga4_view_promotion_datalayer', 'GA4 view_promotion (dataLayer)', array( $this, 'ga4_view_promotion_datalayer_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
	}

	/**
	 * Sanitize the input from the settings page.
	 *
	 * @since    1.0.0
	 * @since    1.1.0 Added sanitizator of transient caching field
	 * @param array<string,mixed> $input Raw options payload.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$sanitized_input  = array();
		$existing_options = get_option( 'go4taste_ads_options', array() );
		if ( isset( $input['api_type'] ) ) {
			$sanitized_input['api_type'] = sanitize_text_field( $input['api_type'] );
		}
		if ( isset( $input['api_url'] ) ) {
			$sanitized_input['api_url'] = esc_url_raw( $input['api_url'] );
		}
		if ( isset( $input['p_parameter_value'] ) ) {
			$sanitized_input['p_parameter_value'] = sanitize_text_field( $input['p_parameter_value'] );
		}
		if ( isset( $input['prestashop_webservice_api_key'] ) ) {
			$submitted_key = trim( (string) $input['prestashop_webservice_api_key'] );
			if ( '' !== $submitted_key ) {
				$sanitized_input['prestashop_webservice_api_key'] = sanitize_text_field( $submitted_key );
			} elseif ( isset( $existing_options['prestashop_webservice_api_key'] ) ) {
				$sanitized_input['prestashop_webservice_api_key'] = sanitize_text_field( $existing_options['prestashop_webservice_api_key'] );
			}
		}
		if ( isset( $input['prestashop_lang'] ) ) {
			$sanitized_input['prestashop_lang'] = sanitize_text_field( $input['prestashop_lang'] );
		}
		if ( isset( $input['prestashop_tax_country_iso'] ) ) {
			$tax_country_iso = strtoupper( trim( (string) sanitize_text_field( $input['prestashop_tax_country_iso'] ) ) );
			if ( preg_match( '/^[A-Z]{2}$/', $tax_country_iso ) ) {
				$sanitized_input['prestashop_tax_country_iso'] = $tax_country_iso;
			} else {
				$sanitized_input['prestashop_tax_country_iso'] = 'PL';
			}
		}
		if ( isset( $input['fallback_product_id'] ) ) {
			$sanitized_input['fallback_product_id'] = sanitize_text_field( $input['fallback_product_id'] );
		}
		if ( isset( $input['algolia_app_id'] ) ) {
			$sanitized_input['algolia_app_id'] = sanitize_text_field( $input['algolia_app_id'] );
		}
		if ( isset( $input['algolia_api_key'] ) ) {
			$sanitized_input['algolia_api_key'] = sanitize_text_field( $input['algolia_api_key'] );
		}
		if ( isset( $input['cache_duration'] ) ) {
			$sanitized_input['cache_duration'] = intval( $input['cache_duration'] );
		}

		$sanitized_input['ga4_view_promotion_datalayer'] = isset( $input['ga4_view_promotion_datalayer'] ) ? 1 : 0;

		return $sanitized_input;
	}

	/**
	 * Callback function for the api_type field.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function api_type_callback() {
		$options  = get_option( 'go4taste_ads_options' );
		$api_type = isset( $options['api_type'] ) ? esc_attr( $options['api_type'] ) : '';
		?>
		<select id="api_type" name="go4taste_ads_options[api_type]">
			<option value="prestashop_1_4" <?php selected( $api_type, 'prestashop_1_4' ); ?>>PrestaShop 1.4</option>
			<option value="prestashop_1_5" <?php selected( $api_type, 'prestashop_1_5' ); ?>>PrestaShop 1.5</option>
			<option value="woocommerce" <?php selected( $api_type, 'woocommerce' ); ?>>WooCommerce</option>
			<option value="prestashop_webservice_api" <?php selected( $api_type, 'prestashop_webservice_api' ); ?>>Prestashop Webservice API</option>
			<option value="custom" <?php selected( $api_type, 'custom' ); ?>>Custom API</option>
		</select>
		<?php
	}

	/**
	 * Callback function for the API URL field.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function api_url_callback() {
		$options = get_option( 'go4taste_ads_options' );
		$api_url = isset( $options['api_url'] ) ? esc_attr( $options['api_url'] ) : '';
		echo '<input type="text" id="api_url" name="go4taste_ads_options[api_url]" value="' . $api_url . '" />';
		echo '<p class="description">Podaj URL sklepu PrestaShop (bez /api). Endpoint /api jest dolaczany automatycznie w kodzie.</p>';
	}

	/**
	 * Callback function for the "p" parameter field.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function p_parameter_callback() {
		$options = get_option( 'go4taste_ads_options' );
		$p_value = isset( $options['p_parameter_value'] ) ? esc_attr( $options['p_parameter_value'] ) : '';
		echo '<input type="password" id="p_parameter_value" name="go4taste_ads_options[p_parameter_value]" value="' . $p_value . '" />';
	}

	/**
	 * Callback function for the PrestaShop Webservice API key field.
	 *
	 * @since    1.2.0
	 * @return void
	 */
	public function prestashop_webservice_api_key_callback() {
		$options              = get_option( 'go4taste_ads_options' );
		$api_type             = isset( $options['api_type'] ) ? sanitize_text_field( $options['api_type'] ) : '';
		$api_url              = isset( $options['api_url'] ) ? esc_url_raw( $options['api_url'] ) : '';
		$stored_key           = isset( $options['prestashop_webservice_api_key'] ) ? sanitize_text_field( $options['prestashop_webservice_api_key'] ) : '';
		$tax_country_iso      = isset( $options['prestashop_tax_country_iso'] ) ? strtoupper( sanitize_text_field( $options['prestashop_tax_country_iso'] ) ) : 'PL';
		$permissions_report_html = '';

		if ( 'prestashop_webservice_api' === $api_type && '' !== trim( (string) $api_url ) && '' !== trim( (string) $stored_key ) ) {
			$api_fetcher             = new Go4Taste_Ads_API_Fetcher();
			$cached_report           = $api_fetcher->get_cached_prestashop_webservice_permissions_report( $api_url, $stored_key, $tax_country_iso );
			$permissions_report_html = $this->render_prestashop_permissions_report_html( $cached_report );
		}

		echo '<input type="password" id="prestashop_webservice_api_key" name="go4taste_ads_options[prestashop_webservice_api_key]" value="" autocomplete="new-password" />';
		echo '<p class="description">Dla bezpieczenstwa klucz nie jest wyswietlany. Wpisz nowy klucz tylko gdy chcesz go zaktualizowac.</p>';
		echo '<p class="description"><strong>Wymagane uprawnienia klucza Webservice (GET/READ):</strong> products, taxes, tax_rules, countries.</p>';

		if ( 'prestashop_webservice_api' !== $api_type ) {
			echo '<p class="description">Weryfikacja uprawnien jest aktywna po wybraniu API Type: Prestashop Webservice API.</p>';
			return;
		}

		if ( '' === trim( (string) $api_url ) || '' === trim( (string) $stored_key ) ) {
			echo '<p class="description" style="color:#a00;">Aby zweryfikowac uprawnienia, uzupelnij API URL i zapisz klucz Webservice.</p>';
			return;
		}

		echo '<p><button type="button" class="button" id="g4t-test-api-permissions">Test API Permissions</button></p>';
		echo '<div id="g4t-api-permissions-result">' . $permissions_report_html . '</div>';
	}

	/**
	 * Callback function for the prestashop lang field.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function prestashop_lang_callback() {
		$options          = get_option( 'go4taste_ads_options' );
		$prestashop_lang  = isset( $options['prestashop_lang'] ) ? esc_attr( $options['prestashop_lang'] ) : '';
		echo '<input type="text" id="prestashop_lang" name="go4taste_ads_options[prestashop_lang]" value="' . $prestashop_lang . '" />';
	}

	/**
	 * Callback function for the tax country ISO field.
	 *
	 * @since    1.2.0
	 * @return void
	 */
	public function prestashop_tax_country_iso_callback() {
		$options         = get_option( 'go4taste_ads_options' );
		$tax_country_iso = isset( $options['prestashop_tax_country_iso'] ) ? esc_attr( $options['prestashop_tax_country_iso'] ) : 'PL';

		echo '<input type="text" id="prestashop_tax_country_iso" name="go4taste_ads_options[prestashop_tax_country_iso]" value="' . $tax_country_iso . '" maxlength="2" style="width:80px; text-transform:uppercase;" />';
		echo '<p class="description">Dwuliterowy kod ISO kraju do mapowania reguly VAT (np. PL, DE, CZ). Domyslnie: PL.</p>';
	}

	/**
	 * Callback function for the fallback product ID field.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function fallback_product_id_callback() {
		$options     = get_option( 'go4taste_ads_options' );
		$fallback_id = isset( $options['fallback_product_id'] ) ? esc_attr( $options['fallback_product_id'] ) : '';
		echo '<input type="text" id="fallback_product_id" name="go4taste_ads_options[fallback_product_id]" value="' . $fallback_id . '" />';
	}

	/**
	 * Callback function for the Algolia App ID field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function algolia_app_id_callback() {
		$options         = get_option( 'go4taste_ads_options' );
		$algolia_app_id  = isset( $options['algolia_app_id'] ) ? esc_attr( $options['algolia_app_id'] ) : '';
		echo '<input type="text" id="algolia_app_id" name="go4taste_ads_options[algolia_app_id]" value="' . $algolia_app_id . '" />';
	}

	/**
	 * Callback function for the Algolia Search-Only API Key field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function algolia_api_key_callback() {
		$options         = get_option( 'go4taste_ads_options' );
		$algolia_api_key = isset( $options['algolia_api_key'] ) ? esc_attr( $options['algolia_api_key'] ) : '';
		echo '<input type="text" id="algolia_api_key" name="go4taste_ads_options[algolia_api_key]" value="' . $algolia_api_key . '" />';
	}

	/**
	 * Callback function for cache duration field.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function cache_duration_callback() {
		$options        = get_option( 'go4taste_ads_options' );
		$cache_duration = isset( $options['cache_duration'] ) ? esc_attr( $options['cache_duration'] ) : '0';
		echo '<input type="number" id="cache_duration" name="go4taste_ads_options[cache_duration]" value="' . $cache_duration . '" min="0" />';
		echo '<p class="description">Set to 0 to disable caching, or specify the number of hours for cache duration.</p>';
	}

	/**
	 * Callback function for dataLayer view_promotion toggle.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function ga4_view_promotion_datalayer_callback() {
		$options = get_option( 'go4taste_ads_options' );
		$enabled = ! empty( $options['ga4_view_promotion_datalayer'] ) ? 1 : 0;

		echo '<label for="ga4_view_promotion_datalayer">';
		echo '<input type="checkbox" id="ga4_view_promotion_datalayer" name="go4taste_ads_options[ga4_view_promotion_datalayer]" value="1" ' . checked( 1, $enabled, false ) . ' /> ';
		echo 'Generuj dataLayer.push(event: view_promotion) dla bloku inline-banner';
		echo '</label>';
		echo '<p class="description">Tryb dataLayer-only: atrybuty data-* dla promocji nie sa renderowane w markupu przycisku.</p>';
	}
}
