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
            $this->version,
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
        $algolia_app_id = isset($options['algolia_app_id']) ? esc_attr($options['algolia_app_id']) : '';
        $algolia_api_key = isset($options['algolia_api_key']) ? esc_attr($options['algolia_api_key']) : '';

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
            const searchClient = algoliasearch('<?php echo $algolia_app_id; ?>', '<?php echo $algolia_api_key; ?>');

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
                        nonce: '<?php echo wp_create_nonce('g4t_ad_preview_nonce'); ?>'
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
        $migration_report = null;
        if ( isset( $_GET['g4t_migrate_done'] ) ) {
            $report_key       = $this->get_migration_report_transient_key();
            $migration_report = get_transient( $report_key );
            delete_transient( $report_key );
        }
        ?>
        <div class="wrap">
            <h1>Go4Taste Ads Settings</h1>

            <?php if ( is_array( $migration_report ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                'Legacy migration (%s): scanned %d entries, matched %d, converted %d groups into %d inline banners, %d entries changed.',
                                ! empty( $migration_report['dry_run'] ) ? 'dry run' : 'write mode',
                                (int) $migration_report['posts_scanned'],
                                (int) $migration_report['posts_matched'],
                                (int) $migration_report['groups_converted'],
                                (int) $migration_report['inline_banners_created'],
                                (int) $migration_report['posts_changed']
                            )
                        );
                        ?>
                    </p>
                    <?php if ( isset( $migration_report['posts_skipped_candidates'] ) && $migration_report['posts_skipped_candidates'] > 0 ) : ?>
                        <p><?php echo esc_html( sprintf( 'Skipped candidate entries: %d', (int) $migration_report['posts_skipped_candidates'] ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $migration_report['errors'] ) ) : ?>
                        <p><?php echo esc_html( sprintf( 'Errors: %d', (int) $migration_report['errors'] ) ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

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
            <form method="post">
                <?php wp_nonce_field('go4taste_sync_products', 'go4taste_sync_products_nonce'); ?>
                <input type="hidden" name="go4taste_sync_action" value="sync_products">
                <button type="submit" class="button-primary"><?php _e('Sync Products', 'go4taste-ads'); ?></button>
            </form>

            <?php
            if (isset($_POST['go4taste_sync_action']) && $_POST['go4taste_sync_action'] === 'sync_products') {
                $this->sync_products_to_algolia();
            }
            ?>

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
            null,
            'go4taste-ads'
        );

        add_settings_section(
            'algolia_settings',              // Section for Algolia settings
            'Algolia Settings',              // Title
            null,                            // Callback
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
            'prestashop_lang',
            'PrestaShop Language',
            array($this, 'prestashop_lang_callback'),
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
        if (isset($input['api_type'])) {
            $sanitized_input['api_type'] = sanitize_text_field($input['api_type']);
        }
        if (isset($input['api_url'])) {
            $sanitized_input['api_url'] = sanitize_text_field($input['api_url']);
        }
        if (isset($input['p_parameter_value'])) {
            $sanitized_input['p_parameter_value'] = sanitize_text_field($input['p_parameter_value']);
        }
        if (isset($input['prestashop_lang'])) {
            $sanitized_input['prestashop_lang'] = sanitize_text_field($input['prestashop_lang']);
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
     * Callback function for the prestashop lang field.
     *
     * @since    1.0.0
     */
    public function prestashop_lang_callback() {
        $options = get_option('go4taste_ads_options');
        $prestashop_lang = isset($options['prestashop_lang']) ? esc_attr($options['prestashop_lang']) : '';
        echo '<input type="text" id="prestashop_lang" name="go4taste_ads_options[prestashop_lang]" value="' . $prestashop_lang . '" />';
    }

    // WooCommerce API Keys callback
    public function woocommerce_keys_callback() {
        $options = get_option('go4taste_ads_options');
        $consumer_key = isset($options['woocommerce_consumer_key']) ? esc_attr($options['woocommerce_consumer_key']) : '';
        $consumer_secret = isset($options['woocommerce_consumer_secret']) ? esc_attr($options['woocommerce_consumer_secret']) : '';
        ?>
        <p>
            <label for="woocommerce_consumer_key">Consumer Key:</label>
            <input type="text" id="woocommerce_consumer_key" name="go4taste_ads_options[woocommerce_consumer_key]" value="<?php echo $consumer_key; ?>" />
        </p>
        <p>
            <label for="woocommerce_consumer_secret">Consumer Secret:</label>
            <input type="text" id="woocommerce_consumer_secret" name="go4taste_ads_options[woocommerce_consumer_secret]" value="<?php echo $consumer_secret; ?>" />
        </p>
        <?php
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
     * Handle migration from legacy prestashop shortcodes to inline banner blocks.
     *
     * @since 1.2.0
     */
    public function handle_migrate_legacy_banners() {
        // Deprecated synchronous path intentionally disabled.
        return;
    }

    /**
     * Build transient key for storing per-user migration report.
     *
     * @since 1.2.0
     * @return string
     */
    private function get_migration_report_transient_key() {
        return 'g4t_migrate_inline_report_' . get_current_user_id();
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
     * Start async migration job (AJAX).
     *
     * @since 1.2.0
     */
    public function ajax_migration_start() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
        }

        check_ajax_referer( 'g4t_migrate_legacy_banners_ajax', 'nonce' );

        $existing_job = get_transient( $this->get_migration_job_transient_key() );
        if ( is_array( $existing_job ) && isset( $existing_job['status'] ) && 'running' === $existing_job['status'] ) {
            wp_send_json_error( array( 'message' => 'Migration is already running.' ), 409 );
        }

        $dry_run       = ! empty( $_POST['dry_run'] );
        $limit         = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 0;
        $batch_size    = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 20;
        $batch_size    = max( 1, min( 100, $batch_size ) );
        $post_types    = isset( $_POST['post_types'] ) && is_array( $_POST['post_types'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['post_types'] ) ) : array( 'post', 'page' );
        $post_statuses = isset( $_POST['post_statuses'] ) && is_array( $_POST['post_statuses'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['post_statuses'] ) ) : array( 'publish', 'draft' );

        if ( empty( $post_types ) ) {
            $post_types = array( 'post', 'page' );
        }

        if ( empty( $post_statuses ) ) {
            $post_statuses = array( 'publish', 'draft' );
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
        $total_wp_query = new WP_Query( $total_query );
        $total          = isset( $total_wp_query->found_posts ) ? (int) $total_wp_query->found_posts : 0;
        if ( $limit > 0 ) {
            $total = min( $total, $limit );
        }
        wp_reset_postdata();

        $job_state = array(
            'dry_run'                  => (bool) $dry_run,
            'limit'                    => $limit,
            'batch_size'               => $batch_size,
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
        );

        set_transient( $this->get_migration_job_transient_key(), $job_state, HOUR_IN_SECONDS );

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

        $remaining = max( 0, (int) $job_state['total'] - (int) $job_state['offset'] );
        if ( 0 === $remaining ) {
            $job_state['status'] = 'done';
            set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
            set_transient( $this->get_migration_report_transient_key(), $job_state, 10 * MINUTE_IN_SECONDS );
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

        $post_ids = get_posts( $batch_query );

        if ( empty( $post_ids ) ) {
            $job_state['status'] = 'done';
            set_transient( $job_key, $job_state, HOUR_IN_SECONDS );
            set_transient( $this->get_migration_report_transient_key(), $job_state, 10 * MINUTE_IN_SECONDS );
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

            $updated_content = $this->convert_legacy_shortcodes_to_inline_blocks( $content, $local_stats );
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
                    'post_content' => $updated_content,
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
            set_transient( $this->get_migration_report_transient_key(), $job_state, 10 * MINUTE_IN_SECONDS );
        }

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

        $job_state = get_transient( $this->get_migration_job_transient_key() );
        if ( ! is_array( $job_state ) ) {
            wp_send_json_error( array( 'message' => 'Migration job not found.' ), 404 );
        }

        wp_send_json_success( array( 'job' => $job_state ) );
    }

    /**
     * Migrate legacy prestashop shortcode banners to inline banner blocks.
     *
     * @since 1.2.0
     * @param bool             $dry_run       Whether to only simulate changes.
     * @param int              $limit         Max number of posts to scan (0 = all).
     * @param array<int,string> $post_types   Post types to scan.
     * @param array<int,string> $post_statuses Post statuses to scan.
     * @return array<string,int|bool>
     */
    public function migrate_legacy_shortcode_banners_to_inline( $dry_run = true, $limit = 0, $post_types = array( 'post', 'page' ), $post_statuses = array( 'publish', 'draft' ) ) {
        $query_args = array(
            'post_type'   => $post_types,
            'post_status' => $post_statuses,
            'numberposts' => $limit > 0 ? $limit : -1,
        );

        $report = array(
            'dry_run'               => (bool) $dry_run,
            'posts_scanned'         => 0,
            'posts_matched'         => 0,
            'posts_skipped_candidates' => 0,
            'posts_changed'         => 0,
            'groups_converted'      => 0,
            'inline_banners_created' => 0,
            'errors'                => 0,
        );

        $posts = get_posts( $query_args );
        foreach ( $posts as $post ) {
            $report['posts_scanned']++;
            $content = (string) $post->post_content;

            if ( false === strpos( $content, 'prestashop_' ) ) {
                continue;
            }

            $local_stats = array(
                'groups_converted'       => 0,
                'inline_banners_created' => 0,
            );

            $updated_content = $this->convert_legacy_shortcodes_to_inline_blocks( $content, $local_stats );
            if ( $updated_content === $content ) {
                $report['posts_skipped_candidates']++;
                continue;
            }

            $report['posts_matched']++;
            $report['groups_converted'] += (int) $local_stats['groups_converted'];
            $report['inline_banners_created'] += (int) $local_stats['inline_banners_created'];

            if ( $dry_run ) {
                $report['posts_changed']++;
                continue;
            }

            $update_result = wp_update_post(
                array(
                    'ID'           => $post->ID,
                    'post_content' => $updated_content,
                ),
                true
            );

            if ( is_wp_error( $update_result ) ) {
                $report['errors']++;
                continue;
            }

            $report['posts_changed']++;
        }

        return $report;
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

        $changed    = false;
        $new_blocks = $this->process_blocks_recursively( $blocks, $stats, $changed );

        if ( ! $changed ) {
            return $content;
        }

        return serialize_blocks( $new_blocks );
    }

    /**
     * Recursively process blocks tree to find and convert legacy shortcodes in nested structures.
     *
     * Handles nested blocks like columns/column, group, etc. by recursively processing innerBlocks.
     *
     * @since 1.2.0
     * @param array<int,array<string,mixed>> $blocks  Parsed block array.
     * @param array<string,int>             &$stats   Stats reference.
     * @param bool                          &$changed Whether any blocks were converted.
     * @return array<int,array<string,mixed>> Processed blocks.
     */
    private function process_blocks_recursively( $blocks, &$stats, &$changed ) {
        if ( ! is_array( $blocks ) || empty( $blocks ) ) {
            return $blocks;
        }

        $new_blocks = array();
        $count      = count( $blocks );

        for ( $i = 0; $i < $count; $i++ ) {
            $block = $blocks[ $i ];

            // Recursively process innerBlocks if they exist.
            if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && ! empty( $block['innerBlocks'] ) ) {
                $block['innerBlocks'] = $this->process_blocks_recursively( $block['innerBlocks'], $stats, $changed );
            }

            // Check if this block is a legacy shortcode.
            $ids = $this->extract_legacy_product_ids_from_shortcode_block( $block );
            if ( empty( $ids ) ) {
                // Not a shortcode block, add as-is.
                $new_blocks[] = $block;
                continue;
            }

            // This is a shortcode block — group consecutive ones.
            $group_ids = $ids;
            $j         = $i + 1;

            while ( $j < $count ) {
                $next_ids = $this->extract_legacy_product_ids_from_shortcode_block( $blocks[ $j ] );
                if ( empty( $next_ids ) ) {
                    break;
                }

                $group_ids = $this->normalize_product_ids( array_merge( $group_ids, $next_ids ) );
                $j++;
            }

            $group_ids = $this->normalize_product_ids( $group_ids );
            if ( ! empty( $group_ids ) ) {
                foreach ( array_chunk( $group_ids, 3 ) as $id_chunk ) {
                    $new_blocks[] = $this->build_inline_banner_block_array( $id_chunk );
                }

                $stats['groups_converted']++;
                $stats['inline_banners_created'] += count( array_chunk( $group_ids, 3 ) );
                $changed = true;
                $i       = $j - 1;
            } else {
                // Edge case: IDs extracted but normalization resulted in empty array.
                error_log( 'Go4Taste Ads: Product IDs found in shortcode block but normalized to empty. Original IDs: ' . wp_json_encode( $ids ) );
                $new_blocks[] = $block;
            }
        }

        return $new_blocks;
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

