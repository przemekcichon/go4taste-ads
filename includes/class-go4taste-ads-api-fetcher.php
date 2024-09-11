<?php

/**
 * The class responsible for fetching product data from different APIs (PrestaShop, WooCommerce, etc.).
 *
 * @link       https://go4taste.pl/techlab
 * @since      1.0.0
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/includes
 */

use Algolia\AlgoliaSearch\SearchClient;
class Go4Taste_Ads_API_Fetcher {

    /**
     * Fetch product data from the selected API.
     *
     * @since 1.0.0
     * @since 1.1.0 Added transient caching with API fields filtering to avoid database overload
     * @param string $product_id The product ID.
     * @param string $p_value The "p" parameter for PrestaShop APIs.
     * @param string $lang The language for PrestaShop APIs.
     * @param string $api_type The API type (e.g., 'prestashop_1_4', 'prestashop_1_5', 'woocommerce').
     * @param string $api_url The API base URL.
     * @return object|false The product data object, or false if not found.
     */
    public function fetch_product_data($product_id, $p_value, $lang, $api_type, $api_url) {
        $transient_key = "product_data_{$api_type}_{$product_id}";
    
        // Fetch cache duration from options, default to 0 (cache disabled)
        $options = get_option('go4taste_ads_options');
        $cache_duration = isset($options['cache_duration']) ? intval($options['cache_duration']) : 0;

        // If cache duration is more than 0, attempt to get cached data
        if ($cache_duration > 0) {
            $cached_product_data = get_transient($transient_key);
            if ($cached_product_data !== false) {
                return $cached_product_data;
            }
        }  
    
        // Proceed to fetch from the API if no cached data
        $url = '';
        
        switch ($api_type) {
            case 'prestashop_1_4':
                $url = trim($api_url, '/') . '/modules/prestashop2wordpress/index.php?w=PrestashopProductDatas&p=' . $p_value . '&l=' . $lang . '&id_product=' . $product_id . '&id_product_attribute=0&image_size=home_default';
                break;

            case 'prestashop_1_5':
                $url = trim($api_url, '/') . '/index.php?fc=module&module=prestashop2wordpress&controller=default&p=' . $p_value . '&l=' . $lang . '&w=PrestashopProductDatas&id_product=' . $product_id . '&id_product_attribute=0&image_size=home_default';
                break;

            case 'woocommerce':
                $url = $api_url . '/wp-json/wc/v3/products/' . $product_id . '?consumer_key=ck_xxxx&consumer_secret=cs_xxxx';
                break;

            default:
                return false;
        }

        // Fetch product data from the API
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $product_data = json_decode($body);

        if ($product_data && isset($product_data->datas)) {

            // Only store the specific fields you need
            $filtered_product_data = new stdClass();
            $filtered_product_data->picture = $product_data->datas->picture ?? '';
            $filtered_product_data->name = $product_data->datas->name ?? '';
            $filtered_product_data->price = $product_data->datas->price ?? '';
            $filtered_product_data->price_without_reduction = $product_data->datas->price_without_reduction ?? '';
            $filtered_product_data->link = $product_data->datas->link ?? '';
            $filtered_product_data->category = $product_data->datas->category ?? '';
            $filtered_product_data->reference = $product_data->datas->reference ?? '';
            $filtered_product_data->price_tax_exc = $product_data->datas->price_tax_exc ?? '';
            $filtered_product_data->id = $product_data->datas->id ?? '';

            // If cache duration is greater than 0, set the transient
            if ($cache_duration > 0) {
                set_transient($transient_key, $filtered_product_data, $cache_duration * HOUR_IN_SECONDS);
            }
            return $filtered_product_data;
        }
    
        return false;
    }

    /**
     * Fetch all products data from the selected API.
     *
     * @since 1.0.0
     * @param string $api_type The API type (e.g., 'prestashop_1_4', 'prestashop_1_5', 'woocommerce').
     * @param string $p_value The "p" parameter for PrestaShop APIs.
     * @param string $lang The language for PrestaShop APIs.
     * @param string $api_url The API base URL.
     * @param string $consumer_key Optional WooCommerce consumer key.
     * @param string $consumer_secret Optional WooCommerce consumer secret.
     * @return array|false An array of all products, or false if not found.
     */
    public function fetch_all_products($api_type, $p_value, $lang, $api_url, $consumer_key = null, $consumer_secret = null) {
        $url = '';
        
        // Check which API type is being used and construct the URL accordingly
        switch ($api_type) {
            case 'prestashop_1_4':
                $url = trim($api_url, '/') . '/modules/prestashop2wordpress/index.php?w=PrestashopProducts&p=' . $p_value . '&l=' . $lang . '&number=1000';
                break;

            case 'prestashop_1_5':
                $url = trim($api_url, '/') . '/index.php?fc=module&module=prestashop2wordpress&controller=default&p=' . $p_value . '&l=' . $lang . '&w=PrestashopProducts&number=1000';
                break;

            case 'woocommerce':
                $url = $api_url . '/wp-json/wc/v3/products?consumer_key=' . $consumer_key . '&consumer_secret=' . $consumer_secret . '&per_page=100'; // WooCommerce pagination example
                break;
                
            default:
                return false; // Invalid API type
        }

        // Fetch product data from the API
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $product_data = json_decode($body);

        // Handle different structures based on API type
        if ($api_type === 'woocommerce') {
            return $product_data; // WooCommerce returns an array of products directly
        }

        return $product_data && isset($product_data->datas) ? $product_data->datas : false;
    }

    /**
     * Store product data to Algolia.
     *
     * @since 1.0.0
     * @param array $products An array of products to store in Algolia.
     * @return void
     */
    public function store_products_to_algolia($products) {

        // Retrieve Algolia App ID and Search-Only API Key from settings
        $options = get_option('go4taste_ads_options');
        $algolia_app_id = isset($options['algolia_app_id']) ? esc_attr($options['algolia_app_id']) : '';
        $algolia_api_key = isset($options['algolia_api_key']) ? esc_attr($options['algolia_api_key']) : '';

        // Create the Algolia client using your App ID and API key
        $client = SearchClient::create( $algolia_app_id, $algolia_api_key );
        
        // Initialize the index
        $index = $client->initIndex('go4taste_product_list');

        // Prepare product data for indexing
        $records = [];
        foreach ($products as $product) {
            $records[] = [
                'objectID' => $product->id,
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'price_without_reduction' => $product->price_without_reduction,
                'picture' => $product->picture,
                'link' => $product->link,
                'category' => $product->category,
                'available_for_order' => $product->available_for_order,
                'low_stock_alert' => $product->low_stock_alert,
            ];
        }

        // Store the products in Algolia
        $response = $index->saveObjects($records)->wait();
    }
}


