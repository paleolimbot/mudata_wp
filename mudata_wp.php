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

// register import page
add_action('admin_menu', 'mudata_create_import_menu');
function mudata_create_import_menu() {
    //create new top-level menu
    add_submenu_page('tools.php', 'mudta import', 'mudata import', 
            'administrator', 'mudata-import', 'mudata_import_page' );
}
function mudata_import_page() {
    include plugin_dir_path(__FILE__) . '/mudata_import_page.php';
}

