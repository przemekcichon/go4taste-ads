<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://go4taste.pl/techlab
 * @since             1.0.0
 * @package           Go4Taste_Ads
 *
 * @wordpress-plugin
 * Plugin Name:       Go4Taste Ads
 * Plugin URI:        https://go4taste.pl/techlab/go4taste-ads
 * Description:       Ads
 * Version:           1.1.1
 * Author:            Go4Taste Techlab
 * Author URI:        https://go4taste.pl/techlab/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       go4taste-ads
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'GO4TASTE_ADS_VERSION', '1.1.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in core/lifecycle/class-go4taste-ads-activator.php
 */
function activate_go4taste_ads() {
	require_once plugin_dir_path( __FILE__ ) . 'core/lifecycle/class-go4taste-ads-activator.php';
	Go4Taste_Ads_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in core/lifecycle/class-go4taste-ads-deactivator.php
 */
function deactivate_go4taste_ads() {
	require_once plugin_dir_path( __FILE__ ) . 'core/lifecycle/class-go4taste-ads-deactivator.php';
	Go4Taste_Ads_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_go4taste_ads' );
register_deactivation_hook( __FILE__, 'deactivate_go4taste_ads' );

/**
 * Autoload dependencies.
 *
 * This line includes the Composer autoloader to ensure that any external libraries
 * or dependencies installed through Composer are loaded and available.
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Feature bootstrap registry.
 */
require_once plugin_dir_path( __FILE__ ) . 'core/bootstrap.php';

/**
 * Load shared boundaries and feature slices.
 */
g4t_bootstrap_features();

