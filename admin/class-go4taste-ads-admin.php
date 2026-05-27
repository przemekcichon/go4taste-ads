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

