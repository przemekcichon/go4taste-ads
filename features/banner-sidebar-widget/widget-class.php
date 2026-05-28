<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sidebar banner widget kept as a dedicated VSA feature slice.
 */
class Go4Taste_Ads_Sidebar_Regular_Widget extends WP_Widget {
	/** constructor */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'g4t-ads-sidebar-regular-widget',
			'description' => __( 'A regular sidebar widget for displaying product ads.', 'go4taste-ads' ),
		);

		parent::__construct(
			'g4t-ads-sidebar-regular-widget',
			__( 'G4T Ads Sidebar Banner Widget', 'go4taste-ads' ),
			$widget_ops
		);
	}

	/**
	 * Get the "p" parameter value from options.
	 *
	 * @return string
	 */
	public function get_p_parameter_value() {
		$options = g4t_core_get_ads_options();
		return isset( $options['p_parameter_value'] ) ? $options['p_parameter_value'] : 'default_value';
	}

	/**
	 * Get fallback product ID from options.
	 *
	 * @return string
	 */
	public function get_fallback_product_id() {
		$options = g4t_core_get_ads_options();
		return isset( $options['fallback_product_id'] ) ? $options['fallback_product_id'] : '';
	}

	/** @see WP_Widget::widget */
	public function widget( $args, $instance ) {
		extract( $args );

		$fallback_product_id = $this->get_fallback_product_id();
		$options             = g4t_core_get_ads_options();

		$product_id = g4t_core_get_sidebar_widget_product_id();

		if ( empty( $product_id ) ) {
			$product_id = $fallback_product_id;
		}

		if ( empty( $product_id ) ) {
			return;
		}

		$product_data = g4t_core_fetch_product_data( $product_id, $options );

		if ( $product_data ) {
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance );

			echo $before_widget;

			if ( $title ) {
				echo $before_title . '<a href="' . esc_url( $product_data->link ) . '">' . esc_html( $title ) . '</a>' . $after_title;
			}

			include __DIR__ . '/widget-template.php';

			echo $after_widget;
		}
	}

	/** @see WP_Widget::update */
	public function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	/** @see WP_Widget::form */
	public function form( $instance ) {
		$title = esc_attr( isset( $instance['title'] ) ? $instance['title'] : '' );
		?>
			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<?php
	}
}
