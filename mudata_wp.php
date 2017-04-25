<?php

/**
 * @package mudata_wp
 * @version 0.1
 */
/*
Plugin Name: mudata WP
Plugin URI: http://github.com/paleolimbot/mudata_wp
Description: Use Wordpress as a time-series data repository using the (mostly) universal data structure.
Author: Dewey Dunnington
Version: 0.1
Author URI: http://www.fishandwhistle.net/
*/

// register activation and uninstall hooks
require_once plugin_dir_path(__FILE__) . '/mudata_db.php';
register_activation_hook( __FILE__, 'mudata_db_install' );
register_uninstall_hook(__FILE__, 'mudata_db_uninstall');

require_once plugin_dir_path(__FILE__) . '/mudata_import.php';

// a test function to put in the admin line
function test_func() {
    $output = dummy_import();
    echo "<pre>\n\n";
    var_dump($output);
    echo "\n\n</pre>";
}

// Now we set that function up to execute when the admin_notices action is called
add_action( 'admin_notices', 'test_func' );

// define custom post types
require_once plugin_dir_path(__FILE__) . '/mudata_post_types.php';
