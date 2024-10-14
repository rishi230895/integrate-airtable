<?php

/**
 * Register Custom Post Type (CPT) for Air Sync Integration
 *
 * This file defines and registers a custom post type named "Air Sync" 
 * to facilitate the creation and management of air sync posts in WordPress. 
 * Additionally, it registers a hierarchical taxonomy called "Air Sync Category" 
 * for categorizing the air sync posts.
 *
 * The custom post type supports various features, including:
 * - Title
 * - Editor
 * - Thumbnail (Featured Image)
 * - Excerpt
 * - Custom Fields
 * - Comments
 * - Revisions
 *
 * The taxonomy allows for the organization of air sync posts into 
 * categories, providing better content management and organization.
 *
 * Text domain for translation is set to 'int-airtable'.
 * 
 * Usage:
 * - Hook into the 'init' action to register the custom post type and taxonomy.
 * 
 */



if( ! function_exists("int_register_air_sync_cpt") ) {
    
    function int_register_air_sync_cpt() {
        
        $labels = array(
            'name'                  => _x('Air Sync', 'Post type general name', INT_ART_TEXT_DOMAIN),
            'singular_name'         => _x('Air Sync', 'Post type singular name', INT_ART_TEXT_DOMAIN),
            'menu_name'             => _x('Air Sync', 'Admin Menu text', INT_ART_TEXT_DOMAIN),
            'name_admin_bar'        => _x('Air Sync', 'Add New on Toolbar', INT_ART_TEXT_DOMAIN),
            'add_new'               => __('Add New', INT_ART_TEXT_DOMAIN),
            'add_new_item'          => __('Add New Air Sync', INT_ART_TEXT_DOMAIN),
            'new_item'              => __('New Air Sync', INT_ART_TEXT_DOMAIN),
            'edit_item'             => __('Edit Air Sync', INT_ART_TEXT_DOMAIN),
            'view_item'             => __('View Air Sync', INT_ART_TEXT_DOMAIN),
            'all_items'             => __('All Air sync', INT_ART_TEXT_DOMAIN),
            'search_items'          => __('Search Air sync', INT_ART_TEXT_DOMAIN),
            'parent_item_colon'     => __('Parent Air sync:', INT_ART_TEXT_DOMAIN),
            'not_found'             => __('No air sync found.', INT_ART_TEXT_DOMAIN),
            'not_found_in_trash'    => __('No air sync found in Trash.', INT_ART_TEXT_DOMAIN),
            'featured_image'        => _x('Air Sync Cover Image', 'Overrides the “Featured Image” phrase', INT_ART_TEXT_DOMAIN),
            'set_featured_image'    => _x('Set cover image', 'Overrides the “Set featured image” phrase', INT_ART_TEXT_DOMAIN),
            'remove_featured_image' => _x('Remove cover image', 'Overrides the “Remove featured image” phrase', INT_ART_TEXT_DOMAIN),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the “Use as featured image” phrase', INT_ART_TEXT_DOMAIN),
            'archives'              => _x('Air Sync Archives', 'The post type archive label', INT_ART_TEXT_DOMAIN),
            'insert_into_item'      => _x('Insert into air sync', 'Overrides the “Insert into post” phrase', INT_ART_TEXT_DOMAIN),
            'uploaded_to_this_item' => _x('Uploaded to this air sync', 'Overrides the “Uploaded to this post” phrase', INT_ART_TEXT_DOMAIN),
            'filter_items_list'     => _x('Filter air sync list', 'Screen reader text for the filter link', INT_ART_TEXT_DOMAIN),
            'items_list_navigation' => _x('Air sync list navigation', 'Screen reader text for the pagination', INT_ART_TEXT_DOMAIN),
            'items_list'            => _x('Air sync list', 'Screen reader text for the items list', INT_ART_TEXT_DOMAIN),
        );

        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'air-sync'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-admin-site-alt3',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'revisions'),
            'show_in_rest'       => true,
        );

   
        register_post_type('air-sync', $args);

        $taxonomy_labels = array(
            'name'              => _x('Categories', 'taxonomy general name', INT_ART_TEXT_DOMAIN),
            'singular_name'     => _x('Category', 'taxonomy singular name', INT_ART_TEXT_DOMAIN),
            'search_items'      => __('Search Categories', INT_ART_TEXT_DOMAIN),
            'all_items'         => __('All Categories', INT_ART_TEXT_DOMAIN),
            'parent_item'       => __('Parent Category', INT_ART_TEXT_DOMAIN),
            'parent_item_colon' => __('Parent Category:', INT_ART_TEXT_DOMAIN),
            'edit_item'         => __('Edit Category', INT_ART_TEXT_DOMAIN),
            'update_item'       => __('Update Category', INT_ART_TEXT_DOMAIN),
            'add_new_item'      => __('Add New Category', INT_ART_TEXT_DOMAIN),
            'new_item_name'     => __('New Category Name', INT_ART_TEXT_DOMAIN),
            'menu_name'         => __('Categories', INT_ART_TEXT_DOMAIN),
        );

        $taxonomy_args = array(
            'hierarchical'      => true,
            'labels'            => $taxonomy_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'air-sync-category'),
        );

        register_taxonomy('air-sync-category', array('air-sync'), $taxonomy_args);
    }

    add_action('init', 'int_register_air_sync_cpt');

}

