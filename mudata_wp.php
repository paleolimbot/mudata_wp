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

// define custom post types
require_once plugin_dir_path(__FILE__) . '/mudata_post_types.php';
