<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://go4taste.pl/techlab
 * @since      1.0.0
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/widget
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two example hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/widget
 * @author     Go4Taste Techlab <przemekcichon@gmail.com>
 */
class Go4Taste_Ads_Sidebar_Regular_Widget extends WP_Widget 
{
    /** constructor */
    public function __construct() 
    {
        $widget_ops = array(
            'classname' => 'g4t-ads-sidebar-regular-widget', // Custom CSS class with hyphens
            'description' => __( 'A regular sidebar widget for displaying product ads.', 'go4taste-ads' ),
        );

        // Create the widget using a unique base ID
        parent::__construct(
            'g4t-ads-sidebar-regular-widget', // Base ID with hyphens
            __('G4T Ads Sidebar Regular Widget', 'go4taste-ads'), // Widget name
            $widget_ops // Widget options, including the classname
        );
    }

    /**
     * Get the "p" parameter value from the WordPress options.
     *
     * @since    1.0.0
     */
    public function get_p_parameter_value() 
    {
        $options = get_option('go4taste_ads_options');
        return isset($options['p_parameter_value']) ? $options['p_parameter_value'] : 'default_value';
    }

    /**
     * Get the fallback product ID from the WordPress options.
     *
     * @since    1.0.0
     */
    public function get_fallback_product_id() 
    {
        $options = get_option('go4taste_ads_options');
        return isset($options['fallback_product_id']) ? $options['fallback_product_id'] : '';
    }

    /** @see WP_Widget::widget */
    public function widget($args, $instance) 
    {
        // Extract the widget arguments into individual variables
        extract($args);

        // Get the fallback product ID and "p" parameter value from the options
        $p_value = $this->get_p_parameter_value();
        $fallback_product_id = $this->get_fallback_product_id();

        // Access the plugin instance and retrieve the product ID
        $plugin_instance = Go4Taste_Ads::get_instance();
        $product_id = $plugin_instance->get_public_instance()->get_prestashop_product_id();

        // Use the fallback product ID if no valid product ID is available
        if (empty($product_id)) {
            $product_id = $fallback_product_id;
        }

        // If there's still no valid product ID, do not display the widget
        if (empty($product_id)) {
            return;
        }

        // Get the API settings (API type, URL, and language)
        $options = get_option('go4taste_ads_options');
        $api_type = isset($options['api_type']) ? $options['api_type'] : 'prestashop_1_5';
        $api_url = isset($options['api_url']) ? $options['api_url'] : '';
        $lang = isset($options['prestashop_lang']) ? $options['prestashop_lang'] : 'en';

        // Fetch the product data using the centralized function
        $product_data = $plugin_instance->fetch_product_data($product_id, $p_value, $lang, $api_type, $api_url);

        // If valid product data is retrieved, display the widget
        if ($product_data) {
            // Apply the widget title filter
            $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance);

            // Start the widget output
            echo $before_widget;

            // Display the title if available
            if ($title) {
                echo $before_title . '<a href="' . esc_url($product_data->link) . '">' . esc_html($title) . '</a>' . $after_title;
            }
            
            // Include the public-facing template for displaying the widget content
            include(plugin_dir_path(dirname(__FILE__)) . 'public/partials/go4taste-ads-sidebar-regular-widget-public-display.php');

            // End the widget output
            echo $after_widget;
        }
    }

    /** @see WP_Widget::update */
    public function update($new_instance, $old_instance)
    {
        return $new_instance;
    }

    /** @see WP_Widget::form */
    public function form($instance)
    {
        $title = esc_attr(isset($instance['title']) ? $instance['title'] : '');
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php
    }
}
