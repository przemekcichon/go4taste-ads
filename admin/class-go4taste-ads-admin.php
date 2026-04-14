<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://go4taste.pl/techlab
 * @since      1.0.0
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueueing admin-specific
 * stylesheets and JavaScript, registering meta boxes, and managing widgets.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/admin
 * @author     Go4Taste Techlab <przemekcichon@gmail.com>
 */

class Go4Taste_Ads_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Include the specific widget class for the sidebar regular widget
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-go4taste-ads-sidebar-regular-widget.php';
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/go4taste-ads-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $admin_script_path = plugin_dir_path( __FILE__ ) . 'js/go4taste-ads-admin.js';
        $admin_script_version = file_exists( $admin_script_path ) ? (string) filemtime( $admin_script_path ) : $this->version;

        // Enqueue Algolia's instantsearch.js and search client
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

        // Enqueue the general JavaScript file for the admin area
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/go4taste-ads-admin.js',
            array('jquery'),
            $admin_script_version,
            true
        );

        wp_localize_script(
            $this->plugin_name,
            'g4tAdsAdmin',
            array(
                'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
                'migrationNonce'         => wp_create_nonce( 'g4t_migrate_legacy_banners_ajax' ),
                'migrationStartAction'   => 'g4t_migration_start',
                'migrationBatchAction'   => 'g4t_migration_batch',
                'migrationStatusAction'  => 'g4t_migration_status',
                'migrationUnlockAction'  => 'g4t_migration_unlock',
                'syncNonce'              => wp_create_nonce( 'g4t_sync_products_ajax' ),
                'syncStartAction'        => 'g4t_sync_start',
                'syncBatchAction'        => 'g4t_sync_batch',
                'syncStatusAction'       => 'g4t_sync_status',
                'syncUnlockAction'       => 'g4t_sync_unlock',
                'permissionsNonce'       => wp_create_nonce( 'g4t_test_permissions_ajax' ),
                'permissionsTestAction'  => 'g4t_test_prestashop_permissions',
            )
        );
    }

    /**
     * Register the public stylesheets for the admin area of post and new post.
     *
     * @since    1.0.0
     */
    public function enqueue_admin_preview_styles() {
        global $pagenow;
    
        // Only enqueue the styles on the post edit screen
        if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
            wp_enqueue_style(
                'go4taste-ads-public',
                plugin_dir_url(__FILE__) . '../public/css/go4taste-ads-public.css',
                array(),
                $this->version,
                'all'
            );
        }
    }
    
    

    /**
     * Registers the meta box for the Go4Taste Ads Widget.
     *
     * @since    1.0.0
     */
    public function add_meta_box() {
        add_meta_box(
            'go4taste_ads_sidebar_regular_widget_meta_box', // Unique ID
            'Go4Taste Ads Widget',                                   // Box title
            array($this, 'render_meta_box'),                         // Content callback, must be of type callable
            'post',                                                  // Post type
            'normal',                                                // Context
            'high'                                                   // Priority
        );
    }

    /**
     * Renders the meta box content for the Go4Taste Ads Widget.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object where the meta box is displayed.
     */
    public function render_meta_box($post) {
        // Add nonce for security and authentication
        wp_nonce_field('go4taste_ads_sidebar_regular_widget_nonce_action', 'go4taste_ads_sidebar_regular_widget_nonce');

        // Retrieve existing value from the database
        $product_id = get_post_meta($post->ID, '_g4t_a_sidebar_regular_widget_ps_product_id', true);

        // Retrieve Algolia App ID and Search-Only API Key from settings
        $options = get_option('go4taste_ads_options');
        $algolia_app_id = isset($options['algolia_app_id']) ? sanitize_text_field($options['algolia_app_id']) : '';
        $algolia_api_key = isset($options['algolia_api_key']) ? sanitize_text_field($options['algolia_api_key']) : '';

        // Display the form field for Product ID (hidden)
        echo '<input type="hidden" id="g4t_a_sidebar_regular_widget_ps_product_id" name="g4t_a_sidebar_regular_widget_ps_product_id" value="' . esc_attr($product_id) . '" size="25" />';

        // Display the Algolia-powered search input box
        echo '<div id="algolia-searchbox"></div>';
        echo '<div id="selected-product-container"></div>';

        // Placeholder for displaying the ad preview
        echo '<div id="g4t-ad-preview" class="g4t-ad-preview"></div>';

        // Inline script to handle Algolia search and AJAX request for preview
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize Algolia Search Client
            const searchClient = algoliasearch('<?php echo esc_js( $algolia_app_id ); ?>', '<?php echo esc_js( $algolia_api_key ); ?>');

            const search = instantsearch({
                indexName: 'go4taste_product_list', // Algolia index name
                searchClient,
                searchFunction(helper) {
                    if (helper.state.query === '') {
                        return;
                    }
                    helper.search();
                }
            });

            // Initialize the index where the products are stored
            const index = searchClient.initIndex( 'go4taste_product_list' );

            // Add the searchBox widget
            search.addWidget(
                instantsearch.widgets.searchBox({
                    container: '#algolia-searchbox',
                    placeholder: 'Szukaj po nazwie lub identyfikatorze produktu ...',
                    showReset: false,
                    showSubmit: false,
                })
            );

            // Create a custom hits widget to display suggestions as a dropdown
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

            // Handle Product Selection
            $(document).on('click', '.algolia-suggestion', function() {
                const productId = $(this).data('id');
                const productName = $(this).find('strong').text();

                // Save product ID to the hidden input field
                $('#g4t_a_sidebar_regular_widget_ps_product_id').val(productId);

                // Display the selected product in the searchbox
                displaySelectedProduct(productId)

                // Hide the searchbox once a product is selected
                $('#algolia-searchbox input').val('');

                // Automatically fetch the product data and update the preview
                fetchProductPreview(productId);
            });

            // Handle Product Removal
            $(document).on('click', '.remove-selected-product', function() {
                // Clear the hidden input field and the displayed product
                $('#g4t_a_sidebar_regular_widget_ps_product_id').val('');
                $('#selected-product-container').html('');
            });

            // Function to show selected product
            function displaySelectedProduct(productId) {
                index.getObject(productId)
                    .then(function(product) {
                        // Display the selected product in the searchbox
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

            // Function to fetch and update product preview
            function fetchProductPreview(productID) {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'g4t_fetch_product_preview',
                        product_id: productID,
                        nonce: '<?php echo esc_js( wp_create_nonce( 'g4t_ad_preview_nonce' ) ); ?>'
                    },
                    success: function(response) {
                        $('#g4t-ad-preview').html(response.data);
                    },
                    error: function() {
                        $('#g4t-ad-preview').html('<p>Error fetching product data.</p>');
                    }
                });
            }

            // Automatically load the preview for the currently saved product when the editor loads
            var savedProductID = $('#g4t_a_sidebar_regular_widget_ps_product_id').val();
            if (savedProductID) {
                // Fetch the product data for the saved product and display the preview
                fetchProductPreview(savedProductID);
                displaySelectedProduct(savedProductID);
            }

            // Start InstantSearch.js
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

    /**
     * AJAX handler for fetching product preview.
     *
     * @since    1.0.0
     */
    public function fetch_product_preview() {
        // Verify the nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'g4t_ad_preview_nonce')) {
            wp_send_json_error('Invalid nonce.');
        }

        // Get the product ID from the AJAX request
        $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';
        
        // If no product ID is provided, return an error message
        if (empty($product_id)) {
            wp_send_json_error('No product ID provided.');
        }
        
        // Fetch additional options from the settings page
        $options = get_option('go4taste_ads_options');
        $p_value = isset($options['p_parameter_value']) ? sanitize_text_field($options['p_parameter_value']) : '';
        $lang = isset($options['prestashop_lang']) ? sanitize_text_field($options['prestashop_lang']) : 'en';
        $api_type = isset($options['api_type']) ? sanitize_text_field($options['api_type']) : 'prestashop_1_5';
        $api_url = isset($options['api_url']) ? esc_url($options['api_url']) : '';

        if ( empty($api_type) || empty($api_url) ) {
            wp_send_json_error( 'API configuration is missing' );
            return;
        }

        // Fetch product data via API (use the fetch_product_data() method)
        $plugin_instance = Go4Taste_Ads::get_instance();
        $product_data = $plugin_instance->fetch_product_data($product_id, $p_value, $lang, $api_type, $api_url);
        
        // Check if product data is available
        if (!$product_data) {
            wp_send_json_error('Failed to fetch product data.');
        }

        // Render the ad preview using the same template as the frontend
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/go4taste-ads-sidebar-regular-widget-public-display.php';
        $output = ob_get_clean();

        // Return the rendered preview
        wp_send_json_success($output);
    }

    /**
     * Render permissions report HTML for admin settings panel.
     *
     * @since 1.2.0
     * @param array<string,mixed>|false $permissions_report Report structure.
     * @return string
     */
    private function render_prestashop_permissions_report_html($permissions_report) {
        if (!is_array($permissions_report)) {
            return '<p class="description">Brak zapisanego wyniku testu uprawnien. Kliknij "Test API Permissions".</p>';
        }

        ob_start();

        $summary_color = !empty($permissions_report['ok']) ? '#188038' : '#a00';
        $summary_text = !empty($permissions_report['message']) ? (string) $permissions_report['message'] : 'Brak danych weryfikacji.';

        echo '<p class="description" style="color:' . esc_attr($summary_color) . ';"><strong>Status uprawnien:</strong> ' . esc_html($summary_text) . '</p>';

        if (!empty($permissions_report['checked_at'])) {
            echo '<p class="description">Ostatni test: ' . esc_html((string) $permissions_report['checked_at']) . '</p>';
        }

        if (!empty($permissions_report['resources']) && is_array($permissions_report['resources'])) {
            echo '<ul style="margin:6px 0 0 18px; list-style:disc;">';
            foreach ($permissions_report['resources'] as $resource_name => $resource_status) {
                $resource_ok = !empty($resource_status['ok']);
                $resource_color = $resource_ok ? '#188038' : '#a00';
                $code_suffix = '';
                if (isset($resource_status['http_code']) && (int) $resource_status['http_code'] > 0) {
                    $code_suffix = ' (HTTP ' . (int) $resource_status['http_code'] . ')';
                }

                $line = strtoupper((string) $resource_name) . ': ' . ((isset($resource_status['message']) && '' !== (string) $resource_status['message']) ? (string) $resource_status['message'] : ($resource_ok ? 'OK' : 'Brak uprawnienia')) . $code_suffix;
                echo '<li style="color:' . esc_attr($resource_color) . ';">' . esc_html($line) . '</li>';
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

        $options = get_option('go4taste_ads_options');
        $api_type = isset($options['api_type']) ? sanitize_text_field($options['api_type']) : '';
        $api_url = isset($options['api_url']) ? esc_url_raw($options['api_url']) : '';
        $stored_key = isset($options['prestashop_webservice_api_key']) ? sanitize_text_field($options['prestashop_webservice_api_key']) : '';
        $tax_country_iso = isset($options['prestashop_tax_country_iso']) ? strtoupper(sanitize_text_field($options['prestashop_tax_country_iso'])) : 'PL';

        if ('prestashop_webservice_api' !== $api_type) {
            wp_send_json_error(array('message' => 'Wybierz API Type: Prestashop Webservice API.'), 400);
        }

        if ('' === trim((string) $api_url) || '' === trim((string) $stored_key)) {
            wp_send_json_error(array('message' => 'Uzupelnij API URL i zapisz klucz Webservice.'), 400);
        }

        $api_fetcher = new Go4Taste_Ads_API_Fetcher();
        $permissions_report = $api_fetcher->verify_prestashop_webservice_permissions($api_url, $stored_key, $tax_country_iso);

        wp_send_json_success(
            array(
                'report' => $permissions_report,
                'html' => $this->render_prestashop_permissions_report_html($permissions_report),
            )
        );
    }

    /**
     * Saves the meta box data when the post is saved.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the post being saved.
     * @return   int                The post ID if the meta box data was not saved, otherwise void.
     */
    public function save_meta_box($post_id) {
        // Check if our nonce is set.
        if (!isset($_POST['go4taste_ads_sidebar_regular_widget_nonce'])) {
            return $post_id;
        }

        $nonce = $_POST['go4taste_ads_sidebar_regular_widget_nonce'];

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce, 'go4taste_ads_sidebar_regular_widget_nonce_action')) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        // Sanitize the user input.
        $product_id = sanitize_text_field($_POST['g4t_a_sidebar_regular_widget_ps_product_id']);

        // Update the meta field in the database.
        update_post_meta($post_id, '_g4t_a_sidebar_regular_widget_ps_product_id', $product_id);

        return $post_id;
    }

    /**
     * Retrieve the PrestaShop product ID from post meta.
     *
     * @since    1.0.0
     * @return   string|int    The product ID.
     */
    public function get_prestashop_product_id() {
		
        global $post;

        // Retrieve the PrestaShop product ID from the post meta
        $product_id = get_post_meta($post->ID, '_g4t_a_sidebar_regular_widget_ps_product_id', true);

        // Return the product ID
        return $product_id;
    }

    /**
     * Public function to signal that the product ID function is available.
     *
     * @since    1.0.0
     */
    public function load_product_id_function() {
        do_action('g4taste_product_ads_sidebar_regular_widget_product_id_function_loaded');
    }


    /**
     * Public function to register widgets.
     *
     * This method registers the Go4Taste Product Ads Sidebar Regular Widget.
     *
     * @since    1.0.0
     */
    public function register_widgets() {
        register_widget('Go4Taste_Ads_Sidebar_Regular_Widget');
    }

    /**
     * Registers the admin menu for the plugin settings.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            'Go4Taste Product Ads Settings',
            'Go4Taste Ads',
            'manage_options',
            'go4taste-ads',
            array($this, 'create_admin_page'),
            'dashicons-admin-generic',
            100
        );
    }

    /**
     * Display the plugin settings page.
     *
     * @since    1.0.0
     */
    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Go4Taste Ads Settings</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('go4taste_ads_options_group');
                do_settings_sections('go4taste-ads');
                do_settings_sections('algolia_settings'); // Section where the fields are registered
                submit_button();
                ?>
            </form>

            <hr>

            <h2><?php _e( 'Update Product IDs from Shortcode', 'go4taste-ads' ); ?></h2>
            <p><?php _e( 'Click the button below to update the product IDs based on the prestashop_data shortcode.', 'go4taste-ads' ); ?></p>
            <form method="post">
                <?php wp_nonce_field( 'go4taste_update_product_ids', 'go4taste_update_product_ids_nonce' ); ?>
                <input type="hidden" name="go4taste_update_action" value="update_product_ids">
                <button type="submit" class="button-primary"><?php _e( 'Update Product IDs', 'go4taste-ads' ); ?></button>
            </form>

            <?php if ( isset( $_POST['go4taste_update_action'] ) && $_POST['go4taste_update_action'] === 'update_product_ids' ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php $this->update_product_ids_from_shortcode(); ?></p>
                </div>
            <?php endif; ?>

            <hr>

            <h2><?php _e( 'Migrate Legacy Shortcode Banners to Inline Banners', 'go4taste-ads' ); ?></h2>
            <p><?php _e( 'Scans post content for old prestashop_* shortcode banners and converts them to go4taste-ads/inline-banner blocks.', 'go4taste-ads' ); ?></p>
            <form method="post" id="g4t-migration-form">
                <?php wp_nonce_field( 'go4taste_migrate_legacy_banners', 'go4taste_migrate_legacy_banners_nonce' ); ?>
                <input type="hidden" name="go4taste_update_action" value="migrate_legacy_banners">
                <p>
                    <label>
                        <input type="checkbox" name="go4taste_migrate_dry_run" value="1" checked>
                        <?php _e( 'Dry run (no database writes)', 'go4taste-ads' ); ?>
                    </label>
                </p>
                <p>
                    <label for="go4taste_migrate_limit"><?php _e( 'Limit posts (0 = all):', 'go4taste-ads' ); ?></label>
                    <input type="number" min="0" step="1" id="go4taste_migrate_limit" name="go4taste_migrate_limit" value="0" style="width:120px;">
                </p>
                <p>
                    <label for="go4taste_migrate_target_post"><?php _e( 'Target post (ID or slug, optional):', 'go4taste-ads' ); ?></label>
                    <input type="text" id="go4taste_migrate_target_post" name="go4taste_migrate_target_post" value="" placeholder="np. 123 lub moj-wpis" style="width:240px;">
                </p>
                <p>
                    <strong><?php _e( 'Post types:', 'go4taste-ads' ); ?></strong><br>
                    <label><input type="checkbox" name="go4taste_migrate_post_types[]" value="post" checked> <?php _e( 'Posts', 'go4taste-ads' ); ?></label><br>
                    <label><input type="checkbox" name="go4taste_migrate_post_types[]" value="page" checked> <?php _e( 'Pages', 'go4taste-ads' ); ?></label>
                </p>
                <p>
                    <strong><?php _e( 'Statuses:', 'go4taste-ads' ); ?></strong><br>
                    <label><input type="checkbox" name="go4taste_migrate_post_statuses[]" value="publish" checked> <?php _e( 'Publish', 'go4taste-ads' ); ?></label><br>
                    <label><input type="checkbox" name="go4taste_migrate_post_statuses[]" value="draft" checked> <?php _e( 'Draft', 'go4taste-ads' ); ?></label>
                </p>

                <p>
                    <label for="go4taste_migrate_batch_size"><?php _e( 'Batch size:', 'go4taste-ads' ); ?></label>
                    <input type="number" min="1" max="100" step="1" id="go4taste_migrate_batch_size" name="go4taste_migrate_batch_size" value="20" style="width:120px;">
                </p>

                <button type="button" class="button-secondary" id="g4t-migration-start"><?php _e( 'Run Migration', 'go4taste-ads' ); ?></button>
            </form>

            <div id="g4t-migration-progress" class="g4t-migration-progress" style="display:none;">
                <div class="g4t-migration-progress-bar-wrap">
                    <div id="g4t-migration-progress-bar" class="g4t-migration-progress-bar" style="width:0%;"></div>
                </div>
                <p id="g4t-migration-progress-text" class="g4t-migration-progress-text"></p>
                <ul id="g4t-migration-progress-stats" class="g4t-migration-progress-stats"></ul>
                <div id="g4t-migration-log" class="g4t-migration-log"></div>
            </div>

            <hr>

            <h2><?php _e('Sync Products to Algolia', 'go4taste-ads'); ?></h2>
            <p><?php _e('Click the button below to sync all products from the PrestaShop API to Algolia.', 'go4taste-ads'); ?></p>
            <form method="post" id="g4t-sync-form" onsubmit="return false;">
                <p>
                    <label for="g4t-sync-batch-size"><?php _e( 'Batch size:', 'go4taste-ads' ); ?></label>
                    <input type="number" min="1" max="100" step="1" id="g4t-sync-batch-size" name="g4t_sync_batch_size" value="20" style="width:120px;">
                </p>
                <button type="button" class="button-primary" id="g4t-sync-start"><?php _e('Sync Products', 'go4taste-ads'); ?></button>
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
     */
    public function page_init() {
        register_setting(
            'go4taste_ads_options_group',
            'go4taste_ads_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'go4taste_ads_setting_section',
            'Product API Settings',
            '__return_false',
            'go4taste-ads'
        );

        add_settings_section(
            'algolia_settings',              // Section for Algolia settings
            'Algolia Settings',              // Title
            '__return_false',                // Callback
            'algolia_settings'               // Page where the fields are displayed
        );

        add_settings_field(
            'api_type',
            'API Type',
            array($this, 'api_type_callback'),
            'go4taste-ads',
            'go4taste_ads_setting_section'
        );

        add_settings_field(
            'api_url',
            'API URL',
            array($this, 'api_url_callback'),
            'go4taste-ads',
            'go4taste_ads_setting_section'
        );

        add_settings_field(
            'p_parameter_value',
            'PrestaShop API Password',
            array($this, 'p_parameter_callback'),
            'go4taste-ads',
            'go4taste_ads_setting_section'
        );

        add_settings_field(
            'prestashop_webservice_api_key',
            'PrestaShop Webservice API Key',
            array($this, 'prestashop_webservice_api_key_callback'),
            'go4taste-ads',
            'go4taste_ads_setting_section'
        );

        add_settings_field(
            'prestashop_lang',
            'PrestaShop Language',
            array($this, 'prestashop_lang_callback'),
            'go4taste-ads',
            'go4taste_ads_setting_section'
        );

        add_settings_field(
            'prestashop_tax_country_iso',
            'PrestaShop Tax Country ISO',
            array($this, 'prestashop_tax_country_iso_callback'),
            'go4taste-ads',
            'go4taste_ads_setting_section'
        );

        add_settings_field(
            'fallback_product_id',
            'Fallback Product ID',
            array($this, 'fallback_product_id_callback'),
            'go4taste-ads',
            'go4taste_ads_setting_section'
        );

        // Fields for Algolia App ID and Search-Only API Key
        add_settings_field(
            'algolia_app_id',
            'Algolia App ID',
            array($this, 'algolia_app_id_callback'),
            'algolia_settings',
            'algolia_settings'
        );

        add_settings_field(
            'algolia_api_key',
            'Algolia Search-Only API Key',
            array($this, 'algolia_api_key_callback'),
            'algolia_settings',
            'algolia_settings'
        );

        add_settings_field(
            'cache_duration', // Field ID
            'Transient Cache Duration (hours)', // Field label
            array($this, 'cache_duration_callback'), // Callback function
            'go4taste-ads', // Page
            'go4taste_ads_setting_section' // Section
        );
        
    }

    /**
     * Sanitize the input from the settings page.
     *
     * @since    1.0.0
     * @since    1.1.0 Added sanitizator of transient caching field
     */
    public function sanitize($input) {
        $sanitized_input = array();
        $existing_options = get_option('go4taste_ads_options', array());
        if (isset($input['api_type'])) {
            $sanitized_input['api_type'] = sanitize_text_field($input['api_type']);
        }
        if (isset($input['api_url'])) {
            $sanitized_input['api_url'] = esc_url_raw($input['api_url']);
        }
        if (isset($input['p_parameter_value'])) {
            $sanitized_input['p_parameter_value'] = sanitize_text_field($input['p_parameter_value']);
        }
        if (isset($input['prestashop_webservice_api_key'])) {
            $submitted_key = trim((string) $input['prestashop_webservice_api_key']);
            if ('' !== $submitted_key) {
                $sanitized_input['prestashop_webservice_api_key'] = sanitize_text_field($submitted_key);
            } elseif (isset($existing_options['prestashop_webservice_api_key'])) {
                $sanitized_input['prestashop_webservice_api_key'] = sanitize_text_field($existing_options['prestashop_webservice_api_key']);
            }
        }
        if (isset($input['prestashop_lang'])) {
            $sanitized_input['prestashop_lang'] = sanitize_text_field($input['prestashop_lang']);
        }
        if (isset($input['prestashop_tax_country_iso'])) {
            $tax_country_iso = strtoupper(trim((string) sanitize_text_field($input['prestashop_tax_country_iso'])));
            if (preg_match('/^[A-Z]{2}$/', $tax_country_iso)) {
                $sanitized_input['prestashop_tax_country_iso'] = $tax_country_iso;
            } else {
                $sanitized_input['prestashop_tax_country_iso'] = 'PL';
            }
        }
        if (isset($input['fallback_product_id'])) {
            $sanitized_input['fallback_product_id'] = sanitize_text_field($input['fallback_product_id']);
        }
        if (isset($input['algolia_app_id'])) {
            $sanitized_input['algolia_app_id'] = sanitize_text_field($input['algolia_app_id']);
        }
        if (isset($input['algolia_api_key'])) {
            $sanitized_input['algolia_api_key'] = sanitize_text_field($input['algolia_api_key']);
        }
        if (isset($input['cache_duration'])) {
            $sanitized_input['cache_duration'] = intval($input['cache_duration']); // Ensure it is an integer
        }        
        return $sanitized_input;
    }

    /**
     * Callback function for the api_type field.
     *
     * @since    1.0.0
     */
    public function api_type_callback() {
        // Retrieve the saved options from the database
        $options = get_option('go4taste_ads_options');
    
        // Retrieve the selected API type, if set, and sanitize it
        $api_type = isset($options['api_type']) ? esc_attr($options['api_type']) : '';
    
        ?>
        <!-- Create a select dropdown for API types -->
        <select id="api_type" name="go4taste_ads_options[api_type]">
            <!-- PrestaShop 1.4 API -->
            <option value="prestashop_1_4" <?php selected($api_type, 'prestashop_1_4'); ?>>
                PrestaShop 1.4
            </option>
    
            <!-- PrestaShop 1.5 API -->
            <option value="prestashop_1_5" <?php selected($api_type, 'prestashop_1_5'); ?>>
                PrestaShop 1.5
            </option>
    
            <!-- WooCommerce API -->
            <option value="woocommerce" <?php selected($api_type, 'woocommerce'); ?>>
                WooCommerce
            </option>

            <!-- PrestaShop Webservice API -->
            <option value="prestashop_webservice_api" <?php selected($api_type, 'prestashop_webservice_api'); ?>>
                Prestashop Webservice API
            </option>
    
            <!-- Custom API -->
            <option value="custom" <?php selected($api_type, 'custom'); ?>>
                Custom API
            </option>
        </select>
        <?php
    }    

    /**
     * Callback function for the API URL field.
     *
     * @since    1.0.0
     */
    public function api_url_callback() {
        $options = get_option('go4taste_ads_options');
        $api_url = isset($options['api_url']) ? esc_attr($options['api_url']) : '';
        echo '<input type="text" id="api_url" name="go4taste_ads_options[api_url]" value="' . $api_url . '" />';
        echo '<p class="description">Podaj URL sklepu PrestaShop (bez /api). Endpoint /api jest dolaczany automatycznie w kodzie.</p>';
    }

    /**
     * Callback function for the "p" parameter field.
     *
     * @since    1.0.0
     */
    public function p_parameter_callback() {
        $options = get_option('go4taste_ads_options');
        $p_value = isset($options['p_parameter_value']) ? esc_attr($options['p_parameter_value']) : '';
        echo '<input type="password" id="p_parameter_value" name="go4taste_ads_options[p_parameter_value]" value="' . $p_value . '" />';
    }

    /**
     * Callback function for the PrestaShop Webservice API key field.
     *
     * @since    1.2.0
     */
    public function prestashop_webservice_api_key_callback() {
        $options = get_option('go4taste_ads_options');
        $api_type = isset($options['api_type']) ? sanitize_text_field($options['api_type']) : '';
        $api_url = isset($options['api_url']) ? esc_url_raw($options['api_url']) : '';
        $stored_key = isset($options['prestashop_webservice_api_key']) ? sanitize_text_field($options['prestashop_webservice_api_key']) : '';
        $tax_country_iso = isset($options['prestashop_tax_country_iso']) ? strtoupper(sanitize_text_field($options['prestashop_tax_country_iso'])) : 'PL';
        $permissions_report_html = '';

        if ('prestashop_webservice_api' === $api_type && '' !== trim((string) $api_url) && '' !== trim((string) $stored_key)) {
            $api_fetcher = new Go4Taste_Ads_API_Fetcher();
            $cached_report = $api_fetcher->get_cached_prestashop_webservice_permissions_report($api_url, $stored_key, $tax_country_iso);
            $permissions_report_html = $this->render_prestashop_permissions_report_html($cached_report);
        }

        echo '<input type="password" id="prestashop_webservice_api_key" name="go4taste_ads_options[prestashop_webservice_api_key]" value="" autocomplete="new-password" />';
        echo '<p class="description">Dla bezpieczenstwa klucz nie jest wyswietlany. Wpisz nowy klucz tylko gdy chcesz go zaktualizowac.</p>';
        echo '<p class="description"><strong>Wymagane uprawnienia klucza Webservice (GET/READ):</strong> products, taxes, tax_rules, countries.</p>';

        if ('prestashop_webservice_api' !== $api_type) {
            echo '<p class="description">Weryfikacja uprawnien jest aktywna po wybraniu API Type: Prestashop Webservice API.</p>';
            return;
        }

        if ('' === trim((string) $api_url) || '' === trim((string) $stored_key)) {
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
     */
    public function prestashop_lang_callback() {
        $options = get_option('go4taste_ads_options');
        $prestashop_lang = isset($options['prestashop_lang']) ? esc_attr($options['prestashop_lang']) : '';
        echo '<input type="text" id="prestashop_lang" name="go4taste_ads_options[prestashop_lang]" value="' . $prestashop_lang . '" />';
    }

    /**
     * Callback function for the tax country ISO field.
     *
     * @since    1.2.0
     */
    public function prestashop_tax_country_iso_callback() {
        $options = get_option('go4taste_ads_options');
        $tax_country_iso = isset($options['prestashop_tax_country_iso']) ? esc_attr($options['prestashop_tax_country_iso']) : 'PL';

        echo '<input type="text" id="prestashop_tax_country_iso" name="go4taste_ads_options[prestashop_tax_country_iso]" value="' . $tax_country_iso . '" maxlength="2" style="width:80px; text-transform:uppercase;" />';
        echo '<p class="description">Dwuliterowy kod ISO kraju do mapowania reguly VAT (np. PL, DE, CZ). Domyslnie: PL.</p>';
    }

    /**
     * Callback function for the fallback product ID field.
     *
     * @since    1.0.0
     */
    public function fallback_product_id_callback() {
        $options = get_option('go4taste_ads_options');
        $fallback_id = isset($options['fallback_product_id']) ? esc_attr($options['fallback_product_id']) : '';
        echo '<input type="text" id="fallback_product_id" name="go4taste_ads_options[fallback_product_id]" value="' . $fallback_id . '" />';
    }

    /**
     * Callback function for the Algolia App ID field.
     *
     * @since 1.0.0
     */
    public function algolia_app_id_callback() {
        $options = get_option('go4taste_ads_options');
        $algolia_app_id = isset($options['algolia_app_id']) ? esc_attr($options['algolia_app_id']) : '';
        echo '<input type="text" id="algolia_app_id" name="go4taste_ads_options[algolia_app_id]" value="' . $algolia_app_id . '" />';
    }

    /**
     * Callback function for the Algolia Search-Only API Key field.
     *
     * @since 1.0.0
     */
    public function algolia_api_key_callback() {
        $options = get_option('go4taste_ads_options');
        $algolia_api_key = isset($options['algolia_api_key']) ? esc_attr($options['algolia_api_key']) : '';
        echo '<input type="text" id="algolia_api_key" name="go4taste_ads_options[algolia_api_key]" value="' . $algolia_api_key . '" />';
    }

    /**
     * Callback function for the Algolia Search-Only API Key field.
     *
     * @since 1.0.1
     */
    public function cache_duration_callback() {
        $options = get_option('go4taste_ads_options');
        $cache_duration = isset($options['cache_duration']) ? esc_attr($options['cache_duration']) : '0'; // Default to 0 (disabled)
        echo '<input type="number" id="cache_duration" name="go4taste_ads_options[cache_duration]" value="' . $cache_duration . '" min="0" />';
        echo '<p class="description">Set to 0 to disable caching, or specify the number of hours for cache duration.</p>';
    }
    
    
    /**
     * Update post meta with product ID from the first occurrence of the prestashop_data shortcode.
     *
     * This function iterates through all posts, searches for the prestashop_data shortcode,
     * extracts the product ID, and updates the relevant post meta field.
     *
     * @since 1.0.0
     */
    public function update_product_ids_from_shortcode() {
        $query_args = array(
            'post_type'   => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
        );

        $posts = get_posts( $query_args );

        foreach ( $posts as $post ) {
            $content = $post->post_content;

            if ( preg_match( '/\[prestashop_data\s[^]]*product="(\d+)"[^]]*\]/', $content, $matches ) ) {
                $product_id = $matches[1];
                $meta_key = '_g4t_a_sidebar_regular_widget_ps_product_id';

                update_post_meta( $post->ID, $meta_key, $product_id );

                echo "<p>Updated post ID {$post->ID} with product ID {$product_id}.</p>";
            }
        }
    }

    /**
     * Handle the update product IDs action from the admin page.
     *
     * @since 1.0.0
     */
    public function handle_update_product_ids() {
        if ( isset( $_POST['go4taste_update_product_ids_nonce'] ) && wp_verify_nonce( $_POST['go4taste_update_product_ids_nonce'], 'go4taste_update_product_ids' ) ) {
            if ( isset( $_POST['go4taste_update_action'] ) && $_POST['go4taste_update_action'] === 'update_product_ids' ) {
                $this->update_product_ids_from_shortcode();
            }
        }
    }

    /**
     * Build transient key for async migration job state.
     *
     * @since 1.2.0
     * @return string
     */
    private function get_migration_job_transient_key() {
        return 'g4t_migrate_inline_job_' . get_current_user_id();
    }

    /**
     * Return stale timeout for running migration job lock.
     *
     * @since 1.2.0
     * @return int
     */
    private function get_migration_job_stale_timeout() {
        return 10 * MINUTE_IN_SECONDS;
    }

    /**
     * Build transient key for async Algolia sync job state.
     *
     * @since 1.2.0
     * @return string
     */
    private function get_sync_job_transient_key() {
        return 'g4t_sync_algolia_job_' . get_current_user_id();
    }

    /**
     * Return stale timeout for running sync job lock.
     *
     * @since 1.2.0
     * @return int
     */
    private function get_sync_job_stale_timeout() {
        return 10 * MINUTE_IN_SECONDS;
    }

    /**
     * Check if running sync job looks stale.
     *
     * @since 1.2.0
     * @param array<string,mixed> $job_state Job state.
     * @return bool
     */
    private function is_sync_job_stale( $job_state ) {
        if ( ! is_array( $job_state ) || empty( $job_state['status'] ) || 'running' !== $job_state['status'] ) {
            return false;
        }

        $updated_at = isset( $job_state['updated_at'] ) ? absint( $job_state['updated_at'] ) : 0;
        if ( 0 === $updated_at ) {
            return true;
        }

        return ( time() - $updated_at ) > $this->get_sync_job_stale_timeout();
    }

    /**
     * Start async Algolia sync job (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_sync_start() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_sync_products_ajax', 'nonce' );

        $job_key      = $this->get_sync_job_transient_key();
        $existing_job = get_transient( $job_key );
        if ( is_array( $existing_job ) && isset( $existing_job['status'] ) && 'running' === $existing_job['status'] ) {
            if ( ! $this->is_sync_job_stale( $existing_job ) ) {
                wp_send_json_error(
                    array(
                        'message' => 'Sync is already running.',
                        'job'     => $existing_job,
                    ),
                    409
                );
            }

            delete_transient( $job_key );
        }

        $batch_size = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 20;
        $batch_size = max( 1, min( 100, $batch_size ) );

        $options = get_option('go4taste_ads_options');
        $api_type = isset($options['api_type']) ? sanitize_text_field($options['api_type']) : 'prestashop_1_5';
        $p_value = isset($options['p_parameter_value']) ? sanitize_text_field($options['p_parameter_value']) : '';
        $lang = isset($options['prestashop_lang']) ? sanitize_text_field($options['prestashop_lang']) : 'pl';
        $api_url = isset($options['api_url']) ? esc_url_raw($options['api_url']) : '';
        $consumer_key = isset($options['woocommerce_consumer_key']) ? sanitize_text_field($options['woocommerce_consumer_key']) : null;
        $consumer_secret = isset($options['woocommerce_consumer_secret']) ? sanitize_text_field($options['woocommerce_consumer_secret']) : null;

        if ( empty( $api_type ) || empty( $api_url ) ) {
            wp_send_json_error( array( 'message' => 'Missing API settings.' ), 400 );
        }

        $api_fetcher = new Go4Taste_Ads_API_Fetcher();
        $product_ids = $api_fetcher->fetch_product_ids_for_sync( $api_type, $p_value, $lang, $api_url, $consumer_key, $consumer_secret );

        if ( empty( $product_ids ) ) {
            wp_send_json_error( array( 'message' => 'No products found for sync.' ), 404 );
        }

        $job_state = array(
            'status'          => 'running',
            'api_type'        => $api_type,
            'p_value'         => $p_value,
            'lang'            => $lang,
            'api_url'         => $api_url,
            'product_ids'     => $product_ids,
            'total'           => count( $product_ids ),
            'offset'          => 0,
            'batch_size'      => $batch_size,
            'synced'          => 0,
            'errors'          => 0,
            'created_at'      => time(),
            'updated_at'      => time(),
        );

        set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
        wp_send_json_success( array( 'job' => $job_state ) );
    }

    /**
     * Process one async Algolia sync batch (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_sync_batch() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_sync_products_ajax', 'nonce' );

        $job_key   = $this->get_sync_job_transient_key();
        $job_state = get_transient( $job_key );
        if ( ! is_array( $job_state ) || empty( $job_state['status'] ) ) {
            wp_send_json_error( array( 'message' => 'Sync job not found.' ), 404 );
        }

        if ( 'done' === $job_state['status'] ) {
            wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
        }

        if ( $this->is_sync_job_stale( $job_state ) ) {
            $job_state['status']        = 'failed';
            $job_state['error_message'] = 'Sync job is stale. Please start again.';
            $job_state['updated_at']    = time();
            set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
            wp_send_json_error( array( 'message' => 'Sync job is stale.', 'job' => $job_state ), 409 );
        }

        $total = isset( $job_state['total'] ) ? (int) $job_state['total'] : 0;
        $offset = isset( $job_state['offset'] ) ? (int) $job_state['offset'] : 0;
        $remaining = max( 0, $total - $offset );

        if ( 0 === $remaining ) {
            $job_state['status'] = 'done';
            $job_state['updated_at'] = time();
            set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
            wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
        }

        $batch_size = isset( $job_state['batch_size'] ) ? (int) $job_state['batch_size'] : 20;
        $batch_size = max( 1, min( 100, $batch_size ) );
        $ids_batch = array_slice( $job_state['product_ids'], $offset, $batch_size );

        if ( empty( $ids_batch ) ) {
            $job_state['status'] = 'done';
            $job_state['updated_at'] = time();
            set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
            wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
        }

        $api_fetcher = new Go4Taste_Ads_API_Fetcher();
        $products = array();

        foreach ( $ids_batch as $product_id ) {
            $product_data = $api_fetcher->fetch_product_data(
                $product_id,
                $job_state['p_value'],
                $job_state['lang'],
                $job_state['api_type'],
                $job_state['api_url']
            );

            if ( $product_data ) {
                $products[] = $product_data;
            } else {
                $job_state['errors'] = isset( $job_state['errors'] ) ? (int) $job_state['errors'] + 1 : 1;
            }
        }

        if ( ! empty( $products ) ) {
            try {
                $api_fetcher->store_products_to_algolia( $products );
                $job_state['synced'] = isset( $job_state['synced'] ) ? (int) $job_state['synced'] + count( $products ) : count( $products );
            } catch ( Throwable $e ) {
                $job_state['errors'] = isset( $job_state['errors'] ) ? (int) $job_state['errors'] + count( $products ) : count( $products );
                $job_state['last_error'] = $e->getMessage();
            }
        }

        $job_state['offset'] = $offset + count( $ids_batch );
        if ( (int) $job_state['offset'] >= $total ) {
            $job_state['status'] = 'done';
        }

        $job_state['updated_at'] = time();
        set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
        wp_send_json_success( array( 'job' => $job_state, 'done' => 'done' === $job_state['status'] ) );
    }

    /**
     * Read async sync status (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_sync_status() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_sync_products_ajax', 'nonce' );

        $job_key   = $this->get_sync_job_transient_key();
        $job_state = get_transient( $job_key );
        if ( ! is_array( $job_state ) ) {
            wp_send_json_error( array( 'message' => 'Sync job not found.' ), 404 );
        }

        if ( $this->is_sync_job_stale( $job_state ) ) {
            $job_state['status']        = 'failed';
            $job_state['error_message'] = 'Sync job is stale. Lock was released.';
            $job_state['updated_at']    = time();
            delete_transient( $job_key );
            wp_send_json_success( array( 'job' => $job_state ) );
        }

        wp_send_json_success( array( 'job' => $job_state ) );
    }

    /**
     * Force unlock async sync job (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_sync_unlock() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_sync_products_ajax', 'nonce' );

        $job_key   = $this->get_sync_job_transient_key();
        $job_state = get_transient( $job_key );

        if ( ! is_array( $job_state ) ) {
            wp_send_json_success( array( 'message' => 'No active sync lock found.' ) );
        }

        if ( isset( $job_state['status'] ) && 'running' === $job_state['status'] && ! $this->is_sync_job_stale( $job_state ) ) {
            wp_send_json_error(
                array(
                    'message' => 'Sync is still running. Unlock is allowed only for stale jobs.',
                    'job'     => $job_state,
                ),
                409
            );
        }

        delete_transient( $job_key );
        wp_send_json_success( array( 'message' => 'Sync lock has been cleared.' ) );
    }

    /**
     * Check if running migration job looks stale.
     *
     * @since 1.2.0
     * @param array<string,mixed> $job_state Job state.
     * @return bool
     */
    private function is_migration_job_stale( $job_state ) {
        if ( ! is_array( $job_state ) || empty( $job_state['status'] ) || 'running' !== $job_state['status'] ) {
            return false;
        }

        $updated_at = isset( $job_state['updated_at'] ) ? absint( $job_state['updated_at'] ) : 0;
        if ( 0 === $updated_at ) {
            // Backward compatibility with older job payloads that did not persist timestamps.
            return true;
        }

        return ( time() - $updated_at ) > $this->get_migration_job_stale_timeout();
    }

    /**
     * Resolve target post from input value (ID or slug).
     *
     * @since 1.2.0
     * @param string            $target_post   Raw user input.
     * @param array<int,string> $post_types    Allowed post types.
     * @param array<int,string> $post_statuses Allowed post statuses.
     * @return int
     */
    private function resolve_target_post_id( $target_post, $post_types, $post_statuses ) {
        $target_post = trim( (string) $target_post );
        if ( '' === $target_post ) {
            return 0;
        }

        if ( ctype_digit( $target_post ) ) {
            $post_id = absint( $target_post );
            $post    = get_post( $post_id );
            if ( $post && in_array( $post->post_type, $post_types, true ) && in_array( $post->post_status, $post_statuses, true ) ) {
                return $post_id;
            }

            return 0;
        }

        $slug_query = get_posts(
            array(
                'name'           => sanitize_title( $target_post ),
                'post_type'      => $post_types,
                'post_status'    => $post_statuses,
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'no_found_rows'  => true,
            )
        );

        if ( empty( $slug_query ) ) {
            return 0;
        }

        return absint( $slug_query[0] );
    }

    /**
     * Start async migration job (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_migration_start() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_migrate_legacy_banners_ajax', 'nonce' );

        $job_key      = $this->get_migration_job_transient_key();
        $existing_job = get_transient( $job_key );
        if ( is_array( $existing_job ) && isset( $existing_job['status'] ) && 'running' === $existing_job['status'] ) {
            if ( ! $this->is_migration_job_stale( $existing_job ) ) {
                wp_send_json_error(
                    array(
                        'message' => 'Migration is already running.',
                        'job'     => $existing_job,
                    ),
                    409
                );
            }

            // Stale lock recovery.
            $existing_job['status']        = 'failed';
            $existing_job['error_message'] = 'Stale migration lock was reset automatically.';
            $existing_job['updated_at']    = time();
            delete_transient( $job_key );
        }

        $dry_run       = ! empty( $_POST['dry_run'] );
        $limit         = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 0;
        $batch_size    = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 20;
        $batch_size    = max( 1, min( 100, $batch_size ) );
        $target_post   = isset( $_POST['target_post'] ) ? sanitize_text_field( wp_unslash( $_POST['target_post'] ) ) : '';
        $post_types    = isset( $_POST['post_types'] ) && is_array( $_POST['post_types'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['post_types'] ) ) : array( 'post', 'page' );
        $post_statuses = isset( $_POST['post_statuses'] ) && is_array( $_POST['post_statuses'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['post_statuses'] ) ) : array( 'publish', 'draft' );

        if ( empty( $post_types ) ) {
            $post_types = array( 'post', 'page' );
        }

        if ( empty( $post_statuses ) ) {
            $post_statuses = array( 'publish', 'draft' );
        }

        $target_post_id = $this->resolve_target_post_id( $target_post, $post_types, $post_statuses );
        if ( '' !== trim( $target_post ) && 0 === $target_post_id ) {
            wp_send_json_error( array( 'message' => 'Target post not found for selected type/status filters.' ), 400 );
        }

        $total_query = array(
            'post_type'      => $post_types,
            'post_status'    => $post_statuses,
            'posts_per_page' => $limit > 0 ? $limit : 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'fields'         => 'ids',
            'no_found_rows'  => false,
        );
        if ( $target_post_id > 0 ) {
            $total_query['post__in']       = array( $target_post_id );
            $total_query['posts_per_page'] = 1;
        }

        $total_wp_query = new WP_Query( $total_query );
        $total          = isset( $total_wp_query->found_posts ) ? (int) $total_wp_query->found_posts : 0;
        if ( $limit > 0 && $target_post_id <= 0 ) {
            $total = min( $total, $limit );
        }
        wp_reset_postdata();

        $job_state = array(
            'dry_run'                  => (bool) $dry_run,
            'limit'                    => $limit,
            'batch_size'               => $batch_size,
            'target_post'              => $target_post,
            'target_post_id'           => $target_post_id,
            'post_types'               => $post_types,
            'post_statuses'            => $post_statuses,
            'total'                    => $total,
            'offset'                   => 0,
            'posts_scanned'            => 0,
            'posts_matched'            => 0,
            'posts_skipped_candidates' => 0,
            'posts_changed'            => 0,
            'groups_converted'         => 0,
            'inline_banners_created'   => 0,
            'errors'                   => 0,
            'status'                   => 'running',
            'created_at'               => time(),
            'updated_at'               => time(),
        );

        set_transient( $job_key, $job_state, HOUR_IN_SECONDS );

        wp_send_json_success( array( 'job' => $job_state ) );
    }

    /**
     * Process one async migration batch (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_migration_batch() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_migrate_legacy_banners_ajax', 'nonce' );

        $job_key   = $this->get_migration_job_transient_key();
        $job_state = get_transient( $job_key );

        if ( ! is_array( $job_state ) || empty( $job_state['status'] ) ) {
            wp_send_json_error( array( 'message' => 'Migration job not found.' ), 404 );
        }

        if ( 'done' === $job_state['status'] ) {
            wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
        }

        if ( $this->is_migration_job_stale( $job_state ) ) {
            $job_state['status']        = 'failed';
            $job_state['error_message'] = 'Migration job became stale and was marked as failed.';
            $job_state['updated_at']    = time();
            set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
            wp_send_json_error( array( 'message' => 'Migration job is stale. Please start again.', 'job' => $job_state ), 409 );
        }

        $remaining = max( 0, (int) $job_state['total'] - (int) $job_state['offset'] );
        if ( 0 === $remaining ) {
            $job_state['status'] = 'done';
            $job_state['updated_at'] = time();
            set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
            wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
        }

        $current_batch_size = min( (int) $job_state['batch_size'], $remaining );

        $batch_query = array(
            'post_type'      => $job_state['post_types'],
            'post_status'    => $job_state['post_statuses'],
            'posts_per_page' => $current_batch_size,
            'offset'         => (int) $job_state['offset'],
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        );
        if ( ! empty( $job_state['target_post_id'] ) ) {
            $batch_query['post__in'] = array( absint( $job_state['target_post_id'] ) );
        }

        $post_ids = get_posts( $batch_query );

        if ( empty( $post_ids ) ) {
            $job_state['status'] = 'done';
            $job_state['updated_at'] = time();
            set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
            wp_send_json_success( array( 'job' => $job_state, 'done' => true ) );
        }

        foreach ( $post_ids as $post_id ) {
            $job_state['posts_scanned']++;
            $content = (string) get_post_field( 'post_content', $post_id );

            if ( false === strpos( $content, 'prestashop_' ) ) {
                continue;
            }

            $local_stats = array(
                'groups_converted'       => 0,
                'inline_banners_created' => 0,
            );

            try {
                $updated_content = $this->convert_legacy_shortcodes_to_inline_blocks( $content, $local_stats );
            } catch ( Throwable $e ) {
                $job_state['errors']++;
                $job_state['last_error'] = $e->getMessage();
                continue;
            }

            if ( $updated_content === $content ) {
                $job_state['posts_skipped_candidates']++;
                continue;
            }

            $job_state['posts_matched']++;
            $job_state['groups_converted'] += (int) $local_stats['groups_converted'];
            $job_state['inline_banners_created'] += (int) $local_stats['inline_banners_created'];

            if ( ! empty( $job_state['dry_run'] ) ) {
                $job_state['posts_changed']++;
                continue;
            }

            $update_result = wp_update_post(
                array(
                    'ID'           => $post_id,
                    'post_content' => wp_slash( $updated_content ),
                ),
                true
            );

            if ( is_wp_error( $update_result ) ) {
                $job_state['errors']++;
                continue;
            }

            $job_state['posts_changed']++;
        }

        $job_state['offset'] += count( $post_ids );
        if ( (int) $job_state['offset'] >= (int) $job_state['total'] ) {
            $job_state['status'] = 'done';
        }

        $job_state['updated_at'] = time();
        set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
        wp_send_json_success( array( 'job' => $job_state, 'done' => 'done' === $job_state['status'] ) );
    }

    /**
     * Read async migration status (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_migration_status() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_migrate_legacy_banners_ajax', 'nonce' );

        $job_key   = $this->get_migration_job_transient_key();
        $job_state = get_transient( $job_key );
        if ( ! is_array( $job_state ) ) {
            wp_send_json_error( array( 'message' => 'Migration job not found.' ), 404 );
        }

        if ( $this->is_migration_job_stale( $job_state ) ) {
            $job_state['status']        = 'failed';
            $job_state['error_message'] = 'Migration job is stale. Lock was released.';
            $job_state['updated_at']    = time();
            delete_transient( $job_key );
            wp_send_json_success( array( 'job' => $job_state ) );
        }

        wp_send_json_success( array( 'job' => $job_state ) );
    }

    /**
     * Force unlock async migration job (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_migration_unlock() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_migrate_legacy_banners_ajax', 'nonce' );

        $job_key   = $this->get_migration_job_transient_key();
        $job_state = get_transient( $job_key );

        if ( ! is_array( $job_state ) ) {
            wp_send_json_success( array( 'message' => 'No active migration lock found.' ) );
        }

        if ( isset( $job_state['status'] ) && 'running' === $job_state['status'] && ! $this->is_migration_job_stale( $job_state ) ) {
            wp_send_json_error(
                array(
                    'message' => 'Migration is still running. Unlock is allowed only for stale jobs.',
                    'job'     => $job_state,
                ),
                409
            );
        }

        delete_transient( $job_key );
        wp_send_json_success( array( 'message' => 'Migration lock has been cleared.' ) );
    }

    /**
     * Convert legacy shortcode blocks into inline banner blocks.
     *
     * Recursively processes all block levels including nested blocks (columns, groups, etc).
     * Scope is limited to Gutenberg shortcode blocks only.
     *
     * @since 1.2.0
     * @param string             $content Original post content.
     * @param array<string,int> &$stats   Conversion stats.
     * @return string
     */
    private function convert_legacy_shortcodes_to_inline_blocks( $content, &$stats ) {
        if ( ! function_exists( 'parse_blocks' ) || ! function_exists( 'serialize_blocks' ) ) {
            return $content;
        }

        $blocks = parse_blocks( $content );
        if ( empty( $blocks ) ) {
            return $content;
        }

        $changed            = false;
        $collected_ids      = array();
        $insertion_index    = null;
        $new_blocks         = $this->remove_legacy_shortcodes_and_columns( $blocks, $collected_ids, $changed, $insertion_index );

        $collected_ids = $this->normalize_product_ids( $collected_ids );
        if ( ! empty( $collected_ids ) ) {
            $inline_banner_blocks = array();
            foreach ( array_chunk( $collected_ids, 3 ) as $id_chunk ) {
                $inline_banner_blocks[] = $this->build_inline_banner_block_array( $id_chunk );
            }

            if ( null === $insertion_index ) {
                $new_blocks = array_merge( $new_blocks, $inline_banner_blocks );
            } else {
                array_splice( $new_blocks, (int) $insertion_index, 0, $inline_banner_blocks );
            }

            // Count one converted source group per post; banner count is tracked separately.
            $stats['groups_converted']++;
            $stats['inline_banners_created'] += count( $inline_banner_blocks );
            $changed = true;
        }

        if ( ! $changed ) {
            return $content;
        }

        return serialize_blocks( $new_blocks );
    }

    /**
     * Remove legacy shortcode blocks and columns containing legacy shortcodes.
     *
     * Legacy shortcode IDs are collected and converted later into top-level inline banner blocks.
     *
     * @since 1.2.0
     * @param array<int,array<string,mixed>> $blocks  Parsed block array.
     * @param array<int,string>            &$collected_ids   Collected legacy product IDs.
     * @param bool                         &$changed         Whether any blocks were removed.
     * @param int|null                     &$insertion_index Top-level insertion point for inline banners.
     * @param int                           $depth           Current recursion depth.
     * @return array<int,array<string,mixed>> Processed blocks.
     */
    private function remove_legacy_shortcodes_and_columns( $blocks, &$collected_ids, &$changed, &$insertion_index = null, $depth = 0 ) {
        if ( ! is_array( $blocks ) || empty( $blocks ) ) {
            return $blocks;
        }

        $new_blocks = array();
        foreach ( $blocks as $block ) {
            if ( ! is_array( $block ) ) {
                $new_blocks[] = $block;
                continue;
            }

            $block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';

            // Anchor insertion to the first top-level block that contains any legacy shortcode in its tree.
            if ( 0 === $depth && null === $insertion_index ) {
                $top_level_tree_ids = $this->extract_legacy_product_ids_from_block_tree( $block );
                if ( ! empty( $top_level_tree_ids ) ) {
                    $insertion_index = count( $new_blocks );
                }
            }

            $shortcode_ids = $this->extract_legacy_product_ids_from_shortcode_block( $block );
            if ( ! empty( $shortcode_ids ) ) {
                $collected_ids = $this->normalize_product_ids( array_merge( $collected_ids, $shortcode_ids ) );
                $changed       = true;
                if ( 0 === $depth && null === $insertion_index ) {
                    $insertion_index = count( $new_blocks );
                }
                continue;
            }

            // Remove entire columns/column wrappers if legacy shortcode is found anywhere inside.
            if ( in_array( $block_name, array( 'core/columns', 'core/column' ), true ) ) {
                $tree_ids = $this->extract_legacy_product_ids_from_block_tree( $block );
                if ( ! empty( $tree_ids ) ) {
                    $collected_ids = $this->normalize_product_ids( array_merge( $collected_ids, $tree_ids ) );
                    $changed       = true;
                    if ( 0 === $depth && null === $insertion_index ) {
                        $insertion_index = count( $new_blocks );
                    }
                    continue;
                }
            }

            if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {
                $block['innerBlocks'] = $this->remove_legacy_shortcodes_and_columns( $block['innerBlocks'], $collected_ids, $changed, $insertion_index, $depth + 1 );
            }

            $new_blocks[] = $block;
        }

        return $new_blocks;
    }

    /**
     * Extract legacy product IDs from a block and all nested child blocks.
     *
     * @since 1.2.0
     * @param array<string,mixed> $block Parsed block.
     * @return array<int,string>
     */
    private function extract_legacy_product_ids_from_block_tree( $block ) {
        if ( ! is_array( $block ) ) {
            return array();
        }

        $ids = $this->extract_legacy_product_ids_from_shortcode_block( $block );

        if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
            foreach ( $block['innerBlocks'] as $child_block ) {
                $ids = array_merge( $ids, $this->extract_legacy_product_ids_from_block_tree( $child_block ) );
            }
        }

        return $this->normalize_product_ids( $ids );
    }

    /**
     * Build dynamic Gutenberg inline banner block markup.
     *
     * @since 1.2.0
     * @param array<int,string|int> $product_ids Product IDs.
     * @return string
     */
    private function build_inline_banner_block_markup( $product_ids ) {
        $products = array();
        foreach ( $product_ids as $id ) {
            $products[] = array( 'id' => (string) $id );
        }

        $attributes = array(
            'products' => $products,
        );

        return '<!-- wp:go4taste-ads/inline-banner ' . wp_json_encode( $attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->';
    }

    /**
     * Build inline banner block array from product IDs.
     *
     * @since 1.2.0
     * @param array<int,string|int> $product_ids Product IDs.
     * @return array<string,mixed>
     */
    private function build_inline_banner_block_array( $product_ids ) {
        $parsed_blocks = parse_blocks( $this->build_inline_banner_block_markup( $product_ids ) );
        if ( ! empty( $parsed_blocks ) ) {
            return $parsed_blocks[0];
        }

        $products = array();
        foreach ( $product_ids as $id ) {
            $products[] = array( 'id' => (string) $id );
        }

        return array(
            'blockName'    => 'go4taste-ads/inline-banner',
            'attrs'        => array(
                'products' => $products,
            ),
            'innerBlocks'  => array(),
            'innerHTML'    => '',
            'innerContent' => array(),
        );
    }

    /**
     * Extract product IDs only from a shortcode Gutenberg block.
     *
     * @since 1.2.0
     * @param array<string,mixed> $block Parsed block.
     * @return array<int,string>
     */
    private function extract_legacy_product_ids_from_shortcode_block( $block ) {
        if ( ! is_array( $block ) || ! isset( $block['blockName'] ) || 'core/shortcode' !== $block['blockName'] ) {
            return array();
        }

        $shortcode_content = '';
        if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
            $shortcode_content = $block['innerHTML'];
        }

        if ( false === stripos( $shortcode_content, 'prestashop_' ) ) {
            return array();
        }

        return $this->extract_legacy_product_ids( $shortcode_content );
    }

    /**
     * Extract product IDs from legacy prestashop_* shortcodes.
     *
     * @since 1.2.0
     * @param string $chunk Chunk of content.
     * @return array<int,string>
     */
    private function extract_legacy_product_ids( $chunk ) {
        $ids = array();
        if ( preg_match_all( '/\[(?:prestashop_image|prestashop_data|prestashop_link)\b[^\]]*product\s*=\s*(?:"(\d+)"|\'(\d+)\'|(\d+))[^\]]*\]/i', $chunk, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $match ) {
                foreach ( array( 1, 2, 3 ) as $index ) {
                    if ( ! empty( $match[ $index ] ) ) {
                        $ids[] = $match[ $index ];
                        break;
                    }
                }
            }
        }

        return $this->normalize_product_ids( $ids );
    }

    /**
     * Normalize product IDs: keep digits only, unique, stable order.
     *
     * @since 1.2.0
     * @param array<int,string|int> $ids Raw product IDs.
     * @return array<int,string>
     */
    private function normalize_product_ids( $ids ) {
        $normalized = array();
        foreach ( $ids as $id ) {
            $id = preg_replace( '/\D+/', '', (string) $id );
            if ( '' === $id ) {
                continue;
            }
            if ( ! in_array( $id, $normalized, true ) ) {
                $normalized[] = $id;
            }
        }

        return $normalized;
    }

    /**
     * Fetch all products from the selected API and store them in Algolia.
     *
     * @since 1.0.0
     */
    public function sync_products_to_algolia() {
        $options = get_option('go4taste_ads_options');
        $api_type = isset($options['api_type']) ? esc_attr($options['api_type']) : 'prestashop_1_5'; // Default to PrestaShop 1.5
        $p_value = isset($options['p_parameter_value']) ? esc_attr($options['p_parameter_value']) : '';
        $lang = isset($options['prestashop_lang']) ? esc_attr($options['prestashop_lang']) : 'pl';
        $api_url = isset($options['api_url']) ? esc_url($options['api_url']) : '';
        $consumer_key = isset($options['woocommerce_consumer_key']) ? esc_attr($options['woocommerce_consumer_key']) : null;
        $consumer_secret = isset($options['woocommerce_consumer_secret']) ? esc_attr($options['woocommerce_consumer_secret']) : null;

        $api_fetcher = new Go4Taste_Ads_API_Fetcher();

        // Fetch all products based on the selected API type
        $products = $api_fetcher->fetch_all_products($api_type, $p_value, $lang, $api_url, $consumer_key, $consumer_secret);
        
        if ($products) {
            
            // Store products in Algolia
            $api_fetcher->store_products_to_algolia($products);

            // Display success message
            echo '<div class="notice notice-success is-dismissible"><p>Products successfully synced to Algolia.</p></div>';
        } else {
            // Display error message
            echo '<div class="notice notice-error is-dismissible"><p>Failed to fetch products from the API.</p></div>';
        }
    }

}

