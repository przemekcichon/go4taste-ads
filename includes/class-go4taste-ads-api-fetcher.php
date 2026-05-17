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

        $rounded_price = $this->format_prestashop_price_with_threshold($gross_price);

        $store_base_url = $this->normalize_prestashop_store_url($api_url);
        $price_with_currency = $rounded_price . ' zł';
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
        $cache_duration = isset($options['cache_duration']) ? intval($options['cache_duration']) : 24;

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




