<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// define custom post types
add_action( 'init', 'mudata_create_post_types', 0);
function mudata_create_post_types() {
    // create post types
    mudata_register_post_type('dataset', 'datasets', 'Dataset', 'Datasets', 'dashicons-portfolio', 5);
    mudata_register_post_type('location', 'locations', 'Location', 'Locations', 'dashicons-location', 6);
    mudata_register_post_type('param', 'params', 'Parameter', 'Parameters', 'dashicons-portfolio', 7);
}

// define taxonomies
add_action( 'init', 'mudata_create_taxonomies', 0 );
function mudata_create_taxonomies() {
    mudata_register_taxonomy('subset', 'Subset', 'Subsets', array('dataset', 'location', 'param'));
}



// generic post type creator, used for location, dataset, and param types
function mudata_register_post_type($slug, $slug_plural, $title, $title_plural, $icon, $position) {
    // Register Custom Post Type

    $labels = array(
            'name'                  => _x( $title_plural, 'Post Type General Name', 'text_domain' ),
            'singular_name'         => _x( $title, 'Post Type Singular Name', 'text_domain' ),
            'menu_name'             => __( $title_plural, 'text_domain' ),
            'name_admin_bar'        => __( $title, 'text_domain' ),
            'archives'              => __( sprintf('%s Archives', $title), 'text_domain' ),
            'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
            'all_items'             => __( sprintf('All %s', $title_plural), 'text_domain' ),
            'add_new_item'          => __( sprintf('Add New %s', $title), 'text_domain' ),
            'add_new'               => __( 'Add New', 'text_domain' ),
            'new_item'              => __( sprintf('New %s', $title), 'text_domain' ),
            'edit_item'             => __( sprintf('Edit %s', $title), 'text_domain' ),
            'update_item'           => __( sprintf('Update %s', $title), 'text_domain' ),
            'view_item'             => __( sprintf('View %s', $title), 'text_domain' ),
            'search_items'          => __( sprintf('Search %s', $title_plural), 'text_domain' ),
            'not_found'             => __( 'Not found', 'text_domain' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
            'featured_image'        => __( 'Featured Image', 'text_domain' ),
            'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
            'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
            'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
            'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
            'items_list'            => __( 'Items list', 'text_domain' ),
            'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
            'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
    );
    $rewrite = array(
            'slug'                  => $slug
    );
    $args = array(
            'label'                 => __( $title, 'text_domain' ),
            'description'           => __( '$title type.', 'text_domain' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields', ),
            'taxonomies'            => array( 'people', 'genres', ' keywords', 'shelves' ),
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_icon'             => $icon,
            'menu_position'         => $position,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => $slug_plural,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'rewrite'               => $rewrite,
            'capability_type'       => 'post',
    );
    register_post_type( $slug, $args );

}

// create a taxonomy, used now just for the subset taxonomy
function mudata_register_taxonomy($slug, $title, $title_plural, $post_types) {
    
	// Add new taxonomy, make it hierarchical (like categories)
	$labels = array(
		'name'              => _x( $title_plural, 'taxonomy general name', 'text_domain' ),
		'singular_name'     => _x( $title, 'taxonomy singular name', 'text_domain' ),
		'search_items'      => __( sprintf('Search %s', $title_plural), 'text_domain' ),
		'all_items'         => __( sprintf('All %s', $title_plural), 'text_domain' ),
		'parent_item'       => __( sprintf('Parent %s', $title), 'text_domain' ),
		'parent_item_colon' => __( sprintf('Parent %s:', $title), 'text_domain' ),
		'edit_item'         => __( sprintf('Edit %s', $title), 'text_domain' ),
		'update_item'       => __( sprintf('Update %s', $title), 'text_domain' ),
		'add_new_item'      => __( sprintf('Add New %s', $title), 'text_domain' ),
		'new_item_name'     => __( sprintf('New %s Name', $title), 'text_domain' ),
		'menu_name'         => __( $title_plural, 'text_domain' ),
	);
	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => $title ),
	);
	register_taxonomy( $slug, $post_types, $args );
}

