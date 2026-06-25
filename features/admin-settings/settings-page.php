<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin settings page feature slice.
 */
class Go4Taste_Ads_Admin_Settings {

        /**
         * Callback for Algolia Admin API Key field.
         *
         * @since 1.3.0
         * @return void
         */
        public function algolia_admin_api_key_callback() {
            // For security, do not prefill the value.
            echo '<input type="password" id="algolia_admin_api_key" name="go4taste_ads_options[algolia_admin_api_key]" value="" autocomplete="new-password" />';
            echo '<p class="description">This key is required for backend sync (write access). For security, it is not displayed. Enter a new key only to update.</p>';
        }
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
        $api_url         = isset( $options['api_url'] ) ? esc_url_raw( $options['api_url'] ) : '';
        $stored_key      = isset( $options['prestashop_webservice_api_key'] ) ? sanitize_text_field( $options['prestashop_webservice_api_key'] ) : '';
        $tax_country_iso = isset( $options['prestashop_tax_country_iso'] ) ? strtoupper( sanitize_text_field( $options['prestashop_tax_country_iso'] ) ) : 'PL';

        if ( '' === trim( (string) $api_url ) || '' === trim( (string) $stored_key ) ) {
            wp_send_json_error( array( 'message' => 'Uzupelnij API URL i zapisz klucz Webservice.' ), 400 );
        }

        $api_fetcher = g4t_core_get_api_fetcher();
        if ( ! $api_fetcher ) {
            wp_send_json_error( array( 'message' => 'Brak klasy API fetchera. Sprawdz logi pluginu.' ), 500 );
        }

        $permissions_report = $api_fetcher->verify_prestashop_webservice_permissions( $api_url, $stored_key, $tax_country_iso );

