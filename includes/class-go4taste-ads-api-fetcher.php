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
     * In-request cache for PrestaShop tax rates by store and tax id.
     *
     * @since 1.2.0
     * @var array<string,float>
     */
    private $prestashop_tax_rate_cache = array();

    /**
     * Normalize store base URL (without trailing /api).
     *
     * @since 1.2.0
     * @param string $api_url URL from plugin settings.
     * @return string
     */
    private function normalize_prestashop_store_url($api_url) {
        $base = rtrim((string) $api_url, '/');
        $base = preg_replace('#/api$#i', '', $base);

        return rtrim((string) $base, '/');
    }

    /**
     * Build native PrestaShop Webservice endpoint URL.
     *
     * @since 1.2.0
     * @param string $api_url Base API URL from plugin settings.
     * @param string $path Relative path under /api.
     * @return string
     */
    private function build_prestashop_webservice_url($api_url, $path) {
        $base = $this->normalize_prestashop_store_url($api_url);
        $path = ltrim((string) $path, '/');

        return $base . '/api/' . $path;
    }

    /**
     * Decode XML from PrestaShop Webservice response.
     *
     * @since 1.2.0
     * @param string $xml_body Raw XML response body.
     * @return SimpleXMLElement|false
     */
    private function parse_prestashop_xml($xml_body) {
        if (!is_string($xml_body) || '' === trim($xml_body)) {
            return false;
        }

        $xml = @simplexml_load_string($xml_body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (false === $xml) {
            return false;
        }

        return $xml;
    }

    /**
     * Resolve multilingual field with preferred language id=1 and fallback to first non-empty value.
     *
     * @since 1.2.0
     * @param SimpleXMLElement|null $field XML field node.
     * @return string
     */
    private function get_prestashop_language_value($field) {
        if (!($field instanceof SimpleXMLElement)) {
            return '';
        }

        if (!isset($field->language)) {
            return trim((string) $field);
        }

        $fallback = '';
        foreach ($field->language as $language_node) {
            $value = trim((string) $language_node);
            if ('' === $value) {
                continue;
            }

            if ('' === $fallback) {
                $fallback = $value;
            }

            $attrs = $language_node->attributes();
            if (isset($attrs['id']) && '1' === (string) $attrs['id']) {
                return $value;
            }
        }

        return $fallback;
    }

    /**
     * Fetch tax rate (%) from native PrestaShop Webservice API.
     *
     * TODO(research): For this project we intentionally map product.id_tax_rules_group
     * directly to /api/taxes/<id>. Re-verify in future if tax_rule_groups -> taxes
     * indirection is ever needed for other stores.
     *
     * TODO(research): We currently accept rate without checking tax active/deleted flags,
     * based on assumption that inactive taxes are not assigned at product level.
     * Revisit if store data model changes.
     *
     * @since 1.2.0
     * @param string $tax_id Tax ID.
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @return float
     */
    private function fetch_prestashop_webservice_tax_rate($tax_id, $api_url, $api_key) {
        $tax_id = trim((string) $tax_id);
        if ('' === $tax_id || '' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            return 0.0;
        }

        $cache_key = $this->normalize_prestashop_store_url($api_url) . '|' . $tax_id;
        if (isset($this->prestashop_tax_rate_cache[$cache_key])) {
            return (float) $this->prestashop_tax_rate_cache[$cache_key];
        }

        $endpoint = $this->build_prestashop_webservice_url($api_url, 'taxes/' . rawurlencode($tax_id));
        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode((string) $api_key . ':'),
                    'Accept' => 'application/xml',
                ),
            )
        );

        if (is_wp_error($response)) {
            $this->prestashop_tax_rate_cache[$cache_key] = 0.0;
            return 0.0;
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            $this->prestashop_tax_rate_cache[$cache_key] = 0.0;
            return 0.0;
        }

        $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
        if (false === $xml || !isset($xml->tax->rate)) {
            $this->prestashop_tax_rate_cache[$cache_key] = 0.0;
            return 0.0;
        }

        $raw_rate = str_replace(',', '.', trim((string) $xml->tax->rate));
        $tax_rate = ('' !== $raw_rate && is_numeric($raw_rate)) ? (float) $raw_rate : 0.0;

        $this->prestashop_tax_rate_cache[$cache_key] = $tax_rate;

        return $tax_rate;
    }
    /**
     * Map native PrestaShop Webservice product XML to plugin product object.
     *
     * TODO(change): price_tax_exc currently mirrors final rounded gross price for backward
     * compatibility. Revisit and align it with tax-exclusive semantics when consumers are ready.
     *
     * @since 1.2.0
     * @param SimpleXMLElement $product_node Product XML node.
     * @param string           $api_url      Base URL from plugin settings.
     * @return stdClass|false
     */
    private function map_prestashop_webservice_product($product_node, $api_url) {
        if (!($product_node instanceof SimpleXMLElement)) {
            return false;
        }

        $product_id = trim((string) $product_node->id);
        if ('' === $product_id) {
            return false;
        }

        $name = $this->get_prestashop_language_value(isset($product_node->name) ? $product_node->name : null);
        $link_rewrite = $this->get_prestashop_language_value(isset($product_node->link_rewrite) ? $product_node->link_rewrite : null);

        $image_id = isset($product_node->id_default_image) ? trim((string) $product_node->id_default_image) : '';
        if ('' === $image_id && isset($product_node->id_default_image)) {
            $image_attrs = $product_node->id_default_image->attributes('xlink', true);
            if (isset($image_attrs['href'])) {
                $image_href = trim((string) $image_attrs['href']);
                if ('' !== $image_href) {
                    $image_id = basename($image_href);
                }
            }
        }

        $raw_price = str_replace(',', '.', trim((string) $product_node->price));
        $net_price = ('' !== $raw_price && is_numeric($raw_price)) ? (float) $raw_price : 0.0;

        $tax_id = isset($product_node->id_tax_rules_group) ? trim((string) $product_node->id_tax_rules_group) : '';
        $tax_rate = $this->fetch_prestashop_webservice_tax_rate($tax_id, $api_url, $api_key);

        $gross_price = $net_price;
        if ($tax_rate > 0) {
            $gross_price = $net_price * (1 + ($tax_rate / 100));
        }

        $rounded_price = (int) ceil($gross_price);

        $store_base_url = $this->normalize_prestashop_store_url($api_url);
        $price_with_currency = (string) $rounded_price . ' zł';
        $product_link = '';
        if ('' !== $link_rewrite) {
            $product_link = $store_base_url . '/' . $link_rewrite . '.html';
        }

        $product_picture = '';
        if ('' !== $image_id && '' !== $link_rewrite) {
            $product_picture = $store_base_url . '/' . rawurlencode($image_id) . '-home_default/' . rawurlencode($link_rewrite) . '.jpg';
        }

        $mapped = new stdClass();
        $mapped->id = $product_id;
        $mapped->name = $name;
        $mapped->price = $price_with_currency;
        $mapped->price_without_reduction = $price_with_currency;
        $mapped->link = $product_link;
        $mapped->picture = $product_picture;
        $mapped->category = '';
        $mapped->reference = isset($product_node->reference) ? trim((string) $product_node->reference) : '';
        $mapped->price_tax_exc = $price_with_currency;
        $mapped->available_for_order = 1;
        $mapped->low_stock_alert = 0;

        return $mapped;
    }

    /**
     * Fetch one product from native PrestaShop Webservice API.
     *
     * @since 1.2.0
     * @param string $product_id Product ID.
     * @param string $api_url    Base API URL.
     * @param string $api_key    Webservice API key.
     * @return stdClass|false
     */
    private function fetch_prestashop_webservice_product($product_id, $api_url, $api_key) {
        if ('' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            return false;
        }

        $endpoint = $this->build_prestashop_webservice_url($api_url, 'products/' . rawurlencode((string) $product_id));
        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode((string) $api_key . ':'),
                    'Accept' => 'application/xml',
                ),
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            return false;
        }

        $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
        if (false === $xml || !isset($xml->product)) {
            return false;
        }

        return $this->map_prestashop_webservice_product($xml->product, $api_url, $api_key);
    }

    /**
     * Fetch product IDs from native PrestaShop Webservice API list endpoint.
     *
     * @since 1.2.0
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @return array<int,string>
     */
    private function fetch_prestashop_webservice_product_ids($api_url, $api_key) {
        if ('' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            return array();
        }

        $endpoint = $this->build_prestashop_webservice_url($api_url, 'products');
        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode((string) $api_key . ':'),
                    'Accept' => 'application/xml',
                ),
            )
        );

        if (is_wp_error($response)) {
            return array();
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            return array();
        }

        $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
        if (false === $xml || !isset($xml->products->product)) {
            return array();
        }

        $ids = array();
        foreach ($xml->products->product as $product_node) {
            $attrs = $product_node->attributes();
            if (!isset($attrs['id'])) {
                continue;
            }

            $id = trim((string) $attrs['id']);
            if ('' !== $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

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
    
        $options = get_option('go4taste_ads_options');
        $prestashop_webservice_api_key = isset($options['prestashop_webservice_api_key']) ? sanitize_text_field($options['prestashop_webservice_api_key']) : '';

        if ('prestashop_webservice_api' === $api_type) {
            $native_product = $this->fetch_prestashop_webservice_product($product_id, $api_url, $prestashop_webservice_api_key);
            if ($native_product) {
                if ($cache_duration > 0) {
                    set_transient($transient_key, $native_product, $cache_duration * HOUR_IN_SECONDS);
                }

                return $native_product;
            }

            return false;
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
    * @param string $api_type The API type (e.g., 'prestashop_1_4', 'prestashop_1_5', 'prestashop_webservice_api', 'woocommerce').
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

            case 'prestashop_webservice_api':
                $options = get_option('go4taste_ads_options');
                $prestashop_webservice_api_key = isset($options['prestashop_webservice_api_key']) ? sanitize_text_field($options['prestashop_webservice_api_key']) : '';
                $product_ids = $this->fetch_prestashop_webservice_product_ids($api_url, $prestashop_webservice_api_key);

                if (empty($product_ids)) {
                    return false;
                }

                $products = array();
                foreach ($product_ids as $product_id) {
                    $mapped_product = $this->fetch_prestashop_webservice_product($product_id, $api_url, $prestashop_webservice_api_key);
                    if ($mapped_product) {
                        $products[] = $mapped_product;
                    }
                }

                return !empty($products) ? $products : false;
                
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
     * Fetch product IDs for sync job initialization.
     *
     * @since 1.2.0
     * @param string      $api_type API type.
     * @param string      $p_value PrestaShop legacy parameter.
     * @param string      $lang PrestaShop language.
     * @param string      $api_url API base URL.
     * @param string|null $consumer_key WooCommerce consumer key.
     * @param string|null $consumer_secret WooCommerce consumer secret.
     * @return array<int,string>
     */
    public function fetch_product_ids_for_sync($api_type, $p_value, $lang, $api_url, $consumer_key = null, $consumer_secret = null) {
        if ('prestashop_webservice_api' === $api_type) {
            $options = get_option('go4taste_ads_options');
            $prestashop_webservice_api_key = isset($options['prestashop_webservice_api_key']) ? sanitize_text_field($options['prestashop_webservice_api_key']) : '';
            return $this->fetch_prestashop_webservice_product_ids($api_url, $prestashop_webservice_api_key);
        }

        $products = $this->fetch_all_products($api_type, $p_value, $lang, $api_url, $consumer_key, $consumer_secret);
        if (empty($products) || !is_array($products)) {
            return array();
        }

        $ids = array();
        foreach ($products as $product) {
            if (isset($product->id) && '' !== trim((string) $product->id)) {
                $ids[] = trim((string) $product->id);
            }
        }

        return array_values(array_unique($ids));
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
                'objectID' => isset($product->id) ? $product->id : '',
                'id' => isset($product->id) ? $product->id : '',
                'name' => isset($product->name) ? $product->name : '',
                'price' => isset($product->price) ? $product->price : '',
                'price_without_reduction' => isset($product->price_without_reduction) ? $product->price_without_reduction : '',
                'picture' => isset($product->picture) ? $product->picture : '',
                'link' => isset($product->link) ? $product->link : '',
                'category' => isset($product->category) ? $product->category : '',
                'available_for_order' => isset($product->available_for_order) ? $product->available_for_order : 0,
                'low_stock_alert' => isset($product->low_stock_alert) ? $product->low_stock_alert : 0,
            ];
        }

        // Store the products in Algolia
        $response = $index->saveObjects($records)->wait();
    }
}




