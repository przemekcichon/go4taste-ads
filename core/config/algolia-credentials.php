<?php
/**
 * Algolia credential resolver (core boundary).
 *
 * Resolves Algolia credentials with the following precedence:
 *   1. wp-config.php constants (per-environment override).
 *   2. Plugin options stored in the database.
 *
 * This lets production and development use different keys so a dev sync
 * never overwrites production Algolia data.
 *
 * Constants (define in wp-config.php):
 *   define( 'GO4TASTE_ADS_ALGOLIA_APP_ID',        '...' );
 *   define( 'GO4TASTE_ADS_ALGOLIA_API_KEY',       '...' ); // search-only (public)
 *   define( 'GO4TASTE_ADS_ALGOLIA_ADMIN_API_KEY', '...' ); // admin/write (backend only)
 *
 * @package Go4Taste_Ads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_ads_resolve_algolia_credential' ) ) {
	/**
	 * Resolve a single Algolia credential: constant first, then DB option.
	 *
	 * Credentials are never passed through esc_attr()/sanitize callbacks that
	 * could HTML-encode and corrupt the key; only trim() is applied.
	 *
	 * @param string $constant_name wp-config.php constant name.
	 * @param string $option_key    Key inside the go4taste_ads_options array.
	 * @return string Resolved credential, or empty string when unavailable.
	 */
	function g4t_ads_resolve_algolia_credential( $constant_name, $option_key ) {
		if ( defined( $constant_name ) ) {
			$constant_value = trim( (string) constant( $constant_name ) );
			if ( '' !== $constant_value ) {
				return $constant_value;
			}
		}

		$options = get_option( 'go4taste_ads_options' );
		if ( is_array( $options ) && isset( $options[ $option_key ] ) ) {
			return trim( (string) $options[ $option_key ] );
		}

		return '';
	}
}

if ( ! function_exists( 'g4t_ads_get_algolia_app_id' ) ) {
	/**
	 * Resolve the Algolia Application ID.
	 *
	 * @return string
	 */
	function g4t_ads_get_algolia_app_id() {
		return g4t_ads_resolve_algolia_credential( 'GO4TASTE_ADS_ALGOLIA_APP_ID', 'algolia_app_id' );
	}
}

if ( ! function_exists( 'g4t_ads_get_algolia_search_key' ) ) {
	/**
	 * Resolve the Algolia search-only (public) API key.
	 *
	 * @return string
	 */
	function g4t_ads_get_algolia_search_key() {
		return g4t_ads_resolve_algolia_credential( 'GO4TASTE_ADS_ALGOLIA_API_KEY', 'algolia_api_key' );
	}
}

if ( ! function_exists( 'g4t_ads_get_algolia_admin_key' ) ) {
	/**
	 * Resolve the Algolia admin/write API key (backend only).
	 *
	 * @return string
	 */
	function g4t_ads_get_algolia_admin_key() {
		return g4t_ads_resolve_algolia_credential( 'GO4TASTE_ADS_ALGOLIA_ADMIN_API_KEY', 'algolia_admin_api_key' );
	}
}

if ( ! function_exists( 'g4t_ads_is_algolia_credential_constant_defined' ) ) {
	/**
	 * Check whether a given Algolia credential is overridden by a wp-config.php constant.
	 *
	 * Used by the admin UI to flag fields as managed in wp-config.php.
	 *
	 * @param string $which One of 'app_id', 'search_key', 'admin_key'.
	 * @return bool True when the matching constant is defined and non-empty.
	 */
	function g4t_ads_is_algolia_credential_constant_defined( $which ) {
		switch ( $which ) {
			case 'app_id':
				$constant_name = 'GO4TASTE_ADS_ALGOLIA_APP_ID';
				break;
			case 'search_key':
				$constant_name = 'GO4TASTE_ADS_ALGOLIA_API_KEY';
				break;
			case 'admin_key':
				$constant_name = 'GO4TASTE_ADS_ALGOLIA_ADMIN_API_KEY';
				break;
			default:
				return false;
		}

		return defined( $constant_name ) && '' !== trim( (string) constant( $constant_name ) );
	}
}
