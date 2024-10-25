<?php

// Check if the function 'int_art_show_data' already exists to avoid redeclaration errors
if( ! function_exists('int_art_show_data') ) {

    /**
     * int_art_show_data
     * 
     * This function is used to display data from a custom post type via a shortcode.
     * It can either fetch specific meta fields if passed as attributes, or all meta keys by default.
     * 
     * @param array $attr Shortcode attributes for filtering post data by meta fields.
     * @return string $html HTML content to display the data, fetched via WP_Query.
     */
    function int_art_show_data($attr) {

        /** 
         * Flag to determine if specific meta fields have been passed through shortcode attributes.
         * Defaults to false, and only becomes true if meta fields are provided in the shortcode. 
         */  
        $is_meta_field_exist = false;     

        // Initialize an empty array to store the meta keys to be fetched.
        $all_meta_keys = []; 


        $stored_meta_keys = int_art_get_meta_keys();


        // If attributes are passed, process them to extract meta fields
        if( $attr ) {
            // Ensure attributes are passed as an array
            if( is_array( $attr ) ) {   
                // Initialize an empty array to hold attribute keys
                $attr_keys = [];

                // Loop through each attribute key-value pair
                foreach( $attr as $key => $col ) {

                    // Check if 'meta_field' attribute is passed and contains a value
                    if(  $key == 'meta_field' ) {
                        if( $col ) {
                            // Split the comma-separated meta field names into an array
                            $temp_exploded = explode("," , $col);
                            
                            // Ensure the result is a valid array
                            if( $temp_exploded && is_array( $temp_exploded ) ) {
                                $temp_col_names = [];

                                // Loop through each column name, clean up any spaces, and add to array
                                foreach( $temp_exploded as $col_name ) {
                                    if( $col_name ) {
                                        $temp_col_names[] = trim($col_name);
                                    }
                                }

                                /** 
                                 * If valid column names are found, convert them to actual meta keys 
                                 * Meta keys are prefixed with 'int_art_' followed by the sanitized column name.
                                 */
                                if( $temp_col_names ) {
                                    $is_meta_field_exist = true;
                                    foreach( $temp_col_names as $key ) {
                                        $attr_keys[] = 'int_art_' . int_separate_underscore( $key );
                                    }
                                }
                            }
                        }
                    }
                }
                // Set the processed attribute keys as meta keys to be fetched
                $all_meta_keys = $attr_keys;
            }
        } else {
            /** 
             * If no attributes are passed, fetch all available meta keys.
             * This is the default behavior when the shortcode is used without any parameters.
             */
            $all_meta_keys = int_art_get_meta_keys();
        }


        // Define query arguments for fetching 'air-sync' post type data
        $args = [
            'post_type'         => 'air-sync',
            'post_status'       => 'publish',
            'posts_per_page'    => -1
        ];
        
        // Fetch posts matching the query arguments using WP_Query
        $post_data = new WP_Query($args);

        ob_start();

        // Include the template file to handle data rendering into a table format
        require_once INT_ART_PLUGIN_PATH . 'templates/int-art-datatable.php';

        // Capture the output of the template file and store it as $html
        $html = ob_get_clean();

        // Return the HTML content for display
        return $html;
    }

    // Register the shortcode 'int_art_show_data' to execute this function
    add_shortcode('int_art_get_meta_data_table', 'int_art_show_data');
}


/** This shortcode will return meta data  */

if( ! function_exists('int_art_get_meta_field_data') ) {
    function int_art_get_meta_field_data($attr) {

        if( ! $attr ) return '';
        if( is_array( $attr ) ) {
            if( array_key_exists('meta_field' , $attr) && array_key_exists('post_id' , $attr)) {
                $meta_key = $attr['meta_field'];
                $post_id  = (int)$attr['post_id'];
                if( $meta_key ) {
                    $meta_key = trim($meta_key);
                    $meta_key = 'int_art_' . int_separate_underscore( $meta_key );
                    return get_post_meta( $post_id  ,$meta_key , true  );
                }
                return '';
            }
        }
        return '';
    }
    add_shortcode('int_art_get_meta_value', 'int_art_get_meta_field_data');
}




