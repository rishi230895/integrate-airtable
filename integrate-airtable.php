<?php
/*
 * Plugin Name:       Integrate Airtable
 * Description:       This plugin is developed to make convenient and easy integration with your WordPress website.
 * Version:           1.0.0
 * Author:            Believin-Technologies Pvt Ltd (Suraj Prakash)
 * Author URI:        https://believintech.com
 * Text Domain:       int-airtable
 * Domain Path:       /languages
 */

/** 
 * Prevent direct access to this file 
 * Checks if the script is being accessed directly and exits if it is.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

/** 
 * Define constants for plugin paths 
 * This sets up constants for the plugin's file path and URL for easier reference throughout the code.
 */
if ( ! defined( 'INT_ART_PLUGIN_PATH' ) ) {
    // Define the path to the plugin directory
    define('INT_ART_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if ( ! defined( 'INT_ART_PLUGIN_URL' ) ) {
    // Define the URL for the plugin directory
    define( 'INT_ART_PLUGIN_URL', plugin_dir_url(__FILE__) );
}

if ( ! defined( 'INT_ART_TEXT_DOMAIN' ) ) {
    // Define the text domain for translation
    define( 'INT_ART_TEXT_DOMAIN', 'int-airtable' );
}

/** 
 * Include required files for the plugin functionality 
 * This section includes all necessary files that provide the core functionality of the plugin.
 */
require_once INT_ART_PLUGIN_PATH . 'vendor/autoload.php'; // Autoload dependencies using Composer
require_once INT_ART_PLUGIN_PATH . 'hooks/int-art-hooks.php'; // Include hooks
require_once INT_ART_PLUGIN_PATH . 'includes/int-art-airt.cpt.php'; // Include custom post type definitions
require_once INT_ART_PLUGIN_PATH . 'includes/int-art-enqueue.php'; // Include scripts and styles for the plugin
require_once INT_ART_PLUGIN_PATH . 'includes/int-art-common.php'; // Include common functions used throughout the plugin
require_once INT_ART_PLUGIN_PATH . 'includes/int-art-menu.php'; // Include menu definitions for the plugin settings page

/** 
 * Define plugin activation function 
 * This function will be executed when the plugin is activated. Currently, it is a placeholder.
 */
if ( ! function_exists('int_art_activator') ) {
    function int_art_activator() {
        // Code to run on plugin activation (if needed)
    }
}

// Register the activation hook for the plugin
register_deactivation_hook( __FILE__, 'int_art_activator' );

/** 
 * Add a settings link in the plugins list for quick access 
 * This function adds a link to the plugin's settings page in the WordPress plugins menu.
 */
if ( ! function_exists('int_art_plugin_settings_link') ) {
    function int_art_plugin_settings_link($links) {
        // Create a settings link for the plugin
        $settings_link = '<a href="' . admin_url('admin.php?page=int_airtable_settings') . '">' . __('Settings') . '</a>';
        // Add the settings link to the beginning of the links array
        array_unshift($links, $settings_link);
        return $links;
    }   
}

// Add the settings link to the plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'int_art_plugin_settings_link');
