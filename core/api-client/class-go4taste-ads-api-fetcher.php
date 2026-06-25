<?php

/**
 * The class responsible for fetching product data from PrestaShop Webservice API.
 *
 * @link       https://go4taste.pl/techlab
 * @since      1.0.0
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/core/api-client
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
     * In-request cache for country id by store and ISO code.
     *
     * @since 1.2.0
     * @var array<string,string>
     */
    private $prestashop_country_id_cache = array();

    /**
     * In-request cache for tax id by store, tax rules group and country id.
     *
     * @since 1.2.0
     * @var array<string,string>
     */
    private $prestashop_tax_id_by_group_cache = array();

    /**
     * In-request cache for PrestaShop specific prices by store and product id.
     *
     * @since 1.2.0
     * @var array<string,array<int,array<string,mixed>>>
     */
    private $prestashop_specific_prices_cache = array();

    /**
     * Perform authenticated PrestaShop Webservice GET request with IPv4 forcing.
     * Uses dedicated http_api_curl hook management to avoid conflicts with fetch_prestashop_webservice_product_ids.
     *
     * @since 1.2.0
     * @param string $endpoint Full endpoint URL.
     * @param string $api_key Webservice API key.
     * @param int    $timeout Request timeout.
     * @return array|WP_Error
     */
    private function prestashop_webservice_get($endpoint, $api_key, $timeout = 15) {
        // Force IPv4 to avoid LocalWP IPv6 hangs.
        // Use method callback to enable proper remove_action.
        add_action('http_api_curl', array($this, 'handle_ipv4_curl'));

        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout'   => (int) $timeout,
                'sslverify' => false,
                'headers'   => array(
                    'Authorization' => 'Basic ' . base64_encode((string) $api_key . ':'),
                    'Accept'        => 'application/xml',
                ),
            )
        );

        // Remove only the IPv4 callback for this specific request.
        remove_action('http_api_curl', array($this, 'handle_ipv4_curl'));

        return $response;
    }

    /**
     * Callback for forcing IPv4 via http_api_curl hook.
     *
     * @since 1.2.0
     * @param mixed $handle cURL handle.
     * @return void
     */
    public function handle_ipv4_curl($handle) {
        if (is_resource($handle) || ($handle instanceof \CurlHandle)) {
            @curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
    }

    /**
     * Format numeric price to Polish currency decimal format.
     *
     * @since 1.2.0
     * @param float $price Price value.
     * @return string
     */
    private function format_prestashop_price_standard($price) {
        return number_format(max(0.0, (float) $price), 2, ',', '');
    }

    /**
     * Parse decimal value from PrestaShop XML fields.
     *
     * @since 1.2.0
     * @param mixed $value Raw input value.
     * @return float|null
     */
    private function parse_prestashop_decimal($value) {
        $normalized = str_replace(',', '.', trim((string) $value));
        if ('' === $normalized || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * Parse integer value from PrestaShop XML fields.
     *
     * @since 1.2.0
     * @param mixed $value Raw input value.
     * @return int
     */
    private function parse_prestashop_int($value) {
        return (int) trim((string) $value);
    }

    /**
     * Check whether specific price date range is active now.
     *
     * @since 1.2.0
     * @param array<string,mixed> $specific_price Specific price row.
     * @return bool
     */
    private function is_prestashop_specific_price_active_now($specific_price) {
        $now_utc = time();

        $from = isset($specific_price['from']) ? trim((string) $specific_price['from']) : '';
        if ('' !== $from && '0000-00-00 00:00:00' !== $from) {
            $from_ts = strtotime($from . ' UTC');
            if (false === $from_ts || $from_ts > $now_utc) {
                return false;
            }
        }

        $to = isset($specific_price['to']) ? trim((string) $specific_price['to']) : '';
        if ('' !== $to && '0000-00-00 00:00:00' !== $to) {
            $to_ts = strtotime($to . ' UTC');
            if (false === $to_ts || $to_ts < $now_utc) {
                return false;
            }
        }

        return true;
    }

    /**
     * Accept global/default scope only when runtime context is unavailable.
     *
     * @since 1.2.0
     * @param array<string,mixed> $specific_price Specific price row.
     * @return bool
     */
    private function is_prestashop_specific_price_scope_supported($specific_price) {
        $scope_keys = array('id_shop', 'id_currency', 'id_country', 'id_group', 'id_customer');

        foreach ($scope_keys as $scope_key) {
            $scope_value = isset($specific_price[$scope_key]) ? trim((string) $specific_price[$scope_key]) : '0';
            if ('' !== $scope_value && '0' !== $scope_value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fetch specific_prices rows for one product from PrestaShop Webservice API.
     *
     * @since 1.2.0
     * @param string $product_id Product ID.
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @return array<int,array<string,mixed>>
     */
    private function fetch_prestashop_specific_prices($product_id, $api_url, $api_key) {
        $product_id = trim((string) $product_id);
        if ('' === $product_id || '' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            error_log('[G4T Sync Debug] fetch_prestashop_specific_prices: missing params for product ' . $product_id);
            return array();
        }

        $cache_key = $this->normalize_prestashop_store_url($api_url) . '|specific_prices|' . $product_id;
        if (isset($this->prestashop_specific_prices_cache[$cache_key])) {
            return $this->prestashop_specific_prices_cache[$cache_key];
        }

        $query = http_build_query(
            array(
                'filter[id_product]' => '[' . $product_id . ']',
                'display' => 'full',
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $endpoint = $this->build_prestashop_webservice_url($api_url, 'specific_prices?' . $query);
        error_log('[G4T Sync Debug] fetch_prestashop_specific_prices: fetching ' . $endpoint);
        $response = $this->prestashop_webservice_get($endpoint, $api_key, 15);
        if (is_wp_error($response)) {
            error_log('[G4T Sync Debug] fetch_prestashop_specific_prices WP_ERROR: ' . $response->get_error_message());
            $this->prestashop_specific_prices_cache[$cache_key] = array();
            return array();
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        error_log('[G4T Sync Debug] fetch_prestashop_specific_prices HTTP code: ' . $http_code);
        if ($http_code < 200 || $http_code >= 300) {
            error_log('[G4T Sync Debug] fetch_prestashop_specific_prices: HTTP error ' . $http_code . ' for product ' . $product_id);
            $this->prestashop_specific_prices_cache[$cache_key] = array();
            return array();
        }

        $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
        if (false === $xml) {
            error_log('[G4T Sync Debug] fetch_prestashop_specific_prices: XML parse failed for product ' . $product_id);
            $this->prestashop_specific_prices_cache[$cache_key] = array();
            return array();
        }
        if (!isset($xml->specific_prices->specific_price)) {
            error_log('[G4T Sync Debug] fetch_prestashop_specific_prices: no specific_prices element for product ' . $product_id);
            $this->prestashop_specific_prices_cache[$cache_key] = array();
            return array();
        }

        $specific_prices = array();
        foreach ($xml->specific_prices->specific_price as $specific_price_node) {
            $specific_prices[] = array(
                'id' => isset($specific_price_node->id) ? trim((string) $specific_price_node->id) : '',
                'id_shop' => isset($specific_price_node->id_shop) ? trim((string) $specific_price_node->id_shop) : '0',
                'id_currency' => isset($specific_price_node->id_currency) ? trim((string) $specific_price_node->id_currency) : '0',
                'id_country' => isset($specific_price_node->id_country) ? trim((string) $specific_price_node->id_country) : '0',
                'id_group' => isset($specific_price_node->id_group) ? trim((string) $specific_price_node->id_group) : '0',
                'id_customer' => isset($specific_price_node->id_customer) ? trim((string) $specific_price_node->id_customer) : '0',
                'from_quantity' => isset($specific_price_node->from_quantity) ? trim((string) $specific_price_node->from_quantity) : '1',
                'price' => isset($specific_price_node->price) ? trim((string) $specific_price_node->price) : '-1',
                'reduction' => isset($specific_price_node->reduction) ? trim((string) $specific_price_node->reduction) : '0',
                'reduction_type' => isset($specific_price_node->reduction_type) ? trim((string) $specific_price_node->reduction_type) : '',
                'reduction_tax' => isset($specific_price_node->reduction_tax) ? trim((string) $specific_price_node->reduction_tax) : '0',
                'from' => isset($specific_price_node->from) ? trim((string) $specific_price_node->from) : '',
                'to' => isset($specific_price_node->to) ? trim((string) $specific_price_node->to) : '',
            );
        }

        $this->prestashop_specific_prices_cache[$cache_key] = $specific_prices;

        return $specific_prices;
    }

    /**
     * Select one active specific price candidate for default context.
     *
     * @since 1.2.0
     * @param array<int,array<string,mixed>> $specific_prices Specific prices rows.
     * @param string $product_id Product ID.
     * @return array<string,mixed>|null
     */
    private function select_prestashop_specific_price_candidate($specific_prices, $product_id) {
        if (!is_array($specific_prices) || empty($specific_prices)) {
            return null;
        }

        $candidates = array();
        foreach ($specific_prices as $specific_price) {
            if (!is_array($specific_price)) {
                continue;
            }

            if (!$this->is_prestashop_specific_price_scope_supported($specific_price)) {
                continue;
            }

            if (!$this->is_prestashop_specific_price_active_now($specific_price)) {
                continue;
            }

            $from_quantity = isset($specific_price['from_quantity']) ? $this->parse_prestashop_int($specific_price['from_quantity']) : 1;
            if ($from_quantity > 1) {
                continue;
            }

            $reduction = isset($specific_price['reduction']) ? $this->parse_prestashop_decimal($specific_price['reduction']) : null;
            $has_reduction = null !== $reduction && $reduction > 0;

            $override_price = isset($specific_price['price']) ? $this->parse_prestashop_decimal($specific_price['price']) : null;
            $has_price_override = null !== $override_price && $override_price >= 0;

            if (!$has_reduction && !$has_price_override) {
                continue;
            }

            $candidate = apply_filters(
                'go4taste/ads/prestashop_specific_price_candidate',
                $specific_price,
                (string) $product_id,
                $specific_prices
            );

            if (!is_array($candidate)) {
                continue;
            }

            $candidates[] = $candidate;
        }

        if (empty($candidates)) {
            return null;
        }

        usort(
            $candidates,
            function($a, $b) {
                $a_id = isset($a['id']) ? (int) $a['id'] : 0;
                $b_id = isset($b['id']) ? (int) $b['id'] : 0;

                return $b_id <=> $a_id;
            }
        );

        return $candidates[0];
    }

    /**
     * Calculate gross price after specific_prices discount rules.
     *
     * @since 1.2.0
     * @param float $base_net_price Base net product price.
     * @param float $base_gross_price Base gross product price.
     * @param float $tax_rate Tax rate in percent.
     * @param array<string,mixed> $specific_price Selected specific price.
     * @return float
     */
    private function calculate_prestashop_discounted_gross_price($base_net_price, $base_gross_price, $tax_rate, $specific_price) {
        $base_net_price = max(0.0, (float) $base_net_price);
        $base_gross_price = max(0.0, (float) $base_gross_price);
        $tax_rate = max(0.0, (float) $tax_rate);

        $rule_base_net = $base_net_price;
        $rule_base_gross = $base_gross_price;

        $override_price = isset($specific_price['price']) ? $this->parse_prestashop_decimal($specific_price['price']) : null;
        if (null !== $override_price && $override_price >= 0) {
            $rule_base_net = $override_price;
            $rule_base_gross = $rule_base_net * (1 + ($tax_rate / 100));
        }

        $final_gross = $rule_base_gross;
        $reduction = isset($specific_price['reduction']) ? $this->parse_prestashop_decimal($specific_price['reduction']) : null;
        $reduction = null === $reduction ? 0.0 : max(0.0, (float) $reduction);

        $reduction_type = isset($specific_price['reduction_type']) ? strtolower(trim((string) $specific_price['reduction_type'])) : '';
        $reduction_tax = isset($specific_price['reduction_tax']) ? $this->parse_prestashop_int($specific_price['reduction_tax']) : 0;

        if ('percentage' === $reduction_type && $reduction > 0) {
            $percentage = $reduction;
            if ($percentage > 1) {
                $percentage = $percentage / 100;
            }
            $percentage = min(1.0, max(0.0, $percentage));

            if (1 === $reduction_tax) {
                $final_gross = $rule_base_gross * (1 - $percentage);
            } else {
                $final_net = $rule_base_net * (1 - $percentage);
                $final_gross = $final_net * (1 + ($tax_rate / 100));
            }
        } elseif ('amount' === $reduction_type && $reduction > 0) {
            if (1 === $reduction_tax) {
                $final_gross = $rule_base_gross - $reduction;
            } else {
                $final_net = $rule_base_net - $reduction;
                $final_gross = $final_net * (1 + ($tax_rate / 100));
            }
        }

        return max(0.0, (float) $final_gross);
    }

    /**
     * Resolve configured tax country ISO for PrestaShop mapping.
     *
     * @since 1.2.0
     * @return string
     */
    private function get_prestashop_tax_country_iso() {
        $options = get_option('go4taste_ads_options');
        $iso = '';

        if (is_array($options) && isset($options['prestashop_tax_country_iso'])) {
            $iso = sanitize_text_field($options['prestashop_tax_country_iso']);
        }

        $iso = strtoupper(trim((string) $iso));
        if ('' === $iso || !preg_match('/^[A-Z]{2}$/', $iso)) {
            return 'PL';
        }

        return $iso;
    }

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
     * @since 1.2.0
     * @param string $tax_id Tax ID.
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @return float
     */
    private function fetch_prestashop_webservice_tax_rate($tax_id, $api_url, $api_key) {
        $tax_rate_result = $this->fetch_prestashop_webservice_tax_rate_with_meta($tax_id, $api_url, $api_key);

        return isset($tax_rate_result['rate']) ? (float) $tax_rate_result['rate'] : 0.0;
    }

    /**
     * Fetch tax rate with diagnostic metadata.
     *
     * @since 1.2.0
     * @param string $tax_id Tax ID.
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @return array<string,mixed>
     */
    private function fetch_prestashop_webservice_tax_rate_with_meta($tax_id, $api_url, $api_key) {
        $result = array(
            'rate' => 0.0,
            'http_code' => 0,
        );

        $tax_id = trim((string) $tax_id);
        if ('' === $tax_id || '' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            return $result;
        }

        $cache_key = $this->normalize_prestashop_store_url($api_url) . '|' . $tax_id;
        if (isset($this->prestashop_tax_rate_cache[$cache_key])) {
            $result['rate'] = (float) $this->prestashop_tax_rate_cache[$cache_key];
            return $result;
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
            return $result;
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        $result['http_code'] = $http_code;
        if ($http_code < 200 || $http_code >= 300) {
            $this->prestashop_tax_rate_cache[$cache_key] = 0.0;
            return $result;
        }

        $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
        if (false === $xml || !isset($xml->tax->rate)) {
            $this->prestashop_tax_rate_cache[$cache_key] = 0.0;
            return $result;
        }

        if (isset($xml->tax->active) && '0' === trim((string) $xml->tax->active)) {
            $this->prestashop_tax_rate_cache[$cache_key] = 0.0;
            return $result;
        }

        if (isset($xml->tax->deleted) && '1' === trim((string) $xml->tax->deleted)) {
            $this->prestashop_tax_rate_cache[$cache_key] = 0.0;
            return $result;
        }

        $raw_rate = str_replace(',', '.', trim((string) $xml->tax->rate));
        $tax_rate = ('' !== $raw_rate && is_numeric($raw_rate)) ? (float) $raw_rate : 0.0;
        $this->prestashop_tax_rate_cache[$cache_key] = $tax_rate;

        $result['rate'] = $tax_rate;

        return $result;
    }

    /**
     * Resolve country ID from PrestaShop Webservice by ISO code.
     *
     * @since 1.2.0
     * @param string $iso_code Country ISO code.
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @return string
     */
    private function fetch_prestashop_country_id_by_iso($iso_code, $api_url, $api_key) {
        $iso_code = strtoupper(trim((string) $iso_code));
        if ('' === $iso_code || '' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            return '';
        }

        $cache_key = $this->normalize_prestashop_store_url($api_url) . '|country|' . $iso_code;
        if (isset($this->prestashop_country_id_cache[$cache_key])) {
            return (string) $this->prestashop_country_id_cache[$cache_key];
        }

        $query = http_build_query(
            array(
                'filter[iso_code]' => '[' . $iso_code . ']',
                'display' => '[id]',
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $endpoint = $this->build_prestashop_webservice_url($api_url, 'countries?' . $query);
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
            $this->prestashop_country_id_cache[$cache_key] = '';
            return '';
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            $this->prestashop_country_id_cache[$cache_key] = '';
            return '';
        }

        $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
        if (false === $xml || !isset($xml->countries->country)) {
            $this->prestashop_country_id_cache[$cache_key] = '';
            return '';
        }

        $country_id = '';
        foreach ($xml->countries->country as $country_node) {
            if (isset($country_node->id) && '' !== trim((string) $country_node->id)) {
                $country_id = trim((string) $country_node->id);
                break;
            }

            $attrs = $country_node->attributes();
            if (isset($attrs['id']) && '' !== trim((string) $attrs['id'])) {
                $country_id = trim((string) $attrs['id']);
                break;
            }
        }

        $this->prestashop_country_id_cache[$cache_key] = $country_id;

        return $country_id;
    }

    /**
     * Resolve tax ID for tax rules group and country from PrestaShop Webservice.
     *
     * @since 1.2.0
     * @param string $tax_rules_group_id Tax rules group ID.
     * @param string $country_id Country ID.
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @return string
     */
    private function fetch_prestashop_tax_id_from_rules_group($tax_rules_group_id, $country_id, $api_url, $api_key) {
        $tax_rules_group_id = trim((string) $tax_rules_group_id);
        $country_id = trim((string) $country_id);
        if ('' === $tax_rules_group_id || '' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            return '';
        }

        $cache_key = $this->normalize_prestashop_store_url($api_url) . '|group|' . $tax_rules_group_id . '|country|' . $country_id;
        if (isset($this->prestashop_tax_id_by_group_cache[$cache_key])) {
            return (string) $this->prestashop_tax_id_by_group_cache[$cache_key];
        }

        $query = http_build_query(
            array(
                'filter[id_tax_rules_group]' => '[' . $tax_rules_group_id . ']',
                'display' => '[id,id_tax,id_country]',
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $endpoint = $this->build_prestashop_webservice_url($api_url, 'tax_rules?' . $query);
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
            $this->prestashop_tax_id_by_group_cache[$cache_key] = '';
            return '';
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        if ($http_code < 200 || $http_code >= 300) {
            $this->prestashop_tax_id_by_group_cache[$cache_key] = '';
            return '';
        }

        $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
        if (false === $xml || !isset($xml->tax_rules->tax_rule)) {
            $this->prestashop_tax_id_by_group_cache[$cache_key] = '';
            return '';
        }

        $fallback_tax_id = '';
        $selected_tax_id = '';

        foreach ($xml->tax_rules->tax_rule as $tax_rule) {
            $rule_tax_id = isset($tax_rule->id_tax) ? trim((string) $tax_rule->id_tax) : '';
            if ('' === $rule_tax_id) {
                continue;
            }

            if ('' === $fallback_tax_id) {
                $fallback_tax_id = $rule_tax_id;
            }

            $rule_country_id = isset($tax_rule->id_country) ? trim((string) $tax_rule->id_country) : '';
            if ('' !== $country_id && $rule_country_id === $country_id) {
                $selected_tax_id = $rule_tax_id;
                break;
            }
        }

        if ('' === $selected_tax_id) {
            $selected_tax_id = $fallback_tax_id;
        }

        $this->prestashop_tax_id_by_group_cache[$cache_key] = $selected_tax_id;

        return $selected_tax_id;
    }

    /**
     * Resolve tax rate (%) using PrestaShop tax rules group.
     *
     * @since 1.2.0
     * @param string $tax_rules_group_id Tax rules group ID.
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @return float
     */
    private function fetch_prestashop_webservice_tax_rate_from_group($tax_rules_group_id, $api_url, $api_key) {
        $tax_rules_group_id = trim((string) $tax_rules_group_id);
        if ('' === $tax_rules_group_id || '' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            return 0.0;
        }

        $tax_country_iso = $this->get_prestashop_tax_country_iso();
        $country_id = $this->fetch_prestashop_country_id_by_iso($tax_country_iso, $api_url, $api_key);
        $tax_id = $this->fetch_prestashop_tax_id_from_rules_group($tax_rules_group_id, $country_id, $api_url, $api_key);
        if ('' === $tax_id) {
            // Backward-compatible fallback for stores where group id equals tax id.
            return $this->fetch_prestashop_webservice_tax_rate($tax_rules_group_id, $api_url, $api_key);
        }

        $tax_rate_result = $this->fetch_prestashop_webservice_tax_rate_with_meta($tax_id, $api_url, $api_key);
        $tax_rate = isset($tax_rate_result['rate']) ? (float) $tax_rate_result['rate'] : 0.0;
        if ($tax_rate > 0) {
            return $tax_rate;
        }

        $http_code = isset($tax_rate_result['http_code']) ? (int) $tax_rate_result['http_code'] : 0;
        if (in_array($http_code, array(401, 403, 404), true)) {
            // Fallback only for access/resource errors on taxes/<id>.
            return $this->fetch_prestashop_webservice_tax_rate($tax_rules_group_id, $api_url, $api_key);
        }

        return 0.0;
    }

    /**
     * Build cache key for permissions verification report.
     *
     * @since 1.2.0
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @param string $country_iso Country ISO.
     * @return string
     */
    private function get_prestashop_permissions_cache_key($api_url, $api_key, $country_iso) {
        return 'g4t_ps_perm_' . md5($this->normalize_prestashop_store_url($api_url) . '|' . strtoupper(trim((string) $country_iso)) . '|' . trim((string) $api_key));
    }

    /**
     * Return cached PrestaShop permissions verification report.
     *
     * @since 1.2.0
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @param string $country_iso Country ISO.
     * @return array<string,mixed>|false
     */
    public function get_cached_prestashop_webservice_permissions_report($api_url, $api_key, $country_iso = 'PL') {
        $api_url = trim((string) $api_url);
        $api_key = trim((string) $api_key);
        $country_iso = strtoupper(trim((string) $country_iso));

        if ('' === $country_iso || !preg_match('/^[A-Z]{2}$/', $country_iso)) {
            $country_iso = 'PL';
        }

        if ('' === $api_url || '' === $api_key) {
            return false;
        }

        $cache_key = $this->get_prestashop_permissions_cache_key($api_url, $api_key, $country_iso);
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['resources'])) {
            return $cached;
        }

        return false;
    }

    /**
     * Verify required PrestaShop Webservice API permissions for this plugin.
     *
     * @since 1.2.0
     * @param string $api_url Base API URL.
     * @param string $api_key Webservice API key.
     * @param string $country_iso Country ISO used for countries endpoint check.
     * @return array<string,mixed>
     */
    public function verify_prestashop_webservice_permissions($api_url, $api_key, $country_iso = 'PL') {
        $api_url = trim((string) $api_url);
        $api_key = trim((string) $api_key);
        $country_iso = strtoupper(trim((string) $country_iso));

        if ('' === $country_iso || !preg_match('/^[A-Z]{2}$/', $country_iso)) {
            $country_iso = 'PL';
        }

        if ('' === $api_url || '' === $api_key) {
            return array(
                'ok' => false,
                'country_iso' => $country_iso,
                'checked_at' => current_time('mysql'),
                'resources' => array(),
                'message' => 'Brak API URL lub klucza API.',
            );
        }

        $cache_key = $this->get_prestashop_permissions_cache_key($api_url, $api_key, $country_iso);
        $cached = get_transient($cache_key);
        if (is_array($cached) && isset($cached['resources'])) {
            return $cached;
        }

        $countries_query = http_build_query(
            array(
                'filter[iso_code]' => '[' . $country_iso . ']',
                'display' => '[id]',
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $checks = array(
            'products' => 'products?display=[id]&limit=1',
            'taxes' => 'taxes?display=[id]&limit=1',
            'tax_rules' => 'tax_rules?display=[id]&limit=1',
            'countries' => 'countries?' . $countries_query,
        );

        $resources = array();
        $all_ok = true;

        foreach ($checks as $resource => $path) {
            $endpoint = $this->build_prestashop_webservice_url($api_url, $path);
            $resource_status = array(
                'ok' => false,
                'http_code' => 0,
                'message' => '',
            );

            $response = wp_remote_get(
                $endpoint,
                array(
                    'timeout' => 6,
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode((string) $api_key . ':'),
                        'Accept' => 'application/xml',
                    ),
                )
            );

            if (is_wp_error($response)) {
                $resource_status['message'] = 'Blad transportu: ' . $response->get_error_message();
                $resources[$resource] = $resource_status;
                $all_ok = false;
                continue;
            }

            $http_code = (int) wp_remote_retrieve_response_code($response);
            $resource_status['http_code'] = $http_code;

            if ($http_code >= 200 && $http_code < 300) {
                $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
                if (false === $xml) {
                    $resource_status['message'] = 'Niepoprawna odpowiedz XML.';
                    $all_ok = false;
                } else {
                    $resource_status['ok'] = true;
                    $resource_status['message'] = 'OK';
                }
            } else {
                if (401 === $http_code || 403 === $http_code) {
                    $resource_status['message'] = 'Brak uprawnienia do odczytu zasobu.';
                } else {
                    $resource_status['message'] = 'HTTP ' . $http_code;
                }
                $all_ok = false;
            }

            $resources[$resource] = $resource_status;
        }

        $result = array(
            'ok' => $all_ok,
            'country_iso' => $country_iso,
            'checked_at' => current_time('mysql'),
            'resources' => $resources,
            'message' => $all_ok ? 'Wszystkie wymagane uprawnienia wygladaja poprawnie.' : 'Wykryto brakujace uprawnienia lub problemy dostepu.',
        );

        set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);

        return $result;
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
     * @param string           $api_key      Webservice API key.
     * @return stdClass|false
     */
    private function map_prestashop_webservice_product($product_node, $api_url, $api_key) {
        if (!($product_node instanceof SimpleXMLElement)) {
            return false;
        }

        $product_id = trim((string) $product_node->id);
        if ('' === $product_id) {
            return false;
        }

        try {
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

            $tax_rules_group_id = isset($product_node->id_tax_rules_group) ? trim((string) $product_node->id_tax_rules_group) : '';
            $tax_rate = 0.0;
            if ('' !== $tax_rules_group_id) {
                $tax_rate = $this->fetch_prestashop_webservice_tax_rate_from_group($tax_rules_group_id, $api_url, $api_key);
            }

            $gross_price = $net_price;
            if ($tax_rate > 0) {
                $gross_price = $net_price * (1 + ($tax_rate / 100));
            }

            $base_rounded_price = $this->format_prestashop_price_with_threshold($gross_price);

            $specific_prices = $this->fetch_prestashop_specific_prices($product_id, $api_url, $api_key);
            $selected_specific_price = $this->select_prestashop_specific_price_candidate($specific_prices, $product_id);

            $discounted_gross_price = $gross_price;
            if (is_array($selected_specific_price)) {
                $discounted_gross_price = $this->calculate_prestashop_discounted_gross_price($net_price, $gross_price, $tax_rate, $selected_specific_price);
                if ($discounted_gross_price >= $gross_price) {
                    // Promo rule doesn't lower price, fallback to base
                    $discounted_gross_price = $gross_price;
                }
            }
            // If no active specific_price, discounted_gross_price stays equal to base gross_price

            $discounted_rounded_price = $base_rounded_price;
            if (is_array($selected_specific_price) && $discounted_gross_price < $gross_price) {
                // Only format with standard formatting if there's actual discount
                $discounted_rounded_price = $this->format_prestashop_price_standard($discounted_gross_price);
            }

            $store_base_url = $this->normalize_prestashop_store_url($api_url);
            $price_with_currency = $discounted_rounded_price . ' zł';
            $price_without_reduction_with_currency = $base_rounded_price . ' zł';

            $mapped_prices = apply_filters(
                'go4taste/ads/prestashop_mapped_prices',
                array(
                    'price' => $price_with_currency,
                    'price_without_reduction' => $price_without_reduction_with_currency,
                    'specific_price' => $selected_specific_price,
                ),
                $product_id,
                $product_node
            );

            if (is_array($mapped_prices)) {
                if (isset($mapped_prices['price']) && is_string($mapped_prices['price']) && '' !== trim($mapped_prices['price'])) {
                    $price_with_currency = $mapped_prices['price'];
                }
                if (isset($mapped_prices['price_without_reduction']) && is_string($mapped_prices['price_without_reduction']) && '' !== trim($mapped_prices['price_without_reduction'])) {
                    $price_without_reduction_with_currency = $mapped_prices['price_without_reduction'];
                }
            }
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
            $mapped->price_without_reduction = $price_without_reduction_with_currency;
            $mapped->link = $product_link;
            $mapped->picture = $product_picture;
            $mapped->category = '';
            $mapped->reference = isset($product_node->reference) ? trim((string) $product_node->reference) : '';
            $mapped->price_tax_exc = $price_with_currency;
            $mapped->available_for_order = 1;
            $mapped->low_stock_alert = 0;

            // Debug: log mapped product for specific product (e.g., 206 with promotion)
            if (in_array($product_id, array('206', '172'), true)) {
                error_log('[G4T Sync Debug] Mapped product ' . $product_id . ': price=' . $mapped->price . ', price_without_reduction=' . $mapped->price_without_reduction);
            }

            return $mapped;
        } catch (Throwable $e) {
            // Fallback: return product with base price if any error occurs in specific price calculation
            error_log('[G4T Sync Debug] map_prestashop_webservice_product Exception for product ' . $product_id . ': ' . $e->getMessage());
            
            // Build minimal product object with base price
            $raw_price = str_replace(',', '.', trim((string) $product_node->price));
            $net_price = ('' !== $raw_price && is_numeric($raw_price)) ? (float) $raw_price : 0.0;
            $base_price_formatted = $this->format_prestashop_price_with_threshold($net_price) . ' zł';
            
            $mapped = new stdClass();
            $mapped->id = $product_id;
            $mapped->name = $this->get_prestashop_language_value(isset($product_node->name) ? $product_node->name : null);
            $mapped->price = $base_price_formatted;
            $mapped->price_without_reduction = $base_price_formatted;
            $mapped->link = '';
            $mapped->picture = '';
            $mapped->category = '';
            $mapped->reference = isset($product_node->reference) ? trim((string) $product_node->reference) : '';
            $mapped->price_tax_exc = $base_price_formatted;
            $mapped->available_for_order = 1;
            $mapped->low_stock_alert = 0;

            return $mapped;
        }
    }

    /**
     * Format PrestaShop gross price with custom threshold rounding.
     *
     * Rule:
     * - decimal part below 0.45 => cut down to one decimal place.
     * - decimal part from 0.45 => move to ,50.
     * - output format always keeps two decimal places.
     *
     * @since 1.2.0
     * @param float $gross_price Gross price value.
     * @return string
     */
    private function format_prestashop_price_with_threshold($gross_price) {
        $gross_price = max(0.0, (float) $gross_price);
        $normalized_cents = (int) round($gross_price * 100);
        $integer_part = intdiv($normalized_cents, 100);
        $decimal_cents = $normalized_cents % 100;

        if ($decimal_cents >= 45) {
            return number_format($integer_part + 0.5, 2, ',', '');
        }

        $decimal_tenths = intdiv($decimal_cents, 10);
        $cut_price = $integer_part + ($decimal_tenths / 10);
        return number_format($cut_price, 2, ',', '');
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
            error_log('[G4T Sync Debug] fetch_prestashop_webservice_product: missing credentials for product ' . $product_id);
            return false;
        }

        error_log('[G4T Sync Debug] fetch_prestashop_webservice_product START for product ' . $product_id);

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
        $start_time = microtime(true);
        error_log('[G4T Sync] START fetching IDs... Time: ' . $start_time);

        if ('' === trim((string) $api_key) || '' === trim((string) $api_url)) {
            return array();
        }

        $query = http_build_query(
            array(
                'display' => '[id]',
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $endpoint = $this->build_prestashop_webservice_url($api_url, 'products');
        $endpoint .= (false === strpos($endpoint, '?') ? '?' : '&') . $query;

        $before_curl = microtime(true);
        error_log('[G4T Sync] BEFORE wp_remote_get... URL: ' . $endpoint . ' Time: ' . $before_curl);

        // Force IPv4 to avoid IPv6 hangs
        add_action('http_api_curl', function($handle) {
            curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        });

        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout'   => 15,
                'sslverify' => false,
                'headers'   => array(
                    'Authorization' => 'Basic ' . base64_encode((string) $api_key . ':'),
                    'Accept'        => 'application/xml',
                ),
            )
        );

        // Remove IPv4 forcing hook immediately after call
        remove_all_actions('http_api_curl');

        $after_curl = microtime(true);
        error_log('[G4T Sync] AFTER wp_remote_get... Duration: ' . ($after_curl - $before_curl) . 's');

        if (is_wp_error($response)) {
            error_log('[G4T Sync] WP_ERROR: ' . $response->get_error_message());
            return array();
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        error_log('[G4T Sync] AFTER wp_remote_get... HTTP Code: ' . $http_code);

        if ($http_code < 200 || $http_code >= 300) {
            error_log('[G4T Sync] HTTP error, code not in success range.');
            return array();
        }

        $before_parse = microtime(true);
        $xml = $this->parse_prestashop_xml(wp_remote_retrieve_body($response));
        $after_parse = microtime(true);

        if (false === $xml || !isset($xml->products->product)) {
            error_log('[G4T Sync] XML parsing failed or no products found.');
            return array();
        }

        $ids = array();
        $attr_count = 0;
        $node_count = 0;

        foreach ($xml->products->product as $product_node) {
            // Try both: ID as attribute AND ID as node element
            $id = null;

            // Check ID as attribute first
            $attrs = $product_node->attributes();
            if (isset($attrs['id'])) {
                $id = trim((string) $attrs['id']);
                if ('' !== $id) {
                    $attr_count++;
                }
            }

            // If not found as attribute, check ID as node element
            if (null === $id || '' === $id) {
                if (isset($product_node->id)) {
                    $id = trim((string) $product_node->id);
                    if ('' !== $id) {
                        $node_count++;
                    }
                }
            }

            if (null !== $id && '' !== $id) {
                $ids[] = $id;
            }
        }

        $unique_ids = array_values(array_unique($ids));
        $end_time = microtime(true);

        error_log('[G4T Sync] AFTER XML parsing... Found ' . count($unique_ids) . ' items. (Attr mode: ' . $attr_count . ', Node mode: ' . $node_count . '). Total time: ' . ($end_time - $start_time) . 's');

        return $unique_ids;
    }

    /**
    * Fetch product data from PrestaShop Webservice API.
    *
    * WARNING: Use only for backend sync, never for frontend rendering.
     *
     * @since 1.0.0
     * @since 1.1.0 Added transient caching with API fields filtering to avoid database overload
     * @param string $product_id The product ID.
     * @param string $lang The language for PrestaShop APIs.
     * @param string $api_url The API base URL.
     * @return object|false The product data object, or false if not found.
     */
    public function fetch_product_data($product_id, $lang, $api_url) {
        $options = get_option('go4taste_ads_options');
        $prestashop_webservice_api_key = isset($options['prestashop_webservice_api_key']) ? sanitize_text_field($options['prestashop_webservice_api_key']) : '';

        return $this->fetch_prestashop_webservice_product($product_id, $api_url, $prestashop_webservice_api_key);
    }

    /**
     * Fetch all products data from PrestaShop Webservice API.
     *
     * @since 1.0.0
     * @param string $lang The language for PrestaShop APIs.
     * @param string $api_url The API base URL.
     * @return array|false An array of all products, or false if not found.
     */
    public function fetch_all_products($lang, $api_url) {
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
    }

    /**
     * Fetch product IDs for sync job initialization.
     *
     * @since 1.2.0
     * @param string      $lang PrestaShop language.
     * @param string      $api_url API base URL.
     * @return array<int,string>
     */
    public function fetch_product_ids_for_sync($lang, $api_url) {
        $options = get_option('go4taste_ads_options');
        $prestashop_webservice_api_key = isset($options['prestashop_webservice_api_key']) ? sanitize_text_field($options['prestashop_webservice_api_key']) : '';

        // Sync start should only initialize the queue quickly; product payloads are fetched in batches.
        return $this->fetch_prestashop_webservice_product_ids($api_url, $prestashop_webservice_api_key);
    }

    /**
     * Store product data to Algolia.
     *
     * @since 1.0.0
     * @param array $products An array of products to store in Algolia.
     * @return void
     */
    public function store_products_to_algolia($products) {
        error_log('[G4T Sync] store_products_to_algolia: processing ' . count($products) . ' products');

        // Retrieve Algolia App ID and Admin API Key from settings
        $options = get_option('go4taste_ads_options');
        $algolia_app_id = isset($options['algolia_app_id']) ? esc_attr($options['algolia_app_id']) : '';
        $algolia_admin_api_key = isset($options['algolia_admin_api_key']) ? esc_attr($options['algolia_admin_api_key']) : '';

        if (empty($algolia_app_id) || empty($algolia_admin_api_key)) {
            error_log('[G4T Sync] store_products_to_algolia: missing Algolia credentials');
            return;
        }

        try {
            // Create the Algolia client using your App ID and Admin API key
            $client = SearchClient::create( $algolia_app_id, $algolia_admin_api_key );
            
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

            error_log('[G4T Sync] store_products_to_algolia: prepared ' . count($records) . ' records, sending to Algolia');

            // Store the products in Algolia
            $response = $index->saveObjects($records)->wait();
            error_log('[G4T Sync] store_products_to_algolia: Algolia response received, success');
        } catch (Throwable $e) {
            error_log('[G4T Sync] store_products_to_algolia Exception: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }
}




