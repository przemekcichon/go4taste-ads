<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/sync-runner.php';
require_once __DIR__ . '/sync-ajax-handlers.php';

add_action( 'wp_ajax_g4t_sync_start', 'g4t_product_catalog_sync_ajax_start' );
add_action( 'wp_ajax_g4t_sync_batch', 'g4t_product_catalog_sync_ajax_batch' );
add_action( 'wp_ajax_g4t_sync_status', 'g4t_product_catalog_sync_ajax_status' );
add_action( 'wp_ajax_g4t_sync_unlock', 'g4t_product_catalog_sync_ajax_unlock' );
