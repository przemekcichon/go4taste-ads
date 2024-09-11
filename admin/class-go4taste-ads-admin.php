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

