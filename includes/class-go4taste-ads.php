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
     * The public-facing functionality instance of the plugin.
     *
     * This property holds the instance of the public-facing functionality class
     * which is responsible for managing front-end features of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Go4Taste_Ads_Public    $public_instance    The public-facing functionality instance.
     */
    protected $public_instance;

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
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_block_hooks();

        // Initialize the public instance
        $this->public_instance = new Go4Taste_Ads_Admin($this->get_plugin_name(), $this->get_version());
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
     * Get the public-facing functionality instance.
     *
     * This method returns the instance of the public-facing functionality class.
     *
     * @since    1.0.0
     * @return   Go4Taste_Ads_Public    The public-facing functionality instance.
     */
    public function get_public_instance() {
        return $this->public_instance;
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Go4Taste_Ads_Loader. Orchestrates the hooks of the plugin.
	 * - Go4Taste_Ads_i18n. Defines internationalization functionality.
	 * - Go4Taste_Ads_Admin. Defines all hooks for the admin area.
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
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-go4taste-ads-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-go4taste-ads-admin.php';

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
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
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
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Go4Taste_Ads_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Go4Taste_Ads_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Go4Taste_Ads_Admin( $this->get_plugin_name(), $this->get_version() );

		// Enqueue styles and scripts
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Add the meta box
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_box' );

        // Save the meta box data
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_box' );

		// Add the custom hook to load the product ID function
		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'load_product_id_function' );

		// Add this function to register widgets
		$this->loader->add_action( 'widgets_init', $plugin_admin, 'register_widgets' );

		// Add the admin menu and settings page
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'page_init' );

		// Handle the update product IDs action from the admin page
		$this->loader->add_action( 'admin_init', $plugin_admin, 'handle_update_product_ids' );

		// Register AJAX handler for logged-in users
		$this->loader->add_action( 'wp_ajax_g4t_fetch_product_preview', $plugin_admin, 'fetch_product_preview' );

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_admin_preview_styles');

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
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to Gutenberg block functionality.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function define_block_hooks() {

		$this->loader->add_action( 'init', $this, 'register_blocks' );

	}

	/**
	 * Register Gutenberg blocks bundled with this plugin.
	 *
	 * @since    1.2.0
	 */
	public function register_blocks() {

		$block_path = plugin_dir_path( dirname( __FILE__ ) ) . 'build/blocks/inline-banner';

		if ( ! file_exists( $block_path . '/block.json' ) ) {
			return;
		}

		register_block_type( $block_path );

		$options = get_option( 'go4taste_ads_options' );
		wp_localize_script(
			'go4taste-ads-inline-banner-editor-script',
			'g4tAdsBlockData',
			array(
				'algoliaAppId'     => isset( $options['algolia_app_id'] ) ? esc_attr( $options['algolia_app_id'] ) : '',
				'algoliaSearchKey' => isset( $options['algolia_api_key'] ) ? esc_attr( $options['algolia_api_key'] ) : '',
				'algoliaIndexName' => 'go4taste_product_list',
			)
		);

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
