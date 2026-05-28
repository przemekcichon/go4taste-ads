<?php

/**
 * Text domain loader — core i18n module
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/core/i18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'g4t_load_plugin_textdomain' ) ) {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * This function is registered early (plugins_loaded) so that all features
	 * and modules can rely on the text domain being loaded.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Moved to core i18n module.
	 *
	 * @return void
	 */
	function g4t_load_plugin_textdomain() {

		load_plugin_textdomain(
			'go4taste-ads',
			false,
			dirname( dirname( dirname( plugin_basename( __FILE__ ) ) ) ) . '/languages/'
		);

	}
}
