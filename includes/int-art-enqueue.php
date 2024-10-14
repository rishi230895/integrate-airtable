<?php

/**
 * Enqueue admin styles and scripts for the Integrate Airtable plugin.
 *
 * This function registers and enqueues necessary CSS and JavaScript files
 * for the admin area of the WordPress dashboard. Currently, it includes
 * the main admin CSS file. This function can be extended to add multiple
 * styles and scripts as needed in the future.
 */

if( ! function_exists('int_enqueue_admins_externals') ) {
    
    function int_enqueue_admins_externals() {
        wp_enqueue_style( 'int-art-admin-style', INT_ART_PLUGIN_URL . 'assets/admin/css/admin.css', array(), time() , false );
    }
    add_action( 'admin_enqueue_scripts', 'int_enqueue_admins_externals' );
}