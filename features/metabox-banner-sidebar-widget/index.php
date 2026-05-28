<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/metabox-registration.php';

add_action( 'add_meta_boxes', 'g4t_promoted_product_metabox_add_meta_box' );
add_action( 'save_post', 'g4t_promoted_product_metabox_save_meta_box' );
add_action( 'wp_ajax_g4t_fetch_product_preview', 'g4t_promoted_product_metabox_fetch_product_preview' );
add_action( 'admin_enqueue_scripts', 'g4t_promoted_product_metabox_enqueue_preview_styles' );