        wp_send_json_success(
            array(
                'report' => $permissions_report,
                'html'   => $this->render_prestashop_permissions_report_html( $permissions_report ),
            )
        );
    }

    /**
     * AJAX: clear all Algolia product soft-cache transients.
     *
     * @since 1.3.0
     * @return void
     */
    public function ajax_clear_algolia_soft_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_clear_soft_cache_ajax', 'nonce' );

        require_once dirname( __DIR__ ) . '/algolia-ad-server/soft-cache.php';
        if ( ! function_exists( 'g4t_algolia_ad_server_clear_all_cache' ) ) {
            wp_send_json_error( array( 'message' => 'Cache clear function is not available.' ), 500 );
        }

        $result = g4t_algolia_ad_server_clear_all_cache();
        if ( ! is_array( $result ) || empty( $result['success'] ) ) {
            $message = is_array( $result ) && ! empty( $result['error'] ) ? (string) $result['error'] : 'Cache clear failed.';
            wp_send_json_error( array( 'message' => $message ), 500 );
        }

        $counts = array(
            'soft_count' => 0,
            'hard_count' => 0,
        );
        if ( function_exists( 'g4t_algolia_ad_server_get_cache_counts' ) ) {
            $count_result = g4t_algolia_ad_server_get_cache_counts();
            if ( is_array( $count_result ) && ! empty( $count_result['success'] ) ) {
                $counts['soft_count'] = isset( $count_result['soft_count'] ) ? (int) $count_result['soft_count'] : 0;
                $counts['hard_count'] = isset( $count_result['hard_count'] ) ? (int) $count_result['hard_count'] : 0;
            }
        }

        wp_send_json_success(
            array(
                'message'      => __( 'Soft cache cleared successfully.', 'go4taste-ads' ),
                'deleted_rows' => isset( $result['deleted_rows'] ) ? (int) $result['deleted_rows'] : 0,
                'executed_at'  => isset( $result['executed_at'] ) ? (string) $result['executed_at'] : '',
                'soft_count'   => $counts['soft_count'],
                'hard_count'   => $counts['hard_count'],
            )
        );
    }

    /**
     * AJAX: clear legacy PrestaShop product transient leftovers.
     *
     * @since 1.3.1
     * @return void
     */
    public function ajax_clear_legacy_presta_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_clear_legacy_presta_cache_ajax', 'nonce' );

        global $wpdb;

        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! isset( $wpdb->options ) ) {
            wp_send_json_error( array( 'message' => 'Database connection is not available.' ), 500 );
        }

        $value_like   = $wpdb->esc_like( '_transient_product_data_prestashop_webservice_api_' ) . '%';
        $timeout_like = $wpdb->esc_like( '_transient_timeout_product_data_prestashop_webservice_api_' ) . '%';

        $query = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $value_like,
            $timeout_like
        );

        if ( ! is_string( $query ) || '' === $query ) {
            wp_send_json_error( array( 'message' => 'Failed to prepare cache cleanup query.' ), 500 );
        }

        $deleted_rows = $wpdb->query( $query );
        if ( false === $deleted_rows ) {
            wp_send_json_error( array( 'message' => 'Failed to execute cache cleanup query.' ), 500 );
        }

        wp_send_json_success(
            array(
                'message'      => __( 'Legacy PrestaShop cache entries cleaned successfully.', 'go4taste-ads' ),
                'deleted_rows' => (int) $deleted_rows,
                'executed_at'  => current_time( 'mysql' ),
            )
        );
    }

    /**
     * AJAX: get Algolia soft/hard cache counts on demand.
     *
     * @since 1.3.0
     * @return void
     */
    public function ajax_get_algolia_cache_counts() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_get_cache_counts_ajax', 'nonce' );

        require_once dirname( __DIR__ ) . '/algolia-ad-server/soft-cache.php';
        if ( ! function_exists( 'g4t_algolia_ad_server_get_cache_counts' ) ) {
            wp_send_json_error( array( 'message' => 'Cache count function is not available.' ), 500 );
        }

        $count_result = g4t_algolia_ad_server_get_cache_counts();
        if ( ! is_array( $count_result ) || empty( $count_result['success'] ) ) {
            $message = is_array( $count_result ) && ! empty( $count_result['error'] ) ? (string) $count_result['error'] : 'Cache count failed.';
            wp_send_json_error( array( 'message' => $message ), 500 );
        }

        wp_send_json_success(
            array(
                'soft_count'  => isset( $count_result['soft_count'] ) ? (int) $count_result['soft_count'] : 0,
                'hard_count'  => isset( $count_result['hard_count'] ) ? (int) $count_result['hard_count'] : 0,
                'executed_at' => isset( $count_result['executed_at'] ) ? (string) $count_result['executed_at'] : '',
            )
        );
    }

    /**
     * AJAX: clear Algolia soft cache for products used on the current page.
     *
     * @since 1.3.0
     * @return void
     */
    public function ajax_clear_current_page_algolia_soft_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_clear_current_page_soft_cache_ajax', 'nonce' );

        $product_ids = isset( $_POST['product_ids'] ) ? wp_unslash( $_POST['product_ids'] ) : array();
        if ( ! is_array( $product_ids ) ) {
            $product_ids = array();
        }

        $normalized_product_ids = array();
        foreach ( $product_ids as $product_id ) {
            $product_id = trim( sanitize_text_field( (string) $product_id ) );
            if ( '' !== $product_id ) {
                $normalized_product_ids[ $product_id ] = $product_id;
            }
        }

        if ( empty( $normalized_product_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No banner products were detected for this page.', 'go4taste-ads' ) ), 400 );
        }

        require_once dirname( __DIR__ ) . '/algolia-ad-server/soft-cache.php';
        if ( ! function_exists( 'g4t_algolia_ad_server_clear_product_cache' ) ) {
            wp_send_json_error( array( 'message' => 'Selective cache clear function is not available.' ), 500 );
        }

        $result = g4t_algolia_ad_server_clear_product_cache( array_values( $normalized_product_ids ) );
        if ( ! is_array( $result ) || empty( $result['success'] ) ) {
            $message = is_array( $result ) && ! empty( $result['error'] ) ? (string) $result['error'] : 'Selective cache clear failed.';
            wp_send_json_error( array( 'message' => $message ), 500 );
        }

        $counts = array(
            'soft_count' => 0,
            'hard_count' => 0,
        );
        if ( function_exists( 'g4t_algolia_ad_server_get_cache_counts' ) ) {
            $count_result = g4t_algolia_ad_server_get_cache_counts();
            if ( is_array( $count_result ) && ! empty( $count_result['success'] ) ) {
                $counts['soft_count'] = isset( $count_result['soft_count'] ) ? (int) $count_result['soft_count'] : 0;
                $counts['hard_count'] = isset( $count_result['hard_count'] ) ? (int) $count_result['hard_count'] : 0;
            }
        }

        wp_send_json_success(
            array(
                'message'             => __( 'Current page soft cache cleared successfully.', 'go4taste-ads' ),
                'deleted_rows'        => isset( $result['deleted_rows'] ) ? (int) $result['deleted_rows'] : 0,
                'executed_at'         => isset( $result['executed_at'] ) ? (string) $result['executed_at'] : '',
                'cleared_product_ids' => isset( $result['cleared_product_ids'] ) && is_array( $result['cleared_product_ids'] ) ? array_values( $result['cleared_product_ids'] ) : array(),
                'soft_count'          => $counts['soft_count'],
                'hard_count'          => $counts['hard_count'],
            )
        );
    }

    /**
     * Resolve product IDs used by banners on the current page.
     *
     * @since 1.3.0
     * @return array<int,string>
     */
    public function get_current_page_product_ids() {
        $product_ids = apply_filters( 'g4t_ads_current_page_product_ids', array() );

        if ( ! is_array( $product_ids ) ) {
            return array();
        }

        $normalized_product_ids = array();
        foreach ( $product_ids as $product_id ) {
            $product_id = trim( (string) $product_id );
            if ( '' !== $product_id ) {
                $normalized_product_ids[ $product_id ] = $product_id;
            }
        }

        return array_values( $normalized_product_ids );
    }

    /**
     * Register G4TAds actions in the admin bar.
     *
     * @since 1.3.0
     * @param WP_Admin_Bar $admin_bar Admin bar instance.
     * @return void
     */
    public function add_admin_bar_menu( $admin_bar ) {
        if ( ! $admin_bar instanceof WP_Admin_Bar || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $current_page_product_ids = $this->get_current_page_product_ids();
        $current_page_count       = count( $current_page_product_ids );

        $admin_bar->add_node(
            array(
                'id'    => 'g4t-ads-admin-bar',
                'title' => __( 'G4TAds', 'go4taste-ads' ),
                'href'  => false,
            )
        );

        $admin_bar->add_node(
            array(
                'id'     => 'g4t-ads-admin-bar-clear-all',
                'parent' => 'g4t-ads-admin-bar',
                'title'  => __( 'Clear All Soft Cache', 'go4taste-ads' ),
                'href'   => '#',
                'meta'   => array(
                    'class'       => 'g4t-ads-admin-bar-action',
                    'data-action' => 'clear-all-soft-cache',
                ),
            )
        );

        $admin_bar->add_node(
            array(
                'id'     => 'g4t-ads-admin-bar-clear-page',
                'parent' => 'g4t-ads-admin-bar',
                'title'  => sprintf(
                    /* translators: %d: number of detected banner products on the current page. */
                    __( 'Clear Soft Cache from this page (%d)', 'go4taste-ads' ),
                    (int) $current_page_count
                ),
                'href'   => '#',
                'meta'   => array(
                    'class'       => 'g4t-ads-admin-bar-action',
                    'data-action' => 'clear-current-page-soft-cache',
                ),
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

            <h2><?php _e( 'Cache Maintenance', 'go4taste-ads' ); ?></h2>
            <p><?php _e( 'Use this action to clear all Algolia banner product soft cache transients immediately.', 'go4taste-ads' ); ?></p>
            <p>
                <button type="button" class="button button-secondary" id="g4t-clear-soft-cache"><?php _e( 'Clear All Soft Cache', 'go4taste-ads' ); ?></button>
                <button type="button" class="button" id="g4t-clean-legacy-presta-cache"><?php _e( 'Clean Legacy PrestaShop Cache', 'go4taste-ads' ); ?></button>
                <button type="button" class="button" id="g4t-count-soft-hard-cache" style="margin-left:8px;"><?php _e( 'Count Cached Products', 'go4taste-ads' ); ?></button>
            </p>
            <div id="g4t-clear-soft-cache-result" class="notice inline" style="display:none;"></div>
            <div id="g4t-clean-legacy-presta-cache-result" class="notice inline" style="display:none;"></div>
            <div id="g4t-cache-count-result" class="notice inline" style="display:none;"></div>

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

        add_settings_field( 'api_url', 'API URL', array( $this, 'api_url_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
        add_settings_field( 'prestashop_webservice_api_key', 'PrestaShop Webservice API Key', array( $this, 'prestashop_webservice_api_key_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
        add_settings_field( 'prestashop_lang', 'PrestaShop Language', array( $this, 'prestashop_lang_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
        add_settings_field( 'prestashop_tax_country_iso', 'PrestaShop Tax Country ISO', array( $this, 'prestashop_tax_country_iso_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
        add_settings_field( 'fallback_product_id', 'Fallback Product ID', array( $this, 'fallback_product_id_callback' ), 'go4taste-ads', 'go4taste_ads_setting_section' );
        add_settings_field( 'algolia_app_id', 'Algolia App ID', array( $this, 'algolia_app_id_callback' ), 'algolia_settings', 'algolia_settings' );
        add_settings_field( 'algolia_api_key', 'Algolia Search-Only API Key', array( $this, 'algolia_api_key_callback' ), 'algolia_settings', 'algolia_settings' );
        add_settings_field( 'algolia_admin_api_key', 'Algolia Admin API Key', array( $this, 'algolia_admin_api_key_callback' ), 'algolia_settings', 'algolia_settings' );
        add_settings_field( 'algolia_soft_cache_ttl_hours', 'Algolia Soft Cache TTL (hours)', array( $this, 'algolia_soft_cache_ttl_hours_callback' ), 'algolia_settings', 'algolia_settings' );
        add_settings_field( 'algolia_hard_cache_ttl_days', 'Algolia Hard Cache TTL (days)', array( $this, 'algolia_hard_cache_ttl_days_callback' ), 'algolia_settings', 'algolia_settings' );
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
        $sanitized_input['api_type'] = 'prestashop_webservice_api';
        if ( isset( $input['api_url'] ) ) {
            $sanitized_input['api_url'] = esc_url_raw( $input['api_url'] );
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
        if ( isset( $input['algolia_admin_api_key'] ) ) {
            $submitted_admin_key = trim( (string) $input['algolia_admin_api_key'] );
            if ( '' !== $submitted_admin_key ) {
                $sanitized_input['algolia_admin_api_key'] = sanitize_text_field( $submitted_admin_key );
            } elseif ( isset( $existing_options['algolia_admin_api_key'] ) ) {
                // Field is write-only (rendered empty); preserve existing key when not re-entered.
                $sanitized_input['algolia_admin_api_key'] = sanitize_text_field( $existing_options['algolia_admin_api_key'] );
            }
        }
        if ( isset( $input['algolia_soft_cache_ttl_hours'] ) ) {
            $sanitized_input['algolia_soft_cache_ttl_hours'] = max( 1, intval( $input['algolia_soft_cache_ttl_hours'] ) );
        }
        if ( isset( $input['algolia_hard_cache_ttl_days'] ) ) {
            $sanitized_input['algolia_hard_cache_ttl_days'] = max( 1, intval( $input['algolia_hard_cache_ttl_days'] ) );
        }

        $sanitized_input['ga4_view_promotion_datalayer'] = isset( $input['ga4_view_promotion_datalayer'] ) ? 1 : 0;

        return $sanitized_input;
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
     * Callback function for the PrestaShop Webservice API key field.
     *
     * @since    1.2.0
     * @return void
     */
    public function prestashop_webservice_api_key_callback() {
        $options              = get_option( 'go4taste_ads_options' );
        $api_url              = isset( $options['api_url'] ) ? esc_url_raw( $options['api_url'] ) : '';
        $stored_key           = isset( $options['prestashop_webservice_api_key'] ) ? sanitize_text_field( $options['prestashop_webservice_api_key'] ) : '';
        $tax_country_iso      = isset( $options['prestashop_tax_country_iso'] ) ? strtoupper( sanitize_text_field( $options['prestashop_tax_country_iso'] ) ) : 'PL';
        $permissions_report_html = '';

        if ( '' !== trim( (string) $api_url ) && '' !== trim( (string) $stored_key ) ) {
            $api_fetcher = g4t_core_get_api_fetcher();
            if ( $api_fetcher ) {
                $cached_report           = $api_fetcher->get_cached_prestashop_webservice_permissions_report( $api_url, $stored_key, $tax_country_iso );
                $permissions_report_html = $this->render_prestashop_permissions_report_html( $cached_report );
            } else {
                $permissions_report_html = '<p class="description" style="color:#a00;">Brak klasy API fetchera. Sprawdz logi pluginu.</p>';
            }
        }

        echo '<input type="password" id="prestashop_webservice_api_key" name="go4taste_ads_options[prestashop_webservice_api_key]" value="" autocomplete="new-password" />';
        echo '<p class="description">Dla bezpieczenstwa klucz nie jest wyswietlany. Wpisz nowy klucz tylko gdy chcesz go zaktualizowac.</p>';
        echo '<p class="description"><strong>Wymagane uprawnienia klucza Webservice (GET/READ):</strong> products, taxes, tax_rules, countries.</p>';

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
     * Callback function for Algolia soft cache TTL field.
     *
     * @since 1.3.0
     * @return void
     */
    public function algolia_soft_cache_ttl_hours_callback() {
        $options   = get_option( 'go4taste_ads_options' );
        $ttl_hours = isset( $options['algolia_soft_cache_ttl_hours'] ) ? esc_attr( $options['algolia_soft_cache_ttl_hours'] ) : '24';
        echo '<input type="number" id="algolia_soft_cache_ttl_hours" name="go4taste_ads_options[algolia_soft_cache_ttl_hours]" value="' . $ttl_hours . '" min="1" />';
        echo '<p class="description">Soft TTL for Algolia banner payload cache in hours (minimum 1).</p>';
    }

    /**
     * Callback function for Algolia hard cache TTL field.
     *
     * @since 1.3.0
     * @return void
     */
    public function algolia_hard_cache_ttl_days_callback() {
        $options  = get_option( 'go4taste_ads_options' );
        $ttl_days = isset( $options['algolia_hard_cache_ttl_days'] ) ? esc_attr( $options['algolia_hard_cache_ttl_days'] ) : '7';
        echo '<input type="number" id="algolia_hard_cache_ttl_days" name="go4taste_ads_options[algolia_hard_cache_ttl_days]" value="' . $ttl_days . '" min="1" />';
        echo '<p class="description">Hard TTL for Algolia banner payload transient in days (minimum 1).</p>';
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