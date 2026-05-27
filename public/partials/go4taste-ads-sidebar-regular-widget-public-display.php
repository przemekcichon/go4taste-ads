<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Backward compatibility shim for legacy template include path.
require plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'features/legacy-sidebar-widget/widget-template.php';

