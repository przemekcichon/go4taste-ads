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
	 * Legacy admin functionality instance placeholder.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      object|null    $admin_instance    Deprecated compatibility property.
	 */
	protected $admin_instance;

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
	 * Backward compatible alias for legacy callers.
	 *
	 * @since    1.0.0
	 * @return   object|null
	 */
	public function get_public_instance() {
		return $this->get_admin_instance();
	}

	/**
	 * Get the admin functionality instance.
	 *
	 * @since    1.2.0
	 * @return   object|null
	 */
	public function get_admin_instance() {
		return $this->admin_instance;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Go4Taste_Ads_Loader. Orchestrates the hooks of the plugin.
	 * - Go4Taste_Ads_i18n. Defines internationalization functionality.
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
		// Admin hooks are registered in feature-local slices.

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
	 * Register all of the hooks related to Gutenberg block functionality.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function define_block_hooks() {

		if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
			return;
		}

		$this->loader->add_action( 'init', $this, 'register_blocks' );

		if ( class_exists( 'WP_Theme_JSON_Data' ) ) {
			$this->loader->add_filter( 'wp_theme_json_data_theme', $this, 'enable_border_settings' );
		}

	}

	/**
	 * Ensure border settings are enabled in theme.json so that
	 * __experimentalBorder block supports render their controls.
	 *
	 * Classic themes (without theme.json) do not opt-in to border
	 * settings by default, which hides the UI even when a block
	 * declares support.
	 *
	 * @since  1.2.0
	 * @param  WP_Theme_JSON_Data $theme_json Current theme JSON data.
	 * @return WP_Theme_JSON_Data
	 */
	public function enable_border_settings( $theme_json ) {

		if ( ! is_object( $theme_json ) || ! method_exists( $theme_json, 'update_with' ) ) {
			return $theme_json;
		}

		$new_data = array(
			'version'  => 3,
			'settings' => array(
				'border' => array(
					'color'  => true,
					'radius' => true,
					'style'  => true,
					'width'  => true,
				),
			),
		);

		return $theme_json->update_with( $new_data );

	}

	/**
	 * Register Gutenberg blocks bundled with this plugin.
	 *
	 * @since    1.2.0
	 */
	public function register_blocks() {

		if ( ! function_exists( 'register_block_type_from_metadata' ) || ! class_exists( 'WP_Block_Type' ) ) {
			return;
		}

		$block_path = plugin_dir_path( dirname( __FILE__ ) ) . 'build/blocks/inline-banner';

		if ( ! file_exists( $block_path . '/block.json' ) ) {
			return;
		}

		$block_type = register_block_type_from_metadata(
			$block_path,
			array(
				'render_callback' => array( $this, 'render_inline_banner_block' ),
			)
		);

		if ( ! $block_type instanceof WP_Block_Type ) {
			return;
		}

		$editor_handle = $block_type->editor_script_handles[0] ?? '';
		if ( empty( $editor_handle ) ) {
			return;
		}

		$options = get_option( 'go4taste_ads_options' );
		$max_products = intval( apply_filters( 'g4t_ads_inline_banner_max_products', 0 ) );
		if ( $max_products < 0 ) {
			$max_products = 0;
		}

		wp_localize_script(
			$editor_handle,
			'g4tAdsBlockData',
			array(
				'algoliaAppId'     => isset( $options['algolia_app_id'] ) ? esc_attr( $options['algolia_app_id'] ) : '',
				'algoliaSearchKey' => isset( $options['algolia_api_key'] ) ? esc_attr( $options['algolia_api_key'] ) : '',
				'algoliaIndexName' => 'go4taste_product_list',
				'maxProducts'      => $max_products,
			)
		);

	}

	/**
	 * Render callback for the inline banner block with theme-template override support.
	 *
	 * @since 1.2.0
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param string              $content    Block content.
	 * @param WP_Block            $block      Block instance.
	 * @return string
	 */
	public function render_inline_banner_block( $attributes, $content, $block ) {

		$default_template = plugin_dir_path( dirname( __FILE__ ) ) . 'build/blocks/inline-banner/render.php';
		$theme_template   = locate_template(
			array(
				'go4taste-ads/inline-banner.php',
				'go4taste-ads-inline-banner.php',
			),
			false,
			false
		);

		$template_path = ! empty( $theme_template ) ? $theme_template : $default_template;

		$template_path = apply_filters( 'g4t_ads_inline_banner_template_path', $template_path, $attributes, $content, $block );

		if ( ! is_string( $template_path ) || '' === $template_path || ! file_exists( $template_path ) ) {
			$template_path = $default_template;
		}

		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		ob_start();
		require $template_path;

		return (string) ob_get_clean();

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
