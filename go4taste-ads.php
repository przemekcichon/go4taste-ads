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
 * Version:           1.1.0
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
define( 'GO4TASTE_ADS_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-go4taste-ads-activator.php
 */
function activate_go4taste_ads() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-go4taste-ads-activator.php';
	Go4Taste_Ads_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-go4taste-ads-deactivator.php
 */
function deactivate_go4taste_ads() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-go4taste-ads-deactivator.php';
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
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-go4taste-ads.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_go4taste_ads() {

	$plugin = Go4Taste_Ads::get_instance();
	$plugin->run();

}
run_go4taste_ads();
