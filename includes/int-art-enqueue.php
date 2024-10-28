<?php

/**
 * Enqueues admin styles and scripts for the Integrate Airtable plugin.
 *
 * This function registers and enqueues the main admin CSS and JavaScript files
 * for use within the WordPress admin dashboard. It uses the file modification
 * time as the version number to ensure that the most recent file changes are loaded.
 *
 * @return void
 */

if( ! function_exists('int_enqueue_admins_externals') ) {
    
    function int_enqueue_admins_externals() {
        wp_enqueue_style( 'int-art-admin-style', INT_ART_PLUGIN_URL . 'assets/admin/css/admin.css', array(), time() , false );
        wp_enqueue_script( 'int-art-admin-script', INT_ART_PLUGIN_URL . 'assets/admin/js/admin.script.js', array(), time(), true );
    }
    add_action( 'admin_enqueue_scripts', 'int_enqueue_admins_externals' );
}


/**
 * Enqueue public styles and scripts for the Integrate Airtable plugin.
 *
 * This function registers and enqueues necessary CSS and JavaScript files
 * for the public area of the WordPress site. Currently, it includes the
 * DataTables CSS and JavaScript files, as well as the main plugin JavaScript
 * file. This function can be extended to add multiple styles and scripts as
 * needed in the future.
 */

if( ! function_exists('int_enqueue_externals') ) {
    function int_enqueue_externals() {
        wp_enqueue_style( 'int-art-datatable-style', INT_ART_PLUGIN_URL . 'assets/public/css/datatable.min.css', array(), time() , false );
        wp_enqueue_script( 'int-art-datatable-script', INT_ART_PLUGIN_URL . 'assets/public/js/datatable.min.js', array('jquery'), time(), false );
        wp_enqueue_script( 'int-art-main-script', INT_ART_PLUGIN_URL . 'assets/public/js/main.script.js', array(), time(), true );
    }
    add_action( 'wp_enqueue_scripts', 'int_enqueue_externals' );
}
