<?php

/**
 * i18n Core Module — Feature-agnostic internationalization
 *
 * Loads plugin text domain for all features to use without dependencies.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/core/i18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'core/i18n/loader.php';

/**
 * Register i18n loading hook.
 *
 * Ensures text domain is loaded early so all features can use it.
 *
 * @since 1.2.0
 */
add_action( 'plugins_loaded', 'g4t_load_plugin_textdomain' );
