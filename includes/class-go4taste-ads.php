<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://go4taste.pl/techlab
 * @since      1.0.0
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/includes
 * @author     Go4Taste Techlab <przemekcichon@gmail.com>
 */
class Go4Taste_Ads{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Go4Taste_Ads_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
     * The single instance of the class.
     *
     * This property ensures that only one instance of the class is created (Singleton pattern).
     *
     * @since    1.0.0
     * @access   private
     * @var      Go4Taste_Ads    $instance    The single instance of this class.
     */
    private static $instance = null;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site. Initialize the public-facing functionality.
     *
     * @since    1.0.0
     */
    private function __construct() {
        $this->plugin_name = 'go4taste-ads';
        $this->version = '1.0.0';

        $this->load_dependencies();
        $this->define_public_hooks();
    }

    /**
     * Retrieve the single instance of the class.
     *
     * This method ensures that only one instance of the class is created. If an instance
     * already exists, it returns that instance; otherwise, it creates a new one.
     *
     * @since    1.0.0
     * @return   Go4Taste_Ads    The single instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Go4Taste_Ads_Loader. Orchestrates the hooks of the plugin.
	 * - Go4Taste_Ads_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-go4taste-ads-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-go4taste-ads-public.php';

		/**
		 * The class responsible for fetching data from various APIs
		 */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-go4taste-ads-api-fetcher.php';

		// Load the WP-CLI command class, if WP-CLI is available.
		if ( defined( 'WP_CLI' ) && true === constant( 'WP_CLI' ) ) {
			/**
			 * Load the WP-CLI command file.
			 *
			 * This file contains the WP-CLI command that allows users to update
			 * post meta fields with product IDs extracted from shortcodes.
			 *
			 * The command can be run using WP-CLI with options to specify the ad type,
			 * source, shortcode name, and to use features like dry-run and force-overwrite.
			 *
			 * @since 1.0.0
			 */
			require_once plugin_dir_path( __FILE__ ) . 'class-go4taste-ads-wp-cli-command.php';
		}

		$this->loader = new Go4Taste_Ads_Loader();

	}

	/**

	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Go4Taste_Ads_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );

	}



	/**
     * Fetch product data using the API Fetcher class.
     *
     * @since 1.0.0
     * @param string $product_id The product ID.
     * @param string $p_value The "p" parameter for PrestaShop APIs.
     * @param string $lang The language for PrestaShop APIs.
     * @param string $api_type The API type.
     * @param string $api_url The API base URL.
     * @return object|false The product data object, or false if not found.
     */
    public function fetch_product_data($product_id, $p_value, $lang, $api_type, $api_url) {
        // Use the API Fetcher class to retrieve product data
        $api_fetcher = new Go4Taste_Ads_API_Fetcher();
        return $api_fetcher->fetch_product_data($product_id, $p_value, $lang, $api_type, $api_url);
    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Go4Taste_Ads_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
