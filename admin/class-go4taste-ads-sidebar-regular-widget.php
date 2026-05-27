<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Backward compatibility shim: keep legacy include path working.
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'features/legacy-sidebar-widget/widget-class.php';
